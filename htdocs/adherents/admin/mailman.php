<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011 	   Juanjo Menent		<jmenent@2byte.es>
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
 *   	\file       htdocs/adherents/admin/mailman.php
 *		\ingroup    mailmanspip
 *		\brief      Page to setup the module MailmanSpip (Mailman)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/mailmanspip.lib.php';

$langs->load("admin");
$langs->load("members");
$langs->load("mailmanspip");

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST("action");
$testsubscribeemail = GETPOST("testsubscribeemail");
$testunsubscribeemail = GETPOST("testunsubscribeemail");

/*
 * Actions
 */

// Action mise a jour ou ajout d'une constante
if ($action == 'update' || $action == 'add')
{
	foreach($_POST['constname'] as $key => $val)
	{
		$constname=$_POST["constname"][$key];
		$constvalue=$_POST["constvalue"][$key];
		$consttype=$_POST["consttype"][$key];
		$constnote=$_POST["constnote"][$key];
		$res=dolibarr_set_const($db,$constname,$constvalue,$type[$consttype],0,$constnote,$conf->entity);
	
		if (! $res > 0) $error++;
	}
	
	if (! $error)
	{
		$mesg = '<div class="ok">'.$langs->trans("SetupSaved").'</div>';
	}
	else
	{
		$mesg = '<div class="error">'.$langs->trans("Error").'</div>';
	}
}

// Action activation d'un sous module du module adherent
if ($action == 'set')
{
    $result=dolibarr_set_const($db, $_GET["name"], $_GET["value"], '', 0, '', $conf->entity);
    if ($result < 0)
    {
        dol_print_error($db);
    }
}

// Action desactivation d'un sous module du module adherent
if ($action == 'unset')
{
    $result=dolibarr_del_const($db, $_GET["name"], $conf->entity);
    if ($result < 0)
    {
        dol_print_error($db);
    }
}

if (($action == 'testsubscribe' || $action == 'testunsubscribe') && ! empty($conf->global->ADHERENT_USE_MAILMAN))
{
    $email=GETPOST($action.'email');
    if (! isValidEmail($email))
    {
        $langs->load("errors");
        $mesg='<div class="error">'.$langs->trans("ErrorBadEMail",$email).'</div>';
    }
    else
    {
        include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';
        $mailmanspip=new MailmanSpip($db);

        $object=new stdClass();
        $object->email=$email;
        $object->pass=$email;
        /*$object->element='member';
        $object->type='Preferred Partners'; */

        if ($action == 'testsubscribe')
        {
            $result=$mailmanspip->add_to_mailman($object);
            if ($result < 0)
            {
                $error++;
                $mesg='<div class="error">'.$mailmanspip->error.'</div>';
            }
            else
            {
                $mesg='MailmanCreationSuccess';
            }
        }
        if ($action == 'testunsubscribe')
        {
            $result=$mailmanspip->del_to_mailman($object);
            if ($result < 0)
            {
                $error++;
                $mesg='<div class="error">'.$mailmanspip->error.'</div>';
            }
            else
            {
                $mesg='MailmanDeletionSuccess';
            }
        }
    }
}


/*
 * View
 */

$help_url='';

llxHeader('',$langs->trans("MailmanSpipSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MailmanSpipSetup"),$linkback,'setup');


$head = mailmanspip_admin_prepare_head();

dol_fiche_head($head, 'mailman', $langs->trans("Setup"), 0, 'user');


dol_htmloutput_mesg($mesg);


$var=!$var;
if (! empty($conf->global->ADHERENT_USE_MAILMAN))
{
    //$lien=img_picto($langs->trans("Active"),'tick').' ';
    $lien='<a href="'.$_SERVER["PHP_SELF"].'?action=unset&value=0&name=ADHERENT_USE_MAILMAN">';
    //$lien.=$langs->trans("Disable");
    $lien.=img_picto($langs->trans("Activated"),'switch_on');
    $lien.='</a>';
    // Edition des varibales globales
    $constantes=array(
        'ADHERENT_MAILMAN_ADMINPW',
        'ADHERENT_MAILMAN_URL',
        'ADHERENT_MAILMAN_UNSUB_URL',
        'ADHERENT_MAILMAN_LISTS'
    );

    print_fiche_titre($langs->trans('MailmanTitle'), $lien,'');

    print '<br>';
    
    // JQuery activity
    print '<script type="text/javascript">
    var i1=0;
    var i2=0;
    jQuery(document).ready(function(){
        jQuery("#exampleclick1").click(function(event){
            if (i1 == 0) { jQuery("#example1").show(); i1=1; }
            else if (i1 == 1)  { jQuery("#example1").hide(); i1=0; }
            });
        jQuery("#exampleclick2").click(function(){
            if (i2 == 0) { jQuery("#example2").show(); i2=1; }
            else if (i2 == 1)  { jQuery("#example2").hide(); i2=0; }
            });
    });
    </script>';

    form_constantes($constantes,1);

    print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
    print '%LISTE%, %MAILMAN_ADMINPW%, %EMAIL% <br>';
}
else
{
    $lien='<a href="'.$_SERVER["PHP_SELF"].'?action=set&value=1&name=ADHERENT_USE_MAILMAN">';
    //$lien.=img_$langs->trans("Activate")
    $lien.=img_picto($langs->trans("Disabled"),'switch_off');
    $lien.='</a>';
    print_fiche_titre($langs->trans('MailmanTitle'), $lien,'');
}

dol_fiche_end();

if (! empty($conf->global->ADHERENT_USE_MAILMAN))
{
    print '<form action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="testsubscribe">';
    print $langs->trans("TestSubscribe").'<br>';
    print $langs->trans("EMail").' <input type="email" class="flat" name="testsubscribeemail" value="'.GETPOST('testsubscribeemail').'"> <input class="button" type="submit" value="'.$langs->trans("Test").'"><br>';
    print '</form>';
    print '<form action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="testunsubscribe">';
    print $langs->trans("TestUnSubscribe").'<br>';
    print $langs->trans("EMail").' <input type="email" class="flat" name="testunsubscribeemail" value="'.GETPOST('testunsubscribeemail').'"> <input class="button" type="submit" value="'.$langs->trans("Test").'"><br>';
    print '</form>';
}


llxFooter();

$db->close();
?>