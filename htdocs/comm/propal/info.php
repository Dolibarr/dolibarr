<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/comm/propal/info.php
 *      \ingroup    propale
 *      \brief      Page d'affichage des infos d'une proposition commerciale
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");

$langs->load('propal');
$langs->load('compta');

$id = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'propale', $id, 'propal');


/*
 *	View
 */

llxHeader();

$propal = new Propal($db);
$propal->fetch($_GET["id"]);

$societe = new Societe($db);
$societe->fetch($propal->socid);

$head = propal_prepare_head($propal);
dol_fiche_head($head, 'info', $langs->trans('Proposal'), 0, 'propal');

$propal->info($propal->id);

print '<table width="100%"><tr><td>';
dol_print_object_info($propal);
print '</td></tr></table>';

print '</div>';


$db->close();

llxFooter();
?>
