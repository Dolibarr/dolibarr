<?php
// Load Dolibarr environment
require '../../../../../../../main.inc.php';

/**
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

if ($user->socid > 0) {
	accessforbidden();
}

// Load documentation translations
$langs->load('uxdocumentation');

// --- Get data attributes ---
$persist = GETPOSTINT('persist');

// Form class
$form = new Form($db);

// Form params
$formID = 'dol-dialog-persist';
$formAction = '#';

print '<form class="dol-dialog-ajax" id="'.$formID.'" action="'.$formAction.'" method="POST">';

if ($persist) {
	print '<p class="nomargintop">I am a persistent dialog. Once opened, I remain in the DOM. Try filling in the field below, then close me. When you reopen this dialog, the value you entered will still be in the field.</p>';
	print '<label for="" class="bold paddingbottom" style="display:block;">My persistent value</label>';
	print '<input type="text" name="persistantvalue" id="persistantvalue" class="centpercent">';
} else {
	print '<p class="nomargintop">I am a non-persistent dialog. Once closed, I am removed from the DOM. Try filling in the field below, then close me. When you reopen this dialog, the value you entered will no longer be there because the dialog is rebuilt from scratch each time.</p>';
	print '<label for="" class="bold paddingbottom" style="display:block;">My persistent value</label>';
	print '<input type="text" name="nonpersistantvalue" id="nonpersistantvalue" class="centpercent">';
}
print '</form>';

print '<div class="dol-dialog-footer">';
	print '<button class="dialog-btn dialog-btn-primary" data-dol-dialog-close>Close dialog</button>';
print '</div>';
