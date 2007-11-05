<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/contact/info.php
        \ingroup    societe
		\brief      Onglet info d'un contact
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/contact.lib.php");

$user->getrights('commercial');

$langs->load("companies");

// Protection quand utilisateur externe
$contactid = isset($_GET["id"])?$_GET["id"]:'';

$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

// Protection restriction commercial
if ($contactid && ! $user->rights->commercial->client->voir)
{
    $sql = "SELECT sc.fk_soc, sp.fk_soc";
    $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."socpeople as sp";
    $sql .= " WHERE sp.rowid = ".$contactid;
    if (! $user->rights->commercial->client->voir && ! $socid)
    {
    	$sql .= " AND sc.fk_soc = sp.fk_soc AND sc.fk_user = ".$user->id;
    }
    if ($socid) $sql .= " AND sp.fk_soc = ".$socid;

    $resql=$db->query($sql);
    if ($resql)
    {
    	if ($db->num_rows() == 0) accessforbidden();
    }
    else
    {
    	dolibarr_print_error($db);
    }
}


/*
 * Fiche info
 */

llxHeader();


$contact = new Contact($db);
$contact->fetch($_GET["id"], $user);


/*
 * Affichage onglets
 */
$head = contact_prepare_head($contact);

dolibarr_fiche_head($head, 'info', $langs->trans("Contact"));


/*
 * Visualisation de la fiche
 *
 */

print '<table width="100%"><tr><td>';
$contact->info($_GET["id"]);
print '</td></tr></table>';
  
if ($contact->socid > 0)
{
  $societe = new Societe($db);
  $societe->fetch($contact->socid);
  
  print $langs->trans("Company").' : '.$societe->getNomUrl(0).'<br>';
}

dolibarr_print_object_info($contact);

print "</div>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
