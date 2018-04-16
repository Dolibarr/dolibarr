<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville   	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur   	<eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  	<marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin          	<regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Andreu Bisquerra Gaya  	<jove@bisquerra.com>
 * Copyright (C) 2012	   David Rodriguez Martinez <davidrm146@gmail.com>
 * Copyright (C) 2012-2017 Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2014	   Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015      Marcos García            <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/fourn/commande/orderstoinvoice.php
 * \ingroup commande
 * \brief Page to invoice multiple supplier orders
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_invoice/modules_facturefournisseur.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}

$langs->load('orders');
$langs->load('deliveries');
$langs->load('companies');

if (! $user->rights->fournisseur->facture->creer)
	accessforbidden();

$id = (GETPOST('id') ? GETPOST('id', 'int') : GETPOST("facid")); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$sref = GETPOST('sref');
$sref_client = GETPOST('sref_client');
$sall = trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$socid = GETPOST('socid', 'int');
$selected = GETPOST('orders_to_invoice');
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$viewstatut = GETPOST('viewstatut');

if (! $sortfield)
	$sortfield = 'c.rowid';
if (! $sortorder)
	$sortorder = 'DESC';

$now = dol_now();
$date_start = dol_mktime(0, 0, 0, $_REQUEST["date_startmonth"], $_REQUEST["date_startday"], $_REQUEST["date_startyear"]); // Date for local PHP server
$date_end = dol_mktime(23, 59, 59, $_REQUEST["date_endmonth"], $_REQUEST["date_endday"], $_REQUEST["date_endyear"]);
$date_starty = dol_mktime(0, 0, 0, $_REQUEST["date_start_delymonth"], $_REQUEST["date_start_delyday"], $_REQUEST["date_start_delyyear"]); // Date for local PHP server
$date_endy = dol_mktime(23, 59, 59, $_REQUEST["date_end_delymonth"], $_REQUEST["date_end_delyday"], $_REQUEST["date_end_delyyear"]);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('facture_fourn');

if ($action == 'create')
{
	if (! is_array($selected))
	{
		$error++;
		setEventMessages($langs->trans('Error_OrderNotChecked'), null, 'errors');
	} else {
		$origin = GETPOST('origin');
		$originid = GETPOST('originid');
	}
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('orderstoinvoicesupplier'));


/*
 * Actions
 */

if (($action == 'create' || $action == 'add') && ! $error) {

	require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
	if (! empty($conf->projet->enabled))
		require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

	$langs->load('bills');
	$langs->load('products');
	$langs->load('main');
	if (isset($_GET['orders_to_invoice'])) {
		$orders_id = GETPOST('orders_to_invoice','',1);
		$n = count($orders_id);
		$i = 0;

		$originid = $orders_id[0];
		$_GET['originid'] = $orders_id[0];
	}
	if (isset($_POST['orders_to_invoice'])) {
		$orders_id = GETPOST('orders_to_invoice','',2);
		$nn = count($orders_id);
		$ii = 0;

		$originid = $orders_id[0];
		$_POST['originid'] = $orders_id[0];
	}

	$projectid = GETPOST('projectid', 'int') ? GETPOST('projectid', 'int') : 0;
	$lineid = GETPOST('lineid', 'int');
	$userid = GETPOST('userid', 'int');
	$search_ref = GETPOST('sf_ref') ? GETPOST('sf_ref') : GETPOST('search_ref');

	// Security check
	if ($user->societe_id)
		$socid = $user->societe_id;
	$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

	$usehm = $conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;
	$object = new FactureFournisseur($db);

	// Insert new invoice in database
	if ($action == 'add' && $user->rights->fournisseur->facture->creer) {
		$object->socid = GETPOST('socid');
		$db->begin();
		$error = 0;

		// Standard or deposit or proforma invoice
		$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		if (empty($datefacture)) {
			$datefacture = dol_mktime(date("h"), date("M"), 0, date("m"), date("d"), date("Y"));
		}
		if (! $error) {
			$object->ref = GETPOST('ref');
			$object->ref_supplier = GETPOST('ref_supplier');
			$object->socid = GETPOST('socid','int');
			$object->libelle = GETPOST('libelle');
			$object->date = $datefacture;
			$object->date_echeance = $datedue;
			$object->note_public = GETPOST('note_public','none');
			$object->note_private = GETPOST('note_private','none');
			$object->cond_reglement_id = GETPOST('cond_reglement_id');
			$object->mode_reglement_id = GETPOST('mode_reglement_id');
			$projectid = GETPOST('projectid');
			if ($projectid > 0)
				$object->fk_project = $projectid;

				// Auto calculation of date due if not filled by user
			if (empty($object->date_echeance))
				$object->date_echeance = $object->calculate_date_lim_reglement();

			$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0) $error++;

			if ($_POST['origin'] && $_POST['originid']) {
				$linked_orders_ids=array();
				foreach ( $orders_id as $origin => $origin_id ) {
					$origin_id = (! empty($origin_id) ? $origin_id : $orders_id[$ii]);
					$linked_orders_ids[]=$origin_id;
				}
				$object->linked_objects = array(GETPOST('origin')=>$linked_orders_ids);
				$id = $object->create($user);

				if ($id > 0) {
					while ( $ii < $nn ) {
						$objectsrc = new CommandeFournisseur($db);
						dol_syslog("Try to find source object origin=" . $object->origin . " originid=" . $object->origin_id . " to add lines");
						$result = $objectsrc->fetch($orders_id[$ii]);
						if ($result > 0) {
							$lines = $objectsrc->lines;
							if (empty($lines) && method_exists($objectsrc, 'fetch_lines')) {
								$objectsrc->fetch_lines();
								$lines = $objectsrc->lines;
							}
							$fk_parent_line = 0;
							$num = count($lines);
							for($i = 0; $i < $num; $i ++) {
								$desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->libelle);

								$desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->libelle);
								$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

								// Dates
								// TODO mutualiser
								$date_start = $lines[$i]->date_debut_prevue;
								if ($lines[$i]->date_debut_reel)
									$date_start = $lines[$i]->date_debut_reel;
								if ($lines[$i]->date_start)
									$date_start = $lines[$i]->date_start;
								$date_end = $lines[$i]->date_fin_prevue;
								if ($lines[$i]->date_fin_reel)
									$date_end = $lines[$i]->date_fin_reel;
								if ($lines[$i]->date_end)
									$date_end = $lines[$i]->date_end;

									// Reset fk_parent_line for no child products and special product
								if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
									$fk_parent_line = 0;
								}
								// FIXME Missing $lines[$i]->ref_supplier and $lines[$i]->label into addline and updateline methods. They are filled when coming from order for example.
								$result = $object->addline($desc, $lines[$i]->subprice, $lines[$i]->tva_tx, $lines[$i]->localtax1_tx, $lines[$i]->localtax2_tx, $lines[$i]->qty, $lines[$i]->fk_product, $lines[$i]->remise_percent, $date_start, $date_end, 0, $lines[$i]->info_bits, 'HT', $product_type, -1, false, 0, $lines[$i]->fk_unit);

								if ($result > 0) {
									$lineid = $result;
								} else {
									$lineid = 0;
									$error ++;
									break;
								}
								// Defined the new fk_parent_line
								if ($result > 0 && $lines[$i]->product_type == 9) {
									$fk_parent_line = $result;
								}
							}
						} else {
							$mesgs[] = $objectsrc->error;
							$error ++;
						}
						$ii ++;
					}
				} else {
					$mesgs[] = $object->error;
					$error ++;
				}
			}
		}

		// End of object creation, we show it
		if ($id > 0 && ! $error) {

			foreach($orders_id as $fk_supplier_order) {
				$supplier_order = new CommandeFournisseur($db);
				if ($supplier_order->fetch($fk_supplier_order)>0 && $supplier_order->statut == 5)
				{
					if ($supplier_order->classifyBilled($user) < 0)
					{
						$db->rollback();
						$action = 'create';
						$_GET["origin"] = $_POST["origin"];
						$_GET["originid"] = $_POST["originid"];
						$mesgs[] = '<div class="error">' . $object->error . '</div>';

						$error++;
						break;
					}

				}
			}

			if(!$error) {
				$db->commit();
				header('Location: ' . DOL_URL_ROOT . '/fourn/facture/card.php?facid=' . $id);
				exit();
			}

		} else {
			$db->rollback();
			$action = 'create';
			$_GET["origin"] = $_POST["origin"];
			$_GET["originid"] = $_POST["originid"];
			$mesgs[] = '<div class="error">' . $object->error . '</div>';
		}
	}
}

/*
 * View
 */

$html = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);

// Mode creation
if ($action == 'create' && !$error) {

	llxHeader();
	print load_fiche_titre($langs->trans('NewBill'));

	$soc = new Societe($db);
	if ($socid)
		$res = $soc->fetch($socid);
	if ($res) {
		$cond_reglement_id = $soc->cond_reglement_id;
		$mode_reglement_id = $soc->mode_reglement_id;
	}
	$dateinvoice = empty($conf->global->MAIN_AUTOFILL_DATE) ? - 1 : '';

	print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="' . $soc->id . '">' . "\n";
	print '<input name="facnumber" type="hidden" value="provisoire">';
	print '<input name="ref_client" type="hidden" value="' . $ref_client . '">';
	print '<input name="ref_int" type="hidden" value="' . $ref_int . '">';
	print '<input type="hidden" name="origin" value="' . GETPOST('origin') . '">';
	print '<input type="hidden" name="originid" value="' . GETPOST('originid') . '">';
	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="fieldrequired">' . $langs->trans('Ref') . '</td><td colspan="2">' . $langs->trans('Draft') . '</td></tr>';

	// Ref supplier
	print '<tr><td class="fieldrequired">' . $langs->trans('RefSupplier') . '</td><td><input name="ref_supplier" value="' . dol_escape_htmltag(isset($_POST['ref_supplier']) ? GETPOST('ref_supplier','alpha', 2) : '') . '" type="text"></td>';
	print '</tr>';

	// Third party
	print '<tr><td class="fieldrequired">' . $langs->trans('Customer') . '</td><td colspan="2">';
	print $soc->getNomUrl(1);
	print '<input type="hidden" name="socid" value="' . $soc->id . '">';
	print '</td>';
	print '</tr>' . "\n";

	// Date invoice
	print '<tr><td class="fieldrequired">' . $langs->trans('Date') . '</td><td colspan="2">';
	$html->select_date('', '', '', '', '', "add", 1, 1);
	print '</td></tr>';
	// Payment term
	print '<tr><td class="nowrap">' . $langs->trans('PaymentConditionsShort') . '</td><td colspan="2">';
	$html->select_conditions_paiements(isset($_POST['cond_reglement_id']) ? $_POST['cond_reglement_id'] : $cond_reglement_id, 'cond_reglement_id');
	print '</td></tr>';
	// Payment mode
	print '<tr><td>' . $langs->trans('PaymentMode') . '</td><td colspan="2">';
	$html->select_types_paiements(isset($_POST['mode_reglement_id']) ? $_POST['mode_reglement_id'] : $mode_reglement_id, 'mode_reglement_id');
	print '</td></tr>';
	// Project
	if (! empty($conf->projet->enabled)) {
		$formproject = new FormProjets($db);

		$langs->load('projects');
		print '<tr><td>' . $langs->trans('Project') . '</td><td colspan="2">';
		$formproject->select_projects($soc->id, $projectid, 'projectid');
		print '</td></tr>';
	}

	$objectsrc = new CommandeFournisseur($db);
	$listoforders = array ();
	foreach ( $selected as $sel ) {
		$result = $objectsrc->fetch($sel);
		if ($result > 0) {
			$listoforders[] = $objectsrc->ref;
		}
	}

	// Other attributes
	$parameters = array (
			'objectsrc' => $objectsrc,
			'idsrc' => $listoforders,
			'colspan' => ' colspan="2"',
	        'cols'=>2
	);
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (empty($reshook))
	{
		$object=new FactureFournisseur($db);
		print $object->showOptionals($extrafields,'edit');
	}

	// Modele PDF
	print '<tr><td>' . $langs->trans('Model') . '</td>';
	print '<td>';
	$liste = ModelePDFSuppliersInvoices::liste_modeles($db);
	print $html->selectarray('model', $liste, $conf->global->INVOICE_SUPPLIER_ADDON_PDF);
	print "</td></tr>";

	// Public note
	print '<tr>';
	print '<td class="border" valign="top">' . $langs->trans('NotePublic') . '</td>';
	print '<td valign="top" colspan="2">';
	print '<textarea name="note_public" wrap="soft" class="quatrevingtpercent" rows="' . ROWS_3 . '">';

	print $langs->trans("Orders") . ": " . implode(', ', $listoforders);

	print '</textarea></td></tr>';
	// Private note
	if (empty($user->societe_id)) {
		print '<tr>';
		print '<td class="border" valign="top">' . $langs->trans('NotePrivate') . '</td>';
		print '<td valign="top" colspan="2">';
		print '<textarea name="note" wrap="soft" cols="70" rows="' . ROWS_3 . '">';

		print '</textarea></td></tr>';
	}

	print '</table>';

	while ( $i < $n ) {
		print '<input type="hidden" name="orders_to_invoice[]" value="' . $orders_id[$i] . '">';

		$i ++;
	}

	// Button "Create Draft"
	print '<br><div class="center"><input type="submit" class="button" name="bouton" value="' . $langs->trans('CreateDraft') . '" /></div>';
	print "</form>\n";

	print '</td></tr>';
	print "</table>\n";
}

// Mode liste
if (($action != 'create' && $action != 'add') && !$error) {
	llxHeader();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
		jQuery("#checkall").click(function() {
			jQuery(".checkformerge").prop('checked', true);
		});
		jQuery("#checknone").click(function() {
			jQuery(".checkformerge").prop('checked', false);
		});
	});
	</script>
<?php

	$sql = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_supplier,';
	$sql .= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'societe as s';
	$sql .= ', ' . MAIN_DB_PREFIX . 'commande_fournisseur as c';
	if (! $user->rights->societe->client->voir && ! $socid)
		$sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
	$sql .= ' WHERE c.entity = ' . $conf->entity;
	$sql .= ' AND c.fk_soc = s.rowid';

	// Show orders with status validated, shipping started and delivered (well any order we can bill)
	$sql .= " AND c.fk_statut IN (5)";
	$sql .= " AND c.billed = 0";

	// Find order that are not already invoiced
	//No need due to the billed status
	//$sql .= " AND c.rowid NOT IN (SELECT fk_source FROM " . MAIN_DB_PREFIX . "element_element WHERE targettype='invoice_supplier')";

	if ($socid)
		$sql .= ' AND s.rowid = ' . $socid;
	if (! $user->rights->societe->client->voir && ! $socid)
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
	if ($sref) {
		$sql .= natural_search("c.ref", $sref);
	}
	if ($sall) {
		$sql .= natural_search(array("c.ref","c.note"), $sall);
	}

	// Date filter
	if ($date_start && $date_end)
		$sql .= " AND c.date_commande >= '" . $db->idate($date_start) . "' AND c.date_commande <= '" . $db->idate($date_end) . "'";
	if ($date_starty && $date_endy)
		$sql .= " AND c.date_livraison >= '" . $db->idate($date_starty) . "' AND c.date_livraison <= '" . $db->idate($date_endy) . "'";

	if (! empty($sref_client)) {
		$sql .= natural_search('c.ref_supplier', $sref_client);
	}
	$sql .= ' ORDER BY ' . $sortfield . ' ' . $sortorder;
	dol_syslog('fourn/commande/ordertoinvoice.php sql=' . $sql);
	$resql = $db->query($sql);

	if ($resql) {
		if ($socid) {
			$soc = new Societe($db);
			$soc->fetch($socid);
		}
		$title = $langs->trans('ListOfSupplierOrders');
		$title .= ' - ' . $langs->trans('StatusOrderReceivedAllShort');
		$num = $db->num_rows($resql);
		print load_fiche_titre($title);
		$i = 0;
		$period = $html->select_date($date_start, 'date_start', 0, 0, 1, '', 1, 0, 1) . ' - ' . $html->select_date($date_end, 'date_end', 0, 0, 1, '', 1, 0, 1);
		$periodely = $html->select_date($date_starty, 'date_start_dely', 0, 0, 1, '', 1, 0, 1) . ' - ' . $html->select_date($date_endy, 'date_end_dely', 0, 0, 1, '', 1, 0, 1);

		if (! empty($socid)) {
			// Company
			$companystatic->id = $socid;
			$companystatic->nom = $soc->nom;
			print '<h3>' . $companystatic->getNomUrl(1, 'customer') . '</h3>';
		}

		print '<form name="orders2invoice" method="GET" action="orderstoinvoice.php">';
		print '<input type="hidden" name="socid" value="' . $socid . '">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre('Ref', 'orderstoinvoice.php', 'c.ref', '', '&amp;socid=' . $socid, '', $sortfield, $sortorder);
		print_liste_field_titre('RefSupplier', 'orderstoinvoice.php', 'c.ref_supplier', '', '&amp;socid=' . $socid, '', $sortfield, $sortorder);
		print_liste_field_titre('OrderDate', 'orderstoinvoice.php', 'c.date_commande', '', '&amp;socid=' . $socid, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre('DeliveryDate', 'orderstoinvoice.php', 'c.date_livraison', '', '&amp;socid=' . $socid, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre('Status', '', '', '', '', 'align="right"');
		print_liste_field_titre('GenerateBill', '', '', '', '', 'align="center"');
		print "</tr>\n";

		// Lignes des champs de filtre
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">';
		// REF
		print '<input class="flat" size="10" type="text" name="sref" value="' . $sref . '">';
		print '</td>';
		// print '<td class="liste_titre">';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="10" name="sref_client" value="' . $sref_client . '">';
        print '</td>';

		// DATE ORDER
		print '<td class="liste_titre" align="center">';
		print $period;
		print '</td>';

		// DATE DELIVERY
		print '<td class="liste_titre" align="center">';
		print $periodely;
		print '</td>';

		// SEARCH BUTTON
		print '<td align="right" class="liste_titre">';
		print '<input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '"  value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
		print '</td>';

		// ALL/NONE
		print '<td class="liste_titre" align="center">';
		if ($conf->use_javascript_ajax)
			print '<a href="#" id="checkall">' . $langs->trans("All") . '</a> / <a href="#" id="checknone">' . $langs->trans("None") . '</a>';
		print '</td>';

		print '</td></tr>';

		$var = True;
		$generic_commande = new CommandeFournisseur($db);

		while ( $i < $num ) {
			$objp = $db->fetch_object($resql);
			$var = ! $var;
			print '<tr ' . $bc[$var] . '>';
			print '<td class="nowrap">';

			$generic_commande->id = $objp->rowid;
			$generic_commande->ref = $objp->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding nowrap">';
			print $generic_commande->getNomUrl(1, $objp->fk_statut);
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
			$filename = dol_sanitizeFileName($objp->ref);
			$filedir = $conf->fournisseur->commande->dir_output . '/' . dol_sanitizeFileName($objp->ref);
			$urlsource = $_SERVER['PHP_SELF'] . '?id=' . $objp->rowid;
			print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);
			print '</td></tr></table>';
			print '</td>';

			print '<td>' . $objp->ref_supplier . '</td>';

			// Order date
			print '<td align="center" nowrap>';
			print dol_print_date($db->jdate($objp->date_commande), 'day');
			print '</td>';

			// Delivery date
			print '<td align="center" nowrap>';
			print dol_print_date($db->jdate($objp->date_livraison), 'day');
			print '</td>';

			// Statut
			print '<td align="right" class="nowrap">' . $generic_commande->LibStatut($objp->fk_statut, 5) . '</td>';

			// Checkbox
			print '<td class="center">';
			print '<input class="flat checkformerge" type="checkbox" name="orders_to_invoice[]" value="' . $objp->rowid . '">';
			print '</td>';

			print '</tr>';

			$total = $total + $objp->price;
			$subtotal = $subtotal + $objp->price;
			$i ++;
		}
		print '</table>';

		/*
		 * Boutons actions
		*/
		print '<div class="center">';
		print '<div class="right">';
		print '<input type="hidden" name="socid" value="' . $socid . '">';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="hidden" name="origin" value="order_supplier"><br>';
		// print '<a class="butAction" href="index.php">'.$langs->trans("GoBack").'</a>';
		print '<input type="submit" class="butAction" value="' . $langs->trans("GenerateBill") . '">';
		print '</div>';
		print '</div>';

		print '</form>';

		$db->free($resql);
	} else {
		print dol_print_error($db);
	}
}

dol_htmloutput_mesg($mesg, $mesgs);

llxFooter();
$db->close();
