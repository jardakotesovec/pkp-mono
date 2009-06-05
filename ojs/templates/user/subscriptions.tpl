{**
 * subscriptions.tpl
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User subscriptions management page.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="user.subscriptions.mySubscriptions"}
{include file="common/header.tpl"}
{/strip}

<h3>{translate key="about.subscriptionsContact"}</h3>
<p>
	{if !empty($subscriptionName)}
		<strong>{$subscriptionName|escape}</strong><br />
	{/if}
	{if !empty($subscriptionMailingAddress)}
		{$subscriptionMailingAddress|nl2br}<br />
	{/if}
	{if !empty($subscriptionPhone)}
		{translate key="user.phone"}: {$subscriptionPhone|escape}<br />
	{/if}
	{if !empty($subscriptionFax)}
		{translate key="user.fax"}: {$subscriptionFax|escape}<br />
	{/if}
	{if !empty($subscriptionEmail)}
		{translate key="user.email"}: {mailto address=$subscriptionEmail|escape encode="hex"}<br />
	{/if}
	{if !empty($subscriptionAdditionalInformation)}
		<br />{$subscriptionAdditionalInformation|nl2br}<br />
	{/if}
</p>

{if $individualSubscriptionTypesExist}
	<h3>{translate key="user.subscriptions.individualSubscriptions"}</h3>
	<p>{translate key="subscriptions.individualDescription"}</p>
	<table width="100%" class="info">
	{if $userIndividualSubscription}
		<tr valign="top">
			<td width="30%">&#187; {$userIndividualSubscription->getSubscriptionTypeName()|escape}</td>
			<td width="30%">&nbsp;</td>
			<td width="25%">
			{assign var="subscriptionStatus" value=$userIndividualSubscription->getStatus()}
			{assign var="isNonExpiring" value=$userIndividualSubscription->isNonExpiring()}
			{if $journalPaymentsEnabled && $acceptSubscriptionPayments && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
				<span class="disabled">{translate key="subscriptions.status.awaitingOnlinePayment"}</span>	
			{elseif $subscriptionStatus != $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
				<span class="disabled">{translate key="subscriptions.inactive"}</span>	
			{else}
				{if $isNonExpiring}
					{translate key="subscriptionTypes.nonExpiring"}
				{else}
					{assign var="isExpired" value=$userIndividualSubscription->isExpired()}
					{if $isExpired}<span class="disabled">{translate key="user.subscriptions.expired"}: {$userIndividualSubscription->getDateEnd()|date_format:$dateFormatShort}</span>{else}{translate key="user.subscriptions.expires"}: {$userIndividualSubscription->getDateEnd()|date_format:$dateFormatShort}{/if}
				{/if}
			{/if}
			</td>
			<td width="15%" align="right">
			{if $journalPaymentsEnabled && $acceptSubscriptionPayments}
				{if $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
					[<a href="{url op="completePurchaseSubscription" path="individual"|to_array:$userIndividualSubscription->getSubscriptionId()}">{translate key="user.subscriptions.purchase"}</a>]
				{elseif $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
					{if !$isNonExpiring}
						[<a href="{url op="payRenewSubscription" path="individual"|to_array:$userIndividualSubscription->getSubscriptionId()}">{translate key="user.subscriptions.renew"}</a>]
					{/if}
					[<a href="{url op="purchaseSubscription" path="individual"|to_array:$userIndividualSubscription->getSubscriptionId()}">{translate key="user.subscriptions.purchase"}</a>]
				{/if}
			{else}
				&nbsp;
			{/if}
			</td>
		</tr>
	{elseif $journalPaymentsEnabled && $acceptSubscriptionPayments}
		<tr valign="top">
			<td colspan="3" align="left">&#187; <a href="{url op="purchaseSubscription" path="individual"}">{translate key="user.subscriptions.purchase"}</a></td> 
		</tr>
	{else}
		<tr valign="top">
			<td colspan="3" align="left">&#187; <a href="{url page="about" op="subscriptions" anchor="subscriptionTypes"}">{translate key="user.subscriptions.viewSubscriptionTypes"}</a></td> 
		</tr>
	{/if}
	</table>
{/if}

{if $institutionalSubscriptionTypesExist}
	<h3>{translate key="user.subscriptions.institutionalSubscriptions"}</h3>
	<p>{translate key="subscriptions.institutionalDescription"}{if $journalPaymentsEnabled && $acceptSubscriptionPayments} {translate key="subscriptions.institutionalOnlinePaymentDescription"}{/if}</p>
	<table width="100%" class="info">
	{if $userInstitutionalSubscriptions}
		{iterate from=userInstitutionalSubscriptions item=userInstitutionalSubscription}
		<tr valign="top">
			<td width="30%">&#187; {$userInstitutionalSubscription->getSubscriptionTypeName()|escape}</td>
			<td width="30%">{$userInstitutionalSubscription->getInstitutionName()|escape}</td>
			<td width="25%">
			{assign var="subscriptionStatus" value=$userInstitutionalSubscription->getStatus()}
			{assign var="isNonExpiring" value=$userInstitutionalSubscription->isNonExpiring()}
			{if $journalPaymentsEnabled && $acceptSubscriptionPayments && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
				<span class="disabled">{translate key="subscriptions.status.awaitingOnlinePayment"}</span>	
			{elseif $journalPaymentsEnabled && $acceptSubscriptionPayments && $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_NEEDS_APPROVAL}
				<span class="disabled">{translate key="subscriptions.status.needsApproval"}</span>	
			{elseif $subscriptionStatus != $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
				<span class="disabled">{translate key="subscriptions.inactive"}</span>	
			{else}	
				{if $isNonExpiring}
					{translate key="subscriptionTypes.nonExpiring"}
				{else}
					{assign var="isExpired" value=$userInstitutionalSubscription->isExpired()}
					{if $isExpired}<span class="disabled">{translate key="user.subscriptions.expired"}: {$userInstitutionalSubscription->getDateEnd()|date_format:$dateFormatShort}</span>{else}{translate key="user.subscriptions.expires"}: {$userInstitutionalSubscription->getDateEnd()|date_format:$dateFormatShort}{/if}
				{/if}
			{/if}
			</td>
			<td width="15%" align="right">
			{if $journalPaymentsEnabled && $acceptSubscriptionPayments}
				{if $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_AWAITING_ONLINE_PAYMENT}
					[<a href="{url op="completePurchaseSubscription" path="institutional"|to_array:$userInstitutionalSubscription->getSubscriptionId()}">{translate key="user.subscriptions.purchase"}</a>]
				{elseif $subscriptionStatus == $smarty.const.SUBSCRIPTION_STATUS_ACTIVE}
					{if !$isNonExpiring}
						[<a href="{url op="payRenewSubscription" path="institutional"|to_array:$userInstitutionalSubscription->getSubscriptionId()}">{translate key="user.subscriptions.renew"}</a>]
					{/if}
					[<a href="{url op="purchaseSubscription" path="institutional"|to_array:$userInstitutionalSubscription->getSubscriptionId()}">{translate key="user.subscriptions.purchase"}</a>]
				{/if}
			{else}
				&nbsp;
			{/if}
			</td>
		</tr>
		<tr><td class="separator" width="100%" colspan="4">&nbsp;</td></tr>
		{/iterate}
	{/if}
	{if $journalPaymentsEnabled && $acceptSubscriptionPayments}
		<tr valign="top">
			<td colspan="3" align="left">&#187; <a href="{url page="user" op="purchaseSubscription" path="institutional"}">{translate key="user.subscriptions.purchase"}</a></td> 
		</tr>
	{else}
		<tr valign="top">
			<td colspan="3" align="left">&#187; <a href="{url page="about" op="subscriptions" anchor="subscriptionTypes"}">{translate key="user.subscriptions.viewSubscriptionTypes"}</a></td> 
		</tr>
	{/if}
	</table>
{/if}

{include file="common/footer.tpl"}
