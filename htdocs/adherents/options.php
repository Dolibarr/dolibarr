<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
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
 */

/**     \file       htdocs/adherents/options.php
        \ingroup    adherent
		\brief      Page de configuratin des champs optionnels
		\version    $Id$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");

$langs->load("members");

$adho = new AdherentOptions($db);
$form = new Form($db);

if ($_POST["action"] == 'add' && $user->rights->adherent->configurer) 
{
    if ($_POST["button"] != $langs->trans("Cancel")) 
    {
        // Type et taille non encore pris en compte => varchar(255)
        if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_POST['attrname']))
        {
        	$adho->create($_POST['attrname'],$_POST['type'],$_POST['size']);
	        if (isset($_POST['label']))
	        {
	        	$adho->create_label($_POST['attrname'],$_POST['label']);
	        }
		    Header("Location: ".$_SERVER["PHP_SELF"]);
    		exit;
        }
        else
        {
        	$langs->load("errors");
        	$mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
			$_GET["action"] = 'create';        	
        }
    }
}

if ($_POST["action"] == 'update' && $user->rights->adherent->configurer) 
{
    if ($_POST["button"] != $langs->trans("Cancel")) 
    {
        if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_POST['attrname']))
        {
        	$adho->update($_POST['attrname'],$_POST['type'],$_POST['size']);
	        if (isset($_POST['label']))
	        {
	        	$adho->update_label($_POST['attrname'],$_POST['label']);
	        }
		    Header("Location: ".$_SERVER["PHP_SELF"]);
		    exit;
        }
        else
        {
        	$langs->load("errors");
        	$mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
        }
    } 
}

# Suppression attribut
if ($_GET["action"] == 'delete' && $user->rights->adherent->configurer)
{
  	if(isset($_GET["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_GET["attrname"]))
  	{
    	$adho->delete($_GET["attrname"]);
  		Header("Location: ".$_SERVER["PHP_SELF"]);
  		exit;
  	}
    else
    {
        $langs->load("errors");
    	$mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
    }
}



/*
 * View
 */

llxHeader();



print_titre($langs->trans("OptionalFieldsSetup"));
print '<br>';

if ($mesg) print '<div class="error">'.$mesg.'</div><br>';

$array_options=$adho->fetch_name_optionals();
$array_label=$adho->fetch_name_optionals_label();

print "<table class=\"noborder\" width=\"100%\">";

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("AttributeCode").'</td>';
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
      print "<td align=\"right\"><a href=\"options.php?action=edit&attrname=$key\">".img_edit()."</a>";
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
if ($_GET["action"] != 'create') 
{
	print '<div class="tabsAction">';
	print "<a class=\"butAction\" href=\"options.php?action=create\">".$langs->trans("NewAttribute")."</a>";
	print "</div>";
}


/* ************************************************************************** */
/*                                                                            */
/* Création d'un champ optionnel                                              */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create')
{
	print "<br>";
	
	print_titre($langs->trans('NewAttribute'));

	print '<form action="options.php" method="post">';
	print '<table class="border" width="100%">';

	print '<input type="hidden" name="action" value="add">';

	print '<tr><td>'.$langs->trans("Label").'</td><td class="valeur"><input type="text" name="label" size="40"></td></tr>';  
	print '<tr><td>'.$langs->trans("AttributeCode").' ('.$langs->trans("AlphaNumOnlyCharsAndNoSpace").')</td><td class="valeur"><input type="text" name="attrname" size="10"></td></tr>';
	print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">';
	$form->select_array('type',array('varchar'=>$langs->trans('String'),
	'text'=>$langs->trans('Text'),
	'int'=>$langs->trans('Int'),
	'date'=>$langs->trans('Date'),
	'datetime'=>$langs->trans('DateAndTime')));
	print '</td></tr>';
	print '<tr><td>Taille</td><td><input type="text" name="size" size="5" value="255"></td></tr>';  

	print '<tr><td colspan="2" align="center"><input type="submit" name="button" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
	print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
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

  print_titre($langs->trans("FieldEdition",$_GET["attrname"]));
  
  /*
   * formulaire d'edition
   */
  print '<form method="post" action="options.php?attrname='.$_GET["attrname"].'">';
  print '<input type="hidden" name="attrname" value="'.$_GET["attrname"].'">';
  print '<input type="hidden" name="action" value="update">';
  print '<table class="border" width="100%">';

  print '<tr><td>'.$langs->trans("Label").'</td><td class="valeur"><input type="text" name="label" size="40" value="'.$adho->attribute_label[$_GET["attrname"]].'"></td></tr>';  
  print '<tr><td>'.$langs->trans("AttributeName").'</td><td class="valeur">'.$_GET["attrname"].'&nbsp;</td></tr>';
  list($type,$size)=preg_split('/\(|\)/',$adho->attribute_name[$_GET["attrname"]]);
  print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">';
  $form->select_array('type',array('varchar'=>$langs->trans('String'),
  'text'=>$langs->trans('Text'),
  'int'=>$langs->trans('Int'),
  'date'=>$langs->trans('Date'),
  'datetime'=>$langs->trans('DateAndTime')),$type);
  print '</td></tr>';

  print '<tr><td>'.$langs->trans("Size").'</td><td class="valeur"><input type="text" name="size" size="5" value="'.$size.'"></td></tr>';  
  print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
  print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
  print '</table>';
  print "</form>";
  
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
