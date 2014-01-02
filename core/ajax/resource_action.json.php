<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013	   Jean-François Ferry		<jfefe@aternatik.fr>
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
 *   	\file       resource/core/ajax/resource_action.json.php
 *		\ingroup    resource
 *		\brief      This file is used for resource planning
 */

if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../../../main.inc.php")) $res=@include '../../../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory

if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

dol_include_once('/resource/class/resource.class.php');

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

// Get parameters
$id				= GETPOST('id','int');
$action			= GETPOST('action','alpha');

$start			= GETPOST('start','int');
$end			= GETPOST('end','int');
$fk_resource	= GETPOST('fk_resource','int');


// Get event in an array
$eventarray=array();

$sql = 'SELECT a.id,a.label,';
$sql.= ' a.datep,';
$sql.= ' a.datep2,';
$sql.= ' a.datea,';
$sql.= ' a.datea2,';
$sql.= ' a.percent,';
$sql.= ' a.fk_user_author,a.fk_user_action,a.fk_user_done,';
$sql.= ' a.priority, a.fulldayevent, a.location,';
$sql.= ' a.fk_soc, a.fk_contact,';
$sql.= ' ca.code';
$sql.= ' FROM ('.MAIN_DB_PREFIX.'c_actioncomm as ca,';
$sql.= " ".MAIN_DB_PREFIX.'user as u,';
$sql.= " ".MAIN_DB_PREFIX."actioncomm as a)";
if($fk_resource > 0) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_resources as r ON a.id = r.element_id ';
}
$sql.= ' WHERE a.fk_action = ca.id';
if($fk_resource > 0) {
	$sql.= " AND r.resource_id = '".$db->escape($fk_resource)."'";
}
$sql.= ' AND a.fk_user_author = u.rowid';
$sql.= ' AND a.entity IN ('.getEntity().')';
if ($actioncode) $sql.=" AND ca.code='".$db->escape($actioncode)."'";
if ($pid) $sql.=" AND a.fk_project=".$db->escape($pid);




if ($type) $sql.= " AND ca.id = ".$type;
if ($status == 'done') { $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep2 <= '".$db->idate($now)."'))"; }
if ($status == 'todo') { $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep2 > '".$db->idate($now)."'))"; }
if ($filtera > 0 || $filtert > 0 || $filterd > 0)
{
    $sql.= " AND (";
    if ($filtera > 0) $sql.= " a.fk_user_author = ".$filtera;
    if ($filtert > 0) $sql.= ($filtera>0?" OR ":"")." a.fk_user_action = ".$filtert;
    if ($filterd > 0) $sql.= ($filtera>0||$filtert>0?" OR ":"")." a.fk_user_done = ".$filterd;
    $sql.= ")";
}
$sql.= ' GROUP BY a.id';
// Sort on date
$sql.= ' ORDER BY datep';
//print $sql;

dol_syslog("comm/action/index.php sql=".$sql, LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i=0;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);

        // Create a new object action
        $event=new ActionComm($db);
        $event->id=$obj->id;
        $event->datep=$db->jdate($obj->datep);      // datep and datef are GMT date
        $event->datef=$db->jdate($obj->datep2);
        $event->type_code=$obj->code;
        $event->libelle=$obj->label;
        $event->percentage=$obj->percent;
        $event->author->id=$obj->fk_user_author;	// user id of creator
        $event->usertodo->id=$obj->fk_user_action;	// user id of owner
        $event->userdone->id=$obj->fk_user_done;	// deprecated
		// $event->userstodo=... with s after user, in future version, will be an array with all id of user assigned to event
        $event->priority=$obj->priority;
        $event->fulldayevent=$obj->fulldayevent;
        $event->location=$obj->location;

        $event->societe->id=$obj->fk_soc;
        $event->contact->id=$obj->fk_contact;


        $eventarray[]=$event;

        $i++;

    }
}
else
{
    dol_print_error($db);
}

//var_dump($eventarray);
foreach ($eventarray as $day => $event) {
	$event_json[] = array(
			'id' => $event->id,
			'title' => $event->libelle,
			'start' => $event->datep,
			'end' => $event->datef,
			'end' => $event->datef,
			'allDay' => $event->fulldayevent?true:false,
			'url' => dol_buildpath("/comm/action/fiche.php",1).'?id='. $event->id
		);
}

//var_dump($event_json);
echo json_encode($event_json);


$db->close();
?>
