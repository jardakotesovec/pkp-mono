{**
 * fileForm.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Files grid form
 *
 * $Id$
 *}

<script type="text/javascript">
	{literal}
	$(function() {
		$('#fileUploadTabs-{/literal}{$fileId}{literal}').tabs();
		$('#fileUploadTabs-{/literal}{$fileId}{literal}').parent().dialog('option', 'buttons', null);  // Clear out default modal buttons
	});
	{/literal}
</script>
<div id="fileUploadTabs-{$fileId}" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
	<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
		<li class="ui-state-default ui-corner-top"><a href="{url component="grid.submit.submissionFiles.SubmissionFilesGridHandler" op="displayFileForm" monographId=$monographId fileId=$fileId}">1. {translate key="author.submit.upload"}</a></li>
		<li class="ui-state-default ui-corner-top"><a href="{url component="grid.submit.submissionFiles.SubmissionFilesGridHandler" op="editMetadata" fileId=$fileId}">2. {translate key="author.submit.metadata"}</a></li>
		{if !$fileId}<li class="ui-state-default ui-corner-top"><a href="{url component="grid.submit.submissionFiles.SubmissionFilesGridHandler" op="finishFileSubmissions" fileId=$fileId}">3. {translate key="author.submit.finishingUp"}</a></li>{/if}
	</ul>

	<input type="hidden" name="monographId" value="{$monographId|escape}" />
	{if $gridId}
	<input type="hidden" name="gridId" value="{$gridId|escape}" />	
	{/if}
	{if $fileId}
	<input type="hidden" name="fileId" value="{$fileId|escape}" />
	{/if}
</div>
