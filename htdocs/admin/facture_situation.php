<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2012-2013  Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2014		Teddy Andreotti				<125155@supinfo.com>
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
 *      \file       htdocs/admin/facture.php
 *		\ingroup    facture
 *		\brief      Page to setup invoice module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'errors', 'other', 'bills'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type='invoice';


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';



/*
 * View
 */

$dirmodels=array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader(
    "", $langs->trans("BillsSetup"),
    'EN:Invoice_Configuration|FR:Configuration_module_facture|ES:ConfiguracionFactura'
);

$form=new Form($db);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("BillsSetup"), $linkback, 'title_setup');

$head = invoice_admin_prepare_head();
dol_fiche_head($head, 'situation', $langs->trans("InvoiceSituation"), -1, 'invoice');


print '<span class="opacitymedium">'.$langs->trans("InvoiceFirstSituationDesc").'</span><br><br>';


/*
 *  Numbering module
 */

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';


print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60">'.$langs->trans("Value").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

_printOnOff('INVOICE_USE_SITUATION', $langs->trans('UseSituationInvoices'));
_printOnOff('INVOICE_USE_SITUATION_CREDIT_NOTE', $langs->trans('UseSituationInvoicesCreditNote'));
_printOnOff('INVOICE_USE_SITUATION_RETAINED_WARRANTY', $langs->trans('Retainedwarranty'));

$metas = array(
    'type' => 'number',
    'step' => '0.01',
    'min' => 0,
    'max' => 100
);
_printInputFormPart('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_PERCENT', $langs->trans('RetainedwarrantyDefaultPercent'), '', $metas);

// Conditions paiements
$inputCount = empty($inputCount)?1:($inputCount+1);
print '<tr class="oddeven">';
print '<td>'.$langs->trans('PaymentConditionsShortRetainedWarranty').'</td>';
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="right" width="300">';
print '<input type="hidden" name="param'.$inputCount.'" value="INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID">';
$form->select_conditions_paiements($conf->global->INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_COND_ID, 'value'.$inputCount, -1, 1);
print '</td></tr>';


print '</table>';
print '</div>';

print '<br>';

_updateBtn();

print '</form>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();

/**
 * Print an update button
 *
 * @return void
 */
function _updateBtn()
{
    global $langs;
    print '<div class="center">';
    print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
    print '</div>';
}

/**
 * Print a On/Off button
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description
 *
 * @return void
 */
function _printOnOff($confkey, $title = false, $desc = '')
{
    global $langs;

    print '<tr class="oddeven">';
    print '<td>'.($title?$title:$langs->trans($confkey));
    if (!empty($desc)) {
        print '<br><small>'.$langs->trans($desc).'</small>';
    }
    print '</td>';
    print '<td class="center" width="20">&nbsp;</td>';
    print '<td class="right" width="300">';
    print ajax_constantonoff($confkey);
    print '</td></tr>';
}


/**
 * Print a form part
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description of
 * @param array  $metas   html meta
 * @param string $type    type of input textarea or input
 * @param bool   $help    help description
 *
 * @return void
 */
function _printInputFormPart($confkey, $title = false, $desc = '', $metas = array(), $type = 'input', $help = false)
{
    global $langs, $conf, $db, $inputCount;

    $inputCount = empty($inputCount)?1:($inputCount+1);
    $form=new Form($db);

    $defaultMetas = array(
        'name' => 'value'.$inputCount
    );

    if ($type!='textarea') {
        $defaultMetas['type']   = 'text';
        $defaultMetas['value']  = $conf->global->{$confkey};
    }


    $metas = array_merge($defaultMetas, $metas);
    $metascompil = '';
    foreach ($metas as $key => $values) {
        $metascompil .= ' '.$key.'="'.$values.'" ';
    }

    print '<tr class="oddeven">';
    print '<td>';

    if (!empty($help)) {
        print $form->textwithtooltip(($title?$title:$langs->trans($confkey)), $langs->trans($help), 2, 1, img_help(1, ''));
    } else {
        print $title?$title:$langs->trans($confkey);
    }

    if (!empty($desc)) {
        print '<br><small>'.$langs->trans($desc).'</small>';
    }

    print '</td>';
    print '<td class="center" width="20">&nbsp;</td>';
    print '<td class="right" width="300">';
    print '<input type="hidden" name="param'.$inputCount.'" value="'.$confkey.'">';

    print '<input type="hidden" name="action" value="setModuleOptions">';
    if ($type=='textarea') {
        print '<textarea '.$metascompil.'  >'.dol_htmlentities($conf->global->{$confkey}).'</textarea>';
    } else {
        print '<input '.$metascompil.'  />';
    }
    print '</td></tr>';
}
