<?php

/**
 * @file controllers/tab/settings/paymentMethod/form/PaymentMethodForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentMethodForm
 * @ingroup controllers_tab_settings_paymentMethod_form
 *
 * @brief Form to edit payment method settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class PaymentMethodForm extends ContextSettingsForm {
	/** @var $paymentPlugins array */
	var $paymentPlugins;

	/**
	 * Constructor.
	 */
	function PaymentMethodForm($wizardMode = false) {
		$settings = array(
			'paymentPluginName' => 'string',
			'currency' => 'string',
		);

		parent::ContextSettingsForm($settings, 'controllers/tab/settings/paymentMethod/form/paymentMethodForm.tpl', $wizardMode);
		$this->paymentPlugins = PluginRegistry::loadCategory('paymethod');
	}

	/**
	 * @see ContextSettingsForm::fetch
	 */
	function fetch(&$request) {
		$templateMgr = TemplateManager::getManager($request);
		$currencyDao = DAORegistry::getDAO('CurrencyDAO');
		$currencies = array();
		foreach ($currencyDao->getCurrencies() as $currency) {
			$currencies[$currency->getCodeAlpha()] = $currency->getName();
		}
		$templateMgr->assign('currencies', $currencies);
		return parent::fetch($request);
	}

	/**
	 * @see ContextSettingsForm::initData
	 */

	/**
	 * @see ContextSettingsForm::readInputData
	 */
	function readInputData(&$request) {
		parent::readInputData($request);

		$paymentPluginName = $this->getData('paymentPluginName');
		if (!isset($this->paymentPlugins[$paymentPluginName])) return false;
		$plugin = $this->paymentPlugins[$paymentPluginName];

		$this->readUserVars($plugin->getSettingsFormFieldNames());
	}

	/**
	 * @see ContextSettingsForm::execute
	 */
	function execute(&$request) {
		$context = $request->getContext();

		// Get the selected payment plugin
		$paymentPluginName = $this->getData('paymentPluginName');
		if (isset($this->paymentPlugins[$paymentPluginName])) {
			$plugin = $this->paymentPlugins[$paymentPluginName];

			// Save the plugin-specific settings
			foreach ($plugin->getSettingsFormFieldNames() as $settingName) {
				$plugin->updateSetting($context->getId(), $settingName, $this->getData($settingName));
			}

			// Remove notification.
			$notificationDao = DAORegistry::getDAO('NotificationDAO');
			$notificationDao->deleteByAssoc($context->getAssocType(), $context->getId(), null, NOTIFICATION_TYPE_CONFIGURE_PAYMENT_METHOD, $context->getId());
		} else {
			// Create notification.
			$notificationMgr = new NotificationManager();
			$notificationMgr->createNotification($request, null, NOTIFICATION_TYPE_CONFIGURE_PAYMENT_METHOD,
				$context->getId(), $context->getAssocType(), $context->getId(), NOTIFICATION_LEVEL_NORMAL);
		}

		return parent::execute($request);
	}
}

?>
