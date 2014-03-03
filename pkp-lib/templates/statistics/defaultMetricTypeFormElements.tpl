{**
 * lib/pkp/templates/statistics/defaultMetricTypeFormElements.tpl
 *
 * Copyright (c) 2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Display elements responsible for defining the default metric type (both for site and contexts).
 *
 *}

{if count($availableMetricTypes) > 1}
	{fbvFormArea id="defaultMetricTypeFormArea" title="defaultMetric.title"}
		<p>{translate key="manager.statistics.defaultMetricDescription" contextObjectName=$contextObjectName}</p>
		{fbvFormSection label="defaultMetric.availableMetrics" inline=true}
			{fbvElement id="defaultMetricType" type=select from=$availableMetricTypes selected=$defaultMetricType translate=false}
		{/fbvFormSection}
	{/fbvFormArea}
{/if}