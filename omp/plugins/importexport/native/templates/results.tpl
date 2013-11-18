{**
 * plugins/importexport/native/templates/results.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}

{translate key="plugins.importexport.native.importComplete"}
<ul>
	{foreach from=$submissions item=submission}
		<li>{$submission->getLocalizedTitle()|strip_unsafe_html}</li>
	{/foreach}
</ul>
<a href="{plugin_url path="index"}">{translate key="common.back"}</a>
