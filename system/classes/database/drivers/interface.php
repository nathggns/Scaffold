<?php defined('SCAFFOLD') or die();

/**
 * All DatabaseDrivers must implement this interface.
 */
interface DatabaseDriverInterface {
    public function __construct(DatabaseQueryBuilder $builder, $config);
    public function connect();
    public function find($table, $options);
    public function fetch($table = null, $options = null);
    public function fetch_all($table = null, $options = null);
}
