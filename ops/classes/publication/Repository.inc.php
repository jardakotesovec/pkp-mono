<?php
/**
 * @file classes/publication/Repository.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class publication
 *
 * @brief Get publications and information about publications
 */

namespace APP\publication;

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\mail\PreprintMailTemplate;
use APP\notification\Notification;
use APP\notification\NotificationManager;
use APP\submission\Submission;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\plugins\HookRegistry;
use PKP\plugins\PluginRegistry;
use PKP\security\Role;
use PKP\stageAssignment\StageAssignmentDAO;

class Repository extends \PKP\publication\Repository
{
    /** @copydoc \PKP\submission\Repository::$schemaMap */
    public $schemaMap = maps\Schema::class;

    /** @copydoc PKP\publication\Repository::validate() */
    public function validate($publication, array $props, array $allowedLocales, string $primaryLocale): array
    {
        $errors = parent::validate($publication, $props, $allowedLocales, $primaryLocale);

        $sectionDao = Application::get()->getSectionDAO(); /** @var SectionDAO $sectionDao */

        // Ensure that the specified section exists
        $section = null;
        if (isset($props['sectionId'])) {
            $section = $sectionDao->getById($props['sectionId']);
            if (!$section) {
                $errors['sectionId'] = [__('publication.invalidSection')];
            }
        }

        // Get the section so we can validate section abstract requirements
        if (!$section && !is_null($publication)) {
            $section = $sectionDao->getById($publication->getData('sectionId'));
        }

        if ($section) {

            // Require abstracts if the section requires them
            if (is_null($publication) && !$section->getData('abstractsNotRequired') && empty($props['abstract'])) {
                $errors['abstract'][$primaryLocale] = [__('author.submit.form.abstractRequired')];
            }

            if (isset($props['abstract']) && empty($errors['abstract'])) {

                // Require abstracts in the primary language if the section requires them
                if (!$section->getData('abstractsNotRequired')) {
                    if (empty($props['abstract'][$primaryLocale])) {
                        if (!isset($errors['abstract'])) {
                            $errors['abstract'] = [];
                        };
                        $errors['abstract'][$primaryLocale] = [__('author.submit.form.abstractRequired')];
                    }
                }

                // Check the word count on abstracts
                foreach ($allowedLocales as $localeKey) {
                    if (empty($props['abstract'][$localeKey])) {
                        continue;
                    }
                    $wordCount = count(preg_split('/\s+/', trim(str_replace('&nbsp;', ' ', strip_tags($props['abstract'][$localeKey])))));
                    $wordCountLimit = $section->getData('wordCount');
                    if ($wordCountLimit && $wordCount > $wordCountLimit) {
                        if (!isset($errors['abstract'])) {
                            $errors['abstract'] = [];
                        };
                        $errors['abstract'][$localeKey] = [__('publication.wordCountLong', ['limit' => $wordCountLimit, 'count' => $wordCount])];
                    }
                }
            }
        }

        return $errors;
    }

    /** @copydoc PKP\publication\Repository::validatePublish() */
    public function validatePublish(Publication $publication, Submission $submission, array $allowedLocales, string $primaryLocale): array
    {
        $errors = parent::validatePublish($publication, $submission, $allowedLocales, $primaryLocale);

        if (!$this->canCurrentUserPublish($submission->getId())) {
            $errors['authorCheck'] = __('author.submit.authorsCanNotPublish');
        }

        return $errors;
    }

    /** @copydoc \PKP\publication\Repository::add() */
    public function add(Publication $publication): int
    {
        // Assign DOI if automatic assigment is enabled
        $context = $this->request->getContext();
        $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $context->getId());
        $doiPubIdPlugin = $pubIdPlugins['doipubidplugin'] ?? null;
        if ($doiPubIdPlugin && $doiPubIdPlugin->getSetting($context->getId(), 'enablePublicationDoiAutoAssign')) {
            $publication->setData('pub-id::doi', $doiPubIdPlugin->getPubId($publication));
        }

        return parent::add($publication);
    }

    /** @copydoc \PKP\publication\Repository::version() */
    public function version(Publication $publication): int
    {
        $newId = parent::version($publication);

        $galleys = $publication->getData('galleys');
        if (!empty($galleys)) {
            foreach ($galleys as $galley) {
                $newGalley = clone $galley;
                $newGalley->setData('id', null);
                $newGalley->setData('publicationId', $newId);
                Services::get('galley')->add($newGalley, $this->request);
            }
        }

        // Version DOI if the pattern includes the publication id
        // FIXME: Move DOI versioning logic out of pubIdPlugin
        $context = $this->request->getContext();
        $pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $context->getId());
        $doiPubIdPlugin = $pubIdPlugins['doipubidplugin'] ?? null;
        if ($doiPubIdPlugin) {
            $pattern = $doiPubIdPlugin->getSetting($context->getId(), 'doiPublicationSuffixPattern');
            if (strpos($pattern, '%b')) {
                $publication = $this->get($newId);
                $this->edit($publication, [
                    'pub-id::doi' => $doiPubIdPlugin->versionPubId($publication),
                ]);
            }
        }

        return $newId;
    }

    /** @copydoc \PKP\publication\Repository::setStatusOnPublish() */
    protected function setStatusOnPublish(Publication $publication)
    {
        // If the publish date is in the future, set the status to scheduled
        $datePublished = $publication->getData('datePublished');
        if ($datePublished && strtotime($datePublished) > strtotime(Core::getCurrentDate())) {
            $publication->setData('status', Submission::STATUS_SCHEDULED);
        } else {
            $publication->setData('status', Submission::STATUS_PUBLISHED);
        }

        // If there is no publish date, set it
        if (!$publication->getData('datePublished')) {
            $publication->setData('datePublished', Core::getCurrentDate());
        }
    }

    /** @copydoc \PKP\publication\Repository::publish() */
    public function publish(Publication $publication)
    {
        parent::publish($publication);

        // Send preprint posted acknowledgement email when the first version is published
        if ($publication->getData('version') == 1) {
            $submission = Repo::submission()->get($publication->getData('submissionId'));
            $context = $this->request->getContext();
            $dispatcher = $this->request->getDispatcher();
            $mail = new PreprintMailTemplate($submission, 'POSTED_ACK', null, null, false);

            if ($mail->isEnabled()) {

                // posted ack emails should be from the contact.
                $mail->setFrom($context->getData('contactEmail'), $context->getData('contactName'));

                // Send to all authors
                $assignedAuthors = Repo::author()->getSubmissionAuthors($submission);
                foreach ($assignedAuthors as $author) {
                    $mail->addRecipient($author->getEmail(), $author->getFullName());
                }

                // Use primary author details in email
                $primaryAuthor = $submission->getPrimaryAuthor();
                $mail->assignParams([
                    'authorPrimary' => $primaryAuthor ? $primaryAuthor->getFullName() : '',
                    'editorialContactSignature' => $context->getData('contactName'),
                    'submissionUrl' => $dispatcher->url($this->request, Application::ROUTE_PAGE, $context->getData('urlPath'), 'preprint', 'view', $submission->getBestId(), null, null, true),
                ]);

                if (!$mail->send($this->request)) {
                    $notificationMgr = new NotificationManager();
                    $notificationMgr->createTrivialNotification($this->request->getUser()->getId(), Notification::NOTIFICATION_TYPE_ERROR, ['contents' => __('email.compose.error')]);
                }
            }
        }
    }

    /** @copydoc \PKP\publication\Repository::delete() */
    public function delete(Publication $publication)
    {
        $galleysIterator = Services::get('galley')->getMany(['publicationIds' => $publication->getId()]);
        foreach ($galleysIterator as $galley) {
            Services::get('galley')->delete($galley);
        }

        parent::delete($publication);
    }

    /**
     * Set the DOI of a related preprint
     */
    public function relate(Publication $publication, int $relationStatus, ?string $vorDoi = '')
    {
        if ($relationStatus !== Publication::PUBLICATION_RELATION_PUBLISHED) {
            $vorDoi = '';
        }
        $this->edit($publication, [
            'relationStatus' => $relationStatus,
            'vorDoi' => $vorDoi,
        ]);
    }

    /**
     * Check if the current user can publish
     *
     * @param string $submissionId
     *
     *
     */
    public function canCurrentUserPublish(int $submissionId): bool
    {

        // Check if current user is an author
        $isAuthor = false;
        $currentUser = $this->request->getUser();
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /** @var StageAssignmentDAO $stageAssignmentDao */
        $submitterAssignments = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, Role::ROLE_ID_AUTHOR);
        while ($assignment = $submitterAssignments->next()) {
            if ($currentUser->getId() == $assignment->getUserId()) {
                $isAuthor = true;
            }
        }

        // By default authors can not publish, but this can be overridden in screening plugins with the hook Publication::canAuthorPublish
        if ($isAuthor) {
            if (HookRegistry::call('Publication::canAuthorPublish', [$this])) {
                return true;
            } else {
                return false;
            }
        }

        // If the user is not an author, has to be an editor, return true
        return true;
    }

    /**
     * @copydoc \PKP\publication\Repository::getErrorMessageOverrides
     */
    protected function getErrorMessageOverrides(): array
    {
        $overrides = parent::getErrorMessageOverrides();
        $overrides['relationStatus'] = __('validation.invalidOption');
        return $overrides;
    }

    /**
     * Create all DOIs associated with the publication
     *
     * @return mixed
     */
    protected function createDois(Publication $newPublication): void
    {
        $submission = Repo::submission()->get($newPublication->getData('submissionId'));
        Repo::submission()->createDois($submission);
    }
}
