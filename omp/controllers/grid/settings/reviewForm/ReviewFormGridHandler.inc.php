<?php

/**
 * @file controllers/grid/settings/reviewForm/ReviewFormGridHandler.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormGridHandler
 * @ingroup controllers_grid_reviewForm
 *
 * @brief Handle requests for Review Form management functions.
 *
*/

import('controllers.grid.settings.SetupGridHandler');
import('controllers.grid.settings.reviewForm.ReviewFormGridRow');

class ReviewFormGridHandler extends SetupGridHandler {
	/**
	 * Constructor
	 **/
	function ReviewFormGridHandler() {
		parent::SetupGridHandler();
	}

	//
	// Getters/Setters
	//
	/**
	 * @see lib/pkp/classes/handler/PKPHandler#getRemoteOperations()
	 */
	function getRemoteOperations() {
		return array_merge(parent::getRemoteOperations(), array('createReviewForm', 'editReviewForm', 'updateReviewForm', 'deleteReviewForm', 'previewReviewForm'));
	}

	/**
	 * Configure the grid
	 * @param PKPRequest $request
	 */
	function initialize(&$request) {
		parent::initialize($request);
		// Basic grid configuration
		$this->setId('reviewForm');
		$this->setTitle('grid.reviewForm.title');

		Locale::requireComponents(array(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OMP_MANAGER));

		// Elements to be displayed in the grid
		$router =& $request->getRouter();
		$press =& $router->getContext($request);

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForms =& $reviewFormDao->getByAssocId(ASSOC_TYPE_PRESS, $press->getId());
		$this->setData($reviewForms);

		// Add grid-level actions
		$this->addAction(
			new GridAction(
				'createReviewForm',
				GRID_ACTION_MODE_MODAL,
				GRID_ACTION_TYPE_APPEND,
				$router->url($request, null, null, 'createReviewForm', null, array('gridId' => $this->getId())),
				'grid.action.addItem'
			),
			GRID_ACTION_POSITION_ABOVE
		);

		// Columns
		$emptyActions = array();
		// Basic grid row configuration
		import('controllers.grid.settings.reviewForm.ReviewFormGridCellProvider');
		$cellProvider =& new ReviewFormGridCellProvider();
		$this->addColumn(new GridColumn('titles', '
										common.title',
										null,
										$emptyActions,
										'controllers/grid/gridCellInSpan.tpl',
										$cellProvider));

		/* FIXME: http://pkp.sfu.ca/bugzilla/show_bug.cgi?id=5122 */
		//$this->addColumn(new GridColumn('completeCount', 'common.completed'));
		//$this->addColumn(new GridColumn('incompleteCount', 'common.title'));
	}

	//
	// Overridden methods from GridHandler
	//
	/**
	 * Get the row handler - override the default row handler
	 * @return ReviewFormGridRow
	 */
	function &getRowInstance() {
		$row = new ReviewFormGridRow();
		return $row;
	}

	//
	// Public Review Form Actions
	//

	/**
	 * Display form to create a new review form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function createReviewForm(&$args, &$request) {
		// Delegate to the row handler
		$reviewFormRow =& $this->getRow();

		// Calling editReviewForm with an empty row id will add a new review form.
		$this->editReviewForm($args, $request);
	}

	/**
	 * Display form to create/edit a review form.
	 * @param $args array, first parameter is the ID of the review form to edit
	 * @param $request PKPRequest
	 */
	function editReviewForm(&$args, &$request) {
		$this->setupTemplate($args, $request);

		$reviewFormId = $this->getId();

		$press =& Request::getPress();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_PRESS, $press->getId());

		if ($reviewFormId != null && (!isset($reviewForm) || $reviewForm->getCompleteCount() != 0 || $reviewForm->getIncompleteCount() != 0)) {
			Request::redirect(null, null, 'reviewForms');
		} else {
			$templateMgr =& TemplateManager::getManager();

			if ($reviewFormId == null) {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.create');
			} else {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.edit');
			}

			import('controllers.grid.settings.reviewForm.form.ReviewFormForm');
			$reviewFormForm = new ReviewFormForm($reviewFormId);

			if ($reviewFormForm->isLocaleResubmit()) {
				$reviewFormForm->readInputData();
			} else {
				$reviewFormForm->initData($args, $request);
			}
			$reviewFormForm->display();
		}
	}

	/**
	 * Save changes to a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function updateReviewForm(&$args, &$request) {
		$reviewFormId = Request::getUserVar('reviewFormId') === null? null : (int) Request::getUserVar('reviewFormId');

		if ($reviewFormId === null) {
			$reviewForm = null;
		} else {
			$router =& $request->getRouter();
			$context =& $router->getContext($request);
			$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
			$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_PRESS, $context->getId());
		}

		$press =& Request::getPress();

		import('controllers.grid.settings.reviewForm.form.ReviewFormForm');
		$reviewFormForm = new ReviewFormForm($reviewFormId);
		$reviewFormForm->readInputData();

		if ($reviewFormForm->validate()) {
			$reviewFormForm->execute();

			$row =& $this->getRowInstance();
			$row->setGridId($this->getId());
			$row->setId($reviewFormForm->reviewForm->getId());
			$row->setData($reviewFormForm->reviewForm);
			$row->initialize($request);

			$json = new JSON('true', $this->_renderRowInternally($request, row));
		} else {
			$json = new JSON('false');

			$templateMgr =& TemplateManager::getManager();
			if ($reviewFormId == null) {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.create');
			} else {
				$templateMgr->assign('pageTitle', 'manager.reviewForms.edit');
			}

			$reviewFormForm->display();
		}
		return $json->getString();
	}

	/**
	 * Delete a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string
	 */
	function deleteReviewForm(&$args, &$request) {
		$router =& $request->getRouter();
		$press =& $router->getContext($request);

		$reviewFormId = $this->getId();
		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_PRESS, $press->getId());

		if (isset($reviewForm) && $reviewForm->getCompleteCount() == 0 && $reviewForm->getIncompleteCount() == 0) {
			$reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$reviewAssignments =& $reviewAssignmentDao->getByReviewFormId($reviewFormId);

			foreach ($reviewAssignments as $reviewAssignment) {
				$reviewAssignment->setReviewFormId('');
				$reviewAssignmentDao->updateObject($reviewAssignment);
			}

			$reviewFormDao->deleteById($reviewFormId);
			$json = new JSON('true');
		} else {
			$json = new JSON('false', Locale::translate('manager.setup.errorDeletingReviewForm'));
		}

		echo $json->getString();
	}

	/**
	 * Preview a review form.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function previewReviewForm(&$args, &$request) {
		$router =& $request->getRouter();
		$press =& $router->getContext($request);

		$reviewFormId = $this->getId();

		$reviewFormDao =& DAORegistry::getDAO('ReviewFormDAO');
		$reviewForm =& $reviewFormDao->getReviewForm($reviewFormId, ASSOC_TYPE_PRESS, $press->getId());
		$reviewFormElementDao =& DAORegistry::getDAO('ReviewFormElementDAO');
		$reviewFormElements =& $reviewFormElementDao->getReviewFormElements($reviewFormId);

		if (!isset($reviewForm)) {
			return '';
		}

		$templateMgr =& TemplateManager::getManager();

		$templateMgr->assign('pageTitle', 'manager.reviewForms.preview');
		$templateMgr->assign_by_ref('reviewForm', $reviewForm);
		$templateMgr->assign('reviewFormElements', $reviewFormElements);
		$templateMgr->display('controllers/grid/settings/reviewForm/previewReviewForm.tpl');
	}

}

?>
