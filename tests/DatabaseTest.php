<?php

class DatabaseTest extends PHPUnit_Framework_TestCase {

    public function testConnectors() {
        $connectors = ['and', 'or'];

        foreach ($connectors as $connector) {
            $obj = call_user_func(['Database', 'where_' . $connector], 'nat');

            $this->assertEquals($connector, $obj->connector);
            $this->assertEquals('nat', $obj->val);
        }
    }

    public function testOperators() {
        $operators = ['gte', 'gt', 'lte', 'lt', 'equals'];

        foreach ($operators as $operator) {
            $obj = call_user_func(['Database', 'where_' . $operator], 'nat');

            $this->assertEquals($operator, $obj->operator);
            $this->assertEquals('nat', $obj->val);
        }
    }

    public function testWhereNot() {
        $obj = Database::where_not('nat');

        $this->assertEquals(['not'], $obj->special);
        $this->assertEquals('nat', $obj->val);
    }

    public function testWhereNested() {
        $obj = Database::where_or(Database::where_gt('nat'));

        $this->assertEquals('or', $obj->connector);
        $this->assertEquals('gt', $obj->operator);
        $this->assertEquals('nat', $obj->val);
    }

    public function testFuncCount() {
        $obj = Database::func_count('*');

        $this->assertEquals('*', $obj->val);
        $this->assertEquals('function', $obj->type);
        $this->assertEquals('count', $obj->function);
    }

    public function testFuncMinWithColA() {
        $obj = Database::func_min('a');

        $this->assertEquals('a', $obj->val);
        $this->assertEquals('function', $obj->type);
        $this->assertEquals('min', $obj->function);
    }

}