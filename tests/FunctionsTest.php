<?php

class FunctionsTest extends PHPUnit_Framework_TestCase {

    public function testAbs2Rel() {
        $file = abs2rel(__FILE__);
        $this->assertEquals('tests/FunctionsTest.php', $file);
    }

    public function testIsHash() {
        $this->assertTrue(is_hash([
            'a' => 'b',
            'c' => 'd'
        ]));

        $this->assertFalse(is_hash(['a', 'b']));
        $this->assertFalse(is_hash(['a']));
        $this->assertFalse(is_hash([null, 'b']));
    }

}