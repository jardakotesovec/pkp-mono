<?php
/**
 * @file classes/components/form/context/PKPPaymentSettingsForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPaymentSettingsForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for configuring the general payment settings.
 */
namespace PKP\components\forms\context;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldOptions;
use \PKP\components\forms\FieldSelect;

define('FORM_PAYMENT_SETTINGS', 'paymentSettings');

class PKPPaymentSettingsForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_PAYMENT_SETTINGS;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $context Context Journal or Press to change settings for
	 */
	public function __construct($action, $locales, $context) {
		$this->action = $action;
		$this->successMessage = __('manager.payment.success');
		$this->locales = $locales;

		$currencyDao = \DAORegistry::getDAO('CurrencyDAO');
		$currencies = [];
		foreach ($currencyDao->getCurrencies() as $currency) {
			$currencies[] = [
				'value' => $currency->getCodeAlpha(),
				'label' => $currency->getName(),
			];
		}

		// Ensure payment method plugins can hook in
		\PluginRegistry::loadCategory('paymethod', true);

		$this->addGroup([
				'id' => 'setup',
				'label' => __('navigation.setup'),
			])
			->addField(new FieldOptions('paymentsEnabled', [
				'label' => __('common.enable'),
				'options' => [
					['value' => true, 'label' => __('manager.payment.options.enablePayments')]
				],
				'value' => (bool) $context->getData('paymentsEnabled'),
				'groupId' => 'setup',
			]))
			->addField(new FieldSelect('currency', [
				'label' => __('manager.paymentMethod.currency'),
				'options' => $currencies,
				'showWhen' => 'paymentsEnabled',
				'value' => $context->getData('currency'),
				'groupId' => 'setup',
			]));
	}
}
