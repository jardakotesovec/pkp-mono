/**
 * @file cypress/support/commands.js
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 */

import 'cypress-file-upload';
import 'cypress-wait-until';

Cypress.Commands.add('install', function() {
	cy.visit('/');

	// Administrator information
	cy.get('input[name=adminUsername]').type('admin', {delay: 0});
	cy.get('input[name=adminPassword]').type('admin', {delay: 0});
	cy.get('input[name=adminPassword2]').type('admin', {delay: 0});
	cy.get('input[name=adminEmail]').type('pkpadmin@mailinator.com', {delay: 0});

	// Database configuration
	cy.get('select[name=databaseDriver]').select(Cypress.env('DBTYPE'));
	cy.get('input[id^=databaseHost-]').clear().type(Cypress.env('DBHOST'), {delay: 0});
	cy.get('input[id^=databasePassword-]').clear().type(Cypress.env('DBPASSWORD'), {delay: 0});
	cy.get('input[id^=databaseUsername-]').clear().type(Cypress.env('DBUSERNAME'), {delay: 0});
	cy.get('input[id^=databaseName-]').clear().type(Cypress.env('DBNAME'), {delay: 0});
	cy.get('input[id=createDatabase]').uncheck();
	cy.get('select[id=connectionCharset]').select('Unicode (UTF-8)');

	// Files directory
	cy.get('input[id^=filesDir-]').clear().type(Cypress.env('FILESDIR'), {delay: 0});

	// Locale configuration
	cy.get('input[id=additionalLocales-en_US').check();
	cy.get('input[id=additionalLocales-fr_CA').check();

	// Complete the installation
	cy.get('button[id^=submitFormButton-]', {timeout: 90000}).click();
});

Cypress.Commands.add('login', (username, password, context) => {
	context = context || 'index';
	password = password || (username + username);
	cy.visit('index.php/' + context + '/login/signIn', {
		method: 'POST',
		body: {username: username, password: password}
	});
});

Cypress.Commands.add('logout', function() {
	cy.visit('index.php/index/login/signOut');
});

Cypress.Commands.add('resetPassword', (username,oldPassword,newPassword) => {
	oldPassword = oldPassword || (username + username);
	newPassword = newPassword || oldPassword;
	cy.get('input[name=oldPassword]').type(oldPassword, {delay: 0});
	cy.get('input[name=password]').type(newPassword, {delay: 0});
	cy.get('input[name=password2]').type(newPassword, {delay: 0});
	cy.get('button').contains('OK').click();
});

Cypress.Commands.add('register', data => {
	if (!('email' in data)) data.email = data.username + '@mailinator.com';
	if (!('password' in data)) data.password = data.username + data.username;
	if (!('password2' in data)) data.password2 = data.username + data.username;

	cy.visit('');
	cy.get('a').contains('Register').click();
	cy.get('input[id=givenName]').type(data.givenName, {delay: 0});
	cy.get('input[id=familyName]').type(data.familyName, {delay: 0});
	cy.get('input[id=affiliation]').type(data.affiliation, {delay: 0});
	cy.get('select[id=country]').select(data.country);
	cy.get('input[id=email]').type(data.email, {delay: 0});
	cy.get('input[id=username]').type(data.username, {delay: 0});
	cy.get('input[id=password]').type(data.password, {delay: 0});
	cy.get('input[id=password2]').type(data.password2, {delay: 0});

	cy.get('input[name=privacyConsent]').click();
	cy.get('button').contains('Register').click();
});

Cypress.Commands.add('createSubmission', (data, context) => {
	// Initialize some data defaults before starting
	if (data.type == 'editedVolume' && !('files' in data)) {
		data.files = [];
		// Edited volumes should default to a single file per chapter, named after it.
		data.chapters.forEach((chapter, index) => {
			data.files.push({
				'file': 'dummy.pdf',
				'fileName': chapter.title.substring(0, 40) + '.pdf',
				'fileTitle': chapter.title,
				'genre': 'Chapter Manuscript'
			});
			data.chapters[index].files = [chapter.title];
		});
	}
	if (!('files' in data)) data.files = [{
		'file': 'dummy.pdf',
		'fileName': data.title + '.pdf',
		'fileTitle': data.title,
		'genre': Cypress.env('defaultGenre')
	}];
	if (!('keywords' in data)) data.keywords = [];
	if (!('additionalAuthors' in data)) data.additionalAuthors = [];
	if ('series' in data) data.section = data.series; // OMP compatible
	// If 'additionalFiles' is specified, it's to be used to augment the default
	// set, rather than overriding it (as using 'files' would do). Add the arrays.
	if ('additionalFiles' in data) {
		data.files = data.files.concat(data.additionalFiles);
	}

	cy.get('a:contains("Make a New Submission"), div#myQueue a:contains("New Submission")').click();

	// === Submission Step 1 ===
	if ('section' in data) cy.get('select[id="sectionId"],select[id="seriesId"]').select(data.section);
	cy.get('input[id^="checklist-"]').click({multiple: true});
	switch (data.type) { // Only relevant to OMP
		case 'monograph':
			cy.get('input[id="isEditedVolume-0"]').click();
			break;
		case 'editedVolume':
			cy.get('input[id="isEditedVolume-1"]').click();
			break;
	}
	cy.get('input[id=privacyConsent]').click();
	if ('submitterRole' in data) {
		cy.get('input[name=userGroupId]').parent().contains(data.submitterRole).click();
	} else cy.get('input[id=userGroupId]').click();
	cy.get('button.submitFormButton').click();

	// === Submission Step 2 ===
	// File uploads
	var firstFile = true;
	data.files.forEach(file => {
		if (!firstFile) cy.get('a[id^="component-grid-"][id*="-add"]').click();
		cy.wait(2000); // Avoid occasional failure due to form init taking time
		cy.get('select[id=genreId]').select(file.genre);
		cy.fixture(file.file, 'base64').then(fileContent => {
			cy.get('input[type=file]').upload(
				{fileContent, 'fileName': file.fileName, 'mimeType': 'application/pdf', 'encoding': 'base64'}
			);
		});
		cy.get('button').contains('Continue').click();
		for (const field in file.metadata) {
			cy.get('input[id^="' + Cypress.$.escapeSelector(field) + '"]:visible,textarea[id^="' + Cypress.$.escapeSelector(field) + '"]').type(file.metadata[field], {delay: 0});
			cy.get('a:contains("2. Review Details")').click(); // Close potential multilingual pop-over
		}
		cy.get('button').contains('Continue').click();
		cy.get('button').contains('Complete').click();
		firstFile = false;
	});

	if (firstFile) {
		// There were no files; close the automatically-opened upload form.
		cy.get('a.pkpModalCloseButton').click();
	}

	cy.get('button').contains('Save and continue').click();

	// === Submission Step 3 ===
	// Metadata fields
	cy.get('input[id^="title-en_US-"').type(data.title, {delay: 0});
	cy.get('label').contains('Title').click(); // Close multilingual popover
	cy.get('textarea[id^="abstract-en_US"]').then(node => {
		cy.setTinyMceContent(node.attr('id'), data.abstract);
	});
	cy.get('ul[id^="en_US-keywords-"]').then(node => {
		data.keywords.forEach(keyword => {
			node.tagit('createTag', keyword);
		});
	});
	data.additionalAuthors.forEach(author => {
		if (!('role' in author)) author.role = 'Author';
		cy.get('a[id^="component-grid-users-author-authorgrid-addAuthor-button-"]').click();
		cy.wait(250);
		cy.get('input[id^="givenName-en_US-"]').type(author.givenName, {delay: 0});
		cy.get('input[id^="familyName-en_US-"]').type(author.familyName, {delay: 0});
		cy.get('select[id=country]').select(author.country);
		cy.get('input[id^="email"]').type(author.email, {delay: 0});
		if ('affiliation' in author) cy.get('input[id^="affiliation-en_US-"]').type(author.affiliation, {delay: 0});
		cy.get('label').contains(author.role).click();
		cy.get('form#editAuthor').find('button:contains("Save")').click();
		cy.get('div[id^="component-grid-users-author-authorgrid-"] span.label:contains("' + Cypress.$.escapeSelector(author.givenName + ' ' + author.familyName) + '")');
	});
	// Chapters (OMP only)
	if ('chapters' in data) data.chapters.forEach(chapter => {
		cy.get('a[id^="component-grid-users-chapter-chaptergrid-addChapter-button-"]:visible').click();
		cy.wait(2000); // Avoid occasional failure due to form init taking time

		// Contributors
		chapter.contributors.forEach(contributor => {
			cy.get('form[id="editChapterForm"] label:contains("' + Cypress.$.escapeSelector(contributor) + '")').click();
		});

		// Title/subtitle
		cy.get('form[id="editChapterForm"] input[id^="title-en_US-"]').type(chapter.title, {delay: 0});
		if ('subtitle' in chapter) {
			cy.get('form[id="editChapterForm"] input[id^="subtitle-en_US-"]').type(chapter.subtitle, {delay: 0});
		}
		cy.get('div.pkp_modal_panel div:contains("Add Chapter")').click(); // FIXME: Resolve focus problem on title field

		cy.flushNotifications();
		cy.get('form[id="editChapterForm"] button:contains("Save")').click();
		cy.get('div:contains("Your changes have been saved.")');

		// Files
		if ('files' in chapter) {
			cy.get('div[id="chaptersGridContainer"] a:contains("' + Cypress.$.escapeSelector(chapter.title) + '")').click();
			chapter.files.forEach(file => {
				cy.get('form[id="editChapterForm"] label:contains("' + Cypress.$.escapeSelector(chapter.title.substring(0, 40)) + '")').click();
			});
			cy.flushNotifications();
			cy.get('form[id="editChapterForm"] button:contains("Save")').click();
			cy.get('div:contains("Your changes have been saved.")');
			cy.waitJQuery(); // Wait for grid to reload.
		}

		cy.get('div[id^="component-grid-users-chapter-chaptergrid-"] a[title="Edit this chapter"]:contains("' + Cypress.$.escapeSelector(chapter.title) + '")');
	});
	cy.get('form[id=submitStep3Form]').find('button').contains('Save and continue').click();

	// === Submission Step 4 ===
	cy.get('form[id=submitStep4Form]').find('button').contains('Finish Submission').click();
	cy.get('button.pkpModalConfirmButton').click();
	cy.get('h2').contains('Submission complete');
});

Cypress.Commands.add('findSubmissionAsEditor', (username, password, title, context) => {
	context = context || 'publicknowledge';
	cy.login(username, password, context);
	cy.get('button[id="active-button"]').click();
	// Get the <a> above the title-containing div
	cy.get('div[id=active]').find('div').contains(title).parent().parent().click();
});

Cypress.Commands.add('sendToReview', (toStage, fromStage) => {
	if (!toStage) toStage = 'External';
	cy.get('*[id^=' + toStage.toLowerCase() + 'Review-button-]').click();
	if (fromStage == "Internal") {
		cy.get('form[id="promote"] button:contains("Next:")').click();
		cy.get('button:contains("Record Editorial Decision")').click();
	} else {
		cy.get('form[id="initiateReview"] button:contains("Send")').click();
	}
	cy.get('span.description:contains("Waiting for reviewers")');
});

Cypress.Commands.add('assignParticipant', (role, name, recommendOnly) => {
	var names = name.split(' ');
	cy.get('a[id^="component-grid-users-stageparticipant-stageparticipantgrid-requestAccount-button-"]:visible').click();
	cy.get('select[name=filterUserGroupId').select(role);
	cy.get('input[id^="namegrid-users-userselect-userselectgrid-"]').type(names[1], {delay: 0});
	cy.get('form[id="searchUserFilter-grid-users-userselect-userselectgrid"]').find('button[id^="submitFormButton-"]').click();
	cy.get('input[name="userId"]').click(); // Assume only one user results from the search.
	if (recommendOnly) cy.get('input[name="recommendOnly"]').click();
	cy.flushNotifications();
	cy.get('button').contains('OK').click();
	cy.waitJQuery();
});

Cypress.Commands.add('recordEditorialRecommendation', recommendation => {
	cy.get('a[id^="recommendation-button-"]').click();
	cy.get('select[id=recommendation]').select(recommendation);
	cy.get('button').contains('Record Editorial Recommendation').click();
	cy.get('div').contains('Recommendation:');
});

Cypress.Commands.add('assignReviewer', name => {
	cy.get('a[id^="component-grid-users-reviewer-reviewergrid-addReviewer-button-"]').click();
	cy.get('fieldset.pkpListPanel--selectReviewer input.pkpSearch__input', {timeout: 20000}).type(name, {delay: 0});
	cy.get('div.pkpListPanelItem--reviewer__fullName:contains(' + Cypress.$.escapeSelector(name) + ')').click();
	cy.get('button[id="selectReviewerButton"]').click();
	cy.flushNotifications();
	cy.get('button:contains("Add Reviewer")').click();
	cy.get('div:contains("' + Cypress.$.escapeSelector(name) + ' was assigned to review")');
});

Cypress.Commands.add('recordEditorialDecision', decision => {
	cy.get('ul.pkp_workflow_decisions:visible a:contains("' + Cypress.$.escapeSelector(decision) + '")', {timeout: 30000}).click();
	if (decision != 'Request Revisions' && decision != 'Decline Submission') {
		cy.get('button:contains("Next:")').click();
	}
	cy.get('button:contains("Record Editorial Decision")').click();
});

Cypress.Commands.add('performReview', (username, password, title, recommendation, comments, context) => {
	context = context || 'publicknowledge';
	comments = comments || 'Here are my review comments';
	cy.login(username, password, context);
	cy.get('div[id=myQueue]').find('div').contains(title).parent().parent().click();
	cy.get('input[id="privacyConsent"]').click();
	cy.get('button:contains("Accept Review, Continue to Step #2")').click();
	cy.get('button:contains("Continue to Step #3")').click();
	cy.wait(2000); // Give TinyMCE control time to load
	cy.get('textarea[id^="comments-"]').then(node => {
		cy.setTinyMceContent(node.attr('id'), comments);
	});
	if (recommendation) {
		cy.get('select#recommendation').select(recommendation);
	}
	cy.get('button:contains("Submit Review")').click();
	cy.get('button:contains("OK")').click();
	cy.get('h2:contains("Review Submitted")');
	cy.logout();
});

Cypress.Commands.add('createUser', user => {
	if (!('email' in user)) user.email = user.username + '@mailinator.com';
	if (!('password' in user)) user.password = user.username + user.username;
	if (!('password2' in user)) user.password2 = user.username + user.username;
	if (!('roles' in user)) user.roles = [];
	cy.get('div[id=userGridContainer] a:contains("Add User")').click();
	cy.wait(2000); // Avoid occasional glitches with given name field
	cy.get('input[id^="givenName-en_US"]').type(user.givenName, {delay: 0});
	cy.get('input[id^="familyName-en_US"]').type(user.familyName, {delay: 0});
	cy.get('input[name=email]').type(user.email, {delay: 0});
	cy.get('input[name=username]').type(user.username, {delay: 0});
	cy.get('input[name=password]').type(user.password, {delay: 0});
	cy.get('input[name=password2]').type(user.password2, {delay: 0});
	if (!user.mustChangePassword) {
		cy.get('input[name="mustChangePassword"]').click();
	}
	cy.get('select[name=country]').select(user.country);
	cy.contains('More User Details').click();
	cy.get('span:contains("Less User Details"):visible');
	cy.get('input[id^="affiliation-en_US"]').type(user.affiliation, {delay: 0});
	cy.get('form[id=userDetailsForm]').find('button[id^=submitFormButton]').click();
	user.roles.forEach(role => {
		cy.get('form[id=userRoleForm]').contains(role).click();
	});
	cy.get('form[id=userRoleForm] button[id^=submitFormButton]').click();
	cy.get('span[id$="-username"]:contains("' + Cypress.$.escapeSelector(user.username) + '")');
	cy.scrollTo('topLeft');
});

Cypress.Commands.add('flushNotifications', function() {
	cy.window().then(win => {
		if (typeof PNotify !== 'undefined') {
			PNotify.removeAll();
		}
	});
});

Cypress.Commands.add('waitJQuery', function() {
	cy.waitUntil(() => cy.window().then(win => win.jQuery.active == 0));
});
