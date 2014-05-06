<?php
/**********************************************************\
|                                                          |
|                          hprose                          |
|                                                          |
| Official WebSite: http://www.hprose.com/                 |
|                   http://www.hprose.net/                 |
|                   http://www.hprose.org/                 |
|                                                          |
\**********************************************************/

/**********************************************************\
 *                                                        *
 * HproseClient.php                                       *
 *                                                        *
 * hprose client library for php5.                        *
 *                                                        *
 * LastModified: Mar 22, 2014                             *
 * Author: Ma Bingyao <andot@hprose.com>                  *
 *                                                        *
\**********************************************************/

require_once('HproseCommon.php');
require_once('HproseIO.php');

class HproseProxy {
    private $client;
    private $namespace;
    public function __construct($client, $namespace = '') {
        $this->client = $client;
        $this->namespace = $namespace;
    }
    public function __call($function, $arguments) {
        $function = $this->namespace . $function;
        return $this->client->invoke($function, $arguments);
    }
    public function __get($name) {
        return new HproseProxy($this->client, $this->namespace . $name . '_');
    }
}

abstract class HproseClient {
    protected $url;
    private $filters;
    private $simple;
    protected abstract function sendAndReceive($request);
    public function __construct($url = '') {
        $this->useService($url);
        $this->filters = array();
        $this->simple = false;
    }
    public function useService($url = '', $namespace = '') {
        if ($url) {
            $this->url = $url;
        }
        return new HproseProxy($this, $namespace);
    }
    public function invoke($functionName, &$arguments = array(), $byRef = false, $resultMode = HproseResultMode::Normal, $simple = NULL) {
        if ($simple === NULL) $simple = $this->simple;
        $stream = new HproseStringStream(HproseTags::TagCall);
        $hproseWriter = new HproseWriter($stream, $simple);
        $hproseWriter->writeString($functionName);
        if (count($arguments) > 0 || $byRef) {
            $hproseWriter->reset();
            $hproseWriter->writeList($arguments);
            if ($byRef) {
                $hproseWriter->writeBoolean(true);
            }
        }
        $stream->write(HproseTags::TagEnd);
        $request = $stream->toString();
        $count = count($this->filters);
        if($count){
            foreach($this->filters as $i => $filter){
                $request = $this->$filter->outputFilter($request, $this);
            }
        }
        $stream->close();
        $response = $this->sendAndReceive($request);
        for ($i = $count - 1; $i >= 0; $i--) {
            $response = $this->filters[$i]->inputFilter($response, $this);
        }
        if ($resultMode == HproseResultMode::RawWithEndTag) {
            return $response;
        }
        if ($resultMode == HproseResultMode::Raw) {
            return substr($response, 0, -1);
        }
        $stream = new HproseStringStream($response);
        $hproseReader = new HproseReader($stream);
        $result = NULL;
        while (($tag = $stream->getc()) !== HproseTags::TagEnd) {
            switch ($tag) {
                case HproseTags::TagResult:
                    if ($resultMode == HproseResultMode::Serialized) {
                        $result = $hproseReader->readRaw()->toString();
                    }
                    else {
                        $hproseReader->reset();
                        $result = &$hproseReader->unserialize();
                    }
                    break;
                case HproseTags::TagArgument:
                    $hproseReader->reset();
                    $args = &$hproseReader->readList();
                    for ($i = 0; $i < count($arguments); $i++) {
                        $arguments[$i] = &$args[$i];
                    }
                    break;
                case HproseTags::TagError:
                    $hproseReader->reset();
                    throw new HproseException($hproseReader->readString());
                    break;
                default:
                    throw new HproseException("Wrong Response: \r\n" . $response);
                    break;
            }
        }
        return $result;
    }
    public function getFilter() {
        if (count($this->filters) === 0) {
            return NULL;
        }
        return $this->filters[0];
    }
    public function setFilter($filter) {
        $this->filters = array();
        if ($filter !== NULL) {
            $this->filters[] = $filter;
        }
    }
    public function addFilter($filter) {
        $this->filters[] = $filter;
    }
    public function removeFilter($filter) {
        $i = array_search($filter, $this->filters);
        if ($i === false || $i === NULL) {
            return false;
        }
        $this->filters = array_splice($this->filters, $i, 1);
        return true;
    }
    public function getSimpleMode() {
        return $this->simple;
    }
    public function setSimpleMode($simple = true) {
        $this->simple = $simple;
    }
    public function __call($function, $arguments) {
        return $this->invoke($function, $arguments);
    }
    public function __get($name) {
        return new HproseProxy($this, $name . '_');
    }
}