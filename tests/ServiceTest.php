<?php

class ServiceTest extends PHPUnit_Framework_Testcase {

    public function testSingleton() {
        $validator = new Validate();
        Service::set('validator', $validator);
        $this->assertEquals($validator, Service::get('validator'));
    }

    public function testDummy() {
        $dummy = Service::get('dummy');

        $this->assertNull($dummy->undefinedFunc());
        $this->assertNull($dummy::undefinedStaticFunc());
        $this->assertNull($dummy->undefinedProperty);
        $this->assertFalse(isset($dummy->undefinedProperty));

        $dummy->property = 'Hello';
        $this->assertEquals('Hello', $dummy->property);
        $this->assertTrue(isset($dummy->property));
    }

    public function testCreate() {

        $test = $this;

        Service::set('validator', function($argument = false, $argument2 = true) use($test) {
            $test->assertTrue($argument);
            $test->assertFalse($argument2);
        });

        $object = Service::get('validator', true, false);
    }

    /**
     * @expectedException ExceptionService
     */
    public function testException() {
        Service::get('__NonExistentService');
    }

    public function testExceptionMessage() {
        try {
            Service::get('__NonExistentService');
        } catch (ExceptionService $e) {
            $this->assertEquals('Service __NonExistentService not found', $e->getMessage());
        }
    }
}
