<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/compta/tva/fiche.php
        \ingroup    tax
		\brief      Page des règlements de TVA
		\version    $Id$
*/

require("./pre.inc.php");
require("../../tva.class.php");

$langs->load("compta");

$id=$_GET["id"];


$mesg = '';


/**
 * Action ajout paiement tva
 */
if ($_POST["action"] == 'add' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
    $tva = new Tva($db);
    
    $db->begin();
    
    $tva->accountid=$_POST["accountid"];
    $tva->paymenttype=$_POST["paiementtype"];
    $tva->datev=mktime(12,0,0, $_POST["datevmonth"], $_POST["datevday"], $_POST["datevyear"]);
    $tva->datep=mktime(12,0,0, $_POST["datepmonth"], $_POST["datepday"], $_POST["datepyear"]);
    $tva->amount=$_POST["amount"];
	$tva->label=$_POST["label"];

    $ret=$tva->addPayment($user);
    if ($ret > 0)
    {
        $db->commit();
        Header ("Location: reglement.php");
        exit;
    }
    else
    {
        $db->rollback();
        $message='<div class="error">'.$tva->error.'</div>';
        $_GET["action"]="create";
    }
}



llxHeader();

$html = new Form($db);

// Formulaire saisie tva
if ($_GET["action"] == 'create')
{
    print "<form name='add' action=\"fiche.php\" method=\"post\">\n";
    print '<input type="hidden" name="action" value="add">';
    
    print_fiche_titre($langs->trans("NewVATPayment"));
      
    if ($message) print $message;
    
    print '<table class="border" width="100%">';
    
    print "<tr>";
    print '<td>'.$langs->trans("DatePayment").'</td><td>';
    print $html->select_date("","datev",'','','','add');
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
    print $html->select_date("","datep",'','','','add');
    print '</td></tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td><input name="label" size="40" value="'.$langs->trans("VATPayment").'"></td></tr>';    

	// Amount
	print '<tr><td>'.$langs->trans("Amount").'</td><td><input name="amount" size="10" value=""></td></tr>';    

    if ($conf->banque->enabled)
    {
		print '<tr><td>'.$langs->trans("Account").'</td><td>';
        $html->select_comptes($charge->accountid,"accountid",0,"courant=1",1);  // Affiche liste des comptes courant
        print '</td></tr>';

	    print '<tr><td>'.$langs->trans("Type").'</td><td>';
	    $html->select_types_paiements($charge->paiementtype, "paiementtype");
	    print "</td>\n";
	}
        
	print '<tr><td>&nbsp;</td><td><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
    print '</table>';
    print '</form>';      
}


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

if ($id)
{
    print_fiche_titre($langs->trans("VATPayment"));
      
    $vatpayment = new Tva($db);

	if ($vatpayment->fetch($id) > 0)
	{
		if ($mesg) print $mesg.'<br>';

		$h = 0;
		$head[$h][0] = DOL_URL_ROOT.'/compta/tva/fiche.php?id='.$tva->id;
		$head[$h][1] = $langs->trans('Card');
		$head[$h][2] = 'card';
		$h++;

		dolibarr_fiche_head($head, 'card', $langs->trans("VATPayment"));


	    print '<table class="border" width="100%">';
	    
	    print "<tr>";
	    print '<td>'.$langs->trans("DatePayment").'</td><td>';
	    print dolibarr_print_date($vatpayment->date);
	    print '</td></tr>';

	    print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
	    print $html->select_date("","datep",'','','','add');
	    print '</td></tr>';

	    print '<tr><td>'.$langs->trans("Type").'</td><td>';
	    $html->select_types_paiements($charge->paiementtype, "paiementtype");
	    print "</td>\n";
	   
	    if ($conf->banque->enabled)
	    {
	        print '<tr><td>'.$langs->trans("Account").'</td><td>';
	        $html->select_comptes($charge->accountid,"accountid",0,"courant=1",1);  // Affiche liste des comptes courant
	        print '</td></tr>';
	    }

	    print '<tr><td>'.$langs->trans("Amount").'</td><td><input name="amount" size="10" value=""></td></tr>';
	}
	
}


$db->close();

llxFooter('$Date$ - $Revision$');

?>
