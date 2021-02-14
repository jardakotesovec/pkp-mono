{**
 * templates/frontend/pages/aboutThisPublishingSystem.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Display the page to view details about the OPS software.
 *
 * @uses $currentContext Server The server currently being viewed
 * @uses $appVersion string Current version of OPS
 * @uses $contactUrl string URL to the server's contact page
 *}
{include file="frontend/components/header.tpl" pageTitle="about.aboutSoftware"}

<div class="page page_about_publishing_system">
	{include file="frontend/components/breadcrumbs.tpl" currentTitleKey="about.aboutSoftware"}
	<h1>
		{translate key="about.aboutSoftware"}
	</h1>

	<p>
		{if $currentContext}
			{translate key="about.aboutOPSServer" opsVersion=$appVersion contactUrl=$contactUrl}
		{else}
			{translate key="about.aboutOPSSite" opsVersion=$appVersion}
		{/if}
	</p>

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}
