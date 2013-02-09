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

    /* Query Helpers */
    public static function __callStatic($name, $args = []) {
        if (preg_match('/^where_/i', $name)) {
            $name = substr($name, strlen('where_'));

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

            $args[] = [$prop => $name];

            return call_user_func_array([self, 'query'], $args);
        }

        return call_user_func_array([get_class($this->driver), $name], $args);
    }

    /**
     * Build query object
     */
    public static function query($val, $opts = []) {

        while (is_object($val)) {
            $opts = array_merge($opts, get_object_vars($val));
            $val = $val->val;
        }

        $opts['val'] = $val;
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
