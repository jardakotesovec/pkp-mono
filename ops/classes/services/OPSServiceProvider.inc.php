<?php

/**
 * @file classes/services/OPSServiceProvider.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OPSServiceProvider
 * @ingroup services
 *
 * @brief Utility class to package all OPS services
 */

namespace APP\services;

use Pimple\Container;
use PKP\services\PKPAuthorService;
use PKP\services\PKPEmailTemplateService;
use PKP\services\PKPFileService;
use PKP\services\PKPSchemaService;
use PKP\services\PKPSiteService;
use PKP\services\PKPUserService;

class OPSServiceProvider implements \Pimple\ServiceProviderInterface
{
    /**
     * Registers services
     *
     * @param Pimple\Container $pimple
     */
    public function register(Container $pimple)
    {

        // Author service
        $pimple['author'] = function () {
            return new PKPAuthorService();
        };

        // File service
        $pimple['file'] = function () {
            return new PKPFileService();
        };

        // Section service
        $pimple['section'] = function () {
            return new SectionService();
        };

        // NavigationMenus service
        $pimple['navigationMenu'] = function () {
            return new NavigationMenuService();
        };

        // Galley service
        $pimple['galley'] = function () {
            return new GalleyService();
        };

        // User service
        $pimple['user'] = function () {
            return new PKPUserService();
        };

        // Context service
        $pimple['context'] = function () {
            return new ContextService();
        };

        // Site service
        $pimple['site'] = function () {
            return new PKPSiteService();
        };

        // Submission file service
        $pimple['submissionFile'] = function () {
            return new SubmissionFileService();
        };

        // Email Templates service
        $pimple['emailTemplate'] = function () {
            return new PKPEmailTemplateService();
        };

        // Schema service
        $pimple['schema'] = function () {
            return new PKPSchemaService();
        };

        // Publication statistics service
        $pimple['stats'] = function () {
            return new StatsService();
        };

        // Editorial statistics service
        $pimple['editorialStats'] = function () {
            return new StatsEditorialService();
        };
    }
}
