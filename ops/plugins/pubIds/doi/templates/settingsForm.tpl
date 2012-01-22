{**
 * plugins/pubIds/doi/templates/settingsForm.tpl
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * URN plugin settings
 *
 *}
{strip}
{assign var="pageTitle" value="plugins.pubIds.doi.manager.settings.doiSettings"}
{include file="common/header.tpl"}
{/strip}
<div id="doiSettings">
	<div id="description">{translate key="plugins.pubIds.doi.manager.settings.description"}</div>

	<div class="separator"></div>

	<br />

	<form method="post" action="{plugin_url path="settings"}">
		{include file="common/formErrors.tpl"}
		<table width="100%" class="data">
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="doiObjects" required="true" key="plugins.pubIds.doi.manager.settings.doiObjects"}</td>
				<td width="80%" class="value">
					<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.explainDois"}</span><br />
					<input type="checkbox" name="enableIssueDoi" id="enableIssueDoi" value="1"{if $enableIssueDoi} checked="checked"{/if} />
					<label for="enableIssueDoi">{translate key="plugins.pubIds.doi.manager.settings.enableIssueDoi"}</label><br />
					<input type="checkbox" name="enableArticleDoi" id="enableArticleDoi" value="1"{if $enableArticleDoi} checked="checked"{/if} />
					<label for="enableArticleDoi">{translate key="plugins.pubIds.doi.manager.settings.enableArticleDoi"}</label><br />
					<input type="checkbox" name="enableGalleyDoi" id="enableGalleyDoi" value="1"{if $enableGalleyDoi} checked="checked"{/if} />
					<label for="enableGalleyDoi">{translate key="plugins.pubIds.doi.manager.settings.enableGalleyDoi"}</label><br />
					<input type="checkbox" name="enableSuppFileDoi" id="enableSuppFileDoi" value="1"{if $enableSuppFileDoi} checked="checked"{/if} />
					<label for="enableSuppFileDoi">{translate key="plugins.pubIds.doi.manager.settings.enableSuppFileDoi"}</label><br />
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="doiPrefix" required="true" key="plugins.pubIds.doi.manager.settings.doiPrefix"}</td>
				<td width="80%" class="value">
					<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiPrefixDescription"}</span><br/>
					<br />
					<input type="text" name="doiPrefix" value="{$doiPrefix|escape}" size="8" maxlength="8" id="doiPrefix" class="textField" />
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td width="20%" class="label">{fieldLabel name="doiSuffix" key="plugins.pubIds.doi.manager.settings.doiSuffix"}</td>
				<td width="80%" class="value">
					<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixDescription"}</span><br />
					<br />
					<table width="100%" class="data">
						<tr>
							<td width="5%" class="label" align="right" valign="top">
								<input type="radio" name="doiSuffix" id="doiSuffix" value="pattern" {if $doiSuffix eq "pattern"}checked{/if} />
							</td>
							<td width="95%" class="value">
								{fieldLabel name="doiSuffix" key="plugins.pubIds.doi.manager.settings.doiSuffixPattern"}
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>
								<input type="text" name="doiIssueSuffixPattern" value="{$doiIssueSuffixPattern|escape}" size="15" maxlength="50" id="doiIssueSuffixPattern" class="textField" />
								<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixPattern.issues"}</span>
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>
								<input type="text" name="doiArticleSuffixPattern" value="{$doiArticleSuffixPattern|escape}" size="15" maxlength="50" id="doiArticleSuffixPattern" class="textField" />
								<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixPattern.articles"}</span>
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>
								<input type="text" name="doiGalleySuffixPattern" value="{$doiGalleySuffixPattern|escape}" size="15" maxlength="50" id="doiGalleySuffixPattern" class="textField" />
								<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixPattern.galleys"}</span>
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>
								<input type="text" name="doiSuppFileSuffixPattern" value="{$doiSuppFileSuffixPattern|escape}" size="15" maxlength="50" id="doiSuppFileSuffixPattern" class="textField" />
								<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixPattern.suppFiles"}</span>
							</td>
						</tr>
						<tr>
							<td>&nbsp;</td>
							<td>
								<span class="instruct">{fieldLabel name="doiSuffixPattern" key="plugins.pubIds.doi.manager.settings.doiSuffixPattern.example"}</span>
							</td>
						</tr>
						<tr>
							<td width="5%" class="label" align="right" valign="top">
								<input type="radio" name="doiSuffix" id="doiSuffixDefault" value="default" {if !in_array($doiSuffix, array("pattern", "publisherId", "customId"))}checked{/if} />
							</td>
							<td width="95%" class="value">
								{fieldLabel name="doiSuffixDefault" key="plugins.pubIds.doi.manager.settings.doiSuffixDefault"}
								<br />
								<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiSuffixDefault.description"}</span>
							</td>
						</tr>
						<tr>
							<td width="5%" class="label" align="right" valign="top">
								<input type="radio" name="doiSuffix" id="doiSuffixPublisherId" value="publisherId" {if $doiSuffix eq "publisherId"}checked{/if} />
							</td>
							<td width="95%" class="value">
								{fieldLabel name="doiSuffixPublisherId" key="plugins.pubIds.doi.manager.settings.doiSuffixPublisherId"}
							</td>
						</tr>
						<tr>
							<td width="5%" class="label" align="right" valign="top">
								<input type="radio" name="doiSuffix" id="doiSuffixCustomIdentifier" value="customId" {if $doiSuffix eq "customId"}checked{/if} />
							</td>
							<td width="95%" class="value">
								{fieldLabel name="doiSuffixCustomIdentifier" key="plugins.pubIds.doi.manager.settings.doiSuffixCustomIdentifier"}
							</td>
						</tr>

					</table>
				</td>
			</tr>
			<tr><td colspan="2">&nbsp;</td></tr>
			<tr valign="top">
				<td class="label">&nbsp;</td>
				<td class="value">
					<span class="instruct">{translate key="plugins.pubIds.doi.manager.settings.doiReassign.description"}</span><br/>
					<input type="submit" name="clearPubIds" value="{translate key="plugins.pubIds.doi.manager.settings.doiReassign"}" onclick="return confirm('{translate|escape:"jsparam" key="plugins.pubIds.doi.manager.settings.doiReassign.confirm"}')" class="action" />
				</td>
			</tr>
		</table>

		<br/>

		<input type="submit" name="save" class="button defaultButton" value="{translate key="common.save"}"/>
		<input type="button" class="button" value="{translate key="common.cancel"}" onclick="history.go(-1)"/>
	</form>

	<p><span class="formRequired">{translate key="common.requiredField"}</span></p>
</div>
{include file="common/footer.tpl"}
