{**
 * citationFormErrorsAndComparison.tpl
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * A part of the citation form that will be refreshed
 * whenever the user changes one of the fields (by manual
 * edit or via a database query). Can be used stand-alone
 * or when refreshing the form as a whole.
 *}

{* create the before/after markup versions of the citations from the citation diff *}
{capture assign=rawCitationWithMarkup}{strip}
	{foreach from=$citationDiff item=change}
		{foreach from=$change key=changeType item=text}
			{* The raw citation gets common strings and deletions *}
			{if $changeType <= 0}
				<span class="citation-comparison-{if $changeType == 0}common{elseif $changeType == -1}deletion{/if}">{$text}</span>
			{/if}
		{/foreach}
	{/foreach}
{/strip}{/capture}
{capture assign=generatedCitationWithMarkup}{strip}
	{foreach from=$citationDiff item=change}
		{foreach from=$change key=changeType item=text}
			{* The generated citation gets common strings and additions *}
			{if $changeType >= 0}
				<span class="citation-comparison-{if $changeType == 0}common{elseif $changeType == 1}addition{/if}">{$text}</span>
			{/if}
		{/foreach}
	{/foreach}
{/strip}{/capture}
<script type="text/javascript">
	$(function() {ldelim}
		// Hide editable raw citation on startup (unless we're adding a
		// new citation) and leaving without editing.
		// We do nothing when the field changes because then an
		// AJAX refresh will be triggered anyway.
		$('#editableRawCitation'){if $citation->getId()}.hide(){/if}
			.find('textarea').blur(function() {ldelim}
				if ($(this).text() === $(this).val()) {ldelim}
					$('#editableRawCitation').hide();
					$('#rawCitationWithMarkup').show();
				{rdelim}
			{rdelim});

		{if !$citation->getId()}
			// Hide the citation comparison markup when we add a new
			// citation.
			$('.citation-comparison').hide();
		{/if}
			
		// Clicking on the raw citation should make it editable.
		$('#rawCitationWithMarkup div.value, #rawCitationWithMarkup a').each(function() {ldelim}
			$(this).click(function() {ldelim}
				$('#rawCitationWithMarkup').hide();
				$('#editableRawCitation').show().find('textarea').focus();
				return false;
			{rdelim});
		{rdelim});

		// Update citation diff after any type of editing.
		$('.citation-field').each(function() {ldelim}
			$(this).unbind('change');
		{rdelim});	
		eventHandler = ajaxAction(
			'post',
			'#citationFormErrorsAndComparison',
			// We bind the raw citation textarea and the wrapper
			// to the change event. The wrapper doesn't implement
			// its own change event but it can be manually triggered
			// if we want to refresh the interface for some reason.
			'#editableRawCitation textarea, #citationFormErrorsAndComparison',
			'{url op="fetchCitationFormErrorsAndComparison"}',
			null,
			'change',
			'#editCitationForm'
		);
		// Bind citation fields with live so that new fields
		// will be automatically active. 
		$('.citation-field').live('change', eventHandler);
	{rdelim});
</script>
<div id="citationFormErrorsAndComparison" class="form-block">
	{if $unsavedChanges || $isError}
		<div id="citationFormMessages" class="help-message">
			{if $unsavedChanges}
				<p class="unsaved-data-warning"><span class="formError">{translate key="submission.citations.form.unsavedChanges"}</span></p>
			{/if}
			{include file="common/formErrors.tpl" dontJumpToError=true}
		</div>
	{/if}
	

	{* We have two versions of the raw citation - one editable and the
	   other with mark-up for comparison. We use JS to switch between the
	   two on user demand. *}
	<div id="editableRawCitation">
		<div class="label">
			{if $citation->getId()}
				{fieldLabel name="rawCitation" key="submission.citations.grid.rawCitation"}
			{else}
				{fieldLabel name="rawCitation" key="submission.citations.grid.newCitation"}
			{/if}
		</div>
		<div class="value">
			<textarea class="textarea" validation="required" id="rawCitation" name="rawCitation">{$rawCitation}</textarea>
		</div>
	</div>
	<div id="rawCitationWithMarkup" class="citation-comparison">
		<div class="label">{translate key="submission.citations.grid.rawCitation"}</div>
		<a class="edit" title="{translate key="submission.citations.grid.clickToEdit"}" href=""></a>
		<div class="value ui-corner-all" title="{translate key="submission.citations.grid.clickToEdit"}">{$rawCitationWithMarkup}</div>
	</div>
	<div id="generatedCitationWithMarkup" class="citation-comparison">
		<div class="label">{translate key="submission.citations.grid.generatedCitation"}</div>
		<div class="value ui-corner-all">{$generatedCitationWithMarkup}</div>
	</div>
</div>
