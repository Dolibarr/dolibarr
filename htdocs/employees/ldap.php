<?php
/* Copyright (C) 2006       Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006       Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014  Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
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
 *       \file       htdocs/employees/ldap.php
 *       \ingroup    ldap employee
 *       \brief      Page fiche LDAP employee
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ldap.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee_type.class.php';

$langs->load("companies");
$langs->load("employees");
$langs->load("ldap");
$langs->load("admin");

$rowid = GETPOST('id','int');
$action = GETPOST('action');

// Protection
$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

$adh = new Employee($db);
$adh->id = $rowid;
$result=$adh->fetch($rowid);
if (! $result)
{
	dol_print_error($db,"Failed to get employee: ".$emp->error);
	exit;
}


/*
 * Actions
 */

if ($action == 'dolibarr2ldap')
{
	$message="";

	$db->begin();

	$ldap=new Ldap();
	$result=$ldap->connect_bind();

	$info=$emp->_load_ldap_info();
	$dn=$emp->_load_ldap_dn($info);
	$olddn=$dn;	// We can say that old dn = dn as we force synchro

	$result=$ldap->update($dn,$info,$user,$olddn);

	if ($result >= 0)
	{
		$message.='<div class="ok">'.$langs->trans("EmployeeSynchronized").'</div>';
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

llxHeader('',$langs->trans("Employee"),'EN:Module_Employees|FR:Module_Salariés|ES:M&oacute;dulo_Asalariados');

$form = new Form($db);

$head = employee_prepare_head($emp);

dol_fiche_head($head, 'ldap', $langs->trans("Employee"), 0, 'user');


print '<table class="border" width="100%">';

// Ref
print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
print '<td class="valeur">';
print $form->showrefnav($emp,'id');
print '</td></tr>';

// Lastname
print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$emp->lastname.'&nbsp;</td>';
print '</tr>';

// Firstname
print '<tr><td width="15%">'.$langs->trans("Firstname").'</td><td class="valeur">'.$emp->firstname.'&nbsp;</td>';
print '</tr>';

// Login
print '<tr><td>'.$langs->trans("Login").'</td><td class="valeur">'.$emp->login.'&nbsp;</td></tr>';

// Password not crypted
if (! empty($conf->global->LDAP_EMPLOYEE_FIELD_PASSWORD))
{
	print '<tr><td>'.$langs->trans("LDAPFieldPasswordNotCrypted").'</td>';
	print '<td class="valeur">'.$emp->pass.'</td>';
	print "</tr>\n";
}

// Password crypted
if (! empty($conf->global->LDAP_EMPLOYEE_FIELD_PASSWORD_CRYPTED))
{
	print '<tr><td>'.$langs->trans("LDAPFieldPasswordCrypted").'</td>';
	print '<td class="valeur">'.$emp->pass_crypted.'</td>';
	print "</tr>\n";
}

// Type
print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$emp->type."</td></tr>\n";

$langs->load("admin");

// LDAP DN
print '<tr><td>LDAP '.$langs->trans("LDAPEmployeeDn").'</td><td class="valeur">'.$conf->global->LDAP_EMPLOYEE_DN."</td></tr>\n";

// LDAP Cle
print '<tr><td>LDAP '.$langs->trans("LDAPNamingAttribute").'</td><td class="valeur">'.$conf->global->LDAP_KEY_EMPLOYEES."</td></tr>\n";

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

if (! empty($conf->global->LDAP_EMPLOYEE_ACTIVE) && $conf->global->LDAP_EMPLOYEE_ACTIVE != 'ldap2dolibarr')
{
	print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$emp->id.'&amp;action=dolibarr2ldap">'.$langs->trans("ForceSynchronize").'</a></div>';
}

print "</div>\n";

if (! empty($conf->global->LDAP_EMPLOYEE_ACTIVE) && $conf->global->LDAP_EMPLOYEE_ACTIVE != 'ldap2dolibarr') print "<br>\n";



// Affichage attributs LDAP
print_titre($langs->trans("LDAPInformationsForThisEmployee"));

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
	$info=$emp->_load_ldap_info();
	$dn=$emp->_load_ldap_dn($info,1);
	$search = "(".$emp->_load_ldap_dn($info,2).")";

	if (empty($dn))
	{
	    $langs->load("errors");
	    print '<tr '.$bc[false].'><td colspan="2"><font class="error">'.$langs->trans("ErrorModuleSetupNotComplete").'</font></td></tr>';
	}
    else
    {
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
    }

	$ldap->unbind();
	$ldap->close();
}
else
{
	dol_print_error('',$ldap->error);
}


print '</table>';


llxFooter();

$db->close();
?>
