<?php

namespace APP\doi;

use Illuminate\Support\Facades\App;
use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\PubIdPlugin;
use APP\publication\Publication;
use APP\server\ServerDAO;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\core\DataObject;
use PKP\galley\Galley;
use PKP\services\PKPSchemaService;
use PKP\submission\Representation;
use PKP\doi\Collector;

class Repository extends \PKP\doi\Repository
{
    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        parent::__construct($dao, $request, $schemaService);
    }

    public function getCollector(): Collector
    {
        return App::makeWith(Collector::class, ['dao' => $this->dao]);
    }

    /**
     * Create a DOI for the given publication
     */
    public function mintPublicationDoi(Publication $publication, Submission $submission, Context $context): ?int
    {
        if ($context->getData(Context::SETTING_DOI_SUFFIX_TYPE) === Repo::doi()::SUFFIX_DEFAULT) {
            $doiSuffix = $this->generateDefaultSuffix();
        } else {
            $doiSuffix = $this->generateSuffixPattern($publication, $context, $context->getData(Context::SETTING_DOI_SUFFIX_TYPE), $submission);
        }

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Create a DOI for the given galley
     */
    public function mintGalleyDoi(Galley $galley, Publication $publication, Submission $submission, Context $context): ?int
    {
        if ($context->getData(Context::SETTING_DOI_SUFFIX_TYPE) === Repo::doi()::SUFFIX_DEFAULT) {
            $doiSuffix = $this->generateDefaultSuffix();
        } else {
            $doiSuffix = $this->generateSuffixPattern($galley, $context, $context->getData(Context::SETTING_DOI_SUFFIX_TYPE), $submission, $galley);
        }

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Generate a suffix using a provided pattern type
     *
     * @param string $patternType Repo::doi()::CUSTOM_SUFFIX_* constants
     *
     */
    protected function generateSuffixPattern(
        DataObject $object,
        Context $context,
        string $patternType,
        ?Submission $submission = null,
        ?Representation $representation = null
    ): string {
        $doiSuffix = '';
        switch ($patternType) {
            case self::SUFFIX_CUSTOM_PATTERN:
                $pubIdSuffixPattern = $this->getPubIdSuffixPattern($object, $context);
                $publication = $submission !== null ? Repo::publication()->get($submission->getData('currentPublicationId')) : null;
                $doiSuffix = PubIdPlugin::generateCustomPattern($context, $pubIdSuffixPattern, $object, $submission, $publication, $representation);
                break;
            case self::SUFFIX_MANUAL:
                break;
        }

        return $doiSuffix;
    }

    /**
     * Get app-specific DOI type constants to check when scheduling deposit for submissions
     */
    protected function getValidSubmissionDoiTypes(): array
    {
        return [
            self::TYPE_PUBLICATION,
            self::TYPE_REPRESENTATION
        ];
    }

    /**
     * Gets all DOIs associated with an issue
     * NB: Assumes only enabled DOI types are allowed
     *
     */
    public function getDoisForSubmission(int $submissionId): array
    {
        $doiIds = [];

        $submission = Repo::submission()->get($submissionId);
        /** @var Publication[] $publications */
        $publications = [$submission->getCurrentPublication()];

        /** @var ServerDAO $contextDao */
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($submission->getData('contextId'));

        foreach ($publications as $publication) {
            $publicationDoiId = $publication->getData('doiId');
            if (!empty($publicationDoiId) && $context->isDoiTypeEnabled(self::TYPE_PUBLICATION)) {
                $doiIds[] = $publicationDoiId;
            }

            // Galleys
            $galleys =Repo::galley()->getCollector()
                ->filterByPublicationIds(['publicationIds' => $publication->getId()])
                ->getMany();

            foreach ($galleys as $galley) {
                $galleyDoiId = $galley->getData('doiId');
                if (!empty($galleyDoiId) && $context->isDoiTypeEnabled(self::TYPE_REPRESENTATION)) {
                    $doiIds[] = $galleyDoiId;
                }
            }
        }

        return $doiIds;
    }

    /**
     *  Gets legacy, user-generated suffix pattern associated with object type and context
     *
     * @return mixed|null
     */
    private function getPubIdSuffixPattern(DataObject $object, Context $context)
    {
        if ($object instanceof Representation) {
            return $context->getData(Repo::doi()::CUSTOM_REPRESENTATION_PATTERN);
        } else {
            return $context->getData(Repo::doi()::CUSTOM_PUBLICATION_PATTERN);
        }
    }
}
