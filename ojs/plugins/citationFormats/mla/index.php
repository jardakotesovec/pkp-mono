<?php

/**
 * @defgroup plugins_citationFormats_mla MLA Citation Format
 */
 
/**
 * @file plugins/citationFormats/mla/index.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_citationFormats_mla
 * @brief Wrapper for MLA citation plugin.
 *
 */

require_once('MlaCitationPlugin.inc.php');

return new MlaCitationPlugin();

?>
