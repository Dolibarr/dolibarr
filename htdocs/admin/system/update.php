<?php
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
 *
 */

/*!	\file htdocs/admin/system/update.php
		\brief      Page de mise a jour Dolibarr
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");

$user->getrights('facture');

print "user " . $user->rights->facture->lire . " !";

if (!$user->rights->facture->lire)
  accessforbidden();

require("../../facture.class.php");
require("../../propal.class.php");

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}
/*
 *
 */	
print_titre($langs->trans("SystemUpdate"));
$err = 0;
/*
 * Factures
 */
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture";
if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $row = $db->fetch_row($i);
      $facture = new Facture($db);
      if ( $facture->fetch($row[0]) )
	{
	  if ( $facture->updateprice($row[0]) > 0 )
	    {
	      print "(ok $row[0])";
	    }
	  else
	    {
	      print "Erreur #2";
	      $err++;
	    }
	}
      else
	{
	  print "Erreur #3";
	  $err++;
	}
      $i++;
    }
  $db->free();
}
else
{
  print "Erreur #1";
  $err++;
}

$sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'MAIN_NEED_UPDATE'";
if (! $db->query($sql))
{
  print "Erreur #100";
  $err++;
}

$db->close();


if ($err == 0)
{
  print '<br><b>'.$langs->trans("SystemSuccessfulyUpdated").'</b>';
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
