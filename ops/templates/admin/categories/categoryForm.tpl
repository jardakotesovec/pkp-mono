{**
 * templates/admin/categories/categoryForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Category form under site administration.
 *
 *}
{strip}
{assign var="pageId" value="admin.categories.categoryForm"}
{assign var="pageCrumbTitle" value=$pageTitle}
{include file="common/header.tpl"}
{/strip}
<div id="categoryFormDiv">

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#categoryForm').pkpHandler('$.pkp.controllers.form.FormHandler');
	{rdelim});
</script>
<form class="pkp_form" id="categoryForm" method="post" action="{url op="updateCategory"}">
{if $category}
	<input type="hidden" name="categoryId" value="{$category->getId()}"/>
{/if}

{include file="common/formErrors.tpl"}
<table class="data">
{if count($formLocales) > 1}
	<tr>
		<td class="label">{fieldLabel name="formLocale" key="form.formLanguage"}</td>
		<td class="value">
			{if $category}{url|assign:"categoryFormUrl" op="editCategory" path=$category->getId() escape=false}
			{else}{url|assign:"categoryFormUrl" op="createCategory" escape=false}
			{/if}
			{form_language_chooser form="categoryForm" url=$categoryFormUrl}
			<span class="instruct">{translate key="form.formLanguage.description"}</span>
		</td>
	</tr>
{/if}
<tr>
	<td class="label">{fieldLabel name="name" required="true" key="admin.categories.name"}</td>
	<td class="value"><input type="text" name="name[{$formLocale|escape}]" value="{$name[$formLocale]|escape}" size="35" maxlength="80" id="name" class="textField" /></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> <input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="categories" escape=false}'" /></p>
</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}

