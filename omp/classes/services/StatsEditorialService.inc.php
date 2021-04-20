<?php
/**
 * @file classes/services/StatsEditorialService.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPStatsEditorialService
 * @ingroup services
 *
 * @brief Helper class that encapsulates business logic for getting
 *   editorial stats
 */

namespace APP\Services;

class StatsEditorialService extends \PKP\Services\PKPStatsEditorialService
{
    /**
     * Process the seriesIds param when getting the query builder
     *
     * @param array $args
     */
    protected function _getQueryBuilder($args = [])
    {
        $statsQB = parent::_getQueryBuilder($args);
        if (!empty(($args['seriesIds']))) {
            $statsQB->filterBySections($args['seriesIds']);
        }
        return $statsQB;
    }
}
