{**
 * reviewForms.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display list of unpublished review forms in journal management.
 *
 *}
{strip}
{assign var="pageTitle" value="manager.reviewForms"}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
{literal}
$(document).ready(function() { setupTableDND("#dragTable", "moveReviewForm"); });
{/literal}
</script>

<p>{translate key="manager.reviewForms.list.description"}</p>

<div id="reviewForms">
<table width="100%" class="listing" id="dragTable">
	<tr>
		<td class="headseparator" colspan="4">&nbsp;</td>
	</tr>
	<tr class="heading" valign="bottom">
		<td width="40%">{translate key="manager.reviewForms.title"}</td>
		<td width="20%">{translate key="manager.reviewForms.inReview"}</td>
		<td width="15%">{translate key="manager.reviewForms.completed"}</td>
		<td width="25%" align="right">{translate key="common.action"}</td>
	</tr>
	<tr>
		<td class="headseparator" colspan="4">&nbsp;</td>
	</tr>
{iterate from=reviewForms item=reviewForm name=reviewForms}
{if $reviewForm->getCompleteCount() == 0 && $reviewForm->getIncompleteCount() == 0}
	{assign var=canEdit value=1}
{else}
	{assign var=canEdit value=0}
{/if}
	<tr valign="top" id="reviewform-{$reviewForm->getReviewFormId()}" class="data">
		<td class="drag">{$reviewForm->getReviewFormTitle()|escape}</td>
		<td class="drag">{$reviewForm->getIncompleteCount()|escape}</td>
		<td class="drag">{$reviewForm->getCompleteCount()|escape}</td>
		<td align="right" class="nowrap">
			{if $canEdit}<a href="{url op="editReviewForm" path=$reviewForm->getReviewFormId()}" class="action">{translate key="common.edit"}</a>&nbsp;|{/if}
			{strip}
				{if $reviewForm->getActive()}
					<a href="{url op="deactivateReviewForm" path=$reviewForm->getReviewFormId()}" class="action">{translate key="common.deactivate"}</a>
				{else}
					<a href="{url op="activateReviewForm" path=$reviewForm->getReviewFormId()}" class="action">{translate key="common.activate"}</a>
				{/if}
				&nbsp;|
			{/strip}
			{if !$canEdit}<a href="{url op="copyReviewForm" path=$reviewForm->getReviewFormId()}" class="action">{translate key="common.copy"}</a>&nbsp;|{/if}
			<a href="{url op="previewReviewForm" path=$reviewForm->getReviewFormId()}" class="action">{translate key="common.preview"}</a>&nbsp;|
			{if $canEdit}<a href="{url op="deleteReviewForm" path=$reviewForm->getReviewFormId()}" onclick="return confirm('{translate|escape:"jsparam" key="manager.reviewForms.confirmDeleteUnpublished"}')" class="action">{translate key="common.delete"}</a>&nbsp;|{/if}
			<a href="{url op="moveReviewForm" d=u id=$reviewForm->getReviewFormId()}" class="action">&uarr;</a>&nbsp;<a href="{url op="moveReviewForm" d=d id=$reviewForm->getReviewFormId()}" class="action">&darr;</a>
		</td>
	</tr>
{/iterate}
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>

{if $reviewForms->wasEmpty()}
	<tr>
		<td colspan="4" class="nodata">{translate key="manager.reviewForms.noneCreated"}</td>
	</tr>
	<tr>
		<td colspan="4" class="endseparator">&nbsp;</td>
	</tr>
{else}
	<tr>
		<td colspan="2" align="left">{page_info iterator=$reviewForms}</td>
		<td colspan="2" align="right">{page_links anchor="reviewForms" name="reviewForms" iterator=$reviewForms}</td>
	</tr>
{/if}

</table>

<a class="action" href="{url op="createReviewForm"}">{translate key="manager.reviewForms.create"}</a>
</div>
{include file="common/footer.tpl"}
