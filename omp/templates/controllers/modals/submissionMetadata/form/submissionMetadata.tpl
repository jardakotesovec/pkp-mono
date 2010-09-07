<!-- templates/controllers/modals/submissionMetadata/submissionMetadata.tpl -->

{**
 * submissionMetadata.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display a submission's metadata
 *
 *}
{modal_title id="#submissionMetadata" key='submission.submit.metadata' iconClass="fileManagement" canClose=1}

{fbvFormArea id="submissionMetadata"}
	{fbvFormSection title="monograph.title"}
		{$monograph->getLocalizedTitle()|escape}
	{/fbvFormSection}
	{fbvFormSection title="monograph.description"}
		{$monograph->getMonographDescription()}
	{/fbvFormSection}
	{fbvFormSection title="common.dateSubmitted"}
		{$monograph->getDateSubmitted()}
	{/fbvFormSection}
	{if $monograph->getSeriesTitle()}
		{fbvFormSection title="series.series"}
			{$monograph->getSeriesTitle()}
		{/fbvFormSection}
	{/if}
{/fbvFormArea}

{init_button_bar id="#submissionMetadata"}

<!-- / templates/controllers/modals/editorDecision/form/initiateReviewForm.tpl -->

