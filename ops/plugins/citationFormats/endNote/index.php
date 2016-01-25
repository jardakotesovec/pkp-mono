<?php

/**
 * @defgroup plugins_citationFormats_endNote EndNote Citation Format
 */
 
/**
 * @file plugins/citationFormats/endNote/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_endNote
 * @brief Wrapper for EndNote citation plugin.
 *
 */

require_once('EndNoteCitationPlugin.inc.php');

return new EndNoteCitationPlugin();

?>
