<?php
// Load Dolibarr environment
require '../../../../../../../main.inc.php';

if ($user->socid > 0) {
	accessforbidden();
}

// Load documentation translations
$langs->load('uxdocumentation');

// --- Get data attributes ---
$productId   	= GETPOST('productId',    'int');
$productRef  	= GETPOST('productRef',   'alpha');
$productPrice   = GETPOST('productPrice', 'alpha');
$productType    = GETPOST('productType',  'alpha');


print '<div class="modal-example">';

	print '<p class="nomargintop">'.$langs->trans('DocUiDialogExamplePresentation', '<b>'.$user->login.'</b>').'</p>';
	print '<p>'.$langs->trans('DocUiDialogExampleDataAttributes').'</p>';
	print '<p>';
		print '<b>ID:</b> '.$productId.'<br>';
		print '<b>Référence:</b> '.$productRef.'<br>';
		print '<b>Price:</b> '.price($productPrice).' '.$conf->currency;
	print '</p>';

print '</div>';
