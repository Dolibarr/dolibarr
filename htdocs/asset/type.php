<?php
/* Copyright (C) 2018      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 *  \file       htdocs/asset/type.php
 *  \ingroup    asset
 *  \brief      Asset's type setup
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/asset.lib.php';
require_once DOL_DOCUMENT_ROOT.'/asset/class/asset.class.php';
require_once DOL_DOCUMENT_ROOT.'/asset/class/asset_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

// Load translation files required by the page
$langs->load("assets");

$rowid  = GETPOST('rowid', 'int');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$type = GETPOST('type', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {  $sortorder = "DESC"; }
if (!$sortfield) {  $sortfield = "a.label"; }

$label = GETPOST("label", "alpha");
$accountancy_code_asset = GETPOST('accountancy_code_asset', 'string');
$accountancy_code_depreciation_asset = GETPOST('accountancy_code_depreciation_asset', 'string');
$accountancy_code_depreciation_expense = GETPOST('accountancy_code_depreciation_expense', 'string');
$comment = GETPOST('comment', 'string');

// Security check
$result = restrictedArea($user, 'asset', $rowid, 'asset_type');

$object = new AssetType($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$type = "";
	$sall = "";
}


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('assettypecard', 'globalcard'));


/*
 *	Actions
 */

if ($cancel) {
	$action = '';

	if (!empty($backtopage))
	{
		header("Location: ".$backtopage);
		exit;
	}
}

if ($action == 'add' && $user->rights->asset->write)
{
	$object->label = trim($label);
	$object->accountancy_code_asset = trim($accountancy_code_asset);
	$object->accountancy_code_depreciation_asset = trim($accountancy_code_depreciation_asset);
	$object->accountancy_code_depreciation_expense = trim($accountancy_code_depreciation_expense);
	$object->note = trim($comment);

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $object);
	if ($ret < 0) $error++;

	if (empty($object->label)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	}
	else {
		$sql = "SELECT label FROM ".MAIN_DB_PREFIX."asset_type WHERE label='".$db->escape($object->label)."'";
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorLabelAlreadyExists", $login), null, 'errors');
		}
	}

	if (!$error)
	{
		$id = $object->create($user);
		if ($id > 0)
		{
			header("Location: ".$_SERVER["PHP_SELF"]);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
		}
	}
	else
	{
		$action = 'create';
	}
}

if ($action == 'update' && $user->rights->asset->write)
{
	$object->fetch($rowid);

	$object->oldcopy = clone $object;

	$object->label = trim($label);
	$object->accountancy_code_asset = trim($accountancy_code_asset);
	$object->accountancy_code_depreciation_asset = trim($accountancy_code_depreciation_asset);
	$object->accountancy_code_depreciation_expense = trim($accountancy_code_depreciation_expense);
	$object->note = trim($comment);

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $object);
	if ($ret < 0) $error++;

	$ret = $object->update($user);

	if ($ret >= 0 && !count($object->errors))
	{
		setEventMessages($langs->trans("AssetsTypeModified"), null, 'mesgs');
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$object->id);
	exit;
}

if ($action == 'confirm_delete' && $user->rights->asset->write)
{
	$object->fetch($rowid);
	$res = $object->delete();

	if ($res > 0)
	{
		setEventMessages($langs->trans("AssetsTypeDeleted"), null, 'mesgs');
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		setEventMessages($langs->trans("AssetsTypeCanNotBeDeleted"), null, 'errors');
		$action = '';
	}
}


/*
 * View
 */

$form = new Form($db);

$helpurl = '';
llxHeader('', $langs->trans("AssetsTypeSetup"), $helpurl);


// List of asset type
if (!$rowid && $action != 'create' && $action != 'edit')
{
	//dol_fiche_head('');

	$sql = "SELECT d.rowid, d.label as label, d.accountancy_code_asset, d.accountancy_code_depreciation_asset, d.accountancy_code_depreciation_expense, d.note";
	$sql .= " FROM ".MAIN_DB_PREFIX."asset_type as d";
	$sql .= " WHERE d.entity IN (".getEntity('asset_type').")";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$nbtotalofrecords = $num;

		$i = 0;

		$param = '';

        $newcardbutton = '';
        if ($user->rights->asset->configurer)
        {
            $newcardbutton = '<a class="butActionNew" href="'.DOL_URL_ROOT.'/asset/type.php?action=create"><span class="valignmiddle text-plus-circle">'.$langs->trans('NewAssetType').'</span>';
            $newcardbutton .= '<span class="fa fa-plus-circle valignmiddle"></span>';
            $newcardbutton .= '</a>';
        }

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="page" value="'.$page.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

		print_barre_liste($langs->trans("AssetsTypes"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'accountancy', 0, $newcardbutton, '', $limit);

		$moreforfilter = '';

		print '<div class="div-table-responsive">';
		print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("Ref").'</th>';
		print '<th>'.$langs->trans("Label").'</th>';
		print '<th align="center">'.$langs->trans("AccountancyCodeAsset").'</th>';
		print '<th align="center">'.$langs->trans("AccountancyCodeDepreciationAsset").'</th>';
		print '<th align="center">'.$langs->trans("AccountancyCodeDepreciationExpense").'</th>';
		print '<th>&nbsp;</th>';
		print "</tr>\n";

		$assettype = new AssetType($db);

		while ($i < $num)
		{
			$objp = $db->fetch_object($result);

			$assettype->id = $objp->rowid;
			$assettype->ref = $objp->rowid;
			$assettype->label = $objp->rowid;

			print '<tr class="oddeven">';
			print '<td>';
			print $assettype->getNomUrl(1);
			//<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowType"),'group').' '.$objp->rowid.'</a>
			print '</td>';
			print '<td>'.dol_escape_htmltag($objp->label).'</td>';

			print '<td class="center">';
			if (!empty($conf->accounting->enabled))
			{
				$accountingaccount = new AccountingAccount($db);
				$accountingaccount->fetch('', $objp->accountancy_code_asset, 1);

				print $accountingaccount->getNomUrl(0, 0, 0, '', 0);
			} else {
				print $objp->accountancy_code_asset;
			}
			print '</td>';

			print '<td class="center">';
			if (!empty($conf->accounting->enabled))
			{
				$accountingaccount2 = new AccountingAccount($db);
				$accountingaccount2->fetch('', $objp->accountancy_code_depreciation_asset, 1);

				print $accountingaccount2->getNomUrl(0, 0, 0, '', 0);
			} else {
				print $objp->accountancy_code_depreciation_asset;
			}
			print '</td>';

			print '<td class="center">';
			if (!empty($conf->accounting->enabled))
			{
				$accountingaccount3 = new AccountingAccount($db);
				$accountingaccount3->fetch('', $objp->accountancy_code_depreciation_expense, 1);

				print $accountingaccount3->getNomUrl(0, 0, 0, '', 0);
			} else {
				print $objp->accountancy_code_depreciation_expense;
			}
			print '</td>';

			if ($user->rights->asset->write)
				print '<td class="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
			else
				print '<td class="right">&nbsp;</td>';
			print "</tr>";
			$i++;
		}
		print "</table>";
		print '</div>';

		print '</form>';
	}
	else
	{
		dol_print_error($db);
	}
}


/* ************************************************************************** */
/*                                                                            */
/* Creation mode                                                              */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'create')
{
	$object = new AssetType($db);
	if (!empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);

	print load_fiche_titre($langs->trans("NewAssetType"));

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('');

	print '<table class="border centpercent">';
	print '<tbody>';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="label" size="40"></td></tr>';

	if (!empty($conf->accounting->enabled))
	{
		// Accountancy_code_asset
		print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeAsset").'</td>';
		print '<td>';
		print $formaccounting->select_account($object->accountancy_code_asset, 'accountancy_code_asset', 1, '', 1, 1);
		print '</td></tr>';

		// Accountancy_code_depreciation_expense
		print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeDepreciationAsset").'</td>';
		print '<td>';
		print $formaccounting->select_account($object->accountancy_code_depreciation_asset, 'accountancy_code_depreciation_asset', 1, '', 1, 1);
		print '</td></tr>';

		// Accountancy_code_depreciation_expense
		print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeDepreciationExpense").'</td>';
		print '<td>';
		print $formaccounting->select_account($object->accountancy_code_depreciation_expense, 'accountancy_code_depreciation_expense', 1, '', 1, 1);
		print '</td></tr>';
	}
	else // For external software
	{
		// Accountancy_code_asset
		print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeAsset").'</td>';
		print '<td><input name="accountancy_code_asset" class="maxwidth200" value="'.$object->accountancy_code_asset.'">';
		print '</td></tr>';

		// Accountancy_code_depreciation_asset
		print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeDepreciationAsset").'</td>';
		print '<td><input name="accountancy_code_depreciation_asset" class="maxwidth200" value="'.$object->accountancy_code_depreciation_asset.'">';
		print '</td></tr>';

		// Accountancy_code_depreciation_expense
		print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeDepreciationExpense").'</td>';
		print '<td><input name="accountancy_code_depreciation_expense" class="maxwidth200" value="'.$object->accountancy_code_depreciation_expense.'">';
		print '</td></tr>';
	}

	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	print '<textarea name="comment" wrap="soft" class="centpercent" rows="3"></textarea></td></tr>';

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook))
	{
		print $object->showOptionals($extrafields, 'edit', $parameters);
	}
	print '<tbody>';
	print "</table>\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" name="button" class="button" value="'.$langs->trans("Add").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'" onclick="history.go(-1)" />';
	print '</div>';

	print "</form>\n";
}

/* ************************************************************************** */
/*                                                                            */
/* View mode                                                                  */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0)
{
	if ($action != 'edit')
	{
		$object = new AssetType($db);
		$object->fetch($rowid);
		$object->fetch_optionals();

		/*
		 * Confirmation suppression
		 */
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?rowid=".$object->id, $langs->trans("DeleteAnAssetType"), $langs->trans("ConfirmDeleteAssetType", $object->label), "confirm_delete", '', 0, 1);
		}

		$head = asset_type_prepare_head($object);

		dol_fiche_head($head, 'card', $langs->trans("AssetType"), -1, 'setup');

		$linkback = '<a href="'.DOL_URL_ROOT.'/asset/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '<div class="refidno">';
		// Ref asset type
		$morehtmlref .= $form->editfieldkey("Label", 'label', $object->label, $object, $user->rights->asset->write, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("Label", 'label', $object->label, $object, $user->rights->asset->write, 'string', '', null, null, '', 1);
		$morehtmlref .= '</div>';

		dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent">';

		print '<tr>';
		print '<td class="nowrap">';
		print $langs->trans("AccountancyCodeAsset");
		print '</td><td>';
		if (!empty($conf->accounting->enabled))
		{
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch('', $object->accountancy_code_asset, 1);

			print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
		} else {
			print $object->accountancy_code_asset;
		}
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="nowrap">';
		print $langs->trans("AccountancyCodeDepreciationAsset");
		print '</td><td>';
		if (!empty($conf->accounting->enabled))
		{
			$accountingaccount2 = new AccountingAccount($db);
			$accountingaccount2->fetch('', $object->accountancy_code_depreciation_asset, 1);

			print $accountingaccount2->getNomUrl(0, 1, 1, '', 1);
		} else {
			print $object->accountancy_code_depreciation_asset;
		}
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="nowrap">';
		print $langs->trans("AccountancyCodeDepreciationExpense");
		print '</td><td>';
		if (!empty($conf->accounting->enabled))
		{
			$accountingaccount3 = new AccountingAccount($db);
			$accountingaccount3->fetch('', $object->accountancy_code_depreciation_expense, 1);

			print $accountingaccount3->getNomUrl(0, 1, 1, '', 1);
		} else {
			print $object->accountancy_code_depreciation_expense;
		}
		print '</td>';
		print '</tr>';

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		print nl2br($object->note)."</td></tr>";

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';
		print '</div>';

		dol_fiche_end();

		/*
		 * Buttons
		 */

		print '<div class="tabsAction">';

		// Edit
		if ($user->rights->asset->write)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;rowid='.$object->id.'">'.$langs->trans("Modify").'</a></div>';
		}

		// Delete
		if ($user->rights->asset->write)
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$object->id.'">'.$langs->trans("DeleteType").'</a></div>';
		}

		print "</div>";
	}

	/* ************************************************************************** */
	/*                                                                            */
	/* Edition mode                                                               */
	/*                                                                            */
	/* ************************************************************************** */

	if ($action == 'edit')
	{
		$object = new AssetType($db);
		$object->fetch($rowid);
		$object->fetch_optionals();
		if (!empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);

		$head = asset_type_prepare_head($object);

		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="rowid" value="'.$object->id.'">';
		print '<input type="hidden" name="action" value="update">';

		dol_fiche_head($head, 'card', $langs->trans("AssetsType"), -1, 'setup');

		print '<table class="border centpercent">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->id.'</td></tr>';

		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" name="label" size="40" value="'.dol_escape_htmltag($object->label).'"></td></tr>';

		if (!empty($conf->accounting->enabled))
		{
			// Accountancy_code_asset
			print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeAsset").'</td>';
			print '<td>';
			print $formaccounting->select_account($object->accountancy_code_asset, 'accountancy_code_asset', 1, '', 1, 1);
			print '</td></tr>';

			// Accountancy_code_depreciation_expense
			print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeDepreciationAsset").'</td>';
			print '<td>';
			print $formaccounting->select_account($object->accountancy_code_depreciation_asset, 'accountancy_code_depreciation_asset', 1, '', 1, 1);
			print '</td></tr>';

			// Accountancy_code_depreciation_expense
			print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeDepreciationExpense").'</td>';
			print '<td>';
			print $formaccounting->select_account($object->accountancy_code_depreciation_expense, 'accountancy_code_depreciation_expense', 1, '', 1, 1);
			print '</td></tr>';
		}
		else // For external software
		{
			// Accountancy_code_asset
			print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeAsset").'</td>';
			print '<td><input name="accountancy_code_asset" class="maxwidth200" value="'.$object->accountancy_code_asset.'">';
			print '</td></tr>';

			// Accountancy_code_depreciation_asset
			print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeDepreciationAsset").'</td>';
			print '<td><input name="accountancy_code_depreciation_asset" class="maxwidth200" value="'.$object->accountancy_code_depreciation_asset.'">';
			print '</td></tr>';

			// Accountancy_code_depreciation_expense
			print '<tr><td class="titlefield">'.$langs->trans("AccountancyCodeDepreciationExpense").'</td>';
			print '<td><input name="accountancy_code_depreciation_expense" class="maxwidth200" value="'.$object->accountancy_code_depreciation_expense.'">';
			print '</td></tr>';
		}

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		print '<textarea name="comment" wrap="soft" class="centpercent" rows="3">'.$object->note.'</textarea></td></tr>';

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $act, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook))
		{
			print $object->showOptionals($extrafields, 'edit', $parameters);
		}

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

		print '</table>';

		dol_fiche_end();

		print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
