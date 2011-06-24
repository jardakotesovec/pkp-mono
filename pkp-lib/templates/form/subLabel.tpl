{**
 * templates/form/label.tpl
 *
 * Copyright (c) 2000-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form label
 *}

<label class="sub_label{if $FBV_error} error{/if}" {if !$FBV_suppressId} for="{$FBV_id|escape}"{/if}>
	{translate key=$FBV_label|escape} {if $FBV_required}<span class="req">*</span>{/if}
</label>
