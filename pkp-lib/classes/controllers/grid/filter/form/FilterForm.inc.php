<?php

/**
 * @file classes/controllers/grid/filter/form/FilterForm.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FilterForm
 * @ingroup classes_controllers_grid_filter_form
 *
 * @brief Form for adding/editing a filter.
 * New filter instances are based on filter templates.
 */

import('lib.pkp.classes.form.Form');

class FilterForm extends Form {
	/** @var Filter the filter being edited */
	var $_filter;

	/** @var string a translation key for the filter form title */
	var $_title;

	/** @var string a translation key for the filter form description */
	var $_description;

	/** @var mixed sample input object required to identify compatible filters */
	var $_inputSample;

	/** @var mixed sample output object required to identify compatible filters */
	var $_outputSample;

	/**
	 * Constructor.
	 * @param $filter Filter
	 * @param $inputSample mixed
	 * @param $outputSample mixed
	 * @param $title string
	 * @param $description string
	 */
	function FilterForm(&$filter, $title, $description, &$inputSample, &$outputSample) {
		parent::Form('controllers/grid/filter/form/filterForm.tpl');

		// Initialize internal state.
		$this->_filter =& $filter;
		$this->_title = $title;
		$this->_description = $description;
		$this->_inputSample =& $inputSample;
		$this->_outputSample =& $outputSample;

		// Transport filter/template id.
		$this->readUserVars(array('filterId', 'filterTemplateId'));

		// Validation check common to all requests.
		$this->addCheck(new FormValidatorPost($this));

		// Validation check for template selection.
		if (!is_null($filter) && !is_numeric($filter->getId())) {
			$this->addCheck(new FormValidator($this, 'filterTemplateId', 'required', 'manager.setup.filter.grid.filterTemplateRequired'));
		}

		// Add filter specific meta-data and checks.
		if (is_a($filter, 'Filter')) {
			$this->setData('filterSettings', $filter->getSettings());
			foreach($filter->getSettings() as $filterSetting) {
				// Add check corresponding to filter setting.
				$settingCheck =& $filterSetting->getCheck($form);
				if (!is_null($settingCheck)) $this->addCheck($settingCheck);
			}
		}
	}

	//
	// Getters and Setters
	//
	/**
	 * Get the filter
	 * @return Filter
	 */
	function &getFilter() {
		return $this->_filter;
	}

	/**
	 * Get the filter form title
	 * @return string
	 */
	function getTitle() {
		return $this->_title;
	}

	/**
	 * Get the filter form description
	 * @return string
	 */
	function getDescription() {
		return $this->_description;
	}

	/**
	 * Get the input sample object
	 * @return mixed
	 */
	function &getInputSample() {
		return $this->_inputSample;
	}

	/**
	 * Get the output sample object
	 * @return mixed
	 */
	function &getOutputSample() {
		return $this->_outputSample;
	}

	//
	// Template methods from Form
	//
	/**
	* Initialize form data.
	* @param $alreadyInstantiatedFilters array
	*/
	function initData(&$alreadyInstantiatedFilters) {
		// Transport filter/template id.
		$this->readUserVars(array('filterId', 'filterTemplateId'));

		$filter =& $this->getFilter();
		if (is_a($filter, 'Filter')) {
			// A transformation has already been chosen
			// so identify the settings and edit them.

			// Add filter default settings as form data.
			foreach($filter->getSettings() as $filterSetting) {
				// Add filter setting data
				$settingName = $filterSetting->getName();
				$this->setData($settingName, $filter->getData($settingName));
			}
		} else {
			// The user did not yet choose a template
			// to base the transformation on.

			// Retrieve all compatible filter templates
			// from the database.
			$filterDao =& DAORegistry::getDAO('FilterDAO');
			$filterTemplateObjects =& $filterDao->getCompatibleObjects($this->_inputSample, $this->_outputSample, true);
			$filterTemplates = array();

			// Make a blacklist of filters that cannot be
			// instantiated again because they already
			// have been instantiated and cannot be parameterized.
			$filterClassBlacklist = array();
			foreach($alreadyInstantiatedFilters->toArray() as $alreadyInstantiatedFilter) {
				if (!$alreadyInstantiatedFilter->hasSettings()) {
					$filterClassBlacklist[] = $alreadyInstantiatedFilter->getClassName();
				}
			}

			foreach($filterTemplateObjects as $filterTemplateObject) {
				// Check whether the filter is on the blacklist.
				if (in_array($filterTemplateObject->getClassName(), $filterClassBlacklist)) continue;

				// The filter can still be added.
				$filterTemplates[$filterTemplateObject->getId()] = $filterTemplateObject->getDisplayName();
			}
			$this->setData('filterTemplates', $filterTemplates);

			// There are no more filter templates to
			// be chosen from.
			if (empty($filterTemplates)) $this->setData('noMoreTemplates', true);
		}
	}

	/**
	 * Initialize form data from user submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('filterId', 'filterTemplateId'));
		// A value of -1 for the filter template means "nothing selected"
		if ($this->getData('filterTemplate') == '-1') $this->setData('filterTemplate', '');

		$filter =& $this->getFilter();
		if(is_a($filter, 'Filter')) {
			foreach($filter->getSettings() as $filterSetting) {
				$userVars[] = $filterSetting->getName();
			}
			$this->readUserVars($userVars);
		}
	}

	/**
	 * @see Form::fetch()
	 */
	function fetch(&$request) {
		$templateMgr =& TemplateManager::getManager($request);

		// The form description depends on the current state
		// of the selection process: do we select a filter template
		// or configure the settings of a selected template?
		$filter =& $this->getFilter();
		if (is_a($filter, 'Filter')) {
			$templateMgr->assign('filterDisplayName', $filter->getDisplayName());
			if (count($filter->getSettings())) {
				$formDescriptionKey = $this->getDescription().'Settings';
			} else {
				$formDescriptionKey = $this->getDescription().'Confirm';
			}
		} else {
			$templateMgr->assign('filterDisplayName', '');
			$formDescriptionKey = $this->getDescription().'Template';
		}

		$templateMgr->assign('formTitle', $this->getTitle());
		$templateMgr->assign('formDescription', $formDescriptionKey);

		return parent::fetch($request);
	}

	/**
	 * Save filter
	 */
	function execute() {
		$filter =& $this->getFilter();
		assert(is_a($filter, 'Filter'));

		// Configure the filter
		foreach($filter->getSettings() as $filterSetting) {
			$settingName = $filterSetting->getName();
			$filter->setData($settingName, $this->getData($settingName));
		}

		// Persist the filter
		$filterDAO =& DAORegistry::getDAO('FilterDAO');
		if (is_numeric($filter->getId())) {
			$filterDAO->updateObject($filter);
		} else {
			$filterDAO->insertObject($filter);
		}
		return true;
	}
}

?>
