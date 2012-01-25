{**
 * catalog/form/publicationMetadataFormFields.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 *}
{* generate a unique form id since this form is used on several tabs *}
{capture assign=publicationFormId}publicationMetadataEntryForm-{$assignedPublicationFormatId}{/capture}

<script type="text/javascript">
	$(function() {ldelim}
		// Attach the form handler.
		$('#{$publicationFormId|escape:"javascript"}').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="{$publicationFormId|escape}" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="saveForm"}">
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="Notification$publicationFormId"}

	<input type="hidden" name="monographId" value="{$monographId|escape}" />
	<input type="hidden" name="stageId" value="{$stageId|escape}" />
	<input type="hidden" name="tabPos" value="{$formParams.tabPos|escape}" />
	<input type="hidden" name="assignedPublicationFormatId" value="{$assignedPublicationFormatId|escape}" />
	<input type="hidden" name="displayedInTab" value="{$formParams.displayedInTab|escape}" />
	<input type="hidden" name="tab" value="publication" />

	{fbvFormArea id="productIdentifier"}
		{fbvFormSection}
			<!-- Product Identification Codes -->
			{assign var="divId" value="identificationCodeGridContainer"|concat:$assignedPublicationFormatId|escape}
			{url|assign:identGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.catalogEntry.IdentificationCodeGridHandler" op="fetchGrid" monographId=$monographId assignedPublicationFormatId=$assignedPublicationFormatId escape=false}
			{load_url_in_div id="$divId" url="$identGridUrl"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="salesRights"}
		{fbvFormSection}
			<!-- Sales rights and regions -->
			{assign var="divId" value="salesRightsGridContainer"|concat:$assignedPublicationFormatId|escape}
			{url|assign:salesGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.catalogEntry.SalesRightsGridHandler" op="fetchGrid" monographId=$monographId assignedPublicationFormatId=$assignedPublicationFormatId escape=false}
			{load_url_in_div id="$divId" url="$salesGridUrl"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="publicationDates"}
		{fbvFormSection}
			<!-- Product Publication/Embargo dates -->
			{assign var="divId" value="publicationDateGridContainer"|concat:$assignedPublicationFormatId|escape}
			{url|assign:dateGridUrl router=$smarty.const.ROUTE_COMPONENT component="grid.catalogEntry.PublicationDateGridHandler" op="fetchGrid" monographId=$monographId assignedPublicationFormatId=$assignedPublicationFormatId escape=false}
			{load_url_in_div id="$divId" url="$dateGridUrl"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="productComposition" title="monograph.publicationFormat.productComposition" border="true"}
		{fbvFormSection for="productCompositionCode"}
			{fbvElement type="select" from=$productCompositionCodes required=true selected=$productCompositionCode translate=false id="productCompositionCode" defaultValue="" defaultLabel="" size=$fbvStyles.size.MEDIUM inline=true}
			{fbvElement type="select" label="monograph.publicationFormat.productFormDetailCode" from=$productFormDetailCodes selected=$productFormDetailCode translate=false id="productFormDetailCode" defaultValue="" defaultLabel="" size=$fbvStyles.size.MEDIUM inline=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="productAvailability" title="monograph.publicationFormat.productAvailability" border="true"}
		{fbvFormSection for="productAvailability"}
			{fbvElement type="select" from=$productAvailabilityCodes required=true selected=$productAvailabilityCode translate=false id="productAvailabilityCode" inline=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="imprintFormArea" title="monograph.publicationFormat.imprint"}
		{fbvFormSection for="imprint"}
			{fbvElement type="text" name="imprint" id="imprint" value=$imprint maxlength="255"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="pricing" title="monograph.publicationFormat.price" border="true"}
		{fbvFormSection for="price" desc="monograph.publicationFormat.pricingInformation"}
			{fbvElement type="text" name="price" id="price" value=$price maxlength="255" size=$fbvStyles.size.SMALL inline="true"}
			{fbvElement type="select" from=$currencyCodes selected=$currencyCode translate=false id="currencyCode" inline="true"}
			{fbvElement type="select" label="monograph.publicationFormat.priceType" from=$priceTypeCodes selected=$priceTypeCode translate=false id="priceTypeCode" defaultValue="" defaultLabel="" inline="true" size=$fbvStyles.size.SMALL}
			{fbvElement type="select" label="monograph.publicationFormat.taxRate" from=$taxRateCodes selected=$taxRateCode translate=false id="taxRateCode" defaultValue="" defaultLabel="" inline="true" size=$fbvStyles.size.SMALL}
			{fbvElement type="select" label="monograph.publicationFormat.taxType" from=$taxTypeCodes selected=$taxTypeCode translate=false id="taxTypeCode" defaultValue="" defaultLabel="" inline="true" size=$fbvStyles.size.SMALL}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="countriesIncluded"}
		{fbvFormSection title="monograph.publicationFormat.productRegion" for="countriesIncludedCode"}
			{fbvElement type="select" from=$countriesIncludedCodes selected=$countriesIncludedCode translate=false id="countriesIncludedCode" name="countriesIncludedCode[]" multiple="multiple" inline=true}
		{/fbvFormSection}
	{/fbvFormArea}

	{if $isPhysicalFormat}
		{include file="catalog/form/physicalPublicationFormat.tpl"}
	{else}
		{include file="catalog/form/digitalPublicationFormat.tpl"}
	{/if}

	{fbvFormButtons id="publicationMetadataFormSubmit" submitText="common.save"}
</form>

