<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
        \file       htdocs/product/stock/fiche.php
        \ingroup    stock
        \brief      Page fiche entrepot
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");
$langs->load("stocks");


$mesg = '';



if ($_POST["action"] == 'add')
{
    $entrepot = new Entrepot($db);
    
    $entrepot->ref         = trim($_POST["ref"]);
    $entrepot->libelle     = trim($_POST["libelle"]);
    $entrepot->description = trim($_POST["desc"]);
    $entrepot->statut      = $_POST["statut"];
    
    if ($entrepot->libelle) {
        $id = $entrepot->create($user);
        Header("Location: fiche.php?id=$id");
    }
    else {
        $mesg="<div class='error'>".$langs->trans("ErrorWarehouseLabelRequired")."</div>"; 
        $_GET["action"]="create";   // Force retour sur page création
    }
}

if ($_POST["action"] == 'update' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
  $entrepot = new Entrepot($db);
  if ($entrepot->fetch($_GET["id"]))
    {
      $entrepot->libelle     = $_POST["libelle"];
      $entrepot->description = $_POST["desc"];
      $entrepot->statut      = $_POST["statut"];
      
      if ( $entrepot->update($_GET["id"], $user))
	{
	  $_GET["cancel"] = '';
	  $mesg = 'Fiche mise à jour';
	}
      else
	{
	  $_GET["cancel"] = 're-edit';
	  $mesg = 'Fiche non mise à jour !' . "<br>" . $entrepot->mesg_error;
	}
    }
  else
    {
      $_GET["cancel"] = 're-edit';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $entrepot->mesg_error;
    }
}



llxHeader("","",$langs->trans("WarehouseCard"));


if ($_GET["cancel"] == $langs->trans("Cancel"))
{
  $_GET["action"] = '';
}


/*
 * Affichage fiche en mode création
 *
 */

if ($_GET["action"] == 'create')
{
  print "<form action=\"fiche.php\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="type" value="'.$type.'">'."\n";
  print_titre($langs->trans("NewWarehouse"));
  
  if ($mesg) {
    print $mesg;
  }
  
  print '<table class="border" width="100%">';
  print '<tr><td width="20%">'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value=""></td></tr>';
  print '<tr><td width="20%" valign="top">'.$langs->trans("Description").'</td><td>';
  print '<textarea name="desc" rows="8" cols="50">';
  print "</textarea></td></tr>";
  print '<tr><td width="20%">'.$langs->trans("Status").'</td><td>';
  print '<select name="statut">';
  print '<option value="0" selected>'.$langs->trans("WarehouseClosed").'</option><option value="1">'.$langs->trans("WarehouseOpened").'</option>';
  print '</td></tr>';
  print '<tr><td>&nbsp;</td><td><input type="submit" value="'.$langs->trans("Create").'"></td></tr>';
  print '</table>';
  print '</form>';      
}
else
{
  if ($_GET["id"])
    {
      if ($_GET["action"] <> 're-edit')
	{
	  $entrepot = new Entrepot($db);
	  $result = $entrepot->fetch($_GET["id"]);
	}

      if ( $result )
	{ 
	  if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	    {
	      print_fiche_titre('Fiche entrepot', $mesg);
      
	      print '<table class="border" width="100%">';
	      print '<tr><td width="20%">'.$langs->trans("Label").'</td><td>'.$entrepot->libelle.'</td>';
	      print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>'.nl2br($entrepot->description).'</td></tr>';
	      print '<tr><td width="20%">'.$langs->trans("Status").'</td><td>'.$entrepot->statuts[$entrepot->statut].'</td></tr>';
	      print '<tr><td valign="top">Nb de produits</td><td>';
	      print $entrepot->nb_products();
	      print "</td></tr>";
	      print "</table>";
	    }
	}

    
      if (($_GET["action"] == 'edit' || $_GET["action"] == 're-edit') && 1)
	{
	  print_fiche_titre('Edition de la fiche entrepot', $mesg);

	  print "<form action=\"fiche.php?id=$entrepot->id\" method=\"post\">\n";
	  print '<input type="hidden" name="action" value="update">';
	  
	  print '<table class="border" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans("Label").'</td><td colspan="2"><input name="libelle" size="40" value="'.$entrepot->libelle.'"></td></tr>';
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="2">';
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $entrepot->description;
	  print "</textarea></td></tr>";
	  print '<tr><td width="20%">'.$langs->trans("Status").'</td><td colspan="2">';
	  print '<select name="statut">';
      print '<option value="0" '.($entrepot->statut == 0?"selected":"").'>'.$langs->trans("WarehouseClosed").'</option>';
      print '<option value="1" '.($entrepot->statut == 0?"":"selected").'>'.$langs->trans("WarehouseOpened").'</option>';
      print '</select>';
	  print '</td></tr>';

	  print "<tr>".'<td colspan="3" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
	  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	  print '</table>';
	  print '</form>';
	}
    }
  else
    {
      dolibarr_print_error($db);
    }
}


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "<br><div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{
  print "<a class=\"tabAction\" href=\"fiche.php?action=edit&id=$entrepot->id\">".$langs->trans("Edit")."</a>";
}

print "</div>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
