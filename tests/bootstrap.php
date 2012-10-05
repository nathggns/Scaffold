<?php

/**
 * Determine how long a particular request takes to compute.
 */
defined('START') or define('START', microtime(true));

/**
 * Determine if a file in the framework is being requested directly or
 * via the framework.
 */
defined('SCAFFOLD') or define('SCAFFOLD', true);

/**
 * Standard directory seperator.
 */
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/**
 * Access to the root folder, independent of the
 * location of the file using it.
 */
defined('ROOT') or define('ROOT', dirname(pathinfo(__FILE__, PATHINFO_DIRNAME)) . DS);

/**
 * Access to the system folder, independent of the
 * location of the file using it.
 */
defined('SYSTEM') or define('SYSTEM', ROOT . 'system' . DS);

/**
 * Access to the application folder, independent
 * of the location of the file using it.
 */
defined('APPLICATION') or define('APPLICATION', ROOT . 'application' . DS);

/**
 * Boot Scaffold
 */
require(SYSTEM . 'bootstrap.php');
