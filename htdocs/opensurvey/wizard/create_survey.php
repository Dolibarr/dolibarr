<?php
/* Copyright (C) 2013-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015-2016  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/opensurvey/wizard/create_survey.php
 *	\ingroup    opensurvey
 *	\brief      Page to create a new survey
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/lib/opensurvey.lib.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Security check
if (!$user->hasRight('opensurvey', 'write')) {
	accessforbidden();
}

$langs->load("opensurvey");

$title = GETPOST('title');
$description = GETPOST('description', 'restricthtml');
$mailsonde = GETPOST('mailsonde');
$creation_sondage_date = GETPOST('creation_sondage_date');
$creation_sondage_autre = GETPOST('creation_sondage_autre');

// We init some session variable to avoir warning
$session_var = array('title', 'description', 'mailsonde');
foreach ($session_var as $var) {
	if (isset($_SESSION[$var])) {
		$_SESSION[$var] = null;
	}
}

// On initialise également les autres variables
$cocheplus = '';
$cochemail = '';

// Jump to correct page
if (!empty($creation_sondage_date) || !empty($creation_sondage_autre)) {
	$error = 0;

	$_SESSION["title"] = $title;
	$_SESSION["description"] = $description;

	if (GETPOST('mailsonde') == 'on') {
		$_SESSION["mailsonde"] = true;
	} else {
		$_SESSION["mailsonde"] = false;
	}

	if (GETPOST('allow_comments') == 'on') {
		$_SESSION['allow_comments'] = true;
	} else {
		$_SESSION['allow_comments'] = false;
	}

	if (GETPOST('allow_spy') == 'on') {
		$_SESSION['allow_spy'] = true;
	} else {
		$_SESSION['allow_spy'] = false;
	}

	$testdate = false;
	$champdatefin = (int) dol_mktime(23, 59, 59, GETPOSTINT('champdatefinmonth'), GETPOSTINT('champdatefinday'), GETPOSTINT('champdatefinyear'));

	if ($champdatefin > 0) {	// A date was provided
		// Expire date is not before today
		if ($champdatefin >= dol_now()) {
			$testdate = true;
			$_SESSION['champdatefin'] = dol_print_date($champdatefin, 'dayrfc');
		} else {
			$error++;
			$testdate = true;
			$_SESSION['champdatefin'] = dol_print_date($champdatefin, 'dayrfc');
			//$testdate = false;
			//$_SESSION['champdatefin'] = dol_print_date($champdatefin,'dayrfc');
			setEventMessages($langs->trans('ErrorDateMustBeInFuture'), null, 'errors');
		}
	}

	if (!$testdate) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("ExpireDate")), null, 'errors');
	}

	if (!$error && $title && $testdate) {
		if (!empty($creation_sondage_date)) {
			header("Location: choix_date.php");
			exit();
		}

		if (!empty($creation_sondage_autre)) {
			header("Location: choix_autre.php");
			exit();
		}
	}
}




/*
 * View
 */

$form = new Form($db);

$arrayofjs = array();
$arrayofcss = array('/opensurvey/css/style.css');
llxHeader('', $langs->trans("OpenSurvey"), '', "", 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre($langs->trans("CreatePoll").' (1 / 2)');


print '<form name="formulaire" action="" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head();

print '<table class="border centpercent">'."\n";

print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("PollTitle").'</td>';

print '<td><input type="text" name="title" class="minwidth300" maxlength="80" value="'.$_SESSION["title"].'" autofocus></td>'."\n";
if (!$_SESSION["title"] && (GETPOST('creation_sondage_date') || GETPOST('creation_sondage_autre'))) {
	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PollTitle")), null, 'errors');
}

print '</tr>'."\n";
print '<tr><td>'.$langs->trans("Description").'</td><td>';
$doleditor = new DolEditor('description', $_SESSION["description"], '', 120, 'dolibarr_notes', 'In', true, 1, 1, ROWS_7, '90%');
$doleditor->Create(0, '');
print '</td>'."\n";
print '</tr>'."\n";

print '<tr><td class="fieldrequired">'.$langs->trans("ExpireDate").'</td><td>';

print $form->selectDate($champdatefin ? $champdatefin : -1, 'champdatefin', 0, 0, 0, "add", 1, 0);

print '</tr>'."\n";
print '</table>'."\n";

print dol_get_fiche_end();

print '<br>'."\n";

// Check or not

if ($_SESSION["mailsonde"]) {
	$cochemail = "checked";
}

print '<input type="checkbox" id="mailsonde" name="mailsonde" '.$cochemail.'> <label for="mailsonde">'.$langs->trans("ToReceiveEMailForEachVote").'</label><br>'."\n";

if ($_SESSION['allow_comments']) {
	$allow_comments = 'checked';
}
if (GETPOSTISSET('allow_comments')) {
	$allow_comments = GETPOST('allow_comments') ? 'checked' : '';
}
print '<input type="checkbox" id="allow_comments" name="allow_comments" '.$allow_comments.'"> <label for="allow_comments">'.$langs->trans('CanComment').'</label><br>'."\n";

if ($_SESSION['allow_spy']) {
	$allow_spy = 'checked';
}
if (GETPOSTISSET('allow_spy')) {
	$allow_spy = GETPOST('allow_spy') ? 'checked' : '';
}
print '<input type="checkbox" id="allow_spy" name="allow_spy" '.$allow_spy.'> <label for="allow_spy">'.$langs->trans('CanSeeOthersVote').'</label><br>'."\n";

if (GETPOST('choix_sondage')) {
	if (GETPOST('choix_sondage') == 'date') {
		print '<input type="hidden" name="creation_sondage_date" value="date">';
	} else {
		print '<input type="hidden" name="creation_sondage_autre" value="autre">';
	}
	print '<input type="hidden" name="choix_sondage" value="'.GETPOST('choix_sondage').'">';
	print '<br><input type="submit" class="button" name="submit" value="'.$langs->trans("CreatePoll").' ('.(GETPOST('choix_sondage') == 'date' ? $langs->trans("TypeDate") : $langs->trans("TypeClassic")).')">';
} else {
	// Show image to select between date survey or other survey
	print '<br><table>'."\n";
	print '<tr><td>'.$langs->trans("CreateSurveyDate").'</td><td></td> '."\n";
	print '<td><input type="image" name="creation_sondage_date" value="'.$langs->trans('CreateSurveyDate').'" src="../img/calendar-32.png"></td></tr>'."\n";
	print '<tr><td>'.$langs->trans("CreateSurveyStandard").'</td><td></td> '."\n";
	print '<td><input type="image" name="creation_sondage_autre" value="'.$langs->trans('CreateSurveyStandard').'" src="../img/chart-32.png"></td></tr>'."\n";
	print '</table>'."\n";
}
print '<br><br><br>'."\n";
print '</form>'."\n";

// End of page
llxFooter();
$db->close();
