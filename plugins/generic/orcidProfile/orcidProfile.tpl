{**
 * plugins/generic/orcidProfile/orcidProfile.tpl
 *
 * Copyright (c) 2015-2016 University of Pittsburgh
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * ORCID Profile authorization form
 *
 *}
<script type="text/javascript">

function openORCID() {ldelim}
	window.location.replace("{$orcidProfileOauthPath|escape}authorize?client_id={$orcidClientId|urlencode}&response_type=code&scope=/authenticate&redirect_uri={url|urlencode page="orcidapi" op="orcidAuthorize" targetOp=$targetOp params=$params escape=false}");
	return false;
{rdelim}
</script>

<button id="connect-orcid-button" onclick="return openORCID();"><img id="orcid-id-logo" src="http://orcid.org/sites/default/files/images/orcid_24x24.png" width="24" height="24" alt="{translate key='plugins.generic.orcidProfile.submitAction'}"/>Create or Connect your ORCID iD</button>
