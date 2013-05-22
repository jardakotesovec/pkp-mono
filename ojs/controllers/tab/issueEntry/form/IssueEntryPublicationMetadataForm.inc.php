<?php

/**
 * @file controllers/tab/issueEntry/form/IssueEntryPublicationMetadataForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueEntryPublicationMetadataForm
 * @ingroup controllers_tab_issueEntry_form_IssueEntryPublicationMetadataForm
 *
 * @brief Displays a submission's publication metadata entry form.
 */

import('lib.pkp.classes.form.Form');

class IssueEntryPublicationMetadataForm extends Form {

	/** @var $_submission Submission The submission used to show metadata information */
	var $_submission;

	/** @var $_publishedArticle PublishedArticle The published article associated with this submission */
	var $_publishedArticle;

	/** @var $_stageId int The current stage id */
	var $_stageId;

	/** @var $_userId int The current user ID */
	var $_userId;

	/**
	 * Parameters to configure the form template.
	 */
	var $_formParams;

	/**
	 * Constructor.
	 * @param $submissionId integer
	 * @param $userId integer
	 * @param $stageId integer
	 * @param $formParams array
	 */
	function IssueEntryPublicationMetadataForm($submissionId, $userId, $stageId = null, $formParams = null) {
		parent::Form('controllers/tab/issueEntry/form/publicationMetadataFormFields.tpl');
		$submissionDao = Application::getSubmissionDAO();
		$this->_submission = $submissionDao->getById($submissionId);

		$this->_stageId = $stageId;
		$this->_formParams = $formParams;
		$this->_userId = $userId;
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Fetch the HTML contents of the form.
	 * @param $request PKPRequest
	 * return string
	 */
	function fetch($request) {

		$context = $request->getContext();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('submissionId', $this->getSubmission()->getId());
		$templateMgr->assign('stageId', $this->getStageId());
		$templateMgr->assign('formParams', $this->getFormParams());
		$templateMgr->assign_by_ref('context', $context);

		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$enablePublicArticleId = $journalSettingsDao->getSetting($context->getId(),'enablePublicArticleId');
		$templateMgr->assign('enablePublicArticleId', $enablePublicArticleId);
		$enablePageNumber = $journalSettingsDao->getSetting($context->getId(), 'enablePageNumber');
		$templateMgr->assign('enablePageNumber', $enablePageNumber);

		// include issue possibilities
		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$templateMgr->assign('issueOptions', $issueAction->getIssueOptions());

		$publishedArticle =& $this->getPublishedArticle();
		if ($publishedArticle) {
			$templateMgr->assign_by_ref('publishedArticle', $publishedArticle);
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getById($publishedArticle->getIssueId());
			if ($issue) {
				$templateMgr->assign('issueAccess', $issue->getAccessStatus());
				$templateMgr->assign('accessOptions', array(
					ARTICLE_ACCESS_ISSUE_DEFAULT => __('editor.issues.default'),
					ARTICLE_ACCESS_OPEN => __('editor.issues.open')
				));
			}
		}

		// include payment information
		// Set up required Payment Related Information
		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$completedPaymentDao = DAORegistry::getDAO('OJSCompletedPaymentDAO');
		$publicationFeeEnabled = $paymentManager->publicationEnabled();
		$templateMgr->assign('publicationFeeEnabled',  $publicationFeeEnabled);
		if ($publicationFeeEnabled) {
			$templateMgr->assign_by_ref('publicationPayment', $completedPaymentDao->getPublicationCompletedPayment($context->getId(), $subission->getId()));
		}

		return parent::fetch($request);
	}

	function initData() {
		AppLocale::requireComponents(
			LOCALE_COMPONENT_APP_COMMON,
			LOCALE_COMPONENT_PKP_SUBMISSION,
			LOCALE_COMPONENT_APP_SUBMISSION,
			LOCALE_COMPONENT_APP_EDITOR
		);

		$submission = $this->getSubmission();
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$this->_publishedArticle =& $publishedArticleDao->getPublishedArticleByArticleId($submission->getId(), null, false);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the Submission
	 * @return Submission
	 */
	function &getSubmission() {
		return $this->_submission;
	}

	/**
	 * Get the PublishedArticle
	 * @return PublishedArticle
	 */
	function &getPublishedArticle() {
		return $this->_publishedArticle;
	}

	/**
	 * Get the stage id
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Get the extra form parameters.
	 */
	function getFormParams() {
		return $this->_formParams;
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('waivePublicationFee', 'markAsPaid', 'issueId', 'datePublished', 'accessStatus', 'pages', 'publicArticleId'));
	}

	/**
	 * Save the metadata and store the catalog data for this published
	 * monograph.
	 */
	function execute($request) {
		parent::execute($request);

		$submission = $this->getSubmission();
		$context = $request->getContext();

		$waivePublicationFee = $request->getUserVar('waivePublicationFee') ? true : false;
		if ($waivePublicationFee) {

			$markAsPaid = $request->getUserVar('markAsPaid');
			import('classes.payment.ojs.OJSPaymentManager');
			$paymentManager = new OJSPaymentManager($request);

			$user = $request->getUser();

			$queuedPayment =& $paymentManager->createQueuedPayment(
				$context->getId(),
				PAYMENT_TYPE_PUBLICATION,
				$markAsPaid ? $submission->getUserId() : $user->getId(),
				$submission->getId(),
				$markAsPaid ? $context->getSetting('publicationFee') : 0,
				$markAsPaid ? $context->getSetting('currency') : ''
			);

			$queuedPaymentId = $paymentManager->queuePayment($queuedPayment);

			// Since this is a waiver, fulfill the payment immediately
			$paymentManager->fulfillQueuedPayment($request, $queuedPayment, $markAsPaid?'ManualPayment':'Waiver');
		} else {
			// Get the issue for publication.
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issueId = $this->getData('issueId');
			$issue = $issueDao->getById($issueId, $context->getId());

			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$sectionEditorSubmissionDao = DAORegistry::getDAO('SectionEditorSubmissionDAO');
			$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticle = $publishedArticleDao->getPublishedArticleByArticleId($submission->getId(), null, false); /* @var $publishedArticle PublishedArticle */

			if ($publishedArticle) {
				if (!$issue || !$issue->getPublished()) {
					$fromIssue = $issueDao->getById($publishedArticle->getIssueId(), $context->getId());
					if ($fromIssue->getPublished()) {
						// Insert article tombstone
						import('classes.article.ArticleTombstoneManager');
						$articleTombstoneManager = new ArticleTombstoneManager();
						$articleTombstoneManager->insertArticleTombstone($submission, $context);
					}
				}
			}

			import('classes.search.ArticleSearchIndex');
			$articleSearchIndex = new ArticleSearchIndex();

			// define the access status for the article if none is set.
			$accessStatus = $this->getData('accessStatus') != '' ? $this->getData('accessStatus') : ARTICLE_ACCESS_ISSUE_DEFAULT;

			$articleDao = DAORegistry::getDAO('ArticleDAO');
			if (!is_null($this->getData('pages'))) {
				$submission->setPages($this->getData('pages'));
			}
			if (!is_null($this->getData('publicArticleId'))) {
				$articleDao->changePubId($submission->getId(), 'publisher-id', $this->getData('publicArticleId'));
			}

			if ($issue) {

				// Schedule against an issue.
				if ($publishedArticle) {
					$publishedArticle->setIssueId($issueId);
					$publishedArticle->setSeq(REALLY_BIG_NUMBER);
					$publishedArticle->setDatePublished($this->getData('datePublished'));
					$publishedArticle->setAccessStatus($accessStatus);
					$publishedArticleDao->updatePublishedArticle($publishedArticle);

					// Re-index the published article metadata.
					$articleSearchIndex->articleMetadataChanged($publishedArticle);
				} else {
					$publishedArticle = $publishedArticleDao->newDataObject();
					$publishedArticle->setId($submission->getId());
					$publishedArticle->setIssueId($issueId);
					$publishedArticle->setDatePublished(Core::getCurrentDate());
					$publishedArticle->setSeq(REALLY_BIG_NUMBER);
					$publishedArticle->setAccessStatus($accessStatus);
					$publishedArticle->setDatePublished($this->getData('datePublished'));

					$publishedArticleDao->insertPublishedArticle($publishedArticle);

					// If we're using custom section ordering, and if this is the first
					// article published in a section, make sure we enter a custom ordering
					// for it. (Default at the end of the list.)
					if ($sectionDao->customSectionOrderingExists($issueId)) {
						if ($sectionDao->getCustomSectionOrder($issueId, $submission->getSectionId()) === null) {
							$sectionDao->insertCustomSectionOrder($issueId, $submission->getSectionId(), REALLY_BIG_NUMBER);
							$sectionDao->resequenceCustomSectionOrders($issueId);
						}
					}

					// Index the published article metadata and files for the first time.
					$articleSearchIndex->articleMetadataChanged($publishedArticle);
					$articleSearchIndex->articleFilesChanged($publishedArticle);
				}

			} else {
				if ($publishedArticle) {
					// This was published elsewhere; make sure we don't
					// mess up sequencing information.
					$issueId = $publishedArticle->getIssueId();
					$publishedArticleDao->deletePublishedArticleByArticleId($ubmission->getId());

					// Delete the article from the search index.
					$articleSearchIndex->articleFileDeleted($ubmission->getId());
				}
			}

			// Resequence the articles.
			$publishedArticleDao->resequencePublishedArticles($submission->getSectionId(), $issueId);

			$submission->stampStatusModified();
			$articleDao->updateObject($submission);

			if ($issue && $issue->getPublished()) {
				$submission->setStatus(STATUS_PUBLISHED);
				// delete article tombstone
				$tombstoneDao = DAORegistry::getDAO('DataObjectTombstoneDAO');
				$tombstoneDao->deleteByDataObjectId($submission->getId());
			} else {
				$submission->setStatus(STATUS_QUEUED);
			}

			$sectionEditorSubmission = $sectionEditorSubmissionDao->getSectionEditorSubmission($submission->getId());
			if ($sectionEditorSubmission) {
				$sectionEditorSubmissionDao->updateSectionEditorSubmission($sectionEditorSubmission);
			}
			$articleSearchIndex->articleChangesFinished();
		}
	}
}

?>
