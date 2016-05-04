<?php

/**
 * @file plugins/generic/orcidProfile/OrcidHandler.inc.php
 *
 * Copyright (c) 2015-2016 University of Pittsburgh
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2 or later. For full terms see the file docs/COPYING.
 *
 * @class OrcidHandler
 * @ingroup plugins_generic_orcidprofile
 *
 * @brief Pass off internal ORCID API requests to ORCID
 */

import('classes.handler.Handler');

class OrcidHandler extends Handler {
	/**
	 * Authorize handler
	 * @param $args array
	 * @param $request Request
	 */
	function orcidAuthorize($args, &$request) {

		$journal = Request::getJournal();
		$op = Request::getRequestedOp();
		$plugin =& PluginRegistry::getPlugin('generic', 'orcidprofileplugin');

		// fetch the access token
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $plugin->getSetting($journal->getId(), 'orcidProfileAPIPath').OAUTH_TOKEN_URL,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array('Accept: application/json'),
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query(array(
				'code' => Request::getUserVar('code'),
				'grant_type' => 'authorization_code',
				'client_id' => $plugin->getSetting($journal->getId(), 'orcidClientId'),
				'client_secret' => $plugin->getSetting($journal->getId(), 'orcidClientSecret')
			))
		));
		$result = curl_exec($curl);
		$response = json_decode($result, true);

		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL =>  $url = $plugin->getSetting($journal->getId(), 'orcidProfileAPIPath') . ORCID_API_VERSION_URL . urlencode($response['orcid']) . '/' . ORCID_PROFILE_URL,
			CURLOPT_POST => false,
			CURLOPT_HTTPHEADER => array('Accept: application/json'),
		));
		$result = curl_exec($curl);
		$info = curl_getinfo($curl);
		if ($info['http_code'] == 200) {
			$json = json_decode($result, true);
		}

		switch (Request::getUserVar('targetOp')) {
			case 'register':
				echo '<html><body><script type="text/javascript">
					opener.document.getElementById("firstName").value = ' . json_encode($json['orcid-profile']['orcid-bio']['personal-details']['given-names']['value']) . ';
					opener.document.getElementById("lastName").value = ' . json_encode($json['orcid-profile']['orcid-bio']['personal-details']['family-name']['value']) . ';
					opener.document.getElementById("email").value = ' . json_encode($json['orcid-profile']['orcid-bio']['contact-details']['email'][0]['value']) . ';
					opener.document.getElementById("orcid").value = ' . json_encode('http://orcid.org/' . $response['orcid']). ';
					opener.document.getElementById("connect-orcid-button").style.display = "none";
					window.close();
				</script></body></html>';
				break;
			case 'profile':
				// Set the ORCiD in the user profile from the response
				echo '<html><body><script type="text/javascript">
					opener.document.getElementById("orcid").value = ' . json_encode('http://orcid.org/' . $response['orcid']). ';
					opener.document.getElementById("connect-orcid-button").style.display = "none";
					window.close();
				</script></body></html>';
				break;
			case 'submit':
				// Submission process: Pre-fill the first author's ORCiD from the ORCiD data
				echo '<html><body><script type="text/javascript">
					opener.document.getElementById("authors-0-orcid").value = ' . json_encode('http://orcid.org/' . $response['orcid']). ';
					opener.document.getElementById("connect-orcid-button").style.display = "none";
					window.close();
				</script></body></html>';
				break;
			default: assert(false);
		}
	}
}

?>
