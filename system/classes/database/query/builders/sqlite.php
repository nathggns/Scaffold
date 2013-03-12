<?php defined('SCAFFOLD') or die;

/**
 * SQLite builder
 */
class DatabaseQueryBuilderSqlite extends DatabaseQueryBuilderSQL {

    function structure($table) {
        return 'PRAGMA table_info(' . $this->backtick($table) . ');';
    }

    function prevent_locking() {
        return 'PRAGMA journal_mode=WAL;';
    }

}