<?php defined('SCAFFOLD') or die();

/**
 * All DatabaseQueryBuilders must implement this interface.
 *
 * This allows us Scaffold to know how to use it.
 */
interface DatabaseQueryBuilderInterface {
    public function select($table, $vals = ['*'], $conds = [], $group = [], $order = []);
    public function insert($table, $data);
    public function update($table, $data, $where = []);
    public function structure($table);
}
