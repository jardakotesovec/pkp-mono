<?php

/**
 * @file classes/services/QueryBuilders/StatsEditorialQueryBuilder.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class StatsEditorialQueryBuilder
 *
 * @ingroup query_builders
 *
 * @brief Editorial statistics list query builder
 */

namespace APP\services\queryBuilders;

use PKP\services\queryBuilders\PKPStatsEditorialQueryBuilder;

class StatsEditorialQueryBuilder extends PKPStatsEditorialQueryBuilder
{
    /** @var string The table column name for section IDs */
    public $sectionIdsColumn = 'series_id';
}
