<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 
/*!	    \file       htdocs/socnote.php
		\brief      Fichier onglet notes liées à la société
		\ingroup    societe
		\version    $Revision$
*/
 
require("./pre.inc.php");

$langs->load("companies");


if ($_POST["action"] == 'add') {
  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET note='".$_POST["note"]."' WHERE idp=".$_POST["socid"];
  $result = $db->query($sql);

  $_GET["socid"]=$_POST["socid"];   // Pour retour sur fiche
}
/*
 *
 */
llxHeader();

if ($_GET["socid"] > 0) {

  $societe = new Societe($db, $_GET["socid"]);
  $societe->fetch($_GET["socid"]);
  /*
   *
   */
      $h=0;

      $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$societe->id;
      $head[$h][1] = $langs->trans("Company");
	  $h++;

      if ($societe->client==1)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$societe->id;
	  $head[$h][1] = $langs->trans("Customer");
	  $h++;
	}
      
      if ($societe->client==2)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$societe->id;
	  $head[$h][1] = $langs->trans("Prospect");
	  $h++;
	}
      if ($societe->fournisseur)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$societe->id;
	  $head[$h][1] = $langs->trans("Supplier");
	  $h++;
	}

      if ($conf->compta->enabled) {
          $langs->load("compta");
          $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$societe->id;
          $head[$h][1] = $langs->trans("Accountancy");
          $h++;
      }

      $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$societe->id;
      $head[$h][1] = $langs->trans("Note");
      $hselected = $h;
      $h++;

      if ($user->societe_id == 0)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$societe->id;
	  $head[$h][1] = $langs->trans("Documents");
	  $h++;
	}
      
      $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$societe->id;
      $head[$h][1] = $langs->trans("Notifications");
	  $h++;
      
      dolibarr_fiche_head($head, $hselected, $societe->nom);


  print_titre($societe->nom);

  print "<form method=\"post\" action=\"socnote.php\">";

  print '<table class="noborder" width="100%">';
  print '<tr><td width="50%" valign="top">';
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print "<input type=\"hidden\" name=\"socid\" value=\"".$societe->id."\">";
  print "<textarea name=\"note\" cols=\"60\" rows=\"10\">".$societe->note."</textarea><br>";
  print '</td><td width="50%" valign="top">'.nl2br($societe->note).'</td>';
  print "</td></tr>";
  print "</table>";
  print '<input type="submit" value="'.$langs->trans("Save").'"></form>';
}

print '<br>';

$db->close();

llxFooter();
?>
