<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2015-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2019  Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/don/card.php
 *  \ingroup    donations
 *  \brief      Page of donation card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
if (!empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->loadLangs(array("bills", "companies", "donations"));

$id = GETPOST('rowid') ?GETPOST('rowid', 'int') : GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$amount = GETPOST('amount');
$donation_date = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
$projectid = (GETPOST('projectid') ? GETPOST('projectid', 'int') : 0);

$object = new Don($db);
$extrafields = new ExtraFields($db);

// Security check
$result = restrictedArea($user, 'don', $id);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('doncard', 'globalcard'));


/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if ($action == 'update')
{
	if (!empty($cancel))
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}

	$error = 0;

    if (empty($donation_date))
    {
    	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
        $action = "create";
        $error++;
    }

	if (empty($amount))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
		$action = "create";
		$error++;
	}

	if (!$error)
	{
		$object->fetch($id);

		$object->firstname = GETPOST("firstname", 'alpha');
		$object->lastname = GETPOST("lastname", 'alpha');
		$object->societe = GETPOST("societe", 'alpha');
		$object->address = GETPOST("address", 'alpha');
		$object->amount = price2num(GETPOST("amount", 'alpha'));
		$object->town = GETPOST("town", 'alpha');
		$object->zip = GETPOST("zipcode", 'alpha');
		$object->country_id = GETPOST('country_id', 'int');
		$object->email = GETPOST("email", 'alpha');
		$object->date = $donation_date;
		$object->public = GETPOST("public", 'alpha');
		$object->fk_project = GETPOST("fk_project", 'alpha');
		$object->note_private = GETPOST("note_private", 'none');
		$object->note_public = GETPOST("note_public", 'none');
		$object->modepaymentid = GETPOST('modepayment', 'int');

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) $error++;

		if ($object->update($user) > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
	}
}

if ($action == 'add')
{
	if (!empty($cancel))
	{
		header("Location: index.php");
		exit;
	}

	$error = 0;

    if (empty($donation_date))
    {
    	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
        $action = "create";
        $error++;
    }

	if (empty($amount))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
		$action = "create";
		$error++;
	}

	if (!$error)
	{
        $object->socid = GETPOST("socid", 'int');
		$object->firstname = GETPOST("firstname", 'alpha');
		$object->lastname = GETPOST("lastname", 'alpha');
		$object->societe = GETPOST("societe", 'alpha');
		$object->address = GETPOST("address", 'alpha');
		$object->amount = price2num(GETPOST("amount", 'alpha'));
		$object->zip = GETPOST("zipcode", 'alpha');
		$object->town = GETPOST("town", 'alpha');
		$object->country_id = GETPOST('country_id', 'int');
		$object->email = GETPOST('email', 'alpha');
        $object->date = $donation_date;
		$object->note_private = GETPOST("note_private", 'none');
		$object->note_public = GETPOST("note_public", 'none');
		$object->public = GETPOST("public", 'alpha');
		$object->fk_project = GETPOST("fk_project", 'alpha');
		$object->modepaymentid = GETPOST('modepayment', 'int');

		// Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost(null, $object);
		if ($ret < 0) $error++;

		$res = $object->create($user);
		if ($res > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$res);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}
if ($action == 'confirm_delete' && GETPOST("confirm") == "yes" && $user->rights->don->supprimer)
{
    $object->fetch($id);
    $result = $object->delete($user);
    if ($result > 0)
    {
        header("Location: index.php");
        exit;
    }
    else
    {
        dol_syslog($object->error, LOG_DEBUG);
        setEventMessages($object->error, $object->errors, 'errors');
    }
}
if ($action == 'valid_promesse')
{
	$object->fetch($id);
	if ($object->valid_promesse($id, $user->id) >= 0)
	{
		setEventMessages($langs->trans("DonationValidated", $object->ref), null);

		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
    else {
	    setEventMessages($object->error, $object->errors, 'errors');
    }
}
if ($action == 'set_cancel')
{
	$object->fetch($id);
	if ($object->set_cancel($id) >= 0)
    {
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
        exit;
    }
    else {
	    setEventMessages($object->error, $object->errors, 'errors');
    }
}
if ($action == 'set_paid')
{
	$object->fetch($id);
	if ($object->set_paid($id, $modepayment) >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
    else {
	    setEventMessages($object->error, $object->errors, 'errors');
    }
}
elseif ($action == 'classin' && $user->rights->don->creer)
{
	$object->fetch($id);
	$object->setProject($projectid);
}

// Actions to build doc
$upload_dir = $conf->don->dir_output;
$permissiontoadd = $user->rights->don->creer;
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';


// Remove file in doc form
/*if ($action == 'remove_file')
{
	$object = new Don($db, 0, $_GET['id']);
	if ($object->fetch($id))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$object->fetch_thirdparty();

		$langs->load("other");
		$upload_dir = $conf->don->dir_output;
		$file = $upload_dir . '/' . GETPOST('file');
		$ret=dol_delete_file($file,0,0,0,$object);
		if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
		$action='';
	}
}
*/

/*
 * Build doc
 */
/*
if ($action == 'builddoc')
{
	$object = new Don($db);
	$result=$object->fetch($id);

	// Save last template used to generate document
	if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->thirdparty->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$result=don_create($db, $object->id, '', $object->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
}
*/


/*
 * View
 */

llxHeader('', $langs->trans("Donation"), 'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones');

$form = new Form($db);
$formfile = new FormFile($db);
$formcompany = new FormCompany($db);
if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

if ($action == 'create')
{
	print load_fiche_titre($langs->trans("AddDonation"), '', 'object_donation');

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('');

	print '<table class="border centpercent">';
	print '<tbody>';

	// Ref
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';

	// Company
	if (!empty($conf->societe->enabled) && !empty($conf->global->DONATION_USE_THIRDPARTIES))
	{
		// Thirdparty
		print '<td>'.$langs->trans('Customer').'</td>';
		if ($soc->id > 0 && !GETPOST('fac_rec', 'alpha'))
		{
			print '<td colspan="2">';
			print $soc->getNomUrl(1);
			print '<input type="hidden" name="socid" value="'.$soc->id.'">';
			// Outstanding Bill
			$arrayoutstandingbills = $soc->getOutstandingBills();
			$outstandingBills = $arrayoutstandingbills['opened'];
			print ' ('.$langs->trans('CurrentOutstandingBill').': ';
			print price($outstandingBills, '', $langs, 0, 0, -1, $conf->currency);
			if ($soc->outstanding_limit != '')
			{
				if ($outstandingBills > $soc->outstanding_limit) print img_warning($langs->trans("OutstandingBillReached"));
				print ' / '.price($soc->outstanding_limit, '', $langs, 0, 0, -1, $conf->currency);
			}
			print ')';
			print '</td>';
		}
		else
		{
			print '<td colspan="2">';
			print $form->select_company($soc->id, 'socid', '(s.client = 1 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
			// Option to reload page to retrieve customer informations. Note, this clear other input
			if (!empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE))
			{
				print '<script type="text/javascript">
				$(document).ready(function() {
					$("#socid").change(function() {
						var socid = $(this).val();
				        var fac_rec = $(\'#fac_rec\').val();
						// reload page
						window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid+"&fac_rec="+fac_rec;
					});
				});
				</script>';
			}
			print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="valignmiddle text-plus-circle">'.$langs->trans("AddThirdParty").'</span><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
			print '</td>';
		}
		print '</tr>'."\n";
	}

	// Date
	print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Date").'</td><td>';
	print $form->selectDate($donation_date ? $donation_date : -1, '', '', '', '', "add", 1, 1);
	print '</td>';

	// Amount
	print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" value="'.dol_escape_htmltag(GETPOST("amount")).'" size="10"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';

	// Public donation
	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $form->selectyesno("public", isset($_POST["public"]) ? $_POST["public"] : 1, 1);
	print "</td></tr>\n";

	if (empty($conf->societe->enabled) || empty($conf->global->DONATION_USE_THIRDPARTIES))
	{
		print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" value="'.dol_escape_htmltag(GETPOST("societe")).'" class="maxwidth200"></td></tr>';
		print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" value="'.dol_escape_htmltag(GETPOST("lastname")).'" class="maxwidth200"></td></tr>';
		print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" value="'.dol_escape_htmltag(GETPOST("firstname")).'" class="maxwidth200"></td></tr>';
		print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
		print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="3">'.dol_escape_htmltag(GETPOST("address", "none"), 0, 1).'</textarea></td></tr>';

		// Zip / Town
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
		print $formcompany->select_ziptown((isset($_POST["zipcode"]) ? $_POST["zipcode"] : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
		print ' ';
		print $formcompany->select_ziptown((isset($_POST["town"]) ? $_POST["town"] : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
		print '</tr>';

		// Country
		print '<tr><td><label for="selectcountry_id">'.$langs->trans('Country').'</label></td><td class="maxwidthonsmartphone">';
		print img_picto('', 'globe-americas', 'class="paddingrightonly"').$form->select_country(GETPOST('country_id') != '' ?GETPOST('country_id') : $object->country_id);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		print '</td></tr>';

		print "<tr>".'<td>'.$langs->trans("EMail").'</td><td>'.img_picto('', 'object_email', 'class="paddingrightonly"').'<input type="text" name="email" value="'.dol_escape_htmltag(GETPOST("email")).'" class="maxwidth200"></td></tr>';
	}

	// Payment mode
	print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";
	$selected = GETPOST('modepayment', 'int');
	$form->select_types_paiements($selected, 'modepayment', 'CRDT', 0, 1);
	print "</td></tr>\n";

	// Public note
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
	print '<td>';

    $doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';

	// Private note
	if (empty($user->socid)) {
		print '<tr>';
		print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
		print '<td>';

		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td></tr>';
	}

	if (!empty($conf->projet->enabled))
    {
        print "<tr><td>".$langs->trans("Project")."</td><td>";
        $formproject->select_projects(-1, $projectid, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 0, 0, 'maxwidth500');
		print "</td></tr>\n";
    }

    // Other attributes
    $parameters = array();
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (empty($reshook))
    {
		print $object->showOptionals($extrafields, 'edit', $parameters);
    }

    print '</tbody>';
	print "</table>\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" onClick="javascript:history.go(-1)">';
	print '</div>';

	print "</form>\n";
}


/* ************************************************************ */
/*                                                              */
/* Donation card in edit mode                                   */
/*                                                              */
/* ************************************************************ */

if (!empty($id) && $action == 'edit')
{
	$result = $object->fetch($id);
	if ($result < 0) {
		dol_print_error($db, $object->error); exit;
	}
	$result = $object->fetch_optionals();
	if ($result < 0) {
		dol_print_error($db); exit;
	}

	$hselected = 'card';
	$head = donation_prepare_head($object);

	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="rowid" value="'.$object->id.'">';
	print '<input type="hidden" name="amount" value="'.$object->amount.'">';


	dol_fiche_head($head, $hselected, $langs->trans("Donation"), 0, 'donation');

	print '<table class="border centpercent">';

	// Ref
	print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="2">';
	print $object->getNomUrl();
	print '</td>';
	print '</tr>';

	// Date
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Date").'</td><td>';
	print $form->selectDate($object->date, '', '', '', '', "update");
	print '</td>';

	// Amount
	if ($object->statut == 0)
	{
		print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" size="10" value="'.price($object->amount).'"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';
	}
	else
	{
		print '<tr><td>'.$langs->trans("Amount").'</td><td>';
		print price($object->amount, 0, $langs, 0, 0, -1, $conf->currency);
		print '</td></tr>';
	}

	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $form->selectyesno("public", 1, 1);
	print "</td>";
	print "</tr>\n";

    if ($object->socid && !empty($conf->societe->enabled) && !empty($conf->global->DONATION_USE_THIRDPARTIES)) {
        $company = new Societe($db);
        $result = $company->fetch($object->socid);

        print '<tr><td>'.$langs->trans("LinkedToDolibarrThirdParty").'</td><td colspan="2">'.$company->getNomUrl(1).'</td></tr>';
    } else {
        $langs->load("companies");
        print '<tr><td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" class="maxwidth200" value="'.dol_escape_htmltag($object->societe).'"></td></tr>';
        print '<tr><td>'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" class="maxwidth200" value="'.dol_escape_htmltag($object->lastname).'"></td></tr>';
        print '<tr><td>'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" class="maxwidth200" value="'.dol_escape_htmltag($object->firstname).'"></td></tr>';
        print '<tr><td>'.$langs->trans("Address").'</td><td>';
        print '<textarea name="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag($object->address, 0, 1).'</textarea></td></tr>';

        // Zip / Town
        print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
        print $formcompany->select_ziptown((isset($_POST["zipcode"]) ? $_POST["zipcode"] : $object->zip), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
        print ' ';
        print $formcompany->select_ziptown((isset($_POST["town"]) ? $_POST["town"] : $object->town), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
        print '</tr>';

        // Country
        print '<tr><td class="titlefieldcreate">'.$langs->trans('Country').'</td><td>';
        print $form->select_country((!empty($object->country_id) ? $object->country_id : $mysoc->country_code), 'country_id');
        if ($user->admin) {
            print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
        }
        print '</td></tr>';

        print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" class="maxwidth200" value="'.dol_escape_htmltag($object->email).'"></td></tr>';
    }
    // Payment mode
    print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";
    if ($object->mode_reglement_id) $selected = $object->mode_reglement_id;
    else $selected = '';
    $form->select_types_paiements($selected, 'modepayment', 'CRDT', 0, 1);
    print "</td></tr>\n";

    // Status
	print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

    // Project
    if (!empty($conf->projet->enabled))
    {
    	$formproject = new FormProjets($db);

        $langs->load('projects');
        print '<tr><td>'.$langs->trans('Project').'</td><td>';
		$formproject->select_projects(-1, $object->fk_project, 'fk_project', 0, 0, 1, 1, 0, 0, 0, '', 0, 0, 'maxwidth500');
        print '</td></tr>';
    }

    // Other attributes
    $parameters = array();
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (empty($reshook))
    {
      	print $object->showOptionals($extrafields, 'edit', $parameters);
    }

	print "</table>\n";

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></div>';

	print "</form>\n";
}



/* ************************************************************ */
/*                                                              */
/* Donation card in view mode                                   */
/*                                                              */
/* ************************************************************ */
if (!empty($id) && $action != 'edit')
{
	// Confirmation delete
    if ($action == 'delete')
    {
        $text = $langs->trans("ConfirmDeleteADonation");
        print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("DeleteADonation"), $text, "confirm_delete", '', '', 1);
    }

	$result = $object->fetch($id);
	if ($result < 0) {
		dol_print_error($db, $object->error); exit;
	}
	$result = $object->fetch_optionals();
	if ($result < 0) {
		dol_print_error($db); exit;
	}

	$hselected = 'card';

	$head = donation_prepare_head($object);
	dol_fiche_head($head, $hselected, $langs->trans("Donation"), -1, 'donation');

	// Print form confirm
	print $formconfirm;

	$linkback = '<a href="'.DOL_URL_ROOT.'/don/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Project
	if (!empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref .= $langs->trans('Project').' ';
	    if ($user->rights->don->creer)
	    {
	        if ($action != 'classify') {
                $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
            }
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                $morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
                $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1, 0, 'maxwidth500');
                $morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref .= '</form>';
            } else {
                $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
	    } else {
	        if (!empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
	            $morehtmlref .= $proj->ref;
	            $morehtmlref .= '</a>';
	        } else {
	            $morehtmlref .= '';
	        }
	    }
	}
	$morehtmlref .= '</div>';


    dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border tableforfield" width="100%">';

	// Date
	print '<tr><td class="titlefield">'.$langs->trans("Date").'</td><td colspan="2">';
	print dol_print_date($object->date, "day");
	print "</td>";

    print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="2">';
	print price($object->amount, 0, $langs, 0, 0, -1, $conf->currency);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("PublicDonation").'</td><td colspan="2">';
	print yn($object->public);
	print '</td></tr>';

    if ($object->socid) {
        $company = new Societe($db);
        $result = $company->fetch($object->socid);

        print '<tr><td>'.$langs->trans("LinkedToDolibarrThirdParty").'</td><td colspan="2">'.$company->getNomUrl(1).'</td></tr>';
    } else {
        print '<tr><td>'.$langs->trans("Company").'</td><td colspan="2">'.$object->societe.'</td></tr>';
        print '<tr><td>'.$langs->trans("Lastname").'</td><td colspan="2">'.$object->lastname.'</td></tr>';
        print '<tr><td>'.$langs->trans("Firstname").'</td><td colspan="2">'.$object->firstname.'</td></tr>';
    }

	// Payment mode
	print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
	$form->form_modes_reglement(null, $object->mode_reglement_id, 'none');
	print "</td></tr>\n";

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';

	/*
	 * Payments
	 */
	$sql = "SELECT p.rowid, p.num_payment, p.datep as dp, p.amount,";
	$sql .= "c.code as type_code,c.libelle as paiement_type";
	$sql .= " FROM ".MAIN_DB_PREFIX."payment_donation as p";
	$sql .= ", ".MAIN_DB_PREFIX."c_paiement as c ";
	$sql .= ", ".MAIN_DB_PREFIX."don as d";
	$sql .= " WHERE d.rowid = '".$id."'";
	$sql .= " AND p.fk_donation = d.rowid";
	$sql .= " AND d.entity IN (".getEntity('donation').")";
	$sql .= " AND p.fk_typepayment = c.id";
	$sql .= " ORDER BY dp";

	//print $sql;
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0; $total = 0;
		print '<table class="noborder paymenttable centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("RefPayment").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Type").'</td>';
   		print '<td class="right">'.$langs->trans("Amount").'</td>';
   		print '</tr>';

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			print '<tr class="oddeven"><td>';
			print '<a href="'.DOL_URL_ROOT.'/don/payment/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"), "payment").' '.$objp->rowid.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
		    $labeltype = $langs->trans("PaymentType".$objp->type_code) != ("PaymentType".$objp->type_code) ? $langs->trans("PaymentType".$objp->type_code) : $objp->paiement_type;
            print "<td>".$labeltype.' '.$objp->num_payment."</td>\n";
			print '<td class="right">'.price($objp->amount)."</td>\n";
			print "</tr>";
			$totalpaid += $objp->amount;
			$i++;
		}

		if ($object->paid == 0)
		{
			print "<tr><td colspan=\"3\" class=\"right\">".$langs->trans("AlreadyPaid")." :</td><td class=\"right\">".price($totalpaid)."</td></tr>\n";
			print "<tr><td colspan=\"3\" class=\"right\">".$langs->trans("AmountExpected")." :</td><td class=\"right\">".price($object->amount)."</td></tr>\n";

			$remaintopay = $object->amount - $totalpaid;

			print "<tr><td colspan=\"3\" class=\"right\">".$langs->trans("RemainderToPay")." :</td>";
			print '<td class="right'.($resteapayeraffiche ? ' amountremaintopay' : '').'">'.price($remaintopay)."</td></tr>\n";
		}
		print "</table>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print '</div>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

    dol_fiche_end();

	$remaintopay = $object->amount - $totalpaid;

	// Actions buttons

	print '<div class="tabsAction">';

	print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$object->id.'">'.$langs->trans('Modify').'</a></div>';

	if ($object->statut == 0)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=valid_promesse">'.$langs->trans("ValidPromess").'</a></div>';
	}

    if (($object->statut == 0 || $object->statut == 1) && $totalpaid == 0 && $object->paid == 0)
    {
        print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=set_cancel">'.$langs->trans("ClassifyCanceled")."</a></div>";
    }

	// Create payment
	if ($object->statut == 1 && $object->paid == 0 && $user->rights->don->creer)
	{
		if ($remaintopay == 0)
		{
			print '<div class="inline-block divButAction"><span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPayment').'</span></div>';
		}
		else
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/don/payment/payment.php?rowid='.$object->id.'&amp;action=create">'.$langs->trans('DoPayment').'</a></div>';
		}
	}

	// Classify 'paid'
	if ($object->statut == 1 && round($remaintopay) == 0 && $object->paid == 0 && $user->rights->don->creer)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=set_paid">'.$langs->trans("ClassifyPaid")."</a></div>";
	}

	// Delete
	if ($user->rights->don->supprimer)
	{
		if ($object->statut == -1 || $object->statut == 0)
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?rowid='.$object->id.'&action=delete">'.$langs->trans("Delete")."</a></div>";
		}
		else
		{
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">'.$langs->trans("Delete")."</a></div>";
		}
	}
	else
	{
		print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">'.$langs->trans("Delete")."</a></div>";
	}

	print "</div>";


    print '<div class="fichecenter"><div class="fichehalfleft">';

	/*
	 * Documents generes
	 */
	$filename = dol_sanitizeFileName($object->id);
	$filedir = $conf->don->dir_output."/".dol_sanitizeFileName($object->id);
	$urlsource = $_SERVER['PHP_SELF'].'?rowid='.$object->id;
	$genallowed	= (($object->paid == 0 || $user->admin) && $user->rights->don->lire);
	$delallowed	= $user->rights->don->creer;

	print $formfile->showdocuments('donation', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf);

	// Show links to link elements
	$linktoelem = $form->showLinkToObjectBlock($object, null, array('don'));
	$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

	// Show online payment link
	$useonlinepayment = (!empty($conf->paypal->enabled) || !empty($conf->stripe->enabled) || !empty($conf->paybox->enabled));

	if ($useonlinepayment) //$object->statut != Facture::STATUS_DRAFT &&
	{
		print '<br><!-- Link to pay -->'."\n";
		require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
		print showOnlinePaymentUrl('donation', $object->ref).'<br>';
	}

	print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	print '</div></div></div>';
}

llxFooter();
$db->close();
