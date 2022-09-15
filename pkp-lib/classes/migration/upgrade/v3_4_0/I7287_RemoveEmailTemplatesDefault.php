<?php

/**
 * @file classes/migration/upgrade/v3_4_0/I7287_RemoveEmailTemplatesDefault.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class I7287_RemoveEmailTemplatesDefault
 * @brief Database migrations to remove email_templates_default template; use Mailable class calls to retrieve this data
 */

namespace PKP\migration\upgrade\v3_4_0;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use PKP\migration\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class I7287_RemoveEmailTemplatesDefault extends Migration
{
    protected Collection $emailTemplatesDefault;
    protected Collection $emailTemplateDefaultData;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->emailTemplatesDefault = DB::table('email_templates_default')->get();
        $this->emailTemplateDefaultData = DB::table('email_templates_default_data')->get();
        Schema::drop('email_templates_default');
        Schema::table('email_templates_default_data', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }

    /**
     * Revert the migrations
     */
    public function down(): void
    {
        // Recreate email_templates_default table
        Schema::create('email_templates_default', function (Blueprint $table) {
            $table->bigInteger('email_id')->autoIncrement();
            $table->string('email_key', 255)->comment('Unique identifier for this email.');
            $table->smallInteger('can_disable')->default(0);
            $table->smallInteger('can_edit')->default(0);
            $table->bigInteger('from_role_id')->nullable();
            $table->bigInteger('to_role_id')->nullable();
            $table->bigInteger('stage_id')->nullable();
            $table->index(['email_key'], 'email_templates_default_email_key');
        });
        DB::table('email_templates_default')->insert($this->emailTemplatesDefault->toArray());

        // Re-add description column to the email_templates_default_data table and populate with data
        Schema::table('email_templates_default_data', function (Blueprint $table) {
            $table->addColumn('text', 'description')->nullable();
        });
        $this->emailTemplateDefaultData->each(function(\stdClass $dataRow) {
            DB::table('email_templates_default_data')
                ->where('email_key', $dataRow->{'email_key'})
                ->where('locale', $dataRow->locale)
                ->update(['description' => $dataRow->description]);
        });
    }
}
