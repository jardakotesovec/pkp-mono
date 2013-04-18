{**
 * templates/submission/form/index.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Main template for the author's submission pages.
 *}
{strip}
{include file="common/header.tpl"}
{/strip}

<script type="text/javascript">
	// Attach the JS file tab handler.
	$(function() {ldelim}
		$('#submitTabs').pkpHandler(
			'$.pkp.pages.submission.SubmissionTabHandler',
			{ldelim}
				submissionProgress: {$submissionProgress},
				notScrollable: true
			{rdelim}
		);
	{rdelim});
</script>

<div id="submitTabs">
	<ul>
		{foreach from=$steps key=step item=stepLocaleKey}
			<li><a href="{url op="step" path=$step submissionId=$submissionId}">{$step}. {translate key=$stepLocaleKey}</a></li>
		{/foreach}
	</ul>
</div>

{strip}
{include file="common/footer.tpl"}
{/strip}
