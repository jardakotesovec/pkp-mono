<?php

/**
 * @file controllers/grid/content/announcements/ViewAnnouncementGridHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ViewAnnouncementGridHandler
 * @ingroup controllers_grid_content_announcements
 *
 * @brief View announcements grid.
 */

import('controllers.grid.content.announcements.AnnouncementGridHandler');

class ViewAnnouncementGridHandler extends AnnouncementGridHandler {
	/**
	 * Constructor
	 */
	function ViewAnnouncementGridHandler() {
		parent::AnnouncementGridHandler();
	}


	/**
	 * @see AnnouncementGridHandler::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);

		$displayLimit = (boolean) $request->getUserVar('displayLimit');
		if ($displayLimit) {
			$press =& $request->getPress();
			$numAnnouncementsHomepage = $press->getSetting('numAnnouncementsHomepage');
			$gridElements = $this->getGridDataElements($request);
			if (count($gridElements) > $numAnnouncementsHomepage) {
				$dispatcher =& $request->getDispatcher();
				import('lib.pkp.classes.linkAction.request.RedirectAction');
				$actionRequest = new RedirectAction($dispatcher->url($request, ROUTE_PAGE, null, 'announcement'));
				$moreAnnouncementsAction = new LinkAction('moreAnnouncements', $actionRequest, __('announcement.moreAnnouncements'));
				$this->addAction($moreAnnouncementsAction, GRID_ACTION_POSITION_BELOW);

				$limitedElements = array();
				for ($i = 0; $i < $numAnnouncementsHomepage; $i++) {
					$limitedElements[key($gridElements)] = current($gridElements);
					next($gridElements);
				}
				$this->setGridDataElements($limitedElements);
			}
		}
	}

	/**
	 * @see GridHandler::getGridRangeInfo()
	 * Override so the display limit announcements setting can work correctly.
	 */
	function getGridRangeInfo($request, $rangeName) {
		import('lib.pkp.classes.db.DBResultRange');
		return new DBResultRange(-1, -1);
	}
}

?>
