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
				$className = $this->prefix . $relationship;
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
	 */
	public function fetch($conditions) {
		/* @TODO: Actaully fetch the data... */

		$tables = [$this->table_name => $this->name];
		$fields = [];

		foreach ($this->schema as $field) {
			$fields[$this->name . '.' . $field] = $this->name . '_' . $field;
		}

		foreach ($this->models as $name => $model) {
			$tables[$model['obj']->table_name] = $model['obj']->name;

			foreach ($model['obj']->schema as $field) {
				$fields[$model['obj']->name . '.' . $field] = $model['obj']->name . '_' . $field;
			}

		}

		$having = [];
		foreach ($conditions as $key => $val) {
			if (strpos($val, '.') === false) {
				$key = $this->name . '.' . $key;
			}

			$having[$key] = $val;
		}

		$conditions = $having;

		foreach ($this->models as $name => $rel) {
			$condition = [];

			switch ($rel['type']) {
				case 'oneToMany':
				case 'oneToOne':
					$condition[] = $name . '.' . Inflector::singularize($this->table_name) . '_id' . ' = ' . $this->name . '.id';

				break;
			}

			$conditions = array_merge($conditions, $condition);
		}

		$results = $this->driver->find($tables, array('having' => $conditions, 'vals' => $fields))->fetch_all();

		$data = [];
		$names = array_keys($this->models);

		foreach ($results as $result) {
			$pieces = $this->expand($result, $fields);

			foreach ($pieces as $name => $piece) {
				if (in_array($name, $names)) {
					$data[$name][] = $piece;
				} else if (!isset($data[$name])) {
					$data[$name] = $piece;
				}
			}
		}

		return $data;
	}

	private function expand($object, $fields) {
		$reverse = array_flip($fields);
		$data = [];

		foreach ($object as $key => $val) {
			$realkey = $reverse[$key];
			$parts = explode('.', $realkey);
			$piece = $val;

			while (!empty($parts)) {
				$piece = [array_pop($parts) => $piece];
			}

			$data = array_merge_recursive($data, $piece);
		}

		return $data;
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