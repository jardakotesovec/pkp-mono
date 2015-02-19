{**
 * templates/workflow/galleyTabs.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Production workflow stage tabs.
 *}

<script type="text/javascript">
// Attach the JS file tab handler.
$(function() {ldelim}
	$('#galleyTabs').pkpHandler(
		'$.pkp.controllers.tab.galley.GalleysTabHandler',
		{ldelim}
			tabsUrl:'{url|escape:javascript router=$smarty.const.ROUTE_PAGE
				op='galleysTab'
				submissionId=$submission->getId()
				stageId=$smarty.const.WORKFLOW_STAGE_ID_PRODUCTION escape=false}',
			{if $currentGalleyTabId}currentGalleyTabId: '{$currentGalleyTabId}',{/if}
			emptyLastTab: true,
		{rdelim}
	);
{rdelim});
</script>
<div id="galleyTabs">
	<ul>
		{foreach from=$galleys item=galley}
			<li>
				<a id="galley{$galley->getId()|escape}"
					href="{url router=$smarty.const.ROUTE_PAGE page="workflow" op="fetchGalley"
					articleGalleyId=$galley->getId()
					submissionId=$galley->getSubmissionId()
					stageId=$smarty.const.WORKFLOW_STAGE_ID_PRODUCTION}">{$galley->getLabel()|escape}</a>
			</li>
		{/foreach}
	</ul>
</div>

