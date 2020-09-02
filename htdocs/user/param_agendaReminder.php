<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2016      Abbes Bahfir         <contact@dolibarrpar.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/user/param_agendaReminder.php
 *      \ingroup    agenda reminder
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by page
$langs->loadLangs(array('companies','admin', 'other', 'agenda'));

$id = GETPOST("id", 'int');
$action = GETPOST('action', 'aZ09');
$actionid=GETPOST('actionid');

// Security check
if ($user->socid) $id=$user->socid;


if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg))
{
    global $conf, $db;

    $usertosetup = new User($db);
    $result = $usertosetup->fetch($id, '', '', 1);
    $code = $reg[1];
    $value = (GETPOST($code, 'alpha') ? GETPOST($code, 'alpha') : 1);
    $tab = array($code => $value);
    if (dol_set_user_param($db, $conf, $usertosetup, $tab) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    } else {
        dol_print_error($db);
    }
}

if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg))
{
    $usertosetup = new User($db);
    $result = $usertosetup->fetch($id, '', '', 1);
    $code = $reg[1];
    $tab = array($code => '');
    if (dol_set_user_param($db, $conf, $usertosetup, $tab) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
        exit;
    } else {
        dol_print_error($db);
    }
}

/*
 *	View
 */

$form = new Form($db);

$object = new User($db);
$result=$object->fetch($id, '', '', 1);
$object->getrights();

$title=$langs->trans("ThirdParty").' - '.$langs->trans("Reminders");
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name.' - '.$langs->trans("Notification");
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);


if ($result > 0)
{
    $head = user_prepare_head($object);

    dol_fiche_head($head, 'agendareminder', $langs->trans("User"), -1, 'user');

    $linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin, 'rowid', 'ref', '', '', 0, '', '', 0, '');

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">';

    //Reminder Agenda Event
    if ($conf->agenda->enabled && $conf->global->AGENDA_REMINDER_BROWSER)
    {
        print '<tr class="oddeven">'."\n";
        print '<td>'.$langs->trans('EventReminderActiveNotification', $langs->transnoentities("Module2300Name")).'</td>'."\n";
        print '<td class="center">&nbsp;</td>'."\n";
        print '<td class="right">'."\n";

        if (empty($object->conf->MAIN_USER_WANT_ALL_EVENTS_NOTIFICATIONS))
        {
            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=set_MAIN_USER_WANT_ALL_EVENTS_NOTIFICATIONS">'.img_picto($langs->trans('Disabled'), 'switch_off').'</a>';
            print '</td></tr>'."\n";
        }
        else {
            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=del_MAIN_USER_WANT_ALL_EVENTS_NOTIFICATIONS">'.img_picto($langs->trans('Enabled'), 'switch_on').'</a>';
            print '</td></tr>'."\n";
        }
    }
}
else dol_print_error('', 'RecordNotFound');

// End of page
llxFooter();