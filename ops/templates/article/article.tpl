{**
 * article.tpl
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View.
 *
 * $Id$
 *}
{include file="article/header.tpl"}

{if $galley}
	{if $galley->isHTMLGalley()}
		<div id="topBar">
			<div id="articleFontSize">
				{translate key="article.fontSize"}:&nbsp;
				<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.small.altText"}');" class="icon">{icon path="templates/images/icons/" name="font_small"}</a>&nbsp;
				<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.medium.altText"}');" class="icon">{icon path="templates/images/icons/" name="font_medium"}</a>&nbsp;
				<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.large.altText"}');" class="icon">{icon path="templates/images/icons/" name="font_large"}</a>
			</div>
		</div>
		{$galley->getHTMLContents()}
	{/if}
{else}
	<div id="topBar">
		{assign var=galleys value=$article->getLocalizedGalleys()}
		{if $galleys && $subscriptionRequired && $showGalleyLinks}
			<div id="accessKey">
				<img src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
				{translate key="reader.openAccess"}&nbsp;
				<img src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
				{if $purchaseArticleEnabled}
					{translate key="reader.subscriptionOrFeeAccess"}
				{else}
					{translate key="reader.subscriptionAccess"}
				{/if}
			</div>
		{/if}
		<div id="articleFontSize">
				{translate key="article.fontSize"}:&nbsp;
			<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.small.altText"}');" class="icon">{icon path="templates/images/icons/" name="font_small"}</a>&nbsp;
			<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.medium.altText"}');" class="icon">{icon path="templates/images/icons/" name="font_medium"}</a>&nbsp;
			<a href="#" onclick="setFontSize('{translate|escape:"jsparam" key="article.fontSize.large.altText"}');" class="icon">{icon path="templates/images/icons/" name="font_large"}</a>
		</div>
	</div>
	{if $coverPagePath}
		<div id="articleCoverImage"><img src="{$coverPagePath|escape}{$coverPageFileName|escape}"{if $coverPageAltText != ''} alt="{$coverPageAltText|escape}"{else} alt="{translate key="article.coverPage.altText"}"{/if}{if $width} width="{$width|escape}"{/if}{if $height} height="{$height|escape}"{/if}/>
		</div>
	{/if}
	<h3>{$article->getArticleTitle()|strip_unsafe_html}</h3>
	<div><em>{$article->getAuthorString()|escape}</em></div>
	<br />
	{if $article->getArticleAbstract()}
		<h4>{translate key="article.abstract"}</h4>
		<br />
		<div>{$article->getArticleAbstract()|strip_unsafe_html|nl2br}</div>
		<br />
	{/if}

	{if (!$subscriptionRequired || $article->getAccessStatus() || $subscribedUser || $subscribedDomain)}
		{assign var=hasAccess value=1}
	{else}
		{assign var=hasAccess value=0}
	{/if}
	
	{if $galleys}
		{translate key="reader.fullText"}
		{if $hasAccess || ($subscriptionRequired && $showGalleyLinks)}
			{foreach from=$article->getLocalizedGalleys() item=galley name=galleyList}
				<a href="{url page="article" op="view" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal)}" class="file" target="_parent">{$galley->getGalleyLabel()|escape}</a>
				{if $subscriptionRequired && $showGalleyLinks && $restrictOnlyPdf}
					{if $article->getAccessStatus() || !$galley->isPdfGalley()}	
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
					{else}
						<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
					{/if}
				{/if}
			{/foreach}
			{if $subscriptionRequired && $showGalleyLinks && !$restrictOnlyPdf}
				{if $article->getAccessStatus()}
					<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_open_medium.gif" alt="{translate key="article.accessLogoOpen.altText"}" />
				{else}
					<img class="accessLogo" src="{$baseUrl}/templates/images/icons/fulltext_restricted_medium.gif" alt="{translate key="article.accessLogoRestricted.altText"}" />
				{/if}
			{/if}					
		{else}
			&nbsp;<a href="{url page="about" op="subscriptions"}" target="_parent">{translate key="reader.subscribersOnly"}</a>
		{/if}
	{/if}
{/if}

{include file="article/comments.tpl"}

{include file="article/footer.tpl"}
