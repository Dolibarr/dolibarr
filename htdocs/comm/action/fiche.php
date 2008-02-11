<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/comm/action/fiche.php
        \ingroup    commercial
        \brief      Page de la fiche action
        \version    $Id$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("other");
$langs->load("bills");
$langs->load("orders");

// Securite acces client
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}
if (isset($_GET["error"])) $error=$_GET["error"];


/*
 * Action creation de l'action
 *
 */
if ($_POST["action"] == 'add_action')
{
    if ($_POST["contactid"])
    {
        $contact = new Contact($db);
        $result=$contact->fetch($_POST["contactid"]);
    }
	
    if ($_POST['cancel'])
	{
		header("Location: ".DOL_URL_ROOT.'/comm/fiche.php?socid='.$_POST['socid']);
		exit;	
	}

	// Nettoyage parametres
	if ($_POST["aphour"] == -1) $_POST["aphour"]='0';
	if ($_POST["apmin"] == -1) $_POST["apmin"]='0';
	if ($_POST["adhour"] == -1) $_POST["adhour"]='0';
	if ($_POST["admin"] == -1) $_POST["admin"]='0';
	$datep=dolibarr_mktime($_POST["aphour"],
                   $_POST["apmin"],
                   0,
                   $_POST["apmonth"],
                   $_POST["apday"],
                   $_POST["apyear"]);
	$datea=dolibarr_mktime($_POST["adhour"],
                   $_POST["admin"],
                   0,
                   $_POST["admonth"],
                   $_POST["adday"],
                   $_POST["adyear"]);
	// Si param incorrects, mktime renvoi false en PHP 5.1, -1 avant
	if (! ($datep > 0)) $datep='';
	if (! ($datea > 0)) $datea='';

    if (! $_POST["actioncode"])
    {
    	$error=1;
		$_GET["action"] = 'create';
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Type")).'</div>';
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
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Date")).'</div>';
 	}

    if (! $error)
    {
        $db->begin();

        $cactioncomm = new CActionComm($db);
        $cactioncomm->fetch($_POST["actioncode"]);

        // Initialisation objet actioncomm
        $actioncomm = new ActionComm($db);

        $actioncomm->type_id = $cactioncomm->id;
        $actioncomm->type_code = $cactioncomm->code;
        $actioncomm->priority = isset($_POST["priority"])?$_POST["priority"]:0;
        $actioncomm->label = trim($_POST["label"]);
        if (! $_POST["label"])
        {
            if ($_POST["actioncode"] == 'AC_RDV' && $contact->getFullName($langs))
            {
                $actioncomm->label = $langs->trans("TaskRDVWith",$contact->getFullName($langs));
            }
            else
            {
                if ($langs->trans("Action".$actioncomm->type_code) != "Action".$actioncomm->type_code)
                {
                    $actioncomm->label = $langs->trans("Action".$actioncomm->type_code)."\n";
                }
            }
        }
//        print $_POST["aphour"]." ".$_POST["apmin"]." ".$_POST["apday"];
    	$actioncomm->datep = $datep;
    	$actioncomm->date = $datea;
	    if ($_POST["percentage"] < 100 && ! $actioncomm->datep) $actioncomm->datep=$actioncomm->date;
		if ($actioncomm->type_code == 'AC_RDV')
		{
			// RDV
			if ($actioncomm->date)
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
		
        $actioncomm->note = trim($_POST["note"]);
        if (isset($_POST["contactid"]))    $actioncomm->contact = $contact;
        if (isset($_REQUEST["socid"]) && $_REQUEST["socid"] > 0)
        {
	        $societe = new Societe($db);
    	    $societe->fetch($_REQUEST["socid"]);
        	$actioncomm->societe = $societe;
       	}
        if ($_POST["add_webcal"] == 'on' && $conf->webcal->enabled) $actioncomm->use_webcal=1;
        if ($_POST["add_phenix"] == 'on' && $conf->phenix->enabled) $actioncomm->use_phenix=1;

        // On cr�e l'action
        $idaction=$actioncomm->add($user);

        if ($idaction > 0)
        {
            if (! $actioncomm->error)
            {
                $db->commit();
                if ($_POST["from"])
                {
					dolibarr_syslog("Back to ".$_POST["from"]);
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
	            $error='<div class="error">'.$actioncomm->error.'</div>';
            }
        }
        else
        {
            $db->rollback();
            $error='<div class="error">'.$actioncomm->error.'</div>';
        }
    }

//    print $_REQUEST["from"]."rr";
}

/*
 * Action suppression de l'action
 *
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
    $actioncomm = new ActionComm($db);
    $actioncomm->fetch($_GET["id"]);
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

/*
 * Action mise a jour de l'action
 *
 */
if ($_POST["action"] == 'update')
{
    if (! $_POST["cancel"])
    {
		if ($_POST["aphour"] == -1) $_POST["aphour"]='0';
		if ($_POST["apmin"] == -1) $_POST["apmin"]='0';
		if ($_POST["adhour"] == -1) $_POST["adhour"]='0';
		if ($_POST["admin"] == -1) $_POST["admin"]='0';

        $actioncomm = new Actioncomm($db);
        $actioncomm->fetch($_POST["id"]);

    	$actioncomm->datep = @mktime($_POST["aphour"],
                                   $_POST["apmin"],
                                   0,
                                   $_POST["apmonth"],
                                   $_POST["apday"],
                                   $_POST["apyear"]);
    	$actioncomm->date = @mktime($_POST["adhour"],
                                   $_POST["admin"],
                                   0,
                                   $_POST["admonth"],
                                   $_POST["adday"],
                                   $_POST["adyear"]);
		//print $_POST["apmonth"].",".$_POST["apday"].",".$_POST["apyear"].",".$_POST["aphour"].",".$_POST["apmin"]."<br>\n";
		//print $actioncomm->datep;
        $actioncomm->label       = $_POST["label"];
        $actioncomm->percentage  = $_POST["percentage"];
        $actioncomm->priority    = $_POST["priority"];
        $actioncomm->contact->id = $_POST["contactid"];
        $actioncomm->note        = $_POST["note"];
		if ($actioncomm->type_code == 'AC_RDV' && $actioncomm->percentage == 100 && ! $actioncomm->date)
		{
			$actioncomm->date = $actioncomm->datep;
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

        $result=$actioncomm->update($user);
    }

    if ($result < 0)
    {
    	$mesg='<div class="error">'.$actioncomm->error.'</div>';
    	$_GET["id"]=$_POST["id"];
    }
    else
    {
    	Header("Location: ".$_POST["from"]);
    	exit;
    }
}



llxHeader();

$html = new Form($db);

/* ************************************************************************** */
/*                                                                            */
/* Affichage fiche en mode creation                                           */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create')
{
	$caction = new CActioncomm($db);

	if ($_GET["contactid"])
	{
		$contact = new Contact($db);
		$contact->fetch($_GET["contactid"]);
	}

	print '<form name="action" action="fiche.php" method="post">';
    print '<input type="hidden" name="from" value="'.($_REQUEST["from"] ? $_REQUEST["from"] : $_SERVER["HTTP_REFERER"]).'">';
	print '<input type="hidden" name="action" value="add_action">';

	/*
	* Si action de type Rendez-vous
	*
	*/
	if ($_GET["actioncode"] == 'AC_RDV')
	{
		print_titre ($langs->trans("AddActionRendezVous"));
		print "<br>";

		if ($mesg) print $mesg.'<br>';

		print '<input type="hidden" name="date" value="'.$db->idate(time()).'">'."\n";

		print '<table class="border" width="100%">';

		// Type d'action
		print '<input type="hidden" name="actioncode" value="AC_RDV">';

		// Societe, contact
		print '<tr><td nowrap>'.$langs->trans("ActionOnCompany").'</td><td>';
		if ($_REQUEST["socid"])
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

		// Si la societe est imposee, on propose ces contacts
		if ($_REQUEST["socid"])
		{
			$contactid = $_REQUEST["contactid"]?$_REQUEST["contactid"]:'';
			print '<tr><td>'.$langs->trans("ActionOnContact").'</td><td>';
			$html->select_contacts($_REQUEST["socid"],$contactid,'contactid',1,1);
			print '</td></tr>';
		}

		// Affecte a
		print '<tr><td nowrap>'.$langs->trans("ActionAffectedTo").'</td><td>';
		$html->select_users($_REQUEST["affectedto"],'affectedto',1);
		print '</td></tr>';

		// Realise par
		print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td>';
		$html->select_users($_REQUEST["doneby"],'doneby',1);
		print '</td></tr>';

		// Date planification
		print '<tr><td>'.$langs->trans("DateActionPlanned").'</td><td>';
		if ($_GET["afaire"] == 1 || $_GET["afaire"] == 2)
		{
			$html->select_date(-1,'ap',1,1,1,"action");
		}
		else
		{
			$html->select_date(-1,'ap',1,1,1,"action");
		}
		print '</td></tr>';

		// Date done
		print '<tr><td>'.$langs->trans("DateActionDone").'</td><td>';
		if ($_GET["afaire"] == 1 || $_GET["afaire"] == 2)
		{
			$html->select_date(-1,'ad',1,1,1,"action");
		}
		else
		{
			$html->select_date(-1,'ad',1,1,1,"action");
		}
		print '</td></tr>';

		// Duration
		print '<tr><td>'.$langs->trans("Duration").'</td><td>';
		$html->select_duree("duree");
		print '</td></tr>';

		add_row_for_calendar_link();

		// Note
		print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>';
		if ($conf->fckeditor->enabled)
	    {
		    // Editeur wysiwyg
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('note','',280,'dolibarr_notes','In',true);
			$doleditor->Create();
	    }
	    else
	    {
			print '<textarea name="note" cols="90" rows="'.ROWS_8.'">'.$societe->note.'</textarea>';
	    }
		print '</td></tr>';

		print '<tr><td colspan="2" align="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';
		print '</table>';
	}

	/*
	* Si action de type autre que rendez-vous
	*
	*/
	else
	{
		print_titre ($langs->trans("AddAnAction"));
		print "<br>";

		if ($mesg) print $mesg.'<br>';

		print '<table class="border" width="100%">';

		// Type d'action actifs
		print '<tr><td><b>'.$langs->trans("Type").'*</b></td><td>';
		if ($_GET["actioncode"])
		{
			print '<input type="hidden" name="actioncode" value="'.$_GET["actioncode"].'">'."\n";
			$caction->fetch($_GET["actioncode"]);
			print $caction->getNomUrl();
		}
		else
		{
			$html->select_type_actions(0, "actioncode");
		}
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Title").'</td><td><input type="text" name="label" size="30"></td></tr>';

		// Societe, contact
		print '<tr><td nowrap>'.$langs->trans("ActionOnCompany").'</td><td>';
		if ($_REQUEST["socid"])
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

		// Si la societe est imposee, on propose ces contacts
		if ($_REQUEST["socid"])
		{
			print '<tr><td nowrap>'.$langs->trans("ActionOnContact").'</td><td>';
			$html->select_contacts($_REQUEST["socid"],'','contactid',1,1);
			print '</td></tr>';
		}

		// Affecte a
		print '<tr><td nowrap>'.$langs->trans("ActionAffectedTo").'</td><td>';
		$html->select_users($_REQUEST["affectedto"],'affectedto',1);
		print '</td></tr>';

		// Realise par
		print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td>';
		$html->select_users($_REQUEST["doneby"],'doneby',1);
		print '</td></tr>';

		// Avancement
		if ($_GET["afaire"] == 1)
		{
			print '<input type="hidden" name="percentage" value="0">';
			print '<input type="hidden" name="todo" value="on">';
			print '<tr><td width="10%">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td>'.$langs->trans("StatusActionToDo").' / 0%</td></tr>';
		}
		elseif ($_GET["afaire"] == 2)
		{
			print '<input type="hidden" name="percentage" value="100">';
			print '<tr><td>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td>'.$langs->trans("StatusActionDone").' / 100%</td></tr>';
		} else
		{
			print '<tr><td>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td><input type="text" name="percentage" value="0" size="4">%</td></tr>';
		}

		// Date planification
		print '<tr><td>'.$langs->trans("DateActionPlanned").'</td><td>';
		if ($_GET["afaire"] == 1 || $_GET["afaire"] == 2)
		{
			$html->select_date('','ap',1,1,0,"action");
		}
		else
		{
			$html->select_date('','ap',1,1,0,"action");
		}
		print '</td></tr>';

		// Date done
		print '<tr><td>'.$langs->trans("DateActionDone").'</td><td>';
		if ($_GET["afaire"] == 1 || $_GET["afaire"] == 2)
		{
			$html->select_date(-1,'ad',1,1,1,"action");
		}
		elseif ($_GET["afaire"] != 1)
		{
			$html->select_date(-1,'ad',1,1,1,"action");
		}
		print '</td></tr>';

		add_row_for_calendar_link();

		// Note
		print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>';
		if ($conf->fckeditor->enabled)
	    {
		    // Editeur wysiwyg
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('note','',280,'dolibarr_notes','In',true);
			$doleditor->Create();
	    }
	    else
	    {
			print '<textarea name="note" cols="90" rows="'.ROWS_8.'"></textarea>';
	    }
		print '</td></tr>';

		print '<tr><td align="center" colspan="2">';
		print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';

		print '</table>';


	}
	print "</form>";
}

/*
 * Affichage action en mode edition ou visu
 *
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
    $act->fetch($_GET["id"]);
    $res=$act->societe->fetch($act->societe->id);

    if ($act->author->id)   $res=$act->author->fetch();     // Le parametre est le login, hors seul l'id est charge.
    if ($act->usermod->id)  $res=$act->usermod->fetch();    
    if ($act->usertodo->id) $res=$act->usertodo->fetch();   
    if ($act->userdone->id) $res=$act->userdone->fetch();

    $res=$act->contact->fetch($act->contact->id);

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

    dolibarr_fiche_head($head, $hselected, $langs->trans("Action"));


    // Confirmation suppression action
    if ($_GET["action"] == 'delete')
    {
        $html->form_confirm("fiche.php?id=".$_GET["id"],$langs->trans("DeleteAction"),$langs->trans("ConfirmDeleteAction"),"confirm_delete");
        print '<br>';
    }

    if ($_GET["action"] == 'edit')
    {
        // Fiche action en mode edition
        print '<form action="fiche.php" method="post">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
        print '<input type="hidden" name="from" value="'.($_REQUEST["from"] ? $_REQUEST["from"] : $_SERVER["HTTP_REFERER"]).'">';

        print '<table class="border" width="100%">';
        print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">'.$act->id.'</td></tr>';
        print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';
        print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3"><input type="text" name="label" size="50" value="'.$act->label.'"></td></tr>';
        print '<tr><td>'.$langs->trans("Company").'</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$act->societe->id.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$act->societe->nom.'</a></td>';

        print '<td>'.$langs->trans("Contact").'</td><td width="30%">';
        $html->select_array("contactid",  $act->societe->contact_array(), $act->contact->id, 1);
        print '</td></tr>';

		// Priorite
		print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
		print '<input type="text" name="priority" value="'.$act->priority.'" size="5">';
		print '</td></tr>';

		// Affecte a
		print '<tr><td nowrap>'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
		$html->select_users($act->usertodo->id,'affectedto',1);
		print '</td></tr>';

		// Realise par
		print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td colspan="3">';
		$html->select_users($act->userdone->id,'doneby',1);
		print '</td></tr>';

		// Date planification
		print '<tr><td>'.$langs->trans("DateActionPlanned").'</td><td colspan="3">';
		$html->select_date(($act->datep?$act->datep:-1),'ap',1,1,1,"action");
		if ($act->percentage < 100 && $act->datep < (time() - $conf->global->MAIN_DELAY_ACTIONS_TODO)) print img_warning($langs->trans("Late"));
		print '</td></tr>';

		// Date done
		print '<tr><td>'.$langs->trans("DateActionDone").'</td><td colspan="3">';
		$html->select_date(($act->date?$act->date:-1),'ad',1,1,1,"action");
		print '</td></tr>';


		// Etat
        print '<tr><td nowrap>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="3"><input name="percentage" value="'.$act->percentage.'" size="4">%</td></tr>';

		// Objet li�
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
			$doleditor=new DolEditor('note',$act->note,280,'dolibarr_notes','In',true);
			$doleditor->Create();
	    }
	    else
	    {
			print '<textarea name="note" cols="90" rows="'.ROWS_8.'">'.$act->note.'</textarea>';
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

		// Libelle
        print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$act->label.'</td></tr>';

		// Societe - contact
        print '<tr><td>'.$langs->trans("Company").'</td><td>'.$act->societe->getNomUrl(1).'</td>';
        print '<td>'.$langs->trans("Contact").'</td>';
        print '<td>';
        if ($act->contact->id > 0)
        {
        	print $act->contact->getNomUrl(1);
        }
        else
        {
        	print $langs->trans("None");
        }

        print '</td></tr>';

		// Priorite
		print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
		print $act->priority;
		print '</td></tr>';

		// Affecte a
		print '<tr><td nowrap>'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
		if ($act->usertodo->id) print $act->usertodo->getNomUrl(1);
		print '</td></tr>';

		// Realise par
		print '<tr><td nowrap>'.$langs->trans("ActionDoneBy").'</td><td colspan="3">';
		if ($act->userdone->id) print $act->userdone->getNomUrl(1);
		print '</td></tr>';

        // Date planification
		print '<tr><td>'.$langs->trans("DateActionPlanned").'</td><td colspan="3">';
		print dolibarr_print_date($act->datep,'dayhour');
		if ($act->percentage < 100 && $act->datep < (time() - $conf->global->MAIN_DELAY_ACTIONS_TODO)) print img_warning($langs->trans("Late"));
		print '</td></tr>';

        // Date fin real
        print '<tr><td>'.$langs->trans("DateActionDone").'</td><td colspan="3">'.dolibarr_print_date($act->date,'dayhour').'</td></tr>';

        // Statut
        print '<tr><td nowrap>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="3">';
        print $act->getLibStatut(4);
        print '</td></tr>';

		// Objet li�
        if ($act->objet_url)
        {
            print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
            print '<td colspan="3">'.$act->objet_url.'</td></tr>';
        }

        // Note
        print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
		if ($conf->fckeditor->enabled) print nl2br($act->note);
		else print nl2br(htmlentities($act->note));
        print '</td></tr>';

        print '</table>';
    }

    print "</div>\n";


    /**
    * Barre d'actions
    *
    */

    print '<div class="tabsAction">';

    if ($_GET["action"] != 'edit' && $_GET["action"] != 'delete')
    {
        print '<a class="butAction" href="fiche.php?action=edit&id='.$act->id.'">'.$langs->trans("Modify").'</a>';

        print '<a class="butActionDelete" href="fiche.php?action=delete&id='.$act->id.'">'.$langs->trans("Delete").'</a>';
    }

    print '</div>';
}

$db->close();

llxFooter('$Date$ - $Revision$');


/**
        \brief      Ajoute une ligne de tableau a 2 colonnes pour avoir l'option synchro calendrier
        \return     int     Retourne le nombre de lignes ajout�es
*/
function add_row_for_calendar_link()
{
	global $conf,$langs,$user;
  $nbtr=0;

  // Lien avec calendrier si module activ�
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
