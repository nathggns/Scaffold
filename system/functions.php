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
 * Recursive glob
 */
function recursive_glob($pattern, $flags = 0) {
    $files = glob($pattern, $flags);

    foreach (glob(dirname($pattern) . DS . '*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge(
            $files,
            recursive_glob($dir . DS . basename($pattern), $flags)
        );
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
 * Check arguments
 */
function arguments($keys, $args) {
    $missing = [];
    $vals = [];

    foreach ($keys as $arg) {
        if (!isset($args[$arg])) {
            $missing[] = $arg;
        } else {
            $vals[$arg] = $args[$arg];
        }
    }

    if (count($missing) > 0) {
        $args = implode(',', $missing);
        throw new InvalidArgumentException('Missing arguments: ' . $args);

        return false;
    }

    return $vals;
}

/**
 * Find files both in the application and system folders
 */
function get_files($pattern, $recursive = true) {
    $parts = ['system' => SYSTEM, 'application' => APPLICATION];
    $files = [];
    $function = $recursive ? 'recursive_glob' : 'glob';

    foreach ($parts as $name => $path) {
        $files[$name] = [];
        $path = $path . $pattern;
        $dir = dirname($path);
        $filter = basename($path);

        if (!file_exists($dir) || !is_dir($dir)) continue;

        $files[$name] = call_user_func($function, $path);
    }

    return $files;
}

/**
 * Overwrite one array with another, recursively.
 */
function recursive_overwrite($parent, $child) {

    foreach ($child as $key => $val) {
        if (is_array($parent[$key])) {
            $val = recursive_overwrite($parent[$key], $val);
        }

        $parent[$key] = $val;
    }

    return $parent;
}