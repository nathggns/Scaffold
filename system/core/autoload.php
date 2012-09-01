<?php defined('SCAFFOLD') or die();

/**
 * Autoload classes when they are requested.
 *
 * @author Nathaniel Higgins
 */
class Autoload {

    /**
     * Register with PHP's spl_autoload
     */
    public static function run() {
        spl_autoload_register(array('Autoload', 'load'));
    }

    /**
     * Load a class.
     *
     * Typically used internally by spl_autoload, but
     * can be used manually.
     *
     * @param string $class Class name to load.
     * @return bool (true|false) Did a class get loaded
     */
    public static function load($class) {
        $parts = preg_split('/([[:upper:]][[:lower:]]+)/', $class, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
         
        $parts = array_map("strtolower", $parts);
 
        if (in_array($parts[0], array('controller', 'model', 'exception'))) {
            $parts[0] = $parts[0] . 's';
        }
 
        $path = strtolower(implode('/', $parts)) . '.php';

        if (strpos($path, '/') === false) {
            $path = 'core/' . $path;
        }

        return load_file($path);
    }
}
