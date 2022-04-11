<?php

/**
 * @file classes/preprint/Author.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Author
 * @ingroup preprint
 *
 * @see DAO
 *
 * @brief Preprint author metadata class.
 */

namespace APP\author;

class Author extends \PKP\author\Author
{
}

if (!PKP_STRICT_MODE) {
    // Required for import/export toolset
    class_alias('\APP\author\Author', '\Author');
}
