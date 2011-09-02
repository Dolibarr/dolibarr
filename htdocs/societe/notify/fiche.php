<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Juanjo Menent		<jmenent@2byte.es>
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
 *	    \file       htdocs/societe/notify/fiche.php
 *      \ingroup    societe notification
 *		\brief      Tab for notifications of third party
 *		\version    $Id: fiche.php,v 1.71 2011/08/08 16:00:16 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/triggers/interface_modNotification_Notification.class.php");

$langs->load("companies");
$langs->load("mails");
$langs->load("admin");
$langs->load("other");

$socid = GETPOST("socid",'int');
$action = GETPOST('action');
$contactid=GETPOST('contactid');    // May be an int or 'thirdparty'
$actionid=GETPOST('actionid');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="c.name";

$now=dol_now();


/*
 * Action
 */

// Add a notification
// Add a notification
if ($action == 'add')
{
    $error=0;

    if (empty($contactid))
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Contact")).'</div>';
        $error++;
    }
    if ($actionid <= 0)
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Action")).'</div>';
        $error++;
    }

    if (! $error)
    {
        $db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
        $sql .= " WHERE fk_soc=".$socid." AND fk_contact=".$contactid." AND fk_action=".$actionid;
        if ($db->query($sql))
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec,fk_soc, fk_contact, fk_action)";
            $sql .= " VALUES ('".$db->idate($now)."',".$socid.",".$contactid.",".$actionid.")";

            if ($db->query($sql))
            {

            }
            else
            {
                $error++;
                dol_print_error($db);
            }
        }
        else
        {
            dol_print_error($db);
        }

        if (! $error)
        {
            $db->commit();
        }
        else
        {
            $db->rollback();
        }
    }
}

// Remove a notification
if ($action == 'delete')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def where rowid=".$_GET["actid"].";";
	$db->query($sql);
}



/*
 *	View
 */

$form = new Form($db);

llxHeader();

$soc = new Societe($db);
$result=$soc->fetch($socid);

if ($result > 0)
{
	$html = new Form($db);
	$langs->load("other");


	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'notify', $langs->trans("ThirdParty"),0,'company');


	print '<table class="border"width="100%">';

	print '<tr><td width="20%">'.$langs->trans("ThirdPartyName").'</td><td colspan="3">';
	print $form->showrefnav($soc,'socid','',($user->societe_id?0:1),'rowid','nom');
	print '</td></tr>';

    // Prefix
    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
    {
        print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
    }

    if ($object->client)
    {
        print '<tr><td>';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $object->code_client;
        if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    if ($object->fournisseur)
    {
        print '<tr><td>';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $object->code_fournisseur;
        if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';
    }

	print '<tr><td width="30%">'.$langs->trans("NbOfActiveNotifications").'</td>';
	print '<td colspan="3">';
	$sql = "SELECT COUNT(n.rowid) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."notify_def as n";
	$sql.= " WHERE fk_soc = ".$soc->id;
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$nb=$obj->nb;
			$i++;
		}
	}
	else {
		dol_print_error($db);
	}
	print $nb;
	print '</td></tr>';
	print '</table>';

	print '</div>';


	// Help
	print $langs->trans("NotificationsDesc").'<br><br>';


	print "\n";

	// Add notification form
	print_fiche_titre($langs->trans("AddNewNotification"),'','');

	print '<form action="fiche.php?socid='.$socid.'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
	
	// Line with titles
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	$param="&socid=".$socid;
	print_liste_field_titre($langs->trans("Contact"),"fiche.php","c.name",'',$param,'"width="45%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Action"),"fiche.php","a.titre",'',$param,'"width="35%"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Type"),"fiche.php","",'',$param,'"width="10%"',$sortfield,$sortorder);
    print_liste_field_titre('');
    print '</tr>';

	$var=false;
	if (count($soc->thirdparty_and_contact_email_array()) > 0)
	{
	    $actions=array();

        // Load array of available notifications
        $notificationtrigger=new InterfaceNotification($db);
        $listofnotifiedevents=$notificationtrigger->getListOfManagedEvents();

        foreach($listofnotifiedevents as $notifiedevent)
        {
            $label=$langs->trans("Notify_".$notifiedevent['code'])!=$langs->trans("Notify_".$notifiedevent['code'])?$langs->trans("Notify_".$notifiedevent['code']):$notifiedevent['label'];
            $actions[$notifiedevent['rowid']]=$label;
        }
		print '<input type="hidden" name="action" value="add">';
		print '<tr '.$bc[$var].'><td>';
		print $html->selectarray("contactid",$soc->thirdparty_and_contact_email_array());
		print '</td>';
		print '<td>';
		print $html->selectarray("actionid",$actions,'',1);
		print '</td>';
        print '<td>';
        $type=array('email'=>$langs->trans("EMail"));
        print $html->selectarray("typeid",$type);
        print '</td>';
		print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
		print '</tr>';
	}
	else
	{
		print '<tr '.$bc[$var].'><td colspan="4">';
		print $langs->trans("YouMustCreateContactFirst");
		print '</td></tr>';
	}

	print '</table>';

	print '</form>';
	print '<br>';

    dol_htmloutput_mesg($mesg);
	
	// List of active notifications
	print_fiche_titre($langs->trans("ListOfActiveNotifications"),'','');
	$var=true;

	// Line with titles
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Contact"),"fiche.php","c.name",'',$param,'"width="45%"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Action"),"fiche.php","a.titre",'',$param,'"width="35%"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Type"),"fiche.php","",'',$param,'"width="10%"',$sortfield,$sortorder);
	print_liste_field_titre('','','');
	print '</tr>';

	// List of notifications for contacts
	$sql = "SELECT n.rowid, n.type,";
	$sql.= " a.code, a.label,";
    $sql.= " c.rowid as contactid, c.name, c.firstname, c.email";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
	$sql.= " ".MAIN_DB_PREFIX."notify_def as n,";
	$sql.= " ".MAIN_DB_PREFIX."socpeople c";
	$sql.= " WHERE a.rowid = n.fk_action";
	$sql.= " AND c.rowid = n.fk_contact";
	$sql.= " AND c.fk_soc = ".$soc->id;

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		$contactstatic=new Contact($db);

		while ($i < $num)
		{
			$var = !$var;

			$obj = $db->fetch_object($resql);

			$contactstatic->id=$obj->contactid;
			$contactstatic->name=$obj->name;
			$contactstatic->firstname=$obj->firstname;
			print '<tr '.$bc[$var].'><td>'.$contactstatic->getNomUrl(1);
			if ($obj->type == 'email')
			{
				if (isValidEmail($obj->email))
				{
					print ' &lt;'.$obj->email.'&gt;';
				}
				else
				{
					$langs->load("errors");
					print ' &nbsp; '.img_warning().' '.$langs->trans("ErrorBadEMail",$obj->email);
				}
			}
			print '</td>';
			print '<td>';
			$label=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);
			print $label;
			print '</td>';
            print '<td>';
            if ($obj->type == 'email') print $langs->trans("Email");
            if ($obj->type == 'sms') print $langs->trans("SMS");
            print '</td>';
            print '<td align="right"><a href="fiche.php?socid='.$socid.'&action=delete&actid='.$obj->rowid.'">'.img_delete().'</a></td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print '</table>';
	print '<br>';


	// List of notifications done
	print_fiche_titre($langs->trans("ListOfNotificationsDone"),'','');
	$var=true;

	// Line with titles
	print '<table width="100%" class="noborder">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Contact"),"fiche.php","c.name",'',"&socid=$socid",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Action"),"fiche.php","a.titre",'',"&socid=$socid",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),"fiche.php","a.titre",'',"&socid=$socid",'align="right"',$sortfield,$sortorder);
	print '</tr>';

	// List
	$sql = "SELECT n.rowid, n.daten, n.email, n.objet_type, n.objet_id,";
	$sql.= " c.rowid as id, c.name, c.firstname, c.email,";
	$sql.= " a.code, a.label";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
	$sql.= " ".MAIN_DB_PREFIX."notify as n, ";
    $sql.= " ".MAIN_DB_PREFIX."socpeople as c";
    $sql.= " WHERE a.rowid = n.fk_action";
    $sql.= " AND c.rowid = n.fk_contact";
    $sql.= " AND c.fk_soc = ".$soc->id;

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		$contactstatic=new Contact($db);

		while ($i < $num)
		{
			$var = !$var;

			$obj = $db->fetch_object($resql);

			$contactstatic->id=$obj->id;
			$contactstatic->name=$obj->name;
			$contactstatic->firstname=$obj->firstname;
			print '<tr '.$bc[$var].'><td>'.$contactstatic->getNomUrl(1);
			print $obj->email?' &lt;'.$obj->email.'&gt;':$langs->trans("NoMail");
			print '</td>';
			print '<td>';
			$label=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);
			print $label;
			print '</td>';
			// TODO Add link to object here
			// print
			print'<td align="right">'.dol_print_date($db->jdate($obj->daten), 'dayhour').'</td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print '</table>';
}
else dol_print_error('','RecordNotFound');

$db->close();

llxFooter('$Date: 2011/08/08 16:00:16 $ - $Revision: 1.71 $');

?>
