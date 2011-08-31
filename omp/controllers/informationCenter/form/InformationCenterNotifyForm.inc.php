<?php

/**
 * @file controllers/informationCenter/form/InformationCenterNotifyForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InformationCenterNotifyForm
 * @ingroup informationCenter_form
 *
 * @brief Form to notify a user regarding a file
 */



import('lib.pkp.classes.form.Form');

class InformationCenterNotifyForm extends Form {
	/** @var int The file/monograph ID this form is for */
	var $itemId;

	/** @var int The type of item the form is for (used to determine which email template to use) */
	var $itemType;

	/**
	 * Constructor.
	 */
	function InformationCenterNotifyForm($itemId, $itemType) {
		parent::Form('controllers/informationCenter/notify.tpl');
		$this->itemId = $itemId;
		$this->itemType = $itemType;

		$this->addCheck(new FormValidator($this, 'newRowId', 'required', 'informationCenter.notify.warning'));
		$this->addCheck(new FormValidator($this, 'message', 'required', 'informationCenter.notify.warning'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Fetch the form.
	 * @see Form::fetch()
	 */
	function fetch(&$request) {
		$templateMgr =& TemplateManager::getManager();
		if($this->itemType == ASSOC_TYPE_MONOGRAPH) {
			$monographId = $this->itemId;
		} else {
			$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
			$monographFile =& $submissionFileDao->getLatestRevision($this->itemId);
			$monographId = $monographFile->getMonographId();
		}
		$templateMgr->assign_by_ref('monographId', $monographId);
		$templateMgr->assign_by_ref('itemId', $this->itemId);

		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('message', 'users'));

		import('lib.pkp.classes.core.JSONManager');
		$jsonManager = new JSONManager();
		$data = $jsonManager->decode($this->getData('users'));
		$newRowIds = array();

		if (isset($data->changes[0])) {
			$ids = $data->changes[0]; // this comes out of the newRowId[name] property in the submitted JSON object, and are userIds
			foreach ($ids as $property => $value) {
				$newRowIds[] = (int) $value;
			}
		}
		$this->setData('newRowId', $newRowIds); // we use these in our validate() method.
	}

	/**
	 * Register a new user.
	 * @return userId int
	 */
	function execute(&$request) {
		import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');
		$userData = $this->getData('users');
		ListBuilderHandler::unpack($request, $userData);
	}

	/**
	 * Prepare an email for each user and send
	 * @see ListbuilderHandler::insertEntry
	 */
	function insertEntry(&$request, $newRowId) {
		$user =& $request->getUser();
		$userDao =& DAORegistry::getDAO('UserDAO');
		$monographDao =& DAORegistry::getDAO('MonographDAO');
		import('classes.mail.MonographMailTemplate');

		if($this->itemType == ASSOC_TYPE_MONOGRAPH) {
			$monographId = $this->itemId;
		} else {
			$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
			$monographFile =& $submissionFileDao->getLatestRevision($this->itemId);
			$monographId = $monographFile->getMonographId();
		}

		$email = new MonographMailTemplate($monographDao->getMonograph($monographId));
		$email->setFrom($user->getEmail(), $user->getFullName());

		foreach ($newRowId as $id) {
			$user = $userDao->getUser($id);
			$email->addRecipient($user->getEmail(), $user->getFullName());
			$email->setBody($this->getData('message'));
			$email->send($request);
		}
	}

	/**
	 * Validate the form
	 * @see Form::validate();
	 */
	function validate() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		foreach ($this->getData('newRowId') as $id) { // sanity check to make sure submitted ids correspond to users in the system
			if ($userDao->getUser($id) == null)
				return false;
		}
		return parent::validate();
	}

	/**
	 * Delete a signoff
	 * FIXME: it was throwing a warning when this was not specified. We just want client side delete.
	 */
	function deleteEntry(&$request, $rowId) {
		return true;
	}
}

?>
