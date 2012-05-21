<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/admin/ldap.php
 *      \ingroup    ldap
 *      \brief      Page d'administration/configuration du module Ldap
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/ldap.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

  $action = GETPOST("action");

/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin)
{
	$error=0;

	$db->begin();
	if (! dolibarr_set_const($db, 'LDAP_SERVER_TYPE',GETPOST("type"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_SERVER_PROTOCOLVERSION',GETPOST("LDAP_SERVER_PROTOCOLVERSION"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_SERVER_HOST',GETPOST("host"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_SERVER_HOST_SLAVE',GETPOST("slave"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_SERVER_PORT',GETPOST("port"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_SERVER_DN',GETPOST("dn"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_ADMIN_DN',GETPOST("admin"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_ADMIN_PASS',GETPOST("pass"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_SERVER_USE_TLS',GETPOST("usetls"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_SYNCHRO_ACTIVE',GETPOST("activesynchro"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_CONTACT_ACTIVE',GETPOST("activecontact"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_ACTIVE',GETPOST("activemembers"),'chaine',0,'',$conf->entity)) $error++;

	if (! $error)
  	{
  		$db->commit();
  		$mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
  	}
  	else
  	{
  		$db->rollback();
		dol_print_error($db);
    }
}



/*
 * View
 */

llxHeader('',$langs->trans("LDAPSetup"),'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

print_fiche_titre($langs->trans("LDAPSetup"),$linkback,'setup');

$head = ldap_prepare_head();

// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	$mesg.='<div class="error">'.$langs->trans("LDAPFunctionsNotAvailableOnPHP").'</div>';  ;
}

dol_fiche_head($head, 'ldap', $langs->trans("LDAPSetup"));

$var=true;
$form=new Form($db);


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="noborder" width="100%">';

// Liste de synchro actives
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("LDAPSynchronization").'</td>';
print "</tr>\n";

// Synchro utilisateurs/groupes active
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPDnSynchroActive").'</td><td>';
$arraylist=array();
$arraylist['0']=$langs->trans("No");
$arraylist['ldap2dolibarr']=$langs->trans("LDAPToDolibarr");
$arraylist['dolibarr2ldap']=$langs->trans("DolibarrToLDAP");
print $form->selectarray('activesynchro',$arraylist,$conf->global->LDAP_SYNCHRO_ACTIVE);
print '</td><td>'.$langs->trans("LDAPDnSynchroActiveExample");
if ($conf->global->LDAP_SYNCHRO_ACTIVE && ! $conf->global->LDAP_USER_DN)
{
	print '<br><font class="error">'.$langs->trans("LDAPSetupNotComplete").'</font>';
}
print '</td></tr>';

// Synchro contact active
if ($conf->societe->enabled)
{
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPDnContactActive").'</td><td>';
	$arraylist=array();
	$arraylist['0']=$langs->trans("No");
	$arraylist['1']=$langs->trans("DolibarrToLDAP");
	print $form->selectarray('activecontact',$arraylist,$conf->global->LDAP_CONTACT_ACTIVE);
	print '</td><td>'.$langs->trans("LDAPDnContactActiveExample").'</td></tr>';
}

// Synchro adherentt active
if ($conf->adherent->enabled)
{
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPDnMemberActive").'</td><td>';
	$arraylist=array();
	$arraylist['0']=$langs->trans("No");
	$arraylist['1']=$langs->trans("DolibarrToLDAP");
	print $form->selectarray('activemembers',$arraylist,$conf->global->LDAP_MEMBER_ACTIVE);
	print '</td><td>'.$langs->trans("LDAPDnMemberActiveExample").'</td></tr>';
}

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print "</tr>\n";

// Type
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("Type").'</td><td>';
$arraylist=array();
$arraylist['activedirectory']='Active Directory';
$arraylist['openldap']='OpenLdap';
$arraylist['egroupware']='Egroupware';
print $form->selectarray('type',$arraylist,$conf->global->LDAP_SERVER_TYPE);
print '</td><td>&nbsp;</td></tr>';

// Version
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("Version").'</td><td>';
$arraylist=array();
$arraylist['3']='Version 3';
$arraylist['2']='Version 2';
print $form->selectarray('LDAP_SERVER_PROTOCOLVERSION',$arraylist,$conf->global->LDAP_SERVER_PROTOCOLVERSION);
print '</td><td>'.$langs->trans("LDAPServerProtocolVersion").'</td></tr>';

// Serveur primaire
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("LDAPPrimaryServer").'</td><td>';
print '<input size="25" type="text" name="host" value="'.$conf->global->LDAP_SERVER_HOST.'">';
print '</td><td>'.$langs->trans("LDAPServerExample").'</td></tr>';

// Serveur secondaire
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("LDAPSecondaryServer").'</td><td>';
print '<input size="25" type="text" name="slave" value="'.$conf->global->LDAP_SERVER_HOST_SLAVE.'">';
print '</td><td>'.$langs->trans("LDAPServerExample").'</td></tr>';

// Port
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPServerPort").'</td><td>';
if ($conf->global->LDAP_SERVER_PORT)
{
  print '<input size="25" type="text" name="port" value="'.$conf->global->LDAP_SERVER_PORT.'">';
}
else
{
  print '<input size="25" type="text" name="port" value="389">';
}
print '</td><td>'.$langs->trans("LDAPServerPortExample").'</td></tr>';

// DNserver
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPServerDn").'</td><td>';
print '<input size="25" type="text" name="dn" value="'.$conf->global->LDAP_SERVER_DN.'">';
print '</td><td>'.$langs->trans("LDAPServerDnExample").'</td></tr>';

// Utiliser TLS
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPServerUseTLS").'</td><td>';
$arraylist=array();
$arraylist['0']=$langs->trans("No");
$arraylist['1']=$langs->trans("Yes");
print $form->selectarray('usetls',$arraylist,$conf->global->LDAP_SERVER_USE_TLS);
print '</td><td>'.$langs->trans("LDAPServerUseTLSExample").'</td></tr>';

print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("ForANonAnonymousAccess").'</td>';
print "</tr>\n";

// DNAdmin
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPAdminDn").'</td><td>';
print '<input size="25" type="text" name="admin" value="'.$conf->global->LDAP_ADMIN_DN.'">';
print '</td><td>'.$langs->trans("LDAPAdminDnExample").'</td></tr>';

// Pass
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPPassword").'</td><td>';
if ($conf->global->LDAP_ADMIN_PASS)
{
	print '<input size="25" type="password" name="pass" value="'.$conf->global->LDAP_ADMIN_PASS.'">';// je le met en visible pour test
}
else
{
	print '<input size="25" type="text" name="pass" value="'.$conf->global->LDAP_ADMIN_PASS.'">';
}
print '</td><td>secret</td></tr>';

print '</table>';

print '<center><input type="submit" class="button" value="'.$langs->trans("Modify").'"></center>';

print '</form>';

print '</div>';



/*
 * Test de la connexion
 */
if (function_exists("ldap_connect"))
{
	if ($conf->global->LDAP_SERVER_HOST)
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test">'.$langs->trans("LDAPTestConnect").'</a><br><br>';
	}

	if ($_GET["action"] == 'test')
	{
		$ldap = new Ldap();	// Les parametres sont passes et recuperes via $conf

		$result = $ldap->connect_bind();
		if ($result)
		{
			// Test ldap connect and bind
			print img_picto('','info').' ';
			print '<font class="ok">'.$langs->trans("LDAPTCPConnectOK",$conf->global->LDAP_SERVER_HOST,$conf->global->LDAP_SERVER_PORT).'</font>';
			print '<br>';

			if ($conf->global->LDAP_ADMIN_DN && $conf->global->LDAP_ADMIN_PASS)
			{
				if ($result == 2)
				{
					print img_picto('','info').' ';
					print '<font class="ok">'.$langs->trans("LDAPBindOK",$conf->global->LDAP_SERVER_HOST,$conf->global->LDAP_SERVER_PORT,$conf->global->LDAP_ADMIN_DN,preg_replace('/./i','*',$conf->global->LDAP_ADMIN_PASS)).'</font>';
					print '<br>';
				}
				else
				{
					print img_picto('','error').' ';
					print '<font class="error">'.$langs->trans("LDAPBindKO",$conf->global->LDAP_SERVER_HOST,$conf->global->LDAP_SERVER_PORT,$conf->global->LDAP_ADMIN_DN,preg_replace('/./i','*',$conf->global->LDAP_ADMIN_PASS)).'</font>';
					print '<br>';
					print $langs->trans("Error").' '.$ldap->error;
					print '<br>';
				}
			}
			else
			{
				print img_picto('','warning').' ';
				print '<font class="warning">'.$langs->trans("LDAPNoUserOrPasswordProvidedAccessIsReadOnly").'</font>';
				print '<br>';
			}


			// Test ldap_getversion
			if (($ldap->getVersion() == 3))
			{
				print img_picto('','info').' ';
				print '<font class="ok">'.$langs->trans("LDAPSetupForVersion3").'</font>';
				print '<br>';
			}
			else
			{
				print img_picto('','info').' ';
				print '<font class="ok">'.$langs->trans("LDAPSetupForVersion2").'</font>';
				print '<br>';
			}

			$unbind = $ldap->unbind();
		}
		else
		{
			print img_picto('','error').' ';
			print '<font class="error">'.$langs->trans("LDAPTCPConnectKO",$conf->global->LDAP_SERVER_HOST,$conf->global->LDAP_SERVER_PORT).'</font>';
			print '<br>';
			print $langs->trans("Error").' '.$ldap->error;
			print '<br>';
		}

	}
}

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
