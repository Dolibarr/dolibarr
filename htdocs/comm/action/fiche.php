<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Herve Prot           <herve.prot@symeos.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/comm/action/fiche.php
 *       \ingroup    agenda
 *       \brief      Page for event card
 *       \version    $Id: fiche.php,v 1.227 2011/07/18 08:59:42 hregis Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formactions.class.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");
if($conf->lead->enabled) require_once(DOL_DOCUMENT_ROOT."/lead/class/lead.class.php");
if($conf->lead->enabled) require_once(DOL_DOCUMENT_ROOT."/lead/lib/lead.lib.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("other");
$langs->load("bills");
$langs->load("orders");
$langs->load("agenda");

$action=GETPOST("action");

// Security check
$socid = GETPOST('socid');
$id = GETPOST('id');
if ($user->societe_id) $socid=$user->societe_id;
// TODO: revoir les droits car pas clair
//$result = restrictedArea($user, 'agenda', $id, 'actioncomm', 'actions', '', 'id');

if (isset($_GET["error"])) $error=$_GET["error"];

$cactioncomm = new CActionComm($db);
$actioncomm = new ActionComm($db);
$contact = new Contact($db);
//var_dump($_POST);

/*
 * Action creation de l'action
 */
if ($action == 'add_action')
{
    $backtopage='';
    if (! empty($_POST["backtopage"])) $backtopage=$_POST["backtopage"];
    if (! $backtopage)
    {
        if ($socid > 0) $backtopage = DOL_URL_ROOT.'/societe/agenda.php?socid='.$socid;
        else $backtopage=DOL_URL_ROOT.'/comm/action/index.php';
    }

    if ($_POST["contactid"])
	{
		$result=$contact->fetch($_POST["contactid"]);
	}

	if ($_POST['cancel'])
	{
		header("Location: ".$backtopage);
		exit;
	}

    $fulldayevent=$_POST["fullday"];

    // Clean parameters
	$datep=dol_mktime(
	$fulldayevent?'00':$_POST["aphour"],
	$fulldayevent?'00':$_POST["apmin"],
	0,
	$_POST["apmonth"],
	$_POST["apday"],
	$_POST["apyear"]);
	
	$datef=dol_mktime(
	$fulldayevent?'23':$_POST["p2hour"],
	$fulldayevent?'59':$_POST["p2min"],
	$fulldayevent?'59':'0',
	$_POST["p2month"],
	$_POST["p2day"],
	$_POST["p2year"]);

	// Initialisation objet cactioncomm
	if (! $_POST["actioncode"])
	{
		$error=1;
		$action = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Type")).'</div>';
	}
	else
	{
		$result=$cactioncomm->fetch($_POST["actioncode"]);
	}
        
    if($cactioncomm->type == 2) //ACTION
    {
        if ($_POST["percentage"]==100)
            $datef=dol_now();
        else
            $datef='';
    }

		// Check parameters
	if (! $datef && $_POST["percentage"] == 100)
	{
		$error=1;
		$action = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("DateEnd")).'</div>';
	}

	// Initialisation objet actioncomm
	$actioncomm->type_id = $cactioncomm->id;
	$actioncomm->type_code = $cactioncomm->code;
	$actioncomm->priority = isset($_POST["priority"])?$_POST["priority"]:0;
	$actioncomm->fulldayevent = $_POST["fullday"]?1:0;
	$actioncomm->location = isset($_POST["location"])?$_POST["location"]:'';
	$actioncomm->label = trim($_POST["label"]);
	if (! $_POST["label"])
	{
		if ($cactioncomm->type == 1 && $contact->getFullName($langs)) //RDV
		{
			$actioncomm->label = $langs->transnoentitiesnoconv("TaskRDVWith",$contact->getFullName($langs));
		}
		else
		{
			if ($langs->trans("Action".$actioncomm->type_code) != "Action".$actioncomm->type_code)
			{
				$actioncomm->label = $langs->transnoentitiesnoconv("Action".$actioncomm->type_code)."\n";
			}
			else $actioncomm->label = $cactioncomm->libelle;
		}
	}
	$actioncomm->fk_project = isset($_POST["projectid"])?$_POST["projectid"]:0;
        $actioncomm->fk_lead = isset($_POST["leadid"])?$_POST["leadid"]:0;
        $actioncomm->propalrowid = isset($_POST["propalid"])?$_POST["propalid"]:0;
        $actioncomm->type = $cactioncomm->type;
        $actioncomm->fk_task = isset($_POST["fk_task"])?$_POST["fk_task"]:0;
	$actioncomm->datep = $datep;
	$actioncomm->datef = $datef;
	if ($cactioncomm->type == 2) //ACTION
            $actioncomm->durationp = !empty($_POST["duration"])?$_POST["duration"]*3600:3600;       
        
	if ($cactioncomm->type == 1) //RDV
	{
		// RDV
		if ($actioncomm->datef && $actioncomm->datef < dol_now('tzref'))
		{
			$actioncomm->percentage = 100;
		}
		else
		{
			$actioncomm->percentage = 0;
		}
	}
	else
	{
		$actioncomm->percentage = isset($_POST["percentage"])?$_POST["percentage"]:0;
	}
	$actioncomm->duree=(($_POST["dureehour"] * 60) + $_POST["dureemin"]) * 60;

	$usertodo=new User($db);
	if ($_POST["affectedto"] > 0)
	{
		$usertodo->fetch($_POST["affectedto"]);
	}
	$actioncomm->usertodo = $usertodo;
	$userdone=new User($db);
	if ($_POST["doneby"] > 0)
	{
		$userdone->fetch($_POST["doneby"]);
	}
	$actioncomm->userdone = $userdone;

	$actioncomm->note = trim($_POST["note"]);
	if (isset($_POST["contactid"])) $actioncomm->contact = $contact;
	if (GETPOST("socid") > 0)
	{
		$societe = new Societe($db);
		$societe->fetch(GETPOST("socid"));
		$actioncomm->societe = $societe;
                $actioncomm->socid=$actioncomm->societe->id;
	}

	// Special for module webcal and phenix
	if ($_POST["add_webcal"] == 'on' && $conf->webcalendar->enabled) $actioncomm->use_webcal=1;
	if ($_POST["add_phenix"] == 'on' && $conf->phenix->enabled) $actioncomm->use_phenix=1;

	// Check parameters
	if ($cactioncomm->type == 1 && ($datef == '')) //RDV
	{
		$error=1;
		$action = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateEnd")).'</div>';
	}
	if ($datea && $_POST["percentage"] == 0)
	{
		$error=1;
		$action = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorStatusCantBeZeroIfStarted").'</div>';
	}

	if (! $_POST["apyear"] && ! $_POST["adyear"])
	{
		$error=1;
		$action = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")).'</div>';
	}

	if (! $error)
	{
		$db->begin();

		// On cree l'action
		$idaction=$actioncomm->add($user);

		if ($idaction > 0)
		{
			if (! $actioncomm->error)
			{
				$db->commit();
				if (! empty($backtopage))
				{
					dol_syslog("Back to ".$backtopage);
					Header("Location: ".$backtopage);
				}
				elseif($idaction)
				{
					Header("Location: ".DOL_URL_ROOT.'/comm/action/fiche.php?id='.$idaction);
				}
				else
				{
					Header("Location: ".DOL_URL_ROOT.'/comm/action/index.php');
				}
				exit;
			}
			else
			{
				// Si erreur
				$db->rollback();
				$id=$idaction;
				$langs->load("errors");
				$error=$langs->trans($actioncomm->error);
			}
		}
		else
		{
			$db->rollback();
			$id=$idaction;
			$langs->load("errors");
			$error=$langs->trans($actioncomm->error);
		}
	}
}

/*
 * Action cloturer l'action
 */
if (GETPOST("action") == 'close')
{
    $actioncomm = new ActionComm($db);
    $actioncomm->fetch($id);

    if($user->rights->agenda->myactions->create || $user->rights->agenda->allactions->create)
    {
        $result=$actioncomm->close();

	if ($result >= 0)
	{
            Header("Location: ".DOL_URL_ROOT.'/comm/action/fiche.php?id='.$id);
            exit;
	}
	else
	{
            $mesg=$actioncomm->error;
        }
    }

}

/*
 * Action suppression de l'action
 */
if ($action == 'confirm_delete' && GETPOST("confirm") == 'yes')
{
	$actioncomm = new ActionComm($db);
	$actioncomm->fetch($id);

	if ($user->rights->agenda->myactions->delete
		|| $user->rights->agenda->allactions->delete)
	{
		$result=$actioncomm->delete();

		if ($result >= 0)
		{
			Header("Location: index.php");
			exit;
		}
		else
		{
			$mesg=$actioncomm->error;
		}
	}
}

/*
 * Action mise a jour de l'action
 */
if ($action == 'update')
{
	if (! $_POST["cancel"])
	{
        $fulldayevent=$_POST["fullday"];

	    // Clean parameters
		if ($_POST["aphour"] == -1) $_POST["aphour"]='0';
		if ($_POST["apmin"] == -1) $_POST["apmin"]='0';
		if ($_POST["p2hour"] == -1) $_POST["p2hour"]='0';
		if ($_POST["p2min"] == -1) $_POST["p2min"]='0';
		//if ($_POST["adhour"] == -1) $_POST["adhour"]='0';
		//if ($_POST["admin"] == -1) $_POST["admin"]='0';

		$actioncomm = new Actioncomm($db);
		$actioncomm->fetch($id);
                
                $result=$cactioncomm->fetch($actioncomm->type_id);
                
		$datep=dol_mktime(
        $fulldayevent?'00':$_POST["aphour"],
        $fulldayevent?'00':$_POST["apmin"],
		0,
		$_POST["apmonth"],
		$_POST["apday"],
		$_POST["apyear"]);

		$datef=dol_mktime(
        $fulldayevent?'23':$_POST["p2hour"],
        $fulldayevent?'59':$_POST["p2min"],
		$fulldayevent?'59':'0',
		$_POST["p2month"],
		$_POST["p2day"],
		$_POST["p2year"]);
		/*$datea=dol_mktime($_POST["adhour"],
		 $_POST["admin"],
		 0,
		 $_POST["admonth"],
		 $_POST["adday"],
		 $_POST["adyear"]);
		 $datea2=dol_mktime($_POST["a2hour"],
		 $_POST["a2min"],
		 0,
		 $_POST["a2month"],
		 $_POST["a2day"],
		 $_POST["a2year"]);
		 */

		//print $_POST["apmonth"].",".$_POST["apday"].",".$_POST["apyear"].",".$_POST["aphour"].",".$_POST["apmin"]."<br>\n";
		//print $actioncomm->datep;
		//print 'dddd'.$datep;
		$actioncomm->label       = $_POST["label"];
		$actioncomm->datep       = $datep;
		$actioncomm->datef       = $datef;
		//$actioncomm->date        = $datea;
		//$actioncomm->dateend     = $datea2;
		$actioncomm->percentage  = $_POST["percentage"];
		$actioncomm->priority    = $_POST["priority"];
        $actioncomm->fulldayevent= $_POST["fullday"]?1:0;
		$actioncomm->location    = isset($_POST["location"])?$_POST["location"]:'';
		$actioncomm->societe->id = $_POST["socid"];
		$actioncomm->contact->id = $_POST["contactid"];
		$actioncomm->fk_project  = $_POST["projectid"];
                $actioncomm->fk_lead     = $_POST["leadid"];
                $actioncomm->propalrowid     = $_POST["propalid"];
		$actioncomm->note        = $_POST["note"];
		$actioncomm->pnote       = $_POST["note"];
                $actioncomm->fk_task    = $_POST["fk_task"];
                $actioncomm->type = $cactioncomm->type;
                
                if ($actioncomm->type==2) //ACTION
                    $actioncomm->durationp = !empty($_POST["duration"])?$_POST["duration"]*3600:3600;

		if ($cactioncomm->type==2 && ! $datef && $_POST["percentage"] == 100)
		{
			$error=$langs->trans("ErrorFieldRequired",$langs->trans("DateEnd"));
			$action = 'edit';
		}

		// Users
		$usertodo=new User($db);
		if ($_POST["affectedto"])
		{
			$usertodo->fetch($_POST["affectedto"]);
		}
		$actioncomm->usertodo = $usertodo;
		$userdone=new User($db);
		if ($_POST["doneby"])
		{
			$userdone->fetch($_POST["doneby"]);
		}
		$actioncomm->userdone = $userdone;

		if (! $error)
		{
			$db->begin();

			$result=$actioncomm->update($user);

			if ($result > 0)
			{
				$db->commit();
			}
			else
			{
				$db->rollback();
			}
		}
	}

	if ($result < 0)
	{
		$langs->load("errors");
		$mesg='<div class="error">'.$langs->trans($actioncomm->error).'</div>';
	}
	else
	{
		if (! empty($_POST["from"]))  // deprecated. Use backtopage instead
		{
			header("Location: ".$_POST["from"]);
			exit;
		}
                if (! empty($_POST["backtopage"]))
                {
                header("Location: ".$_POST["backtopage"]);
                exit;
                }
                else
                {
                    Header("Location: ".DOL_URL_ROOT.'/comm/action/fiche.php?id='.$id);
                    exit;
                }
	}
}



/*
 * View
 */

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);

$html = new Form($db);
$htmlactions = new FormActions($db);


if ($action == 'create')
{
	$contact = new Contact($db);

	if (GETPOST("contactid"))
	{
		$result=$contact->fetch(GETPOST("contactid"));
		if ($result < 0) dol_print_error($db,$contact->error);
	}
        
        $actioncomm->fk_task=GETPOST("fk_task")?GETPOST("fk_task"):0;
        
    if ($conf->use_javascript_ajax)
    {
        print "\n".'<script type="text/javascript" language="javascript">';
        print 'jQuery(document).ready(function () {
                     function setdatefields()
                     {
                            if (jQuery("#fullday:checked").val() == null)
                            {
                                jQuery(".fulldaystarthour").attr(\'disabled\', false);
                                jQuery(".fulldaystartmin").attr(\'disabled\', false);
                                jQuery(".fulldayendhour").attr(\'disabled\', false);
                                jQuery(".fulldayendmin").attr(\'disabled\', false);
                            }
                            else
                            {
                                jQuery(".fulldaystarthour").attr(\'disabled\', true);
                                jQuery(".fulldaystartmin").attr(\'disabled\', true);
                                jQuery(".fulldayendhour").attr(\'disabled\', true);
                                jQuery(".fulldayendmin").attr(\'disabled\', true);
                                jQuery(".fulldaystarthour").val("00");
                                jQuery(".fulldaystartmin").val("00");
                                //jQuery(".fulldayendhour").val("00");
                                //jQuery(".fulldayendmin").val("00");
                                jQuery(".fulldayendhour").val("23");
                                jQuery(".fulldayendmin").val("59");
                        }
                    }
                    setdatefields();
                    jQuery("#fullday").change(function() {
                        setdatefields();
                    });
                    jQuery("#selectcomplete").change(function() {
                        if (jQuery("#selectcomplete").val() == 100)
                        {
                            if (jQuery("#doneby").val() <= 0) jQuery("#doneby").val(\''.$user->id.'\');
                        }
                        if (jQuery("#selectcomplete").val() == 0)
                        {
                            jQuery("#doneby").val(-1);
                        }
                   });
                   jQuery("#actioncode").change(function() {
                        if (jQuery("#actioncode").val() == \'AC_RDV\') jQuery("#dateend").addClass("fieldrequired");
                        else jQuery("#dateend").removeClass("fieldrequired");
                   });
               })';
        print '</script>'."\n";
        print "\n".'<script type="text/javascript" language="javascript">';
        print 'jQuery(document).ready(function () {
                     function settype()
                     {
                            var typeselected=jQuery("#actioncode option:selected").val();
                            var type=typeselected.split("_");
                            if (type[0] == "RDV")
                            {
                                $("#jqfullday").css("display","table-row");
                                $("#jqech").hide();
                                $("#jqstart").show();
                                $("#jqend").css("display","table-row");
                                $("#jqloc").css("display","table-row");
                                $(".fulldaystarthour").show();
                                $(".fulldaystartmin").show();
                                $(".fulldayendhour").show();
                                $(".fulldayendmin").show();
                                jQuery(".fulldaystartmin").val("00");
                                jQuery(".fulldayendmin").val("00");
                                $("#jqduration").hide();
                            }
                            else
                            {
                                $("#jqfullday").css("display","none");
                                $("#jqech").show();
                                $("#jqstart").hide();
                                $("#jqend").css("display","none");
                                $("#jqloc").css("display","none");
                                $(".fulldaystarthour").hide();
                                $(".fulldaystartmin").hide();
                                $(".fulldayendhour").hide();
                                $(".fulldayendmin").hide();
                                jQuery(".fulldaystartmin").val("00");
                                $("#jqduration").show();
                            }
                    }
                    settype();
                    jQuery("#actioncode").change(function() {
                        settype();
                    });
               })';
        print '</script>'."\n";
     /*   print "\n".'<script type="text/javascript" language="javascript">';
        print 'jQuery(document).ready(function () {
                     function setday()
                     {
                            if ($("#ap").val()!="")
                            {                               
                                $("#p2").val($("#ap").val());
                            }
                    }
                    setday();
                    jQuery("#p2").click(function() {
                        setday();
                    });
               })';
        print '</script>'."\n";
        print "\n".'<script type="text/javascript" language="javascript">';
        print 'jQuery(document).ready(function () {
                     function sethour()
                     {
                            var hour=parseInt($(".fulldaystarthour option:selected").val());
                            hour=hour+1;
                            var strhour=hour.toString(10)
                            if(strhour.length==1)
                                $(".fulldayendhour").val(0+strhour);
                            else
                                $(".fulldayendhour").val(strhour);
                    }
                    sethour();
                    jQuery(".fulldaystarthour").click(function() {
                        sethour();
                    });
               })';
        print '</script>'."\n";*/
    }

	print '<form name="formaction" action="'.DOL_URL_ROOT.'/comm/action/fiche.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add_action">';
        print '<input type="hidden" name="fk_task" value="'.$actioncomm->fk_task.'">';
	if (GETPOST("backtopage")) print '<input type="hidden" name="backtopage" value="'.(GETPOST("backtopage") != 1 ? GETPOST("backtopage") : $_SERVER["HTTP_REFERER"]).'">';

	//if (GETPOST("actioncode") == 'AC_RDV') print_fiche_titre ($langs->trans("AddActionRendezVous"));
	//else 
	print_fiche_titre ($langs->trans("AddAnAction"));

	dol_htmloutput_mesg($mesg);

        print '<div class="tabBar">';
	print '<table class="border" width="100%">';

	// Type d'action actifs
	print '<tr><td width="30%"><span class="fieldrequired">'.$langs->trans("Type").'</span></b></td><td>';
	/*if (GETPOST("actioncode"))
	{
		print '<input type="hidden" name="actioncode" value="'.GETPOST("actioncode").'">'."\n";
		$cactioncomm->fetch(GETPOST("actioncode"));
		print $cactioncomm->getNomUrl();
                $actioncomm->type=split("_",$cactioncomm->code);
	}
	else
	{*/
		$htmlactions->select_type_actions($actioncomm->type_code, "actioncode");
	//}
	print '</td></tr>';

    // Title
    print '<tr id="jqtitle"><td>'.$langs->trans("Title").'</td><td><input type="text" name="label" size="60" value="'.GETPOST('label').'"></td></tr>';

        // Full day
        print '<tr id="jqfullday"><td>'.$langs->trans("EventOnFullDay").'</td><td><input type="checkbox" id="fullday" name="fullday" '.(GETPOST('fullday')?' checked="checked"':'').'></td></tr>';

	// Date start
	$datep=$actioncomm->datep;
	if (GETPOST('datep','int',1)) $datep=dol_stringtotime(GETPOST('datep','int',1),0);
	print '<tr><td width="30%" nowrap="nowrap"><span class="fieldrequired" id="jqech">'.$langs->trans("DateEchAction").'</span><span class="fieldrequired" id="jqstart">'.$langs->trans("DateActionStart").'</span></td><td>';
	if (GETPOST("afaire") == 1) $html->select_date($datep,'ap',1,1,0,"action",1,1,0,0,'fulldayend');
	else if (GETPOST("afaire") == 2) $html->select_date($datep,'ap',1,1,1,"action",1,1,0,0,'fulldayend');
        //  $html->select_date($datep,'ap',0,0,1,"action",1,1,0,0,'fulldaystart');
        $html->select_date($datep,'ap',1,1,0,"action",1,1,0,0,'fulldaystart');
	print '</td></tr>';
	// Date end
        
	$datef=$actioncomm->datef;
        if (GETPOST('datef','int',1)) $datef=dol_stringtotime(GETPOST('datef','int',1),0);
	print '<tr id="jqend"><td>'.$langs->trans("DateActionEnd").'</td><td>';
	if (GETPOST("afaire") == 1) $html->select_date($datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
	else if (GETPOST("afaire") == 2) $html->select_date($datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
	else $html->select_date($datef,'p2',1,1,0,"action",1,1,0,0,'fulldayend');
	print '</td></tr>';
        
        // duration task
        print '<tr id="jqduration"><td>'.$langs->trans("Duration").'</td><td colspan="3"><input type="text" name="duration" size="3" value="'.(empty($actioncomm->durationp)?1:$actioncomm->durationp/3600).'"></td></tr>';
        
        
	// Status
	print '<tr><td width="10%">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td>';
	print '<td>';
	$percent=-1;
	if (isset($_GET['percentage']) || isset($_POST['percentage']))
	{
		$percent=GETPOST('percentage');
	}
	else
	{
		if (GETPOST("afaire") == 1) $percent=0;
		if (GETPOST("afaire") == 2) $percent=100;
	}
	print $htmlactions->form_select_status_action('formaction',$percent,1,'complete');
	print '</td></tr>';

        // Location
        print '<tr id="jqloc"><td>'.$langs->trans("Location").'</td><td colspan="3"><input type="text" name="location" size="50" value="'.$actioncomm->location.'"></td></tr>';

	print '</table>';

	print '<br>';

	print '<table class="border" width="100%">';

	// Affected by
	print '<tr><td width="30%" nowrap="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td>';
	$html->select_users(GETPOST("affectedto")?GETPOST("affectedto"):($actioncomm->usertodo->id > 0 ? $actioncomm->usertodo : $user),'affectedto',1);
	print '</td></tr>';

	// Realised by
	//print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td>';
	//$html->select_users(GETPOST("doneby")?GETPOST("doneby"):($percent==100?$actioncomm->userdone:0),'doneby',1);
	//print '</td></tr>';

	print '</table>';
	print '<br>';
	print '<table class="border" width="100%">';

	// Societe, contact
	print '<tr><td width="30%" nowrap="nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	if (GETPOST("socid") > 0)
	{
		$societe = new Societe($db);
		$societe->fetch(GETPOST("socid"));
		print $societe->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.GETPOST("socid").'">';
	}
	else
	{
		print $html->select_societes('','socid','',1,1);
	}
	print '</td></tr>';

	// If company is forced, we propose contacts (may be contact is also forced)
	if (GETPOST("contactid") > 0 || GETPOST("socid") > 0)
	{
		print '<tr><td nowrap>'.$langs->trans("ActionOnContact").'</td><td>';
		$html->select_contacts(GETPOST("socid"),GETPOST('contactid'),'contactid',1);
		print '</td></tr>';
	}

        // Lead
	if ($conf->lead->enabled && GETPOST("leadid"))
	{
		// Affaire associe
		$langs->load("lead");

		print '<tr><td valign="top">'.$langs->trans("Lead").'</td><td>';
		$numlead=select_leads($societe->id,GETPOST("leadid")?GETPOST("leadid"):$leadid,'leadid');
		if ($numlead==0)
		{
			print ' &nbsp; <a href="../../lead/fiche.php?socid='.$societe->id.'&action=create">'.$langs->trans("AddLead").'</a>';
		}
		print '</td></tr>';
	}

	// Project
	if ($conf->projet->enabled && GETPOST("Projectid"))
	{
		// Projet associe
		$langs->load("project");

		print '<tr><td valign="top">'.$langs->trans("Project").'</td><td>';
		$numproject=select_projects($societe->id,GETPOST("projectid")?GETPOST("projectid"):$projectid,'projectid');
		if ($numproject==0)
		{
			print ' &nbsp; <a href="../../projet/fiche.php?socid='.$societe->id.'&action=create">'.$langs->trans("AddProject").'</a>';
		}
		print '</td></tr>';
	}

        // PropalID
	if ( GETPOST("propalid") )
	{
            // Object linked
                $propal = new Propal($db);
                $propal->fetch(GETPOST("propalid") );
		
		print '<tr><td valign="top">'.$langs->trans("LinkedObject").'</td><td>';
                print '<input type="hidden" name="propalid" value="'.$propal->id.'">';
                print $propal->getNomUrl(1);
		print '</td></tr>';
	}

	if (GETPOST("datep") && preg_match('/^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])$/',GETPOST("datep"),$reg))
	{
		$actioncomm->datep=dol_mktime(0,0,0,$reg[2],$reg[3],$reg[1]);
	}

	// Priority
	print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
	print '<input type="text" name="priority" value="'.($_POST["priority"]?$_POST["priority"]:$actioncomm->priority).'" size="5">';
	print '</td></tr>';

	add_row_for_calendar_link();

    // Description
    print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
    require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
    $doleditor=new DolEditor('note',($_POST["note"]?$_POST["note"]:$actioncomm->note),'',280,'dolibarr_notes','In',true,true,$conf->fckeditor->enabled,ROWS_7,90);
    $doleditor->Create();
    print '</td></tr>';

	print '</table>';
        print '</div>';

	print '<center><br>';
	print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</center>';

	print "</form>";
        
}

// View or edit
if ($id)
{
	if ($error)
	{
		print '<div class="error">'.$error.'</div><br>';
	}
	if ($mesg)
	{
		print $mesg.'<br>';
	}

	$act = new ActionComm($db);
	$result=$act->fetch($id);
        
	if ($result < 0)
	{
		dol_print_error($db,$act->error);
		exit;
	}

	$societe = new Societe($db);
	if ($act->societe->id)
	{
		$result=$societe->fetch($act->societe->id);
	}
	$act->societe = $societe;

	if ($act->author->id > 0)   { $tmpuser=new User($db); $res=$tmpuser->fetch($act->author->id); $act->author=$tmpuser; }
	if ($act->usermod->id > 0)  { $tmpuser=new User($db); $res=$tmpuser->fetch($act->usermod->id); $act->usermod=$tmpuser; }
	if ($act->usertodo->id > 0) { $tmpuser=new User($db); $res=$tmpuser->fetch($act->usertodo->id); $act->usertodo=$tmpuser; }
	if ($act->userdone->id > 0) { $tmpuser=new User($db); $res=$tmpuser->fetch($act->userdone->id); $act->userdone=$tmpuser; }

	$contact = new Contact($db);
	if ($act->contact->id)
	{
		$result=$contact->fetch($act->contact->id,$user);
	}
	$act->contact = $contact;

	/*
	 * Affichage onglets
	 */

	$head=actions_prepare_head($act);
	dol_fiche_head($head, 'card', $langs->trans("Action"),0,'action');

	$now=dol_now();
	$delay_warning=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;

	// Confirmation suppression action
	if ($action == 'delete')
	{
		$ret=$html->form_confirm("fiche.php?id=".$id,$langs->trans("DeleteAction"),$langs->trans("ConfirmDeleteAction"),"confirm_delete",'','',1);
		if ($ret == 'html') print '<br>';
	}

	if ($action == 'edit')
	{
	    if ($conf->use_javascript_ajax)
            {
            print "\n".'<script type="text/javascript" language="javascript">';
            print 'jQuery(document).ready(function () {
                         function setdatefields()
                         {
                                if (jQuery("#fullday:checked").val() == null)
                                {
                                    jQuery(".fulldaystarthour").attr(\'disabled\', false);
                                    jQuery(".fulldaystartmin").attr(\'disabled\', false);
                                    jQuery(".fulldayendhour").attr(\'disabled\', false);
                                    jQuery(".fulldayendmin").attr(\'disabled\', false);
                                }
                                else
                                {
                                    jQuery(".fulldaystarthour").attr(\'disabled\', true);
                                    jQuery(".fulldaystartmin").attr(\'disabled\', true);
                                    jQuery(".fulldayendhour").attr(\'disabled\', true);
                                    jQuery(".fulldayendmin").attr(\'disabled\', true);
                                    jQuery(".fulldaystarthour").val("00");
                                    jQuery(".fulldaystartmin").val("00");
                                    //jQuery(".fulldayendhour").val("00");
                                    //jQuery(".fulldayendmin").val("00");
                                    jQuery(".fulldayendhour").val("23");
                                    jQuery(".fulldayendmin").val("59");
                            }
                        }
                        setdatefields();
                        jQuery("#fullday").change(function() {
                            setdatefields();
                        });
                   })';
            print '</script>'."\n";
       /*     print "\n".'<script type="text/javascript" language="javascript">';
        print 'jQuery(document).ready(function () {
                     function setday()
                     {
                            if ($("#ap").val()!="")
                            {                               
                                $("#p2").val($("#ap").val());
                            }
                    }
                    setday();
                    jQuery("#p2").click(function() {
                        setday();
                    });
               })';
        print '</script>'."\n";
        print "\n".'<script type="text/javascript" language="javascript">';
        print 'jQuery(document).ready(function () {
                     function sethour()
                     {
                            var hour=parseInt($(".fulldaystarthour option:selected").val());
                            hour=hour+1;
                            var strhour=hour.toString(10)
                            if(strhour.length==1)
                                $(".fulldayendhour").val(0+strhour);
                            else
                                $(".fulldayendhour").val(strhour);
                    }
                    sethour();
                    jQuery(".fulldayendhour").click(function() {
                        sethour();
                    });
               })';
        print '</script>'."\n";*/
        }

        // Fiche action en mode edition
		print '<form name="formaction" action="'.DOL_URL_ROOT.'/comm/action/fiche.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$id.'">';
                print '<input type="hidden" name="fk_task" value="'.$act->fk_task.'">';
		if (GETPOST("backtopage")) print '<input type="hidden" name="backtopage" value="'.(GETPOST("backtopage") ? GETPOST("backtopage") : $_SERVER["HTTP_REFERER"]).'">';

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">'.$act->id.'</td></tr>';

		// Type
		print '<tr><td class="fieldrequired">'.($act->type==2?$langs->trans("Actions"):$langs->trans("Event")).'</td><td colspan="3">'.$act->type_label.'</td></tr>';

		// Title
		print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3"><input type="text" name="label" size="50" value="'.$act->label.'"></td></tr>';

                if($act->type==1) //RDV
                {
                // Full day event
                print '<tr><td>'.$langs->trans("EventOnFullDay").'</td><td colspan="3"><input type="checkbox" id="fullday" name="fullday" '.($act->fulldayevent?' checked="true"':'').'></td></tr>';
                }


		// Date start
		print '<tr><td nowrap="nowrap" class="fieldrequired">'.($act->type==2?$langs->trans("DateEchAction"):$langs->trans("DateActionStart")).'</td><td colspan="3">';
		if (GETPOST("afaire") == 1) $html->select_date($act->datep,'ap',1,1,0,"action",1,1,0,0,'fulldaystart');
		else if (GETPOST("afaire") == 2) $html->select_date($act->datep,'ap',1,1,1,"action",1,1,0,0,'fulldaystart');
		//else if ($act->type==2) //ACTION
                //    $html->select_date($act->datep,'ap',0,0,1,"action",1,1,0,0,'fulldaystart');
                //else
                    $html->select_date($act->datep,'ap',1,1,1,"action",1,1,0,0,'fulldaystart');
		print '</td></tr>';
		
                if($act->type==1) //RDV
                {
                // Date end
		print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="3">';
		if (GETPOST("afaire") == 1) $html->select_date($act->datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
		else if (GETPOST("afaire") == 2) $html->select_date($act->datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
		else $html->select_date($act->datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
		print '</td></tr>';
                }
                else
                {
                    // duration task
                    print '<tr id="jqduration"><td>'.$langs->trans("Duration").'</td><td colspan="3"><input type="text" name="duration" size="3" value="'.($act->durationp/3600).'"></td></tr>';
                }

		// Status
		print '<tr><td nowrap>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="3">';
		$percent=GETPOST("percentage")?GETPOST("percentage"):$act->percentage;
		print $htmlactions->form_select_status_action('formaction',$percent,1);
		print '</td></tr>';

                if($act->type==1) //RDV
                {
                // Location
                print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3"><input type="text" name="location" size="50" value="'.$act->location.'"></td></tr>';
                }

		print '</table><br><table class="border" width="100%">';

		// Input by
		print '<tr><td width="30%" nowrap>'.$langs->trans("ActionAskedBy").'</td><td colspan="3">';
		print $act->author->getNomUrl(1);
		print '</td></tr>';

		// Affected to
		print '<tr><td nowrap>'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
		$html->select_users($act->usertodo->id,'affectedto',1);
		print '</td></tr>';

		// Realised by
		print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td colspan="3">';
		$html->select_users($act->userdone->id,'doneby',1);
		print '</td></tr>';

		print '</table><br>';

		print '<table class="border" width="100%">';

		// Company
		print '<tr><td width="30%">'.$langs->trans("ActionOnCompany").'</td>';
		print '<td>';
		print $html->select_societes($act->societe->id,'socid','',1,1);
		print '</td>';

		// Contact
		print '<td>'.$langs->trans("Contact").'</td><td width="30%">';
		print $html->selectarray("contactid",  $act->societe->contact_array(), $act->contact->id, 1);
		print '</td></tr>';

                // Lead
		if ($conf->lead->enabled)
		{
			// Lead associe
			$langs->load("lead");

			print '<tr><td valign="top">'.$langs->trans("Lead").'</td><td colspan="3">';
			$numlead=select_leads($act->societe->id,$act->fk_lead,'leadid');
			if ($numlead==0)
			{
				print ' &nbsp; <a href="../../lead/fiche.php?socid='.$societe->id.'&action=create">'.$langs->trans("AddLead").'</a>';
			}
			print '</td></tr>';
		}

		// Project
		if ($conf->projet->enabled)
		{
			// Projet associe
			$langs->load("project");

			print '<tr><td valign="top">'.$langs->trans("Project").'</td><td colspan="3">';
			$numprojet=select_projects($act->societe->id,$act->fk_project,'projectid');
			if ($numprojet==0)
			{
				print ' &nbsp; <a href="../../projet/fiche.php?socid='.$societe->id.'&action=create">'.$langs->trans("AddProject").'</a>';
			}
			print '</td></tr>';
		}

		// Priority
		print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
		print '<input type="text" name="priority" value="'.$act->priority.'" size="5">';
		print '</td></tr>';

		// Object linked
		if (! empty($act->fk_element) && ! empty($act->elementtype))
		{
			print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
			print '<td colspan="3">'.$act->getElementUrl($act->fk_element,$act->elementtype,1).'</td></tr>';
		}

        // Description
        print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">';
        // Editeur wysiwyg
        require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
        $doleditor=new DolEditor('note',$act->note,'',240,'dolibarr_notes','In',true,true,$conf->fckeditor->enabled,ROWS_5,90);
        $doleditor->Create();
        print '</td></tr>';

		print '</table>';

		print '<center><br><input type="submit" class="button" name="edit" value="'.$langs->trans("Save").'">';
		print ' &nbsp; &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</center>';

		print '</form>';
	}
	else
	{
		// Affichage fiche action en mode visu
                $var=false;
                print '<table width="100%"><tr><td valign="top" width="65%">';
		print '<table class="noborder" width="100%">';

		// Ref
		print '<tr class="liste_titre"><td width="30%">'.($act->type==2?$langs->trans("Actions"):$langs->trans("Event")).'</td><td colspan="3" id="value">';
		print $html->showrefnav($act,'id','',($user->societe_id?0:1),'id','ref','');
		print '</td></tr>';

		// Type
		print '<tr '.$bc[$var].'><td id="label">'.($act->type==2?$langs->trans("Actions"):$langs->trans("Event")).'</td><td colspan="3" id="value">'.$act->type_label.'</td></tr>';
                $var=!$var;

		// Title
		print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("Title").'</td><td colspan="3" id="value">'.$act->label.'</td></tr>';
                $var=!$var;

                // Full day event
                if($act->type==1)//RDV
                {
                    print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("EventOnFullDay").'</td><td colspan="3" id="value">'.yn($act->fulldayevent).'</td></tr>';
                    $var=!$var;
                }

		// Date start
		print '<tr '.$bc[$var].'><td width="30%" id="label">'.($act->type==2?$langs->trans("DateEchAction"):$langs->trans("DateActionStart")).'</td><td colspan="2" id="value">';
		if (! $act->fulldayevent) print dol_print_date($act->datep,'dayhour');
		else print dol_print_date($act->datep,'day');
		if ($act->percentage < 100 && $act->datep && $act->datep < ($now - $delay_warning)) print img_warning($langs->trans("Late"));
		print '</td>';
		if($act->type==1) //RDV
                {
		print '<td rowspan="3" align="center" valign="middle" width="180">'."\n";
        print '<form name="listactionsfiltermonth" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="show_month">';
        print '<input type="hidden" name="year" value="'.dol_print_date($act->datep,'%Y').'">';
        print '<input type="hidden" name="month" value="'.dol_print_date($act->datep,'%m').'">';
        print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
        //print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
        print img_picto($langs->trans("ViewCal"),'object_calendar').' <input type="submit" style="width: 120px" class="button" name="viewcal" value="'.$langs->trans("ViewCal").'">';
        print '</form>'."\n";
        print '<form name="listactionsfilterweek" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="show_week">';
        print '<input type="hidden" name="year" value="'.dol_print_date($act->datep,'%Y').'">';
        print '<input type="hidden" name="month" value="'.dol_print_date($act->datep,'%m').'">';
        print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
        //print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
        print img_picto($langs->trans("ViewCal"),'object_calendarweek').' <input type="submit" style="width: 120px" class="button" name="viewweek" value="'.$langs->trans("ViewWeek").'">';
        print '</form>'."\n";
        print '<form name="listactionsfilterday" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="show_day">';
        print '<input type="hidden" name="year" value="'.dol_print_date($act->datep,'%Y').'">';
        print '<input type="hidden" name="month" value="'.dol_print_date($act->datep,'%m').'">';
        print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
        //print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
        print img_picto($langs->trans("ViewCal"),'object_calendarday').' <input type="submit" style="width: 120px" class="button" name="viewday" value="'.$langs->trans("ViewDay").'">';
        print '</form>'."\n";
        print '</td>';
		print '</tr>';
		$var=!$var;

		// Date end
		print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("DateActionEnd").'</td><td colspan="2">';
        if (! $act->fulldayevent) print dol_print_date($act->datef,'dayhour');
		else print dol_print_date($act->datef,'day');
		if ($act->percentage > 0 && $act->percentage < 100 && $act->datef && $act->datef < ($now- $delay_warning)) print img_warning($langs->trans("Late"));
		print '</td>';
		}
        print '</tr>';
        $var=!$var;

		// Duration task
                if($act->type==2)
                {
                    print '<tr '.$bc[$var].'><td nowrap id="label">'.$langs->trans("Duration").'</td><td colspan="3" id="value">';
                    print $act->durationp/3600;
                    print '</td></tr>';
                    $var=!$var;
                }

		// Status
		print '<tr '.$bc[$var].'><td nowrap id="label">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="2"  id="value">';
		print $act->getLibStatut(4);
		print '</td></tr>';
                $var=!$var;
                
                // Priority
		print '<tr '.$bc[$var].'><td nowrap id="label">'.$langs->trans("Priority").'</td><td colspan="3" id="value">';
		print $act->priority;
		print '</td></tr>';
                $var=!$var;


        // Location
		if($act->type==1)
        {
        	print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("Location").'</td><td colspan="3" id="value">'.$act->location.'</td></tr>';
        	$var=!$var;
        }

                // Description
		print '<tr '.$bc[$var].'><td valign="top" id="label">'.$langs->trans("Description").'</td><td colspan="3" id="value">';
		print dol_htmlentitiesbr($act->note);
		print '</td></tr>';
                $var=!$var;
                
		print '</table>';
                print '</td>';

                print '<td valign="top" width="35%"><table class="noborder" width="100%">';

                print '<tr class="liste_titre"><td colspan="2">';
                print $langs->trans('Company');
                print '</td></tr>';

		// Third party - Contact
		print '<tr '.$bc[$var].'><td  id="label" width="30%">'.$langs->trans("ActionOnCompany").'</td><td id="value">'.($act->societe->id?$act->societe->getNomUrl(1):$langs->trans("None"));
		if ($act->societe->id && $act->type_code == 'AC_TEL')
		{
			if ($act->societe->fetch($act->societe->id))
			{
				print "<br>".dol_print_phone($act->societe->tel);
			}
		}
		print '</td></tr>';
                $var=!$var;
                print '<tr '.$bc[$var].'>';
		print '<td id="label">'.$langs->trans("Contact").'</td>';
		print '<td id="value">';
		if ($act->contact->id > 0)
		{
			print $act->contact->getNomUrl(1);
			if ($act->contact->id && $act->type_code == 'AC_TEL')
			{
				if ($act->contact->fetch($act->contact->id))
				{
					print "<br>".dol_print_phone($act->contact->phone_pro);
				}
			}
		}
		else
		{
			print $langs->trans("None");
		}

		print '</td></tr>';
                $var=!$var;

                // Lead
		if ($conf->lead->enabled && $act->fk_lead )
		{
			print '<tr '.$bc[$var].'><td valign="top" id="label">'.$langs->trans("Lead").'</td><td colspan="1" id="value">';
			if ($act->fk_lead)
			{
				$lead=new Lead($db);
				$lead->fetch($act->fk_lead);
				print $lead->getNomUrl(1);
			}
			print '</td></tr>';
                        $var=!$var;
		}

		// Project
		if ($conf->projet->enabled && $act->fk_project)
		{
			print '<tr '.$bc[$var].'><td valign="top" id="label">'.$langs->trans("Project").'</td><td colspan="1" id="value">';
			if ($act->fk_project)
			{
				$project=new Project($db);
				$project->fetch($act->fk_project);
				print $project->getNomUrl(1);
			}
			print '</td></tr>';
                        $var=!$var;
		}
                
		// Object linked
		if (! empty($act->fk_element) && ! empty($act->elementtype))
		{
			print '<tr '.$bc[$var].'><td id="label">'.$langs->trans("LinkedObject").'</td>';
			print '<td colspan="1" id="value">'.$act->getElementUrl($act->fk_element,$act->elementtype,1).'</td></tr>';
			$var=!$var;
		}

                print '</table><br><table class="noborder" width="100%">';

                print '<tr class="liste_titre"><td colspan="2">';
                print $langs->trans("ActionAffectedTo").'</td></tr>';

		// Input by
		print '<tr '.$bc[$var].'><td width="40%" nowrap id="label">'.$langs->trans("ActionAskedBy").'</td><td colspan="1" id="value">';
		if ($act->author->id > 0) print $act->author->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';
                $var=!$var;

		// Affecte a
		print '<tr '.$bc[$var].'><td nowrap id="label">'.$langs->trans("ActionAffectedTo").'</td><td colspan="1" id="value">';
		if ($act->usertodo->id > 0) print $act->usertodo->getNomUrl(1);
		print '</td></tr>';
                $var=!$var;

		// Done by
		print '<tr '.$bc[$var].'><td nowrap id="label">'.$langs->trans("ActionDoneBy").'</td><td colspan="1" id="value">';
		if ($act->userdone->id > 0) print $act->userdone->getNomUrl(1);
		print '</td></tr>';
                $var=!$var;

		print '</table></td>';
                print '</tr></table>';
	}

	print "</div>\n";


	/*
	 * Barre d'actions
	 *
	 */

	

	if ($action != 'edit')
	{
            print '<div class="tabsAction">';
            
		if ($user->rights->agenda->allactions->create ||
		   (($act->author->id == $user->id || $act->usertodo->id == $user->id) && $user->rights->agenda->myactions->create))
		{
                    print '<a class="butAction" href="fiche.php?action=create&fk_task='.$act->id.($act->socid?"&socid=".$act->socid:"").($act->contact->id?"&contactid=".$act->contact->id:"").($act->fk_lead?"&leadid=".$act->fk_lead:"").($act->fk_project?"&projectid=".$act->fk_project:"")."&backtopage=".DOL_URL_ROOT.'/comm/action/fiche.php?id='.$act->id.'">'.$langs->trans("AddAction").'</a>';
                    print '<a class="butAction" href="fiche.php?action=edit&id='.$act->id.'">'.$langs->trans("Modify").'</a>';
                    if($act->percentage < 100)
                        print '<a class="butAction" href="fiche.php?action=close&id='.$act->id.'">'.$langs->trans("Close").'</a>';
                    else
                        print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Close").'</a>';
		}
		else
		{
                        print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("AddAction").'</a>';
                        print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("AddEvent").'</a>';
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Modify").'</a>';
                        print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Close").'</a>';
		}

		if ($user->rights->agenda->allactions->delete ||
		   (($act->author->id == $user->id || $act->usertodo->id == $user->id) && $user->rights->agenda->myactions->delete))
		{
			print '<a class="butActionDelete" href="fiche.php?action=delete&id='.$act->id.'">'.$langs->trans("Delete").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Delete").'</a>';
		}
	

            print '</div>';
            print "<br>\n";

            print '<table width="100%"><tr><td width="50%" valign="top">';
            /*
            * Actions to do
            */
            if ($user->rights->agenda->myactions->read)
            {
                show_array_actions_to_do(10,$act->id);
            }

            print '</td><td width="50%" valign="top">';
            /*
            * Last actions
            */
            if ($user->rights->agenda->myactions->read)
            {
                show_array_last_actions_done(10,$act->id);
            }
            print '</td></tr></table>';
        }
}


$db->close();

llxFooter('$Date: 2011/07/18 08:59:42 $ - $Revision: 1.227 $');


/**
 *  \brief      Ajoute une ligne de tableau a 2 colonnes pour avoir l'option synchro calendrier
 *  \return     int     Retourne le nombre de lignes ajoutees
 */
function add_row_for_calendar_link()
{
	global $conf,$langs,$user;
	$nbtr=0;

	// Lien avec calendrier si module active
	if ($conf->webcalendar->enabled)
	{
		if ($conf->global->PHPWEBCALENDAR_SYNCRO != 'never')
		{
			$langs->load("other");

			print '<tr><td width="25%" nowrap>'.$langs->trans("AddCalendarEntry","Webcalendar").'</td>';

			if (! $user->webcal_login)
			{
				print '<td><input type="checkbox" disabled name="add_webcal">';
				print ' '.$langs->transnoentities("ErrorWebcalLoginNotDefined","<a href=\"".DOL_URL_ROOT."/user/fiche.php?id=".$user->id."\">".$user->login."</a>");
				print '</td>';
				print '</tr>';
				$nbtr++;
			}
			else
			{
				if ($conf->global->PHPWEBCALENDAR_SYNCRO == 'always')
				{
					print '<input type="hidden" name="add_webcal" value="on">';
				}
				else
				{
					print '<td><input type="checkbox" name="add_webcal"'.(($conf->global->PHPWEBCALENDAR_SYNCRO=='always' || $conf->global->PHPWEBCALENDAR_SYNCRO=='yesbydefault')?' checked':'').'></td>';
					print '</tr>';
					$nbtr++;
				}
			}
		}
	}

	if ($conf->phenix->enabled)
	{
		if ($conf->global->PHPPHENIX_SYNCRO != 'never')
		{
			$langs->load("other");

			print '<tr><td width="25%" nowrap>'.$langs->trans("AddCalendarEntry","Phenix").'</td>';

			if (! $user->phenix_login)
			{
				print '<td><input type="checkbox" disabled name="add_phenix">';
				print ' '.$langs->transnoentities("ErrorPhenixLoginNotDefined","<a href=\"".DOL_URL_ROOT."/user/fiche.php?id=".$user->id."\">".$user->login."</a>");
				print '</td>';
				print '</tr>';
				$nbtr++;
			}
			else
			{
				if ($conf->global->PHPPHENIX_SYNCRO == 'always')
				{
					print '<input type="hidden" name="add_phenix" value="on">';
				}
				else
				{
					print '<td><input type="checkbox" name="add_phenix"'.(($conf->global->PHPPHENIX_SYNCRO=='always' || $conf->global->PHPPHENIX_SYNCRO=='yesbydefault')?' checked':'').'></td>';
					print '</tr>';
					$nbtr++;
				}
			}
		}
	}

	return $nbtr;
}


?>
