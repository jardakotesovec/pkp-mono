<?php
/**
 * @file classes/services/StatsEditorialService.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPStatsEditorialService
 *
 * @ingroup services
 *
 * @brief Helper class that encapsulates business logic for getting
 *   editorial stats
 */

namespace APP\services;

use PKP\decision\Decision;
use PKP\plugins\Hook;

class StatsEditorialService extends \PKP\services\PKPStatsEditorialService
{
    /**
     * Get overview of key editorial stats
     *
     * @copydoc PKPStatsEditorialService::getOverview()
     */
    public function getOverview($args = [])
    {
        $overview = [
            [
                'key' => 'submissionsReceived',
                'name' => 'stats.name.submissionsReceived',
                'value' => $this->countSubmissionsReceived($args),
            ],
            [
                'key' => 'submissionsDeclined',
                'name' => 'stats.name.submissionsDeclined',
                'value' => $this->countByDecisions(Decision::INITIAL_DECLINE, $args),
            ],
            [
                'key' => 'submissionsPublished',
                'name' => 'stats.name.submissionsPublished',
                'value' => $this->countSubmissionsPublished($args),
            ],
            [
                'key' => 'submissionsSkipped',
                'name' => 'stats.name.submissionsSkipped',
                'value' => $this->countSubmissionsSkipped($args),
            ],
        ];

        Hook::call('EditorialStats::overview', [&$overview, $args]);

        return $overview;
    }


    /**
     * Process the sectionIds param when getting the query builder
     *
     * @param array $args
     */
    protected function getQueryBuilder($args = [])
    {
        $statsQB = parent::getQueryBuilder($args);
        if (!empty(($args['sectionIds']))) {
            $statsQB->filterBySections($args['sectionIds']);
        }
        return $statsQB;
    }
}
