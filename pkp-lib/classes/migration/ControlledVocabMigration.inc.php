<?php

/**
 * @file classes/migration/ControlledVocabMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocabMigration
 * @brief Describe database table structures.
 */

namespace PKP\migration;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ControlledVocabMigration extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Controlled vocabularies
        Schema::create('controlled_vocabs', function (Blueprint $table) {
            $table->bigInteger('controlled_vocab_id')->autoIncrement();
            $table->string('symbolic', 64);
            $table->bigInteger('assoc_type')->default(0);
            $table->bigInteger('assoc_id')->default(0);
            $table->unique(['symbolic', 'assoc_type', 'assoc_id'], 'controlled_vocab_symbolic');
        });

        // Controlled vocabulary entries
        Schema::create('controlled_vocab_entries', function (Blueprint $table) {
            $table->bigInteger('controlled_vocab_entry_id')->autoIncrement();
            $table->bigInteger('controlled_vocab_id');
            $table->float('seq', 8, 2)->nullable();
            $table->index(['controlled_vocab_id', 'seq'], 'controlled_vocab_entries_cv_id');
        });

        // Controlled vocabulary entry settings
        Schema::create('controlled_vocab_entry_settings', function (Blueprint $table) {
            $table->bigInteger('controlled_vocab_entry_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 6);
            $table->index(['controlled_vocab_entry_id'], 'c_v_e_s_entry_id');
            $table->unique(['controlled_vocab_entry_id', 'locale', 'setting_name'], 'c_v_e_s_pkey');
        });

        // Reviewer Interests Associative Table
        Schema::create('user_interests', function (Blueprint $table) {
            $table->bigInteger('user_id');
            $table->bigInteger('controlled_vocab_entry_id');
            $table->unique(['user_id', 'controlled_vocab_entry_id'], 'u_e_pkey');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down()
    {
        Schema::drop('user_interests');
        Schema::drop('controlled_vocab_entry_settings');
        Schema::drop('controlled_vocab_entries');
        Schema::drop('controlled_vocabs');
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\migration\ControlledVocabMigration', '\ControlledVocabMigration');
}
