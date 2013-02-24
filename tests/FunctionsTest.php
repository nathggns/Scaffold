<?php

class FunctionTests extends PHPUnit_Framework_TestCase {

    public function testAbs2Rel() {
        $file = abs2rel(__FILE__);
        $this->assertEquals('tests/FunctionsTest.php', $file);
    }

}