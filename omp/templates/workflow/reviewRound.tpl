{**
 * templates/workflow/reviewRound.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review round info for a particular round
 *}
{include file="controllers/notification/inPlaceNotification.tpl" notificationId="reviewRoundNotification_"|concat:$reviewRoundId requestOptions=$reviewRoundNotificationRequestOptions}

{url|assign:reviewFileSelectionGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.EditorReviewFilesGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
{load_url_in_div id="reviewFileSelection-round_"|concat:$reviewRoundId url=$reviewFileSelectionGridUrl}

{url|assign:reviewersGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.reviewer.ReviewerGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
{load_url_in_div id="reviewersGrid-round_"|concat:$reviewRoundId url=$reviewersGridUrl}

{url|assign:revisionsGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.files.review.ReviewRevisionsGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId reviewRoundId=$reviewRoundId escape=false}
{load_url_in_div id="revisionsGrid-round_"|concat:$reviewRoundId url=$revisionsGridUrl}

{* editorial decision actions *}
{include file="workflow/editorialLinkActions.tpl" contextId="reviewRoundTab-"|concat:$reviewRoundId}
