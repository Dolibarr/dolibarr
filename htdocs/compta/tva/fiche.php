<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");
require("../../tva.class.php");


$mesg = '';

if ($_POST["action"] == 'add' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
  $tva = new Tva($db);

  $tva->add_payement(mktime(12,0,0,
			    $_POST["datevmonth"],
			    $_POST["datevday"],
			    $_POST["datevyear"]
			    ),
		     mktime(12,0,0,
			    $_POST["datepmonth"],
			    $_POST["datepday"],
			    $_POST["datepyear"]
			    ),
		     $_POST["amount"]
		     );
  Header ( "Location: reglement.php");
}

llxHeader();

/*
 *
 *
 */
$html = new Form($db);
if ($action == 'create')
{
  print "<form action=\"fiche.php\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print '<div class="titre">Nouveau réglement TVA</div><br>';
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
  print "<tr>";
  print '<td>Date de paiement</td><td>';
  print $html->select_date("","datev");
  print '</td></tr>';
  print '<td>Date de valeur</td><td>';
  print $html->select_date("","datep");
  print '</td></tr>';
  print '<tr><td>Montant</td><td><input name="amount" size="10" value=""></td></tr>';    
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

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
