<?php

/**
 * @file controllers/grid/users/author/form/PKPAuthorForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPAuthorForm
 * @ingroup controllers_grid_users_author_form
 *
 * @brief Form for adding/editing a author
 */

use APP\template\TemplateManager;
use PKP\form\Form;

use PKP\services\interfaces\EntityWriteInterface;

class PKPAuthorForm extends Form
{
    /** The publication associated with the contributor being edited **/
    public $_publication;

    /** Author the author being edited **/
    public $_author;

    /**
     * Constructor.
     */
    public function __construct($publication, $author)
    {
        parent::__construct('controllers/grid/users/author/form/authorForm.tpl');
        $this->setPublication($publication);
        $this->setAuthor($author);

        // the publication locale should be the default/required locale
        $this->setDefaultFormLocale($publication->getData('locale'));

        // Validation checks for this form
        $form = $this;
        $this->addCheck(new \PKP\form\validation\FormValidatorLocale($this, 'givenName', 'required', 'user.profile.form.givenNameRequired', $this->defaultLocale));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'familyName', 'optional', 'user.profile.form.givenNameRequired.locale', function ($familyName) use ($form) {
            $givenNames = $form->getData('givenName');
            foreach ($familyName as $locale => $value) {
                if (!empty($value) && empty($givenNames[$locale])) {
                    return false;
                }
            }
            return true;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorEmail($this, 'email', 'required', 'form.emailRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorUrl($this, 'userUrl', 'optional', 'user.profile.form.urlInvalid'));
        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'userGroupId', 'required', 'submission.submit.form.contributorRoleRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorORCID($this, 'orcid', 'optional', 'user.orcid.orcidInvalid'));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    //
    // Getters and Setters
    //
    /**
     * Get the author
     *
     * @return Author
     */
    public function getAuthor()
    {
        return $this->_author;
    }

    /**
     * Set the author
     *
     * @param @author Author
     */
    public function setAuthor($author)
    {
        $this->_author = $author;
    }

    /**
     * Get the Publication
     *
     * @return Publication
     */
    public function getPublication()
    {
        return $this->_publication;
    }

    /**
     * Set the Publication
     *
     * @param Publication
     */
    public function setPublication($publication)
    {
        $this->_publication = $publication;
    }


    //
    // Overridden template methods
    //
    /**
     * Initialize form data from the associated author.
     */
    public function initData()
    {
        $author = $this->getAuthor();

        if ($author) {
            $this->_data = [
                'authorId' => $author->getId(),
                'givenName' => $author->getGivenName(null),
                'familyName' => $author->getFamilyName(null),
                'preferredPublicName' => $author->getPreferredPublicName(null),
                'affiliation' => $author->getAffiliation(null),
                'country' => $author->getCountry(),
                'email' => $author->getEmail(),
                'userUrl' => $author->getUrl(),
                'orcid' => $author->getOrcid(),
                'userGroupId' => $author->getUserGroupId(),
                'biography' => $author->getBiography(null),
                'primaryContact' => $this->getPublication()->getData('primaryContactId') === $author->getId(),
                'includeInBrowse' => $author->getIncludeInBrowse(),
            ];
        } else {
            // assume authors should be listed unless otherwise specified.
            $this->_data = ['includeInBrowse' => true];
        }
        // in order to be able to use the hook
        return parent::initData();
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /** @var UserGroupDAO $userGroupDao */
        $authorUserGroups = $userGroupDao->getByRoleId($request->getContext()->getId(), ROLE_ID_AUTHOR);
        $publication = $this->getPublication();
        $isoCodes = new \Sokil\IsoCodes\IsoCodesFactory();
        $countries = [];
        foreach ($isoCodes->getCountries() as $country) {
            $countries[$country->getAlpha2()] = $country->getLocalName();
        }
        asort($countries);
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'submissionId' => $publication->getData('submissionId'),
            'publicationId' => $publication->getId(),
            'countries' => $countries,
            'authorUserGroups' => $authorUserGroups,
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * Assign form data to user-submitted data.
     *
     * @see Form::readInputData()
     */
    public function readInputData()
    {
        $this->readUserVars([
            'authorId',
            'givenName',
            'familyName',
            'preferredPublicName',
            'affiliation',
            'country',
            'email',
            'userUrl',
            'orcid',
            'userGroupId',
            'biography',
            'primaryContact',
            'includeInBrowse',
        ]);
    }

    /**
     * Save author
     *
     * @see Form::execute()
     */
    public function execute(...$functionParams)
    {
        $authorDao = DAORegistry::getDAO('AuthorDAO'); /** @var AuthorDAO $authorDao */
        $publication = $this->getPublication();

        $author = $this->getAuthor();
        if (!$author) {
            // this is a new submission contributor
            $this->_author = $authorDao->newDataObject();
            $author = $this->getAuthor();
            $author->setData('publicationId', $publication->getId());
            $author->setData('seq', count($publication->getData('authors')));
            $existingAuthor = false;
        } else {
            $existingAuthor = true;
            if ($publication->getId() !== $author->getData('publicationId')) {
                fatalError('Invalid author!');
            }
        }

        $author->setGivenName($this->getData('givenName'), null);
        $author->setFamilyName($this->getData('familyName'), null);
        $author->setPreferredPublicName($this->getData('preferredPublicName'), null);
        $author->setAffiliation($this->getData('affiliation'), null); // localized
        $author->setCountry($this->getData('country'));
        $author->setEmail($this->getData('email'));
        $author->setUrl($this->getData('userUrl'));
        $author->setOrcid($this->getData('orcid'));
        $author->setUserGroupId($this->getData('userGroupId'));
        $author->setBiography($this->getData('biography'), null); // localized
        $author->setIncludeInBrowse(($this->getData('includeInBrowse') ? true : false));

        // in order to be able to use the hook
        parent::execute(...$functionParams);

        if ($existingAuthor) {
            $authorDao->updateObject($author);
            $authorId = $author->getId();
        } else {
            $authorId = $authorDao->insertObject($author);
        }

        if ($this->getData('primaryContact')) {
            $submission = Services::get('submission')->get($publication->getData('submissionId'));
            $context = Services::get('context')->get($submission->getData('contextId'));
            $params = ['primaryContactId' => $authorId];
            $errors = Services::get('publication')->validate(
                EntityWriteInterface::VALIDATE_ACTION_EDIT,
                $params,
                $context->getData('supportedLocales'),
                $publication->getData('locale')
            );
            if (!empty($errors)) {
                throw new Exception('Invalid primary contact ID. This author can not be a primary contact.');
            }
            $publication = Services::get('publication')->edit($publication, $params, Application::get()->getRequest());
        }

        return $authorId;
    }
}
