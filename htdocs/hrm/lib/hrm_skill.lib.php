<?php
/* Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021 Greg Rastklan <greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021 Jean-Pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Grégory BLEMAND <gregory.blemand@atm-consulting.fr>
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
 * \file    lib/hrm_skill.lib.php
 * \ingroup hrm
 * \brief   Library files with common functions for Skill
 */

/**
 * Prepare array of tabs for Skill
 *
 * @param	Skill	$object		Skill
 * @return 	array					Array of tabs
 */
function skillPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("hrm");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/hrm/skill_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("SkillCard");
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
		$head[$h][0] = dol_buildpath('/hrm/skill_note.php', 1).'?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
		}
		$head[$h][2] = 'note';
		$h++;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->hrm->dir_output."/skill/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = dol_buildpath("/hrm/skill_document.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = dol_buildpath("/hrm/skill_agenda.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	$head[$h][2] = 'agenda';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@hrm:/hrm/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@hrm:/hrm/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'skill@hrm');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'skill@hrm', 'remove');

	return $head;
}


//Affichage des 5 trades  pour cet object
function showTraductionNote ($object){


}


/**
 * 		Show html area for list of traduction
 *
 *		@param	Conf		$conf		Object conf
 * 		@param	Translate	$langs		Object langs
 * 		@param	DoliDB		$db			Database handler
 * 		@param	Societe		$object		Third party object
 *      @param  string		$backtopage	Url to go once contact is created
 *      @return	int
 */
function show_traduction($conf, $langs, $db, $object, $backtopage = '')
{
	global $user, $conf, $extrafields, $hookmanager;
	global $contextpage;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
	$formcompany = new FormCompany($db);
	$form = new Form($db);

	$optioncss = GETPOST('optioncss', 'alpha');
	$sortfield = GETPOST("sortfield", 'alpha');
	$sortorder = GETPOST("sortorder", 'alpha');
	$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

	$search_status = GETPOST("search_status", 'int');
	if ($search_status == '') {
		$search_status = 1; // always display active customer first
	}

	$search_name    = GETPOST("search_name", 'alpha');
	$search_address = GETPOST("search_address", 'alpha');
	$search_poste   = GETPOST("search_poste", 'alpha');
	$search_roles = GETPOST("search_roles", 'array');

	$socialnetworks = getArrayOfSocialNetworks();

	$searchAddressPhoneDBFields = array(
		//Address
		't.address',
		't.zip',
		't.town',

		//Phone
		't.phone',
		't.phone_perso',
		't.phone_mobile',

		//Fax
		't.fax',

		//E-mail
		't.email',
	);
	//Social media
	//    foreach ($socialnetworks as $key => $value) {
	//        if ($value['active']) {
	//            $searchAddressPhoneDBFields['t.'.$key] = "t.socialnetworks->'$.".$key."'";
	//        }
	//    }

	if (!$sortorder) {
		$sortorder = "ASC";
	}
	if (!$sortfield) {
		$sortfield = "t.lastname";
	}

	if (!empty($conf->clicktodial->enabled)) {
		$user->fetch_clicktodial(); // lecture des infos de clicktodial du user
	}


	$SkillLinestatic = new SkillLine($db);

	$extrafields->fetch_name_optionals_label($SkillLinestatic->table_element);

	$SkillLinestatic->fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>1, 'notnull'=>1, 'visible'=>0, 'noteditable'=>'1', 'index'=>1, 'css'=>'left', 'comment'=>"Id"),
		'fk_skill' => array('type'=>'integer:Skill:skill/class/skill.class.php', 'label'=>'foreign key skill', 'enabled'=>'1', 'position'=>510, 'notnull'=>1, 'visible'=>-2, 'foreignkey'=>'user.rowid',),
		'traduction_note' => array('type'=>'text', 'label'=>'traduction_note', 'enabled'=>'1', 'position'=>56, 'notnull'=>0, 'visible'=>1,),
		'rank' => array('type'=>'integer', 'label'=>'rank', 'enabled'=>'1', 'position'=>55, 'notnull'=>1, 'visible'=>1, 'index'=>1,),
	);

	// Definition of fields for list
	$arrayfields = array(
		't.rowid'=>array('label'=>"TechnicalID", 'checked'=>(!empty($conf->global->MAIN_SHOW_TECHNICAL_ID) ? 1 : 0), 'enabled'=>(!empty($conf->global->MAIN_SHOW_TECHNICAL_ID) ? 1 : 0), 'position'=>1),
		't.name'=>array('label'=>"Name", 'checked'=>1, 'position'=>10),
		't.poste'=>array('label'=>"PostOrFunction", 'checked'=>1, 'position'=>20),
		't.address'=>array('label'=>(empty($conf->dol_optimize_smallscreen) ? $langs->trans("Address").' / '.$langs->trans("Phone").' / '.$langs->trans("Email") : $langs->trans("Address")), 'checked'=>1, 'position'=>30),
		'sc.role'=>array('label'=>"ContactByDefaultFor", 'checked'=>1, 'position'=>40),
		't.statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>50, 'class'=>'center'),
	);
	// Extra fields
	if (!empty($extrafields->attributes[$contactstatic->table_element]['label']) && is_array($extrafields->attributes[$contactstatic->table_element]['label']) && count($extrafields->attributes[$contactstatic->table_element]['label'])) {
		foreach ($extrafields->attributes[$contactstatic->table_element]['label'] as $key => $val) {
			if (!empty($extrafields->attributes[$contactstatic->table_element]['list'][$key])) {
				$arrayfields["ef.".$key] = array(
					'label'=>$extrafields->attributes[$contactstatic->table_element]['label'][$key],
					'checked'=>(($extrafields->attributes[$contactstatic->table_element]['list'][$key] < 0) ? 0 : 1),
					'position'=>1000 + $extrafields->attributes[$contactstatic->table_element]['pos'][$key],
					'enabled'=>(abs($extrafields->attributes[$contactstatic->table_element]['list'][$key]) != 3 && $extrafields->attributes[$contactstatic->table_element]['perms'][$key]));
			}
		}
	}

	// Initialize array of search criterias
	$search = array();
	foreach ($arrayfields as $key => $val) {
		$queryName = 'search_'.substr($key, 2);
		if (GETPOST($queryName, 'alpha')) {
			$search[substr($key, 2)] = GETPOST($queryName, 'alpha');
		}
	}
	$search_array_options = $extrafields->getOptionalsFromPost($contactstatic->table_element, '', 'search_');

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_status = '';
		$search_name = '';
		$search_roles = array();
		$search_address = '';
		$search_poste = '';
		$search = array();
		$search_array_options = array();

		foreach ($contactstatic->fields as $key => $val) {
			$search[$key] = '';
		}
	}

	$contactstatic->fields = dol_sort_array($contactstatic->fields, 'position');
	$arrayfields = dol_sort_array($arrayfields, 'position');

	$newcardbutton = '';
	if ($user->rights->societe->contact->creer) {
		$addTraduction = $langs->trans("AddTraduction");
		$newcardbutton .= dolGetButtonTitle($addTraduction , '', 'fa fa-plus-circle', DOL_URL_ROOT.'/skilldet/card.php?skillid='.$object->id.'&amp;action=create&amp;backtopage='.urlencode($backtopage));
	}

	print "\n";

	$title = $langs->trans("TraductionRule") ;
	print load_fiche_titre($title, $newcardbutton, '');

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="socid" value="'.$object->id.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	//if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
	print "\n".'<table class="tagtable liste">'."\n";

	$param = "socid=".urlencode($object->id);
	if ($search_status != '') {
		$param .= '&search_status='.urlencode($search_status);
	}
	if (count($search_roles) > 0) {
		$param .= implode('&search_roles[]=', $search_roles);
	}
	if ($search_name != '') {
		$param .= '&search_name='.urlencode($search_name);
	}
	if ($search_poste != '') {
		$param .= '&search_poste='.urlencode($search_poste);
	}
	if ($search_address != '') {
		$param .= '&search_address='.urlencode($search_address);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}

	// Add $param from extra fields
	$extrafieldsobjectkey = $contactstatic->table_element;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$sql = "SELECT t.rowid, t.lastname, t.firstname, t.fk_pays as country_id, t.civility, t.poste, t.phone as phone_pro, t.phone_mobile, t.phone_perso, t.fax, t.email, t.socialnetworks, t.statut, t.photo,";
	$sql .= " t.civility as civility_id, t.address, t.zip, t.town";
	$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as t";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople_extrafields as ef on (t.rowid = ef.fk_object)";
	$sql .= " WHERE t.fk_soc = ".$object->id;
	if ($search_status != '' && $search_status != '-1') {
		$sql .= " AND t.statut = ".$db->escape($search_status);
	}
	if ($search_name) {
		$sql .= natural_search(array('t.lastname', 't.firstname'), $search_name);
	}
	if ($search_poste) {
		$sql .= natural_search('t.poste', $search_poste);
	}
	if ($search_address) {
		$sql .= natural_search($searchAddressPhoneDBFields, $search_address);
	}
	if (count($search_roles) > 0) {
		$sql .= " AND t.rowid IN (SELECT sc.fk_socpeople FROM ".MAIN_DB_PREFIX."societe_contacts as sc WHERE sc.fk_c_type_contact IN (".$db->sanitize(implode(',', $search_roles))."))";
	}
	// Add where from extra fields
	$extrafieldsobjectkey = $contactstatic->table_element;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	if ($sortfield == "t.name") {
		$sql .= " ORDER BY t.lastname $sortorder, t.firstname $sortorder";
	} else {
		$sql .= " ORDER BY $sortfield $sortorder";
	}

	dol_syslog('core/lib/company.lib.php :: show_contacts', LOG_DEBUG);
	$result = $db->query($sql);
	if (!$result) {
		dol_print_error($db);
	}

	$num = $db->num_rows($result);

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($contactstatic->fields as $key => $val) {
		$align = '';
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
			$align .= ($align ? ' ' : '').'center';
		}
		if (in_array($val['type'], array('timestamp'))) {
			$align .= ($align ? ' ' : '').'nowrap';
		}
		if ($key == 'status' || $key == 'statut') {
			$align .= ($align ? ' ' : '').'center';
		}
		if (!empty($arrayfields['t.'.$key]['checked']) || !empty($arrayfields['sc.'.$key]['checked'])) {
			print '<td class="liste_titre'.($align ? ' '.$align : '').'">';
			if (in_array($key, array('statut'))) {
				print $form->selectarray('search_status', array('-1'=>'', '0'=>$contactstatic->LibStatut(0, 1), '1'=>$contactstatic->LibStatut(1, 1)), $search_status);
			} elseif (in_array($key, array('role'))) {
				print $formcompany->showRoles("search_roles", $contactstatic, 'edit', $search_roles);
			} else {
				print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.(!empty($search[$key]) ? dol_escape_htmltag($search[$key]) : '').'">';
			}
			print '</td>';
		}
	}
	// Extra fields
	$extrafieldsobjectkey = $contactstatic->table_element;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $contactstatic); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="liste_titre" align="right">';
	print $form->showFilterButtons();
	print '</td>';
	print '</tr>'."\n";


	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach ($contactstatic->fields as $key => $val) {
		$align = '';
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
			$align .= ($align ? ' ' : '').'center';
		}
		if (in_array($val['type'], array('timestamp'))) {
			$align .= ($align ? ' ' : '').'nowrap';
		}
		if ($key == 'status' || $key == 'statut') {
			$align .= ($align ? ' ' : '').'center';
		}
		if (!empty($arrayfields['t.'.$key]['checked'])) {
			print getTitleFieldOfList($val['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($align ? 'class="'.$align.'"' : ''), $sortfield, $sortorder, $align.' ')."\n";
		}
		if ($key == 'role') {
			$align .= ($align ? ' ' : '').'left';
		}
		if (!empty($arrayfields['sc.'.$key]['checked'])) {
			print getTitleFieldOfList($arrayfields['sc.'.$key]['label'], 0, $_SERVER['PHP_SELF'], '', '', $param, ($align ? 'class="'.$align.'"' : ''), $sortfield, $sortorder, $align.' ')."\n";
		}
	}
	// Extra fields
	$extrafieldsobjectkey = $contactstatic->table_element;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ')."\n";
	print '</tr>'."\n";

	$i = -1;

	if ($num || (GETPOST('button_search') || GETPOST('button_search.x') || GETPOST('button_search_x'))) {
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($result);

			$contactstatic->id = $obj->rowid;
			$contactstatic->ref = $obj->rowid;
			$contactstatic->statut = $obj->statut;
			$contactstatic->lastname = $obj->lastname;
			$contactstatic->firstname = $obj->firstname;
			$contactstatic->civility_id = $obj->civility_id;
			$contactstatic->civility_code = $obj->civility_id;
			$contactstatic->poste = $obj->poste;
			$contactstatic->address = $obj->address;
			$contactstatic->zip = $obj->zip;
			$contactstatic->town = $obj->town;
			$contactstatic->phone_pro = $obj->phone_pro;
			$contactstatic->phone_mobile = $obj->phone_mobile;
			$contactstatic->phone_perso = $obj->phone_perso;
			$contactstatic->email = $obj->email;
			$contactstatic->socialnetworks = $obj->socialnetworks;
			$contactstatic->photo = $obj->photo;

			$country_code = getCountry($obj->country_id, 2);
			$contactstatic->country_code = $country_code;

			$contactstatic->setGenderFromCivility();
			$contactstatic->fetch_optionals();

			$resultRole = $contactstatic->fetchRoles();
			if ($resultRole < 0) {
				setEventMessages(null, $contactstatic->errors, 'errors');
			}

			if (is_array($contactstatic->array_options)) {
				foreach ($contactstatic->array_options as $key => $val) {
					$obj->$key = $val;
				}
			}

			print '<tr class="oddeven">';

			// ID
			if (!empty($arrayfields['t.rowid']['checked'])) {
				print '<td>';
				print $contactstatic->id;
				print '</td>';
			}

			// Photo - Name
			if (!empty($arrayfields['t.name']['checked'])) {
				print '<td>';
				print $form->showphoto('contact', $contactstatic, 0, 0, 0, 'photorefnoborder valignmiddle marginrightonly', 'small', 1, 0, 1);
				print $contactstatic->getNomUrl(0, '', 0, '&backtopage='.urlencode($backtopage));
				print '</td>';
			}

			// Job position
			if (!empty($arrayfields['t.poste']['checked'])) {
				print '<td>';
				if ($obj->poste) {
					print $obj->poste;
				}
				print '</td>';
			}

			// Address - Phone - Email
			if (!empty($arrayfields['t.address']['checked'])) {
				print '<td>';
				print $contactstatic->getBannerAddress('contact', $object);
				print '</td>';
			}

			// Role
			if (!empty($arrayfields['sc.role']['checked'])) {
				print '<td>';
				print $formcompany->showRoles("roles", $contactstatic, 'view');
				print '</td>';
			}

			// Status
			if (!empty($arrayfields['t.statut']['checked'])) {
				print '<td class="center">'.$contactstatic->getLibStatut(5).'</td>';
			}

			// Extra fields
			$extrafieldsobjectkey = $contactstatic->table_element;
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

			// Actions
			print '<td align="right">';

			// Add to agenda
			if (!empty($conf->agenda->enabled) && $user->rights->agenda->myactions->create) {
				print '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&actioncode=&contactid='.$obj->rowid.'&socid='.$object->id.'&backtopage='.urlencode($backtopage).'">';
				print img_object($langs->trans("Event"), "action");
				print '</a> &nbsp; ';
			}

			// Edit
			if ($user->rights->societe->contact->creer) {
				print '<a class="editfielda paddingleft" href="'.DOL_URL_ROOT.'/contact/card.php?action=edit&id='.$obj->rowid.'&backtopage='.urlencode($backtopage).'">';
				print img_edit();
				print '</a>';
			}

			print '</td>';

			print "</tr>\n";
			$i++;
		}
	} else {
		$colspan = 1;
		foreach ($arrayfields as $key => $val) {
			if (!empty($val['checked'])) {
				$colspan++;
			}
		}
		print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	print "\n</table>\n";
	print '</div>';

	print '</form>'."\n";

	return $i;
}
