<?php

/**
 * @file controllers/grid/users/queries/form/QueryForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class QueryForm
 * @ingroup controllers_grid_users_queries_form
 *
 * @brief Form for adding/editing a new query
 */

import('lib.pkp.classes.form.Form');

class QueryForm extends Form {
	/** @var Submission The submission associated with the query being edited **/
	var $_submission;

	/** @var int The stage id associated with the query being edited **/
	var $_stageId;

	/** @var Query Query the query being edited **/
	var $_query;

	/** @var boolean True iff this is a newly-created query */
	var $_isNew;

	/**
	 * Constructor.
	 * @param $request Request
	 * @param $submission Submission
	 * @param $stageId int
	 * @param $queryId int Optional query ID to edit. If none provided, a
	 *  (potentially temporary) query will be created.
	 */
	function QueryForm($submission, $stageId, $queryId = null) {
		parent::Form('controllers/grid/queries/form/queryForm.tpl');
		$this->setSubmission($submission);
		$this->setStageId($stageId);

		$queryDao = DAORegistry::getDAO('QueryDAO');
		if (!$queryId) {
			$this->_isNew = true;

			$query = $queryDao->newDataObject();
			$query->setSubmissionId($submission->getId());
			$query->setStageId($stageId);
			$queryDao->insertObject($query);
		} else {
			$query = $queryDao->getById($queryId, $submission->getId());
			assert($query);
			// New queries will not have a head note.
			$this->_isNew = !$query->getHeadNote();
		}

		$this->setQuery($query);

		// Validation checks for this form
		$this->addCheck(new FormValidatorListbuilder($this, 'users', 'stageParticipants.notify.warning'));
		$this->addCheck(new FormValidator($this, 'subject', 'required', 'submission.queries.subjectRequired'));
		$this->addCheck(new FormValidator($this, 'comment', 'required', 'submission.queries.messageRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the query
	 * @return Query
	 */
	function getQuery() {
		return $this->_query;
	}

	/**
	 * Set the query
	 * @param @query Query
	 */
	function setQuery($query) {
		$this->_query = $query;
	}

	/**
	 * Get the stage id
	 * @return int
	 */
	function getStageId() {
		return $this->_stageId;
	}

	/**
	 * Set the stage id
	 * @param int
	 */
	function setStageId($stageId) {
		$this->_stageId = $stageId;
	}

	/**
	 * Get the Submission
	 * @return Submission
	 */
	function getSubmission() {
		return $this->_submission;
	}

	/**
	 * Set the Submission
	 * @param Submission
	 */
	function setSubmission($submission) {
		$this->_submission = $submission;
	}

	//
	// Overridden template methods
	//
	/**
	 * Initialize form data from the associated author.
	 */
	function initData() {
		$queryDao = DAORegistry::getDAO('QueryDAO');
		if ($query = $this->getQuery()) {
			$noteDao = DAORegistry::getDAO('NoteDAO');
			$notes = $noteDao->getByAssoc(ASSOC_TYPE_QUERY, $query->getId(), null, NOTE_ORDER_ID, SORT_DIRECTION_ASC);
			$firstNote = $notes->next();
			$this->_data = array(
				'queryId' => $query->getId(),
				'subject' => $firstNote?$firstNote->getTitle():null,
				'comment' => $firstNote?$firstNote->getContents():null,
				'userId' => $firstNote?$firstNote->getUserId():null,
				'userIds' => $queryDao->getParticipantIds($query->getId()),
			);
		} else {
			// set intial defaults for queries.
		}
		// in order to be able to use the hook
		return parent::initData();
	}

	/**
	 * Fetch the form.
	 * @see Form::fetch()
	 */
	function fetch($request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_EDITOR);

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'submissionId' => $this->getSubmission()->getId(),
			'stageId' => $this->getStageId(),
			'isNew' => $this->_isNew,
		));

		return parent::fetch($request);
	}

	/**
	 * Assign form data to user-submitted data.
	 * @see Form::readInputData()
	 */
	function readInputData($request) {
		$this->readUserVars(array(
			'submissionId',
			'stageId',
			'subject',
			'comment',
			'users',
		));

		// For new queries, make the user ID available to set upon execute
		$user = $request->getUser();
		$this->setData('userId', $user->getId());
	}

	/**
	 * @copydoc Form::execute()
	 * @param $request PKPRequest
	 */
	function execute($request) {
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$query = $this->getQuery();

		$noteDao = DAORegistry::getDAO('NoteDAO');
		if ($this->_isNew) {
			$headNote = $noteDao->newDataObject();
			$headNote->setUserId($request->getUser()->getId());
			$headNote->setAssocType(ASSOC_TYPE_QUERY);
			$headNote->setAssocId($query->getId());
			$headNote->setDateCreated(Core::getCurrentDate());
		} else {
			$headNote = $query->getHeadNote();
		}
		$headNote->setDateModified(Core::getCurrentDate());
		$headNote->setTitle($this->getData('subject'));
		$headNote->setContents($this->getData('comment'));
		if ($this->_isNew) {
			$noteDao->insertObject($headNote);
		} else {
			$noteDao->updateObject($headNote);
		}

		import('lib.pkp.classes.controllers.listbuilder.ListbuilderHandler');
		ListbuilderHandler::unpack($request, $this->getData('users'));

		$queryDao->updateObject($query);
	}

	/**
	 * @copydoc ListbuilderHandler::insertEntry()
	 */
	function insertEntry($request, $newRowId) {
		$query = $this->getQuery();
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$queryDao->insertParticipant($query->getId(), $newRowId['name']);
	}

	/**
	 * @copydoc ListbuilderHandler::deleteEntry()
	 */
	function deleteEntry($request, $rowId) {
		$query = $this->getQuery();
		$queryDao = DAORegistry::getDAO('QueryDAO');
		$queryDao->removeParticipant($query->getId(), $rowId);
	}
}

?>
