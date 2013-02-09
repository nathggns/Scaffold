<?php defined('SCAFFOLD') or die();

/**
 * Wrapper for the specfic driver in use.
 *
 * @todo Merge config with that from a file.
 */
class Database {

    /**
     * Store the driver instance.
     */
    protected $driver;


    public function __construct($config) {
        $this->driver = Service::get('database.driver', $config->get('database'));
    }

    /* Complex Query Helpers */
    public static function where_or($val) {
        return static::query($val, static::query_arr('or'));
    }

    public static function where_and($val) {
        return static::query($val, static::query_arr('and'));
    }

    public static function where_equals($val) {
        return static::query($val, static::query_arr('equals'));
    }

    public static function where_gt($val) {
        return static::query($val, static::query_arr('gt'));
    }

    public static function where_gte($val) {
        return static::query($val, static::query_arr('gte'));
    }

    public static function where_lt($val) {
        return static::query($val, static::query_arr('lt'));
    }

    public static function where_lte($val) {
        return static::query($val, static::query_arr('lte'));
    }

    public static function where_not($val) {
        return static::query($val, static::query_arr('not'));
    }

    public static function query_arr($name) {
        switch ($name) {
            case 'not':
                $prop = 'special';
                $name = ['not'];
            break;

            case 'or':
            case 'and':
                $prop = 'connector';
            break;

            case 'gt':
            case 'gte':
            case 'lt':
            case 'lte':
            case 'equals':
                $prop = 'operator';
            break;

            default: return;
        }

        return [$prop => $name];
    }

    public static function query($val, $opts) {

        if (is_object($val)) {
            $opts = array_merge(get_object_vars($val), $opts);
        } else {
            $opts['val'] = $val;
        }

        $obj = new Dynamic($opts);

        return $obj;
    }

    /**
     * Act like the driver.
     */

    public function __get($name) {
        return $this->driver->$name;
    }

    public function __set($name, $value) {
        $this->driver->$name = $value;
    }

    public function __isset($name) {
        return isset($this->driver->$name);
    }

    public function __unset($name) {
        unset($this->driver->$name);
    }

    public function __call($name, $arguments) {
        return call_user_func_array([$this->driver, $name], $arguments);
    }
}
