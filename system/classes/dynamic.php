<?php defined('SCAFFOLD') or die;

/**
 * A dynamic object that supports properties and methods.
 */
class Dynamic {

	/**
	 * Constructor function. An array of methods and functions
	 */
	public function __construct(array $arr) {
		foreach ($arr as $key => $val) {
			$this->$key = $val;
		}
	}

	/**
	 * Handle method calling
	 */
	public function __call($name, $args) {
		array_unshift($args, $this);
		if (property_exists($this, $name) && is_callable($this->$name)) {
			call_user_func_array($this->$name, $args);
		} else {
			throw new Exception('Method ' . $name . ' not found');
		}
	}

}