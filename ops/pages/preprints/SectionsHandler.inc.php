<?php

/**
 * @file pages/sections/SeriesHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionsHandler
 * @ingroup pages_preprints
 *
 * @brief Handle requests for sections functions.
 *
 */

import('classes.handler.Handler');

class SectionsHandler extends Handler {
	/** sections associated with the request **/
	var $sections;

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {

		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('classes.security.authorization.OpsServerMustPublishPolicy');
		$this->addPolicy(new OpsServerMustPublishPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * View a section
	 *
	 * @param $args array [
	 *		@option string Section ID
	 *		@option string page number
 	 * ]
	 * @param $request PKPRequest
	 * @return null|JSONMessage
	 */
	function section($args, $request) {
		$sectionPath = isset($args[0]) ? $args[0] : null;
		$page = isset($args[1]) && ctype_digit((string) $args[1]) ? (int) $args[1] : 1;
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : CONTEXT_ID_NONE;

		// The page $arg can only contain an integer that's not 1. The first page
		// URL does not include page $arg
		if (isset($args[1]) && (!ctype_digit((string) $args[1]) || $args[1] == 1)) {
			$request->getDispatcher()->handle404();
			exit;
		}

		if (!$sectionPath || !$contextId) {
			$request->getDispatcher()->handle404();
			exit;
		}

		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$sections = $sectionDao->getByContextId($contextId);

		$sectionExists = false;
		while ($section = $sections->next()) {
			if ($section->getData('path') === $sectionPath) {
				$sectionExists = true;
				break;
			}
		}

		if (!$sectionExists) {
			$request->getDispatcher()->handle404();
			exit;
		}

		import('classes.submission.Submission'); // Import status constants

		$params = [
			'contextId' => $contextId,
			'count' => $context->getData('itemsPerPage'),
			'offset' => $page ? ($page - 1) * $context->getData('itemsPerPage') : 0,
			'orderBy' => 'datePublished',
			'sectionIds' => [(int) $section->getId()],
			'status' => STATUS_PUBLISHED,
		];

		$result = Services::get('submission')->getMany($params);
		$total = Services::get('submission')->getMax($params);

		if ($page > 1 && !$result->valid()) {
			$request->getDispatcher()->handle404();
			exit;
		}

		$submissions = [];
		foreach ($result as $submission) {
			$submissions[] = $submission;
		}

		$showingStart = $params['offset'] + 1;
		$showingEnd = min($params['offset'] + $params['count'], $params['offset'] + count($submissions));
		$nextPage = $total > $showingEnd ? $page + 1 : null;
		$prevPage = $showingStart > 1 ? $page - 1 : null;

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'section' => $section,
			'sectionPath' => $sectionPath,
			'preprints' => $submissions,
			'showingStart' => $showingStart,
			'showingEnd' => $showingEnd,
			'total' => $total,
			'nextPage' => $nextPage,
			'prevPage' => $prevPage,
		));

		$templateMgr->display('frontend/pages/sections.tpl');

	}


}
