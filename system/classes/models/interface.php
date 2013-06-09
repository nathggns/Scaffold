<?php defined('SCAFFOLD') or die;

interface ModelInterface extends ArrayAccess, Iterator, Countable {

    public function __get($key);
    public function value($key);
    public function __set($key, $val);

    public function save();
    public function fetch($conditions);
    public function fetch_all($conditions);
    public function create();
    public function delete();
    public function reset();
    public function find($conditions, $mode = null);
    public function export($values = [], $level = 1);
    public function force_load();
    public function alias($alias, $key);
    public function virtual($field, $value);

    public function has_many();
    public function has_one();
    public function habtm();
    public function belongs_to();
    public function relationship($args);

    public function random();
    public function count();
    public function __call($key, $val);
}