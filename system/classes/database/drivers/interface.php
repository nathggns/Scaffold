<?php defined('SCAFFOLD') or die;

/**
 * All DatabaseDrivers must implement this interface.
 */
interface DatabaseDriverInterface {
    public function __construct(DatabaseQueryBuilder $builder, $config);
    public function connect();
    public function find($table, $options = false);
    public function fetch($table = null, $options = null);
    public function fetch_all($table = null, $options = null);
    public function count();
    public function structure($table);
    public function insert($table, $data);
    public function update($table, $data = false, $where = false);
    public function delete($table, $where = []);
    public function id();
    public function clear($table);
}
