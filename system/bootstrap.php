<?php defined('SCAFFOLD') or die();

/**
 * Scaffold Framework bootstrap
 *
 * Do not edit this file, instead, create a custom
 * bootstrap.php file in the application folder.
 * Editing this file could lead to unexpected results.
 */

/**
 * We need to load functions.php because it's contents
 * are used elsewhere in Scaffold.
 */
require(SYSTEM . 'functions.php');

/**
 * Check if a custom bootstrap exists. If it does,
 * use that, and not the system one.
 */
if (load_file('bootstrap.php', false)) die();

/**
 * Include our Autoloader
 *
 * This is the only class we should be including manually.
 */
load_file('core/autoload.php');

/**
 * Run the Autoloader
 *
 * This will enable us to use classes without having
 * to manually include them everytime.
 */
Autoload::run();
