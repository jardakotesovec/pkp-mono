<?php
/**
 * @file controllers/form/context/PKPEmailSetupForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPEmailSetupForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for configuring a context's email settings.
 */
import('lib.pkp.components.forms.FormComponent');

define('FORM_EMAIL_SETUP', 'emailSetup');

class PKPEmailSetupForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_EMAIL_SETUP;

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
		$this->successMessage = __('manager.publication.emailSetup.success');
		$this->locales = $locales;

		$canEnvelopeSender = Config::getVar('email', 'allow_envelope_sender');

		$this->addField(new FieldRichTextarea('emailSignature', [
				'label' => __('manager.setup.emailSignature'),
				'tooltip' => __('manager.setup.emailSignature.description'),
				'value' => $context->getData('emailSignature'),
			]));

		if ($canEnvelopeSender) {
			$this->addField(new FieldText('envelopeSender', [
				'label' => __('manager.setup.emailBounceAddress'),
				'tooltip' => __('manager.setup.emailBounceAddress.description'),
				'value' => $context->getData('envelopeSender'),
			]));
		} else {
			$this->addField(new FieldHTML('envelopeSender', [
				'label' => __('manager.setup.emailBounceAddress'),
				'value' => __('manager.setup.emailBounceAddress.disabled'),
			]));
		}
	}
}
