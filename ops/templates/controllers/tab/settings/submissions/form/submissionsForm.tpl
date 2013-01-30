{**
 * templates/controllers/tab/settings/submissions/form/submissionsForm.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Step 2 of journal setup.
 *
 *}

<script>
	$(function() {ldelim}
		// Attach the form handler.
		$('#submissionSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="submissionSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT component="tab.settings.JournalSettingsTabHandler" op="saveFormData" tab="submissions"}">

{include file="controllers/notification/inPlaceNotification.tpl" notificationId="submissionsFormNotification"}
{include file="controllers/tab/settings/wizardMode.tpl" wizardMode=$wizardMode}

<div id="focusAndScopeDescription">
<h3>2.1 {translate key="manager.setup.focusAndScopeOfJournal"}</h3>
<p>{translate key="manager.setup.focusAndScopeDescription"}</p>
<p>
	<textarea name="focusScopeDesc[{$formLocale|escape}]" id="focusScopeDesc" rows="12" cols="60" class="textArea richContent">{$focusScopeDesc[$formLocale]|escape}</textarea>
</p>
</div>

<div class="separator"></div>

<div id="peerReviewPolicy">
<h3>2.2 {translate key="manager.setup.peerReviewPolicy"}</h3>
<div id="peerReviewDescription">
<p>{translate key="manager.setup.peerReviewDescription"}</p>

<h4>{translate key="manager.setup.reviewPolicy"}</h4>

<p><textarea name="reviewPolicy[{$formLocale|escape}]" id="reviewPolicy" rows="12" cols="60" class="textArea richContent">{$reviewPolicy[$formLocale]|escape}</textarea></p>
</div>
<div id="reviewGuidelinesInfo">

<h4>{translate key="manager.setup.reviewGuidelines"}</h4>

{url|assign:"reviewFormsUrl" router=$smarty.const.ROUTE_PAGE op="reviewForms"}
<p>{translate key="manager.setup.reviewGuidelinesDescription" reviewFormsUrl=$reviewFormsUrl}</p>

<p><textarea name="reviewGuidelines[{$formLocale|escape}]" id="reviewGuidelines" rows="12" cols="60" class="textArea richContent">{$reviewGuidelines[$formLocale]|escape}</textarea></p>
</div>
<div id="reviewProcess">
<h4>{translate key="manager.setup.reviewProcess"}</h4>

<p>{translate key="manager.setup.reviewProcessDescription"}</p>

<table class="data">
	<tr>
		<td width="5%" class="label" align="right">
			<input type="radio" name="mailSubmissionsToReviewers" id="mailSubmissionsToReviewers-0" value="0"{if not $mailSubmissionsToReviewers} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="mailSubmissionsToReviewers-0"><strong>{translate key="manager.setup.reviewProcessStandard"}</strong></label>
			<br />
			<span class="instruct">{translate key="manager.setup.reviewProcessStandardDescription"}</span>
		</td>
	</tr>
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	<tr>
		<td width="5%" class="label" align="right">
			<input type="radio" name="mailSubmissionsToReviewers" id="mailSubmissionsToReviewers-1" value="1"{if $mailSubmissionsToReviewers} checked="checked"{/if} />
		</td>
		<td class="value">
			<label for="mailSubmissionsToReviewers-1"><strong>{translate key="manager.setup.reviewProcessEmail"}</strong></label>
			<br />
			<span class="instruct">{translate key="manager.setup.reviewProcessEmailDescription"}</span>
		</td>
	</tr>
</table>
</div>
<div id="reviewOptions">
<h4>{translate key="manager.setup.reviewOptions"}</h4>

	<script>
		{literal}
			function toggleAllowSetInviteReminder(form) {
				form.numDaysBeforeInviteReminder.disabled = !form.numDaysBeforeInviteReminder.disabled;
			}
			function toggleAllowSetSubmitReminder(form) {
				form.numDaysBeforeSubmitReminder.disabled = !form.numDaysBeforeSubmitReminder.disabled;
			}
		{/literal}
	</script>

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewTime"}</strong><br/>
	{translate key="manager.setup.reviewOptions.numWeeksPerReview"}: <input type="text" name="numWeeksPerReview" id="numWeeksPerReview" value="{$numWeeksPerReview|escape}" size="2" maxlength="8" class="textField" /> {translate key="common.weeks"}<br/>
	{translate key="common.note"}: {translate key="manager.setup.reviewOptions.noteOnModification"}
</p>

	<p>
		<strong>{translate key="manager.setup.reviewOptions.reviewerReminders"}</strong><br/>
		{translate key="manager.setup.reviewOptions.automatedReminders"}:<br/>
		<input type="checkbox" name="remindForInvite" id="remindForInvite" value="1" onclick="toggleAllowSetInviteReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled" {elseif $remindForInvite} checked="checked"{/if} />&nbsp;
		<label for="remindForInvite">{translate key="manager.setup.reviewOptions.remindForInvite1"}</label>
		<select name="numDaysBeforeInviteReminder" size="1" class="selectMenu"{if not $remindForInvite || !$scheduledTasksEnabled} disabled="disabled"{/if}>
			{section name="inviteDayOptions" start=3 loop=11}
			<option value="{$smarty.section.inviteDayOptions.index}"{if $numDaysBeforeInviteReminder eq $smarty.section.inviteDayOptions.index or ($smarty.section.inviteDayOptions.index eq 5 and not $remindForInvite)} selected="selected"{/if}>{$smarty.section.inviteDayOptions.index}</option>
			{/section}
		</select>
		{translate key="manager.setup.reviewOptions.remindForInvite2"}
		<br/>

		<input type="checkbox" name="remindForSubmit" id="remindForSubmit" value="1" onclick="toggleAllowSetSubmitReminder(this.form)"{if !$scheduledTasksEnabled} disabled="disabled"{elseif $remindForSubmit} checked="checked"{/if} />&nbsp;
		<label for="remindForSubmit">{translate key="manager.setup.reviewOptions.remindForSubmit1"}</label>
		<select name="numDaysBeforeSubmitReminder" size="1" class="selectMenu"{if not $remindForSubmit || !$scheduledTasksEnabled} disabled="disabled"{/if}>
			{section name="submitDayOptions" start=0 loop=11}
				<option value="{$smarty.section.submitDayOptions.index}"{if $numDaysBeforeSubmitReminder eq $smarty.section.submitDayOptions.index} selected="selected"{/if}>{$smarty.section.submitDayOptions.index}</option>
		{/section}
		</select>
		{translate key="manager.setup.reviewOptions.remindForSubmit2"}
		{if !$scheduledTasksEnabled}
		<br/>
		{translate key="manager.setup.reviewOptions.automatedRemindersDisabled"}
		{/if}
	</p>

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewerRatings"}</strong><br/>
	<input type="checkbox" name="rateReviewerOnQuality" id="rateReviewerOnQuality" value="1"{if $rateReviewerOnQuality} checked="checked"{/if} />&nbsp;
	<label for="rateReviewerOnQuality">{translate key="manager.setup.reviewOptions.onQuality"}</label>
</p>

<p>
	<strong>{translate key="manager.setup.reviewOptions.reviewerAccess"}</strong><br/>
	<input type="checkbox" name="reviewerAccessKeysEnabled" id="reviewerAccessKeysEnabled" value="1"{if $reviewerAccessKeysEnabled} checked="checked"{/if} />&nbsp;
	<label for="reviewerAccessKeysEnabled">{translate key="manager.setup.reviewOptions.reviewerAccessKeysEnabled"}</label><br/>
	<span class="instruct">{translate key="manager.setup.reviewOptions.reviewerAccessKeysEnabled.description"}</span><br/>
	<input type="checkbox" name="restrictReviewerFileAccess" id="restrictReviewerFileAccess" value="1"{if $restrictReviewerFileAccess} checked="checked"{/if} />&nbsp;
	<label for="restrictReviewerFileAccess">{translate key="manager.setup.reviewOptions.restrictReviewerFileAccess"}</label>
</p>

<p>
	<strong>{translate key="manager.setup.reviewOptions.blindReview"}</strong><br/>
	<input type="checkbox" name="showEnsuringLink" id="showEnsuringLink" value="1"{if $showEnsuringLink} checked="checked"{/if} />&nbsp;
	{get_help_id|assign:"blindReviewHelpId" key="editorial.sectionEditorsRole.review.blindPeerReview" url="true"}
	<label for="showEnsuringLink">{translate key="manager.setup.reviewOptions.showEnsuringLink" blindReviewHelpId=$blindReviewHelpId}</label><br/>
</p>
</div>
</div>
<div class="separator"></div>
<div id="privacyStatementInfo">
<h3>2.3 {translate key="manager.setup.privacyStatement"}</h3>

<p><textarea name="privacyStatement[{$formLocale|escape}]" id="privacyStatement" rows="12" cols="60" class="textArea richContent">{$privacyStatement[$formLocale]|escape}</textarea></p>
</div>

<div class="separator"></div>

<div id="editorDecision">
<h3>2.4 {translate key="manager.setup.editorDecision"}</h3>

<p><input type="checkbox" name="notifyAllAuthorsOnDecision" id="notifyAllAuthorsOnDecision" value="1"{if $notifyAllAuthorsOnDecision} checked="checked"{/if} /> <label for="notifyAllAuthorsOnDecision">{translate key="manager.setup.notifyAllAuthorsOnDecision"}</label></p>
</div>
<div class="separator"></div>

<div id="addItemtoAboutJournal">
<h3>2.5 {translate key="manager.setup.addItemtoAboutJournal"}</h3>

<table class="data">
{foreach name=customAboutItems from=$customAboutItems[$formLocale] key=aboutId item=aboutItem}
	<tr>
		<td width="5%" class="label">{fieldLabel name="customAboutItems-$aboutId-title" key="common.title"}</td>
		<td class="value"><input type="text" name="customAboutItems[{$formLocale|escape}][{$aboutId|escape}][title]" id="customAboutItems-{$aboutId|escape}-title" value="{$aboutItem.title|escape}" size="40" maxlength="255" class="textField richContent" />{if $smarty.foreach.customAboutItems.total > 1} <input type="submit" name="delCustomAboutItem[{$aboutId|escape}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="customAboutItems-$aboutId-content" key="manager.setup.aboutItemContent"}</td>
		<td class="value"><textarea name="customAboutItems[{$formLocale|escape}][{$aboutId|escape}][content]" id="customAboutItems-{$aboutId|escape}-content" rows="12" cols="40" class="textArea">{$aboutItem.content|escape}</textarea></td>
	</tr>
	{if !$smarty.foreach.customAboutItems.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr>
		<td class="label">{fieldLabel name="customAboutItems-0-title" key="common.title"}</td>
		<td class="value"><input type="text" name="customAboutItems[{$formLocale|escape}][0][title]" id="customAboutItems-0-title" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="customAboutItems-0-content" key="manager.setup.aboutItemContent"}</td>
		<td class="value"><textarea name="customAboutItems[{$formLocale|escape}][0][content]" id="customAboutItems-0-content" rows="12" cols="40" class="textArea"></textarea></td>
	</tr>
{/foreach}
</table>

<p><input type="submit" name="addCustomAboutItem" value="{translate key="manager.setup.addAboutItem"}" class="button" /></p>
</div>
<div class="separator"></div>

<div id="journalArchiving">
<h3>2.6 {translate key="manager.setup.journalArchiving"}</h3>

<p>{translate key="manager.setup.lockssDescription"}</p>

{url|assign:"lockssExistingArchiveUrl" router=$smarty.const.ROUTE_PAGE page="manager" op="email" template="LOCKSS_EXISTING_ARCHIVE"}
{url|assign:"lockssNewArchiveUrl" router=$smarty.const.ROUTE_PAGE page="manager" op="email" template="LOCKSS_NEW_ARCHIVE"}
<p>{translate key="manager.setup.lockssRegister" lockssExistingArchiveUrl=$lockssExistingArchiveUrl lockssNewArchiveUrl=$lockssNewArchiveUrl}</p>

{url|assign:"lockssUrl" router=$smarty.const.ROUTE_PAGE page="gateway" op="lockss"}
<p><input type="checkbox" name="enableLockss" id="enableLockss" value="1"{if $enableLockss} checked="checked"{/if} /> <label for="enableLockss">{translate key="manager.setup.lockssEnable" lockssUrl=$lockssUrl}</label></p>

<p>
	<textarea name="lockssLicense[{$formLocale|escape}]" id="lockssLicense" rows="6" cols="60" class="textArea richContent">{$lockssLicense[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.lockssLicenses"}</span>
</p>
</div>

<p>{translate key="manager.setup.clockssDescription"}</p>

<p>{translate key="manager.setup.clockssRegister"}</p>

{url|assign:"clockssUrl" router=$smarty.const.ROUTE_PAGE page="gateway" op="clockss"}
<p><input type="checkbox" name="enableClockss" id="enableClockss" value="1"{if $enableClockss} checked="checked"{/if} /> <label for="enableClockss">{translate key="manager.setup.clockssEnable" clockssUrl=$clockssUrl}</label></p>

<p>
	<textarea name="clockssLicense[{$formLocale|escape}]" id="clockssLicense" rows="6" cols="60" class="textArea richContent">{$clockssLicense[$formLocale]|escape}</textarea>
	<br />
	<span class="instruct">{translate key="manager.setup.clockssLicenses"}</span>
</p>
</div>

<div class="separator"></div>

<div id="reviewerDatabaseLink">
<h3>2.7 {translate key="manager.setup.reviewerDatabaseLink"}</h3>

<p>{translate key="manager.setup.reviewerDatabaseLink.desc"}</p>

<table class="data">
{foreach name=reviewerDatabaseLinks from=$reviewerDatabaseLinks key=reviewerDatabaseLinkId item=reviewerDatabaseLink}
	<tr>
		<td width="5%" class="label">{fieldLabel name="reviewerDatabaseLinks-$reviewerDatabaseLinkId-title" key="common.title"}</td>
		<td class="value"><input type="text" name="reviewerDatabaseLinks[{$reviewerDatabaseLinkId|escape}][title]" id="reviewerDatabaseLinks-{$reviewerDatabaseLinkId|escape}-title" value="{$reviewerDatabaseLink.title|escape}" size="40" maxlength="255" class="textField" />{if $smarty.foreach.reviewerDatabaseLinks.total > 1} <input type="submit" name="delReviewerDatabaseLink[{$reviewerDatabaseLinkId|escape}]" value="{translate key="common.delete"}" class="button" />{/if}</td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="reviewerDatabaseLinks-$reviewerDatabaseLinkId-url" key="common.url"}</td>
		<td class="value"><input type="text" name="reviewerDatabaseLinks[{$reviewerDatabaseLinkId|escape}][url]" id="reviewerDatabaseLinks-{$reviewerDatabaseLinkId|escape}-url" value="{$reviewerDatabaseLink.url|escape}" size="40" maxlength="255" class="textField" /></td>
	</tr>
	{if !$smarty.foreach.reviewerDatabaseLinks.last}
	<tr>
		<td colspan="2" class="separator">&nbsp;</td>
	</tr>
	{/if}
{foreachelse}
	<tr>
		<td class="label">{fieldLabel name="reviewerDatabaseLinks-0-title" key="common.title"}</td>
		<td class="value"><input type="text" name="reviewerDatabaseLinks[0][title]" id="reviewerDatabaseLinks-0-title" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
	<tr>
		<td class="label">{fieldLabel name="reviewerDatabaseLinks-0-url" key="common.url"}</td>
		<td class="value"><input type="text" name="reviewerDatabaseLinks[0][url]" id="reviewerDatabaseLinks-0-url" value="" size="40" maxlength="255" class="textField" /></td>
	</tr>
{/foreach}
</table>

{if !$wizardMode}
	{fbvFormButtons id="setupFormSubmit" submitText="common.save" hideCancel=true}
{/if}

</form>
