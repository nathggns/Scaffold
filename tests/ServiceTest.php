<?php

class ServiceTest extends PHPUnit_Framework_Testcase {

    public function tearDown() {
        Service::reset();
    }

    /**
     * @cover Service::instance
     * @cover Service::get
     */
    public function testInstance() {
        $object = new stdClass();

        Service::instance('test', $object);
        $this->assertEquals($object, Service::get('test'));
    }

    /**
     * @cover Service::singleton
     * @cover Service::get
     */
    public function testSingleton() {
        $calls = 0;

        Service::singleton('test', function() use (&$calls) {
            $calls++;

            $object = new stdClass();
            $object->foo = rand();

            return $object;
        });

        $this->assertSame(Service::get('test'), Service::get('test'));
        $this->assertEquals(1, $calls);
    }

    /**
     * @cover Service::register
     * @cover Service::get
     */
    public function testRegister() {
        $calls = 0;

        Service::register('test', function($foo = false, $bar = true) use (&$calls) {
            $calls++;

            $this->assertTrue($foo);
            $this->assertFalse($bar);

            $object = new stdClass();
            $object->foo = rand();

            return $object;
        });

        $this->assertNotSame(
            Service::get('test', true, false),
            Service::get('test', true, false)
        );

        $this->assertEquals(2, $calls);
    }

    /**
     * @cover Service::default
     */
    public function testDefault() {
        Service::register('test', function() {
            return 'test';
        });

        Service::register('test.alternative', function() {
           return 'test.alternative';
        });

        $this->assertEquals('test', Service::get('test'));

        Service::set_default('test.alternative');
        $this->assertEquals('test.alternative', Service::get('test'));

        Service::register('test.default', function() {
            return 'test.default';
        }, true);

        $this->assertEquals('test.default', Service::get('test'));
    }

    /**
     * @expectedException       ExceptionService
     * @expectedExceptioMessage Service __NonExistentService not found
     */
    public function testException() {
        Service::get('__NonExistentService');
    }

    public function testGettingNotRegisteredService() {
        $autoload = Service::get('Autoload');
        $this->assertInstanceOf('Autoload', $autoload);
    }

    public function testGettingNotRegisteredServiceWithArgs() {
        $dynamic = Service::get('Dynamic', [
            'a' => 'b',
            'c' => 'd'
        ]);
        $this->assertInstanceOf('Dynamic', $dynamic);
        $this->assertEquals('b', $dynamic->a);
        $this->assertEquals('d', $dynamic->c);
    }

    public function testTransformation() {
        $driver = Service::get('database.driver.PDO');

        $this->assertInstanceOf('DatabaseDriverPDO', $driver);
    }
}
