// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue';
import GlobalMixins from '@/mixins/global.js';
import App from '@/docs/App';
import router from '@/docs/router';

Vue.mixin(GlobalMixins);

Vue.config.productionTip = false;

window.pkp = {
	eventBus: new Vue(),
	/**
	 * Dummy global function used by components
	 */
	userHasRole: function (role) {
		return true;
	},
	/**
	 * Dummy constants required by components
	 */
	const: {
		'WORKFLOW_STAGE_ID_SUBMISSION': 1,
		'WORKFLOW_STAGE_ID_INTERNAL_REVIEW': 2,
		'WORKFLOW_STAGE_ID_EXTERNAL_REVIEW': 3,
		'WORKFLOW_STAGE_ID_EDITING': 4,
		'WORKFLOW_STAGE_ID_PRODUCTION': 5,
		'STAGE_STATUS_SUBMISSION_UNASSIGNED': 1,
		'REVIEW_ROUND_STATUS_PENDING_REVIEWERS': 6,
		'REVIEW_ROUND_STATUS_REVIEWS_READY': 8,
		'REVIEW_ROUND_STATUS_REVIEWS_COMPLETED': 9,
		'REVIEW_ROUND_STATUS_REVIEWS_OVERDUE': 10,
		'REVIEW_ROUND_STATUS_REVISIONS_SUBMITTED': 11,
		'REVIEW_ROUND_STATUS_REVISIONS_REQUESTED': 1,
		'REVIEW_ASSIGNMENT_STATUS_AWAITING_RESPONSE': 0,
		'REVIEW_ASSIGNMENT_STATUS_RESPONSE_OVERDUE': 4,
		'REVIEW_ASSIGNMENT_STATUS_REVIEW_OVERDUE': 6,
		'REVIEW_ASSIGNMENT_STATUS_ACCEPTED': 5,
		'REVIEW_ASSIGNMENT_STATUS_RECEIVED': 7,
		'REVIEW_ASSIGNMENT_STATUS_COMPLETE': 8,
		'REVIEW_ASSIGNMENT_STATUS_THANKED': 9,
		'ASSOC_TYPE_PRESS': 512,
		'ASSOC_TYPE_CATEGORY': 525,
		'ASSOC_TYPE_SECTION': 530,
		'ASSOC_TYPE_SERIES': 530, // OMP - always matches ASSOC_TYPE_SECTION
	},
};

/**
 * Spoof some of the variables in the $.pkp object from the app
 */
window.$.pkp = {
	app: {
		currentLocale: 'en_US',
		primaryLocale: 'en_US',
	},
};

/* eslint-disable no-new */
new Vue({
	el: '#app',
	router,
	template: '<App/>',
	components: { App },
});
