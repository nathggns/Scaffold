<?php

class MDT_DatabaseDriverSqliteTestClass extends DatabaseDriverSqlite {
    public function manual_query() {
        return call_user_func_array([$this, 'query'], func_get_args());
    }
}

class MDT_ModelUser extends ModelDatabase {
    protected $table_name = 'users';

    public function init() {
        $this->has_many('MDT_ModelPost', 'posts');
        $this->has_one('MDT_ModelSettings', 'settings');
        $this->habtm('MDT_ModelUser', 'followers', 'follower_id', 'id', 'user_id', 'friendships');
    }
}

class MDT_ModelPost extends ModelDatabase {
    protected $table_name = 'posts';

    static $default_fields = ['id', 'body'];
    protected $export_fields = ['id', 'body'];
}

class MDT_ModelSettings extends ModelDatabase {
    protected $table_name = 'settings';

    public function init() {
        $this->belongs_to('MDT_ModelUser', 'user');
    }
}


class ModelDatabaseTest extends PHPUnit_Framework_TestCase {

    static $names = ['Nat', 'Joe', 'Andrew', 'Claudio', 'Doug', 'Will', 'Matt', 'Alex'];
    public static $driver;

    public static function setUpBeforeClass() {

        if (!static::$driver) {
            $builder = new DatabaseQueryBuilderSqlite();
            static::$driver = new MDT_DatabaseDriverSqliteTestClass($builder, [
                'dsn' => 'sqlite:test.db'
            ]);
        }

        $driver = static::$driver;

        $driver->manual_query('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT);');
        $driver->manual_query('CREATE TABLE posts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, body TEXT);');
        $driver->manual_query('CREATE TABLE settings (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, key TEXT, value TEXT);');
        $driver->manual_query('CREATE TABLE friendships (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, follower_id INTEGER);');
    }

    public function setUp() {
        static::$driver->delete('posts');
        static::$driver->delete('settings');
        static::$driver->delete('friendships');
        static::$driver->delete('users');
        static::$driver->delete('SQLITE_SEQUENCE', [
            'where' => [
                'name' => ['users', 'posts', 'settings', 'friendships']
            ]
        ]);

        foreach (static::$names as $name) {
            static::$driver->insert('users', [
                'name' => $name
            ]);
        }

        static::$driver->insert('posts', [
            'user_id' => 1,
            'body' => 'Lorem ipsum...'
        ]);
        
        static::$driver->insert('settings', [
            'user_id' => 1,
            'key' => 'privacy',
            'value' => 'all'
        ]);
        
        static::$driver->insert('friendships', [
            'user_id' => 1,
            'follower_id' => 2
        ]);
        
        static::$driver->insert('friendships', [
            'user_id' => 1,
            'follower_id' => 3
        ]);
        
        static::$driver->insert('friendships', [
            'user_id' => 3,
            'follower_id' => 1
        ]);
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

    public function testVirtualsSurviveAfterSave() {
        $user = $this->get()->fetch(['id' => 1]);
        $user->virtual('name', 'Joseph Hudson-Small')->save();

        $this->assertEquals('Joseph Hudson-Small', $user->name);
    }

    public function testVirtualAfterFetch() {
        $user = $this->get()->fetch(['id' => 1]);
        $name = $user->name;

        $user->virtual('other_name', function() use ($name) {
            return $name;
        });

        $this->assertEquals($name, $user->other_name);
    }

    public function testThatVirtualsOverwriteValues() {
        $user = $this->get()->fetch(['id' => 1]);

        $user->virtual('name', 'Joe');

        $this->assertEquals('Joe', $user->name);
    }

    // public function testRelationshipHasMany() {
    //     $user = $this->get()->fetch(['id' => 1]);

    //     $this->assertCount(1, $user->posts);
    //     $this->assertCount(1, $user->posts[0]);
    //     $this->assertEquals('Lorem ipsum...', $user->posts[0]->body);
    //     $this->assertEquals(1, $user->posts[0]->id);
    // }

    // public function testRelationshipHasOne() {
    //     $user = $this->get()->fetch(['id' => 1]);

    //     $this->assertEquals(1, $user->settings->id);
    //     $this->assertEquals('privacy', $user->settings->key);
    //     $this->assertEquals('all', $user->settings->value);
    //     $this->assertCount(1, $user->settings);
    // }

    // public function testRelationshipBelongsTo() {
    //     $settings = new MDT_ModelSettings(1);

    //     $this->assertEquals(1, $settings->user->id);
    //     $this->assertEquals('Nat', $settings->user->name);
    //     $this->assertCount(1, $settings->user);
    // }

    // public function testRelationshipHABTM() {
    //     $user = $this->get()->fetch(['id' => 1]);

    //     $this->assertCount(2, $user->followers);
    //     $this->assertCount(1, $user->followers[0]);
    //     $this->assertCount(1, $user->followers[1]);
    //     $this->assertEquals('2', $user->followers[0]->id);
    //     $this->assertEquals('3', $user->followers[1]->id);
    //     $this->assertEquals('Joe', $user->followers[0]->name);
    //     $this->assertEquals('Andrew', $user->followers[1]->name);
    //     $this->assertCount(0, $user->followers[0]->followers);
    //     $this->assertCount(1, $user->followers[1]->followers);
    //     $this->assertCount(1, $user->followers[1]->followers[0]);
    //     $this->assertEquals('1', $user->followers[1]->followers[0]->id);
    //     $this->assertEquals('Nat', $user->followers[1]->followers[0]->name);
    // }

    public function testModelCount() {
        $users = $this->get()->fetch_all();
        $this->assertCount(8, $users);

        $users = $this->get()->fetch_all(['id' => 1]);
        $this->assertCount(1, $users);

        $users = $this->get()->fetch(['id' => 1]);
        $this->assertCount(1, $users);
    }

    public function loopThroughModels() {
        $users = $this->get()->fetch_all();

        $i = 0;

        foreach ($users as $key => $user) {
            $this->assertEquals($i, $key);
            $this->assertEquals((string)$i, $user->id);
            $this->assertEquals(static::$names[$i], $user->name);

            $i++;
        }

        $this->assertEquals(8, $i);
    }

    /**
     * @expectedException       OutOfRangeException
     * @expectedExceptioMessage Cannot get index 8
     */
    public function testGettingIndexOutOfRange() {
        $user = $this->get()->fetch_all()[8];
    }

    public function testLoopingThroughSingle() {
        $user = $this->get()->fetch(['id' => 1]);

        $keys = ['id', 'name', 'posts', 'settings', 'followers'];
        $values = ['1', 'Nat', $user->posts, $user->settings, $user->followers];
        $i = 0;

        foreach ($user as $key => $val) {
            $this->assertEquals($keys[$i], $key);
            $this->assertEquals($values[$i], $val);

            $i++;
        }

        $this->assertEquals(count($keys), $i);
    }

    /**
     * @expectedException       Exception
     * @expectedExceptioMessage Property id does not exist on model MDT_ModelUser
     */
    public function testExceptionWhenGettingPropertyFromMultiModel() {
        $user = $this->get()->fetch_all();

        $id = $user->id;
    }

    /**
     * @expectedException       Exception
     * @expectedExceptioMessage Property full_name does not exist on model MDT_ModelUser
     */
    public function testExceptionWhenGettingPropertyThatDoesntExist() {
        $user = $this->get()->fetch(['id' => 1]);

        $id = $user->full_name;
    }

    /**
     * @expectedException       Exception
     * @expectedExceptioMessage Cannot access row via index
     */
    public function testExceptionWhenGettingIndexFromSingleModel() {
        $user = $this->get()->fetch(['id' => 1]);

        $id = $user[0];
    }

    // public function testExportBasic() {
    //     $user = $this->get()->fetch(['id' => 1]);

    //     $data = $user->export();

    //     $expected = [
    //         'id' => $user->id,
    //         'name' => $user->name,
    //         'posts' => [
    //             [
    //                 'id' => 1,
    //                 'body' => 'Lorem ipsum...'
    //             ]
    //         ],
    //         'settings' => [
    //             'id' => 1,
    //             'user_id' => 1,
    //             'key' => 'privacy',
    //             'value' => 'all'
    //         ],
    //         'followers' => [
    //             [
    //                 'id' => 2,
    //                 'name' => 'Joe'
    //             ],

    //             [
    //                 'id' => 3,
    //                 'name' => 'Andrew'
    //             ]
    //         ]
    //     ];

    //     $keys = array_keys($expected);

    //     $this->assertCount(count($keys), $data);

    //     foreach ($expected as $key => $val) {
    //         $this->assertArrayHasKey($key, $data);
    //         $this->assertEquals($val, $data[$key]);
    //     }

    //     $this->assertEquals($expected, $data);
    // }

    // public function testExportSettingFields() {
    //     $user = $this->get()->fetch(['id' => 1]);

    //     $data = $user->export(['id', 'name']);

    //     $this->assertEquals([
    //         'id' => 1,
    //         'name' => 'Nat'
    //     ], $data);
    // }

    // public function testExportSettingLevelAndFields() {
    //     $user = $this->get()->fetch(['id' => 1]);

    //     $data = $user->export(['id', 'name', 'followers'], 2);

    //     $this->assertEquals([
    //         'id' => 1,
    //         'name' => 'Nat',
    //         'followers' => [
    //             [
    //                 'id' => 2,
    //                 'name' => 'Joe'
    //             ],
    //             [
    //                 'id' => 3,
    //                 'name' => 'Andrew'
    //             ]
    //         ]
    //     ], $data);
    // }

    // public function testExportSettingLevelAndFieldsAndCountModels() {
    //     $user = $this->get()->fetch(['id' => 1]);

    //     $data = $user->export(['id', 'name', 'followers'], 2, true);

    //     $this->assertEquals([
    //         'id' => 1,
    //         'name' => 'Nat',
    //         'followers' => 2
    //     ], $data);
    // }

    public function testStartingWithNonId() {
        $post = new MDT_ModelPost('Lorem ipsum...');

        $this->assertCount(1, $post);
        $this->assertEquals('1', $post->id);
        $this->assertEquals('1', $post->user_id);
        $this->assertEquals('Lorem ipsum...', $post->body);
    }

    /**
     * @expectedException       Exception
     * @expectedExceptioMessage Property id does not exist on model MDT_ModelUser
     */
    // public function testFindNothing() {
    //     $user = new MDT_ModelUser(['id' => 9]);

    //     $this->assertNull($user->export());
    //     $this->assertCount(0, $user);

    //     $user->id;
    // }

}