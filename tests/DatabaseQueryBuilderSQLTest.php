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

}