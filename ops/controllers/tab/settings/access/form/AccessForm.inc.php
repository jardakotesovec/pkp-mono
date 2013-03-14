<?php

/**
 * @file controllers/tab/settings/siteAccessOptions/form/AccessForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AccessForm
 * @ingroup controllers_tab_settings_siteAccessOptions_form
 *
 * @brief Form to edit site access options.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class AccessForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function AccessForm($wizardMode = false) {
		parent::ContextSettingsForm(
			array(
				'publishingMode' => 'int',
			),
			'controllers/tab/settings/access/form/accessForm.tpl',
			$wizardMode
		);
	}
}

?>
