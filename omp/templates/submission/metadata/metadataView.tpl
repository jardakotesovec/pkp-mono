{**
 * metadata_view.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * View (but not edit) metadata of a monograph.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="submission.viewMetadata"}
{include file="common/header.tpl"}
{/strip}

{if $canViewAuthors}
<h3>{translate key="monograph.authors"}</h3>

<table width="100%" class="data">
	{foreach name=authors from=$authors key=authorIndex item=author}
	<tr valign="top">
		<td width="20%" class="label">{translate key="user.name"}</td>
		<td width="80%" class="value">
			{assign var=emailString value="`$author.firstName` `$author.middleName` `$author.lastName` <`$author.email`>"}
			{url|assign:"url" page="user" op="email" to=$emailString|to_array redirectUrl=$currentUrl monographId=$monographId}
			{$author.firstName|escape} {$author.middleName|escape} {$author.lastName|escape} {icon name="mail" url=$url}
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.url"}</td>
		<td class="value">{$author.url|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="user.affiliation"}</td>
		<td class="value">{$author.affiliation|escape|default:"&mdash;"}</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="common.country"}</td>
		<td class="value">{$author.countryLocalized|escape|default:"&mdash;"}</td>
	</tr>
	{if $currentPress->getSetting('requireAuthorCompetingInterests')}
	<tr valign="top">
		<td class="label">
			{url|assign:"competingInterestGuidelinesUrl" page="information" op="competingInterestGuidelines"}
			{translate key="author.competingInterests" competingInterestGuidelinesUrl=$competingInterestGuidelinesUrl}
		</td>
		<td class="value">{$author.competingInterests.$formLocale|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{/if}
	<tr valign="top">
		<td class="label">{translate key="user.biography"}</td>
		<td class="value">{$author.biography.$formLocale|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
	{if !$smarty.foreach.authors.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{/foreach}
</table>


<div class="separator"></div>
{/if}


<h3>{translate key="submission.titleAndAbstract"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="monograph.title"}</td>
		<td width="80%" class="value">{$title[$formLocale]|strip_unsafe_html|default:"&mdash;"}</td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="monograph.abstract"}</td>
		<td class="value">{$abstract[$formLocale]|strip_unsafe_html|nl2br|default:"&mdash;"}</td>
	</tr>
</table>

<div class="separator"></div>

<h3>{translate key="editor.monograph.cover"}</h3>

<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="coverPage" key="editor.monograph.coverPage"}</td>
		<td width="80%" class="value">{if $fileName[$formLocale]}<a href="javascript:openWindow('{$publicFilesDir}/{$fileName[$formLocale]|escape:"url"}');" class="file">{$originalFileName[$formLocale]}</a>{else}&mdash;{/if}</td>
	</tr>

	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="coverPageAltText" key="common.altText"}</td>
		<td class="value">{$coverPageAltText[$formLocale]|escape}</td>
	</tr>
</table>

<div class="separator"></div>

<h3>{translate key="submission.indexing"}</h3>
	
<table width="100%" class="data">
	{if $pressSettings.metaDiscipline}
	<tr valign="top">
		<td width="20%" class="label">{translate key="monograph.discipline"}</td>
		<td width="80%" class="value">{$discipline[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $pressSettings.metaSubjectClass}
	<tr valign="top">
		<td colspan="2" class="label"><a href="{$currentPress->getLocalizedSetting('metaSubjectClassUrl')|escape}" target="_blank">{$currentPress->getLocalizedSetting('metaSubjectClassTitle')|escape}</a></td>
	</tr>
	<tr valign="top">
		<td width="20%"class="label">{translate key="monograph.subjectClassification"}</td>
		<td width="80%" class="value">{$subjectClass[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $pressSettings.metaSubject}
	<tr valign="top">
		<td width="20%" class="label">{translate key="monograph.subject"}</td>
		<td width="80%" class="value">{$subject[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $pressSettings.metaCoverage}
	<tr valign="top">
		<td width="20%" class="label">{translate key="monograph.coverageGeo"}</td>
		<td width="80%" class="value">{$coverageGeo[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="monograph.coverageChron"}</td>
		<td class="value">{$coverageChron[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td class="label">{translate key="monograph.coverageSample"}</td>
		<td class="value">{$coverageSample[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	{if $pressSettings.metaType}
	<tr valign="top">
		<td width="20%" class="label">{translate key="monograph.type"}</td>
		<td width="80%" class="value">{$type[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
	<tr valign="top">
		<td width="20%" class="label">{translate key="monograph.language"}</td>
		<td width="80%" class="value">{$language|escape|default:"&mdash;"}</td>
	</tr>
</table>


<div class="separator"></div>


<h3>{translate key="submission.supportingAgencies"}</h3>
	
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{translate key="author.submit.agencies"}</td>
		<td width="80%" class="value">{$sponsor[$formLocale]|escape|default:"&mdash;"}</td>
	</tr>
</table>

{include file="common/footer.tpl"}
