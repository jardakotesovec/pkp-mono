{**
 * textArea.tpl
 *
 * Copyright (c) 2009 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * form text area
 *}

<textarea {$FBV_textAreaParams} class="field textarea{if $FBV_sizeInfo} {$FBV_sizeInfo}{/if}"{if $FBV_disabled} disabled="disabled"{/if}>{$FBV_value|escape}</textArea>
