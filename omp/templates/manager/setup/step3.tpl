{**
 * step3.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 3 of press setup.
 *
 * $Id$
 *}
{assign var="pageTitle" value="manager.setup.guidingSubmissions"}
{include file="manager/setup/setupHeader.tpl"}

<form name="setupForm" method="post" action="{url op="saveSetup" path="3"}" enctype="multipart/form-data">
{include file="common/formErrors.tpl"}

{if count($formLocales) > 1}
<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td width="80%" class="value">
			{url|assign:"setupFormUrl" op="setup" path="3"}
			{form_language_chooser form="setupForm" url=$setupFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
</table>
{/if}

<h3>3.2 {translate key="manager.setup.bookFileTypes}</h3>

<p>{translate key="manager.setup.bookFileTypesDescription"}</p>

{foreach name=bookFileTypes from=$bookFileTypes[$formLocale] key=fileTypeId item=fileTypeItem}
	{if !$notFirstFileTypeItem}
		{assign var=notFirstFileTypeItem value=1}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="5%">&nbsp;</td>
				<td width="30%">{translate key="common.type"}</td>
				<td width="70%">{translate key="common.filePrefix"}</td>
			</tr>
	{/if}

	<tr valign="top">
		<td><input type="checkbox" name="bookFileTypeSelect[]" value="{$fileTypeId}" /></td>
		<td>{$fileTypeItem.type}</td>
		<td>{$fileTypeItem.prefix}</td>
	</tr>
{/foreach}

{if $notFirstFileTypeItem}
	</table>
{/if}
<p>
<input type="submit" name="deleteSelectedBookFileTypes" value="{translate key="manager.setup.deleteSelected"}" class="button" />
<input type="submit" name="restoreDefaultBookFileTypes" value="{translate key="manager.setup.restoreDefaults"}" class="button" />
</p>
<div class="newItemContainer">
<h3>{translate key="manager.setup.newBookFileType"}</h3>
<p>{translate key="manager.setup.newBookFileTypeDescription"}</p>
<table>
<tr>
	<td>{translate key="common.filePrefix"}</td><td><input type="text" name="newBookFileType[prefix]" class="textField" /></td>
</tr>
<tr>
	<td>{translate key="common.type"}</td><td><input type="text" name="newBookFileType[type]" class="textField" /></td>
</tr>
<tr>
	<td>{translate key="common.description"}</td><td><textarea name="newBookFileType[description]" rows="5" cols="30" class="textArea"></textarea></td>
</tr>
<tr>
	<td>{translate key="common.sortableByComponent"}</td><td><input type="checkbox" name="newBookFileType[sortable]" class="textField" /></td>
</tr>
<tr>
	<td>&nbsp;</td><td><input type="submit" name="addBookFileType" value="{translate key="common.create"}" class="button" /></td>
</tr>
</table>
</div>

<div class="separator"></div>

<h3>3.4 {translate key="manager.setup.forAuthorsToIndexTheirWork"}</h3>

<p>{translate key="manager.setup.forAuthorsToIndexTheirWorkDescription"}</p>

<table width="100%" class="data">
	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaDiscipline" id="metaDiscipline" value="1"{if $metaDiscipline} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaDiscipline" key="manager.setup.discipline"}</h4>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.disciplineDescription"}</span><br/>
			<span class="instruct">{translate key="manager.setup.disciplineProvideExamples"}:</span>
			<br />
			<input type="text" name="metaDisciplineExamples[{$formLocale|escape}]" id="metaDisciplineExamples" value="{$metaDisciplineExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.disciplineExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaSubjectClass" id="metaSubjectClass" value="1"{if $metaSubjectClass} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaSubjectClass" key="manager.setup.subjectClassification"}</h4>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<table width="100%">
				<tr valign="top">
					<td width="10%">{fieldLabel name="metaSubjectClassTitle" key="common.title"}</td>
					<td width="90%"><input type="text" name="metaSubjectClassTitle[{$formLocale|escape}]" id="metaSubjectClassTitle" value="{$metaSubjectClassTitle[$formLocale]|escape}" size="40" maxlength="255" class="textField" /></td>
				</tr>
				<tr valign="top">
					<td width="10%">{fieldLabel name="metaSubjectClassUrl" key="common.url"}</td>
					<td width="90%"><input type="text" name="metaSubjectClassUrl[{$formLocale|escape}]" id="metaSubjectClassUrl" value="{if $metaSubjectClassUrl[$formLocale]}{$metaSubjectClassUrl[$formLocale]|escape}{else}http://{/if}" size="40" maxlength="255" class="textField" /></td>
				</tr>
			</table>
			<span class="instruct">{translate key="manager.setup.subjectClassificationExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaSubject" id="metaSubject" value="1"{if $metaSubject} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaSubject" key="manager.setup.subjectKeywordTopic"}</h4>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.subjectProvideExamples"}:</span>
			<br />
			<input type="text" name="metaSubjectExamples[{$formLocale|escape}]" id="metaSubjectExamples" value="{$metaSubjectExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.subjectExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaCoverage" id="metaCoverage" value="1"{if $metaCoverage} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaCoverage" key="manager.setup.coverage"}</h4>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.coverageDescription"}</span><br/>
			<span class="instruct">{translate key="manager.setup.coverageGeoProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageGeoExamples[{$formLocale|escape}]" id="metaCoverageGeoExamples" value="{$metaCoverageGeoExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.coverageGeoExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.coverageChronProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageChronExamples[{$formLocale|escape}]" id="metaCoverageChronExamples" value="{$metaCoverageChronExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.coverageChronExamples"}</span>
		</td>
	</tr>
	<tr>
		<td class="separator" colspan="2">&nbsp;</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.coverageResearchSampleProvideExamples"}:</span>
			<br />
			<input type="text" name="metaCoverageResearchSampleExamples[{$formLocale|escape}]" id="metaCoverageResearchSampleExamples" value="{$metaCoverageResearchSampleExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.coverageResearchSampleExamples"}</span>
		</td>
	</tr>
	
	<tr>
		<td class="separator" colspan="2"><br />&nbsp;</td>
	</tr>
	
	<tr valign="top">
		<td width="5%" class="label" valign="bottom"><input type="checkbox" name="metaType" id="metaType" value="1"{if $metaType} checked="checked"{/if} /></td>
		<td width="95%" class="value">
			<h4>{fieldLabel name="metaType" key="manager.setup.typeMethodApproach"}</h4>
		</td>
	</tr>
	<tr valign="top">
		<td>&nbsp;</td>
		<td class="value">
			<span class="instruct">{translate key="manager.setup.typeProvideExamples"}:</span>
			<br />
			<input type="text" name="metaTypeExamples[{$formLocale|escape}]" id="metaTypeExamples" value="{$metaTypeExamples[$formLocale]|escape}" size="60" maxlength="255" class="textField" />
			<br />
			<span class="instruct">{translate key="manager.setup.typeExamples"}</span>
		</td>
	</tr>
</table>


<div class="separator"></div>

<p><input type="submit" value="{translate key="common.saveAndContinue"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="setup" escape=false}'" /></p>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

</form>

{include file="common/footer.tpl"}
