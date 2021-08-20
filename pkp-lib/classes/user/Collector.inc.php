<?php
/**
 * @file classes/user/Collector.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Collector
 *
 * @brief A helper class to configure a Query Builder to get a collection of users
 */

namespace PKP\user;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

use PKP\core\interfaces\CollectorInterface;
use PKP\core\PKPString;
use PKP\identity\Identity;
use PKP\plugins\HookRegistry;

class Collector implements CollectorInterface
{
    /** @var DAO */
    public $dao;

    /** @var array|null */
    public $userGroupIds = null;

    /** @var array|null */
    public $roleIds = null;

    /** @var array|null */
    public $userIds = null;

    /** @var array|null */
    public $workflowStageIds = null;

    /** @var array|null */
    public $contextIds = null;

    /** @var boolean|null */
    public $disabled = null;

    /** @var boolean */
    public $includeReviewerData = false;

    /** @var array|null */
    public $assignedSectionIds = null;

    /** @var array|null */
    public $assignedCategoryIds = null;

    /** @var array|null */
    public $settings = null;

    /** @var ?string */
    public $searchPhrase = null;

    /** @var array|null */
    public $excludeSubmissionStage = null;

    /** @var array|null */
    public $submissionAssignment = null;

    /** @var int|null */
    public $count = null;

    /** @var int|null */
    public $offset = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Limit results to users in these user groups
     */
    public function filterByUserGroupIds(?array $userGroupIds): self
    {
        $this->userGroupIds = $userGroupIds;
        return $this;
    }

    public function filterByUserIds(?array $userIds): self
    {
        $this->userIds = $userIds;
        return $this;
    }

    /**
     * Limit results to users enrolled in these roles
     */
    public function filterByRoleIds(?array $roleIds): self
    {
        $this->roleIds = $roleIds;
        return $this;
    }

    /**
     * Limit results to users enrolled in these roles
     */
    public function filterByWorkflowStageIds(?array $workflowStageIds): self
    {
        $this->workflowStageIds = $workflowStageIds;
        return $this;
    }


    /**
     * Limit results to users with user groups in these context IDs
     */
    public function filterByContextIds(?array $contextIds): self
    {
        $this->contextIds = $contextIds;
        return $this;
    }

    public function includeReviewerData(bool $includeReviewerData = true): self
    {
        $this->includeReviewerData = $includeReviewerData;
        return $this;
    }

    /**
     * Retrieve a set of users not assigned to a given submission stage as a user group.
     * (Replaces UserStageAssignmentDAO::getUsersNotAssignedToStageInUserGroup)
     */
    public function filterExcludeSubmissionStage(int $submissionId, int $stageId, int $userGroupId): self
    {
        $this->excludeSubmissionStage = [
            'submission_id' => $submissionId,
            'stage_id' => $stageId,
            'user_group_id' => $userGroupId,
        ];
        return $this;
    }

    /**
     * Retrieve StageAssignments by submission and stage IDs.
     * (Replaces UserStageAssignmentDAO::getUsersBySubmissionAndStageId)
     */
    public function filterSubmissionAssignment(?int $submissionId, ?int $stageId = null, ?int $userGroupId = null): self
    {
        if ($submissionId === null) {
            // Clear the condition.
            $this->submissionAssignment = null;
            if ($stageId !== null || $userGroupId !== null) {
                throw new \InvalidArgumentException('If a stage or user group ID is specified, a submission ID must be specified as well.');
            }
        } else {
            $this->submissionAssignment = [
                'submission_id' => $submissionId,
                'stage_id' => $stageId,
                'user_group_id' => $userGroupId,
            ];
        }
        return $this;
    }

    /**
     * Filter by disabled/enabled status.
     *
     * @param $disabled boolean true iff only disabled users should be returned; false iff only enabled users should be returned; null if both can be included.
     */
    public function filterByDisabled(?bool $disabled = true): self
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * Filter by assigned subeditor section IDs
     */
    public function filterByAssignedSectionIds(?array $sectionIds): self
    {
        $this->assignedSectionIds = $sectionIds;
        return $this;
    }

    /**
     * Filter by assigned subeditor section IDs
     */
    public function filterByAssignedCategoryIds(?array $categoryIds): self
    {
        $this->assignedCategoryIds = $categoryIds;
        return $this;
    }

    public function filterBySettings(?array $settings): self
    {
        $this->settings = $settings;
        return $this;
    }

    /**
     * Limit results to users matching this search query
     */
    public function searchPhrase(?string $phrase): self
    {
        $this->searchPhrase = $phrase;
        return $this;
    }

    /**
     * Limit the number of objects retrieved
     */
    public function limit(?int $count): self
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Offset the number of objects retrieved, for example to
     * retrieve the second page of contents
     */
    public function offset(?int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @copydoc CollectorInterface::getQueryBuilder()
     */
    public function getQueryBuilder(): Builder
    {
        $q = DB::table('users AS u')
            ->select('u.*')
            ->when($this->userGroupIds !== null || $this->roleIds !== null || $this->contextIds !== null || $this->workflowStageIds !== null, function ($query) {
                return $query->whereIn('u.user_id', function ($query) {
                    return $query->select('uug.user_id')
                        ->from('user_user_groups AS uug')
                        ->join('user_groups AS ug', 'uug.user_group_id', '=', 'ug.user_group_id')
                        ->when($this->userGroupIds !== null, function ($query) {
                            return $query->whereIn('uug.user_group_id', $this->userGroupIds);
                        })
                        ->when($this->workflowStageIds !== null, function ($query) {
                            $query->join('user_group_stage AS ugs', 'ug.user_group_id', '=', 'ugs.user_group_id')
                                ->whereIn('ugs.stage_id', $this->workflowStageIds);
                        })
                        ->when($this->roleIds !== null, function ($query) {
                            return $query->whereIn('ug.role_id', $this->roleIds);
                        })
                        ->when($this->contextIds !== null, function ($query) {
                            return $query->whereIn('ug.context_id', $this->contextIds);
                        });
                });
            })
            ->when($this->userIds !== null, function ($query) {
                $query->whereIn('u.user_id', $this->userIds);
            })
            ->when($this->settings !== null, function ($query) {
                foreach ($this->settings as $settingName => $value) {
                    $query->whereIn('u.user_id', function ($query) {
                        return $query->select('user_id')
                            ->from('user_settings')
                            ->where('setting_name', '=', $settingName)
                            ->where('setting_value', '=', $value);
                    });
                }
            })
            ->when($this->excludeSubmissionStage !== null, function ($query) {
                $query->join('user_user_groups AS uug_exclude', 'u.user_id', '=', 'uug_exclude.user_id')
                    ->join('user_group_stage AS ugs_exclude', function ($join) {
                        return $join->on('uug_exclude.user_group_id', '=', 'ugs_exclude.user_group_id')
                            ->where('ugs_exclude.stage_id', '=', $this->excludeSubmissionStage['stage_id']);
                    })
                    ->leftJoin('stage_assignments AS sa_exclude', function ($join) {
                        return $join->on('sa_exclude.user_id', '=', 'uug_exclude.user_id')
                            ->on('sa_exclude.user_group_id', '=', 'uug_exclude.user_group_id')
                            ->where('sa_exclude.submission_id', '=', $this->excludeSubmissionStage['submission_id']);
                    })
                    ->where('uug_exclude.user_group_id', '=', $this->excludeSubmissionStage['user_group_id'])
                    ->whereNull('sa_exclude.user_group_id');
            })
            ->when($this->submissionAssignment !== null, function ($query) {
                return $query->whereIn('u.user_id', function ($query) {
                    return $query->select('sa.user_id')
                        ->from('stage_assignments AS sa')
                        ->join('user_group_stage AS ugs', 'sa.user_group_id', '=', 'ugs.user_group_id')
                        ->when(isset($this->submissionAssignment['submission_id']), function ($query) {
                            return $query->where('sa.submission_id', '=', $this->submissionAssignment['submission_id']);
                        })
                        ->when(isset($this->submissionAssignment['stage_id']), function ($query) {
                            return $query->where('ugs.stage_id', '=', $this->submissionAssignment['stage_id']);
                        })
                        ->when(isset($this->submissionAssignment['user_group_id']), function ($query) {
                            return $query->where('sa.user_group_id', '=', $this->submissionAssignment['user_group_id']);
                        });
                });
            })
            ->when($this->disabled !== null, function ($query) {
                $query->where('u.disabled', '=', $this->disabled);
            })
            ->when($this->assignedSectionIds !== null, function ($query) {
                $query->whereIn('u.user_id', function ($query) {
                    return $query->select('user_id')
                        ->from('subeditor_submission_group')
                        ->where('assoc_type', '=', ASSOC_TYPE_SECTION)
                        ->whereIn('assoc_id', $this->assignedSectionIds);
                });
            })
            ->when($this->assignedCategoryIds !== null, function ($query) {
                $query->whereIn('u.user_id', function ($query) {
                    return $query->select('user_id')
                        ->from('subeditor_submission_group')
                        ->where('assoc_type', '=', ASSOC_TYPE_CATEGORY)
                        ->whereIn('assoc_id', $this->assignedCategoryIds);
                });
            })
            ->when($this->searchPhrase !== null, function ($query) {
                $words = explode(' ', $this->searchPhrase);
                foreach ($words as $word) {
                    $query->whereIn('u.user_id', function ($query) use ($word) {
                        $likePattern = '%' . addcslashes(PKPString::strtolower($word), '%_') . '%';
                        return $query->select('u.user_id')
                            ->from('users AS u')
                            ->join('user_settings AS us', function ($join) {
                                $join->on('u.user_id', '=', 'us.user_id')
                                    ->whereIn('us.setting_name', [Identity::IDENTITY_SETTING_GIVENNAME, Identity::IDENTITY_SETTING_FAMILYNAME]);
                            })
                            ->where(DB::raw('LOWER(us.setting_value)'), 'LIKE', $likePattern)
                            ->orWhere(DB::raw('LOWER(email)'), 'LIKE', $likePattern)
                            ->orWhere(DB::raw('LOWER(username)'), 'LIKE', $likePattern);
                    });
                }
            })
            ->when($this->includeReviewerData, function ($query) {
                // Latest assigned review
                $query->leftJoin('review_assignments AS ra_latest', 'u.user_id', '=', 'ra_latest.reviewer_id')
                    ->leftJoin('review_assignments AS ra_latest_nonexistent', function ($join) {
                        $join->on('u.user_id', '=', 'ra_latest_nonexistent.reviewer_id')
                            ->on('ra_latest.review_id', '<', 'ra_latest_nonexistent.review_id');
                    })
                    ->whereNull('ra_latest_nonexistent.review_id')
                    ->addSelect('ra_latest.date_assigned AS last_assigned');

                // Review counts
                $query->addSelect([
                    DB::raw('(SELECT COALESCE(SUM(CASE WHEN ra.date_completed IS NULL AND ra.declined <> 1 THEN 1 ELSE 0 END), 0) FROM review_assignments AS ra WHERE u.user_id = ra.reviewer_id) as incomplete_count'),
                    DB::raw('(SELECT COALESCE(SUM(CASE WHEN ra.date_completed IS NOT NULL AND ra.declined <> 1 THEN 1 ELSE 0 END), 0) FROM review_assignments AS ra WHERE u.user_id = ra.reviewer_id) as complete_count'),
                    DB::raw('(SELECT COALESCE(SUM(CASE WHEN ra.declined = 1 THEN 1 ELSE 0 END), 0) FROM review_assignments AS ra WHERE u.user_id = ra.reviewer_id) as declined_count'),
                    DB::raw('(SELECT COALESCE(SUM(CASE WHEN ra.cancelled = 1 THEN 1 ELSE 0 END), 0) FROM review_assignments AS ra WHERE u.user_id = ra.reviewer_id) as cancelled_count'),
                    DB::raw('(SELECT COALESCE(SUM(CASE WHEN ra.cancelled = 1 THEN 1 ELSE 0 END), 0) FROM review_assignments AS ra WHERE u.user_id = ra.reviewer_id) as cancelled_count'),
                ]);

                switch (\Config::getVar('database', 'driver')) {
                    case 'mysql':
                    case 'mysqli':
                        $dateDiffClause = 'DATEDIFF(ra.date_completed, ra.date_notified)';
                        break;
                    default: // PostgreSQL
                        $dateDiffClause = 'DATE_PART(\'day\', ra.date_completed - ra.date_notified)';
                }
                $query->addSelect(DB::raw('(SELECT AVG(' . $dateDiffClause . ') FROM review_assignments AS ra WHERE u.user_id = ra.reviewer_id AND ra.date_completed IS NOT NULL) as average_time'));
                $query->addSelect(DB::raw('(SELECT AVG(ra.quality) FROM review_assignments AS ra WHERE u.user_id = ra.reviewer_id AND ra.quality IS NOT NULL) as reviewer_rating'));
            });

        // Limit and offset results for pagination
        if (!is_null($this->count)) {
            $q->limit($this->count);
        }
        if (!is_null($this->offset)) {
            $q->offset($this->offset);
        }

        // Add app-specific query statements
        HookRegistry::call('User::Collector::getQueryBuilder', [&$q, $this]);

        return $q;
    }
}
