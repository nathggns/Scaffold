<?php defined('SCAFFOLD') or die;

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
        if (!array_key_exists($key, $arr)) {
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

        $files[$name] = $function($path);
    }

    return $files;
}

/**
 * Overwrite one array with another, recursively.
 */
function recursive_overwrite($parent, $child) {

    foreach ($child as $key => $val) {
        if (isset($parent[$key]) && is_array($parent[$key]) && is_array($val)) {
            $val = recursive_overwrite($parent[$key], $val);
        }

        $parent[$key] = $val;
    }

    return $parent;
}

/**
 * Implode using keys as well as values
 */
function key_implode($pair_glue, $glue, $arr) {

    $key_arr = [];

    foreach ($arr as $key => $val) {
        $key_arr[] = is_int($key) ? [$val] : [$key, $val];
    }

    $key_arr = array_map(function($val) use($pair_glue) {

        return implode($pair_glue, $val);

    }, $key_arr);

    return implode($glue, $key_arr);

}

/**
 * Absolute to relative path
 *
 * @param string $path The absolute path
 * @param string $base The base, to base it on. Defaults to ROOT
 */
function abs2rel($path, $base = ROOT) {
    $path = explode('/', $path);
    $base = explode('/', $base);
    $rel = $path;

    foreach ($base as $depth => $dir) {
        if ($dir === $path[$depth]) {
            array_shift($rel);
        } else {
            $remaining = count($base) - $depth;

            if ($remaining > 1) {
                $padlen = (count($rel) + $remaining - 1) * -1;
                $rel = array_pad($rel, $padlen, '..');
                break;
            }
        }
    }

    return implode('/', $rel);
}


/**
 * Limitable "var_dump".
 *
 * NOTE: Due to limits in PHP, we cannot get protected/private properties. 
 * 
 * @param  mixed   $val   The var to dump
 * @param  integer $depth The limit to how deep you can go. Set to -1 to
 *                        have an infinite limit.
 * @param  scalar  $key   The key of the item you're dumping
 * @param  integer $level The current level of how deep you are
 * @return string         The dumped value
 */
function var_log($val, $depth = 4, $key = null, $level = 0) {

    $tab = str_repeat(' ', $level * 2);

    $output = '';

    $output .= $tab;

    if (!is_null($key)) {
        $output .= '[';

        if (is_string($key)) {
            $output .= chr(34) . $key . chr(34);
        } else {
            $output .= $key;
        }

        $output .= ']=> ';
    }

    $output .= chr(10);
    $output .= $tab;

    if (is_array($val) || is_object($val)) {
        if (is_array($val)) {
            $output .= 'array(' . count($val) . ')';
        } else {
            $output .= 'object(' . get_class($val) . ')';
        }

        if ($depth) {

            if (is_object($val)) {
                $val = get_object_vars($val);
            }

            $output .= ' {';

            if (count($val)) {
                $output .= chr(10);

                $first = true;

                foreach ($val as $k => $v) {

                    if (!$first) {
                        $output .= chr(10);
                    }

                    $first = false;

                    $output .= var_log($v, $depth - 1, $k, $level + 1);
                }

                $output .= chr(10);
                $output .= $tab;
            } else {
                $output .= ' ';
            }
            $output .= '}';
        }
    } else if (is_string($val)) {
        $output .= 'string(' . strlen($val) . ') ' . chr(34) . $val . chr(34);
    } else if (is_bool($val)) {
        $output .= $val ? 'TRUE' : 'FALSE';
    } else if (is_null($val)) {
        $output .= 'NULL';
    } else {
        $output .=  'int(' . $val . ')';
    }

    return $output;

}