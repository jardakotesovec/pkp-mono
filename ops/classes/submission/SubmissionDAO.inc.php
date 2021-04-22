<?php

/**
 * @file classes/submission/SubmissionDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionDAO
 * @ingroup submission
 *
 * @see Submission
 *
 * @brief Operations for retrieving and modifying Preprint objects.
 */

namespace APP\submission;

use \PKP\identity\Identity;
use \PKP\submission\PKPSubmissionDAO;
use \PKP\db\DAORegistry;

use \APP\submission\Submission;
use \APP\core\Services;

class SubmissionDAO extends PKPSubmissionDAO
{
    /**
     * Return a new data object.
     *
     * @return Submission
     */
    public function newDataObject()
    {
        return new Submission();
    }

    /**
     * @copydoc SchemaDAO::deleteById
     */
    public function deleteById($submissionId)
    {
        $publicationIds = Services::get('publication')->getIds(['submissionIds' => $submissionId]);
        $preprintGalleyDao = DAORegistry::getDAO('PreprintGalleyDAO'); /* @var $preprintGalleyDao PreprintGalleyDAO */

        foreach ($publicationIds as $publicationId) {
            $galleys = $preprintGalleyDao->getByPublicationId($publicationId)->toArray();
            foreach ($galleys as $galley) {
                $preprintGalleyDao->deleteById($galley->getId());
            }
        }

        $preprintSearchDao = DAORegistry::getDAO('PreprintSearchDAO'); /* @var $preprintSearchDao PreprintSearchDAO */
        $preprintSearchDao->deleteSubmissionKeywords($submissionId);

        $preprintSearchIndex = Application::getSubmissionSearchIndex();
        $preprintSearchIndex->preprintDeleted($submissionId);
        $preprintSearchIndex->submissionChangesFinished();

        parent::deleteById($submissionId);

        $this->flushCache();
    }

    /**
     * Change the status of the preprint
     *
     * @param $preprintId int
     * @param $status int
     */
    public function changeStatus($preprintId, $status)
    {
        $this->update(
            'UPDATE submissions SET status = ? WHERE submission_id = ?',
            [(int) $status, (int) $preprintId]
        );

        $this->flushCache();
    }

    /**
     * Removes preprints from a section by section ID
     *
     * @param $sectionId int
     */
    public function removeSubmissionsFromSection($sectionId)
    {
        $this->update(
            'DELETE FROM publication_settings WHERE setting_name = \'sectionId\' AND setting_value = ?',
            [(int) $sectionId]
        );

        $this->flushCache();
    }

    /**
     * Get all published submissions (eventually with a pubId assigned and) matching the specified settings.
     *
     * @param $contextId integer optional
     * @param $pubIdType string
     * @param $title string optional
     * @param $author string optional
     * @param $pubIdSettingName string optional
     * (e.g. crossref::status or crossref::registeredDoi)
     * @param $pubIdSettingValue string optional
     * @param $rangeInfo DBResultRange optional
     *
     * @return DAOResultFactory
     */
    public function getExportable($contextId, $pubIdType = null, $title = null, $author = null, $pubIdSettingName = null, $pubIdSettingValue = null, $rangeInfo = null)
    {
        $params = [];
        if ($pubIdSettingName) {
            $params[] = $pubIdSettingName;
        }
        $params[] = STATUS_PUBLISHED;
        $params[] = $contextId;
        if ($pubIdType) {
            $params[] = 'pub-id::' . $pubIdType;
        }
        if ($title) {
            $params[] = 'title';
            $params[] = '%' . $title . '%';
        }
        if ($author) {
            $params[] = $author;
            $params[] = $author;
        }
        if ($pubIdSettingName && $pubIdSettingValue && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED) {
            $params[] = $pubIdSettingValue;
        }

        $result = $this->retrieveRange(
            $sql = 'SELECT	s.*
			FROM	submissions s
				LEFT JOIN publications p ON s.current_publication_id = p.publication_id
				LEFT JOIN publication_settings ps ON p.publication_id = ps.publication_id'
                . ($pubIdType != null ? ' LEFT JOIN publication_settings pspidt ON (p.publication_id = pspidt.publication_id)' : '')
                . ($title != null ? ' LEFT JOIN publication_settings pst ON (p.publication_id = pst.publication_id)' : '')
                . ($author != null ? ' LEFT JOIN authors au ON (p.publication_id = au.publication_id)
						LEFT JOIN author_settings asgs ON (asgs.author_id = au.author_id AND asgs.setting_name = \'' . Identity::IDENTITY_SETTING_GIVENNAME . '\')
						LEFT JOIN author_settings asfs ON (asfs.author_id = au.author_id AND asfs.setting_name = \'' . Identity::IDENTITY_SETTING_FAMILYNAME . '\')
					' : '')
                . ($pubIdSettingName != null ? ' LEFT JOIN submission_settings pss ON (s.submission_id = pss.submission_id AND pss.setting_name = ?)' : '')
            . ' WHERE	s.status = ?
				AND s.context_id = ?'
                . ($pubIdType != null ? ' AND pspidt.setting_name = ? AND pspidt.setting_value IS NOT NULL' : '')
                . ($title != null ? ' AND (pst.setting_name = ? AND pst.setting_value LIKE ?)' : '')
                . ($author != null ? ' AND (asgs.setting_value LIKE ? OR asfs.setting_value LIKE ?)' : '')
                . (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue == EXPORT_STATUS_NOT_DEPOSITED) ? ' AND pss.setting_value IS NULL' : '')
                . (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED) ? ' AND pss.setting_value = ?' : '')
                . (($pubIdSettingName != null && is_null($pubIdSettingValue)) ? ' AND (pss.setting_value IS NULL OR pss.setting_value = \'\')' : '')
            . ' GROUP BY s.submission_id
			ORDER BY MAX(p.date_published) DESC, s.submission_id DESC',
            $params,
            $rangeInfo
        );

        return new DAOResultFactory($result, $this, '_fromRow', [], $sql, $params, $rangeInfo); // Counted via paging in CrossRef export
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\submission\SubmissionDAO', '\SubmissionDAO');
}
