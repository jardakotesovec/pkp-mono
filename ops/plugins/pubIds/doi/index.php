<?php

/**
 * @defgroup plugins_pubIds_doi DOI Pub ID Plugin
 */

/**
 * @file plugins/pubIds/doi/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_pubIds_doi
 * @brief Wrapper for DOI plugin.
 *
 */
require_once('DOIPubIdPlugin.inc.php');

return new DOIPubIdPlugin();

?>
