<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */
require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();


llxHeader();


$compta_mode = defined("COMPTA_MODE")?COMPTA_MODE:"RECETTES-DEPENSES";

if ($action == 'setcomptamode')
{
  $compta_mode = $HTTP_POST_VARS["compta_mode"];
  if (! dolibarr_set_const($db, "COMPTA_MODE",$compta_mode)) { print $db->error(); }
}


$form = new Form($db);
$typeconst=array('yesno','texte','chaine');


if ($HTTP_POST_VARS["action"] == 'update' || $HTTP_POST_VARS["action"] == 'add')
{
	if (! dolibarr_set_const($db, $HTTP_POST_VARS["constname"],$HTTP_POST_VARS["constvalue"],$typeconst[$HTTP_POST_VARS["consttype"]],0,isset($HTTP_POST_VARS["constnote"])?$HTTP_POST_VARS["constnote"]:''));
	{
	  	print $db->error();
	}
}


if ($_GET["action"] == 'delete')
{
	if (! dolibarr_del_const($db, $_GET["constname"]));
	{
	  	print $db->error();
	}
}



print_titre("Configuration du module Comptabilité");

print "<br>";

print '<table class="noborder" cellpadding="3" cellspacing="0" width=\"100%\">';

print '<form action="compta.php" method="post">';
print '<input type="hidden" name="action" value="setcomptamode">';
print '<tr class="liste_titre">';
print '<td>Option de tenue de comptabilité</td><td>Description</td>';
print '<td><input type="submit" value="Modifier"></td>';
print "</tr>\n";
print "<tr ".$bc[True]."><td width=\"200\"><input type=\"radio\" name=\"compta_mode\" value=\"RECETTES-DEPENSES\"".($compta_mode != "CREANCES-DETTES"?" checked":"")."> Option Recettes-Dépenses</td>";
print "<td colspan=\"2\">Dans ce mode, le CA est calculé sur la base des factures à l'état payé.\nLa validité des chiffres n'est donc assurée que si la tenue de la comptabilité passe rigoureusement par des entrées/sorties sur les comptes via des factures.\nDe plus, dans cette version, Dolibarr utilise la date de passage de la facture à l'état 'Validé' et non la date de passage à l'état 'Payé'.</td></tr>\n";
print "<tr ".$bc[False]."><td width=\"200\"><input type=\"radio\" name=\"compta_mode\" value=\"CREANCES-DETTES\"".($compta_mode == "CREANCES-DETTES"?" checked":"")."> Option Créances-Dettes</td>";
print "<td colspan=\"2\">Dans ce mode, le CA est calculé sur la base des factures validées. Qu'elles soient ou non payés, dès lors qu'elles sont dues, elles apparaissent dans le résultat.</td></tr>\n";
print "</form>";

print "</table>\n";

print "<br>\n";

$sql = "SELECT rowid, name, value, type, note FROM llx_const WHERE name like 'COMPTA_%' and name not in ('COMPTA_MODE')";
$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  $var=True;

  if ($num) { 
  	print '<table class="noborder" cellpadding="3" cellspacing="0" width=\"100%\">';
		print '<tr class="liste_titre">';
		print '<td>Autres option du module comptabilité</td><td>&nbsp;</td><td>&nbsp;</td><td>Description</td>';
		print '<td>&nbsp;</td>';
		print "</tr>\n";
  }

  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      $var=!$var;

      print '<form action="compta.php" method="POST">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="rowid" value="'.$rowid.'">';
      print '<input type="hidden" name="constname" value="'.$obj->name.'">';

      print "<tr $bc[$var] class=value><td>$obj->name</td>\n";

      print '<td>';
      if ($obj->type == 'yesno')
	{
	  $form->selectyesnonum('constvalue',$obj->value);
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte','chaine'),0);
	}
      elseif ($obj->type == 'texte')
	{
	  print '<textarea name="constvalue" cols="35" rows="5" wrap="soft">';
	  print $obj->value;
	  print "</textarea>\n";
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte','chaine'),1);
	}
      else
	{
	  print '<input type="text" size="30" name="constvalue" value="'.stripslashes($obj->value).'">';
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte','chaine'),2);
	}
      print '</td><td>';

      print '<input type="text" size="15" name="constnote" value="'.stripslashes(nl2br($obj->note)).'">';
      print '</td><td>';
      print '<input type="Submit" value="Update" name="Button"> &nbsp; ';
      print '<a href="compta.php?constname='.$obj->name.'&action=delete">'.img_delete().'</a>';
      print "</td></tr>\n";

      print '</form>';

      $i++;
    }
    
  if ($num) {
	 print "</table>\n";
  }

}

	














llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
