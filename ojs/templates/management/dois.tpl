{extends file="layouts/backend.tpl"}
{block name="page"}
	<!-- Add page content here -->
	<h1 class="app__pageHeading">
        {translate key="doi.manager.displayName"}
	</h1>

	<tabs :track-history="true">
        {if $displaySubmissionsTab}
			<tab id="submission-doi-management" label="{translate key="article.articles"}">
				<h1>{translate key="article.articles"}</h1>
				<doi-list-panel
						v-bind="components.submissionDoiListPanel"
						@set="set"
				/>
			</tab>
        {/if}
        {if $displayIssuesTab}
			<tab id="issue-doi-management" label="{translate key="issue.issues"}">
				<h1>{translate key="issue.issues"}</h1>
				<doi-list-panel
						v-bind="components.issueDoiListPanel"
						@set="set"
				/>
			</tab>
        {/if}
	</tabs>
{/block}
