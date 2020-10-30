<?php
/*
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

if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

print '<script type="text/javascript" language="javascript">
	$(document).ready(function() {

		// Click Function
		$(":button[name=addcontact]").click(function() {
				$(":hidden[name=action]").val("add");
				$("#find_customer").submit();
		});

		$(":button[name=loadfilter]").click(function() {
				$(":hidden[name=action]").val("loadfilter");
				$("#find_customer").submit();
		});

		$(":button[name=deletefilter]").click(function() {
				$(":hidden[name=action]").val("deletefilter");
				$("#find_customer").submit();
		});

		$(":button[name=savefilter]").click(function() {
				$(":hidden[name=action]").val("savefilter");
				$("#find_customer").submit();
		});

		$(":button[name=createfilter]").click(function() {
				$(":hidden[name=action]").val("createfilter");
				$("#find_customer").submit();
		});
	});
</script>';


print load_fiche_titre($langs->trans("AdvTgtTitle"));

print '<div class="tabBar">'."\n";
print '<form name="find_customer" id="find_customer" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'"  method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="">'."\n";
print '<table class="border centpercent">'."\n";

print '<tr>'."\n";
print '<td colspan="3" class="right">'."\n";

print '<input type="button" name="addcontact" id="addcontact" value="'.$langs->trans('AdvTgtAddContact').'" class="button"/>'."\n";

print '</td>'."\n";
print '</tr>'."\n";

print '<tr><td>'.$langs->trans('AdvTgtNameTemplate').'</td><td class="valignmiddle">';
if (!empty($template_id)) {
	$default_template = $template_id;
} else {
	$default_template = $advTarget->id;
}
print $formadvtargetemaling->selectAdvtargetemailingTemplate('template_id', $default_template, 0, $advTarget->type_element, 'valignmiddle');
print '<input type="button" name="loadfilter" id="loadfilter" value="'.$langs->trans('AdvTgtLoadFilter').'" class="button"/>';
print '<input type="button" name="deletefilter" id="deletefilter" value="'.$langs->trans('AdvTgtDeleteFilter').'" class="button"/>';
print '<input type="button" name="savefilter" id="savefilter" value="'.$langs->trans('AdvTgtSaveFilter').'" class="button"/>';
print '</td><td>'."\n";
print '</td></tr>'."\n";

print '<tr><td>'.$langs->trans('AdvTgtOrCreateNewFilter').'</td><td>';
print '<input type="text" name="template_name" id="template_name" value=""/>';
print '<input type="button" name="createfilter" id="createfilter" value="'.$langs->trans('AdvTgtCreateFilter').'" class="button"/>';
print '</td><td>'."\n";
print '</td></tr>'."\n";

print '<tr><td>'.$langs->trans('AdvTgtTypeOfIncude').'</td><td>';
print $form->selectarray('type_of_target', $advTarget->select_target_type, $array_query['type_of_target']);
print '</td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtTypeOfIncudeHelp"), 1, 'help');
print '</td></tr>'."\n";

// Customer name
print '<tr><td>'.$langs->trans('ThirdPartyName');
if (!empty($array_query['cust_name'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td><input type="text" name="cust_name" value="'.$array_query['cust_name'].'"/></td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
print '</td></tr>'."\n";

// Code Client
print '<tr><td>'.$langs->trans('CustomerCode');
if (!empty($array_query['cust_code'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td><input type="text" name="cust_code" value="'.$array_query['cust_code'].'"/></td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
print '</td></tr>'."\n";

// Address Client
print '<tr><td>'.$langs->trans('Address');
if (!empty($array_query['cust_adress'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td><input type="text" name="cust_adress" value="'.$array_query['cust_adress'].'"/></td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
print '</td></tr>'."\n";

// Zip Client
print '<tr><td>'.$langs->trans('Zip');
if (!empty($array_query['cust_zip'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td><input type="text" name="cust_zip" value="'.$array_query['cust_zip'].'"/></td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
print '</td></tr>'."\n";

// City Client
print '<tr><td>'.$langs->trans('Town');
if (!empty($array_query['cust_city'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td><input type="text" name="cust_city" value="'.$array_query['cust_city'].'"/></td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
print '</td></tr>'."\n";

// Customer Country
print '<tr><td>'.$langs->trans("Country");
if (!empty($array_query['cust_country'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>'."\n";
print $formadvtargetemaling->multiselectCountry('cust_country', $array_query['cust_country']);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// State Customer
print '<tr><td>'.$langs->trans('Status').' '.$langs->trans('ThirdParty');
if (!empty($array_query['cust_status'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>';
print $formadvtargetemaling->advMultiselectarray(
    'cust_status', array(
		'0' => $langs->trans('ActivityCeased'),
		'1' => $langs->trans('InActivity')
    ),
    $array_query['cust_status']
);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Mother Company
print '<tr><td>'.$langs->trans("Maison mère");
if (!empty($array_query['cust_mothercompany'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>'."\n";
print '<input type="text" name="cust_mothercompany" value="'.$array_query['cust_mothercompany'].'"/>';
print '</td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
print '</td></tr>'."\n";

// Prospect/Customer
$selected = $array_query['cust_typecust'];
print '<tr><td>'.$langs->trans('ProspectCustomer').' '.$langs->trans('ThirdParty');
if (!empty($array_query['cust_typecust'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>';
$options_array = array(
	2 => $langs->trans('Prospect'),
	3 => $langs->trans('ProspectCustomer'),
	1 => $langs->trans('Customer'),
	0 => $langs->trans('NorProspectNorCustomer')
);
print $formadvtargetemaling->advMultiselectarray('cust_typecust', $options_array, $array_query['cust_typecust']);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Prospection status
print '<tr><td>'.$langs->trans('ProspectLevel');
if (!empty($array_query['cust_prospect_status'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>';
print $formadvtargetemaling->multiselectProspectionStatus($array_query['cust_prospect_status'], 'cust_prospect_status', 1);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Prospection comm status
print '<tr><td>'.$langs->trans('StatusProsp');
if (!empty($array_query['cust_comm_status'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>';
print $formadvtargetemaling->advMultiselectarray('cust_comm_status', $advTarget->type_statuscommprospect, $array_query['cust_comm_status']);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Customer Type
print '<tr><td>'.$langs->trans("ThirdPartyType");
if (!empty($array_query['cust_typeent'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>'."\n";
print $formadvtargetemaling->advMultiselectarray('cust_typeent', $formcompany->typent_array(0, " AND id <> 0"), $array_query['cust_typeent']);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Staff number
print '<td>'.$langs->trans("Staff");
if (!empty($array_query['cust_effectif_id'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>';
print $formadvtargetemaling->advMultiselectarray("cust_effectif_id", $formcompany->effectif_array(0, " AND id <> 0"), $array_query['cust_effectif_id']);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Sales manager
print '<tr><td>'.$langs->trans("SalesRepresentatives");
if (!empty($array_query['cust_saleman'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>'."\n";
print $formadvtargetemaling->multiselectselectSalesRepresentatives('cust_saleman', $array_query['cust_saleman'], $user);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Customer Default Langauge
if (!empty($conf->global->MAIN_MULTILANGS)) {
	print '<tr><td>'.$langs->trans("DefaultLang");
	if (!empty($array_query['cust_language'])) {
		print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
	}
	print '</td><td>'."\n";
	print $formadvtargetemaling->multiselectselectLanguage('cust_language', $array_query['cust_language']);
	print '</td><td>'."\n";
	print '</td></tr>'."\n";
}

if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
	// Customer Categories
	print '<tr><td>'.$langs->trans("CustomersCategoryShort");
	if (!empty($array_query['cust_categ'])) {
		print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
	}
	print '</td><td>'."\n";
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, 'parent', null, null, 1);
	print $form->multiselectarray('cust_categ', $cate_arbo, GETPOST('cust_categ', 'array'), null, null, null, null, "90%");
	print '</td><td>'."\n";
	print '</td></tr>'."\n";
}

// Standard Extrafield feature
if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
	$socstatic = new Societe($db);
	$elementtype = $socstatic->table_element;
	// fetch optionals attributes and labels
	dol_include_once('/core/class/extrafields.class.php');
	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label($elementtype);
	foreach ($extrafields->attributes[$elementtype]['label'] as $key => $val) {
		if ($key != 'ts_nameextra' && $key != 'ts_payeur') {
			print '<tr><td>'.$extrafields->attributes[$elementtype]['label'][$key];
			if (!empty($array_query['options_'.$key]) || (is_array($array_query['options_'.$key]) && count($array_query['options_'.$key]) > 0)) {
				print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
			}
			print '</td><td>';
			if (($extrafields->attributes[$elementtype]['type'][$key] == 'varchar') || ($extrafields->attributes[$elementtype]['type'][$key] == 'text')) {
				print '<input type="text" name="options_'.$key.'"/></td><td>'."\n";
				print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'int') || ($extrafields->attributes[$elementtype]['type'][$key] == 'double')) {
				print $langs->trans("AdvTgtMinVal").'<input type="text" name="options'.$key.'_min"/>';
				print $langs->trans("AdvTgtMaxVal").'<input type="text" name="options'.$key.'_max"/>';
				print '</td><td>'."\n";
				print $form->textwithpicto('', $langs->trans("AdvTgtSearchIntHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'date') || ($extrafields->attributes[$elementtype]['type'][$key] == 'datetime')) {
				print '<table class="nobordernopadding"><tr>';
				print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
				print $form->selectDate('', 'options_'.$key.'_st_dt');
				print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
				print $form->selectDate('', 'options_'.$key.'_end_dt');
				print '</td></tr></table>';

				print '</td><td>'."\n";
				print $form->textwithpicto('', $langs->trans("AdvTgtSearchDtHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'boolean')) {
                print $form->selectarray(
                    'options_'.$key,
                    array(
						'' => '',
						'1' => $langs->trans('Yes'),
						'0' => $langs->trans('No')
                    ),
                    $array_query['options_'.$key]
                );
				print '</td><td>'."\n";
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'select')) {
				print $formadvtargetemaling->advMultiselectarray('options_'.$key, $extrafields->attributes[$key]['param']['options'], $array_query['options_'.$key]);
				print '</td><td>'."\n";
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'sellist')) {
				print $formadvtargetemaling->advMultiselectarraySelllist('options_'.$key, $extrafields->attributes[$key]['param']['options'], $array_query['options_'.$key]);
				print '</td><td>'."\n";
			} else {
				print '<table class="nobordernopadding"><tr>';
				print '<td></td><td>';
				if (is_array($array_query['options_'.$key])) {
					print $extrafields->showInputField($key, implode(',', $array_query['options_'.$key]));
				} else {
					print $extrafields->showInputField($key, $array_query['options_'.$key]);
				}
				print '</td></tr></table>';

				print '</td><td>'."\n";
			}
			print '</td></tr>'."\n";
		}
	}
} else {
	$std_soc = new Societe($db);
	$action_search = 'query';

	// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
	include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
	$hookmanager = new HookManager($db);
	$hookmanager->initHooks(array('thirdpartycard'));

	$parameters = array();
	if (!empty($advTarget->id)) {
		$parameters = array('array_query' => $advTarget->filtervalue);
	}
	// Other attributes
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $std_soc, $action_search);
    print $hookmanager->resPrint;
}

// State Contact
print '<tr><td>'.$langs->trans('Status').' '.$langs->trans('Contact');
if (!empty($array_query['contact_status'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>';
print $formadvtargetemaling->advMultiselectarray(
    'contact_status',
    array(
		'0' => $langs->trans('ActivityCeased'),
		'1' => $langs->trans('InActivity')
    ),
    $array_query['contact_status']
);
print '</td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtContactHelp"), 1, 'help');
print '</td></tr>'."\n";

// Civility
print '<tr><td width="15%">'.$langs->trans("UserTitle");
if (!empty($array_query['contact_civility'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>';
print $formadvtargetemaling->multiselectCivility('contact_civility', $array_query['contact_civility']);
print '</td></tr>';

// contact name
print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans('Lastname');
if (!empty($array_query['contact_lastname'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td><input type="text" name="contact_lastname" value="'.$array_query['contact_lastname'].'"/></td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
print '</td></tr>'."\n";
print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans('Firstname');
if (!empty($array_query['contact_firstname'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td><input type="text" name="contact_firstname" value="'.$array_query['contact_firstname'].'"/></td><td>'."\n";
print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
print '</td></tr>'."\n";

// Contact Country
print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("Country");
if (!empty($array_query['contact_country'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>'."\n";
print $formadvtargetemaling->multiselectCountry('contact_country', $array_query['contact_country']);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Never send mass mailing
print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("No_Email");
if (!empty($array_query['contact_no_email'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>'."\n";
print $form->selectarray(
    'contact_no_email',
    array(
		'' => '',
		'1' => $langs->trans('Yes'),
		'0' => $langs->trans('No')
    ),
    $array_query['contact_no_email']
);
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Contact Date Create
print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("DateCreation");
if (!empty($array_query['contact_create_st_dt'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>'."\n";
print '<table class="nobordernopadding"><tr>';
print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
print $form->selectDate($array_query['contact_create_st_dt'], 'contact_create_st_dt', 0, 0, 1, 'find_customer', 1, 1);
print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
print $form->selectDate($array_query['contact_create_end_dt'], 'contact_create_end_dt', 0, 0, 1, 'find_customer', 1, 1);
print '</td></tr></table>';
print '</td><td>'."\n";
print '</td></tr>'."\n";

// Contact update Create
print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("DateLastModification");
if (!empty($array_query['contact_update_st_dt'])) {
	print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
}
print '</td><td>'."\n";
print '<table class="nobordernopadding"><tr>';
print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
print $form->selectDate($array_query['contact_update_st_dt'], 'contact_update_st_dt', 0, 0, 1, 'find_customer', 1, 1);
print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
print $form->selectDate($array_query['contact_update_end_dt'], 'contact_update_end_dt', 0, 0, 1, 'find_customer', 1, 1);
print '</td></tr></table>';
print '</td><td>'."\n";
print '</td></tr>'."\n";

if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
	// Customer Categories
	print '<tr><td>'.$langs->trans("ContactCategoriesShort");
	if (!empty($array_query['contact_categ'])) {
		print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
	}
	print '</td><td>'."\n";
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_CONTACT, null, 'parent', null, null, 1);
	print $form->multiselectarray('contact_categ', $cate_arbo, GETPOST('contact_categ', 'array'), null, null, null, null, "90%");
	print '</td><td>'."\n";
	print '</td></tr>'."\n";
}

// Standard Extrafield feature
if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
	$contactstatic = new Contact($db);
	$elementype = $contactstatic->table_element;
	// fetch optionals attributes and labels
	dol_include_once('/core/class/extrafields.class.php');
	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label($elementype);
	if (!empty($extrafields->attributes[$elementtype]['type'])) {
		foreach ($extrafields->attributes[$elementtype]['type'] as $key => &$value) {
			if ($value == 'radio')
				$value = 'select';
		}
	}
	if (!empty($extrafields->attributes[$elementtype]['label'])) {
		foreach ($extrafields->attributes[$elementtype]['label'] as $key => $val) {
			print '<tr><td>' . $extrafields->attributes[$elementtype]['label'][$key];
			if ($array_query['options_' . $key . '_cnct'] != '' || (is_array($array_query['options_' . $key . '_cnct']) && count($array_query['options_' . $key . '_cnct']) > 0)) {
				print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
			}
			print '</td><td>';
			if (($extrafields->attributes[$elementtype]['type'][$key] == 'varchar') || ($extrafields->attributes[$elementtype]['type'][$key] == 'text')) {
				print '<input type="text" name="options_' . $key . '_cnct"/></td><td>' . "\n";
				print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'int') || ($extrafields->attributes[$elementtype]['type'][$key] == 'double')) {
				print $langs->trans("AdvTgtMinVal") . '<input type="text" name="options_' . $key . '_min_cnct"/>';
				print $langs->trans("AdvTgtMaxVal") . '<input type="text" name="options_' . $key . '_max_cnct"/>';
				print '</td><td>' . "\n";
				print $form->textwithpicto('', $langs->trans("AdvTgtSearchIntHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'date') || ($extrafields->attributes[$elementtype]['type'][$key] == 'datetime')) {
				print '<table class="nobordernopadding"><tr>';
				print '<td>' . $langs->trans("AdvTgtStartDt") . '</td><td>';
				print $form->selectDate('', 'options_' . $key . '_st_dt_cnct');
				print '</td><td>' . $langs->trans("AdvTgtEndDt") . '</td><td>';
				print $form->selectDate('', 'options_' . $key . '_end_dt_cnct');
				print '</td></tr></table>';
				print '</td><td>' . "\n";
				print $form->textwithpicto('', $langs->trans("AdvTgtSearchDtHelp"), 1, 'help');
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'boolean')) {
				print $form->selectarray(
					'options_' . $key . '_cnct',
					array(
						''  => '',
						'1' => $langs->trans('Yes'),
						'0' => $langs->trans('No')
					),
					$array_query['options_' . $key . '_cnct']
				);
				print '</td><td>' . "\n";
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'select')) {
				print $formadvtargetemaling->advMultiselectarray('options_' . $key . '_cnct', $extrafields->attributes[$key]['param']['options'], $array_query['options_' . $key . '_cnct']);
				print '</td><td>' . "\n";
			} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'sellist')) {
				print $formadvtargetemaling->advMultiselectarraySelllist('options_' . $key . '_cnct', $extrafields->attributes[$key]['param']['options'], $array_query['options_' . $key . '_cnct']);
				print '</td><td>' . "\n";
			} else {
				if (is_array($array_query['options_' . $key . '_cnct'])) {
					print $extrafields->showInputField($key, implode(',', $array_query['options_' . $key . '_cnct']), '', '_cnct');
				} else {
					print $extrafields->showInputField($key, $array_query['options_' . $key . '_cnct'], '', '_cnct');
				}
				print '</td><td>' . "\n";
			}
			print '</td></tr>' . "\n";
		}
	}
}
print '<tr>'."\n";
print '<td colspan="3" class="right">'."\n";
print '<input type="button" name="addcontact" id="addcontact" value="'.$langs->trans('AdvTgtAddContact').'" class="butAction"/>'."\n";
print '</td>'."\n";
print '</tr>'."\n";
print '</table>'."\n";
print '</form>'."\n";
print '</div>'."\n";
print '<form action="'.$_SERVER['PHP_SELF'].'?action=clear&id='.$object->id.'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print load_fiche_titre($langs->trans("ToClearAllRecipientsClickHere"));
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="liste_titre right"><input type="submit" class="button" value="'.$langs->trans("TargetsReset").'"></td>';
print '</tr>';
print '</table>';
print '</form>';
print '<br>';
