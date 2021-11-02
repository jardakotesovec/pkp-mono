<?php

/**
 * @file mail/Configurable.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Configurable
 * @ingroup mail
 *
 * @brief trait to support Mailable's name and description displayed in the UI
 */

namespace PKP\mail;

use Exception;

trait Configurable
{
    /**
     * Retrieve localized Mailable's name
     * @throws Exception
     */
    public static function getName(): string
    {
        return !is_null(static::$name)
            ? __(static::$name)
            : throw new Exception('Configurable mailable created without a name.');
    }

    /**
     * Retrieve localized Mailable's description
     * @throws Exception
     */
    public static function getDescription(): string
    {
        return !is_null(static::$description)
            ? __(static::$description)
            : throw new Exception('Configurable mailable created without a description.');
    }
}
