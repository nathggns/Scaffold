<?php

class ConstantsTest extends PHPUnit_Framework_TestCase {

    public function testScaffoldConstant() {
        $this->assertTrue(defined('SCAFFOLD'));
        $this->assertTrue(SCAFFOLD);
    }

    public function testDSConstant() {
        $this->assertTrue(defined('DS'));
        $this->assertEquals(DIRECTORY_SEPARATOR, DS);
    }

    public function testConsoleConstant() {
        $this->assertTrue(defined('CONSOLE'));
        $this->assertTrue(CONSOLE);
    }


}