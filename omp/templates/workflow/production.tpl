{**
 * templates/workflow/production.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage
 *}
{strip}
{include file="workflow/header.tpl"}
{/strip}

<script type="text/javascript">
	// Initialise JS handler.
	$(function() {ldelim}
		$('#production').pkpHandler(
			'$.pkp.pages.workflow.ProductionHandler',
			{ldelim}
				formatsTabContainerSelector: '#publicationFormatTabsContainer',
				submissionProgressBarSelector: '#submissionProgressBarDiv'
			{rdelim}
		);
	{rdelim});
</script>

<div id="production">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="productionNotification" requestOptions=$productionNotificationRequestOptions}

	<p class="pkp_help">{translate key="editor.monograph.production.introduction"}</p>

	{url|assign:productionReadyFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.productionReady.ProductionReadyFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
	{load_url_in_div id="productionReadyFilesGridDiv" url=$productionReadyFilesGridUrl}

	{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR), $userRoles)}
		{fbvFormArea id="publicationFormats"}
			{fbvFormSection}
				<!--  Formats -->
				{url|assign:formatGridUrl router=$smarty.const.ROUTE_COMPONENT  component="grid.catalogEntry.PublicationFormatGridHandler" op="fetchGrid" submissionId=$submission->getId()}
				{load_url_in_div id="formatsGridContainer"|uniqid url=$formatGridUrl}
			{/fbvFormSection}
		{/fbvFormArea}
	{else}
		<h3>{translate key="submission.publicationFormats"}</h3>
	{/if}

	<div id='publicationFormatTabsContainer'>
		{include file="workflow/productionFormatsTab.tpl" formatTabsId=$formatTabsId publicationFormats=$publicationFormats}
	</div>
</div>

{include file="common/footer.tpl"}
