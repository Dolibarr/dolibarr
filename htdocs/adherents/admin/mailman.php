<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011 	   Juanjo Menent		<jmenent@2byte.es>
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
 *   	\file       htdocs/adherents/admin/adherent.php
 *		\ingroup    member
 *		\brief      Page to setup the module Foundation
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/mailmanspip.lib.php");

$langs->load("admin");
$langs->load("members");

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST("action");


/*
 * Actions
 */

// Action mise a jour ou ajout d'une constante
if ($action == 'update' || $action == 'add')
{
	$constname=GETPOST("constname");
	$constvalue=GETPOST("constvalue");

	if (($constname=='ADHERENT_CARD_TYPE' || $constname=='ADHERENT_ETIQUETTE_TYPE') && $constvalue == -1) $constvalue='';
	if ($constname=='ADHERENT_LOGIN_NOT_REQUIRED') // Invert choice
	{
		if ($constvalue) $constvalue=0;
		else $constvalue=1;
	}

	if (in_array($constname,array('ADHERENT_MAIL_VALID','ADHERENT_MAIL_COTIS','ADHERENT_MAIL_RESIL'))) $constvalue=$_POST["constvalue".$constname];
	$consttype=$_POST["consttype"];
	$constnote=GETPOST("constnote");
	$res=dolibarr_set_const($db,$constname,$constvalue,$type[$consttype],0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

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
    $result=dolibarr_set_const($db, $_GET["name"],$_GET["value"],'',0,'',$conf->entity);
    if ($result < 0)
    {
        dol_print_error($db);
    }
}

// Action desactivation d'un sous module du module adherent
if ($action == 'unset')
{
    $result=dolibarr_del_const($db,$_GET["name"],$conf->entity);
    if ($result < 0)
    {
        dol_print_error($db);
    }
}



/*
 * View
 */

$help_url='';

llxHeader('',$langs->trans("MailmanSpipSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MailmanSpipSetup"),$linkback,'setup');


$head = mailmanspip_admin_prepare_head($adh);

dol_fiche_head($head, 'mailman', $langs->trans("Setup"), 0, 'user');


dol_htmloutput_mesg($mesg);


/*
 * Mailman
 */
$var=!$var;
if ($conf->global->ADHERENT_USE_MAILMAN)
{
    $lien=img_picto($langs->trans("Active"),'tick').' ';
    $lien.='<a href="'.$_SERVER["PHP_SELF"].'?action=unset&value=0&name=ADHERENT_USE_MAILMAN">'.$langs->trans("Disable").'</a>';
    // Edition des varibales globales
    $constantes=array(
        'ADHERENT_MAILMAN_ADMINPW',
        'ADHERENT_MAILMAN_URL',
        'ADHERENT_MAILMAN_UNSUB_URL',
        'ADHERENT_MAILMAN_LISTS'
    );

    print_fiche_titre("Mailman mailing list system",$lien,'');

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

    form_constantes($constantes);

    print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
    print '%LISTE%, %MAILMAN_ADMINPW%, %EMAIL% <br>';

    print '<br>';
}
else
{
    $lien='<a href="'.$_SERVER["PHP_SELF"].'?action=set&value=1&name=ADHERENT_USE_MAILMAN">'.$langs->trans("Activate").'</a>';
    print_fiche_titre("Mailman mailing list system",$lien,'');
    print "<hr>\n";
}

dol_fiche_end();

llxFooter();

$db->close();
?>