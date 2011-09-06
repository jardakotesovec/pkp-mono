{**
 * templates/workflow/reviewRound.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review round info for a particular round
 *}
{if $roundStatus}
	{include file="common/reviewRoundStatus.tpl" round=$round roundStatus=$roundStatus}
{/if}
{url|assign:reviewFileSelectionGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.EditorReviewFilesGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId round=$round escape=false}
{load_url_in_div id="reviewFileSelection" url=$reviewFileSelectionGridUrl}

{url|assign:reviewersGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.reviewer.ReviewerGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId round=$round escape=false}
{load_url_in_div id="reviewersGrid" url=$reviewersGridUrl}

{url|assign:revisionsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.ReviewRevisionsGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId round=$round escape=false}
{load_url_in_div id="revisionsGrid" url=$revisionsGridUrl}

{** editorial decision actions *}
<div class="grid_actions">
	{foreach from=$editorActions item=action}
		{include file="linkAction/linkAction.tpl" action=$action contextId="reviewTabs"}
	{/foreach}
</div>

