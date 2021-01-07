<?php

/**
 * @file pages/catalog/CatalogBookHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CatalogBookHandler
 * @ingroup pages_catalog
 *
 * @brief Handle requests for the book-specific part of the public-facing
 *   catalog.
 */

import('classes.handler.Handler');

// import UI base classes
import('lib.pkp.classes.linkAction.LinkAction');
import('lib.pkp.classes.core.JSONMessage');

class CatalogBookHandler extends Handler {
	/** @var Publication The requested publication */
	public $publication;

	/** @var boolean Is this a request for a specific version */
	public $isVersionRequest = false;

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Overridden functions from PKPHandler
	//
	/**
	 * @see PKPHandler::authorize()
	 * @param $request PKPRequest
	 * @param $args array
	 * @param $roleAssignments array
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('classes.security.authorization.OmpPublishedSubmissionAccessPolicy');
		$this->addPolicy(new OmpPublishedSubmissionAccessPolicy($request, $args, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}


	//
	// Public handler methods
	//
	/**
	 * Display a published submission in the public catalog.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function book($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$this->setupTemplate($request, $submission);
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_SUBMISSION); // submission.synopsis; submission.copyrightStatement

		// Get the requested publication or default to the current publication
		$submissionId = array_shift($args);
		$subPath = empty($args) ? 0 : array_shift($args);
		if ($subPath === 'version') {
			$this->isVersionRequest = true;
			$publicationId = (int) array_shift($args);
			foreach ($submission->getData('publications') as $publication) {
				if ($publication->getId() === $publicationId) {
					$this->publication = $publication;
				}
			}
		} else {
			$this->publication = $submission->getCurrentPublication();
		}

		if (!$this->publication || $this->publication->getData('status') !== STATUS_PUBLISHED) {
			$request->getDispatcher()->handle404();
		}

		// If the publication has been reached through an outdated
		// urlPath, redirect to the latest version
		if (!ctype_digit((string) $submissionId) && $submissionId !== $this->publication->getData('urlPath') && !$subPath) {
			$newArgs = $args;
			$newArgs = $this->publication->getData('urlPath')
				? $this->publication->getData('urlPath')
				: $this->publication->getId();
			$request->redirect(null, $request->getRequestedPage(), $request->getRequestedOp(), $newArgs);
		}

		$templateMgr->assign([
			'publishedSubmission' => $submission,
			'publication' => $this->publication,
			'firstPublication' => reset($submission->getData('publications')),
			'currentPublication' => $submission->getCurrentPublication(),
			'authorString' => $this->publication->getAuthorString(DAORegistry::getDAO('UserGroupDAO')->getByContextId($submission->getData('contextId'))->toArray()),
		]);

		// Provide the publication formats to the template
		$availablePublicationFormats = [];
		$availableRemotePublicationFormats = [];
		foreach ($this->publication->getData('publicationFormats') as $format) {
			if ($format->getIsAvailable()) {
				$availablePublicationFormats[] = $format;
				if ($format->getRemoteURL()) {
					$availableRemotePublicationFormats[] = $format;
				}
			}
		}
		$templateMgr->assign(array(
			'publicationFormats' => $availablePublicationFormats,
			'remotePublicationFormats' => $availableRemotePublicationFormats,
		));

		// Assign chapters (if they exist)
		$templateMgr->assign('chapters', DAORegistry::getDAO('ChapterDAO')->getByPublicationId($this->publication->getId())->toAssociativeArray());

		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign(array(
			'pubIdPlugins' => PluginRegistry::loadCategory('pubIds', true),
			'ccLicenseBadge' => Application::getCCLicenseBadge($this->publication->getData('licenseUrl')),
		));

		// Categories
		$templateMgr->assign([
			'categories' => DAORegistry::getDAO('CategoryDAO')->getByPublicationId($this->publication->getId())->toArray(),
		]);

		// Citations
		if ($this->publication->getData('citationsRaw')) {
			$parsedCitations = DAORegistry::getDAO('CitationDAO')->getByPublicationId($this->publication->getId());
			$templateMgr->assign([
				'citations' => $parsedCitations->toArray(),
				'parsedCitations' => $parsedCitations, // compatible with older themes
			]);
		}

		// Retrieve editors for an edited volume
		$editors = [];
		if ($submission->getWorkType() == WORK_TYPE_EDITED_VOLUME) {
			foreach ($this->publication->getData('authors') as $author) {
				if ($author->getIsVolumeEditor()) {
					$editors[] = $author;
				}
			}
		}
		$templateMgr->assign([
			'editors' => $editors,
		]);

		// Consider public identifiers
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		$templateMgr->assign('pubIdPlugins', $pubIdPlugins);

		$pubFormatFiles = Services::get('submissionFile')->getMany([
			'submissionIds' => [$submission->getId()],
			'assocTypes' => [ASSOC_TYPE_PUBLICATION_FORMAT]
		]);
		$availableFiles = [];
		foreach ($pubFormatFiles as $pubFormatFile) {
			if ($pubFormatFile->getDirectSalesPrice() !== null) {
				$availableFiles[] = $pubFormatFile;
			}
		}

		// Only pass files in pub formats that are also available
		$filteredAvailableFiles = array();
		foreach ($availableFiles as $submissionFile) {
			foreach ($availablePublicationFormats as $format) {
				if ($submissionFile->getData('assocId') == $format->getId()) {
					$filteredAvailableFiles[] = $submissionFile;
					break;
				}
			}
		}
		$templateMgr->assign('availableFiles', $filteredAvailableFiles);

		// Provide the currency to the template, if configured.
		if ($currencyCode = $request->getContext()->getData('currency')) {
			$isoCodes = new \Sokil\IsoCodes\IsoCodesFactory();
			$templateMgr->assign('currency', $isoCodes->getCurrencies()->getByLetterCode($currencyCode));
		}

		// Add data for backwards compatibility
		$templateMgr->assign([
			'keywords' => $this->publication->getLocalizedData('keywords'),
			'licenseUrl' => $this->publication->getData('licenseUrl'),
		]);

		// Ask robots not to index outdated versions and point to the canonical url for the latest version
		if ($this->publication->getId() !== $submission->getCurrentPublication()->getId()) {
			$templateMgr->addHeader('noindex', '<meta name="robots" content="noindex">');
			$url = $request->getDispatcher()->url($request, ROUTE_PAGE, null, 'catalog', 'book', $submission->getBestId());
			$templateMgr->addHeader('canonical', '<link rel="canonical" href="' . $url . '">');
		}

		// Display
		if (!HookRegistry::call('CatalogBookHandler::book', array(&$request, &$submission))) {
			return $templateMgr->display('frontend/pages/book.tpl');
		}
	}

	/**
	 * Use an inline viewer to view a published submission publication
	 * format file.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function view($args, $request) {
		$this->download($args, $request, true);
	}

	/**
	 * Download a published submission publication format file.
	 * @param $args array
	 * @param $request PKPRequest
	 * @param $view boolean True iff inline viewer should be used, if available
	 */
	function download($args, $request, $view = false) {
		$dispatcher = $request->getDispatcher();
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$this->setupTemplate($request, $submission);
		$press = $request->getPress();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_SUBMISSION);

		$monographId = array_shift($args); // Validated thru auth
		$subPath = array_shift($args);
		if ($subPath === 'version') {
			$publicationId = array_shift($args);
			$representationId = array_shift($args);
			$bestFileId = array_shift($args);
		} else {
			$publicationId = $submission->getCurrentPublication()->getId();
			$representationId = $subPath;
			$bestFileId = array_shift($args);
		}

		$publicationFormat = Application::get()->getRepresentationDAO()->getByBestId($representationId, $publicationId);
		if (!$publicationFormat || !$publicationFormat->getIsAvailable() || $remoteURL = $publicationFormat->getRemoteURL()) $dispatcher->handle404();

		$publication = null;
		foreach ((array) $submission->getData('publications') as $iPublication) {
			if ($iPublication->getId() == $publicationId) {
				$publication = $iPublication;
				break;
			}
		}

		if (empty($publication)
				|| $publication->getData('status') !== STATUS_PUBLISHED
				|| $publicationFormat->getData('publicationId') !== $publication->getId()) {
			$dispatcher->handle404();
		}

		import('lib.pkp.classes.submission.SubmissionFile'); // File constants
		$submissionFile = DAORegistry::getDAO('SubmissionFileDAO')->getByBestId($bestFileId, $submission->getId());
		if (!$submissionFile) $dispatcher->handle404();

		$path = $submissionFile->getData('path');
		$filename = Services::get('file')->formatFilename($path, $submissionFile->getLocalizedData('name'));
		switch ($submissionFile->getData('assocType')) {
			case ASSOC_TYPE_PUBLICATION_FORMAT: // Publication format file
				if ($submissionFile->getData('assocId') != $publicationFormat->getId() || $submissionFile->getDirectSalesPrice() === null) $dispatcher->handle404();
				break;
			case ASSOC_TYPE_SUBMISSION_FILE: // Dependent file
				$genreDao = DAORegistry::getDAO('GenreDAO'); /* @var $genreDao GenreDAO */
				$genre = $genreDao->getById($submissionFile->getGenreId());
				if (!$genre->getDependent()) $dispatcher->handle404();
				return Services::get('file')->download($path, $filename);
				break;
			default: $dispatcher->handle404();
		}

		$urlPath = [$submission->getBestId()];
		if ($publicationId !== $submission->getCurrentPublication()->getId()) {
			$urlPath[] = 'version';
			$urlPath[] = $publicationId;
		}
		$urlPath[] = $publicationFormat->getBestId();
		$urlPath[] = $submissionFile->getBestId();

		$chapterDao = DAORegistry::getDAO('ChapterDAO'); /* @var $chapterDao ChapterDAO */
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign(array(
			'publishedSubmission' => $submission,
			'publicationFormat' => $publicationFormat,
			'submissionFile' => $submissionFile,
			'chapter' => $chapterDao->getChapter($submissionFile->getData('chapterId')),
			'downloadUrl' => $dispatcher->url($request, ROUTE_PAGE, null, null, 'download', $urlPath, array('inline' => true)),
		));

		$ompCompletedPaymentDao = DAORegistry::getDAO('OMPCompletedPaymentDAO'); /* @var $ompCompletedPaymentDao OMPCompletedPaymentDAO */
		$user = $request->getUser();
		if ($submissionFile->getDirectSalesPrice() === '0' || ($user && $ompCompletedPaymentDao->hasPaidPurchaseFile($user->getId(), $submissionFile->getId()))) {
			// Paid purchase or open access.
			if (!$user && $press->getData('restrictMonographAccess')) {
				// User needs to register first.
				Validation::redirectLogin();
			}

			if ($view) {
				if (HookRegistry::call('CatalogBookHandler::view', array(&$this, &$submission, &$publicationFormat, &$submissionFile))) {
					// If the plugin handled the hook, prevent further default activity.
					exit();
				}
			}

			// Inline viewer not available, or viewing not wanted.
			// Download or show the file.
			$inline = $request->getUserVar('inline')?true:false;
			if (HookRegistry::call('CatalogBookHandler::download', array(&$this, &$submission, &$publicationFormat, &$submissionFile, &$inline))) {
				// If the plugin handled the hook, prevent further default activity.
				exit();
			}
			$returner = true;
			HookRegistry::call('FileManager::downloadFileFinished', array(&$returner));
			return Services::get('file')->download($path, $filename, $inline);
		}

		// Fall-through: user needs to pay for purchase.

		// Users that are not logged in need to register/login first.
		if (!$user) return $request->redirect(null, 'login', null, null, array('source' => $request->url(null, null, null, array($monographId, $representationId, $bestFileId))));

		// They're logged in but need to pay to view.
		import('classes.payment.omp.OMPPaymentManager');
		$paymentManager = new OMPPaymentManager($press);
		if (!$paymentManager->isConfigured()) {
			$request->redirect(null, 'catalog');
		}

		$queuedPayment = $paymentManager->createQueuedPayment(
			$request,
			PAYMENT_TYPE_PURCHASE_FILE,
			$user->getId(),
			$submissionFile->getId(),
			$submissionFile->getDirectSalesPrice(),
			$press->getData('currency')
		);
		$paymentManager->queuePayment($queuedPayment);

		$paymentForm = $paymentManager->getPaymentForm($queuedPayment);
		$paymentForm->display($request);
	}

	/**
	 * Set up common template variables.
	 * @param $request PKPRequest
	 * @param $submission Submission
	 */
	function setupTemplate($request, $submission = null) {
		$templateMgr = TemplateManager::getmanager($request);
		if ($seriesId = $submission->getSeriesId()) {
			$seriesDao = DAORegistry::getDAO('SeriesDAO'); /* @var $seriesDao SeriesDAO */
			$series = $seriesDao->getById($seriesId, $submission->getData('contextId'));
			$templateMgr->assign('series', $series);
		}

		parent::setupTemplate($request);
	}
}


