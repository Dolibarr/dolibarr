<?php
/* Copyright (C) 2019-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       mo_production.php
 *		\ingroup    mrp
 *		\brief      Page to make production on a MO
 */

// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
dol_include_once('/mrp/class/mo.class.php');
dol_include_once('/mrp/lib/mrp_mo.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("mrp", "stocks", "other", "productbatch"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'mocard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

$collapse = GETPOST('collapse', 'aZ09comma');

// Initialize technical objects
$object = new Mo($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mrp->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('mocard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'mrp', $object->id, 'mrp_mo', '', 'fk_soc', 'rowid', $isdraft);

$permissionnote = $user->rights->mrp->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->mrp->write; // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->rights->mrp->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->mrp->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$upload_dir = $conf->mrp->multidir_output[isset($object->entity) ? $object->entity : 1];

$permissiontoproduce = $permissiontoadd;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error = 0;

	$backurlforlist = dol_buildpath('/mrp/mo_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		//var_dump($backurlforlist);exit;
		if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
		else $backtopage = DOL_URL_ROOT.'/mrp/mo_production.php?id='.($id > 0 ? $id : '__ID__');
	}
	$triggermodname = 'MRP_MO_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, delete or clone
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'MO_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MO_TO';
	$trackid = 'mo'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

	if ($action == 'set_thirdparty' && $permissiontoadd)
	{
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'MO_MODIFY');
	}
	if ($action == 'classin' && $permissiontoadd)
	{
		$object->setProject(GETPOST('projectid', 'int'));
	}

	if ($action == 'confirm_reopen') {
		$result = $object->setStatut($object::STATUS_INPROGRESS, 0, '', 'MRP_REOPEN');
	}

	if ($action == 'confirm_addconsumeline' && GETPOST('addconsumelinebutton')) {
		$moline = new MoLine($db);

		// Line to produce
		$moline->fk_mo = $object->id;
		$moline->qty = GETPOST('qtytoadd', 'int'); ;
		$moline->fk_product = GETPOST('productidtoadd', 'int');
		$moline->role = 'toconsume';
		$moline->position = 0;

		$resultline = $moline->create($user, false); // Never use triggers here
		if ($resultline <= 0) {
			$error++;
			setEventMessages($moline->error, $molines->errors, 'errors');
		}

		$action = '';
	}

	if (in_array($action, array('confirm_consumeorproduce', 'confirm_consumeandproduceall'))) {
		$stockmove = new MouvementStock($db);

		$labelmovement = GETPOST('inventorylabel', 'alphanohtml');
		$codemovement  = GETPOST('inventorycode', 'alphanohtml');

		$db->begin();

		// Process line to consume
		foreach ($object->lines as $line) {
			if ($line->role == 'toconsume') {
				$tmpproduct = new Product($db);
				$tmpproduct->fetch($line->fk_product);

				$i = 1;
				while (GETPOSTISSET('qty-'.$line->id.'-'.$i)) {
					$qtytoprocess = price2num(GETPOST('qty-'.$line->id.'-'.$i));

					if ($qtytoprocess != 0) {
						// Check warehouse is set if we should have to
						if (GETPOSTISSET('idwarehouse-'.$line->id.'-'.$i)) {	// If there is a warehouse to set
							if (!(GETPOST('idwarehouse-'.$line->id.'-'.$i) > 0)) {	// If there is no warehouse set.
								$langs->load("errors");
								setEventMessages($langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Warehouse"), $tmpproduct->ref), null, 'errors');
								$error++;
							}
							if ($tmpproduct->status_batch && (!GETPOST('batch-'.$line->id.'-'.$i))) {
								$langs->load("errors");
								setEventMessages($langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Batch"), $tmpproduct->ref), null, 'errors');
								$error++;
							}
						}

						$idstockmove = 0;
						if (!$error && GETPOST('idwarehouse-'.$line->id.'-'.$i) > 0) {
							// Record stock movement
							$id_product_batch = 0;
							$stockmove->origin = $object;
							$idstockmove = $stockmove->livraison($user, $line->fk_product, GETPOST('idwarehouse-'.$line->id.'-'.$i), $qtytoprocess, 0, $labelmovement, dol_now(), '', '', GETPOST('batch-'.$line->id.'-'.$i), $id_product_batch, $codemovement);
							if ($idstockmove < 0) {
								$error++;
								setEventMessages($stockmove->error, $stockmove->errors, 'errors');
							}
						}

						if (!$error) {
							$pos = 0;
							// Record consumption
							$moline = new MoLine($db);
							$moline->fk_mo = $object->id;
							$moline->position = $pos;
							$moline->fk_product = $line->fk_product;
							$moline->fk_warehouse = GETPOST('idwarehouse-'.$line->id.'-'.$i);
							$moline->qty = $qtytoprocess;
							$moline->batch = GETPOST('batch-'.$line->id.'-'.$i);
							$moline->role = 'consumed';
							$moline->fk_mrp_production = $line->id;
							$moline->fk_stock_movement = $idstockmove;
							$moline->fk_user_creat = $user->id;

							$resultmoline = $moline->create($user);
							if ($resultmoline <= 0) {
								$error++;
								setEventMessages($moline->error, $moline->errors, 'errors');
							}

							$pos++;
						}
					}

					$i++;
				}
			}
		}

		// Process line to produce
		foreach ($object->lines as $line) {
			if ($line->role == 'toproduce') {
				$tmpproduct = new Product($db);
				$tmpproduct->fetch($line->fk_product);

				$i = 1;
				while (GETPOSTISSET('qtytoproduce-'.$line->id.'-'.$i)) {
					$qtytoprocess = price2num(GETPOST('qtytoproduce-'.$line->id.'-'.$i));

					if ($qtytoprocess != 0) {
						// Check warehouse is set if we should have to
						if (GETPOSTISSET('idwarehousetoproduce-'.$line->id.'-'.$i)) {	// If there is a warehouse to set
							if (!(GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i) > 0)) {	// If there is no warehouse set.
								$langs->load("errors");
								setEventMessages($langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Warehouse"), $tmpproduct->ref), null, 'errors');
								$error++;
							}
							if (!empty($conf->productbatch->enabled) && $tmpproduct->status_batch && (!GETPOST('batchtoproduce-'.$line->id.'-'.$i))) {
								$langs->load("errors");
								setEventMessages($langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Batch"), $tmpproduct->ref), null, 'errors');
								$error++;
							}
						}

						$idstockmove = 0;
						if (!$error && GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i) > 0) {
							// Record stock movement
							$id_product_batch = 0;
							$stockmove->origin = $object;
							$idstockmove = $stockmove->reception($user, $line->fk_product, GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i), $qtytoprocess, 0, $labelmovement, '', '', GETPOST('batchtoproduce-'.$line->id.'-'.$i), dol_now(), $id_product_batch, $codemovement);
							if ($idstockmove < 0) {
								$error++;
								setEventMessages($stockmove->error, $stockmove->errors, 'errors');
							}
						}

						if (!$error) {
							$pos = 0;
							// Record production
							$moline = new MoLine($db);
							$moline->fk_mo = $object->id;
							$moline->position = $pos;
							$moline->fk_product = $line->fk_product;
							$moline->fk_warehouse = GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i);
							$moline->qty = $qtytoprocess;
							$moline->batch = GETPOST('batchtoproduce-'.$line->id.'-'.$i);
							$moline->role = 'produced';
							$moline->fk_mrp_production = $line->id;
							$moline->fk_stock_movement = $idstockmove;
							$moline->fk_user_creat = $user->id;

							$resultmoline = $moline->create($user);
							if ($resultmoline <= 0) {
								$error++;
								setEventMessages($moline->error, $moline->errors, 'errors');
							}

							$pos++;
						}
					}

					$i++;
				}
			}
		}

		if (!$error) {
			$consumptioncomplete = true;
			$productioncomplete = true;

			if (GETPOST('autoclose', 'int')) {
				foreach ($object->lines as $line) {
					if ($line->role == 'toconsume') {
						$arrayoflines = $object->fetchLinesLinked('consumed', $line->id);
						$alreadyconsumed = 0;
						foreach ($arrayoflines as $line2) {
							$alreadyconsumed += $line2['qty'];
						}

						if ($alreadyconsumed < $line->qty) {
							$consumptioncomplete = false;
						}
					}
					if ($line->role == 'toproduce') {
						$arrayoflines = $object->fetchLinesLinked('produced', $line->id);
						$alreadyproduced = 0;
						foreach ($arrayoflines as $line2) {
							$alreadyproduced += $line2['qty'];
						}

						if ($alreadyproduced < $line->qty) {
							$productioncomplete = false;
						}
					}
				}
			} else {
				$consumptioncomplete = false;
				$productioncomplete = false;
			}

			// Update status of MO
			dol_syslog("consumptioncomplete = ".$consumptioncomplete." productioncomplete = ".$productioncomplete);
			//var_dump("consumptioncomplete = ".$consumptioncomplete." productioncomplete = ".$productioncomplete);
			if ($consumptioncomplete && $productioncomplete) {
				$result = $object->setStatut($object::STATUS_PRODUCED, 0, '', 'MRP_MO_PRODUCED');
			} else {
				$result = $object->setStatut($object::STATUS_INPROGRESS, 0, '', 'MRP_MO_PRODUCED');
			}
			if ($result <= 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if ($error) {
			$action = str_replace('confirm_', '', $action);
			$db->rollback();
		} else {
			$db->commit();

			// Redirect to avoid to action done a second time if we make a back from browser
			header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
	}

	// Action close produced
	if ($action == 'confirm_produced' && $confirm == 'yes' && $permissiontoadd)
	{
		$result = $object->setStatut($object::STATUS_PRODUCED, 0, '', 'MRP_MO_PRODUCED');
		if ($result >= 0)
		{
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, 0, 0, 0);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}



/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);
$formproduct = new FormProduct($db);
$tmpwarehouse = new Entrepot($db);
$tmpbatch = new Productlot($db);

$help_url = 'EN:Module_Manufacturing_Orders|FR:Module_Ordres_de_Fabrication';
llxHeader('', $langs->trans('Mo'), $help_url, '', 0, 0, array('/mrp/js/lib_dispatch.js.php'));

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
	$res = $object->fetch_thirdparty();
	$res = $object->fetch_optionals();

	$head = moPrepareHead($object);

	print dol_get_fiche_head($head, 'production', $langs->trans("ManufacturingOrder"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteMo'), $langs->trans('ConfirmDeleteMo'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneMo', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of validation
	if ($action == 'validate')
	{
		// We check that object has a temporary ref
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV') {
			$object->fetch_product();
			$numref = $object->getNextNumRef($object->fk_product);
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateMo', $numref);
		/*if (! empty($conf->notification->enabled))
		 {
		 require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
		 $notify = new Notify($db);
		 $text .= '<br>';
		 $text .= $notify->confirmMessage('BOM_VALIDATE', $object->socid, $object);
		 }*/

		$formquestion = array();
		if (!empty($conf->mrp->enabled))
		{
			$langs->load("mrp");
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			$forcecombo = 0;
			if ($conf->browser->name == 'ie') $forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
			$formquestion = array(
				// 'text' => $langs->trans("ConfirmClone"),
				// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
				// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			);
		}

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validate'), $text, 'confirm_validate', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/mrp/mo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', null, null, '', 1);*/
	// Thirdparty
	$morehtmlref .= $langs->trans('ThirdParty').' : '.(is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	// Project
	if (!empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($permissiontoadd)
		{
			if ($action != 'classify')
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects($object->fk_soc, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_soc, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl();
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak = 'fk_warehouse';
	unset($object->fields['fk_project']);
	unset($object->fields['fk_soc']);
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	if (!in_array($action, array('consumeorproduce', 'consumeandproduceall')))
	{
		print '<div class="tabsAction">';

		$parameters = array();
		// Note that $action and $object may be modified by hook
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
		if (empty($reshook)) {
			// Validate
			if ($object->status == $object::STATUS_DRAFT)
			{
				if ($permissiontoadd)
				{
					if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0))
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate">'.$langs->trans("Validate").'</a>';
					} else {
						$langs->load("errors");
						print '<a class="butActionRefused" href="" title="'.$langs->trans("ErrorAddAtLeastOneLineFirst").'">'.$langs->trans("Validate").'</a>';
					}
				}
			}

			// Consume or produce
			if ($object->status == Mo::STATUS_VALIDATED || $object->status == Mo::STATUS_INPROGRESS) {
				if ($permissiontoproduce) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=consumeorproduce">'.$langs->trans('ConsumeOrProduce').'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('ConsumeOrProduce').'</a>';
				}
			} elseif ($object->status == Mo::STATUS_DRAFT) {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ValidateBefore").'">'.$langs->trans('ConsumeOrProduce').'</a>';
			}

			// ConsumeAndProduceAll
			if ($object->status == Mo::STATUS_VALIDATED || $object->status == Mo::STATUS_INPROGRESS) {
				if ($permissiontoproduce) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=consumeandproduceall">'.$langs->trans('ConsumeAndProduceAll').'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('ConsumeAndProduceAll').'</a>';
				}
			} elseif ($object->status == Mo::STATUS_DRAFT) {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ValidateBefore").'">'.$langs->trans('ConsumeAndProduceAll').'</a>';
			}

			// Cancel - Reopen
			if ($permissiontoadd)
			{
				if ($object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_INPROGRESS)
				{
					$arrayproduced = $object->fetchLinesLinked('produced', 0);
					$nbProduced = 0;
					foreach ($arrayproduced as $lineproduced) {
						$nbProduced += $lineproduced['qty'];
					}
					if ($nbProduced > 0) {	// If production has started, we can close it
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_produced&confirm=yes">'.$langs->trans("Close").'</a>'."\n";
					} else {
						print '<a class="butActionRefused" href="#" title="'.$langs->trans("GoOnTabProductionToProduceFirst", $langs->transnoentitiesnoconv("Production")).'">'.$langs->trans("Close").'</a>'."\n";
					}

					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_close&confirm=yes">'.$langs->trans("Cancel").'</a>'."\n";
				}

				if ($object->status == $object::STATUS_CANCELED)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_reopen&confirm=yes">'.$langs->trans("Re-Open").'</a>'."\n";
				}

				if ($object->status == $object::STATUS_PRODUCED) {
					if ($permissiontoproduce) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_reopen">'.$langs->trans('ReOpen').'</a>';
					} else {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('ReOpen').'</a>';
					}
				}
			}
		}

		print '</div>';
	}

	if (in_array($action, array('consumeorproduce', 'consumeandproduceall', 'addconsumeline')))
	{
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="confirm_'.$action.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		// Note: closing form is add end of page

		if (in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
			$defaultstockmovementlabel = GETPOST('inventorylabel', 'alphanohtml') ? GETPOST('inventorylabel', 'alphanohtml') : $langs->trans("ProductionForRef", $object->ref);
			//$defaultstockmovementcode = GETPOST('inventorycode', 'alphanohtml') ? GETPOST('inventorycode', 'alphanohtml') : $object->ref.'_'.dol_print_date(dol_now(), 'dayhourlog');
			$defaultstockmovementcode = GETPOST('inventorycode', 'alphanohtml') ? GETPOST('inventorycode', 'alphanohtml') : $langs->trans("ProductionForRef", $object->ref);

			print '<div class="center'.(in_array($action, array('consumeorproduce', 'consumeandproduceall')) ? ' formconsumeproduce' : '').'">';
			print '<span class="opacitymedium hideonsmartphone">'.$langs->trans("ConfirmProductionDesc", $langs->transnoentitiesnoconv("Confirm")).'<br></span>';
			print '<span class="fieldrequired">'.$langs->trans("InventoryCode").':</span> <input type="text" class="maxwidth200" name="inventorycode" value="'.$defaultstockmovementcode.'"> &nbsp; ';
			print '<span class="clearbothonsmartphone"></span>';
			print $langs->trans("MovementLabel").': <input type="text" class="minwidth300" name="inventorylabel" value="'.$defaultstockmovementlabel.'"><br><br>';
			print '<input type="checkbox" id="autoclose" name="autoclose" value="1"'.(GETPOSTISSET('inventorylabel') ? (GETPOST('autoclose') ? ' checked="checked"' : '') : ' checked="checked"').'> <label for="autoclose">'.$langs->trans("AutoCloseMO").'</label><br>';
			print '<input class="button" type="submit" value="'.$langs->trans("Confirm").'" name="confirm">';
			print ' &nbsp; ';
			print '<input class="button button-cancel" type="submit" value="'.$langs->trans("Cancel").'" name="cancel">';
			print '<br><br>';
			print '</div>';

			print '<br>';
		}
	}


	/*
	 * Lines
	 */
	$collapse = 1;

	if (!empty($object->table_element_line))
	{
		// Show object lines
		$object->fetchLines();

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="clearboth"></div>';

		$newlinetext = '';
		if ($action != 'consumeorproduce' && $action != 'consumeandproduceall') {
			$newlinetext = '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addconsumeline">'.$langs->trans("AddNewConsumeLines").'</a>';
		}
		print load_fiche_titre($langs->trans('Consumption'), '', '', 0, '', '', $newlinetext);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder noshadow centpercent nobottom">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Product").'</td>';
		print '<td class="right">'.$langs->trans("Qty").'</td>';
		print '<td class="right">'.$langs->trans("QtyAlreadyConsumed").'</td>';
		print '<td>';
		if ($collapse || in_array($action, array('consumeorproduce', 'consumeandproduceall'))) print $langs->trans("Warehouse");
		print '</td>';
		if ($conf->productbatch->enabled) {
			print '<td>';
			if ($collapse || in_array($action, array('consumeorproduce', 'consumeandproduceall'))) print $langs->trans("Batch");
			print '</td>';
		}
		print '</tr>';

		if ($action == 'addconsumeline') {
			print '<!-- Add line to consume -->'."\n";
			print '<tr class="liste_titre">';
			print '<td>';
			print $form->select_produits('', 'productidtoadd', '', 0, 0, -1, 2, '', 0, array(), 0, '1', 0, 'maxwidth300');
			print '</td>';
			print '<td class="right"><input type="text" name="qtytoadd" value="1" class="width50 right"></td>';
			print '<td class="right"></td>';
			print '<td>';
			print '<input type="submit" class="button buttongen" name="addconsumelinebutton" value="'.$langs->trans("Add").'">';
			print '</td>';
			if ($conf->productbatch->enabled) {
				print '<td></td>';
			}
			print '</tr>';
		}

		// Lines to consume

		if (!empty($object->lines))
		{
			$nblinetoconsume = 0;
			foreach ($object->lines as $line) {
				if ($line->role == 'toconsume') {
					$nblinetoconsume++;
				}
			}

			$nblinetoconsumecursor = 0;
			foreach ($object->lines as $line) {
				if ($line->role == 'toconsume') {
					$nblinetoconsumecursor++;

					$tmpproduct = new Product($db);
					$tmpproduct->fetch($line->fk_product);

					$arrayoflines = $object->fetchLinesLinked('consumed', $line->id);
					$alreadyconsumed = 0;
					foreach ($arrayoflines as $line2) {
						$alreadyconsumed += $line2['qty'];
					}

					print '<tr>';
					print '<td>'.$tmpproduct->getNomUrl(1);
					print '<br><span class="opacitymedium small">'.$tmpproduct->label.'</span>';
					print '</td>';
					print '<td class="right nowraponall">';
					$help = '';
					if ($line->qty_frozen) $help .= ($help ? '<br>' : '').'<strong>'.$langs->trans("QuantityFrozen").'</strong>: '.yn(1).' ('.$langs->trans("QuantityConsumedInvariable").')';
					if ($line->disable_stock_change) $help .= ($help ? '<br>' : '').'<strong>'.$langs->trans("DisableStockChange").'</strong>: '.yn(1).' ('.(($tmpproduct->type == Product::TYPE_SERVICE && empty($conf->global->STOCK_SUPPORTS_SERVICES)) ? $langs->trans("NoStockChangeOnServices") : $langs->trans("DisableStockChangeHelp")).')';
					if ($help) {
						print $form->textwithpicto($line->qty, $help, -1);
					} else {
						print $line->qty;
					}
					print '</td>';
					print '<td class="right">';
					if ($alreadyconsumed) {
						print '<script>';
						print 'jQuery(document).ready(function() {
								jQuery("#expandtoproduce'.$line->id.'").click(function() {
									console.log("Expand mrp_production line '.$line->id.'");
									jQuery(".expanddetail'.$line->id.'").toggle();';
						if ($nblinetoconsume == $nblinetoconsumecursor) {	// If it is the last line
							print 'if (jQuery("#tablelines").hasClass("nobottom")) { jQuery("#tablelines").removeClass("nobottom"); } else { jQuery("#tablelines").addClass("nobottom"); }';
						}
						print '
								});
							});';
						print '</script>';
						if (empty($conf->use_javascript_ajax)) print '<a href="'.$_SERVER["PHP_SELF"].'?collapse='.$collapse.','.$line->id.'">';
						print img_picto($langs->trans("ShowDetails"), "chevron-down", 'id="expandtoproduce'.$line->id.'"');
						if (empty($conf->use_javascript_ajax)) print '</a>';
					} else {
						if ($nblinetoconsume == $nblinetoconsumecursor) {	// If it is the last line
							print '<script>jQuery("#tablelines").removeClass("nobottom");</script>';
						}
					}
					print ' '.$alreadyconsumed;
					print '</td>';
					print '<td>'; // Warehouse
					print '</td>';
					if ($conf->productbatch->enabled) {
						print '<td></td>'; // Lot
					}
					print '</tr>';

					// Show detailed of already consumed with js code to collapse
					foreach ($arrayoflines as $line2) {
						print '<tr class="expanddetail'.$line->id.' hideobject opacitylow">';
						print '<td>';
						print dol_print_date($line2['date'], 'dayhour');
						print '</td>';
						print '<td></td>';
						print '<td class="right">'.$line2['qty'].'</td>';
						print '<td class="tdoverflowmax150">';
						if ($line2['fk_warehouse'] > 0) {
							$result = $tmpwarehouse->fetch($line2['fk_warehouse']);
							if ($result > 0) print $tmpwarehouse->getNomUrl(1);
						}
						print '</td>';
						// Lot Batch
						print '<td>';
						if ($line2['batch'] != '') {
							$tmpbatch->fetch(0, $line2['fk_product'], $line2['batch']);
							print $tmpbatch->getNomUrl(1);
						}
						print '</td>';
						print '</tr>';
					}

					if (in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
						$i = 1;
						print '<!-- Enter line to consume -->'."\n";
						print '<tr>';
						print '<td><span class="opacitymedium">'.$langs->trans("ToConsume").'</span></td>';
						$preselected = (GETPOSTISSET('qty-'.$line->id.'-'.$i) ? GETPOST('qty-'.$line->id.'-'.$i) : max(0, $line->qty - $alreadyconsumed));
						if ($action == 'consumeorproduce' && !GETPOSTISSET('qty-'.$line->id.'-'.$i)) $preselected = 0;
						print '<td class="right"><input type="text" class="width50 right" name="qty-'.$line->id.'-'.$i.'" value="'.$preselected.'"></td>';
						print '<td></td>';
						print '<td>';
						if ($tmpproduct->type == Product::TYPE_PRODUCT || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
							if (empty($line->disable_stock_change)) {
								$preselected = (GETPOSTISSET('idwarehouse-'.$line->id.'-'.$i) ? GETPOST('idwarehouse-'.$line->id.'-'.$i) : ($tmpproduct->fk_default_warehouse > 0 ? $tmpproduct->fk_default_warehouse : 'ifone'));
								print $formproduct->selectWarehouses($preselected, 'idwarehouse-'.$line->id.'-'.$i, '', 1, 0, $line->fk_product, '', 1, 0, null, 'maxwidth300');
							} else {
								print '<span class="opacitymedium">'.$langs->trans("DisableStockChange").'</span>';
							}
						} else {
							print '<span class="opacitymedium">'.$langs->trans("NoStockChangeOnServices").'</span>';
						}
						// Lot / Batch
						print '</td>';
						if ($conf->productbatch->enabled) {
							print '<td>';
							if ($tmpproduct->status_batch) {
								$preselected = (GETPOSTISSET('batch-'.$line->id.'-'.$i) ? GETPOST('batch-'.$line->id.'-'.$i) : '');
								print '<input type="text" class="width50" name="batch-'.$line->id.'-'.$i.'" value="'.$preselected.'">';
							}
							print '</td>';
						}
						print '</tr>';
					}
				}
			}
		}

   		print '</table>';
		print '</div>';

		// Lines to produce

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="clearboth"></div>';

		print load_fiche_titre($langs->trans('Production'), '', '');

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelinestoproduce" class="noborder noshadow nobottom centpercent">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Product").'</td>';
		print '<td class="right">'.$langs->trans("Qty").'</td>';
		print '<td class="right">'.$langs->trans("QtyAlreadyProduced").'</td>';
		print '<td>';
		if ($collapse || in_array($action, array('consumeorproduce', 'consumeandproduceall'))) print $langs->trans("Warehouse");
		print '</td>';
		if ($conf->productbatch->enabled) {
			print '<td>';
			if ($collapse || in_array($action, array('consumeorproduce', 'consumeandproduceall'))) print $langs->trans("Batch");
			print '</td>';
			print '<td></td>';
		}
		print '</tr>';

		if (!empty($object->lines))
		{
			$nblinetoproduce = 0;
			foreach ($object->lines as $line) {
				if ($line->role == 'toproduce') {
					$nblinetoproduce++;
				}
			}

			$nblinetoproducecursor = 0;
			foreach ($object->lines as $line) {
				if ($line->role == 'toproduce') {
					$i = 1;

					$nblinetoproducecursor++;

					$tmpproduct = new Product($db);
					$tmpproduct->fetch($line->fk_product);

					$arrayoflines = $object->fetchLinesLinked('produced', $line->id);
					$alreadyproduced = 0;
					foreach ($arrayoflines as $line2) {
						$alreadyproduced += $line2['qty'];
					}

					$suffix = '_'.$line->id;
					print '<!-- Line to dispatch '.$suffix.' -->'."\n";
					// hidden fields for js function
					print '<input id="qty_ordered'.$suffix.'" type="hidden" value="'.$line->qty.'">';
					print '<input id="qty_dispatched'.$suffix.'" type="hidden" value="'.$alreadyproduced.'">';

					print '<tr>';
					print '<td>'.$tmpproduct->getNomUrl(1);
					print '<br><span class="opacitymedium small">'.$tmpproduct->label.'</span>';
					print '</td>';
					print '<td class="right">'.$line->qty.'</td>';
					print '<td class="right nowraponall">';
					if ($alreadyproduced) {
						print '<script>';
						print 'jQuery(document).ready(function() {
							jQuery("#expandtoproduce'.$line->id.'").click(function() {
								console.log("Expand mrp_production line '.$line->id.'");
								jQuery(".expanddetailtoproduce'.$line->id.'").toggle();';
						if ($nblinetoproduce == $nblinetoproducecursor) {
							print 'if (jQuery("#tablelinestoproduce").hasClass("nobottom")) { jQuery("#tablelinestoproduce").removeClass("nobottom"); } else { jQuery("#tablelinestoproduce").addClass("nobottom"); }';
						}
						print '
							});
						});';
						print '</script>';
						if (empty($conf->use_javascript_ajax)) print '<a href="'.$_SERVER["PHP_SELF"].'?collapse='.$collapse.','.$line->id.'">';
						print img_picto($langs->trans("ShowDetails"), "chevron-down", 'id="expandtoproduce'.$line->id.'"');
						if (empty($conf->use_javascript_ajax)) print '</a>';
					}
					print ' '.$alreadyproduced;
					print '</td>';
					print '<td>'; // Warehouse
					print '</td>';
					if ($conf->productbatch->enabled) {
						print '<td></td>'; // Lot
						print '<td></td>';
					}
					print '</tr>';

					// Show detailed of already consumed with js code to collapse
					foreach ($arrayoflines as $line2) {
						print '<tr class="expanddetailtoproduce'.$line->id.' hideobject opacitylow">';
						print '<td>';
						print dol_print_date($line2['date'], 'dayhour');
						print '</td>';
						print '<td></td>';
						print '<td class="right">'.$line2['qty'].'</td>';
						print '<td class="tdoverflowmax150">';
						if ($line2['fk_warehouse'] > 0) {
							$result = $tmpwarehouse->fetch($line2['fk_warehouse']);
							if ($result > 0) print $tmpwarehouse->getNomUrl(1);
						}
						print '</td>';
						if ($conf->productbatch->enabled) {
							print '<td>';
							if ($line2['batch'] != '') {
								$tmpbatch->fetch(0, $line2['fk_product'], $line2['batch']);
								print $tmpbatch->getNomUrl(1);
							}
							print '</td>';
							print '<td></td>';
						}
						print '</tr>';
					}

					if (in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
						print '<!-- Enter line to produce -->'."\n";
						print '<tr name="batch_'.$line->id.'_'.$i.'">';
						print '<td><span class="opacitymedium">'.$langs->trans("ToProduce").'</span></td>';
						$preselected = (GETPOSTISSET('qtytoproduce-'.$line->id.'-'.$i) ? GETPOST('qtytoproduce-'.$line->id.'-'.$i) : max(0, $line->qty - $alreadyproduced));
						if ($action == 'consumeorproduce' && !GETPOSTISSET('qtytoproduce-'.$line->id.'-'.$i)) $preselected = 0;
						print '<td class="right"><input type="text" class="width50 right" id="qtytoproduce-'.$line->id.'-'.$i.'" name="qtytoproduce-'.$line->id.'-'.$i.'" value="'.$preselected.'"></td>';
						print '<td></td>';
						print '<td>';
						if ($tmpproduct->type == Product::TYPE_PRODUCT || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
							$preselected = (GETPOSTISSET('idwarehousetoproduce-'.$line->id.'-'.$i) ? GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i) : ($object->fk_warehouse > 0 ? $object->fk_warehouse : 'ifone'));
							print $formproduct->selectWarehouses($preselected, 'idwarehousetoproduce-'.$line->id.'-'.$i, '', 1, 0, $line->fk_product, '', 1, 0, null, 'maxwidth300 csswarehouse_'.$line->id.'_'.$i);
						} else {
							print '<span class="opacitymedium">'.$langs->trans("NoStockChangeOnServices").'</span>';
						}
						print '</td>';
						if ($conf->productbatch->enabled) {
							print '<td>';
							if ($tmpproduct->status_batch) {
								$preselected = (GETPOSTISSET('batchtoproduce-'.$line->id.'-'.$i) ? GETPOST('batchtoproduce-'.$line->id.'-'.$i) : '');
								print '<input type="text" class="width50" name="batchtoproduce-'.$line->id.'-'.$i.'" value="'.$preselected.'">';
							}
							print '</td>';
							print '<td>';
							if ($tmpproduct->status_batch) {
								$type = 'batch';
								print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine('.$line->id.', \''.$type.'\', \'qtymissing\')"');
							}
							print '</td>';
						}
						print '</tr>';
					}
				}
			}
		}

		print '</table>';
		print '</div>';

		print '</div>';
		print '</div>';
	}

	if (in_array($action, array('consumeorproduce', 'consumeandproduceall', 'addconsumeline')))
	{
		print "</form>\n";
	}
}

// End of page
llxFooter();
$db->close();
