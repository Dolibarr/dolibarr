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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

$langs->load("projects");

if (GETPOST('actioncode','array'))
{
    $actioncode=GETPOST('actioncode','array',3);
    if (! count($actioncode)) $actioncode='0';
}
else
{
    $actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode")=='0'?'0':(empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE)?'':$conf->global->AGENDA_DEFAULT_FILTER_TYPE));
}

// Security check
$socid=0;
$id = GETPOST("id",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'projet',$id,'');

if (!$user->rights->projet->lire)	accessforbidden();



/*
 *	Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
{
    $actioncode='';
}



/*
 * View
 */

$title=$langs->trans("Project").' - '.$object->ref.' '.$object->name;
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->ref.' '.$object->name.' - '.$langs->trans("Info");
$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$title,$help_url);

$object = new Project($db);
$object->fetch($id);
$object->info($id);

$head = project_prepare_head($object);

dol_fiche_head($head, 'agenda', $langs->trans("Project"), 0, ($object->public?'projectpub':'project'));


print '<table width="100%"><tr><td>';

dol_print_object_info($object, 1);

print '</td></tr></table>';

dol_fiche_end();


// Actions buttons

$out='';
$permok=$user->rights->agenda->myactions->create;
if ($permok)
{
    $out.='&projectid='.$object->id;
}


print '<div class="tabsAction">';

if (! empty($conf->agenda->enabled))
{
    if (! empty($user->rights->agenda->myactions->create) || ! empty($user->rights->agenda->allactions->create))
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out.'">'.$langs->trans("AddAction").'</a>';
    }
    else
    {
        print '<a class="butActionRefused" href="#">'.$langs->trans("AddAction").'</a>';
    }
}

print '</div>';


if (!empty($object->id))
{
    print load_fiche_titre($langs->trans("ActionsOnProject"),'','');
    
    // List of actions on element
    /*include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
    $formactions=new FormActions($db);
    $somethingshown=$formactions->showactions($object,'project',0);*/
    
    // List of todo actions
    //show_actions_todo($conf,$langs,$db,$object,null,0,$actioncode);
    
    // List of done actions
    //show_actions_done($conf,$langs,$db,$object,null,0,$actioncode);
    
    // List of all actions
    show_actions_done($conf,$langs,$db,$object,null,0,$actioncode, '');
}


llxFooter();
$db->close();
