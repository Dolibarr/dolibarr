<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
    \file       htdocs/index.php
    \brief      Page accueil par defaut
    \version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('');


// Simule le menu par défaut sur Home
$_GET["mainmenu"]="home";


llxHeader();

$userstring=$user->prenom . ' ' . $user->login .' ('.$user->nom.')';
print_fiche_titre($langs->trans("WelcomeString",dolibarr_print_date(mktime(),"%A %d %B %Y"),$userstring), '<a href="about.php">'.$langs->trans("About").'</a>');

if (defined("MAIN_MOTD") && strlen(trim(MAIN_MOTD)))
{
  print "<br>".nl2br(MAIN_MOTD);
}

print "<br>\n";


/*
 * Affichage des boites
 *
 */
include_once("./boxes.php");
$infobox=new InfoBox($db);
$boxes=$infobox->listboxes("0");       // 0 = valeur pour la page accueil

$NBCOLS=2;      // Nombre de colonnes pour les boites
print '<table width="100%">';
for ($ii=0, $ni=sizeof($boxes); $ii<$ni; $ii++)
{
  if ($ii % $NBCOLS == 0) print "<tr>\n";
  print '<td valign="top" width="50%">';

  // Affichage boite ii
  include_once(DOL_DOCUMENT_ROOT."/includes/boxes/".$boxes[$ii].".php");
  $box=new $boxes[$ii]();
  $box->loadBox();
  $box->showBox();

  print "</td>";
  if ($ii % $NBCOLS == ($NBCOLS-1)) print "</tr>\n";
}
if ($ii % $NBCOLS == ($NBCOLS-1)) print "</tr>\n";
print "</table>";


$db->close();

llxFooter('$Date$ - $Revision$');
?>










