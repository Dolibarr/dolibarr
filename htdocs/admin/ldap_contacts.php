<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    	\file       htdocs/admin/ldap_contacts.php
		\ingroup    ldap
		\brief      Page d'administration/configuration du module Ldap
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */

if ($_GET["action"] == 'setvalue' && $user->admin)
{
	$error=0;
	if (! dolibarr_set_const($db, 'LDAP_KEY_CONTACTS',$_POST["key"])) $error++;

	if (! dolibarr_set_const($db, 'LDAP_CONTACT_DN',$_POST["contactdn"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_CONTACT_OBJECT_CLASS',$_POST["objectclass"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_FULLNAME',$_POST["fieldfullname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_NAME',$_POST["fieldname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_FIRSTNAME',$_POST["fieldfirstname"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_COMPANY',$_POST["fieldcompany"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MAIL',$_POST["fieldmail"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_PHONE',$_POST["fieldphone"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_HOMEPHONE',$_POST["fieldhomephone"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MOBILE',$_POST["fieldmobile"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_FAX',$_POST["fieldfax"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_ADDRESS',$_POST["fieldaddress"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_ZIP',$_POST["fieldzip"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_TOWN',$_POST["fieldtown"])) $error++;
	if (! dolibarr_set_const($db, 'LDAP_FIELD_COUNTRY',$_POST["fieldcountry"])) $error++;
	
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

// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	$mesg=$langs->trans("LDAPFunctionsNotAvailableOnPHP");
}

if ($mesg) print '<div class="error">'.$mesg.'</div>';


dolibarr_fiche_head($head, 'contacts', $langs->trans("LDAPSetup"));


print $langs->trans("LDAPDescContact").'<br>';
print '<br>';

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?action=setvalue">';

$html=new Form($db);

print '<table class="noborder" width="100%">';
$var=true;

print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("LDAPSynchronizeUsers").'</td>';
print "</tr>\n";
   

// DN Pour les contacts
$var=!$var;
print '<tr '.$bc[$var].'><td width="25%"><b>'.$langs->trans("LDAPContactDn").picto_required().'</b></td><td>';
print '<input size="48" type="text" name="contactdn" value="'.$conf->global->LDAP_CONTACT_DN.'">';
print '</td><td>'.$langs->trans("LDAPContactDnExample").'</td>';
print '<td>&nbsp;</td>';
print '</tr>';

// List of object class used to define attributes in structure
$var=!$var;
print '<tr '.$bc[$var].'><td width="25%"><b>'.$langs->trans("LDAPContactObjectClassList").picto_required().'</b></td><td>';
print '<input size="48" type="text" name="objectclass" value="'.$conf->global->LDAP_CONTACT_OBJECT_CLASS.'">';
print '</td><td>'.$langs->trans("LDAPContactObjectClassListExample").'</td>';
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

// Common name
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFullname").'</td><td>';
print '<input size="25" type="text" name="fieldfullname" value="'.$conf->global->LDAP_FIELD_FULLNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFullnameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_FULLNAME.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_FULLNAME?' checked="true"':'')."></td>";
print '</tr>';

// Name
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldName").'</td><td>';
print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_FIELD_NAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_NAME.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_NAME?' checked="true"':'')."></td>";
print '</tr>';

// Firstname
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFirstName").'</td><td>';
print '<input size="25" type="text" name="fieldfirstname" value="'.$conf->global->LDAP_FIELD_FIRSTNAME.'">';
print '</td><td>'.$langs->trans("LDAPFieldFirstNameExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_FIRSTNAME.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_FIRSTNAME?' checked="true"':'')."></td>";
print '</tr>';

// Company
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldCompany").'</td><td>';
print '<input size="25" type="text" name="fieldcompany" value="'.$conf->global->LDAP_FIELD_COMPANY.'">';
print '</td><td>'.$langs->trans("LDAPFieldCompanyExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_COMPANY.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_COMPANY?' checked="true"':'')."></td>";
print '</tr>';

// Mail
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldMail").'</td><td>';
print '<input size="25" type="text" name="fieldmail" value="'.$conf->global->LDAP_FIELD_MAIL.'">';
print '</td><td>'.$langs->trans("LDAPFieldMailExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_MAIL.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_MAIL?' checked="true"':'')."></td>";
print '</tr>';

// Phone pro
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldPhone").'</td><td>';
print '<input size="25" type="text" name="fieldphone" value="'.$conf->global->LDAP_FIELD_PHONE.'">';
print '</td><td>'.$langs->trans("LDAPFieldPhoneExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_PHONE.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_PHONE?' checked="true"':'')."></td>";
print '</tr>';

// Phone home
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldHomePhone").'</td><td>';
print '<input size="25" type="text" name="fieldhomephone" value="'.$conf->global->LDAP_FIELD_HOMEPHONE.'">';
print '</td><td>'.$langs->trans("LDAPFieldHomePhoneExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_HOMEPHONE.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_HOMEPHONE?' checked="true"':'')."></td>";
print '</tr>';

// Mobile
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldMobile").'</td><td>';
print '<input size="25" type="text" name="fieldmobile" value="'.$conf->global->LDAP_FIELD_MOBILE.'">';
print '</td><td>'.$langs->trans("LDAPFieldMobileExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_MOBILE.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_MOBILE?' checked="true"':'')."></td>";
print '</tr>';

// Fax
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldFax").'</td><td>';
print '<input size="25" type="text" name="fieldfax" value="'.$conf->global->LDAP_FIELD_FAX.'">';
print '</td><td>'.$langs->trans("LDAPFieldFaxExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_FAX.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_FAX?' checked="true"':'')."></td>";
print '</tr>';

// Address
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldAddress").'</td><td>';
print '<input size="25" type="text" name="fieldaddress" value="'.$conf->global->LDAP_FIELD_ADDRESS.'">';
print '</td><td>'.$langs->trans("LDAPFieldAddressExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_ADDRESS.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_ADDRESS?' checked="true"':'')."></td>";
print '</tr>';

// CP
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldZip").'</td><td>';
print '<input size="25" type="text" name="fieldzip" value="'.$conf->global->LDAP_FIELD_ZIP.'">';
print '</td><td>'.$langs->trans("LDAPFieldZipExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_ZIP.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_ZIP?' checked="true"':'')."></td>";
print '</tr>';

// Ville
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldTown").'</td><td>';
print '<input size="25" type="text" name="fieldtown" value="'.$conf->global->LDAP_FIELD_TOWN.'">';
print '</td><td>'.$langs->trans("LDAPFieldTownExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_TOWN.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_TOWN?' checked="true"':'')."></td>";
print '</tr>';

// Pays
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldCountry").'</td><td>';
print '<input size="25" type="text" name="fieldcountry" value="'.$conf->global->LDAP_FIELD_COUNTRY.'">';
print '</td><td>'.$langs->trans("LDAPFieldCountryExample").'</td>';
print '<td align="right"><input type="radio" name="key" value="'.$conf->global->LDAP_FIELD_COUNTRY.'"'.($conf->global->LDAP_KEY_CONTACTS==$conf->global->LDAP_FIELD_COUNTRY?' checked="true"':'')."></td>";
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
	if ($conf->global->LDAP_SERVER_HOST)
	{
		print '<br>';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test">'.$langs->trans("LDAPTestSynchroContact").'</a>';
		print '<br><br>';
	}

	if ($_GET["action"] == 'test')
	{
		// Creation objet
		$contact=new Contact($db);
		$contact->initAsSpecimen();

		// Test synchro
		$ldap=new Ldap();
		$result=$ldap->connect_bind();

		if ($result > 0)
		{
			$info=$contact->_load_ldap_info();
			$dn=$contact->_load_ldap_dn($info);

			$result2=$ldap->update($dn,$info,$user);
			$result3=$ldap->delete($dn);

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
			}
		}
		else
		{
			print img_picto('','error').' ';
			print '<font class="error">'.$langs->trans("LDAPSynchroKO");
			print ': '.$ldap->error;
			print '</font><br>';
		}

	}
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
