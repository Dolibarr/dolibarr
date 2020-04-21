<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Sebastien Di Cintio	<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier		<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2006-2020	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2013	Juanjo Menent		<jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/admin/ldap.php
 *      \ingroup    ldap
 *      \brief      Page to setup module LDAP
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

// Load translation files required by the page
$langs->load("admin");

if (!$user->admin)
	accessforbidden();

$action = GETPOST('action', 'aZ09');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('adminldap', 'globaladmin'));

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'setvalue' && $user->admin)
	{
		$error = 0;

		$db->begin();

		if (!dolibarr_set_const($db, 'LDAP_SERVER_TYPE', GETPOST("type", 'aZ09'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_SERVER_PROTOCOLVERSION', GETPOST("LDAP_SERVER_PROTOCOLVERSION", 'aZ09'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_SERVER_HOST', GETPOST("host", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_SERVER_HOST_SLAVE', GETPOST("slave", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_SERVER_PORT', GETPOST("port", 'int'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_SERVER_DN', GETPOST("dn", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_ADMIN_DN', GETPOST("admin", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_ADMIN_PASS', GETPOST("pass", 'none'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_SERVER_USE_TLS', GETPOST("usetls", 'aZ09'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_SYNCHRO_ACTIVE', GETPOST("activesynchro", 'aZ09'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_CONTACT_ACTIVE', GETPOST("activecontact", 'aZ09'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_MEMBER_ACTIVE', GETPOST("activemembers", 'aZ09'), 'chaine', 0, '', $conf->entity)) $error++;
		if (!dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_ACTIVE', GETPOST("activememberstypes", 'aZ09'), 'chaine', 0, '', $conf->entity)) $error++;

		if (!$error)
		{
			$db->commit();
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		}
		else
		{
			$db->rollback();
			dol_print_error($db);
		}
	}
}

/*
 * View
 */

llxHeader('', $langs->trans("LDAPSetup"), 'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("LDAPSetup"), $linkback, 'title_setup');

$head = ldap_prepare_head();

// Test si fonction LDAP actives
if (!function_exists("ldap_connect"))
{
	setEventMessages($langs->trans("LDAPFunctionsNotAvailableOnPHP"), null, 'errors');
}


$form = new Form($db);


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';
print '<input type="hidden" name="token" value="'.newToken().'">';

dol_fiche_head($head, 'ldap', $langs->trans("LDAPSetup"), -1);

print '<table class="noborder centpercent">';

// Liste de synchro actives
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("LDAPSynchronization").'</td>';
print "</tr>\n";

// Synchro utilisateurs/groupes active

print '<tr class="oddeven"><td>'.$langs->trans("LDAPDnSynchroActive").'</td><td>';
$arraylist = array();
$arraylist['0'] = $langs->trans("No");
$arraylist['ldap2dolibarr'] = $langs->trans("LDAPToDolibarr");
$arraylist['dolibarr2ldap'] = $langs->trans("DolibarrToLDAP");
print $form->selectarray('activesynchro', $arraylist, $conf->global->LDAP_SYNCHRO_ACTIVE);
print '</td><td>'.$langs->trans("LDAPDnSynchroActiveExample");
if ($conf->global->LDAP_SYNCHRO_ACTIVE && !$conf->global->LDAP_USER_DN)
{
	print '<br><font class="error">'.$langs->trans("LDAPSetupNotComplete").'</font>';
}
print '</td></tr>';

// Synchro contact active
if (!empty($conf->societe->enabled))
{
	print '<tr class="oddeven"><td>'.$langs->trans("LDAPDnContactActive").'</td><td>';
	$arraylist = array();
	$arraylist['0'] = $langs->trans("No");
	$arraylist['1'] = $langs->trans("DolibarrToLDAP");
	print $form->selectarray('activecontact', $arraylist, $conf->global->LDAP_CONTACT_ACTIVE);
	print '</td><td>'.$langs->trans("LDAPDnContactActiveExample").'</td></tr>';
}

// Synchro member active
if (!empty($conf->adherent->enabled))
{
	print '<tr class="oddeven"><td>'.$langs->trans("LDAPDnMemberActive").'</td><td>';
	$arraylist = array();
	$arraylist['0'] = $langs->trans("No");
	$arraylist['1'] = $langs->trans("DolibarrToLDAP");
	$arraylist['ldap2dolibarr'] = $langs->trans("LDAPToDolibarr").' ('.$langs->trans("SupportedForLDAPImportScriptOnly").')';
	print $form->selectarray('activemembers', $arraylist, $conf->global->LDAP_MEMBER_ACTIVE);
	print '</td><td>'.$langs->trans("LDAPDnMemberActiveExample").'</td></tr>';
}

// Synchro member type active
if (!empty($conf->adherent->enabled))
{
	print '<tr class="oddeven"><td>'.$langs->trans("LDAPDnMemberTypeActive").'</td><td>';
	$arraylist = array();
	$arraylist['0'] = $langs->trans("No");
	$arraylist['1'] = $langs->trans("DolibarrToLDAP");
	$arraylist['ldap2dolibarr'] = $langs->trans("LDAPToDolibarr").' ('.$langs->trans("SupportedForLDAPImportScriptOnly").')';
	print $form->selectarray('activememberstypes', $arraylist, $conf->global->LDAP_MEMBER_TYPE_ACTIVE);
	print '</td><td>'.$langs->trans("LDAPDnMemberTypeActiveExample").'</td></tr>';
}

// Fields from hook
$parameters = array();
$reshook = $hookmanager->executeHooks('addAdminLdapOptions', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print "</tr>\n";

// Type
print '<tr class="oddeven"><td>'.$langs->trans("Type").'</td><td>';
$arraylist = array();
$arraylist['activedirectory'] = 'Active Directory';
$arraylist['openldap'] = 'OpenLdap';
$arraylist['egroupware'] = 'Egroupware';
print $form->selectarray('type', $arraylist, $conf->global->LDAP_SERVER_TYPE);
print '</td><td>&nbsp;</td></tr>';

// Version
print '<tr class="oddeven"><td>'.$langs->trans("Version").'</td><td>';
$arraylist = array();
$arraylist['3'] = 'Version 3';
$arraylist['2'] = 'Version 2';
print $form->selectarray('LDAP_SERVER_PROTOCOLVERSION', $arraylist, $conf->global->LDAP_SERVER_PROTOCOLVERSION);
print '</td><td>'.$langs->trans("LDAPServerProtocolVersion").'</td></tr>';

// Serveur primaire
print '<tr class="oddeven"><td>';
print $langs->trans("LDAPPrimaryServer").'</td><td>';
print '<input size="25" type="text" name="host" value="'.$conf->global->LDAP_SERVER_HOST.'">';
print '</td><td>'.$langs->trans("LDAPServerExample").'</td></tr>';

// Serveur secondaire
print '<tr class="oddeven"><td>';
print $langs->trans("LDAPSecondaryServer").'</td><td>';
print '<input size="25" type="text" name="slave" value="'.$conf->global->LDAP_SERVER_HOST_SLAVE.'">';
print '</td><td>'.$langs->trans("LDAPServerExample").'</td></tr>';

// Port
print '<tr class="oddeven"><td>'.$langs->trans("LDAPServerPort").'</td><td>';
if (!empty($conf->global->LDAP_SERVER_PORT))
{
	print '<input size="25" type="text" name="port" value="'.$conf->global->LDAP_SERVER_PORT.'">';
}
else
{
	print '<input size="25" type="text" name="port" value="389">';
}
print '</td><td>'.$langs->trans("LDAPServerPortExample").'</td></tr>';

// DNserver
print '<tr class="oddeven"><td>'.$langs->trans("LDAPServerDn").'</td><td>';
print '<input size="25" type="text" name="dn" value="'.$conf->global->LDAP_SERVER_DN.'">';
print '</td><td>'.$langs->trans("LDAPServerDnExample").'</td></tr>';

// Utiliser TLS
print '<tr class="oddeven"><td>'.$langs->trans("LDAPServerUseTLS").'</td><td>';
$arraylist = array();
$arraylist['0'] = $langs->trans("No");
$arraylist['1'] = $langs->trans("Yes");
print $form->selectarray('usetls', $arraylist, $conf->global->LDAP_SERVER_USE_TLS);
print '</td><td>'.$langs->trans("LDAPServerUseTLSExample").'</td></tr>';

print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("ForANonAnonymousAccess").'</td>';
print "</tr>\n";

// DNAdmin
print '<tr class="oddeven"><td>'.$langs->trans("LDAPAdminDn").'</td><td>';
print '<input size="25" type="text" name="admin" value="'.$conf->global->LDAP_ADMIN_DN.'">';
print '</td><td>'.$langs->trans("LDAPAdminDnExample").'</td></tr>';

// Pass
print '<tr class="oddeven"><td>'.$langs->trans("LDAPPassword").'</td><td>';
if (!empty($conf->global->LDAP_ADMIN_PASS))
{
	print '<input size="25" type="password" name="pass" value="'.$conf->global->LDAP_ADMIN_PASS.'">'; // je le met en visible pour test
}
else
{
	print '<input size="25" type="text" name="pass" value="'.$conf->global->LDAP_ADMIN_PASS.'">';
}
print '</td><td>'.$langs->trans('Password').' (ex: secret)</td></tr>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br>';


/*
 * Test de la connexion
 */
if (function_exists("ldap_connect"))
{
	if (!empty($conf->global->LDAP_SERVER_HOST))
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test">'.$langs->trans("LDAPTestConnect").'</a><br><br>';
	}

	if ($_GET["action"] == 'test')
	{
		$ldap = new Ldap(); // Les parametres sont passes et recuperes via $conf

		$result = $ldap->connect_bind();
		if ($result > 0)
		{
			// Test ldap connect and bind
			print img_picto('', 'info').' ';
			print '<font class="ok">'.$langs->trans("LDAPTCPConnectOK", $conf->global->LDAP_SERVER_HOST, $conf->global->LDAP_SERVER_PORT).'</font>';
			print '<br>';

			if ($conf->global->LDAP_ADMIN_DN && !empty($conf->global->LDAP_ADMIN_PASS))
			{
				if ($result == 2)
				{
					print img_picto('', 'info').' ';
					print '<font class="ok">'.$langs->trans("LDAPBindOK", $conf->global->LDAP_SERVER_HOST, $conf->global->LDAP_SERVER_PORT, $conf->global->LDAP_ADMIN_DN, preg_replace('/./i', '*', $conf->global->LDAP_ADMIN_PASS)).'</font>';
					print '<br>';
				}
				else
				{
					print img_picto('', 'error').' ';
					print '<font class="error">'.$langs->trans("LDAPBindKO", $conf->global->LDAP_SERVER_HOST, $conf->global->LDAP_SERVER_PORT, $conf->global->LDAP_ADMIN_DN, preg_replace('/./i', '*', $conf->global->LDAP_ADMIN_PASS)).'</font>';
					print '<br>';
					print $langs->trans("Error").' '.$ldap->error;
					print '<br>';
				}
			}
			else
			{
				print img_picto('', 'warning').' ';
				print '<font class="warning">'.$langs->trans("LDAPNoUserOrPasswordProvidedAccessIsReadOnly").'</font>';
				print '<br>';
			}


			// Test ldap_getversion
			if (($ldap->getVersion() == 3))
			{
				print img_picto('', 'info').' ';
				print '<font class="ok">'.$langs->trans("LDAPSetupForVersion3").'</font>';
				print '<br>';
			}
			else
			{
				print img_picto('', 'info').' ';
				print '<font class="ok">'.$langs->trans("LDAPSetupForVersion2").'</font>';
				print '<br>';
			}

			$unbind = $ldap->unbind();
		}
		else
		{
			print img_picto('', 'error').' ';
			print '<font class="error">'.$langs->trans("LDAPTCPConnectKO", $conf->global->LDAP_SERVER_HOST, $conf->global->LDAP_SERVER_PORT).'</font>';
			print '<br>';
			print $langs->trans("Error").' '.$ldap->error;
			print '<br>';
		}
	}
}

// End of page
llxFooter();
$db->close();
