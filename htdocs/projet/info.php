<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *      \file       htdocs/projet/info.php
 *      \ingroup    commande
 *		\brief      Page with info on project
 */

require __DIR__.'/../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

if (!$user->rights->projet->lire)	accessforbidden();

$langs->load("projects");

// Security check
$socid=0;
$id = GETPOST("id",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'projet',$id,'');



/*
 * View
 */

$title=$langs->trans("Project").' - '.$object->ref.' '.$object->name;
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->ref.' '.$object->name.' - '.$langs->trans("Info");
$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$title,$help_url);

$projet = new Project($db);
$projet->fetch($id);
$projet->info($id);
$soc = new Societe($db);
$soc->fetch($projet->socid);

$head = project_prepare_head($projet);

dol_fiche_head($head, 'info', $langs->trans("Project"), 0, ($object->public?'projectpub':'project'));


print '<table width="100%"><tr><td>';
dol_print_object_info($projet);
print '</td></tr></table>';

print '</div>';

llxFooter();
$db->close();
