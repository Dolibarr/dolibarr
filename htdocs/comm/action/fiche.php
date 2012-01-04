<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/comm/action/fiche.php
 *       \ingroup    agenda
 *       \brief      Page for event card
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/agenda.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formactions.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");

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
	$error=0;

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
	$datep=dol_mktime($fulldayevent?'00':$_POST["aphour"], $fulldayevent?'00':$_POST["apmin"], 0, $_POST["apmonth"], $_POST["apday"], $_POST["apyear"]);
	$datef=dol_mktime($fulldayevent?'23':$_POST["p2hour"], $fulldayevent?'59':$_POST["p2min"], $fulldayevent?'59':'0', $_POST["p2month"], $_POST["p2day"], $_POST["p2year"]);

	// Check parameters
	if (! $datef && $_POST["percentage"] == 100)
	{
		$error++;
		$action = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("DateEnd")).'</div>';
	}

	// Initialisation objet cactioncomm
	if (! $_POST["actioncode"])
	{
		$error++;
		$action = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Type")).'</div>';
	}
	else
	{
		$result=$cactioncomm->fetch($_POST["actioncode"]);
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
		if ($_POST["actioncode"] == 'AC_RDV' && $contact->getFullName($langs))
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
	$actioncomm->datep = $datep;
	$actioncomm->datef = $datef;
	$actioncomm->percentage = isset($_POST["percentage"])?$_POST["percentage"]:0;
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
	}

	// Special for module webcal and phenix
	if ($_POST["add_webcal"] == 'on' && $conf->webcalendar->enabled) $actioncomm->use_webcal=1;
	if ($_POST["add_phenix"] == 'on' && $conf->phenix->enabled) $actioncomm->use_phenix=1;

	// Check parameters
	if ($actioncomm->type_code == 'AC_RDV' && ($datep == '' || $datef == ''))
	{
		$error++;
		$action = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateEnd")).'</div>';
	}
	if ($datea && $_POST["percentage"] == 0)
	{
		$error++;
		$action = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorStatusCantBeZeroIfStarted").'</div>';
	}

	if (! $_POST["apyear"] && ! $_POST["adyear"])
	{
		$error++;
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

		$datep=dol_mktime($fulldayevent?'00':$_POST["aphour"], $fulldayevent?'00':$_POST["apmin"], 0, $_POST["apmonth"], $_POST["apday"], $_POST["apyear"]);
		$datef=dol_mktime($fulldayevent?'23':$_POST["p2hour"], $fulldayevent?'59':$_POST["p2min"], $fulldayevent?'59':'0', $_POST["p2month"], $_POST["p2day"], $_POST["p2year"]);

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
		$actioncomm->note        = $_POST["note"];
		$actioncomm->pnote       = $_POST["note"];

		if (! $datef && $_POST["percentage"] == 100)
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
	}
}


/*
 * View
 */

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);

$form = new Form($db);
$htmlactions = new FormActions($db);


if ($action == 'create')
{
	$contact = new Contact($db);

	if (GETPOST("contactid"))
	{
		$result=$contact->fetch(GETPOST("contactid"));
		if ($result < 0) dol_print_error($db,$contact->error);
	}

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
    }

	print '<form name="formaction" action="'.DOL_URL_ROOT.'/comm/action/fiche.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add_action">';
	if (GETPOST("backtopage")) print '<input type="hidden" name="backtopage" value="'.(GETPOST("backtopage") != 1 ? GETPOST("backtopage") : $_SERVER["HTTP_REFERER"]).'">';

	if (GETPOST("actioncode") == 'AC_RDV') print_fiche_titre ($langs->trans("AddActionRendezVous"));
	else print_fiche_titre ($langs->trans("AddAnAction"));

	dol_htmloutput_mesg($mesg);

	print '<table class="border" width="100%">';

	// Type d'action actifs
	print '<tr><td width="30%"><span class="fieldrequired">'.$langs->trans("Type").'</span></b></td><td>';
	if (GETPOST("actioncode"))
	{
		print '<input type="hidden" name="actioncode" value="'.GETPOST("actioncode").'">'."\n";
		$cactioncomm->fetch(GETPOST("actioncode"));
		print $cactioncomm->getNomUrl();
	}
	else
	{
		$htmlactions->select_type_actions($actioncomm->type_code, "actioncode");
	}
	print '</td></tr>';

	// Title
	print '<tr><td>'.$langs->trans("Title").'</td><td><input type="text" name="label" size="60" value="'.GETPOST('label').'"></td></tr>';

    // Full day
    print '<tr><td>'.$langs->trans("EventOnFullDay").'</td><td><input type="checkbox" id="fullday" name="fullday" '.(GETPOST('fullday')?' checked="checked"':'').'></td></tr>';

	// Date start
	$datep=$actioncomm->datep;
	if (GETPOST('datep','int',1)) $datep=dol_stringtotime(GETPOST('datep','int',1),0);
	print '<tr><td width="30%" nowrap="nowrap"><span class="fieldrequired">'.$langs->trans("DateActionStart").'</span></td><td>';
	if (GETPOST("afaire") == 1) $form->select_date($datep,'ap',1,1,0,"action",1,1,0,0,'fulldayend');
	else if (GETPOST("afaire") == 2) $form->select_date($datep,'ap',1,1,1,"action",1,1,0,0,'fulldayend');
	else $form->select_date($datep,'ap',1,1,1,"action",1,1,0,0,'fulldaystart');
	print '</td></tr>';
	// Date end
	$datef=$actioncomm->datef;
    if (GETPOST('datef','int',1)) $datef=dol_stringtotime(GETPOST('datef','int',1),0);
	print '<tr><td><span id="dateend"'.(GETPOST("actioncode") == 'AC_RDV'?' class="fieldrequired"':'').'>'.$langs->trans("DateActionEnd").'</span></td><td>';
	if (GETPOST("afaire") == 1) $form->select_date($datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
	else if (GETPOST("afaire") == 2) $form->select_date($datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
	else $form->select_date($datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
	print '</td></tr>';

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
    print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3"><input type="text" name="location" size="50" value="'.$act->location.'"></td></tr>';

	print '</table>';

	print '<br>';

	print '<table class="border" width="100%">';

	// Affected by
	print '<tr><td width="30%" nowrap="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td>';
	$form->select_users(GETPOST("affectedto")?GETPOST("affectedto"):($actioncomm->usertodo->id > 0 ? $actioncomm->usertodo : $user),'affectedto',1);
	print '</td></tr>';

	// Realised by
	print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td>';
	$form->select_users(GETPOST("doneby")?GETPOST("doneby"):($percent==100?$actioncomm->userdone:0),'doneby',1);
	print '</td></tr>';

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
		print $form->select_societes('','socid','',1,1);
	}
	print '</td></tr>';

	// If company is forced, we propose contacts (may be contact is also forced)
	if (GETPOST("contactid") > 0 || GETPOST("socid") > 0)
	{
		print '<tr><td nowrap>'.$langs->trans("ActionOnContact").'</td><td>';
		$form->select_contacts(GETPOST("socid"),GETPOST('contactid'),'contactid',1);
		print '</td></tr>';
	}

	// Project
	if ($conf->projet->enabled)
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

	if (GETPOST("datep") && preg_match('/^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])$/',GETPOST("datep"),$reg))
	{
		$actioncomm->datep=dol_mktime(0,0,0,$reg[2],$reg[3],$reg[1]);
	}

	// Priority
	print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
	print '<input type="text" name="priority" value="'.($_POST["priority"]?$_POST["priority"]:($actioncomm->priority?$actioncomm->priority:'')).'" size="5">';
	print '</td></tr>';

	add_row_for_calendar_link();

    // Description
    print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
    require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
    $doleditor=new DolEditor('note',($_POST["note"]?$_POST["note"]:$actioncomm->note),'',280,'dolibarr_notes','In',true,true,$conf->fckeditor->enabled,ROWS_7,90);
    $doleditor->Create();
    print '</td></tr>';

	print '</table>';

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
		dol_htmloutput_errors($error);
	}
	if ($mesg)
	{
		dol_htmloutput_mesg($mesg);
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
		$ret=$form->form_confirm("fiche.php?id=".$id,$langs->trans("DeleteAction"),$langs->trans("ConfirmDeleteAction"),"confirm_delete",'','',1);
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
        }

        // Fiche action en mode edition
		print '<form name="formaction" action="'.DOL_URL_ROOT.'/comm/action/fiche.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="ref_ext" value="'.$act->ref_ext.'">';
		if (GETPOST("backtopage")) print '<input type="hidden" name="backtopage" value="'.(GETPOST("backtopage") ? GETPOST("backtopage") : $_SERVER["HTTP_REFERER"]).'">';

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">'.$act->id.'</td></tr>';

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';

		// Title
		print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3"><input type="text" name="label" size="50" value="'.$act->label.'"></td></tr>';

        // Full day event
        print '<tr><td>'.$langs->trans("EventOnFullDay").'</td><td colspan="3"><input type="checkbox" id="fullday" name="fullday" '.($act->fulldayevent?' checked="checked"':'').'></td></tr>';

		// Date start
		print '<tr><td nowrap="nowrap" class="fieldrequired">'.$langs->trans("DateActionStart").'</td><td colspan="3">';
		if (GETPOST("afaire") == 1) $form->select_date($act->datep,'ap',1,1,0,"action",1,1,0,0,'fulldaystart');
		else if (GETPOST("afaire") == 2) $form->select_date($act->datep,'ap',1,1,1,"action",1,1,0,0,'fulldaystart');
		else $form->select_date($act->datep,'ap',1,1,1,"action",1,1,0,0,'fulldaystart');
		print '</td></tr>';
		// Date end
		print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="3">';
		if (GETPOST("afaire") == 1) $form->select_date($act->datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
		else if (GETPOST("afaire") == 2) $form->select_date($act->datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
		else $form->select_date($act->datef,'p2',1,1,1,"action",1,1,0,0,'fulldayend');
		print '</td></tr>';

		// Status
		print '<tr><td nowrap>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="3">';
		$percent=GETPOST("percentage")?GETPOST("percentage"):$act->percentage;
		print $htmlactions->form_select_status_action('formaction',$percent,1);
		print '</td></tr>';

        // Location
        print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3"><input type="text" name="location" size="50" value="'.$act->location.'"></td></tr>';

		print '</table><br><table class="border" width="100%">';

		// Input by
		print '<tr><td width="30%" nowrap>'.$langs->trans("ActionAskedBy").'</td><td colspan="3">';
		print $act->author->getNomUrl(1);
		print '</td></tr>';

		// Affected to
		print '<tr><td nowrap>'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
		print $form->select_dolusers($act->usertodo->id>0?$act->usertodo->id:-1,'affectedto',1);
		print '</td></tr>';

		// Realised by
		print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td colspan="3">';
		print $form->select_dolusers($act->userdone->id> 0?$act->userdone->id:-1,'doneby',1);
		print '</td></tr>';

		print '</table><br>';

		print '<table class="border" width="100%">';

		// Company
		print '<tr><td width="30%">'.$langs->trans("ActionOnCompany").'</td>';
		print '<td>';
		print $form->select_societes($act->societe->id,'socid','',1,1);
		print '</td>';

		// Contact
		print '<td>'.$langs->trans("Contact").'</td><td width="30%">';
		print $form->selectarray("contactid",  $act->societe->contact_array(), $act->contact->id, 1);
		print '</td></tr>';

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
		print '<input type="text" name="priority" value="'.($act->priority?$act->priority:'').'" size="5">';
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
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
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
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($act,'id','',($user->societe_id?0:1),'id','ref','');
		print '</td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';

		// Title
		print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$act->label.'</td></tr>';

        // Full day event
        print '<tr><td>'.$langs->trans("EventOnFullDay").'</td><td colspan="3">'.yn($act->fulldayevent).'</td></tr>';

		// Date start
		print '<tr><td width="30%">'.$langs->trans("DateActionStart").'</td><td colspan="2">';
		if (! $act->fulldayevent) print dol_print_date($act->datep,'dayhour');
		else print dol_print_date($act->datep,'day');
		if ($act->percentage == 0 && $act->datep && $act->datep < ($now - $delay_warning)) print img_warning($langs->trans("Late"));
		print '</td>';
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

		// Date end
		print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="2">';
        if (! $act->fulldayevent) print dol_print_date($act->datef,'dayhour');
		else print dol_print_date($act->datef,'day');
		if ($act->percentage > 0 && $act->percentage < 100 && $act->datef && $act->datef < ($now- $delay_warning)) print img_warning($langs->trans("Late"));
		print '</td></tr>';

		// Status
		print '<tr><td nowrap>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="2">';
		print $act->getLibStatut(4);
		print '</td></tr>';

        // Location
        print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3">'.$act->location.'</td></tr>';

		print '</table><br><table class="border" width="100%">';

		// Input by
		print '<tr><td width="30%" nowrap>'.$langs->trans("ActionAskedBy").'</td><td colspan="3">';
		if ($act->author->id > 0) print $act->author->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';

		// Affecte a
		print '<tr><td nowrap>'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
		if ($act->usertodo->id > 0) print $act->usertodo->getNomUrl(1);
		print '</td></tr>';

		// Done by
		print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td colspan="3">';
		if ($act->userdone->id > 0) print $act->userdone->getNomUrl(1);
		print '</td></tr>';

		print '</table><br><table class="border" width="100%">';

		// Third party - Contact
		print '<tr><td width="30%">'.$langs->trans("ActionOnCompany").'</td><td>'.($act->societe->id?$act->societe->getNomUrl(1):$langs->trans("None"));
		if ($act->societe->id && $act->type_code == 'AC_TEL')
		{
			if ($act->societe->fetch($act->societe->id))
			{
				print "<br>".dol_print_phone($act->societe->tel);
			}
		}
		print '</td>';
		print '<td>'.$langs->trans("Contact").'</td>';
		print '<td>';
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

		// Project
		if ($conf->projet->enabled)
		{
			print '<tr><td valign="top">'.$langs->trans("Project").'</td><td colspan="3">';
			if ($act->fk_project)
			{
				$project=new Project($db);
				$project->fetch($act->fk_project);
				print $project->getNomUrl(1);
			}
			print '</td></tr>';
		}

		// Priority
		print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
		print ($act->priority?$act->priority:'');
		print '</td></tr>';

		// Object linked
		if (! empty($act->fk_element) && ! empty($act->elementtype))
		{
			print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
			print '<td colspan="3">'.$act->getElementUrl($act->fk_element,$act->elementtype,1).'</td></tr>';
		}

		// Description
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="3">';
		print dol_htmlentitiesbr($act->note);
		print '</td></tr>';

		print '</table>';
	}

	print "</div>\n";


	/*
	 * Barre d'actions
	 *
	 */

	print '<div class="tabsAction">';

	if ($action != 'edit')
	{
		if ($user->rights->agenda->allactions->create ||
		   (($act->author->id == $user->id || $act->usertodo->id == $user->id) && $user->rights->agenda->myactions->create))
		{
			print '<a class="butAction" href="fiche.php?action=edit&id='.$act->id.'">'.$langs->trans("Modify").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Modify").'</a>';
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
	}

	print '</div>';
}

$db->close();

llxFooter();


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
