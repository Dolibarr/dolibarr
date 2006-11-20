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
        \file       htdocs/adherents/ldap.php
        \ingroup    ldap
        \brief      Page fiche LDAP adherent
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/authldap.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");

$user->getrights('commercial');

$langs->load("companies");
$langs->load("members");
$langs->load("ldap");

// Protection quand utilisateur externe
$rowid = isset($_GET["id"])?$_GET["id"]:'';

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


$adh = new Adherent($db);
$adh->id = $rowid;
$result=$adh->fetch($rowid);
if (! $result)
{
	dolibarr_print_error($db,"Failed to get adherent: ".$adh->error);
	exit;
}
$adh->fetch_optionals($rowid);

$adht = new AdherentType($db);
$result=$adht->fetch($adh->typeid);
if (! $result)
{
	dolibarr_print_error($db,"Failed to get type of adherent: ".$adht->error);
	exit;
}



/*
 * Affichage onglets
 */
$head = member_prepare_head($adh);

dolibarr_fiche_head($head, 'ldap', $langs->trans("Member").": ".$adh->fullname);



/*
 * Fiche en mode visu
 */
print '<table class="border" width="100%">';

// Ref
print '<tr><td>'.$langs->trans("Ref").'</td><td class="valeur">'.$adh->id.'&nbsp;</td></tr>';

// Nom
print '<tr><td>'.$langs->trans("Lastname").'*</td><td class="valeur">'.$adh->nom.'&nbsp;</td>';
print '</tr>';

// Prenom
print '<tr><td width="15%">'.$langs->trans("Firstname").'*</td><td class="valeur">'.$adh->prenom.'&nbsp;</td>';
print '</tr>';

// Login
print '<tr><td>'.$langs->trans("Login").'*</td><td class="valeur">'.$adh->login.'&nbsp;</td></tr>';

// Type
print '<tr><td>'.$langs->trans("Type").'*</td><td class="valeur">'.$adh->type."</td></tr>\n";

// LDAP DN
$langs->load("admin");
print '<tr><td>'.$langs->trans("LDAPMemberDn").'*</td><td class="valeur">'.$conf->global->LDAP_MEMBER_DN."</td></tr>\n";

// LDAP Server
print '<tr><td>'.$langs->trans("LDAPPrimaryServer").'*</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST."</td></tr>\n";
print '<tr><td>'.$langs->trans("LDAPSecondaryServer").'*</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST_SLAVE."</td></tr>\n";
print '<tr><td>'.$langs->trans("LDAPServerPort").'*</td><td class="valeur">'.$conf->global->LDAP_SERVER_PORT."</td></tr>\n";

print '</table>';

print '</div>';

print '<br>';


print_titre($langs->trans("LDAPInformationsForThisMember"));

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
		$info["cn"] = trim($adh->prenom." ".$adh->nom);
		$info["uid"] = trim($adh->login);

		$dn = $conf->global->LDAP_MEMBER_DN;
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
