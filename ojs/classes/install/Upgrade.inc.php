<?php

/**
 * @file classes/install/Upgrade.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Upgrade
 * @ingroup install
 *
 * @brief Perform system upgrade.
 */


import('lib.pkp.classes.install.Installer');

class Upgrade extends Installer {
	/**
	 * Constructor.
	 * @param $params array upgrade parameters
	 */
	function Upgrade($params, $installFile = 'upgrade.xml', $isPlugin = false) {
		parent::Installer($installFile, $params, $isPlugin);
	}


	/**
	 * Returns true iff this is an upgrade process.
	 * @return boolean
	 */
	function isUpgrade() {
		return true;
	}

	//
	// Upgrade actions
	//

	/**
	 * Rebuild the search index.
	 * @return boolean
	 */
	function rebuildSearchIndex() {
		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->rebuildIndex();
		return true;
	}

	/**
	 * For upgrade to 2.1.1: Designate original versions as review versions
	 * in all cases where review versions aren't designated. (#2144)
	 * @return boolean
	 */
	function designateReviewVersions() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		import('classes.submission.author.AuthorAction');

		$journals =& $journalDao->getJournals();
		while ($journal =& $journals->next()) {
			$articles =& $articleDao->getArticlesByJournalId($journal->getId());
			while ($article =& $articles->next()) {
				if (!$article->getReviewFileId() && $article->getSubmissionProgress() == 0) {
					$authorSubmission =& $authorSubmissionDao->getAuthorSubmission($article->getId());
					AuthorAction::designateReviewVersion($authorSubmission, true);
				}
				unset($article);
			}
			unset($journal);
		}
		return true;
	}

	/**
	 * For upgrade to 2.1.1: Migrate the RT settings from the rt_settings
	 * table to journal settings and drop the rt_settings table.
	 * @return boolean
	 */
	function migrateRtSettings() {
		$rtDao =& DAORegistry::getDAO('RTDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		// Bring in the comments constants.
		$commentDao =& DAORegistry::getDao('CommentDAO');

		$result =& $rtDao->retrieve('SELECT * FROM rt_settings');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$journalId = $row['journal_id'];
			$journal = $journalDao->getById($journalId);
			$rt = new JournalRT($journalId);
			$rt->setEnabled(true); // No toggle in prior OJS; assume true
			$rt->setVersion($row['version_id']);
			$rt->setAbstract(true); // No toggle in prior OJS; assume true
			$rt->setCaptureCite($row['capture_cite']);
			$rt->setViewMetadata($row['view_metadata']);
			$rt->setSupplementaryFiles($row['supplementary_files']);
			$rt->setPrinterFriendly($row['printer_friendly']);
			$rt->setDefineTerms($row['define_terms']);

			$journal->updateSetting('enableComments', $row['add_comment']?COMMENTS_AUTHENTICATED:COMMENTS_DISABLED);

			$rt->setEmailAuthor($row['email_author']);
			$rt->setEmailOthers($row['email_others']);
			$rtDao->updateJournalRT($rt);
			unset($rt);
			unset($journal);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		// Drop the table once all settings are migrated.
		$rtDao->update('DROP TABLE rt_settings');
		return true;
	}

	/**
	 * For upgrade to OJS 2.2.0: Migrate the currency settings so the
	 * currencies table can be dropped in favour of XML.
	 * @return boolean
	 */
	function correctCurrencies() {
		$currencyDao =& DAORegistry::getDAO('CurrencyDAO');
		$result =& $currencyDao->retrieve('SELECT st.type_id AS type_id, c.code_alpha AS code_alpha FROM subscription_types st LEFT JOIN currencies c ON (c.currency_id = st.currency_id)');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$currencyDao->update('UPDATE subscription_types SET currency_code_alpha = ? WHERE type_id = ?', array($row['code_alpha'], $row['type_id']));
			$result->MoveNext();
		}
		unset($result);
		return true;
	}

	/**
	 * For upgrade to 2.2.0: Migrate the issue label column and values to the new
	 * show volume, show number, etc. columns and values. Migrate the publication
	 * format settings for the journal to the new issue label format. (#2291)
	 * @return boolean
	 */
	function migrateIssueLabelAndSettings() {
		// First, migrate label_format values in issues table.
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$issueDao->update('UPDATE issues SET show_volume=0, show_number=0, show_year=0, show_title=1 WHERE label_format=4'); // ISSUE_LABEL_TITLE
		$issueDao->update('UPDATE issues SET show_volume=0, show_number=0, show_year=1, show_title=0 WHERE label_format=3'); // ISSUE_LABEL_YEAR
		$issueDao->update('UPDATE issues SET show_volume=1, show_number=0, show_year=1, show_title=0 WHERE label_format=2'); // ISSUE_LABEL_VOL_YEAR
		$issueDao->update('UPDATE issues SET show_volume=1, show_number=1, show_year=1, show_title=0 WHERE label_format=1'); // ISSUE_LABEL_NUM_VOL_YEAR

		// Drop the old label_format column once all values are migrated.
		$issueDao->update('ALTER TABLE issues DROP COLUMN label_format');

		// Migrate old publicationFormat journal setting to new journal settings.
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$result =& $journalDao->retrieve('SELECT j.journal_id AS journal_id, js.setting_value FROM journals j LEFT JOIN journal_settings js ON (js.journal_id = j.journal_id AND js.setting_name = ?)', 'publicationFormat');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$settings = array(
				'publicationFormatVolume' => false,
				'publicationFormatNumber' => false,
				'publicationFormatYear' => false,
				'publicationFormatTitle' => false
			);
			switch ($row['setting_value']) {
				case 4: // ISSUE_LABEL_TITLE
					$settings['publicationFormatTitle'] = true;
					break;
				case 3: // ISSUE_LABEL_YEAR
					$settings['publicationFormatYear'] = true;
					break;
 				case 2: // ISSUE_LABEL_VOL_YEAR
					$settings['publicationFormatVolume'] = true;
					$settings['publicationFormatYear'] = true;
					break;
				case 1: // ISSUE_LABEL_NUM_VOL_YEAR
				default:
					$settings['publicationFormatVolume'] = true;
					$settings['publicationFormatNumber'] = true;
					$settings['publicationFormatYear'] = true;
			}
			foreach ($settings as $name => $value) {
				$journalDao->update('INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?)', array($row['journal_id'], $name, $value?1:0, 'bool'));
			}
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		$journalDao->update('DELETE FROM journal_settings WHERE setting_name = ?', array('publicationFormat'));

		return true;
	}

	/**
	 * For upgrade to 2.2: Move primary_locale from journal settings into
	 * dedicated column.
	 * @return boolean
	 */
	function setJournalPrimaryLocales() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');

		$result =& $journalSettingsDao->retrieve('SELECT journal_id, setting_value FROM journal_settings WHERE setting_name = ?', array('primaryLocale'));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$journalDao->update('UPDATE journals SET primary_locale = ? WHERE journal_id = ?', array($row['setting_value'], $row['journal_id']));
			$result->MoveNext();
		}
		$journalDao->update('UPDATE journals SET primary_locale = ? WHERE primary_locale IS NULL OR primary_locale = ?', array(INSTALLER_DEFAULT_LOCALE, ''));
		$result->Close();
		return true;
	}

	/**
	 * For upgrade to 2.2: Install default settings for block plugins.
	 * @return boolean
	 */
	function installBlockPlugins() {
		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals();

		// Get journal IDs for insertion, including 0 for site-level
		$journalIds = array(0);
		while ($journal =& $journals->next()) {
			$journalIds[] = $journal->getId();
			unset($journal);
		}

		$pluginNames = array(
			'DevelopedByBlockPlugin',
			'HelpBlockPlugin',
			'UserBlockPlugin',
			'RoleBlockPlugin',
			'LanguageToggleBlockPlugin',
			'NavigationBlockPlugin',
			'FontSizeBlockPlugin',
			'InformationBlockPlugin'
		);
		foreach ($journalIds as $journalId) {
			$i = 0;
			foreach ($pluginNames as $pluginName) {
				$pluginSettingsDao->updateSetting($journalId, $pluginName, 'enabled', 'true', 'bool');
				$pluginSettingsDao->updateSetting($journalId, $pluginName, 'seq', $i++, 'int');
				$pluginSettingsDao->updateSetting($journalId, $pluginName, 'context', BLOCK_CONTEXT_RIGHT_SIDEBAR, 'int');
			}
		}

		return true;
	}

	/**
	 * Clear the data cache files (needed because of direct tinkering
	 * with settings tables)
	 * @return boolean
	 */
	function clearDataCache() {
		$cacheManager =& CacheManager::getManager();
		$cacheManager->flush(null, CACHE_TYPE_FILE);
		$cacheManager->flush(null, CACHE_TYPE_OBJECT);
		return true;
	}

	/**
	 * For 2.2 upgrade: add locale data to existing journal settings that
	 * were not previously localized.
	 * @return boolean
	 */
	function localizeJournalSettings() {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$settingNames = array(
			// Setup page 1
			'title' => 'title',
			'journalInitials' => 'initials', // Rename
			'journalAbbreviation' => 'abbreviation', // Rename
			'sponsorNote' => 'sponsorNote',
			'publisherNote' => 'publisherNote',
			'contributorNote' => 'contributorNote',
			'searchDescription' => 'searchDescription',
			'searchKeywords' => 'searchKeywords',
			'customHeaders' => 'customHeaders',
			// Setup page 2
			'focusScopeDesc' => 'focusScopeDesc',
			'reviewPolicy' => 'reviewPolicy',
			'reviewGuidelines' => 'reviewGuidelines',
			'privacyStatement' => 'privacyStatement',
			'customAboutItems' => 'customAboutItems',
			'lockssLicense' => 'lockssLicense',
			'clockssLicense' => 'clockssLicense',
			// Setup page 3
			'authorGuidelines' => 'authorGuidelines',
			'submissionChecklist' => 'submissionChecklist',
			'copyrightNotice' => 'copyrightNotice',
			'metaDisciplineExamples' => 'metaDisciplineExamples',
			'metaSubjectClassTitle' => 'metaSubjectClassTitle',
			'metaSubjectClassUrl' => 'metaSubjectClassUrl',
			'metaSubjectExamples' => 'metaSubjectExamples',
			'metaCoverageGeoExamples' => 'metaCoverageGeoExamples',
			'metaCoverageChronExamples' => 'metaCoverageChronExamples',
			'metaCoverageResearchSampleExamples' => 'metaCoverageResearchSampleExamples',
			'metaTypeExamples' => 'metaTypeExamples',
			'metaCitations' => 'metaCitations',
			// Setup page 4
			'pubFreqPolicy' => 'pubFreqPolicy',
			'copyeditInstructions' => 'copyeditInstructions',
			'layoutInstructions' => 'layoutInstructions',
			'proofInstructions' => 'proofInstructions',
			'openAccessPolicy' => 'openAccessPolicy',
			'announcementsIntroduction' => 'announcementsIntroduction',
			// Setup page 5
			'homeHeaderLogoImage' => 'homeHeaderLogoImage',
			'homeHeaderTitleType' => 'homeHeaderTitleType',
			'homeHeaderTitle' => 'homeHeaderTitle',
			'homeHeaderTitleImage' => 'homeHeaderTitleImage',
			'pageHeaderTitleType' => 'pageHeaderTitleType',
			'pageHeaderTitle' => 'pageHeaderTitle',
			'pageHeaderTitleImage' => 'pageHeaderTitleImage',
			'homepageImage' => 'homepageImage',
			'readerInformation' => 'readerInformation',
			'authorInformation' => 'authorInformation',
			'librarianInformation' => 'librarianInformation',
			'journalPageHeader' => 'journalPageHeader',
			'journalPageFooter' => 'journalPageFooter',
			'additionalHomeContent' => 'additionalHomeContent',
			'description' => 'description',
			'navItems' => 'navItems',
			// Subscription policies
			'subscriptionAdditionalInformation' => 'subscriptionAdditionalInformation',
			'delayedOpenAccessPolicy' => 'delayedOpenAccessPolicy',
			'authorSelfArchivePolicy' => 'authorSelfArchivePolicy'
		);

		foreach ($settingNames as $oldName => $newName) {
			$result =& $journalDao->retrieve('SELECT j.journal_id, j.primary_locale FROM journals j, journal_settings js WHERE j.journal_id = js.journal_id AND js.setting_name = ? AND (js.locale IS NULL OR js.locale = ?)', array($oldName, ''));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$journalSettingsDao->update('UPDATE journal_settings SET locale = ?, setting_name = ? WHERE journal_id = ? AND setting_name = ? AND (locale IS NULL OR locale = ?)', array($row['primary_locale'], $newName, $row['journal_id'], $oldName, ''));
				$result->MoveNext();
			}
			$result->Close();
			unset($result);
		}

		return true;
	}

	/**
	 * For 2.3 upgrade: add locale data to existing journal settings that
	 * were not previously localized.
	 * @return boolean
	 */
	function localizeMoreJournalSettings() {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$settingNames = array(
			// Setup page 1
			'contactTitle' => 'contactTitle',
			'contactAffiliation' => 'contactAffiliation',
			'contactMailingAddress' => 'contactMailingAddress'
		);

		foreach ($settingNames as $oldName => $newName) {
			$result =& $journalDao->retrieve('SELECT j.journal_id, j.primary_locale FROM journals j, journal_settings js WHERE j.journal_id = js.journal_id AND js.setting_name = ? AND (js.locale IS NULL OR js.locale = ?)', array($oldName, ''));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$journalSettingsDao->update('UPDATE journal_settings SET locale = ?, setting_name = ? WHERE journal_id = ? AND setting_name = ? AND (locale IS NULL OR locale = ?)', array($row['primary_locale'], $newName, $row['journal_id'], $oldName, ''));
				$result->MoveNext();
			}
			$result->Close();
			unset($result);
		}

		return true;
	}

	/**
	 * For 2.2 upgrade: Migrate the "publisher" setting from a serialized
	 * array into three localized settings: publisherUrl, publisherNote,
	 * and publisherInstitution.
	 * @return boolean
	 */
	function migratePublisher() {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');

		$result =& $journalSettingsDao->retrieve('SELECT j.primary_locale, s.setting_value, j.journal_id FROM journal_settings s, journals j WHERE s.journal_id = j.journal_id AND s.setting_name = ?', array('publisher'));
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$publisher = null;
			$publisher = @unserialize($row['setting_value']);

			foreach (array('note' => 'publisherNote', 'institution' => 'publisherInstitution', 'url' => 'publisherUrl') as $old => $new) {
				if (isset($publisher[$old])) $journalSettingsDao->update(
					'INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type, locale) VALUES (?, ?, ?, ?, ?)',
					array(
						$row['journal_id'],
						$new, $publisher[$old],
						'string',
						($new == 'publisherNote'?$row['primary_locale']:'')
					)
				);
			}

			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		$journalSettingsDao->update('DELETE FROM journal_settings WHERE setting_name = ?', 'publisher');

		return true;
	}

	/**
	 * For 2.2 upgrade: Set locales for galleys.
	 * @return boolean
	 */
	function setGalleyLocales() {
		$articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$result =& $journalDao->retrieve('SELECT g.galley_id, j.primary_locale FROM journals j, articles a, article_galleys g WHERE a.journal_id = j.journal_id AND g.article_id = a.article_id AND (g.locale IS NULL OR g.locale = ?)', '');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$articleGalleyDao->update('UPDATE article_galleys SET locale = ? WHERE galley_id = ?', array($row['primary_locale'], $row['galley_id']));
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return true;
	}

	/**
	 * For 2.2 upgrade: user_settings table has been renamed in order to
	 * apply the schema changes for localization. Migrate the settings from
	 * user_settings_old to user_settings now that the new schema has been
	 * applied.
	 */
	function migrateUserSettings() {
		$userSettingsDao =& DAORegistry::getDAO('UserSettingsDAO');

		$result =& $userSettingsDao->retrieve('SELECT u.user_id, u.setting_name, u.setting_value, u.setting_type, s.primary_locale FROM user_settings_old u, site s');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$userSettingsDao->update('INSERT INTO user_settings (user_id, setting_name, assoc_id, setting_value, setting_type, locale) VALUES (?, ?, ?, ?, ?, ?)', array($row['user_id'], $row['setting_name'], (int) $row['journal_id'], $row['setting_value'], $row['setting_type'], $row['primary_locale']));
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return true;
	}

	/**
	 * For 2.2 upgrade: index handling changed away from using the <KEY />
	 * syntax in schema descriptors in cases where AUTONUM columns were not
	 * used, in favour of specifically-named indexes using the <index ...>
	 * syntax. For this, all indexes (including potentially duplicated
	 * indexes from before) on OJS tables should be dropped prior to the new
	 * schema being applied.
	 * @return boolean
	 */
	function dropAllIndexes() {
		$siteDao =& DAORegistry::getDAO('SiteDAO');
		$dict = NewDataDictionary($siteDao->getDataSource());
		$dropIndexSql = array();

		// This is a list of tables that were used in 2.1.1 (i.e.
		// before the way indexes were used was changed). All indexes
		// from these tables will be dropped.
		$tables = array(
			'versions', 'site', 'site_settings', 'scheduled_tasks',
			'sessions', 'journal_settings',
			'plugin_settings', 'roles',
			'section_settings', 'section_editors', 'issue_settings',
			'custom_issue_orders', 'custom_section_orders',
			'article_settings', 'article_author_settings',
			'article_supp_file_settings', 'review_rounds',
			'article_html_galley_images',
			'email_templates_default_data', 'email_templates_data',
			'article_search_object_keywords',
			'oai_resumption_tokens', 'subscription_type_settings',
			'announcement_type_settings', 'announcement_settings'
		);

		// Assemble a list of indexes to be dropped
		foreach ($tables as $tableName) {
			$indexes = $dict->MetaIndexes($tableName);
			if (is_array($indexes)) foreach ($indexes as $indexName => $indexData) {
				$dropIndexSql = array_merge($dropIndexSql, $dict->DropIndexSQL($indexName, $tableName));
			}
		}

		// Execute the DROP INDEX statements.
		foreach ($dropIndexSql as $sql) {
			$siteDao->update($sql);
		}

		// Second run: Only return primary indexes. This is necessary
		// so that primary indexes can be dropped by MySQL.
		foreach ($tables as $tableName) {
			$indexes = $dict->MetaIndexes($tableName, true);
			if (!empty($indexes)) switch(Config::getVar('database', 'driver')) {
				case 'mysql':
					$siteDao->update("ALTER TABLE $tableName DROP PRIMARY KEY");
					break;
			}
		}


		return true;
	}

	/**
	 * The supportedLocales setting may be missing for journals; ensure
	 * that it is properly set.
	 */
	function ensureSupportedLocales() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$result =& $journalDao->retrieve(
			'SELECT	j.journal_id,
				j.primary_locale
			FROM	journals j
				LEFT JOIN journal_settings js ON (js.journal_id = j.journal_id AND js.setting_name = ?)
			WHERE	js.setting_name IS NULL',
			array('supportedLocales')
		);
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$journalSettingsDao->updateSetting(
				$row['journal_id'],
				'supportedLocales',
				array($row['primary_locale']),
				'object',
				false
			);
			$result->MoveNext();
		}
		$result->Close();
		unset($result);
		return true;
	}

	/**
	 * For 2.2.1 upgrade: Replace "payPerView" to "purchaseArticle" in settings.
	 * @return boolean
	 */
	function renamePayPerViewSettings() {
		$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		$settingNames = array(
			'payPerViewFeeEnabled' => 'purchaseArticleFeeEnabled',
			'payPerViewFee' => 'purchaseArticleFee',
			'payPerViewFeeName' => 'purchaseArticleFeeName',
			'payPerViewFeeDescription' => 'purchaseArticleFeeDescription'
		);

		foreach ($settingNames as $oldName => $newName) {
			$journalSettingsDao->update('UPDATE journal_settings SET setting_name = ? WHERE setting_name = ?', array($newName, $oldName));
		}

		return true;
	}

	/**
	 * For 2.3 upgrade: Separate out individual and institutional subscriptions.
	   Also pull apart single ip range string into multiple, shorter strings.
	 * @return boolean
	 */
	function separateSubscriptions() {
		import('classes.subscription.InstitutionalSubscription');
		$subscriptionDao =& DAORegistry::getDAO('SubscriptionDAO');

		// Retrieve all subscriptions from pre-2.3 subscriptions table
		$result =& $subscriptionDao->retrieve('SELECT so.*, st.institutional FROM subscriptions_old so LEFT JOIN subscription_types st ON (so.type_id = st.type_id)');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$subscriptionId = (int) $row['subscription_id'];
			$membership = $row['membership'] ? $row['membership'] : '';

			// Insert into new subscriptions table
			$subscriptionDao->update('INSERT INTO subscriptions (subscription_id, journal_id, user_id, type_id, date_start, date_end, status, membership, reference_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', array($subscriptionId, (int) $row['journal_id'], (int) $row['user_id'], (int) $row['type_id'], $row['date_start'], $row['date_end'], 1, $membership, '', ''));

			// If institutional subscription, also add records to institutional_subscriptions
			// and institutional_subscription_ip tables.
			// Since institution name did not exist pre-2.3, use membership string as default for institution name
			if ($row['institutional']) {
				$subscriptionDao->update('INSERT INTO institutional_subscriptions (subscription_id, institution_name, mailing_address, domain) VALUES (?, ?, ?, ?)', array($subscriptionId, $membership, '', $row['domain']));
				$ipRangeText = $row['ip_range'];

				// Break apart pre-2.3 single ip range string which contained all ip ranges into
				// multiple strings, each with exactly one ip range. See Bug #4117
				$ipRanges = explode(';', $ipRangeText);

				while (list(, $curIPString) = each($ipRanges)) {
					$ipStart = null;
					$ipEnd = null;

					// Parse and check single IP string
					if (strpos($curIPString, SUBSCRIPTION_IP_RANGE_RANGE) === false) {

						// Check for wildcards in IP
						if (strpos($curIPString, SUBSCRIPTION_IP_RANGE_WILDCARD) === false) {

							// Get non-CIDR IP
							if (strpos($curIPString, '/') === false) {
								$ipStart = sprintf("%u", ip2long(trim($curIPString)));

							// Convert CIDR IP to IP range
							} else {
								list($curIPString, $cidrBits) = explode('/', trim($curIPString));

								if ($cidrBits == 0) {
									$cidrMask = 0;
								} else {
									$cidrMask = (0xffffffff << (32 - $cidrBits));
								}

								$ipStart = sprintf('%u', ip2long($curIPString) & $cidrMask);

								if ($cidrBits != 32) {
									$ipEnd = sprintf('%u', ip2long($curIPString) | (~$cidrMask & 0xffffffff));
								}
							}

						// Convert wildcard IP to IP range
						} else {
							$ipStart = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($curIPString))));
							$ipEnd = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($curIPString))));
						}

					// Convert wildcard IP range to IP range
					} else {
						list($ipStart, $ipEnd) = explode(SUBSCRIPTION_IP_RANGE_RANGE, $curIPString);

						// Replace wildcards in start and end of range
						$ipStart = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '0', trim($ipStart))));
						$ipEnd = sprintf('%u', ip2long(str_replace(SUBSCRIPTION_IP_RANGE_WILDCARD, '255', trim($ipEnd))));
					}

					if ($ipStart != null) {
						$subscriptionDao->update('INSERT INTO institutional_subscription_ip (subscription_id, ip_string, ip_start, ip_end) VALUES(?, ?, ?, ?)', array($subscriptionId, $curIPString, $ipStart, $ipEnd));
					}
				}
			}

			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return true;
	}

	/**
	 * For 2.3 upgrade: Add clean titles for every article title so sorting by title ignores punctuation.
	 * @return boolean
	 */
	function cleanTitles() {
		$articleDao =& DAORegistry::getDAO('ArticleDAO');
		$punctuation = array ("\"", "\'", ",", ".", "!", "?", "-", "$", "(", ")");

		$result =& $articleDao->retrieve('SELECT article_id, locale, setting_value FROM article_settings WHERE setting_name = ?', "title");
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$cleanTitle = str_replace($punctuation, "", $row['setting_value']);
			$articleDao->update('INSERT INTO article_settings (article_id, locale, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?, ?)', array((int) $row['article_id'], $row['locale'], "cleanTitle", $cleanTitle, "string"));
			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		return true;
	}

	/**
	 * For 2.3 upgrade: Move image alts for Journal Setup Step 5 from within the image
	 * settings into their own settings. (Improves usability of page 5 setup form and
	 * simplifies the code considerably.)
	 * @return boolean
	 */
	function cleanImageAlts() {
		$imageSettings = array(
			'homeHeaderTitleImage' => 'homeHeaderTitleImageAltText',
			'homeHeaderLogoImage' => 'homeHeaderLogoImageAltText',
			'homepageImage' => 'homepageImageAltText',
			'pageHeaderTitleImage' => 'pageHeaderTitleImageAltText',
			'pageHeaderLogoImage' => 'pageHeaderLogoImageAltText'
		);
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournals();
		while ($journal =& $journals->next()) {
			foreach ($imageSettings as $imageSettingName => $newSettingName) {
				$imageSetting = $journal->getSetting($imageSettingName);
				$newSetting = array();
				if ($imageSetting) foreach ($imageSetting as $locale => $setting) {
					if (isset($setting['altText'])) $newSetting[$locale] = $setting['altText'];
				}
				if (!empty($newSetting)) {
					$journal->updateSetting($newSettingName, $newSetting, 'string', true);
				}
			}
			unset($journal);
		}
		return true;
	}

	/**
	 * For 2.3.3 upgrade:  Migrate reviewing interests from free text to controlled vocab structure
	 * @return boolean
	 */
	function migrateReviewingInterests() {
		$userDao =& DAORegistry::getDAO('UserDAO');
		$controlledVocabDao =& DAORegistry::getDAO('ControlledVocabDAO');

		$result =& $userDao->retrieve('SELECT setting_value as interests, user_id FROM user_settings WHERE setting_name = ?', 'interests');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			if(!empty($row['interests'])) {
				$userId = $row['user_id'];
				$interests = explode(',', $row['interests']);

				if (empty($interests)) $interests = array();
				elseif (!is_array($interests)) $interests = array($interests);

				$controlledVocabDao->update(
					sprintf('INSERT INTO controlled_vocabs (symbolic, assoc_type, assoc_id) VALUES (?, ?, ?)'),
					array('interest', ROLE_ID_REVIEWER, $userId)
				);
				$controlledVocabId = $controlledVocabDao->_getInsertId('controlled_vocabs', 'controlled_vocab_id');

				foreach($interests as $interest) {
					// Trim unnecessary whitespace
					$interest = trim($interest);

					$controlledVocabDao->update(
						sprintf('INSERT INTO controlled_vocab_entries (controlled_vocab_id) VALUES (?)'),
						array($controlledVocabId)
					);

					$controlledVocabEntryId = $controlledVocabDao->_getInsertId('controlled_vocab_entries', 'controlled_vocab_entry_id');

					$controlledVocabDao->update(
						sprintf('INSERT INTO controlled_vocab_entry_settings (controlled_vocab_entry_id, setting_name, setting_value, setting_type) VALUES (?, ?, ?, ?)'),
						array($controlledVocabEntryId, 'interest', $interest, 'string')
					);
				}

			}

			$result->MoveNext();
		}

		// Remove old interests from the user_setting table
		$userDao->update('DELETE FROM user_settings WHERE setting_name = ?', 'interests');

		return true;
	}

	/**
	 * For 2.4 Upgrade -- Overhaul notification structure
	 */
	function migrateNotifications() {
		$notificationDao =& DAORegistry::getDAO('NotificationDAO');

		// Retrieve all notifications from pre-2.4 notifications table
		$result =& $notificationDao->retrieve('SELECT * FROM notifications_old');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$type = $row['assoc_type'];
			$url = $row['location'];

			// Get the ID of the associated object from the URL and store in $matches.
			//  This value will not be set in all cases.
			preg_match_all('/\d+/', $url, $matches);

			// Set the base data for the notification
			$notification = $notificationDao->newDataObject();
			$notification->setId($row['notification_id']);
			$notification->setUserId($row['user_id']);
			$notification->setLevel(NOTIFICATION_LEVEL_NORMAL);
			$notification->setDateCreated($notificationDao->datetimeFromDB($row['date_created']));
			$notification->setDateRead($notificationDao->datetimeFromDB($row['date_read']));
			$notification->setContextId($row['context']);
			$notification->setType($type);

			switch($type) {
				case NOTIFICATION_TYPE_COPYEDIT_COMMENT:
				case NOTIFICATION_TYPE_PROOFREAD_COMMENT:
				case NOTIFICATION_TYPE_METADATA_MODIFIED:
				case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
				case NOTIFICATION_TYPE_LAYOUT_COMMENT:
				case NOTIFICATION_TYPE_ARTICLE_SUBMITTED:
				case NOTIFICATION_TYPE_SUPP_FILE_MODIFIED:
				case NOTIFICATION_TYPE_GALLEY_MODIFIED:
				case NOTIFICATION_TYPE_REVIEWER_COMMENT:
				case NOTIFICATION_TYPE_REVIEWER_FORM_COMMENT:
				case NOTIFICATION_TYPE_EDITOR_DECISION_COMMENT:
					$id = array_pop($matches[0]);
					$notification->setAssocType(ASSOC_TYPE_ARTICLE);
					$notification->setAssocId($id);
					break;
				case NOTIFICATION_TYPE_USER_COMMENT:
					// Remove the last two elements of the array.  They refer to the
					//  galley and parent, which we no longer use
					$matches = array_slice($matches[0], -3);
					$id = array_shift($matches);
					$notification->setAssocType(ASSOC_TYPE_ARTICLE);
					$notification->setAssocId($id);
					$notification->setType(NOTIFICATION_TYPE_USER_COMMENT);
					break;
				case NOTIFICATION_TYPE_PUBLISHED_ISSUE:
					// We do nothing here, as our URL points to the current issue
					break;
				case NOTIFICATION_TYPE_NEW_ANNOUNCEMENT:
					$id = array_pop($matches[0]);
					$notification->setAssocType(ASSOC_TYPE_ANNOUNCEMENT);
					$notification->setAssocId($id);
					$notification->setType(NOTIFICATION_TYPE_NEW_ANNOUNCEMENT);
					break;
			}

			$notificationDao->update(
				sprintf('INSERT INTO notifications
						(user_id, level, date_created, date_read, context_id, type, assoc_type, assoc_id)
					VALUES
						(?, ?, %s, %s, ?, ?, ?, ?)',
					$notificationDao->datetimeToDB($notification->getDateCreated()), $notificationDao->datetimeToDB($notification->getDateRead())),
				array(
					(int) $notification->getUserId(),
					(int) $notification->getLevel(),
					(int) $notification->getContextId(),
					(int) $notification->getType(),
					(int) $notification->getAssocType(),
					(int) $notification->getAssocId()
				)
			);
			unset($notification);
			$result->MoveNext();
		}

		$result->Close();
		unset($result);


		// Retrieve all settings from pre-2.4 notification_settings table
		$result =& $notificationDao->retrieve('SELECT * FROM notification_settings_old');
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$settingName = $row['setting_name'];
			$contextId = $row['context'];

			switch ($settingName) {
				case 'email':
				case 'notify':
					$notificationType = $row['setting_value'];
					$newSettingName = ($settingName == 'email' ? 'emailed_notification' : 'blocked_notification');
					$userId = $row['user_id'];

					$notificationDao->update(
						'INSERT INTO notification_subscription_settings
							(setting_name, setting_value, user_id, context, setting_type)
							VALUES
							(?, ?, ?, ?, ?)',
						array(
							$newSettingName,
							(int) $notificationType,
							(int) $userId,
							(int) $contextId,
							'int'
						)
					);
					break;
				case 'mailList':
				case 'mailListUnconfirmed':
					$confirmed = ($settingName == 'mailList') ? 1 : 0;
					$email = $row['setting_value'];
					$settingId = $row['setting_id'];

					// Get the token from the access_keys table
					$accessKeyDao =& DAORegistry::getDAO('AccessKeyDAO'); /* @var $accessKeyDao AccessKeyDAO */
					$accessKey =& $accessKeyDao->getAccessKeyByUserId('MailListContext', $settingId);
					if(!$accessKey) continue;
					$token = $accessKey->getKeyHash();

					// Delete the access key -- we don't need it anymore
					$accessKeyDao->deleteObject($accessKey);

					$notificationDao->update(
						'INSERT INTO notification_mail_list
							(email, context, token, confirmed)
							VALUES
							(?, ?, ?, ?)',
						array(
							$email,
							(int) $contextId,
							$token,
							$confirmed
						)
					);
					break;
			}

			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return true;
	}


	/**
	 * For 2.3.7 Upgrade -- Remove author revised file upload IDs erroneously added to copyedit signoff
	 */
	function removeAuthorRevisedFilesFromSignoffs() {
		import('classes.article.Article');
		$signoffDao =& DAORegistry::getDAO('SignoffDAO'); /* @var $signoffDao SignoffDAO */

		$result =& $signoffDao->retrieve(
			'SELECT DISTINCT s.signoff_id
			FROM articles a
				JOIN signoffs s ON (a.article_id = s.assoc_id)
			WHERE s.symbolic = ?
				AND s.file_id = a.revised_file_id',
			'SIGNOFF_COPYEDITING_INITIAL'
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			$signoff = $signoffDao->getById($row['signoff_id']); /* @var $signoff Signoff */
			$signoff->setFileId(null);
			$signoff->setFileRevision(null);
			$signoffDao->updateObject($signoff);

			$result->MoveNext();
		}

		return true;
	}

	/*
	 * For 2.3.7 upgrade: Improve reviewing interests storage structure
	 * @return boolean
	 */
	function migrateReviewingInterests2() {
		$interestDao =& DAORegistry::getDAO('InterestDAO'); /* @var $interestDao InterestDAO */
		$interestEntryDao =& DAORegistry::getDAO('InterestEntryDAO'); /* @var $interestEntryDao InterestEntryDAO */

		// Check if this upgrade method has already been run to prevent data corruption on subsequent upgrade attempts
		$idempotenceCheck =& $interestDao->retrieve('SELECT * FROM controlled_vocabs cv WHERE symbolic = ?', array('interest'));
		$row = $idempotenceCheck->GetRowAssoc(false);
		if ($idempotenceCheck->RecordCount() == 1 && $row['assoc_id'] == 0 && $row['assoc_type'] == 0) return true;
		unset($idempotenceCheck);

		// Get all interests for all users
		$result =& $interestDao->retrieve(
			'SELECT DISTINCT cves.setting_value as interest_keyword,
				cv.assoc_id as user_id
			FROM	controlled_vocabs cv
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)
			WHERE	cv.symbolic = ?',
			array('interest')
		);

		$oldEntries =& $interestDao->retrieve(
			'SELECT	controlled_vocab_entry_id
			FROM	controlled_vocab_entry_settings cves
			WHERE	cves.setting_name = ?',
			array('interest')
		);
		while (!$oldEntries->EOF) {
			$row = $oldEntries->GetRowAssoc(false);
			$controlledVocabEntryId = (int) $row['controlled_vocab_entry_id'];
			$interestDao->update('DELETE FROM controlled_vocab_entries WHERE controlled_vocab_entry_id = ?', $controlledVocabEntryId);
			$interestDao->update('DELETE FROM controlled_vocab_entry_settings WHERE controlled_vocab_entry_id = ?', $controlledVocabEntryId);
			$oldEntries->MoveNext();
		}
		$oldEntries->Close();
		unset($oldEntries);

		$controlledVocab = $interestDao->build();

		// Insert the user interests using the new storage structure
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);

			$userId = (int) $row['user_id'];
			$interest = $row['interest_keyword'];

			$interestEntry = $interestEntryDao->getBySetting($interest, $controlledVocab->getSymbolic(),
				$controlledVocab->getAssocId(), $controlledVocab->getAssocType(),
				$controlledVocab->getSymbolic()
			);

			if(!$interestEntry) {
				$interestEntry = $interestEntryDao->newDataObject(); /* @var $interestEntry InterestEntry */
				$interestEntry->setInterest($interest);
				$interestEntry->setControlledVocabId($controlledVocab->getId());
				$interestEntryDao->insertObject($interestEntry);
			}

			$interestEntryDao->update(
				'INSERT INTO user_interests (user_id, controlled_vocab_entry_id) VALUES (?, ?)',
				array($userId, (int) $interestEntry->getId())
			);

			$result->MoveNext();
		}
		$result->Close();
		unset($result);

		// Remove the obsolete interest data
		$interestDao->update('DELETE FROM controlled_vocabs WHERE symbolic = ?  AND assoc_type > 0', array('interest'));

		return true;
	}

	/**
	 * For 3.0.0 upgrade: Convert string-field semi-colon separated metadata to controlled vocabularies.
	 * @return boolean
	 */
	function migrateArticleMetadata() {

		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$articleDao =& DAORegistry::getDAO('ArticleDAO');

		// controlled vocabulary DAOs.
		$submissionSubjectDao =& DAORegistry::getDAO('SubmissionSubjectDAO');
		$submissionDisciplineDao =& DAORegistry::getDAO('SubmissionDisciplineDAO');
		$submissionAgencyDao =& DAORegistry::getDAO('SubmissionAgencyDAO');
		$submissionLanguageDao =& DAORegistry::getDAO('SubmissionLanguageDAO');
		$controlledVocabDao =& DAORegistry::getDAO('ControlledVocabDAO');

		// check to see if there are any existing controlled vocabs for submissionAgency, submissionDiscipline, submissionSubject, or submissionLanguage.
		// IF there are, this implies that this code has run previously, so return.
		$vocabTestResult =& $controlledVocabDao->retrieve('SELECT count(*) AS total FROM controlled_vocabs WHERE symbolic = \'submissionAgency\' OR symbolic = \'submissionDiscipline\' OR symbolic = \'submissionSubject\' OR symbolic = \'submissionLanguage\'');
		$testRow = $vocabTestResult->GetRowAssoc(false);
		if ($testRow['total'] > 0) return true;

		$journals =& $journalDao->getJournals();
		while ($journal =& $journals->next()) {
			// for languages, we depend on the journal locale settings since languages are not localized.
			// Use Journal locales, or primary if no defined submission locales.
			$supportedLocales = $journal->getSupportedSubmissionLocales();

			if (empty($supportedLocales)) $supportedLocales = array($journal->getPrimaryLocale());
			else if (!is_array($supportedLocales)) $supportedLocales = array($supportedLocales);

			$result =& $articleDao->retrieve('SELECT a.article_id FROM articles a WHERE a.journal_id = ?', array((int)$journal->getId()));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$articleId = (int)$row['article_id'];
				$settings = array();
				$settingResult =& $articleDao->retrieve('SELECT setting_value, setting_name, locale FROM article_settings WHERE article_id = ? AND (setting_name = \'discipline\' OR setting_name = \'subject\' OR setting_name = \'sponsor\');', array((int)$articleId));
				while (!$settingResult->EOF) {
					$settingRow = $settingResult->GetRowAssoc(false);
					$locale = $settingRow['locale'];
					$settingName = $settingRow['setting_name'];
					$settingValue = $settingRow['setting_value'];
					$settings[$settingName][$locale] = $settingValue;
					$settingResult->MoveNext();
					unset($locale);
					unset($settingName);
					unset($settingValue);
				}
				$settingResult->Close();
				unset($settingResult);

				$languageResult =& $articleDao->retrieve('SELECT language FROM articles WHERE article_id = ?', array((int)$articleId));
				$languageRow = $languageResult->getRowAssoc(false);
				// language is NOT localized originally.
				$language = $languageRow['language'];
				$languageResult->Close();
				// test for locales for each field since locales may have been modified since
				// the article was last edited.

				$disciplines = $subjects = $agencies = array();

				if (array_key_exists('discipline', $settings)) {
					$disciplineLocales = array_keys($settings['discipline']);
					if (is_array($disciplineLocales)) {
						foreach ($disciplineLocales as &$locale) {
							$disciplines[$locale] = preg_split('/[,;:]/', $settings['discipline'][$locale]);
						}
						$submissionDisciplineDao->insertDisciplines($disciplines, $articleId, false);
					}
					unset($disciplineLocales);
					unset($disciplines);
				}

				if (array_key_exists('subject', $settings)) {
					$subjectLocales = array_keys($settings['subject']);
					if (is_array($subjectLocales)) {
						foreach ($subjectLocales as &$locale) {
							$subjects[$locale] = preg_split('/[,;:]/', $settings['subject'][$locale]);
						}
						$submissionSubjectDao->insertSubjects($subjects, $articleId, false);
					}
					unset($subjectLocales);
					unset($subjects);
				}

				if (array_key_exists('sponsor', $settings)) {
					$sponsorLocales = array_keys($settings['sponsor']);
					if (is_array($sponsorLocales)) {
						foreach ($sponsorLocales as &$locale) {
							// note array name change.  Sponsor -> Agency
							$agencies[$locale] = preg_split('/[,;:]/', $settings['sponsor'][$locale]);
						}
						$submissionAgencyDao->insertAgencies($agencies, $articleId, false);
					}
					unset($sponsorLocales);
					unset($agencies);
				}

				$languages = array();
				foreach ($supportedLocales as &$locale) {
					$languages[$locale] = preg_split('/\s+/', $language);
				}

				$submissionLanguageDao->insertLanguages($languages, $articleId, false);

				unset($languages);
				unset($language);
				unset($settings);
				$result->MoveNext();
			}
			$result->Close();
			unset($supportedLocales);
			unset($result);
			unset($journal);
		}

		return true;
	}
}

?>
