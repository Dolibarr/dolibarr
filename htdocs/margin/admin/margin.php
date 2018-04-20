<?php
/* Copyright (C) 2012	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2016	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *      \file       /htdocs/margin/admin/margin.php
 *		\ingroup    margin
 *		\brief      Page to setup margin module
 */

include '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");

$langs->load("admin");
$langs->load("bills");
$langs->load("margins");
$langs->load("stocks");

if (! $user->admin) accessforbidden();

$action=GETPOST('action','alpha');


/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_set_const($db, $code, 1, 'yesno', 0, '', $conf->entity) > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
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
        header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if ($action == 'remises')
{
    if (dolibarr_set_const($db, 'MARGIN_METHODE_FOR_DISCOUNT', $_POST['MARGIN_METHODE_FOR_DISCOUNT'], 'chaine', 0, '', $conf->entity) > 0)
    {
          setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
    }
    else
    {
        dol_print_error($db);
    }
}

if ($action == 'typemarges')
{
    if (dolibarr_set_const($db, 'MARGIN_TYPE', $_POST['MARGIN_TYPE'], 'chaine', 0, '', $conf->entity) > 0)
    {
          setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
    }
    else
    {
        dol_print_error($db);
    }
}

if ($action == 'contact')
{
    if (dolibarr_set_const($db, 'AGENT_CONTACT_TYPE', $_POST['AGENT_CONTACT_TYPE'], 'chaine', 0, '', $conf->entity) > 0)
    {
          setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
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


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("margesSetup"),$linkback,'title_setup');


$head = marges_admin_prepare_head();

dol_fiche_head($head, 'parameters', $langs->trans("Margins"), -1, 'margin');

print load_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width=300>'.$langs->trans("Description").'</td>';
print '<td colspan="2" align="center">'.$langs->trans("Value").'</td>'."\n";
print '<td align="left">'.$langs->trans("Description").'</td>'."\n";
print '</tr>';

$var=true;
$form = new Form($db);

// GLOBAL DISCOUNT MANAGEMENT

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"typemarges\">";
print '<tr class="oddeven">';
print '<td>'.$langs->trans("MARGIN_TYPE").'</td>';
print '<td>';
print ' <input type="radio" name="MARGIN_TYPE" value="1" ';
if (isset($conf->global->MARGIN_TYPE) && $conf->global->MARGIN_TYPE == '1')
	print 'checked ';
print '/> ';
print $langs->trans('MargeType1');
print '<br>';
print ' <input type="radio" name="MARGIN_TYPE" value="pmp" ';
if (isset($conf->global->MARGIN_TYPE) && $conf->global->MARGIN_TYPE == 'pmp') print 'checked ';
print '/> ';
print $langs->trans('MargeType2');
print '<br>';
print ' <input type="radio" name="MARGIN_TYPE" value="costprice" ';
if (isset($conf->global->MARGIN_TYPE) && $conf->global->MARGIN_TYPE == 'costprice') print 'checked ';
print '/> ';
print $langs->trans('MargeType3');
print '</td>';
print '<td>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'" class="button">';
print '</td>';
print '<td>'.$langs->trans('MarginTypeDesc');
print '</td>';
print '</tr>';
print '</form>';

// DISPLAY MARGIN RATES

print '<tr class="oddeven">';
print '<td>'.$langs->trans("DisplayMarginRates").'</td>';
print '<td colspan="2" align="center">';
if (! empty($conf->use_javascript_ajax))
{
	print ajax_constantonoff('DISPLAY_MARGIN_RATES');
}
else
{
	if (empty($conf->global->DISPLAY_MARGIN_RATES))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_DISPLAY_MARGIN_RATES">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_DISPLAY_MARGIN_RATES">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td>';
print '<td>'.$langs->trans('MarginRate').' = '.$langs->trans('Margin').' / '.$langs->trans('BuyingPrice').'</td>';
print '</tr>';

// DISPLAY MARK RATES

print '<tr class="oddeven">';
print '<td>'.$langs->trans("DisplayMarkRates").'</td>';
print '<td colspan="2" align="center">';
if (! empty($conf->use_javascript_ajax))
{
	print ajax_constantonoff('DISPLAY_MARK_RATES');
}
else
{
	if (empty($conf->global->DISPLAY_MARK_RATES))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_DISPLAY_MARK_RATES">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_DISPLAY_MARK_RATES">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td>';
print '<td>'.$langs->trans('MarkRate').' = '.$langs->trans('Margin').' / '.$langs->trans('SellingPrice').'</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("ForceBuyingPriceIfNull").'</td>';
print '<td colspan="2" align="center">';
if (! empty($conf->use_javascript_ajax))
{
	print ajax_constantonoff('ForceBuyingPriceIfNull');
}
else
{
	if (empty($conf->global->ForceBuyingPriceIfNull))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ForceBuyingPriceIfNull">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ForceBuyingPriceIfNull">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td>';
print '<td>'.$langs->trans('ForceBuyingPriceIfNullDetails').'</td>';
print '</tr>';

// GLOBAL DISCOUNT MANAGEMENT
$methods = array(
	1 => $langs->trans('UseDiscountAsProduct'),
	2 => $langs->trans('UseDiscountAsService'),
	3 => $langs->trans('UseDiscountOnTotal')
);


print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"remises\">";
print '<tr class="oddeven">';
print '<td>'.$langs->trans("MARGIN_METHODE_FOR_DISCOUNT").'</td>';
print '<td align="left">';
print Form::selectarray('MARGIN_METHODE_FOR_DISCOUNT', $methods, $conf->global->MARGIN_METHODE_FOR_DISCOUNT);
print '</td>';
print '<td>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '<td>'.$langs->trans('MARGIN_METHODE_FOR_DISCOUNT_DETAILS').'</td>';
print '</tr>';
print '</form>';

// INTERNAL CONTACT TYPE USED AS COMMERCIAL AGENT

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"contact\">";
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AgentContactType").'</td>';
print '<td align="left">';
$formcompany = new FormCompany($db);
$facture = new Facture($db);
print $formcompany->selectTypeContact($facture, $conf->global->AGENT_CONTACT_TYPE, "AGENT_CONTACT_TYPE","internal","code",1);
print '</td>';
print '<td>';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '<td>'.$langs->trans('AgentContactTypeDetails').'</td>';
print '</tr>';
print '</form>';

print '</table>';

dol_fiche_end();

print '<br>';

llxFooter();
$db->close();
