<?php

/**
 * @file plugins/generic/piwik/PiwikPlugin.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PiwikPlugin
 * @ingroup plugins_generic_piwik
 *
 * @brief Piwik plugin class
 */


import('lib.pkp.classes.plugins.GenericPlugin');

class PiwikPlugin extends GenericPlugin {

	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if (!Config::getVar('general', 'installed')) return false;
		$this->addLocaleData();
		if ($success) {
			// Insert Piwik page tag to common footer
			HookRegistry::register('Templates::Common::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert Piwik page tag to article footer
			HookRegistry::register('Templates::Article::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert Piwik page tag to article interstitial footer
			HookRegistry::register('Templates::Article::Interstitial::PageFooter', array($this, 'insertFooter'));

			// Insert Piwik page tag to article pdf interstitial footer
			HookRegistry::register('Templates::Article::PdfInterstitial::PageFooter', array($this, 'insertFooter'));

			// Insert Piwik page tag to reading tools footer
			HookRegistry::register('Templates::Rt::Footer::PageFooter', array($this, 'insertFooter'));

			// Insert Piwik page tag to help footer
			HookRegistry::register('Templates::Help::Footer::PageFooter', array($this, 'insertFooter'));
		}
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category, and should be suitable for part of a filename
	 * (ie short, no spaces, and no dependencies on cases being unique).
	 * @return String name of plugin
	 */
	function getName() {
		return 'PiwikPlugin';
	}

	function getDisplayName() {
		return __('plugins.generic.piwik.displayName');
	}

	function getDescription() {
		return __('plugins.generic.piwik.description');
	}

	/**
	 * Extend the {url ...} smarty to support this plugin.
	 */
	function smartyPluginUrl($params, &$smarty) {
		$path = array($this->getCategory(), $this->getName());
		if (is_array($params['path'])) {
			$params['path'] = array_merge($path, $params['path']);
		} elseif (!empty($params['path'])) {
			$params['path'] = array_merge($path, array($params['path']));
		} else {
			$params['path'] = $path;
		}

		if (!empty($params['id'])) {
			$params['path'] = array_merge($params['path'], array($params['id']));
			unset($params['id']);
		}
		return $smarty->smartyUrl($params, $smarty);
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				__('manager.plugins.disable')
			);
			$verbs[] = array(
				'settings',
				__('plugins.generic.piwik.manager.settings')
			);
		} else {
			$verbs[] = array(
				'enable',
				__('manager.plugins.enable')
			);
		}
		return $verbs;
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled() {
		$request = $this->getRequest();
		$journal = $request->getJournal();
		if (!$journal) return false;
		return $this->getSetting($journal->getId(), 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin
	 */
	function setEnabled($enabled) {
		$request = $this->getRequest();
		$journal = $request->getJournal();
		if ($journal) {
			$this->updateSetting($journal->getId(), 'enabled', $enabled ? true : false);
			return true;
		}
		return false;
	}

	/**
	 * Insert Piwik page tag to footer
	 */
	function insertFooter($hookName, $params) {
		if ($this->getEnabled()) {
			$smarty = $params[1];
			$output =& $params[2];
			$request = $this->getRequest();

			$journal = $request->getJournal();
			$journalId = $journal->getId();
			$journalPath = $journal->getPath();
			$piwikSiteId = $this->getSetting($journalId, 'piwikSiteId');
			$piwikUrl = $this->getSetting($journalId, 'piwikUrl');
			if (!empty($piwikSiteId) && !empty($piwikUrl)) {
				$output = 	'<!-- Piwik -->'.
						'<script type="text/javascript">'.
						'var pkBaseURL = "'.$piwikUrl.'/";'.
						'document.write(unescape("%3Cscript src=\'" + pkBaseURL + "piwik.js\' type=\'text/javascript\'%3E%3C/script%3E"));'.
						'</script><script type="text/javascript">'.
						'try {'.
						'var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", '.$piwikSiteId.');'.
						'piwikTracker.setDocumentTitle("'.$journalPath.'");'.
						'piwikTracker.trackPageView();'.
						'piwikTracker.enableLinkTracking();'.
						'} catch( err ) {}'.
						'</script><noscript><p><img src="'.$piwikUrl.'/piwik.php?idsite='.$piwikSiteId.'" style="border:0" alt="" /></p></noscript>'.
						'<!-- End Piwik Tag -->';
			}
		}
		return false;
	}

 	/**
	 * @see PKPPlugin::manage()
	 */
	function manage($verb, $args, &$message, &$messageParams, &$pluginModalContent = null) {
		$request = $this->getRequest();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

		$journal = $request->getJournal();
		$returner = true;

		switch ($verb) {
			case 'enable':
				$this->setEnabled(true);
				$returner = false;
				break;
			case 'disable':
				$this->setEnabled(false);
				$returner = false;
				break;
			case 'settings':
				if ($this->getEnabled()) {
					$this->import('PiwikSettingsForm');
					$form = new PiwikSettingsForm($this, $journal->getId());
					if ($request->getUserVar('save')) {
						$form->readInputData();
						if ($form->validate()) {
							$form->execute();
							$request->redirect(null, 'manager', 'plugin');
						} else {
							$form->display();
						}
					} else {
						$form->initData();
						$form->display();
					}
				} else {
					$request->redirect(null, 'manager');
				}
				break;
			default:
				$request->redirect(null, 'manager');
		}
		return $returner;
	}
}
?>
