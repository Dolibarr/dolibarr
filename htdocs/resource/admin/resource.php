<?php
/* Copyright (C) 2015      Ion Agorria          <ion@agorria.com>
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
 *  \file       htdocs/resource/admin/resource.php
 *  \ingroup    resource
 *  \brief      Setup page of resource module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';

$langs->load("admin");
$langs->load("resource");

// Security check
if (! $user->admin || empty($conf->resource->enabled))
{
	accessforbidden();
}

/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0)
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

/*
 * View
 */

$title=$langs->trans("ResourceConfiguration");
llxHeader("",$title,"");

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title,$linkback,'title_setup');

$head=resource_admin_prepare_head();
dol_fiche_head($head,'card', $langs->trans("MenuResourceIndex"), 0, 'resource');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

$var=true;
$form = new Form($db);

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ResourceOccupationEnable").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('RESOURCE_OCCUPATION');
}
else
{
	if (empty($conf->global->RESOURCE_OCCUPATION))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_RESOURCE_OCCUPATION">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_RESOURCE_OCCUPATION">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("ResourceOccupationByQty").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">';
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('RESOURCE_OCCUPATION_BY_QTY');
}
else
{
	if (empty($conf->global->RESOURCE_OCCUPATION_BY_QTY))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_RESOURCE_OCCUPATION_BY_QTY">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_RESOURCE_OCCUPATION_BY_QTY">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>';

print '</table>';

llxFooter();
$db->close();