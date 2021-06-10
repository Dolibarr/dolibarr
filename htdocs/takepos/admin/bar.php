<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017 Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/takepos/admin/bar.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module - Bar Restaurant features
 */

require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";

// Security check
if (!$user->admin) {
	accessforbidden();
}

$langs->loadLangs(array("admin", "cashdesk", "printing"));

$res = 0;


/*
 * Actions
 */

if (GETPOST('action', 'alpha') == 'set') {
	$db->begin();

	dol_syslog("admin/bar");

	$suplement_category = GETPOST('TAKEPOS_SUPPLEMENTS_CATEGORY', 'alpha');
	if ($suplement_category < 0) {
		$suplement_category = 0;
	}

	$res = dolibarr_set_const($db, "TAKEPOS_SUPPLEMENTS_CATEGORY", $suplement_category, 'chaine', 0, '', $conf->entity);
	if ($res <= 0) {
		$error++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($conf->global->TAKEPOS_ORDER_NOTES == 1) {
	$extrafields = new ExtraFields($db);
	$extrafields->addExtraField('order_notes', 'Order notes', 'varchar', 0, 255, 'facturedet', 0, 0, '', '', 0, '', 0, 1);
}

/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

$arrayofcss = array("/takepos/css/colorbox.css");

llxHeader('', $langs->trans("CashDeskSetup"), '', '', 0, 0, $arrayofjs, $arrayofcss);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup").' (TakePOS)', $linkback, 'title_setup');
$head = takepos_admin_prepare_head();
print dol_get_fiche_head($head, 'bar', 'TakePOS', -1, 'cash-register');


// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

?>
<script type="text/javascript" src="<?php echo DOL_URL_ROOT ?>/takepos/js/jquery.colorbox-min.js"></script>	<!-- TODO It seems we don't need this -->
<script type="text/javascript">
function Floors() {
	console.log("Open box to select floor");
	$.colorbox({href:"<?php echo DOL_URL_ROOT ?>/takepos/floors.php?mode=edit&place=0", width:"90%", height:"90%", transition:"none", iframe:"true", title:"<?php echo $langs->trans("Floors"); ?>"});
}
</script>

<?php

print '<center>';
print $langs->trans("EnableBarOrRestaurantFeatures");
print ajax_constantonoff("TAKEPOS_BAR_RESTAURANT", array(), $conf->entity, 0, 0, 1, 0);
print '</center>';

print '<br>';

if ($conf->global->TAKEPOS_BAR_RESTAURANT) {
	print '<br>';
	print '<a href="" onclick="Floors(); return false;"><span class="fa fa-glass-cheers"></span> '.$langs->trans("DefineTablePlan").'</a><br>';
	print '<br><br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").'</td><td class="center">'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	if ($conf->global->TAKEPOS_PRINT_METHOD != "browser") {		// Why this ?
		print '<tr class="oddeven value"><td>';
		print $langs->trans("OrderPrinters").' (<a href="'.DOL_URL_ROOT.'/takepos/admin/orderprinters.php?leftmenu=setup">'.$langs->trans("Setup").'</a>)';
		print '</td>';
		print '<td class="center">';
		print ajax_constantonoff("TAKEPOS_ORDER_PRINTERS", array(), $conf->entity, 0, 0, 1, 0);
		//print $form->selectyesno("TAKEPOS_ORDER_PRINTERS", $conf->global->TAKEPOS_ORDER_PRINTERS, 1);
		print '</td></tr>';

		print '<tr class="oddeven value"><td>';
		print $langs->trans("OrderNotes");
		print '</td>';
		print '<td class="center">';
		print ajax_constantonoff("TAKEPOS_ORDER_NOTES", array(), $conf->entity, 0, 0, 1, 0);
		//print $form->selectyesno("TAKEPOS_ORDER_NOTES", $conf->global->TAKEPOS_ORDER_NOTES, 1);
		print '</td></tr>';
	} else {
		print '<tr class="oddeven value"><td>';
		print $langs->trans("OrderPrinters");
		print '</td>';
		print '<td class="center">';
		print '<span class="opacitymedium">'.$langs->trans("NotAvailableWithBrowserPrinter").'</span>';
		print '</td></tr>';

		print '<tr class="oddeven value"><td>';
		print $langs->trans("OrderNotes");
		print '</td>';
		print '<td class="center">';
		print '<span class="opacitymedium">'.$langs->trans("NotAvailableWithBrowserPrinter").'</span>';
		print '</td></tr>';
	}

	print '<tr class="oddeven value"><td>';
	print $langs->trans("BasicPhoneLayout");
	print '</td>';
	print '<td class="center">';
	//print $form->selectyesno("TAKEPOS_PHONE_BASIC_LAYOUT", $conf->global->TAKEPOS_PHONE_BASIC_LAYOUT, 1);
	print ajax_constantonoff("TAKEPOS_PHONE_BASIC_LAYOUT", array(), $conf->entity, 0, 0, 1, 0);
	print '</td></tr>';

	print '<tr class="oddeven value"><td>';
	print $langs->trans("ProductSupplements");
	print '</td>';
	print '<td class="center">';
	//print $form->selectyesno("TAKEPOS_SUPPLEMENTS", $conf->global->TAKEPOS_SUPPLEMENTS, 1);
	print ajax_constantonoff("TAKEPOS_SUPPLEMENTS", array(), $conf->entity, 0, 0, 1, 0);
	print '</td></tr>';

	if ($conf->global->TAKEPOS_SUPPLEMENTS) {
		print '<tr class="oddeven"><td>';
		print $langs->trans("SupplementCategory");
		print '</td>';
		print '<td class="center">';
		print $form->select_all_categories(Categorie::TYPE_PRODUCT, $conf->global->TAKEPOS_SUPPLEMENTS_CATEGORY, 'TAKEPOS_SUPPLEMENTS_CATEGORY', 64, 0, 0);
		print ajax_combobox('TAKEPOS_SUPPLEMENTS_CATEGORY');
		print "</td></tr>\n";
	}

	print '<tr class="oddeven value"><td>';
	print 'QR - '.$langs->trans("CustomerMenu");
	print '</td>';
	print '<td class="center">';
	print ajax_constantonoff("TAKEPOS_QR_MENU", array(), $conf->entity, 0, 0, 1, 0);
	print '</td></tr>';

	print '<tr class="oddeven value"><td>';
	print 'QR - '.$langs->trans("AutoOrder");
	print '</td>';
	print '<td class="center">';
	print ajax_constantonoff("TAKEPOS_AUTO_ORDER", array(), $conf->entity, 0, 0, 1, 0);
	print '</td></tr>';

	print '</table>';
	print '</div>';

	print '<br>';

	print '<div class="center"><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></div>';
}

if (!empty($conf->global->TAKEPOS_BAR_RESTAURANT)) {
	if ($conf->global->TAKEPOS_QR_MENU) {
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		print '<br>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("URL").'</td><td class="right">'.$langs->trans("QR").'</td>';
		print "</tr>\n";
		print '<tr class="oddeven value"><td>';
		print '<a target="_blank" href="'.$urlwithroot.'/takepos/public/menu.php">'.$urlwithroot.'/takepos/public/menu.php</a>';
		print '</td>';
		print '<td class="right">';
		print '<a target="_blank" href="printqr.php"><img src="'.DOL_URL_ROOT.'/takepos/genimg/qr.php" height="42" width="42"></a>';
		print '</td></tr>';
		print '</table>';
	}

	if ($conf->global->TAKEPOS_AUTO_ORDER) {
		print '<br>';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Table").'</td><td>'.$langs->trans("URL").'</td><td class="right">'.$langs->trans("QR").'</td>';
		print "</tr>\n";

		//global $dolibarr_main_url_root;
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		$sql = "SELECT rowid, entity, label, leftpos, toppos, floor FROM ".MAIN_DB_PREFIX."takepos_floor_tables";
		$resql = $db->query($sql);
		$rows = array();
		while ($row = $db->fetch_array($resql)) {
			print '<tr class="oddeven value"><td>';
			print $langs->trans("Table")." ".$row['label'];
			print '</td>';
			print '<td>';
			print "<a target='_blank' href='".$urlwithroot."/takepos/public/auto_order.php?key=".dol_encode($row['rowid'])."'>".$urlwithroot."/takepos/public/auto_order.php?key=".dol_encode($row['rowid'])."</a>";
			print '</td>';
			print '<td class="right">';
			print "<a target='_blank' href='printqr.php?id=".$row['rowid']."'><img src='".DOL_URL_ROOT."/takepos/genimg/qr.php?key=".dol_encode($row['rowid'])."' height='42' width='42'></a>";
			print '</td></tr>';
		}

		print '</table>';
	}
}


print "</form>\n";

print '<br>';

llxFooter();
$db->close();
