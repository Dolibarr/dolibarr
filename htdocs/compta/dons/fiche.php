<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
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
 *	    \file       htdocs/compta/dons/fiche.php
 *		\ingroup    don
 *		\brief      Page of donation card
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/dons/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
if (! empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

$langs->load("companies");
$langs->load("donations");
$langs->load("bills");

$id=GETPOST('rowid')?GETPOST('rowid','int'):GETPOST('id','int');
$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel');
$amount=GETPOST('amount');

$mesg="";
$mesgs=array();

$don = new Don($db);
$donation_date=dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));

// Security check
$result = restrictedArea($user, 'don', $id);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('doncard'));


/*
 * Actions
 */

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
        $mesgs[]=$langs->trans("ErrorFieldRequired",$langs->trans("Date"));
        $action = "create";
        $error++;
    }

	if (empty($amount))
	{
		$mesgs[]=$langs->trans("ErrorFieldRequired",$langs->trans("Amount"));
		$action = "create";
		$error++;
	}

	if (! $error)
	{
		$don->fetch($id);

		$don->firstname   = $_POST["firstname"];
		$don->lastname    = $_POST["lastname"];
		$don->societe     = $_POST["societe"];
		$don->address     = $_POST["address"];
		$don->amount      = price2num($_POST["amount"]);
		$don->town        = $_POST["town"];
        $don->zip         = $_POST["zipcode"];
        $don->country     = $_POST["country"];
		$don->email       = $_POST["email"];
		$don->date        = $donation_date;
		$don->note        = $_POST["note"];
		$don->public      = $_POST["public"];
		$don->fk_project  = $_POST["projectid"];
		$don->note_private= GETPOST("note_private");
		$don->note_public = GETPOST("note_public");
		$don->modepaiementid = $_POST["modepaiement"];

		if ($don->update($user) > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$don->id);
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
        $mesgs[]=$langs->trans("ErrorFieldRequired",$langs->trans("Date"));
        $action = "create";
        $error++;
    }

	if (empty($amount))
	{
		$mesgs[]=$langs->trans("ErrorFieldRequired",$langs->trans("Amount"));
		$action = "create";
		$error++;
	}

	if (! $error)
	{
		$don->firstname   = $_POST["firstname"];
		$don->lastname    = $_POST["lastname"];
		$don->societe     = $_POST["societe"];
		$don->address     = $_POST["address"];
		$don->amount      = price2num($_POST["amount"]);
		$don->town        = $_POST["town"];
        $don->zip         = $_POST["zipcode"];
        $don->town        = $_POST["town"];
        $don->country     = $_POST["country"];
		$don->email       = $_POST["email"];
		$don->date        = $donation_date;
		$don->note_private= GETPOST("note_private");
		$don->note_public = GETPOST("note_public");
		$don->public      = $_POST["public"];
		$don->fk_project  = $_POST["projectid"];
		$don->modepaiementid = $_POST["modepaiement"];

		if ($don->create($user) > 0)
		{
			header("Location: index.php");
			exit;
		}
	}
}

if ($action == 'delete')
{
	$don->delete($id);
	header("Location: liste.php");
	exit;
}
if ($action == 'commentaire')
{
	$don->fetch($id);
	$don->update_note($_POST["commentaire"]);
}
if ($action == 'valid_promesse')
{
	if ($don->valid_promesse($id, $user->id) >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
    else $mesg=$don->error;
}
if ($action == 'set_cancel')
{
    if ($don->set_cancel($id) >= 0)
    {
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
        exit;
    }
    else $mesg=$don->error;
}
if ($action == 'set_paid')
{
	if ($don->set_paye($id, $modepaiement) >= 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
    else $mesg=$don->error;
}
if ($action == 'set_encaisse')
{
	if ($don->set_encaisse($id) >= 0)
	{
        header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
    else $mesg=$don->error;
}

/*
 * Build doc
 */
if ($action == 'builddoc')
{
	$donation = new Don($db);
	$donation->fetch($id);

	if ($_REQUEST['model'])
	{
		$donation->setDocModel($user, $_REQUEST['model']);
	}

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$donation->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$result=don_create($db, $donation->id, '', $donation->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
	else
	{
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$donation->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
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
/* Creation                                                                   */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{
	print_fiche_titre($langs->trans("AddDonation"));

	dol_htmloutput_errors($mesg,$mesgs);

	print '<form name="add" action="fiche.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

	print '<input type="hidden" name="action" value="add">';

    $nbrows=11;
    if (! empty($conf->projet->enabled)) $nbrows++;

    // Date
	print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	$form->select_date($donation_date?$donation_date:-1,'','','','',"add",1,1);
	print '</td>';

    print '<td rowspan="'.$nbrows.'" valign="top">'.$langs->trans("Comments").' :<br>';
    print "<textarea name=\"note_private\" wrap=\"soft\" cols=\"40\" rows=\"15\">".GETPOST("note_private")."</textarea></td>";
    print "</tr>";

    // Amount
    print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" value="'.$_POST["amount"].'" size="10"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $form->selectyesno("public",isset($_POST["public"])?$_POST["public"]:1,1);
	print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" value="'.$_POST["societe"].'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" value="'.$_POST["firstname"].'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" value="'.$_POST["lastname"].'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
	print '<textarea name="address" wrap="soft" cols="40" rows="3">'.$_POST["address"].'</textarea></td></tr>';

    // Zip / Town
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
	print $formcompany->select_ziptown((isset($_POST["zipcode"])?$_POST["zipcode"]:$don->zip),'zipcode',array('town','selectcountry_id','state_id'),6);
    print ' ';
    print $formcompany->select_ziptown((isset($_POST["town"])?$_POST["town"]:$don->town),'town',array('zipcode','selectcountry_id','state_id'));
    print '</tr>';

	print "<tr>".'<td>'.$langs->trans("Country").'</td><td><input type="text" name="country" value="'.$_POST["country"].'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" value="'.$_POST["email"].'" size="40"></td></tr>';

    print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";
    $form->select_types_paiements('', 'modepaiement', 'CRDT', 0, 1);
    print "</td></tr>\n";

	if (! empty($conf->projet->enabled))
    {
        // Si module projet actif
        print "<tr><td>".$langs->trans("Project")."</td><td>";
        select_projects('',$_POST["projectid"],"projectid");
        print "</td></tr>\n";
    }

    // Other attributes
    $parameters=array('colspan' => ' colspan="1"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$don,$action);    // Note that $action and $object may have been modified by hook

	print "</table>\n";
	print '<br><center><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';
	print "</form>\n";
}


/* ************************************************************ */
/*                                                              */
/* Fiche don en mode edition                                    */
/*                                                              */
/* ************************************************************ */

if (! empty($id) && $action == 'edit')
{
	$don->fetch($id);

	$h=0;
	$head[$h][0] = $_SERVER['PHP_SELF']."?id=".$don->id;
	$head[$h][1] = $langs->trans("Card");
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("Donation"), 0, 'generic');

	print '<form name="update" action="fiche.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="rowid" value="'.$don->id.'">';

	// Ref
	print "<tr>".'<td>'.$langs->trans("Ref").'</td><td colspan="2">';
	print $don->getNomUrl();
	print '</td>';
	print '</tr>';

    $nbrows=12;
    if (! empty($conf->projet->enabled)) $nbrows++;

    // Date
	print "<tr>".'<td width="25%" class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	$form->select_date($don->date,'','','','',"update");
	print '</td>';

    print '<td rowspan="'.$nbrows.'" valign="top">'.$langs->trans("Comments").' :<br>';
    print "<textarea name=\"note_private\" wrap=\"soft\" cols=\"40\" rows=\"15\">".$don->note_private."</textarea></td>";
    print "</tr>";

	// Amount
    print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" size="10" value="'.$don->amount.'"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $form->selectyesno("public",1,1);
	print "</td>";
	print "</tr>\n";

	$langs->load("companies");
	print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.$don->societe.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" size="40" value="'.$don->firstname.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" size="40" value="'.$don->lastname.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
	print '<textarea name="address" wrap="soft" cols="40" rows="'.ROWS_3.'">'.$don->address.'</textarea></td></tr>';

    // Zip / Town
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
    print $formcompany->select_ziptown((isset($_POST["zipcode"])?$_POST["zipcode"]:$don->zip),'zipcode',array('town','selectcountry_id','state_id'),6);
    print ' ';
    print $formcompany->select_ziptown((isset($_POST["town"])?$_POST["town"]:$don->town),'town',array('zipcode','selectcountry_id','state_id'));
    print '</tr>';

	print "<tr>".'<td>'.$langs->trans("Country").'</td><td><input type="text" name="country" size="40" value="'.$don->country.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" size="40" value="'.$don->email.'"></td></tr>';

    print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";

    if ($don->modepaiementid) $selected = $don->modepaiementid;
    else $selected = '';

    $form->select_types_paiements($selected, 'modepaiement', 'CRDT', 0, 1);
    print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$don->getLibStatut(4).'</td></tr>';

    // Project
    if (! empty($conf->projet->enabled))
    {
        $langs->load('projects');
        print '<tr><td>'.$langs->trans('Project').'</td><td>';
        select_projects(-1, (isset($_POST["projectid"])?$_POST["projectid"]:$don->fk_project), 'projectid');
        print '</td></tr>';
    }

    // Other attributes
    $parameters=array('colspan' => ' colspan="1"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$don,$action);    // Note that $action and $object may have been modified by hook

	print "</table>\n";

	print '<br><center><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

	print "</form>\n";

	print "</div>\n";
}



/* ************************************************************ */
/*                                                              */
/* Fiche don en mode visu                                       */
/*                                                              */
/* ************************************************************ */
if (! empty($id) && $action != 'edit')
{
	$result=$don->fetch($id);

	$h=0;
	$head[$h][0] = $_SERVER['PHP_SELF']."?id=".$don->id;
	$head[$h][1] = $langs->trans("Card");
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("Donation"), 0, 'generic');

	print "<form action=\"fiche.php\" method=\"post\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/dons/liste.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

    $nbrows=12;
    if (! empty($conf->projet->enabled)) $nbrows++;

	// Ref
	print "<tr>".'<td>'.$langs->trans("Ref").'</td><td colspan="2">';
	print $form->showrefnav($don, 'rowid', $linkback, 1, 'rowid', 'ref', '');
	print '</td>';
	print '</tr>';

	// Date
	print '<tr><td width="25%">'.$langs->trans("Date").'</td><td>';
	print dol_print_date($don->date,"day");
	print "</td>";

    print '<td rowspan="'.$nbrows.'" valign="top" width="50%">'.$langs->trans("Comments").' :<br>';
	print nl2br($don->note_private).'</td></tr>';

    print "<tr>".'<td>'.$langs->trans("Amount").'</td><td>'.price($don->amount).' '.$langs->trans("Currency".$conf->currency).'</td></tr>';

	print "<tr><td>".$langs->trans("PublicDonation")."</td><td>";
	print yn($don->public);
	print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Company").'</td><td>'.$don->societe.'</td></tr>';
	print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td>'.$don->firstname.'</td></tr>';
	print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td>'.$don->lastname.'</td></tr>';
	print "<tr>".'<td>'.$langs->trans("Address").'</td><td>'.dol_nl2br($don->address).'</td></tr>';

	// Zip / Town
	print "<tr>".'<td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>'.$don->zip.($don->zip && $don->town?' / ':'').$don->town.'</td></tr>';

	// Country
	print "<tr>".'<td>'.$langs->trans("Country").'</td><td>'.$don->country.'</td></tr>';

	// EMail
	print "<tr>".'<td>'.$langs->trans("EMail").'</td><td>'.dol_print_email($don->email).'</td></tr>';

	// Payment mode
	print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
	print $form->form_modes_reglement(null, $don->modepaiementid,'none');
	print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$don->getLibStatut(4).'</td></tr>';

    // Project
    if (! empty($conf->projet->enabled))
    {
        print "<tr>".'<td>'.$langs->trans("Project").'</td><td>'.$don->projet.'</td></tr>';
    }

    // Other attributes
    $parameters=array('colspan' => ' colspan="1"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$don,$action);    // Note that $action and $object may have been modified by hook

	print "</table>\n";
	print "</form>\n";

	print "</div>";

	// TODO Gerer action emettre paiement
	$resteapayer = 0;


	/**
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?action=edit&rowid='.$don->id.'">'.$langs->trans('Modify').'</a></div>';

	if ($don->statut == 0)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$don->id.'&action=valid_promesse">'.$langs->trans("ValidPromess").'</a></div>';
	}

    if (($don->statut == 0 || $don->statut == 1) && $resteapayer == 0 && $don->paye == 0)
    {
        print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$don->id.'&action=set_cancel">'.$langs->trans("ClassifyCanceled")."</a></div>";
    }

	// TODO Gerer action emettre paiement
	if ($don->statut == 1 && $resteapayer > 0)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="paiement.php?rowid='.$don->id.'&action=create">'.$langs->trans("DoPayment")."</a></div>";
	}

	if ($don->statut == 1 && $resteapayer == 0 && $don->paye == 0)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$don->id.'&action=set_paid">'.$langs->trans("ClassifyPaid")."</a></div>";
	}

	if ($user->rights->don->supprimer)
	{
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="fiche.php?rowid='.$don->id.'&action=delete">'.$langs->trans("Delete")."</a></div>";
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
	$filename=dol_sanitizeFileName($don->id);
	$filedir=$conf->don->dir_output . '/' . get_exdir($filename,2);
	$urlsource=$_SERVER['PHP_SELF'].'?rowid='.$don->id;
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
?>
