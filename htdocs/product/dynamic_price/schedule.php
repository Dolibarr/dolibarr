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
 *   	\file       htdocs/product/dynamic_price/schedule.php
 *		\ingroup    product
 *		\brief      Page to manage price schedules
 */

//TODO: deal with DST and timezones, currently only GMT is supported

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/dynamic_price/class/price_schedule.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Load traductions files required by page
$langs->load("products");
$langs->load("companies");
$langs->load("other");

//Basic parameters
$id                    = GETPOST('id','int');
$action                = GETPOST('action','alpha');
$confirm               = GETPOST('confirm','alpha');
$cancel                = GETPOST('cancel','alpha');

//Schedule
$product_id            = GETPOST('product_id','int');
$type                  = GETPOST('type', 'int');
$product_supplier      = GETPOST('product_supplier','int');
$year                  = GETPOST('year','int');
$starting_hour         = GETPOST('starting_hour','int');

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
$section_price         = GETPOST('section_price','alpha');
$section_selection     = GETPOST('section_selection','alpha');

// Protection if external user
if ($user->socid > 0)
{
    accessforbidden();
}
if( ! $user->rights->service->lire || ! $user->rights->dynamicprices->schedule_read )
{
    accessforbidden();
}

$object = new PriceSchedule($db);
if ($id > 0)
{
    if ($object->fetch($id) <= 0)
    {
        dol_print_error($db, $object->error);
    }
}

$product_id = $product_id ? $product_id : $object->fk_product;
$type = $type ? $type : $object->schedule_type;
$product_supplier = $product_supplier ? $product_supplier : $object->fk_product_supplier;
$params = "&type=".$type;
if (!empty($product_supplier)) $params.= "&product_supplier=".$product_supplier;
if (!empty($action)) $params.= '&action='.$action;

$product = new Product($db);
if ($product->fetch($product_id) <= 0)
{
    dol_print_error($db, $product->error);
}

if (!empty($cancel))
{
    if ($action == 'create') {
        Header("Location: list_schedule.php?id=".$product_id.$params);
        exit;
    } else {
        $action = '';
    }
}

if ($type == PriceSchedule::TYPE_SUPPLIER_SERVICE && empty($product_supplier))
{
    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Supplier")), null, 'errors');
    Header("Location: list_schedule.php?id=".$product_id.$params);
    exit;
}

//Set defaults if not set
$gm = "server";
$now = dol_getdate(dol_now(), true);
if (empty($year))       $year = $object->schedule_year;
if (empty($year))       $year = $now['year'];
if (empty($sel_month))  $sel_month = $now['mon'];
if (empty($sel_day))    $sel_day = $now['mday'];
$first_date = dol_getdate(dol_get_first_day($year, $sel_month, $gm), true);
$last_date = dol_getdate(dol_get_last_day($year, $sel_month, $gm), true);
$sel_day = min($sel_day, $last_date['mday']);
if (empty($range_start_month))    $range_start_month = empty($conf->global->SCHEDULE_DATE_RANGE_YEAR) ? $sel_month : 1;
if (empty($range_start_day))      $range_start_day = empty($conf->global->SCHEDULE_DATE_RANGE_YEAR) ? $sel_day : 1;
if (empty($range_start_hour))     $range_start_hour = 0;
if (empty($range_start_min))      $range_start_min = 0;
if (empty($range_end_month))      $range_end_month = empty($conf->global->SCHEDULE_DATE_RANGE_YEAR) ? $sel_month : 12;
if (empty($range_end_day))        $range_end_day = empty($conf->global->SCHEDULE_DATE_RANGE_YEAR) ? $sel_day : 31;
if (empty($range_end_hour))       $range_end_hour = 23;
if (empty($range_end_min))        $range_end_min = 59;

//Disable or hide starting hour when necessary
$disable_starting_hour = false;
$hide_starting_hour = false;
$other = new PriceSchedule($db);
$result = $other->fetchAll($product_id, null, null);
if ($result > 0 && count($other->lines))
{
    $disable_starting_hour = true;
    //Set current hour as previously defined
    $schedule = $other->lines[0];
    $starting_hour = $schedule->starting_hour;
}
if (!empty($product->duration_value))
{
    if (($product->duration_unit == 'h' && $product->duration_value == 1) || ($product->duration_unit == 'm' && $product->duration_value >= 12))
    {
        $hide_starting_hour = true;
    }
}
if ($hide_starting_hour)
{
    $starting_hour = 0;
    $disable_starting_hour = true;
}

//Bound starting hour to 24h
if (empty($starting_hour)) $starting_hour = 0;
if ($starting_hour < 0) $starting_hour = 0;
if ($starting_hour > 23) $starting_hour = 23;

//Hook init
$hookmanager->initHooks(array('price_schedule'));
$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

/*******************************************************************
* ACTIONS
********************************************************************/

if ($action == 'create' && $user->rights->dynamicprices->schedule_write)
{
    $data_correct = $year && $product->isService() && $product->duration_unit && $product->duration_value;
    $error = 0;
    if ($data_correct) {
        //Check if there is another schedule with this year in service
        $other = new PriceSchedule($db);
        $result = $other->fetch('', $product_id, $type, $product_supplier, $year);
        if ($result > 0) {
            setEventMessages($langs->trans('ErrorScheduleSameYearExist'), null, 'errors');
            $data_correct = false;
            $error++;
        }
        else if ($result < 0)
        {
            setEventMessages($other->error, $other->errors, 'errors');
            $data_correct = false;
            $error++;
        }

        //Load data to object
        $object->fk_product = $product_id;
        $object->fk_product_supplier = $product_supplier;
        $object->schedule_type = $type;
        $object->schedule_year = $year;
        $object->starting_hour = $starting_hour;

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
                setEventMessages($langs->trans('ErrorScheduleNoSectionGenerated'), null, $preview?'warnings':'errors');
                $data_correct = false;
                $error++;
            }
            else if ($result < 0)
            {
                setEventMessages($object->error, $object->errors, 'errors');
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
else if ($action == 'edit' && !empty($confirm) && $user->rights->dynamicprices->schedule_write)
{
    $range_start=dol_mktime($range_start_hour, $range_start_min, 0, $range_start_month, $range_start_day, $year, $gm);
    $range_end=dol_mktime($range_end_hour, $range_end_min, 59, $range_end_month, $range_end_day, $year, $gm);
    if ($range_start > $range_end)
    {
        setEventMessages($langs->trans('ErrorStartDateGreaterEnd'), null, 'errors');
    }
    else if (!empty($section_selection) && !preg_match("/^[0-9.,]+$/", $section_selection))
    {
        setEventMessages($langs->trans('ErrorSelectionFormat'), null, 'errors');
    }
    else
    {
        $section = new PriceScheduleSection($db);
        $section->fk_schedule = $object->id;
        $section->price = price2num($section_price);

        if (empty($section_selection))
        {
            $section->date_start = $range_start;
            $section->date_end = $range_end;
            $result = $section->updateSections($user, false);
        }
        else
        {
            $result = $section->updateSelectedSections($user, explode(",", $section_selection), false);
        }
        if ($result > 0)
        {
            setEventMessages($langs->trans("ScheduleAllSectionsChanged"), null, 'mesgs');
        }
        else
        {
            setEventMessages($section->error, $section->errors, 'errors');
        }
    }
}
else if ($action == 'copy_to_sell' && $user->rights->dynamicprices->schedule_delete)
{
    $action='';
    $error = 0;
    //Check if same year exist for schedule
    $other = new PriceSchedule($db);
    $result = $other->fetch(0, $product_id, PriceSchedule::TYPE_SERVICE, null, $object->schedule_year);

    if ($result > 0)
    {
        setEventMessages($langs->trans("ErrorScheduleSameYearExist"), null, 'errors');
        $error++;
    }
    else
    {
        //Create price schedule from current supplier price schedule
        $other->fk_product = $object->fk_product;
        $other->fk_product_supplier = 0;
        $other->schedule_type = PriceSchedule::TYPE_SERVICE;
        $other->schedule_year = $object->schedule_year;
        $other->starting_hour = $object->starting_hour;

        //Create object
        $result = $other->create($user);
        if ($result <= 0)
        {
            setEventMessages($other->error, $other->errors, 'errors');
            $error++;
        }

        if (!$error)
        {
            //Generate sections
            $result = $other->copySections($object);
            if ($result == 0)
            {
                setEventMessages($langs->trans('ErrorScheduleNoSectionGenerated'), null, 'errors');
                $error++;
            }
            if ($result < 0)
            {
                setEventMessages($other->error, $other->errors, 'errors');
                $error++;
            }
        }

        //Load newly created schedule
        if (!$error)
        {
            Header("Location: schedule.php?id=".$other->id);
            exit;
        }
        else if ($other->id)
        {
            //Object was created but error occurred, delete it
            $other->delete($user);
        }
    }
}
else if ($action == 'confirm_delete' && $user->rights->dynamicprices->schedule_delete)
{
    if ($confirm == 'yes') {
        $result = $object->delete($user);
        if ($result > 0)
        {
            Header("Location: list_schedule.php?id=".$product_id.$params);
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
$pagetitle = $langs->trans($action == 'create' ? 'AddSchedule' : 'PriceSchedule');
llxHeader('',$pagetitle,'');

$form = new Form($db);
$formother = new FormOther($db);

if ($action == 'create' && ($user->rights->dynamicprices->schedule_write))
{
    print load_fiche_titre($pagetitle, '');

    dol_fiche_head('');

    //Header
    print '<form action="'.$_SERVER["PHP_SELF"].'?product_id='.$product_id.$params.'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="border" width="100%">';

    //Ref
    print '<tr><td style="width:30%">'.$langs->trans("Ref").'</td><td>';
    print '<div class="inline-block floatleft">'.dol_escape_htmltag($product->ref).'</div>';
    print '</td></tr>';

    //Label
    print '<tr><td style="width:30%">'.$langs->trans("Label").'</td><td>';
    print '<div class="inline-block floatleft">'.dol_escape_htmltag($product->label).'</div>';
    print '</td></tr>';

    //Section
    print '<tr><td style="width:30%">'.$langs->trans("SectionDuration").'</td><td>';
    print $product->duration_value.'&nbsp;';
    $da=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
    print $langs->trans($da[$product->duration_unit].($product->duration_value > 1?'s':''));
    print '</td></tr>';

    // Year
    print '<tr><td style="width:30%" class="fieldrequired">'.$langs->trans("Year").'</td>';
    print '<td>';
    $currentyear = empty($year)?date("Y"):$year;
    print $formother->selectyear($currentyear,'year',0, 10, 5, -date("Y") + $currentyear);
    print '</td>';
    print '</tr>';

    // Starting hour
    if (!$disable_starting_hour)
    {
        print '<tr><td style="width:30%" class="fieldrequired">'.$langs->trans("StartingHour").'</td>';
        print '<td>';
        print '<input name="starting_hour" size="6" maxlength="2" value="' . $starting_hour . '">&nbsp;';
        print '</td></tr>';
    }
    else if (!$hide_starting_hour)
    {
        print '<tr><td style="width:30%">'.$langs->trans("StartingHour").'</td><td>';
        print $starting_hour;
        print '</td></tr>';
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
        print showSectionViewer($object, "sectionviewer", $sel_month, $sel_day, $product->duration_unit, $product->duration_value, false, false, false, '');
    }

    print '</form>';
}
else if ( $object->id > 0)
{
    $head=product_prepare_head($product);
    $tab=$type==PriceSchedule::TYPE_SUPPLIER_SERVICE?'supplierpriceschedule':'priceschedule';
    dol_fiche_head($head, $tab, $pagetitle,0,'product@product');

    // Confirm deleting price schedule
    if ($action == 'delete')
    {
        print $form->formconfirm("schedule.php?&id=".$object->id,$langs->trans("DeleteSchedule"),$langs->trans("ConfirmDeleteSchedule"),"confirm_delete",'','',1);
    }

    print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.$params.'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<table width="100%" class="border">';

    //Ref
    print '<tr><td style="width:30%">'.$langs->trans("Ref").'</td><td>';
    print dol_escape_htmltag($product->ref);
    print '<div class="inline-block floatright">';
    print '<a href="list_schedule.php?id='.$product_id.$params.'">';
    print $langs->trans("BackToList");
    print '</a></div>';
    print '</td></tr>';

    //Label
    print '<tr><td style="width:30%">'.$langs->trans("Label").'</td><td>';
    print '<div class="inline-block floatleft">'.dol_escape_htmltag($product->label).'</div>';
    print '</td></tr>';

    //Section
    print '<tr><td style="width:30%">'.$langs->trans("SectionDuration").'</td><td>';
    print $product->duration_value.'&nbsp;';
    $da=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
    print $langs->trans($da[$product->duration_unit].($product->duration_value > 1?'s':''));
    print '</td></tr>';

    //Year
    print '<tr><td style="width:30%">'.$langs->trans("Year").'</td><td>';
    print $object->schedule_year;
    print '</td></tr>';


    // Starting hour
    if (!$hide_starting_hour)
    {
        print '<tr><td style="width:30%">'.$langs->trans("StartingHour").'</td><td>';
        print $starting_hour;
        print '</td></tr>';
    }

    print '</table>';

    dol_fiche_end('');

    // Section writer
    if ($action == 'edit' && $user->rights->dynamicprices->schedule_write)
    {
        //Header
        print '<br>';
        print load_fiche_titre($langs->trans("ModifySections"), '', '');
        print '<table style="width: 600px" class="noborder">';
        print '<tr class="liste_titre">';
        print '<td class="fieldrequired">'.$langs->trans("Price").'</td>';
        print '<td class="fieldrequired"><span class="range_date">'.$langs->trans("DateStart").'</span></td>';
        print '<td class="fieldrequired"><span class="range_date">'.$langs->trans("DateEnd").'</span></td>';
        print '</tr><tr>';

        //Price
        print '<td style="width: 20%; text-align: left;">';
        print '<input id="section_price" name="section_price" size="10" value="'.$section_price.'">';
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

    /*
     * Buttons actions
     */
    print '<div class="tabsAction">';
    if ($action != "edit")
    {
        // Copy to sell price schedule
        if ($product_supplier && $user->rights->dynamicprices->schedule_write)
        {
            print '<div class="inline-block divButAction">';
            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=copy_to_sell" class="butAction">'.$langs->trans('CopyToSellSchedule').'</a>';
            print '</div>';
        }

        // Edit
        if ($user->rights->dynamicprices->schedule_write)
        {
            print '<div class="inline-block divButAction">';
            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;sel_month='.$sel_month.'&amp;sel_day='.$sel_day.'&amp;action=edit" class="butAction">'.$langs->trans('ModifySections').'</a>';
            print '</div>';
        }

        // Delete
        if($user->rights->dynamicprices->schedule_delete)
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
    print showSectionViewer($object, "sectionviewer", $sel_month, $sel_day, $product->duration_unit, $product->duration_value, $interactive, $selectable, true, $params);

    print '</form>';
}
else
{
    dol_print_error(0, $object->error.' - '.(empty($product->error)?"":" ".$product->error));
}

// End of page
llxFooter();
$db->close();
