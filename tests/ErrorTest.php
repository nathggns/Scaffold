<?php

class ET_Class {

    public static $e;

    public static function method($e) {
        static::$e = $e; 
    }
}

class Exception_ET extends Exception {

}

class ErrorTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->error = Service::get('error');
    }

    public function testAliasFunction() {
        $this->assertEquals('exceptions', $this->error->alias('exceptions')->alias);
    }
    
    public function testThrowingException() {

        $errored = false;

        $this->error->alias('testThrowingException')->attach('Exception', function($e) use (&$errored) {
            $e->catch();
            $errored = true;
        }, 'testThrowingException');

        try {
            throw new Exception;
        } catch (Exception $e) {
            $this->error->handle($e);
        }

        $this->assertTrue($errored);
    }

    public function testUnCatching() {
        $errored = false;

        $this->error->alias('testUnCatching')->attach('Exception', function($e) use (&$errored) {
            $e->catch()->uncatch();
        }, 'testUnCatching');

        try {
            try {
                throw new Exception;
            } catch (Exception $e) {
                $this->error->handle($e);
            }
        } catch (Exception $e) {
            $errored = true;
        }

        $this->assertTrue($errored);
    }

    public function testNotCatching() {
        $errored = false;

        $this->error->alias('testNotCatching')->attach('Exception', function($e) use (&$errored) {
        }, 'testNotCatching');

        try {
            try {
                throw new Exception;
            } catch (Exception $e) {
                $this->error->handle($e);
            }
        } catch (Exception $e) {
            $errored = true;
        }

        $this->assertTrue($errored);
    }

    public function testStopping() {
        $errored = false;

        $this->error->alias('testStopping')->attach('Exception', function($e) use (&$errored) {
            $e->catch()->stop();
            $errored = true;
        }, 'testStopping');

        $this->error->alias('testStopping')->attach('Exception', function($e) use (&$errored) {
            $errored = false;
        }, 'testStopping');

        try {
            throw new Exception;
        } catch (Exception $e) {
            $this->error->handle($e);
        } 

        $this->assertTrue($errored);
    }

    public function testRethrow() {
        $errored = false;

        $this->error->alias('testRethrow')->attach('Exception', function($e) use (&$errored) {
            $e->catch()->rethrow();
        }, 'testRethrow');

        try {
            try {
                throw new Exception;
            } catch (Exception $e) {
                $this->error->handle($e);
            }
        } catch (Exception $e) {
            $errored = true;
        }

        $this->assertTrue($errored);
    }

    public function testExceptionGoesThroughToHandler() {
        $error = false;
        $catchederror = false;

        $this->error->alias('testExceptionGoesThroughToHandler')->attach('Exception', function($e) use (&$catchederror) {
            $e->catch();
            $catchederror = $e->exc;
        }, 'testExceptionGoesThroughToHandler');

        try {
            throw new Exception;
        } catch (Exception $e) {
            $error = $e;
            $this->error->handle($e);
        }

        $this->assertEquals($error, $catchederror);
    }

    public function testMultipleHandlers() {
        $error = null;

        $this->error->attach('Exception', ['ET_Class', 'method'], 'testMultipleHandlers');
        $this->error->attach('Exception_ET', function($e) use (&$error) {
            $e->catch();
            $error = $e;
        }, 'testMultipleHandlers');

        $this->error->handle(new Exception_ET(), 'testMultipleHandlers');

        $this->assertNotEquals(null, $error);
        $this->assertNotEquals(null, ET_Class::$e);
    }

}