<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*! \file htdocs/adherents/options.php
        \ingroup    adherent
		\brief      Page de configuratin des champs optionnels
		\version    $Revision$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");

$adho = new AdherentOptions($db);
$form = new Form($db);

if ($_POST["action"] == 'add' && $user->admin) 
{
    if ($_POST["button"] != $langs->trans("Cancel")) {
        // Type et taille non encore pris en compte => varchar(255)
        if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-]*$/",$_POST['attrname'])){
        $adho->create($_POST['attrname'],$_POST['type'],$_POST['size']);
        }
        if (isset($_POST['label'])){
        $adho->create_label($_POST['attrname'],$_POST['label']);
        }
    }
    Header("Location: ".$_SERVER["PHP_SELF"]);
}

if ($_POST["action"] == 'update' && $user->admin) 
{
    if ($_POST["button"] != $langs->trans("Cancel")) {
        if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-]*$/",$_POST['attrname'])){
        $adho->update($_POST['attrname'],$_POST['type'],$_POST['size']);
        }
        if (isset($_POST['label'])){
        $adho->update_label($_POST['attrname'],$_POST['label']);
        }
    } 
    Header("Location: ".$_SERVER["PHP_SELF"]);
}

# Suppression attribut
if ($_GET["action"] == 'delete' && $user->admin)
{
  if(isset($_GET["attrname"]) && preg_match("/^\w[a-zA-Z0-9-]*$/",$_GET["attrname"])){
    $adho->delete($_GET["attrname"]);
  }
  Header("Location: ".$_SERVER["PHP_SELF"]);
}


llxHeader();



print_titre("Configuration des champs optionnels");
print '<br>';

/* ************************************************************************** */
/*                                                                            */
/*                                                                            */
/*                                                                            */
/* ************************************************************************** */

$array_options=$adho->fetch_name_optionals();
$array_label=$adho->fetch_name_optionals_label();

print "<table class=\"noborder\">";

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Label").'</td>';
print "<td>Nom de l'attribut</td>";
print '<td>'.$langs->trans("Type").'</td><td width="80">&nbsp;</td>';
print "</tr>\n";
  
if (sizeof($array_options)>0) 
{
  $var=True;
  foreach($adho->attribute_name as $key => $value)
    {
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td>".$adho->attribute_label[$key]."&nbsp;</td>\n";
      print "<td>$key</td>\n";
      print "<td>$value</td>\n";
      print "<td align=\"center\"><a href=\"options.php?action=edit&attrname=$key\">".img_edit()."</a>";
      print "&nbsp; <a href=\"options.php?action=delete&attrname=$key\">".img_delete()."</a></td>\n";
      print "</tr>";
      //      $i++;
    }

}

print "</table>";

/*
 * Barre d'actions
 *
 */
print '<div class="tabsAction">';
print "<a class=\"tabAction\" href=\"options.php?action=create\">Nouvel attribut</a>";
print "</div>";


/* ************************************************************************** */
/*                                                                            */
/* Création d'un champ optionnel                                              */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create') {

  print_titre("Nouvel attribut");
  
  print "<form action=\"options.php\" method=\"post\">";
  print '<table class="border" width="100%">';
  
  print '<input type="hidden" name="action" value="add">';

  print '<tr><td>'.$langs->trans("Label").'</td><td class="valeur"><input type="text" name="label" size="40"></td></tr>';  
  print '<tr><td>Nom de l\'attribut (pas d\'espace et uniquement des carateres alphanumeriques)</td><td class="valeur"><input type="text" name="attrname" size="40"></td></tr>';  
  print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">';
  $form->select_array('type',array('varchar'=>'chaine',
				   'text'=>'texte',
				   'int'=>'entier',
				   'date'=>'date',
				   'datetime'=>'date et heure'));
  print '</td></tr>';
  print '<tr><td>Taille</td><td><input type="text" name="size" size="5" value="255"></td></tr>';  
  
  print '<tr><td colspan="2" align="center"><input type="submit" name="button" value="'.$langs->trans("Save").'"> &nbsp; ';
  print '<input type="submit" name="button" value="'.$langs->trans("Cancel").'"></td></tr>';
  print "</form>\n";
  print "</table>\n";
  
      
} 
/* ************************************************************************** */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* ************************************************************************** */
if ($_GET["attrname"] && $_GET["action"] == 'edit')
{

  print_titre("Edition du champ ".$_GET["attrname"]);
  
  /*
   * formulaire d'edition
   */
  print '<form method="post" action="options.php?attrname='.$_GET["attrname"].'">';
  print '<input type="hidden" name="attrname" value="'.$_GET["attrname"].'">';
  print '<input type="hidden" name="action" value="update">';
  print '<table class="border" width="100%">';

  print '<tr><td>'.$langs->trans("Label").'</td><td class="valeur"><input type="text" name="label" size="40" value="'.$adho->attribute_label[$_GET["attrname"]].'"></td></tr>';  
  print '<tr><td>Nom de l\'attribut</td><td class="valeur">'.$_GET["attrname"].'&nbsp;</td></tr>';
  list($type,$size)=preg_split('/\(|\)/',$adho->attribute_name[$_GET["attrname"]]);
  print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">';
  $form->select_array('type',array('varchar'=>'chaine',
				   'text'=>'texte',
				   'int'=>'entier',
				   'date'=>'date',
				   'datetime'=>'date et heure'),$type);
  print '</td></tr>';

  print '<tr><td>'.$langs->trans("Size").'</td><td class="valeur"><input type="text" name="size" size="5" value="'.$size.'"></td></tr>';  
  print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'"> &nbsp; ';
  print '<input type="submit" name="button" value="'.$langs->trans("Cancel").'"></td></tr>';
  print '</table>';
  print "</form>";
  
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
