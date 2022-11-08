<?php

/**
 * @file classes/context/PKPSectionDAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPSectionDAO
 * @ingroup context
 *
 * @see PKPSection
 *
 * @brief Operations for retrieving and modifying Section objects.
 */

namespace PKP\context;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class PKPSectionDAO extends \PKP\db\DAO
{
    /**
     * Get the name of the section table in the database
     */
    abstract protected function _getTableName(): string;

    /**
     * Get the id column of the section table in the database
     */
    abstract protected function _getIdColumnName(): string;

    /**
     * Get the context_id column of the section table in the database
     */
    abstract protected function _getContextIdColumnName(): string;

    /**
     * Create a new data object.
     *
     * @return PKPSection
     */
    abstract public function newDataObject();

    /**
     * Retrieve a section by ID.
     *
     * @param int $sectionId
     * @param null|mixed $contextId
     *
     * @return Section
     */
    abstract public function getById($sectionId, $contextId = null);

    /**
     * Check if a section exists
     */
    public function exists(int $sectionId, ?int $contextId = null)
    {
        return DB::table($this->_getTableName())
            ->where($this->_getIdColumnName(), $sectionId)
            ->when($contextId !== null, fn (Builder $q) => $q->where($this->_getContextIdColumnName(), $contextId))
            ->exists();
    }

    /**
     * Generate a new PKPSection object from row.
     *
     * @param array $row
     *
     * @return PKPSection
     */
    public function _fromRow($row)
    {
        $section = $this->newDataObject();

        $section->setReviewFormId($row['review_form_id']);
        $section->setEditorRestricted($row['editor_restricted']);
        $section->setSequence($row['seq']);

        return $section;
    }

    /**
     * Get the list of fields for which data can be localized.
     *
     * @return array
     */
    public function getLocaleFieldNames()
    {
        return array_merge(parent::getLocaleFieldNames(), ['title', 'policy']);
    }

    /**
     * Delete a section.
     *
     * @param Section $section
     */
    public function deleteObject($section)
    {
        return $this->deleteById($section->getId(), $section->getContextId());
    }

    /**
     * Delete a section by ID.
     *
     * @param int $sectionId
     * @param null|mixed $contextId
     */
    abstract public function deleteById($sectionId, $contextId = null);

    /**
     * Delete sections by context ID
     * NOTE: This does not necessarily delete dependent entries.
     *
     * @param int $contextId
     */
    public function deleteByContextId($contextId)
    {
        $sections = $this->getByContextId($contextId);
        while ($section = $sections->next()) {
            $this->deleteObject($section);
        }
    }

    /**
     * Retrieve all sections for a context.
     *
     * @param int $contextId context ID
     * @param DBResultRange $rangeInfo optional
     * @param bool $submittableOnly optional. Whether to return only sections
     *  that can be submitted to by anyone.
     *
     * @return DAOResultFactory containing Sections ordered by sequence
     */
    abstract public function getByContextId($contextId, $rangeInfo = null, $submittableOnly = false);

    /**
     * Retrieve the IDs and titles of the sections for a context in an associative array.
     *
     * @param int $contextId context ID
     * @param bool $submittableOnly optional. Whether to return only sections
     *  that can be submitted to by anyone.
     *
     * @return array
     */
    public function getTitlesByContextId($contextId, $submittableOnly = false)
    {
        $sections = [];
        $sectionsIterator = $this->getByContextId($contextId, null, $submittableOnly);
        while ($section = $sectionsIterator->next()) {
            $sections[$section->getId()] = $section->getLocalizedTitle();
        }
        return $sections;
    }

    /**
     * Check if a section is inactive
     */
    public function isInactive(int $sectionId): bool
    {
        $row = DB::table($this->_getTableName())
            ->where($this->_getIdColumnName(), '=', $sectionId)
            ->get('is_inactive')
            ->first();

        return $row && $row->is_inactive > 0;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\context\PKPSectionDAO', '\PKPSectionDAO');
}
