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
    	\file       htdocs/admin/ldap.php
		\ingroup    ldap
		\brief      Page d'administration/configuration du module Ldap
		\version    $Revision$
        \remarks    Exemple configuration :
                    LDAP_SERVER_HOST    Serveur LDAP		      192.168.1.50
                    LDAP_SERVER_PORT    Port LDAP             389
                    LDAP_ADMIN_DN       Administrateur LDAP	  cn=adminldap,dc=societe,dc=com	
                    LDAP_ADMIN_PASS     Mot de passe		      xxxxxxxx
                    LDAP_USER_DN        DN des utilisateurs	  ou=users,dc=societe,dc=com
                    LDAP_GROUP_DN       DN des groupes		    ou=groups,dc=societe,dc=com	
                    LDAP_CONTACT_DN     DN des contacts		    ou=contacts,dc=societe,dc=com
                    LDAP_SERVER_TYPE    Type				          Openldap
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

/*
 * Actions
 */
 
if ($_GET["action"] == 'setvalue' && $user->admin)
{
	if (! dolibarr_set_const($db, 'LDAP_SERVER_TYPE',$_POST["type"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_SERVER_PROTOCOLVERSION',$_POST["version"]))
	{
		print $db->error();
	}

	if (! dolibarr_set_const($db, 'LDAP_SERVER_HOST',$_POST["host"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_SERVER_PORT',$_POST["port"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_ADMIN_DN',$_POST["admin"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_ADMIN_PASS',$_POST["pass"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_ADMIN_DN',$_POST["admin"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_USER_DN',$_POST["user"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_GROUP_DN',$_POST["group"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_CONTACT_ACTIVE',$_POST["activecontact"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_CONTACT_DN',$_POST["contact"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_SERVER_USE_TLS',$_POST["usetls"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_FIELD_NAME',$_POST["fieldname"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_FIELD_REALNAME',$_POST["fieldrealname"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_FIELD_MAIL',$_POST["fieldmail"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_FIELD_PHONE',$_POST["fieldphone"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_FILTER_CONNECTION',$_POST["filterconnection"]))
	{
		print $db->error();
	}
	if (! dolibarr_set_const($db, 'LDAP_FIELD_LOGIN',$_POST["fieldlogin"]))
	{
		print $db->error();
	}
	if ($db->query($sql))
    {
    	Header("Location: ldap.php");
    	exit;
    }
}



/*
 * Visu
 */

llxHeader();

print_titre($langs->trans("LDAPSetup"));

// Test si fonction LDAP actives
if (! function_exists("ldap_connect"))
{
	$mesg=$langs->trans("LDAPFunctionsNotAvailableOnPHP");
}

if ($mesg) print '<div class="error">'.$mesg.'</div>';
else print '<br>';


print '<form method="post" action="ldap.php?action=setvalue">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print "</tr>\n";
   
$var=true;
$html=new Form($db);

// Type
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("Type").'</td><td>';
$arraylist=array();
$arraylist['activedirectory']='Active Directory';
$arraylist['openldap']='OpenLdap';
$arraylist['egroupware']='Egroupware';
$html->select_array('type',$arraylist,$conf->global->LDAP_SERVER_TYPE);
print '</td><td>&nbsp;</td></tr>';

// Version
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("Version").'</td><td>';
$arraylist=array();
$arraylist['3']='Version 3';
$arraylist['2']='Version 2';
$html->select_array('version',$arraylist,$conf->global->LDAP_SERVER_PROTOCOLVERSION);
print '</td><td>&nbsp;</td></tr>';

// Serveur
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("LDAPServer").'</td><td>';
print '<input size="25" type="text" name="host" value="'.$conf->global->LDAP_SERVER_HOST.'">';
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

// DNAdmin
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("DNAdmin").'</td><td>';
print '<input size="25" type="text" name="admin" value="'.$conf->global->LDAP_ADMIN_DN.'">';
print '</td><td>&nbsp;</td></tr>';

// Pass
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPPassword").'</td><td>';
if ($conf->global->LDAP_ADMIN_PASS)
{
	print '<input size="25" type="text" name="pass" value="'.$conf->global->LDAP_ADMIN_PASS.'">';// je le met en visible pour test
}
else
{
	print '<input size="25" type="text" name="pass" value="'.$conf->global->LDAP_ADMIN_PASS.'">';
}
print '</td><td>&nbsp;</td></tr>';

// Utiliser TLS
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPUseTLS").'</td><td>';
$arraylist=array();
$arraylist['0']=$langs->trans("No");
$arraylist['1']=$langs->trans("Yes");
$html->select_array('usetls',$arraylist,$conf->global->LDAP_SERVER_USE_TLS);
print '</td><td>'.$langs->trans("LDAPServerUseTLSExample").'</td></tr>';


print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("LDAPSynchronizeUsersAndGroup").'</td>';
print "</tr>\n";

// Synchro utilisateurs/groupes active
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("DNUserGroupActive").'</td><td>';
$arraylist=array();
$arraylist['0']=$langs->trans("No");
$arraylist['1']=$langs->trans("Yes");
$html->select_array('activecontact',$arraylist,$conf->global->LDAP_USERGROUP_ACTIVE);
print '</td><td>'.$langs->trans("NotYetAvailable").'</td></tr>';

// DN Pour les utilisateurs
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("DNUser").'</td><td>';
print '<input size="25" type="text" name="user" value="'.$conf->global->LDAP_USER_DN.'">';
print '</td><td>'.$langs->trans("DNUserExample").'</td></tr>';

// Champ de login
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldLogin").'</td><td>';
if ($conf->global->LDAP_FIELD_LOGIN)
{
  print '<input size="25" type="text" name="fieldlogin" value="'.$conf->global->LDAP_FIELD_LOGIN.'">';
}
else
{
  print '<input size="25" type="text" name="fieldlogin" value="uid">';
}
print '</td><td>'.$langs->trans("LDAPFieldLoginExample").'</td></tr>';

// Filtre de connexion
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFilterConnection").'</td><td>';
if ($conf->global->LDAP_FILTER_CONNECTION)
{
  print '<input size="25" type="text" name="filterconnection" value="'.$conf->global->LDAP_FILTER_CONNECTION.'">';
}
else
{
  print '<input size="25" type="text" name="filterconnection" value="(&(objectClass=user)(objectCategory=person))">';
}
print '</td><td>'.$langs->trans("LDAPFilterConnectionExample").'</td></tr>';

// DN pour les groupes
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("DNGroup").'</td><td>';
print '<input size="25" type="text" name="group" value="'.$conf->global->LDAP_GROUP_DN.'">';
print '</td><td>'.$langs->trans("DNGroupExample").'</td></tr>';


print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("LDAPSynchronizeContacts").'</td>';
print "</tr>\n";

// Synchro contact active
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("DNContactActive").'</td><td>';
$arraylist=array();
$arraylist['0']=$langs->trans("No");
$arraylist['1']=$langs->trans("Yes");
$html->select_array('activecontact',$arraylist,$conf->global->LDAP_CONTACT_ACTIVE);
print '</td><td>'.$langs->trans("DNContactActiveExample").'</td></tr>';

// DN Pour les contacts
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("DNContact").'</td><td>';
print '<input size="25" type="text" name="contact" value="'.$conf->global->LDAP_CONTACT_DN.'">';
print '</td><td>'.$langs->trans("DNContactExample").'</td></tr>';

print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("ConnectionDolibarrLdap").'</td>';
print "</tr>\n";

// SAMAccountName
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldName").'</td><td>';
if ($conf->global->LDAP_FIELD_NAME)
{
  print '<input size="25" type="text" name="fieldname" value="'.$conf->global->LDAP_FIELD_NAME.'">';
}
else
{
  print '<input size="25" type="text" name="fieldname" value="samaccountname">';
}
print '</td><td>'.$langs->trans("LDAPFieldNameExample").'</td></tr>';

// RealName
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldRealName").'</td><td>';
if ($conf->global->LDAP_FIELD_REALNAME)
{
  print '<input size="25" type="text" name="fieldrealname" value="'.$conf->global->LDAP_FIELD_REALNAME.'">';
}
else
{
  print '<input size="25" type="text" name="fieldrealname" value="name">';
}
print '</td><td>'.$langs->trans("LDAPFieldRealNameExample").'</td></tr>';

// Mail
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldMail").'</td><td>';
if ($conf->global->LDAP_FIELD_MAIL)
{
  print '<input size="25" type="text" name="fieldmail" value="'.$conf->global->LDAP_FIELD_MAIL.'">';
}
else
{
  print '<input size="25" type="text" name="fieldmail" value="mail">';
}
print '</td><td>'.$langs->trans("LDAPFieldMailExample").'</td></tr>';

// Phone
$var=!$var;
print '<tr '.$bc[$var].'><td>'.$langs->trans("LDAPFieldPhone").'</td><td>';
if ($conf->global->LDAP_FIELD_PHONE)
{
  print '<input size="25" type="text" name="fieldphone" value="'.$conf->global->LDAP_FIELD_PHONE.'">';
}
else
{
  print '<input size="25" type="text" name="fieldphone" value="telephonenumber">';
}
print '</td><td>'.$langs->trans("LDAPFieldPhoneExample").'</td></tr>';


print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table>';

print '</form>';



/*
 * Test de la connexion
 */
if (function_exists("ldap_connect"))
{
	if ($conf->global->LDAP_SERVER_HOST)
	{
	    print '<a class="tabAction" href="ldap.php?action=test">'.$langs->trans("LDAPTestConnect").'</a><br><br>';
	}
	
	
	if ($conf->global->LDAP_SERVER_HOST && $conf->global->LDAP_ADMIN_DN && $conf->global->LDAP_ADMIN_PASS && $_GET["action"] == 'test')
	{
		$ldap = New Ldap();
		// Test ldap_connect
		// ce test n'est pas fiable car une ressource est constamment retournée
		// il faut se fier au test ldap_bind
		$ds = $ldap->dolibarr_ldap_connect();
		if ($ds)
		{
			print img_picto('','info');
			print $langs->trans("LDAPTestOK").'<br>';
		}
		else
		{
			print img_picto('','alerte');
			print $langs->trans("LDAPTestKO").'<br>';
			print "<br>";
			print $ldap->err;
			print "<br>";
		}

		if ($ds)
		{
			// Test ldap_getversion
			if (($ldap->dolibarr_ldap_getversion($ds) == 3))
			{
				print img_picto('','info');
				print $langs->trans("LDAPSetupForVersion3").'<br>';
			}
			else
			{
				print img_picto('','info');
				print $langs->trans("LDAPSetupForVersion2").'<br>';
			}
	
		  // Test ldap_bind
			$bind = $ldap->dolibarr_ldap_bind($ds);
			
			if ($bind)
			{
				print img_picto('','info');
				print "Connexion au dn $dn réussi<br>";
			}
			else
			{
				print img_picto('','alerte');
				print "Connexion au dn $dn raté : ";
				print $ldap->err;
				print "<br>";
			}

		  // Test ldap_unbind
		  $unbind = $ldap->dolibarr_ldap_unbind($ds);
		  
		  if ($bind && $unbind)
		  {
		  	print img_picto('','info');
		  	print "Déconnection du dn $dn réussi<br>";
		  }
		  else
		  {
			  print img_picto('','alerte');
			  print "Déconnection du dn $dn raté";
			  print "<br>";
			}
		}
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
