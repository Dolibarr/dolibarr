<?php
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
 *
 */

/*! \file htdocs/comm/propal/info.php
        \ingroup    propale
		\brief      Page d'affichage des infos d'une proposition commerciale
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("propal");

$user->getrights('propale');
if (!$user->rights->propale->lire)
  accessforbidden();

require("../../propal.class.php");
/*
 *
 *
 */
llxHeader();

if ($_GET["propalid"])
{
  $propal = new Propal($db);
  $propal->fetch($_GET["propalid"]);
  
  $societe = new Societe($db);
  $societe->fetch($propal->soc_id);
  $h=0;
  
  $head[$h][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans("Card");
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans("Note");
  $h++;

  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
  $head[$h][1] = $langs->trans("Info");
  $hselected=$h;
  $h++;
  
  dolibarr_fiche_head($head, $hselected, $langs->trans("Prop").": $propal->ref");
  
  $propal->info($propal->id);

  print '<table width="100%"><tr><td>';
  dolibarr_print_object_info($propal);
  print '</td></tr></table>';

  print "<br></div>";
 
  $db->close();
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
