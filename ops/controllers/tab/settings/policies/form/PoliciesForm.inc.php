<?php

/**
 * @file controllers/tab/settings/policies/form/PoliciesForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep3Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 3 of journal setup.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class PoliciesForm extends ContextSettingsForm {
	/**
	 * Constructor.
	 */
	function PoliciesForm($wizardMode = false) {
		$settings = array(
			'copyrightNotice' => 'string',
			'includeCreativeCommons' => 'bool',
			'copyrightNoticeAgree' => 'bool',
			'requireAuthorCompetingInterests' => 'bool',
			'requireReviewerCompetingInterests' => 'bool',
			'metaDiscipline' => 'bool',
			'metaDisciplineExamples' => 'string',
			'metaSubjectClass' => 'bool',
			'metaSubjectClassTitle' => 'string',
			'metaSubjectClassUrl' => 'string',
			'metaSubject' => 'bool',
			'metaSubjectExamples' => 'string',
			'metaCoverage' => 'bool',
			'metaCoverageGeoExamples' => 'string',
			'metaCoverageChronExamples' => 'string',
			'metaCoverageResearchSampleExamples' => 'string',
			'metaType' => 'bool',
			'metaTypeExamples' => 'string',
			'metaCitations' => 'bool',
			'metaCitationOutputFilterId' => 'int',
			'copySubmissionAckPrimaryContact' => 'bool',
			'copySubmissionAckSpecified' => 'bool',
			'copySubmissionAckAddress' => 'string'
		);
		parent::ContextSettingsForm($settings, 'controllers/tab/settings/policies/form/policiesOldForm.tpl', $wizardMode);

		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress', 'optional', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorLocaleURL($this, 'metaSubjectClassUrl', 'optional', 'manager.setup.subjectClassificationURLValid'));
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('authorGuidelines', 'copyrightNotice', 'metaDisciplineExamples', 'metaSubjectClassTitle', 'metaSubjectClassUrl', 'metaSubjectExamples', 'metaCoverageGeoExamples', 'metaCoverageChronExamples', 'metaCoverageResearchSampleExamples', 'metaTypeExamples');
	}

	/**
	 * Fetch the form
	 * @param $request Request
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		// Add extra style sheets required for ajax components
		// FIXME: Must be removed after OMP->OJS backporting
		$templateMgr->addStyleSheet($request->getBaseUrl().'/styles/ojs.css');

		// Add extra java script required for ajax components
		// FIXME: Must be removed after OMP->OJS backporting
		$templateMgr->addJavaScript('lib/pkp/js/functions/citation.js');
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/validate/jquery.validate.min.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/jqueryValidatorI18n.js');

		import('classes.mail.MailTemplate');
		$mail = new MailTemplate('SUBMISSION_ACK');
		if ($mail->isEnabled()) {
			$templateMgr->assign('submissionAckEnabled', true);
		}

		//
		// Citation editor filter configuration
		//

		// 1) Add the filter grid URLs
		$dispatcher = $request->getDispatcher();
		$parserFilterGridUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.filter.ParserFilterGridHandler', 'fetchGrid');
		$templateMgr->assign('parserFilterGridUrl', $parserFilterGridUrl);
		$lookupFilterGridUrl = $dispatcher->url($request, ROUTE_COMPONENT, null, 'grid.filter.LookupFilterGridHandler', 'fetchGrid');
		$templateMgr->assign('lookupFilterGridUrl', $lookupFilterGridUrl);

		// 2) Create a list of all available citation output filters.
		$router =& $request->getRouter();
		$journal =& $router->getContext($request);
		$filterDao =& DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$metaCitationOutputFilterObjects =& $filterDao->getObjectsByGroup('nlm30-element-citation=>plaintext', $journal->getId());
		foreach($metaCitationOutputFilterObjects as $metaCitationOutputFilterObject) {
			$metaCitationOutputFilters[$metaCitationOutputFilterObject->getId()] = $metaCitationOutputFilterObject->getDisplayName();
		}
		$templateMgr->assign_by_ref('metaCitationOutputFilters', $metaCitationOutputFilters);

		return parent::fetch($request);
	}
}

?>
