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
    private static $singletons = [];

    /**
     * Cache to store instances objects.
     */
    private static $instances = [];

    /**
     * Cache to store constructor functions
     */
    private static $functions = [];

    /**
     * Register an object creator
     *
     * @param string $name Name of the resource
     * @param object|function $resource the resource
     *
     */
    public static function register($name, $resource) {
        if (is_callable($resource)) {
            static::$functions[$name] = $resource;
            return true;
        }

        return false;
    }

    /**
     * Register an instance
     *
     * @param string $name Name of the resource
     * @param object $resource the resource
     *
     */
    public static function instance($name, $resource) {
        if (is_object($resource)) {
            static::$instances[$name] = $resource;
            return true;
        }

        return false;
    }

    /**
     * Register aa singleton creator
     *
     * @param string $name Name of the resource
     * @param function $resource the resource
     *
     */
    public static function singleton($name, $resource) {
        if (is_callable($resource)) {
            static::$singletons[$name] = $resource;
            return true;
        }

        return false;
    }

    /**
     * Get a resource.
     *
     * @param string $name The name of the resource
     * @param mixed $arguments arguments to pass the creator
     * @return object The resource
     */
    public static function get() {

        list($name, $arguments) = call_user_func_array(['Service', 'args'], func_get_args());

        if (isset(static::$instances[$name])) {
            return static::$instances[$name];
        } else if ($result = call_user_func_array(['Service', 'build'], func_get_args()) or $result !== false) {
            return $result;
        }

        return static::exception($name);
    }

    /**
     * Build an object, even if it's a singleton
     *
     * @param string $name The name of the resource
     * @param mixed $arguments arguments to pass the creator
     * @return object The resource
     */
    public static function build() {
        list($name, $arguments) = call_user_func_array(['Service', 'args'], func_get_args());

        $func = false;

        if (isset(static::$instances[$name])) {
            return clone static::$inatances[$name];
        } else if (isset(static::$functions[$name])) {
            $func = static::$functions[$name];
        } else if (isset(static::$singletons[$name])) {
            $func = static::$singletons[$name];
        } else {
            return static::exception($name);
        }

        $result = call_user_func_array($func, $arguments);

        return $result;
    }

    /**
     * Get args
     */
    private static function args() {
        $args = func_get_args();
        $name = $args[0];
        $arguments = count($args) > 1 ? array_slice($args, 1) : [];

        return [$name, $arguments];
    }

    private static function exception($name) {
        throw new ExceptionService('Service ' . $name . ' not found');
    }
}
