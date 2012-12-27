<?php defined('SCAFFOLD') or die();

/**
 * Simply a set of system wide functions.
 */

/**
 * Load a file from the application folder if it exists,
 * else, load it from the system folder.
 *
 * @param string file File to load
 * @param bool system Load from system if the default doesn't exist?
 * @return bool File loaded?
 */
function load_file($file, $system = true) {
    if (file_exists(APPLICATION . $file)) {
        return require_once(APPLICATION . $file);
    } elseif ($system && file_exists(SYSTEM . $file)) {
        return require_once(SYSTEM . $file);
    }

    return false;
}

/**
 * Recursive scan_dir
 *
 * @param string $dir Directory to scan
 * @param bool|string $filetype filetype to look for.
 * @return array List of files in directory.
 */
function recursive_scan_dir($dir, $filetype = false) {
    $files = [];
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item[0] == '_' || $item[0] == '.') continue;

        $item = $dir . DS . $item;

        if (is_dir($item)) {
            $files = array_merge($files, recursive_scan_dir($item, $filetype));
        } else {
            if ($filetype) {
                if (end(explode('.', $item)) !== $filetype) continue;
            }

            $files[] = $item;
        }
    }

    return $files;
}

/**
 * Is an array a hash?
 */
function is_hash($arr) {
    if (!is_array($arr)) return false;

    $keys = range(0, count($arr) - 1);

    foreach ($keys as $key) {
        if (!isset($arr[$key])) {
            return true;
        }
    }

    return false;
}

/**
 * Recursively merge arrays, overwriting non-array values.
 *
 * @param array $first First array
 * @param array $second Second array
 * ...
 */
function array_merge_recursive_overwrite() {
    // Get all of our arguments
    $args = func_get_args();

    // Make sure we have arguments to work with
    if (count($args) < 2) return;

    // Handle more than 2 arrays
    if (count($args) > 2) {
        while (count($args) > 2) {
            $first = array_shift($args);
            $second = array_shift($args);
            array_unshift($args, array_merge_recursive_overwrite($first, $second));
        }

        $args = array_merge_recursive_overwrite($args[0], $args[1]);

        return $args;
    }

    $first = $args[0];
    $second = $args[1];

    foreach ($second as $key => $val) {
        if (isset($first[$key]) && is_array($first[$key])) {
            $val = array_merge_recursive_overwrite($first[$key], $val);
        }

        $first[$key] = $val;
    }

    return $first;
}
