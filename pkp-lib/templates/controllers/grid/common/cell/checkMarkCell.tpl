{**
 * templates/controllers/grid/common/cell/checkMarkCell.tpl
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Cell to represent a boolean true with a check mark.
 *
 *}
{if $isChecked}
	<div id="isChecked"><div href="#" class='pkp_helpers_container_center checked'></div></div>
{else}
	<div id="notChecked"><div href="#" class='pkp_helpers_container_center notChecked'></div></div>
{/if}

