<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/*!	\file htdocs/admin/system/gen-commande.php
		\brief      Générateur de données aléatoires pour les produits et sociétés
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

llxHeader();

$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product"; $productsid = array();
if ($db->query($sql)) {
  $num = $db->num_rows(); $i = 0;	
  while ($i < $num) {      $row = $db->fetch_row($i);      $productsid[$i] = $row[0];      $i++; } }

$sql = "SELECT idp FROM ".MAIN_DB_PREFIX."societe"; $societesid = array();
if ($db->query($sql)) { $num = $db->num_rows(); $i = 0;	
while ($i < $num) { $row = $db->fetch_row($i);      $societesid[$i] = $row[0];      $i++; } } else { print "err"; }

$dates = array (mktime(12,0,0,1,3,2003),
		mktime(12,0,0,1,9,2003),
		mktime(12,0,0,2,13,2003),
		mktime(12,0,0,2,23,2003),
		mktime(12,0,0,3,30,2003),
		mktime(12,0,0,4,3,2003),
		mktime(12,0,0,4,3,2003),
		mktime(12,0,0,5,9,2003),
		mktime(12,0,0,5,1,2003),
		mktime(12,0,0,5,13,2003),
		mktime(12,0,0,5,19,2003),
		mktime(12,0,0,5,23,2003),
		mktime(12,0,0,6,3,2003),
		mktime(12,0,0,6,19,2003),
		mktime(12,0,0,6,24,2003),
		mktime(12,0,0,7,3,2003),
		mktime(12,0,0,7,9,2003),
		mktime(12,0,0,7,23,2003),
		mktime(12,0,0,7,30,2003),
		mktime(12,0,0,8,9,2003),
		mktime(12,0,0,9,23,2003),
		mktime(12,0,0,10,3,2003)
		);

require DOL_DOCUMENT_ROOT."/commande/commande.class.php";

$com = new Commande($db);

$com->soc_id         = 4;
$com->date_commande  = $dates[rand(1, sizeof($dates)-1)];
$com->note           = $HTTP_POST_VARS["note"];
$com->source         = 1;
$com->projetid       = 0;
$com->remise_percent = 0;

$pidrand = rand(1, sizeof($productsid)-1);
$com->add_product($productsid[rand(1, sizeof($productsid)-1)],rand(1,11),rand(1,6),rand(0,20));
  
print $com->create($user) . " " . $com->date_commande;
  




llxFooter();
?>
