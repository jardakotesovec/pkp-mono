{**
 * templates/catalog/book/bookFiles.tpl
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Displays a book file list with download/payment links in the public catalog.
 *}
{foreach from=$availableFiles[$publicationFormatId] item=availableFile}{* There will be at most one of these *}
	<li>
		<div class="publicationFormatName">{$availableFile->getLocalizedName()|escape}</div>
		<div class="publicationFormatLink">
			<a href="{url op="download" path=$publishedMonograph->getId()|to_array:$publicationFormatId:$availableFile->getFileIdAndRevision()}"><span title="{$availableFile->getDocumentType()|upper|escape}" class="sprite {$availableFile->getDocumentType()|escape}"></span>{if $availableFile->getDirectSalesPrice()}{translate key="payment.directSales.purchase amount=$availableFile->getDirectSalesPrice() currency=$currency}{else}{translate key="payment.directSales.download"}<span title="{translate key="monograph.accessLogoOpen.altText"}" class="sprite openaccess"></span>{/if}</a>
		</div>
	</li>
{/foreach}
