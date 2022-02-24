/**
 * @file cypress/tests/data/60-content/MallySubmission.spec.js
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
			'username': 'mally',
			'givenName': 'Mohamed',
			'familyName': 'Ally',
			'affiliation': 'Athabasca University',
			'country': 'Canada'
		});

		var submission = {
			'type': 'editedVolume',
			'title': 'Mobile Learning: Transforming the Delivery of Education and Training',
			'abstract': 'This collection is for anyone interested in the use of mobile technology for various distance learning applications. Readers will discover how to design learning materials for delivery on mobile technology and become familiar with the best practices of other educators, trainers, and researchers in the field, as well as the most recent initiatives in mobile learning research. Businesses and governments can learn how to deliver timely information to staff using mobile devices. Professors can use this book as a textbook for courses on distance education, mobile learning, and educational technology.',
			'keywords': [
				'Educational Technology'
			],
			'submitterRole': 'Volume editor',
			'additionalAuthors': [
				{
					'givenName': 'John',
					'familyName': 'Traxler',
					'country': 'United Kingdom',
					// 'affiliation': '',
					'email': 'jtraxler@mailinator.com',
				},
				{
					'givenName': 'Marguerite',
					'familyName': 'Koole',
					'country': 'Canada',
					// 'affiliation': '',
					'email': 'mkoole@mailinator.com',
				},
				{
					'givenName': 'Torstein',
					'familyName': 'Rekkedal',
					'country': 'Norway',
					// 'affiliation': '',
					'email': 'trekkedal@mailinator.com',
				},
			],
			'chapters': [
				{
					'title': 'Current State of Mobile Learning',
					'contributors': ['John Traxler'],
				},
				{
					'title': 'A Model for Framing Mobile Learning',
					'contributors': ['Marguerite Koole']
				},
				{
					'title': 'Mobile Distance Learning with PDAs: Development and Testing of Pedagogical and System Solutions Supporting Mobile Distance Learners',
					'contributors': ['Torstein Rekkedal']
				}
			],
		};
		cy.createSubmission(submission);
		cy.logout();

		cy.findSubmissionAsEditor('dbarnes', null, 'Ally');

		// Internal review
		cy.clickDecision('Send to Internal Review');
		cy.recordDecisionSendToReview('Send to Internal Review', ['Mohamed Ally'], submission.chapters.map(chapter => chapter.title.substring(0, 35)));
		cy.isActiveStageTab('Internal Review');
		cy.assignReviewer('Paul Hudson');

		// External review
		cy.clickDecision('Send to External Review');
		cy.recordDecisionSendToReview('Send to External Review', ['Mohamed Ally'], []);
		cy.isActiveStageTab('External Review');
		cy.assignReviewer('Adela Gallego');
		cy.assignReviewer('Al Zacharia');
		cy.assignReviewer('Gonzalo Favio');

		cy.logout();

		// Perform reviews
		cy.performReview('agallego', null, submission.title, null, 'I recommend requiring revisions.');
		cy.performReview('gfavio', null, submission.title, null, 'I recommend resubmitting.');

		// Accept submission
		cy.findSubmissionAsEditor('dbarnes', null, 'Ally');
		cy.clickDecision('Accept Submission');
		cy.recordDecisionAcceptSubmission(['Mohamed Ally'], ['Adela Gallego', 'Gonzalo Favio'], []);
		cy.isActiveStageTab('Copyediting');
	});
});
