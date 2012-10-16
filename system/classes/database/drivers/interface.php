<?php defined('SCAFFOLD') or die();

/**
 * All DatabaseDrivers must implement this interface.
 */
interface DatabaseDriverInterface {
    public function __construct(DatabaseQueryBuilder $builder, $config);
    public function connect();
    public function find();
    public function fetch();
    public function fetch_all();
}
