<?php defined('SCAFFOLD') or die;

/**
 * All DatabaseQueryBuilders must implement this interface.
 *
 * This allows us Scaffold to know how to use it.
 */
interface DatabaseQueryBuilderInterface {

    // Should be implemented by the class
    public function select();
    public function count();
    public function insert($table, $data);
    public function update();
    public function structure($table);
    public function delete();
    
    // Should be implemented by the builder base class
    public function start($type = null, $opts = []);
    public function end();
    public function __toString();
    public function get_conds();
    public function distinct();
    public function where($key, $val = null);
    public function __call($name, $args);
    public function group();
    public function order($col, $dir = false);
    public function limit($start, $end = null);
    public function set($key, $value);
    public function offset($offset);
    public function val($val, $clear_star = true);
}
