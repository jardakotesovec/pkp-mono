<?php

declare(strict_types=1);

/**
 * @file classes/core/PKPQueueProvider.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPQueueProvider
 * @ingroup core
 *
 * @brief Registers Events Service Provider and boots data on events and their listeners
 */

namespace PKP\core;

use Illuminate\Queue\WorkerOptions;

use PKP\config\Config;
use PKP\Domains\Jobs\Job as PKPJobModel;

class PKPQueueProvider
{
    public function runJobsAtShutdown(): void
    {
        $shouldRun = Config::getVar('queues', 'run_jobs_at_shutdown', false);

        if (!$shouldRun) {
            return;
        }

        $job = PKPJobModel
            ::isavailable()
                ->notexcedeedattempts()
                ->limit(1)
                ->first();

        if (!$job) {
            return;
        }

        // $artisan = app()->make('Illuminate\Support\Facades\Artisan');
        // dd(app()->call([$artisan, 'call'], ['queue:worker' => '--once']));
        $laravelContainer = PKPContainer::getInstance();
        $options = new WorkerOptions(
            $job->getDelay(),
            $job->getAllowedMemory(),
            $job->getTimeout(),
            $job->getSleep(),
            $job->getMaxAttempts(),
            $job->getForceFlag(),
            $job->getStopWhenEmptyFlag(),
        );

        $result = $laravelContainer['queue.worker']->runNextJob(
            'database',
            $job->queue,
            $options
        );

        error_log(var_export($result, true));
    }

    public function register()
    {
        register_shutdown_function([$this, 'runJobsAtShutdown']);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\core\PKPQueueProvider', '\PKPQueueProvider');
}
