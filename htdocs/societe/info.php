<?php
/* Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
        \file       htdocs/societe/info.php
        \ingroup    societe
		\brief      Page des informations d'une societe
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");

$langs->load("companies");
$langs->load("other");

// Sécurité accés client
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($socid == '') accessforbidden();
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

// Protection restriction commercial
if (!$user->rights->commercial->client->voir && $socid && !$user->societe_id > 0)
{
        $sql = "SELECT sc.fk_soc, s.client";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE sc.fk_soc = ".$socid." AND sc.fk_user = ".$user->id." AND s.client = 1";

        if ( $db->query($sql) )
        {
          if ( $db->num_rows() == 0) accessforbidden();
        }
}

llxHeader();


/*
 * Visualisation de la fiche
 *
 */

$soc = new Societe($db);
$soc->id = $socid;
$soc->fetch($socid);
$soc->info($socid);

/*
 * Affichage onglets
 */
$head = societe_prepare_head($soc);

dolibarr_fiche_head($head, 'info', $soc->nom);



print '<table width="100%"><tr><td>';
dolibarr_print_object_info($soc);
print '</td></tr></table>';

print '</div>';

// Juste pour éviter bug IE qui réorganise mal div précédents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
