<!-- templates/common/footer.tpl -->

{**
 * footer.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site footer.
 *
 *}
{strip}
{if $pageFooter==''}
	{if $currentJournal && $currentJournal->getSetting('onlineIssn')}
		{assign var=issn value=$currentJournal->getSetting('onlineIssn')}
	{elseif $currentJournal && $currentJournal->getSetting('printIssn')}
		{assign var=issn value=$currentJournal->getSetting('printIssn')}
	{/if}
	{if $issn}
		{translate|assign:"issnText" key="journal.issn"}
		{assign var=pageFooter value="$issnText: $issn"}
	{/if}
{/if}
{include file="core:common/footer.tpl"}
{/strip}

<!-- / templates/common/footer.tpl -->

