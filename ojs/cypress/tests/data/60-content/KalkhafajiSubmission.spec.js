/**
 * @file cypress/tests/data/60-content/KalkhafajiSubmission.spec.js
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 */

describe('Data suite tests', function() {

	var title = 'Learning Sustainable Design through Service';
	var submission = {
		'section': 'Articles',
		sectionId: 1,
		'title': title,
		'abstract': 'Environmental sustainability and sustainable development principles are vital topics that engineering education has largely failed to address. Service-learning, which integrates social service into an academic setting, is an emerging tool that can be leveraged to teach sustainable design to future engineers. We present a model of using service-learning to teach sustainable design based on the experiences of the Stanford chapter of Engineers for a Sustainable World. The model involves the identification of projects and partner organizations, a student led, project-based design course, and internships coordinated with partner organizations. The model has been very successful, although limitations and challenges exist. These are discussed along with future directions for expanding the model.',
		'keywords': [
			'Development',
			'engineering education',
			'service learning',
			'sustainability',
		],
		'additionalAuthors': [
			{
				givenName: {en_US: 'Margaret'},
				familyName: {en_US: 'Morse'},
				affiliation: {en_US: 'Stanford University'},
				email: 'mmorse@mailinator.com',
				country: 'US',
				userGroupId: Cypress.env('authorUserGroupId')
			}
		],
		files: [
			{
				'file': 'dummy.pdf',
				'fileName': title + '.pdf',
				'mimeType': 'application/pdf',
				'genre': Cypress.env('defaultGenre')
			}
		]
	};

	it('Create a submission', function() {
		cy.register({
			'username': 'kalkhafaji',
			'givenName': 'Karim',
			'familyName': 'Al-Khafaji',
			'affiliation': 'Stanford University',
			'country': 'United States',
		});

		// Go to page where CSRF token is available
		cy.visit('/index.php/publicknowledge/user/profile');

		let csrfToken = '';
		cy.window()
			.then((win) => {
				csrfToken = win.pkp.currentUser.csrfToken;
			})
			.then(() => {
				return cy.createSubmissionWithApi(submission, csrfToken);
			})
			.then(xhr => {
				return cy.submitSubmissionWithApi(submission.id, csrfToken);
			});
	});
});
