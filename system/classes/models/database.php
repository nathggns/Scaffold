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
    protected static $static_schema = [];
    protected $schema;
    protected $schema_db;

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
    protected $defaults = [];

    /**
     * Our relationships
     */
    protected $relationships = [];

    /**
     * Virtual fields
     */
    protected $virtual_fields = [];

    /**
     * Default fields to export.
     * True means all. False, or empty array, means none.
     */
    protected $export_fields = true;

    /**
     * The default fields that a value passed to the constructor could be
     */
    protected static $default_fields = ['id'];

    /**
     * Data that has been fetched from the database but has not yet been added
     * to the database
     */
    protected $db_data = [];

    /**
     * Aliases
     */
    protected $aliases = [];

    /**
     * Inital Setup
     */
    public function __construct($id = null, $driver = null) {

        $this->properties = array_keys(get_object_vars($this));

        // Store our database connection
        $this->driver = $driver ? $driver : Service::get('database');

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

        if (!isset(static::$static_schema[$this->name])) {
            $structure = $this->driver->structure($this->table_name);

            foreach ($structure as $row) {
                static::$static_schema[$this->name][$row['field']] = $row;
            }
        }

        $this->schema = static::$static_schema[$this->name];
        $this->schema_db = static::$static_schema[$this->name];

        // Let the child class do custom stuff.
        $this->init();

        // If we have an id, 'become' it
        if (!is_null($id)) {
            $fields = static::$default_fields;

            if (!is_array($fields)) $fields = [$fields];

            $id = Database::where_or($id);
            $where = [];

            foreach ($fields as $field) {
                $where[$field] = $id;
            }

            $this->fetch($where);
        }
    }

    public function fetch($conditions = [], $reset = true) {
        if ($reset) $this->reset();
        $this->mode = static::MODE_SINGLE;
        $this->conditions['where'] = recursive_overwrite($this->conditions['where'], $conditions);

        return $this;
    }

    public function fetch_all($conditions = [], $reset = true) {
        if ($reset) $this->reset();
        $this->mode = static::MODE_MULT;
        $this->conditions['where'] = recursive_overwrite($this->conditions['where'], $conditions);

        return $this;
    }

    public function find($conditions, $mode = null, $reset = true) {

        if ($reset) $this->reset();

        if (is_null($mode)) {
            $mode = static::MODE_MULT;
        }

        $this->mode = $mode;
        $this->conditions = recursive_overwrite($this->conditions, $conditions);

        return $this;
    }

    public function create($data = null) {
        $this->reset();
        $this->mode = static::MODE_INSERT;

        if (!is_null($data)) return $this->save($data);

        return $this;
    }

    /**
     * Save data.
     */
    public function save($data = []) {

        if (!parent::save($data)) {
            return false;
        }

        if ($this->mode === static::MODE_INSERT) {

            if (isset($this->schema['created']) && !isset($this->data['created'])) {
                $this->data['created'] = time();
            }

            $this->driver->insert($this->table_name, $data = array_merge($this->data, $data));
            $this->reset();
            $this->fetch(['id' => $this->driver->id()]);

        } else if ((count($this->updated) > 0 || count($data) > 0) && $this->mode === static::MODE_SINGLE) {

            if (isset($this->schema['updated'])) {
                $this->updated['updated'] = time();
            }

            $this->driver->update($this->table_name, $data = array_merge($this->updated, $data), [
                'id' => $this->id
            ]); 

            foreach ($data as $key => $val) {
                $this->data[$key] = $val;
            }

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
        $this->db_data = [];

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

        if (!$alias) {

            $alias = strtolower($model);

            if (!in_array($type, [static::HAS_ONE, static::BELONGS_TO])) {
                $alias = Inflector::pluralize($alias);
            }
        }

        $this->relationships[$type][$model][] = array_merge([
            'model' => $model,
            'alias' => $alias,
            'foreign_key' => $foreign_key,
            'local_key' => $local_key
        ], $other);

        // @TODO Use a single method to build the array
        $this->schema[$alias] = [
            'field' => $alias
        ];
    }

    public function virtual($field, $value) {
        $this->virtual_fields[$field] = $value;
        $this->schema[$field] = [
            'field' => $field
        ];

        return $this;
    }

    public function export($values = null, $level = 1, $count_models = false) {

        if ($this->count() < 1) {

            if ($this->mode === static::MODE_MULT) {
                return [];
            } else {
                return null;
            }
        }

        $data = [];

        switch ($this->mode) {

            case static::MODE_MULT:
                foreach ($this as $item) {
                    $data[] = $item->export($values, $level - 1, $count_models);
                }
            break;

            case static::MODE_SINGLE:

                if (is_null($values)) {
                    $values = $this->export_fields;
                }

                if ($values === false) $values = [];

                if (!is_bool($values) && !is_array($values)) $values = [$values];

                $schema = array_keys($this->schema);

                if ($values !== true) {
                    $new = [];

                    foreach ($values as $key => $value) {
                        if (!is_array($value) && $key !== $value) {
                            $key = $value;
                        }
                        $new[$key] = $value;
                    }

                    $values = $new;
                } else {
                    $keys = array_keys($this->schema);
                    $values = array_combine($keys, $keys);
                }

                $schema = array_intersect($schema, array_keys($values));

                foreach ($schema as $key){
                    $value = $this->__get($key);

                    if ($value instanceof Model) {
                        if ($count_models) {
                            $value = $value->count();
                        } else if ($level > 0) {
                            $value = $value->export(is_array($values[$key]) ? $values[$key] : null, $level - 1, $count_models);
                        } else {
                            continue;
                        }
                    }

                    $data[$key] = $value;
                }

                if (false) {
                    

                    foreach ($schema as $key) {
                        $value = $this->__get($key);

                        if ($value instanceof Model) {

                            if ($count_models) {
                                $value = $value->count();
                            } else if ($level > 0) {
                                $value = $value->export(is_array($values[$key]) ? $values[$key] : null, $level - 1, $count_models);

                                if (is_array($value) && count($value) > 0) {
                                    $is_null = true;

                                    foreach ($value as $part) {
                                        if (!is_null($part)) {
                                            $is_null = false;
                                            break;
                                        }
                                    }

                                    if ($is_null) continue;
                                }
                            } else {
                                continue;
                            }
                        }

                        $data[$key] = $value;
                    }     
                }

               
            break;

            default:
                throw new Exception('Cannot export this model');
            break;
        }

        return $data;
    }

    public function force_load() {
        switch ($this->mode) {
            case static::MODE_SINGLE:
                $this->__get('id');
            break;

            case static::MODE_MULT:
                $this->offsetGet(0);
            break;
        }
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
     * Get the count
     *
     * @todo Implement Iterator instead.
     */
    public function count() {
        $this->__find();

        return $this->driver->count();
    }

    /**
     * Alias for ModelDatabase::value
     */
    public function __get($key) {
        return $this->value($key);
    }

    /**
     * Handle gets
     */
    public function value($key) {

        // Check for aliases
        if (isset($this->aliases[$key])) {
            $key = $this->aliases[$key];
        }

        // If we already have it, return it
        if (array_key_exists($key, $this->data)) {
            $value = $this->data[$key];

            if ($value instanceof Closure) {
                $value = $value->bindTo($this);
                $this->data[$key] = $value = call_user_func($value, $key);
            }

            return $value;
        }

        // If we won't be able to get it, throw an exception
        if ($this->mode !== static::MODE_SINGLE || (!array_key_exists($key, $this->schema) || $this->count() === 0)) {
            throw new Exception('Property ' . $key . ' does not exist on model ' . $this->name);
        }
        
        // If we have a virtual field for this
        if (isset($this->virtual_fields[$key])) {
            $this->data[$key] = $this->virtual_fields[$key];

            return $this->value($key);
        }

        // Let's check relationships...
        foreach ($this->relationships as $type => $relationships) {

            // Test the type..
            switch ($type) {
                case static::HAS_ONE:
                case static::HAS_MANY:
                case static::HABTM:
                case static::BELONGS_TO:
                    // Do nothing...
                break;

                default:
                    continue;
                break;
            }

            foreach ($relationships as $model => $rels) {
                foreach ($rels as $rel) {

                    // Only do the relationship if it's the key we're looking for
                    if ($rel['alias'] !== $key) continue;

                    $class_name = static::$prefix . $model;

                    $obj = new $class_name;

                    $foreign_key = $rel['foreign_key'];
                    $local_key = $rel['local_key'];

                    $table_name = $obj->table_name;

                    if (!$foreign_key) {
                        switch ($type) {
                            case static::HAS_MANY:
                            case static::HAS_ONE:
                                $foreign_key = Inflector::singularize($this->table_name) . '_' . $local_key;
                            break;

                            case static::BELONGS_TO:
                                $foreign_key = Inflector::singularize($table_name) . '_' . $local_key;
                            break;
                        }
                    }

                    switch ($type) {
                        case static::HAS_MANY:
                            $obj->fetch_all([
                                $foreign_key => $this->value($local_key)
                            ]);
                        break;

                        case static::HAS_ONE:
                            $obj->fetch([
                                $foreign_key => $this->value($local_key)
                            ]);
                        break;

                        case static::BELONGS_TO:
                            $obj->fetch([
                                $local_key => $this->value($foreign_key)
                            ]);
                        break;

                        case static::HABTM:
                        
                            $this->driver->find($rel['table'], [
                                'vals' => [$foreign_key],
                                'where' => [
                                    $rel['table_foreign_key'] => $this->value($local_key)
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

                    if (!isset($this->data[$rel['alias']])) {
                        $this->data[$rel['alias']] = $obj;
                    }
                }
            }
        }

        // If the relationship stuff set it, return it
        if (isset($this->data[$key])) {
            return $this->value($key);
        }

        // If we have already fetched it from the database
        if (array_key_exists($key, $this->db_data)) {
            $this->data[$key] = $this->db_data[$key];

            return $this->value($key);
        }

        // If key is a column in the database
        if ($key === 'id' || isset($this->schema_db[$key])) {
            $this->__find();
            $result = $this->driver->fetch();

            if (!isset($result[$key])) {
                $result[$key] = null;
            }

            $this->db_data = array_merge($this->db_data, $result);

            return $this->value($key);
        }
    }

    /**
     * Real find
     */
    protected function __find($conditions = []) {
        return $this->driver->find(
            $this->table_name,
            array_merge_recursive($conditions, $this->conditions())
        );
    }

    public function has($data, $value = null) {
        if (!is_array($data)) {
            $key = $data;

            if (is_null($value)) {
                $value = $key;
                $key = 'id';
            }

            $data = [$key => $value];
        }

        $has = false;
        $conds = $this->conditions();

        foreach ($data as $key => $val) {

            if (isset($conds['where'][$key])) {
                $real_val = $conds['where'][$key];

                if (!is_array($real_val)) $real_val = [$real_val];

                $has = in_array($val, $real_val);
            } else {
                $has = false;
            }
        }

        return $has;
    }

    public function offsetGet($offset) {
        if ($this->mode != static::MODE_MULT) {
            throw new Exception('Cannot access row via index');
        }

        if (count($this->rows) > 0 && ($offset + 1 > count($this->rows))) {
            throw new OutOfRangeException('Cannot get index ' . $offset);
        }

        if (isset($this->rows[$offset])) {
            return $this->rows[$offset];
        }

        if (is_null($this->rows)) {
            return null;
        }

        $this->rows = [];

        $this->__find([
            'vals' => ['id']
        ]);

        $results = $this->driver->fetch_all();

        if (count($results) === 0) {
            $this->rows = null;

            return $this->offsetGet($offset);
        }

        if (is_null($results)) {
            // @todo Determine error
            throw new Exception('Unknown Error');
        }

        $class = $this->class_name;

        if (is_array($results)) {
            foreach ($results as $result) {
                $this->rows[] = new $class($result['id']);
            }
        }

        return $this->offsetGet($offset);
    }

    public function conditions($others = []) {
        return array_merge_recursive($this->defaults, $this->conditions);
    }

    /**
     * Set aliases
     */
    public function alias($alias, $key) {
        $this->aliases[$alias] = $key;
    }

}