<?php
/* Copyright (C) 2007-2010  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2013       Jean-François Ferry <jfefe@aternatik.fr>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

dol_include_once('/resource/class/resource.class.php');

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

$langs->load("conference@conference");

// Get parameters
$id				= GETPOST('id','int');
$action			= GETPOST('action','alpha');
$actioncode		= GETPOST('actioncode','alpha');

$start			= GETPOST('start','int');
$end			= GETPOST('end','int');
$fk_resource	= GETPOST('fk_resource');

// Get event in an array
$eventarray=array();

// FIXME: only select events with an affected resource
$sql = 'SELECT a.id, a.label,';
$sql.= ' a.datep,';
$sql.= ' a.datep2,';
$sql.= ' a.datea,';
$sql.= ' a.datea2,';
$sql.= ' a.percent,';
$sql.= ' a.code, a.note,';
$sql.= ' a.fk_user_author,a.fk_user_action,a.fk_user_done,';
$sql.= ' a.priority, a.fulldayevent, a.location,';
$sql.= ' a.fk_soc, a.fk_contact,';
$sql.= ' ca.code';
if(is_array($fk_resource) || $fk_resource > 0) {
	$sql.= ', r.resource_id';
}
$sql.= ' FROM ('.MAIN_DB_PREFIX.'c_actioncomm as ca,';
$sql.= " ".MAIN_DB_PREFIX.'user as u,';
$sql.= " ".MAIN_DB_PREFIX."actioncomm as a)";
if(is_array($fk_resource) || $fk_resource > 0 ) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'element_resources as r ON a.id = r.element_id ';
}
$sql.= ' WHERE a.fk_action = ca.id';
// FILTER by date
if (!empty($start)) {
	$sql .= ' AND a.datep2 > ' . $db->idate($start);
}
if (!empty($end)) {
	$sql .= ' AND a.datep < ' . $db->idate($end);
}
if(!is_array($fk_resource) && $fk_resource > 0) {
	$sql.= " AND r.resource_id = '".$db->escape($fk_resource)."'";
}
elseif(is_array($fk_resource) and count($fk_resource) > 0)
{
	$sql.= " AND r.resource_id IN (".$db->escape(implode(',',$fk_resource)).")";
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

dol_syslog("/resource/core.ajax.resource_action.json.php sql=".$sql, LOG_DEBUG);

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i=0;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);
        //$transcode=
        // Create a new object action
        $event=new ActionComm($db);
        $resourcestat = new Resource($db);

        $resources = $resourcestat->getElementResources($event->element,$obj->id);
        if(is_array($resources) && count($resources) > 0)
        {
	        $j=0;
	        foreach($resources as $nb => $resource)
	        {
	        	$event->resources[$i] = fetchObjectByElement($resource['resource_id'],$resource['resource_type']);
	        	$j++;
	        }
        }

        $event->id=$obj->id;
        $event->datep=dol_print_date($db->jdate($obj->datep),'dayhourrfc');      // datep and datef are GMT date
        $event->datef=dol_print_date($db->jdate($obj->datep2),'dayhourrfc');
        $event->code=$obj->code;
        $event->action_code = $langs->transnoentities("Action".$obj->code);
        $event->libelle=$obj->label;
        $event->percentage=$obj->percent;
        $event->author->id=$obj->fk_user_author;	// user id of creator
        $event->usertodo->id=$obj->fk_user_action;	// user id of owner
        $event->userdone->id=$obj->fk_user_done;	// deprecated
		// $event->userstodo=... with s after user, in future version, will be an array with all id of user assigned to event
        $event->priority=$obj->priority;
        $event->fulldayevent=$obj->fulldayevent;
        $event->location=$obj->location;
        $event->note=$obj->note;

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

$event_json = array();
foreach($eventarray as $day => $event) {

    $description = '<strong>'.dol_print_date(dol_stringtotime($event->datep,0),'dayhour').' to '.dol_print_date(dol_stringtotime($event->datef,0),'dayhour').'</strong><br />';
	$description.= $event->note;
	$event_resources = array();
	if(is_array($event->resources) && count($event->resources) > 0)
	{
		$description.="<br /><strong>".$langs->trans('Ressources')."</strong><br>";
		foreach($event->resources as $resource_event) {
			$description.= $resource_event->getNomUrl(1);
			$description.= '<br>';
			$event_resources[] = $resource_event->id;
		}
	}

	$colors = array ('AC_WORKSHOP' => '#95DC16', 'AC_CONFERENC'=> '#F2B579');

	$event_json[] = array(
			'id' => $event->id,
			'title' =>  $event->libelle,
			'code' => $event->code,
			'action_code' => $langs->trans($event->action_code),
			'description' =>  $description,
			'start' => $event->datep,
			'end' => $event->datef,
			'allDay' => $event->fulldayevent?true:false,
			'url' => dol_buildpath("/comm/action/fiche.php",1).'?id='. $event->id,
			// TODO : associer une couleur au thme et la reprendre ici
			'backgroundColor' => $colors[$event->code],
			'resource' => $event_resources
			//'color' => 'white'
		);
}

// Resources list
// FIXME: limit shouldn't be needed
$resourcestat = new Resource($db);
$resource_json = array();
$resourcestat->fetch_all_used('ASC', 't.rowid', 1000000,0,'',$fk_resource);
if(is_array($resourcestat->lines))
{
    foreach($resourcestat->lines as $resource) {
    		$resource_json[] = array(
    			'name' => $resource->ref,
    			'id' => $resource->id
    		);
    }    
}

header('Content-Type: application/json');
switch($action) {
	case 'resource':
		echo json_encode($resource_json);
		break;
	case 'events':
		echo json_encode($event_json);
		break;

}

$db->close();
?>
