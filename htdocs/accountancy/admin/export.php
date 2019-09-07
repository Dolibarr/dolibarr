<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2017 Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2014	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2017-2018 Frédéric France      <frederic.france@netlogic.fr>
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
 * \file 		htdocs/accountancy/admin/export.php
 * \ingroup 	Accountancy (Double entries)
 * \brief 		Setup page to configure accounting expert module
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancyexport.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta","bills","admin","accountancy"));

// Security access
if (empty($user->rights->accounting->chartofaccount))
{
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Parameters ACCOUNTING_EXPORT_*
$main_option = array(
    'ACCOUNTING_EXPORT_PREFIX_SPEC',
);

$configuration = AccountancyExport::getTypeConfig();

$listparam = $configuration['param'];

$listformat = $configuration['format'];

$listcr = $configuration['cr'];


$model_option = array (
    '1' => array(
        'label' => 'ACCOUNTING_EXPORT_FORMAT',
        'param' => $listformat,
    ),
    '2' => array(
        'label' => 'ACCOUNTING_EXPORT_SEPARATORCSV',
        'param' => '',
    ),
    '3' => array(
        'label' => 'ACCOUNTING_EXPORT_ENDLINE',
        'param' => $listcr,
    ),
    '4' => array(
        'label' => 'ACCOUNTING_EXPORT_DATE',
        'param' => '',
    ),
);


/*
 * Actions
 */

if ($action == 'update') {
	$error = 0;

	$modelcsv = GETPOST('ACCOUNTING_EXPORT_MODELCSV', 'int');

	if (! empty($modelcsv)) {
		if (! dolibarr_set_const($db, 'ACCOUNTING_EXPORT_MODELCSV', $modelcsv, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
		//if ($modelcsv==AccountancyExport::$EXPORT_TYPE_QUADRATUS || $modelcsv==AccountancyExport::$EXPORT_TYPE_CIEL) {
		//	dolibarr_set_const($db, 'ACCOUNTING_EXPORT_FORMAT', 'txt', 'chaine', 0, '', $conf->entity);
		//}
	} else {
		$error ++;
	}

	foreach ($main_option as $constname) {
		$constvalue = GETPOST($constname, 'alpha');

		if (! dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error ++;
		}
	}

    foreach ($listparam[$modelcsv] as $key => $value) {
        $constante = $key;

        if (strpos($constante, 'ACCOUNTING')!==false) {
            $constvalue = GETPOST($key, 'alpha');
            if (! dolibarr_set_const($db, $constante, $constvalue, 'chaine', 0, '', $conf->entity)) {
                $error ++;
            }
        }
	}

	if (! $error) {
        // reload
        $configuration = AccountancyExport::getTypeConfig();
        $listparam = $configuration['param'];
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}



/*
 * View
 */

llxHeader();

$form = new Form($db);

// $linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ConfigAccountingExpert'), $linkback, 'title_setup');


print "\n".'<script type="text/javascript" language="javascript">'."\n";
print 'jQuery(document).ready(function () {'."\n";
print '    function initfields()'."\n";
print '    {'."\n";
foreach ($listparam as $key => $param) {
    print '        if (jQuery("#ACCOUNTING_EXPORT_MODELCSV").val()=="'.$key.'")'."\n";
    print '        {'."\n";
    print '            //console.log("'.$param['label'].'");'."\n";
    if (empty($param['ACCOUNTING_EXPORT_FORMAT'])) {
        print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").val("'.$conf->global->ACCOUNTING_EXPORT_FORMAT.'");'."\n";
        print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").prop("disabled", true);'."\n";
    } else {
        print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").val("'.$param['ACCOUNTING_EXPORT_FORMAT'].'");'."\n";
        print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").removeAttr("disabled");'."\n";
    }
    if (empty($param['ACCOUNTING_EXPORT_SEPARATORCSV'])) {
        print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").val("");'."\n";
        print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").prop("disabled", true);'."\n";
    } else {
        print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").val("'.$conf->global->ACCOUNTING_EXPORT_SEPARATORCSV.'");'."\n";
        print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").removeAttr("disabled");'."\n";
    }
    if (empty($param['ACCOUNTING_EXPORT_ENDLINE'])) {
        print '            jQuery("#ACCOUNTING_EXPORT_ENDLINE").prop("disabled", true);'."\n";
    } else {
        print '            jQuery("#ACCOUNTING_EXPORT_ENDLINE").removeAttr("disabled");'."\n";
    }
    if (empty($param['ACCOUNTING_EXPORT_DATE'])) {
        print '            jQuery("#ACCOUNTING_EXPORT_DATE").val("");'."\n";
        print '            jQuery("#ACCOUNTING_EXPORT_DATE").prop("disabled", true);'."\n";
    } else {
        print '            jQuery("#ACCOUNTING_EXPORT_DATE").val("'.$conf->global->ACCOUNTING_EXPORT_DATE.'");'."\n";
        print '            jQuery("#ACCOUNTING_EXPORT_DATE").removeAttr("disabled");'."\n";
    }
    print '        }'."\n";
}
print '    }'."\n";
print '    initfields();'."\n";
print '    jQuery("#ACCOUNTING_EXPORT_MODELCSV").change(function() {'."\n";
print '        initfields();'."\n";
print '    });'."\n";
print '})'."\n";
print '</script>'."\n";

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="post">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="update">';

/*
 * Main Options
 */

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans('Options') . '</td>';
print "</tr>\n";

$num = count($main_option);
if ($num) {
	foreach ($main_option as $key) {

		print '<tr class="oddeven value">';

		// Param
		$label = $langs->trans($key);
		print '<td width="50%">' . $label . '</td>';

		// Value
		print '<td>';
		print '<input type="text" size="20" id="'.$key.'" name="' . $key . '" value="' . $conf->global->$key . '">';
		print '</td></tr>';
	}
}

print "</table>\n";

print "<br>\n";

/*
 * Export model
 */
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans("Modelcsv") . '</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td width="50%">' . $langs->trans("Selectmodelcsv") . '</td>';
if (! $conf->use_javascript_ajax) {
	print '<td class="nowrap">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print "</td>";
} else {
	print '<td>';
	$listmodelcsv = AccountancyExport::getType();
	print $form->selectarray("ACCOUNTING_EXPORT_MODELCSV", $listmodelcsv, $conf->global->ACCOUNTING_EXPORT_MODELCSV, 0, 0, 0, '', 0, 0, 0, '', '', 1);

	print '</td>';
}
print "</td></tr>";
print "</table>";

print "<br>\n";

/*
 *  Parameters
 */

$num2 = count($model_option);
if ($num2) {
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">' . $langs->trans('OtherOptions') . '</td>';
	print "</tr>\n";

	foreach ($model_option as $key) {
		print '<tr class="oddeven value">';

        // Param
        $label = $key['label'];
		print '<td width="50%">' . $langs->trans($label) . '</td>';

		// Value
        print '<td>';
        if (is_array($key['param'])) {
            print $form->selectarray($label, $key['param'], $conf->global->$label, 0);
        } else {
            print '<input type="text" size="20" id="'. $label .'" name="' . $key['label'] . '" value="' . $conf->global->$label . '">';
        }

		print '</td></tr>';
	}

	print "</table>\n";
}

print '<div class="center"><input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('Modify')) . '" name="button"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
