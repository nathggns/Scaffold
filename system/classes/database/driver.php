<?php defined('SCAFFOLD') or die();

/**
 * Database drivers should extend this abstract class.
 *
 * This is to allow us to share some common functionality.
 */
abstract class DatabaseDriver implements DatabaseDriverInterface {

    public function __construct(DatabaseQueryBuilder $builder, $config) {
        $this->builder = $builder;
        $this->config = $config;
        $this->connect();
    }
}
