{**
 * templates/controllers/grid/user/reviewer/form/advancedSearchReviewerFilterForm.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays the form to filter results in the reviewerSelect grid.
 *
 *}

<script type="text/javascript">
	$(function() {ldelim}
		// Handle filter form submission
		$('#reviewerFilterForm').pkpHandler('$.pkp.controllers.form.ClientFormHandler',
			{ldelim}
				trackFormChanges: false
			{rdelim}
		);
	{rdelim});
</script>

<form class="pkp_form filter" id="reviewerFilterForm" action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.users.reviewerSelect.ReviewerSelectGridHandler" op="fetchGrid"}" method="post" class="pkp_controllers_reviewerSelector">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="advancedSearchReviewerFilterFormNotification"}
	{fbvFormArea id="reviewerSearchForm"}
		<input type="hidden" id="submissionId" name="submissionId" value="{$submissionId|escape}" />
		<input type="hidden" id="stageId" name="stageId" value="{$stageId|escape}" />
		<input type="hidden" id="reviewRoundId" name="reviewRoundId" value="{$reviewRoundId|escape}" />
		<input type="hidden" name="clientSubmit" value="1" />

		{capture assign="extraFilters"}
			{fbvFormSection inline="true" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="rangeSlider" id="done" min=0 max=100 label="manager.reviewerSearch.doneAmount" valueMin=$reviewerValues.doneMin|default:0 valueMax=$reviewerValues.doneMax|default:100}
			{/fbvFormSection}
			{fbvFormSection inline="true" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="rangeSlider" id="avg" min=0 max=365 label="manager.reviewerSearch.avgAmount" valueMin=$reviewerValues.avgMin|default:0 valueMax=$reviewerValues.avgMax|default:365}
			{/fbvFormSection}

			{fbvFormSection inline="true" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="rangeSlider" id="last" min=0 max=365 label="manager.reviewerSearch.lastAmount" valueMin=$reviewerValues.lastMin|default:0 valueMax=$reviewerValues.lastMax|default:365}
			{/fbvFormSection}
			{fbvFormSection inline="true" size=$fbvStyles.size.MEDIUM}
				{fbvElement type="rangeSlider" id="active" min=0 max=100 label="manager.reviewerSearch.activeAmount" valueMin=$reviewerValues.activeMin|default:0 valueMax=$reviewerValues.activeMax|default:100}
			{/fbvFormSection}

			{fbvFormSection title="manager.reviewerSearch.form.interests.instructions"}
				{fbvElement type="interests" id="interests" interests=$interestSearchKeywords}
			{/fbvFormSection}
		{/capture}

		<div id="reviewerAdvancedSearchFilters">
			{include file="controllers/extrasOnDemand.tpl"
				id="reviewerAdvancedSearchFiltersWrapper"
				widgetWrapper="#reviewerAdvancedSearchFilters"
				moreDetailsText="search.advancedSearchMore"
				lessDetailsText="search.advancedSearchLess"
				extraContent=$extraFilters
			}
		</div>

		{fbvFormSection class="pkp_helpers_text_right"}
			{fbvElement type="submit" id="submitFilter" label="common.search"}
		{/fbvFormSection}
	{/fbvFormArea}
</form>
