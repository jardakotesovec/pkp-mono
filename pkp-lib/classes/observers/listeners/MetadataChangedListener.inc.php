<?php

declare(strict_types=1);

/**
 * @file classes/observers/listeners/MetadataChangedListener.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DeletedSubmission
 * @ingroup core
 *
 * @brief Listener fired when submission metadata's changed
 */

namespace PKP\observers\listeners;

use PKP\Jobs\Metadata\MetadataChangedJob;
use PKP\observers\events\MetadataChanged;

class MetadataChangedListener
{
    /**
     * Handle the listener call
     *
     *
     */
    public function handle(MetadataChanged $event)
    {
        dispatch(new MetadataChangedJob($event->submission->getId()));
    }
}
