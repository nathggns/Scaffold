<?php defined('SCAFFOLD') or die;

/**
 * A dynamic object that supports properties and methods.
 */
class Dynamic {

    protected $alias;

    /**
     * Constructor function. An array of methods and functions
     */
    public function __construct($arr) {

        if (is_array($arr) && (is_hash($arr) || !is_callable($arr))) {
            foreach ($arr as $key => $val) {
                if (is_callable([$val, 'bindTo'])) $val = $val->bindTo($this);

                $this->$key = $val;
            }
        } else {
            $this->alias = $arr;
        }

    }

    /**
     * Handle method calling
     */
    public function __call($name, $args) {

        if ($this->alias) {
            array_unshift($args, $name);
            return call_user_func_array($this->alias, $args);
        }

        array_unshift($args, $this);
        if (property_exists($this, $name) && is_callable($this->$name)) {
            $retval = call_user_func_array($this->$name, $args);

            return is_null($retval) ? $this : $retval;

        } else {
            throw new Exception('Method ' . $name . ' not found');
        }
    }

}