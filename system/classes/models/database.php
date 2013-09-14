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
     * Does this model exist?
     */
    protected $exists = null;

    /**
     * Prefix all vals with this, if it's "truthy"
     */
    protected $val_prefix = null;

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

        $this->exists = null;

        $this->mode = static::MODE_SINGLE;
        $this->conditions['where'] = recursive_overwrite($this->conditions['where'], $conditions);

        return $this;
    }

    public function fetch_all($conditions = [], $reset = true) {
        if ($reset) $this->reset();

        $this->exists = null;

        $this->mode = static::MODE_MULT;
        $this->conditions['where'] = recursive_overwrite($this->conditions['where'], $conditions);

        return $this;
    }

    public function find($conditions, $mode = null, $reset = true) {

        if ($reset) $this->reset();

        $this->exists = null;

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

        $this->exists = null;

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

        $this->exists = null;

        if ($this->mode !== static::MODE_SINGLE) {
            throw new Exception('Can\'t delete non-single row');
        }

        foreach ($this->relationships as $type => $relationships) {
            foreach ($relationships as $model => $real_realationships) {
                foreach ($real_realationships as $relationship) {
                    if ($relationship['dependant']) {
                        $models = $this->value($relationship['alias']);

                        if ($models->mode === static::MODE_SINGLE) {
                            $models = [$models];
                        }

                        foreach ($models as $model) {
                            if ($model->count()) {
                                $model->delete();    
                            }
                        }
                    }
                }
            }
        }

        $id = $this->id;

        $this->driver->delete($this->table_name, ['id' => $id]);
        return $this->reset();

    }

    public function reset() {
        parent::reset();
        $this->conditions = [];
        $this->db_data = [];
        $this->exists = null;

        return $this;
    }

    protected function init() {
        // Here to be overwritten
    }

    public function exists($recheck = false, $check = true, $use_results = false) {
        $mode = $this->mode();

        if ($mode === static::MODE_INSERT) {
            return true;
        }

        if ($check && ($this->exists === null || $recheck)) {
            $this->exists = !!$this->count($use_results);
        }

        return $this->exists;
    }

    public function has_many() {
        $args = func_get_args();
        $args = $this->relationship_args_shuffle(static::HAS_MANY, $args);

        return $this->relationship($args);
    }

    public function has_one() {
        $args = func_get_args();
        $args = $this->relationship_args_shuffle(static::HAS_ONE, $args);

        return $this->relationship($args);
    }

    public function belongs_to() {
        $args = func_get_args();
        $args = $this->relationship_args_shuffle(static::BELONGS_TO, $args);

        return $this->relationship($args);
    }

    public function habtm() {
        $args = func_get_args();
        $args = $this->relationship_args_shuffle(static::HABTM, $args, ['table_foreign_key', 'table']);

        $this->relationship($args);
    }

    public function relationship($args) {

        $this->exists = null;

        extract($args);

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
            'local_key' => $local_key,
            'dependant' => $dependant
        ], $other);

        // @TODO Use a single method to build the array
        $this->schema[$alias] = [
            'field' => $alias
        ];
    }

    public function virtual($field, $value) {

        $this->exists = null;

        $this->virtual_fields[$field] = $value;
        $this->schema[$field] = [
            'field' => $field
        ];

        return $this;
    }

    public function export() {

        $args = $this->shuffle_export_args(func_get_args());

        extract($args);

        if (!$this->exists(false, true, true)) {

            if ($this->mode === static::MODE_MULT) {
                return [];
            } else {
                return null;
            }
        }

        $data = [];

        switch ($this->mode) {

            case static::MODE_MULT:

                $this->fetch_data();

                foreach ($this as $item) {
                    $data[] = $item->export($values, $level, $count_models);
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
                    $value = $this->value($key);

                    if ($value instanceof Model) {
                        if ($count_models && $level === 1) {
                            $value = $value->count();
                        } else if ($level > 0) {
                            $value = $value->export(is_array($values[$key]) ? $values[$key] : null, $level - 1, $count_models);
                        } else {
                            continue;
                        }
                    }

                    $data[$key] = $value;
                }
               
            break;

            default:
                throw new Exception('Cannot export this model');
            break;
        }

        return $data;
    }

    public function force_load() {

        $this->exists = null;

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
    public function count($use_results = false) {

        $this->__find([], false);

        if ($use_results) {
            
            try {
                $this->force_load();
            } catch (Exception $e) {
                return 0;
            }

            if ($this->mode === static::MODE_SINGLE) {
                return 1;
            } else {
                return count($this->rows);
            }
        }

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

        // Is it a function?
        if ($this->driver->builder->is_func($key)) {
            return $this->func($key);
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
        if ($this->mode !== static::MODE_SINGLE || (!array_key_exists($key, $this->schema) || $this->exists(false, false) === false)) {
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

                    $obj = new $class_name(null, $this->driver);

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
                            
                            $obj->find([
                                'from' => [
                                    Database::alias($rel['table'], 'join'),
                                    Database::alias($this->table_name, 'local'),
                                    Database::alias($obj->table_name, 'remote')
                                ],
                                'vals' => array_map(function($field) {
                                    return $field['field'];
                                }, $obj->schema_db),
                                'where' => [
                                    'local.' . $local_key => Database::column('join.' . $rel['table_foreign_key']),
                                    'remote.id' => Database::column('join.' . $rel['foreign_key']),
                                    'join.' . $rel['table_foreign_key'] => $this->value($local_key)
                                ]
                            ]);

                            $obj->val_prefix = 'remote.';
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

            if ($result && count($result)) {
                if (!isset($result[$key])) {
                    $result[$key] = null;
                }

                $this->db_data = array_merge($this->db_data, $result);   
            } else {
                $this->exists = false;
            }

            return $this->value($key);
        }
    }

    /**
     * Real find
     */
    protected function __find($conditions = [], $execute = true) {

        $this->exists = null;

        $conditions = array_merge_recursive($conditions, $this->conditions());

        if ($this->val_prefix && isset($conditions['vals'])) {
            $val_prefix = $this->val_prefix;

            $real_conds = [];

            foreach ($conditions['vals'] as $k => $v) {
                if (is_int($k)) {
                    $k = $v;
                }

                if (strpos($k, '.') === false) {
                    $k = $val_prefix . $k;
                }

                $real_conds[$k] = $v;
            }

            $conditions['vals'] = $real_conds;
        }

        return $this->driver->find(
            $this->table_name,
            $conditions,
            $execute
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

    /**
     * This will fetch the data for all of your models in one query. Helps
     * to cut down on query counts.
     */
    public function fetch_data() {
        if ($this->mode !== static::MODE_MULT) {
            throw new Exception('Can only fetch all for multiple models');
        }

        $this->__find();
        $results = $this->driver->fetch_all();

        $map = [];

        foreach ($this as $row) {
            $id = $row->conditions()['where']['id'];

            while (is_object($id)) {
                $id = $id->val;
            }

            $map[$id] = $row;
        }

        foreach ($results as $i => $result) {
            $id = $result['id'];
            $map[$id]->data = array_merge($map[$id]->data, $result);
        }

        return $this;
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

        $conditions = $this->conditions();
        $conditions['vals'] = ['id'];

        $this->conditions = $conditions;

        $this->__find();

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
                $model = new $class($result['id'], $this->driver);
                $model->exists = true;

                $this->rows[] = $model;
            }
        }

        return $this->offsetGet($offset);
    }

    public function conditions($others = []) {
        return array_merge_recursive($this->defaults, $this->conditions, $others);
    }

    /**
     * Set aliases
     */
    public function alias($alias, $key) {
        $this->aliases[$alias] = $key;
    }

    /** Get functions */
    protected function func($key) {

        if (!$this->driver->builder->is_func($key)) return;

        $conditions = $this->conditions();
        $alias = false;

        while (!$alias || in_array($alias, array_values($conditions['vals'])) || in_array($alias, array_keys($conditions['vals']))) {
            $alias = 'function_' . uniqid();
        }

        $key->as($alias);

        $conditions['vals'][] = $key;

        $result = $this->driver->find($this->table_name, $conditions)->fetch();

        return $result[$alias];
    }

    public function __call($name, $args) {
        $obj = call_user_func_array(['Database', 'func_' . $name], $args);

        return $this->func($obj);
    }

    public function __set($key, $val) {
        $this->exists = null;

        return call_user_func_array(['parent', '__set'], func_get_args());
    }

    public function random() {
        $conditions = $this->conditions();
        $this->reset();
        $conditions['order'] = [ Database::func_random() ];
        $conditions['limit'] = 1;

        $this->find($conditions, Model::MODE_SINGLE);
        
        return $this;
    }

    public function data() {
        return $this->data;
    }

}