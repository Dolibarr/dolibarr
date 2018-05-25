<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/comm/action/info.php
 *      \ingroup    agenda
 *		\brief      Page des informations d'une action
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
if (! empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}

$langs->load("commercial");

$id = GETPOST('id','int');

// Security check
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$result = restrictedArea($user, 'agenda', $id, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id');


/*
 * View
 */

$form=new Form($db);

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);

$object = new ActionComm($db);
$object->fetch($id);
$object->info($object->id);

$head=actions_prepare_head($object);
dol_fiche_head($head, 'info', $langs->trans("Action"), -1, 'action');

$linkback = img_picto($langs->trans("BackToList"),'object_list','class="hideonsmartphone pictoactionview"');
$linkback.= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php">'.$langs->trans("BackToList").'</a>';

// Link to other agenda views
$out='';
$out.='</li><li class="noborder litext">'.img_picto($langs->trans("ViewPerUser"),'object_calendarperuser','class="hideonsmartphone pictoactionview"');
$out.='<a href="'.DOL_URL_ROOT.'/comm/action/peruser.php?action=show_peruser&year='.dol_print_date($object->datep,'%Y').'&month='.dol_print_date($object->datep,'%m').'&day='.dol_print_date($object->datep,'%d').'">'.$langs->trans("ViewPerUser").'</a>';
$out.='</li><li class="noborder litext">'.img_picto($langs->trans("ViewCal"),'object_calendar','class="hideonsmartphone pictoactionview"');
$out.='<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_month&year='.dol_print_date($object->datep,'%Y').'&month='.dol_print_date($object->datep,'%m').'&day='.dol_print_date($object->datep,'%d').'">'.$langs->trans("ViewCal").'</a>';
$out.='</li><li class="noborder litext">'.img_picto($langs->trans("ViewWeek"),'object_calendarweek','class="hideonsmartphone pictoactionview"');
$out.='<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_day&year='.dol_print_date($object->datep,'%Y').'&month='.dol_print_date($object->datep,'%m').'&day='.dol_print_date($object->datep,'%d').'">'.$langs->trans("ViewWeek").'</a>';
$out.='</li><li class="noborder litext">'.img_picto($langs->trans("ViewDay"),'object_calendarday','class="hideonsmartphone pictoactionview"');
$out.='<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_day&year='.dol_print_date($object->datep,'%Y').'&month='.dol_print_date($object->datep,'%m').'&day='.dol_print_date($object->datep,'%d').'">'.$langs->trans("ViewDay").'</a>';

$linkback.=$out;

$morehtmlref='<div class="refidno">';
// Thirdparty
//$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
// Project
if (! empty($conf->projet->enabled))
{
    $langs->load("projects");
    //$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
    $morehtmlref.=$langs->trans('Project') . ': ';
    if (! empty($object->fk_project)) {
        $proj = new Project($db);
        $proj->fetch($object->fk_project);
        $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
        $morehtmlref.=$proj->ref;
        $morehtmlref.='</a>';
        if ($proj->title) $morehtmlref.=' - '.$proj->title;
    } else {
        $morehtmlref.='';
    }
}
$morehtmlref.='</div>';

dol_banner_tab($object, 'id', $linkback, ($user->societe_id?0:1), 'id', 'ref', $morehtmlref);

print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

dol_fiche_end();

llxFooter();
$db->close();
