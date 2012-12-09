<?php defined('SCAFFOLD') or die;

interface ModelInterface extends ArrayAccess {

	public function __get($key);
	public function __set($key, $val);

	public function save();
	public function fetch($conditions);
	public function fetch_all($conditions);
	public function create();
	public function delete();
	public function reset();
	public function find($conditions, $mode = null);
}