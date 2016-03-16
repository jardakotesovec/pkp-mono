<?php
/**
 * @defgroup controllers_confirmationModal_linkAction Confirmation Modal Link Action
 */

/**
 * @file controllers/confirmationModal/linkAction/ViewReviewGuidelinesLinkAction.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ViewReviewGuidelinesLinkAction
 * @ingroup controllers_confirmationModal_linkAction
 *
 * @brief An action to open the review guidelines confirmation modal.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class ViewReviewGuidelinesLinkAction extends LinkAction {
	/** @var Context */
	var $_context;

	/** @var int WORKFLOW_STAGE_ID_... */
	var $_stageId;

	/**
	 * Constructor
	 * @param $request Request
	 * @param $stageId int Stage ID of review assignment
	 */
	function ViewReviewGuidelinesLinkAction($request, $stageId) {
		$this->_context = $request->getContext();
		$this->_stageId = $stageId;

		import('lib.pkp.classes.linkAction.request.ConfirmationModal');
		$viewGuidelinesModal = new ConfirmationModal(
			$this->getGuidelines(),
			__('reviewer.submission.guidelines'),
			null, null,
			false
		);

		// Configure the link action.
		parent::LinkAction('viewReviewGuidelines', $viewGuidelinesModal, __('reviewer.submission.guidelines'));
	}

	/**
	 * Get the guidelines for the specified stage.
	 * @return string?
	 */
	function getGuidelines() {
		return $this->_context->getLocalizedSetting(
			$this->_stageId==WORKFLOW_STAGE_ID_EXTERNAL_REVIEW?'reviewGuidelines':'internalReviewGuidelines'
		);
	}
}

?>
