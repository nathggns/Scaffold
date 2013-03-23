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

    public function testScalarVirtual() {
        $user = $this->get()->fetch(['id' => 1]);

        $user->virtual('full_name', 'Joseph Hudson-Small');

        $this->assertEquals('Joseph Hudson-Small', $user->full_name);
    }

    public function testClosureVirtualWithoutArguments() {
        $user = $this->get()->fetch(['id' => 1]);

        $user->virtual('full_name', function() {
            return 'Joseph Hudson-Small';
        });

        $this->assertEquals('Joseph Hudson-Small', $user->full_name);
    }

    public function testClosureVirtualWithArguments() {
        $user = $this->get()->fetch(['id' => 1]);

        $user->virtual('field', function($field) {
            return $field;
        });

        $this->assertEquals('field', $user->field);
    }

    public function testArrayVirtualWithClosure() {
        $user = $this->get()->fetch(['id' => 1]);

        $user->virtual('first_name', 'Joseph');
        $user->virtual('last_name', 'Hudson-Small');

        $user->virtual('name', function() {
            return [
                'short' => 'Joe',
                'full_name' => $this->first_name . ' ' . $this->last_name
            ];
        });

        $this->assertEquals('Joe', $user->name['short']);
        $this->assertEquals('Joseph Hudson-Small', $user->name['full_name']);
    }

    public function testObjectVirtualWithClosure() {
        $user = $this->get()->fetch(['id' => 1]);

        $user->virtual('first_name', 'Joseph');
        $user->virtual('last_name', 'Hudson-Small');

        $user->virtual('name', function() {
            return new Dynamic([
                'short' => 'Joe',
                'full_name' => $this->first_name . ' ' . $this->last_name
            ]);
        });

        $this->assertEquals('Joe', $user->name->short);
        $this->assertEquals('Joseph Hudson-Small', $user->name->full_name);
    }

    public function testArrayVirtual() {
        $user = $this->get()->fetch(['id' => 1]);


        $user->virtual('name', [
            'short' => 'Joe'
        ]);

        $this->assertEquals('Joe', $user->name['short']);
    }

    public function testObjectVirtual() {
        $user = $this->get()->fetch(['id' => 1]);


        $user->virtual('name', new Dynamic([
            'short' => 'Joe'
        ]));

        $this->assertEquals('Joe', $user->name->short);
    }

}