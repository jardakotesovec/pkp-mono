<?php

/**
 * @file classes/server/ServerSettingsDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ServerSettingsDAO
 * @ingroup server
 *
 * @brief Operations for retrieving and modifying server settings.
 */

namespace APP\server;

use PKP\db\SettingsDAO;

class ServerSettingsDAO extends SettingsDAO
{
    /**
     * Get the settings table name.
     *
     * @return string
     */
    protected function _getTableName()
    {
        return 'server_settings';
    }

    /**
     * Get the primary key column name.
     */
    protected function _getPrimaryKeyColumn()
    {
        return 'server_id';
    }

    /**
     * Get the cache name.
     */
    protected function _getCacheName()
    {
        return 'serverSettings';
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\server\ServerSettingsDAO', '\ServerSettingsDAO');
}
