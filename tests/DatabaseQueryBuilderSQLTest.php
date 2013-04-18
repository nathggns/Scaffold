<?php

class DatabaseQueryBuilderSQLTest extends PHPUnit_Framework_Testcase {

    public function setUp() {
        $this->builder = new DatabaseQueryBuilderSQL();
    }

    public function testBasicSelect() {
        $sql = $this->builder->select(['table' => 'users']);
        $this->assertEquals($sql, 'SELECT * FROM `users`;');
    }

    public function testBasicCount() {
        $sql = $this->builder->count(['table' => 'users']);
        $this->assertEquals($sql, 'SELECT COUNT(*) FROM `users`;');
    }

    public function testBasicSelectDistinct() {
        $sql = $this->builder->select(['table' => 'users', 'distinct' => true]);
        $this->assertEquals($sql, 'SELECT DISTINCT * FROM `users`;');
    }

    public function testBasicSelectWithStringForTable() {
        $sql = $this->builder->select('users');
        $this->assertEquals($sql, 'SELECT * FROM `users`;');
    }

    public function testBasicSelectChainedWithStart() {
        $sql = $this->builder->start()->select('users')->end();
        $this->assertEquals($sql, 'SELECT * FROM `users`;');
    }

    public function testBasicSelectChainedWithoutStart() {
        $sql = $this->builder->select('users')->end();
        $this->assertEquals($sql, 'SELECT * FROM `users`;');
    }

    public function testBasicSelectChainedDistinct() {
        $sql = $this->builder->select('users')->distinct()->end();
        $this->assertEquals($sql, 'SELECT DISTINCT * FROM `users`;');
    }

    public function testBasicSelectChainedWithArgs() {
        $sql = $this->builder->select('users')->where('id', 1)->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1;', $sql);
    }

    public function testSelectChainedWithMultArgsOr() {
        $sql = $this->builder->select('users')->where('id', 1)->where_or('name', 2)->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 OR `name` = 2;', $sql);
    }

    public function testSelectChainedWithMultArgsAnd() {
        $sql = $this->builder->select('users')->where('id', 1)->where_and('name', 2)->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 AND `name` = 2;', $sql);
    }

    public function testSelectChainedWithMultArgsAndSeperate() {
        $sql = $this->builder->select('users')->where('id', 1)->where_or()->where('name', 2)->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 OR `name` = 2;', $sql);
    }

    public function testSelectChainedOrder() {
        $sql = $this->builder->select('users')->order('id')->end();
        $this->assertEquals('SELECT * FROM `users` ORDER BY `id` ASC;', $sql);
    }

    public function testSelectChainedOrderSpecifiedASC() {
        $sql = $this->builder->select('users')->order('id', 'asc')->end();
        $this->assertEquals('SELECT * FROM `users` ORDER BY `id` ASC;', $sql);
    }

    public function testSelectChainedOrderSpecifiedDESC() {
        $sql = $this->builder->select('users')->order('id', 'desc')->end();
        $this->assertEquals('SELECT * FROM `users` ORDER BY `id` DESC;', $sql);
    }

    public function testSelectChainedOrderMultiple() {
        $sql = $this->builder->select('users')->order('id')->order('name')->end();
        $this->assertEquals('SELECT * FROM `users` ORDER BY `id` ASC, `name` ASC;', $sql);
    }

    public function testSelectChainedOrderMultipleSpecified() {
        $sql = $this->builder->select('users')->order('id', 'desc')->order('name', 'asc')->end();
        $this->assertEquals('SELECT * FROM `users` ORDER BY `id` DESC, `name` ASC;', $sql);
    }

    public function testSelectChainedLimit() {
        $sql = $this->builder->select('users')->limit(24)->end();
        $this->assertEquals('SELECT * FROM `users` LIMIT 0, 24;', $sql);
    }

    public function testSelectChainedLimitAdvanced() {
        $sql = $this->builder->select('users')->limit(12, 24)->end();
        $this->assertEquals('SELECT * FROM `users` LIMIT 12, 24;', $sql);
    }

    public function testSelectChainedLimitAdvancedSeperate() {
        $sql = $this->builder->select('users')->limit(12)->limit(24)->end();
        $this->assertEquals('SELECT * FROM `users` LIMIT 12, 24;', $sql);    
    }

    public function testSelectChainedNot() {
        $sql = $this->builder->select('users')->where_not('name', 'nat')->end();
        $this->assertEquals('SELECT * FROM `users` WHERE NOT `name` = \'nat\';', $sql);
    }

    public function testSelectChainedGroup() {
        $sql = $this->builder->select('users')->group('logins')->end();
        $this->assertEquals('SELECT * FROM `users` GROUP BY `logins`;', $sql);
    }

    public function testSelectChainedGroupMult() {
        $sql = $this->builder->select('users')->group('logins', 'ip')->end();
        $this->assertEquals('SELECT * FROM `users` GROUP BY `logins`, `ip`;', $sql);
    }

    public function testSelectChainedWithMultArgsDefault() {
        $sql = $this->builder->select('users')->where('id', 1)->where('name', 2)->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 AND `name` = 2;', $sql);
    }

    public function testSelectChainedChoosingOperator() {
        $sql = $this->builder->select('users')->where_gt('id', 4)->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `id` > 4;', $sql);
    }

    public function testSelectChainedChoosingOperatorSeperate() {
        $sql = $this->builder->select('users')->where_gt()->where('id', 4)->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `id` > 4;', $sql);
    }

    public function testBasicSelectWithStringForTableAndArgs() {
        $sql = $this->builder->select('users', ['conds' => ['id' => 1]]);
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1;', $sql);
    }

    public function testSelectWithSimpleWhere() {
        $sql = $this->builder->select(['table' => 'users', 'conds' => ['id' => 1]]);
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1;', $sql);
    }

    public function testSelectWithTwoWheres() {
        $sql = $this->builder->select(['table' => 'users', 'conds' => ['id' => 1, 'user' => 'Nat']]);
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 AND `user` = \'Nat\';', $sql);
    }

    public function testSelectWithTwoWheresChoosingConnector() {
        $sql = $this->builder->select(['table' => 'users', 'conds' => ['id' => 1, 'user' => Database::where_or('Nat')]]);
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 OR `user` = \'Nat\';', $sql);
    }

    public function testSelectChoosingOperator() {
        $sql = $this->builder->select(['table' => 'users', 'conds' => ['id' => Database::where_gt(1)]]);
        $this->assertEquals('SELECT * FROM `users` WHERE `id` > 1;', $sql);
    }

    public function testSelectWithTwoWheresChoosingConnectorAndOperator() {
        $sql = $this->builder->select(['table' => 'users', 'conds' => ['id' => 1, 'logins' => Database::where_or(Database::where_gte(1))]]);
        $this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 OR `logins` >= 1;', $sql);
    }

    public function testSelectWithGroups() {
        $sql = $this->builder->select([
            'table' => 'users',
            'conds' => [
                [
                    'id' => 1,
                    'name' => 'Nat'
                ],

                Database::where_or([
                    'id' => 2,
                    'name' => 'Claudio'
                ])
            ]
        ]);

        $this->assertEquals('SELECT * FROM `users` WHERE (`id` = 1 AND `name` = \'Nat\') OR (`id` = 2 AND `name` = \'Claudio\');', $sql);
    }

    public function testSelectWithGroupsLevelTwo() {
        $sql = $this->builder->select([
            'table' => 'users',
            'conds' => [
                [
                    'id' => 1,
                    'name' => 'Nat',

                    [
                        'key' => 'val',
                        'key_two' => 'val_two'
                    ]
                ],

                Database::where_or([
                    'id' => 2,
                    'name' => 'Claudio'
                ])
            ]
        ]);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE (`id` = 1 AND `name` = \'Nat\' AND (`key` = \'val\' AND `key_two` = \'val_two\')) OR (`id` = 2 AND `name` = \'Claudio\');',
            $sql
        );
    }

    public function testSelectWithIn() {
        $sql = $this->builder->select([
            'table' => 'users',
            'conds' => [
                'id' => [1, 2]
            ]
        ]);

        $this->assertEquals('SELECT * FROM `users` WHERE `id` IN (1, 2);', $sql);
    }

    public function testSelectWithLimit() {
        $sql = $this->builder->select([
            'table' => 'users',
            'limit' => 2
        ]);

        $this->assertEquals('SELECT * FROM `users` LIMIT 0, 2;', $sql);
    }

    public function testSelectWithAdvancedLimit() {
        $sql = $this->builder->select([
            'table' => 'users',
            'limit' => [25, 56]
        ]);

        $this->assertEquals('SELECT * FROM `users` LIMIT 25, 56;', $sql);
    }

    public function testSelectWithGroupBy() {
        $sql = $this->builder->select([
            'table' => 'users',
            'group' => 'last_name'
        ]);

        $this->assertEquals('SELECT * FROM `users` GROUP BY `last_name`;', $sql);
    }

    public function testSelectWithMultipleGroupBys() {
        $sql = $this->builder->select([
            'table' => 'users',
            'group' => ['last_name', 'location']
        ]);

        $this->assertEquals('SELECT * FROM `users` GROUP BY `last_name`, `location`;', $sql);
    }

    public function testSelectWithOrder() {
        $sql = $this->builder->select([
            'table' => 'users',
            'order' => 'name'
        ]);

        $this->assertEquals('SELECT * FROM `users` ORDER BY `name` ASC;', $sql);
    }

    public function testSelectWithOrderOfOrder() {
        $sql = $this->builder->select([
            'table' => 'users',
            'order' => [['name', 'desc']]
        ]);

        $this->assertEquals('SELECT * FROM `users` ORDER BY `name` DESC;', $sql);
    }

    public function testSelectWithMultipleOrders() {
        $sql = $this->builder->select([
            'table' => 'users',
            'order' => ['last_name', 'name']
        ]);

        $this->assertEquals('SELECT * FROM `users` ORDER BY `last_name` ASC, `name` ASC;', $sql);
    }

    public function testSelectWithMultipleOrdersWithOrderofOrders() {
        $sql = $this->builder->select([
            'table' => 'users',
            'order' => [
                'last_name',
                ['name', 'desc']
            ]
        ]);

        $this->assertEquals('SELECT * FROM `users` ORDER BY `last_name` ASC, `name` DESC;', $sql);
    }

    public function testUpdate() {
        $sql = $this->builder->update('users', [
            'name' => 'Bob'
        ], [
            'name' => 'Paul'
        ]);

        $this->assertEquals('UPDATE `users` SET `name` = \'Bob\' WHERE `name` = \'Paul\';', $sql);
    }

    public function testUpdateWithLimit() {
        $sql = $this->builder->update([
            'table' => 'users',
            'data' => [
                'name' => 'Bob'
            ],
            'where' => [
                'name' => 'Paul'
            ],
            'limit' => [23, 43]
        ]);

        $this->assertEquals('UPDATE `users` SET `name` = \'Bob\' WHERE `name` = \'Paul\' LIMIT 23, 43;', $sql);
    }

    public function testUpdateWithOrder() {
        $sql = $this->builder->update([
            'table' => 'users',
            'data' => [
                'name' => 'Bob'
            ],
            'where' => [
                'name' => 'Paul'
            ],
            'order' => [
                ['name', 'DESC']
            ]
        ]);

        $this->assertEquals('UPDATE `users` SET `name` = \'Bob\' WHERE `name` = \'Paul\' ORDER BY `name` DESC;', $sql);
    }

    public function testUpdateWithOrderAndLimit() {
        $sql = $this->builder->update([
            'table' => 'users',
            'data' => [
                'name' => 'Bob'
            ],
            'where' => [
                'name' => 'Paul'
            ],
            'order' => [
                ['name', 'DESC']
            ],
            'limit' => [23, 43]
        ]);

        $this->assertEquals('UPDATE `users` SET `name` = \'Bob\' WHERE `name` = \'Paul\' ORDER BY `name` DESC LIMIT 23, 43;', $sql);
    }

    public function testUpdateWithoutWhere() {
        $sql = $this->builder->update('users', [
            'name' => 'Bob'
        ]);

        $this->assertEquals('UPDATE `users` SET `name` = \'Bob\';', $sql);
    }

    public function testUpdateWithComplexWhere() {
        $sql = $this->builder->update('users', [
            'name' => 'Bob'
        ], [
            'name' => Database::where_not('nat')
        ]);

        $this->assertEquals('UPDATE `users` SET `name` = \'Bob\' WHERE NOT `name` = \'nat\';', $sql);
    }

    public function testUpdateMultipleValues() {
        $sql = $this->builder->update('users', [
            'name' => 'Bob',
            'logins' => 5
        ]);

        $this->assertEquals('UPDATE `users` SET `name` = \'Bob\', `logins` = 5;', $sql);
    }

    public function testUpdateChained() {
        $sql = $this->builder->start()->update('users')->set('name', 'nat')->where('name', 'bob')->end();
        $this->assertEquals('UPDATE `users` SET `name` = \'nat\' WHERE `name` = \'bob\';', $sql);
    }

    public function testUpdateChainedOrder() {
        $sql = $this->builder->start()->update('users')->set('name', 'nat')->where('name', 'bob')->order('name', 'desc')->end();
        $this->assertEquals('UPDATE `users` SET `name` = \'nat\' WHERE `name` = \'bob\' ORDER BY `name` DESC;', $sql);
    }

    public function testUpdateChainedLimit() {
        $sql = $this->builder->start()->update('users')->set('name', 'nat')->where('name', 'bob')->limit(22, 56)->end();
        $this->assertEquals('UPDATE `users` SET `name` = \'nat\' WHERE `name` = \'bob\' LIMIT 22, 56;', $sql);
    }

    public function testUpdateChainedOrderAndLimit() {
        $sql = $this->builder->start()->update('users')->set('name', 'nat')->where('name', 'bob')->order('name', 'desc')->limit(22, 56)->end();
        $this->assertEquals('UPDATE `users` SET `name` = \'nat\' WHERE `name` = \'bob\' ORDER BY `name` DESC LIMIT 22, 56;', $sql);
    }

    public function testUpdateChainedWithoutStart() {
        $sql = $this->builder->update('users')->set('name', 'nat')->where('name', 'bob')->end();
        $this->assertEquals('UPDATE `users` SET `name` = \'nat\' WHERE `name` = \'bob\';', $sql);
    }

    public function testInsert() {
        $sql = $this->builder->insert('users', [
            'name' => 'Joe',
            'email' => 'joe.is@awesome.com'
        ]);

        $this->assertEquals('INSERT INTO `users` (`name`, `email`) VALUES (\'Joe\', \'joe.is@awesome.com\');', $sql);
    }

    public function testInsertChained() {
        $sql = $this->builder->start()->insert('users')->set('name', 'Joe')->set('email', 'joe.is@awesome.com')->end();
        $this->assertEquals('INSERT INTO `users` (`name`, `email`) VALUES (\'Joe\', \'joe.is@awesome.com\');', $sql);
    }
    public function testInsertChainedWithoutStart() {
        $sql = $this->builder->insert('users')->set('name', 'Joe')->set('email', 'joe.is@awesome.com')->end();
        $this->assertEquals('INSERT INTO `users` (`name`, `email`) VALUES (\'Joe\', \'joe.is@awesome.com\');', $sql);    
    }

    public function testDelete() {
        $sql = $this->builder->delete('users', [
            'name' => 'Harry'
        ]);

        $this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\';', $sql);
    }

    public function testDeleteWithOrder() {
        $sql = $this->builder->delete([
            'table' => 'users',
            'where' => [
                'name' => 'Harry'
            ],
            'order' => [
                ['name', 'DESC']
            ]
        ]);

        $this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\' ORDER BY `name` DESC;', $sql);
    }

    public function testDeleteWithLimit() {
        $sql = $this->builder->delete([
            'table' => 'users',
            'where' => [
                'name' => 'Harry'
            ],
            'order' => [
                ['name', 'DESC']
            ],
            'limit' => [26, 50]
        ]);

        $this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\' ORDER BY `name` DESC LIMIT 26, 50;', $sql);
    }

    public function testDeleteWithOrderAndLimit() {
        $sql = $this->builder->delete([
            'table' => 'users',
            'where' => [
                'name' => 'Harry'
            ],
            'limit' => [26, 50]
        ]);

        $this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\' LIMIT 26, 50;', $sql);
    }


    public function testDeleteChained() {
        $sql = $this->builder->start()->delete('users')->where('name', 'Harry')->end();
        $this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\';', $sql);
    }

    public function testDeleteChainedWithoutStart() {
        $sql = $this->builder->delete('users')->where('name', 'Harry')->end();
        $this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\';', $sql);
    }

    public function testDeleteChainedWithOrder() {
        $sql = $this->builder->start()->delete('users')->where('name', 'Harry')->order('name', 'desc')->end();
        $this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\' ORDER BY `name` DESC;', $sql);
    }

    public function testDeleteChainedWithLimit() {
        $sql = $this->builder->start()->delete('users')->where('name', 'Harry')->limit(22, 30)->end();
        $this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\' LIMIT 22, 30;', $sql);
    }

    public function testDeleteChainedWithLimitAndOrder() {
        $sql = $this->builder->start()->delete('users')->where('name', 'Harry')->order('name', 'desc')->limit(22, 30)->end();
        $this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\' ORDER BY `name` DESC LIMIT 22, 30;', $sql);
    }

    public function testDeleteWithComplexWhere() {
        $sql = $this->builder->delete('users', [
            'logins' => Database::where_gt(5)
        ]);

        $this->assertEquals('DELETE FROM `users` WHERE `logins` > 5;', $sql);
    }

    public function testDeleteWithMultComplexWhere() {
        $sql = $this->builder->delete('users', [
            'logins' => Database::where_gt(5),
            'name' => Database::where_or('Josh')
        ]);

        $this->assertEquals('DELETE FROM `users` WHERE `logins` > 5 OR `name` = \'Josh\';', $sql);
    }

    public function testDeleteWithoutWhere() {
        $sql = $this->builder->delete('users');
        $this->assertEquals('DELETE FROM `users`;', $sql);
    }

    public function testChainToString() {
        $sql = (string) $this->builder->select('users');
        $this->assertEquals('SELECT * FROM `users`;', $sql);
    }

    public function testOrGtChaing() {
        $sql = $this->builder->select('users')->where('name', 'nat')->where_or_gt('logins', 5)->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `name` = \'nat\' OR `logins` > 5;', $sql);
    }
    
    public function testBasicGroupChain() {
        $sql = $this->builder->select('users')->where(['name' => 'nat'])->end();
        $this->assertEquals('SELECT * FROM `users` WHERE (`name` = \'nat\');', $sql);
    }

    public function testBasicGroupChainWithOther() {
        $sql = $this->builder->select('users')->where(['name' => 'nat'])->where_gt('logins', 5)->end();
        $this->assertEquals('SELECT * FROM `users` WHERE (`name` = \'nat\') AND `logins` > 5;', $sql);
    }

    public function testAdvancedGroupChain() {
        $sql = $this->builder->start()->select('users')->where('name', 'nat')->where_or(['id' => 2, 'logins' => Database::where_or(Database::where_gt(5))])->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `name` = \'nat\' OR (`id` = 2 OR `logins` > 5);', $sql);
    }

    public function testNestedGroupChain() {
        $sql = $this->builder->start()->select('users')->where('name', 'nat')->where_or(['id' => 2, 'logins' => Database::where_or(Database::where_gt(5)), ['name' => 'joe']])->end();
        $this->assertEquals('SELECT * FROM `users` WHERE `name` = \'nat\' OR (`id` = 2 OR `logins` > 5 AND (`name` = \'joe\'));', $sql);
    }

    public function testGroupFunction() {
        $sql = $this->builder->start()->select('users')->where('name', 'nat')->where_or(function() {;
            $this->where('id', 2)->where_or_gt('logins', 5);
        })->end();

        $this->assertEquals('SELECT * FROM `users` WHERE `name` = \'nat\' OR (`id` = 2 OR `logins` > 5);', $sql);
    }

    public function testGroupFunctionMultiLayer() {
        $sql = $this->builder->start()->select('users')->where('name', 'nat')->where_or(function() {
            $this->where('id', 2)->where_or_gt('logins', 5)->where(function() {
                $this->where('name', 'joe');
            });
        })->end();

        $this->assertEquals('SELECT * FROM `users` WHERE `name` = \'nat\' OR (`id` = 2 OR `logins` > 5 AND (`name` = \'joe\'));', $sql);
    }

    public function testLimitWithOffset() {
        $sql = $this->builder->select('users', [
            'limit' => 1,
            'offset' => 1
        ]);

        $this->assertEquals('SELECT * FROM `users` LIMIT 1 OFFSET 1;', $sql);
    }

    public function testLimitWithOffsetChained() {
        $sql = $this->builder->select('users')->limit(1)->offset(1)->end();

        $this->assertEquals('SELECT * FROM `users` LIMIT 1 OFFSET 1;', $sql);
    }

    public function testLimitDeleteOffset() {
        $sql = $this->builder->delete([
            'table' => 'users',
            'limit' => 1,
            'offset' => 3
        ]);

        $this->assertEquals('DELETE FROM `users` LIMIT 1 OFFSET 3;', $sql);
    }

    public function testLimitUpdateOffset() {
        $sql = $this->builder->update([
            'table' => 'users',
            'data' => [
                'name' => 'Joe'
            ],
            'limit' => 1,
            'offset' => 1
        ]);

        $this->assertEquals('UPDATE `users` SET `name` = \'Joe\' LIMIT 1 OFFSET 1;', $sql);
    }

    public function testWhereValIsNull() {
        $sql = $this->builder->select('users')->where('name', 'Nat')->where('suspended', null)->end();

        $this->assertEquals('SELECT * FROM `users` WHERE `name` = \'Nat\' AND `suspended` IS NULL', $sql);
    }

}