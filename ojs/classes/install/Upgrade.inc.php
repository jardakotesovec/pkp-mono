<?php

/**
 * @file classes/install/Upgrade.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
	 * Clear the data cache files (needed because of direct tinkering
	 * with settings tables)
	 * @return boolean
	 */
	function clearDataCache() {
		$cacheManager = CacheManager::getManager();
		$cacheManager->flush(null, CACHE_TYPE_FILE);
		$cacheManager->flush(null, CACHE_TYPE_OBJECT);
		return true;
	}

	/**
	 * For 2.3.3 upgrade:  Migrate reviewing interests from free text to controlled vocab structure
	 * @return boolean
	 */
	function migrateReviewingInterests() {
		$userDao = DAORegistry::getDAO('UserDAO');
		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');

		$result = $userDao->retrieve('SELECT setting_value as interests, user_id FROM user_settings WHERE setting_name = ?', 'interests');
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
		$notificationDao = DAORegistry::getDAO('NotificationDAO');

		// Retrieve all notifications from pre-2.4 notifications table
		$result = $notificationDao->retrieve('SELECT * FROM notifications_old');
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
				case NOTIFICATION_TYPE_METADATA_MODIFIED:
				case NOTIFICATION_TYPE_SUBMISSION_COMMENT:
				case NOTIFICATION_TYPE_SUBMISSION_SUBMITTED:
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
		$result = $notificationDao->retrieve('SELECT * FROM notification_settings_old');
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
					$accessKeyDao = DAORegistry::getDAO('AccessKeyDAO'); /* @var $accessKeyDao AccessKeyDAO */
					$accessKey = $accessKeyDao->getAccessKeyByUserId('MailListContext', $settingId);
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
		$signoffDao = DAORegistry::getDAO('SignoffDAO'); /* @var $signoffDao SignoffDAO */

		$result = $signoffDao->retrieve(
			'SELECT DISTINCT s.signoff_id
			FROM submissions a
				JOIN signoffs s ON (a.submission_id = s.assoc_id)
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
		$interestDao = DAORegistry::getDAO('InterestDAO'); /* @var $interestDao InterestDAO */
		$interestEntryDao = DAORegistry::getDAO('InterestEntryDAO'); /* @var $interestEntryDao InterestEntryDAO */

		// Check if this upgrade method has already been run to prevent data corruption on subsequent upgrade attempts
		$idempotenceCheck = $interestDao->retrieve('SELECT * FROM controlled_vocabs cv WHERE symbolic = ?', array('interest'));
		$row = $idempotenceCheck->GetRowAssoc(false);
		if ($idempotenceCheck->RecordCount() == 1 && $row['assoc_id'] == 0 && $row['assoc_type'] == 0) return true;
		unset($idempotenceCheck);

		// Get all interests for all users
		$result = $interestDao->retrieve(
			'SELECT DISTINCT cves.setting_value as interest_keyword,
				cv.assoc_id as user_id
			FROM	controlled_vocabs cv
				LEFT JOIN controlled_vocab_entries cve ON (cve.controlled_vocab_id = cv.controlled_vocab_id)
				LEFT JOIN controlled_vocab_entry_settings cves ON (cves.controlled_vocab_entry_id = cve.controlled_vocab_entry_id)
			WHERE	cv.symbolic = ?',
			array('interest')
		);

		$oldEntries = $interestDao->retrieve(
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
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$articleDao = DAORegistry::getDAO('ArticleDAO');

		// controlled vocabulary DAOs.
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO');
		$submissionDisciplineDao = DAORegistry::getDAO('SubmissionDisciplineDAO');
		$submissionAgencyDao = DAORegistry::getDAO('SubmissionAgencyDAO');
		$submissionLanguageDao = DAORegistry::getDAO('SubmissionLanguageDAO');
		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');

		// check to see if there are any existing controlled vocabs for submissionAgency, submissionDiscipline, submissionSubject, or submissionLanguage.
		// IF there are, this implies that this code has run previously, so return.
		$vocabTestResult = $controlledVocabDao->retrieve('SELECT count(*) AS total FROM controlled_vocabs WHERE symbolic = \'submissionAgency\' OR symbolic = \'submissionDiscipline\' OR symbolic = \'submissionSubject\' OR symbolic = \'submissionLanguage\'');
		$testRow = $vocabTestResult->GetRowAssoc(false);
		if ($testRow['total'] > 0) return true;

		$journals = $journalDao->getAll();
		while ($journal = $journals->next()) {
			// for languages, we depend on the journal locale settings since languages are not localized.
			// Use Journal locales, or primary if no defined submission locales.
			$supportedLocales = $journal->getSupportedSubmissionLocales();

			if (empty($supportedLocales)) $supportedLocales = array($journal->getPrimaryLocale());
			else if (!is_array($supportedLocales)) $supportedLocales = array($supportedLocales);

			$result = $articleDao->retrieve('SELECT a.submission_id FROM submissions a WHERE a.context_id = ?', array((int)$journal->getId()));
			while (!$result->EOF) {
				$row = $result->GetRowAssoc(false);
				$articleId = (int)$row['submission_id'];
				$settings = array();
				$settingResult = $articleDao->retrieve('SELECT setting_value, setting_name, locale FROM submission_settings WHERE submission_id = ? AND (setting_name = \'discipline\' OR setting_name = \'subject\' OR setting_name = \'sponsor\');', array((int)$articleId));
				while (!$settingResult->EOF) {
					$settingRow = $settingResult->GetRowAssoc(false);
					$locale = $settingRow['locale'];
					$settingName = $settingRow['setting_name'];
					$settingValue = $settingRow['setting_value'];
					$settings[$settingName][$locale] = $settingValue;
					$settingResult->MoveNext();
				}
				$settingResult->Close();

				$languageResult = $articleDao->retrieve('SELECT language FROM submissions WHERE submission_id = ?', array((int)$articleId));
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

	/**
	 * For 3.0.0 upgrade:  Migrate the static user role structure to
	 * user groups and stage assignments.
	 * @return boolean
	 */
	function migrateUserRoles() {

		// if there are any user_groups created, then this has already run.  Return immediately in that case.

		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$userGroupTest = $userGroupDao->retrieve('SELECT count(*) AS total FROM user_groups');
		$testRow = $userGroupTest->GetRowAssoc(false);
		if ($testRow['total'] > 0) return true;

		// First, do Admins.
		// create the admin user group.
		$userGroupDao->update('INSERT INTO user_groups (user_group_id, context_id, role_id, path, is_default) VALUES (?, ?, ?, ?, ?)', array(1, 0, ROLE_ID_SITE_ADMIN, 'admin', 1));
		$userResult = $userGroupDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array(0, ROLE_ID_SITE_ADMIN));
		while (!$userResult->EOF) {
			$row = $userResult->GetRowAssoc(false);
			$userGroupDao->update('INSERT INTO user_user_groups (user_group_id, user_id) VALUES (?, ?)', array(1, (int) $row['user_id']));
			$userResult->MoveNext();
		}

		// iterate through all journals and assign remaining users to their respective groups.
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journals = $journalDao->getAll();

		AppLocale::requireComponents(LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_PKP_DEFAULT);

		while ($journal = $journals->next()) {
			// Install default user groups so we can assign users to them.
			$userGroupDao->installSettings($journal->getId(), 'registry/userGroups.xml');

			// Readers.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_READER);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_READER));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// Subscription Managers.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_SUBSCRIPTION_MANAGER);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_SUBSCRIPTION_MANAGER));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// Managers.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_MANAGER);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_MANAGER));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// Authors.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_AUTHOR);
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_AUTHOR));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// update the user_group_id colun in the authors table.
			$userGroupDao->update('UPDATE authors SET user_group_id = ?', array((int) $group->getId()));

			// Reviewers.  All existing OJS reviewers get mapped to external reviewers.
			// There should only be one user group with ROLE_ID_REVIEWER in the external review stage.
			$userGroups = $userGroupDao->getUserGroupsByStage($journal->getId(), WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, true, false, ROLE_ID_REVIEWER);
			while ($group = $userGroups->next()) {
				// make sure.
				if ($group->getRoleId() != ROLE_ID_REVIEWER) continue;
				$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_REVIEWER));
				while (!$userResult->EOF) {
					$row = $userResult->GetRowAssoc(false);
					$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
					$userResult->MoveNext();
				}
			}

			// fix stage id assignments for reviews.  OJS hard coded *all* of these to '1' initially. Consider OJS reviews as external reviews.
			$userGroupDao->update('UPDATE review_assignments SET stage_id = ?', array(WORKFLOW_STAGE_ID_EXTERNAL_REVIEW));

			// Guest editors.
			$userGroupIds = $userGroupDao->getUserGroupIdsByRoleId(ROLE_ID_GUEST_EDITOR, $journal->getId());
			$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_GUEST_EDITOR));
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				// there should only be one guest editor group id.
				$userGroupDao->assignUserToGroup($row['user_id'], $userGroupIds[0]);
				$userResult->MoveNext();
			}

			// regular Editors.  NOTE:  this involves a role id change from 0x100 to 0x10 (old OJS _EDITOR to PKP-lib _MANAGER).
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_MANAGER);

			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.editor') {
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_EDITOR));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}

			// Section Editors.
			$group = $userGroupDao->getDefaultByRoleId($journal->getId(), ROLE_ID_SECTION_EDITOR);
			$userResult = $journalDao->retrieve('SELECT DISTINCT user_id FROM section_editors WHERE journal_id = ?', array((int) $journal->getId()));;
			while (!$userResult->EOF) {
				$row = $userResult->GetRowAssoc(false);
				$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
				$userResult->MoveNext();
			}

			// Layout Editors. NOTE:  this involves a role id change from 0x300 to 0x1001 (old OJS _LAYOUT_EDITOR to PKP-lib _ASSISTANT).
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_ASSISTANT);
			define('ROLE_ID_LAYOUT_EDITOR',	0x00000300);
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.layoutEditor') {
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_LAYOUT_EDITOR));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}

			// Copyeditors. NOTE:  this involves a role id change from 0x2000 to 0x1001 (old OJS _COPYEDITOR to PKP-lib _ASSISTANT).
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_ASSISTANT);
			define('ROLE_ID_COPYEDITOR', 0x00002000);
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.copyeditor') {
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_COPYEDITOR));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}

			// Proofreaders. NOTE:  this involves a role id change from 0x3000 to 0x1001 (old OJS _PROOFREADER to PKP-lib _ASSISTANT).
			define('ROLE_ID_PROOFREADER', 0x00003000);
			$userGroups = $userGroupDao->getByRoleId($journal->getId(), ROLE_ID_ASSISTANT);
			while ($group = $userGroups->next()) {
				if ($group->getData('nameLocaleKey') == 'default.groups.name.proofreader') {
					$userResult = $journalDao->retrieve('SELECT user_id FROM roles WHERE journal_id = ? AND role_id = ?', array((int) $journal->getId(), ROLE_ID_PROOFREADER));
					while (!$userResult->EOF) {
						$row = $userResult->GetRowAssoc(false);
						$userGroupDao->assignUserToGroup($row['user_id'], $group->getId());
						$userResult->MoveNext();
					}
				}
			}
		}
		return true;
	}

	/**
	 * For 2.4 upgrade: migrate COUNTER statistics to the metrics table.
	 */
	function migrateCounterPluginUsageStatistics() {
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$loadId = '3.0.0-upgrade-counter';
		$metricsDao->purgeLoadBatch($loadId);

		$fileTypeCounts = array(
			'count_html' => USAGE_STATS_REPORT_PLUGIN_FILE_TYPE_HTML,
			'count_pdf' => USAGE_STATS_REPORT_PLUGIN_FILE_TYPE_PDF,
			'count_other' => USAGE_STATS_REPORT_PLUGIN_FILE_TYPE_OTHER
		);

		$result = $metricsDao->retrieve('SELECT * FROM counter_monthly_log');

		while(!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			foreach ($fileTypeCounts as $countType => $fileType) {
				$month = (string) $row['month'];
				if (strlen($month) == 1) {
					$month = '0' . $month;
				}
				if ($row[$countType]) {
					$record = array(
						'load_id' => $loadId,
						'assoc_type' => ASSOC_TYPE_JOURNAL,
						'assoc_id' => $row['journal_id'],
						'metric_type' => OJS_METRIC_TYPE_LEGACY_COUNTER,
						'metric' => $row[$countType],
						'file_type' => $fileType,
						'month' => $row['year'] . $month
					);
					$metricsDao->insertRecord($record);
				}
			}
			$result->MoveNext();
		}

		// Remove the plugin settings.
		$metricsDao->update('DELETE FROM plugin_settings WHERE plugin_name = ?', array('counterplugin'), false);

		return true;
	}

	/**
	* For 2.4 upgrade: migrate Timed views statistics to the metrics table.
	*/
	function migrateTimedViewsUsageStatistics() {
		$metricsDao = DAORegistry::getDAO('MetricsDAO'); /* @var $metricsDao MetricsDAO */
		$loadId = '3.0.0-upgrade-timedViews';
		$metricsDao->purgeLoadBatch($loadId);

		$plugin = PluginRegistry::getPlugin('generic', 'usagestatsplugin');
		$plugin->import('UsageStatsTemporaryRecordDAO');
		$tempStatsDao = new UsageStatsTemporaryRecordDAO();
		$tempStatsDao->deleteByLoadId($loadId);

		import('plugins.generic.usageStats.GeoLocationTool');
		$geoLocationTool = new GeoLocationTool();

		$result = $metricsDao->retrieve('SELECT * FROM timed_views_log');
		while(!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			list($countryId, $cityName, $region) = $geoLocationTool->getGeoLocation($row['ip_address']);
			if ($row['galley_id']) {
				$assocType = ASSOC_TYPE_GALLEY;
				$assocId = $row['galley_id'];
			} else {
				$assocType = ASSOC_TYPE_ARTICLE;
				$assocId = $row['submission_id'];
			};

			$day = date('Ymd', strtotime($row['date']));
			$tempStatsDao->insert($assocType, $assocId, $day, $countryId, $region, $cityName, null, $loadId);
			$result->MoveNext();
		}

		// Articles.
		$params = array(OJS_METRIC_TYPE_TIMED_VIEWS, $loadId, ASSOC_TYPE_ARTICLE);
		$tempStatsDao->update(
					'INSERT INTO metrics (load_id, metric_type, assoc_type, assoc_id, day, country_id, region, city, submission_id, metric, context_id, issue_id)
					SELECT tr.load_id, ?, tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, tr.assoc_id, count(tr.metric), a.context_id, pa.issue_id
					FROM usage_stats_temporary_records AS tr
					LEFT JOIN submissions AS a ON a.submission_id = tr.assoc_id
					LEFT JOIN published_submissions AS pa ON pa.submission_id = tr.assoc_id
					WHERE tr.load_id = ? AND tr.assoc_type = ? AND a.context_id IS NOT NULL AND pa.issue_id IS NOT NULL
					GROUP BY tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, tr.file_type, tr.load_id', $params
		);

		// Galleys.
		$params = array(OJS_METRIC_TYPE_TIMED_VIEWS, $loadId, ASSOC_TYPE_GALLEY);
		$tempStatsDao->update(
					'INSERT INTO metrics (load_id, metric_type, assoc_type, assoc_id, day, country_id, region, city, submission_id, metric, context_id, issue_id)
					SELECT tr.load_id, ?, tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, ag.submission_id, count(tr.metric), a.context_id, pa.issue_id
					FROM usage_stats_temporary_records AS tr
					LEFT JOIN submission_galleys AS ag ON ag.galley_id = tr.assoc_id
					LEFT JOIN submissions AS a ON a.submission_id = ag.submission_id
					LEFT JOIN published_submissions AS pa ON pa.submission_id = ag.submission_id
					WHERE tr.load_id = ? AND tr.assoc_type = ? AND a.context_id IS NOT NULL AND pa.issue_id IS NOT NULL
					GROUP BY tr.assoc_type, tr.assoc_id, tr.day, tr.country_id, tr.region, tr.city, tr.file_type, tr.load_id', $params
		);

		$tempStatsDao->deleteByLoadId($loadId);

		// Remove the plugin settings.
		$metricsDao->update('DELETE FROM plugin_settings WHERE plugin_name = ?', array('timedviewplugin'), false);

		return true;
	}

	/**
	* For 2.4 upgrade: migrate OJS default statistics to the metrics table.
	*/
	function migrateDefaultUsageStatistics() {
		$loadId = '3.0.0-upgrade-ojsViews';
		$metricsDao = DAORegistry::getDAO('MetricsDAO');
		$insertIntoClause = 'INSERT INTO metrics (file_type, load_id, metric_type, assoc_type, assoc_id, submission_id, metric, context_id, issue_id)';

		// Galleys.
		$galleyUpdateCases = array(
			array('fileType' => STATISTICS_FILE_TYPE_PDF, 'isHtml' => false, 'assocType' => ASSOC_TYPE_GALLEY),
			array('fileType' => STATISTICS_FILE_TYPE_HTML, 'isHtml' => true, 'assocType' => ASSOC_TYPE_GALLEY),
			array('fileType' => STATISTICS_FILE_TYPE_OTHER, 'isHtml' => false, 'assocType' => ASSOC_TYPE_GALLEY),
			array('fileType' => STATISTICS_FILE_TYPE_PDF, 'assocType' => ASSOC_TYPE_ISSUE_GALLEY),
			array('fileType' => STATISTICS_FILE_TYPE_OTHER, 'assocType' => ASSOC_TYPE_ISSUE_GALLEY),
		);

		foreach ($galleyUpdateCases as $case) {
			$params = array();
			if ($case['fileType'] == STATISTICS_FILE_TYPE_PDF) {
				$pdfFileTypeWhereCheck = 'IN';
			} else {
				$pdfFileTypeWhereCheck = 'NOT IN';
			}

			$params = array($case['fileType'], $loadId, OJS_METRIC_TYPE_LEGACY_DEFAULT, $case['assocType']);

			if ($case['assocType'] == ASSOC_TYPE_GALLEY) {
				array_push($params, (int) $case['isHtml']);
				$selectClause = ' SELECT ?, ?, ?, ?, ag.galley_id, ag.article_id, ag.views, a.context_id, pa.issue_id
					FROM article_galleys_stats_migration as ag
					LEFT JOIN submissions AS a ON ag.article_id = a.submission_id
					LEFT JOIN published_submissions as pa on ag.article_id = pa.submission_id
					LEFT JOIN submission_files as af on ag.file_id = af.file_id
					WHERE a.submission_id is not null AND ag.views > 0 AND ag.html_galley = ?
						AND af.file_type ';
			} else {
				$selectClause = 'SELECT ?, ?, ?, ?, ig.galley_id, 0, ig.views, i.journal_id, ig.issue_id
					FROM issue_galleys_stats_migration AS ig
					LEFT JOIN issues AS i ON ig.issue_id = i.issue_id
					LEFT JOIN issue_files AS ifi ON ig.file_id = ifi.file_id
					WHERE ig.views > 0 AND i.issue_id is not null AND ifi.file_type ';
			}

			array_push($params, 'application/pdf', 'application/x-pdf', 'text/pdf', 'text/x-pdf');

			$metricsDao->update($insertIntoClause . $selectClause . $pdfFileTypeWhereCheck . ' (?, ?, ?, ?)', $params, false);
		}

		// Published articles.
		$params = array(null, $loadId, OJS_METRIC_TYPE_LEGACY_DEFAULT, ASSOC_TYPE_ARTICLE);
		$metricsDao->update($insertIntoClause .
			' SELECT ?, ?, ?, ?, pa.article_id, pa.article_id, pa.views, i.journal_id, pa.issue_id
			FROM published_articles_stats_migration as pa
			LEFT JOIN issues AS i ON pa.issue_id = i.issue_id
			WHERE pa.views > 0 AND i.issue_id is not null;', $params, false);

		// Set the site default metric type.
		$siteSettingsDao = DAORegistry::getDAO('SiteSettingsDAO'); /* @var $siteSettingsDao SiteSettingsDAO */
		$siteSettingsDao->updateSetting('defaultMetricType', OJS_METRIC_TYPE_COUNTER);

		return true;
	}
}

?>
