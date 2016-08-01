<?php
/* Copyright (C) 2005-2015  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015		Charlie BENKE		 <charlie@patas-monkey.com>
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
 *	\file       htdocs/compta/salaries/info.php
 *	\ingroup    salaries
 *	\brief      Page with info about salaries contribution
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/salaries.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("compta");
$langs->load("bills");
$langs->load("salaries");

$id=GETPOST('id','int');
$action=GETPOST("action");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'salaries', '', '', '');


/*
 * View
 */

llxHeader("",$langs->trans("SalaryPayment"));

$salpayment = new PaymentSalary($db);
$result = $salpayment->fetch($id);
$salpayment->info($id);

$head = salaries_prepare_head($salpayment);

dol_fiche_head($head, 'info', $langs->trans("SalaryPayment"), 0, 'payment');


print '<table width="100%"><tr><td>';
dol_print_object_info($salpayment);
print '</td></tr></table>';

print '</div>';

llxFooter();

$db->close();
