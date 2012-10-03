<?php defined('SCAFFOLD') or die();

/**
 * A 'dummy' service returned by Service when a service doesn't exist
 *
 * @author Nathaniel Higgins
 */
class ServiceDummy {

    private $__data = [];

    public function __set($name, $value) {
        $this->__data[$name] = $value;

        return true;
    }

    public function __get($name) {
        if (isset($this->__data[$name])) return $this->__data[$name];

        return null;
    }

    public function __call($name, $arguments) {
        return null;
    }

    public static function __callStatic($name, $arguments) {
        return null;
    }

    public function __isset($name) {
        if (isset($this->__data[$name])) return true;

        return false;
    }

    public function __unset($name) {
        if (isset($this->__data[$name])) unset($this->__data[$name]);

        return null;
    }

}
