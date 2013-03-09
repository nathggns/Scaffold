<?php

class MDT_DatabaseDriverPDOTestClass extends DatabaseDriverPDO {
    public function manual_query() {
        return call_user_func_array([$this, 'query'], func_get_args());
    }
}

class MDT_ModelUser extends ModelDatabase {
    var $table_name = 'users';
}

class ModelDatabaseTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $builder = new DatabaseQueryBuilderSqlite();
        $driver = new MDT_DatabaseDriverPDOTestClass($builder, [
            'dsn' => 'sqlite:test.db'
        ]);

        $driver->manual_query('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT);');

        $names = ['Nat', 'Joe', 'Andrew', 'Claudio', 'Doug', 'Will', 'Matt', 'Alex'];

        foreach ($names as $name) {
            $driver->insert('users', [
                'name' => $name
            ]);
        }

        $this->driver = $driver;
    }

    public function tearDown() {
        unlink(ROOT . 'test.db');
    }

    public function get() {
        return new MDT_ModelUser(null, $this->driver);
    }

    public function equals($arr) {
        foreach ($key as $val) {
            $this->assertEquals($val, $key);
        }
    }
}