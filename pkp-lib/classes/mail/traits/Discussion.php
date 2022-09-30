<?php

/**
 * @file mail/Discussion.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Discussion
 * @ingroup mail
 *
 * @brief trait to support Discussion email template variables
 */

namespace PKP\mail\traits;

trait Discussion
{
    protected static string $discussionSubject = 'subject';
    protected static string $discussionContent = 'content';
    
    
    /**
     * Add a variable with comments from all completed review assignments
     *
     * @param string $subject
     * @param string $content
     */
    protected function setupDiscussionVariables(string $subject, string $content)
    {
        $this->addData([
            self::$discussionSubject => $subject,
            self::$discussionContent => $content,
        ]);
    }

    /**
     * Add the title of the discussion and the content of the current note to the list of registered variables
     */
    protected static function addDiscussionDescription(array $variables): array
    {
        $variables[self::$discussionSubject] = __('emailTemplate.variable.discussion.subject');
        $variables[self::$discussionContent] = __('emailTemplate.variable.discussion.message');
        return $variables;
    }
}