<?php defined('SCAFFOLD') or die;

class ModelDatabase implements ArrayAccess {

	/**
	 * List of all default properties in the model
	 */
	protected $properties;

	/**
	 * Database driver
	 */
	protected $driver;

	/**
	 * What is the model prefixed with?
	 */
	protected static $prefix = 'Model';

	/**
	 * Schema for the table
	 */
	protected $schema = [];

	/**
	 * We need to know the class name in order to guess other properties if they're not provided.
	 */
	protected $class_name;

	/**
	 * The table the model represents
	 */
	protected $table_name;

	/**
	 * The model name
	 */
	protected $name;

	/**
	 * The conditions for the model
	 */
	protected $conditions = [];

	/**
	 * Rows of data
	 */
	protected $data = [];

	protected $mode = 'multi';

	protected $rows = [];

	/**
	 * Inital Setup
	 */
	public function __construct($id = null) {

		$this->properties = array_keys(get_object_vars($this));

		// Store our database connection
		$this->driver = Service::get('database');

		// Set all of our properties
		if (!$this->class_name) {
			$this->class_name = get_class($this);
		}

		if (!$this->name) {
			$this->name = $this->guess_name($this->class_name);
		}

		if (!$this->table_name) {
			$this->table_name = $this->guess_table_name($this->name);
		}

		$structure = $this->driver->structure($this->table_name)->fetch_all();

		foreach ($structure as $row) {
			$this->schema[$row['Field']] = $row;
		}

		// If we have an id, 'become' it
		if (!is_null($id)) {
			$this->fetch(['id' => $id]);
		}
	}

	public function fetch($conditions = []) {
		$this->mode = 'single';
		$this->conditions = $conditions;

		return $this;
	}

	public function fetch_all($conditions = []) {
		$this->mode = 'multi';
		$this->conditions = $conditions;

		return $this;
	}

	/**
	 * Guess the name of our model
	 */
	private function guess_name($class) {
		$length = strlen(static::$prefix);

		if (substr($class, 0, $length) === static::$prefix) {
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

	/**
	 * Handle gets
	 */
	public function __get($key) {
		if (!isset($this->schema[$key]) || $this->mode !== 'single') {
			throw new Exception('Property ' . $key . ' does not exist on model ' . $this->name);
		}

		if (isset($this->data[$key])) {
			return $this->data[$key];
		}

		if ($this->mode === 'single') {
			$this->driver->find(
				$this->table_name,
				[
					'where' => $this->conditions
				]
			);

			$result = $this->driver->fetch();
			$this->data = $result;

			return $this->data[$key];	
		}
	}

	public function offsetExists($offset) {
		return true;
	}

	public function offsetGet($offset) {
		if ($this->mode != 'multi') {
			throw new Exception('Cannot access row via index');
		}

		if (isset($this->rows[$offset])) {
			return $this->rows[$offset];
		}

		$this->rows = [];

		$this->driver->find(
			$this->table_name,
			[
				'vals' => ['id'],
				'where' => $this->conditions
			]
		);

		$results = $this->driver->fetch_all();

		$class = $this->class_name;

		foreach ($results as $result) {
			$this->rows[] = new $class($result['id']);
		}

		return $this->rows[$offset];
	}

	public function offsetSet($offset, $value) {
		return null;
	}

	public function offsetUnset($offset) {
		return null;
	}

}