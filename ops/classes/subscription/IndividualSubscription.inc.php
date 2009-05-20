<?php

/**
 * @defgroup subscription
 */
 
/**
 * @file classes/subscription/IndividualSubscription.inc.php
 *
 * Copyright (c) 2003-2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IndividualSubscription
 * @ingroup subscription 
 * @see IndividualSubscriptionDAO
 *
 * @brief Basic class describing an individual (non-institutional) subscription.
 */

// $Id$

import('subscription.Subscription');


class IndividualSubscription extends Subscription {

	function IndividualSubscription() {
		parent::Subscription();
	}

}

?>
