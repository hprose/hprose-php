<?php

require_once('hprose/Hprose.php');

class Chat {
    private $gens = array();
    private $messages = array();
    private $getMessage = array();
    private $getUpdateUsers = array();
    private $maybeOffline = array();
    private $timer = null;

    private function messageGenerator($who) {
        while (true) {
            $message = yield;
            if ($message === null) {
                break;
            }
            if (isset($this->getMessage[$who])) {
                $getMessage = $this->getMessage[$who];
                unset($this->getMessage[$who]);
                swoole_timer_clear($getMessage->timer);
                $oldMessage = '';
                if (isset($this->messages[$who])) {
                    $oldMessage = $this->messages[$who];
                    unset($this->messages[$who]);
                }
                $getMessage->completer->complete($oldMessage . $message);
            }
            else {
                if (isset($this->messages[$who])) {
                    $this->messages[$who] .= $message;
                }
                else {
                    $this->messages[$who] = $message;
                }
            }
        }
    }

    private function sendUsers() {
        $users = $this->getAllUsers();
        foreach ($users as $user) {
            if (isset($this->getUpdateUsers[$user])) {
                $getUpdateUsers = $this->getUpdateUsers[$user];
                unset($this->getUpdateUsers[$user]);
                swoole_timer_clear($getUpdateUsers->timer);
                $getUpdateUsers->completer->complete($users);
            }
        }
    }

    private function online($who) {
        if (!isset($this->gens[$who])) {
            $this->gens[$who] = $this->messageGenerator($who);
            $this->sendUsers();
            $this->broadcast($who, $who . " is online.");
        }
        if ($this->timer == null) {
            $this->timer = swoole_timer_tick(3000, function() {
                try {
                    $users = $this->getAllUsers();
                    foreach ($users as $user) {
                        if (!isset($this->getMessage[$user]) &&
                            !isset($this->getUpdateUsers[$user])) {
                            if (!isset($this->maybeOffline[$user])) {
                                $this->maybeOffline[$user] = true;
                            }
                            else {
                                $this->offline($user);
                            }
                        }
                        else {
                            if (isset($this->maybeOffline[$user])) {
                                unset($this->maybeOffline[$user]);
                            }
                        }
                    }
                }
                catch (\Exception $e) {}
            });
        }
    }

    private function offline($who) {
        if (isset($this->gens[$who])) {
            $this->broadcast($who, $who . " is offline.");
            $gen = $this->gens[$who];
            unset($this->gens[$who]);
            unset($this->maybeOffline[$who]);
            unset($this->messages[$who]);
            $gen->send(null);
            $this->sendUsers();
        }
    }

    public function getAllUsers() {
        return array_keys($this->gens);
    }

    public function updateUsers($who) {
        $this->online($who);
        $getUpdateUsers = new StdClass();
        $getUpdateUsers->completer = new HproseCompleter();
        $getUpdateUsers->timer = swoole_timer_after(30000, function() use ($who) {
            try {
                if (isset($this->getUpdateUsers[$who])) {
                    $getUpdateUsers = $this->getUpdateUsers[$who];
                    unset($this->getUpdateUsers[$who]);
                    $getUpdateUsers->completer->complete($this->getAllUsers());
                }
            }
            catch (\Exception $e) {}
        });
        $this->getUpdateUsers[$who] = $getUpdateUsers;
        return $getUpdateUsers->completer->future();
    }

    public function message($who) {
        $this->online($who);
        if (isset($this->messages[$who])) {
            $message = $this->messages[$who];
            unset($this->messages[$who]);
            return $message;
        }
        $getMessage = new StdClass();
        $getMessage->completer = new HproseCompleter();
        $getMessage->timer = swoole_timer_after(30000, function() use ($who) {
            try {
                if (isset($this->getMessage[$who])) {
                    $getMessage = $this->getMessage[$who];
                    unset($this->getMessage[$who]);
                    $getMessage->completer->complete(null);
                }
            }
            catch (\Exception $e) {}
        });
        $this->getMessage[$who] = $getMessage;
        return $getMessage->completer->future();
    }

    public function sendMessage($from, $to, $message) {
        $this->online($from);
        if (!isset($this->gens[$to])) {
            return $to . "is offline.";
        }
        $this->gens[$to]->send($from . " talk to me: " . $message . "\r\n");
        $this->gens[$from]->send("I talk to " . $to . ": " . $message . "\r\n");
    }

    public function broadcast($from, $message) {
        $this->online($from);
        foreach ($this->gens as $gen) {
            $gen->send($from . " said: " . $message . "\r\n");
        }
    }
}

$server = new HproseSwooleServer("ws://0.0.0.0:8080", SWOOLE_BASE);
$server->setErrorTypes(E_ALL);
$server->setDebugEnabled(true);
$server->setCrossDomainEnabled(true);
$server->add(new Chat());
$server->start();
