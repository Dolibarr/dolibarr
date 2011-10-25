<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/adherents/ldap.php
 *       \ingroup    ldap member
 *       \brief      Page fiche LDAP adherent
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/member.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/ldap.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");

$langs->load("companies");
$langs->load("members");
$langs->load("ldap");
$langs->load("admin");

// Protection quand utilisateur externe
$rowid = isset($_GET["id"])?$_GET["id"]:'';

$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

$adh = new Adherent($db);
$adh->id = $rowid;
$result=$adh->fetch($rowid);
if (! $result)
{
	dol_print_error($db,"Failed to get adherent: ".$adh->error);
	exit;
}

/*
 * Actions
 */

if ($_GET["action"] == 'dolibarr2ldap')
{
	$message="";

	$db->begin();

	$ldap=new Ldap();
	$result=$ldap->connect_bind();

	$info=$adh->_load_ldap_info();
	$dn=$adh->_load_ldap_dn($info);
	$olddn=$dn;	// We can say that old dn = dn as we force synchro

	$result=$ldap->update($dn,$info,$user,$olddn);

	if ($result >= 0)
	{
		$message.='<div class="ok">'.$langs->trans("MemberSynchronized").'</div>';
		$db->commit();
	}
	else
	{
		$message.='<div class="error">'.$ldap->error.'</div>';
		$db->rollback();
	}
}



/*
 *	View
 */

llxHeader('',$langs->trans("Member"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$html = new Form($db);

$head = member_prepare_head($adh);

dol_fiche_head($head, 'ldap', $langs->trans("Member"), 0, 'user');


print '<table class="border" width="100%">';

// Ref
print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
print '<td class="valeur">';
print $html->showrefnav($adh,'id');
print '</td></tr>';

// Nom
print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$adh->nom.'&nbsp;</td>';
print '</tr>';

// Prenom
print '<tr><td width="15%">'.$langs->trans("Firstname").'</td><td class="valeur">'.$adh->prenom.'&nbsp;</td>';
print '</tr>';

// Login
print '<tr><td>'.$langs->trans("Login").'</td><td class="valeur">'.$adh->login.'&nbsp;</td></tr>';

// Password not crypted
if ($conf->global->LDAP_MEMBER_FIELD_PASSWORD)
{
	print '<tr><td>'.$langs->trans("LDAPFieldPasswordNotCrypted").'</td>';
	print '<td class="valeur">'.$fuser->pass.'</td>';
	print "</tr>\n";
}

// Password crypted
if ($conf->global->LDAP_MEMBER_FIELD_PASSWORD_CRYPTED)
{
	print '<tr><td>'.$langs->trans("LDAPFieldPasswordCrypted").'</td>';
	print '<td class="valeur">'.$fuser->pass_crypted.'</td>';
	print "</tr>\n";
}

// Type
print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adh->type."</td></tr>\n";

$langs->load("admin");

// LDAP DN
print '<tr><td>LDAP '.$langs->trans("LDAPMemberDn").'</td><td class="valeur">'.$conf->global->LDAP_MEMBER_DN."</td></tr>\n";

// LDAP Cle
print '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur">'.$conf->global->LDAP_KEY_MEMBERS."</td></tr>\n";

// LDAP Server
print '<tr><td>LDAP '.$langs->trans("Type").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_TYPE."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("Version").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_PROTOCOLVERSION."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST_SLAVE."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_PORT."</td></tr>\n";

print '</table>';

print '</div>';


dol_htmloutput_mesg($message);


/*
 * Barre d'actions
 */

print '<div class="tabsAction">';

if (! empty($conf->global->LDAP_MEMBER_ACTIVE) && $conf->global->LDAP_MEMBER_ACTIVE != 'ldap2dolibarr')
{
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$adh->id.'&amp;action=dolibarr2ldap">'.$langs->trans("ForceSynchronize").'</a>';
}

print "</div>\n";

if (! empty($conf->global->LDAP_MEMBER_ACTIVE) && $conf->global->LDAP_MEMBER_ACTIVE != 'ldap2dolibarr') print "<br>\n";



// Affichage attributs LDAP
print_titre($langs->trans("LDAPInformationsForThisMember"));

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
	$info=$adh->_load_ldap_info();
	$dn=$adh->_load_ldap_dn($info,1);
	$search = "(".$adh->_load_ldap_dn($info,2).")";
	$records=$ldap->getAttribute($dn,$search);

	//print_r($records);

	// Affichage arbre
	if (count($records) && $records != false && (! isset($records['count']) || $records['count'] > 0))
	{
		if (! is_array($records))
		{
			print '<tr '.$bc[false].'><td colspan="2"><font class="error">'.$langs->trans("ErrorFailedToReadLDAP").'</font></td></tr>';
		}
		else
		{
			$result=show_ldap_content($records,0,$records['count'],true);
		}
	}
	else
	{
		print '<tr '.$bc[false].'><td colspan="2">'.$langs->trans("LDAPRecordNotFound").' (dn='.$dn.' - search='.$search.')</td></tr>';
	}

	$ldap->unbind();
	$ldap->close();
}
else
{
	dol_print_error('',$ldap->error);
}


print '</table>';




$db->close();

llxFooter();
?>
