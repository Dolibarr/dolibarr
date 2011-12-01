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
require_once(DOL_DOCUMENT_ROOT."/core/lib/member.lib.php");

$langs->load("admin");
$langs->load("members");

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST("action");


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
        print $db->error();
    }
}

// Action desactivation d'un sous module du module adherent
if ($action == 'unset')
{
    $result=dolibarr_del_const($db,$_GET["name"],$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}



/*
 * View
 */

$help_url='EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';

llxHeader('',$langs->trans("MembersSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MembersSetup"),$linkback,'setup');


$head = member_admin_prepare_head($adh);

dol_fiche_head($head, 'general', $langs->trans("Member"), 0, 'user');


dol_htmloutput_mesg($mesg);


print_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";
$var=true;
$form = new Form($db);

// Login/Pass required for members
if ($conf->global->MAIN_FEATURES_LEVEL > 0)
{
    $var=!$var;
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="rowid" value="'.$rowid.'">';
    print '<input type="hidden" name="constname" value="ADHERENT_LOGIN_NOT_REQUIRED">';
    print '<tr '.$bc[$var].'><td>'.$langs->trans("AdherentLoginRequired").'</td><td>';
    print $form->selectyesno('constvalue',!$conf->global->ADHERENT_LOGIN_NOT_REQUIRED,1);
    print '</td><td align="center" width="80">';
    print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
    print "</td></tr>\n";
    print '</form>';
}

// Mail required for members
$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="ADHERENT_MAIL_REQUIRED">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("AdherentMailRequired").'</td><td>';
print $form->selectyesno('constvalue',$conf->global->ADHERENT_MAIL_REQUIRED,1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Send mail information is on by default
$var=!$var;
print '<form action="adherent.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="ADHERENT_DEFAULT_SENDINFOBYMAIL">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("MemberSendInformationByMailByDefault").'</td><td>';
print $form->selectyesno('constvalue',$conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL,1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Insertion cotisations dans compte financier
$var=!$var;
print '<form action="adherent.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="ADHERENT_BANK_USE">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("AddSubscriptionIntoAccount").'</td>';
if ($conf->banque->enabled)
{
    print '<td>';
    print $form->selectyesno('constvalue',$conf->global->ADHERENT_BANK_USE,1);
    print '</td><td align="center" width="80">';
    print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
    print '</td>';
}
else
{
    print '<td align="right" colspan="2">';
    print $langs->trans("WarningModuleNotActive",$langs->transnoentities("Module85Name")).' '.img_warning("","");
    print '</td>';
}
print "</tr>\n";
print '</form>';
print '</table>';
print '<br>';



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
    		'ADHERENT_MAILMAN_LISTS',
    		'ADHERENT_MAILMAN_ADMINPW',
    		'ADHERENT_MAILMAN_URL',
    		'ADHERENT_MAILMAN_UNSUB_URL'
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
		    print '<br>';
}
else
{
    $lien='<a href="'.$_SERVER["PHP_SELF"].'?action=set&value=1&name=ADHERENT_USE_MAILMAN">'.$langs->trans("Activate").'</a>';
    print_fiche_titre("Mailman mailing list system",$lien,'');
    print "<hr>\n";
}


/*
 * Spip
 */
$var=!$var;
if ($conf->global->ADHERENT_USE_SPIP)
{
    $lien=img_picto($langs->trans("Active"),'tick').' ';
    $lien.='<a href="'.$_SERVER["PHP_SELF"].'?action=unset&value=0&name=ADHERENT_USE_SPIP">'.$langs->trans("Disable").'</a>';
    // Edition des varibales globales
    $constantes=array(
    		'ADHERENT_USE_SPIP_AUTO',
    		'ADHERENT_SPIP_SERVEUR',
    		'ADHERENT_SPIP_DB',
    		'ADHERENT_SPIP_USER',
    		'ADHERENT_SPIP_PASS'
			);

		    print_fiche_titre("SPIP CMS",$lien,'');
		    form_constantes($constantes);
		    print '<br>';
}
else
{
    $lien='<a href="'.$_SERVER["PHP_SELF"].'?action=set&value=1&name=ADHERENT_USE_SPIP">'.$langs->trans("Activate").'</a>';
    print_fiche_titre("SPIP - CMS",$lien,'');
    print "<hr>\n";
}


/*
 * Edition info modele document
 */
$constantes=array(
		'ADHERENT_CARD_TYPE',
//		'ADHERENT_CARD_BACKGROUND',
		'ADHERENT_CARD_HEADER_TEXT',
		'ADHERENT_CARD_TEXT',
		'ADHERENT_CARD_TEXT_RIGHT',
		'ADHERENT_CARD_FOOTER_TEXT'
		);

print_fiche_titre($langs->trans("MembersCards"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %PRENOM%, %NOM%, %LOGIN%, %PASSWORD%, ';
print '%SOCIETE%, %ADRESSE%, %CP%, %VILLE%, %PAYS%, %EMAIL%, %NAISS%, %PHOTO%, %TYPE%, ';
print '%YEAR%, %MONTH%, %DAY%';
//print '%INFOS%'; Deprecated
print '<br>';

print '<br>';


/*
 * Edition info modele document
 */
$constantes=array('ADHERENT_ETIQUETTE_TYPE');

print_fiche_titre($langs->trans("MembersTickets"),'','');

form_constantes($constantes);

print '<br>';


/*
 * Edition des variables globales non rattache a un theme specifique
 */
$constantes=array(
		'ADHERENT_AUTOREGISTER_MAIL_SUBJECT',
		'ADHERENT_AUTOREGISTER_MAIL',
		'ADHERENT_MAIL_VALID_SUBJECT',
		'ADHERENT_MAIL_VALID',
		'ADHERENT_MAIL_COTIS_SUBJECT',
		'ADHERENT_MAIL_COTIS',
		'ADHERENT_MAIL_RESIL_SUBJECT',
		'ADHERENT_MAIL_RESIL',
		'ADHERENT_MAIL_FROM',
		);

print_fiche_titre($langs->trans("Other"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %PRENOM%, %NOM%, %LOGIN%, %PASSWORD%,';
print '%SOCIETE%, %ADRESSE%, %CP%, %VILLE%, %PAYS%, %EMAIL%, %NAISS%, %PHOTO%, %TYPE%';
//print '%YEAR%, %MONTH%, %DAY%';	// Not supported
//print '%INFOS%'; Deprecated
print '<br>';

dol_fiche_end();

$db->close();

llxFooter();


function form_constantes($tableau)
{
    global $db,$bc,$langs,$conf,$_Avery_Labels;

    $form = new Form($db);

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Description").'</td>';
    print '<td>'.$langs->trans("Value").'*</td>';
    print '<td>&nbsp;</td>';
    print '<td align="center" width="80">'.$langs->trans("Action").'</td>';
    print "</tr>\n";
    $var=true;

    $listofparam=array();
    foreach($tableau as $const)	// Loop on each param
    {
        $sql = "SELECT ";
        $sql.= "rowid";
        $sql.= ", ".$db->decrypt('name')." as name";
        $sql.= ", ".$db->decrypt('value')." as value";
        $sql.= ", type";
        $sql.= ", note";
        $sql.= " FROM ".MAIN_DB_PREFIX."const";
        $sql.= " WHERE ".$db->decrypt('name')." = '".$const."'";
        $sql.= " AND entity in (0, ".$conf->entity.")";
        $sql.= " ORDER BY name ASC, entity DESC";
        $result = $db->query($sql);

        dol_syslog("List params sql=".$sql);
        if ($result)
        {
            $obj = $db->fetch_object($result);	// Take first result of select
            $var=!$var;

            print "\n".'<form action="adherent.php" method="POST">';

            print "<tr ".$bc[$var].">";

            // Affiche nom constante
            print '<td>';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="rowid" value="'.$obj->rowid.'">';
            print '<input type="hidden" name="constname" value="'.$const.'">';
            print '<input type="hidden" name="constnote" value="'.nl2br($obj->note).'">';

            print $langs->trans("Desc".$const) != ("Desc".$const) ? $langs->trans("Desc".$const) : ($obj->note?$obj->note:$const);

            if ($const=='ADHERENT_MAILMAN_URL')
            {
                print '. '.$langs->trans("Example").': <a href="#" id="exampleclick1">'.img_down().'</a><br>';
                //print 'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&subscribees=%EMAIL%&send_welcome_msg_to_this_batch=1';
                print '<div id="example1" class="hidden">';
                print 'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members/add?subscribees_upload=%EMAIL%&adminpw=%MAILMAN_ADMINPW%&subscribe_or_invite=0&send_welcome_msg_to_this_batch=0&notification_to_list_owner=0';
                print '</div>';
            }
            if ($const=='ADHERENT_MAILMAN_UNSUB_URL')
            {
                print '. '.$langs->trans("Example").': <a href="#" id="exampleclick2">'.img_down().'</a><br>';
                print '<div id="example2" class="hidden">';
                print 'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members/remove?unsubscribees_upload=%EMAIL%&adminpw=%MAILMAN_ADMINPW%&send_unsub_ack_to_this_batch=0&send_unsub_notifications_to_list_owner=0';
                print '</div>';
                //print 'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members/remove?adminpw=%MAILMAN_ADMINPW%&unsubscribees=%EMAIL%';
            }


            print "</td>\n";

            if ($const == 'ADHERENT_CARD_TYPE' || $const == 'ADHERENT_ETIQUETTE_TYPE')
            {
                print '<td>';
                // List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
                require_once(DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php');
                $arrayoflabels=array();
                foreach(array_keys($_Avery_Labels) as $codecards)
                {
                    $arrayoflabels[$codecards]=$_Avery_Labels[$codecards]['name'];
                }
                print $form->selectarray('constvalue',$arrayoflabels,($obj->value?$obj->value:'CARD'),1,0,0);
                print '</td><td>';
                print '<input type="hidden" name="consttype" value="yesno">';
                print '</td>';
            }
            else
            {
                print '<td>';
                //print 'aa'.$const;
                if (in_array($const,array('ADHERENT_CARD_TEXT','ADHERENT_CARD_TEXT_RIGHT')))
                {
                    print '<textarea class="flat" name="constvalue" cols="35" rows="5" wrap="soft">'."\n";
                    print $obj->value;
                    print "</textarea>\n";
                    print '</td><td>';
                    print '<input type="hidden" name="consttype" value="texte">';
                }
                else if (in_array($const,array('ADHERENT_AUTOREGISTER_MAIL','ADHERENT_MAIL_VALID','ADHERENT_MAIL_COTIS','ADHERENT_MAIL_RESIL')))
                {
                    require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
                    $doleditor=new DolEditor('constvalue'.$const,$obj->value,'',160,'dolibarr_notes','',false,false,$conf->fckeditor->enabled,5,60);
                    $doleditor->Create();

                    print '</td><td>';
                    print '<input type="hidden" name="consttype" value="texte">';
                }
                else if ($obj->type == 'yesno')
                {
                    print $form->selectyesno('constvalue',$obj->value,1);
                    print '</td><td>';
                    print '<input type="hidden" name="consttype" value="yesno">';
                }
                else
                {
                    print '<input type="text" class="flat" size="48" name="constvalue" value="'.$obj->value.'">';
                    print '</td><td>';
                    print '<input type="hidden" name="consttype" value="chaine">';
                }
                print '</td>';
            }
            print '<td align="center">';
            print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button"> &nbsp;';
            // print '<a href="adherent.php?name='.$const.'&action=unset">'.img_delete().'</a>';
            print "</td>";
            print "</tr>\n";
            print "</form>\n";
        }
    }
    print '</table>';
}

?>