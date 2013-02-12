<?php

class DatabaseQueryBuilderSQLTest extends PHPUnit_Framework_Testcase {

	public function setUp() {
		$this->builder = new DatabaseQueryBuilderSQL();
	}

	public function testBasicSelect() {
		$sql = $this->builder->select(['table' => 'users']);
		$this->assertEquals($sql, 'SELECT * FROM `users`;');
	}

	public function testBasicSelectWithStringForTable() {
		$sql = $this->builder->select('users');
		$this->assertEquals($sql, 'SELECT * FROM `users`;');
	}

	public function testBasicSelectChained() {
		$sql = $this->builder->start()->select('users')->end();
		$this->assertEquals($sql, 'SELECT * FROM `users`;');
	}

	public function testBasicSelectChainedWithArgs() {
		$sql = $this->builder->start()->select('users')->where('id', 1)->end();
		$this->assertEquals('SELECT * FROM `users` WHERE `id` = 1;', $sql);
	}

	public function testSelectChainedWithMultArgsOr() {
		$sql = $this->builder->start()->select('users')->where('id', 1)->where_or('name', 2)->end();
		$this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 OR `name` = 2;', $sql);
	}

	public function testSelectChainedWithMultArgsAnd() {
		$sql = $this->builder->start()->select('users')->where('id', 1)->where_and('name', 2)->end();
		$this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 AND `name` = 2;', $sql);
	}

	public function testSelectChainedWithMultArgsAndSeperate() {
		$sql = $this->builder->start()->select('users')->where('id', 1)->where_or()->where('name', 2)->end();
		$this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 OR `name` = 2;', $sql);
	}

	public function testSelectChainedOrder() {
		$sql = $this->builder->start()->select('users')->order('id')->end();
		$this->assertEquals('SELECT * FROM `users` ORDER BY `id` ASC;', $sql);
	}

	public function testSelectChainedOrderSpecifiedASC() {
		$sql = $this->builder->start()->select('users')->order('id', 'asc')->end();
		$this->assertEquals('SELECT * FROM `users` ORDER BY `id` ASC;', $sql);
	}

	public function testSelectChainedOrderSpecifiedDESC() {
		$sql = $this->builder->start()->select('users')->order('id', 'desc')->end();
		$this->assertEquals('SELECT * FROM `users` ORDER BY `id` DESC;', $sql);
	}

	public function testSelectChainedOrderMultiple() {
		$sql = $this->builder->start()->select('users')->order('id')->order('name')->end();
		$this->assertEquals('SELECT * FROM `users` ORDER BY `id` ASC, `name` ASC;', $sql);
	}

	public function testSelectChainedOrderMultipleSpecified() {
		$sql = $this->builder->start()->select('users')->order('id', 'desc')->order('name', 'asc')->end();
		$this->assertEquals('SELECT * FROM `users` ORDER BY `id` DESC, `name` ASC;', $sql);
	}

	public function testSelectChainedLimit() {
		$sql = $this->builder->start()->select('users')->limit(24)->end();
		$this->assertEquals('SELECT * FROM `users` LIMIT 0, 24;', $sql);
	}

	public function testSelectChainedLimitAdvanced() {
		$sql = $this->builder->start()->select('users')->limit(12, 24)->end();
		$this->assertEquals('SELECT * FROM `users` LIMIT 12, 24;', $sql);
	}

	public function testSelectChainedLimitAdvancedSeperate() {
		$sql = $this->builder->start()->select('users')->limit(12)->limit(24)->end();
		$this->assertEquals('SELECT * FROM `users` LIMIT 12, 24;', $sql);	
	}

	public function testSelectChainedNot() {
		$sql = $this->builder->start()->select('users')->where_not('name', 'nat')->end();
		$this->assertEquals('SELECT * FROM `users` WHERE NOT `name` = \'nat\';', $sql);
	}

	public function testSelectChainedGroup() {
		$sql = $this->builder->start()->select('users')->group('logins')->end();
		$this->assertEquals('SELECT * FROM `users` GROUP BY `logins`;', $sql);
	}

	public function testSelectChainedGroupMult() {
		$sql = $this->builder->start()->select('users')->group('logins', 'ip')->end();
		$this->assertEquals('SELECT * FROM `users` GROUP BY `logins`, `ip`;', $sql);
	}

	public function testSelectChainedWithMultArgsDefault() {
		$sql = $this->builder->start()->select('users')->where('id', 1)->where('name', 2)->end();
		$this->assertEquals('SELECT * FROM `users` WHERE `id` = 1 AND `name` = 2;', $sql);
	}

	public function testSelectChainedChoosingOperator() {
		$sql = $this->builder->start()->select('users')->where_gt('id', 4)->end();
		$this->assertEquals('SELECT * FROM `users` WHERE `id` > 4;', $sql);
	}

	public function testSelectChainedChoosingOperatorSeperate() {
		$sql = $this->builder->start()->select('users')->where_gt()->where('id', 4)->end();
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

	public function testInsert() {
		$sql = $this->builder->insert('users', [
			'name' => 'Joe',
			'email' => 'joe.is@awesome.com'
		]);

		$this->assertEquals('INSERT INTO `users` (`name`, `email`) VALUES (\'Joe\', \'joe.is@awesome.com\');', $sql);
	}

	public function testDelete() {
		$sql = $this->builder->delete('users', [
			'name' => 'Harry'
		]);

		$this->assertEquals('DELETE FROM `users` WHERE `name` = \'Harry\';', $sql);
	}

}