<?php

/**
 * @file classes/controllers/listbuilder/ListbuilderGridRow.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ListbuilderGridRow
 * @ingroup controllers_listbuilder
 *
 * @brief Handle list builder row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class ListbuilderGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function ListbuilderGridRow() {
		parent::GridRow();
	}

	//
	// Overridden template methods
	//
	/**
	 * @see GridRow::initialize()
	 * @param PKPRequest $request
	 */
	function initialize(&$request, $template = 'controllers/listbuilder/listbuilderGridRow.tpl') {
		parent::initialize($request);

		// add list builder row template
		$this->setTemplate($template);
	}
}

?>
