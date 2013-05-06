<?php

class ValidateTest extends PHPUnit_Framework_Testcase {
    public function testEmptyWithEmptyData() {
        $validator = new Validate(['value' => 'empty']);

        $validator->test([]);
        $validator->test(['value' => '']);
        $validator->test(['value' => null]);
    }

    public function testEmptyWithEmptyDataAsArray() {
        $validator = new Validate(['value' => ['empty']]);

        $this->assertTrue($validator->test([]));
        $this->assertTrue($validator->test(['value' => '']));
        $this->assertTrue($validator->test(['value' => null]));
    }

    /**
     * @expectedException ExceptionValidate
     */
    public function testEmptyWithNonEmptyDataAsArray() {
        $validator = new Validate(['value' => ['empty']]);

        $validator->test(['value' => 'abc']);
    }

    /**
     * @expectedException ExceptionValidate
     */
    public function testEmptyWithNonEmptyData() {
        $validator = new Validate(['value' => 'empty']);

        $validator->test(['value' => 'abc']);
    }

    /**
     * @expectedException ExceptionValidate
     */
    public function testAlphanumericWithEmptyData() {
        $validator = new Validate(['value' => 'alphanumeric']);

        $validator->test(['value' => '']);
        $validator->test(['value' => null]);
        $validator->test([]);
    }

    /**
     * @expectedException ExceptionValidate
     */
    public function testAlphanumericWithNonAlphanumericData() {
        $validator = new Validate(['value' => 'alphanumeric']);

        $validator->test(['value' => '!@£$']);
    }

    public function testAlphanumericData() {
        $validator = new Validate(['value' => 'alphanumeric']);

        $this->assertTrue($validator->test(['value' => 'abc']));
    }

    public function testWithDifferentTests() {
        $validator = new Validate([
            'value' => 'alphanumeric'
        ]);

        $validator->set('email', 'email');
        $validator->set([
            'name' => '/[A-Za-z\s]/',
            'status' => 'numeric',
            'username' => 'alphanumeric not_email',
            'password' => function($pass) {
                return strlen($pass) >= 6;
            }
        ]);

        $this->assertTrue($validator->test([
            'value' => 'abc',
            'name' => 'Nathaniel Higgins',
            'email' => 'nat@nath.is',
            'status' => 1,
            'username' => 'nathggns',
            'password' => 'abcdef'
        ]));
    }

    /**
     * @expectedException ExceptionValidate
     */
    public function testWithDifferentTestsFails() {
        $validator = new Validate([
            'value' => 'alphanumeric'
        ]);

        $validator->set('email', 'email');
        $validator->set([
            'name' => '/[A-Za-z\s]/',
            'status' => 'numeric',
            'username' => 'alphanumeric not_email',
            'password' => function($pass) {
                return strlen($pass) >= 6;
            }
        ]);

        $validator->test([
            'value' => 'abc@',
            'name' => 'Nathaniel Higgins!',
            'email' => 'nat',
            'status' => '1a',
            'username' => 'nat@nath.is',
            'password' => 'abc'
        ]); 
    }

    public function testWithValue() {
        $validator = new Validate([
            'value' => 'Test'
        ]);

        $validator->test(['value' => 'Test']);
    }

    /**
     * @expectedException ExceptionValidate
     */
    public function testWithValueFail() {
        $validator = new Validate([
            'value' => 'Test'
        ]);

        $validator->test(['value' => 'TestA']);
    }

    /**
     * @expectedException ExceptionValidate
     */
    public function testWithFunctionFails() {
        $validator = new Validate([
            'custom' => function() {
                return false;
            }
        ]);

        $validator->test(['custom' => 'abc']);
    }

    public function testWithFunctionPasses() {
        $validator = new Validate([
            'custom' => function($val) {
                return $val === 'abc';
            }
        ]);

        $validator->test(['custom' => 'abc']);
    }

    /**
     * @expectedException ExceptionValidate
     */
    public function testWithNamedTests() {
        $validator = new Validate([
            'custom' => [
                'equals_abc' => function($val) {
                    return $val === 'abc';
                }
            ]
        ]);

        $validator->test(['custom' => 'abcd']);
    }
}