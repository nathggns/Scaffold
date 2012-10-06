<?php

class DummyTest extends PHPUnit_Framework_Testcase {

    public function setUp() {
        $this->dummy = new Dummy();
    }

    /**
     * @covers Dummy
     */
    public function testDummy() {
        $this->assertNull($this->dummy->undefinedFunction());
        $this->assertNull(Dummy::undefinedStaticFunction());
        $this->assertNull($this->dummy->undefinedProperty);
        $this->assertFalse(isset($this->dummy->undefinedProperty));

        $this->dummy->property = 'test';
        $this->assertEquals('test', $this->dummy->property);
        $this->assertTrue(isset($this->dummy->property));
    }

}
