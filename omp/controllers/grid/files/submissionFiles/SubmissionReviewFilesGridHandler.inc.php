<?php

/**
 * @file controllers/grid/files/submissionFiles/SubmissionFilesGridHandler.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionFilesGridHandler
 * @ingroup controllers_grid_file
 *
 * @brief Handle uploading files to the review files grid.
 */

// import submission files grid specific classes
import('controllers.grid.files.submissionFiles.SubmissionFilesGridHandler');

class SubmissionReviewFilesGridHandler extends SubmissionFilesGridHandler {
	/** The monograph to add files to */
	var $_monographId;

	/**
	 * Constructor
	 */
	function SubmissionReviewFilesGridHandler() {
		parent::GridHandler();
	}

	//
	// Getters/Setters
	//

	//
	// Overridden template methods
	//
	/*
	* Configure the grid
	* @param PKPRequest $request
	*/
	function initialize(&$request) {
		parent::initialize($request);
		$this->setId('reviewFiles');

		$this->setId('reviewFiles');
		import('controllers.grid.files.reviewFiles.ReviewFilesGridCellProvider');
		$cellProvider =& new ReviewFilesGridCellProvider();
		$this->addColumn(new GridColumn('select',
			'common.select',
			null,
			'controllers/grid/files/reviewFiles/gridRowSelectInput.tpl',
			$cellProvider)
		);


	}

	//
	// Overridden methods from GridHandler
	//
	/**
	* Get the row handler - override the default row handler
	* @return LibraryFileGridRow
	*/
	function &getRowInstance() {
		$row = new SubmissionFilesGridRow();
		return $row;
	}

	/**
	 * Validate that the user is the assigned author for the monograph
	 * Raises a fatal error if validation fails.
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

		// 2) Only Editors may access
		$this->addCheck(new HandlerValidatorRoles($this, false, 'Insufficient privileges!', null, array(ROLE_ID_EDITOR)));

		// Execute standard checks
		if (!parent::validate($requiredContexts, $request)) return false;

		return true;

	}

	/**
	 * Display the final tab of the modal
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function finishFileSubmission(&$args, &$request) {
		$fileId = isset($args['fileId']) ? $args['fileId'] : null;
		$monographId = isset($args['monographId']) ? $args['monographId'] : null;

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('monographId', $monographId);
		$templateMgr->assign('fileId', $fileId);
		$templateMgr->assign('gridId', $this->getId());

		$json = new JSON('true', $templateMgr->fetch('controllers/grid/files/submissionFiles/form/reviewFileSubmissionComplete.tpl'));
		return $json->getString();
	}

	/**
	 * Return a grid row with for the submission grid
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function returnFileRow(&$args, &$request) {
		$fileId = isset($args['fileId']) ? $args['fileId'] : null;

		$bookFileTypeDao =& DAORegistry::getDAO('BookFileTypeDAO');
		$monographFileDao =& DAORegistry::getDAO('MonographFileDAO');
		$monographFile =& $monographFileDao->getMonographFile($fileId);
		$monographId = $monographFile->getMonographId();

		if($monographFile) {
			import('controllers.grid.files.reviewFiles.ReviewFilesGridHandler');
			$reviewFilesGridHandler =& new ReviewFilesGridHandler();
			$reviewFilesGridHandler->initialize($request);

			$fileType = $bookFileTypeDao->getById($monographFile->getAssocId());

			$row =& $reviewFilesGridHandler->getRowInstance();
			$row->setId($monographFile->getFileId());
			$row->setData($monographFile);
			$row->initialize($request);

			$json = new JSON('true', $reviewFilesGridHandler->_renderRowInternally($request, $row));
		} else {
			$json = new JSON('false', Locale::translate("There was an error with trying to fetch the file"));
		}

		return $json->getString();
	}




}