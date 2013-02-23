<?php

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
			$e->catch();
			$e->uncatch();
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
			$e->catch();
			$e->stop();
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

}