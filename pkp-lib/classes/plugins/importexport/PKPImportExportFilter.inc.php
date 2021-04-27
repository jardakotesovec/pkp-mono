<?php

/**
 * @file classes/plugins/importexport/PKPImportExportFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPImportExportFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base helper class for import/export filters
 */

use Exception;

use PKP\filter\PersistableFilter;

class PKPImportExportFilter extends PersistableFilter
{
    /** @var PKPNativeImportExportDeployment */
    private $_deployment;

    //
    // Deployment management
    //
    /**
     * Set the import/export deployment
     *
     * @param $deployment NativeImportExportDeployment
     */
    public function setDeployment($deployment)
    {
        $this->_deployment = $deployment;
    }

    /**
     * Get the import/export deployment
     *
     * @return PKPNativeImportExportDeployment
     */
    public function getDeployment()
    {
        return $this->_deployment;
    }

    /**
     * Static method that gets the filter object given its name
     *
     * @param $filter string
     * @param $deployment PKPImportExportDeployment
     * @param $opts array
     *
     * @return Filter
     */
    public static function getFilter($filter, $deployment, $opts = [])
    {
        $filterDao = DAORegistry::getDAO('FilterDAO'); /** @var FilterDAO $filterDao */
        $filters = $filterDao->getObjectsByGroup($filter);

        if (count($filters) != 1) {
            throw new Exception(
                __(
                    'plugins.importexport.native.common.error.filter.configuration.count',
                    [
                        'filterName' => $filter,
                        'filterCount' => count($filters)
                    ]
                )
            );
        }

        $currentFilter = array_shift($filters);
        $currentFilter->setDeployment($deployment);

        if (is_a($currentFilter, 'NativeExportFilter')) {
            $currentFilter->setOpts($opts);
        }

        return $currentFilter;
    }
}
