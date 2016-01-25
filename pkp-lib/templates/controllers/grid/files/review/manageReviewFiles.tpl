{**
 * controllers/grid/files/review/manageReviewFiles.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Allows editor to add more file to the review (that weren't added when the submission was sent to review)
 *}


<!-- Current review files -->
<p class="pkp_help">{translate key="editor.submission.review.manageReviewFilesDescription"}

<div id="existingFilesContainer">
	<script type="text/javascript">
		$(function() {ldelim}
			// Attach the form handler.
			$('#manageReviewFilesForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		{rdelim});
	</script>
	<form class="pkp_form" id="manageReviewFilesForm" action="{url component="grid.files.review.ManageReviewFilesGridHandler" op="updateReviewFiles" submissionId=$submissionId|escape stageId=$stageId|escape reviewRoundId=$reviewRoundId|escape}" method="post">
		<!-- Available submission files -->
		{url|assign:availableReviewFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.ManageReviewFilesGridHandler" op="fetchGrid" submissionId=$submissionId stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
		{load_url_in_div id="availableReviewFilesGrid" url=$availableReviewFilesGridUrl}
		{fbvFormButtons}
	</form>
</div>

