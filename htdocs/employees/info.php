<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/employees/info.php
 *      \ingroup    employee
 *		  \brief      Page d'informations d'un salarié
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("employees");
$langs->load("users");

$id=(GETPOST('id','int') ? GETPOST('id','int') : GETPOST('rowid','int'));

// Security check
$result=restrictedArea($user,'employee',$id);


/*
 * View
 */

llxHeader('',$langs->trans("Member"),'EN:Module_Employees|FR:Module_Salariés|ES:M&oacute;dulo_Asalariados');

$emp = new Employee($db);
$emp->fetch($id);
$emp->info($id);

$head = employee_prepare_head($emp);

dol_fiche_head($head, 'info', $langs->trans("Employee"), 0, 'user');


print '<table width="100%"><tr><td>';
dol_print_object_info($emp);
print '</td></tr></table>';

print '</div>';


llxFooter();
$db->close();
?>
