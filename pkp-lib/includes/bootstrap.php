<?php

/**
 * @defgroup index Index
 * Bootstrap and initialization code.
 */

/**
 * @file includes/bootstrap.php
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

// Load Composer autoloader
$loader = require_once 'lib/pkp/lib/vendor/autoload.php';
$pkpApp = PKP_APP;
$loader->addPsr4("APP\\controllers\\","../{$pkpApp}/controllers/");
$loader->addPsr4("APP\\API\\","../{$pkpApp}/api/");
$loader->addPsr4("APP\\pages\\","../{$pkpApp}/pages/");
$loader->addPsr4("APP\\","../{$pkpApp}/classes/");
$loader->addPsr4("APP\\plugins\\","../{$pkpApp}/plugins/");
$loader->addPsr4("APP\\jobs\\","../{$pkpApp}/jobs/");


define('BASE_SYS_DIR', dirname(INDEX_FILE_LOCATION));
chdir(BASE_SYS_DIR);

// System-wide functions
require_once './lib/pkp/includes/functions.php';

// Initialize the application environment
return new \APP\core\Application();
