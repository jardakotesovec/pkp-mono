<?php

/**
 * @file controllers/modals/editorDecision/form/SendReviewsForm.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SendReviewsForm
 * @ingroup controllers_modals_editorDecision_form
 *
 * @brief Form to request additional work from the author (Request revisions or
 *         resubmit for review), or to decline the submission.
 */

import('lib.pkp.classes.form.Form');

class SendReviewsForm extends Form {
	/** The monograph associated with the review assignment **/
	var $_monograph;

	/** The decision being taken **/
	var $_decision;

	/**
	 * Constructor.
	 */
	function SendReviewsForm($monograph, $decision) {
		parent::Form('controllers/modals/editorDecision/form/sendReviewsForm.tpl');
		$this->_monograph = $monograph;
		$this->_decision = (int) $decision;

		// Validation checks for this form
		$this->addCheck(new FormValidatorPost($this));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the Monograph
	 * @return Monograph
	 */
	function getMonograph() {
		return $this->_monograph;
	}

	//
	// Overridden template methods
	//
	/**
	 * Initialize form data with the author name and the monograph id.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function initData($args, &$request) {
		$press =& $request->getPress();
		$monograph =& $this->getMonograph();
		$submitter = $monograph->getUser();

		Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON, LOCALE_COMPONENT_OMP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION));

		import('classes.mail.MonographMailTemplate');
		$email = new MonographMailTemplate($monograph, 'EDITOR_DECISION_ACCEPT');
		$paramArray = array(
			'authorName' => $submitter->getFullName(),
			'pressName' => $press->getLocalizedName(),
			'monographTitle' => $monograph->getLocalizedTitle(),
			'editorialContactSignature' => $submitter->getContactSignature(),
		);
		$email->assignParams($paramArray);

		import('classes.submission.common.Action');
		$actionLabels = array(SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS => 'editor.monograph.decision.requestRevisions',
							  SUBMISSION_EDITOR_DECISION_RESUBMIT => 'editor.monograph.decision.resubmit',
							  SUBMISSION_EDITOR_DECISION_DECLINE => 'editor.monograph.decision.decline');

		$this->_data = array(
			'monographId' => $this->_monographId,
			'decision' => $this->_decision,
			'authorName' => $monograph->getAuthorString(),
			'personalMessage' => $email->getBody(),
			'actionLabel' => $actionLabels[$this->_decision]
		);

	}

	/**
	 * Fetch the modal content
	 * @param $request PKPRequest
	 * @see Form::fetch()
	 */
	function fetch(&$request) {
		$monograph =& $this->getMonograph();
		$reviewType = (int) $request->getUserVar('reviewType'); //FIXME #6102: What to do with reviewType?
		$round = (int) $request->getUserVar('round');
		assert($round <= $monograph->getCurrentReviewRound() && $round > 0);

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('monographId', $this->_monographId);
		$templateMgr->assign_by_ref('monograph', $monograph);
		$this->setData('reviewType', $reviewType);
		$this->setData('round', $round);
		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('decision', 'personalMessage', 'selectedAttachments'));
	}

	/**
	 * Save review assignment
	 * @param $args array
	 * @param $request PKPRequest
	 * @see Form::execute()
	 */
	function execute($args, &$request) {
		$decision = $this->getData('decision');
		$monograph =& $this->getMonograph();
		$seriesEditorSubmissionDao =& DAORegistry::getDAO('SeriesEditorSubmissionDAO');
		$seriesEditorSubmission =& $seriesEditorSubmissionDao->getSeriesEditorSubmission($monograph->getMonographId());

		$reviewRoundDao =& DAORegistry::getDAO('ReviewRoundDAO');
		$currentReviewRound =& $reviewRoundDao->build($monograph->getMonographId(), $seriesEditorSubmission->getCurrentReviewType(), $seriesEditorSubmission->getCurrentRound());

		import('classes.submission.seriesEditor.SeriesEditorAction');
		$seriesEditorAction =& new SeriesEditorAction();
		switch ($decision) {
			case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
				// 1. Record the decision
				$seriesEditorAction->recordDecision($seriesEditorSubmission, SUBMISSION_EDITOR_DECISION_DECLINE);

				// 2. select email key
				$emailKey = 'SUBMISSION_UNSUITABLE';

				// 3. Set status of round
				$status = REVIEW_ROUND_STATUS_REVISIONS_REQUESTED;
				break;

			case SUBMISSION_EDITOR_DECISION_RESUBMIT:
				// 1. Record the decision
				$seriesEditorAction->recordDecision($seriesEditorSubmission, SUBMISSION_EDITOR_DECISION_RESUBMIT);

				// 2.  Set status of round
				$status = REVIEW_ROUND_STATUS_RESUBMITTED;

				// 3.  Select email key
				$emailKey = 'EDITOR_DECISION_RESUBMIT';
				break;

			case SUBMISSION_EDITOR_DECISION_DECLINE:
				// 1. Record the decision
				$seriesEditorAction->recordDecision($seriesEditorSubmission, SUBMISSION_EDITOR_DECISION_DECLINE);

				// 2. select email key
				$emailKey = 'SUBMISSION_UNSUITABLE';

				// 3. Set status of round
				$status = REVIEW_ROUND_STATUS_DECLINED;
				break;

			default:
				// only support the three decisions above
				assert(false);
		}

		$currentReviewRound->setStatus($status);
		$reviewRoundDao->updateObject($currentReviewRound);

		// n. Send Personal message to author
		$submitter = $seriesEditorSubmission->getUser();
		import('classes.mail.MonographMailTemplate');
		$email = new MonographMailTemplate($seriesEditorSubmission, $emailKey);
		$email->setBody($this->getData('personalMessage'));
		$email->addRecipient($submitter->getEmail(), $submitter->getFullName());
		$email->setAssoc(MONOGRAPH_EMAIL_EDITOR_NOTIFY_AUTHOR, MONOGRAPH_EMAIL_TYPE_EDITOR, $currentReviewRound->getRound());

		// Attach the selected reviewer attachments
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$selectedAttachments = $this->getData('selectedAttachments') ? $this->getData('selectedAttachments') : array();
		$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewIndexes =& $reviewAssignmentDao->getReviewIndexesForRound($seriesEditorSubmission->getId(), $seriesEditorSubmission->getCurrentRound());
		assert(is_array($reviewAssignmentId));
		foreach ($selectedAttachments as $attachmentId) {
			$monographFile =& $monographFileDao->getMonographFile($attachmentId);
			assert(is_a($monographFile, 'MonographFile'));

			$fileName = $monographFile->getOriginalFileName();
			$reviewAssignmentId = $monographFile->getAssocId();
			assert(is_numeric($reviewAssignmentId));

			$reviewIndex = $reviewIndexes[$reviewAssignmentId];
			assert(!is_null($reviewIndex));

			$reviewerPrefix = chr(ord('A') + $reviewIndex);
			$email->addAttachment($monographFile->getFilePath(), $reviewerPrefix . '-' . $monographFile->getOriginalFileName());

			// Update monograph to set viewable as true, so author can view the file on their submission summary page
			$monographFile->setViewable(true);
			$monographFileDao->updateMonographFile($monographFile);
		}

		$email->send();
	}
}

?>
