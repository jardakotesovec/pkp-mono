<?php

/**
 * @file controllers/grid/settings/library/LibraryFileAdminGridDataProvider.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FilesGridDataProvider
 * @ingroup controllers_grid_settings_library
 *
 * @brief The data provider for the admin library files grid.
 */


import('lib.pkp.classes.controllers.grid.CategoryGridDataProvider');

class LibraryFileAdminGridDataProvider extends CategoryGridDataProvider
{
    /** the context for this library **/
    public $_context;

    /** whether or not this grid is editable **/
    public $_canEdit;

    /**
     * Constructor
     */
    public function __construct($canEdit)
    {
        $this->_canEdit = $canEdit;
        parent::__construct();
    }


    //
    // Getters and Setters
    //

    /**
     * @copydoc GridDataProvider::getAuthorizationPolicy()
     */
    public function getAuthorizationPolicy($request, $args, $roleAssignments)
    {
        $this->_context = $request->getContext();
        import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
        return new ContextAccessPolicy($request, $roleAssignments);
    }

    /**
     * @copydoc GridDataProvider::getRequestArgs()
     */
    public function getRequestArgs()
    {
        return ['canEdit' => $this->canEdit()];
    }

    /**
     * get the current context
     *
     * @return $context Context
     */
    public function &getContext()
    {
        return $this->_context;
    }


    /**
     * get whether or not this grid is editable (has actions).
     *
     * @return boolean $canEdit
     */
    public function canEdit()
    {
        return $this->_canEdit;
    }


    /**
     * @copydoc CategoryGridHandler::loadCategoryData()
     *
     * @param null|mixed $filter
     */
    public function loadCategoryData($request, $fileType, $filter = null)
    {

        // Elements to be displayed in the grid
        $libraryFileDao = DAORegistry::getDAO('LibraryFileDAO'); /** @var LibraryFileDAO $libraryFileDao */
        $context = $this->getContext();
        $libraryFiles = $libraryFileDao->getByContextId($context->getId(), $fileType);

        return $libraryFiles->toAssociativeArray();
    }
}
