<?php

/**
 * @file classes/core/Handler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Handler
 * @ingroup core
 *
 * @brief Base request handler class.
 */

// $Id$


import('core.PKPHandler');

class Handler extends PKPHandler {
	/**
	 * Perform request access validation based on security settings.
	 * @param $requiresPress boolean
	 */
	function validate($requiresPress = false) {
                parent::validate();

		$press = Request::getPress();

		if ($requiresPress && $press == null) {
			// Requested page is only allowed for a press
			Request::redirect(null, 'about');
		}

		$page = Request::getRequestedPage();
		if (	$press != null &&
			!Validation::isLoggedIn() &&
			!in_array($page, Handler::getLoginExemptions()) &&
			$press->getSetting('restrictSiteAccess')
		) {
			Request::redirect(null, 'login');
		}
	}

	/**
	 * Get a list of pages that don't require login, even if the press does.
	 * @return array
	 */
	function getLoginExemptions() {
		return array('user', 'login', 'help');
	}

	/**
	 * Generate a unique-ish hash of the page's identity, including all
	 * context that differentiates it from other similar pages (e.g. all
	 * articles vs. all articles starting with "l").
	 * @param $contextData array A set of information identifying the page
	 * @return string hash
	 */
	function hashPageContext($contextData = array()) {
		return md5(
			Request::getRequestedPressPath() . ',' .
			Request::getRequestedPage() . ',' .
			Request::getRequestedOp() . ',' .
			serialize($contextData)
		);
	}

	/**
	 * Return the DBResultRange structure and misc. variables describing the current page of a set of pages.
	 * @param $rangeName string Symbolic name of range of pages; must match the Smarty {page_list ...} name.
	 * @param $contextData array If set, this should contain a set of data that are required to
	 * 	define the context of this request (for maintaining page numbers across requests).
	 *	To disable persistent page contexts, set this variable to null.
	 * @return array ($pageNum, $dbResultRange)
	 */
	function &getRangeInfo($rangeName, $contextData = null) {
		$press =& Request::getPress();
		$journalSettingsDao =& DAORegistry::getDAO('PressSettingsDAO');

		$pageNum = Request::getUserVar($rangeName . 'Page');
		if (empty($pageNum)) {
			$session = &Request::getSession();
			$pageNum = 1; // Default to page 1
			if ($session && $contextData !== null) {
				// See if we can get a page number from a prior request
				$context = Handler::hashPageContext($contextData);

				if (Request::getUserVar('clearPageContext')) {
					// Explicitly clear the old page context
					$session->unsetSessionVar("page-$context");
				} else {
					$oldPage = $session->getSessionVar("page-$context");
					if (is_numeric($oldPage)) $pageNum = $oldPage;
				}
			}
		} else {
			$session =& Request::getSession();
			if ($session && $contextData !== null) {
				// Store the page number
				$context = Handler::hashPageContext($contextData);
				$session->setSessionVar("page-$context", $pageNum);
			}
		}

		if ($press) $count = $journalSettingsDao->getSetting($press->getPressId(), 'itemsPerPage');

		if (!isset($count)) $count = Config::getVar('interface', 'items_per_page');

		import('db.DBResultRange');

		if (isset($count)) $returner = &new DBResultRange($count, $pageNum);
		else $returner = &new DBResultRange(-1, -1);

		return $returner;
	}

	/**
	 * Set up the template.
	 */
	function setupTemplate() {
		parent::setupTemplate();
		Locale::requireComponents(array(
			LOCALE_COMPONENT_OMP_COMMON
		));
	}
}

?>
