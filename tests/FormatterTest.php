<?php

class Person {
    public $name;
    public $age;
}

class FormatterTest extends PHPUnit_Framework_TestCase {
    public function testInteger() {
        for ($i = 0; $i <= 9; $i++) {
            $s = hprose_serialize($i);
            $this->assertEquals($s, $i . "");
            $this->assertEquals(hprose_unserialize($s), $i);
        }
        $s = hprose_serialize(10);
        $this->assertEquals($s, "i10;");
        $this->assertEquals(hprose_unserialize($s), 10);
        $s = hprose_serialize(-1);
        $this->assertEquals($s, "i-1;");
        $this->assertEquals(hprose_unserialize($s), -1);
        $s = hprose_serialize(2147483647);
        $this->assertEquals($s, "i2147483647;");
        $this->assertEquals(hprose_unserialize($s), 2147483647);
        $s = hprose_serialize(-2147483648);
        $this->assertEquals($s, "i-2147483648;");
        $this->assertEquals(hprose_unserialize($s), -2147483648);
        $s = hprose_serialize(2147483648);
        $this->assertEquals($s, "l2147483648;");
        $this->assertEquals(hprose_unserialize($s), 2147483648);
        $s = hprose_serialize(4294967295);
        $this->assertEquals($s, "l4294967295;");
        $this->assertEquals(hprose_unserialize($s), 4294967295);
    }
    public function testDouble() {
        $s = hprose_serialize(1.1);
        $this->assertEquals($s, "d1.1;");
        $this->assertEquals(hprose_unserialize($s), 1.1);
        $s = hprose_serialize(-3.141926);
        $this->assertEquals($s, "d-3.141926;");
        $s = hprose_serialize(log(-1));
        $this->assertEquals($s, "N");
        $this->assertEquals(is_nan(hprose_unserialize($s)), true);
        $s = hprose_serialize(log(0));
        $this->assertEquals($s, "I-");
        $this->assertEquals(is_infinite(hprose_unserialize($s)), true);
        $this->assertEquals(hprose_unserialize($s) < 0, true);
        $s = hprose_serialize(-log(0));
        $this->assertEquals($s, "I+");
        $this->assertEquals(is_infinite(hprose_unserialize($s)), true);
        $this->assertEquals(hprose_unserialize($s) > 0, true);
    }
    public function testBoolean() {
        $s = hprose_serialize(true);
        $this->assertEquals($s, "t");
        $this->assertEquals(hprose_unserialize($s), true);
        $s = hprose_serialize(false);
        $this->assertEquals($s, "f");
        $this->assertEquals(hprose_unserialize($s), false);
    }
    public function testNull() {
        $s = hprose_serialize(NULL);
        $this->assertEquals($s, "n");
        $this->assertEquals(hprose_unserialize($s), NULL);
    }
    public function testEmpty() {
        $s = hprose_serialize("");
        $this->assertEquals($s, "e");
        $this->assertEquals(hprose_unserialize($s), "");
    }
    public function testString() {
        $s = hprose_serialize("A");
        $this->assertEquals($s, "uA");
        $this->assertEquals(hprose_unserialize($s), "A");
        $s = hprose_serialize("你");
        $this->assertEquals($s, "u你");
        $this->assertEquals(hprose_unserialize($s), "你");
        $s = hprose_serialize("你好");
        $this->assertEquals($s, 's2"你好"');
        $this->assertEquals(hprose_unserialize($s), "你好");
        $bs = "";
        for ($i = 0; $i < 255; $i++) $bs .= chr($i);
        $s = hprose_serialize($bs);
        $this->assertEquals($s, 'b255"' . $bs . '"');
        $this->assertEquals(hprose_unserialize($s), $bs);
    }
    public function testArray() {
        $s = hprose_serialize(array(1,2,3,4,5));
        $this->assertEquals($s, 'a5{12345}');
        $this->assertEquals(hprose_unserialize($s), array(1,2,3,4,5));
        $s = hprose_serialize(array("name" => "tom", "age" => 18));
        $this->assertEquals($s, 'm2{s4"name"s3"tom"s3"age"i18;}');
        $this->assertEquals(hprose_unserialize($s), array("name" => "tom", "age" => 18));
    }
    public function testObject() {
        $o = new stdClass();
        $o->name = "tom";
        $o->age = 18;
        $s = hprose_serialize($o);
        $this->assertEquals($s, 'm2{s4"name"s3"tom"s3"age"i18;}');
        $this->assertEquals(hprose_unserialize($s), array("name" => "tom", "age" => 18));
        $s = 'c6"People"2{s4"name"s3"age"}o0{s3"tom"i18;}';
        $this->assertEquals(hprose_unserialize($s), $o);
    }
    public function testObject2() {
        $o = new Person();
        $o->name = "tom";
        $o->age = 18;
        $s = hprose_serialize($o);
        $this->assertEquals($s, 'c6"Person"2{s4"name"s3"age"}o0{s3"tom"i18;}');
        $this->assertEquals(hprose_unserialize($s), $o);
        $s = 'c6"People"2{s4"name"s3"age"}o0{s3"tom"i18;}';
        $this->assertNotEquals(hprose_unserialize($s), $o);
    }
    public function testReference() {
        $o = new Person();
        $o->name = "tom";
        $o->age = 18;
        $a = array($o, $o);
        $s = hprose_serialize($a);
        $this->assertEquals($s, 'a2{c6"Person"2{s4"name"s3"age"}o0{s3"tom"i18;}r3;}');
        $this->assertEquals(hprose_unserialize($s), $a);
    }
}
