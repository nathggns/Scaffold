<?php

/**
 * We use make a class that will mimic the behavior of DatabaseDriverPDO
 * but just return the queries as strings, as are going to assume that
 * PDO actually works. Fair assumption to make, don't you think?
 *
 * @todo Work out how to write tests for the actual database interactions,
 *       rather than just the argument shuffling part. 
 */
class DDPT_DatabaseDriverPDOTestClass extends DatabaseDriverPDO {

    public $query_string;
    var $query = true;

    function query($sql, $ret = false) {
        $this->query_string = $sql;
        return $this;
    }

    function fetch($table = null, $options = null) {
        return $this;
    }

    public function get_dsn() {
        return call_user_func_array([$this, 'dsn'], func_get_args());
    }
}

class DatabaseDriverPDOTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $config = [
            'dsn' => 'sqlite::memory:'
        ];

        $builder = Service::get('database.builder', 'sqlite');
        $this->driver = new DDPT_DatabaseDriverPDOTestClass($builder, $config, false);
    }

    public function testDSN() {
        $config = [
            'type' => 'mysql',
            'host' => 'localhost',
            'database' => 'scaffold'
        ];

        $dsn = $this->driver->get_dsn($config);

        $this->assertEquals('mysql:host=localhost;dbname=scaffold', $dsn);
    }

    public function testDSNCompilicated() {
        $config = [
            'type' => 'mysql',
            'host' => 'localhost',
            'database' => 'scaffold',
            'version' => '10'
        ];

        $dsn = $this->driver->get_dsn($config);

        $this->assertEquals('mysql:host=localhost;version=10;dbname=scaffold', $dsn);
    }

    public function testDSNManual() {
        $config = [
            'dsn' => 'sqlite::memory:'
        ];

        $dsn = $this->driver->get_dsn($config);

        $this->assertEquals('sqlite::memory:', $dsn);
    }

    public function testFindWithJustTable() {
        $query = $this->driver->find('users');

        $this->assertEquals('SELECT * FROM `users`;', $query->query_string);
    }

    public function testFindWithVals() {
        $query = $this->driver->find('users', [
            'vals' => ['id', 'name']
        ]);

        $this->assertEquals('SELECT `id`, `name` FROM `users`;', $query->query_string);   
    }

    public function testFindWithWhere() {
        $query = $this->driver->find('users', [
            'where' => [
                'name' => 'joe',
                Database::where_or([
                    'name' => 'nat',
                    'partner' => 'joe'
                ])
            ]
        ]);

        $this->assertEquals('SELECT * FROM `users` WHERE `name` = \'joe\' OR (`name` = \'nat\' AND `partner` = \'joe\');', $query->query_string);
    }

    public function testFindWithOrder() {
        $query = $this->driver->find('users', [
            'order' => [['name', 'DESC'], ['id', 'ASC']]
        ]);

        $this->assertEquals('SELECT * FROM `users` ORDER BY `name` DESC, `id` ASC;', $query->query_string);   
    }

    public function testFindWithLimit() {
        $query = $this->driver->find('users', [
            'limit' => [35, 56]
        ]);

        $this->assertEquals('SELECT * FROM `users` LIMIT 35, 56;', $query->query_string); 
    }

    public function testFindWithAll() {       
        $query = $this->driver->find('users', [
            'vals' => ['id', 'name'],
            'where' => [
                'name' => 'joe',
                Database::where_or([
                    'name' => 'nat',
                    'partner' => 'joe'
                ])
            ],
            'order' => [['name', 'DESC'], ['id', 'ASC']],
            'limit' => [35, 56]
        ]);

        $this->assertEquals('SELECT `id`, `name` FROM `users` WHERE `name` = \'joe\' OR (`name` = \'nat\' AND `partner` = \'joe\') ORDER BY `name` DESC, `id` ASC LIMIT 35, 56;', $query->query_string); 
    }

    public function testInsert() {
        $query = $this->driver->insert('users', [
            'name' => 'Claudio',
            'partner' => 'SuperMegaHotGuy'
        ]);

        $this->assertEquals('INSERT INTO `users` (`name`, `partner`) VALUES (\'Claudio\', \'SuperMegaHotGuy\');', $query->query_string);
    }

    public function testUpdate() {
        $query = $this->driver->update('users', [
            'partner' => 'SuperMegaHotGuy'
        ], [
            'name' => 'Claudio'
        ]);

        $this->assertEquals('UPDATE `users` SET `partner` = \'SuperMegaHotGuy\' WHERE `name` = \'Claudio\';', $query->query_string);
    }

    public function testDeleteWithoutWhere() {
        $query = $this->driver->delete('users');

        $this->assertEquals('DELETE FROM `users`;', $query->query_string);
    }

    public function testDeleteWithWhere() {
        $query = $this->driver->delete('users', [
            'inactive' => 1
        ]);

        $this->assertEquals('DELETE FROM `users` WHERE `inactive` = 1;', $query->query_string);
    }

    public function testCount() {
        $query = $this->driver->find('users')->count();

        $this->assertEquals('SELECT COUNT(*) FROM `users`;', $query->query_string);
    }

    public function testCountWithConds() {
        $query = $this->driver->find('users', [
            'where' => [
                'id' => 5
            ]
        ])->count();

        $this->assertEquals('SELECT COUNT(*) FROM `users` WHERE `id` = 5;', $query->query_string);
    }
}