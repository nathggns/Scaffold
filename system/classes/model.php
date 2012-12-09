<?php defined('SCAFFOLD') or die();

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
    protected $mode;

    /**
     * Validate before saving.
     *
     * At this point, the data that is supposed to be saved should be in $data.
     * Doesn't actually save anything, this should be implemented by an extending
     * class.
     */
    public function save() {
        $validator = new Validate($this->rules);
        $validator->test($this->data);

        return true;
    }

    public function reset() {
        $this->mode = null;
        $this->data = [];
        $this->rows = [];
        $this->updated = [];
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

}