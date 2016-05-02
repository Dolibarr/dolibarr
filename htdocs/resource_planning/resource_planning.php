<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013       Jean-François Ferry <jfefe@aternatik.fr>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014       Florian Henry		<florian.henry@open-concept.pro>
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
 *  \file       resource/resource_planning
 *  \ingroup    resource
 *  \brief      Resource planning view
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory

if (! $res) die("Include of main fails");

dol_include_once('/resource/class/resource.class.php');
dol_include_once('/resource/class/html.formresource.class.php');
require_once('class/html.formresource_planning.class.php');
include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';

// Translations
$langs->load("companies");
$langs->load("other");

//FIXME: missing rights enforcement

$form = new Form($db);
$formresource = new FormResourcePlanning($db);
$formaction = new Form($db);
$formactions=new FormActions($db);

$fk_resource=GETPOST('fk_resource');
$actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode")=='0'?'0':(empty($conf->global->AGENDA_USE_EVENT_TYPE)?'AC_OTH':''));


/***************************************************
* VIEW
****************************************************/
$morecss=array(
	"/resource_planning/js/fullcalendar/fullcalendar.css",
	"/resource_planning/js/jquery.qtip.css",
    "/resource_planning/inc/multiselect/css/ui.multiselect.css"
);

$morejs=array(
	"/resource_planning/js/fullcalendar/fullcalendar.min.js",
	"/resource_planning/js/jquery.qtip.min.js"
);


$monthNames=array(	'"'.$langs->trans('Month01').'"',
				  	'"'.$langs->trans('Month02').'"',
				 	'"'.$langs->trans('Month03').'"',
					'"'.$langs->trans('Month04').'"',
					'"'.$langs->trans('Month05').'"',
					'"'.$langs->trans('Month06').'"',
					'"'.$langs->trans('Month07').'"',
					'"'.$langs->trans('Month08').'"',
					'"'.$langs->trans('Month09').'"',
					'"'.$langs->trans('Month10').'"',
					'"'.$langs->trans('Month11').'"',
					'"'.$langs->trans('Month12').'"');
$monthNamesShort=array(	'"'.$langs->trans('MonthShort01').'"',
		'"'.$langs->trans('MonthShort02').'"',
		'"'.$langs->trans('MonthShort03').'"',
		'"'.$langs->trans('MonthShort04').'"',
		'"'.$langs->trans('MonthShort05').'"',
		'"'.$langs->trans('MonthShort06').'"',
		'"'.$langs->trans('MonthShort07').'"',
		'"'.$langs->trans('MonthShort08').'"',
		'"'.$langs->trans('MonthShort09').'"',
		'"'.$langs->trans('MonthShort10').'"',
		'"'.$langs->trans('MonthShort11').'"',
		'"'.$langs->trans('MonthShort12').'"');
$dayNames=array(	
        '"'.$langs->trans('Sunday').'"',
        '"'.$langs->trans('Monday').'"',
		'"'.$langs->trans('Tuesday').'"',
		'"'.$langs->trans('Wednesday').'"',
		'"'.$langs->trans('Thursday').'"',
		'"'.$langs->trans('Friday').'"',
		'"'.$langs->trans('Saturday').'"');
$dayNamesShort=array(	
        '"'.$langs->trans('SundayMin').'"',
        '"'.$langs->trans('MondayMin').'"',
		'"'.$langs->trans('TuesdayMin').'"',
		'"'.$langs->trans('WednesdayMin').'"',
		'"'.$langs->trans('ThursdayMin').'"',
		'"'.$langs->trans('FridayMin').'"',
		'"'.$langs->trans('SaturdayMin').'"');


if(is_array($fk_resource))
{
    $params='';
    foreach($fk_resource as $id_res)
    {
        $params.='&fk_resource[]='.$id_res;
    }
}


$fullcalendar = '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	$("#calendar").fullCalendar({
    firstDay: 1,
	header: {
		left: \'prev,next today\',
		center: \'title\',
		right: \'resourceDay,resourceWeek,resourceNextWeeks,resourceMonth,month,agendaWeek,agendaDay\'
	},
	monthNames: ['.implode(',',$monthNames).'],
	monthNamesShort: ['.implode(',',$monthNamesShort).'],
	dayNames: ['.implode(',',$dayNames).'],
	dayNamesShort: ['.implode(',',$dayNamesShort).'],
	defaultView: \'resourceWeek\',
	maxTime: 23.9, // Work around a display bug on the resourceDay view, see https://github.com/jarnokurlin/fullcalendar/issues/15
	resources: "' . dol_buildpath('/resource_planning/core/ajax/resource_action.json.php', 1) . '?action=resource'.$params.'",
	buttonText: {
		today: \''.$langs->trans('Today').'\',
		month: \''.$langs->trans('Month').'\',
		week: \''.$langs->trans('Week').'\',
		day: \''.$langs->trans('Day').'\',
	},
    eventRender: function(event, element) {
		element.qtip({
			content: {
				title: event.title + " (" + event.action_code + ")" ,
				text: event.description
			},
			position: {
				at: "bottomLeft"
			}
		});
	},
    eventSources: [

        // your event source
        {
            url: "'.dol_buildpath('/resource_planning/core/ajax/resource_action.json.php?action=events',1).'",
            type: "POST",
            data: {
                fk_resource: '.(is_array($fk_resource)?json_encode($fk_resource):'"'.$fk_resource.'"').',
                actioncode: "'.$actioncode.'"
            },
            error: function() {
                alert("there was an error while fetching events!");
            },
        }

	]
    });

                		
    // Click Function
	$(":button[name=gotodate]").click(function() {
                    
		day=$("#select_start_dateday").val();
		month=$("#select_start_datemonth").val()-1;
		year=$("#select_start_dateyear").val();
		datewished= new Date(year, month, day);

		$("#calendar").fullCalendar("gotoDate", year, month, day );
	});	
                		
});
</script>';

$fullcalendar.= '<script type="text/javascript">
jQuery(document).ready(function () {
	jQuery.extend($.ui.multiselect.locale, {
		addAll:"'.$langs->transnoentities("AddAll").'",
		removeAll:"'.$langs->transnoentities("RemoveAll").'",
		itemsCount:"'.$langs->transnoentities("ItemsCount").'"
	});

	jQuery(function(){
	  jQuery(".multiselect").multiselect({sortable: false, searchable: false});
	});
});
</script>';


llxHeader($fullcalendar, $title, '', '', 0, 0, $morejs, $morecss);

print '<form action="'.$_SERVER['PHP_SELF'].'" >';
print $form->select_date($select_start_date, 'select_start_date', 0, 0, 1,'',1,1);
print '<input type="button" value="'.$langs->trans('GotoDate').'" id="gotodate" name="gotodate">';
print '</form>';

print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
print $formresource->select_resource_list_multi($fk_resource,'fk_resource',$filter='', 0, 500);

print '<br />';
print $formactions->select_type_actions($actioncode, "actioncode", '', (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : 0));
print ' <input type="submit" value="'.$langs->trans('Filter').'"  name="filter_resource" class="submit">';
print '</form>';

print '<div id="calendar"></div>';

// Page end
llxFooter();
$db->close();
?>
