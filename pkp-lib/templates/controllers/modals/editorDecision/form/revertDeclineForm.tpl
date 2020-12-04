{**
 * templates/controllers/modals/editorDecision/form/revertDeclineForm.tpl
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Form used revert a decline decision
 *
 *}
<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#revertDecline').pkpHandler('$.pkp.controllers.form.AjaxFormHandler', null);
	{rdelim});
</script>

<form class="pkp_form" id="revertDecline" method="post" action="{url op="saveRevertDecline"}" >
	{csrf}
	<input type="hidden" name="submissionId" value="{$submissionId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />
	<input type="hidden" name="decision" value="{$decision|escape}" />
	{fbvFormButtons submitText="editor.submission.decision.revertDecline"}
</form>