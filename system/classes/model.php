<?php defined('SCAFFOLD') or die;

/**
 * Base model class.
 *
 * Doesn't do much beside validation
 *
 * @author Nathaniel Higgins
 */
abstract class Model implements ModelInterface {

    /**
     * Associative array mapping keys to some sort
     * of validation test. 
     */
    protected $rules = [];

    /**
     * Store data
     */
    protected $data = [];
    protected $rows = [];
    protected $updated = [];

    /**
     * Modes
     */
    const MODE_SINGLE = 4;
    const MODE_MULT = 5;
    const MODE_INSERT = 6;
    protected $mode = 5;

    /**
     * For the iterator
     */
    private $position = 0;

    /**
     * Validate before saving.
     *
     * At this point, the data that is supposed to be saved should be in $data.
     * Doesn't actually save anything, this should be implemented by an extending
     * class.
     */
    public function save($data = []) {
        $validator = new Validate($this->rules);
        $model_data = $this->data;

        if ($this->mode !== static::MODE_INSERT) {
            $keys = array_keys($this->rules);

            foreach ($keys as $key) {
                $model_data[$key] = $this->__get($key);
            }

            $model_data = array_merge($model_data, $this->updated);
        }

        $data = array_merge($model_data, $data);

        $validator->test($data);

        return true;
    }

    public function __get($key) {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }

    public function reset() {
        $this->mode = static::MODE_MULT;
        $this->data = [];
        $this->rows = [];
        $this->updated = [];
        $this->position = 0;
    }

    public function mode() {
        return $this->mode;
    }

    public function offsetSet($offset, $value) {
        return null;
    }

    public function offsetUnset($offset) {
        return null;
    }


    public function offsetExists($offset) {
        return isset($this->rows[$offset]);
    }

    public function __set($key, $value) {

        if (isset($this->schema[$key])) {
            
            if ($this->mode === static::MODE_SINGLE) {
                $this->updated[$key] = $value;
            }
            
            if (isset($this->data[$key]) || $this->mode === static::MODE_INSERT) {
                $this->data[$key] = $value;
            }
        }
    }

    protected function iterator_master() {

        if (!in_array($this->mode, [static::MODE_SINGLE, static::MODE_MULT])) {
            throw new Exception('Cannot iterate on this mode');
        }

        $this->force_load();
    }

    function rewind() {

        $this->iterator_master();
        $this->position = 0;
    }

    function current() {

        $this->iterator_master();
        
        switch ($this->mode) {

            case static::MODE_SINGLE:
                return $this->__get($this->getSinglePosition());
            break;

            case static::MODE_MULT:
                return $this->rows[$this->position];
            break;

        }
    }

    function valid() {

        $this->iterator_master();

        switch ($this->mode) {

            case static::MODE_SINGLE:
                $keys = array_keys($this->schema);
                return isset($keys[$this->position]);
            break;

            case static::MODE_MULT:
                return isset($this->rows[$this->position]);
            break;
        }

    }

    function key() {

        $this->iterator_master();

        $pos = $this->mode === static::MODE_SINGLE ? $this->getSinglePosition() : $this->position;

        return $pos;
    }

    function next() {

        $this->iterator_master();

        ++$this->position;
    }

    private function getSinglePosition() {
        $keys = array_keys($this->schema);

        if ($this->position > count($keys)-1) {
            throw new OutOfRangeException('Cannot get index ' . $this->position);
        }

        $key = $keys[$this->position];

        return $key;
    }

}