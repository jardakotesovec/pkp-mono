<?php

/**
 * @file classes/controllers/grid/ArrayGridCellProvider.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArrayGridCellProvider
 * @ingroup controllers_grid
 *
 * @brief Base class for a cell provider that can retrieve labels from arrays
 */

import('lib.pkp.classes.controllers.grid.GridCellProvider');

class ArrayGridCellProvider extends GridCellProvider
{
    //
    // Template methods from GridCellProvider
    //
    /**
     * This implementation assumes a simple data element array that
     * has column ids as keys.
     *
     * @see GridCellProvider::getTemplateVarsFromRowColumn()
     *
     * @param $row GridRow
     * @param $column GridColumn
     *
     * @return array
     */
    public function getTemplateVarsFromRowColumn($row, $column)
    {
        $element = & $row->getData();
        $columnId = $column->getId();
        switch ($columnId) {
            case 'id':
                return ['label' => $row->getId()];
            default:
                assert(is_array($element) && in_array($columnId, array_keys($element)));
                return ['label' => $element[$columnId]];
        };
    }
}
