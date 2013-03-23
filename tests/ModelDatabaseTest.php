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

    static $names = ['Nat', 'Joe', 'Andrew', 'Claudio', 'Doug', 'Will', 'Matt', 'Alex'];
    public static $driver;

    public static function setUpBeforeClass() {

        if (!static::$driver) {
            $builder = new DatabaseQueryBuilderSqlite();
            static::$driver = new MDT_DatabaseDriverPDOTestClass($builder, [
                'dsn' => 'sqlite:test.db'
            ]);
        }

        $driver = static::$driver;

        $driver->manual_query('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT);');
    }

    public function setUp() {
        static::$driver->delete('users');
        static::$driver->delete('SQLITE_SEQUENCE', [
            'where' => [
                'name' => 'users'
            ]
        ]);

        foreach (static::$names as $name) {
            static::$driver->insert('users', [
                'name' => $name
            ]);
        }
    }

    public static function tearDownAfterClass() {
        unlink(ROOT . 'test.db');
    }

    public function get() {
        return new MDT_ModelUser(null, static::$driver);
    }

    public function equals($arr, $obj = false) {
        foreach ($arr as $key => $val) {

            if ($obj) {
                $key = $obj->$key;
            }

            $this->assertEquals($val, $key);
        }
    }

    public function testFindUserWithIdFromConstruct() {
        $user = new MDT_ModelUser(1, static::$driver);

        $this->equals([
            'id' => '1',
            'name' => 'Nat',
        ], $user);

        $this->assertEquals(1, count($user));
    }

    public function testFetchUserWithId() {
        $user = $this->get()->fetch(['id' => 1]);

        $this->equals([
            'id' => '1',
            'name' => 'Nat',
        ], $user);

        $this->assertEquals(1, count($user));
    }

    public function testFetchAllUsers() {
        $users = $this->get()->fetch_all();

        $this->assertEquals(8, count($users));
        $this->assertEquals(8, $users->count());

        foreach (static::$names as $i => $name) {
            $user = $users[$i];

            $this->equals([
                'id' => (string) $i + 1,
                'name' => $name
            ], $user);
        }
    }

}