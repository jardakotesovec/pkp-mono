<?php

/**
 * @file plugins/blocks/authorBios/AuthorBiosBlockPlugin.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorBiosBlockPlugin
 * @ingroup plugins_blocks_author_bios
 *
 * @brief Class for author bios block plugin
 */

import('lib.pkp.classes.plugins.BlockPlugin');

class AuthorBiosBlockPlugin extends BlockPlugin {
	/**
	 * Get the display name of this plugin.
	 * @return String
	 */
	function getDisplayName() {
		return __('plugins.block.authorBios.displayName');
	}

	/**
	 * Get a description of the plugin.
	 */
	function getDescription() {
		return __('plugins.block.authorBios.description');
	}

	/**
	 * Get the supported contexts (e.g. BLOCK_CONTEXT_...) for this block.
	 * @return array
	 */
	function getSupportedContexts() {
		return array(BLOCK_CONTEXT_RIGHT_SIDEBAR);
	}

	/**
	 * Get the HTML contents for this block.
	 * @param $templateMgr object
	 * @param $request PKPRequest
	 * @return $string
	 */
	function getContents(&$templateMgr, $request = null) {
		// Only show the block for article pages.
		switch ($request->getRequestedPage() . '/' . $request->getRequestedOp()) {
			case 'article/view':
				if (!$templateMgr->get_template_vars('article')) return '';
				return parent::getContents($templateMgr, $request);
			default:
				return '';
		}
	}
}

?>
