<?php

/**
 * @file classes/plugins/PubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PubIdPlugin
 * @ingroup plugins
 *
 * @brief Public identifiers plugins common functions
 */

namespace APP\plugins;

use PKP\plugins\PKPPubIdPlugin;
use PKP\core\JSONMessage;
use PKP\submission\PKPSubmission;

abstract class PubIdPlugin extends PKPPubIdPlugin {

	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		$user = $request->getUser();
		$router = $request->getRouter();
		$context = $router->getContext($request);

		$notificationManager = new NotificationManager();
		switch ($request->getUserVar('verb')) {
			case 'assignPubIds':
				if (!$request->checkCSRF()) return new JSONMessage(false);
				$suffixFieldName = $this->getSuffixFieldName();
				$suffixGenerationStrategy = $this->getSetting($context->getId(), $suffixFieldName);
				if ($suffixGenerationStrategy != 'customId') {
					$submissionEnabled = $this->isObjectTypeEnabled('Publication', $context->getId());
					$representationEnabled = $this->isObjectTypeEnabled('Representation', $context->getId());
					if ($submissionEnabled || $representationEnabled) {
						$publicationDao = DAORegistry::getDAO('PublicationDAO'); /* @var $publicationDao PublicationDAO */
						$representationDao = Application::getRepresentationDAO();
						$submissions = Services::get('submission')->getMany([
							'contextId' => $context->getId(),
							'status' => PKPSubmission::STATUS_PUBLISHED,
							'count' => 5000, // large upper limit
						]);
						foreach ($submissions as $submission) {
							$publications = $submission->getData('publications');
							if ($submissionEnabled) {
								foreach ($publications as $publication) {
									$publicationPubId = $publication->getStoredPubId($this->getPubIdType());
									if (empty($publicationPubId)) {
										$publicationPubId = $this->getPubId($publication);
										$publicationDao->changePubId($publication->getId(), $this->getPubIdType(), $publicationPubId);
									}
								}
							}
							if ($representationEnabled) {
								foreach ($publications as $publication) {
									$representations = $representationDao->getByPublicationId($publication->getId(), $context->getId());
									while ($representation = $representations->next()) {
										$representationPubId = $representation->getStoredPubId($this->getPubIdType());
										if (empty($representationPubId)) {
											$representationPubId = $this->getPubId($representation);
											$representationDao->changePubId($representation->getId(), $this->getPubIdType(), $representationPubId);
										}
									}
								}
							}
						}
					}
				}
				return new JSONMessage(true);
			default:
				return parent::manage($args, $request);
		}
	}

	//
	// Protected template methods from PKPPlubIdPlugin
	//

	/**
	 * @copydoc PKPPubIdPlugin::getPubId()
	 */
	function getPubId($pubObject) {
		// Get the pub id type
		$pubIdType = $this->getPubIdType();

		// If we already have an assigned pub id, use it.
		$storedPubId = $pubObject->getStoredPubId($pubIdType);
		if ($storedPubId) return $storedPubId;

		// Determine the type of the publishing object.
		$pubObjectType = $this->getPubObjectType($pubObject);

		// Initialize variables for publication objects.
		$submission = ($pubObjectType == 'Submission' ? $pubObject : null);
		$representation = ($pubObjectType == 'Representation' ? $pubObject : null);
		$submissionFile = ($pubObjectType == 'SubmissionFile' ? $pubObject : null);

		// Get the context id.
		if ($pubObjectType === 'Representation') {
			$publication = Services::get('publication')->get($pubObject->getData('publicationId'));
			$submission = Services::get('submission')->get($publication->getData('submissionId'));
			$contextId = $submission->getData('contextId');
		} elseif ($pubObjectType === 'Publication') {
			$submission = Services::get('submission')->get($pubObject->getData('submissionId'));
			$publication = Services::get('publication')->get($pubObject->getId());
			$contextId = $submission->getData('contextId');
		} elseif ($pubObjectType === 'SubmissionFile') {
			$submission = Services::get('submission')->get($pubObject->getData('submissionId'));
			$contextId = $submission->getData('contextId');
		}


		// Check the context
		$context = $this->getContext($contextId);
		if (!$context) return null;
		$contextId = $context->getId();

		// Check whether pub ids are enabled for the given object type.
		$objectTypeEnabled = $this->isObjectTypeEnabled($pubObjectType, $contextId);
		if (!$objectTypeEnabled) return null;

		// Retrieve the pub id prefix.
		$pubIdPrefix = $this->getSetting($contextId, $this->getPrefixFieldName());
		if (empty($pubIdPrefix)) return null;

		// Generate the pub id suffix.
		$suffixFieldName = $this->getSuffixFieldName();
		$suffixGenerationStrategy = $this->getSetting($contextId, $suffixFieldName);
		switch ($suffixGenerationStrategy) {
			case 'customId':
				$pubIdSuffix = $pubObject->getData($suffixFieldName);
				break;

			case 'pattern':
				$suffixPatternsFieldNames = $this->getSuffixPatternsFieldNames();
				$pubIdSuffix = $this->getSetting($contextId, $suffixPatternsFieldNames[$pubObjectType]);

				// %j - server initials, remove special characters and uncapitalize
				$pubIdSuffix = PKPString::regexp_replace('/%j/', PKPString::regexp_replace('/[^A-Za-z0-9]/', '', PKPString::strtolower($context->getAcronym($context->getPrimaryLocale()))), $pubIdSuffix);

				// %x - custom identifier
				if ($pubObject->getStoredPubId('publisher-id')) {
					$pubIdSuffix = PKPString::regexp_replace('/%x/', $pubObject->getStoredPubId('publisher-id'), $pubIdSuffix);
				}

				if ($submission) {
					// %a - preprint id
					$pubIdSuffix = PKPString::regexp_replace('/%a/', $submission->getId(), $pubIdSuffix);
				}

				if ($publication) {
					// %b - publication id
					$pubIdSuffix = PKPString::regexp_replace('/%b/', $publication->getId(), $pubIdSuffix);
				}

				if ($representation) {
					// %g - galley id
					$pubIdSuffix = PKPString::regexp_replace('/%g/', $representation->getId(), $pubIdSuffix);
				}

				if ($submissionFile) {
					// %f - file id
					$pubIdSuffix = PKPString::regexp_replace('/%f/', $submissionFile->getId(), $pubIdSuffix);
				}

				break;

			default:
				$pubIdSuffix = PKPString::regexp_replace('/[^A-Za-z0-9]/', '', PKPString::strtolower($context->getAcronym($context->getPrimaryLocale())));

				if ($submission) {
					$pubIdSuffix .= '.' . $submission->getId();
				}

				if ($representation) {
					$pubIdSuffix .= '.g' . $representation->getId();
				}

				if ($submissionFile) {
					$pubIdSuffix .= '.f' . $submissionFile->getId();
				}
		}
		if (empty($pubIdSuffix)) return null;

		// Costruct the pub id from prefix and suffix.
		$pubId = $this->constructPubId($pubIdPrefix, $pubIdSuffix, $contextId);

		return $pubId;
	}

	/**
	 * Version a publication pubId
	 */
	function versionPubId($pubObject) {
		$pubObjectType = $this->getPubObjectType($pubObject);
		$submission = Services::get('submission')->get($pubObject->getData('submissionId'));
		$publication = Services::get('publication')->get($pubObject->getId());
		$contextId = $submission->getData('contextId');

		// Check the context
		$context = $this->getContext($contextId);
		if (!$context) return null;
		$contextId = $context->getId();

		// Check whether pub ids are enabled for the given object type.
		$objectTypeEnabled = $this->isObjectTypeEnabled($pubObjectType, $contextId);
		if (!$objectTypeEnabled) return null;

		// Retrieve the pub id prefix.
		$pubIdPrefix = $this->getSetting($contextId, $this->getPrefixFieldName());
		if (empty($pubIdPrefix)) return null;

		// Retrieve the pub id suffix.
		$suffixPatternsFieldNames = $this->getSuffixPatternsFieldNames();

		$pubIdSuffix = $this->getSetting($contextId, $suffixPatternsFieldNames[$pubObjectType]);

		// %j - server initials
		$pubIdSuffix = PKPString::regexp_replace('/%j/', PKPString::regexp_replace('/[^A-Za-z0-9]/', '', PKPString::strtolower($context->getAcronym($context->getPrimaryLocale()))), $pubIdSuffix);

		// %x - custom identifier
		if ($pubObject->getStoredPubId('publisher-id')) {
			$pubIdSuffix = PKPString::regexp_replace('/%x/', $pubObject->getStoredPubId('publisher-id'), $pubIdSuffix);
		}

		// %a - preprint id
		if ($submission) {
			$pubIdSuffix = PKPString::regexp_replace('/%a/', $submission->getId(), $pubIdSuffix);
		}

		// %b - publication id
		if ($publication) {
			$pubIdSuffix = PKPString::regexp_replace('/%b/', $publication->getId(), $pubIdSuffix);
		}

		if (empty($pubIdSuffix)) return null;

		// Costruct the pub id from prefix and suffix.
		$pubId = $this->constructPubId($pubIdPrefix, $pubIdSuffix, $contextId);

		return $pubId;
	}

}


