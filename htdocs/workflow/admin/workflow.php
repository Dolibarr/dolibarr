<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 */

/**
 *	\file       htdocs/admin/workflow.php
 *	\ingroup    workflow
 *	\brief      Page d'administration/configuration du module Workflow
 *	\version    $Id$
 */

require("../../main.inc.php");
//require_once(DOL_DOCUMENT_ROOT."/workflow/class/workflow.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$langs->load("admin");

if (!$user->admin || $user->entity)
accessforbidden();

//$wf = new Workflow($db);

/*
 * Actions
 */



/*
 * View
 */

llxHeader('',$langs->trans("WorkflowSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("WorkflowSetup"),$linkback,'setup');

print '<br>';

$template_dir = DOL_DOCUMENT_ROOT.'/workflow/tpl/';


llxFooter('$Date$ - $Revision$');
?>