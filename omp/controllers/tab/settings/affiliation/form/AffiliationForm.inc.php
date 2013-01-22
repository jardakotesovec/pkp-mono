<?php

/**
 * @file controllers/tab/settings/affiliation/form/AffiliationForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AffiliationForm
 * @ingroup controllers_tab_settings_affiliation_form
 *
 * @brief Form to edit press affiliation and support information.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class AffiliationForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function AffiliationForm($wizardMode = false) {
		$settings = array(
			'sponsorNote' => 'string',
			'contributorNote' => 'string'
		);

		parent::ContextSettingsForm($settings, 'controllers/tab/settings/affiliation/form/affiliationForm.tpl', $wizardMode);
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @see Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('sponsorNote', 'contributorNote');
	}
}

?>
