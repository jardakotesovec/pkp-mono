<?php

/**
 * @file controllers/grid/content/announcements/AnnouncementTypeGridRow.inc.php
 *
 * Copyright (c) 2000-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementTypeGridRow
 * @ingroup controllers_grid_content_announcements
 *
 * @brief Announcement type grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class AnnouncementTypeGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function AnnouncementTypeGridRow() {
		parent::GridRow();
	}


	//
	// Overridden methods from GridRow
	//
	/**
	 * @see GridRow::initialize()
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Is this a new row or an existing row?
		$element =& $this->getData();
		assert(is_a($element, 'AnnouncementType'));

		$rowId = $this->getId();

		if (!empty($rowId) && is_numeric($rowId)) {
			// Only add row actions if this is an existing row
			$router =& $request->getRouter();
			$actionArgs = array(
				'announcementTypeId' => $rowId
			);
			$this->addAction(
				new LinkAction(
					'edit',
					new AjaxModal(
						$router->url($request, null, null, 'editAnnouncementType', null, $actionArgs),
						__('grid.action.edit'),
						'edit',
						true
						),
					__('grid.action.edit'),
					'edit')
			);
			$this->addAction(
				new LinkAction(
					'remove',
					new RemoteActionConfirmationModal(
						__('common.confirmDelete'),
						null,
						$router->url($request, null, null, 'deleteAnnouncementType', null, $actionArgs)
						),
					__('grid.action.remove'),
					'delete')
			);

			// Set a non-default template that supports row actions
			$this->setTemplate('controllers/grid/gridRowWithActions.tpl');
		}
	}
}

?>
