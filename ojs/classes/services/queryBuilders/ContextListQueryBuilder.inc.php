<?php
/**
 * @file classes/services/QueryBuilders/ContextListQueryBuilder.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContextListQueryBuilder
 * @ingroup query_builders
 *
 * @brief Journal list query builder
 */
namespace OJS\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;

class ContextListQueryBuilder extends \PKP\Services\QueryBuilders\PKPContextListQueryBuilder {
	/** @var string The database name for this context: `journals` or `presses` */
	protected $db = 'journals';

	/** @var string The database name for this context's settings: `journal_setttings` or `press_settings` */
	protected $dbSettings = 'journal_settings';

	/** @var string The column name for a context ID: `journal_id` or `press_id` */
	protected $dbIdColumn = 'journal_id';
}
