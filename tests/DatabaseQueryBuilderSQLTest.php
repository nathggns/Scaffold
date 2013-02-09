<?php

class DatabaseQueryBuilderSQLTest extends PHPUnit_Framework_Testcase {

	public function setUp() {
		$this->builder = new DatabaseQueryBuilderSQL();
	}

	public function testBasicSelect() {
		$sql = $this->builder->select(['table' => 'users']);

		$this->assertEquals($sql, 'SELECT * FROM `users`;');
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

}