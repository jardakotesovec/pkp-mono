<?php
/**
 * @file api/v1/_dois/BackendDoiHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class BackendDoiHandler
 * @ingroup api_v1_backend
 *
 * @brief Handle API requests for backend operations.
 *
 */

use APP\facades\Repo;
use APP\services\GalleyService;
use PKP\core\APIResponse;
use PKP\security\Role;

use Slim\Http\Request as SlimRequest;

import('lib.pkp.api.v1._dois.PKPBackendDoiHandler');
class BackendDoiHandler extends PKPBackendDoiHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_handlerPath = '_dois';
        $this->_endpoints = array_merge_recursive($this->_endpoints, [
            'PUT' => [
                [
                    'pattern' => $this->getEndpointPattern() . "/galleys/{galleyId:\d+}",
                    'handler' => [$this, 'editGalley'],
                    'roles' => [Role::ROLE_ID_MANAGER],
                ]
            ]
        ]);
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function editGalley(SlimRequest $slimRequest, APIResponse $response, array $args): \Slim\Http\Response
    {
        $request = $this->getRequest();
        $context = $request->getContext();

        /** @var GalleyService $galleyService */
        $galleyService = Services::get('galley');

        $galley = $galleyService->get($args['galleyId']);
        if (!$galley) {
            return $response->withStatus(404)->withJsonError('api.404.resourceNotFound');
        }

        if ($galley->getData('contextId') !== $context->getId()) {
            return $response->withStatus(403)->withJsonError('api.dois.403.editItemOutOfContext');
        }

        $params = $this->convertStringsToSchema(\PKP\services\PKPSchemaService::SCHEMA_GALLEY, $slimRequest->getParsedBody());

        $doi = Repo::doi()->get((int) $params['doiId']);
        if (!$doi) {
            return $response->withStatus(404)->withJsonError('api.dois.404.doiNotFound');
        }

        $galley = $galleyService->edit($galley, ['doiId' => $doi->getId()], $request);
        $galleyProps = $galleyService->getFullProperties($galley, [
            'request' => $request
        ]);
        return $response->withJson($galleyProps, 200);
    }
}
