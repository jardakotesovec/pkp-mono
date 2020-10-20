<?php

/**
 * @file pages/stats/StatsHandler.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsHandler
 * @ingroup pages_stats
 *
 * @brief Handle requests for statistics pages.
 */

import('lib.pkp.pages.stats.PKPStatsHandler');

class StatsHandler extends PKPStatsHandler {
	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		HookRegistry::register ('TemplateManager::display', array($this, 'addSectionFilters'));
		HookRegistry::register ('TemplateManager::display', array($this, 'removeEditorialStatsChartView'));
	}

	/**
	 * Add OPS-specific configuration options to the stats component data
	 *
	 * Fired when the `TemplateManager::display` hook is called.
	 *
	 * @param string $hookname
	 * @param array $args [$templateMgr, $template, $sendContentType, $charset, $output]
	 */
	public function addSectionFilters($hookName, $args) {
		$templateMgr = $args[0];
		$template = $args[1];

		if (!in_array($template, ['stats/publications.tpl', 'stats/editorial.tpl'])) {
			return;
		}

		$sectionFilters = APP\components\listPanels\SubmissionsListPanel::getSectionFilters();

		if (count($sectionFilters) < 2) {
			return;
		}

		$filters = $templateMgr->getState('filters');
		if (is_null($filters)) {
			$filters = [];
		}
		$filters[] = [
			'heading' => __('section.sections'),
			'filters' => $sectionFilters,
		];
		$templateMgr->setState([
			'filters' => $filters
		]);
	}

	/**
	 * Remove pie chart from editorial statistics in OPS
	 *
	 * Fired when the `TemplateManager::display` hook is called.
	 *
	 * @param string $hookname
	 * @param array $args [$templateMgr, $template, $sendContentType, $charset, $output]
	 */
	public function removeEditorialStatsChartView($hookName, $args) {
		$templateMgr = $args[0];
		$template = $args[1];

		if ($template == 'stats/editorial.tpl') {
			$templateMgr->setState([
				'activeByStage' => null
			]);
		}
	}

}
