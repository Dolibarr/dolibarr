<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006 Regis Houssin        <regis.houssin@cap-networks.com>
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
        \file       htdocs/user/ldap.php
        \ingroup    ldap
        \brief      Page fiche LDAP utilisateur
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");
require_once (DOL_DOCUMENT_ROOT."/lib/authldap.lib.php");

$user->getrights('commercial');

$langs->load("companies");
$langs->load("ldap");

// Protection quand utilisateur externe
$contactid = isset($_GET["id"])?$_GET["id"]:'';

$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}


/*
 *	Affichage page
 */

llxHeader();

$form = new Form($db);

$fuser = new User($db, $_GET["id"]);
$fuser->fetch();
$fuser->getrights();


/*
 * Affichage onglets
 */
$head = user_prepare_head($fuser);

dolibarr_fiche_head($head, 'ldap', $langs->trans("User").": ".$fuser->fullname);



/*
 * Fiche en mode visu
 */
print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
print '<td>'.$fuser->id.'</td>';
print '</tr>';

// Nom
print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
print '<td>'.$fuser->nom.'</td>';
print "</tr>\n";

// Prenom
print '<tr><td width="25%" valign="top">'.$langs->trans("Firstname").'</td>';
print '<td>'.$fuser->prenom.'</td>';
print "</tr>\n";

// Login
print '<tr><td width="25%" valign="top">'.$langs->trans("Login").'</td>';
if ($fuser->ldap_sid)
{
	print '<td class="warning">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
}
else
{
	print '<td>'.$fuser->login.'</td>';
}
print '</tr>';

$langs->load("admin");

// LDAP DN
print '<tr><td>LDAP '.$langs->trans("LDAPUserDn").'</td><td class="valeur">'.$conf->global->LDAP_USER_DN."</td></tr>\n";

// LDAP Clé
print '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur">'.$conf->global->LDAP_KEY_USERS."</td></tr>\n";

// LDAP Server
print '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST_SLAVE."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_PORT."</td></tr>\n";

print '</table>';

print '</div>';

print '<br>';


print_titre($langs->trans("LDAPInformationsForThisUser"));

// Affichage attributs LDAP
print '<table width="100%" class="noborder">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("LDAPAttributes").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Lecture LDAP
$ldap=new AuthLdap();
$result=$ldap->connect();
if ($result)
{
	$bind='';
	if ($conf->global->LDAP_ADMIN_DN && $conf->global->LDAP_ADMIN_PASS)
	{
		dolibarr_syslog("ldap.php: authBind user=".$conf->global->LDAP_ADMIN_DN,LOG_DEBUG);
		$bind=$ldap->authBind($conf->global->LDAP_ADMIN_DN,$conf->global->LDAP_ADMIN_PASS);
	}
	if (! $bind)	// Si pas de login ou si connexion avec login en echec, on tente en anonyme
	{
		dolibarr_syslog("ldap.php: bind",LOG_DEBUG);
		$bind=$ldap->bind();
	}

	if ($bind)
	{
		$info["cn"] = trim($fuser->prenom." ".$fuser->nom);

		$dn = $conf->global->LDAP_USER_DN;
//		$dn = "cn=".$info["cn"].",".$dn;
//		$dn = "uid=".$info["uid"].",".$dn
		$search = "(cn=".$info["cn"].")";
		//$search = "(uid=".$info["uid"].")";

		$result=$ldap->search($dn,$search);

		// Affichage arbre
		if (sizeof($result))
		{
			$html=new Form($db);
			$html->show_ldap_content($result,0,0,true);
		}
		else
		{
			print '<tr><td colspan="2">'.$langs->trans("LDAPRecordNotFound").'</td></tr>';
		}

		$ldap->unbind();
	}
	else
	{
		dolibarr_print_error('',$ldap->error);
	}
	$ldap->close();
}
else
{
	dolibarr_print_error('',$ldap->error);
}

print '</table>';




$db->close();

llxFooter('$Date$ - $Revision$');
?>
