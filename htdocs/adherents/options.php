<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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
//require("../adherent.class.php");
//require("../adherent_type.class.php");
require($GLOBALS["DOCUMENT_ROOT"]."/adherents/adherent_options.class.php");
//require("../cotisation.class.php");
//require("../paiement.class.php");


$db = new Db();
$adho = new AdherentOptions($db);

if ($HTTP_POST_VARS["action"] == 'add' && $user->admin) 
{

  //$adho->libelle     = $HTTP_POST_VARS["attrname"];
  //$adho->cotisation  = $HTTP_POST_VARS["cotisation"];
  //$adho->commentaire = $HTTP_POST_VARS["comment"];
  if (preg_match("/^\w[a-zA-Z0-9-]*$/",$_POST['attrname'])){
    $adho->create($_POST['attrname']);
  }
  Header("Location: $PHP_SELF");
}

if ($HTTP_POST_VARS["action"] == 'update' && $user->admin) 
{

  //  $adho->libelle     = $HTTP_POST_VARS["libelle"];
  //  $adho->cotisation  = $HTTP_POST_VARS["cotisation"];
  //  $adho->commentaire = $HTTP_POST_VARS["comment"];
  
  //  if ($adho->update($user->id) ) 
  //    {	  
  //    }
  Header("Location: $PHP_SELF");
 
}

if ($_POST["action"] == 'delete' && $user->admin)
{
  if(isset($_POST["attrname"])){
    $adho->delete($_POST["attrname"]);
  }
  Header("Location: $PHP_SELF");
}
if ($action == 'commentaire')
{
  $don = new Don($db);
  $don->set_commentaire($rowid,$HTTP_POST_VARS["commentaire"]);
  $action = "edit";
}



llxHeader();

print_titre("Champs optionnels");

/* ************************************************************************** */
/*                                                                            */
/*                                                                            */
/*                                                                            */
/* ************************************************************************** */

$array_options=$adho->fetch_name_optionals();

if (sizeof($array_options)>0) 
{
  print "<TABLE border=\"0\" cellspacing=\"0\" cellpadding=\"4\">";
  
  print '<TR class="liste_titre">';
  print "<td>Libelle</td>";
  print "<td>Nom de l'attribut</td>";
  print "<td>type</td><td>&nbsp;</td>";
  print "</TR>\n";
  
  $var=True;
  foreach($adho->attribute_name as $key => $value)
    {
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD>&nbsp;</td>\n";
      print "<TD>$key</td>\n";
      print "<TD>$value</TD>\n";
      print "<TD><a href=\"$PHP_SELF?action=edit&attrname=$key\">Editer</TD>\n";
      print "</tr>";
      //      $i++;
    }
  print "</table>";
}
print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";

/*
 * Case 1
 */

print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?action=create\">Nouvel attribut</a>]</td>";

/*
 * Case 2
 */

print "<td align=\"center\" width=\"25%\">-</td>";

/*
 * Case 3
 */
print "<td align=\"center\" width=\"25%\">-</td>";

/*
 * Case 4
 */

print "<td align=\"center\" width=\"25%\">-</td>";

print "</tr></table></form><p>";



/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche don                                                   */
/*                                                                            */
/* ************************************************************************** */


if ($action == 'create') {

  print_titre("Nouvel attribut");
  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print '<input type="hidden" name="action" value="add">';

  print '<tr><td>Libellé</td><td><input type="text" name="libelle" size="40"></td></tr>';  
  print '<tr><td>Nom de l\'attribut (pas d\'espace et uniquement des carateres alphanumeriques)</td><td><input type="text" name="attrname" size="40"></td></tr>';  

  print '<tr><td>Type</td><td>';

  print '<select name="type">';
  print '<option value="varchar">chaine</option>';
  print '<option value="integer">entier</option>';
  print '<option value="date">date</option>';
  print '<option value="datetime">date et heure</option>';
  print '</select>';
  print '</td></tr>';

  print '<tr><td>taille</td><td><input type="text" name="size" size="5" value="255"></td></tr>';  
  
  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  print "</form>\n";
  print "</table>\n";
  
      
} 
/* ************************************************************************** */
/*                                                                            */
/* Edition de la fiche                                                        */
/*                                                                            */
/* ************************************************************************** */
if (isset($attrname) && $attrname != '' && $action == 'edit')
{

  print_titre("Edition du champ $attrname");
  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

  print '<tr><td>Libellé</td><td class="valeur">&nbsp;</td></tr>';
  print '<tr><td>Nom de l\'attribut</td><td class="valeur">'.$attrname.'&nbsp;</td></tr>';
  print '<tr><td>Type</td><td class="valeur">'.$adho->attribute_name[$attrname].'&nbsp;</td></tr>';

  print "</table>\n";

  
  /*
   *
   *
   *
   */
  print '<form method="post" action="'.$PHP_SELF.'?attrname='.$attrname.'">';
  print '<input type="hidden" name="attrname" value="'.$attrname.'">';
  print '<input type="hidden" name="action" value="update">';
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

  print '<tr><td>Libellé</td><td class="valeur"><input type="text" name="libelle" size="40" value=" "></td></tr>';  
  print '<tr><td>Nom de l\'attribut</td><td class="valeur">'.$attrname.'&nbsp;</td></tr>';
  print '<tr><td>Type</td><td class="valeur"><input type="text" name="type" size="40" value="'.$adho->attribute_name[$attrname].'"></td></tr>';
  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"</td></tr>';
  print '</table>';
  print "</form>";
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
