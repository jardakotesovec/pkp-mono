<?php

/**
 * @file api/v1/submission/SubmissionHandler.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 * @ingroup api_v1_submission
 *
 * @brief Handle API requests for submission operations.
 *
 */

import('lib.pkp.classes.handler.APIHandler');

class SubmissionHandler extends APIHandler {

	/**
	 * The unique endpoint string for this handler
	 *
	 * @param string
	 */
	protected $_handlerPath = 'submissions';

	/**
	 * Constructor
	 */
	public function __construct() {
		$roles = array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_REVIEWER, ROLE_ID_AUTHOR);
		$this->_endpoints = array(
			'GET' => array (
				array(
					'pattern' => $this->getEndpointPattern(),
					'handler' => array($this, 'get'),
					'roles' => array(
						ROLE_ID_SITE_ADMIN,
						ROLE_ID_MANAGER,
						ROLE_ID_SUB_EDITOR,
						ROLE_ID_AUTHOR,
						ROLE_ID_REVIEWER,
						ROLE_ID_ASSISTANT,
					),
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}/files/{fileId}',
					'handler' => array($this,'getFile'),
					'roles' => $roles
				),
				array(
					'pattern' => $this->getEndpointPattern() . '/{submissionId}',
					'handler' => array($this,'submissionMetadata'),
					'roles' => $roles
				),
			)
		);
		parent::__construct();
	}

	//
	// Implement methods from PKPHandler
	//
	function authorize($request, &$args, $roleAssignments) {
		//import('lib.pkp.classes.security.authorization.SubmissionFileAccessPolicy');
		//$this->addPolicy(new SubmissionFileAccessPolicy($request, $args, $roleAssignments, SUBMISSION_FILE_ACCESS_READ));
		import('lib.pkp.classes.security.authorization.SubmissionAccessPolicy');
		$this->addPolicy(new SubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Get the entity ID for a specified parameter name.
	 * (Parameter names are generally defined in authorization policies
	 * @return int|string?
	 */
	public function getEntityId($parameterName) {
		switch ($parameterName) {
			case 'submissionId':
				$parts = explode('/', trim($_SERVER['PATH_INFO'], '/'));
				return $parts[4];
				break;
		}
		return parent::getEntityId($parameterName);
	}


	//
	// Public handler methods
	//
	/**
	 * Get a list of submissions according to passed query parameters
	 *
	 * `assignedTo` int Return submissions assigned to this user ID. Note: only
	 *   journal managers and admins can view submissions assigned to anyone
	 *   but themselves. Default: null
	 * `unassigned` bool Whether to fetch submissions without an assigned editor
	 *   Default: null. Note: can't be used if an `assignedTo` param is passed.
	 * `searchPhrase` string Return submissions matching the words in this
	 *   phrase. @see SubmissionDAO::get(). Default: null
	 * `status` int|array|string Return submissions with this status or a
	 *   comma-separated list of statuses. Accepts the following constants:
	 *   STATUS_QUEUED, STATUS_PUBLISHED, STATUS_DECLINED. Default:
	 *   [STATUS_QUEUED, STATUS_PUBLISHED, STATUS_DECLINED]
	 * `count` int Maximum number of submissions to return. Default: 20
	 * `page` int Page of results to start. Default: 1
	 * `orderBy` string Order by column. Supports `id`, `dateSubmitted`,
	 *    `lastModified`. Default: `dateSubmitted`
	 * `order` string Supports `ASC` or `DESC`. Default: `DESC`
	 *
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function get($slimRequest, $response, $args) {

		$request = Application::getRequest();
		$currentUser = $request->getUser();
		$context = $request->getContext();

		// Merge query params over default params
		$defaultParams = array(
			'count' => 20,
			'page' => 1,
		);

		if (!$currentUser->hasRole(array(ROLE_ID_MANAGER, ROLE_ID_ADMIN), $context->getId())) {
			$defaultParams['assignedTo'] = $currentUser->getId();
		}

		$params = array_merge($defaultParams, $slimRequest->getQueryParams());

		// Process query params to format incoming data as needed
		foreach ($params as $param => $val) {
			switch ($param) {

				// Always convert status to array
				case 'status':
					if (strpos($val, ',') > -1) {
						$val = explode(',', $val);
					} elseif (!is_array($val)) {
						$val = array($val);
					}
					$params[$param] = array_map('intval', $val);
					break;

				case 'assignedTo':
					$params[$param] = (int) $val;
					break;

				// Only journal managers and admins can access unassigned
				// submissions
				case 'unassigned':
					$params[$param] = $currentUser->hasRole(array(ROLE_ID_MANAGER, ROLE_ID_ADMIN), $context->getId());
					break;

				// Enforce a maximum count to prevent the API from crippling the
				// server
				case 'count':
					$params[$param] = min(20, (int) $val);
					break;

				case 'page':
					$params[$param] = (int) $val;
					break;

				case 'orderBy':
					if ($val !== 'id' || $val !== 'dateSubmitted' || $val !== 'lastModified') {
						unset($params[$param]);
					}
					break;

				case 'order':
					$params[$param] = $val === 'ASC' ? $val : 'DESC';
					break;
			}
		}

		// Prevent users from viewing submissions they're not assigned to,
		// except for journal managers and admins.
		if (!$currentUser->hasRole(array(ROLE_ID_MANAGER, ROLE_ID_ADMIN), $context->getId())
				&& $params['assignedTo'] != $currentUser->getId()) {
			return $response->withStatus(403)->withJsonError('api.submissions.403.requestedOthersUnpublishedSubmissions');
		}

		$submissionDao = Application::getSubmissionDAO();
		$data = $submissionDao->get($this, $params, $context->getId());

		return $response->withJson($data);
	}

	/**
	 * Handle file download
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function getFile($slimRequest, $response, $args) {
		//$fileId = $slimRequest->getAttribute('fileId');
		//$response->getBody()->write("Serving file with id: {$fileId}");
		//return $response;
		$request = $this->_request;
		$submissionFile = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILE);
		assert($submissionFile); // Should have been validated already
		$context = $request->getContext();
		import('lib.pkp.classes.file.SubmissionFileManager');
		$fileManager = new SubmissionFileManager($context->getId(), $submissionFile->getSubmissionId());
		if (!$fileManager->downloadFile($submissionFile->getFileId(), $submissionFile->getRevision(), false, $submissionFile->getClientFileName())) {
			error_log('FileApiHandler: File ' . $submissionFile->getFilePath() . ' does not exist or is not readable!');
			header('HTTP/1.0 500 Internal Server Error');
			fatalError('500 Internal Server Error');
		}
	}

	/**
	 * Get submission metadata
	 * @param $slimRequest Request Slim request object
	 * @param $response Response object
	 * @param array $args arguments
	 * @return Response
	 */
	public function submissionMetadata($slimRequest, $response, $args) {
		$request = $this->_request;
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		assert($submission);

		$queryParams = $slimRequest->getQueryParams();
		$format = isset($queryParams['format'])?$queryParams['format']:'';
		import('plugins.metadata.dc11.schema.Dc11Schema');
		if ($format == 'dc11' || $format == '') {
			$schema = new Dc11Schema();
			return $this->getMetadaJSON($submission, $schema);
		}
		import('plugins.metadata.mods34.schema.Mods34Schema');
		if ($format == 'mods34') {
			$schema = new Mods34Schema();
			return $this->getMetadaJSON($submission, $schema);
		}
	}

	function getMetadaJSON($submission, $schema) {
		$metadata = array();
		$dcDescription = $submission->extractMetadata($schema);
		foreach ($dcDescription->getProperties() as $propertyName => $property) {
			if ($dcDescription->hasStatement($propertyName)) {
				if ($property->getTranslated()) {
					$values = $dcDescription->getStatementTranslations($propertyName);
				} else {
					$values = $dcDescription->getStatement($propertyName);
				}
				$metadata[$propertyName][] = $values;
			}
		}
		return json_encode($metadata);
	}
}
