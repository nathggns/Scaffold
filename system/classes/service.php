<?php defined('SCAFFOLD') or die;

/**
 * Used to manage objects
 *
 * @author Nathaniel Higgins
 */
class Service {

    /**
     * Array of singleton constructors
     *
     * @var array
     */
    protected static $singletons = [];

    /**
     * Array of instance objects.
     *
     * @var array
     */
    protected static $instances = [];

    /**
     * Array of constructors
     *
     * @var array
     */
    protected static $functions = [];

    /**
     * Cache to store built singleton instances
     */
    protected static $builds = [];

    /**
     * Defaults
     *
     * @var array
     */
    protected static $defaults = [];

    /**
     * Register an object creator
     *
     * @param  string   $name      name of service
     * @param  callable $resource  resource
     * @param  boolean  $default   set as default
     * @return boolean             success
     */
    public static function register($name, $resource, $default = false) {
        if (!is_callable($resource)) return false;

        static::$functions[$name] = $resource;
        if ($default) static::set_default($name);

        return true;
    }

    /**
     * Register an instance
     *
     * @param  string  $name     name of service
     * @param  object  $resource resource
     * @param  boolean $default set as default
     * @return boolean           success
     */
    public static function instance($name, $resource, $default = false) {
        if (!is_object($resource)) return false;

        static::$instances[$name] = $resource;
        if ($default) static::set_default($name);

        return true;
    }

    /**
     * Register a singleton creator
     *
     * @param  string   $name     name of service
     * @param  function $resource resource
     * @param  boolean  $default  set as default
     * @return boolean            success
     */
    public static function singleton($name, $resource, $default = false) {
        if (!is_callable($resource)) return false;

        static::$singletons[$name] = $resource;
        if ($default) static::set_default($name);

        return true;
    }

    /**
     * Set default for service
     *
     * @param  string  $name name of service
     * @return boolean       success
     */
    public static function set_default($name) {
        $parts = explode('.', $name);
        $name  = array_shift($parts);

        if (count($parts) > 0) {
            $default = implode('.', $parts);
            static::$defaults[$name] = $default;
        } else {
            unset(static::$defaults[$name]);
        }

        return true;
    }

    /**
     * Get default for service
     *
     * @param string $name name of service
     */
    public static function get_default($name) {
        if (isset(static::$defaults[$name])) $name .= '.' . static::$defaults[$name];

        return $name;
    }

    /**
     * Get a resource.
     *
     * @param  string $name      name of service
     * @param  mixed  $arguments arguments to pass the creator
     * @return object            resource
     */
    public static function get() {
        list($name, $arguments) = call_user_func_array(['Service', 'args'], func_get_args());

        $name = static::get_default($name);

        if (isset(static::$instances[$name])) {
            return static::$instances[$name];
        } elseif (isset(static::$singletons[$name])) {
            if (!isset(static::$builds[$name])) {
                static::$builds[$name] = call_user_func_array(static::$singletons[$name], $arguments);
            }

            return static::$builds[$name];
        } else if (isset(static::$functions[$name])) {
            return call_user_func_array(static::$functions[$name], $arguments);
        } else {

            if (!($reflect = Autoload::load($name))) {
                $name = implode('', array_map(function($a) {
                    return ucfirst($a);
                }, explode('.', $name)));

                $reflect = Autoload::load($name);
            }

            if ($reflect) {
                return (new ReflectionClass($name))->newInstanceArgs($arguments);
            }
        }

        static::throw_error($name);
    }

    /**
     * Build an object, even if it's a singleton
     *
     * @param  string $name      name of service
     * @param  mixed  $arguments arguments to pass the creator
     * @return object            resource
     */
    public static function build() {
        list($name, $arguments) = call_user_func_array(['Service', 'args'], func_get_args());

        $name = static::get_default($name);

        if (isset(static::$instances[$name])) {
            return clone static::$instances[$name];
        } else {
            if (isset(static::$singletons[$name])) {
                $resource = static::$singletons[$name];
            } elseif (isset(static::$functions[$name])) {
                $resource = static::$functions[$name];
            } else {
                return static::throw_error($name);
            }

            return call_user_func_array($func, $arguments);
        }
    }

    /**
     * Get args
     *
     * @return array name and arguments
     */
    protected static function args() {
        $args = func_get_args();
        $name = $args[0];
        $arguments = count($args) > 1 ? array_slice($args, 1) : [];

        return [$name, $arguments];
    }

    /**
     * Remove all services
     */
    public static function reset() {
        static::$instances  = [];
        static::$singletons = [];
        static::$functions  = [];
        static::$builds     = [];
    }

    /**
     * Throw service error
     *
     * @param  string           $name name
     * @throws ExceptionService
     */
    protected static function throw_error($name) {
        throw new ExceptionService('Service ' . $name . ' not found');
    }
}
