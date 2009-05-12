<?php

/**
 * @mainpage OMP API Reference
 * 
 * Welcome to the OMP API Reference. This resource contains documentation
 * generated automatically from the OMP source code.
 * 
 * The design of Open Monograph press is heavily structured for maintainability,
 * flexibility and robustness. For this reason it may seem complex when first  
 * approached. Those familiar with Sun's Enterprise Java Beans technology or the  
 * Model-View-Controller (MVC) pattern will note many similarities. 
 * 
 * As in a MVC structure, data storage and representation, user interface  
 * presentation, and control are separated into different layers. The major  
 * categories, roughly ordered from "front-end" to "back-end," follow: 
 * - Smarty templates, which are responsible for assembling HTML pages to  
 * display to users; 
 * - Page classes, which receive requests from users' web browsers, delegate any  
 * required processing to various other classes, and call up the appropriate  
 * Smarty template to generate a response; 
 * - Action classes, which are used by the Page classes to perform non-trivial  
 * processing of user requests; 
 * - Model classes, which implement PHP objects representing the system's  
 * various entities, such as Users, Articles, and Presses; 
 * - Data Access Objects (DAOs), which generally provide (amongst others)  
 * update, create, and delete functions for their associated Model classes, are  
 * responsible for all database interaction; 
 * - Support classes, which provide core functionalities, miscellaneous common  
 * 
 * As the system makes use of inheritance and has consistent class naming  
 * conventionsl it is generally easy to tell what category a particular class falls into.  
 * For example, a Data Access Object class always inherits from the DAO class, has a  
 * Class name of the form [Something]%DAO, and has a filename of the form  
 * [Something]%DAO.inc.php. 
 *
 * To learn more about developing OMP, there are several additional resources that may be useful:
 * - The docs/README document
 * - The PKP support forum at http://pkp.sfu.ca/support/forum
 * - The technical reference (and other documents), available at http://pkp.sfu.ca/omp_documentation
 * 
 * @file index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup index
 *
 * Front controller for OMP site. Loads required files and dispatches requests to the appropriate handler. 
 */

ini_set('display_errors', '2');
ERROR_REPORTING(E_ALL);

define('INDEX_FILE_LOCATION', __FILE__);
/**
 * Handle a new request.
 */
function handleRequest() {
	if (!Config::getVar('general', 'installed')) {
		define('SESSION_DISABLE_INIT', 1);
		if (pageRequiresInstall()) {
			// Redirect to installer if application has not been installed
			Request::redirect(null, 'install');
		}
	}

	// Determine the handler for this request
	$page = Request::getRequestedPage();
	$op = Request::getRequestedOp();

	$sourceFile = sprintf('pages/%s/index.php', $page);

	// If a hook has been registered to handle this page, give it the
	// opportunity to load required resources and set HANDLER_CLASS.
	if (!HookRegistry::call('LoadHandler', array(&$page, &$op, &$sourceFile))) {
		if (file_exists($sourceFile) || file_exists('lib/pkp/'.$sourceFile)) require($sourceFile);
		else require('pages/index/index.php');
	}

	if (!defined('SESSION_DISABLE_INIT')) {
		// Initialize session
		$sessionManager =& SessionManager::getManager();
		$session =& $sessionManager->getUserSession();
	}

	$methods = array_map('strtolower', get_class_methods(HANDLER_CLASS));

	if (in_array(strtolower($op), $methods)) {
		// Call a specific operation
		//call_user_func(array(HANDLER_CLASS, $op), Request::getRequestedArgs());
		$HandlerClass = HANDLER_CLASS;
		$handler = new $HandlerClass;
		$handler->$op(Request::getRequestedArgs());

	} else {
		// Call the selected handler's index operation
		//call_user_func(array(HANDLER_CLASS, 'index'), Request::getRequestedArgs());
		$HandlerClass = HANDLER_CLASS;
		$handler = new $HandlerClass;
		$handler->index(Request::getRequestedArgs());
	}
}

// Initialize system and handle the current request
require('includes/driver.inc.php');
initSystem();
handleRequest();

?>
