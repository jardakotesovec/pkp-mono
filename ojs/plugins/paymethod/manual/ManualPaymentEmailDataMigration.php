<?php

/**
 * @file ManualPaymentEmailDataMigration.php
 *
 * Copyright (c) 2014-2023 Simon Fraser University
 * Copyright (c) 2000-2023 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ManualPaymentEmailDataMigration
 * @brief Migrations for the plugin's email templates
 */

namespace APP\plugins\paymethod\manual;

use Exception;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use PKP\db\XMLDAO;
use PKP\facades\Locale;

class ManualPaymentEmailDataMigration extends Migration
{
    private ManualPaymentPlugin $plugin;

    public function __construct(ManualPaymentPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $xmlDao = new XMLDAO();

        $data = $xmlDao->parseStruct($this->plugin->getInstallEmailTemplatesFile(), ['email']);

        if (!isset($data['email'])) {
            throw new Exception('Unable to find <email> entries in ' . $this->plugin->getInstallEmailTemplatesFile());
        }

        $locales = json_decode(DB::table('site')->value('installed_locales'));

        foreach ($data['email'] as $entry) {
            $attrs = $entry['attributes'];
            $name = $attrs['name'] ?? null;
            $emailKey = $attrs['key'];

            if (!$name) {
                throw new Exception('Failed to install email template ' . $emailKey . ' due to missing name');
            }

            $previous = Locale::getMissingKeyHandler();
            Locale::setMissingKeyHandler(fn (string $key): string => '');

            foreach ($locales as $locale) {
                $translatedName = $name ? __($name, [], $locale) : $attrs['key'];
                DB::table('email_templates_default_data')
                    ->where('email_key', $emailKey)
                    ->where('locale', $locale)
                    ->update(['name' => $translatedName]);
            }

            Locale::setMissingKeyHandler($previous);
        }
    }

    /**
     * Revers the migrations
     */
    public function down(): void
    {
        $xmlDao = new XMLDAO();

        $data = $xmlDao->parseStruct($this->plugin->getInstallEmailTemplatesFile(), ['email']);

        if (!isset($data['email'])) {
            return;
        }

        foreach ($data['email'] as $entry) {
            $attrs = $entry['attributes'];
            $emailKey = $attrs['key'];

            DB::table('email_templates_default_data')
                ->where('email_key', $emailKey)
                ->update(['name' => '']);
        }
    }
}
