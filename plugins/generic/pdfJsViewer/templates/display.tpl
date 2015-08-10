{**
 * plugins/viewableFile/pdfArticleGalley/display.tpl
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Embedded viewing of a PDF galley.
 *}
{if $galley}
	{url|assign:"pdfUrl" op="download" path=$article->getBestArticleId($currentJournal)|to_array:$galley->getBestGalleyId($currentJournal):$firstGalleyFile->getId() escape=false}
	<script type="text/javascript" src="{$pluginUrl}/pdf.js/build/pdf.js"></script>
	<script type="text/javascript">
		{literal}
			$(document).ready(function() {
				PDFJS.workerSrc='{/literal}{$pluginUrl}/pdf.js/build/pdf.worker.js{literal}';
				PDFJS.getDocument({/literal}'{$pdfUrl|escape:"javascript"}'{literal}).then(function(pdf) {
					// Using promise to fetch the page
					pdf.getPage(1).then(function(page) {
						var pdfCanvasContainer = $('#pdfCanvasContainer');
						var canvas = document.getElementById('pdfCanvas');
						canvas.height = pdfCanvasContainer.height();
						canvas.width = pdfCanvasContainer.width()-2; // 1px border each side
						var viewport = page.getViewport(canvas.width / page.getViewport(1.0).width);
						var context = canvas.getContext('2d');
						var renderContext = {
							canvasContext: context,
							viewport: viewport
						};
						page.render(renderContext);
					});
				});
			});
		{/literal}
	</script>
	<script type="text/javascript" src="{$pluginUrl}/pdf.js/web/viewer.js"></script>

	<div id="pdfCanvasContainer">
		<iframe src="{$pluginUrl}/pdf.js/web/viewer.html?file={$pdfUrl|escape:"url"}" width="100%" height="100%" style="min-height: 500px;" allowfullscreen webkitallowfullscreen></iframe> 
	</div>
{/if}
