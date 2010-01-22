<?php

/**
 * @file controllers/grid/sponsor/form/SponsorForm.inc.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SponsorForm
 * @ingroup controllers_grid_sponsor_form
 *
 * @brief Form for adding/edditing a sponsor
 * stores/retrieves from an associative array
 */

import('form.Form');

class SponsorForm extends Form {
	/** the id for the sponsor being edited **/
	var $sponsorId;

	/**
	 * Constructor.
	 */
	function SponsorForm($sponsorId = null) {
		$this->sponsorId = $sponsorId;
		parent::Form('controllers/grid/sponsor/form/sponsorForm.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidator($this, 'institution', 'required', 'manager.setup.form.sponsors.institutionRequired'));
		$this->addCheck(new FormValidator($this, 'url', 'required', 'manager.emails.form.sponsors.urlRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData(&$args, &$request) {
		$press =& Request::getPress();

		$sponsors = $press->getSetting('sponsors');
		if ( $this->sponsorId && isset($sponsors[$this->sponsorId]) ) {
			$this->_data = array(
				'sponsorId' => $this->sponsorId,
				'institution' => $sponsors[$this->sponsorId]['institution'],
				'url' => $sponsors[$this->sponsorId]['url']
				);
		} else {
			$this->_data = array(
				'institution' => '',
				'url' => ''
			);
		}

		// grid related data
		$this->_data['gridId'] = $args['gridId'];
		$this->_data['rowId'] = isset($args['rowId']) ? $args['rowId'] : null;
	}

	/**
	 * Display
	 */
	function display() {
		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_MANAGER));
		parent::display();
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('sponsorId', 'institution', 'url'));
		$this->readUserVars(array('gridId', 'rowId'));
	}

	/**
	 * Save email template.
	 */
	function execute() {
		$press =& Request::getPress();
		$sponsors = $press->getSetting('sponsors');
		//FIXME: a bit of kludge to get unique sponsor id's
		$this->sponsorId = ($this->sponsorId?$this->sponsorId:(max(array_keys($sponsors)) + 1));
		$sponsors[$this->sponsorId] = array('institution' => $this->getData('institution'),
							'url' => $this->getData('url'));

		$press->updateSetting('sponsors', $sponsors, 'object', false);
		return true;
	}
}

?>
