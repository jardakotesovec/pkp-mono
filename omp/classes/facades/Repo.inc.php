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
 * @brief Extends the base Repo facade with any overrides for OMP
 */

namespace APP\facades;

use Illuminate\Support\Facades\App;

use PKP\submissionFile\Repository as SubmissionFileRepository;

class Repo extends \PKP\facades\Repo
{
    public static function publication(): \APP\publication\Repository
    {
        return App::make(\APP\publication\Repository::class);
    }

    public static function submission(): \APP\submission\Repository
    {
        return App::make(\APP\submission\Repository::class);
    }

    public static function user(): \APP\user\Repository
    {
        return App::make(\APP\user\Repository::class);
    }

    public static function author(): \APP\author\Repository
    {
        return App::make(\APP\author\Repository::class);
    }

    public static function submissionFiles(): SubmissionFileRepository
    {
        return App::make(\APP\submissionFile\Repository::class);
    }
}
