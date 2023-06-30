<?php

/**
 * @defgroup pages_preprint Preprint Pages
 */

/**
 * @file pages/preprint/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_preprint
 *
 * @brief Handle requests for preprint functions.
 *
 */

switch ($op) {
    case 'view':
    case 'download':
        return new APP\pages\preprint\PreprintHandler();
}
