<?php

/**
 * @file classes/plugins/ViewableFilePlugin.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ViewableFilePlugin
 * @ingroup plugins
 *
 * @brief Abstract class for article galley plugins
 */

import('lib.pkp.classes.plugins.PKPViewableFilePlugin');

class ViewableFilePlugin extends PKPViewableFilePlugin {
	/**
	 * Constructor
	 */
	function ViewableFilePlugin() {
		parent::PKPViewableFilePlugin();
	}

	/**
	 * @see Plugin::register()
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			if ($this->getEnabled()) {
				HookRegistry::register('Templates::Galley::displayGalley', array($this, 'callback'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Display this galley in some manner.
	 *
	 * @param $templateMgr object
	 * @param $request PKPRequest
	 * @param $params array
	 * @return string
	 */
	function displayArticleGalley($templateMgr, $request, $params) {
		$templateFilename = $this->getTemplateFilename();
		if ($templateFilename === null) return '';
		return $templateMgr->fetch($this->getTemplatePath() . $templateFilename);
	}

	/**
	 * Callback that renders the galley.
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return string
	 */
	function callback($hookName, $args) {
		$params =& $args[0];
		$templateMgr =& $args[1];
		$output =& $args[2];

		$galley = $templateMgr->get_template_vars('galley'); // set in ArticleHandler
		if ($galley && $galley->getGalleyType() == $this->getName()) {
			$output .= $this->displayArticleGalley($templateMgr, $this->getRequest(), $params);
		}

		return false;
	}
}

?>
