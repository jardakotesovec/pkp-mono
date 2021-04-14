<?php

/**
 * @file classes/migration/upgrade/3_4_0/I4904_UsageStatsTemporaryRecords.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I4904_UsageStatsTemporaryRecords
 * @brief Describe upgrade/downgrade operations for DB table usage_stats_temporary_records.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class I4904_UsageStatsTemporaryRecords extends Migration {
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up() {
		// pkp/pkp-lib#4904: additional column in the table usage_stats_temporary_records
		if (Schema::hasTable('usage_stats_temporary_records') && !Schema::hasColumn('usage_stats_temporary_records', 'representation_id')) {
			Schema::table('usage_stats_temporary_records', function(Blueprint $table) {
				$table->bigInteger('representation_id')->nullable()->default(NULL);
			});
		}
	}

	/**
	 * Reverse the downgrades
	 * @return void
	 */
	public function down() {
	}
}
