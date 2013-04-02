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

            if (isset($this->schema['created']) && !isset($this->data['created'])) {
                $this->data['created'] = time();
            }

            $this->driver->insert($this->table_name, $this->data);
            $this->reset();
            $this->fetch(['id' => $this->driver->id()]);

        } else if (count($this->updated) > 0 && $this->mode === static::MODE_SINGLE) {

            if (isset($this->schema['updated'])) {
                $this->updated['updated'] = time();
            }

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

        if (count($this->data) > 0) {
            $this->data[$field] = $this->value($field, $value);
        }

        return $this;
    }

    protected function value($field, $value) {

        if ($value instanceof Closure) {
            $value = $value->bindTo($this);
            $value = $value($field);
        }
        
        return $value;
    }

    public function export($values = null, $level = 1, $count_models = false) {

        if (is_null($values)) {
            $values = $this->export_fields;
        }

        if ($values === false) $values = [];

        if (!is_bool($values) && !is_array($values)) $values = [$values];

        $data = [];

        if ($this->mode === static::MODE_MULT) {
            foreach ($this as $item) {
                $data[] = $item->export($values, $level, $count_models);
            }

        } else {
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
     * Handle gets
     * 
     * @todo Lazy load all properties
     */
    public function __get($key) {

        if ($this->mode !== static::MODE_SINGLE || (count($this->data) > 0 && (!isset($key, $this->data) || is_null($this->data[$key])))) {
            throw new Exception('Property ' . $key . ' does not exist on model ' . $this->name);
        }

        if (isset($this->data[$key])) {
            return $this->value($key, $this->data[$key]);
        }

        $this->__find();

        $result = $this->driver->fetch();

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

                    $result[$alias] = $obj;
                }
            }
        }

        foreach ($this->virtual_fields as $field => $val) {
            $result[$field] = $val;
        }

        $schema = array_keys($this->schema);

        foreach ($schema as $field) {
            if (!isset($result[$field])) {
                $result[$field] = null;
            }
        }

        $this->data = $result;

        return $this->__get($key);  
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

        if (isset($this->rows[$offset])) {
            return $this->rows[$offset];
        }

        $this->rows = [];

        $this->__find([
            'vals' => ['id']
        ]);

        $results = $this->driver->fetch_all();
        $class = $this->class_name;

        if (is_array($results)) {
            foreach ($results as $result) {
                $this->rows[] = new $class($result['id']);
            }
        }

        if (!isset($this->rows[$offset])) {
            $this->rows[$offset] = null;
        }

        return $this->rows[$offset];
    }

    public function conditions($others = []) {
        return array_merge_recursive($this->defaults, $this->conditions);
    }

}