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
$langs->load('uxdocumentation');

// --- Get data attributes ---
$dialogHeader = GETPOST('dialogHeader', 'alphanohtml');

if ($dialogHeader == 'no') {
	print '<div class="center">';
		print '<h4 class="nomargintop nomarginbottom">No header? No problem.</h4>';
		print '<p class="nomargintop">Perfect for lightweight confirmations, quick alerts, or minimal prompts where you just need a simple message.</p>';

		print '<div class="center" style="margin-top:24px;">';
			print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
		print '</div>';
	print '</div>';
} elseif ($dialogHeader == 'icon') {
	print '<p class="nomargintop">Use the icon parameter to display any icon of your choice in the dialog. Just pass FontAwesome class of your icon in params.</p>';
	print '<h4 class="nomargintop nomarginbottom">Examples</h4>';
	print '<p class="nomargintop">icon: \'fas fa-flask\'<br>';
	print 'icon: \'fas fa-dolly infobox-order_supplier\'</p>';
	print '<div class="right" style="margin-top:24px;">';
			print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
		print '</div>';
} elseif ($dialogHeader == 'iconcolor') {
	print '<p class="nomargintop">There is also a setting that allows you to control the color of the icon: icon_color</p>';
	print '<h4 class="nomargintop nomarginbottom">Examples</h4>';
	print '<p class="nomargintop">icon: \'fas fa-landmark\', icon_color: \'#b0bb39\'<br>';
	print 'icon: \'fas fa-cube\', icon_color: \'#a69944\'</p>';
	print '<div class="right" style="margin-top:24px;">';
		print '<button class="dialog-btn dialog-btn-primary" style="margin:0;" data-dol-dialog-close>OK</button>';
	print '</div>';
}
