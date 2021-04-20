<?php
/**
 * @file classes/services/StatsEditorialService.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPStatsEditorialService
 * @ingroup services
 *
 * @brief Helper class that encapsulates business logic for getting
 *   editorial stats
 */

namespace APP\Services;

class StatsEditorialService extends \PKP\Services\PKPStatsEditorialService
{
    /**
     * Get overview of key editorial stats
     *
     * @copydoc PKPStatsEditorialService::getOverview()
     */
    public function getOverview($args = [])
    {
        import('classes.workflow.EditorDecisionActionsManager');
        import('lib.pkp.classes.submission.PKPSubmission');
        \AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_MANAGER);

        $received = $this->countSubmissionsReceived($args);
        $accepted = $this->countByDecisions(SUBMISSION_EDITOR_DECISION_ACCEPT, $args);
        $declinedDesk = $this->countByDecisions(SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE, $args);
        $declinedReview = $this->countByDecisions(SUBMISSION_EDITOR_DECISION_DECLINE, $args);
        $declined = $declinedDesk + $declinedReview;

        $overview = [
            [
                'key' => 'submissionsReceived',
                'name' => 'stats.name.submissionsReceived',
                'value' => $received,
            ],
            [
                'key' => 'submissionsAccepted',
                'name' => 'stats.name.submissionsAccepted',
                'value' => $accepted,
            ],
            [
                'key' => 'submissionsDeclined',
                'name' => 'stats.name.submissionsDeclined',
                'value' => $declined,
            ],
            [
                'key' => 'submissionsPublished',
                'name' => 'stats.name.submissionsPublished',
                'value' => $this->countSubmissionsPublished($args),
            ],
        ];

        \HookRegistry::call('EditorialStats::overview', [&$overview, $args]);

        return $overview;
    }


    /**
     * Process the sectionIds param when getting the query builder
     *
     * @param array $args
     */
    protected function getQueryBuilder($args = [])
    {
        $statsQB = parent::getQueryBuilder($args);
        if (!empty(($args['sectionIds']))) {
            $statsQB->filterBySections($args['sectionIds']);
        }
        return $statsQB;
    }
}
