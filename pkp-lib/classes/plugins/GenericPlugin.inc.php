<?php

/**
 * @file classes/plugins/GenericPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GenericPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for generic plugins
 */

import('lib.pkp.classes.plugins.LazyLoadPlugin');

abstract class GenericPlugin extends LazyLoadPlugin {
	/**
	 * Constructor
	 */
	function GenericPlugin() {
		parent::LazyLoadPlugin();
	}
}

?>
