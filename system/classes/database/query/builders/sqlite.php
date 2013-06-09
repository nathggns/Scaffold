<?php defined('SCAFFOLD') or die;

/**
 * SQLite builder
 */
class DatabaseQueryBuilderSqlite extends DatabaseQueryBuilderSQL {

    public $type = 'sqlite';

    public function structure($table) {
        return 'PRAGMA table_info(' . $this->backtick($table) . ');';
    }

    public function prevent_locking() {
        return 'PRAGMA journal_mode=WAL;';
    }

    public function clear($table) {
        return 'DELETE FROM ' . $this->backtick($table) . ';';
    }

}