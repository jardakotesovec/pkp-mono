<?php

/**
 * @file controllers/grid/chapter/ChapterGridHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ChapterGridHandler
 * @ingroup controllers_grid_chapter
 *
 * @brief Handle chapter grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.CategoryGridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

// import chapter grid specific classes
import('controllers.grid.users.submissionContributor.SubmissionContributorGridCellProvider');
import('controllers.grid.users.chapter.ChapterGridCategoryRow');

// import validation classes
import('classes.handler.validation.HandlerValidatorPress');
import('lib.pkp.classes.handler.validation.HandlerValidatorRoles');

class ChapterGridHandler extends CategoryGridHandler{
	/** @var Monograph */
	var $_monograph;

	/**
	 * Constructor
	 */
	function ChapterGridHandler() {
		parent::GridHandler();
	}

	//
	// Getters/Setters
	//
	/**
	 * @see PKPHandler::getRemoteOperations()
	 * @return array
	 */
	function getRemoteOperations() {
		return array_merge(parent::getRemoteOperations(), array('addChapter', 'editChapter', 'updateChapter', 'deleteChapter'));
	}

	/**
	 * Get the monograph associated with this chapter grid.
	 * @return Monograph
	 */
	function &getMonograph() {
		return $this->_monograph;
	}


	//
	// Overridden methods from PKPHandler
	//
	/**
	 * Validate that the user is the assigned section editor for
	 * the chapter's monograph, or is a managing editor. Raises a
	 * fatal error if validation fails.
	 * @param $requiredContexts array
	 * @param $request PKPRequest
	 * @return boolean
	 */
	function validate($requiredContexts, $request) {
		// Retrieve the request context
		$router =& $request->getRouter();
		$press =& $router->getContext($request);
		$user =& $request->getUser();

		// 1) Ensure we're in a press
		$this->addCheck(new HandlerValidatorPress($this, false, 'No press in context!'));

		// 2) Only Authors may access
		$this->addCheck(new HandlerValidatorRoles($this, false, 'Insufficient privileges!', null, array(ROLE_ID_AUTHOR)));

		// 3) Only this monograph's author may access
		$monographId = $request->getUserVar('monographId');
		$monographDao =& DAORegistry::getDAO('MonographDAO');
		$monograph = $monographDao->getMonograph($monographId);

		if ( isset($user) && isset($monograph)) {
			$userId = $user->getId();
			$monographSubmiter = $monograph->getUserId();
			import('lib.pkp.classes.handler.validation.HandlerValidatorCustom');
			$this->addCheck(new HandlerValidatorCustom($this, false, 'Restricted site access!', null, create_function('$monographSubmitter, $userId', 'if ($monographSubmitter != $userId) return false; else return true;'), array($monographSubmiter, $userId)));
		}

		// Execute standard checks
		if (!parent::validate($requiredContexts, $request)) return false;

		$monographId =& $request->getUserVar('monographId');
		if (!is_numeric($monographId)) return false;

		// Retrieve the monograph associated with this citation grid
		$monographDAO =& DAORegistry::getDAO('MonographDAO');
		$monograph =& $monographDAO->getMonograph($monographId);

		// Monograph and editor validation
		if (!is_a($monograph, 'Monograph')) return false;

		// Validation successful
		$this->_monograph =& $monograph;

		return true;
	}

	/*
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);
		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_DEFAULT_SETTINGS));
		// Basic grid configuration
		$this->setTitle('grid.chapters.title');

		// Get the monograph id
		$monograph =& $this->getMonograph();
		assert(is_a($monograph, 'Monograph'));
		$monographId = $monograph->getId();

		// Set the category data
		$chapterDao =& DAORegistry::getDAO('ChapterDAO');
		$chapters =& $chapterDao->getChaptersWithAuthors($monographId);
		$this->setData($chapters);

		// Grid actions
		$router =& $request->getRouter();
		$actionArgs = array('monographId' => $monographId);
		$this->addAction(
			new LinkAction(
				'addChapter',
				LINK_ACTION_MODE_MODAL,
				LINK_ACTION_TYPE_APPEND,
				$router->url($request, null, null, 'addChapter', null, $actionArgs),
				'grid.action.addItem'
			)
		);

		// Columns
		// reuse the cell providers for the SubmissionContributorGrid
		$cellProvider = new SubmissionContributorGridCellProvider();
		$this->addColumn(
			new GridColumn(
				'name',
				'author.users.contributor.name',
				null,
				'controllers/grid/gridCell.tpl',
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'email',
				'author.users.contributor.email',
				null,
				'controllers/grid/gridCell.tpl',
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'role',
				'author.users.contributor.role',
				null,
				'controllers/grid/gridCell.tpl',
				$cellProvider
			)
		);
		$this->addColumn(
			new GridColumn(
				'principalContact',
				'author.users.contributor.principalContact',
				null,
				'controllers/grid/users/submissionContributor/primaryContact.tpl',
				$cellProvider
			)
		);
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return ChapterGridRow
	 */
	function &getCategoryRowInstance() {
		// Return a chapter row
		$row = new ChapterGridCategoryRow();
		return $row;
	}

	function getCategoryData(&$chapter) {
		return $chapter->getAuthors();
	}

	//
	// Public Chapter Grid Actions
	//
	function addChapter(&$args, &$request) {
		// Calling editChapter() with an empty row id will add
		// a new chapter.
		return $this->editChapter($args, $request);
	}

	/**
	 * Edit a chapter
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editChapter(&$args, &$request) {
		// Identify the monograph being worked on
		$monographId = $request->getUserVar('monographId');
		// Identify the chapter to be updated
		$chapterId = $request->getUserVar('chapterId');

		// Form handling
		import('controllers.grid.users.chapter.form.ChapterForm');
		$chapterForm = new ChapterForm($monographId, $chapterId);
		if ($chapterForm->isLocaleResubmit()) {
			$chapterForm->readInputData();
		} else {
			$chapterForm->initData();
		}

		$json = new JSON('true', $chapterForm->display($request));
		return $json->getString();
	}

	/**
	 * Update a chapter
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function updateChapter(&$args, &$request) {
		// Identify the monograph being worked on
		$monographId = $request->getUserVar('monographId');
		// Identify the chapter to be updated
		$chapterId = $request->getUserVar('chapterId');

		// Form initialization
		import('controllers.grid.users.chapter.form.ChapterForm');
		$chapterForm = new ChapterForm($monographId, $chapterId);
		$chapterForm->readInputData();

		// Form validation
		if ($chapterForm->validate()) {
			$chapterForm->execute();

			$chapter =& $chapterForm->getChapter();

			// Prepare the grid row data
			$categoryRow =& $this->getCategoryRowInstance();
			$categoryRow->setGridId($this->getId());
			$categoryRow->setId($chapter->getId());
			$categoryRow->setData($chapter);
			$categoryRow->initialize($request);

			// Render the row into a JSON response
			$chapterAuthorDao =& DAORegistry::getDAO('ChapterAuthorDAO');
			$authors =& $chapterAuthorDao->getAuthors($monographId, $chapter->getId());
			$groupIterator = $chapter->getId() % 5;
			$json = new JSON('true', $this->_renderCategoryInternally($request, $categoryRow, $groupIterator));
		} else {
			// Return an error
			$json = new JSON('false');
		}

		// Return the serialized JSON response
		return $json->getString();
	}

	/**
	 * Delete a chapter
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function deleteChapter(&$args, &$request) {
		// Identify the chapter to be deleted
		$chapter =& $this->_getChapterFromArgs($args);

		$chapterDAO = DAORegistry::getDAO('ChapterDAO');
		$result = $chapterDAO->deleteChapter($chapter);

		if ($result) {
			$json = new JSON('true');
		} else {
			$json = new JSON('false', Locale::translate('submission.chapters.grid.errorDeletingChapter'));
		}
		return $json->getString();
	}
}