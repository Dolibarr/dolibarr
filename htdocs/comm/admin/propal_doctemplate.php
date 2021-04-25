<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2021		Anthony Berton			<bertonanthony@gmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/comm/admin/propal_extrafields.php
 *		\ingroup    propal
 *		\brief      Page to setup extra fields of third party
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'admin', 'propal'));

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label = ExtraFields::$type2label;
$type2label = array('');
foreach ($tmptype2label as $key => $val) {
	$type2label[$key] = $langs->transnoentitiesnoconv($val);
}

$action = GETPOST('action', 'aZ09');
$attrname = GETPOST('attrname', 'alpha');
$elementtype = 'propal'; //Must be the $table_element of the class that manage extrafield

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */




/*
 * View
 */

$textobject = $langs->transnoentitiesnoconv("Proposals");

llxHeader('', $langs->trans("PropalSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("PropalSetup"), $linkback, 'title_setup');


$head = propal_admin_prepare_head();

print dol_get_fiche_head($head, 'doctemplateoption', $langs->trans("Proposals"), -1, 'propal');

print load_fiche_titre($langs->trans("doctemplateoption"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '</tr>'."\n";

// Picture in line
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AddPDFPictureInline").'</td>';
print '<td>'.$langs->trans("AddPDFPictureInlineDescription").'</td>';
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_GENERATE_PROPOSALS_WITH_PICTURE');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MAIN_GENERATE_PROPOSALS_WITH_PICTURE", $arrval, $conf->global->MAIN_GENERATE_PROPOSALS_WITH_PICTURE);
}
print '</td>';
print '</tr>';

//Signature
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AddPDFElectronicSigning").'</td>';
print '<td>'.$langs->trans("AddPDFElectronicSigningDescription").'</td>';
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING", $arrval, $conf->global->MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING);
}
print '</td>';
print '</tr>';
print '</table>';

print dol_get_fiche_end();
// End of page
llxFooter();
$db->close();
