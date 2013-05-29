<?php

class DatabaseQueryBuilderFunctionTest extends PHPUnit_Framework_TestCase {

    public function testBasicSQL() {
        $func = Service::get('database.query.builder.function', 'count', ['*']);
        $this->assertEquals('COUNT(*)', $func->generate('sql'));
    }

    public function testMultipleArgsSQL() {
        $func = Service::get('database.query.builder.function', 'max', ['5', '6']);
        $this->assertEquals('MAX(5, 6)', $func->generate('sql'));
    }

    public function testExtendingSQL() {
        $func = Service::get('database.query.builder.function', 'random', []);
        $this->assertEquals('RAND()', $func->generate('sql'));
    }

    public function testFallingBackToSQLFromSQLite() {
        $func = Service::get('database.query.builder.function', 'random', []);
        $this->assertEquals('RAND()', $func->generate('sqlite'));
    }

}