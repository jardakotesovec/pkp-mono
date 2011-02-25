{**
 * javascriptInit.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Contains all javascript that needs to be initialized on page load.
 *}
	<script type="text/javascript">{literal}
		// initialise plugins
		$(function(){
			$('ul.sf-menu').superfish(); // Initialize the navigation menu
			fontSize("#sizer", ".page", 9, 12, 20); // Initialize the font sizer
			$('.button').button();
			$("a.openHelp").each(function(){
				$(this).click(function() {openHelp($(this).attr('href')); return false;})
			});
			$(".tagit").live('click', function() {
				$(this).find('input').focus();
			});
		});
	{/literal}</script>
