<?php
/**
 * @file classes/components/form/statistics/users/ReportForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AssignToIssueForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for configuring the users report.
 */
namespace PKP\components\forms\statistics\users;
use \PKP\components\forms\{FormComponent, FieldOptions};
use \PKP\User\Report\Mappings;

class ReportForm extends FormComponent {
	/**
	 * Constructor
	 *
	 * @param string $action URL to submit the form to
	 * @param \Context $context The context
	 */
	public function __construct(string $action, \Context $context)
	{
		$this->action = $action;
		$this->id = 'reportForm';
		$this->method = 'POST';

		$this->addPage(array('id' => 'default', 'submitButton' => array('label' => __('common.export'))));
		$this->addGroup(array('id' => 'default', 'pageId' => 'default'));

		$userGroups = iterator_to_array(\DAORegistry::getDAO('UserGroupDAO')->getByContextId()->toIterator());
		$this->addField(new FieldOptions('userGroupIds', [
			'groupId' => 'default',
			'label' => __('user.group'),
			'options' => array_map(function ($userGroup) {
				return [
					'value' => $userGroup->getId(),
					'label' => $userGroup->getLocalizedName()
				];
			}, $userGroups),
			'default' => array_map(function ($userGroup) {
				return $userGroup->getId();
			}, $userGroups)
		]));

		$mappingGroups = [
			['value' => Mappings\Notifications::class, 'label' => __('notification.notifications')],
			['value' => Mappings\UserGroups::class, 'label' => __('manager.roles')]
		];
		$this->addField(new FieldOptions('mappings', [
			'groupId' => 'default',
			'label' => __('common.export'),
			'options' => $mappingGroups,
			'default' => array_map(function ($mappingGroup) {
				return $mappingGroup['value'];
			}, $mappingGroups)
		]));
	}
}
