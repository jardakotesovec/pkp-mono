{**
 * templates/editMiscFile.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Misc. file editor dialog
 *}
{assign var=saveFormId value="saveLocaleFile"|uniqid}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#{$saveFormId}').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>
<form id="{$saveFormId}" action="{url op="save" locale=$locale filename=$filename}" method="post" class="pkp_form">
	{* Reference area *}
	{fbvFormArea id="referenceArea-"|uniqid title="plugins.generic.translator.file.reference"}
		{fbvElement type="textarea" id="reference" readonly=true value=$referenceContents}
	{/fbvFormArea}

	{* Content area *}
	{fbvFormArea id="contentArea-"|uniqid title="plugins.generic.translator.file.translation"}
		{fbvElement type="textarea" id="fileContents" value=$fileContents}
	{/fbvFormArea}

	{* Form buttons *}
	{fbvElement type="submit" class="submitFormButton" id="submitFormButton-"|uniqid label="common.save"}
</form>
