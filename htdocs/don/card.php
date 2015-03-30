<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Alexandre Spangaro	  	<alexandre.spangaro@gmail.com>
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
if (! empty($conf->projet->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->load("companies");
$langs->load("donations");
$langs->load("bills");

$id=GETPOST('rowid')?GETPOST('rowid','int'):GETPOST('id','int');
$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel');
$amount=GETPOST('amount');
$donation_date=dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));

$object = new Don($db);
$extrafields = new ExtraFields($db);

// Security check
$result = restrictedArea($user, 'don', $id);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('doncard','globalcard'));

/*
 * Actions
 */
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if ($action == 'update')
{
	if (! empty($cancel))
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}

	$error=0;

    if (empty($donation_date))
    {
	    setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("Date")), 'errors');
        $action = "create";
        $error++;
    }

	if (empty($amount))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("Amount")), 'errors');
		$action = "create";
		$error++;
	}

	if (! $error)
	{
		$object->fetch($id);

		$object->firstname   = GETPOST("firstname");
		$object->lastname    = GETPOST("lastname");
		$object->societe     = GETPOST("societe");
		$object->address     = GETPOST("address");
		$object->amount      = price2num(GETPOST("amount"));
		$object->town        = GETPOST("town");
        $object->zip         = GETPOST("zipcode");
        $object->country_id  = GETPOST('country_id', 'int');
        $object->email       = GETPOST("email");
		$object->date        = $donation_date;
		$object->public      = GETPOST("public");
		$object->fk_project  = GETPOST("fk_project");
		$object->note_private= GETPOST("note_private");
		$object->note_public = GETPOST("note_public");
		
		// Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
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
	if (! empty($cancel))
	{
		header("Location: index.php");
		exit;
	}

	$error=0;

    if (empty($donation_date))
    {
	    setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("Date")), 'errors');
        $action = "create";
        $error++;
    }

	if (empty($amount))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->trans("Amount")), 'errors');
		$action = "create";
		$error++;
	}

	if (! $error)
	{
		$object->firstname   = GETPOST("firstname");
		$object->lastname    = GETPOST("lastname");
		$object->societe     = GETPOST("societe");
		$object->address     = GETPOST("address");
		$object->amount      = price2num(GETPOST("amount"));
        $object->zip         = GETPOST("zipcode");
        $object->town        = GETPOST("town");
        $object->country_id  = GETPOST('country_id', 'int');
		$object->email       = GETPOST("email");
		$object->date        = $donation_date;
		$object->note_private= GETPOST("note_private");
		$object->note_public = GETPOST("note_public");
		$object->public      = GETPOST("public");
		$object->fk_project  = GETPOST("fk_project");
		
		// Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

		if ($object->create($user) > 0)
		{
			header("Location: index.php");
			exit;
		}
	}
}
if ($action == 'confirm_delete' && GETPOST("confirm") == "yes" && $user->rights->don->supprimer)
{
    $object->fetch($id);
    $result=$object->delete($user);
    if ($result > 0)
    {
        header("Location: index.php");
        exit;
    }
    else
    {
        dol_syslog($object->error,LOG_DEBUG);
        setEventMessage($object->error,'errors');
        setEventMessage($object->errors,'errors');
    }
}
if ($action == 'valid_promesse')
{
	if ($object->valid_promesse($id, $user->id) >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
    else {
	    setEventMessage($object->error, 'errors');
    }
}
if ($action == 'set_cancel')
{
    if ($object->set_cancel($id) >= 0)
    {
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
        exit;
    }
    else {
	    setEventMessage($object->error, 'errors');
    }
}
if ($action == 'set_paid')
{
	if ($object->set_paid($id, $modepayment) >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
    else {
	    setEventMessage($object->error, 'errors');
    }
}

/*
 * Build doc
 */
if ($action == 'builddoc')
{
	$object = new Don($db);
	$object->fetch($id);

	// Save last template used to generate document
	if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
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


/*
 * View
 */

llxHeader('',$langs->trans("Donations"),'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones');

$form=new Form($db);
$formfile = new FormFile($db);
$formcompany = new FormCompany($db);


/* ************************************************************************** */
/*                                                                            */
/* Donation card in create mode                                               */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{
	print_fiche_titre($langs->trans("AddDonation"));

	print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

	print '<input type="hidden" name="action" value="add">';

    $nbrows=11;
    if (! empty($conf->projet->enabled)) $nbrows++;

    // Date
	print '<tr><td class="fieldrequired" width="25%">'.$langs->trans("Date").'</td><td>';
	$form->select_date($donation_date?$donation_date:-1,'','','','',"add",1,1);
	print '</td>';

    // Amount
    print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" value="'.GETPOST("amount").'" size="10"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $form->selectyesno("public",isset($_POST["public"])?$_POST["public"]:1,1);
	print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" value="'.GETPOST("societe").'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" value="'.GETPOST("lastname").'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" value="'.GETPOST("firstname").'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
	print '<textarea name="address" wrap="soft" cols="40" rows="3">'.GETPOST("address").'</textarea></td></tr>';

    // Zip / Town
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
	print $formcompany->select_ziptown((isset($_POST["zipcode"])?$_POST["zipcode"]:$object->zip),'zipcode',array('town','selectcountry_id','state_id'),6);
    print ' ';
    print $formcompany->select_ziptown((isset($_POST["town"])?$_POST["town"]:$object->town),'town',array('zipcode','selectcountry_id','state_id'));
    print '</tr>';

	// Country
    print '<tr><td width="25%"><label for="selectcountry_id">'.$langs->trans('Country').'</label></td><td colspan="3" class="maxwidthonsmartphone">';
    print $form->select_country(GETPOST('country_id')!=''?GETPOST('country_id'):$object->country_id);
    if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
    print '</td></tr>';
	
	print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" value="'.GETPOST("email").'" size="40"></td></tr>';

	// Public note
	print '<tr>';
	print '<td class="border" valign="top">' . $langs->trans('NotePublic') . '</td>';
	print '<td valign="top" colspan="2">';
	
    $doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
	print $doleditor->Create(1);
	print '</td></tr>';

	// Private note
	if (empty($user->societe_id)) {
		print '<tr>';
		print '<td class="border" valign="top">' . $langs->trans('NotePrivate') . '</td>';
		print '<td valign="top" colspan="2">';

		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
		print $doleditor->Create(1);
		print '</td></tr>';
	}

	if (! empty($conf->projet->enabled))
    {
    	
    	$formproject=new FormProjets($db);
    	
        print "<tr><td>".$langs->trans("Project")."</td><td>";
        $formproject->select_projects(-1, GETPOST("fk_project"),'fk_project', 0, 1, 0, 1);
		print "</td></tr>\n";
    }

    // Other attributes
    $parameters=array('colspan' => 3);
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
		print $object->showOptionals($extrafields,'edit',$parameters);
    }
	
	print "</table>\n";
	print '<br><div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></div>';
	print "</form>\n";
}


/* ************************************************************ */
/*                                                              */
/* Donation card in edit mode                                   */
/*                                                              */
/* ************************************************************ */

if (! empty($id) && $action == 'edit')
{
	$result=$object->fetch($id);
	if ($result < 0) {
		dol_print_error($db,$object->error); exit;
	}
	$result=$object->fetch_optionals($object->id,$extralabels);
	if ($result < 0) {
		dol_print_error($db); exit;
	}

	$head = donation_prepare_head($object);
	dol_fiche_head($head, $hselected, $langs->trans("Donation"), 0, 'generic');

	print '<form name="update" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="rowid" value="'.$object->id.'">';
	print '<input type="hidden" name="amount" value="'.$object->amount.'">';

	// Ref
	print "<tr>".'<td>'.$langs->trans("Ref").'</td><td colspan="2">';
	print $object->getNomUrl();
	print '</td>';
	print '</tr>';

    $nbrows=12;
    if (! empty($conf->projet->enabled)) $nbrows++;

	// Date
	print "<tr>".'<td width="25%" class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	$form->select_date($object->date,'','','','',"update");
	print '</td>';

	// Amount
	if ($object->statut == 0)
	{
		print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" size="10" value="'.$object->amount.'"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';
	}
	else
	{
		print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="2">';
		print price($object->amount,0,$langs,0,0,-1,$conf->currency);
		print '</td></tr>';
	}

	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $form->selectyesno("public",1,1);
	print "</td>";
	print "</tr>\n";

	$langs->load("companies");
	print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.$object->societe.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" size="40" value="'.$object->lastname.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" size="40" value="'.$object->firstname.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
	print '<textarea name="address" wrap="soft" cols="40" rows="'.ROWS_3.'">'.$object->address.'</textarea></td></tr>';

    // Zip / Town
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
	print $formcompany->select_ziptown((isset($_POST["zipcode"])?$_POST["zipcode"]:$object->zip),'zipcode',array('town','selectcountry_id','state_id'),6);
    print ' ';
	print $formcompany->select_ziptown((isset($_POST["town"])?$_POST["town"]:$object->town),'town',array('zipcode','selectcountry_id','state_id'));
	print '</tr>';

	// Country
	print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
	print $form->select_country((!empty($object->country_id)?$object->country_id:$mysoc->country_code),'country_id');
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	print '</td></tr>';
	
	print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" size="40" value="'.$object->email.'"></td></tr>';

    print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";

    if ($object->modepaymentid) $selected = $object->modepaymentid;
    else $selected = '';

    $form->select_types_paiements($selected, 'modepayment', 'CRDT', 0, 1);
    print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

    // Project
    if (! empty($conf->projet->enabled))
    {
    	$formproject=new FormProjets($db);
    	
        $langs->load('projects');
        print '<tr><td>'.$langs->trans('Project').'</td><td>';
		$formproject->select_projects(-1, $object->fk_project,'fk_project', 0, 1, 0, 1);
        print '</td></tr>';
    }

    // Other attributes
    $parameters=array('colspan' => ' colspan="2"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
      	print $object->showOptionals($extrafields,'edit');
    }

	print "</table>\n";

	print '<br><div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></div>';

	print "</form>\n";

	print "</div>\n";
}



/* ************************************************************ */
/*                                                              */
/* Donation card in view mode                                   */
/*                                                              */
/* ************************************************************ */
if (! empty($id) && $action != 'edit')
{
	// Confirmation delete
    if ($action == 'delete')
    {
        $text=$langs->trans("ConfirmDeleteADonation");
        print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("DeleteADonation"),$text,"confirm_delete",'','',1);
    }
	
	$result=$object->fetch($id);
	if ($result < 0) {
		dol_print_error($db,$object->error); exit;
	}
	$result=$object->fetch_optionals($object->id,$extralabels);
	if ($result < 0) {
		dol_print_error($db); exit;
	}
	
	$head = donation_prepare_head($object);
	dol_fiche_head($head, $hselected, $langs->trans("Donation"), 0, 'generic');

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/don/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

    $nbrows=12;
    if (! empty($conf->projet->enabled)) $nbrows++;

	// Ref
	print "<tr>".'<td>'.$langs->trans("Ref").'</td><td colspan="2">';
	print $form->showrefnav($object, 'rowid', $linkback, 1, 'rowid', 'ref', '');
	print '</td>';
	print '</tr>';

	// Date
	print '<tr><td width="25%">'.$langs->trans("Date").'</td><td colspan="2">';
	print dol_print_date($object->date,"day");
	print "</td>";

    print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="2">';
	print price($object->amount,0,$langs,0,0,-1,$conf->currency);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("PublicDonation").'</td><td colspan="2">';
	print yn($object->public);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Company").'</td><td colspan="2">'.$object->societe.'</td></tr>';
	print '<tr><td>'.$langs->trans("Lastname").'</td><td colspan="2">'.$object->lastname.'</td></tr>';
	print '<tr><td>'.$langs->trans("Firstname").'</td><td colspan="2">'.$object->firstname.'</td></tr>';
	print '<tr><td>'.$langs->trans("Address").'</td><td>'.dol_nl2br($object->address).'</td>';
	
	$rowspan=6;
	if (! empty($conf->projet->enabled)) $rowspan++;
	print '<td rowspan="'.$rowspan.'" valign="top">';

	/*
	 * Payments
	 */
	$sql = "SELECT p.rowid, p.num_payment, p.datep as dp, p.amount,";
	$sql.= "c.code as type_code,c.libelle as paiement_type";
	$sql.= " FROM ".MAIN_DB_PREFIX."payment_donation as p";
	$sql.= ", ".MAIN_DB_PREFIX."c_paiement as c ";
	$sql.= ", ".MAIN_DB_PREFIX."don as d";
	$sql.= " WHERE d.rowid = '".$id."'";
	$sql.= " AND p.fk_donation = d.rowid";
	$sql.= " AND d.entity = ".$conf->entity;
	$sql.= " AND p.fk_typepayment = c.id";
	$sql.= " ORDER BY dp";

	//print $sql;
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0; $total = 0;
		print '<table class="nobordernopadding" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("RefPayment").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>'.$langs->trans("Type").'</td>';
   		print '<td align="right">'.$langs->trans("Amount").'</td>';
   		print '<td>&nbsp;</td>';
   		print '</tr>';

		$var=True;
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print "<tr ".$bc[$var]."><td>";
			print '<a href="'.DOL_URL_ROOT.'/don/payment/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"),"payment").' '.$objp->rowid.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
		        $labeltype=$langs->trans("PaymentType".$object->type_code)!=("PaymentType".$object->type_code)?$langs->trans("PaymentType".$object->type_code):$object->paiement_type;				
                               print "<td>".$labeltype.' '.$object->num_paiement."</td>\n";
			print '<td align="right">'.price($objp->amount)."</td><td>&nbsp;".$langs->trans("Currency".$conf->currency)."</td>\n";
			print "</tr>";
			$totalpaid += $objp->amount;
			$i++;
		}

		if ($object->paid == 0)
		{
			print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("AlreadyPaid")." :</td><td align=\"right\"><b>".price($totalpaid)."</b></td><td>&nbsp;".$langs->trans("Currency".$conf->currency)."</td></tr>\n";
			print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("AmountExpected")." :</td><td align=\"right\" bgcolor=\"#d0d0d0\">".price($object->amount)."</td><td bgcolor=\"#d0d0d0\">&nbsp;".$langs->trans("Currency".$conf->currency)."</td></tr>\n";

			$remaintopay = $object->amount - $totalpaid;

			print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("RemainderToPay")." :</td>";
			print "<td align=\"right\" bgcolor=\"#f0f0f0\"><b>".price($remaintopay)."</b></td><td bgcolor=\"#f0f0f0\">&nbsp;".$langs->trans("Currency".$conf->currency)."</td></tr>\n";
		}
		print "</table>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
	print "</td>";

	print "</tr>";

	// Zip / Town
	print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
	print $object->zip.($object->zip && $object->town?' / ':'').$object->town.'</td></tr>';

	// Country
	print '<tr><td>'.$langs->trans('Country').'</td><td>';
	if (! empty($object->country_code))
	{
		$img=picto_from_langcode($object->country_code);
		print ($img?$img.' ':'');
		print $object->country;
	}
	else
	{
		print $object->country_olddata;
	}	
	print '</td></tr>';

	// EMail
	print "<tr>".'<td>'.$langs->trans("EMail").'</td><td>'.dol_print_email($object->email).'</td></tr>';

	// Payment mode
	print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
	$form->form_modes_reglement(null, $object->modepaymentid,'none');
	print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

    // Project
    if (! empty($conf->projet->enabled))
    {
        print '<tr>';
		print '<td>'.$langs->trans("Project").'</td>';
		print '<td>'.$object->projet.'</td>';
		print '</tr>';
    }

    // Other attributes
    $parameters=array('colspan' => ' colspan="2"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
       	print $object->showOptionals($extrafields);
    }

	print "</table>\n";
	print "</form>\n";

	print "</div>";

	$remaintopay = $object->amount - $totalpaid;

	/**
	 * Actions buttons
	 */
	print '<div class="tabsAction">';

	print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?action=edit&rowid='.$object->id.'">'.$langs->trans('Modify').'</a></div>';

	if ($object->statut == 0)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?rowid='.$object->id.'&action=valid_promesse">'.$langs->trans("ValidPromess").'</a></div>';
	}

    if (($object->statut == 0 || $object->statut == 1) && $remaintopay == 0 && $object->paye == 0)
    {
        print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?rowid='.$object->id.'&action=set_cancel">'.$langs->trans("ClassifyCanceled")."</a></div>";
    }

	// Create payment
	if ($object->statut == 1 && $object->paid == 0 && $user->rights->don->creer) 
	{
		if ($remaintopay == 0) 
		{
			print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseRemainderToPayIsZero") . '">' . $langs->trans('DoPayment') . '</span></div>';
		} 
		else
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/don/payment/payment.php?rowid=' . $object->id . '&amp;action=create">' . $langs->trans('DoPayment') . '</a></div>';
		}
	}

	// Classify 'paid'
	if ($object->statut == 1 && round($remaintopay) == 0 && $object->paid == 0 && $user->rights->don->creer)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?rowid='.$object->id.'&action=set_paid">'.$langs->trans("ClassifyPaid")."</a></div>";
	}
	
	// Delete
	if ($user->rights->don->supprimer)
	{
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?rowid='.$object->id.'&action=delete">'.$langs->trans("Delete")."</a></div>";
	}
	else
	{
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">'.$langs->trans("Delete")."</a></div>";
	}

	print "</div>";


	print '<table width="100%"><tr><td width="50%" valign="top">';

	/*
	 * Documents generes
	 */
	$filename=dol_sanitizeFileName($object->id);
	$filedir=$conf->don->dir_output . '/' . get_exdir($filename,2);
	$urlsource=$_SERVER['PHP_SELF'].'?rowid='.$object->id;
	//            $genallowed=($fac->statut == 1 && ($fac->paye == 0 || $user->admin) && $user->rights->facture->creer);
	//            $delallowed=$user->rights->facture->supprimer;
	$genallowed=1;
	$delallowed=0;

	$var=true;

	print '<br>';
	$formfile->show_documents('donation',$filename,$filedir,$urlsource,$genallowed,$delallowed);

	print '</td><td>&nbsp;</td>';

	print '</tr></table>';

}

llxFooter();
$db->close();