{**
 * plugins/viewableFile/lensGalley/display.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of a PDF galley.
 *}
<script src="{$pluginLensPath}/lens.js"></script>
<script type="text/javascript">{literal}

	var linkElement = document.createElement("link");
	linkElement.rel = "stylesheet";
	linkElement.href = "{/literal}{$pluginLensPath|escape:"javascript"}{literal}/lens.css"; //Replace here

	document.head.appendChild(linkElement);

	$(document).ready(function(){
		var app = new Lens({
			document_url: "{/literal}{$xmlUrl|escape:'javascript'}{literal}"
		});
		app.start();
		window.app = app;
	});
{/literal}</script>
