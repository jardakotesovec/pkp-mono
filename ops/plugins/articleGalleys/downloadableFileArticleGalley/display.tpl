{if $galley}
	<h3>{translate key="article.nonpdf.title"}</h3>
	{url|assign:"url" op="download" path=$article->getId()|to_array:$galley->getBestGalleyId($currentJournal)}
	<p>{translate key="article.nonpdf.note" url=$url}</p>

	<script>
		<!--
		var delay = 2000;
		setTimeout(function(){ldelim}
			window.location = '{$url}';
		{rdelim}, delay);
		// -->
	</script>
{/if}
