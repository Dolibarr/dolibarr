<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Sebastien Di Cintio	<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier		<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     	\file       htdocs/admin/ldap_members_types.php
 *     	\ingroup    ldap
 *		\brief      Page to setup LDAP synchronization for members types
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';

$langs->load("admin");
$langs->load("errors");

if (!$user->admin)
	accessforbidden();

$action = GETPOST('action','aZ09');


/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin)
{
	$error=0;
	$db->begin();

	if (! dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_DN',GETPOST("membertype"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_OBJECT_CLASS',GETPOST("objectclass"),'chaine',0,'',$conf->entity)) $error++;

	if (! dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_FIELD_FULLNAME',GETPOST("fieldfullname"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_FIELD_DESCRIPTION',GETPOST("fielddescription"),'chaine',0,'',$conf->entity)) $error++;
	if (! dolibarr_set_const($db, 'LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS',GETPOST("fieldmembertypemembers"),'chaine',0,'',$conf->entity)) $error++;

	// This one must be after the others
	$valkey='';
	$key=GETPOST("key");
	if ($key) $valkey=$conf->global->$key;
	if (! dolibarr_set_const($db, 'LDAP_KEY_MEMBERS_TYPES',$valkey,'chaine',0,'',$conf->entity)) $error++;

	if (! $error)
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



/*
 * View
 */

llxHeader('',$langs->trans("LDAPSetup"),'EN:Module_LDAP_En|FR:Module_LDAP|ES:M&oacute;dulo_LDAP');
$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("LDAPSetup"),$linkback,'title_setup');

$head = ldap_prepare_head();

// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	setEventMessages($langs->trans("LDAPFunctionsNotAvailableOnPHP"), null, 'errors');
}

dol_fiche_head($head, 'memberstypes', $langs->trans("LDAPSetup"), -1);


print $langs->trans("LDAPDescMembersTypes").'<br>';
print '<br>';


print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

$form=new Form($db);

print '<table class="noborder" width="100%">';
$var=true;

print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeMembersTypes").'</td>';
print "</tr>\n";

// DN pour les types de membres

print '<tr class="oddeven"><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPMemberTypeDn").'</span></td><td>';
print '<input size="48" type="text" name="membertype" value="'.$conf->global->LDAP_MEMBER_TYPE_DN.'">';
print '</td><td>'.$langs->trans("LDAPMemberTypepDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure

print '<tr class="oddeven"><td width="25%"><span class="fieldrequired">'.$langs->trans("LDAPMemberTypeObjectClassList").'</span></td><td>';
print '<input size="48" type="text" name="objectclass" value="'.$conf->global->LDAP_MEMBER_TYPE_OBJECT_CLASS.'">';
print '</td><td>'.$langs->trans("LDAPMemberTypeObjectClassListExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

print '</table>';
print '<br>';
print '<table class="noborder" width="100%">';
$var=true;

print '<tr class="liste_titre">';
print '<td width="25%">'.$langs->trans("LDAPDolibarrMapping").'</td>';
print '<td colspan="2">'.$langs->trans("LDAPLdapMapping").'</td>';
print '<td align="right">'.$langs->trans("LDAPNamingAttribute").'</td>';
print "</tr>\n";

// Filtre

// Common name

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="'.$conf->global->LDAP_MEMBER_TYPE_FIELD_FULLNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldCommonNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_TYPE_FIELD_FULLNAME"'.(($conf->global->LDAP_KEY_MEMBERS_TYPES && $conf->global->LDAP_KEY_MEMBERS_TYPES==$conf->global->LDAP_MEMBER_TYPE_FIELD_FULLNAME)?' checked':'')."></td>";
print '</tr>';

// Description

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldDescription").'</td><td>';
print '<input size="25" type="text" name="fielddescription" value="'.$conf->global->LDAP_MEMBER_TYPE_FIELD_DESCRIPTION.'">';
print '</td><td>'.$langs->trans("LDAPFieldDescriptionExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_TYPE_FIELD_DESCRIPTION"'.(($conf->global->LDAP_KEY_MEMBERS_TYPES && $conf->global->LDAP_KEY_MEMBER_TYPES==$conf->global->LDAP_MEMBER_TYPE_FIELD_DESCRIPTION)?' checked':'')."></td>";
print '</tr>';

// User group

print '<tr class="oddeven"><td>'.$langs->trans("LDAPFieldGroupMembers").'</td><td>';
print '<input size="25" type="text" name="fieldmembertypemembers" value="'.$conf->global->LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS.'">';
print '</td><td>'.$langs->trans("LDAPFieldGroupMembersExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS"'.(($conf->global->LDAP_KEY_MEMBERS_TYPES && $conf->global->LDAP_KEY_MEMBERS_TYPES==$conf->global->LDAP_MEMBER_TYPE_FIELD_GROUPMEMBERS)?' checked':'')."></td>";
print '</tr>';

print '</table>';

print info_admin($langs->trans("LDAPDescValues"));

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';


/*
 * Test de la connexion
 */
if ($conf->global->LDAP_MEMBER_TYPE_ACTIVE == '1')
{
	$butlabel=$langs->trans("LDAPTestSynchroMemberType");
	$testlabel='testmembertype';
	$key=$conf->global->LDAP_KEY_MEMBERS_TYPES;
	$dn=$conf->global->LDAP_MEMBER_TYPE_DN;
	$objectclass=$conf->global->LDAP_MEMBER_TYPE_OBJECT_CLASS;

	show_ldap_test_button($butlabel,$testlabel,$key,$dn,$objectclass);
}

if (function_exists("ldap_connect"))
{
	if ($_GET["action"] == 'testmembertype')
	{
		// Creation objet
		$object=new AdherentType($db);
		$object->initAsSpecimen();

		// Test synchro
		$ldap=new Ldap();
		$result=$ldap->connect_bind();

		if ($result > 0)
		{
			$info=$object->_load_ldap_info();
			$dn=$object->_load_ldap_dn($info);

			// Get a gid number for objectclass PosixGroup
			if (in_array('posixGroup',$info['objectclass'])) {
				$info['gidNumber'] = $ldap->getNextGroupGid('LDAP_KEY_MEMBERS_TYPES');
			}

			$result1=$ldap->delete($dn);			// To be sure to delete existing records
			$result2=$ldap->add($dn,$info,$user);	// Now the test
			$result3=$ldap->delete($dn);			// Clean what we did

			if ($result2 > 0)
			{
				print img_picto('','info').' ';
				print '<font class="ok">'.$langs->trans("LDAPSynchroOK").'</font><br>';
			}
			else
			{
				print img_picto('','error').' ';
				print '<font class="error">'.$langs->trans("LDAPSynchroKOMayBePermissions");
				print ': '.$ldap->error;
				print '</font><br>';
				print $langs->trans("ErrorLDAPMakeManualTest",$conf->ldap->dir_temp).'<br>';
			}

			print "<br>\n";
			print "LDAP input file used for test:<br><br>\n";
			print nl2br($ldap->dump_content($dn,$info));
			print "\n<br>";
		}
		else
		{
			print img_picto('','error').' ';
			print '<font class="error">'.$langs->trans("LDAPSynchroKO");
			print ': '.$ldap->error;
			print '</font><br>';
			print $langs->trans("ErrorLDAPMakeManualTest",$conf->ldap->dir_temp).'<br>';
		}
	}
}

llxFooter();
$db->close();
