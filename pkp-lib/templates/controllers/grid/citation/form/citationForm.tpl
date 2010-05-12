{**
 * citationForm.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Citation grid form
 *}


<div id="editCitationFormContainer-{$citation->getId()}">
	<form name="editCitationForm" id="editCitationForm" method="post" action="{url op="updateCitation"}" >
		<h3>{translate key="submission.citations.form.title"}</h3>

		<p>{translate key="submission.citations.form.description"}</p>
		{if $unsavedChanges}
			<p><span class="formError">{translate key="submission.citations.form.unsavedChanges"}</span></p>
		{/if}

		{include file="common/formErrors.tpl"}

		<span class="options">
			{include file="controllers/grid/gridAction.tpl" action=$checkAction id="editCitationFormContainer-"|concat:$citation->getId()}
		</span>

		<table width="100%" class="data">
			<tr valign="top">
				<td width="30%" class="label">{fieldLabel name="editedCitation" key="submission.citations.grid.editedCitation"}</td>
				<td width="70%" class="value">{fbvElement type="textarea" name="editedCitation" id="editedCitation" size=$fbvStyles.size.LARGE value=$editedCitation}</td>
			</tr>
		</table>

		<script type='text/javascript'>
			$(function() {ldelim}
				$.ajaxSetup({ldelim}cache:false{rdelim});
				$("#citationFormTab-{$tabUid}").tabs({ldelim} cache: false {rdelim});
			{rdelim});
		</script>

		<div id="citationFormTab-{$tabUid}">
			<ul>
				{* Tabs that contain editable fields *}
				{foreach from=$citationFormTabs key=citationFormTabName item=varsArray}
					<li><a href="#{$citationFormTabName|escape|regex_replace:"/\s*/":""}-{$tabUid}">{$citationFormTabName|escape}</a></li>
				{/foreach}
				
				{* Tabs that contain source data *}
				{foreach from=$citationSourceTabs key=citationSourceTabId item=citationSourceTab}
					<li><a href="#{$citationSourceTabId}-{$tabUid}">{$citationSourceTab.displayName|escape}</a></li>
				{/foreach}
			</ul>
			
			{* Tab content for tabs that contain editable fields *}
			{foreach from=$citationFormTabs key=citationFormTabName item=varsArray}
				<div id="{$citationFormTabName|escape|regex_replace:"/\s*/":""}-{$tabUid}">
					<table>
						<tr valign="top">
							<td width="30%" class="label">{fieldLabel name="fieldNames" key="submission.citations.grid.fields"}</td>
							<td width="70%" class="value">{fieldLabel name="fieldValues" key="submission.citations.grid.values"}</td>
						</tr>
						{foreach from=$varsArray key=fieldName item=fieldDisplayName}
							<tr valign="top">
								<td width="30%" class="label">{fieldLabel name=$fieldName key=$fieldDisplayName}</td>
								{capture assign=fieldValueVar}{ldelim}${$fieldName}|escape{rdelim}{/capture}
								{eval|assign:"fieldValue" var=$fieldValueVar}
								<td width="70%" class="value">{fbvElement type="text" name=$fieldName id=$fieldName size=$fbvStyles.size.SMALL maxlength="250" value=$fieldValue"}</td>
							</tr>
						{/foreach}
					</table>
				</div>
			{/foreach}
			
			{* Tab content for tabs that contain source data *}
			{foreach from=$citationSourceTabs key=citationSourceTabId item=citationSourceTab}
				<div id="{$citationSourceTabId}-{$tabUid}">
					<table>
						{foreach from=$citationSourceTab.statements key=sourcePropertyId item=sourceStatement}
							<tr valign="top">
								<td width="30%" class="label">{translate key=$sourceStatement.displayName}</td>
								<td width="70%" class="value">{$sourceStatement.value|escape}</td>
							</tr>
						{/foreach}
					</table>
				</div>
			{/foreach}
		</div>

		<input type="hidden" name="assocId" value="{$citation->getAssocId()|escape}" />
		{if $citation->getId()}
			<input type="hidden" name="citationId" value="{$citation->getId()|escape}" />
			<input type="hidden" name="citationState" value="{$citation->getCitationState()|escape}" />
		{/if}

		<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
		<p>{translate key="submission.citations.form.approveCitation"}{fbvCheckbox id="citationApproved" name="citationApproved" value="citationApproved" checked=$citationApproved}</p>
	</form>
</div>
