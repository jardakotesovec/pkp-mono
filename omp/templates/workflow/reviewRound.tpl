{**
 * templates/workflow/reviewRound.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review round info for a particular round
 *}
{* Editorial decision actions, if available *}
{url|assign:reviewDecisionsUrl router=$smarty.const.ROUTE_PAGE page="workflow" op="editorDecisionActions" monographId=$monograph->getId() stageId=$stageId reviewRoundId=$reviewRoundId contextId="reviewRoundTab-"|concat:$reviewRoundId escape=false}
{load_url_in_div id="reviewDecisionsDiv-"|concat:$reviewRoundId url=$reviewDecisionsUrl class="editorDecisionActions"}

{if $stageId == $smarty.const.WORKFLOW_STAGE_ID_INTERNAL_REVIEW}
	<p class="pkp_help">{translate key="editor.internalReview.introduction"}</p>
{elseif $stageId == $smarty.const.WORKFLOW_STAGE_ID_EXTERNAL_REVIEW}
	<p class="pkp_help">{translate key="editor.externalReview.introduction"}</p>
{/if}

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="reviewRoundNotification_"|concat:$reviewRoundId requestOptions=$reviewRoundNotificationRequestOptions}

{url|assign:reviewFileSelectionGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.EditorReviewFilesGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
{load_url_in_div id="reviewFileSelection-round_"|concat:$reviewRoundId url=$reviewFileSelectionGridUrl}

{url|assign:reviewersGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.reviewer.ReviewerGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
{load_url_in_div id="reviewersGrid-round_"|concat:$reviewRoundId url=$reviewersGridUrl}

{url|assign:revisionsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.WorkflowReviewRevisionsGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
{load_url_in_div id="revisionsGrid-round_"|concat:$reviewRoundId url=$revisionsGridUrl}

