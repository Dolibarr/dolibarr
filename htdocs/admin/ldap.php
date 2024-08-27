<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2021  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2013  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "ldap"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('adminldap', 'globaladmin'));


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'setvalue' && $user->admin) {
		$error = 0;

		$db->begin();

		if (!dolibarr_set_const($db, 'LDAP_SERVER_TYPE', GETPOST("type", 'aZ09'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_USERACCOUNTCONTROL', GETPOSTINT("userAccountControl"), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_SERVER_PROTOCOLVERSION', GETPOST("LDAP_SERVER_PROTOCOLVERSION", 'aZ09'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_SERVER_HOST', GETPOST("host", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_SERVER_HOST_SLAVE', GETPOST("slave", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_SERVER_PORT', GETPOSTINT("port"), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_SERVER_DN', GETPOST("dn", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_ADMIN_DN', GETPOST("admin", 'alphanohtml'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_ADMIN_PASS', GETPOST("pass", 'none'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_SERVER_USE_TLS', GETPOST("usetls", 'aZ09'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_SYNCHRO_ACTIVE', GETPOST("activesynchro", 'aZ09'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_CONTACT_ACTIVE', GETPOST("activecontact", 'aZ09'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_MEMBER_ACTIVE', GETPOST("activemembers", 'aZ09'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_ACTIVE', GETPOST("activememberstypes", 'aZ09'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
		if (!dolibarr_set_const($db, 'LDAP_PASSWORD_HASH_TYPE', GETPOST("LDAP_PASSWORD_HASH_TYPE", 'aZ09'), 'chaine', 0, '', $conf->entity)) {
			$error++;
		}

		if (!$error) {
			$db->commit();
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			$db->rollback();
			dol_print_error($db);
		}
	}
}

/*
 * View
 */

llxHeader('', $langs->trans("LDAPSetup"), 'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP', '', 0, 0, '', '', '', 'mod-admin page-ldap');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("LDAPSetup"), $linkback, 'title_setup');

$head = ldap_prepare_head();

// Test if the LDAP functionality is available
if (!function_exists("ldap_connect")) {
	setEventMessages($langs->trans("LDAPFunctionsNotAvailableOnPHP"), null, 'errors');
}


$form = new Form($db);
$formldap = new FormLdap($db);

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue&token='.newToken().'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'ldap', '', -1);

print '<table class="noborder centpercent">';

// List of active synchronisations
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("LDAPSynchronization").'</td>';
print "</tr>\n";

// Synchronise active users and groups

print '<tr class="oddeven"><td>'.$langs->trans("LDAPDnSynchroActive").'</td><td>';
print $formldap->selectLdapDnSynchroActive(getDolGlobalInt('LDAP_SYNCHRO_ACTIVE'), 'activesynchro');
print '</td><td><span class="opacitymedium">'.$langs->trans("LDAPDnSynchroActiveExample").'</span>';
if (getDolGlobalString('LDAP_SYNCHRO_ACTIVE') && !getDolGlobalString('LDAP_USER_DN')) {
	print '<br><span class="error">'.$langs->trans("LDAPSetupNotComplete").'</span>';
}
print '</td></tr>';

// Synchro contact active
if (isModEnabled('societe')) {
	print '<tr class="oddeven"><td>'.$langs->trans("LDAPDnContactActive").'</td><td>';
	print $formldap->selectLdapDnSynchroActive(getDolGlobalInt('LDAP_CONTACT_ACTIVE'), 'activecontact', array(Ldap::SYNCHRO_LDAP_TO_DOLIBARR));
	print '</td><td><span class="opacitymedium">' . $langs->trans("LDAPDnContactActiveExample") . '</span></td></tr>';
}

// Synchro member active
if (isModEnabled('member')) {
	print '<tr class="oddeven"><td>' . $langs->trans("LDAPDnMemberActive") . '</td><td>';
	print $formldap->selectLdapDnSynchroActive(getDolGlobalInt('LDAP_MEMBER_ACTIVE'), 'activemembers', array(), 2);
	print '</td><td><span class="opacitymedium">' . $langs->trans("LDAPDnMemberActiveExample") . '</span></td></tr>';
}

// Synchro member type active
if (isModEnabled('member')) {
	print '<tr class="oddeven"><td>' . $langs->trans("LDAPDnMemberTypeActive") . '</td><td>';
	print $formldap->selectLdapDnSynchroActive(getDolGlobalInt('LDAP_MEMBER_TYPE_ACTIVE'), 'activememberstypes', array(), 2);
	print '</td><td><span class="opacitymedium">' . $langs->trans("LDAPDnMemberTypeActiveExample") . '</span></td></tr>';
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
print $formldap->selectLdapServerType(getDolGlobalString('LDAP_SERVER_TYPE'), 'type');
print '</td><td>&nbsp;</td></tr>';

// userAccountControl
print '<tr class="oddeven"><td>'.$langs->trans("LDAPUserAccountControl").'</td><td>';
print '<input class="width75" type="text" name="userAccountControl" value="'.getDolGlobalString('LDAP_USERACCOUNTCONTROL', '512').'">';
print '</td><td><span class="opacitymedium">'.$langs->trans("LDAPUserAccountControlExample").'</span></td></tr>';

// Version
print '<tr class="oddeven"><td>'.$langs->trans("Version").'</td><td>';
print $formldap->selectLdapServerProtocolVersion(getDolGlobalString('LDAP_SERVER_PROTOCOLVERSION'), 'LDAP_SERVER_PROTOCOLVERSION');
print '</td><td><span class="opacitymedium">'.$langs->trans("LDAPServerProtocolVersion").'</span></td></tr>';

// Serveur primaire
print '<tr class="oddeven"><td>';
print $langs->trans("LDAPPrimaryServer").'</td><td>';
print '<input class="minwidth200" type="text" name="host" value="'.getDolGlobalString('LDAP_SERVER_HOST').'">';
print '</td><td><span class="opacitymedium">'.$langs->trans("LDAPServerExample").'</span></td></tr>';

// Serveur secondaire
print '<tr class="oddeven"><td>';
print $langs->trans("LDAPSecondaryServer").'</td><td>';
print '<input class="minwidth200" type="text" name="slave" value="'.getDolGlobalString('LDAP_SERVER_HOST_SLAVE').'">';
print '</td><td><span class="opacitymedium">'.$langs->trans("LDAPServerExample").'</span></td></tr>';

// Port
print '<tr class="oddeven"><td>'.$langs->trans("LDAPServerPort").'</td><td>';
print '<input class="width75" type="text" name="port" value="'.getDolGlobalString('LDAP_SERVER_PORT', '389').'">';
print '</td><td><span class="opacitymedium">'.$langs->trans("LDAPServerPortExample").'</span></td></tr>';

// DNserver
print '<tr class="oddeven"><td>'.$langs->trans("LDAPServerDn").'</td><td>';
print '<input class="minwidth300" type="text" name="dn" value="'.getDolGlobalString('LDAP_SERVER_DN').'">';
print '</td><td><span class="opacitymedium">'.$langs->trans("LDAPServerDnExample").'</span></td></tr>';

// Utiliser TLS
print '<tr class="oddeven"><td>'.$langs->trans("LDAPServerUseTLS").'</td><td>';
print $form->selectyesno('usetls', getDolGlobalInt('LDAP_SERVER_USE_TLS'), 1);
print '</td><td><span class="opacitymedium">'.$langs->trans("LDAPServerUseTLSExample").'</span></td></tr>';

// Password hash type
print '<tr class="oddeven"><td>'.$langs->trans("LDAPPasswordHashType").'</td><td>';
print $formldap->selectLdapPasswordHashType(getDolGlobalString('LDAP_PASSWORD_HASH_TYPE'), 'LDAP_PASSWORD_HASH_TYPE');
print '</td><td><span class="opacitymedium">'.$langs->trans("LDAPPasswordHashTypeExample").'</span></td></tr>';

print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("ForANonAnonymousAccess").'</td>';
print "</tr>\n";

// DNAdmin
print '<!-- LDAP_ADMIN_DN -->';
print '<tr class="oddeven"><td>'.$langs->trans("LDAPAdminDn").'</td><td>';
print '<input class="minwidth300" type="text" name="admin" value="'.getDolGlobalString('LDAP_ADMIN_DN').'">';
print '</td><td class="maxwidthhalf"><span class="opacitymedium">'.$langs->trans("LDAPAdminDnExample").'</span></td></tr>';

// Pass
print '<!-- LDAP_ADMIN_PASS -->';
print '<tr class="oddeven"><td>'.$langs->trans("LDAPPassword").'</td><td>';
print '<input class="minwidth150" type="password" name="pass" value="'.dol_escape_htmltag(getDolGlobalString('LDAP_ADMIN_PASS')).'">';
print showValueWithClipboardCPButton(getDolGlobalString('LDAP_ADMIN_PASS'), 0, '&nbsp;');
print '</td><td><span class="opacitymedium">'.$langs->trans('Password').' (ex: secret)</span></td></tr>';

print '</table>';

print dol_get_fiche_end();

print $form->buttonsSaveCancel("Modify", '');

print '</form>';

print '<br>';


/*
 * Test the connection
 */
if (function_exists("ldap_connect")) {
	if (getDolGlobalString('LDAP_SERVER_HOST')) {
		print '<a class="butAction reposition" href="'.$_SERVER["PHP_SELF"].'?action=test">'.$langs->trans("LDAPTestConnect").'</a><br><br>';
	}

	if ($action == 'test') {
		$ldap = new Ldap(); // The parameters are provided and recovered through $conf

		$result = $ldap->connectBind();
		if ($result > 0) {
			// Test ldap connect and bind
			print img_picto('', 'info').' ';
			print '<span class="ok">'.$langs->trans("LDAPTCPConnectOK", $ldap->connectedServer, getDolGlobalString('LDAP_SERVER_PORT')).'</span>';
			print '<br>';

			if (getDolGlobalString('LDAP_ADMIN_DN') && getDolGlobalString('LDAP_ADMIN_PASS')) {
				if ($result == 2) {
					print img_picto('', 'info').' ';
					print '<span class="ok">'.$langs->trans("LDAPBindOK", $ldap->connectedServer, getDolGlobalString('LDAP_SERVER_PORT'), getDolGlobalString('LDAP_ADMIN_DN'), preg_replace('/./i', '*', $conf->global->LDAP_ADMIN_PASS)).'</span>';
					print '<br>';
				} else {
					print img_picto('', 'error').' ';
					print '<span class="error">'.$langs->trans("LDAPBindKO", $ldap->connectedServer, getDolGlobalString('LDAP_SERVER_PORT'), getDolGlobalString('LDAP_ADMIN_DN'), preg_replace('/./i', '*', $conf->global->LDAP_ADMIN_PASS)).'</span>';
					print '<br>';
					print $langs->trans("Error").' '.$ldap->error;
					print '<br>';
				}
			} else {
				print img_picto('', 'warning').' ';
				print '<span class="warning">'.$langs->trans("LDAPNoUserOrPasswordProvidedAccessIsReadOnly").'</span>';
				print '<br>';
			}


			// Test ldap_getversion
			if (($ldap->getVersion() == 3)) {
				print img_picto('', 'info').' ';
				print '<span class="ok">'.$langs->trans("LDAPSetupForVersion3").'</span>';
				print '<br>';
			} else {
				print img_picto('', 'info').' ';
				print '<span class="ok">'.$langs->trans("LDAPSetupForVersion2").'</span>';
				print '<br>';
			}

			$ldap->unbind();
		} else {
			print img_picto('', 'error').' ';
			print '<span class="error">'.$langs->trans("LDAPTCPConnectKO", $ldap->connectedServer, getDolGlobalString('LDAP_SERVER_PORT')).'</span>';
			print '<br>';
			print $langs->trans("Error").' '.$ldap->error;
			print '<br>';
		}
	}
}

// End of page
llxFooter();
$db->close();
