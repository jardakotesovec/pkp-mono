{**
 * header.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common header for help pages.
 *
 * $Id$
 *}

<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>{translate key=$pageTitle}</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta name="description" content="" />
	<meta name="keywords" content="" />
	<link rel="stylesheet" href="{$baseUrl}/styles/default.css" type="text/css" />
	<link rel="stylesheet" href="{$baseUrl}/styles/help.css" type="text/css" />
	<script type="text/javascript" src="{$baseUrl}/js/general.js"></script>
</head>
<body>
{literal}<script type="text/javascript">if (self.blur) { self.focus(); }</script>{/literal}

<div id="navMenu">
<div id="searchBox">
	<form action="{$pageUrl}/help/search" method="post" style="display: inline">
	{translate key="common.search"}: <input type="text" name="keyword" size="16" maxlength="60" value="{$helpSearchKeyword}" class="textField" />
	</form>
</div>
{translate key="help.ojsHelp"}
</div>

<div id="container">
