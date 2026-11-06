<?php
/* Copyright (C) 2026 Dolibarr development team
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
 *	\file       htdocs/opensurvey/wizard/choix_form.php
 *	\ingroup    opensurvey
 *	\brief      Page to create a new survey of format 'F' (form): the questions
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/lib/opensurvey.lib.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveyquestion.class.php";

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

$maxquestions = 50;


/*
 * Action
 */

$arrayofquestions = GETPOST('qquestion', 'array:alphanohtml');
$arrayoftypes = GETPOST('qtype', 'array:alpha');
$arrayofanswers = GETPOST('qanswers', 'array:alphanohtml');
$arrayofmandatory = GETPOST('qmandatory', 'array:int');

if (!isset($_SESSION["nbquestions"])) {
	$_SESSION["nbquestions"] = 5;
}

if (GETPOST("ajoutquestions")) {
	if ($_SESSION["nbquestions"] < $maxquestions) {
		$_SESSION["nbquestions"] += 5;
	}
}

// Create survey into database
if (GETPOSTISSET("confirmecreation")) {
	if (empty($_SESSION['title'])) {
		dol_print_error(null, $langs->trans('ErrorOpenSurveyFillFirstSection'));
		exit;
	}

	$rows = array();
	foreach ($arrayofquestions as $i => $text) {
		$rows[] = array(
			'question' => $text,
			'type' => isset($arrayoftypes[$i]) ? $arrayoftypes[$i] : '',
			'available_answers' => isset($arrayofanswers[$i]) ? $arrayofanswers[$i] : '',
			'mandatory' => !empty($arrayofmandatory[$i]) ? 1 : 0,
		);
	}

	$errors = array();
	$questions = Opensurveyquestion::buildList($db, '', $rows, $errors);

	if (empty($questions) && empty($errors)) {
		setEventMessages($langs->trans("ErrorOpenSurveyOneQuestion"), null, 'errors');
	} elseif (!empty($errors)) {
		foreach (array_unique($errors) as $errorkey) {
			setEventMessages($langs->trans($errorkey), null, 'errors');
		}
	} else {
		// format of survey FORM
		$_SESSION["formatsondage"] = "F";
		$_SESSION["toutchoix"] = '';

		// Add into database
		ajouter_sondage($questions);
	}
}

/*
 * View
 */

$form = new Form($db);
$typesofquestion = Opensurveyquestion::getTypes($langs);

$arrayofjs = array();
$arrayofcss = array('/opensurvey/css/style.css');
llxHeader('', $langs->trans("OpenSurvey"), "", '', 0, 0, $arrayofjs, $arrayofcss);

if (empty($_SESSION['title'])) {
	dol_print_error(null, $langs->trans('ErrorOpenSurveyFillFirstSection'));
	llxFooter();
	exit;
}

print '<form name="formulaire" id="surveyform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';

print load_fiche_titre($langs->trans("CreatePoll").' (2 / 2)');

print '<br>'.$langs->trans("PollOnForm").'<br><br>'."\n";

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre"><td>'.$langs->trans("OpenSurveyQuestion").'</td><td>'.$langs->trans("Type").'</td>';
print '<td>'.$langs->trans("OpenSurveyAvailableAnswersOnePerLine").'</td><td class="center">'.$langs->trans("OpenSurveyQuestionMandatory").'</td></tr>'."\n";

// Display form fields
for ($i = 0; $i < $_SESSION["nbquestions"]; $i++) {
	$valuetext = isset($arrayofquestions[$i]) ? $arrayofquestions[$i] : '';
	$valuetype = isset($arrayoftypes[$i]) ? $arrayoftypes[$i] : Opensurveyquestion::TYPE_RADIO;
	$valueanswers = isset($arrayofanswers[$i]) ? $arrayofanswers[$i] : '';

	print '<tr class="oddeven">';
	print '<td><input type="text" name="qquestion['.$i.']" class="minwidth300" maxlength="255" placeholder="'.dol_escape_htmltag($langs->trans("OpenSurveyQuestionNumber", $i + 1)).'" value="'.dol_escape_htmltag($valuetext).'"></td>';
	print '<td>'.$form->selectarray("qtype[".$i."]", $typesofquestion, $valuetype, 0, 0, 0, '', 0, 0, 0, '', 'minwidth200', 1).'</td>';
	print '<td><textarea name="qanswers['.$i.']" rows="3" class="quatrevingtpercent">'.dol_escape_htmltag($valueanswers, 0, 1).'</textarea></td>';
	print '<td class="center"><input type="checkbox" name="qmandatory['.$i.']" value="1"'.(!empty($arrayofmandatory[$i]) ? ' checked' : '').'></td>';
	print '</tr>'."\n";
}

print '</table>'."\n";
print '</div>';

// Add extra questions
if ($_SESSION["nbquestions"] < $maxquestions) {
	print '<br><input type="submit" class="button small" name="ajoutquestions" value="'.dol_escape_htmltag($langs->trans("5MoreQuestions")).'">';
}
print '<br><br>'."\n";

print '<input type="submit" class="button" name="confirmecreation" value="'.dol_escape_htmltag($langs->trans("CreatePoll")).'">'."\n";

print '</form>'."\n";

print '<br><br><br>'."\n";

// End of page
llxFooter();
$db->close();
