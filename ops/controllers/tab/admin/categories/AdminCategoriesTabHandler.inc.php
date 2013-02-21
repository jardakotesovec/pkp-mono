<?php

/**
 * @file controllers/tab/settings/AdminCategoriesTabHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminCategoriesTabHandler
 * @ingroup controllers_tab_settings
 *
 * @brief Handle AJAX operations for categories tab on administration settings page.
 */

// Import the base Handler.
import('lib.pkp.controllers.tab.settings.AdminSettingsTabHandler');

class AdminCategoriesTabHandler extends AdminSettingsTabHandler {

	/**
	 * Constructor
	 */
	function AdminCategoriesTabHandler() {
		parent::AdminSettingsTabHandler();
		$this->setPageTabs(array(
			'categories' => 'controllers.tab.admin.categories.form.CategorySettingsForm'
		));
	}
}

?>
