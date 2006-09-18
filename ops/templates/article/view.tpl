{**
 * view.tpl
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Article View.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$defaultCharset}" />
	<title>{$article->getFirstAuthor(true)|escape}</title>
	<link rel="stylesheet" href="{$baseUrl}/styles/common.css" type="text/css" />
	<link rel="alternate stylesheet" title="{translate key="icon.small.alt"}" href="{$baseUrl}/styles/small.css" type="text/css" />
	<link rel="stylesheet" title="{translate key="icon.medium.alt"}" href="{$baseUrl}/styles/medium.css" type="text/css" />
	<link rel="alternate stylesheet" title="{translate key="icon.large.alt"}" href="{$baseUrl}/styles/large.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/articleView.css" type="text/css" />
	{foreach from=$stylesheets item=cssUrl}
	<link rel="stylesheet" href="{$cssUrl}" type="text/css" />
	{/foreach}
	{$additionalHeadData}
</head>
<frameset cols="*,180" frameborder="0" framespacing="0" border="0">
	{if !$galley}
		{url|assign:"articleUrl" op="viewArticle" path=$articleId}
		{url|assign:"rstUrl" op="viewRST" path=$articleId}
	{else}
		{url|assign:"rstUrl" op="viewRST" path=$articleId|to_array:$galleyId}
		{if $galley->isHtmlGalley()}
			{url|assign:"articleUrl" op="viewArticle" path=$articleId|to_array:$galleyId}
		{elseif $galley->isPdfGalley()}
			{url|assign:"articleUrl" op="viewPDFInterstitial" path=$articleId|to_array:$galleyId}
		{else}
			{url|assign:"articleUrl" op="viewDownloadInterstitial" path=$articleId|to_array:$galleyId}
		{/if}
	{/if}
	<frame src="{$articleUrl}" frameborder="0"/>
	<frame src="{$rstUrl}" noresize="noresize" frameborder="0" scrolling="auto" />
<noframes>
<body>
	<table width="100%">
		<tr>
			<td align="center">
				{translate key="common.error.framesRequired" url=$articleUrl}
			</td>
		</tr>
	</table>
</body>
</noframes>
</frameset>
</html>
