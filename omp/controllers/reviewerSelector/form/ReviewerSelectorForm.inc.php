<?php

/**
 * @file controllers/reviewerSelector/form/ReviewerSelectorForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewerSelectorForm
 * @ingroup controllers_reviewerSelector_form
 *
 * @brief Form for displaying an advanced reviewer search
 */

import('lib.pkp.classes.form.Form');

class ReviewerSelectorForm extends Form {

	/** @var int */
	var $_monographId;

	/**
	 * Constructor.
	 */
	function ReviewerSelectorForm($monographId = null) {
		parent::Form('controllers/reviewerSelector/advancedSearchForm.tpl');

		$this->_monographId = $monographId;

		$this->addCheck(new FormValidatorPost($this));
	}



	/**
	 * Fetch
	 * @see Form::fetch()
	 */
	function fetch(&$request) {
		$templateMgr =& TemplateManager::getManager();

		$seriesEditorSubmissionDAO =& DAORegistry::getDAO('SeriesEditorSubmissionDAO');
		$reviewerValues = $seriesEditorSubmissionDAO->getAnonymousReviewerStatistics();

		$templateMgr->assign('reviewerValues', $reviewerValues);

		return parent::fetch($request);
	}

	/**
	 * Save submission file
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function execute($args, &$request) {
		$submissionFileDao =& DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$monographFile =& $submissionFileDao->getLatestRevision($this->_fileId);

		$monographFile->setName($this->getData('name'), Locale::getLocale());
		$submissionFileDao->updateObject($monographFile);
	}
}

?>
