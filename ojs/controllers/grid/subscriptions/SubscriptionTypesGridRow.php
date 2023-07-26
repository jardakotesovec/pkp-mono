<?php

/**
 * @file controllers/grid/subscriptions/SubscriptionTypesGridRow.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionTypesGridRow
 *
 * @ingroup controllers_grid_subscriptions
 *
 * @brief User grid row definition
 */

namespace APP\controllers\grid\subscriptions;

use APP\subscription\SubscriptionType;
use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class SubscriptionTypesGridRow extends GridRow
{
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
        parent::initialize($request, $template);

        // Is this a new row or an existing row?
        $element = & $this->getData();
        assert($element instanceof SubscriptionType);

        $rowId = $this->getId();
        if (!empty($rowId) && is_numeric($rowId)) {
            // Only add row actions if this is an existing row
            $router = $request->getRouter();
            $actionArgs = [
                'gridId' => $this->getGridId(),
                'rowId' => $rowId
            ];

            $actionArgs = array_merge($actionArgs, $this->getRequestArgs());

            $this->addAction(
                new LinkAction(
                    'edit',
                    new AjaxModal(
                        $router->url($request, null, null, 'editSubscriptionType', null, $actionArgs),
                        __('manager.subscriptionTypes.edit'),
                        'modal_edit',
                        true
                    ),
                    __('common.edit'),
                    'edit'
                )
            );
            $this->addAction(
                new LinkAction(
                    'delete',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('manager.subscriptionTypes.confirmDelete'),
                        __('common.delete'),
                        $router->url($request, null, null, 'deleteSubscriptionType', null, $actionArgs),
                        'modal_delete'
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }
}
