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
    if (file_exists(APPLICATION . $file) || ($system && file_exists(SYSTEM . $file))) {
        return require_once($file);
    } else {
        return false;
    }
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
