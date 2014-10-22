<?php

/**
 * @file controllers/grid/LocaleGridRow.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LocaleGridRow
 * @ingroup controllers_grid_translator
 *
 * @brief Handle locale grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RedirectAction');

class LocaleGridRow extends GridRow {
	/** @var string JQuery selector for containing tab element */
	var $tabsSelector;

	/**
	 * Constructor
	 * @param $tabsSelector string Selector for containing tab element
	 */
	function LocaleGridRow($tabsSelector) {
		parent::GridRow();
		$this->tabsSelector = $tabsSelector;
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request) {
		parent::initialize($request);
		$router = $request->getRouter();

		// Create the "edit" action
		import('lib.pkp.classes.linkAction.request.AddTabAction');
		$this->addAction(
			new LinkAction(
				'edit',
				new AddTabAction(
					$this->tabsSelector,
					$router->url($request, null, null, 'edit', null, array(
						'locale' => $this->getId(),
						'tabsSelector' => $this->tabsSelector,
					)),
					__('plugins.generic.translator.locale', array('locale' => $this->getId()))
				),
				__('grid.action.edit'),
				'edit'
			)
		);

		// Create the "check" action
		/*if ($this->getId() != MASTER_LOCALE) $this->addAction(
			new LinkAction(
				'checkLocale',
				new AjaxModal(
					$router->url($request, null, null, 'checkLocale', null, array('locale' => $this->getId())),
					__('plugins.generic.translator.check'),
					'modal_edit',
					true),
				__('plugins.generic.translator.check'),
				'edit'
			)
		);*/

		// Create the "export" action
		$this->addAction(
			new LinkAction(
				'export',
				new RedirectAction(
					$router->url($request, null, null, 'exportLocale', null, array('locale' => $this->getId()))
				),
				__('common.export'),
				'download'
			)
		);
	}
}

?>
