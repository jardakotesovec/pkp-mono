<?php

/**
 * @file controllers/grid/users/chapter/ChapterGridHandler.inc.php
 *
 * Copyright (c) 2000-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ChapterGridHandler
 * @ingroup controllers_grid_users_chapter
 *
 * @brief Handle chapter grid requests.
 */

// import grid base classes
import('lib.pkp.classes.controllers.grid.CategoryGridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

// import chapter grid specific classes
import('controllers.grid.users.author.AuthorGridCellProvider');
import('controllers.grid.users.chapter.ChapterGridCategoryRow');

// Link action & modal classes
import('lib.pkp.classes.linkAction.request.AjaxModal');

class ChapterGridHandler extends CategoryGridHandler {
	/**
	 * Constructor
	 */
	function ChapterGridHandler() {
		parent::GridHandler();
		$this->addRoleAssignment(
			array(ROLE_ID_AUTHOR, ROLE_ID_SERIES_EDITOR, ROLE_ID_PRESS_MANAGER),
			array('fetchGrid', 'fetchRow', 'addChapter', 'editChapter', 'updateChapter', 'deleteChapter')
		);
	}


	//
	// Getters and Setters
	//
	/**
	 * Get the monograph associated with this chapter grid.
	 * @return Monograph
	 */
	function &getMonograph() {
		return $this->getAuthorizedContextObject(ASSOC_TYPE_MONOGRAPH);
	}

	//
	// Implement template methods from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize(&$request, $args, $roleAssignments) {
		import('classes.security.authorization.OmpSubmissionAccessPolicy');
		$this->addPolicy(new OmpSubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Configure the grid
	 * @param $request PKPRequest
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Retrieve the authorized monograph
		$monograph =& $this->getMonograph();

		Locale::requireComponents(array(LOCALE_COMPONENT_OMP_DEFAULT_SETTINGS, LOCALE_COMPONENT_OMP_SUBMISSION));
		// Basic grid configuration
		$this->setTitle('grid.chapters.title');

		// Grid actions
		$router =& $request->getRouter();
		$actionArgs = array('monographId' => $monograph->getId());
		$this->addAction(
			new LinkAction(
				'addChapter',
				new AjaxModal(
					$router->url($request, null, null, 'addChapter', null, $actionArgs),
					__('submission.chapter.addChapter'),
					'fileManagement'
				),
				__('grid.action.addItem'),
				'add_item'
			)
		);

		// Columns
		// reuse the cell providers for the AuthorGrid
		$cellProvider = new AuthorGridCellProvider();
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
				'controllers/grid/users/author/primaryContact.tpl',
				$cellProvider
			)
		);
	}

	/**
	 * @see GridDataProvider::getRequestArgs()
	 */
	function getRequestArgs() {
		$monograph =& $this->getMonograph();
		return array(
			'monographId' => $monograph->getId()
		);
	}

	/**
	 * @see GridHandler::loadData
	 */
	function &loadData(&$request, $filter) {
		$monograph =& $this->getMonograph();
		$chapterDao =& DAORegistry::getDAO('ChapterDAO');
		$chapters =& $chapterDao->getChapters($monograph->getId());
		return $chapters;
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * @see GridHandler::getRowInstance()
	 * @return ChapterGridRow
	 */
	function &getCategoryRowInstance() {
		$row = new ChapterGridCategoryRow();
		return $row;
	}


	//
	// Implement template methods from CategoryGridHandler
	//
	/**
	 * @see CategoryGridHandler::getCategoryData()
	 */
	function getCategoryData(&$chapter) {
		$authorFactory =& $chapter->getAuthors(); /* @var $authorFactory DAOResultFactory */
		$authors = $authorFactory->toAssociativeArray();
		return $authors;
	}


	//
	// Public Chapter Grid Actions
	//
	/**
	 * Add a chapter.
	 * @param $args array
	 * @param $request Request
	 */
	function addChapter($args, &$request) {
		// Calling editChapter() with an empty row id will add
		// a new chapter.
		return $this->editChapter($args, $request);
	}

	/**
	 * Edit a chapter
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function editChapter($args, &$request) {
		$chapter =& $this->_getChapterFromArgs();

		// Form handling
		import('controllers.grid.users.chapter.form.ChapterForm');
		$chapterForm = new ChapterForm($this->getMonograph(), $chapter->getId());
		$chapterForm->initData();

		$json = new JSONMessage(true, $chapterForm->fetch($request));
		return $json->getString();
	}

	/**
	 * Update a chapter
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function updateChapter($args, &$request) {
		// Identify the chapter to be updated
		$chapter =& $this->_getChapterFromArgs($args);

		// Form initialization
		import('controllers.grid.users.chapter.form.ChapterForm');
		$chapterForm = new ChapterForm($this->getMonograph(), $chapter->getId());
		$chapterForm->readInputData();

		// Form validation
		if ($chapterForm->validate()) {
			$chapterForm->execute();

			return DAO::getDataChangedEvent($chapterId);
		} else {
			// Return an error
			$json = new JSONMessage(false);
		}

		// Return the serialized JSON response
		return $json->getString();
	}

	/**
	 * Delete a chapter
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function deleteChapter($args, &$request) {
		// Identify the chapter to be deleted
		$chapter =& $this->_getChapterFromArgs($args);

		$chapterDAO = DAORegistry::getDAO('ChapterDAO');
		$result = $chapterDAO->deleteChapter($chapter);

		if ($result) {
			$json = new JSONMessage(true);
		} else {
			$json = new JSONMessage(false, Locale::translate('submission.chapters.grid.errorDeletingChapter'));
		}
		return $json->getString();
	}

	/**
	 * Fetch and validate the chapter from the request arguments
	 */
	function &_getChapterFromArgs() {
		$monograph =& $this->getMonograph();
		$chapterDao =& DAORegistry::getDAO('ChapterDAO');
		$chapter =& $chapterDao->getChapter((int) $request->getUserVar('chapterId'), $monograph->getId());
	}
}

?>
