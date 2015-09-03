{**
 * lib/pkp/templates/controllers/tab/authorDashboard/externalReview.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display the external review stage on the author dashboard.
 *}
{if $submission->getStageId() >= $smarty.const.WORKFLOW_STAGE_ID_EXTERNAL_REVIEW && !$externalReviewRounds->wasEmpty()}
	{include file="authorDashboard/reviewRoundTab.tpl" reviewRounds=$externalReviewRounds reviewRoundTabsId="externalReviewRoundTabs" lastReviewRoundNumber=$lastReviewRoundNumber.externalReview}

	<!-- Display queries grid -->
	{url|assign:queriesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.queries.QueriesGridHandler" op="fetchGrid" submissionId=$submission->getId() stageId=$smarty.const.WORKFLOW_STAGE_ID_EXTERNAL_REVIEW escape=false}
	{load_url_in_div id="queriesGridDiv" url=$queriesGridUrl}
{else}
	{translate key="submission.stageNotInitiated"}
{/if}
