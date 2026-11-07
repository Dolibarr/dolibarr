<?php
// Load Dolibarr environment
require '../../../../../../../main.inc.php';

/**
 * @var Conf $conf
 * @var Translate $langs
 * @var User $user
 */

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
	print '<p class="nomargintop">Just set a data- attribute to your button in kebab-case and grab it in camelCase. E.g. `data-product-ref` becomes `GETPOST(\'productRef\')`.</p>';
	print '<p>';
		print '<b>ID:</b> '.$productId.'<br>';
		print '<b>Référence:</b> '.$productRef.'<br>';
		print '<b>Type:</b> '.ucfirst($productType).'<br>';
		print '<b>Price:</b> '.price($productPrice).' '.$conf->currency;
	print '</p>';
print '</div>';

print '<div class="dol-dialog-footer">';
	print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
print '</div>';
