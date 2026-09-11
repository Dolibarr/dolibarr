<?php
// Load Dolibarr environment
require '../../../../../../../main.inc.php';

/**
 * @var Translate $langs
 * @var User $user
 */

if ($user->socid > 0) {
	accessforbidden();
}

// Load documentation translations
$langs->load('uxdocumentation'); ?>

<p class="nomargintop">I am a dialog that opens instantly without any opening animation, meaning I appear immediately in the DOM with no transition effects or visual delays.</p>

<div class="dol-dialog-footer">
	<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>
</div>
