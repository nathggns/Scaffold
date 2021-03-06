<?php defined('SCAFFOLD') or die;

/**
 * Autoload classes when they are requested.
 *
 * @author Nathaniel Higgins
 */
class Autoload {

    /**
     * List of parents
     *
     * @var array
     */
    public static $parents = [];

    /**
     * List of manually registered paths
     */
    public static $paths = [];

    /**
     * Register with PHP's spl_autoload
     */
    public static function run() {
        // load inflector
        load_file('classes' . DS . 'inflector.php');

        // search for pluralized directories
        $system_directories      = recursive_glob(SYSTEM . 'classes' . DS . '*', GLOB_ONLYDIR);
        $application_directories = recursive_glob(APPLICATION . 'classes' . DS . '*', GLOB_ONLYDIR);
        $directories             = array_merge($system_directories, $application_directories);

        $directories = array_filter($directories, function($directory) {
           return Inflector::pluralize($directory) === $directory;
        });

        static::$parents = [];

        $paths = [];

        foreach ($directories as $directory) {
            $parts = explode(DS, substr($directory, strlen(ROOT)));
            $parts = array_slice($parts, 2);

            static::$parents[] = DS . implode(DS, $parts);
        }

        // register autoloader
        spl_autoload_register(['Autoload', 'load']);
    }

    /**
     * Load a class.
     *
     * Typically used internally by spl_autoload, but
     * can be used manually.
     *
     * @param  string $class class name to load.
     * @return bool          did a class get loaded
     */
    public static function load($class) {
        if (class_exists($class)) return true;

        // For manual paths
        if (isset(static::$paths[$class]) && $result = static::load_file(static::$paths[$class], $class)) {
            return $result;
        }

        $parts = preg_split('/([[:upper:]][[:lower:]]+)/', $class, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $parts = array_map('strtolower', $parts);

        if ($result = static::load_file($parts, $class)) {
            return $result;
        }

        $previous_parts = [];

        foreach ($parts as $key => $part) {
            $plural_directory = Inflector::pluralize($part);


            if (in_array(DS . ltrim(implode(DS, $previous_parts) . DS . $plural_directory, DS), static::$parents)) {
                $parts[$key] = $plural_directory;
            }

            $previous_parts[] = $parts[$key];

            if ($result = static::load_file($parts, $class)) {
                return $result;
            }
        }

        return false;
    }

    /**
     * Manually register classes with the Autoloader.
     *
     * @param string $class Class name
     * @param string $path Path to class
     *
     * or
     *
     * @param array $class Array of class paths
     */
    public static function register($class, $path = null) {
        if (is_array($class)) {
            foreach ($class as $k => $v) {
                static::register($k, $v);
            }
        } else {
            static::$paths[$class] = $path;
        }
    }

    /**
     * Gets the file name from a parts list
     */
    private static function load_file($path, $class) {

        if (is_array($path)) {
            $path = implode(DS, $path) . '.php';
        } else {
            $path = implode(DS, array_slice(explode(DS, substr($path, strlen(ROOT))), 2));
        }

        return load_file('classes' . DS . $path) && class_exists($class);
    }
}
