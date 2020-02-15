/**
 * @file cypress/tests/data/60-content/JmwandengaSubmission.spec.js
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		var title = 'Signalling Theory Dividends: A Review Of The Literature And Empirical Evidence';
		cy.register({
			'username': 'jmwandenga',
			'givenName': 'John',
			'familyName': 'Mwandenga',
			'affiliation': 'University of Cape Town',
			'country': 'South Africa',
		});

		cy.createSubmission({
			'section': 'Preprints',
			title,
			'abstract': 'The signaling theory suggests that dividends signal future prospects of a firm. However, recent empirical evidence from the US and the Uk does not offer a conclusive evidence on this issue. There are conflicting policy implications among financial economists so much that there is no practical dividend policy guidance to management, existing and potential investors in shareholding. Since corporate investment, financing and distribution decisions are a continuous function of management, the dividend decisions seem to rely on intuitive evaluation.',
		});

		cy.logout();
		cy.findSubmissionAsEditor('dbarnes', null, title);
		cy.get('ul.pkp_workflow_decisions button:contains("Schedule For Publication")').click();
		cy.get('div.pkpPublication button:contains("Schedule For Publication"):visible').click();
		cy.get('div:contains("All publication requirements have been met. Are you sure you want to publish this?")');
		cy.get('button:contains("Publish")').click();
	});
});
