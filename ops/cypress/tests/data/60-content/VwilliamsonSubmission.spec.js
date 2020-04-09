/**
 * @file cypress/tests/data/60-content/VwilliamsonSubmission.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		var title = 'Self-Organization in Multi-Level Institutions in Networked Environments';
		cy.register({
			'username': 'vwilliamson',
			'givenName': 'Valerie',
			'familyName': 'Williamson',
			'affiliation': 'University of Windsor',
			'country': 'Canada',
		});

		cy.createSubmission({
			'section': 'Preprints',
			title,
			'abstract': 'We compare a setting where actors individually decide whom to sanction with a setting where sanctions are only implemented when actors collectively agree that a certain actor should be sanctioned. Collective sanctioning decisions are problematic due to the difficulty of reaching consensus. However, when a decision is made collectively, perverse sanctioning (e.g. punishing high contributors) by individual actors is ruled out. Therefore, collective sanctioning decisions are likely to be in the interest of the whole group.',
			'keywords': [
				'Self-Organization',
				'Multi-Level Institutions',
				'Goverance',
			],
		});

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, title);
		cy.get('ul.pkp_workflow_decisions button:contains("Schedule For Publication")').click();
		cy.get('div.pkpPublication button:contains("Schedule For Publication"):visible').click();
		cy.get('div:contains("All requirements have been met. Are you sure you want to publish this?")');
		cy.get('button:contains("Publish")').click();
	});
});
