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
 *   	\file       resource/schedule.php
 *		\ingroup    resource
 *		\brief      Page to manage resource schedules
 */

//TODO: deal with DST and timezones, currently only GMT is supported

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT . '/resource/class/resourceschedule.class.php';
require_once DOL_DOCUMENT_ROOT . '/resource/class/html.formresource.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/resource.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Load traductions files required by page
$langs->load("resource");
$langs->load("companies");
$langs->load("other");

//Basic parameters
$id                    = GETPOST('id','int');
$action                = GETPOST('action','alpha');
$confirm               = GETPOST('confirm','alpha');
$cancel                = GETPOST('cancel','alpha');

//Schedule
$resource_id           = GETPOST('resource_id','int');
$year                  = GETPOST('year','int');

//Selector
$sel_month             = GETPOST('sel_month','int');
$sel_day               = GETPOST('sel_day','int');

//Date start/end
$range_start_month     = GETPOST('range_start_month','int');
$range_start_day       = GETPOST('range_start_day','int');
$range_start_hour      = GETPOST('range_start_hour','int');
$range_start_min       = GETPOST('range_start_min','int');
$range_end_month       = GETPOST('range_end_month','int');
$range_end_day         = GETPOST('range_end_day','int');
$range_end_hour        = GETPOST('range_end_hour','int');
$range_end_min         = GETPOST('range_end_min','int');

//Section
$section_status        = GETPOST('section_status','int');
$section_selection     = GETPOST('section_selection','alpha');

//Placement
$soc                   = GETPOST('soc','int');
$ref_client            = GETPOST('ref_client','alpha');

// Protection if external user
if ($user->socid > 0)
{
    accessforbidden();
}
if( ! $user->rights->resource->read || ! $user->rights->resource->schedule_read )
{
    accessforbidden();
}

$object = new ResourceSchedule($db);
$section_statuses = ResourceStatus::translated();
if ($id > 0)
{
    if ($object->fetch($id) <= 0)
    {
        dol_print_error($db, $object->error);
    }
}

$resource = new Dolresource($db);
if ($resource->fetch($resource_id ? $resource_id : $object->fk_resource) <= 0)
{
    dol_print_error($db, $resource->error);
}

if (!empty($cancel))
{
    if ($action == 'create') {
        Header("Location: list_schedule.php?id=".$resource_id);
        exit;
    } else {
        $action = '';
    }
}

//Set defaults if not set
$gm = "server";
$now = dol_getdate(dol_now(), true);
if (empty($year))       $year = $object->schedule_year;
if (empty($year))       $year = $now['year'];
if (empty($sel_month))  $sel_month = $now['mon'];
if (empty($sel_day))    $sel_day = $now['mday'];
$first_date = dol_getdate(dol_get_first_day($year, $sel_month), $gm);
$last_date = dol_getdate(dol_get_last_day($year, $sel_month), $gm);
$sel_day = min($sel_day, $last_date['mday']);
if (empty($range_start_month))    $range_start_month = empty($conf->global->SCHEDULE_DATE_RANGE_YEAR) ? $sel_month : 1;
if (empty($range_start_day))      $range_start_day = empty($conf->global->SCHEDULE_DATE_RANGE_YEAR) ? $sel_day : 1;
if (empty($range_start_hour))     $range_start_hour = 0;
if (empty($range_start_min))      $range_start_min = 0;
if (empty($range_end_month))      $range_end_month = empty($conf->global->SCHEDULE_DATE_RANGE_YEAR) ? $sel_month : 12;
if (empty($range_end_day))        $range_end_day = empty($conf->global->SCHEDULE_DATE_RANGE_YEAR) ? $sel_day : 31;
if (empty($range_end_hour))       $range_end_hour = 23;
if (empty($range_end_min))        $range_end_min = 59;

//Set action to params if present
$params = '';
if (!empty($action)) $params = '&amp;action='.$action;

//Hook init
$hookmanager->initHooks(array('resource', 'resource_schedule'));
$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

/*******************************************************************
* ACTIONS
********************************************************************/

if ($action == 'create' && $user->rights->resource->schedule_write)
{
    $data_correct = $year && $resource->management_type == Dolresource::MANAGEMENT_TYPE_SCHEDULE;
    $error = 0;
    if (!is_numeric($resource->starting_hour))
    {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("StartingHour")), null, 'errors');
        $data_correct = false;
    }
    if ($data_correct) {
        //Load data to object
        $object->fk_resource = $resource_id;
        $object->schedule_year = $year;

        //Check if there is another schedule with this year in resource
        $other = new ResourceSchedule($db);
        $result = $other->fetch('', $resource_id, $year);
        if ($result > 0) {
            setEventMessages($langs->trans('ErrorScheduleSameYearExist'), null, 'errors');
            $data_correct = false;
            $error++;
        }

        if (!$error)
        {
            //Create object
            if (!empty($confirm))
            {
                $result = $object->create($user);
                if ($result <= 0)
                {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $error++;
                }
            }

            //Generate sections, preview mode if error occurred or is not confirmed
            $preview = $error || empty($confirm);
            $timezone = @date_default_timezone_get();
            $result = $object->generateSections($preview, $timezone);
            if ($result == 0)
            {
                setEventMessages($langs->trans('ErrorScheduleNoSectionGenerated'), null, 'warnings');
                $data_correct = false;
                $error++;
            }
            else if ($result < 0)
            {
                setEventMessages($object->error, $object->errors, 'errors');
                $data_correct = false;
                $error++;
            }

            //Load newly created schedule
            if (!$error && !empty($confirm))
            {
                Header("Location: schedule.php?id=".$object->id);
                exit;
            }
            else if ($object->id && !empty($confirm))
            {
                //Object was created but error occurred, delete it
                $object->delete($user);
            }
        }
    }
}
else if ($action == 'edit' && !empty($confirm) && $user->rights->resource->schedule_write)
{
    $range_start=dol_mktime($range_start_hour, $range_start_min, 0, $range_start_month, $range_start_day, $year, $gm);
    $range_end=dol_mktime($range_end_hour, $range_end_min, 59, $range_end_month, $range_end_day, $year, $gm);
    if ($range_start > $range_end)
    {
        setEventMessages($langs->trans('ErrorStartDateGreaterEnd'), null, 'errors');
    }
    else if (!in_array($section_status, ResourceStatus::$MANUAL))
    {
        setEventMessages($langs->trans('ErrorStatusNotAssignable'), null, 'errors');
    }
    else if (!empty($section_selection) && !preg_match("/^[0-9.,]+$/", $section_selection))
    {
        setEventMessages($langs->trans('ErrorSelectionFormat'), null, 'errors');
    }
    else
    {
        $section = new ResourceScheduleSection($db);
        $section->fk_schedule = $object->id;
        $section->status = $section_status;

        if (empty($section_selection))
        {
            $section->date_start = $range_start;
            $section->date_end = $range_end;
            $result = $section->updateSections(ResourceStatus::$MANUAL, false);
        }
        else
        {
            $result = $section->updateSelectedSections(explode(",", $section_selection), ResourceStatus::$MANUAL, false);
        }
        if ($result > 0)
        {
            setEventMessages($langs->trans("ScheduleSectionsUnchanged", $result), null, 'warnings');
        }
        else if ($result == 0)
        {
            setEventMessages($langs->trans("ScheduleAllSectionsChanged"), null, 'mesgs');
        }
        else
        {
            setEventMessages($section->error, $section->errors, 'errors');
        }
    }
}
else if ($action == 'placement' && !empty($confirm) && $user->rights->resource->placement_write)
{
    $error = 0;
    $range_start=dol_mktime($range_start_hour, $range_start_min, 0, $range_start_month, $range_start_day, $year, $gm);
    $range_end=dol_mktime($range_end_hour, $range_end_min, 59, $range_end_month, $range_end_day, $year, $gm);
    if ($range_start > $range_end)
    {
        setEventMessages($langs->trans('ErrorStartDateGreaterEnd'), null, 'errors');
        $error++;
    }

    if (!$error)
    {
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $customer = new Societe($db);
        $customer->fetch($soc);
        if (empty($customer->id))
        {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Customer")), null, 'errors');
            $error++;
        }
    }

    if (!$error)
    {
        require_once DOL_DOCUMENT_ROOT.'/resource/class/resourceplacement.class.php';
        $placement = new ResourcePlacement($db);
        $placement->ref_client = $ref_client;
        $placement->fk_soc = $customer->id;
        $placement->fk_resource = $object->fk_resource;
        $placement->date_creation = dol_now();
        $placement->date_start = $range_start;
        $placement->date_end = $range_end;

        $result = $placement->create($user);
        if ($result < 0)
        {
            setEventMessages($placement->error, $placement->errors, 'errors');
        }
        else
        {
            setEventMessages($langs->trans("ResourcePlacementSuccessful"), null, 'mesgs');
            Header("Location: schedule.php?id=".$object->id);
            exit;
        }
    }
}
else if ($action == 'confirm_delete' && $user->rights->resource->schedule_delete)
{
    if ($confirm == 'yes') {
        $result = $object->delete($user);
        if ($result > 0)
        {
            Header("Location: list_schedule.php?id=".$object->fk_resource);
            exit;
        }
        else
        {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
    else
    {
        $action='';
    }
}

/***************************************************
* VIEW
****************************************************/
$pagetitle = $langs->trans($action == 'create' ? 'AddSchedule' : 'ResourceSchedule');
llxHeader('',$pagetitle,'');

$form = new Form($db);
$formother = new FormOther($db);

if ($action == 'create' && ($user->rights->resource->schedule_write))
{
    print load_fiche_titre($pagetitle, '');

    dol_fiche_head('');

    //Header
    print '<form action="'.$_SERVER["PHP_SELF"].'?resource_id='.$resource_id.$params.'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="border" width="100%">';

    //Ref
    print '<tr><td style="width:30%">'.$langs->trans("ResourceFormLabel_ref").'</td><td>';
    print '<div class="inline-block floatleft">'.$resource->ref.'</div>';
    print '</td></tr>';

    //Section
    print '<tr><td style="width:30%">'.$langs->trans("SectionDuration").'</td><td>';
    print $resource->duration_value.'&nbsp;';
    $da=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
    print $langs->trans($da[$resource->duration_unit].($resource->duration_value > 1?'s':''));
    print '</td></tr>';

    // Year
    print '<tr><td style="width:30%" class="fieldrequired">'.$langs->trans("Year").'</td>';
    print '<td>';
    $currentyear = empty($year)?date("Y"):$year;
    print $formother->selectyear($currentyear,'year',0, 10, 5, -date("Y") + $currentyear);
    print '</td>';
    print '</tr>';

    // Starting hour
    if (!empty($resource->duration_value))
    {
        if (!($resource->duration_unit == 'h' && $resource->duration_value == 1))
        {
            print '<tr><td style="width:30%">'.$langs->trans("StartingHour").'</td><td>';
            print $resource->starting_hour;
            print '</td></tr>';
        }
    }

    print '</table>';

    dol_fiche_end('');

    //Buttons
    print '<br><div align="center">';
    if ($data_correct)
    {
        print '<input type="submit" class="button" name="confirm" value="'.$langs->trans('Create').'" />';
        print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    }
    print '<input type="submit" class="button" name="generate" value="'.$langs->trans('Preview').'" />';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'" />';
    print '</div>';

    //Section viewer
    if ($data_correct) {
        print "<br>";
        print showSectionViewer($object, "sectionviewer", $sel_month, $sel_day, $resource->duration_unit, $resource->duration_value, false, false, false, '');
    }

    print '</form>';
}
else if ( $object->id > 0)
{
    $head=resource_prepare_head($resource);
    dol_fiche_head($head, 'schedule', $pagetitle,0,'resource@resource');

    // Confirm deleting resource schedule
    if ($action == 'delete')
    {
        print $form->formconfirm("schedule.php?&id=".$object->id,$langs->trans("DeleteSchedule"),$langs->trans("ConfirmDeleteSchedule"),"confirm_delete",'','',1);
    }

    print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.$params.'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<table width="100%" class="border">';

    //Ref
    print '<tr><td style="width:30%">'.$langs->trans("ResourceFormLabel_ref").'</td><td>';
    print $resource->ref;
    print '<div class="inline-block floatright">';
    print '<a href="list_schedule.php?id='.$object->fk_resource.'">';
    print $langs->trans("BackToList");
    print '</a></div>';
    print '</td></tr>';

    //Section
    print '<tr><td style="width:30%">'.$langs->trans("SectionDuration").'</td><td>';
    print $resource->duration_value.'&nbsp;';
    $da=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
    print $langs->trans($da[$resource->duration_unit].($resource->duration_value > 1?'s':''));
    print '</td></tr>';

    //Year
    print '<tr><td style="width:30%">'.$langs->trans("Year").'</td><td>';
    print $object->schedule_year;
    print '</td></tr>';

    // Starting hour
    if (!empty($resource->duration_value))
    {
        if (!($resource->duration_unit == 'h' && $resource->duration_value == 1))
        {
            print '<tr><td style="width:30%">'.$langs->trans("StartingHour").'</td><td>';
            print $resource->starting_hour;
            print '</td></tr>';
        }
    }

    print '</table>';

    dol_fiche_end('');

    // Section writer
    if ($action == 'edit' && $user->rights->resource->schedule_write)
    {

        //Remove non manual from selector
        $section_status_selectable = $section_statuses;
        foreach ($section_status_selectable as $status => $_)
        {
            if (!in_array($status, ResourceStatus::$MANUAL))
            {
                unset($section_status_selectable[$status]);
            }
        }
        if (empty($conf->global->RESOURCE_SHOW_UNKNOWN))
        {
            unset($section_status_selectable[ResourceStatus::UNKNOWN]);
        }

        //Header
        print '<br>';
        print load_fiche_titre($langs->trans("ModifySections"), '', '');
        print '<table style="width: 600px" class="noborder">';
        print '<tr class="liste_titre">';
        print '<td class="fieldrequired">'.$langs->trans("Status").'</td>';
        print '<td class="fieldrequired"><span class="range_date">'.$langs->trans("DateStart").'</span></td>';
        print '<td class="fieldrequired"><span class="range_date">'.$langs->trans("DateEnd").'</span></td>';
        print '</tr><tr>';

        //Status
        print '<td style="width: 20%; text-align: left;">';
        print $form->selectarray('section_status', $section_status_selectable, $section_status);
        print '</td>';

        //Date start
        print '<td style="width: 40%;" class="range_date">';
        print $formother->select_monthday($year, $range_start_month, $range_start_day, 'range_start_');
        print '&nbsp;';
        print $form->select_date('0000-00-00 '.$range_start_hour.':'.$range_start_min, 'range_start_', 1, 0, 0, '', 0, 0, 1).'<br>';
        print '</td>';

        //Date end
        print '<td style="width: 40%;" class="range_date">';
        print $formother->select_monthday($year, $range_end_month, $range_end_day, 'range_end_');
        print '&nbsp;';
        print $form->select_date('0000-00-00 '.$range_end_hour.':'.$range_end_min, 'range_end_', 1, 0, 0, '', 0, 0, 1).'<br>';
        print '</td>';

        //Selection mode
        print '<td style="width: 80%; text-align: center;" colspan="2" hidden id="selection_mode">'.$langs->trans("SelectionMode").'</td>';
        print '</tr></table>';
        print '<input type="hidden" id="sectionviewer_selection" name="section_selection" value="">';


        //Buttons
        print '<input type="submit" class="button" name="confirm" value="'.$langs->trans("Save").'"> &nbsp; ';
        print '<input type="submit" class="button" name="cancel" Value="'.$langs->trans("Cancel").'">';
    }

    // Section placement
    if ($action == 'placement' && $user->rights->resource->placement_write)
    {
        //Header
        print '<br>';
        print load_fiche_titre($langs->trans("ResourcePlacement"), '', '');
        print '<table style="width: 1000px" class="noborder">';
        print '<tr class="liste_titre">';
        print '<td class="fieldrequired">'.$langs->trans("Customer").'</td>';
        print '<td>'.$langs->trans('RefCustomer').'</td>';
        print '<td class="fieldrequired">'.$langs->trans("DateStart").'</td>';
        print '<td class="fieldrequired">'.$langs->trans("DateEnd").'</td>';
        print '</tr><tr>';

        //Client selector
        print '<td style="width: 30%;">';
        print $form->select_company('', 'soc', 's.client = 1 OR s.client = 3', 1, 0, 1);
        print '</td>';

        // Reference client
        print '<td style="width: 20%;">';
        print '<input type="text" name="ref_client" value="'.$ref_client.'">';
        print '</td>';

        //Date start
        print '<td style="width: 25%;">';
        print $formother->select_monthday($year, $range_start_month, $range_start_day, 'range_start_');
        print '&nbsp;';
        print $form->select_date('0000-00-00 '.$range_start_hour.':'.$range_start_min, 'range_start_', 1, 0, 0, '', 0, 0, 1).'<br>';
        print '</td>';

        //Date end
        print '<td style="width: 25%;">';
        print $formother->select_monthday($year, $range_end_month, $range_end_day, 'range_end_');
        print '&nbsp;';
        print $form->select_date('0000-00-00 '.$range_end_hour.':'.$range_end_min, 'range_end_', 1, 0, 0, '', 0, 0, 1).'<br>';
        print '</td>';
        print '</tr></table>';

        //Buttons
        print '<input type="submit" class="button" name="confirm" value="'.$langs->trans("Create").'"> &nbsp; ';
        print '<input type="submit" class="button" name="cancel" Value="'.$langs->trans("Cancel").'">';
    }

    /*
     * Buttons actions
     */
    print '<div class="tabsAction">';
    // Edit
    if ($action != "placement" && $user->rights->resource->schedule_write)
    {
        if ($action != "edit")
        {
            print '<div class="inline-block divButAction">';
            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;sel_month='.$sel_month.'&amp;sel_day='.$sel_day.'&amp;action=edit" class="butAction">'.$langs->trans('ModifySections').'</a>';
            print '</div>';
        }
    }
    if ($action != "edit" && $user->rights->resource->placement_write)
    {
        if ($action != "placement")
        {
            print '<div class="inline-block divButAction">';
            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;sel_month='.$sel_month.'&amp;sel_day='.$sel_day.'&amp;action=placement" class="butAction">'.$langs->trans('CreateResourcePlacement').'</a>';
            print '</div>';
        }
    }
    if ($action != "edit" && $action != "placement")
    {
        // Delete
        if($user->rights->resource->schedule_delete)
        {
            print '<div class="inline-block divButAction">';
            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;action=delete" class="butActionDelete">'.$langs->trans('Delete').'</a>';
            print '</div>';
        }
    }
    print '</div>';

    //Load sections
    $week_date = dol_get_first_day_week($sel_day, $sel_month, $year, $gm);
    $week_start=dol_mktime(0,0,0,$week_date['first_month'],$week_date['first_day'],$week_date['first_year'], $gm);
    $week_end = dol_time_plus_duree($week_start, 1, 'w') - 1;
    $result = $object->fetchSections($week_start, $week_end);
    if ($result < 0)
    {
        dol_print_error($db, $result.' - '.$object->error, 'errors');
    }

    //Section viewer
    $interactive = $action == 'edit' || $action == 'placement';
    $selectable = $action == 'edit';
    print showSectionViewer($object, "sectionviewer", $sel_month, $sel_day, $resource->duration_unit, $resource->duration_value, $interactive, $selectable, true, $params);

    print '</form>';
}
else
{
    dol_print_error(0, $object->error.' - '.(empty($resource->error)?"":" ".$resource->error));
}

// End of page
llxFooter();
$db->close();
