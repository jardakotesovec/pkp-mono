{**
 * templates/workflow/header.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Header that contains details about the submission
 *}

<div class="pkp_submissionHeader">
	<div class="pkp_submissionHeaderTop">
		{include file="common/submissionHeader.tpl" stageId=$stageId monograph=$monograph}

		<div class="action pkp_linkActions">
			{url|assign:"allParticipantsUrl" router=$smarty.const.ROUTE_COMPONENT component="modals.submissionParticipants.SubmissionParticipantsHandler" op="fetch" stageId=$monograph->getStageId() monographId=$monograph->getId() escape=false}
			{modal url="$allParticipantsUrl" actOnType="nothing" actOnId="nothing" dialogText='reviewer.step1.viewAllDetails' button="#allParticipants"}
			<a id="allParticipants" class="user_list" href="{$allParticipantsUrl}">{translate key="submission.submit.allParticipants"}</a>
		</div>

		<div class="action pkp_linkActions">
			{url|assign:"metadataUrl" router=$smarty.const.ROUTE_COMPONENT component="modals.submissionMetadata.SubmissionDetailsSubmissionMetadataHandler" op="fetch" stageId=$monograph->getStageId() monographId=$monograph->getId() escape=false}
			{modal url="$metadataUrl" actOnType="nothing" actOnId="nothing" dialogText='reviewer.step1.viewAllDetails' button="#viewMetadata"}
			<a id="viewMetadata" class="more_info" href="{$metadataUrl}">{translate key="submission.submit.metadata"}</a>
		</div>

		<div class="action pkp_linkActions">
			{url|assign:"informationCenterUrl" router=$smarty.const.ROUTE_COMPONENT component="informationCenter.SubmissionInformationCenterHandler" op="viewInformationCenter" monographId=$monograph->getId() escape=false}
			{modal url="$informationCenterUrl" actOnType="nothing" actOnId="nothing" button="#viewInformationCenter"}
			<a id="viewInformationCenter" class="more_info" href="{$informationCenterUrl}">{translate key="grid.action.moreInformation"}</a>
		</div>
	</div>

	<div class="pkp_helpers_clear"></div>

	{url|assign:timelineUrl router=$smarty.const.ROUTE_COMPONENT component="timeline.TimelineHandler" op="index" monographId=$monograph->getId() escape=false}
	{load_url_in_div id="pkp_submissionTimeline" url="$timelineUrl"}

	<div class="pkp_helpers_clear"></div>

	<div class="pkp_workflow_headerBottom">
		<div class="pkp_workflow_headerUserInfo">
			{include file="controllers/notification/inPlaceNotification.tpl" notificationId="workflowNotification" requestOptions=$workflowNotificationRequestOptions}
		</div>
		<div class="pkp_workflow_headerStageParticipants">
			{url|assign:stageParticipantGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.users.stageParticipant.StageParticipantGridHandler" op="fetchGrid" monographId=$monograph->getId() stageId=$stageId escape=false}
			{load_url_in_div id="stageParticipantGridContainer" url="$stageParticipantGridUrl"}
		</div>
	</div>
</div>
<div class="pkp_helpers_clear"></div>
