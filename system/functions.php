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
        require_once($file);
    } else if ($system && file_exists(SYSTEM . $file)) {
        require_once($file);
    } else {
        return false;
    }

    return true;
}

/**
 * Returns the last item of an array, useful when that's
 * all that you need from a function. Only works when an array
 * is integer key based.
 *
 * @param array $arr Array to use
 * @return mixed Last item in $arr
 * @todo Allow for non-integer key based arrays
 */
function array_last($arr) {
    return $arr[count($arr) - 1];
}

/**
 * Recursive scan_dir
 *
 * @param string $dir Directory to scan
 * @param bool|string $filetype filetype to look for.
 * @return array List of files in directory.
 */
function recursive_scan_dir($dir, $filetype = false) {
    $files = array();
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item[0] == "_" || $item[0] == ".") continue;

        $item = $dir . "/" . $item;

        if (is_dir($item)) {
            $files = array_merge($files, recursive_scan_dir($item, $filetype));
        } else {
            if ($filetype) {
                if (array_last(explode(".", $item)) !== $filetype) continue;
            }

            $files[] = $item;
        }
    }

    return $files;
}
