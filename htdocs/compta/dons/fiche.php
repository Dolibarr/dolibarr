<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	    \file       htdocs/compta/dons/fiche.php
 *		\ingroup    don
 *		\brief      Page de fiche de don
 *		\version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/dons/modules_don.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/don.class.php");
require_once(DOL_DOCUMENT_ROOT."/paiement.class.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/lib/project.lib.php');

$langs->load("companies");
$langs->load("donations");
$langs->load("bills");

$mesg="";


/*
 * Actions
 */
 
if ($_POST["action"] == 'update')
{
    if ($_POST["amount"] > 0)
    {

        $don = new Don($db);
        $don->id = $_POST["rowid"];
        $don->fetch($_POST["rowid"]);

        $don->prenom      = $_POST["prenom"];
        $don->nom         = $_POST["nom"];
        $don->societe     = $_POST["societe"];
        $don->adresse     = $_POST["adresse"];
        $don->amount      = $_POST["amount"];
        $don->cp          = $_POST["cp"];
        $don->ville       = $_POST["ville"];
        $don->email       = $_POST["email"];
        $don->date        = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
        $don->note        = $_POST["note"];
        $don->pays        = $_POST["pays"];
        $don->public      = $_POST["public"];
        $don->projetid    = $_POST["projetid"];
        $don->note        = $_POST["comment"];
        $don->modepaiementid = $_POST["modepaiement"];

        if ($don->update($user) > 0)
        {
            Header("Location: fiche.php?rowid=".$don->id);
            exit;
        }
    }
    else
    {
        $mesg="Montant non défini";
    }
}

if ($_POST["action"] == 'add')
{
    if ($_POST["amount"] > 0)
    {
        $don = new Don($db);

        $don->prenom      = $_POST["prenom"];
        $don->nom         = $_POST["nom"];
        $don->societe     = $_POST["societe"];
        $don->adresse     = $_POST["adresse"];
        $don->amount      = $_POST["amount"];
        $don->cp          = $_POST["cp"];
        $don->ville       = $_POST["ville"];
        $don->email       = $_POST["email"];
        $don->date        = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
        $don->note        = $_POST["note"];
        $don->pays        = $_POST["pays"];
        $don->public      = $_POST["public"];
        $don->projetid    = $_POST["projetid"];
        $don->note        = $_POST["comment"];
        $don->modepaiementid = $_POST["modepaiement"];

        if ($don->create($user) > 0)
        {
            Header("Location: index.php");
            exit;
        }
    }
    else
    {
        $mesg=$langs->trans("ErrorFieldRequired",$langs->trans("Amount"));
        $_GET["action"] = "create";
    }
}

if ($_GET["action"] == 'delete')
{
    $don = new Don($db);
    $don->delete($_GET["rowid"]);
    Header("Location: liste.php");
    exit;
}
if ($_POST["action"] == 'commentaire')
{
    $don = new Don($db);
	$don->fetch($_POST["rowid"]);
    $don->update_note($_POST["commentaire"]);
    $_GET["rowid"] = $_POST["rowid"];
}
if ($_GET["action"] == 'valid_promesse')
{
    $don = new Don($db);
    if ($don->valid_promesse($_GET["rowid"], $user->id))
    {
        Header("Location: fiche.php?rowid=".$_GET["rowid"]);
        exit;
    }
}
if ($_GET["action"] == 'set_payed')
{
    $don = new Don($db);
    if ($don->set_paye($_GET["rowid"], $modepaiement))
    {
        Header("Location: fiche.php?rowid=".$_GET["rowid"]);
        exit;
    }
}
if ($_GET["action"] == 'set_encaisse')
{
    $don = new Don($db);
    if ($don->set_encaisse($_GET["rowid"]))
    {
        Header("Location: liste.php");
        exit;
    }
}

/*
 * Générer ou regénérer le document
 */
if ($_REQUEST['action'] == 'builddoc')
{
	$donation = new Don($db, 0, $_GET['rowid']);
	$donation->fetch($_GET['rowid']);

	if ($_REQUEST['model'])
	{
		$donation->setDocModel($user, $_REQUEST['model']);
	}

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=don_create($db, $donation->id, '', $donation->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$result);
		exit;
	}
	else
	{
		Header ('Location: '.$_SERVER["PHP_SELF"].'?rowid='.$donation->id.'#builddoc');
		exit;
	}
}


/*
 * View
 */

llxHeader();

$html=new Form($db);
$formfile = new FormFile($db);


/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche don                                                   */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create')
{
    print_titre($langs->trans("AddDonation"));
    
    print '<form name="add" action="fiche.php" method="post">';
    print '<table class="border" width="100%">';
    
    print '<input type="hidden" name="action" value="add">';
    
    print '<tr><td>'.$langs->trans("Date").'</td><td>';
    $html->select_date('','','','','',"add");
    print '</td>';
    
    $nbrows=11;
    if ($conf->projet->enabled) $nbrows++;
    
    print '<td rowspan="'.$nbrows.'" valign="top">'.$langs->trans("Comments").' :<br>';
    print "<textarea name=\"comment\" wrap=\"soft\" cols=\"40\" rows=\"15\"></textarea></td></tr>";

    print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";
	$html->select_types_paiements('', 'modepaiement', 'CRDT', 0);
    print "</td></tr>\n";
    
    if ($conf->projet->enabled)
    {
        // Si module projet actif
        print "<tr><td>".$langs->trans("Project")."</td><td>";
        select_projects('','','',"projetid");
        print "</td></tr>\n";
    }
    
    print "<tr><td>".$langs->trans("PublicDonation")."</td><td>";
    print $html->selectyesno("public",1,1);
    print "</td></tr>\n";
    
    print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="prenom" size="40"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="nom" size="40"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
    print '<textarea name="adresse" wrap="soft" cols="40" rows="3"></textarea></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td><input type="text" name="cp" size="8"> <input type="text" name="ville" size="40"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Country").'</td><td><input type="text" name="pays" size="40"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" size="40"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Amount").'</td><td><input type="text" name="amount" size="10"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    print "<tr>".'<td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
    print "</table>\n";
    print "</form>\n";
} 


/* ************************************************************ */
/*                                                              */
/* Fiche don en mode edition                                    */
/*                                                              */
/* ************************************************************ */

if ($_GET["rowid"] && $_GET["action"] == 'edit')
{
    $don = new Don($db);
    $don->id = $_GET["rowid"];
    $don->fetch($_GET["rowid"]);
    
    $h=0;
    $head[$h][0] = DOL_URL_ROOT."/compta/dons/fiche.php?rowid=".$_GET["rowid"];
    $head[$h][1] = $langs->trans("Donation");
    $hselected=$h;
    $h++;
    
    dolibarr_fiche_head($head, $hselected, $langs->trans("Ref").": ".$_GET["rowid"]);
    
    print '<form name="update" action="fiche.php" method="post">';
    print '<table class="border" width="100%">';
    
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="rowid" value="'.$don->id.'">';
    
    print "<tr>".'<td>'.$langs->trans("Date").'</td><td>';
    $html->select_date($don->date,'','','','',"update");
    print '</td>';
    
    $nbrows=12;
    if ($conf->projet->enabled) $nbrows++;
    
    print '<td rowspan="'.$nbrows.'" valign="top">'.$langs->trans("Comments").' :<br>';
    print "<textarea name=\"comment\" wrap=\"soft\" cols=\"40\" rows=\"15\">".$don->note."</textarea></td></tr>";
    
    if ($conf->projet->enabled)
    {
        print "<tr><td>".$langs->trans("Project")."</td><td><select name=\"projetid\">\n";
        $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."don_projet";
        $sql.= " ORDER BY rowid";
        if ($db->query($sql))
        {
            $num = $db->num_rows();
            $i = 0;
            while ($i < $num)
            {
                $objopt = $db->fetch_object();
                print "<option value=\"$objopt->rowid\">$objopt->libelle</option>\n";
                $i++;
            }
        }
        else
        {
            dolibarr_print_error($db);
        }
        print "</select><br>";
        print "</td></tr>\n";
    }
    
    print "<tr><td>".$langs->trans("PublicDonation")."</td><td>";
    print $html->selectyesno("public",1,1);
    print "</td>";
    print "</tr>\n";
    
    $langs->load("companies");
    print "<tr>".'<td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.$don->societe.'"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td><input type="text" name="prenom" size="40" value="'.$don->prenom.'"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td><input type="text" name="nom" size="40" value="'.$don->nom.'"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Address").'</td><td>';
    print '<textarea name="adresse" wrap="soft" cols="40" rows="'.ROWS_3.'">'.$don->adresse.'</textarea></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td><input type="text" name="cp" size="8" value="'.$don->cp.'"> <input type="text" name="ville" size="40" value="'.$don->ville.'"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Country").'</td><td><input type="text" name="pays" size="40" value="'.$don->pays.'"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("EMail").'</td><td><input type="text" name="email" size="40" value="'.$don->email.'"></td></tr>';
    print "<tr>".'<td>'.$langs->trans("Amount").'</td><td><input type="text" name="amount" size="10" value="'.$don->amount.'"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    
    print "<tr><td>".$langs->trans("PaymentMode")."</td><td>\n";
	$html->select_types_paiements('', 'modepaiement', 'CRDT', 0);
    print "</td></tr>\n";

    print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$don->getLibStatut(4).'</td></tr>';
    
    print "<tr>".'<td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
    
    print "</table>\n";
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
    $don = new Don($db);
    $don->id = $_GET["rowid"];
    $don->fetch($_GET["rowid"]);
    
    
    $h=0;
    $head[$h][0] = DOL_URL_ROOT."/compta/dons/fiche.php?rowid=".$_GET["rowid"];
    $head[$h][1] = $langs->trans("Donation");
    $hselected=$h;
    $h++;
    
    dolibarr_fiche_head($head, $hselected, $langs->trans("Ref").": ".$_GET["rowid"]);
    
    print "<form action=\"fiche.php\" method=\"post\">";
    print '<table class="border" width="100%">';
    
    print "<tr><td>".$langs->trans("Date")."</td><td>";
    print dolibarr_print_date($don->date,"day");
    print "</td>";
    
    $nbrows=12;
    if ($conf->projet->enabled) $nbrows++;
    
    print '<td rowspan="'.$nbrows.'" valign="top" width="50%">'.$langs->trans("Comments").' :<br>';
    print nl2br($don->note).'</td></tr>';
    
    if ($conf->projet->enabled)
    {
        print "<tr>".'<td>'.$langs->trans("Project").'</td><td>'.$don->projet.'</td></tr>';
    }
    
    print "<tr><td>".$langs->trans("PublicDonation")."</td><td>";
    print $yn[$don->public];
    print "</td></tr>\n";
    
    print "<tr>".'<td>'.$langs->trans("Company").'</td><td>'.$don->societe.'</td></tr>';
    print "<tr>".'<td>'.$langs->trans("Firstname").'</td><td>'.$don->prenom.'</td></tr>';
    print "<tr>".'<td>'.$langs->trans("Lastname").'</td><td>'.$don->nom.'</td></tr>';
    print "<tr>".'<td>'.$langs->trans("Address").'</td><td>'.nl2br($don->adresse).'</td></tr>';
    print "<tr>".'<td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>'.$don->cp.' '.$don->ville.'</td></tr>';
    print "<tr>".'<td>'.$langs->trans("Country").'</td><td>'.$don->pays.'</td></tr>';
    print "<tr>".'<td>'.$langs->trans("EMail").'</td><td>'.$don->email.'</td></tr>';
    print "<tr>".'<td>'.$langs->trans("Amount").'</td><td>'.price($don->amount).' '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
    print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
    print $don->modepaiement;
    print "</td></tr>\n";

    print "<tr>".'<td>'.$langs->trans("Status").'</td><td>'.$don->getLibStatut(4).'</td></tr>';
    
    print "</table>\n";
    print "</form>\n";
    
    print "</div>";


    /**
     * Barre d'actions
     */
    print '<div class="tabsAction">';

    print '<a class="butAction" href="fiche.php?action=edit&rowid='.$don->id.'">'.$langs->trans('Modify').'</a>';
	
    if ($don->statut == 0)
    {
      print '<a class="butAction" href="fiche.php?rowid='.$don->id.'&action=valid_promesse">'.$langs->trans("ValidPromess").'</a>';
    }

    // \todo Gérer action émettre paiement
    if ($don->statut == 1 && $resteapayer > 0) 
    {
      print "<a class=\"butAction\" href=\"paiement.php?facid=$facid&action=create\">".$langs->trans("DoPayment")."</a>";
    }

    if ($don->statut == 1 && abs($resteapayer) == 0 && $don->paye == 0) 
    {
      print "<a class=\"butAction\" href=\"fiche.php?rowid=$don->id&action=set_payed\">".$langs->trans("ClassifyPayed")."</a>";
    }

    if ($don->statut == 0) 
    {
      print "<a class=\"butActionDelete\" href=\"fiche.php?rowid=$don->id&action=delete\">".$langs->trans("Delete")."</a>";
    }

    print "</div>";


	print '<table width="100%"><tr><td width="50%" valign="top">';

	/*
	 * Documents générés
	 */
	$filename=sanitizeFileName($don->id);
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

llxFooter('$Date$ - $Revision$');
?>
