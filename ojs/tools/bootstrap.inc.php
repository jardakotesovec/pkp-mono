<?php

/**
 * @file tools/bootstrap.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup tools
 *
 * @brief application-specific configuration common to all tools (corresponds
 *  to index.php for web requests).
 */

// $Id$


require(dirname(dirname(__FILE__)) . '/lib/pkp/classes/cliTool/CliTool.inc.php');

// Initialize the application environment
import('core.OJSApplication');
$application = new OJSApplication();
?>
