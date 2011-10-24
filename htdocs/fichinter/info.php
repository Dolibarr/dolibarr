<?php
/* Copyright (C) 2005-2009  Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2009-2010  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011       Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/fichinter/info.php
 *	\ingroup    fichinter
 *	\brief      Page d'affichage des infos d'une fiche d'intervention
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/fichinter/class/fichinter.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fichinter.lib.php");

$langs->load('companies');
$langs->load("interventions");

$fichinterid = GETPOST("id");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $fichinterid, 'fichinter');


/*
*	View
*/

llxHeader();

$fichinter = new Fichinter($db);
$fichinter->fetch($fichinterid);

$societe = new Societe($db);
$societe->fetch($fichinter->socid);

$head = fichinter_prepare_head($fichinter);
dol_fiche_head($head, 'info', $langs->trans('InterventionCard'), 0, 'intervention');

$fichinter->info($fichinter->id);

print '<table width="100%"><tr><td>';
dol_print_object_info($fichinter);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter();
?>
