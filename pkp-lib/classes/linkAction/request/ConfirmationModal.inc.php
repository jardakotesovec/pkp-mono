<?php
/**
 * @file classes/linkAction/request/ConfirmationModal.inc.php
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ConfirmationModal
 * @ingroup linkAction_request
 *
 * @brief Class defining a simple confirmation modal.
 */


import('lib.pkp.classes.linkAction.request.Modal');

class ConfirmationModal extends Modal {
	/** @var string A URL to be called when the confirmation button is clicked. */
	var $_remoteAction;

	/**
	 * @var string A translation key defining the text for the confirmation
	 * button of the modal.
	 */
	var $_okButton;

	/**
	 * @var string a translation key defining the text for the cancel
	 * button of the modal.
	 */
	var $_cancelButton;

	/**
	 * @var string a translation key defining the text for the dialog
	 *  text.
	 */
	var $_dialogText;

	/**
	 * Constructor
	 * @param $dialogText string (optional)
	 * @param $title string (optional)
	 * @param $remoteAction string (optional) A URL to be called
	 *  when the confirmation button is clicked.
	 * @param $titleIcon string (optional)
	 * @param $okButton string (optional)
	 * @param $cancelButton string (optional)
	 * @param $canClose boolean (optional)
	 */
	function ConfirmationModal($dialogText, $title = null, $remoteAction = null, $titleIcon = null, $okButton = 'common.ok', $cancelButton = 'common.cancel', $canClose = true) {
		parent::Modal($title, $titleIcon, $canClose);

		$this->_remoteAction = $remoteAction;
		$this->_okButton = $okButton;
		$this->_cancelButton = $cancelButton;
		$this->_dialogText = $dialogText;
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the remote action.
	 * @return string
	 */
	function getRemoteAction() {
		return $this->_remoteAction;
	}

	/**
	 * Get the translation key for the confirmation
	 * button text.
	 * @return string
	 */
	function getOkButton() {
		return $this->_okButton;
	}

	/**
	 * Get the translation key for the cancel
	 * button text.
	 * @return string
	 */
	function getCancelButton() {
		return $this->_cancelButton;
	}

	/**
	 * Get the translation key for the dialog
	 * text.
	 * @return string
	 */
	function getDialogText() {
		return $this->_dialogText;
	}


	//
	// Overridden methods from LinkActionRequest
	//
	/**
	 * @see LinkActionRequest::getLocalizedOptions()
	 */
	function getLocalizedOptions() {
		return array_merge(parent::getLocalizedOptions(), array(
				'modalHandler' => '$.pkp.controllers.modal.ConfirmationModalHandler',
				'remoteAction' => $this->getRemoteAction(),
				'okButton' => Locale::translate($this->getOkButton()),
				'cancelButton' => Locale::translate($this->getCancelButton()),
				'dialogText' => Locale::translate($this->getDialogText())));
	}
}

?>
