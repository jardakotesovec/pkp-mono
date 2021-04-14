<?php

/**
 * @file classes/migration FilesMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FilesMigration
 * @brief Create the files database table
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FilesMigration extends Migration {
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up() {
		// Create a new table to track files in file storage
		Schema::create('files', function (Blueprint $table) {
			$table->bigIncrements('file_id');
			$table->string('path', 255);
			$table->string('mimetype', 255);
		});
	}

	/**
	 * Reverse the migration.
	 * @return void
	 */
	public function down() {
		Schema::drop('files');
	}
}
