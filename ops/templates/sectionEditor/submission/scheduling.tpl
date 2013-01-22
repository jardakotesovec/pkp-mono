{**
 * templates/sectionEditor/submission/scheduling.tpl
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the scheduling table.
 *
 *}
<div id="scheduling">
<h3>{translate key="submission.scheduling"}</h3>

<table class="data">
{if !$publicationFeeEnabled || $publicationPayment}
	<script>
		$(function() {ldelim}
			// Attach the form handler.
			$('#schedulingForm').pkpHandler('$.pkp.controllers.form.FormHandler');
		{rdelim});
	</script>
	<form class="pkp_form" id="schedulingForm" action="{url op="scheduleForPublication" path=$submission->getId()}" method="post">
		<tr>
			<td class="label">
				<label for="issueId">{translate key="editor.article.scheduleForPublication"}</label>
			</td>
			<td class="value">
				{if $publishedArticle}
					{assign var=issueId value=$publishedArticle->getIssueId()}
				{else}
					{assign var=issueId value=0}
				{/if}
				<select name="issueId" id="issueId" class="selectMenu">
					<option value="">{translate key="editor.article.scheduleForPublication.toBeAssigned"}</option>
					{html_options options=$issueOptions|truncate:40:"..." selected=$issueId}
				</select>
			</td>
			<td class="value">
				<input type="submit" value="{translate key="common.record"}" class="button defaultButton" />&nbsp;
				{if $issueId}
					{if $isEditor}
						<a href="{url op="issueToc" path=$issueId}" class="action">{translate key="issue.toc"}</a>
					{else}
						<a href="{url page="issue" op="view" path=$issueId}" class="action">{translate key="issue.toc"}</a>
					{/if}
				{/if}
			</td>
		</tr>
	</form>
	{if $publishedArticle}
		<script>
			$(function() {ldelim}
				// Attach the form handler.
				$('#setDatePublishedForm').pkpHandler('$.pkp.controllers.form.FormHandler');
			{rdelim});
		</script>
		<form class="pkp_form" id="setDatePublishedForm" action="{url op="setDatePublished" path=$submission->getId()}" method="post">
			<tr>
				<td class="label">
					<label for="issueId">{translate key="editor.issues.published"}</label>
				</td>
				<td class="value">
					{* Find good values for starting and ending year options *}
					{assign var=currentYear value=$smarty.now|date_format:"%Y"}
					{if $publishedArticle->getDatePublished()}
						{assign var=publishedYear value=$publishedArticle->getDatePublished()|date_format:"%Y"}
						{math|assign:"minYear" equation="min(x,y)-10" x=$publishedYear y=$currentYear}
						{math|assign:"maxYear" equation="max(x,y)+2" x=$publishedYear y=$currentYear}
					{else}
						{* No issue publication date info *}
						{math|assign:"minYear" equation="x-10" x=$currentYear}
						{math|assign:"maxYear" equation="x+2" x=$currentYear}
					{/if}
					{html_select_date prefix="datePublished" time=$publishedArticle->getDatePublished()|default:"---" all_extra="class=\"selectMenu\"" start_year=$minYear end_year=$maxYear year_empty="-" month_empty="-" day_empty="-"}
				</td>
				<td class="value">
					<input type="submit" value="{translate key="common.record"}" class="button defaultButton" />&nbsp;
				</td>
			</tr>
		</form>
	{/if}{* $publishedArticle *}
{else}
	<tr>
		<td>{translate key="editor.article.payment.publicationFeeNotPaid"}</td>
		<td align="right">
			<script>
				$(function() {ldelim}
					// Attach the form handler.
					$('#markAsPaidForm').pkpHandler('$.pkp.controllers.form.FormHandler');
				{rdelim});
			</script>
			<form class="pkp_form" id="markAsPaidForm" action="{url op="waivePublicationFee" path=$submission->getId()}" method="post">
			<input type="hidden" name="markAsPaid" value=1 />
			<input type="hidden" name="sendToScheduling" value=1 />
			<input type="submit" value="{translate key="payment.paymentReceived"}" class="button defaultButton" />&nbsp;
			</form>
		</td>
		{if $isEditor}
			<td align="left">
				<script>
					$(function() {ldelim}
						// Attach the form handler.
						$('#waiveFeeForm').pkpHandler('$.pkp.controllers.form.FormHandler');
					{rdelim});
				</script>
				<form class="pkp_form" id="waiveFeeForm" action="{url op="waivePublicationFee" path=$submission->getId()}" method="post">
					<input type="hidden" name="sendToScheduling" value=1 />
					<input type="submit" value="{translate key="payment.waive"}" class="button defaultButton" />&nbsp;
				</form>
			</td>
		{/if}
	</tr>
{/if}
</table>
</div>
