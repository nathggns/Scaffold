<?php

class ErrorTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		$this->error = Service::get('error');
	}
	
	public function testThrowingException() {

		$errored = false;

		$this->error->attach('Exception', function($e) use (&$errored) {
			$e->catch();
			$errored = true;
		});

		try {
			throw new Exception;
		} catch (Exception $e) {
			$this->error->handle($e);
		}

		$this->assertTrue($errored);
	}

}