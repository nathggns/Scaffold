<?php defined('SCAFFOLD') or die();

/**
 * Used to manage objects
 *
 * @author Nathaniel Higgins
 */
class Service {

    /**
     * Cache to store singleton objects.
     */
    private static $cache = [];

    /**
     * Cache to store constructor functions
     */
    private static $functions = [];

    /**
     * Set a resource.
     *
     * @param string $name Name of the resource
     * @param object|function $resource the resource
     *
     * If you pass it a callable, it assumes your passing it an object creator,
     * otherwise, it will asume that you're passing it a singleton.
     */
    public static function set($name, $resource) {
        if (isset(static::$cache[$name])) {
            unset(static::$cache[$name]);
        } else if (isset(static::$functions[$name])) {
            unset(static::$functions[$name]);
        }

        if (is_callable($resource)) {
            static::$functions[$name] = $resource;
        } else {
            static::$cache[$name] = $resource;
        }
    }

    /**
     * Get a resource.
     *
     * @param string $name The name of the resource
     * @param mixed $arguments arguments to pass the creator
     * @return object The resource
     */
    public static function get() {

        $args = func_get_args();
        $name = $args[0];
        $arguments = count($args) > 1 ? array_slice($args, 1) : [];

        if ($name === 'dummy') {
            return new ServiceDummy();
        } else if (isset(static::$functions[$name])) {
            return static::create($name, $arguments);
        } else if (isset(static::$cache[$name])) {
            return static::$cache[$name];
        }

        throw new ExceptionService('Service ' . $name . ' not found');
    }

    /**
     * Create an object
     *
     * @param string $name The name of the resource
     * @param mixed $arguments arguments to pass the creator
     * @return object The resource
     */
    private static function create($name, $arguments = []) {
        return call_user_func_array(static::$functions[$name], $arguments);
    }
}
