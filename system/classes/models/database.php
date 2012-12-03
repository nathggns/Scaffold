<?php defined('SCAFFOLD') or die;

/**
 * Basic Database Model.
 *
 * @todo Relationship Types
 * @todo Validation
 * @todo ORM
 * @todo Writing
 * @todo Initiate as a record
 */
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
	protected static $prefix = 'Model';

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
	 * All the models we have to request data from
	 */
	protected $models = [];

	/**
	 * Our relationships
	 */
	protected $relationships = [];

	protected $schema = [];

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

		// Initiate all of our models
		foreach ($this->relationships as $type => $relationships) {
			foreach ($relationships as $relationship) {
				$className = static::$prefix . $relationship;
				$this->models[$relationship] = [
					'obj' => new $className,
					'type' => $type
				];
			}
		}

		// Store out database connection
		$this->driver = Service::get('database');

		$this->schema = $this->driver->structure($this->table_name)->fetch_all();

		foreach ($this->schema as $k => $f) {
			$this->schema[$k] = $f['Field'];
		}
	}

	/**
	 * Find a row, based on our row data.
	 *
	 * @todo Use on query instead of many.
	 *       I'm having trouble making this work with one query, so I am going to use multiple queries instead.
	 */
	public function fetch($conditions = []) {

		$table_conditions = [];

		foreach ($conditions as $key => $val) {
			
			if (strpos($key, '.') === false) {
				$key = $this->name . '.' . $key;
			}

			list($table, $key) = explode('.', $key);
			
			$table_conditions[$table][$key] = $val;

		}

		$rows = $this->driver->find($this->table_name, [
			'where' => $table_conditions[$this->name]
		])->fetch_all();

		$real_rows = [];

		foreach ($rows as $row) {
			$real_row = [];

			$real_row[$this->name] = $row;
			
			$real_rows[] = $real_row;
		}

		$single = Inflector::singularize($this->table_name);

		foreach ($real_rows as $key => $real_row) {

			foreach ($this->models as $model) {

				$real_row[$model['obj']->name] = $this->driver->find($model['obj']->table_name, [
					'where' => [
						$single . '_id' => $real_row[$this->name]['id']
					]
				])->fetch_all();

			}

			$real_rows[$key] = $real_row;

		}
		
		return $real_row;

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
	 * Forward all property setting to the row property
	 */
	public function __set($name, $value) {
		return $this->row[$name] = $value;
	}

	/**
	 * Forward all property getting to the row property
	 */
	public function __get($name) {
		return $this->row[$name];
	}


}