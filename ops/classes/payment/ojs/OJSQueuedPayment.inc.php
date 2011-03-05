<?php

/**
 * @file classes/payment/ojs/OJSQueuedPayment.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSQueuedPayment
 * @ingroup payment
 *
 * @brief Queued payment data structure for OJS
 *
 */

import('lib.pkp.classes.payment.QueuedPayment');

class OJSQueuedPayment extends QueuedPayment {
	var $journalId;
	var $type;
	var $requestUrl;

	/**
	 * Get the journal ID of the payment.
	 * @return int
	 */
	function getJournalId() {
		return $this->journalId;
	}

	/**
	 * Set the journal ID of the payment.
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		$this->journalId = $journalId;
	}

	function setType($type) {
		$this->type = $type;
	}

	function getType() {
		return $this->type;
	}

	/**
	 * Returns the name of the QueuedPayment.
	 * Pulled from Journal Settings if present, or from locale file otherwise.
	 * For subscriptions, pulls subscription type name.
	 * @return string
	 */
	function getName() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getJournal($this->getJournalId());

		switch ($this->type) {
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');

				if ($institutionalSubscriptionDao->subscriptionExists($this->assocId)) {
					$subscription =& $institutionalSubscriptionDao->getSubscription($this->assocId);
				} else {
					$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
					$subscription =& $individualSubscriptionDao->getSubscription($this->assocId);
				}
				if (!$subscription) return Locale::translate('payment.type.subscription');

				$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
				$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());

				return Locale::translate('payment.type.subscription') . ' (' . $subscriptionType->getSubscriptionTypeName() . ')';
			case PAYMENT_TYPE_DONATION:
				if ($journal->getLocalizedSetting('donationFeeName') != '') {
					return $journal->getLocalizedSetting('donationFeeName');
				} else {
					return Locale::translate('payment.type.donation');
				}
			case PAYMENT_TYPE_MEMBERSHIP:
				if ($journal->getLocalizedSetting('membershipFeeName') != '') {
					return $journal->getLocalizedSetting('membershipFeeName');
				} else {
					return Locale::translate('payment.type.membership');
				}
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				if ($journal->getLocalizedSetting('purchaseArticleFeeName') != '') {
					return $journal->getLocalizedSetting('purchaseArticleFeeName');
				} else {
					return Locale::translate('payment.type.purchaseArticle');
				}
			case PAYMENT_TYPE_PURCHASE_ISSUE:
				if ($journal->getLocalizedSetting('purchaseIssueFeeName') != '') {
					return $journal->getLocalizedSetting('purchaseIssueFeeName');
				} else {
					return Locale::translate('payment.type.purchaseIssue');
				}
			case PAYMENT_TYPE_SUBMISSION:
				if ($journal->getLocalizedSetting('submissionFeeName') != '') {
					return $journal->getLocalizedSetting('submissionFeeName');
				} else {
					return Locale::translate('payment.type.submission');
				}
			case PAYMENT_TYPE_FASTTRACK:
				if ($journal->getLocalizedSetting('fastTrackFeeName') != '') {
					return $journal->getLocalizedSetting('fastTrackFeeName');
				} else {
					return Locale::translate('payment.type.fastTrack');
				}
			case PAYMENT_TYPE_PUBLICATION:
				if ($journal->getLocalizedSetting('publicationFeeName') != '') {
					return $journal->getLocalizedSetting('publicationFeeName');
				} else {
					return Locale::translate('payment.type.publication');
				}
			case PAYMENT_TYPE_GIFT:
				$giftDao =& DAORegistry::getDAO('GiftDAO');
				$gift =& $giftDao->getGift($this->assocId);

				// Try to return gift details in name
				if ($gift) {
					return $gift->getGiftName();
				}

				// Otherwise, generic gift name
				return Locale::translate('payment.type.gift');
		}
	}

	/**
	 * Returns the description of the QueuedPayment.
	 * Pulled from Journal Settings if present, or from locale file otherwise.
	 * For subscriptions, pulls subscription type name.
	 * @return string
	 */
	function getDescription() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getJournal($this->getJournalId());

		switch ($this->type) {
			case PAYMENT_TYPE_PURCHASE_SUBSCRIPTION:
			case PAYMENT_TYPE_RENEW_SUBSCRIPTION:
				$institutionalSubscriptionDao =& DAORegistry::getDAO('InstitutionalSubscriptionDAO');

				if ($institutionalSubscriptionDao->subscriptionExists($this->assocId)) {
					$subscription =& $institutionalSubscriptionDao->getSubscription($this->assocId);
				} else {
					$individualSubscriptionDao =& DAORegistry::getDAO('IndividualSubscriptionDAO');
					$subscription =& $individualSubscriptionDao->getSubscription($this->assocId);
				}
				if (!$subscription) return Locale::translate('payment.type.subscription');

				$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
				$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($subscription->getTypeId());
				return $subscriptionType->getSubscriptionTypeDescription();
			case PAYMENT_TYPE_DONATION:
				if ($journal->getLocalizedSetting('donationFeeDescription') != '') {
					return $journal->getLocalizedSetting('donationFeeDescription');
				} else {
					return Locale::translate('payment.type.donation');
				}
			case PAYMENT_TYPE_MEMBERSHIP:
				if ($journal->getLocalizedSetting('membershipFeeDescription') != '') {
					return $journal->getLocalizedSetting('membershipFeeDescription');
				} else {
					return Locale::translate('payment.type.membership');
				}
			case PAYMENT_TYPE_PURCHASE_ARTICLE:
				if ($journal->getLocalizedSetting('purchaseArticleFeeDescription') != '') {
					return $journal->getLocalizedSetting('purchaseArticleFeeDescription');
				} else {
					return Locale::translate('payment.type.purchaseArticle');
				}
			case PAYMENT_TYPE_PURCHASE_ISSUE:
				if ($journal->getLocalizedSetting('purchaseIssueFeeDescription') != '') {
					return $journal->getLocalizedSetting('purchaseIssueFeeDescription');
				} else {
					return Locale::translate('payment.type.purchaseIssue');
				}
			case PAYMENT_TYPE_SUBMISSION:
				if ($journal->getLocalizedSetting('submissionFeeDescription') != '') {
					return $journal->getLocalizedSetting('submissionFeeDescription');
				} else {
					return Locale::translate('payment.type.submission');
				}
			case PAYMENT_TYPE_FASTTRACK:
				if ($journal->getLocalizedSetting('fastTrackFeeDescription') != '') {
					return $journal->getLocalizedSetting('fastTrackFeeDescription');
				} else {
					return Locale::translate('payment.type.fastTrack');
				}
			case PAYMENT_TYPE_PUBLICATION:
				if ($journal->getLocalizedSetting('publicationFeeDescription') != '') {
					return $journal->getLocalizedSetting('publicationFeeDescription');
				} else {
					return Locale::translate('payment.type.publication');
				}
			case PAYMENT_TYPE_GIFT:
				$giftDao =& DAORegistry::getDAO('GiftDAO');
				$gift =& $giftDao->getGift($this->assocId);

				// Try to return gift details in description
				if ($gift) {
					import('classes.gift.Gift');

					if ($gift->getGiftType() == GIFT_TYPE_SUBSCRIPTION) {
						$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');
						$subscriptionType =& $subscriptionTypeDao->getSubscriptionType($gift->getAssocId());

						if ($subscriptionType) {
							return $subscriptionType->getSubscriptionTypeDescription();	
						} else {
							return Locale::translate('payment.type.gift') . ' ' . Locale::translate('payment.type.gift.subscription');								
						}
					}
				}

				// Otherwise, generic gift name
				return Locale::translate('payment.type.gift');
		}
	}

	function setRequestUrl($url) {
		$this->requestUrl = $url;
	}

	function getRequestUrl() {
		return $this->requestUrl;
	}
}

?>
