<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *	    \file       htdocs/societe/notify/card.php
 *      \ingroup    societe notification
 *		\brief      Tab for notifications of third party
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_50_modNotification_Notification.class.php';

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
if (! $sortfield) $sortfield="c.lastname";

$now=dol_now();


/*
 * Actions
 */

// Add a notification
if ($action == 'add')
{
    $error=0;

    if (empty($contactid))
    {
	    setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Contact")), 'errors');
        $error++;
    }
    if ($actionid <= 0)
    {
	    setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Action")), 'errors');
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

            if (! $db->query($sql))
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
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def where rowid=".$_GET["actid"];
    $db->query($sql);
}



/*
 *	View
 */

$form = new Form($db);

$object = new Societe($db);
$result=$object->fetch($socid);

$title=$langs->trans("ThirdParty").' - '.$langs->trans("Notification");
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name.' - '.$langs->trans("Notification");
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$title,$help_url);


if ($result > 0)
{
    $langs->load("other");

    $head = societe_prepare_head($object);

    dol_fiche_head($head, 'notify', $langs->trans("ThirdParty"),0,'company');


    print '<table class="border"width="100%">';

    print '<tr><td width="25%">'.$langs->trans("ThirdPartyName").'</td><td colspan="3">';
    print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom');
    print '</td></tr>';

	// Alias names (commercial, trademark or alias names)
	print '<tr><td valign="top">'.$langs->trans('AliasNames').'</td><td colspan="3">';
	print $object->name_alias;
	print "</td></tr>";

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

    print '<tr><td>'.$langs->trans("NbOfActiveNotifications").'</td>';
    print '<td colspan="3">';
    $notify=new Notify($db);
    $tmparray = $notify->getNotificationsArray('', $object->id);
    print count($tmparray);
    print '</td></tr>';
    print '</table>';

    dol_fiche_end();

    // Help
    print $langs->trans("NotificationsDesc").'<br><br>';

    print "\n";

    // Add notification form
    print load_fiche_titre($langs->trans("AddNewNotification"),'','');

    print '<form action="'.$_SERVER["PHP_SELF"].'?socid='.$socid.'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    $param="&socid=".$socid;

    // Line with titles
    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Target"),$_SERVER["PHP_SELF"],"c.lastname",'',$param,'"width="45%"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"a.titre",'',$param,'"width="35%"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"",'',$param,'"width="10%"',$sortfield,$sortorder);
    print_liste_field_titre('');
	print "</tr>\n";

    $var=false;
    $listofemails=$object->thirdparty_and_contact_email_array();
    if (count($listofemails) > 0)
    {
        $actions=array();

        // Load array of available notifications
        $notificationtrigger=new InterfaceNotification($db);
        $listofnotifiedevents=$notificationtrigger->getListOfManagedEvents();

        foreach($listofnotifiedevents as $notifiedevent)
        {
 			$label=($langs->trans("Notify_".$notifiedevent['code'])!="Notify_".$notifiedevent['code']?$langs->trans("Notify_".$notifiedevent['code']):$notifiedevent['label']);
            $actions[$notifiedevent['rowid']]=$label;
        }
        print '<tr '.$bc[$var].'><td>';
        print $form->selectarray("contactid",$listofemails);
        print '</td>';
        print '<td>';
        print $form->selectarray("actionid",$actions,'',1);
        print '</td>';
        print '<td>';
        $type=array('email'=>$langs->trans("EMail"));
        print $form->selectarray("typeid",$type);
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

    // List of active notifications
    print load_fiche_titre($langs->trans("ListOfActiveNotifications"),'','');
    $var=true;

    // Line with titles
    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Target"),$_SERVER["PHP_SELF"],"c.lastname",'',$param,'"width="45%"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"a.titre",'',$param,'"width="35%"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"",'',$param,'"width="10%"',$sortfield,$sortorder);
    print_liste_field_titre('','','');
    print '</tr>';

	$langs->load("errors");
	$langs->load("other");

    // List of notifications enabled for contacts
    $sql = "SELECT n.rowid, n.type,";
    $sql.= " a.code, a.label,";
    $sql.= " c.rowid as contactid, c.lastname, c.firstname, c.email";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
    $sql.= " ".MAIN_DB_PREFIX."notify_def as n,";
    $sql.= " ".MAIN_DB_PREFIX."socpeople c";
    $sql.= " WHERE a.rowid = n.fk_action";
    $sql.= " AND c.rowid = n.fk_contact";
    $sql.= " AND c.fk_soc = ".$object->id;

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
            $contactstatic->lastname=$obj->lastname;
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
            print '<td align="right"><a href="card.php?socid='.$socid.'&action=delete&actid='.$obj->rowid.'">'.img_delete().'</a></td>';
            print '</tr>';
            $i++;
        }
        $db->free($resql);
    }
    else
    {
        dol_print_error($db);
    }

    // List of notifications enabled for fixed email
    /*
    foreach($conf->global as $key => $val)
    {
    	if (! preg_match('/^NOTIFICATION_FIXEDEMAIL_(.*)/', $key, $reg)) continue;
    	$var = ! $var;
		print '<tr '.$bc[$var].'><td>';
		$listtmp=explode(',',$val);
		$first=1;
		foreach($listtmp as $keyemail => $valemail)
		{
			if (! $first) print ', ';
			$first=0;
			$valemail=trim($valemail);
    		//print $keyemail.' - '.$valemail.' - '.$reg[1].'<br>';
			if (isValidEmail($valemail, 1))
			{
				if ($valemail == '__SUPERVISOREMAIL__') print $valemail;
				else print ' &lt;'.$valemail.'&gt;';
			}
			else
			{
				print ' '.img_warning().' '.$langs->trans("ErrorBadEMail",$valemail);
			}
		}
		print '</td>';
		print '<td>';
		$notifcode=preg_replace('/_THRESHOLD_.*$/','',$reg[1]);
		$notifcodecond=preg_replace('/^.*_(THRESHOLD_)/','$1',$reg[1]);
		$label=($langs->trans("Notify_".$notifcode)!="Notify_".$notifcode?$langs->trans("Notify_".$notifcode):$notifcode);
		print $label;
		if (preg_match('/^THRESHOLD_HIGHER_(.*)$/',$notifcodecond,$regcond) && ($regcond[1] > 0))
		{
			print ' - '.$langs->trans("IfAmountHigherThan",$regcond[1]);
		}
		print '</td>';
		print '<td>';
		print $langs->trans("Email");
		print '</td>';
		print '<td align="right">'.$langs->trans("SeeModuleSetup", $langs->transnoentitiesnoconv("Module600Name")).'</td>';
		print '</tr>';
    }*/
    if ($user->admin)
    {
	    $var = ! $var;
		print '<tr '.$bc[$var].'><td colspan="4">';
		print '+ <a href="'.DOL_URL_ROOT.'/admin/notification.php">'.$langs->trans("SeeModuleSetup", $langs->transnoentitiesnoconv("Module600Name")).'</a>';
		print '</td></tr>';
    }

    print '</table>';
    print '<br>';


    // List of notifications done
    print load_fiche_titre($langs->trans("ListOfNotificationsDone"),'','');
    $var=true;

    // Line with titles
    print '<table width="100%" class="noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Target"),$_SERVER["PHP_SELF"],"c.lastname",'',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Action"),$_SERVER["PHP_SELF"],"a.titre",'',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"",'',$param,'',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("Object"),$_SERVER["PHP_SELF"],"",'',$param,'"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"a.daten",'',$param,'align="right"',$sortfield,$sortorder);
    print '</tr>';

    // List
    $sql = "SELECT n.rowid, n.daten, n.email, n.objet_type as object_type, n.objet_id as object_id, n.type,";
    $sql.= " c.rowid as id, c.lastname, c.firstname, c.email as contactemail,";
    $sql.= " a.code, a.label";
    $sql.= " FROM ".MAIN_DB_PREFIX."c_action_trigger as a,";
    $sql.= " ".MAIN_DB_PREFIX."notify as n ";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as c ON n.fk_contact = c.rowid";
    $sql.= " WHERE a.rowid = n.fk_action";
    $sql.= " AND n.fk_soc = ".$object->id;

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

            print '<tr '.$bc[$var].'><td>';
            if ($obj->id > 0)
            {
	            $contactstatic->id=$obj->id;
	            $contactstatic->lastname=$obj->lastname;
	            $contactstatic->firstname=$obj->firstname;
	            print $contactstatic->getNomUrl(1);
	            print $obj->email?' &lt;'.$obj->email.'&gt;':$langs->trans("NoMail");
            }
            else
			{
				print $obj->email;
            }
            print '</td>';
            print '<td>';
            $label=($langs->trans("Notify_".$obj->code)!="Notify_".$obj->code?$langs->trans("Notify_".$obj->code):$obj->label);
            print $label;
            print '</td>';
            print '<td>';
            if ($obj->type == 'email') print $langs->trans("Email");
            if ($obj->type == 'sms') print $langs->trans("Sms");
            print '</td>';
            // TODO Add link to object here for other types
            /*print '<td>';
            if ($obj->object_type == 'order')
            {
				$orderstatic->id=$obj->object_id;
				$orderstatic->ref=...
				print $orderstatic->getNomUrl(1);
            }
           	print '</td>';*/
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


llxFooter();

$db->close();
