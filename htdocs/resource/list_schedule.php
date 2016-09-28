<?php
/* Copyright (C) 2013-2014	Jean-François Ferry	<jfefe@aternatik.fr>
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
 *   	\file       resource/list_schedule.php
 *		\ingroup    resource
 *		\brief      Page to list resource schedules
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT . '/resource/class/html.formresource.class.php';
require_once DOL_DOCUMENT_ROOT . '/resource/class/resourceschedule.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/resource.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Load traductions files required by page
$langs->load("resource");
$langs->load("companies");
$langs->load("other");

// Get parameters
$id        = GETPOST('id','int');
$sortorder = GETPOST('sortorder','alpha');
$sortfield = GETPOST('sortfield','alpha');
$page      = GETPOST('page','int');

// Protection if external user
if ($user->societe_id > 0)
{
    accessforbidden();
}

if( ! $user->rights->resource->read || ! $user->rights->resource->schedule_read )
{
    accessforbidden();
}

$pagetitle = $langs->trans('ResourceSchedule');
llxHeader('',$pagetitle,'');

$form = new Form($db);
$resource = new Dolresource($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('resource', 'resource_schedule_list'));

if ( $resource->fetch($id) > 0 )
{
    $head=resource_prepare_head($resource);
    dol_fiche_head($head, 'schedule', $langs->trans("ResourceSingular"),0,'resource@resource');

    /*
     * View object
     */
    print '<table width="100%" class="border">';
    print '<tr><td style="width:35%">'.$langs->trans("ResourceFormLabel_ref").'</td><td>';
    print '<div class="inline-block floatleft refid">'.$resource->ref.'</div>';
    print '</td></tr>';
    print '</table>';
    print '</div>';


    //List stuff
    if (empty($sortorder)) $sortorder="DESC";
    if (empty($sortfield)) $sortfield="schedule_year";
    if (empty($arch)) $arch = 0;
    if ($page == -1) {
        $page = 0 ;
    }

    $limit = $conf->liste_limit;
    $offset = $limit * $page ;
    $pageprev = $page - 1;
    $pagenext = $page + 1;

    // Load schedule list
    $object = new ResourceSchedule($db);
    $ret = $object->fetchAll($id, $sortorder, $sortfield, $limit, $offset);
    if($ret == -1)
    {
        dol_print_error($db,$object->error);
        exit;
    }
    if(!$ret)
    {
        print '<div class="warning">'.$langs->trans('ResourceScheduleNone').'</div>';
    }
    else
    {
        $var=true;

        print '<table class="noborder" width="100%">'."\n";
        print '<tr class="liste_titre">';
        $param = 'id='.$id;
        print_liste_field_titre($langs->trans('Year'),$_SERVER['PHP_SELF'],'schedule_year','',$param,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Action'),"","","","",'width="60" align="center"',"","");
        print "</tr>\n";

        foreach ($object->lines as $schedule)
        {
            $var=!$var;

            //Year
            print '<tr '.$bc[$var].'><td>';
            print '<a href="./schedule.php?id='.$schedule->id.'">'.$schedule->schedule_year.'</a>';
            print '</td>';

            //Buttons
            print '<td align="center">';
            print '<a href="./schedule.php?id='.$schedule->id.'&action=edit">';
            print img_edit();
            print '</a>';
            print '&nbsp;';
            print '<a href="./schedule.php?id='.$schedule->id.'&action=delete">';
            print img_delete();
            print '</a>';
            print '</td>';

            print '</tr>';
        }

        print '</table>';
    }
}
else
{
    dol_print_error(0,$resource->error);
}


/*
 * Add schedule button
 */
print '<div class="tabsAction">';
if($user->rights->resource->schedule_write)
{
    print '<div class="inline-block divButAction">';
    print '<a href="schedule.php?resource_id='.$id.'&action=create" class="butAction">'.$langs->trans('AddSchedule').'</a>';
    print '</div>';
}
print '</div>';

// End of page
llxFooter();
$db->close();
