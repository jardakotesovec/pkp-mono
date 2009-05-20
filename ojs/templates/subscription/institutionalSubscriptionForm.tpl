{**
 * institutionalSubscriptionForm.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Individual subscription form under journal management.
 *
 * $Id$
 *}
{strip}
{assign var="pageCrumbTitle" value="$subscriptionTitle"}
{if $subscriptionId}
	{assign var="pageTitle" value="manager.subscriptions.edit"}
{else}
	{assign var="pageTitle" value="manager.subscriptions.create"}
{/if}
{assign var="pageId" value="manager.subscriptions.institutionalSubscriptionForm"}
{include file="common/header.tpl"}
{/strip}

<br/>

<form method="post" action="{url op="updateSubscription" path="institutional"}">
{if $subscriptionId}
<input type="hidden" name="subscriptionId" value="{$subscriptionId|escape}" />
{/if}

{include file="common/formErrors.tpl"}

<table class="data" width="100%">
{include file="subscription/subscriptionForm.tpl"}
</table>

<br />
<div class="separator"></div>
<br />

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="institutionName" required="true" key="manager.subscriptions.form.institutionName"}</td>
	<td width="80%" class="value"><input type="text" name="institutionName" id="institutionName" value="{if $institutionName}{$institutionName|escape}{/if}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td class="label">{fieldLabel name="institutionMailingAddress" key="manager.subscriptions.form.institutionMailingAddress"}</td>
	<td class="value"><textarea name="institutionMailingAddress" id="institutionMailingAddress" rows="3" cols="40" class="textArea">{$institutionMailingAddress|escape}</textarea></td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="domain" key="manager.subscriptions.form.domain"}</td>
	<td width="80%" class="value"><input type="text" name="domain" id="domain" value="{if $domain}{$domain|escape}{/if}" size="30" maxlength="90" class="textField" /></td>
</tr>
<tr valign="top">
	<td width="20%">&nbsp;</td>
	<td width="80%"><span class="instruct">{translate key="manager.subscriptions.form.domainInstructions"}</span></td>
</tr>

</table>
<table class="data" width="100%">
	{foreach name=ipRanges from=$ipRanges key=ipRangeIndex item=ipRange}
	<tr valign="top">
		{if $ipRangeIndex == 0}
		<td width="15%" class="label">{fieldLabel name="ipRanges" key="manager.subscriptions.form.ipRange"}</td>
		{else}
		<td width="15%">&nbsp;</td>	
		{/if}
		<td width="5%" class="label">{fieldLabel name="ipRanges[$ipRangeIndex]" key="manager.subscriptions.form.ipRangeItem}</td>
		<td width="80%" class="value"><input type="text" name="ipRanges[{$ipRangeIndex|escape}]" id="ipRanges-{$ipRangeIndex|escape}" value="{$ipRange|escape}" size="20" maxlength="40" class="textField" />
		{if $smarty.foreach.ipRanges.total > 1}
		<input type="submit" name="delIpRange[{$ipRangeIndex|escape}]" value="{translate key="manager.subscriptions.form.deleteIpRange"}" class="button" /></td>
		{else}
		</td>
		{/if}
	</tr>
	{foreachelse}
	<tr valign="top">
		<td width="15%" class="label">{fieldLabel name="ipRanges" key="manager.subscriptions.form.ipRange"}</td>
		<td width="5%" class="label">{fieldLabel name="ipRanges[0]" key="manager.subscriptions.form.ipRangeItem}</td>
		<td width="80%" class="value"><input type="text" name="ipRanges[0]" id="ipRanges-0" size="20" maxlength="40" class="textField" /></td>
	</tr>
	{/foreach}
	<tr valign="top">
		<td width="15%">&nbsp;</td>
		<td width="5%">&nbsp;</td>
		<td width="80%"><input type="submit" class="button" name="addIpRange" value="{translate key="manager.subscriptions.form.addIpRange"}" /></td>
	</tr>
	<tr valign="top">
		<td width="15%">&nbsp;</td>
		<td width="5%">&nbsp;</td>
		<td width="80%"><span class="instruct">{translate key="manager.subscriptions.form.ipRangeInstructions"}</span></td>
	</tr>
</table>

<div class="separator"></div>
<br />

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="userId" required="true" key="manager.subscriptions.form.userContact"}</td>
	<td width="80%" class="value">
		{$username|escape}&nbsp;&nbsp;<a href="{if $subscriptionId}{url op="selectSubscriber" path="institutional" subscriptionId=$subscriptionId}{else}{url op="selectSubscriber" path="institutional"}{/if}" class="action">{translate key="common.select"}</a>
		<input type="hidden" name="userId" id="userId" value="{$userId}"/>
	</td>
</tr>
{include file="subscription/subscriptionFormUser.tpl"}
</table>

<br />
<div class="separator"></div>
<br />

<table class="data" width="100%">
<tr valign="top">
	<td width="20%" class="label">{fieldLabel name="notes" key="manager.subscriptions.form.notes"}</td>
	<td width="80%" class="value"><textarea name="notes" id="notes" cols="40" rows="6" class="textArea">{$notes|escape}</textarea></td>
</tr>
</table>

<p><input type="submit" value="{translate key="common.save"}" class="button defaultButton" /> {if not $subscriptionId}<input type="submit" name="createAnother" value="{translate key="manager.subscriptions.form.saveAndCreateAnother"}" class="button" /> {/if}<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url op="subscriptions" path="institutional" escape=false}'" /></p>

</form>

<p><span class="formRequired">{translate key="common.requiredField"}</span></p>

{include file="common/footer.tpl"}
