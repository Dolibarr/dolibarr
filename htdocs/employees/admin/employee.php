<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent		    <jmenent@2byte.es>
 * Copyright (C) 2012      J. Fernando Lagrange <fernando@demo-tic.org>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
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
 *   	\file       htdocs/employees/admin/employee.php
 *		\ingroup    employee
 *		\brief      Page to setup the module Employee
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';

$langs->load("admin");
$langs->load("employees");

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST('action','alpha');


/*
 * Actions
 */

// Action mise a jour ou ajout d'une constante
if ($action == 'update' || $action == 'add')
{
	$constname=GETPOST('constname','alpha');
	$constvalue=(GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));

	if (($constname=='EMPLOYEE_CARD_TYPE' || $constname=='ADHERENT_ETIQUETTE_TYPE') && $constvalue == -1) $constvalue='';
	if ($constname=='EMPLOYEE_LOGIN_NOT_REQUIRED') // Invert choice
	{
		if ($constvalue) $constvalue=0;
		else $constvalue=1;
	}

	$consttype=GETPOST('consttype','alpha');
	$constnote=GETPOST('constnote');
	$res=dolibarr_set_const($db,$constname,$constvalue,$type[$consttype],0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		$mesg = '<div class="ok">'.$langs->trans("SetupSaved").'</div>';
	}
	else
	{
		$mesg = '<div class="error">'.$langs->trans("Error").'</div>';
	}
}

// Action activation d'un sous module du module employee
if ($action == 'set')
{
    $result=dolibarr_set_const($db, GETPOST('name','alpha'),GETPOST('value'),'',0,'',$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}

// Action desactivation d'un sous module du module employee
if ($action == 'unset')
{
    $result=dolibarr_del_const($db,GETPOST('name','alpha'),$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}



/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Employees|FR:Module_Salariés|ES:M&oacute;dulo_Asalariados';

llxHeader('',$langs->trans("EmployeesSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("EmployeesSetup"),$linkback,'setup');


$head = employee_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("Employee"), 0, 'user');


dol_htmloutput_mesg($mesg);


print_fiche_titre($langs->trans("EmployeeMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";
$var=true;

// Login/Pass required for employees
$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="EMPLOYEE_LOGIN_NOT_REQUIRED">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("EmployeeLoginRequired").'</td><td>';
print $form->selectyesno('constvalue',(! empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED)?0:1),1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Mail required for employees
$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="EMPLOYEE_MAIL_REQUIRED">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("EmployeeMailRequired").'</td><td>';
print $form->selectyesno('constvalue',(! empty($conf->global->EMPLOYEE_MAIL_REQUIRED)?$conf->global->EMPLOYEE_MAIL_REQUIRED:0),1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Send mail information is off by default
$var=!$var;
print '<form action="employee.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="EMPLOYEE_DEFAULT_SENDINFOBYMAIL">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("EmployeeSendInformationByMailByDefault").'</td><td>';
print $form->selectyesno('constvalue',(! empty($conf->global->EMPLOYEE_DEFAULT_SENDINFOBYMAIL)?$conf->global->EMPLOYEE_DEFAULT_SENDINFOBYMAIL:0),1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

print '</table>';
print '<br>';


/*
 * Edition info modele document
 */
$constantes=array(
		'EMPLOYEE_CARD_TYPE',
		'EMPLOYEE_CARD_HEADER_TEXT',
		'EMPLOYEE_CARD_TEXT',
		'EMPLOYEE_CARD_TEXT_RIGHT',
		'EMPLOYEE_CARD_FOOTER_TEXT'
		);

print_fiche_titre($langs->trans("EmployeesCards"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
print '%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %SEX%, %PHOTO%, %TYPE%, ';
print '%YEAR%, %MONTH%, %DAY%';
print '<br>';

print '<br>';


/*
 * Edition info modele document
 */
$constantes=array('EMPLOYEE_ETIQUETTE_TYPE','EMPLOYEE_ETIQUETTE_TEXT');

print_fiche_titre($langs->trans("EmployeesTickets"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
print '%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %SEX%, %PHOTO%, %TYPE%, ';
print '%YEAR%, %MONTH%, %DAY%';
print '<br>';

print '<br>';


/*
 * Editing global variables not related to a specific theme
 */
$constantes=array(
		'EMPLOYEE_AUTOREGISTER_NOTIF_MAIL_SUBJECT',
		'EMPLOYEE_AUTOREGISTER_NOTIF_MAIL',
		'EMPLOYEE_AUTOREGISTER_MAIL_SUBJECT',
		'EMPLOYEE_AUTOREGISTER_MAIL',
		'EMPLOYEE_MAIL_VALID_SUBJECT',
		'EMPLOYEE_MAIL_VALID',
		'EMPLOYEE_MAIL_FROM',
		);

print_fiche_titre($langs->trans("Other"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
print '%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %SEX%, %PHOTO%, %TYPE%, ';
//print '%YEAR%, %MONTH%, %DAY%';	// Not supported
print '<br>';

dol_fiche_end();


llxFooter();

$db->close();
?>
