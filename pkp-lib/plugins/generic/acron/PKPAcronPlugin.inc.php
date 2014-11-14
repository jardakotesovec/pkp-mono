<?php

/**
 * @file plugins/generic/acron/PKPAcronPlugin.inc.php
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAcronPlugin
 * @ingroup plugins_generic_acron
 *
 * @brief Removes dependency on 'cron' for scheduled tasks, including
 * possible tasks defined by plugins. See the PKPAcronPlugin::parseCrontab
 * hook implementation.
 */

import('lib.pkp.classes.plugins.GenericPlugin');
import('lib.pkp.classes.scheduledTask.ScheduledTaskHelper');

// TODO: Error handling. If a scheduled task encounters an error...?

class PKPAcronPlugin extends GenericPlugin {

	/**
	 * @see LazyLoadPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;
		if ($success) {
			$this->addLocaleData();
			HookRegistry::register('LoadHandler',array(&$this, 'callbackLoadHandler'));
		}
		return $success;
	}

	/**
	* @see PKPPlugin::isSitePlugin()
	*/
	function isSitePlugin() {
		// This is a site-wide plugin.
		return true;
	}

	/**
	 * @see LazyLoadPlugin::getName()
	 */
	function getName() {
		return 'acronPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.acron.name');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.acron.description');
	}

	/**
	* @see PKPPlugin::getInstallSitePluginSettingsFile()
	*/
	function getInstallSitePluginSettingsFile() {
		return PKP_LIB_PATH . DIRECTORY_SEPARATOR . $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * @see GenericPlugin::getManagementVerbs()
	 */
	function getManagementVerbs() {
		$isEnabled = $this->getSetting(0, 'enabled');

		$verbs = array();
		$verbs[] = array(
			($isEnabled?'disable':'enable'),
			__($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);
		$verbs[] = array(
			'reload', __('plugins.generic.acron.reload')
		);
		return $verbs;
	}

	/**
	 * @see GenericPlugin::manage()
	 */
	function manage($verb, $args, &$message) {
		switch ($verb) {
			case 'enable':
				$this->updateSetting(0, 'enabled', true);
				$message = __('plugins.generic.acron.enabled');
				break;
			case 'disable':
				$this->updateSetting(0, 'enabled', false);
				$message = __('plugins.generic.acron.disabled');
				break;
			case 'reload':
				$this->_parseCrontab();
				$message = __('plugins.generic.acron.reloadedCronTab');
		}
		return false;
	}

	/**
	 * @see PKPPageRouter::loadHandler()
	 */
	function callbackLoadHandler($hookName, $args) {
		$isEnabled = $this->getSetting(0, 'enabled');
		if($isEnabled) {
			$taskDao = DAORegistry::getDao('ScheduledTaskDAO');

			// Grab the scheduled scheduled tree
			$scheduledTasks = $this->getSetting(0, 'crontab');
			if(is_null($scheduledTasks)) {
				$this->_parseCrontab();
				$scheduledTasks = $this->getSetting(0, 'crontab');
			}

			foreach($scheduledTasks as $task) {
				// We don't allow tasks without frequency, see _parseCronTab().
				$frequency = new XMLNode();
				$frequency->setAttribute(key($task['frequency']), current($task['frequency']));
				$canExecute = ScheduledTaskHelper::checkFrequency($task['className'], $frequency);

				if ($canExecute) {
					// Strip off the package name(s) to get the base class name
					$className = $task['className'];

					$pos = strrpos($className, '.');
					if ($pos === false) {
						$baseClassName = $className;
					} else {
						$baseClassName = substr($className, $pos+1);
					}

					$taskArgs = array();
					if (isset($task['args'])) {
						$taskArgs = $task['args'];
					}


					// There's a race here. Several requests may come in closely spaced.
					// Each may decide it's time to run scheduled tasks, and more than one
					// can happily go ahead and do it before the "last run" time is updated.

					// By updating the last run time as soon as feasible, we can minimize
					// the race window. See bug #8737.
					$updateResult = $taskDao->updateLastRunTime($className, time());

					switch ($updateResult) {
						case false: // DB doesn't support the get affected rows used inside update method.
						case 1: // Introduced a new last run time.
							// Load and execute the task.
							import($className);
							$task = new $baseClassName($taskArgs);
							$task->execute();
							break;
						case 0: // Another simultaneously request came first.
						default:
							break;
					}
				}
			}
		}

		return false;
	}


	//
	// Private helper methods.
	//
	/**
	 * Parse all scheduled tasks files and
	 * save the result object in database.
	 */
	function _parseCrontab() {
		$xmlParser = new XMLParser();

		$taskFilesPath = array();

		// Let plugins register their scheduled tasks too.
		HookRegistry::call('AcronPlugin::parseCronTab', array(&$taskFilesPath)); // Reference needed.

		// Add the default tasks file.
		$taskFilesPath[] = Config::getVar('general', 'registry_dir') . '/scheduledTasks.xml'; // TODO: make this a plugin setting, rather than assuming.

		$tasks = array();
		foreach ($taskFilesPath as $filePath) {
			$tree = $xmlParser->parse($filePath);

			if (!$tree) {
				$xmlParser->destroy();

				// TODO: graceful error handling
				fatalError('Error parsing scheduled tasks XML file: ' . $filePath);
			}

			foreach ($tree->getChildren() as $task) {
				$frequency = $task->getChildByName('frequency');

				$args = ScheduledTaskHelper::getTaskArgs($task);

				// Tasks without a frequency defined, or defined to zero, will run on every request.
				// To avoid that happening (may cause performance problems) we
				// setup a default period of time.
				$frequencyAttributes = $frequency->getAttributes();
				$setDefaultFrequency = true;
				$minHoursRunPeriod = 24;
				if (is_array($frequencyAttributes)) {
					foreach($frequencyAttributes as $key => $value) {
						if ($value != 0) {
							$setDefaultFrequency = false;
							break;
						}
					}
				}

				$tasks[] = array(
					'className' => $task->getAttribute('class'),
					'frequency' => $setDefaultFrequency ? array('hour' => $minHoursRunPeriod) : $frequencyAttributes,
					'args' => $args
				);
			}

			$xmlParser->destroy();
		}

		// Store the object.
		$this->updateSetting(0, 'crontab', $tasks, 'object');
	}
}
?>
