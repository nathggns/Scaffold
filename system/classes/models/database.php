<?php defined('SCAFFOLD') or die;

class ModelDatabase {

	/**
	 * Models name, used for relating it to a database table.
	 * 
	 * This can be set by the child class, or can be ripped from the class
	 * name.
	 */
	protected $name;

	/**
	 * The database table this class is supposed to represent.
	 *
	 * If not provided, we can guess it using the Inflector.
	 */
	protected $table_name;

	/**
	 * Class name. Used for various calculations
	 */
	protected $class_name;

	/**
	 * Model class name prefix. Shouldn't need to change if the application
	 * follows autoloader rules.
	 */
	protected $prefix = 'Model';

	/**
	 * Names of all properties, to avoid saving class data to a row.
	 */
	protected $properties = [];

	/**
	 * Row data
	 */
	protected $row = [];

	/**
	 * Database driver
	 */
	protected $driver;

	/**
	 * Inital Setup
	 */
	public function __construct($id = null) {
		// Set our base properties, so that we know which properties are 'data' properties.
		$this->properties = array_keys(get_object_vars($this));

		// Store the class name
		$this->class_name = get_class($this);

		// If we dont have a name, guess it.
		if (!$this->name) {
			$this->name = $this->guess_name($this->class_name);
		}

		// If we don't have a table_name, guess it.
		if (!$this->table_name) {
			$this->table_name = $this->guess_table_name($this->name);
		}

		// Store out database connection
		$this->driver = Service::get('database');
	}

	/**
	 * Read a row
	 */
	public function read($id) {
		$this->row['id'] = $id;

		return $this->fetch();
	}

	/**
	 * Find a row, based on our row data.
	 */
	public function fetch() {
		/* @TODO: Actaully fetch the data... */

		return [];
	}

	/**
	 * Guess the name of our model
	 */
	private function guess_name($class) {
		$length = strlen($this->prefix);

		if (substr($class, 0, $length) === $this->prefix) {
			return substr($class, $length);
		}

		return $class;
	}

	/**
	 * Guess the table name of our model
	 */
	private function guess_table_name($name) {
		return Inflector::tableize($name);
	}

}