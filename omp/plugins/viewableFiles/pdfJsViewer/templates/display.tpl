{**
 * plugins/viewableFile/pdfSubmissionFile/display.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @brief Embedded viewing of a PDF galley.
 *
 * @uses $publishedMonograph Monograph Monograph this file is attached to
 * @uses $downloadUrl string (optional) A URL to download this file
 * @uses $pluginUrl string URL to this plugin's files
 *}
{* Display Google Scholar metadata *}
{include file="frontend/objects/monographFile_googleScholar.tpl" monograph=$publishedMonograph}

<div class="viewable_file_frame">
    <iframe class="viewable_file_frame" src="{$pluginUrl}/pdf.js/web/viewer.html?file={$downloadUrl|escape:"url"}" allowfullscreen webkitallowfullscreen></iframe>
</div>
