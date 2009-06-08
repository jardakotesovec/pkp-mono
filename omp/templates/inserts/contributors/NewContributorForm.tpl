{**
 * NewContributorForm.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for creating new contributors from within the Contributors insert.
 *
 * $Id$
 *}

<input type="hidden" name="newContributorId" value="{$newContributorId}" />

<div class="newItemContainer">

{if $inserts_ContributorInsert_isError}
<p>
	<div id="inserts_ContributorInsert_formErrors">
	<span class="formError">{translate key="form.errorsOccurred"}:</span>
	<ul class="formErrorList">
	{foreach key=field item=message from=$inserts_ContributorInsert_errors}
		<li>{translate key=$message}</li>
	{/foreach}
	</ul>
	</div>
</p>
<script type="text/javascript">
{literal}
<!--
// Jump to form errors.
window.location.hash="inserts_ContributorInsert_formErrors";
// -->
{/literal}
</script>
{/if}

<table style="info">
<tr>
	<td width="10%"></td><td width="80%"><h2>{translate key="inserts.contributors.heading.newContributor"}</h2></td><td width="10%"></td>
</tr>
<tr>
	<td width="10%"></td><td width="80%">{translate key="inserts.contributors.newContributor.description"}<br /><br /></td><td width="10%"></td>
</tr>
<tr>
	<td width="10%"></td>
	<td width="80%">
	<table width="100%" class="data">
	<tr valign="top">
		<td width="20%" class="label">
			{fieldLabel name="firstName" required="true" key="user.firstName"}
		</td>
		<td width="80%" class="value">
			<input type="text" name="newContributor[firstName]" value="{$newContributor.firstName|escape}" size="20" maxlength="40" class="textField" />
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="middleName" key="user.middleName"}</td>
		<td class="value"><input type="text" name="newContributor[middleName]" value="{$newContributor.middleName|escape}" size="20" maxlength="40" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="lastName" required="true" key="user.lastName"}</td>
		<td class="value"><input type="text" name="newContributor[lastName]" value="{$newContributor.lastName|escape}" size="20" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="affiliation" key="user.affiliation"}</td>
		<td class="value"><input type="text" name="newContributor[affiliation]" value="{$newContributor.affiliation|escape}" size="30" maxlength="255" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="country" key="common.country"}</td>
		<td class="value">
			<select name="newContributor[country]" class="selectMenu">
				<option value=""></option>
				{html_options options=$countries selected=$newContributor.country|escape}
			</select>
		</td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="email" required="true" key="user.email"}</td>
		<td class="value"><input type="text" name="newContributor[email]" value="{$newContributor.email|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="url" key="user.url"}</td>
		<td class="value"><input type="text" name="newContributor[url]" value="{$newContributor.url|escape}" size="30" maxlength="90" class="textField" /></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="biography" key="user.biography"}<br />{translate key="user.biography.description"}</td>
		<td class="value"><textarea name="newContributor[biography][{$formLocale|escape}]" rows="5" cols="40" class="textArea">{$newContributor.biography.$formLocale|escape}</textarea></td>
	</tr>
	{if $workType == EDITED_VOLUME}
	<tr valign="top">
		<td>&nbsp;</td>
		<td>
			<input type="checkbox" name="newContributor[contributionType]" value="1"{if $newContributor.contributionType == VOLUME_EDITOR} checked="checked"{/if} /> <label for="newContributor[contributionType]">{translate key="inserts.contributors.isVolumeEditor"}</label>
		</td>
	</tr>
	{/if}
	<tr valign="top">
		<td>&nbsp;</td>
		<td><input type="submit" name="addContributor" value="{translate key="inserts.contributors.button.addContributor"}" class="button" /></td>
	</tr>
	</table>
	</td>
	<td width="10%"></td>
</tr>
</table>
</div>
