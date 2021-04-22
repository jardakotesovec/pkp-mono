<?php

/**
 * @file controllers/grid/catalogEntry/RepresentativesGridRow.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class RepresentativesGridRow
 * @ingroup controllers_grid_catalogEntry
 *
 * @brief Representatives grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class RepresentativesGridRow extends GridRow
{
    /** @var Monograph **/
    public $_monograph;

    /**
     * Constructor
     */
    public function __construct($monograph)
    {
        $this->_monograph = $monograph;
        parent::__construct();
    }

    //
    // Overridden methods from GridRow
    //
    /**
     * @copydoc GridRow::initialize()
     *
     * @param null|mixed $template
     */
    public function initialize($request, $template = null)
    {
        // Do the default initialization
        parent::initialize($request, $template);

        $monograph = $this->getMonograph();

        // Is this a new row or an existing row?
        $representative = $this->_data;
        if ($representative != null && is_numeric($representative->getId())) {
            $router = $request->getRouter();
            $actionArgs = array_merge(
                parent::getRequestArgs(),
                ['submissionId' => $monograph->getId(),
                    'representativeId' => $representative->getId()]
            );

            // Add row-level actions
            import('lib.pkp.classes.linkAction.request.AjaxModal');
            $this->addAction(
                new LinkAction(
                    'editRepresentative',
                    new AjaxModal(
                        $router->url($request, null, null, 'editRepresentative', null, $actionArgs),
                        __('grid.action.edit'),
                        'modal_edit'
                    ),
                    __('grid.action.edit'),
                    'edit'
                )
            );

            import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
            $this->addAction(
                new LinkAction(
                    'deleteRepresentative',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('common.confirmDelete'),
                        __('common.delete'),
                        $router->url($request, null, null, 'deleteRepresentative', null, $actionArgs),
                        'modal_delete'
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }

    /**
     * Get the monograph for this row (already authorized)
     *
     * @return Monograph
     */
    public function getMonograph()
    {
        return $this->_monograph;
    }
}
