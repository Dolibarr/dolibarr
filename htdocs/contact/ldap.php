<?php
/* Copyright (C) 2006-2010	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2017	Regis Houssin        <regis.houssin@inodbox.com>
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
 *       \file       htdocs/contact/ldap.php
 *       \ingroup    ldap
 *       \brief      Page fiche LDAP contact
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'ldap'));
$langs->load("admin");

$action=GETPOST('action', 'aZ09');

// Security check
$id = GETPOST('id', 'int');
if ($user->socid) $socid=$user->socid;
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe');

$object = new Contact($db);
if ($id > 0)
{
	$object->fetch($id, $user);
}


/*
 * Actions
 */

if ($action == 'dolibarr2ldap')
{
	$db->begin();

	$ldap=new Ldap();
	$result=$ldap->connect_bind();

	$info=$object->_load_ldap_info();
	$dn=$object->_load_ldap_dn($info);
	$olddn=$dn;	// We can say that old dn = dn as we force synchro

	$result=$ldap->update($dn, $info, $user, $olddn);

	if ($result >= 0)
	{
		setEventMessages($langs->trans("ContactSynchronized"), null, 'mesgs');
		$db->commit();
	}
	else
	{
		setEventMessages($ldap->error, $ldap->errors, 'errors');
		$db->rollback();
	}
}


/*
 *	View
 */

$form = new Form($db);

$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

llxHeader('', $title, 'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas');

$head = contact_prepare_head($object);

dol_fiche_head($head, 'ldap', $title, -1, 'contact');

$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '');

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent">';

// Company
if ($object->socid > 0)
{
	$thirdparty = new Societe($db);
	$thirdparty->fetch($object->socid);

	print '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td colspan="3">'.$thirdparty->getNomUrl(1).'</td></tr>';
}
else
{
	print '<tr><td class="titlefield">'.$langs->trans("ThirdParty").'</td><td colspan="3">';
	print $langs->trans("ContactNotLinkedToCompany");
	print '</td></tr>';
}

// Civility
print '<tr><td class="titlefield">'.$langs->trans("UserTitle").'</td><td colspan="3">';
print $object->getCivilityLabel();
print '</td></tr>';

// LDAP DN
print '<tr><td>LDAP '.$langs->trans("LDAPContactDn").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_CONTACT_DN."</td></tr>\n";

// LDAP Cle
print '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_KEY_CONTACTS."</td></tr>\n";

// LDAP Server
print '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_SERVER_HOST."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_SERVER_HOST_SLAVE."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur" colspan="3">'.$conf->global->LDAP_SERVER_PORT."</td></tr>\n";

print '</table>';

print '</div>';

dol_fiche_end();


/*
 * Barre d'actions
 */

print '<div class="tabsAction">';

if (! empty($conf->global->LDAP_CONTACT_ACTIVE) && $conf->global->LDAP_CONTACT_ACTIVE != 'ldap2dolibarr')
{
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=dolibarr2ldap">'.$langs->trans("ForceSynchronize").'</a>';
}

print "</div>\n";

if (! empty($conf->global->LDAP_CONTACT_ACTIVE) && $conf->global->LDAP_CONTACT_ACTIVE != 'ldap2dolibarr') print "<br>\n";



// Affichage attributs LDAP
print load_fiche_titre($langs->trans("LDAPInformationsForThisContact"));

print '<table width="100%" class="noborder">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("LDAPAttributes").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Lecture LDAP
$ldap=new Ldap();
$result=$ldap->connect_bind();
if ($result > 0)
{
	$info=$object->_load_ldap_info();
	$dn=$object->_load_ldap_dn($info, 1);
	$search = "(".$object->_load_ldap_dn($info, 2).")";

	$records = $ldap->getAttribute($dn, $search);

	//var_dump($records);

	// Show tree
    if (((! is_numeric($records)) || $records != 0) && (! isset($records['count']) || $records['count'] > 0))
	{
		if (! is_array($records))
		{
			print '<tr class="oddeven"><td colspan="2"><font class="error">'.$langs->trans("ErrorFailedToReadLDAP").'</font></td></tr>';
		}
		else
		{
			$result=show_ldap_content($records, 0, $records['count'], true);
		}
	}
	else
	{
		print '<tr class="oddeven"><td colspan="2">'.$langs->trans("LDAPRecordNotFound").' (dn='.$dn.' - search='.$search.')</td></tr>';
	}

	$ldap->unbind();
	$ldap->close();
}
else
{
	setEventMessages($ldap->error, $ldap->errors, 'errors');
}


print '</table>';

llxFooter();
$db->close();
