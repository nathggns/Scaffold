<?php defined('SCAFFOLD') or die;

/**
 * Lazy loaded Database Model
 * @todo HABTM
 * @todo Export data as array
 */
class ModelDatabase extends Model {

	const HAS_ONE = 1;
	const HAS_MANY = 2;
	const BELONGS_TO = 3;
	const HABTM = 7;

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
	 * Our relationships
	 */
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

		// Let the child class do custom stuff.
		$this->init();

		// If we have an id, 'become' it
		if (!is_null($id)) {
			$this->fetch(['id' => $id]);
		}
	}

	public function fetch($conditions = []) {
		$this->reset();
		$this->mode = static::MODE_SINGLE;
		$this->conditions['where'] = $conditions;

		return $this;
	}

	public function fetch_all($conditions = []) {
		$this->reset();
		$this->mode = static::MODE_MULT;
		$this->conditions['where'] = $conditions;

		return $this;
	}

	public function find($conditions, $mode = null) {

		$this->reset();

		if (is_null($mode)) {
			$mode = static::MODE_MULT;
		}

		$this->mode = $mode;
		$this->conditions = $conditions;

		return $this;
	}

	public function create() {
		$this->reset();
		$this->mode = static::MODE_INSERT;

		return $this;
	}

	/**
	 * Save data.
	 */
	public function save() {

		if (!parent::save()) {
			return false;
		}

		if ($this->mode === static::MODE_INSERT) {
			$this->driver->insert($this->table_name, $this->data);
			$this->reset();
			$this->conditions = ['id' => $this->driver->id()];
		} else if (count($this->updated) > 0 && $this->mode === static::MODE_SINGLE) {
			$this->driver->update($this->table_name, $this->updated, [
				'id' => $this->id
			]);
		}

		return $this;
	}

	public function delete() {
		if ($this->mode !== static::MODE_SINGLE) {
			throw new Exception('Can\'t delete non-single row');
		}

		$id = $this->id;

		$this->driver->delete($this->table_name, ['id' => $id]);
		return $this->reset();

	}

	public function reset() {
		parent::reset();
		$this->conditions = [];

		return $this;
	}

	protected function init() {
		// Here to be overwritten
	}

	protected function has_many($model, $alias = null, $foreign_key = null, $local_key = 'id') {
		$this->relationship(static::HAS_MANY, $model, $alias, $foreign_key, $local_key);
	}

	protected function has_one($model, $alias = null, $foreign_key = null, $local_key = 'id') {
		$this->relationship(static::HAS_ONE, $model, $alias, $foreign_key, $local_key);
	}

	protected function belongs_to($model, $alias = null, $foreign_key = null, $local_key = 'id') {
		$this->relationship(static::BELONGS_TO, $model, $alias, $foreign_key, $local_key);
	}

	protected function habtm($model, $alias = null, $foreign_key, $local_key, $table_foreign_key, $table) {
		$this->relationship(static::HABTM, $model, $alias, $foreign_key, $local_key, ['table' => $table, 'table_foreign_key' => $table_foreign_key]);
	}

	protected function relationship($type, $model, $alias = null, $foreign_key = null, $local_key = 'id', $other = []) {
		$this->relationships[$type][$model] = array_merge([
			'model' => $model,
			'alias' => $alias,
			'foreign_key' => $foreign_key,
			'local_key' => $local_key
		], $other);
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
	 * 
	 * @todo Lazy load all properties
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
			$this->conditions
		);

		$result = $this->driver->fetch();

		foreach ($this->relationships as $type => $relationships) {
			foreach ($relationships as $model => $rel) {
				$class_name = static::$prefix . $model;

				$obj = new $class_name;

				$foreign_key = $rel['foreign_key'];
				$local_key = $rel['local_key'];

				$table_name = $obj->table_name;

				if (!$foreign_key) {
					switch ($type) {
						case static::HAS_MANY:
						case static::HAS_ONE:
						case static::BELONGS_TO:
							$foreign_key = Inflector::singularize($table_name) . '_' . $local_key;
						break;
					}
				}

				switch ($type) {
					case static::HAS_MANY:
						$obj->fetch_all([
							$foreign_key => $result[$local_key]
						]);
					break;

					case static::HAS_ONE:
						$obj->fetch([
							$foreign_key => $result[$local_key]
						]);
					break;

					case static::BELONGS_TO:
						$obj->fetch([
							$local_key => $result[$foreign_key]
						]);
					break;

					case static::HABTM:
					
						$this->driver->find($rel['table'], [
							'vals' => [$foreign_key],
							'where' => [
								$rel['table_foreign_key'] => $result[$local_key]
							]
						]);

						$results = $this->driver->fetch_all();

						$ids = array_map(function($a) use($foreign_key) {
							return $a[$foreign_key];
						}, $results);

						$obj->fetch_all([
							'id' => $ids
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
			array_merge([
				'vals' => ['id']
			], $this->conditions)
		);

		$results = $this->driver->fetch_all();

		$class = $this->class_name;
		
		foreach ($results as $result) {
			$this->rows[] = new $class($result['id']);
		}

		return $this->rows[$offset];
	}

}