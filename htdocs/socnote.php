<?PHP
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
require("./pre.inc.php");

if ($_POST["action"] == 'add') {
  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET note='$note' WHERE idp=$socid";
  $result = $db->query($sql);
}
/*
 *
 */
llxHeader();

if ($socid > 0) {

  $societe = new Societe($db, $socid);
  $societe->fetch($socid);
  /*
   *
   */

      $head[0][0] = DOL_URL_ROOT.'/soc.php?socid='.$_GET["socid"];
      $head[0][1] = "Fiche société";
      $h = 1;

      if ($societe->client==1)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$societe->id;
	  $head[$h][1] = 'Fiche client';
	  $h++;
	}
      
      if ($societe->client==2)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$societe->id;
	  $head[$h][1] = 'Fiche prospect';
	  $h++;
	}
      if ($societe->fournisseur)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$societe->id;
	  $head[$h][1] = 'Fiche fournisseur';
	  $h++;
	}

      if ($conf->compta->enabled) {
          $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$societe->id;
          $head[$h][1] = 'Fiche compta';
          $h++;
      }

      $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$societe->id;
      $head[$h][1] = 'Note';
      $a = $h;
      $h++;

      if ($user->societe_id == 0)
	{
	  $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$societe->id;
	  $head[$h][1] = 'Documents';
	  $h++;
	}
      
      $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$societe->id;
      $head[$h][1] = 'Notifications';
      
      dolibarr_fiche_head($head, $a);


  print_titre($societe->nom);

  print "<form method=\"post\" action=\"socnote.php?socid=$societe->id\">";

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
  print '<tr><td width="50%" valign="top">';
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print "<textarea name=\"note\" cols=\"60\" rows=\"10\">$societe->note</textarea><br>";
  print '</td><td width="50%" valign="top">'.nl2br($societe->note).'</td>';
  print "</td></tr>";
  print "</table>";
  print '<input type="submit" value="Enregistrer"></form>';
}

$db->close();

llxFooter();
?>
