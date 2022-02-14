<?php
/* Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/handson_vertrag.lib.php
 * \ingroup handson
 * \brief   Library files with common functions for Vertrag
 */

require_once DOL_DOCUMENT_ROOT . '/custom/handson/class/vertrag.class.php';

/**
 * Prepare array of tabs for Vertrag
 *
 * @param Vertrag $object Vertrag
 * @return    array                    Array of tabs
 */
function vertragPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("handson@handson");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/handson/vertrag_card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
		$head[$h][0] = dol_buildpath('/handson/vertrag_note.php', 1) . '?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		$head[$h][2] = 'note';
		$h++;
	}

	/*require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->handson->dir_output."/vertrag/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/handson/vertrag_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/handson/vertrag_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;*/

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@handson:/handson/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@handson:/handson/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'vertrag@handson');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'vertrag@handson', 'remove');

	return $head;
}

function show_vertraege($conf, $langs, $db, $object, $backtopage = '')
{
	global $user, $conf, $extrafields, $hookmanager;
	global $contextpage;

	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
	$formcompany = new FormCompany($db);
	$form = new Form($db);

	$optioncss = GETPOST('optioncss', 'alpha');
	$sortfield = GETPOST("sortfield", 'alpha');
	$sortorder = GETPOST("sortorder", 'alpha');
	$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

	$search_status = GETPOST("search_status", 'int');
	if ($search_status == '') $search_status = 1; // always display active customer first

	$search_name = GETPOST("search_name", 'alpha');
	$search_address = GETPOST("search_address", 'alpha');
	$search_poste = GETPOST("search_poste", 'alpha');
	$search_roles = GETPOST("search_roles", 'array');

	$socialnetworks = getArrayOfSocialNetworks();

	$searchAddressPhoneDBFields = array(
		//Address
		'v.ref',
		'v.label',
		'v.fk_soc',
		'v.description',
		'v.sent',
		'v.signed',
		'v.date_creation',
		'v.programm',
		'v.region',
		'v.saison',
		'v.zustand',
		'v.extra'
	);
	//Social media
	/*foreach ($socialnetworks as $key => $value) {
		if ($value['active']) {
			$searchAddressPhoneDBFields['t.'.$key] = "t.socialnetworks->'$.".$key."'";
		}
	}

	if (!$sortorder) $sortorder = "ASC";
	if (!$sortfield) $sortfield = "t.lastname";

	if (!empty($conf->clicktodial->enabled)) {
		$user->fetch_clicktodial(); // lecture des infos de clicktodial du user
	}*/


	$vertragstatic = new Vertrag($db);

	$extrafields->fetch_name_optionals_label($vertragstatic->table_element);
/*	$vertragstatic->fields = array(
		'label'      =>array('type'=>'varchar(128)', 'label'=>'Name', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1),
		'fk_soc'     =>array('type'=>'varchar(128)', 'label'=>'Partner', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>20),
		'description'   =>array('type'=>'varchar(128)', 'label'=>'Beschreibung', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>30),
		'sent'      =>array('type'=>'checkbox', 'label'=>'Versendet', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>40),
		'signed'      =>array('type'=>'checkbox', 'label'=>'Unterschrieben', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>45),
		'programm'    =>array('type'=>'chkbxlst', 'label'=>'Programm', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'default'=>0, 'index'=>1, 'position'=>50),
		'region'    =>array('type'=>'chkbxlst', 'label'=>'Region', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'default'=>0, 'index'=>1, 'position'=>55),
		'saison'    =>array('type'=>'chkbxlst', 'label'=>'Saison', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'default'=>0, 'index'=>1, 'position'=>60),
		'zustand'    =>array('type'=>'varchar(128)', 'label'=>'Zustand', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'default'=>0, 'index'=>1, 'position'=>65),
		'extra'    =>array('type'=>'checkbox', 'label'=>'Extra', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'default'=>0, 'index'=>1, 'position'=>70),
	);*/


	// Definition of fields for list
	$arrayfields = array(
		't.ref' => array('label' => "Systemname", 'checked' => 1, 'position' => 5),
		't.label' => array('label' => "Bezeichnung", 'checked' => 1, 'position' => 10),
		't.fk_soc' => array('label' => "Partner", 'checked' => 1, 'position' => 20),
		't.description' => array('label' => 'Beschreibung', 'checked' => 1, 'position' => 30),
		't.sent' => array('label' => "Gesendet", 'checked' => 1, 'position' => 40),
		't.signed' => array('label' => "Unterschrieben", 'checked' => 1, 'position' => 45),
		't.programm' => array('label' => "Programm", 'checked' => 1, 'position' => 50, 'class' => 'center'),
		't.region' => array('label' => "Region", 'checked' => 1, 'position' => 55, 'class' => 'center'),
		't.saison' => array('label' => "Saison", 'checked' => 1, 'position' => 60, 'class' => 'center'),
		't.zustand' => array('label' => "Zustand", 'checked' => 1, 'position' => 60, 'class' => 'center'),
		't.extra' => array('label' => "Extra", 'checked' => 1, 'position' => 60, 'class' => 'center'),
	);

	/*foreach ($vertragstatic->fields as $key => $value) {
		$arrayfields['v.'.$key] = $value;
		$arrayfields['v.'.$key]['checked'] = 1;
	}*/

	// Extra fields
	if (!empty($extrafields->attributes[$vertragstatic->table_element]['label']) && is_array($extrafields->attributes[$vertragstatic->table_element]['label']) && count($extrafields->attributes[$vertragstatic->table_element]['label'])) {
		foreach ($extrafields->attributes[$vertragstatic->table_element]['label'] as $key => $val) {
			if (!empty($extrafields->attributes[$vertragstatic->table_element]['list'][$key])) {
				$arrayfields["ef." . $key] = array(
					'label' => $extrafields->attributes[$vertragstatic->table_element]['label'][$key],
					'checked' => (($extrafields->attributes[$vertragstatic->table_element]['list'][$key] < 0) ? 0 : 1),
					'position' => 1000 + $extrafields->attributes[$vertragstatic->table_element]['pos'][$key],
					'enabled' => (abs($extrafields->attributes[$vertragstatic->table_element]['list'][$key]) != 3 && $extrafields->attributes[$vertragstatic->table_element]['perms'][$key]));
			}
		}
	}

	// Initialize array of search criterias
	$search = array();
	foreach ($arrayfields as $key => $val) {
		$queryName = 'search_' . substr($key, 2);
		if (GETPOST($queryName, 'alpha')) {
			$search[substr($key, 2)] = GETPOST($queryName, 'alpha');
		}
	}
	$search_array_options = $extrafields->getOptionalsFromPost($vertragstatic->table_element, '', 'search_');

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_status = '';
		$search_name = '';
		$search_roles = array();
		$search_address = '';
		$search_poste = '';
		$search = array();
		$search_array_options = array();

		foreach ($vertragstatic->fields as $key => $val) {
			$search[$key] = '';
		}
	}

	$vertragstatic->fields = dol_sort_array($vertragstatic->fields, 'position');
	$arrayfields = dol_sort_array($arrayfields, 'position');

	$newcardbutton = '';
	if ($user->rights->societe->contact->creer) {
		$addcontact = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
		$newcardbutton .= dolGetButtonTitle($addcontact, '', 'fa fa-plus-circle', DOL_URL_ROOT . '/contact/card.php?socid=' . $object->id . '&amp;action=create&amp;backtopage=' . urlencode($backtopage));
	}

	print "\n";

	$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Vorhandene Veträge") : $langs->trans("Vorhandene Veträge"));
	print load_fiche_titre($title, $newcardbutton, '');

	print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '" name="formfilter">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="socid" value="' . $object->id . '">';
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
	print '<input type="hidden" name="page" value="' . $page . '">';

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	//if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print "\n" . '<table class="tagtable liste">' . "\n";

	$param = "socid=" . urlencode($object->id);
	if ($search_status != '') $param .= '&search_status=' . urlencode($search_status);
	if (count($search_roles) > 0) $param .= implode('&search_roles[]=', $search_roles);
	if ($search_name != '') $param .= '&search_name=' . urlencode($search_name);
	if ($search_poste != '') $param .= '&search_poste=' . urlencode($search_poste);
	if ($search_address != '') $param .= '&search_address=' . urlencode($search_address);
	if ($optioncss != '') $param .= '&optioncss=' . urlencode($optioncss);

	// Add $param from extra fields
	$extrafieldsobjectkey = $vertragstatic->table_element;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

	$sql = "SELECT *";
	/*foreach ($vertragstatic->fields as $key => $val) {
		$sql .= 'v.' . $key . ', ';
	}*/
	$sql .= " FROM " . MAIN_DB_PREFIX . "handson_vertrag as t";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "handson_vertrag_extrafields as ef on (t.rowid = ef.fk_object)";
	$sql .= " WHERE t.fk_soc = " . $object->id;

	dol_syslog('core/lib/company.lib.php :: show_contacts', LOG_DEBUG);
	$result = $db->query($sql);
	if (!$result) dol_print_error($db);

	$num = $db->num_rows($result);

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($vertragstatic->fields as $key => $val) {
		$align = '';
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $align .= ($align ? ' ' : '') . 'center';
		if (in_array($val['type'], array('timestamp'))) $align .= ($align ? ' ' : '') . 'nowrap';
		if ($key == 'status' || $key == 'statut') $align .= ($align ? ' ' : '') . 'center';
		if (!empty($arrayfields['t.' . $key]['checked'])) {
			print '<td class="liste_titre' . ($align ? ' ' . $align : '') . '">';
			if (in_array($key, array('statut'))) {
				print $form->selectarray('search_status', array('-1' => '', '0' => $vertragstatic->LibStatut(0, 1), '1' => $vertragstatic->LibStatut(1, 1)), $search_status);
			} elseif (in_array($key, array('role'))) {
				print $formcompany->showRoles("search_roles", $vertragstatic, 'edit', $search_roles);
			} else {
				print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . (!empty($search[$key]) ? dol_escape_htmltag($search[$key]) : '') . '">';
			}
			print '</td>';
		}
	}
	// Extra fields
	$extrafieldsobjectkey = $vertragstatic->table_element;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $vertragstatic); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="liste_titre" align="right">';
	print $form->showFilterButtons();
	print '</td>';
	print '</tr>' . "\n";


	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($vertragstatic->fields as $key => $val) {
		$align = '';
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $align .= ($align ? ' ' : '') . 'center';
		if (in_array($val['type'], array('timestamp'))) $align .= ($align ? ' ' : '') . 'nowrap';
		if ($key == 'status' || $key == 'statut') $align .= ($align ? ' ' : '') . 'center';
		if (!empty($arrayfields['t.' . $key]['checked'])) print getTitleFieldOfList($val['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($align ? 'class="' . $align . '"' : ''), $sortfield, $sortorder, $align . ' ') . "\n";
		if ($key == 'role') $align .= ($align ? ' ' : '') . 'left';
	}
	// Extra fields
	$extrafieldsobjectkey = $vertragstatic->table_element;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ') . "\n";
	print '</tr>' . "\n";

	$i = -1;

	if ($num || (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x'))) {
		$i = 0;

		while ($i < $num) {

			$obj = $db->fetch_object($result);
			if (empty($obj)) {
				break; // Should not happen
			}

			// Store properties in $object
			$vertragstatic->setVarsFromFetchObj($obj);
			// Show here line of result
			print '<tr class="oddeven">';
			foreach ($vertragstatic->fields as $key => $val) {
				$cssforfield = (empty($val['css']) ? '' : $val['css']);
				if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
					$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
				} elseif ($key == 'status') {
					$cssforfield .= ($cssforfield ? ' ' : '') . 'center';
				}

				if (in_array($val['type'], array('timestamp'))) {
					$cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
				} elseif ($key == 'ref') {
					$cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
				}

				if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('rowid', 'status'))) {
					$cssforfield .= ($cssforfield ? ' ' : '') . 'right';
				}
				//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

				if (!empty($arrayfields['t.' . $key]['checked'])) {
					print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . '>';
					if ($key == 'status') {
						print $vertragstatic->getLibStatut(5);
					} else {
						print $vertragstatic->showOutputField($val, $key, $vertragstatic->$key, '');
					}
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
					if (!empty($val['isameasure'])) {
						if (!$i) {
							$totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
						}
						$totalarray['val']['t.' . $key] += $vertragstatic->$key;
					}
				}
			}
			// Extra fields
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters = array('arrayfields' => $arrayfields, 'object' => $vertragstatic, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Action column
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) $selected = 1;
				print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $vertragstatic->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;

			print '</tr>' . "\n";

			$i++;
		}

	} else {
		$colspan = 1;
		foreach ($arrayfields as $key => $val) {
			if (!empty($val['checked'])) $colspan++;
		}
		print '<tr><td colspan="' . $colspan . '" class="opacitymedium">' . $langs->trans("None") . '</td></tr>';
	}
	print "\n</table>\n";
	print '</div>';

	print '</form>' . "\n";

	return $i;

}
