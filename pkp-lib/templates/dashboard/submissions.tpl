{**
 * templates/dashboard/submissions.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Dashboard user related submissions tab.
 *}
<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#contextSubmissionForm').pkpHandler('$.pkp.controllers.dashboard.form.DashboardTaskFormHandler',
			{ldelim}
				{if $contextCount == 1}
					singleContextSubmissionUrl: {url|json_encode context=$context->getPath() page="submission" op="wizard" escape=false},
				{/if}
				trackFormChanges: false
			{rdelim}
		);
	{rdelim});
</script>
<ul class="pkp_context_panel">
	<li class="pkp_context_actions">
		<form id="contextSubmissionForm">
			<ul>
				<li>
					<!-- New Submission entry point -->
					{**
					 * @todo only the single journal context has been styled.
					 *   a new UI pattern is needed for multi-journal context
					 *}
					{if $contextCount > 1}
						{capture assign="defaultLabel"}{translate key="context.select"}{/capture}
						{fbvElement type="select" id="multipleContext" from=$contexts defaultValue=0 defaultLabel=$defaultLabel translate=false size=$fbvStyles.size.MEDIUM}
					{elseif $contextCount == 1}
						{capture assign="singleLabel"}{translate key="submission.submit.newSubmissionSingle"}{/capture}
						{fbvElement type="button" id="singleContext" label=$singleLabel translate=false}
					{/if}
				</li>
			</ul>
		</form>
	</li>
</ul>

<div class="pkp_content_panel">
	<!-- Author and editor submissions grid -->
	{if array_intersect(array(ROLE_ID_AUTHOR, ROLE_ID_MANAGER, ROLE_ID_GUEST_EDITOR, ROLE_ID_SUB_EDITOR), $userRoles)}
		{url|assign:mySubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.mySubmissions.MySubmissionsListGridHandler" op="fetchGrid" escape=false}
		{load_url_in_div id="mySubmissionsListGridContainer" url=$mySubmissionsListGridUrl}
	{/if}

	<!-- Unassigned submissions grid: If the user is a manager or a series editor, then display these submissions which have not been assigned to anyone -->
	{if array_intersect(array(ROLE_ID_MANAGER, ROLE_ID_GUEST_EDITOR), $userRoles)}
		{url|assign:unassignedSubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.unassignedSubmissions.UnassignedSubmissionsListGridHandler" op="fetchGrid" escape=false}
		{load_url_in_div id="unassignedSubmissionsListGridContainer" url=$unassignedSubmissionsListGridUrl}
	{/if}

	<!-- Assigned submissions grid: Show all submissions the user is assigned to (besides their own) -->
	{url|assign:assignedSubmissionsListGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.submissions.assignedSubmissions.AssignedSubmissionsListGridHandler" op="fetchGrid" escape=false}
	{load_url_in_div id="assignedSubmissionsListGridContainer" url=$assignedSubmissionsListGridUrl}
</div>
