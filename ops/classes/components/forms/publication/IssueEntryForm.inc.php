<?php
/**
 * @file classes/components/form/publication/IssueEntryForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueEntryForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for setting a publication's section, categories etc.
 */
namespace APP\components\forms\publication;
use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldOptions;
use \PKP\components\forms\FieldSelect;
use \PKP\components\forms\FieldText;
use \PKP\components\forms\FieldUploadImage;

define('FORM_ISSUE_ENTRY', 'issueEntry');

class IssueEntryForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_ISSUE_ENTRY;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param $action string URL to submit the form to
	 * @param $locales array Supported locales
	 * @param $publication Publication The publication to change settings for
	 * @param $publicationContext Context The context of the publication
	 * @param $baseUrl string Site's base URL. Used for image previews.
	 * @param $temporaryFileApiUrl string URL to upload files to
	 */
	public function __construct($action, $locales, $publication, $publicationContext, $baseUrl, $temporaryFileApiUrl) {
		$this->action = $action;
		$this->locales = $locales;

		// Section options
		$sections = \Services::get('section')->getSectionList($publicationContext->getId());
		$sectionOptions = [];
		foreach ($sections as $section) {
			$sectionOptions[] = [
				'label' => (($section['group'])? __('publication.inactiveSection', ['section' => $section['title']]) : $section['title']),
				'value' => (int) $section['id'],
			];
		}
		$this->addField(new FieldSelect('sectionId', [
				'label' => __('section.section'),
				'options' => $sectionOptions,
				'value' => (int) $publication->getData('sectionId'),
			]));

		// Categories
		$categoryOptions = [];
		$categoryDao = \DAORegistry::getDAO('CategoryDAO'); /* @var $categoryDao CategoryDAO */
		$categories = $categoryDao->getByContextId($publicationContext->getId())->toAssociativeArray();
		foreach ($categories as $category) {
			$label = $category->getLocalizedTitle();
			if ($category->getParentId()) {
				$label = $categories[$category->getParentId()]->getLocalizedTitle() . ' > ' . $label;
			}
			$categoryOptions[] = [
				'value' => (int) $category->getId(),
				'label' => $category->getLocalizedTitle(),
			];
		}
		if (!empty($categoryOptions)) {
			$this->addField(new FieldOptions('categoryIds', [
					'label' => __('submission.submit.placement.categories'),
					'value' => (array) $publication->getData('categoryIds'),
					'options' => $categoryOptions,
				]));
		}

		$this->addField(new FieldUploadImage('coverImage', [
				'label' => __('editor.article.coverImage'),
				'value' => $publication->getData('coverImage'),
				'isMultilingual' => true,
				'baseUrl' => $baseUrl,
				'options' => [
					'url' => $temporaryFileApiUrl,
				],
			]))
			->addField(new FieldText('urlPath', [
				'label' => __('publication.urlPath'),
				'description' => __('publication.urlPath.description'),
				'value' => $publication->getData('urlPath'),
			]))
			->addField(new FieldText('datePublished', [
				'label' => __('publication.datePublished'),
				'description' => __('publication.datePublished.description'),
				'value' => $publication->getData('datePublished'),
				'size' => 'small',
			]));
	}
}
