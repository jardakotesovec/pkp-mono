<?php
/**
 * @file classes/services/PublicationFormatService.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublicationFormatService
 *
 * @ingroup services
 *
 * @brief A service class with methods to handle publication formats
 */

namespace APP\services;

use APP\core\Application;
use APP\facades\Repo;
use APP\log\SubmissionEventLogEntry;
use APP\press\Press;
use APP\publicationFormat\IdentificationCodeDAO;
use APP\publicationFormat\MarketDAO;
use APP\publicationFormat\PublicationDateDAO;
use APP\publicationFormat\PublicationFormat;
use APP\publicationFormat\SalesRightsDAO;
use APP\submission\Submission;
use PKP\db\DAORegistry;
use PKP\log\SubmissionLog;

class PublicationFormatService
{
    /**
     * Delete a publication format
     *
     * @param PublicationFormat $publicationFormat
     * @param Submission $submission
     * @param Press $context
     */
    public function deleteFormat($publicationFormat, $submission, $context)
    {
        Application::getRepresentationDAO()->deleteById($publicationFormat->getId());

        // Delete publication format metadata
        $metadataDaos = ['IdentificationCodeDAO', 'MarketDAO', 'PublicationDateDAO', 'SalesRightsDAO'];
        foreach ($metadataDaos as $metadataDao) {
            /** @var IdentificationCodeDAO|MarketDAO|PublicationDateDAO|SalesRightsDAO */
            $dao = DAORegistry::getDAO($metadataDao);
            $result = $dao->getByPublicationFormatId($publicationFormat->getId());
            while (!$result->eof()) {
                $object = $result->next();
                $dao->deleteObject($object);
            }
        }

        $submissionFiles = Repo::submissionFile()
            ->getCollector()
            ->filterBySubmissionIds([$submission->getId()])
            ->filterByAssoc(
                Application::ASSOC_TYPE_REPRESENTATION,
                [$publicationFormat->getId()]
            )
            ->getMany();

        // Delete submission files for this publication format
        foreach ($submissionFiles as $submissionFile) {
            Repo::submissionFile()->delete($submissionFile);
        }

        // Log the deletion of the format.
        SubmissionLog::logEvent(Application::get()->getRequest(), $submission, SubmissionEventLogEntry::SUBMISSION_LOG_PUBLICATION_FORMAT_REMOVE, 'submission.event.publicationFormatRemoved', ['formatName' => $publicationFormat->getLocalizedName()]);
    }
}
