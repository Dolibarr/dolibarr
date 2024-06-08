<?php
/* Copyright (C) 2018 Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/printbox.php
 *	\ingroup    takepos
 *	\brief      Popup to enter details before printing
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))	define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))	define('NOREQUIRETRAN','1');
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

global $langs, $db;

$langs->loadLangs(array("bills", "cashdesk"));

$facid = GETPOSTINT('facid');

$action = GETPOST('action', 'aZ09');

if (!$user->hasRight('takepos', 'run')) {
	accessforbidden();
}


/*
 * View
 */

$arrayofcss = array('/takepos/css/pos.css.php');
$arrayofjs = array('/takepos/js/jquery.colorbox-min.js');

$head = '';
top_htmlhead($head, '', 0, 0, $arrayofjs, $arrayofcss);

?>
<body>
<script>
	/**
	 * Save (validate)
	 */
	function Save() {
		console.log("We click so we call page receipt.php with facid=<?php echo $facid; ?>");
		parent.$.colorbox.close();
		$.colorbox({ href:"receipt.php?facid=<?php echo $facid; ?>&action=<?php echo $action; ?>&token=<?php echo newToken(); ?>&label="+$('#label').val()+"&qty="+$('#qty').val(), width:"40%", height:"90%", transition:"none", iframe:"true", title:'<?php echo dol_escape_js($langs->trans("PrintTicket")); ?>'});
	}

	jQuery(document).ready(function(){
		$('#label').focus()
	});
</script>

<br>
<center>
<?php
if ($action == "without_details") {
	$label_default = getDolGlobalString('TAKEPOS_PRINT_WITHOUT_DETAILS_LABEL_DEFAULT');
	$qty_default = 1;

	print '<input type="text" id="label" name="label" class="takepospay" value="' . $label_default . '" style="width:40%;" placeholder="' . $langs->trans('Label') . '">';
	print '<input type="text" id="qty" name="qty" class="takepospay" value="' . $qty_default . '" style="width:10%;" placeholder="' . $langs->trans('Qty') . '">';
}
?>
<input type="button" class="button takepospay clearboth" value="OK" onclick="Save();">
</center>

</body>
</html>
