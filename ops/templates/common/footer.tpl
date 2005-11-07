{**
 * footer.tpl
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Common site footer.
 *
 * $Id$
 *}

{if $pageFooter}
<br /><br />
{$pageFooter}
{/if}
</div>
</div>
</div>
</div>

<div id="footer">
	<div id="footerContent">
		{get_debug_info}
		{if $enableDebugStats}
		<div class="debugStats">
		{translate key="debug.executionTime"}: {$debugExecutionTime|string_format:"%.4f"}s<br />
		{translate key="debug.databaseQueries"}: {$debugNumDatabaseQueries}<br/>
		{if $debugNotes}
			<strong>{translate key="debug.notes"}</strong><br/>
			{foreach from=$debugNotes item=note}
				{translate key=$note[0] params=$note[1]}<br/>
			{/foreach}
		{/if}
		</div>
		{/if}
	</div>
</div>
</div>
</body>
</html>
