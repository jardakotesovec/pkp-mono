<?php

/**
 * @file classes/facade/Repo.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repo
 *
 * @brief Extends the base Repo facade with any overrides for OJS
 */

namespace APP\facades;

use APP\issue\Repository as IssueRepository;
use APP\publication\Repository as PublicationRepository;
use APP\submission\Repository as SubmissionRepository;
use APP\submissionFile\Repository as SubmissionFileRepository;
use APP\user\Repository as UserRepository;

use PKP\facades\Repo as BaseRepo;

class Repo extends BaseRepo
{
    public static function issue(): IssueRepository
    {
        return app()->make(IssueRepository::class);
    }

    public static function publication(): PublicationRepository
    {
        return app()->make(PublicationRepository::class);
    }

    public static function submission(): SubmissionRepository
    {
        return app()->make(SubmissionRepository::class);
    }

    public static function user(): UserRepository
    {
        return app()->make(UserRepository::class);
    }

    public static function submissionFile(): SubmissionFileRepository
    {
        return app()->make(SubmissionFileRepository::class);
    }
}
