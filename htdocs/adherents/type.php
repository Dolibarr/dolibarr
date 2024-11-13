<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry				<florian.henry@open-concept.pro>
 * Copyright (C) 2015-2024	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2019-2022	Thibault Foucart			<support@ptibogxiv.net>
 * Copyright (C) 2020		Josep Lluís Amador			<joseplluis@lliuretic.cat>
 * Copyright (C) 2021		Waël Almoman				<info@almoman.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *      \file       htdocs/adherents/type.php
 *      \ingroup    member
 *      \brief      Member's type setup
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->load("members");

$rowid  = GETPOSTINT('rowid');
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$toselect 	= GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$mode = GETPOST('mode', 'alpha');

$sall = GETPOST("sall", "alpha");
$filter = GETPOST("filter", 'alpha');
$search_ref = GETPOST('search_ref', 'alpha');
$search_lastname = GETPOST('search_lastname', 'alpha');
$search_login = GETPOST('search_login', 'alpha');
$search_email = GETPOST('search_email', 'alpha');
$type = GETPOST('type', 'intcomma');
$status = GETPOST('status', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "d.lastname";
}

$label = GETPOST("label", "alpha");
$morphy = GETPOST("morphy", "alpha");
$status = GETPOST("status", "intcomma");
$subscription = GETPOSTINT("subscription");
$amount = GETPOST('amount', 'alpha');
$duration_value = GETPOSTINT('duration_value');
$duration_unit = GETPOST('duration_unit', 'alpha');
$vote = GETPOSTINT("vote");
$comment = GETPOST("comment", 'restricthtml');
$mail_valid = GETPOST("mail_valid", 'restricthtml');
$caneditamount = GETPOSTINT("caneditamount");

// Initialize a technical object
$object = new AdherentType($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('membertypecard', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);


// Definition of array of fields for columns
$tableprefix = 't';
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval((string) $val['visible'], 1);
		$arrayfields[$tableprefix.'.'.$key] = array(
			'label' => $val['label'],
			'checked' => (($visible < 0) ? 0 : 1),
			'enabled' => (abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
			'position' => $val['position'],
			'help' => isset($val['help']) ? $val['help'] : ''
		);
	}
}


// Security check
$result = restrictedArea($user, 'adherent', $rowid, 'adherent_type');


/*
 *	Actions
 */
$error = 0;

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_ref = "";
	$search_lastname = "";
	$search_login = "";
	$search_email = "";
	$type = "";
	$sall = "";
}

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

if ($cancel) {
	$action = '';

	if (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}
}

if ($action == 'add' && $user->hasRight('adherent', 'configurer')) {
	$object->label = trim($label);
	$object->morphy = trim($morphy);
	$object->status = (int) $status;
	$object->subscription = (int) $subscription;
	$object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
	$object->caneditamount = $caneditamount;
	$object->duration_value = $duration_value;
	$object->duration_unit = $duration_unit;
	$object->note_public = trim($comment);
	$object->note_private = '';
	$object->mail_valid = trim($mail_valid);
	$object->vote = $vote;  // $vote is already int

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $object);
	if ($ret < 0) {
		$error++;
	}

	if (empty($object->label)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	} else {
		$sql = "SELECT libelle FROM ".MAIN_DB_PREFIX."adherent_type WHERE libelle = '".$db->escape($object->label)."'";
		$sql .= " WHERE entity IN (".getEntity('member_type').")";
		$result = $db->query($sql);
		$num = null;
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorLabelAlreadyExists", $object->label), null, 'errors');
		}
	}

	if (!$error) {
		$id = $object->create($user);
		if ($id > 0) {
			$backurlforlist = $_SERVER["PHP_SELF"];

			$urltogo = $backtopage ? str_replace('__ID__', (string) $id, $backtopage) : $backurlforlist;
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', (string) $object->id, $urltogo); // New method to autoselect field created after a New on another form object creation

			header("Location: " . $urltogo);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
		}
	} else {
		$action = 'create';
	}
}

if ($action == 'update' && $user->hasRight('adherent', 'configurer')) {
	$object->fetch($rowid);

	$object->oldcopy = dol_clone($object, 2);

	$object->label = trim($label);
	$object->morphy	= trim($morphy);
	$object->status	= (int) $status;
	$object->subscription = (int) $subscription;
	$object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
	$object->caneditamount = $caneditamount;
	$object->duration_value = $duration_value;
	$object->duration_unit = $duration_unit;
	$object->note_public = trim($comment);
	$object->note_private = '';
	$object->mail_valid = trim($mail_valid);
	$object->vote = $vote;  // $vote is already int.

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
	if ($ret < 0) {
		$error++;
	}

	$ret = $object->update($user);

	if ($ret >= 0 && !count($object->errors)) {
		setEventMessages($langs->trans("MemberTypeModified"), null, 'mesgs');
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?rowid=".$object->id);
	exit;
}

if ($action == 'confirm_delete' && $user->hasRight('adherent', 'configurer')) {
	$object->fetch($rowid);
	$res = $object->delete($user);

	if ($res > 0) {
		setEventMessages($langs->trans("MemberTypeDeleted"), null, 'mesgs');
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		setEventMessages($langs->trans("MemberTypeCanNotBeDeleted"), null, 'errors');
		$action = '';
	}
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

$title = $langs->trans("MembersTypeSetup");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-member page-type');

$arrayofselected = is_array($toselect) ? $toselect : array();

// List of members type
if (!$rowid && $action != 'create' && $action != 'edit') {
	//print dol_get_fiche_head([]);

	$sql = "SELECT d.rowid, d.libelle as label, d.subscription, d.amount, d.caneditamount, d.vote, d.statut as status, d.morphy, d.duration";
	$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
	$sql .= " WHERE d.entity IN (".getEntity('member_type').")";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$nbtotalofrecords = $num;

		$i = 0;

		$param = '';
		if (!empty($mode)) {
			$param .= '&mode='.urlencode($mode);
		}
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
			$param .= '&contextpage='.$contextpage;
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.$limit;
		}

		$newcardbutton = '';

		$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
		$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));

		if ($user->hasRight('adherent', 'configurer')) {
			$newcardbutton .= dolGetButtonTitleSeparator();
			$newcardbutton .= dolGetButtonTitle($langs->trans('NewMemberType'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/adherents/type.php?action=create');
		}

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		if ($optioncss != '') {
			print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		}
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="mode" value="'.$mode.'">';


		print_barre_liste($langs->trans("MembersTypes"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'members', 0, $newcardbutton, '', $limit, 0, 0, 1);

		$moreforfilter = '';

		print '<div class="div-table-responsive">';
		print '<table class="tagtable noborder liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

		print '<tr class="liste_titre">';
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<th>&nbsp;</th>';
		}
		print '<th>'.$langs->trans("Ref").'</th>';
		print '<th>'.$langs->trans("Label").'</th>';
		print '<th class="center">'.$langs->trans("MembersNature").'</th>';
		print '<th class="center">'.$langs->trans("MembershipDuration").'</th>';
		print '<th class="center">'.$langs->trans("SubscriptionRequired").'</th>';
		print '<th class="center">'.$langs->trans("Amount").'</th>';
		print '<th class="center">'.$langs->trans("CanEditAmountShort").'</th>';
		print '<th class="center">'.$langs->trans("VoteAllowed").'</th>';
		print '<th class="center">'.$langs->trans("Status").'</th>';
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<th>&nbsp;</th>';
		}
		print "</tr>\n";

		$membertype = new AdherentType($db);

		$i = 0;
		$savnbfield = 10;
		/*$savnbfield = $totalarray['nbfield'];
		$totalarray = array();
		$totalarray['nbfield'] = 0;*/

		$imaxinloop = ($limit ? min($num, $limit) : $num);
		while ($i < $imaxinloop) {
			$objp = $db->fetch_object($result);

			$membertype->id = $objp->rowid;
			$membertype->ref = $objp->rowid;
			$membertype->label = $objp->rowid;
			$membertype->status = $objp->status;
			$membertype->subscription = $objp->subscription;
			$membertype->amount = $objp->amount;
			$membertype->caneditamount = $objp->caneditamount;

			if ($mode == 'kanban') {
				if ($i == 0) {
					print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
					print '<div class="box-flex-container kanban">';
				}
				//output kanban
				$membertype->label = $objp->label;
				print $membertype->getKanbanView('', array('selected' => in_array($object->id, $arrayofselected)));
				if ($i == ($imaxinloop - 1)) {
					print '</div>';
					print '</td></tr>';
				}
			} else {
				print '<tr class="oddeven">';

				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					if ($user->hasRight('adherent', 'configurer')) {
						print '<td class="center"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
					}
				}

				print '<td class="nowraponall">';
				print $membertype->getNomUrl(1);
				//<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowType"),'group').' '.$objp->rowid.'</a>
				print '</td>';

				print '<td>'.dol_escape_htmltag($objp->label).'</td>';

				print '<td class="center">';
				if ($objp->morphy == 'phy') {
					print $langs->trans("Physical");
				} elseif ($objp->morphy == 'mor') {
					print $langs->trans("Moral");
				} else {
					print $langs->trans("MorAndPhy");
				}
				print '</td>';

				print '<td class="center nowrap">';
				if ($objp->duration) {
					$duration_value = intval($objp->duration);
					if ($duration_value > 1) {
						$dur = array("i" => $langs->trans("Minutes"), "h" => $langs->trans("Hours"), "d" => $langs->trans("Days"), "w" => $langs->trans("Weeks"), "m" => $langs->trans("Months"), "y" => $langs->trans("Years"));
					} else {
						$dur = array("i" => $langs->trans("Minute"), "h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
					}
					$unit = preg_replace("/[^a-zA-Z]+/", "", $objp->duration);
					print max(1, $duration_value).' '.$dur[$unit];
				}
				print '</td>';

				print '<td class="center">'.yn($objp->subscription).'</td>';

				print '<td class="center"><span class="amount">'.(is_null($objp->amount) || $objp->amount === '' ? '' : price($objp->amount)).'</span></td>';

				print '<td class="center">'.yn($objp->caneditamount).'</td>';

				print '<td class="center">'.yn($objp->vote).'</td>';

				print '<td class="center">'.$membertype->getLibStatut(5).'</td>';

				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					if ($user->hasRight('adherent', 'configurer')) {
						print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$objp->rowid.'">'.img_edit().'</a></td>';
					}
				}
				print "</tr>";
			}
			$i++;
		}

		// Show total line
		include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

		// If no record found
		if ($num == 0) {
			$colspan = 1;
			foreach ($arrayfields as $key => $val) {
				//if (!empty($val['checked'])) {
					$colspan++;
				//}
			}
			print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
		}

		print "</table>";
		print '</div>';

		print '</form>';
	} else {
		dol_print_error($db);
	}
}

// Creation
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewMemberType"), '', 'members');

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head(array());

	print '<table class="border centpercent">';
	print '<tbody>';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" class="minwidth200" name="label" autofocus="autofocus"></td></tr>';

	print '<tr><td>'.$langs->trans("Status").'</td><td>';
	print $form->selectarray('status', array('0' => $langs->trans('ActivityCeased'), '1' => $langs->trans('InActivity')), 1, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
	print '</td></tr>';

	// Morphy
	$morphys = array();
	$morphys[""] = $langs->trans("MorAndPhy");
	$morphys["phy"] = $langs->trans("Physical");
	$morphys["mor"] = $langs->trans("Moral");
	print '<tr><td><span>'.$langs->trans("MembersNature").'</span></td><td>';
	print $form->selectarray("morphy", $morphys, GETPOSTISSET("morphy") ? GETPOST("morphy", 'aZ09') : 'morphy');
	print "</td></tr>";

	print '<tr><td>'.$form->textwithpicto($langs->trans("SubscriptionRequired"), $langs->trans("SubscriptionRequiredDesc")).'</td><td>';
	print $form->selectyesno("subscription", 1, 1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td>';
	print '<input name="amount" size="5" value="'.(GETPOSTISSET('amount') ? GETPOST('amount') : price($amount)).'">';
	print '</td></tr>';

	print '<tr><td>'.$form->textwithpicto($langs->trans("CanEditAmountShort"), $langs->transnoentities("CanEditAmount")).'</td><td>';
	print $form->selectyesno("caneditamount", GETPOSTISSET('caneditamount') ? GETPOST('caneditamount') : 0, 1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
	print $form->selectyesno("vote", GETPOSTISSET("vote") ? GETPOST('vote', 'aZ09') : 1, 1);
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3">';
	print '<input name="duration_value" size="5" value="'.GETPOST('duraction_unit', 'aZ09').'"> ';
	print $formproduct->selectMeasuringUnits("duration_unit", "time", GETPOSTISSET("duration_unit") ? GETPOST('duration_unit', 'aZ09') : 'y', 0, 1);
	print '</td></tr>';

	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('comment', (GETPOSTISSET('comment') ? GETPOST('comment', 'restricthtml') : $object->note_public), '', 200, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
	$doleditor->Create();

	print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('mail_valid', GETPOSTISSET('mail_valid') ? GETPOST('mail_valid') : $object->mail_valid, '', 250, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '<tbody>';
	print "</table>\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print "</form>\n";
}

// View
if ($rowid > 0) {
	if ($action != 'edit') {
		$object = new AdherentType($db);
		$object->fetch($rowid);
		$object->fetch_optionals();

		/*
		 * Confirmation deletion
		 */
		if ($action == 'delete') {
			print $form->formconfirm($_SERVER['PHP_SELF']."?rowid=".$object->id, $langs->trans("DeleteAMemberType"), $langs->trans("ConfirmDeleteMemberType", $object->label), "confirm_delete", '', 0, 1);
		}

		$head = member_type_prepare_head($object);

		print dol_get_fiche_head($head, 'card', $langs->trans("MemberType"), -1, 'group');

		$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'rowid', $linkback);

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="tableforfield border centpercent">';

		// Morphy
		print '<tr><td>'.$langs->trans("MembersNature").'</td><td class="valeur" >'.$object->getmorphylib($object->morphy).'</td>';
		print '</tr>';

		print '<tr><td>'.$form->textwithpicto($langs->trans("SubscriptionRequired"), $langs->trans("SubscriptionRequiredDesc")).'</td><td>';
		print yn($object->subscription);
		print '</tr>';

		// Amount
		print '<tr><td class="titlefield">'.$langs->trans("Amount").'</td><td>';
		print((is_null($object->amount) || $object->amount === '') ? '' : '<span class="amount">'.price($object->amount).'</span>');
		print '</tr>';

		print '<tr><td>'.$form->textwithpicto($langs->trans("CanEditAmountShort"), $langs->transnoentities("CanEditAmount")).'</td><td>';
		print yn($object->caneditamount);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print yn($object->vote);
		print '</tr>';

		// Duration
		print '<tr><td class="titlefield">'.$langs->trans("Duration").'</td><td colspan="2">'.$object->duration_value.'&nbsp;';
		if ($object->duration_value > 1) {
			$dur = array("i" => $langs->trans("Minutes"), "h" => $langs->trans("Hours"), "d" => $langs->trans("Days"), "w" => $langs->trans("Weeks"), "m" => $langs->trans("Months"), "y" => $langs->trans("Years"));
		} elseif ($object->duration_value > 0) {
			$dur = array("i" => $langs->trans("Minute"), "h" => $langs->trans("Hour"), "d" => $langs->trans("Day"), "w" => $langs->trans("Week"), "m" => $langs->trans("Month"), "y" => $langs->trans("Year"));
		}
		print(!empty($object->duration_unit) && isset($dur[$object->duration_unit]) ? $langs->trans($dur[$object->duration_unit]) : '')."&nbsp;";
		print '</td></tr>';

		// Description
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td><div class="longmessagecut">';
		print dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->note_public));
		print "</div></td></tr>";

		// Welcome email content
		print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td><div class="longmessagecut">';
		print dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->mail_valid));
		print "</div></td></tr>";

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';
		print '</div>';

		print dol_get_fiche_end();


		/*
		 * Buttons
		 */

		print '<div class="tabsAction">';

		// Edit
		if ($user->hasRight('adherent', 'configurer')) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().'&rowid='.$object->id.'">'.$langs->trans("Modify").'</a></div>';
		}

		// Add
		if ($object->morphy == 'phy') {
			$morphy = 'phy';
		} elseif ($object->morphy == 'mor') {
			$morphy = 'mor';
		} else {
			$morphy = '';
		}

		if ($user->hasRight('adherent', 'configurer') && !empty($object->status)) {
			print '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=create&token='.newToken().'&typeid='.$object->id.($morphy ? '&morphy='.urlencode($morphy) : '').'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?rowid='.$object->id).'">'.$langs->trans("AddMember").'</a></div>';
		} else {
			print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NoAddMember")).'">'.$langs->trans("AddMember").'</a></div>';
		}

		// Delete
		if ($user->hasRight('adherent', 'configurer')) {
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&rowid='.$object->id.'">'.$langs->trans("DeleteType").'</a></div>';
		}

		print "</div>";


		// Show list of members (nearly same code than in page list.php)

		$membertypestatic = new AdherentType($db);

		$now = dol_now();

		$sql = "SELECT d.rowid, d.ref, d.entity, d.login, d.firstname, d.lastname, d.societe as company, d.fk_soc,";
		$sql .= " d.datefin,";
		$sql .= " d.email, d.photo, d.fk_adherent_type as type_id, d.morphy, d.statut as status,";
		$sql .= " t.libelle as type, t.subscription, t.amount";

		$sqlfields = $sql; // $sql fields to remove for count total

		$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
		$sql .= " WHERE d.fk_adherent_type = t.rowid ";
		$sql .= " AND d.entity IN (".getEntity('adherent').")";
		$sql .= " AND t.rowid = ".((int) $object->id);
		if ($sall) {
			$sql .= natural_search(array("d.firstname", "d.lastname", "d.societe", "d.email", "d.login", "d.address", "d.town", "d.note_public", "d.note_private"), $sall);
		}
		if ($status != '') {
			$sql .= natural_search('d.statut', $status, 2);
		}
		if ($action == 'search') {
			if (GETPOST('search', 'alpha')) {
				$sql .= natural_search(array("d.firstname", "d.lastname"), GETPOST('search', 'alpha'));
			}
		}
		if (!empty($search_ref)) {
			$sql .= natural_search("d.ref", $search_ref);
		}
		if (!empty($search_lastname)) {
			$sql .= natural_search(array("d.firstname", "d.lastname"), $search_lastname);
		}
		if (!empty($search_login)) {
			$sql .= natural_search("d.login", $search_login);
		}
		if (!empty($search_email)) {
			$sql .= natural_search("d.email", $search_email);
		}
		if ($filter == 'uptodate') {
			$sql .= " AND (datefin >= '".$db->idate($now)."') OR t.subscription = 0)";
		}
		if ($filter == 'outofdate') {
			$sql .= " AND (datefin < '".$db->idate($now)."' AND t.subscription = 1)";
		}

		// Count total nb of records
		$nbtotalofrecords = '';
		if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
			/* The fast and low memory method to get and count full list converts the sql into a sql count */
			$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
			$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
			$resql = $db->query($sqlforcount);
			if ($resql) {
				$objforcount = $db->fetch_object($resql);
				$nbtotalofrecords = $objforcount->nbtotalofrecords;
			} else {
				dol_print_error($db);
			}

			if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
				$page = 0;
				$offset = 0;
			}
			$db->free($resql);
		}

		// Complete request and execute it with limit
		$sql .= $db->order($sortfield, $sortorder);
		if ($limit) {
			$sql .= $db->plimit($limit + 1, $offset);
		}

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			$titre = $langs->trans("MembersList");
			if ($status != '') {
				if ($status == '-1,1') {
					$titre = $langs->trans("MembersListQualified");
				} elseif ($status == '-1') {
					$titre = $langs->trans("MembersListToValid");
				} elseif ($status == '1' && !$filter) {
					$titre = $langs->trans("MembersListValid");
				} elseif ($status == '1' && $filter == 'uptodate') {
					$titre = $langs->trans("MembersListUpToDate");
				} elseif ($status == '1' && $filter == 'outofdate') {
					$titre = $langs->trans("MembersListNotUpToDate");
				} elseif ($status == '0') {
					$titre = $langs->trans("MembersListResiliated");
				} elseif ($status == '-2') {
					$titre = $langs->trans("MembersListExcluded");
				}
			} elseif ($action == 'search') {
				$titre = $langs->trans("MembersListQualified");
			}

			if ($type > 0) {
				$membertype = new AdherentType($db);
				$result = $membertype->fetch($type);
				$titre .= " (".$membertype->label.")";
			}

			$param = "&rowid=".urlencode((string) ($object->id));
			if (!empty($mode)) {
				$param .= '&mode='.urlencode($mode);
			}
			if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
				$param .= '&contextpage='.urlencode($contextpage);
			}
			if ($limit > 0 && $limit != $conf->liste_limit) {
				$param .= '&limit='.((int) $limit);
			}
			if (!empty($status)) {
				$param .= "&status=".urlencode($status);
			}
			if (!empty($search_ref)) {
				$param .= "&search_ref=".urlencode($search_ref);
			}
			if (!empty($search_lastname)) {
				$param .= "&search_lastname=".urlencode($search_lastname);
			}
			if (!empty($search_login)) {
				$param .= "&search_login=".urlencode($search_login);
			}
			if (!empty($search_email)) {
				$param .= "&search_email=".urlencode($search_email);
			}
			if (!empty($filter)) {
				$param .= "&filter=".urlencode($filter);
			}

			if ($sall) {
				print $langs->trans("Filter")." (".$langs->trans("Lastname").", ".$langs->trans("Firstname").", ".$langs->trans("EMail").", ".$langs->trans("Address")." ".$langs->trans("or")." ".$langs->trans("Town")."): ".$sall;
			}

			print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="formfilter" autocomplete="off">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input class="flat" type="hidden" name="rowid" value="'.$object->id.'"></td>';
			print '<input class="flat" type="hidden" name="page_y" value=""></td>';

			print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'generic', 0, '', '', $limit);

			$moreforfilter = '';

			print '<div class="div-table-responsive">';
			print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

			// Fields title search
			print '<tr class="liste_titre_filter">';

			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="liste_titre center maxwidthsearch">';
				$searchpicto = $form->showFilterButtons('left');
				print $searchpicto;
				print '</td>';
			}

			print '<td class="liste_titre left">';
			print '<input class="flat maxwidth100" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';

			print '<td class="liste_titre left">';
			print '<input class="flat maxwidth100" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'"></td>';

			print '<td class="liste_titre left">';
			print '<input class="flat maxwidth100" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'"></td>';

			print '<td class="liste_titre">&nbsp;</td>';

			print '<td class="liste_titre left">';
			print '<input class="flat maxwidth100" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'"></td>';

			print '<td class="liste_titre">&nbsp;</td>';

			print '<td class="liste_titre">&nbsp;</td>';

			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="liste_titre center nowraponall">';
				print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
				print '&nbsp; ';
				print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
				print '</td>';
			}

			print "</tr>\n";

			print '<tr class="liste_titre">';
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", $param, "", 'width="60" align="center"', $sortfield, $sortorder);
			}
			print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "d.ref", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("NameSlashCompany", $_SERVER["PHP_SELF"], "d.lastname", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("Login", $_SERVER["PHP_SELF"], "d.login", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("MemberNature", $_SERVER["PHP_SELF"], "d.morphy", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("EMail", $_SERVER["PHP_SELF"], "d.email", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "d.statut,d.datefin", $param, "", "", $sortfield, $sortorder);
			print_liste_field_titre("EndSubscription", $_SERVER["PHP_SELF"], "d.datefin", $param, "", 'align="center"', $sortfield, $sortorder);
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print_liste_field_titre("Action", $_SERVER["PHP_SELF"], "", $param, "", 'width="60" align="center"', $sortfield, $sortorder);
			}
			print "</tr>\n";

			$adh = new Adherent($db);

			$imaxinloop = ($limit ? min($num, $limit) : $num);
			while ($i < $imaxinloop) {
				$objp = $db->fetch_object($resql);

				$datefin = $db->jdate($objp->datefin);

				$adh->id = $objp->rowid;
				$adh->ref = $objp->ref;
				$adh->login = $objp->login;
				$adh->lastname = $objp->lastname;
				$adh->firstname = $objp->firstname;
				$adh->datefin = $datefin;
				$adh->need_subscription = $objp->subscription;
				$adh->statut = $objp->status;
				$adh->status = $objp->status;
				$adh->email = $objp->email;
				$adh->photo = $objp->photo;

				print '<tr class="oddeven">';

				// Actions
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="center">';
					if ($user->hasRight('adherent', 'creer')) {
						print '<a class="editfielda marginleftonly" href="card.php?rowid='.$objp->rowid.'&action=edit&token='.newToken().'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?rowid='.$object->id).'">'.img_edit().'</a>';
					}
					if ($user->hasRight('adherent', 'supprimer')) {
						print '<a class="marginleftonly" href="card.php?rowid='.$objp->rowid.'&action=resiliate&token='.newToken().'">'.img_picto($langs->trans("Resiliate"), 'disable.png').'</a>';
					}
					print "</td>";
				}

				// Ref
				print "<td>";
				print $adh->getNomUrl(-1, 0, 'card', 'ref', '', -1, 0, 1);
				print "</td>\n";

				// Lastname
				if ($objp->company != '') {
					print '<td><a href="card.php?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowMember"), "user", 'class="paddingright"').$adh->getFullName($langs, 0, -1, 20).' / '.dol_trunc($objp->company, 12).'</a></td>'."\n";
				} else {
					print '<td><a href="card.php?rowid='.$objp->rowid.'">'.img_object($langs->trans("ShowMember"), "user", 'class="paddingright"').$adh->getFullName($langs, 0, -1, 32).'</a></td>'."\n";
				}

				// Login
				print "<td>".dol_escape_htmltag($objp->login)."</td>\n";

				// Type
				/*print '<td class="nowrap">';
				$membertypestatic->id=$objp->type_id;
				$membertypestatic->label=$objp->type;
				print $membertypestatic->getNomUrl(1,12);
				print '</td>';
				*/

				// Moral/Physique
				print "<td>".$adh->getmorphylib($objp->morphy, 1)."</td>\n";

				// EMail
				print "<td>".dol_print_email($objp->email, 0, 0, 1)."</td>\n";

				// Status
				print '<td class="nowrap">';
				print $adh->getLibStatut(2);
				print "</td>";

				// Date end subscription
				if ($datefin) {
					print '<td class="nowrap center">';
					if ($datefin < dol_now() && $objp->status > 0) {
						print dol_print_date($datefin, 'day')." ".img_warning($langs->trans("SubscriptionLate"));
					} else {
						print dol_print_date($datefin, 'day');
					}
					print '</td>';
				} else {
					print '<td class="nowrap center">';
					if (!empty($objp->subscription)) {
						print '<span class="opacitymedium">'.$langs->trans("SubscriptionNotReceived").'</span>';
						if ($objp->status > 0) {
							print " ".img_warning();
						}
					} else {
						print '&nbsp;';
					}
					print '</td>';
				}

				// Actions
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="center">';
					if ($user->hasRight('adherent', 'creer')) {
						print '<a class="editfielda marginleftonly" href="card.php?rowid='.$objp->rowid.'&action=edit&token='.newToken().'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?rowid='.$object->id).'">'.img_edit().'</a>';
					}
					if ($user->hasRight('adherent', 'supprimer')) {
						print '<a class="marginleftonly" href="card.php?rowid='.$objp->rowid.'&action=resiliate&token='.newToken().'">'.img_picto($langs->trans("Resiliate"), 'disable.png').'</a>';
					}
					print "</td>";
				}
				print "</tr>\n";
				$i++;
			}

			if ($i == 0) {
				print '<tr><td colspan="9"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}

			print "</table>\n";
			print '</div>';
			print '</form>';
		} else {
			dol_print_error($db);
		}
	}

	/* ************************************************************************** */
	/*                                                                            */
	/* Edition mode                                                               */
	/*                                                                            */
	/* ************************************************************************** */

	if ($action == 'edit') {
		$object = new AdherentType($db);
		$object->fetch($rowid);
		$object->fetch_optionals();

		$head = member_type_prepare_head($object);

		print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="rowid" value="'.$object->id.'">';
		print '<input type="hidden" name="action" value="update">';

		print dol_get_fiche_head($head, 'card', $langs->trans("MemberType"), 0, 'group');

		print '<table class="border centpercent">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->id.'</td></tr>';

		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input type="text" class="minwidth300" name="label" value="'.dol_escape_htmltag($object->label).'"></td></tr>';

		print '<tr><td>'.$langs->trans("Status").'</td><td>';
		print $form->selectarray('status', array('0' => $langs->trans('ActivityCeased'), '1' => $langs->trans('InActivity')), $object->status, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
		print '</td></tr>';

		// Morphy
		$morphys[""] = $langs->trans("MorAndPhy");
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		print '<tr><td><span>'.$langs->trans("MembersNature").'</span></td><td>';
		print $form->selectarray("morphy", $morphys, GETPOSTISSET("morphy") ? GETPOST("morphy", 'aZ09') : $object->morphy);
		print "</td></tr>";

		print '<tr><td>'.$langs->trans("SubscriptionRequired").'</td><td>';
		print $form->selectyesno("subscription", $object->subscription, 1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Amount").'</td><td>';
		print '<input name="amount" size="5" value="';
		print((is_null($object->amount) || $object->amount === '') ? '' : price($object->amount));
		print '">';
		print '</td></tr>';

		print '<tr><td>'.$form->textwithpicto($langs->trans("CanEditAmountShort"), $langs->transnoentities("CanEditAmountDetail")).'</td><td>';
		print $form->selectyesno("caneditamount", $object->caneditamount, 1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("VoteAllowed").'</td><td>';
		print $form->selectyesno("vote", $object->vote, 1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3">';
		print '<input name="duration_value" size="5" value="'.$object->duration_value.'"> ';
		print $formproduct->selectMeasuringUnits("duration_unit", "time", ($object->duration_unit === '' ? 'y' : $object->duration_unit), 0, 1);
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('comment', $object->note_public, '', 220, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
		$doleditor->Create();
		print "</td></tr>";

		print '<tr><td class="tdtop">'.$langs->trans("WelcomeEMail").'</td><td>';
		$doleditor = new DolEditor('mail_valid', $object->mail_valid, '', 280, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor'), 15, '90%');
		$doleditor->Create();
		print "</td></tr>";

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

		print '</table>';

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel();

		print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
