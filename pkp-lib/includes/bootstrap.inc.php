<?php

/**
 * @defgroup index Index
 * Bootstrap and initialization code.
 */

/**
 * @file includes/bootstrap.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup index
 *
 * @brief Core system initialization code.
 * This file is loaded before any others.
 * Any system-wide imports or initialization code should be placed here.
 */


/**
 * Basic initialization (pre-classloading).
 */

define('ENV_SEPARATOR', strtolower(substr(PHP_OS, 0, 3)) == 'win' ? ';' : ':');
if (!defined('DIRECTORY_SEPARATOR')) {
    // Older versions of PHP do not define this
    define('DIRECTORY_SEPARATOR', strtolower(substr(PHP_OS, 0, 3)) == 'win' ? '\\' : '/');
}
define('BASE_SYS_DIR', dirname(INDEX_FILE_LOCATION));
chdir(BASE_SYS_DIR);

// System-wide functions
require('./lib/pkp/includes/functions.inc.php');

// Register custom autoloader functions for namespaces
spl_autoload_register(function ($class) {
    $prefix = 'PKP\\';
    $rootPath = BASE_SYS_DIR . '/lib/pkp/classes';
    customAutoload($rootPath, $prefix, $class);
});
spl_autoload_register(function ($class) {
    $prefix = 'APP\\';
    $rootPath = BASE_SYS_DIR . '/classes';
    customAutoload($rootPath, $prefix, $class);
});

// Initialize the application environment
return new \APP\core\Application();
