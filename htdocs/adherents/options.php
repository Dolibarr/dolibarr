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
require(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");

$adho = new AdherentOptions($db);
$form = new Form($db);

if ($HTTP_POST_VARS["action"] == 'add' && $user->admin) 
{
  // type et taille non encore pris en compte => varchar(255)
  if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-]*$/",$_POST['attrname'])){
    $adho->create($_POST['attrname'],$_POST['type'],$_POST['size']);
  }
  if (isset($_POST['label'])){
    $adho->create_label($_POST['attrname'],$_POST['label']);
  }
  Header("Location: $PHP_SELF");
}

if ($HTTP_POST_VARS["action"] == 'update' && $user->admin) 
{
  //if ($adho->update($user->id) ) 
  //    {	  
  //    }
  if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-]*$/",$_POST['attrname'])){
    $adho->update($_POST['attrname'],$_POST['type'],$_POST['size']);
  }
  if (isset($_POST['label'])){
    $adho->update_label($_POST['attrname'],$_POST['label']);
  }
  Header("Location: $PHP_SELF");
 
}

if ($action == 'delete' && $user->admin)
{
  if(isset($attrname) && preg_match("/^\w[a-zA-Z0-9-]*$/",$attrname)){
    $adho->delete($attrname);
  }
  Header("Location: $PHP_SELF");
}

llxHeader();

print_titre("Configuration des champs optionnels");

/* ************************************************************************** */
/*                                                                            */
/*                                                                            */
/*                                                                            */
/* ************************************************************************** */

$array_options=$adho->fetch_name_optionals();
$array_label=$adho->fetch_name_optionals_label();
if (sizeof($array_options)>0) 
{
  print "<TABLE border=\"0\" cellspacing=\"0\" cellpadding=\"4\">";
  
  print '<TR class="liste_titre">';
  print "<td>Libelle</td>";
  print "<td>Nom de l'attribut</td>";
  print "<td>type</td><td>&nbsp;</td><td>&nbsp;</td>";
  print "</TR>\n";
  
  $var=True;
  foreach($adho->attribute_name as $key => $value)
    {
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD>".$adho->attribute_label[$key]."&nbsp;</td>\n";
      print "<TD>$key</td>\n";
      print "<TD>$value</TD>\n";
      print "<TD><a href=\"$PHP_SELF?action=edit&attrname=$key\">Editer</TD>\n";
      print "<TD><a href=\"$PHP_SELF?action=delete&attrname=$key\">Supprimer</TD>\n";
      print "</tr>";
      //      $i++;
    }
  print "</table>";
}

    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';
    print "<a class=\"tabAction\" href=\"$PHP_SELF?action=create\">Nouvel attribut</a>";
    print "</div>";



/* ************************************************************************** */
/*                                                                            */
/* Création d'un champ optionnel                                              */
/*                                                                            */
/* ************************************************************************** */


if ($action == 'create') {

  print_titre("Nouvel attribut");
  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print '<input type="hidden" name="action" value="add">';

  print '<tr><td>Libellé</td><td class="valeur"><input type="text" name="label" size="40"></td></tr>';  
  print '<tr><td>Nom de l\'attribut (pas d\'espace et uniquement des carateres alphanumeriques)</td><td class="valeur"><input type="text" name="attrname" size="40"></td></tr>';  

  print '<tr><td>Type (non pris en compte)</td><td class="valeur">';

  $form->select_array('type',array('varchar'=>'chaine',
				   'text'=>'texte',
				   'integer'=>'entier',
				   'date'=>'date',
				   'datetime'=>'date et heure'));
  /*
  print '<select name="type">';
  print '<option value="varchar">chaine</option>';
  print '<option value="text">texte</option>';
  print '<option value="integer">entier</option>';
  print '<option value="date">date</option>';
  print '<option value="datetime">date et heure</option>';
  print '</select>';
  */
  print '</td></tr>';

  print '<tr><td>taille</td><td><input type="text" name="size" size="5" value="255"></td></tr>';  
  
  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  print "</form>\n";
  print "</table>\n";
  
      
} 
/* ************************************************************************** */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* ************************************************************************** */
if (isset($attrname) && $attrname != '' && $action == 'edit')
{

  print_titre("Edition du champ $attrname");
  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

  print '<tr><td>Libellé</td><td class="valeur">'.$adho->attribute_label[$attrname].'&nbsp;</td></tr>';
  print '<tr><td>Nom de l\'attribut</td><td class="valeur">'.$attrname.'&nbsp;</td></tr>';
  print '<tr><td>Type</td><td class="valeur">'.$adho->attribute_name[$attrname].'&nbsp;</td></tr>';

  print "</table>\n";

  
  /*
   * formulaire d'edition
   */
  print '<form method="post" action="'.$PHP_SELF.'?attrname='.$attrname.'">';
  print '<input type="hidden" name="attrname" value="'.$attrname.'">';
  print '<input type="hidden" name="action" value="update">';
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

  print '<tr><td>Libellé</td><td class="valeur"><input type="text" name="label" size="40" value="'.$adho->attribute_label[$attrname].'"></td></tr>';  
  print '<tr><td>Nom de l\'attribut</td><td class="valeur">'.$attrname.'&nbsp;</td></tr>';
  list($type,$size)=preg_split('/\(|\)/',$adho->attribute_name[$attrname]);
  print '<tr><td>Type (non pris en compte)</td><td class="valeur">';
  $form->select_array('type',array('varchar'=>'chaine',
				   'text'=>'texte',
				   'integer'=>'entier',
				   'date'=>'date',
				   'datetime'=>'date et heure'),$type);
  print '</td></tr>';
  //  print '<tr><td>Type (non pris en compte)</td><td class="valeur"><input type="text" name="type" size="40" value="'.$adho->attribute_name[$attrname].'"></td></tr>';
  print '<tr><td>taille</td><td class="valeur"><input type="text" name="size" size="5" value="'.$size.'"></td></tr>';  
  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"</td></tr>';
  print '</table>';
  print "</form>";
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
