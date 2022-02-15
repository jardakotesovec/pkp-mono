<?php

/**
 * @file classes/mail/mailables/ReviewRemind.php
 *
 * Copyright (c) 2014-2022 Simon Fraser University
 * Copyright (c) 2000-2022 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewRemind
 * @ingroup mail_mailables
 *
 * @brief Email is sent by an editor to a reviewer to remind about the review request
 */

namespace PKP\mail\mailables;

use APP\core\Application;
use PKP\context\Context;
use APP\facades\Repo;
use PKP\mail\Configurable;
use PKP\mail\Mailable;
use PKP\mail\Recipient;
use PKP\mail\Sender;
use PKP\mail\variables\ReviewAssignmentEmailVariable;
use PKP\security\AccessKeyManager;
use PKP\security\Validation;
use PKP\submission\PKPSubmission;
use PKP\submission\reviewAssignment\ReviewAssignment;

class ReviewRemind extends Mailable
{
    use Sender;
    use Recipient;
    use Configurable;

    public const EMAIL_KEY = 'REVIEW_REMIND';

    protected static ?string $name = 'mailable.reviewRemind.name';

    protected static ?string $description = 'mailable.reviewRemind.description';

    protected static array $groupIds = [self::GROUP_REVIEW];

    protected Context $context;
    protected ReviewAssignment $reviewAssignment;

    public function __construct(Context $context, PKPSubmission $submission, ReviewAssignment $reviewAssignment)
    {
        parent::__construct(func_get_args());
        $this->context = $context;
        $this->reviewAssignment = $reviewAssignment;
    }

    /*
     * Override reviewAssignmentUrl template variable if one-click reviewer access is enabled and add passwordResetUrl
     */
    public function setData(?string $locale = null)
    {
        parent::setData($locale);
        $reviewerId = $this->reviewAssignment->getData('reviewerId');
        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();

        if ($this->context->getData('reviewerAccessKeysEnabled')) {
            $accessKeyManager = new AccessKeyManager();
            $expiryDays = ($this->context->getData('numWeeksPerReview') + 4) * 7;
            $accessKey = $accessKeyManager->createKey(
                $this->context->getId(),
                $reviewerId,
                $this->reviewAssignment->getId(), $expiryDays
            );
            $reviewUrlArgs = [
                'submissionId' => $this->reviewAssignment->getSubmissionId(),
                'reviewId' => $this->reviewAssignment->getId(),
                'key' => $accessKey,
            ];

            $this->viewData[ReviewAssignmentEmailVariable::REVIEW_ASSIGNMENT_URL] =
                $dispatcher->url(
                    $request,
                    Application::ROUTE_PAGE,
                    null,
                    'reviewer',
                    'submission',
                    null,
                    $reviewUrlArgs
            );
        }

        // Old Review Remind template contains additional variable not supplied by _Variable classes
        $reviewer = Repo::user()->get($reviewerId);
        $this->viewData['passwordResetUrl'] =
            $dispatcher->url(
                $request,
                Application::ROUTE_PAGE,
                null,
                'login',
                'resetPassword',
                $reviewer->getUsername(),
                ['confirm' => Validation::generatePasswordResetHash($reviewerId)]
            );
    }
}
