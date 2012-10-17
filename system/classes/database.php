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
        $this->driver = Service::get('database.driver', $config['driver']);
        $this->driver->connect($config['connection']);
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
