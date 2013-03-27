<?php

/**
 * @defgroup pages_manager
 */

/**
 * @file pages/manager/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_manager
 * @brief Handle requests for journal management functions.
 *
 */

switch ($op) {
	//
	// People Management
	//
	case 'people':
	case 'showNoRole':
	case 'enrollSearch':
	case 'enroll':
	case 'unEnroll':
	case 'enrollSyncSelect':
	case 'enrollSync':
	case 'createUser':
	case 'suggestUsername':
	case 'mergeUsers':
	case 'disableUser':
	case 'enableUser':
	case 'removeUser':
	case 'editUser':
	case 'updateUser':
	case 'userProfile':
		define('HANDLER_CLASS', 'PeopleHandler');
		import('pages.manager.PeopleHandler');
		break;
	//
	// Section Management
	//
	case 'sections':
	case 'createSection':
	case 'editSection':
	case 'updateSection':
	case 'deleteSection':
	case 'moveSection':
		define('HANDLER_CLASS', 'SectionHandler');
		import('pages.manager.SectionHandler');
		break;
	//
	// Review Form Management
	//
	case 'reviewForms':
	case 'createReviewForm':
	case 'editReviewForm':
	case 'updateReviewForm':
	case 'previewReviewForm':
	case 'deleteReviewForm':
	case 'activateReviewForm':
	case 'deactivateReviewForm':
	case 'copyReviewForm':
	case 'moveReviewForm':
	case 'reviewFormElements':
	case 'createReviewFormElement':
	case 'editReviewFormElement':
	case 'deleteReviewFormElement':
	case 'updateReviewFormElement':
	case 'moveReviewFormElement':
	case 'copyReviewFormElement':
		define('HANDLER_CLASS', 'ReviewFormHandler');
		import('pages.manager.ReviewFormHandler');
		break;
	//
	// Files Browser
	//
	case 'files':
	case 'fileUpload':
	case 'fileMakeDir':
	case 'fileDelete':
		define('HANDLER_CLASS', 'FilesHandler');
		import('pages.manager.FilesHandler');
		break;
	//
	// Subscription Policies
	//
	case 'subscriptionPolicies':
	case 'saveSubscriptionPolicies':
	//
	// Subscription Types
	//
	case 'subscriptionTypes':
	case 'deleteSubscriptionType':
	case 'createSubscriptionType':
	case 'selectSubscriber':
	case 'editSubscriptionType':
	case 'updateSubscriptionType':
	case 'moveSubscriptionType':
	//
	// Subscriptions
	//
	case 'subscriptions':
	case 'subscriptionsSummary':
	case 'deleteSubscription':
	case 'renewSubscription':
	case 'createSubscription':
	case 'editSubscription':
	case 'updateSubscription':
		define('HANDLER_CLASS', 'SubscriptionHandler');
		import('pages.manager.SubscriptionHandler');
		break;
	//
	// Import/Export
	//
	case 'importexport':
		define('HANDLER_CLASS', 'ImportExportHandler');
		import('pages.manager.ImportExportHandler');
		break;
	//
	// Statistics Functions
	//
	case 'statistics':
	case 'saveStatisticsSettings':
	case 'savePublicStatisticsList':
	case 'report':
		define('HANDLER_CLASS', 'StatisticsHandler');
		import('pages.manager.StatisticsHandler');
		break;
	//
	// Payment
	//
	case 'payments':
	case 'savePaymentSettings':
	case 'payMethodSettings':
	case 'savePayMethodSettings':
	case 'viewPayments':
	case 'viewPayment':
		define('HANDLER_CLASS', 'ManagerPaymentHandler');
		import('pages.manager.ManagerPaymentHandler');
		break;
	case 'index':
		define('HANDLER_CLASS', 'ManagerHandler');
		import('pages.manager.ManagerHandler');
	//
	// Plugin Management
	//
	case 'plugin':
		define('HANDLER_CLASS', 'PluginHandler');
		import('lib.pkp.pages.manager.PluginHandler');
		break;
}

?>
