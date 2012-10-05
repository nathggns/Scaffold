<?php defined('SCAFFOLD') or die();

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
        $system_directories      = glob(SYSTEM . 'classes' . DS . '*', GLOB_ONLYDIR);
        $application_directories = glob(APPLICATION . 'classes' . DS . '*', GLOB_ONLYDIR);
        $directories             = array_merge($system_directories, $application_directories);

        $directories = array_filter($directories, function($directory) {
           return Inflector::pluralize($directory) === $directory;
        });

        static::$parents = array_map(function($directory) {
           $parts = explode(DS, $directory);
           return end($parts);
        }, $directories);

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
        $parts = preg_split('/([[:upper:]][[:lower:]]+)/', $class, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

        for ($i = 1, $l = count($parts); $i < $l; $i++) {
            $part = $parts[$i];

            if (strtoupper($part) === $part) {
                $parts[$i - 1] .= $part;
                unset($parts[$i]);
            }
        }

        $parts = array_map('strtolower', $parts);
        $plural_directory = Inflector::pluralize($parts[0]);

        if (count($parts) > 1 && in_array($plural_directory, static::$parents)) {
            $parts[0] = $plural_directory;
        }

        return load_file('classes' . DS . implode(DS, $parts) . '.php');
    }
}
