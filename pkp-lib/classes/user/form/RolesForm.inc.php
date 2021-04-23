<?php

/**
 * @file classes/user/form/RolesForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPProfileForm
 * @ingroup user_form
 *
 * @brief Form to edit the roles area of the user profile.
 */

import('lib.pkp.classes.user.form.BaseProfileForm');

use PKP\user\InterestManager;

class RolesForm extends BaseProfileForm
{
    /**
     * Constructor.
     *
     * @param $user User
     */
    public function __construct($user)
    {
        parent::__construct('user/rolesForm.tpl', $user);

        // Validation checks for this form
    }

    /**
     * @copydoc BaseProfileForm::fetch
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);

        $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /** @var UserGroupDAO $userGroupDao */
        $userGroupAssignmentDao = DAORegistry::getDAO('UserGroupAssignmentDAO'); /** @var UserGroupAssignmentDAO $userGroupAssignmentDao */
        $userGroupAssignments = $userGroupAssignmentDao->getByUserId($request->getUser()->getId());
        $userGroupIds = [];
        while ($assignment = $userGroupAssignments->next()) {
            $userGroupIds[] = $assignment->getUserGroupId();
        }
        $templateMgr->assign('userGroupIds', $userGroupIds);

        import('lib.pkp.classes.user.form.UserFormHelper');
        $userFormHelper = new UserFormHelper();
        $userFormHelper->assignRoleContent($templateMgr, $request);

        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_USER);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc BaseProfileForm::initData()
     */
    public function initData()
    {
        $interestManager = new InterestManager();

        $user = $this->getUser();

        $this->_data = [
            'interests' => $interestManager->getInterestsForUser($user),
        ];
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        parent::readInputData();

        $this->readUserVars([
            'authorGroup',
            'reviewerGroup',
            'readerGroup',
            'interests',
        ]);
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $request = Application::get()->getRequest();
        $user = $request->getUser();

        // Save the roles
        import('lib.pkp.classes.user.form.UserFormHelper');
        $userFormHelper = new UserFormHelper();
        $userFormHelper->saveRoleContent($this, $user);

        // Insert the user interests
        $interestManager = new InterestManager();
        $interestManager->setInterestsForUser($user, $this->getData('interests'));

        parent::execute(...$functionArgs);
    }
}
