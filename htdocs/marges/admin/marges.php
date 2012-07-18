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
 *      \file       /marges/admin/marges.php
 *		\ingroup    marges
 *		\brief      Page to setup advanced extra fields module
 *		\version    $Id:$
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once("../lib/marges.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("marges@marges");

if (!$user->admin)
accessforbidden();


/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_set_const($db, $code, 1, 'yesno', 0, '', $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if (preg_match('/del_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if ($_POST["action"] == 'remises')
{
    if (dolibarr_set_const($db, 'MARGIN_METHODE_FOR_DISCOUNT', $_POST['MARGIN_METHODE_FOR_DISCOUNT'], 'chaine', 0, '', $conf->entity) > 0)
    {
          $conf->global->MARGIN_METHODE_FOR_DISCOUNT = $_POST['MARGIN_METHODE_FOR_DISCOUNT'];
    }
    else
    {
        dol_print_error($db);
    }
}

if ($_POST["action"] == 'typemarges')
{
    if (dolibarr_set_const($db, 'MARGIN_TYPE', $_POST['MARGIN_TYPE'], 'chaine', 0, '', $conf->entity) > 0)
    {
          $conf->global->MARGIN_METHODE_FOR_DISCOUNT = $_POST['MARGIN_TYPE'];
    }
    else
    {
        dol_print_error($db);
    }
}

/*
 * View
 */

llxHeader('',$langs->trans("margesSetup"));


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("margesSetup"),$linkback,'setup');


$head = marges_admin_prepare_head($adh);

dol_fiche_head($head, 'parameters', $langs->trans("marges"), 0, 'company');

print "<br>";


print_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width=300>'.$langs->trans("Description").'</td>';

print '<td colspan="2" align="center">'.$langs->trans("Value").'</td>'."\n";
print '<td align="left">'.$langs->trans("Details").'</td>'."\n";
print '</tr>';

$var=true;
$form = new Form($db);
																							
// GLOBAL DISCOUNT MANAGEMENT
$var=!$var;
print "<form method=\"post\" action=\"marges.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"typemarges\">";
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("MARGIN_TYPE").'</td>';
print '<td align="right">'.$langs->trans('MargeBrute');
print ' <input type="radio" name="MARGIN_TYPE" value="1" ';
if ($conf->global->MARGIN_TYPE == '1')
	print 'checked ';
print '/><br/>'.$langs->trans('MargeNette');
print ' <input type="radio" name="MARGIN_TYPE" value="2" ';
if ($conf->global->MARGIN_TYPE == '2')
	print 'checked ';
print '/>';
print '</td>';
print '<td>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '<td>'.$langs->trans('MARGIN_TYPE_DETAILS').'</td>';
print '</tr>';
print '</form>';

// DISPLAY MARGIN RATES
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DisplayMarginRates").'</td>';


print '<td colspan="2" align="center">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('DISPLAY_MARGIN_RATES');
}
else
{
	if($conf->global->DISPLAY_MARGIN_RATES == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_DISPLAY_MARGIN_RATES">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->DISPLAY_MARGIN_RATES == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_DISPLAY_MARGIN_RATES">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td>';
print '<td>'.$langs->trans('MarginRate').' = '.$langs->trans('Margin').' / '.$langs->trans('BuyingPrice').'</td>';
print '</tr>';

// DISPLAY MARK RATES
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("DisplayMarkRates").'</td>';


print '<td colspan="2" align="center">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('DISPLAY_MARK_RATES');
}
else
{
	if($conf->global->DISPLAY_MARK_RATES == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_DISPLAY_MARK_RATES">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->DISPLAY_MARK_RATES == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_DISPLAY_MARK_RATES">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td>';
print '<td>'.$langs->trans('MarkRate').' = '.$langs->trans('Margin').' / '.$langs->trans('SellingPrice').'</td>';
print '</tr>';
print '<tr>';
print '<td>'.$langs->trans("ForceBuyingPriceIfNull").'</td>';

print '<td colspan="2" align="center">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('ForceBuyingPriceIfNull');
}
else
{
	if($conf->global->ForceBuyingPriceIfNull == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ForceBuyingPriceIfNull">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->ForceBuyingPriceIfNull == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ForceBuyingPriceIfNull">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td>';
print '<td>'.$langs->trans('ForceBuyingPriceIfNullDetails').'</td>';
print '</tr>';

// GLOBAL DISCOUNT MANAGEMENT
$var=!$var;
print "<form method=\"post\" action=\"marges.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"remises\">";
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("MARGIN_METHODE_FOR_DISCOUNT").'</td>';
print '<td align="left">';
print '<select name="MARGIN_METHODE_FOR_DISCOUNT" >';
print '<option value="1" ';
if ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '1')
	print 'selected ';
print '>'.$langs->trans('UseDiscountAsProduct').'</option>';
print '<option value="2" ';
if ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '2')
	print 'selected ';
print '>'.$langs->trans('UseDiscountAsService').'</option>';
print '<option value="3" ';
if ($conf->global->MARGIN_METHODE_FOR_DISCOUNT == '3')
	print 'selected ';
print '>'.$langs->trans('UseDiscountOnTotal').'</option>';
print '</select>';
print '</td>';
print '<td>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '<td>'.$langs->trans('MARGIN_METHODE_FOR_DISCOUNT_DETAILS').'</td>';
print '</tr>';
print '</form>';

print '</table>';
print '<br>';



$db->close();

llxFooter('$Date: 2011/07/31 22:23:21 $ - $Revision: 1.6 $');
?>
