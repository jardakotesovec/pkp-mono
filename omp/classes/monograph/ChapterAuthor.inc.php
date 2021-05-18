<?php

/**
 * @file classes/monograph/ChapterAuthor.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ChapterAuthor
 * @ingroup monograph
 *
 * @see ChapterAuthorDAO
 * @see AuthorDAO
 *
 * @brief Adds chapterId to an Author
 */

namespace APP\monograph;

use APP\monograph\Author;

class ChapterAuthor extends Author
{
    //
    // Get/set methods
    //

    /**
     * Get Chapter ID of this author
     *
     * @return int
     */
    public function getChapterId()
    {
        return $this->getData('chapterId');
    }

    /**
     * Set ID of chapter.
     *
     * @param $chapterId int
     */
    public function setChapterId($chapterId)
    {
        return $this->setData('chapterId', $chapterId);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\monograph\ChapterAuthor', '\ChapterAuthor');
}
