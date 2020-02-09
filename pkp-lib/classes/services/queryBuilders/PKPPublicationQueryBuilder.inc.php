<?php
/**
 * @file classes/services/QueryBuilders/PKPPublicationQueryBuilder.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPPublicationQueryBuilder
 * @ingroup query_builders
 *
 * @brief Class for building database queries for publications
 */

namespace PKP\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;
use PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface;

class PKPPublicationQueryBuilder extends BaseQueryBuilder implements EntityQueryBuilderInterface {

	/** @var array get publications for one or more contexts */
	protected $contextIds = [];

	/** @var array get publications with one of these publisherIds */
	protected $publisherIds = [];

	/** @var array get publications for one or more submissions */
	protected $submissionIds = [];

	/** @var int|null whether to limit the number of results returned */
	protected $limit = null;

	/** @var int whether to offset the number of results returned. Use to return a second page of results. */
	protected $offset = 0;

	/**
	 * Set contextIds filter
	 *
	 * @param array|int $contextIds
	 * @return \PKP\Services\QueryBuilders\PKPPublicationQueryBuilder
	 */
	public function filterByContextIds($contextIds) {
		$this->contextIds = is_array($contextIds) ? $contextIds : [$contextIds];
		return $this;
	}

	/**
	 * Set publisherIds filter
	 *
	 * @param array|int $publisherIds
	 * @return \PKP\Services\QueryBuilders\PKPPublicationQueryBuilder
	 */
	public function filterByPublisherIds($publisherIds) {
		$this->publisherIds = is_array($publisherIds) ? $publisherIds : [$publisherIds];
		return $this;
	}

	/**
	 * Set submissionIds filter
	 *
	 * @param array|int $submissionIds
	 * @return \PKP\Services\QueryBuilders\PKPPublicationQueryBuilder
	 */
	public function filterBySubmissionIds($submissionIds) {
		$this->submissionIds = is_array($submissionIds) ? $submissionIds : [$submissionIds];
		return $this;
	}

	/**
	 * Set query limit
	 *
	 * @param int $count
	 *
	 * @return \PKP\Services\QueryBuilders\PKPPublicationQueryBuilder
	 */
	public function limitTo($count) {
		$this->limit = $count;
		return $this;
	}

	/**
	 * Set how many results to skip
	 *
	 * @param int $offset
	 *
	 * @return \PKP\Services\QueryBuilders\PKPPublicationQueryBuilder
	 */
	public function offsetBy($offset) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getCount()
	 */
	public function getCount() {
		return $this
			->getQuery()
			->select('p.publication_id')
			->get()
			->count();
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getIds()
	 */
	public function getIds() {
		return $this
			->getQuery()
			->select('p.publication_id')
			->pluck('p.publication_id')
			->toArray();
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getQuery()
	 */
	public function getQuery() {
		$this->columns = ['*'];
		$q = Capsule::table('publications as p');

		if (!empty($this->contextIds)) {
			$q->leftJoin('submissions as s', 's.submission_id', '=', 'p.submission_id')
				->whereIn('s.context_id', $this->contextIds);
		}

		if (!empty($this->publisherIds)) {
			$q->leftJoin('publication_settings as ps', 'p.publication_id', '=', 'ps.publication_id')
				->where(function($q) {
					$q->where('ps.setting_name', 'pub-id::publisher-id');
					$q->whereIn('ps.setting_value', $this->publisherIds);
				});
		}

		if (!empty($this->submissionIds)) {
			$q->whereIn('p.submission_id', $this->submissionIds);
		}

		// Limit and offset results for pagination
		if (!is_null($this->limit)) {
			$q->limit($this->limit);
		}
		if (!empty($this->offset)) {
			$q->offset($this->offset);
		}

		// Add app-specific query statements
		\HookRegistry::call('Publication::getMany::queryObject', array(&$q, $this));

		$q->select($this->columns);

		return $q;
	}

	/**
	 * Get the oldest and most recent publication dates for publications
	 *
	 * @return object Query object
	 */
	public function getDateBoundaries() {
		return $this->getQuery()
			->select([
				Capsule::raw('MIN(p.date_published), MAX(p.date_published)')
			]);
	}
}
