<?php

/**
 * @file controllers/grid/preprintGalleys/PreprintGalleyGridCellProvider.php
 *
 * Copyright (c) 2016-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PreprintGalleyGridCellProvider
 * @ingroup controllers_grid_preprintGalleys
 *
 * @brief Base class for a cell provider for preprint galleys.
 */

namespace APP\controllers\grid\preprintGalleys;

use PKP\controllers\api\file\linkAction\DownloadFileLinkAction;
use APP\facades\Repo;
use PKP\controllers\grid\DataObjectGridCellProvider;
use PKP\controllers\grid\GridHandler;
use PKP\controllers\api\file\linkAction\DownloadFileLinkAction;

class PreprintGalleyGridCellProvider extends DataObjectGridCellProvider
{
    /** @var Submission */
    public $_submission;

    /** @var Publication */
    public $_publication;

    public $_isEditable;

    /**
     * Constructor
     *
     * @param Submission $submission
     */
    public function __construct($submission, $publication, $isEditable)
    {
        parent::__construct();
        $this->_submission = $submission;
        $this->_publication = $publication;
        $this->_isEditable = $isEditable;
    }

    //
    // Template methods from GridCellProvider
    //
    /**
     * @copydoc GridCellProvider::getTemplateVarsFromRowColumn()
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $element = $row->getData();
        $columnId = $column->getId();
        assert(is_a($element, 'DataObject') && !empty($columnId));

        switch ($columnId) {
            case 'label':
                return [
                    'label' => ($element->getRemoteUrl() == '' && $element->getData('submissionFileId')) ? '' : $element->getLabel()
                ];
                break;
            default: assert(false);
        }
        return parent::getTemplateVarsFromRowColumn($row, $column);
    }

    /**
     * Get request arguments.
     *
     * @param GridRow $row
     *
     * @return array
     */
    public function getRequestArgs($row)
    {
        return [
            'submissionId' => $this->_submission->getId(),
            'publicationId' => $this->_publication->getId(),
        ];
    }

    /**
     * @copydoc GridCellProvider::getCellActions()
     */
    public function getCellActions($request, $row, $column, $position = GridHandler::GRID_ACTION_POSITION_DEFAULT)
    {
        switch ($column->getId()) {
            case 'label':
                $element = $row->getData();
                if ($element->getRemoteUrl() != '' || !$element->getData('submissionFileId')) {
                    break;
                }

                $submissionFile = Repo::submissionFile()->get($element->getData('submissionFileId'));
                return [new DownloadFileLinkAction($request, $submissionFile, WORKFLOW_STAGE_ID_PRODUCTION, $element->getLabel())];
        }
        return parent::getCellActions($request, $row, $column, $position);
    }
}
