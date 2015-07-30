{**
 * templates/workflow/editorial.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Editorial workflow stage
 *}
{include file="controllers/tab/workflow/stageParticipants.tpl"}
<div id="editorial">
	{url|assign:copyeditingEditorDecisionsUrl router=$smarty.const.ROUTE_PAGE page="workflow" op="editorDecisionActions" submissionId=$submission->getId() stageId=$stageId contextId="copyediting" escape=false}
	{load_url_in_div id="copyeditingEditorDecisionsDiv" url=$copyeditingEditorDecisionsUrl class="editorDecisionActions"}

	<p class="pkp_help">{translate key="editor.submission.editorial.introduction"}</p>

	{url|assign:finalDraftFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.final.FinalDraftFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
	{load_url_in_div id="finalDraftFilesGrid" url=$finalDraftFilesGridUrl}

	{url|assign:queriesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
	{load_url_in_div id="queriesGrid" url=$queriesGridUrl}

	{url|assign:copyeditedFilesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.copyedit.CopyeditedFilesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$stageId escape=false}
	{load_url_in_div id="copyeditedFilesGrid" url=$copyeditedFilesGridUrl}

</div>
