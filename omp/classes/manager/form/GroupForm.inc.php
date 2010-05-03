<?php

/**
 * @file classes/manager/form/GroupForm.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GroupForm
 * @ingroup manager_form
 * @see Group
 *
 * @brief Form for press managers to create/edit groups.
 */

// $Id$


import('lib.pkp.classes.form.Form');
import('lib.pkp.classes.group.Group');

class GroupForm extends Form {
	/** @var groupId int the ID of the group being edited */
	var $group;

	/**
	 * Constructor
	 * @param group Group object; null to create new
	 */
	function GroupForm($group = null) {
		$press =& Request::getPress();

		parent::Form('manager/groups/groupForm.tpl');

		// Group title is provided
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.groups.form.groupTitleRequired'));

		$this->addCheck(new FormValidatorPost($this));

		$this->group =& $group;
	}

	/**
	 * Get the list of localized field names for this object
	 * @return array
	 */
	function getLocaleFieldNames() {
		$groupDao =& DAORegistry::getDAO('GroupDAO');
		return $groupDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('group', $this->group);
		$templateMgr->assign('helpTopicId', 'press.managementPages.groups');
		$templateMgr->assign('groupContextOptions', array(
			GROUP_CONTEXT_EDITORIAL_TEAM => 'manager.groups.context.editorialTeam',
			GROUP_CONTEXT_PEOPLE => 'manager.groups.context.people'
		));
		parent::display();
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		if ($this->group != null) {
			$this->_data = array(
				'title' => $this->group->getTitle(null), // Localized
				'context' => $this->group->getContext()
			);
		} else {
			$this->_data = array(
				'context' => GROUP_CONTEXT_EDITORIAL_TEAM
			);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('title', 'context'));
	}

	/**
	 * Save group group. 
	 */
	function execute() {
		$groupDao =& DAORegistry::getDAO('GroupDAO');
		$press =& Request::getPress();

		if (!isset($this->group)) {
			$this->group = new Group();
		}

		$this->group->setAssocType(ASSOC_TYPE_PRESS);
		$this->group->setAssocId($press->getId());
		$this->group->setTitle($this->getData('title'), null); // Localized
		$this->group->setContext($this->getData('context'));

		// Eventually this will be a general Groups feature; for now,
		// we're just using it to display press team entries in About.
		$this->group->setAboutDisplayed(true);

		// Update or insert group group
		if ($this->group->getId() != null) {
			$groupDao->updateObject($this->group);
		} else {
			$this->group->setSequence(REALLY_BIG_NUMBER);
			$groupDao->insertGroup($this->group);

			// Re-order the groups so the new one is at the end of the list.
			$groupDao->resequenceGroups($this->group->getAssocType(), $this->group->getAssocId());
		}
	}
}

?>
