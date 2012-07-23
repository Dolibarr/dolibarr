<?php
/* Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *      \file       /htdocs/admin/commissions.php
 *		\ingroup    commissions
 *		\brief      Page to setup advanced commissions module
 */

$res=@include("../main.inc.php");					// For root directory

require_once(DOL_DOCUMENT_ROOT."/commissions/lib/commissions.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("commissions");

if (!$user->admin)
accessforbidden();

// init
if ($conf->global->COMMISSION_BASE == "") {
  if ($conf->marges->enabled)
    $conf->global->COMMISSION_BASE = "MARGES";
  else
    $conf->global->COMMISSION_BASE = "CA";
}

/*
 * Action
 */
if (isset($_POST['commissionBase']))
{
    if (dolibarr_set_const($db, 'COMMISSION_BASE', $_POST['commissionBase'], 'string', 0, '', $conf->entity) > 0)
    {
          $conf->global->COMMISSION_BASE = $_POST['commissionBase'];
    }
    else
    {
        dol_print_error($db);
    }
}

if (isset($_POST['productCommissionRate']))
{
    if (dolibarr_set_const($db, 'PRODUCT_COMMISSION_RATE', $_POST['productCommissionRate'], 'rate', 0, '', $conf->entity) > 0)
    {
    }
    else
    {
        dol_print_error($db);
    }
}

if (isset($_POST['serviceCommissionRate']))
{
    if (dolibarr_set_const($db, 'SERVICE_COMMISSION_RATE', $_POST['serviceCommissionRate'], 'rate', 0, '', $conf->entity) > 0)
    {
    }
    else
    {
        dol_print_error($db);
    }
}


/*
 * View
 */

llxHeader('',$langs->trans("CommissionsSetup"));


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("commissionsSetup"),$linkback,'setup');


$head = commissions_admin_prepare_head($adh);

dol_fiche_head($head, 'parameters', $langs->trans("Commissions"), 0, 'commissions');

print "<br>";


print_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="left">'.$langs->trans("Value").'</td>'."\n";
print '<td align="left">'.$langs->trans("Details").'</td>'."\n";
print '</tr>';

$var=true;
$form = new Form($db);

print '<form method="post">';

// COMMISSION BASE (CA / MARGES)
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("CommissionBase").'</td>';
print '<td align="left">';
print '<input type="radio" name="commissionBase" value="CA" ';
if ($conf->global->COMMISSION_BASE == "CA")
  print 'checked';
print ' />';
print $langs->trans("CommissionBasedOnCA");
print '<br/>';
print '<input type="radio" name="commissionBase" value="MARGES" ';
if (!$conf->marges->enabled)
  print 'disabled';
elseif ($conf->global->COMMISSION_BASE == "MARGES")
  print 'checked';
print ' />';
print $langs->trans("CommissionBasedOnMargins");
print '</td>';
print '<td>'.$langs->trans('CommissionBaseDetails');
print '<br/>';
print $langs->trans('CommissionBasedOnMarginsDetails');
print '</td>';
print '</tr>';

// PRODUCT COMMISSION RATE
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ProductCommissionRate").'</td>';
print '<td align="left">';
print '<input type="text" name="productCommissionRate" value="'.$conf->global->PRODUCT_COMMISSION_RATE.'" size=6 />&nbsp; %';
print '</td>';
print '<td>'.$langs->trans('ProductCommissionRateDetails').'</td>';
print '</tr>';

// SERVICE COMMISSION RATE
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ServiceCommissionRate").'</td>';
print '<td align="left">';
print '<input type="text" name="serviceCommissionRate" value="'.$conf->global->SERVICE_COMMISSION_RATE.'" size=6 />&nbsp; %';
print '</td>';
print '<td>'.$langs->trans('ServiceCommissionRateDetails').'</td>';
print '</tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td align="center" colspan="3">';
print '<input type="submit" />';
print '</td>';
print '</tr>';


print '</table>';
print '<br>';

print '</form>';

$db->close();

llxFooter('$Date: 2011/07/31 22:23:21 $ - $Revision: 1.6 $');
?>
