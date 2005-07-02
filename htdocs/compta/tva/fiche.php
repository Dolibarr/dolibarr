<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 *
 */

/**
	    \file       htdocs/compta/tva/fiche.php
		\brief      Page des règlements de TVA
		\version    $Revision$
*/

require("./pre.inc.php");
require("../../tva.class.php");

$langs->load("compta");


$mesg = '';


/**
 * Action ajout paiement tva
 */
if ($_POST["action"] == 'add' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
    $tva = new Tva($db);
    
    $db->begin();
    
    $tva->label = $langs->trans("VATPayment");
    $tva->accountid=$_POST["accountid"];
    $tva->paymenttype=$_POST["paiementtype"];
    $tva->datev=mktime(12,0,0, $_POST["datevmonth"], $_POST["datevday"], $_POST["datevyear"]);
    $tva->datep=mktime(12,0,0, $_POST["datepmonth"], $_POST["datepday"], $_POST["datepyear"]);
    $tva->amount=$_POST["amount"];

    $ret=$tva->add_payement($user);
    if ($ret > 0)
    {
        $db->commit();
        Header ("Location: reglement.php");
    }
    else
    {
        $db->rollback();
        $message=$langs->trans("Error");
        $_GET["action"]="create";
    }
}



llxHeader();

$html = new Form($db);

// Formulaire saisie tva
if ($_GET["action"] == 'create')
{
    print "<form action=\"fiche.php\" method=\"post\">\n";
    print '<input type="hidden" name="action" value="add">';
    
    print_fiche_titre($langs->trans("NewVATPayment"));
      
    if ($message) print '<br>'.$message.'</br>';
    
    print '<table class="border" width="100%">';
    
    print "<tr>";
    print '<td>'.$langs->trans("DatePayment").'</td><td>';
    print $html->select_date("","datev");
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("DateValue").'</td><td>';
    print $html->select_date("","datep");
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Type").'</td><td>';
    $html->select_types_paiements($charge->paiementtype, "paiementtype");
    print "</td>\n";
   
    print '<tr><td>Compte à créditer :</td><td>';
    $html->select_comptes($charge->accountid, "accountid", 0, "courant=1");  // Affiche liste des comptes courant
    print '</td></tr>';
    
    print '<tr><td>'.$langs->trans("Amount").'</td><td><input name="amount" size="10" value=""></td></tr>';    
    print '<tr><td>&nbsp;</td><td><input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
    print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
    print '</table>';
    print '</form>';      
}


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

// Aucune action

$db->close();

llxFooter('$Date$ - $Revision$');

?>
