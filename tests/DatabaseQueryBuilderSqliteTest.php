<?php

class DatabaseQueryBuilderSqliteTest extends DatabaseQueryBuilderSQLTest {

    public function setUp() {
        $this->builder = new DatabaseQueryBuilderSqlite();
    }

    public function testClear() {
        $sql = $this->builder->clear('users');

        $this->assertEquals('DELETE FROM `users`;', $sql);
    }
}
