<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  
  $head[0][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
  $head[0][1] = "Proposition commerciale : $propal->ref";
  $h = 1;
  $a = 0;
  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
  $head[$h][1] = "Note";
  $h++;
  $head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
  $head[$h][1] = "Info";
  $a=$h;
  
  dolibarr_fiche_head($head, $a, $societe->nom);
  
  $propal->info($propal->id);
  dolibarr_print_object_info($propal);


  /* TODO A FINIR */
  print "  /* TODO A FINIR */<br>";
  

  $validor = new User($db, $obj->fk_user_valid);
  $validor->fetch('');
  $cloturor = new User($db, $obj->fk_user_cloture);
  $cloturor->fetch('');
  
  print 'Suivi des actions<br>';
  print '<table cellspacing=0 border=1 cellpadding=3>';
  print '<tr><td>&nbsp;</td><td>Nom</td><td>Date</td></tr>';
  print '<tr><td>Création</td><td>'.$author->fullname.'</td>';
  print '<td>'.$obj->datec.'</td></tr>';
  
  print '<tr><td>Validation</td><td>'.$validor->fullname.'&nbsp;</td>';
  print '<td>'.$obj->date_valid.'&nbsp;</td></tr>';
  
  print '<tr><td>Cloture</td><td>'.$cloturor->fullname.'&nbsp;</td>';
  print '<td>'.$obj->date_cloture.'&nbsp;</td></tr>';      
  print '</table>';
  

  print "<br></div>";
 
  $db->close();
 
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
