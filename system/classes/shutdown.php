<?php defined('SCAFFOLD') or die;

/**
 * Handles the shutdown function, as we only want to register with PHP once.
 */
class Shutdown {

    protected static $func;
    protected static $loaded = false;

    public static function register() {
        if (static::$loaded) {
            return;
        }

        $class = get_called_class();

        register_shutdown_function(function() use ($class) {
            if (($err = error_get_last()) && $class::$func) {
                call_user_func_array([$class, 'call'], $err);
                die;
            }
        });

        static::$loaded = true;
    }

    public static function set($func) {
        static::$func = $func;
    }

    public static function get() {
        return static::$func;
    }

    public static function call() {
        return call_user_func_array(static::$func, func_get_args());
    }

}
