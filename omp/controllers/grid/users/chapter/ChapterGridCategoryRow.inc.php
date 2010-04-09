<?php

/**
 * @file controllers/grid/chapter/ChapterGridCategoryRow.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ChapterGridCategoryRow
 * @ingroup controllers_grid_chapter
 *
 * @brief Chapter grid category row definition
 */

import('controllers.grid.GridCategoryRow');

class ChapterGridCategoryRow extends GridCategoryRow {
	/**
	 * Constructor
	 */
	function ChapterGridCategoryRow() {
		parent::GridCategoryRow();
	}

	//
	// Overridden methods from GridRow
	//
	/**
	 * @see GridCategoryRow::initialize()
	 * @param $request PKPRequest
	 */
	function initialize(&$request) {
		// Do the default initialization
		parent::initialize($request);

		// Retrieve the monograph id from the request
		$monographId = $request->getUserVar('monographId');
		assert(is_numeric($monographId));

		// Is this a new row or an existing row?
		$chapterId = $this->getId();
		if (!empty($chapterId) && is_numeric($chapterId)) {
			$chapter =& $this->getData();

			// Only add row actions if this is an existing row
			$router =& $request->getRouter();
			$actionArgs = array(
				'monographId' => $monographId,
				'chapterId' => $chapterId
			);

			$this->addAction(
				new GridAction(
					'editChapter',
					GRID_ACTION_MODE_MODAL,
					GRID_ACTION_TYPE_REPLACE,
					$router->url($request, null, null, 'editChapter', null, $actionArgs),
					$chapter->getLocalizedTitle()
				)
			);
		}
	}
}