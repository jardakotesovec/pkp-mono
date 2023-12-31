<?php
/**
 * @file classes/mailable/Repository.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Repository
 *
 * @brief A repository to find and edit Mailables.
 */

namespace APP\mail;

use Illuminate\Support\Collection;

class Repository extends \PKP\mail\Repository
{
    /**
     * Registers app-specific mailables
     */
    public function map(): Collection
    {
        return parent::map()->merge(collect([
            mailables\DecisionSendInternalReviewNotifyAuthor::class,
        ]));
    }
}
