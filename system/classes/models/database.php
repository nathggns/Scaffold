<?php defined('SCAFFOLD') or die;

/**
 * Lazy loaded Database Model
 * 
 * @todo Data Validation
 * @todo HABTM
 * @todo More advanced finding
 * @todo Export data as array
 * @todo Writing
 * @todo Deleting
 */
class ModelDatabase implements ArrayAccess {

	const HAS_ONE = 1;
	const HAS_MANY = 2;
	const BELONGS_TO = 3;

	const MODE_SINGLE = 4;
	const MODE_MULT = 5;

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
	 * The main row of data.
	 */
	protected $data = [];

	/**
	 * An array of model objects.
	 */
	protected $rows = [];

	/**
	 * Store the current fetch mode
	 */
	protected $mode;

	/**
	 * Our relationships
	 */
	protected $relationships = [];

	/**
	 * Updated values
	 */
	protected $updated = [];

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

		// Load in our relationships...
		$this->relationships();

		// If we have an id, 'become' it
		if (!is_null($id)) {
			$this->fetch(['id' => $id]);
		}
	}

	public function fetch($conditions = []) {
		$this->mode = static::MODE_SINGLE;
		$this->conditions = $conditions;

		return $this;
	}

	public function fetch_all($conditions = []) {
		$this->mode = static::MODE_MULT;
		$this->conditions = $conditions;

		return $this;
	}

	/**
	 * Save data.
	 */
	public function save() {

		if (count($this->updated) > 0 && $this->mode === static::MODE_SINGLE) {
			$this->driver->update($this->table_name, $this->updated, [
				'id' => $this->id
			]);
		}

		return $this;
	}

	protected function relationships() {
		// We don't have any default relationships
	}

	protected function hasMany($model, $alias = null, $foreign_key = null) {
		$this->relationship(static::HAS_MANY, $model, $alias, $foreign_key);
	}

	protected function hasOne($model, $alias = null, $foreign_key = null) {
		$this->relationship(static::HAS_ONE, $model, $alias, $foreign_key);
	}

	protected function belongsTo($model, $alias = null, $foreign_key = null) {
		$this->relationship(static::BELONGS_TO, $model, $alias, $foreign_key);
	}

	protected function relationship($type, $model, $alias = null, $foreign_key = null) {
		$this->relationships[$type][$model] = [
			'model' => $model,
			'alias' => $alias,
			'foreign_key' => $foreign_key
		];
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

		if ($this->mode !== static::MODE_SINGLE || (count($this->data) > 0 && !isset($this->data[$key]))) {
			throw new Exception('Property ' . $key . ' does not exist on model ' . $this->name);
		}

		if (isset($this->data[$key])) {
			return $this->data[$key];
		}

		$this->driver->find(
			$this->table_name,
			[
				'where' => $this->conditions
			]
		);

		$result = $this->driver->fetch();

		foreach ($this->relationships as $type => $relationships) {
			foreach ($relationships as $model => $rel) {
				$class_name = static::$prefix . $model;
				$obj = new $class_name;

				$foreign_key = $rel['foreign_key'];

				if (!$foreign_key) {
					switch ($type) {
						case static::HAS_MANY:
						case static::HAS_ONE:
							$foreign_key = Inflector::singularize($this->table_name) . '_id';
						break;

						case static::BELONGS_TO:
							$foreign_key = Inflector::singularize($obj->table_name) . '_id';
						break;
					}
				}

				switch ($type) {
					case static::HAS_MANY:
						$obj->fetch_all([
							$foreign_key => $result['id']
						]);
					break;

					case static::HAS_ONE:
						$obj->fetch([
							$foreign_key => $result['id']
						]);
					break;

					case static::BELONGS_TO:
						$obj->fetch([
							'id' => $result[$foreign_key]
						]);
					break;
				}

				$alias = $rel['alias'];

				if (!$alias) {
					$alias = $obj->table_name;

					if (in_array($type, [static::HAS_ONE, static::BELONGS_TO])) {
						$alias = Inflector::singularize($alias);
					}
				}

				$result[$alias] = $obj;

			}
		}

		$this->data = $result;

		return $this->data[$key];	
	}

	public function __set($key, $value) {

		if (isset($this->schema[$key])) {
			$this->updated[$key] = $value;
			
			if (isset($this->data[$key])) {
				$this->data[$key] = $value;
			}

		}

	}

	public function offsetExists($offset) {
		return isset($this->rows[$offset]);
	}

	public function offsetGet($offset) {
		if ($this->mode != static::MODE_MULT) {
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