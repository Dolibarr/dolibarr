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
 * \file    lib/handson_player.lib.php
 * \ingroup handson
 * \brief   Library files with common functions for Player
 */

require_once DOL_DOCUMENT_ROOT . '/custom/handson/class/player.class.php';

/**
 * Prepare array of tabs for Player
 *
 * @param Player $object Player
 * @return    array                    Array of tabs
 */
function playerPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("handson@handson");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/handson/player_card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (isset($object->fields['note_public']) || isset($object->fields['note_private'])) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = dol_buildpath('/handson/player_note.php', 1) . '?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">' . $nbNote . '</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
	$upload_dir = $conf->handson->dir_output . "/player/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/handson/player_document.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . ($nbFiles + $nbLinks) . '</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/handson/player_agenda.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@handson:/handson/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@handson:/handson/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'player@handson');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'player@handson', 'remove');

	return $head;
}

function showPlayers($conf, $langs, $db, $object, $backtopage = '')
{
	global $user, $conf, $extrafields, $hookmanager;
	global $contextpage;

	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
	$formcompany = new FormCompany($db);
	$form = new Form($db);
	$playerstatic = new Player($db);

	$extrafields->fetch_name_optionals_label($playerstatic->table_element);

	// Definition of fields for list
	/*$arrayfields = array(
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
	);*/

	foreach ($playerstatic->fields as $key => $value) {
		if($key != 'rowid') {
			$arrayfields['t.' . $key] = $value;
			$arrayfields['t.' . $key]['checked'] = 1;
		}
	}

	// Extra fields
	if (!empty($extrafields->attributes[$playerstatic->table_element]['label']) && is_array($extrafields->attributes[$playerstatic->table_element]['label']) && count($extrafields->attributes[$playerstatic->table_element]['label'])) {
		foreach ($extrafields->attributes[$playerstatic->table_element]['label'] as $key => $val) {
			if (!empty($extrafields->attributes[$playerstatic->table_element]['list'][$key])) {
				$arrayfields["ef." . $key] = array(
					'label' => $extrafields->attributes[$playerstatic->table_element]['label'][$key],
					'checked' => (($extrafields->attributes[$playerstatic->table_element]['list'][$key] < 0) ? 0 : 1),
					'position' => 1000 + $extrafields->attributes[$playerstatic->table_element]['pos'][$key],
					'enabled' => (abs($extrafields->attributes[$playerstatic->table_element]['list'][$key]) != 3 && $extrafields->attributes[$playerstatic->table_element]['perms'][$key]));
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
	$search_array_options = $extrafields->getOptionalsFromPost($playerstatic->table_element, '', 'search_');

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_name = '';

		foreach ($playerstatic->fields as $key => $val) {
			$search[$key] = '';
		}
	}

	$playerstatic->fields = dol_sort_array($playerstatic->fields, 'position');
	$arrayfields = dol_sort_array($arrayfields, 'position');

	$newcardbutton = '';
	if ($user->rights->societe->contact->creer) {
		$addcontact = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
		$newcardbutton .= dolGetButtonTitle($addcontact, '', 'fa fa-plus-circle', DOL_URL_ROOT . '/contact/card.php?socid=' . $object->id . '&amp;action=create&amp;backtopage=' . urlencode($backtopage));
	}

	print "\n";

	print load_fiche_titre("Teammitglieder", '', '');

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
	if ($search_name != '') $param .= '&search_name=' . urlencode($search_name);

	// Add $param from extra fields
	$extrafieldsobjectkey = $playerstatic->table_element;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

	$sql = "SELECT *";
	/*foreach ($playerstatic->fields as $key => $val) {
		$sql .= 'v.' . $key . ', ';
	}*/
	$sql .= " FROM " . MAIN_DB_PREFIX . "handson_player as t";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "handson_player_extrafields as ef on (t.rowid = ef.fk_object)";
	$sql .= " WHERE t.team = " . $object->id;

	dol_syslog('core/lib/company.lib.php :: show_contacts', LOG_DEBUG);
	$result = $db->query($sql);
	if (!$result) dol_print_error($db);

	$num = $db->num_rows($result);

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($playerstatic->fields as $key => $val) {
		$align = '';
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $align .= ($align ? ' ' : '') . 'center';
		if (in_array($val['type'], array('timestamp'))) $align .= ($align ? ' ' : '') . 'nowrap';
		if ($key == 'status' || $key == 'statut') $align .= ($align ? ' ' : '') . 'center';
		if (!empty($arrayfields['t.' . $key]['checked'])) {
			print '<td class="liste_titre' . ($align ? ' ' . $align : '') . '">';
			if (in_array($key, array('statut'))) {
				print $form->selectarray('search_status', array('-1' => '', '0' => $playerstatic->LibStatut(0, 1), '1' => $playerstatic->LibStatut(1, 1)), $search_status);
			} elseif (in_array($key, array('role'))) {
				print $formcompany->showRoles("search_roles", $playerstatic, 'edit', $search_roles);
			} else {
				print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . (!empty($search[$key]) ? dol_escape_htmltag($search[$key]) : '') . '">';
			}
			print '</td>';
		}
	}
	// Extra fields
	$extrafieldsobjectkey = $playerstatic->table_element;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $playerstatic); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="liste_titre" align="right">';
	print $form->showFilterButtons();
	print '</td>';
	print '</tr>' . "\n";


	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($playerstatic->fields as $key => $val) {
		$align = '';
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $align .= ($align ? ' ' : '') . 'center';
		if (in_array($val['type'], array('timestamp'))) $align .= ($align ? ' ' : '') . 'nowrap';
		if ($key == 'status' || $key == 'statut') $align .= ($align ? ' ' : '') . 'center';
		if (!empty($arrayfields['t.' . $key]['checked'])) print getTitleFieldOfList($val['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($align ? 'class="' . $align . '"' : ''), $sortfield, $sortorder, $align . ' ') . "\n";
		if ($key == 'role') $align .= ($align ? ' ' : '') . 'left';
	}
	// Extra fields
	$extrafieldsobjectkey = $playerstatic->table_element;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ') . "\n";
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
			$playerstatic->setVarsFromFetchObj($obj);
			// Show here line of result
			print '<tr class="oddeven">';
			foreach ($playerstatic->fields as $key => $val) {
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

				if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'gender' && !in_array($key, array('rowid', 'status'))) {
					$cssforfield .= ($cssforfield ? ' ' : '') . 'right';
				}
				//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

				if (!empty($arrayfields['t.' . $key]['checked'])) {
					print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . '>';
					if ($key == 'status') {
						print $playerstatic->getLibStatut(5);
					} else {
						print $playerstatic->showOutputField($val, $key, $playerstatic->$key, '');
					}
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
					if (!empty($val['isameasure'])) {
						if (!$i) {
							$totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
						}
						$totalarray['val']['t.' . $key] += $playerstatic->$key;
					}
				}
			}
			// Extra fields
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters = array('arrayfields' => $arrayfields, 'object' => $playerstatic, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Action column
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) $selected = 1;
				print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $playerstatic->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
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
