/**
 * @file cypress/tests/data/60-content/MforanSubmission.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup tests_data
 *
 * @brief Data build suite: Create submission
 */

describe('Data suite tests', function() {
	it('Create a submission', function() {
		cy.register({
			'username': 'mforan',
			'givenName': 'Max',
			'familyName': 'Foran',
			'affiliation': 'University of Calgary',
			'country': 'Canada'
		});

		var submission = {
			'type': 'monograph',
			'title': 'Expansive Discourses: Urban Sprawl in Calgary, 1945-1978',
			'abstract': 'A groundbreaking study of urban sprawl in Calgary after the Second World War. The interactions of land developers and the local government influenced how the pattern grew: developers met market demands and optimized profits by building houses as efficiently as possible, while the City had to consider wider planning constraints and infrastructure costs. Foran examines the complexity of their interactions from a historical perspective, why each party acted as it did, and where each can be criticized.',
			'submitterRole': 'Author',
			'chapters': [
				{
					'title': 'Setting the Stage',
					'contributors': ['Max Foran']
				},
				{
					'title': 'Going It Alone, 1945-1954',
					'contributors': ['Max Foran']
				},
				{
					'title': 'Establishing the Pattern, 1955-1962',
					'contributors': ['Max Foran']
				},
			],
		};
		cy.createSubmission(submission);
		cy.logout();

		cy.findSubmissionAsEditor('dbarnes', null, 'Foran');
		cy.clickDecision('Send to External Review');
		cy.recordDecisionSendToReview('Send to External Review', ['Max Foran'], [submission.title]);
		cy.isActiveStageTab('External Review');
	});
});
