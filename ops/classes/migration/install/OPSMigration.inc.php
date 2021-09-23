<?php

/**
 * @file classes/migration/install/OPSMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OPSMigration
 * @brief Describe database table structures.
 */

namespace APP\migration\install;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OPSMigration extends \PKP\migration\Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Servers and basic server settings.
        Schema::create('servers', function (Blueprint $table) {
            $table->bigInteger('server_id')->autoIncrement();
            $table->string('path', 32);
            $table->float('seq', 8, 2)->default(0)->comment('Used to order lists of servers');
            $table->string('primary_locale', 14);
            $table->tinyInteger('enabled')->default(1)->comment('Controls whether or not the server is considered "live" and will appear on the website. (Note that disabled servers may still be accessible, but only if the user knows the URL.)');
            $table->unique(['path'], 'servers_path');
        });

        // Server settings.
        Schema::create('server_settings', function (Blueprint $table) {
            $table->bigInteger('server_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 6)->nullable();
            $table->index(['server_id'], 'server_settings_server_id');
            $table->unique(['server_id', 'locale', 'setting_name'], 'server_settings_pkey');
        });

        // Server sections.
        Schema::create('sections', function (Blueprint $table) {
            $table->bigInteger('section_id')->autoIncrement();
            $table->bigInteger('server_id');
            $table->bigInteger('review_form_id')->nullable();
            $table->float('seq', 8, 2)->default(0);
            $table->tinyInteger('editor_restricted')->default(0);
            $table->tinyInteger('meta_indexed')->default(0);
            $table->tinyInteger('meta_reviewed')->default(1);
            $table->tinyInteger('abstracts_not_required')->default(0);
            $table->tinyInteger('hide_title')->default(0);
            $table->tinyInteger('hide_author')->default(0);
            $table->tinyInteger('is_inactive')->default(0);
            $table->bigInteger('abstract_word_count')->nullable();
            $table->index(['server_id'], 'sections_server_id');
        });

        // Section-specific settings
        Schema::create('section_settings', function (Blueprint $table) {
            $table->bigInteger('section_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
            $table->index(['section_id'], 'section_settings_section_id');
            $table->unique(['section_id', 'locale', 'setting_name'], 'section_settings_pkey');
        });

        // Publications
        Schema::create('publications', function (Blueprint $table) {
            $table->bigInteger('publication_id')->autoIncrement();
            $table->bigInteger('access_status')->default(0)->nullable();
            $table->date('date_published')->nullable();
            $table->datetime('last_modified')->nullable();
            $table->string('locale', 14)->nullable();
            $table->bigInteger('primary_contact_id')->nullable();
            $table->bigInteger('section_id')->nullable();
            $table->bigInteger('submission_id');
            $table->tinyInteger('status')->default(1); // PKPSubmission::STATUS_QUEUED
            $table->string('url_path', 64)->nullable();
            $table->bigInteger('version')->nullable();
            $table->index(['submission_id'], 'publications_submission_id');
            $table->index(['section_id'], 'publications_section_id');
            $table->index(['url_path'], 'publications_url_path');
        });

        // Publication galleys
        Schema::create('publication_galleys', function (Blueprint $table) {
            $table->bigInteger('galley_id')->autoIncrement();
            $table->string('locale', 14)->nullable();
            $table->bigInteger('publication_id');
            $table->string('label', 255)->nullable();
            $table->bigInteger('submission_file_id')->unsigned()->nullable();
            $table->float('seq', 8, 2)->default(0);
            $table->string('remote_url', 2047)->nullable();
            $table->tinyInteger('is_approved')->default(0);
            $table->string('url_path', 64)->nullable();
            $table->index(['publication_id'], 'publication_galleys_publication_id');
            $table->index(['url_path'], 'publication_galleys_url_path');
            $table->foreign('submission_file_id')->references('submission_file_id')->on('submission_files');
        });

        // Galley metadata.
        Schema::create('publication_galley_settings', function (Blueprint $table) {
            $table->bigInteger('galley_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->index(['galley_id'], 'publication_galley_settings_galley_id');
            $table->unique(['galley_id', 'locale', 'setting_name'], 'publication_galley_settings_pkey');
        });
        // Add partial index (DBMS-specific)
        switch (DB::getDriverName()) {
            case 'mysql': DB::unprepared('CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name(50), setting_value(150))'); break;
            case 'pgsql': DB::unprepared('CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name, setting_value)'); break;
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::drop('completed_payments');
        Schema::drop('servers');
        Schema::drop('server_settings');
        Schema::drop('sections');
        Schema::drop('section_settings');
        Schema::drop('publications');
        Schema::drop('publication_galleys');
        Schema::drop('publication_galley_settings');
    }
}
