<?php
/* Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->load("projects");

$id     = GETPOST('id','int');
$ref    = GETPOST('ref','alpha');
$socid  = GETPOST('socid','int');
$action = GETPOST('action','alpha');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield","alpha");
$sortorder = GETPOST("sortorder");
$page = GETPOST("page");
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
if (! $sortfield) $sortfield="a.datep,a.id";
if (! $sortorder) $sortorder="DESC";
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (GETPOST('actioncode','array'))
{
    $actioncode=GETPOST('actioncode','array',3);
    if (! count($actioncode)) $actioncode='0';
}
else
{
    $actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode")=='0'?'0':(empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT)?'':$conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}
$search_agenda_label=GETPOST('search_agenda_label');


// Security check
$id = GETPOST("id",'int');
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result=restrictedArea($user,'projet',$id,'');

if (!$user->rights->projet->lire)	accessforbidden();



/*
 *	Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All test are required to be compatible with all browsers
{
    $actioncode='';
    $search_agenda_label='';
}



/*
 * View
 */

$form = new Form($db);
$object = new Project($db);

if ($id > 0 || ! empty($ref))
{
    $object->fetch($id, $ref);
    $object->fetch_thirdparty();
	if(! empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();
    $object->info($object->id);
}

$title=$langs->trans("Project").' - '.$object->ref.' '.$object->name;
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->ref.' '.$object->name.' - '.$langs->trans("Info");
$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$title,$help_url);

$head = project_prepare_head($object);

dol_fiche_head($head, 'agenda', $langs->trans("Project"), -1, ($object->public?'projectpub':'project'));


// Project card

$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref='<div class="refidno">';
// Title
$morehtmlref.=$object->title;
// Thirdparty
if ($object->thirdparty->id > 0)
{
    $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'project');
}
$morehtmlref.='</div>';

// Define a complementary filter for search of next/prev ref.
if (! $user->rights->projet->all->lire)
{
    $objectsListId = $object->getProjectsAuthorizedForUser($user,0,0);
    $object->next_prev_filter=" rowid in (".(count($objectsListId)?join(',',array_keys($objectsListId)):'0').")";
}

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

dol_print_object_info($object, 1);

print '</div>';

print '<div class="clearboth"></div>';

dol_fiche_end();


// Actions buttons

$out='';
$permok=$user->rights->agenda->myactions->create;
if ($permok)
{
    $out.='&projectid='.$object->id;
}


//print '<div class="tabsAction">';
$morehtmlcenter='';
if (! empty($conf->agenda->enabled))
{
    if (! empty($user->rights->agenda->myactions->create) || ! empty($user->rights->agenda->allactions->create))
    {
        $morehtmlcenter.='<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id).'">'.$langs->trans("AddAction").'</a>';
    }
    else
    {
        $morehtmlcenter.='<a class="butActionRefused" href="#">'.$langs->trans("AddAction").'</a>';
    }
}

//print '</div>';

if (!empty($object->id))
{
	print '<br>';

	$param='&id='.$object->id;
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
    if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;

    print_barre_liste($langs->trans("ActionsOnProject"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlcenter, '', 0, 1, 1);

    // List of all actions
    $filters=array();
    $filters['search_agenda_label']=$search_agenda_label;
    show_actions_done($conf,$langs,$db,$object,null,0,$actioncode, '', $filters, $sortfield, $sortorder);
}


llxFooter();
$db->close();
