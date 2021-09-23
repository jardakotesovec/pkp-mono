<?php

/**
 * @file classes/migration/install/TemporaryFilesMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class TemporaryFilesMigration
 * @brief Describe database table structures.
 */

namespace PKP\migration\install;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TemporaryFilesMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Temporary file storage
        Schema::create('temporary_files', function (Blueprint $table) {
            $table->bigInteger('file_id')->autoIncrement();
            $table->bigInteger('user_id');
            $table->string('file_name', 90);
            $table->string('file_type', 255)->nullable();
            $table->bigInteger('file_size');
            $table->string('original_file_name', 127)->nullable();
            $table->datetime('date_uploaded');
            $table->index(['user_id'], 'temporary_files_user_id');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down()
    {
        Schema::drop('temporary_files');
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\migration\TemporaryFilesMigration', '\TemporaryFilesMigration');
}
