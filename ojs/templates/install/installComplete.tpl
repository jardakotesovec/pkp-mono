{**
 * installComplete.tpl
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display confirmation of successful installation.
 * If necessary, will also display new config file contents if config file could not be written.
 *
 * $Id$
 *}

{assign var="pageTitle" value="installer.ojsInstallation"}
{include file="common/header.tpl"}

{translate key="installer.installationComplete" indexUrl=$indexUrl}

{if $writeConfigFailed}
<br /><br />
{translate key="installer.overwriteConfigFileInstructions"}
<br /><br />

<form>
<div class="form">
{translate key="installer.contentsOfConfigFile"}:<br />
<textarea name="config" cols="80" rows="20" class="textAreaFixed">{$configFileContents|escape}</textarea>
</div>
</form>
{/if}

{if $manualInstall}
<br /><br />
{translate key="installer.manualSQLInstructions"}
<br /><br />

<form>
<div class="form">
{translate key="installer.installerSQLStatements"}:<br />
<textarea name="sql" cols="80" rows="20" class="textAreaFixed">{foreach from=$installSql item=sqlStmt}{$sqlStmt|escape};


{/foreach}</textarea>
</div>
</form>
{/if}

{include file="common/footer.tpl"}
