<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014      Cedric GROSS         <c.gross@kreiz-it.fr>
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
 *       \file       htdocs/comm/action/card.php
 *       \ingroup    agenda
 *       \brief      Page for event card
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("companies");
$langs->load("commercial");
$langs->load("other");
$langs->load("bills");
$langs->load("orders");
$langs->load("agenda");

$action=GETPOST('action','alpha');
$cancel=GETPOST('cancel','alpha');
$backtopage=GETPOST('backtopage','alpha');
$contactid=GETPOST('contactid','int');
$origin=GETPOST('origin','alpha');
$originid=GETPOST('originid','int');

$fulldayevent=GETPOST('fullday');
$datep=dol_mktime($fulldayevent?'00':GETPOST("aphour"), $fulldayevent?'00':GETPOST("apmin"), 0, GETPOST("apmonth"), GETPOST("apday"), GETPOST("apyear"));
$datef=dol_mktime($fulldayevent?'23':GETPOST("p2hour"), $fulldayevent?'59':GETPOST("p2min"), $fulldayevent?'59':'0', GETPOST("p2month"), GETPOST("p2day"), GETPOST("p2year"));

// Security check
$socid = GETPOST('socid','int');
$id = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'agenda', $id, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id');
if ($user->societe_id && $socid) $result = restrictedArea($user,'societe',$socid);

$error=GETPOST("error");
$donotclearsession=0;

$cactioncomm = new CActionComm($db);
$object = new ActionComm($db);
$contact = new Contact($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

//var_dump($_POST);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('actioncard'));


/*
 * Actions
 */

// Remove user to assigned list
if (! empty($_POST['removedassigned']))
{
	$idtoremove=$_POST['removedassigned'];
	if (! empty($_SESSION['assignedtouser'])) $tmpassigneduserids=dol_json_decode($_SESSION['assignedtouser'],1);
	else $tmpassigneduserids=array();
	foreach ($tmpassigneduserids as $key => $val)
	{
		if ($val['id'] == $idtoremove) unset($tmpassigneduserids[$key]);
	}
	//var_dump($_POST['removedassigned']);exit;
	$_SESSION['assignedtouser']=dol_json_encode($tmpassigneduserids);
	$donotclearsession=1;
	if ($action == 'add') $action = 'create';
	if ($action == 'update') $action = 'edit';
}

// Add user to assigned list
if (GETPOST('addassignedtouser') || GETPOST('updateassignedtouser'))
{
	// Add a new user
	if (GETPOST('assignedtouser') > 0)
	{
		$assignedtouser=array();
		if (! empty($_SESSION['assignedtouser']))
		{
			$assignedtouser=dol_json_decode($_SESSION['assignedtouser'], true);
		}
		$assignedtouser[GETPOST('assignedtouser')]=array('id'=>GETPOST('assignedtouser'), 'transparency'=>GETPOST('transparency'),'mandatory'=>1);
		$_SESSION['assignedtouser']=dol_json_encode($assignedtouser);
	}
	$donotclearsession=1;
	if ($action == 'add') $action = 'create';
	if ($action == 'update') $action = 'edit';
}

// Add event
if ($action == 'add')
{
	$error=0;

    if (empty($backtopage))
    {
        if ($socid > 0) $backtopage = DOL_URL_ROOT.'/societe/agenda.php?socid='.$socid;
        else $backtopage=DOL_URL_ROOT.'/comm/action/index.php';
    }

    if ($contactid)
	{
		$result=$contact->fetch($contactid);
	}

	if ($cancel)
	{
		header("Location: ".$backtopage);
		exit;
	}

    $percentage=in_array(GETPOST('status'),array(-1,100))?GETPOST('status'):GETPOST("percentage");	// If status is -1 or 100, percentage is not defined and we must use status

    // Clean parameters
	$datep=dol_mktime($fulldayevent?'00':GETPOST("aphour"), $fulldayevent?'00':GETPOST("apmin"), 0, GETPOST("apmonth"), GETPOST("apday"), GETPOST("apyear"));
	$datef=dol_mktime($fulldayevent?'23':GETPOST("p2hour"), $fulldayevent?'59':GETPOST("p2min"), $fulldayevent?'59':'0', GETPOST("p2month"), GETPOST("p2day"), GETPOST("p2year"));

	// Check parameters
	if (! $datef && $percentage == 100)
	{
		$error++; $donotclearsession=1;
		$action = 'create';
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateEnd")), 'errors');
	}

	if (empty($conf->global->AGENDA_USE_EVENT_TYPE) && ! GETPOST('label'))
	{
		$error++; $donotclearsession=1;
		$action = 'create';
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Title")), 'errors');
	}

	// Initialisation objet cactioncomm
	if (! GETPOST('actioncode') > 0)
	{
		$error++; $donotclearsession=1;
		$action = 'create';
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")), 'errors');
	}
	else
	{
		$result=$cactioncomm->fetch(GETPOST('actioncode'));
	}

	// Initialisation objet actioncomm
	$object->type_id = $cactioncomm->id;
	$object->type_code = $cactioncomm->code;
	$object->priority = GETPOST("priority")?GETPOST("priority"):0;
	$object->fulldayevent = (! empty($fulldayevent)?1:0);
	$object->location = GETPOST("location");
	$object->label = trim(GETPOST('label'));
	$object->fk_element = GETPOST("fk_element");
	$object->elementtype = GETPOST("elementtype");
	if (! GETPOST('label'))
	{
		if (GETPOST('actioncode') == 'AC_RDV' && $contact->getFullName($langs))
		{
			$object->label = $langs->transnoentitiesnoconv("TaskRDVWith",$contact->getFullName($langs));
		}
		else
		{
			if ($langs->trans("Action".$object->type_code) != "Action".$object->type_code)
			{
				$object->label = $langs->transnoentitiesnoconv("Action".$object->type_code)."\n";
			}
			else $object->label = $cactioncomm->libelle;
		}
	}
	$object->fk_project = isset($_POST["projectid"])?$_POST["projectid"]:0;
	$object->datep = $datep;
	$object->datef = $datef;
	$object->percentage = $percentage;
	$object->duree=((float) (GETPOST('dureehour') * 60) + (float) GETPOST('dureemin')) * 60;

	$listofuserid=array();
	if (! empty($_SESSION['assignedtouser'])) $listofuserid=dol_json_decode($_SESSION['assignedtouser']);
	$i=0;
	foreach($listofuserid as $key => $value)
	{
		if ($i == 0)	// First entry
		{
			$usertodo=new User($db);
			if ($value['id'] > 0)
			{
				$usertodo->fetch($value['id']);
			}
			$object->usertodo = $usertodo;
			$object->transparency = (GETPOST("transparency")=='on'?1:0);
		}

		$object->userassigned[$value['id']]=array('id'=>$value['id'], 'transparency'=>(GETPOST("transparency")=='on'?1:0));

		$i++;
	}

	if (! empty($conf->global->AGENDA_ENABLE_DONEBY))
	{
		$userdone=new User($db);
		if ($_POST["doneby"] > 0)
		{
			$userdone->fetch($_POST["doneby"]);
		}
		$object->userdone = $userdone;
	}

	$object->note = trim($_POST["note"]);

	if (isset($_POST["contactid"])) $object->contact = $contact;

	if (GETPOST('socid','int') > 0)
	{
		$societe = new Societe($db);
		$societe->fetch(GETPOST('socid','int'));
		$object->societe = $societe;	// deprecated
		$object->thirdparty = $societe;
	}

	// Special for module webcal and phenix
	// TODO external modules
	if (! empty($conf->webcalendar->enabled) && GETPOST('add_webcal') == 'on') $object->use_webcal=1;
	if (! empty($conf->phenix->enabled) && GETPOST('add_phenix') == 'on') $object->use_phenix=1;

	// Check parameters
	if (empty($object->usertodo))
	{
		$error++; $donotclearsession=1;
		$action = 'create';
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ActionAffectedTo")), 'errors');
	}
	if ($object->type_code == 'AC_RDV' && ($datep == '' || ($datef == '' && empty($fulldayevent))))
	{
		$error++; $donotclearsession=1;
		$action = 'create';
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateEnd")), 'errors');
	}

	if (! GETPOST('apyear') && ! GETPOST('adyear'))
	{
		$error++; $donotclearsession=1;
		$action = 'create';
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")), 'errors');
	}

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

	if (! $error)
	{
		$db->begin();

		// On cree l'action
		$idaction=$object->add($user);

		if ($idaction > 0)
		{
			if (! $object->error)
			{
				unset($_SESSION['assignedtouser']);

				$db->commit();
				if (! empty($backtopage))
				{
					dol_syslog("Back to ".$backtopage);
					header("Location: ".$backtopage);
				}
				elseif($idaction)
				{
					header("Location: ".DOL_URL_ROOT.'/comm/action/card.php?id='.$idaction);
				}
				else
				{
					header("Location: ".DOL_URL_ROOT.'/comm/action/index.php');
				}
				exit;
			}
			else
			{
				// If error
				$db->rollback();
				$langs->load("errors");
				$error=$langs->trans($object->error);
				setEventMessage($error,'errors');
				$action = 'create'; $donotclearsession=1;
			}
		}
		else
		{
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create'; $donotclearsession=1;
		}
	}
}

/*
 * Action update event
 */
if ($action == 'update')
{
	if (empty($cancel))
	{
        $fulldayevent=GETPOST('fullday');
        $aphour=GETPOST('aphour');
        $apmin=GETPOST('apmin');
        $p2hour=GETPOST('p2hour');
        $p2min=GETPOST('p2min');
		$percentage=in_array(GETPOST('status'),array(-1,100))?GETPOST('status'):(in_array(GETPOST('complete'),array(-1,100))?GETPOST('complete'):GETPOST("percentage"));	// If status is -1 or 100, percentage is not defined and we must use status

	    // Clean parameters
		if ($aphour == -1) $aphour='0';
		if ($apmin == -1) $apmin='0';
		if ($p2hour == -1) $p2hour='0';
		if ($p2min == -1) $p2min='0';

		$object->fetch($id);
		$object->fetch_userassigned();

		$datep=dol_mktime($fulldayevent?'00':$aphour, $fulldayevent?'00':$apmin, 0, $_POST["apmonth"], $_POST["apday"], $_POST["apyear"]);
		$datef=dol_mktime($fulldayevent?'23':$p2hour, $fulldayevent?'59':$p2min, $fulldayevent?'59':'0', $_POST["p2month"], $_POST["p2day"], $_POST["p2year"]);

		$object->fk_action   = dol_getIdFromCode($db, $_POST["actioncode"], 'c_actioncomm');
		$object->label       = $_POST["label"];
		$object->datep       = $datep;
		$object->datef       = $datef;
		$object->percentage  = $percentage;
		$object->priority    = $_POST["priority"];
        $object->fulldayevent= $_POST["fullday"]?1:0;
		$object->location    = GETPOST('location');
		$object->socid       = $_POST["socid"];
		$object->contactid   = $_POST["contactid"];
		$object->societe->id = $_POST["socid"];			// deprecated
		$object->contact->id = $_POST["contactid"];		// deprecated
		$object->fk_project  = $_POST["projectid"];
		$object->note        = $_POST["note"];
		$object->pnote       = $_POST["note"];
		$object->fk_element	 = $_POST["fk_element"];
		$object->elementtype = $_POST["elementtype"];

		if (! $datef && $percentage == 100)
		{
			$error++; $donotclearsession=1;
			setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateEnd")),$object->errors,'errors');
			$action = 'edit';
		}
		// Users
		$listofuserid=array();
		//$assignedtouser=(GETPOST("assignedtouser") >0)?GETPOST("assignedtouser"):(! empty($object->usertodo->id) && $object->usertodo->id > 0 ? $object->usertodo->id : 0);
		$assignedtouser=(! empty($object->usertodo->id) && $object->usertodo->id > 0 ? $object->usertodo->id : 0);
		if ($assignedtouser) $listofuserid[$assignedtouser]=array('id'=>$assignedtouser, 'mandatory'=>0, 'transparency'=>$object->transparency);	// Owner first

		if (! empty($_SESSION['assignedtouser']))
		{
			// Restore array with key with same value than param 'id'
			$tmplist1=dol_json_decode($_SESSION['assignedtouser'], true); $tmplist2=array();
			foreach($tmplist1 as $key => $val)
			{
				if ($val['id'] && $val['id'] != $assignedtouser) $listofuserid[$val['id']]=$val;
			}
		}

		$object->userassigned=array();	// Clear old content
		foreach($listofuserid as $key => $val)
		{
			$object->userassigned[$val['id']]=array('id'=>$val['id'], 'mandatory'=>0, 'transparency'=>(GETPOST("transparency")=='on'?1:0));
		}

		if (! empty($conf->global->AGENDA_ENABLE_DONEBY))
		{
			$userdone=new User($db);
			if ($_POST["doneby"])
			{
				$userdone->fetch($_POST["doneby"]);
			}
			$object->userdone = $userdone;
		}

		// Check parameters
		if (! GETPOST('actioncode') > 0)
		{
			$error++; $donotclearsession=1;
			$action = 'edit';
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")), 'errors');
		}
		else
		{
			$result=$cactioncomm->fetch(GETPOST('actioncode'));
		}
		if (empty($object->usertodo))
		{
			$error++; $donotclearsession=1;
			$action = 'edit';
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ActionAffectedTo")), 'errors');
		}

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

		if (! $error)
		{
			$db->begin();

			$result=$object->update($user);

			if ($result > 0)
			{
				unset($_SESSION['assignedtouser']);

				$db->commit();
			}
			else
			{
				setEventMessages($object->error,$object->errors,'errors');
				$db->rollback();
			}
		}
	}

	if (! $error)
	{
        if (! empty($backtopage))
        {
        	unset($_SESSION['assignedtouser']);
            header("Location: ".$backtopage);
            exit;
        }
	}
}

/*
 * delete event
 */
if ($action == 'confirm_delete' && GETPOST("confirm") == 'yes')
{
	$object->fetch($id);

	if ($user->rights->agenda->myactions->delete
		|| $user->rights->agenda->allactions->delete)
	{
		$result=$object->delete();

		if ($result >= 0)
		{
			header("Location: index.php");
			exit;
		}
		else
		{
			setEventMessages($object->error,$object->errors,'errors');
		}
	}
}

/*
 * Action move update, used when user move an event in calendar by drag'n drop
 */
if ($action == 'mupdate')
{
    $object->fetch($id);
    $object->fetch_userassigned();

    $shour = dol_print_date($object->datep,"%H");
    $smin = dol_print_date($object->datep, "%M");

    $newdate=GETPOST('newdate','alpha');
    if (empty($newdate) || strpos($newdate,'dayevent_') != 0 )
    {
       header("Location: ".$backtopage);
        exit;
    }

    $datep=dol_mktime($shour, $smin, 0, substr($newdate,13,2), substr($newdate,15,2), substr($newdate,9,4));
    if ($datep!=$object->datep)
    {
        if (!empty($object->datef))
        {
            $object->datef+=$datep-$object->datep;
        }
        $object->datep=$datep;
        $result=$object->update($user);
        if ($result < 0)
        {
            setEventMessage($object->error,'errors');
            setEventMessage($object->errors,'errors');
        }
    }
    if (! empty($backtopage))
    {
        header("Location: ".$backtopage);
        exit;
    }
    else
    {
        $action='';
    }

}


/*
 * View
 */

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);

$form = new Form($db);
$formactions = new FormActions($db);

if ($action == 'create')
{
	$contact = new Contact($db);

	if (GETPOST("contactid"))
	{
		$result=$contact->fetch(GETPOST("contactid"));
		if ($result < 0) dol_print_error($db,$contact->error);
	}

	dol_set_focus("#label");

    if (! empty($conf->use_javascript_ajax))
    {
        print "\n".'<script type="text/javascript">';
        print '$(document).ready(function () {
        			function setdatefields()
	            	{
	            		if ($("#fullday:checked").val() == null) {
	            			$(".fulldaystarthour").removeAttr("disabled");
	            			$(".fulldaystartmin").removeAttr("disabled");
	            			$(".fulldayendhour").removeAttr("disabled");
	            			$(".fulldayendmin").removeAttr("disabled");
	            			$("#p2").removeAttr("disabled");
	            		} else {
	            			$(".fulldaystarthour").attr("disabled","disabled").val("00");
	            			$(".fulldaystartmin").attr("disabled","disabled").val("00");
	            			$(".fulldayendhour").attr("disabled","disabled").val("23");
	            			$(".fulldayendmin").attr("disabled","disabled").val("59");
	            			$("#p2").removeAttr("disabled");
	            		}
	            	}
                    setdatefields();
                    $("#fullday").change(function() {
                        setdatefields();
                    });
                    $("#selectcomplete").change(function() {
                        if ($("#selectcomplete").val() == 100)
                        {
                            if ($("#doneby").val() <= 0) $("#doneby").val(\''.$user->id.'\');
                        }
                        if ($("#selectcomplete").val() == 0)
                        {
                            $("#doneby").val(-1);
                        }
                   });
                   $("#actioncode").change(function() {
                        if ($("#actioncode").val() == \'AC_RDV\') $("#dateend").addClass("fieldrequired");
                        else $("#dateend").removeClass("fieldrequired");
                   });
               })';
        print '</script>'."\n";
    }

	print '<form name="formaction" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]).'">';

	if (GETPOST("actioncode") == 'AC_RDV') print_fiche_titre($langs->trans("AddActionRendezVous"));
	else print_fiche_titre($langs->trans("AddAnAction"));

	print '<table class="border" width="100%">';

	// Type d'action actifs
	if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
	{
		print '<tr><td width="30%"><span class="fieldrequired">'.$langs->trans("Type").'</span></b></td><td>';
		$formactions->select_type_actions(GETPOST("actioncode")?GETPOST("actioncode"):$object->type_code, "actioncode","systemauto");
		print '</td></tr>';
	}
	else print '<input type="hidden" name="actioncode" value="AC_OTH">';

	// Title
	print '<tr><td'.(empty($conf->global->AGENDA_USE_EVENT_TYPE)?' class="fieldrequired"':'').'>'.$langs->trans("Title").'</td><td><input type="text" id="label" name="label" size="60" value="'.GETPOST('label').'"></td></tr>';

    // Full day
    print '<tr><td>'.$langs->trans("EventOnFullDay").'</td><td><input type="checkbox" id="fullday" name="fullday" '.(GETPOST('fullday')?' checked="checked"':'').'></td></tr>';

	// Date start
	$datep=($datep?$datep:$object->datep);
	if (GETPOST('datep','int',1)) $datep=dol_stringtotime(GETPOST('datep','int',1),0);
	print '<tr><td width="30%" class="nowrap"><span class="fieldrequired">'.$langs->trans("DateActionStart").'</span></td><td>';
	if (GETPOST("afaire") == 1) $form->select_date($datep,'ap',1,1,0,"action",1,1,0,0,'fulldayend');
	else if (GETPOST("afaire") == 2) $form->select_date($datep,'ap',1,1,1,"action",1,1,0,0,'fulldayend');
	else $form->select_date($datep,'ap',1,1,1,"action",1,1,0,0,'fulldaystart');
	print '</td></tr>';

	// Date end
	$datef=($datef?$datef:$object->datef);
    if (GETPOST('datef','int',1)) $datef=dol_stringtotime(GETPOST('datef','int',1),0);
	if (empty($datef) && ! empty($datep) && ! empty($conf->global->AGENDA_AUTOSET_END_DATE_WITH_DELTA_HOURS))
	{
		$datef=dol_time_plus_duree($datep, $conf->global->AGENDA_AUTOSET_END_DATE_WITH_DELTA_HOURS, 'h');
	}
	print '<tr><td><span id="dateend"'.(GETPOST("actioncode") == 'AC_RDV'?' class="fieldrequired"':'').'>'.$langs->trans("DateActionEnd").'</span></td><td>';
	if (GETPOST("afaire") == 1) $form->select_date($datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
	else if (GETPOST("afaire") == 2) $form->select_date($datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
	else $form->select_date($datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
	print '</td></tr>';

	// Status
	print '<tr><td width="10%">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td>';
	print '<td>';
	$percent=-1;
	if (isset($_GET['status']) || isset($_POST['status'])) $percent=GETPOST('status');
	else if (isset($_GET['percentage']) || isset($_POST['percentage'])) $percent=GETPOST('percentage');
	else
	{
		if (GETPOST('complete') == '0' || GETPOST("afaire") == 1) $percent='0';
		else if (GETPOST('complete') == 100 || GETPOST("afaire") == 2) $percent=100;
	}
	$formactions->form_select_status_action('formaction',$percent,1,'complete');
	print '</td></tr>';

    // Location
    if (empty($conf->global->AGENDA_DISABLE_LOCATION))
    {
		print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3"><input type="text" name="location" size="50" value="'.(GETPOST('location')?GETPOST('location'):$object->location).'"></td></tr>';
    }

	// Assigned to
	print '<tr><td class="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td>';
	$listofuserid=array();
	if (empty($donotclearsession))
	{
		$assignedtouser=GETPOST("assignedtouser")?GETPOST("assignedtouser"):(! empty($object->usertodo->id) && $object->usertodo->id > 0 ? $object->usertodo->id : $user->id);
		if ($assignedtouser) $listofuserid[$assignedtouser]=array('id'=>$assignedtouser,'mandatory'=>0,'transparency'=>$object->transparency);	// Owner first
		$_SESSION['assignedtouser']=dol_json_encode($listofuserid);
	}
	/*
	if (empty($donotclearsession))
	{
		$assignedtouser=GETPOST("assignedtouser")?GETPOST("assignedtouser"):(! empty($object->usertodo->id) && $object->usertodo->id > 0 ? $object->usertodo->id : $user->id);
		$_SESSION['assignedtouser']=dol_json_encode(array($assignedtouser=>array('id'=>$assignedtouser,'transparency'=>1,'mandatory'=>1)));
	}*/
	print $form->select_dolusers_forevent(($action=='create'?'add':'update'),'assignedtouser',1);
	//print $form->select_dolusers(GETPOST("assignedtouser")?GETPOST("assignedtouser"):(! empty($object->usertodo->id) && $object->usertodo->id > 0 ? $object->usertodo->id : $user->id),'affectedto',1);
	print '</td></tr>';

	print '</table>';

	print '<br><br>';

	print '<table class="border" width="100%">';

	// Busy
	print '<tr><td width="30%" class="nowrap">'.$langs->trans("Busy").'</td><td>';
	print '<input id="transparency" type="checkbox" name="transparency"'.(((! isset($_GET['transparency']) && ! isset($_POST['transparency'])) || GETPOST('transparency'))?' checked="checked"':'').'>';
	print '</td></tr>';

	// Realised by
	if (! empty($conf->global->AGENDA_ENABLE_DONEBY))
	{
		print '<tr><td class="nowrap">'.$langs->trans("ActionDoneBy").'</td><td>';
		print $form->select_dolusers(GETPOST("doneby")?GETPOST("doneby"):(! empty($object->userdone->id) && $percent==100?$object->userdone->id:0),'doneby',1);
		print '</td></tr>';
	}

	print '</table>';
	print '<br><br>';
	print '<table class="border" width="100%">';

	// Societe, contact
	print '<tr><td width="30%" class="nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	if (GETPOST('socid','int') > 0)
	{
		$societe = new Societe($db);
		$societe->fetch(GETPOST('socid','int'));
		print $societe->getNomUrl(1);
		print '<input type="hidden" id="socid" name="socid" value="'.GETPOST('socid','int').'">';
	}
	else
	{

		$events=array();
		$events[]=array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php',1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
		//For external user force the company to user company
		if (!empty($user->societe_id)) {
			print $form->select_company($user->societe_id,'socid','',1,1,0,$events);
		} else {
			print $form->select_company('','socid','',1,1,0,$events);
		}

	}
	print '</td></tr>';

	print '<tr><td class="nowrap">'.$langs->trans("ActionOnContact").'</td><td>';
	$form->select_contacts(GETPOST('socid','int'),GETPOST('contactid'),'contactid',1);
	print '</td></tr>';


	// Project
	if (! empty($conf->projet->enabled))
	{
		$formproject=new FormProjets($db);

		// Projet associe
		$langs->load("projects");

		print '<tr><td valign="top">'.$langs->trans("Project").'</td><td>';

		$numproject=$formproject->select_projects((! empty($societe->id)?$societe->id:0),GETPOST("projectid")?GETPOST("projectid"):'','projectid');
		if ($numproject==0)
		{
			print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$societe->id.'&action=create">'.$langs->trans("AddProject").'</a>';
		}
		print '</td></tr>';
	}
	if(!empty($origin) && !empty($originid))
	{
		print '<input type="hidden" name="fk_element" size="10" value="'.GETPOST('originid').'">';
		print '<input type="hidden" name="elementtype" size="10" value="'.GETPOST('origin').'">';
	}

	if (GETPOST("datep") && preg_match('/^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])$/',GETPOST("datep"),$reg))
	{
		$object->datep=dol_mktime(0,0,0,$reg[2],$reg[3],$reg[1]);
	}

	// Priority
	print '<tr><td class="nowrap">'.$langs->trans("Priority").'</td><td colspan="3">';
	print '<input type="text" name="priority" value="'.(GETPOST('priority')?GETPOST('priority'):($object->priority?$object->priority:'')).'" size="5">';
	print '</td></tr>';

    // Description
    print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('note',(GETPOST('note')?GETPOST('note'):$object->note),'',180,'dolibarr_notes','In',true,true,$conf->fckeditor->enabled,ROWS_6,90);
    $doleditor->Create();
    print '</td></tr>';


    // Other attributes
    $parameters=array('id'=>$object->id);
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook


	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $object->showOptionals($extrafields,'edit');
	}

	print '</table>';

	print '<center><br>';
	print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</center>';

	print "</form>";
}

// View or edit
if ($id > 0)
{
	$result1=$object->fetch($id);
	$result2=$object->fetch_thirdparty();
	$result3=$object->fetch_userassigned();
	$result4=$object->fetch_optionals($id,$extralabels);

	if ($result1 < 0 || $result2 < 0 || $result3 < 0 || $result4 < 0)
	{
		dol_print_error($db,$object->error);
		exit;
	}

	if ($object->author->id > 0)   { $tmpuser=new User($db); $res=$tmpuser->fetch($object->author->id); $object->author=$tmpuser; }
	if ($object->usermod->id > 0)  { $tmpuser=new User($db); $res=$tmpuser->fetch($object->usermod->id); $object->usermod=$tmpuser; }
	if ($object->usertodo->id > 0) { $tmpuser=new User($db); $res=$tmpuser->fetch($object->usertodo->id); $object->usertodo=$tmpuser; }
	if ($object->userdone->id > 0) { $tmpuser=new User($db); $res=$tmpuser->fetch($object->userdone->id); $object->userdone=$tmpuser; }

	$contact = new Contact($db);
	if ($object->contact->id)
	{
		$result=$contact->fetch($object->contact->id,$user);
	}
	$object->contact = $contact;

	/*
	 * Affichage onglets
	 */

	$head=actions_prepare_head($object);

	$now=dol_now();
	$delay_warning=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;

	// Confirmation suppression action
	if ($action == 'delete')
	{
		print $form->formconfirm("card.php?id=".$id,$langs->trans("DeleteAction"),$langs->trans("ConfirmDeleteAction"),"confirm_delete",'','',1);
	}

	if ($action == 'edit')
	{
	    if (! empty($conf->use_javascript_ajax))
        {
            print "\n".'<script type="text/javascript">';
            print '$(document).ready(function () {
	            		function setdatefields()
	            		{
	            			if ($("#fullday:checked").val() == null) {
	            				$(".fulldaystarthour").removeAttr("disabled");
	            				$(".fulldaystartmin").removeAttr("disabled");
	            				$(".fulldayendhour").removeAttr("disabled");
	            				$(".fulldayendmin").removeAttr("disabled");
	            			} else {
	            				$(".fulldaystarthour").attr("disabled","disabled").val("00");
	            				$(".fulldaystartmin").attr("disabled","disabled").val("00");
	            				$(".fulldayendhour").attr("disabled","disabled").val("23");
	            				$(".fulldayendmin").attr("disabled","disabled").val("59");
	            			}
	            		}
	            		setdatefields();
	            		$("#fullday").change(function() {
	            			setdatefields();
	            		});
                   })';
            print '</script>'."\n";
        }

        // Fiche action en mode edition
		print '<form name="formaction" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="ref_ext" value="'.$object->ref_ext.'">';
		if ($backtopage) print '<input type="hidden" name="backtopage" value="'.($backtopage != '1'? $backtopage : $_SERVER["HTTP_REFERER"]).'">';

		dol_fiche_head($head, 'card', $langs->trans("Action"),0,'action');

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">'.$object->id.'</td></tr>';

		// Type
		if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
		{
			print '<tr><td class="fieldrequired">'.$langs->trans("Type").'</td><td colspan="3">';
			$formactions->select_type_actions(GETPOST("actioncode")?GETPOST("actioncode"):$object->type_code, "actioncode","systemauto");
			print '</td></tr>';
		}

		// Title
		print '<tr><td'.(empty($conf->global->AGENDA_USE_EVENT_TYPE)?' class="fieldrequired"':'').'>'.$langs->trans("Title").'</td><td colspan="3"><input type="text" name="label" size="50" value="'.$object->label.'"></td></tr>';

        // Full day event
        print '<tr><td class="fieldrequired">'.$langs->trans("EventOnFullDay").'</td><td colspan="3"><input type="checkbox" id="fullday" name="fullday" '.($object->fulldayevent?' checked="checked"':'').'></td></tr>';

		// Date start
		print '<tr><td class="nowrap"><span class="fieldrequired">'.$langs->trans("DateActionStart").'</span></td><td colspan="3">';
		if (GETPOST("afaire") == 1) $form->select_date($object->datep,'ap',1,1,0,"action",1,1,0,0,'fulldaystart');
		else if (GETPOST("afaire") == 2) $form->select_date($object->datep,'ap',1,1,1,"action",1,1,0,0,'fulldaystart');
		else $form->select_date($object->datep,'ap',1,1,1,"action",1,1,0,0,'fulldaystart');
		print '</td></tr>';
		// Date end
		print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="3">';
		if (GETPOST("afaire") == 1) $form->select_date($object->datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
		else if (GETPOST("afaire") == 2) $form->select_date($object->datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
		else $form->select_date($object->datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
		print '</td></tr>';

		// Status
		print '<tr><td class="nowrap">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="3">';
		$percent=GETPOST("percentage")?GETPOST("percentage"):$object->percentage;
		$formactions->form_select_status_action('formaction',$percent,1);
		print '</td></tr>';

        // Location
	    if (empty($conf->global->AGENDA_DISABLE_LOCATION))
	    {
			print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3"><input type="text" name="location" size="50" value="'.$object->location.'"></td></tr>';
	    }

		// Assigned to
		print '<tr><td class="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
		$listofuserid=array();
		if (empty($donotclearsession))
		{
			if (is_object($object->usertodo)) $listofuserid[$object->usertodo->id]=array('id'=>$object->usertodo->id,'transparency'=>$object->transparency);	// Owner first
			$listofuserid=array_merge($listofuserid,$object->userassigned);
			$_SESSION['assignedtouser']=dol_json_encode($listofuserid);
		}
		print $form->select_dolusers_forevent(($action=='create'?'add':'update'),'assignedtouser',1);
		//print $form->select_dolusers($object->usertodo->id>0?$object->usertodo->id:-1,'assignedtouser',1);
		print '</td></tr>';

        print '</table><br><br><table class="border" width="100%">';

		// Busy
		print '<tr><td width="30%" class="nowrap">'.$langs->trans("Busy").'</td><td>';
		print '<input id="transparency" type="checkbox" name="transparency"'.($object->transparency?' checked="checked"':'').'">';
		print '</td></tr>';

		// Realised by
		if (! empty($conf->global->AGENDA_ENABLE_DONEBY))
		{
			print '<tr><td class="nowrap">'.$langs->trans("ActionDoneBy").'</td><td colspan="3">';
			print $form->select_dolusers($object->userdone->id> 0?$object->userdone->id:-1,'doneby',1);
			print '</td></tr>';
		}

		print '</table><br><br>';

		print '<table class="border" width="100%">';

		// Thirdparty - Contact
		if ($conf->societe->enabled)
		{
			print '<tr><td width="30%">'.$langs->trans("ActionOnCompany").'</td>';
			print '<td>';
			$events=array();
			$events[]=array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php',1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
			print $form->select_company($object->thirdparty->id,'socid','',1,1,0,$events);
			print '</td>';

			// Contact
			print '<td>'.$langs->trans("Contact").'</td><td width="30%">';
			$form->select_contacts($object->thirdparty->id, $object->contact->id,'contactid',1);
			print '</td></tr>';
		}

		// Project
		if (! empty($conf->projet->enabled))
		{

			$formproject=new FormProjets($db);

			// Projet associe
			$langs->load("project");

			print '<tr><td width="30%" valign="top">'.$langs->trans("Project").'</td><td colspan="3">';
			$numprojet=$formproject->select_projects($object->thirdparty->id,$object->fk_project,'projectid');
			if ($numprojet==0)
			{
				print ' &nbsp; <a href="../../projet/card.php?socid='.$societe->id.'&action=create">'.$langs->trans("AddProject").'</a>';
			}
			print '</td></tr>';
		}

		// Priority
		print '<tr><td nowrap width="30%">'.$langs->trans("Priority").'</td><td colspan="3">';
		print '<input type="text" name="priority" value="'.($object->priority?$object->priority:'').'" size="5">';
		print '</td></tr>';

		// Object linked
		if (! empty($object->fk_element) && ! empty($object->elementtype))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
			print '<td colspan="3">'.dolGetElementUrl($object->fk_element,$object->elementtype,1).'</td></tr>';
		}

        // Description
        print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">';
        // Editeur wysiwyg
        require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
        $doleditor=new DolEditor('note',$object->note,'',240,'dolibarr_notes','In',true,true,$conf->fckeditor->enabled,ROWS_5,90);
        $doleditor->Create();
        print '</td></tr>';

        // Other attributes
        $parameters=array('colspan'=>' colspan="3"', 'colspanvalue'=>'3', 'id'=>$object->id);
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields,'edit');
		}

		print '</table>';

		dol_fiche_end();

		print '<center><input type="submit" class="button" name="edit" value="'.$langs->trans("Save").'">';
		print ' &nbsp; &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</center>';

		print '</form>';
	}
	else
	{
		dol_fiche_head($head, 'card', $langs->trans("Action"),0,'action');

		// Affichage fiche action en mode visu
		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/comm/action/listactions.php">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object, 'id', $linkback, ($user->societe_id?0:1), 'id', 'ref', '');
		print '</td></tr>';

		// Type
		if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
		{
			print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$object->type.'</td></tr>';
		}

		// Title
		print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$object->label.'</td></tr>';

        // Full day event
        print '<tr><td>'.$langs->trans("EventOnFullDay").'</td><td colspan="3">'.yn($object->fulldayevent).'</td></tr>';

		$rowspan=4;
		if (empty($conf->global->AGENDA_DISABLE_LOCATION)) $rowspan++;

		// Date start
		print '<tr><td width="30%">'.$langs->trans("DateActionStart").'</td><td colspan="2">';
		if (! $object->fulldayevent) print dol_print_date($object->datep,'dayhour');
		else print dol_print_date($object->datep,'day');
		if ($object->percentage == 0 && $object->datep && $object->datep < ($now - $delay_warning)) print img_warning($langs->trans("Late"));
		print '</td>';
		print '<td rowspan="'.$rowspan.'" align="center" valign="middle" width="180">'."\n";
        print '<form name="listactionsfiltermonth" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="show_month">';
        print '<input type="hidden" name="year" value="'.dol_print_date($object->datep,'%Y').'">';
        print '<input type="hidden" name="month" value="'.dol_print_date($object->datep,'%m').'">';
        print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
        //print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
        print img_picto($langs->trans("ViewCal"),'object_calendar','class="hideonsmartphone"').' <input type="submit" style="min-width: 120px" class="button" name="viewcal" value="'.$langs->trans("ViewCal").'">';
        print '</form>'."\n";
        print '<form name="listactionsfilterweek" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="show_week">';
        print '<input type="hidden" name="year" value="'.dol_print_date($object->datep,'%Y').'">';
        print '<input type="hidden" name="month" value="'.dol_print_date($object->datep,'%m').'">';
        print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
        //print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
        print img_picto($langs->trans("ViewCal"),'object_calendarweek','class="hideonsmartphone"').' <input type="submit" style="min-width: 120px" class="button" name="viewweek" value="'.$langs->trans("ViewWeek").'">';
        print '</form>'."\n";
        print '<form name="listactionsfilterday" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="show_day">';
        print '<input type="hidden" name="year" value="'.dol_print_date($object->datep,'%Y').'">';
        print '<input type="hidden" name="month" value="'.dol_print_date($object->datep,'%m').'">';
        print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
        //print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
        print img_picto($langs->trans("ViewCal"),'object_calendarday','class="hideonsmartphone"').' <input type="submit" style="min-width: 120px" class="button" name="viewday" value="'.$langs->trans("ViewDay").'">';
        print '</form>'."\n";
        print '<form name="listactionsfilterperuser" action="'.DOL_URL_ROOT.'/comm/action/peruser.php" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="show_peruser">';
        print '<input type="hidden" name="year" value="'.dol_print_date($object->datep,'%Y').'">';
        print '<input type="hidden" name="month" value="'.dol_print_date($object->datep,'%m').'">';
        print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
        //print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
        print img_picto($langs->trans("ViewCal"),'object_calendarperuser','class="hideonsmartphone"').' <input type="submit" style="min-width: 120px" class="button" name="viewperuser" value="'.$langs->trans("ViewPerUser").'">';
        print '</form>'."\n";
        print '</td>';
		print '</tr>';

		// Date end
		print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="2">';
        if (! $object->fulldayevent) print dol_print_date($object->datef,'dayhour');
		else print dol_print_date($object->datef,'day');
		if ($object->percentage > 0 && $object->percentage < 100 && $object->datef && $object->datef < ($now- $delay_warning)) print img_warning($langs->trans("Late"));
		print '</td></tr>';

		// Status
		print '<tr><td class="nowrap">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="2">';
		print $object->getLibStatut(4);
		print '</td></tr>';

        // Location
	    if (empty($conf->global->AGENDA_DISABLE_LOCATION))
    	{
			print '<tr><td>'.$langs->trans("Location").'</td><td colspan="2">'.$object->location.'</td></tr>';
    	}

		// Assigned to
		//if ($object->usertodo->id > 0) print $object->usertodo->getNomUrl(1);
    	print '<tr><td width="30%" class="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td>';
		$listofuserid=array();
		if (empty($donotclearsession))
		{
			if (is_object($object->usertodo)) $listofuserid[$object->usertodo->id]=array('id'=>$object->usertodo->id,'transparency'=>$object->transparency);	// Owner first
			$listofuserid=array_merge($listofuserid,$object->userassigned);
			$_SESSION['assignedtouser']=dol_json_encode($listofuserid);
			//var_dump($_SESSION['assignedtouser']);
		}
		print $form->select_dolusers_forevent('view','assignedtouser',1);
		print '</td></tr>';

		print '</table><br><br><table class="border" width="100%">';

		// Busy
		print '<tr><td width="30%" class="nowrap">'.$langs->trans("Busy").'</td><td colspan="3">';
		if ($object->usertodo->id > 0) print yn(($object->transparency > 0)?1:0);	// We show nothing if event is assigned to nobody
		print '</td></tr>';

		// Done by
		if ($conf->global->AGENDA_ENABLE_DONEBY)
		{
			print '<tr><td class="nowrap">'.$langs->trans("ActionDoneBy").'</td><td colspan="3">';
			if ($object->userdone->id > 0) print $object->userdone->getNomUrl(1);
			print '</td></tr>';
		}

		print '</table><br><br><table class="border" width="100%">';

		// Third party - Contact
		if ($conf->societe->enabled)
		{
			print '<tr><td width="30%">'.$langs->trans("ActionOnCompany").'</td><td>'.($object->thirdparty->id?$object->thirdparty->getNomUrl(1):$langs->trans("None"));
			if (is_object($object->thirdparty) && $object->thirdparty->id > 0 && $object->type_code == 'AC_TEL')
			{
				if ($object->thirdparty->fetch($object->thirdparty->id))
				{
					print "<br>".dol_print_phone($object->thirdparty->phone);
				}
			}
			print '</td>';
			print '<td>'.$langs->trans("Contact").'</td>';
			print '<td>';
			if ($object->contact->id > 0)
			{
				print $object->contact->getNomUrl(1);
				if ($object->contact->id && $object->type_code == 'AC_TEL')
				{
					if ($object->contact->fetch($object->contact->id))
					{
						print "<br>".dol_print_phone($object->contact->phone_pro);
					}
				}
			}
			else
			{
				print $langs->trans("None");
			}
			print '</td></tr>';
		}

		// Project
		if (! empty($conf->projet->enabled))
		{
			print '<tr><td width="30%" valign="top">'.$langs->trans("Project").'</td><td colspan="3">';
			if ($object->fk_project)
			{
				$project=new Project($db);
				$project->fetch($object->fk_project);
				print $project->getNomUrl(1,'',1);
			}
			print '</td></tr>';
		}

		// Priority
		print '<tr><td nowrap width="30%">'.$langs->trans("Priority").'</td><td colspan="3">';
		print ($object->priority?$object->priority:'');
		print '</td></tr>';

		// Object linked
		if (! empty($object->fk_element) && ! empty($object->elementtype))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
			print '<td colspan="3">'.dolGetElementUrl($object->fk_element,$object->elementtype,1).'</td></tr>';
		}

		// Description
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">';
		print dol_htmlentitiesbr($object->note);
		print '</td></tr>';

        // Other attributes
		$parameters=array('colspan'=>' colspan="3"', 'colspanvalue'=>'3', 'id'=>$object->id);
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

		print '</table>';

		//Extra field
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print '<br><br><table class="border" width="100%">';
			foreach($extrafields->attribute_label as $key=>$label)
			{
				$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:(isset($object->array_options['options_'.$key])?$object->array_options['options_'.$key]:''));
				print '<tr><td width="30%">'.$label.'</td><td>';
				print $extrafields->showOutputField($key,$value);
				print "</td></tr>\n";
			}
			print '</table>';
		}

		dol_fiche_end();
	}


	/*
	 * Barre d'actions
	 */

	print '<div class="tabsAction">';

	$parameters=array();
	$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook))
	{
		if ($action != 'edit')
		{
			if ($user->rights->agenda->allactions->create ||
			   (($object->author->id == $user->id || $object->usertodo->id == $user->id) && $user->rights->agenda->myactions->create))
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=edit&id='.$object->id.'">'.$langs->trans("Modify").'</a></div>';
			}
			else
			{
				print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Modify").'</a></div>';
			}

			if ($user->rights->agenda->allactions->delete ||
			   (($object->author->id == $user->id || $object->usertodo->id == $user->id) && $user->rights->agenda->myactions->delete))
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?action=delete&id='.$object->id.'">'.$langs->trans("Delete").'</a></div>';
			}
			else
			{
				print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Delete").'</a></div>';
			}
		}
	}

	print '</div>';
}


llxFooter();

$db->close();
