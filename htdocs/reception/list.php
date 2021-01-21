<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2016	   Ferran Marcet        <fmarcet@2byte.es>
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
 *      \file       htdocs/reception/list.php
 *      \ingroup    reception
 *      \brief      Page to list all receptions
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->loadLangs(array("sendings", "receptions", "deliveries", 'companies', 'bills', 'orders'));

$socid = GETPOST('socid', 'int');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

// Security check
$receptionid = GETPOST('id', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'reception', $receptionid, '');

$diroutputmassaction = $conf->reception->dir_output.'/temp/massgeneration/'.$user->id;

$search_ref_rcp = GETPOST("search_ref_rcp");
$search_ref_liv = GETPOST('search_ref_liv');
$search_ref_supplier = GETPOST('search_ref_supplier');
$search_company = GETPOST("search_company");
$search_town = GETPOST('search_town', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_state = GETPOST("search_state");
$search_country = GETPOST("search_country", 'int');
$search_type_thirdparty = GETPOST("search_type_thirdparty", 'int');
$search_billed = GETPOST("search_billed", 'int');
$sall = GETPOST('sall', 'alphanohtml');
$optioncss = GETPOST('optioncss', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (!$sortfield) $sortfield = "e.ref";
if (!$sortorder) $sortorder = "DESC";
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$contextpage = 'receptionlist';

$search_status = GETPOST('search_status');

$object = new Reception($db);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('receptionlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = (array) $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'e.ref'=>"Ref",
	's.nom'=>"ThirdParty",
	'e.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["e.note_private"] = "NotePrivate";

$checkedtypetiers = 0;
$arrayfields = array(
	'e.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'e.ref_supplier'=>array('label'=>$langs->trans("RefSupplier"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
	's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
	's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
	'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
	'e.date_delivery'=>array('label'=>$langs->trans("DateDeliveryPlanned"), 'checked'=>1),
	'e.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'e.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'e.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
	'e.billed'=>array('label'=>$langs->trans("Billed"), 'checked'=>1, 'position'=>1000, 'enabled'=>(!empty($conf->global->WORKFLOW_BILL_ON_RECEPTION)))
);

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


/*
 * Actions
 */

if (GETPOST('cancel')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction') && $massaction != 'confirm_createbills') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_ref_supplier = '';
	$search_ref_rcp = '';
	$search_ref_liv = '';
	$search_company = '';
	$search_town = '';
	$search_zip = "";
	$search_state = "";
	$search_country = '';
	$search_type_thirdparty = '';
	$search_billed = '';
	$search_status = '';
	$search_array_options = array();
}

if (empty($reshook))
{
	if ($massaction == 'confirm_createbills') {
		$receptions = GETPOST('toselect', 'array');
		$createbills_onebythird = GETPOST('createbills_onebythird', 'int');
		$validate_invoices = GETPOST('validate_invoices', 'int');

		$TFact = array();
		$TFactThird = array();

		$nb_bills_created = 0;

		$db->begin();
		$errors = array();
		foreach ($receptions as $id_reception)
		{
			$rcp = new Reception($db);
			 // On ne facture que les réceptions validées
			if ($rcp->fetch($id_reception) <= 0 || $rcp->statut != 1) {
				$errors[] = $langs->trans('StatusOfRefMustBe', $rcp->ref, $langs->transnoentities("StatusSupplierOrderValidatedShort"));
				$error++;
				continue;
			}

			$object = new FactureFournisseur($db);
			if (!empty($createbills_onebythird) && !empty($TFactThird[$rcp->socid])) {
				$object = $TFactThird[$rcp->socid]; // If option "one bill per third" is set, we use already created reception.
				if (empty($object->rowid) && $object->id != null)$object->rowid = $object->id;
				if (!empty($object->rowid))$object->fetchObjectLinked();
				$rcp->fetchObjectLinked();

				if (count($rcp->linkedObjectsIds['reception']) > 0)
				{
					foreach ($rcp->linkedObjectsIds['reception'] as $key => $value)
					{
						if (empty($object->linkedObjectsIds['reception']) || !in_array($value, $object->linkedObjectsIds['reception']))//Dont try to link if already linked
							$object->add_object_linked('reception', $value); // add supplier order linked object
					}
				}
			} else {
				$object->socid = $rcp->socid;
				$object->type = FactureFournisseur::TYPE_STANDARD;
				$object->cond_reglement_id	= $rcp->thirdparty->cond_reglement_supplier_id;
				$object->mode_reglement_id	= $rcp->thirdparty->mode_reglement_supplier_id;
				$object->fk_account = !empty($rcp->thirdparty->fk_account) ? $rcp->thirdparty->fk_account : 0;
				$object->remise_percent 	= !empty($rcp->thirdparty->remise_percent) ? $rcp->thirdparty->remise_percent : 0;
				$object->remise_absolue 	= !empty($rcp->thirdparty->remise_absolue) ? $rcp->thirdparty->remise_absolue : 0;

				$object->fk_project			= $rcp->fk_project;
				$object->ref_supplier = $rcp->ref_supplier;

				$datefacture = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
				if (empty($datefacture))
				{
					$datefacture = dol_mktime(date("h"), date("M"), 0, date("m"), date("d"), date("Y"));
				}

				$object->date = $datefacture;
				$object->origin    = 'reception';
				$object->origin_id = $id_reception;

				$rcp->fetchObjectLinked();
				if (count($rcp->linkedObjectsIds['reception']) > 0)
				{
					foreach ($rcp->linkedObjectsIds['reception'] as $key => $value)
					{
						$object->linked_objects['reception'] = $value;
					}
				}

				$res = $object->create($user);
				//var_dump($object->error);exit;
				if ($res > 0) {
					$nb_bills_created++;
					$object->id = $res;
				} else {
					$errors[] = $rcp->ref.' : '.$langs->trans($object->error);
					$error++;
				}
			}

			if ($object->id > 0)
			{
				if (!empty($createbills_onebythird) && !empty($TFactThird[$rcp->socid])) { //cause function create already add object linked for facturefournisseur
					$res = $object->add_object_linked($object->origin, $id_reception);

					if ($res == 0)
					{
						$errors[] = $object->error;
						$error++;
					}
				}

				if (!$error)
				{
					$lines = $rcp->lines;
					if (empty($lines) && method_exists($rcp, 'fetch_lines'))
					{
						$rcp->fetch_lines();
						$lines = $rcp->lines;
					}

					$fk_parent_line = 0;
					$num = count($lines);

					for ($i = 0; $i < $num; $i++)
					{
						$desc = ($lines[$i]->desc ? $lines[$i]->desc : $lines[$i]->libelle);
						if ($lines[$i]->subprice < 0)
						{
							// Negative line, we create a discount line
							$discount = new DiscountAbsolute($db);
							$discount->fk_soc = $object->socid;
							$discount->amount_ht = abs($lines[$i]->total_ht);
							$discount->amount_tva = abs($lines[$i]->total_tva);
							$discount->amount_ttc = abs($lines[$i]->total_ttc);
							$discount->tva_tx = $lines[$i]->tva_tx;
							$discount->fk_user = $user->id;
							$discount->description = $desc;
							$discountid = $discount->create($user);
							if ($discountid > 0)
							{
								$result = $object->insert_discount($discountid);
								//$result=$discount->link_to_invoice($lineid,$id);
							} else {
								setEventMessages($discount->error, $discount->errors, 'errors');
								$error++;
								break;
							}
						} else {
							// Positive line
							$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);
							// Date start
							$date_start = false;
							if ($lines[$i]->date_debut_prevue) $date_start = $lines[$i]->date_debut_prevue;
							if ($lines[$i]->date_debut_reel) $date_start = $lines[$i]->date_debut_reel;
							if ($lines[$i]->date_start) $date_start = $lines[$i]->date_start;
							//Date end
							$date_end = false;
							if ($lines[$i]->date_fin_prevue) $date_end = $lines[$i]->date_fin_prevue;
							if ($lines[$i]->date_fin_reel) $date_end = $lines[$i]->date_fin_reel;
							if ($lines[$i]->date_end) $date_end = $lines[$i]->date_end;
							// Reset fk_parent_line for no child products and special product
							if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9)
							{
								$fk_parent_line = 0;
							}
							$result = $object->addline(
								$desc,
								$lines[$i]->subprice,
								$lines[$i]->tva_tx,
								$lines[$i]->localtax1_tx,
								$lines[$i]->localtax2_tx,
								$lines[$i]->qty,
								$lines[$i]->fk_product,
								$lines[$i]->remise_percent,
								$date_start,
								$date_end,
								0,
								$lines[$i]->info_bits,
								'HT',
								$product_type,
								$i,
								false,
								0,
								null,
								$lines[$i]->rowid,
								0,
								$lines[$i]->ref_supplier
							);

							$rcp->add_object_linked('facture_fourn_det', $result);

							if ($result > 0)
							{
								$lineid = $result;
							} else {
								$lineid = 0;
								$error++;
								break;
							}
							// Defined the new fk_parent_line
							if ($result > 0 && $lines[$i]->product_type == 9)
							{
								$fk_parent_line = $result;
							}
						}
					}
				}
			}

			//$rcp->classifyBilled($user);        // Disabled. This behavior must be set or not using the workflow module.

			if (!empty($createbills_onebythird) && empty($TFactThird[$rcp->socid])) $TFactThird[$rcp->socid] = $object;
			else $TFact[$object->id] = $object;
		}

		// Build doc with all invoices
		$TAllFact = empty($createbills_onebythird) ? $TFact : $TFactThird;
		$toselect = array();

		if (!$error && $validate_invoices)
		{
			$massaction = $action = 'builddoc';
			foreach ($TAllFact as &$object)
			{
				$result = $object->validate($user);
				if ($result <= 0)
				{
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
					break;
				}

				$id = $object->id; // For builddoc action

				// Fac builddoc
				$donotredirect = 1;
				$upload_dir = $conf->fournisseur->facture->dir_output;
				$permissiontoadd = $user->rights->fournisseur->facture->creer;
				include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
			}

			$massaction = $action = 'confirm_createbills';
		}

		if (!$error)
		{
			$db->commit();
			setEventMessage($langs->trans('BillCreated', $nb_bills_created));
		} else {
			$db->rollback();
			$action = 'create';
			$_GET["origin"] = $_POST["origin"];
			$_GET["originid"] = $_POST["originid"];
			setEventMessages($object->error, $errors, 'errors');
			$error++;
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$companystatic = new Societe($db);
$reception = new Reception($db);
$formcompany = new FormCompany($db);
$formfile = new FormFile($db);


$helpurl = 'EN:Module_Receptions|FR:Module_Receptions|ES:M&oacute;dulo_Receptiones';
llxHeader('', $langs->trans('ListOfReceptions'), $helpurl);

$sql = "SELECT e.rowid, e.ref, e.ref_supplier, e.date_reception as date_reception, e.date_delivery as delivery_date, l.date_delivery as date_reception2, e.fk_statut, e.billed,";
$sql .= ' s.rowid as socid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client, ';
$sql .= " typent.code as typent_code,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
$sql .= ' e.date_creation as date_creation, e.tms as date_update';
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."reception as e";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (e.rowid = ef.fk_object)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee ON e.rowid = ee.fk_source AND ee.sourcetype = 'reception' AND ee.targettype = 'delivery'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."delivery as l ON l.rowid = ee.fk_target";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE e.entity IN (".getEntity('reception').")";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql .= " AND e.fk_soc = sc.fk_soc";
	$sql .= " AND sc.fk_user = ".$user->id;
}
if ($socid)
{
	$sql .= " AND e.fk_soc = ".$socid;
}
if ($search_status <> '' && $search_status >= 0) {
	$sql .= " AND e.fk_statut = ".$search_status;
}
if ($search_billed != '' && $search_billed >= 0) $sql .= ' AND e.billed = '.$search_billed;
if ($search_town)  $sql .= natural_search('s.town', $search_town);
if ($search_zip)   $sql .= natural_search("s.zip", $search_zip);
if ($search_state) $sql .= natural_search("state.nom", $search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_ref_rcp) $sql .= natural_search('e.ref', $search_ref_rcp);
if ($search_ref_liv) $sql .= natural_search('l.ref', $search_ref_liv);
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_ref_supplier) $sql .= natural_search('e.ref_supplier', $search_ref_supplier);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);

// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
	$crit = $val;
	$tmpkey = preg_replace('/search_options_/', '', $key);
	$typ = $extrafields->attributes[$object->table_element]['type'][$tmpkey];
	$mode = 0;
	if (in_array($typ, array('int', 'double', 'real'))) $mode = 1; // Search on a numeric
	if (in_array($typ, array('sellist')) && $crit != '0' && $crit != '-1') $mode = 2; // Search on a foreign key int
	if ($crit != '' && (!in_array($typ, array('select', 'sellist')) || $crit != '0'))
	{
		$sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
	}
}
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

//print $sql;
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$reception = new Reception($db);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall) $param .= "&amp;sall=".urlencode($sall);
	if ($search_ref_rcp) $param .= "&amp;search_ref_rcp=".urlencode($search_ref_rcp);
	if ($search_ref_liv) $param .= "&amp;search_ref_liv=".urlencode($search_ref_liv);
	if ($search_company) $param .= "&amp;search_company=".urlencode($search_company);
	if ($optioncss != '') $param .= '&amp;optioncss='.urlencode($optioncss);
	if ($search_billed != '' && $search_billed >= 0) $param .= "&amp;search_billed=".urlencode($search_billed);
	if ($search_town)  $param .= "&amp;search_town=".urlencode($search_town);
	if ($search_zip)  $param .= "&amp;search_zip=".urlencode($search_zip);
	if ($search_state) $param .= "&amp;search_state=".urlencode($search_state);
	if ($search_status != '') $param .= "&amp;search_status=".urlencode($search_status);
	if ($search_country) $param .= "&amp;search_country=".urlencode($search_country);
	if ($search_type_thirdparty) $param .= "&amp;search_type_thirdparty=".urlencode($search_type_thirdparty);
	if ($search_ref_supplier) $param .= "&amp;search_ref_supplier=".urlencode($search_ref_supplier);
	// Add $param from extra fields
	foreach ($search_array_options as $key => $val)
	{
		$crit = $val;
		$tmpkey = preg_replace('/search_options_/', '', $key);
		if ($val != '') $param .= '&search_options_'.$tmpkey.'='.urlencode($val);
	}


	$arrayofmassactions = array(
	// 'presend'=>$langs->trans("SendByMail"),
	);

	if ($user->rights->fournisseur->facture->creer)$arrayofmassactions['createbills'] = $langs->trans("CreateInvoiceForThisSupplier");
	if ($massaction == 'createbills') $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
	//$massactionbutton=$form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail"), 'builddoc'=>$langs->trans("PDFMerge")));

	$i = 0;
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	print_barre_liste($langs->trans('ListOfReceptions'), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'dollyrevert', 0, '', '', $limit, 0, 0, 1);


	if ($massaction == 'createbills')
	{
		//var_dump($_REQUEST);
		print '<input type="hidden" name="massaction" value="confirm_createbills">';

		print '<table class="noborder" width="100%" >';
		print '<tr>';
		print '<td class="titlefieldmiddle">';
		print $langs->trans('DateInvoice');
		print '</td>';
		print '<td>';
		print $form->selectDate('', '', '', '', '', '', 1, 1);
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td>';
		print $langs->trans('CreateOneBillByThird');
		print '</td>';
		print '<td>';
		print $form->selectyesno('createbills_onebythird', '', 1);
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td>';
		print $langs->trans('ValidateInvoices');
		print '</td>';
		print '<td>';
		if (!empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_BILL))
		{
			print $form->selectyesno('validate_invoices', 0, 1, 1);
			print ' ('.$langs->trans("AutoValidationNotPossibleWhenStockIsDecreasedOnInvoiceValidation").')';
		} else {
			print $form->selectyesno('validate_invoices', 0, 1);
		}
		print '</td>';
		print '</tr>';
		print '</table>';

		print '<br>';
		print '<div class="center">';
		print '<input type="submit" class="button" id="createbills" name="createbills" value="'.$langs->trans('CreateInvoiceForThisSupplier').'">  ';
		print '<input type="submit" class="button button-cancel" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';
		print '<br>';
	}

	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print $langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall);
	}

	$moreforfilter = '';
	if (!empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		$parameters = array('type'=>$type);
		$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);


	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre_filter">';
	// Ref
	if (!empty($arrayfields['e.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref_rcp" value="'.$search_ref_rcp.'">';
		print '</td>';
	}
	// Ref customer
	if (!empty($arrayfields['e.ref_supplier']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref_supplier" value="'.$search_ref_supplier.'">';
		print '</td>';
	}
	// Thirdparty
	if (!empty($arrayfields['s.nom']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="8" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
		print '</td>';
	}
	// Town
	if (!empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';
	// Zip
	if (!empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_zip" value="'.$search_zip.'"></td>';
	// State
	if (!empty($arrayfields['state.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
		print '</td>';
	}
	// Country
	if (!empty($arrayfields['country.code_iso']['checked']))
	{
		print '<td class="liste_titre center">';
		print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
		print '</td>';
	}
	// Company type
	if (!empty($arrayfields['typent.code']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone center">';
		print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT));
		print '</td>';
	}
	// Date delivery planned
	if (!empty($arrayfields['e.date_delivery']['checked']))
	{
		print '<td class="liste_titre">&nbsp;</td>';
	}
	if (!empty($arrayfields['l.ref']['checked']))
	{
		// Delivery ref
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_ref_liv" value="'.$search_ref_liv.'"';
		print '</td>';
	}
	if (!empty($arrayfields['l.date_delivery']['checked']))
	{
		// Date received
		print '<td class="liste_titre">&nbsp;</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['e.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['e.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Status
	if (!empty($arrayfields['e.fk_statut']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone right">';
		print $form->selectarray('search_status', array('0'=>$langs->trans('StatusReceptionDraftShort'), '1'=>$langs->trans('StatusReceptionValidatedShort'), '2'=>$langs->trans('StatusReceptionProcessedShort')), $search_status, 1);
		print '</td>';
	}
	// Status billed
	if (!empty($arrayfields['e.billed']['checked']))
	{
		print '<td class="liste_titre maxwidthonsmartphone center">';
		print $form->selectyesno('search_billed', $search_billed, 1, 0, 1);
		print '</td>';
	}
	// Action column
	print '<td class="liste_titre middle">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['e.ref']['checked']))            print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], "e.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['e.ref_supplier']['checked']))   print_liste_field_titre($arrayfields['e.ref_supplier']['label'], $_SERVER["PHP_SELF"], "e.ref_supplier", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder, 'left ');
	if (!empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'], $_SERVER["PHP_SELF"], 's.town', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'], $_SERVER["PHP_SELF"], 's.zip', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'], $_SERVER["PHP_SELF"], "typent.code", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['e.date_delivery']['checked']))  print_liste_field_titre($arrayfields['e.date_delivery']['label'], $_SERVER["PHP_SELF"], "e.date_delivery", "", $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['l.ref']['checked']))            print_liste_field_titre($arrayfields['l.ref']['label'], $_SERVER["PHP_SELF"], "l.ref", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['l.date_delivery']['checked']))  print_liste_field_titre($arrayfields['l.date_delivery']['label'], $_SERVER["PHP_SELF"], "l.date_delivery", "", $param, '', $sortfield, $sortorder, 'center ');
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['e.datec']['checked']))  print_liste_field_titre($arrayfields['e.datec']['label'], $_SERVER["PHP_SELF"], "e.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['e.tms']['checked']))    print_liste_field_titre($arrayfields['e.tms']['label'], $_SERVER["PHP_SELF"], "e.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	if (!empty($arrayfields['e.fk_statut']['checked'])) print_liste_field_titre($arrayfields['e.fk_statut']['label'], $_SERVER["PHP_SELF"], "e.fk_statut", "", $param, '', $sortfield, $sortorder, 'right ');
	if (!empty($arrayfields['e.billed']['checked'])) print_liste_field_titre($arrayfields['e.billed']['label'], $_SERVER["PHP_SELF"], "e.billed", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$reception->id = $obj->rowid;
		$reception->ref = $obj->ref;

		$companystatic->id = $obj->socid;
		$companystatic->ref = $obj->name;
		$companystatic->name = $obj->name;


		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['e.ref']['checked']))
		{
			print "<td>";
			print $reception->getNomUrl(1);
			$filename = dol_sanitizeFileName($reception->ref);
			$filedir = $conf->reception->dir_output.'/'.dol_sanitizeFileName($reception->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$reception->id;
			print $formfile->getDocumentsLink($reception->element, $filename, $filedir);
			print "</td>\n";

			if (!$i) $totalarray['nbfield']++;
		}

		// Ref customer
		if (!empty($arrayfields['e.ref_supplier']['checked']))
		{
			print "<td>";
			print $obj->ref_supplier;
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Third party
		if (!empty($arrayfields['s.nom']['checked']))
		{
			print '<td>';
			print $companystatic->getNomUrl(1);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Town
		if (!empty($arrayfields['s.town']['checked']))
		{
			print '<td class="nocellnopadd">';
			print $obj->town;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Zip
		if (!empty($arrayfields['s.zip']['checked']))
		{
			print '<td class="nocellnopadd">';
			print $obj->zip;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// State
		if (!empty($arrayfields['state.nom']['checked']))
		{
			print "<td>".$obj->state_name."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}
		// Country
		if (!empty($arrayfields['country.code_iso']['checked']))
		{
			print '<td class="center">';
			$tmparray = getCountry($obj->fk_pays, 'all');
			print $tmparray['label'];
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Type ent
		if (!empty($arrayfields['typent.code']['checked']))
		{
			print '<td class="center">';
			if (count($typenArray) == 0) $typenArray = $formcompany->typent_array(1);
			print $typenArray[$obj->typent_code];
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Date delivery planed
		if (!empty($arrayfields['e.date_delivery']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->delivery_date), "day");
			/*$now = time();
    		if ( ($now - $db->jdate($obj->date_reception)) > $conf->warnings->lim && $obj->statutid == 1 )
    		{
    		}*/
			print "</td>\n";
		}

		if (!empty($arrayfields['l.ref']['checked']) || !empty($arrayfields['l.date_delivery']['checked']))
		{
			$reception->fetchObjectLinked($reception->id, $reception->element);
			$receiving = '';
			if (count($reception->linkedObjects['delivery']) > 0) $receiving = reset($reception->linkedObjects['delivery']);

			if (!empty($arrayfields['l.ref']['checked']))
			{
				// Ref
				print '<td>';
				print !empty($receiving) ? $receiving->getNomUrl($db) : '';
				print '</td>';
			}

			if (!empty($arrayfields['l.date_delivery']['checked']))
			{
				// Date received
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_reception), "day");
				print '</td>'."\n";
			}
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['e.datec']['checked']))
		{
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date modification
		if (!empty($arrayfields['e.tms']['checked']))
		{
			print '<td class="center nowrap">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Status
		if (!empty($arrayfields['e.fk_statut']['checked']))
		{
			print '<td class="right nowrap">'.$reception->LibStatut($obj->fk_statut, 5).'</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Billed
		if (!empty($arrayfields['e.billed']['checked']))
		{
			print '<td class="center">'.yn($obj->billed).'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Action column
		print '<td class="center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print "</tr>\n";

		$i++;
	}

	print "</table>";
	print "</div>";
	print '</form>';
	$db->free($resql);
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();
