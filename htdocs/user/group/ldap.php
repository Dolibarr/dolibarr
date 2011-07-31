<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/user/group/ldap.php
 *       \ingroup    ldap
 *       \brief      Page fiche LDAP groupe
 *       \version    $Id: ldap.php,v 1.26 2011/07/31 23:21:25 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php');
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");

$langs->load("companies");
$langs->load("ldap");
$langs->load("users");

$canreadperms=true;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
	$canreadperms=($user->admin || $user->rights->user->group_advance->read);
}

// Protection quand utilisateur externe
$contactid = isset($_GET["id"])?$_GET["id"]:'';

$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

$fgroup = new Usergroup($db, $_GET["id"]);
$fgroup->fetch($_GET["id"]);
$fgroup->getrights();


/*
 * Actions
 */

if ($_GET["action"] == 'dolibarr2ldap')
{
	$message="";

	$db->begin();

	$ldap=new Ldap();
	$result=$ldap->connect_bind();

	$info=$fgroup->_load_ldap_info();
	$dn=$fgroup->_load_ldap_dn($info);
	$olddn=$dn;	// We can say that old dn = dn as we force synchro

	$result=$ldap->update($dn,$info,$user,$olddn);

	if ($result >= 0)
	{
		$message.='<div class="ok">'.$langs->trans("GroupSynchronized").'</div>';
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

llxHeader();

$form = new Form($db);


$head = group_prepare_head($fgroup);

dol_fiche_head($head, 'ldap', $langs->trans("Group"), 0, 'group');

print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
print '<td colspan="2">';
print $form->showrefnav($fgroup,'id','',$canreadperms);
print '</td>';
print '</tr>';

// Name
print '<tr><td width="25%" valign="top">'.$langs->trans("Name").'</td>';
print '<td width="75%" class="valeur">'.$fgroup->nom;
if (!$fgroup->entity)
{
	print img_redstar($langs->trans("GlobalGroup"));
}
print "</td></tr>\n";

// Note
print '<tr><td width="25%" valign="top">'.$langs->trans("Note").'</td>';
print '<td class="valeur">'.nl2br($fgroup->note).'&nbsp;</td>';
print "</tr>\n";

$langs->load("admin");

// LDAP DN
print '<tr><td>LDAP '.$langs->trans("LDAPGroupDn").'</td><td class="valeur">'.$conf->global->LDAP_GROUP_DN."</td></tr>\n";

// LDAP Cle
print '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur">'.$conf->global->LDAP_KEY_GROUPS."</td></tr>\n";

// LDAP Server
print '<tr><td>LDAP '.$langs->trans("LDAPPrimaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPSecondaryServer").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_HOST_SLAVE."</td></tr>\n";
print '<tr><td>LDAP '.$langs->trans("LDAPServerPort").'</td><td class="valeur">'.$conf->global->LDAP_SERVER_PORT."</td></tr>\n";

print "</table>\n";

print '</div>';


dol_htmloutput_mesg($message);


/*
 * Barre d'actions
 */

print '<div class="tabsAction">';

if ($conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
{
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$fgroup->id.'&amp;action=dolibarr2ldap">'.$langs->trans("ForceSynchronize").'</a>';
}

print "</div>\n";

if ($conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap') print "<br>\n";



// Affichage attributs LDAP
print_titre($langs->trans("LDAPInformationsForThisGroup"));

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
	$info=$fgroup->_load_ldap_info();
	$dn=$fgroup->_load_ldap_dn($info,1);
	$search = "(".$fgroup->_load_ldap_dn($info,2).")";
	$records = $ldap->getAttribute($dn,$search);

	//var_dump($records);

	// Affichage arbre
	if (count($records) && (! isset($records['count']) || $records['count'] > 0))
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

llxFooter('$Date: 2011/07/31 23:21:25 $ - $Revision: 1.26 $');
?>
