<?php

/**
 * @file classes/controllers/grid/GridColumn.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GridColumn
 * @ingroup controllers_grid
 *
 * @brief The GridColumn class represents a column within a grid. It is used to
 *  format the data presented in a particular column, which is provided by the
 *  GridRow implementation, and to handle user operations on that column (such
 *  as clicking a checkbox).
 *
 * For general information on grids, see GridHandler.
 */

namespace PKP\controllers\grid;

class GridColumn extends GridBodyElement
{
    public const COLUMN_ALIGNMENT_LEFT = 'left';
    public const COLUMN_ALIGNMENT_CENTER = 'center';
    public const COLUMN_ALIGNMENT_RIGHT = 'right';

    /** @var string the column title i18n key */
    public $_title;

    /** @var string the column title (translated) */
    public $_titleTranslated;

    /** @var string the controller template for the cells in this column */
    public $_template;

    /**
     * Constructor
     *
     * @param $id string Grid column identifier
     * @param $title string Locale key for grid column title
     * @param $titleTranslated string Optional translated grid title
     * @param $template string Optional template filename for grid column, including path
     * @param $cellProvider GridCellProvider Optional grid cell provider for this column
     * @param $flags array Optional set of flags for this grid column
     */
    public function __construct(
        $id = '',
        $title = null,
        $titleTranslated = null,
        $template = null,
        $cellProvider = null,
        $flags = []
    ) {

        // Use default template if none specified
        if ($template === null) {
            $template = 'controllers/grid/gridCell.tpl';
        }

        parent::__construct($id, $cellProvider, $flags);

        $this->_title = $title;
        $this->_titleTranslated = $titleTranslated;
        $this->_template = $template;
    }

    //
    // Setters/Getters
    //
    /**
     * Get the column title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Set the column title (already translated)
     *
     * @param $title string
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * Set the column title (already translated)
     */
    public function setTitleTranslated($titleTranslated)
    {
        $this->_titleTranslated = $titleTranslated;
    }

    /**
     * Get the translated column title
     *
     * @return string
     */
    public function getLocalizedTitle()
    {
        if ($this->_titleTranslated) {
            return $this->_titleTranslated;
        }
        return __($this->_title);
    }

    /**
     * get the column's cell template
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->_template;
    }

    /**
     * set the column's cell template
     *
     * @param $template string
     */
    public function setTemplate($template)
    {
        $this->_template = $template;
    }

    /**
     * @see GridBodyElement::getCellProvider()
     */
    public function getCellProvider()
    {
        if (is_null(parent::getCellProvider())) {
            // provide a sensible default cell provider
            $cellProvider = new ArrayGridCellProvider();
            $this->setCellProvider($cellProvider);
        }

        return parent::getCellProvider();
    }

    /**
     * Get cell actions for this column.
     *
     * NB: Subclasses have to override this method to
     * actually provide cell-specific actions. The default
     * implementation returns an empty array.
     *
     * @param $row GridRow The row for which actions are
     *  being requested.
     *
     * @return array An array of LinkActions for the cell.
     */
    public function getCellActions($request, $row, $position = GridHandler::GRID_ACTION_POSITION_DEFAULT)
    {
        // The default implementation returns an empty array
        return [];
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\controllers\grid\GridColumn', '\GridColumn');
    foreach ([
        'COLUMN_ALIGNMENT_LEFT',
        'COLUMN_ALIGNMENT_CENTER',
        'COLUMN_ALIGNMENT_RIGHT',
    ] as $constantName) {
        define($constantName, constant('\GridColumn::' . $constantName));
    }
}
