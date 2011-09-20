<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/dons/modules_don.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/dons/class/don.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/paiement/class/paiement.class.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/lib/project.lib.php');

$langs->load("companies");
$langs->load("donations");
$langs->load("bills");

$mesg="";
$mesgs=array();

$don = new Don($db);
$donation_date=dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);


/*
 * Actions
 */

if ($_POST["action"] == 'update')
{
	if (! empty($_POST['cancel']))
	{
		Header("Location: fiche.php?rowid=".$_POST["rowid"]);
		exit;
	}

	$error=0;

    if (empty($donation_date))
    {
        $mesgs[]=$langs->trans("ErrorFieldRequired",$langs->trans("Date"));
        $_GET["action"] = "create";
        $error++;
    }

	if (! $_POST["amount"] > 0)
	{
		$mesgs[]=$langs->trans("ErrorFieldRequired",$langs->trans("Amount"));
		$_GET["action"] = "create";
		$error++;
	}

	if (! $error)
	{
		$don->id = $_POST["rowid"];
		$don->fetch($_POST["rowid"]);

		$don->prenom      = $_POST["prenom"];
		$don->nom         = $_POST["nom"];
		$don->societe     = $_POST["societe"];
		$don->adresse     = $_POST["adresse"];
		$don->amount      = price2num($_POST["amount"]);
		$don->cp          = $_POST["zipcode"];
		$don->ville       = $_POST["town"];
        $don->zip         = $_POST["zipcode"];
        $don->town        = $_POST["town"];
		$don->email       = $_POST["email"];
		$don->date        = $donation_date;
		$don->note        = $_POST["note"];
		$don->pays        = $_POST["pays"];
		$don->public      = $_POST["public"];
		$don->fk_project  = $_POST["projectid"];
		$don->note        = $_POST["comment"];
		$don->modepaiementid = $_POST["modepaiement"];

		if ($don->update($user) > 0)
		{
			Header("Location: fiche.php?rowid=".$don->id);
			exit;
		}
	}
}

if ($_POST["action"] == 'add')
{
	if (! empty($_POST['cancel']))
	{
		Header("Location: index.php");
		exit;
	}

	$error=0;

    if (empty($donation_date))
    {
        $mesgs[]=$langs->trans("ErrorFieldRequired",$langs->trans("Date"));
        $_GET["action"] = "create";
        $error++;
    }

	if (! $_POST["amount"] > 0)
	{
		$mesgs[]=$langs->trans("ErrorFieldRequired",$langs->trans("Amount"));
		$_GET["action"] = "create";
		$error++;
	}

	if (! $error)
	{
		$don->prenom      = $_POST["prenom"];
		$don->nom         = $_POST["nom"];
		$don->societe     = $_POST["societe"];
		$don->adresse     = $_POST["adresse"];
		$don->amount      = price2num($_POST["amount"]);
		$don->cp          = $_POST["zipcode"];
		$don->ville       = $_POST["town"];
        $don->zip         = $_POST["zipcode"];
        $don->town        = $_POST["town"];
		$don->email       = $_POST["email"];
		$don->date        = $donation_date;
		$don->note        = $_POST["note"];
		$don->pays        = $_POST["pays"];
		$don->public      = $_POST["public"];
		$don->fk_project  = $_POST["projectid"];
		$don->note        = $_POST["comment"];
		$don->modepaiementid = $_POST["modepaiement"];

		if ($don->create($user) > 0)
		{
			Header("Location: index.php");
			exit;
		}
	}
}

if ($_GET["action"] == 'delete')
{
	$don->delete($_GET["rowid"]);
	Header("Location: liste.php");
	exit;
}
if ($_POST["action"] == 'commentaire')
{
	$don->fetch($_POST["rowid"]);
	$don->update_note($_POST["commentaire"]);
	$_GET["rowid"] = $_POST["rowid"];
}
if ($_GET["action"] == 'valid_promesse')
{
	if ($don->valid_promesse($_GET["rowid"], $user->id) >= 0)
	{
		Header("Location: fiche.php?rowid=".$_GET["rowid"]);
		exit;
	}
    else $mesg=$don->error;
}
if ($_GET["action"] == 'set_cancel')
{
    if ($don->set_cancel($_GET["rowid"]) >= 0)
    {
        Header("Location: fiche.php?rowid=".$_GET["rowid"]);
        exit;
    }
    else $mesg=$don->error;
}
if ($_GET["action"] == 'set_paid')
{
	if ($don->set_paye($_GET["rowid"], $modepaiement) >= 0)
	{
		Header("Location: fiche.php?rowid=".$_GET["rowid"]);
		exit;
	}
    else $mesg=$don->error;
}
if ($_GET["action"] == 'set_encaisse')
{
	if ($don->set_encaisse($_GET["rowid"]) >= 0)
	{
        Header("Location: fiche.php?rowid=".$_GET["rowid"]);
		exit;
	}
    else $mesg=$don->error;
}

/*
 * Build doc
 */
if ($_REQUEST['action'] == 'builddoc')
{
	$donation = new Don($db);
	$donation->fetch($_GET['rowid']);

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
		Header('Location: '.$_SERVER["PHP_SELF"].'?rowid='.$donation->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
		exit;
	}
}


/*
 * View
 */

llxHeader('',$langs->trans("Donations"),'EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Subvenciones');

$html=new Form($db);
$formfile = new FormFile($db);
$htmlcompany = new FormCompany($db);


/* ************************************************************************** */
/*                                                                            */
/* Creation                                                                   */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create')
{
	print_fiche_titre($langs->trans("AddDonation"));

	dol_htmloutput_errors($mesg,$mesgs);

	print '<form name="add" action="fiche.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

	print '<input type="hidden" name="action" value="add">';

    $nbrows=11;
    if ($conf->projet->enabled) $nbrows++;

    // Date
	print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	$html->select_date($donation_date?$donation_date:-1,'','','','',"add",1,1);
	print '</td>';

    print '<td rowspan="'.$nbrows.'" valign="top">'.$langs->trans("Comments").' :<br>';
    print "<textarea name=\"comment\" wrap=\"soft\" cols=\"40\" rows=\"15\">".$_POST["comment"]."</textarea></td>";
    print "</tr>";

    // Amount
    print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" value="'.$_POST["amount"].'" size="10"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $html->selectyesno("public",isset($_POST["public"])?$_POST["public"]:1,1);
	print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" value="'.$_POST["societe"].'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="prenom" value="'.$_POST["prenom"].'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="nom" value="'.$_POST["nom"].'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
	print '<textarea name="adresse" wrap="soft" cols="40" rows="3">'.$_POST["adresse"].'</textarea></td></tr>';

    // Zip / Town
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
	print $htmlcompany->select_ziptown((isset($_POST["zipcode"])?$_POST["zipcode"]:$don->zip),'zipcode',array('town','selectpays_id','departement_id'),6);
    print ' ';
    print $htmlcompany->select_ziptown((isset($_POST["town"])?$_POST["town"]:$don->town),'town',array('zipcode','selectpays_id','departement_id'));
    print '</tr>';

	print "<tr>".'<td>'.$langs->trans("Country").'</td><td><input type="text" name="pays" value="'.$_POST["pays"].'" size="40"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" value="'.$_POST["email"].'" size="40"></td></tr>';

    print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";
    $html->select_types_paiements('', 'modepaiement', 'CRDT', 0, 1);
    print "</td></tr>\n";

	if ($conf->projet->enabled)
    {
        // Si module projet actif
        print "<tr><td>".$langs->trans("Project")."</td><td>";
        select_projects('',$_POST["projectid"],"projectid");
        print "</td></tr>\n";
    }

	print "</table>\n";
	print '<br><center><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';
	print "</form>\n";
}


/* ************************************************************ */
/*                                                              */
/* Fiche don en mode edition                                    */
/*                                                              */
/* ************************************************************ */

if ($_GET["rowid"] && $_GET["action"] == 'edit')
{
	$don->id = $_GET["rowid"];
	$don->fetch($_GET["rowid"]);

	$h=0;
	$head[$h][0] = DOL_URL_ROOT."/compta/dons/fiche.php?rowid=".$_GET["rowid"];
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
    if ($conf->projet->enabled) $nbrows++;

    // Date
	print "<tr>".'<td width="25%" class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	$html->select_date($don->date,'','','','',"update");
	print '</td>';

    print '<td rowspan="'.$nbrows.'" valign="top">'.$langs->trans("Comments").' :<br>';
    print "<textarea name=\"comment\" wrap=\"soft\" cols=\"40\" rows=\"15\">".$don->note."</textarea></td>";
    print "</tr>";

	// Amount
    print "<tr>".'<td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="amount" size="10" value="'.$don->amount.'"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PublicDonation")."</td><td>";
	print $html->selectyesno("public",1,1);
	print "</td>";
	print "</tr>\n";

	$langs->load("companies");
	print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.$don->societe.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="prenom" size="40" value="'.$don->prenom.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="nom" size="40" value="'.$don->nom.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
	print '<textarea name="adresse" wrap="soft" cols="40" rows="'.ROWS_3.'">'.$don->adresse.'</textarea></td></tr>';

    // Zip / Town
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
    print $htmlcompany->select_ziptown((isset($_POST["zipcode"])?$_POST["zipcode"]:$don->zip),'zipcode',array('town','selectpays_id','departement_id'),6);
    print ' ';
    print $htmlcompany->select_ziptown((isset($_POST["town"])?$_POST["town"]:$don->town),'town',array('zipcode','selectpays_id','departement_id'));
    print '</tr>';

	print "<tr>".'<td>'.$langs->trans("Country").'</td><td><input type="text" name="pays" size="40" value="'.$don->pays.'"></td></tr>';
	print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" size="40" value="'.$don->email.'"></td></tr>';

    print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";
    $html->select_types_paiements('', 'modepaiement', 'CRDT', 0, 1);
    print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$don->getLibStatut(4).'</td></tr>';

    // Project
    if ($conf->projet->enabled)
    {
        $langs->load('projects');
        print '<tr><td>'.$langs->trans('Project').'</td><td>';
        select_projects($soc->id, isset($_POST["projectid"])?$_POST["projectid"]:$don->fk_project, 'projectid');
        print '</td></tr>';
    }

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
if ($_GET["rowid"] && $_GET["action"] != 'edit')
{
	$don->id = $_GET["rowid"];
	$result=$don->fetch($_GET["rowid"]);


	$h=0;
	$head[$h][0] = DOL_URL_ROOT."/compta/dons/fiche.php?rowid=".$_GET["rowid"];
	$head[$h][1] = $langs->trans("Card");
	$hselected=$h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("Donation"), 0, 'generic');

	print "<form action=\"fiche.php\" method=\"post\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

    $nbrows=12;
    if ($conf->projet->enabled) $nbrows++;

	// Ref
	print "<tr>".'<td>'.$langs->trans("Ref").'</td><td colspan="2">';
	print $html->showrefnav($don,'rowid','',1,'rowid','ref','');
	print '</td>';
	print '</tr>';

	// Date
	print '<tr><td width="25%">'.$langs->trans("Date").'</td><td>';
	print dol_print_date($don->date,"day");
	print "</td>";

    print '<td rowspan="'.$nbrows.'" valign="top" width="50%">'.$langs->trans("Comments").' :<br>';
	print nl2br($don->note).'</td></tr>';

    print "<tr>".'<td>'.$langs->trans("Amount").'</td><td>'.price($don->amount).' '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	print "<tr><td>".$langs->trans("PublicDonation")."</td><td>";
	print yn($don->public);
	print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Company").'</td><td>'.$don->societe.'</td></tr>';
	print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td>'.$don->prenom.'</td></tr>';
	print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td>'.$don->nom.'</td></tr>';
	print "<tr>".'<td>'.$langs->trans("Address").'</td><td>'.dol_nl2br($don->adresse).'</td></tr>';

	// Zip / Town
	print "<tr>".'<td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>'.$don->cp.($don->cp && $don->ville?' / ':'').$don->ville.'</td></tr>';

	// Country
	print "<tr>".'<td>'.$langs->trans("Country").'</td><td>'.$don->pays.'</td></tr>';

	// EMail
	print "<tr>".'<td>'.$langs->trans("EMail").'</td><td>'.dol_print_email($don->email).'</td></tr>';

	// Payment mode
	print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
	print $don->modepaiement;
	print "</td></tr>\n";

	print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$don->getLibStatut(4).'</td></tr>';

    // Project
    if ($conf->projet->enabled)
    {
        print "<tr>".'<td>'.$langs->trans("Project").'</td><td>'.$don->projet.'</td></tr>';
    }

	print "</table>\n";
	print "</form>\n";

	print "</div>";

	// TODO Gerer action emettre paiement
	$resteapayer = 0;


	/**
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	print '<a class="butAction" href="fiche.php?action=edit&rowid='.$don->id.'">'.$langs->trans('Modify').'</a>';

	if ($don->statut == 0)
	{
		print '<a class="butAction" href="fiche.php?rowid='.$don->id.'&action=valid_promesse">'.$langs->trans("ValidPromess").'</a>';
	}

    if (($don->statut == 0 || $don->statut == 1) && $resteapayer == 0 && $don->paye == 0)
    {
        print "<a class=\"butAction\" href=\"fiche.php?rowid=$don->id&action=set_cancel\">".$langs->trans("ClassifyCanceled")."</a>";
    }

	// TODO Gerer action emettre paiement
	if ($don->statut == 1 && $resteapayer > 0)
	{
		print "<a class=\"butAction\" href=\"paiement.php?facid=$facid&action=create\">".$langs->trans("DoPayment")."</a>";
	}

	if ($don->statut == 1 && $resteapayer == 0 && $don->paye == 0)
	{
		print "<a class=\"butAction\" href=\"fiche.php?rowid=$don->id&action=set_paid\">".$langs->trans("ClassifyPaid")."</a>";
	}

	if ($user->rights->don->supprimer)
	{
		print "<a class=\"butActionDelete\" href=\"fiche.php?rowid=$don->id&action=delete\">".$langs->trans("Delete")."</a>";
	}
	else
	{
		print "<a class=\"butActionRefused\" href=\"#\">".$langs->trans("Delete")."</a>";
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



$db->close();

llxFooter();
?>
