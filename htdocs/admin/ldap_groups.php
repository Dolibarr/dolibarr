<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005 Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */
 
/**
    	\file       htdocs/admin/ldap_groups.php
		\ingroup    ldap
		\brief      Page d'administration/configuration du module Ldap
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */
 
if ($_GET["action"] == 'setvalue' && $user->admin)
{
	$error=0;
	if (! dolibarr_set_const($db, 'LDAP_KEY_GROUPS',$_POST["key"])) $error++;

	if (! dolibarr_set_const($db, 'LDAP_GROUP_DN',$_POST["group"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_FULLNAME',$_POST["fieldfullname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_NAME',$_POST["fieldname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_DESCRIPTION',$_POST["fielddescription"])) $error++;

	if ($error)
	{
		dolibarr_print_error($db->error());
	}
}



/*
 * Visu
 */

llxHeader();

$head = ldap_prepare_head();

print_fiche_titre($langs->trans("LDAPSetup"),'','setup');


// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	$mesg=$langs->trans("LDAPFunctionsNotAvailableOnPHP");
}

if ($mesg) print '<div class="error">'.$mesg.'</div>';
else print '<br>';


dolibarr_fiche_head($head, 'groups', $langs->trans("LDAP"));


print $langs->trans("LDAPDescGroups").'<br>';
print '<br>';


print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';

print '<table class="noborder" width="100%">';
   
$var=true;
$html=new Form($db);

print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("LDAPSynchronizeUsers").'</td>';
print '<td>'.$langs->trans("LDAPNamingAttribute").'</td>';
print "</tr>\n";

// DN pour les groupes
$var=!$var;
print '<tr '.$bc[$var].'><td><b>'.$langs->trans("LDAPGroupDn").picto_required().'</b></td><td>';
print '<input size="48" type="text" name="group" value="'.$conf->global->LDAP_GROUP_DN.'">';
print '</td><td>'.$langs->trans("LDAPGroupDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// Filtre
/*
$var=!$var;
print '<tr '.$bc[$var].'><td><b>'.$langs->trans("LDAPFilterConnection").picto_required().'</b></td><td>';
print '<input size="38" type="text" name="filterconnection" value="'.$conf->global->LDAP_FILTER_CONNECTION.'">';
print '</td><td>'.$langs->trans("LDAPFilterConnectionExample").'</td>';
print '</tr>';
*/

// Common name
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldCommonName").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="'.$conf->global->LDAP_FIELD_FULLNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldCommonNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_FULLNAME.'"'.($conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_FIELD_FULLNAME?' checked="true"':'')."></td>";
print '</tr>';

// Name
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_FIELD_NAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_NAME.'"'.($conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_FIELD_NAME?' checked="true"':'')."></td>";
print '</tr>';

// Description
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldDescription").'</td><td>';
print '<input size="25" type="text" name="fielddescription" value="'.$conf->global->LDAP_FIELD_DESCRIPTION.'">';
print '</td><td>'.$langs->trans("LDAPFieldDescriptionExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_DESCRIPTION.'"'.($conf->global->LDAP_KEY_GROUPS==$conf->global->LDAP_FIELD_DESCRIPTION?' checked="true"':'')."></td>";
print '</tr>';


$var=!$var;
print '<tr '.$bc[$var].'><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table>';

print '</form>';

print '</div>';

print info_admin($langs->trans("LDAPDescValues"));

/*
 * Test de la connexion
 */
if (function_exists("ldap_connect"))
{
	if ($conf->global->LDAP_SERVER_HOST && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
	{
		print '<br>';
		print '<a class="tabAction" href="'.$_SERVER["PHP_SELF"].'?action=testgroup">'.$langs->trans("LDAPTestSynchroGroup").'</a>';
		print '<br><br>';
	}

	if ($_GET["action"] == 'testgroup')
	{
		// Creation contact
		$fgroup=new UserGroup($db);
		$fgroup->initAsSpecimen();

		// Test synchro
		//$result1=$fgroup->delete_ldap($user);
		$result2=$fgroup->update_ldap($user);
		$result3=$fgroup->delete_ldap($user);
	
		if ($result2 > 0)
		{
			print img_picto('','info').' ';
			print '<font class="ok">'.$langs->trans("LDAPSynchroOK").'</font><br>';
		}
		else
		{
			print img_picto('','error').' ';
			print '<font class="warning">'.$langs->trans("LDAPSynchroKO");
			print ': '.$fgroup->error;
			print '</font><br>';
		}
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
