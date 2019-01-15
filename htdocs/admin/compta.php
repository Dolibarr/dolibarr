<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2013 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2013-2017 Philippe Grand	    <philippe.grand@atoo-net.com>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 *	\file       htdocs/admin/compta.php
 *	\ingroup    compta
 *	\brief      Page to setup accountancy module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'compta', 'accountancy'));

if (!$user->admin)
accessforbidden();

$action = GETPOST('action','alpha');

// Other parameters ACCOUNTING_*
$list = array(
    'ACCOUNTING_PRODUCT_BUY_ACCOUNT',
    'ACCOUNTING_PRODUCT_SOLD_ACCOUNT',
    'ACCOUNTING_SERVICE_BUY_ACCOUNT',
    'ACCOUNTING_SERVICE_SOLD_ACCOUNT',
    'ACCOUNTING_VAT_SOLD_ACCOUNT',
    'ACCOUNTING_VAT_BUY_ACCOUNT',
    'ACCOUNTING_ACCOUNT_CUSTOMER',
    'ACCOUNTING_ACCOUNT_SUPPLIER'
);

/*
 * Actions
 */

$accounting_mode = empty($conf->global->ACCOUNTING_MODE) ? 'RECETTES-DEPENSES' : $conf->global->ACCOUNTING_MODE;

if ($action == 'update')
{
    $error = 0;

    $accounting_modes = array(
        'RECETTES-DEPENSES',
        'CREANCES-DETTES'
    );

    $accounting_mode = GETPOST('accounting_mode','alpha');


    if (in_array($accounting_mode,$accounting_modes)) {

        if (!dolibarr_set_const($db, 'ACCOUNTING_MODE', $accounting_mode, 'chaine', 0, '', $conf->entity)) {
            $error++;
        }
    } else {
        $error++;
    }

    foreach ($list as $constname) {
        $constvalue = GETPOST($constname, 'alpha');

        if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
            $error++;
        }
    }

    if (! $error)
    {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

/*
 * View
 */

llxHeader();

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('ComptaSetup'),$linkback,'title_setup');

print '<br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder" width="100%">';

// case of the parameter ACCOUNTING_MODE

print '<tr class="liste_titre">';
print '<td>'.$langs->trans('OptionMode').'</td><td>'.$langs->trans('Description').'</td>';
print "</tr>\n";
print '<tr class="oddeven"><td width="200"><input type="radio" name="accounting_mode" value="RECETTES-DEPENSES"'.($accounting_mode != 'CREANCES-DETTES' ? ' checked' : '').'> '.$langs->trans('OptionModeTrue').'</td>';
print '<td colspan="2">'.nl2br($langs->trans('OptionModeTrueDesc'));
// Write info on way to count VAT
//if (! empty($conf->global->MAIN_MODULE_COMPTABILITE))
//{
//	//	print "<br>\n";
//	//	print nl2br($langs->trans('OptionModeTrueInfoModuleComptabilite'));
//}
//else
//{
//	//	print "<br>\n";
//	//	print nl2br($langs->trans('OptionModeTrueInfoExpert'));
//}
print "</td></tr>\n";
print '<tr class="oddeven"><td width="200"><input type="radio" name="accounting_mode" value="CREANCES-DETTES"'.($accounting_mode == 'CREANCES-DETTES' ? ' checked' : '').'> '.$langs->trans('OptionModeVirtual').'</td>';
print '<td colspan="2">'.nl2br($langs->trans('OptionModeVirtualDesc'))."</td></tr>\n";

print "</table>\n";

print "<br>\n";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans('OtherOptions').'</td>';
print "</tr>\n";


foreach ($list as $key)
{
	print '<tr class="oddeven value">';

	// Param
	$libelle = $langs->trans($key);
	print '<td><label for="'.$key.'">'.$libelle.'</label></td>';

	// Value
	print '<td>';
	print '<input type="text" size="20" id="'.$key.'" name="'.$key.'" value="'.$conf->global->$key.'">';
	print '</td></tr>';
}

print "</table>\n";

print '<br><br><div style="text-align:center"><input type="submit" class="button" value="'.$langs->trans('Modify').'" name="button"></div>';
print '</form>';

// End of page
llxFooter();
$db->close();
