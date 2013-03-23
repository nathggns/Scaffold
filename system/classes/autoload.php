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

        $parts = preg_split('/([[:upper:]][[:lower:]]+)/', $class, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $parts = array_map('strtolower', $parts);

        if ($result = static::load_file($parts)) return $result;

        $previous_parts = [];

        foreach($parts as $key => $part) {
            $plural_directory = Inflector::pluralize($part);


            if (in_array(DS . ltrim(implode(DS, $previous_parts) . DS . $plural_directory, DS), static::$parents)) {
                $parts[$key] = $plural_directory;
            }

            $previous_parts[] = $parts[$key];

            if ($result = static::load_file($parts)) return $result;
        }
    }

    /**
     * Gets the file name from a parts list
     */
    private static function load_file($parts) {
        return load_file('classes' . DS . implode(DS, $parts) . '.php');
    }
}
