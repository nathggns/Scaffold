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

	protected $relationships = [];

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
		$this->mode = 'single';
		$this->conditions = $conditions;

		return $this;
	}

	public function fetch_all($conditions = []) {
		$this->mode = 'multi';
		$this->conditions = $conditions;

		return $this;
	}

	protected function relationships() {
		// We don't have any default relationships
	}

	protected function hasMany($model, $alias = null, $foreign_key = null) {
		$this->relationship('hasMany', $model, $alias, $foreign_key);
	}

	protected function hasOne($model, $alias = null, $foreign_key = null) {
		$this->relationship('hasOne', $model, $alias, $foreign_key);
	}

	protected function belongsTo($model, $alias = null, $foreign_key = null) {
		$this->relationship('belongsTo', $model, $alias, $foreign_key);
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

		if ($this->mode !== 'single' || (count($this->data) > 0 && !isset($this->data[$key]))) {
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

			foreach ($this->relationships as $type => $relationships) {
				foreach ($relationships as $model => $rel) {
					$class_name = static::$prefix . $model;
					$obj = new $class_name;

					$foreign_key = $rel['foreign_key'];

					if (!$foreign_key) {
						switch ($type) {
							case 'hasMany':
							case 'hasOne':
								$foreign_key = Inflector::singularize($this->table_name) . '_id';
							break;

							case 'belongsTo':
								$foreign_key = Inflector::singularize($obj->table_name) . '_id';
							break;
						}
					}

					switch ($type) {
						case 'hasMany':
							$obj->fetch_all([
								$foreign_key => $result['id']
							]);
						break;

						case 'hasOne':
							$obj->fetch([
								$foreign_key => $result['id']
							]);
						break;

						case 'belongsTo':
							$obj->fetch([
								'id' => $result[$foreign_key]
							]);
						break;
					}

					$alias = $rel['alias'];

					if (!$alias) {
						$alias = $obj->table_name;

						if (in_array($type, ['hasOne', 'belongsTo'])) {
							$alias = Inflector::singularize($alias);
						}
					}

					$result[$alias] = $obj;

				}
			}

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