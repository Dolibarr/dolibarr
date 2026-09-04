<?php
// Load Dolibarr environment
require '../../../../../../../main.inc.php';

/**
 * @var Translate $langs
 * @var User      $user
 */

if ($user->socid > 0) {
	accessforbidden();
}

// Load documentation translations
$langs->load('uxdocumentation');

// --- Get data attributes (each trigger passes its own values) ---
$productId = GETPOST('productId', 'int');
$productRef = GETPOST('productRef', 'alpha');
$productType = GETPOST('productType', 'alpha');

print '<div class="modal-example">';
print '<p class="nomargintop">This dialog was opened by one of several buttons sharing the same class selector, armed in a single call. The values below come from the button you clicked — proof that each trigger loads its own content:</p>';
print '<p>';
print '<b>ID:</b> '.$productId.'<br>';
print '<b>Reference:</b> '.$productRef.'<br>';
print '<b>Type:</b> '.ucfirst($productType);
print '</p>';
print '<p class="opacitymedium"><i class="fas fa-info-circle"></i> Curious how these values are passed to the dialog? See the <a href="#dialogsection-data" data-dol-dialog-close>Variables</a> section.</p>';
print '</div>';

print '<div class="dol-dialog-footer">';
print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
print '</div>';
