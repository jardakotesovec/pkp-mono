<?php

/**
 * @file classes/migration/OPSMigration.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OPSMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class OPSMigration extends Migration {
        /**
         * Run the migrations.
         * @return void
         */
        public function up() {
		// Journals and basic journal settings.
		Capsule::schema()->create('journals', function (Blueprint $table) {
			$table->bigInteger('journal_id')->autoIncrement();
			$table->string('path', 32);
			$table->float('seq', 8, 2)->default(0)->comment('Used to order lists of journals');
			$table->string('primary_locale', 14);
			$table->tinyInteger('enabled')->default(1)->comment('Controls whether or not the journal is considered "live" and will appear on the website. (Note that disabled journals may still be accessible, but only if the user knows the URL.)');
			$table->unique(['path'], 'journals_path');
		});

		// Journal settings.
		Capsule::schema()->create('journal_settings', function (Blueprint $table) {
			$table->bigInteger('journal_id');
			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->text('setting_value')->nullable();
			$table->string('setting_type', 6)->nullable();
			$table->index(['journal_id'], 'journal_settings_journal_id');
			$table->unique(['journal_id', 'locale', 'setting_name'], 'journal_settings_pkey');
		});

		// Journal sections.
		Capsule::schema()->create('sections', function (Blueprint $table) {
			$table->bigInteger('section_id')->autoIncrement();
			$table->bigInteger('journal_id');
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
			$table->index(['journal_id'], 'sections_journal_id');
		});

		// Section-specific settings
		Capsule::schema()->create('section_settings', function (Blueprint $table) {
			$table->bigInteger('section_id');
			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->text('setting_value')->nullable();
			$table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
			$table->index(['section_id'], 'section_settings_section_id');
			$table->unique(['section_id', 'locale', 'setting_name'], 'section_settings_pkey');
		});

		// Archived, removed from TOC, unscheduled or unpublished journal articles.
		Capsule::schema()->create('submission_tombstones', function (Blueprint $table) {
			$table->bigInteger('tombstone_id')->autoIncrement();
			$table->bigInteger('submission_id');
			$table->datetime('date_deleted');
			$table->bigInteger('journal_id');
			$table->bigInteger('section_id');
			$table->string('set_spec', 255);
			$table->string('set_name', 255);
			$table->string('oai_identifier', 255);
			$table->index(['journal_id'], 'submission_tombstones_journal_id');
			$table->index(['submission_id'], 'submission_tombstones_submission_id');
		});

		// Publications
		Capsule::schema()->create('publications', function (Blueprint $table) {
			$table->bigInteger('publication_id')->autoIncrement();
			$table->bigInteger('access_status')->default(0)->nullable();
			$table->date('date_published')->nullable();
			$table->datetime('last_modified')->nullable();
			$table->string('locale', 14)->nullable();
			$table->bigInteger('primary_contact_id')->nullable();
			$table->bigInteger('section_id')->nullable();
			$table->bigInteger('submission_id');
			//  STATUS_QUEUED
			$table->tinyInteger('status')->default(1);
			$table->string('url_path', 64)->nullable();
			$table->bigInteger('version')->nullable();
			$table->index(['submission_id'], 'publications_submission_id');
			$table->index(['section_id'], 'publications_section_id');
			$table->index(['url_path'], 'publications_url_path');
		});

		// Publication galleys
		Capsule::schema()->create('publication_galleys', function (Blueprint $table) {
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
		Capsule::schema()->create('publication_galley_settings', function (Blueprint $table) {
			$table->bigInteger('galley_id');
			$table->string('locale', 14)->default('');
			$table->string('setting_name', 255);
			$table->text('setting_value')->nullable();
			$table->index(['galley_id'], 'publication_galley_settings_galley_id');
			$table->unique(['galley_id', 'locale', 'setting_name'], 'publication_galley_settings_pkey');
		});
		// Add partial index (DBMS-specific)
		switch (Capsule::connection()->getDriverName()) {
			case 'mysql': Capsule::connection()->unprepared('CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name(50), setting_value(150))'); break;
			case 'pgsql': Capsule::connection()->unprepared('CREATE INDEX publication_galley_settings_name_value ON publication_galley_settings (setting_name, setting_value)'); break;
		}
	}

	/**
	 * Reverse the migration.
	 * @return void
	 */
	public function down() {
		Capsule::schema()->drop('completed_payments');
		Capsule::schema()->drop('journals');
		Capsule::schema()->drop('journal_settings');
		Capsule::schema()->drop('sections');
		Capsule::schema()->drop('section_settings');
		Capsule::schema()->drop('submission_tombstones');
		Capsule::schema()->drop('publications');
		Capsule::schema()->drop('publication_galleys');
		Capsule::schema()->drop('publication_galley_settings');
	}
}
