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

    public function testFuncCountPassingName() {
        $obj = Database::func_count('id');

        $this->assertEquals('function', $obj->type);
        $this->assertEquals('count', $obj->name);
        $this->assertCount(1, $obj->args);
        $this->assertEquals('id', $obj->args[0]);
    }

    public function testFuncPassingCallback() {
        $obj = Database::func(function() {
            return $this->count('id');
        });

        $this->assertEquals('function', $obj->type);
        $this->assertEquals('count', $obj->name);
        $this->assertCount(1, $obj->args);
        $this->assertEquals('id', $obj->args[0]);
    }

    public function testFuncPassingCallbackInArgs() {
        $main = Database::func_count(function() {
            return $this->max('id');
        });

        foreach (['count' => $main, 'max' => $main->args[0]] as $name => $obj) {
            $this->assertEquals('function', $obj->type);
            $this->assertEquals($name, $obj->name);
            $this->assertCount(1, $obj->args);
        }
        
        $this->assertEquals('id', $main->args[0]->args[0]);
    }
}