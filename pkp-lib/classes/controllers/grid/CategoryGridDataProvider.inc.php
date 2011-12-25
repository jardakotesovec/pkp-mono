<?php

/**
 * @file classes/controllers/grid/CategoryGridDataProvider.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategoryGridDataProvider
 * @ingroup classes_controllers_grid
 *
 * @brief Provide access to category grid data.
 */

// Import base class.
import('lib.pkp.classes.controllers.grid.GridDataProvider');

class CategoryGridDataProvider extends GridDataProvider {

	/**
	 * Constructor
	 */
	function CategoryGridDataProvider() {
		parent::GridDataProvider();
	}


	//
	// Template methods to be implemented by subclasses
	//
	/**
	 * Retrieve the category data to load into the grid.
	 * @param $categoryDataElement mixed
	 * @return array
	 */
	function &getCategoryData($categoryDataElement) {
		assert(false);
	}
}

?>
