<?php

/**
 * @file classes/migration/upgrade/OMPv3_3_0UpgradeMigration.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionsMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class OMPv3_3_0UpgradeMigration extends Migration {
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up() {
		Capsule::schema()->table('press_settings', function (Blueprint $table) {
			// pkp/pkp-lib#6096 DB field type TEXT is cutting off long content
			$table->mediumText('setting_value')->nullable()->change();
		});
		Capsule::schema()->table('series', function (Blueprint $table) {
			$table->smallInteger('is_inactive')->default(0);
		});
	}

	/**
	 * Reverse the downgrades
	 * @return void
	 */
	public function down() {
		Capsule::schema()->table('press_settings', function (Blueprint $table) {
			// pkp/pkp-lib#6096 DB field type TEXT is cutting off long content
			$table->text('setting_value')->nullable()->change();
		});
	}
}
