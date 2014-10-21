{**
 * templates/workflow/review.tpl
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Review workflow stage.
 *}
<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#reviewTabs').pkpHandler(
			'$.pkp.controllers.TabHandler',
			{ldelim}
				{assign var=roundIndex value=$lastReviewRoundNumber-1}
				selected: {$roundIndex},
				notScrollable: true,
			{rdelim}
		);
	{rdelim});
</script>
{include file="controllers/tab/workflow/stageParticipants.tpl"}

{if $reviewRounds}
	<div id="reviewTabs" class="pkp_controllers_tab">
		<ul>
			{foreach from=$reviewRounds item=reviewRound}
				<li>
					<a href="{url router=$smarty.const.ROUTE_COMPONENT component="tab.workflow.ReviewRoundTabHandler" op=$reviewRoundOp submissionId=$submission->getId() stageId=$reviewRound->getStageId() reviewRoundId=$reviewRound->getId()}">{translate key="submission.round" round=$reviewRound->getRound()}</a>
				</li>
			{/foreach}
			{if $newRoundAction}
				<li>
					{* FIXME: this <a> tag is here just to get the CSS to work *}
					<a id="newRoundTabContainer" href="/" style="padding-left: 0px; padding-right: 0px;"></a>
					{include file="linkAction/linkAction.tpl" image="add_item" action=$newRoundAction contextId="newRoundTabContainer"}
				</li>
			{/if}
		</ul>
	</div>
{else}
	<p>{translate key="editor.review.notInitiated"}</p>
{/if}
</div>
