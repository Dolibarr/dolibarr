<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *       \brief      Page for action card
 *       \version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formactions.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("other");
$langs->load("bills");
$langs->load("orders");
$langs->load("agenda");

// If socid provided by ajax company selector
if (! empty($_REQUEST['socid_id']))
{
	$_GET['socid'] = $_GET['socid_id'];
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}

// Security check
$socid=isset($_GET['socid'])?$_GET['socid']:$_POST['socid'];
$id = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
// TODO: revoir les droits car pas clair
//$result = restrictedArea($user, 'agenda', $id, 'actioncomm', 'actions', '', 'id');

if (isset($_GET["error"])) $error=$_GET["error"];

$cactioncomm = new CActionComm($db);
$actioncomm = new ActionComm($db);
$contact = new Contact($db);

// If socid provided by ajax company selector
if (! empty($_REQUEST['socid_id']))
{
	$_GET['socid'] = $_GET['socid_id'];
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}


/*
 * Action creation de l'action
 */
if ($_POST["action"] == 'add_action')
{
	if ($_POST["contactid"])
	{
		$result=$contact->fetch($_POST["contactid"]);
	}

	if ($_POST['cancel'])
	{
		$backtopage='';
		if (! empty($_POST["backtopage"])) $backtopage=$_POST["backtopage"];
		if (! $backtopage)
		{
			if ($socid) $backtopage = DOL_URL_ROOT.'/comm/fiche.php?socid='.$socid;
			else $backtopage=DOL_URL_ROOT.'/comm/action/index.php';
		}
		header("Location: ".$backtopage);
		exit;
	}

	// Clean parameters
	if ($_POST["aphour"] == -1) $_POST["aphour"]='0';
	if ($_POST["apmin"] == -1) $_POST["apmin"]='0';
	if ($_POST["p2hour"] == -1) $_POST["p2hour"]='0';
	if ($_POST["p2min"] == -1) $_POST["p2min"]='0';
	//if ($_POST["adhour"] == -1) $_POST["adhour"]='0';
	//if ($_POST["admin"] == -1) $_POST["admin"]='0';
	//if ($_POST["a2hour"] == -1) $_POST["a2hour"]='0';
	//if ($_POST["a2min"] == -1) $_POST["a2min"]='0';
	$datep=dol_mktime($_POST["aphour"],
	$_POST["apmin"],
	0,
	$_POST["apmonth"],
	$_POST["apday"],
	$_POST["apyear"]);
	$datep2=dol_mktime($_POST["p2hour"],
	$_POST["p2min"],
	0,
	$_POST["p2month"],
	$_POST["p2day"],
	$_POST["p2year"]);
	/*$datea=dol_mktime($_POST["adhour"],	// deprecated
	 $_POST["admin"],
	 0,
	 $_POST["admonth"],
	 $_POST["adday"],
	 $_POST["adyear"]);
	 $datea2=dol_mktime($_POST["a2hour"],	// deprecated
	 $_POST["a2min"],
	 0,
	 $_POST["a2month"],
	 $_POST["a2day"],
	 $_POST["a2year"]);
	 */

	if (! $datep2 && $_POST["percentage"] == 100)
	{
		$error=1;
		$_GET["action"] = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("DateEnd")).'</div>';
	}

	// Initialisation objet cactioncomm
	if (! $_POST["actioncode"])
	{
		$error=1;
		$_GET["action"] = 'create';
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
	$actioncomm->datep = $datep;
	//$actioncomm->date = $datea;
	$actioncomm->datef = $datep2;
	//$actioncomm->dateend = $datea2;
	//if ($_POST["percentage"] < 100 && strval($actioncomm->datep) != '') $actioncomm->datep=$actioncomm->date;
	if ($actioncomm->type_code == 'AC_RDV')
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

	$usertodo=new User($db,$_POST["affectedto"]);
	if ($_POST["affectedto"] > 0)
	{
		$usertodo->fetch();
	}
	$actioncomm->usertodo = $usertodo;
	$userdone=new User($db,$_POST["doneby"]);
	if ($_POST["doneby"] > 0)
	{
		$userdone->fetch();
	}
	$actioncomm->userdone = $userdone;

	$actioncomm->note = trim($_POST["note"]);
	if (isset($_POST["contactid"])) $actioncomm->contact = $contact;
	if (isset($_REQUEST["socid"]) && $_REQUEST["socid"] > 0)
	{
		$societe = new Societe($db);
		$societe->fetch($_REQUEST["socid"]);
		$actioncomm->societe = $societe;
	}

	// Special for module webcal and phenix
	if ($_POST["add_webcal"] == 'on' && $conf->webcal->enabled) $actioncomm->use_webcal=1;
	if ($_POST["add_phenix"] == 'on' && $conf->phenix->enabled) $actioncomm->use_phenix=1;

	// Check parameters
	if ($actioncomm->type_code == 'AC_RDV' && ($datep == '' || $datep2 == ''))
	{
		$error=1;
		$_GET["action"] = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateEnd")).'</div>';
	}
	if ($datea && $_POST["percentage"] == 0)
	{
		$error=1;
		$_GET["action"] = 'create';
		$mesg='<div class="error">'.$langs->trans("ErrorStatusCantBeZeroIfStarted").'</div>';
	}

	if (! $_POST["apyear"] && ! $_POST["adyear"])
	{
		$error=1;
		$_GET["action"] = 'create';
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
				if ($_POST["from"])
				{
					dol_syslog("Back to ".$_POST["from"]);
					Header("Location: ".$_POST["from"]);
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
				$_GET["id"]=$idaction;
				$langs->load("errors");
				$error=$langs->trans($actioncomm->error);
			}
		}
		else
		{
			$db->rollback();
			$_GET["id"]=$idaction;
			$langs->load("errors");
			$error=$langs->trans($actioncomm->error);
		}
	}

	//    print $_REQUEST["from"]."rr";
}

/*
 * Action suppression de l'action
 */
if ($_REQUEST["action"] == 'confirm_delete' && $_REQUEST["confirm"] == 'yes')
{
	$actioncomm = new ActionComm($db);
	$actioncomm->fetch($_GET["id"]);

	if ($user->rights->agenda->myactions->create
		|| $user->rights->agenda->allactions->create)
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
 *
 */
if ($_POST["action"] == 'update')
{
	if (! $_POST["cancel"])
	{
		// Clean parameters
		if ($_POST["aphour"] == -1) $_POST["aphour"]='0';
		if ($_POST["apmin"] == -1) $_POST["apmin"]='0';
		if ($_POST["p2hour"] == -1) $_POST["p2hour"]='0';
		if ($_POST["p2min"] == -1) $_POST["p2min"]='0';
		//if ($_POST["adhour"] == -1) $_POST["adhour"]='0';
		//if ($_POST["admin"] == -1) $_POST["admin"]='0';

		$actioncomm = new Actioncomm($db);
		$actioncomm->fetch($_POST["id"]);

		$datep=dol_mktime($_POST["aphour"],
		$_POST["apmin"],
		0,
		$_POST["apmonth"],
		$_POST["apday"],
		$_POST["apyear"]);

		$datep2=dol_mktime($_POST["p2hour"],
		$_POST["p2min"],
		0,
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
		$actioncomm->datef       = $datep2;
		//$actioncomm->date        = $datea;
		//$actioncomm->dateend     = $datea2;
		$actioncomm->percentage  = $_POST["percentage"];
		$actioncomm->priority    = $_POST["priority"];
		$actioncomm->location    = isset($_POST["location"])?$_POST["location"]:'';
		$actioncomm->societe->id = $_POST["socid"];
		$actioncomm->contact->id = $_POST["contactid"];
		$actioncomm->note        = $_POST["note"];

		if (! $datep2 && $_POST["percentage"] == 100)
		{
			$error=$langs->trans("ErrorFieldRequired",$langs->trans("DateEnd"));
			$_REQUEST["action"] = 'edit';
		}

		// Users
		$usertodo=new User($db,$_POST["affectedto"]);
		if ($_POST["affectedto"])
		{
			$usertodo->fetch();
		}
		$actioncomm->usertodo = $usertodo;
		$userdone=new User($db,$_POST["doneby"]);
		if ($_POST["doneby"])
		{
			$userdone->fetch();
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
		$_GET["id"]=$_POST["id"];
	}
	else
	{
		if (! empty($_POST["from"]))
		{
			header("Location: ".$_POST["from"]);
			exit;
		}
		else
		{
			$_GET["id"]=$_REQUEST["id"];
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

/* ************************************************************************** */
/*                                                                            */
/* Affichage fiche en mode creation                                           */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create')
{
	$contact = new Contact($db);

	if ($_REQUEST["contactid"])
	{
		$result=$contact->fetch($_REQUEST["contactid"]);
		if ($result < 0) dol_print_error($db,$contact->error);
	}

	print '<form name="formaction" action="fiche.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add_action">';
	if (! empty($_REQUEST["backtopage"])) print '<input type="hidden" name="backtopage" value="'.($_REQUEST["backtopage"] != 1 ? $_REQUEST["backtopage"] : $_SERVER["HTTP_REFERER"]).'">';

	if ($_GET["actioncode"] == 'AC_RDV') print_fiche_titre ($langs->trans("AddActionRendezVous"));
	else print_fiche_titre ($langs->trans("AddAnAction"));

	if ($mesg) print $mesg.'<br>';
	else print "<br>";

	print '<table class="border" width="100%">';

	// Type d'action actifs
	print '<tr><td width="30%"><b>'.$langs->trans("Type").'*</b></td><td>';
	if ($_GET["actioncode"])
	{
		print '<input type="hidden" name="actioncode" value="'.$_GET["actioncode"].'">'."\n";
		$cactioncomm->fetch($_GET["actioncode"]);
		print $cactioncomm->getNomUrl();
	}
	else
	{
		$htmlactions->select_type_actions($actioncomm->type_code, "actioncode");
	}
	print '</td></tr>';

	// Title
	print '<tr><td>'.$langs->trans("Title").'</td><td><input type="text" name="label" size="60" value="'.$actioncomm->label.'"></td></tr>';

	// Location
	print '<tr><td>'.$langs->trans("Location").'</td><td><input type="text" name="location" size="60" value="'.$actioncomm->location.'"></td></tr>';

	// Societe, contact
	print '<tr><td width="30%" nowrap="nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	if ($_REQUEST["socid"] > 0)
	{
		$societe = new Societe($db);
		$societe->fetch($_REQUEST["socid"]);
		print $societe->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$_REQUEST["socid"].'">';
	}
	else
	{
		print $html->select_societes('','socid',1,1);
	}
	print '</td></tr>';

	// If company is forced, we propose contacts (may be contact is also forced)
	if ($_REQUEST["socid"] > 0)
	{
		print '<tr><td nowrap>'.$langs->trans("ActionOnContact").'</td><td>';
		$html->select_contacts($_REQUEST["socid"],$_REQUEST['contactid'],'contactid',1,1);
		print '</td></tr>';
	}

	print '</table>';
	print '<br>';
	print '<table class="border" width="100%">';

	// Affecte a
	print '<tr><td width="30%" nowrap="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td>';
	//	$html->select_users($_REQUEST["affectedto"]?$_REQUEST["affectedto"]:$actioncomm->usertodo,'affectedto',1);
	$html->select_users($_REQUEST["affectedto"]?$_REQUEST["affectedto"]:($actioncomm->usertodo->id > 0 ? $actioncomm->usertodo : $user),'affectedto',1);
	print '</td></tr>';

	// Realise par
	print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td>';
	$html->select_users($_REQUEST["doneby"]?$_REQUEST["doneby"]:$actioncomm->userdone,'doneby',1);
	print '</td></tr>';

	print '</table>';
	print '<br>';
	print '<table class="border" width="100%">';

	if (! empty($_GET["datep"]) && eregi('^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])$',$_GET["datep"],$reg))
	{
		$actioncomm->datep=dol_mktime(0,0,0,$reg[2],$reg[3],$reg[1]);
	}

	// Date start
	print '<tr><td width="30%" nowrap="nowrap">'.$langs->trans("DateActionStart").'</td><td>';
	if ($_REQUEST["afaire"] == 1) $html->select_date($actioncomm->datep,'ap',1,1,0,"action",1,1);
	else if ($_REQUEST["afaire"] == 2) $html->select_date($actioncomm->datep,'ap',1,1,1,"action",1,1);
	else $html->select_date($actioncomm->datep,'ap',1,1,1,"action",1,1);
	print '</td></tr>';
	// Date end
	print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td>';
	if ($_REQUEST["afaire"] == 1) $html->select_date($actioncomm->datef,'p2',1,1,1,"action",1,1);
	else if ($_REQUEST["afaire"] == 2) $html->select_date($actioncomm->datef,'p2',1,1,1,"action",1,1);
	else $html->select_date($actioncomm->datef,'p2',1,1,1,"action",1,1);
	print '</td></tr>';

	// Avancement
	print '<tr><td width="10%">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td>';
	print '<td>';
	$percent=0;
	if (isset($_POST['percentage']))
	{
		$percent=$_POST['percentage'];
	}
	else
	{
		if ($_REQUEST["afaire"] == 1) $percent=0;
		if ($_REQUEST["afaire"] == 2) $percent=100;
	}
	print $htmlactions->form_select_status_action('formaction',$percent,1);
	print '</td></tr>';

	// Priority
	print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
	print '<input type="text" name="priority" value="'.($_POST["priority"]?$_POST["priority"]:$actioncomm->priority).'" size="5">';
	print '</td></tr>';

	add_row_for_calendar_link();

	// Note
	print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>';
	if ($conf->fckeditor->enabled)
	{
		// Editeur wysiwyg
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('note',($_POST["note"]?$_POST["note"]:$actioncomm->note),280,'dolibarr_notes','In',true);
		$doleditor->Create();
	}
	else
	{
		print '<textarea name="note" cols="90" rows="'.ROWS_7.'">'.($_POST["note"]?$_POST["note"]:$actioncomm->note).'</textarea>';
	}
	print '</td></tr>';

	print '<tr><td align="center" colspan="2">';
	print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';

	print '</table>';

	print "</form>";
}

/*
 * Affichage action en mode edition ou visu
 */
if ($_GET["id"])
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
	$result=$act->fetch($_GET["id"]);
	if ($result < 0) dol_print_error($db,$act->error);

	$societe = new Societe($db);
	if ($act->societe->id)
	{
		$result=$societe->fetch($act->societe->id);
	}
	$act->societe = $societe;

	if ($act->author->id > 0)   { $tmpuser=new User($db); $tmpuser->id=$act->author->id;   $res=$tmpuser->fetch(); $act->author=$tmpuser; }
	if ($act->usermod->id > 0)  { $tmpuser=new User($db); $tmpuser->id=$act->usermod->id;  $res=$tmpuser->fetch(); $act->usermod=$tmpuser; }
	if ($act->usertodo->id > 0) { $tmpuser=new User($db); $tmpuser->id=$act->usertodo->id; $res=$tmpuser->fetch(); $act->usertodo=$tmpuser; }
	if ($act->userdone->id > 0) { $tmpuser=new User($db); $tmpuser->id=$act->userdone->id; $res=$tmpuser->fetch(); $act->userdone=$tmpuser; }

	$contact = new Contact($db);
	if ($act->contact->id)
	{
		$result=$contact->fetch($act->contact->id,$user);
	}
	$act->contact = $contact;

	/*
	 * Affichage onglets
	 */

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/fiche.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans("CardAction");
	$hselected=$h;
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/document.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans('Documents');
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/comm/action/info.php?id='.$_GET["id"];
	$head[$h][1] = $langs->trans('Info');
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("Action"),0,'task');

	$now=gmmktime();
	$delay_warning=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;

	// Confirmation suppression action
	if ($_GET["action"] == 'delete')
	{
		$ret=$html->form_confirm("fiche.php?id=".$_GET["id"],$langs->trans("DeleteAction"),$langs->trans("ConfirmDeleteAction"),"confirm_delete",'','',1);
		if ($ret == 'html') print '<br>';
	}

	if ($_REQUEST["action"] == 'edit')
	{
		// Fiche action en mode edition
		print '<form name="formaction" action="fiche.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$_REQUEST["id"].'">';
		if (! empty($_REQUEST["backtopage"])) print '<input type="hidden" name="from" value="'.($_REQUEST["from"] ? $_REQUEST["from"] : $_SERVER["HTTP_REFERER"]).'">';

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">'.$act->id.'</td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';

		// Title
		print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3"><input type="text" name="label" size="50" value="'.$act->label.'"></td></tr>';

		// Location
		print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3"><input type="text" name="location" size="50" value="'.$act->location.'"></td></tr>';

		// Company
		print '<tr><td>'.$langs->trans("Company").'</td>';
		print '<td>';
		print $html->select_societes($act->societe->id,'socid',1,1);
		print '</td>';

		print '<td>'.$langs->trans("Contact").'</td><td width="30%">';
		$html->select_array("contactid",  $act->societe->contact_array(), $act->contact->id, 1);
		print '</td></tr>';

		print '</table><br><table class="border" width="100%">';

		// Input by
		print '<tr><td width="30%" nowrap>'.$langs->trans("ActionAskedBy").'</td><td colspan="3">';
		print $act->author->getNomUrl(1);
		print '</td></tr>';

		// Affecte a
		print '<tr><td nowrap>'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
		$html->select_users($act->usertodo->id,'affectedto',1);
		print '</td></tr>';

		// Realise par
		print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td colspan="3">';
		$html->select_users($act->userdone->id,'doneby',1);
		print '</td></tr>';

		print '</table><br><table class="border" width="100%">';

		// Date start
		print '<tr><td width="30%" nowrap="nowrap">'.$langs->trans("DateActionStart").'</td><td colspan="3">';
		if ($_REQUEST["afaire"] == 1) $html->select_date($act->datep,'ap',1,1,0,"action",1,1);
		else if ($_REQUEST["afaire"] == 2) $html->select_date($act->datep,'ap',1,1,1,"action",1,1);
		else $html->select_date($act->datep,'ap',1,1,1,"action",1,1);
		print '</td></tr>';
		// Date end
		print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="3">';
		if ($_REQUEST["afaire"] == 1) $html->select_date($act->datef,'p2',1,1,1,"action",1,1);
		else if ($_REQUEST["afaire"] == 2) $html->select_date($act->datef,'p2',1,1,1,"action",1,1);
		else $html->select_date($act->datef,'p2',1,1,1,"action",1,1);
		print '</td></tr>';

		// Status
		print '<tr><td nowrap>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="3">';
		$percent=isset($_REQUEST["percentage"])?$_REQUEST["percentage"]:$act->percentage;
		print $htmlactions->form_select_status_action('formaction',$percent,1);
		print '</td></tr>';

		// Priority
		print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
		print '<input type="text" name="priority" value="'.$act->priority.'" size="5">';
		print '</td></tr>';

		// Object linked
		if ($act->objet_url)
		{
			print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
			print '<td colspan="3">'.$act->objet_url.'</td></tr>';
		}

		// Note
		print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
		if ($conf->fckeditor->enabled)
		{
			// Editeur wysiwyg
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('note',$act->note,240,'dolibarr_notes','In',true);
			$doleditor->Create();
		}
		else
		{
			print '<textarea name="note" cols="90" rows="'.ROWS_7.'">'.dol_htmlentitiesbr_decode($act->note).'</textarea>';
		}

		print '</td></tr>';

		print '<tr><td align="center" colspan="4"><input type="submit" class="button" name="edit" value="'.$langs->trans("Save").'">';
		print ' &nbsp; &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';

		print '</table></form>';
	}
	else
	{
		// Affichage fiche action en mode visu
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">'.$act->id.'</td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';

		// Title
		print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$act->label.'</td></tr>';

		// Location
		print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3">'.$act->location.'</td></tr>';

		// Societe - contact
		print '<tr><td>'.$langs->trans("Company").'</td><td>'.($act->societe->id?$act->societe->getNomUrl(1):$langs->trans("None"));
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

		// Realise par
		print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td colspan="3">';
		if ($act->userdone->id > 0) print $act->userdone->getNomUrl(1);
		print '</td></tr>';

		print '</table><br><table class="border" width="100%">';

		// Date debut
		print '<tr><td width="30%">'.$langs->trans("DateActionStart").'</td><td colspan="3">';
		print dol_print_date($act->datep,'dayhour');
		if ($act->percentage == 0 && $act->datep && $act->datep < ($now - $delay_warning)) print img_warning($langs->trans("Late"));
		print '</td></tr>';

		// Date fin
		print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="3">';
		print dol_print_date($act->datef,'dayhour');
		if ($act->percentage > 0 && $act->percentage < 100 && $act->datef && $act->datef < ($now- $delay_warning)) print img_warning($langs->trans("Late"));
		print '</td></tr>';

		// Statut
		print '<tr><td nowrap>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="3">';
		print $act->getLibStatut(4);
		print '</td></tr>';

		// Priority
		print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
		print $act->priority;
		print '</td></tr>';

		// Objet lie
		if ($act->objet_url)
		{
			print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
			print '<td colspan="3">'.$act->objet_url.'</td></tr>';
		}

		// Note
		print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
		print dol_htmlentitiesbr($act->note);
		print '</td></tr>';

		print '</table>';
	}

	print "</div>\n";


	/**
	 * Barre d'actions
	 *
	 */

	print '<div class="tabsAction">';

	if ($_GET["action"] != 'edit')
	{
		if ($user->rights->agenda->allactions->create)
		{
			print '<a class="butAction" href="fiche.php?action=edit&id='.$act->id.'">'.$langs->trans("Modify").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Modify").'</a>';
		}

		if ($user->rights->agenda->allactions->create)
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

llxFooter('$Date$ - $Revision$');


/**
 \brief      Ajoute une ligne de tableau a 2 colonnes pour avoir l'option synchro calendrier
 \return     int     Retourne le nombre de lignes ajoutees
 */
function add_row_for_calendar_link()
{
	global $conf,$langs,$user;
	$nbtr=0;

	// Lien avec calendrier si module active
	if ($conf->webcal->enabled)
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
