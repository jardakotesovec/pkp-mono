<?php

/**
 * @file classes/core/Request.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Request
 * @ingroup core
 *
 * @brief Class providing operations associated with HTTP requests.
 * Requests are assumed to be in the format http://host.tld/index.php/<press_id>/<page_name>/<operation_name>/<arguments...>
 * <press_id> is assumed to be "index" for top-level site requests.
 */

// $Id$


import('core.PKPRequest');

class Request extends PKPRequest {
	/**
	 * Redirect to the specified page within OJS. Shorthand for a common call to Request::redirect(Request::url(...)).
	 * @param $pressPath string The path of the Press to redirect to.
	 * @param $page string The name of the op to redirect to.
	 * @param $op string optional The name of the op to redirect to.
	 * @param $path mixed string or array containing path info for redirect.
	 * @param $params array Map of name => value pairs for additional parameters
	 * @param $anchor string Name of desired anchor on the target page
	 */
	function redirect($pressPath = null, $page = null, $op = null, $path = null, $params = null, $anchor = null) {
		Request::redirectUrl(Request::url($pressPath, $page, $op, $path, $params, $anchor));
	}

	/**
	 * Get the Press path requested in the URL ("index" for top-level site requests).
	 * @return string 
	 */
	function getRequestedPressPath() {
		static $press;

		if (!isset($press)) {
			if (Request::isPathInfoEnabled()) {
				$press = '';
				if (isset($_SERVER['PATH_INFO'])) {
					$vars = explode('/', $_SERVER['PATH_INFO']);
					if (count($vars) >= 2) {
						$press = Core::cleanFileVar($vars[1]);
					}
				}
			} else {
				$press = Request::getUserVar('press');
			}

			$press = empty($press) ? 'index' : $press;
			HookRegistry::call('Request::getRequestedPressPath', array(&$press));
		}

		return $press;
	}

	/**
	 * Get the Press associated with the current request.
	 * @return Press
	 */
	function &getPress() {
		static $press;

		if (!isset($press)) {
			$path = Request::getRequestedPressPath();
			if ($path != 'index') {
				$pressDao = &DAORegistry::getDAO('PressDAO');
				$press = $pressDao->getPressByPath(Request::getRequestedPressPath());
			}
		}

		return $press;
	}

	/**
	 * A Generic call to a context-defined path (e.g. a Journal or a Conference's path) 
	 * @param $contextLevel int (optional) the number of levels of context to return in the path
	 * @return array of String (each element the path to one context element)
	 */
	function getRequestedContextPath($contextLevel = null) {
		//there is only one $contextLevel, so no need to check
		return array(Request::getRequestedPressath());
	}
	
	/**
	 * A Generic call to a context defining object (e.g. a Journal, a Conference, or a SchedConf)
	 * @return Press
	 * @param $level int (optional) the desired context level
	 */
	function &getContext($level = 1) {
		$returner = false;
		switch ($level) {
			case 1:
				$returner =& Request::getPress();
				break;
		}
		return $returner;	
	}	
	
	/**
	 * Get the object that represents the desired context (e.g. Conference or Journal)
	 * @param $contextName String specifying the page context 
	 * @return Press
	 */
	function &getContextByName($contextName) {
		$returner = false;
		switch ($contextName) {
			case 'press':
				$returner =& Request::getPress();
				break;
		}
		return $returner;
	}

}

?>
