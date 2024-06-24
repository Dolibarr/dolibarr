<?php
/* Copyright (C) 2013-2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Marcos García       <marcosgdf@gmail.com>
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
 *	\file       htdocs/public/opensurvey/studs.php
 *	\ingroup    opensurvey
 *	\brief      Page to list surveys
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveysondage.class.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/lib/opensurvey.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


// Init vars
$action = GETPOST('action', 'aZ09');
$numsondage = '';
if (GETPOST('sondage')) {
	$numsondage = GETPOST('sondage', 'alpha');
}

$object = new Opensurveysondage($db);
$result = $object->fetch(0, $numsondage);

$nblines = $object->fetch_lines();

//If the survey has not yet finished, then it can be modified
$canbemodified = ((empty($object->date_fin) || $object->date_fin > dol_now()) && $object->status != Opensurveysondage::STATUS_CLOSED);

// Security check
if (!isModEnabled('opensurvey')) {
	httponly_accessforbidden('Module Survey not enabled');
}


/*
 * Actions
 */

$nbcolonnes = substr_count($object->sujet, ',') + 1;

$listofvoters = explode(',', $_SESSION["savevoter"]);

// Add comment
if (GETPOST('ajoutcomment', 'alpha')) {
	if (!$canbemodified) {
		httponly_accessforbidden('ErrorForbidden');
	}

	$error = 0;

	$comment = GETPOST("comment", 'alphanohtml');
	$comment_user = GETPOST('commentuser', 'alphanohtml');

	if (!$comment) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Comment")), null, 'errors');
	}
	if (!$comment_user) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Name")), null, 'errors');
	}

	if (!in_array($comment_user, $listofvoters)) {
		setEventMessages($langs->trans("UserMustBeSameThanUserUsedToVote"), null, 'errors');
		$error++;
	}

	$user_ip = getUserRemoteIP();
	$nb_post_max = getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 200);
	$now = dol_now();
	$minmonthpost = dol_time_plus_duree($now, -1, "m");
	// Calculate nb of post for IP
	$nb_post_ip = 0;
	if ($nb_post_max > 0) {	// Calculate only if there is a limit to check
		$sql = "SELECT COUNT(id_comment) as nb_comments";
		$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_comments";
		$sql .= " WHERE ip = '".$db->escape($user_ip)."'";
		$sql .= " AND date_creation > '".$db->idate($minmonthpost)."'";
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$i++;
				$obj = $db->fetch_object($resql);
				$nb_post_ip = $obj->nb_comments;
			}
		}
	}

	if ($nb_post_max > 0 && $nb_post_ip >= $nb_post_max) {
		setEventMessages($langs->trans("AlreadyTooMuchPostOnThisIPAdress"), null, 'errors');
		$error++;
	}

	if (!$error) {
		$resql = $object->addComment($comment, $comment_user, $user_ip);

		if (!$resql) {
			dol_print_error($db);
		}
	}
}

// Add vote
if (GETPOST("boutonp") || GETPOST("boutonp.x") || GETPOST("boutonp_x")) {		// boutonp for chrome, boutonp_x for firefox
	if (!$canbemodified) {
		httponly_accessforbidden('ErrorForbidden');
	}

	//Si le nom est bien entré
	if (GETPOST('nom', 'alphanohtml')) {
		$nouveauchoix = '';
		for ($i = 0; $i < $nbcolonnes; $i++) {
			if (GETPOSTISSET("choix".$i) && GETPOST("choix".$i) == '1') {
				$nouveauchoix .= "1";
			} elseif (GETPOSTISSET("choix".$i) && GETPOST("choix".$i) == '2') {
				$nouveauchoix .= "2";
			} else {
				$nouveauchoix .= "0";
			}
		}

		$user_ip = getUserRemoteIP();
		$nb_post_max = getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 200);
		$now = dol_now();
		$minmonthpost = dol_time_plus_duree($now, -1, "m");
		// Calculate nb of post for IP
		$nb_post_ip = 0;
		if ($nb_post_max > 0) {	// Calculate only if there is a limit to check
			$sql = "SELECT COUNT(id_users) as nb_records";
			$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
			$sql .= " WHERE ip = '".$db->escape($user_ip)."'";
			$sql .= " AND date_creation > '".$db->idate($minmonthpost)."'";
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$i++;
					$obj = $db->fetch_object($resql);
					$nb_post_ip = $obj->nb_records;
				}
			}
		}


		$nom = substr(GETPOST("nom", 'alphanohtml'), 0, 64);

		// Check if vote already exists
		$sql = 'SELECT id_users, nom as name';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'opensurvey_user_studs';
		$sql .= " WHERE id_sondage='".$db->escape($numsondage)."' AND nom = '".$db->escape($nom)."' ORDER BY id_users";
		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}

		$num_rows = $db->num_rows($resql);

		if ($num_rows > 0) {
			setEventMessages($langs->trans("VoteNameAlreadyExists"), null, 'errors');
			$error++;
		} elseif ($nb_post_max > 0 && $nb_post_ip >= $nb_post_max) {
			setEventMessages($langs->trans("AlreadyTooMuchPostOnThisIPAdress"), null, 'errors');
			$error++;
		} else {
			$now = dol_now();
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'opensurvey_user_studs (nom, id_sondage, reponses, ip, date_creation)';
			$sql .= " VALUES ('".$db->escape($nom)."', '".$db->escape($numsondage)."','".$db->escape($nouveauchoix)."', '".$db->escape($user_ip)."', '".$db->idate($now)."')";
			$resql = $db->query($sql);

			if ($resql) {
				// Add voter to session
				$_SESSION["savevoter"] = $nom.','.(empty($_SESSION["savevoter"]) ? '' : $_SESSION["savevoter"]); // Save voter
				$listofvoters = explode(',', $_SESSION["savevoter"]);

				if ($object->mailsonde) {
					if ($object->fk_user_creat) {
						$userstatic = new User($db);
						$userstatic->fetch($object->fk_user_creat);

						$email = $userstatic->email;
					} else {
						$email = $object->mail_admin;
					}

					//Linked user may not have an email set
					if ($email) {
						include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

						$application = ($conf->global->MAIN_APPLICATION_TITLE ? $conf->global->MAIN_APPLICATION_TITLE : 'Dolibarr ERP/CRM');

						$link = getUrlSondage($numsondage, true);
						$link = '<a href="'.$link.'">'.$link.'</a>';
						$body = str_replace('\n', '<br>', $langs->transnoentities('EmailSomeoneVoted', $nom, $link));
						//var_dump($body);exit;

						$cmailfile = new CMailFile("[".$application."] ".$langs->trans("Poll").': '.$object->title, $email, $conf->global->MAIN_MAIL_EMAIL_FROM, $body, null, null, null, '', '', 0, -1);
						$result = $cmailfile->sendfile();
					}
				}
			} else {
				dol_print_error($db);
			}
		}
	} else {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Name")), null, 'errors');
	}
}


// Update vote
$testmodifier = false;
$testligneamodifier = false;
$ligneamodifier = -1;
for ($i = 0; $i < $nblines; $i++) {
	if (GETPOSTISSET('modifierligne'.$i)) {
		$ligneamodifier = $i;
		$testligneamodifier = true;
	}

	//test to see if a line is to be modified
	if (GETPOSTISSET('validermodifier'.$i)) {
		$modifier = $i;
		$testmodifier = true;
	}
}

if ($testmodifier) {
	$nouveauchoix = '';
	for ($i = 0; $i < $nbcolonnes; $i++) {
		if (GETPOSTISSET("choix".$i) && GETPOST("choix".$i) == '1') {
			$nouveauchoix .= "1";
		} elseif (GETPOSTISSET("choix".$i) && GETPOST("choix".$i) == '2') {
			$nouveauchoix .= "2";
		} else {
			$nouveauchoix .= "0";
		}
	}

	if (!$canbemodified) {
		httponly_accessforbidden('ErrorForbidden');
	}

	$idtomodify = GETPOST("idtomodify".$modifier);
	$sql = 'UPDATE '.MAIN_DB_PREFIX."opensurvey_user_studs";
	$sql .= " SET reponses = '".$db->escape($nouveauchoix)."'";
	$sql .= " WHERE id_users = '".$db->escape($idtomodify)."'";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}
}

// Delete comment
$idcomment = GETPOSTINT('deletecomment');
if ($idcomment) {
	if (!$canbemodified) {
		httponly_accessforbidden('ErrorForbidden');
	}

	$resql = $object->deleteComment($idcomment);
}



/*
 * View
 */

$form = new Form($db);

$arrayofjs = array();
$arrayofcss = array('/opensurvey/css/style.css');

llxHeaderSurvey($object->title, "", 0, 0, $arrayofjs, $arrayofcss, $numsondage);

if (empty($object->ref)) {     // For survey, id is a hex string
	$langs->load("errors");
	print $langs->trans("ErrorRecordNotFound");

	llxFooterSurvey();

	$db->close();
	exit();
}

// Define format of choices
$toutsujet = explode(",", $object->sujet);
$listofanswers = array();
foreach ($toutsujet as $value) {
	$tmp = explode('@', $value);
	$listofanswers[] = array('label'=>$tmp[0], 'format'=>(!empty($tmp[1]) ? $tmp[1] : 'checkbox'));
}
$toutsujet = str_replace("°", "'", $toutsujet);


print '<div class="survey_intro">';
print '<div class="survey_invitation">'.$langs->trans("YouAreInivitedToVote").'</div>';
print '<span class="opacitymedium">'.$langs->trans("OpenSurveyHowTo").'</span><br>';
if (empty($object->allow_spy)) {
	print '<span class="opacitymedium">'.$langs->trans("YourVoteIsPrivate").'</span><br>';
} else {
	print $form->textwithpicto('<span class="opacitymedium">'.$langs->trans("YourVoteIsPublic").'</span>', $langs->trans("CanSeeOthersVote")).'<br>';
}
print '</div>';
print '<br>';

if (empty($object->description)) {
	print '<div class="corps">'."\n";
} else {
	print '<br>'."\n";
}

// show title of survey
$titre = str_replace("\\", "", $object->title);
print '<div class="survey_title">'.img_picto('', 'poll', 'class="size15x paddingright"').' <strong>'.dol_htmlentities($titre).'</strong></div>';

if (!empty($object->description)) {
	print '<br><div class="corps">'."\n";
}

// show description of survey
if ($object->description) {
	print dol_htmlentitiesbr($object->description);
}

print '</div>'."\n";

//The survey has expired, users can't vote or do any action
if (!$canbemodified) {
	print '<br><center><div class="quatrevingtpercent center warning">'.$langs->trans('SurveyExpiredInfo').'</div></center>';
	llxFooterSurvey();

	$db->close();
	exit;
}

print '<div class="cadre"> '."\n";
print '<br><br>'."\n";

// Start to show survey result
print '<div class="div-table-responsive">';
print '<table class="resultats">'."\n";

// Show choice titles
if ($object->format == "D") {
	//display of survey topics
	print '<tr>'."\n";
	print '<td></td>'."\n";

	//display of years
	$colspan = 1;
	$nbofsujet = count($toutsujet);
	for ($i = 0; $i < $nbofsujet; $i++) {
		if (isset($toutsujet[$i + 1]) && date('Y', intval($toutsujet[$i])) == date('Y', intval($toutsujet[$i + 1]))) {
			$colspan++;
		} else {
			print '<td colspan='.$colspan.' class="annee">'.date('Y', intval($toutsujet[$i])).'</td>'."\n";
			$colspan = 1;
		}
	}

	print '</tr>'."\n";
	print '<tr>'."\n";
	print '<td></td>'."\n";

	//display of months
	$colspan = 1;
	for ($i = 0; $i < $nbofsujet; $i++) {
		$cur = intval($toutsujet[$i]); // intval() est utiliser pour supprimer le suffixe @* qui déplaît logiquement à strftime()

		if (isset($toutsujet[$i + 1]) === false) {
			$next = false;
		} else {
			$next = intval($toutsujet[$i + 1]);
		}

		if ($next && dol_print_date($cur, "%B") == dol_print_date($next, "%B") && dol_print_date($cur, "%Y") == dol_print_date($next, "%Y")) {
			$colspan++;
		} else {
			print '<td colspan='.$colspan.' class="mois">'.dol_print_date($cur, "%B").'</td>'."\n";
			$colspan = 1;
		}
	}

	print '</tr>'."\n";
	print '<tr>'."\n";
	print '<td></td>'."\n";

	//display of days
	$colspan = 1;
	for ($i = 0; $i < $nbofsujet; $i++) {
		$cur = intval($toutsujet[$i]);
		if (isset($toutsujet[$i + 1]) === false) {
			$next = false;
		} else {
			$next = intval($toutsujet[$i + 1]);
		}
		if ($next && dol_print_date($cur, "%a %d") == dol_print_date($next, "%a %d") && dol_print_date($cur, "%B") == dol_print_date($next, "%B")) {
			$colspan++;
		} else {
			print '<td colspan="'.$colspan.'" class="jour">'.dol_print_date($cur, "%a %d").'</td>'."\n";
			$colspan = 1;
		}
	}

	print '</tr>'."\n";

	//Display schedules
	if (strpos($object->sujet, '@') !== false) {
		print '<tr>'."\n";
		print '<td></td>'."\n";

		for ($i = 0; isset($toutsujet[$i]); $i++) {
			$heures = explode('@', $toutsujet[$i]);
			if (isset($heures[1])) {
				print '<td class="heure">'.dol_htmlentities($heures[1]).'</td>'."\n";
			} else {
				print '<td class="heure"></td>'."\n";
			}
		}

		print '</tr>'."\n";
	}
} else {
	//display of survey topics
	print '<tr>'."\n";
	print '<td></td>'."\n";

	for ($i = 0; isset($toutsujet[$i]); $i++) {
		$tmp = explode('@', $toutsujet[$i]);
		print '<td class="sujet">'.dol_escape_htmltag($tmp[0]).'</td>'."\n";
	}

	print '</tr>'."\n";
}


// Loop on each answer
$currentusername = '';
$sumfor = array();
$sumagainst = array();
$compteur = 0;
$sql = "SELECT id_users, nom as name, id_sondage, reponses";
$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
$sql .= " WHERE id_sondage = '".$db->escape($numsondage)."'";
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}
$num = $db->num_rows($resql);
while ($compteur < $num) {
	$obj = $db->fetch_object($resql);

	$ensemblereponses = $obj->reponses;

	// ligne d'un usager pré-authentifié
	$mod_ok = (in_array($obj->name, $listofvoters));

	if (!$mod_ok && !$object->allow_spy) {
		$compteur++;
		continue;
	}

	print '<tr>'."\n";

	// Name
	print '<td class="nom">'.img_picto($obj->name, 'user', 'class="pictofixedwidth"').dol_htmlentities($obj->name).'</td>'."\n";

	// si la ligne n'est pas a changer, on affiche les données
	if (!$testligneamodifier) {
		for ($i = 0; $i < $nbcolonnes; $i++) {
			$car = substr($ensemblereponses, $i, 1);
			//print 'xx'.$i."-".$car.'-'.$listofanswers[$i]['format'].'zz';

			if (empty($listofanswers[$i]['format']) || !in_array($listofanswers[$i]['format'], array('yesno', 'foragainst'))) {
				if (((string) $car) == "1") {
					print '<td class="ok">OK</td>'."\n";
				} else {
					print '<td class="non">KO</td>'."\n";
				}
				// Total
				if (!isset($sumfor[$i])) {
					$sumfor[$i] = 0;
				}
				if (((string) $car) == "1") {
					$sumfor[$i]++;
				}
			}
			if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno') {
				if (((string) $car) == "1") {
					print '<td class="ok">'.$langs->trans("Yes").'</td>'."\n";
				} elseif (((string) $car) == "0") {
					print '<td class="non">'.$langs->trans("No").'</td>'."\n";
				} else {
					print '<td class="vide">&nbsp;</td>'."\n";
				}
				// Total
				if (!isset($sumfor[$i])) {
					$sumfor[$i] = 0;
				}
				if (!isset($sumagainst[$i])) {
					$sumagainst[$i] = 0;
				}
				if (((string) $car) == "1") {
					$sumfor[$i]++;
				}
				if (((string) $car) == "0") {
					$sumagainst[$i]++;
				}
			}
			if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst') {
				if (((string) $car) == "1") {
					print '<td class="ok">'.$langs->trans("For").'</td>'."\n";
				} elseif (((string) $car) == "0") {
					print '<td class="non">'.$langs->trans("Against").'</td>'."\n";
				} else {
					print '<td class="vide">&nbsp;</td>'."\n";
				}
				// Total
				if (!isset($sumfor[$i])) {
					$sumfor[$i] = 0;
				}
				if (!isset($sumagainst[$i])) {
					$sumagainst[$i] = 0;
				}
				if (((string) $car) == "1") {
					$sumfor[$i]++;
				}
				if (((string) $car) == "0") {
					$sumagainst[$i]++;
				}
			}
		}
	} else {
		// Else, replace the user's choices with a line of checkboxes for entry
		if ($compteur == $ligneamodifier) {
			for ($i = 0; $i < $nbcolonnes; $i++) {
				$car = substr($ensemblereponses, $i, 1);
				print '<td class="vide">';
				if (empty($listofanswers[$i]['format']) || !in_array($listofanswers[$i]['format'], array('yesno', 'foragainst'))) {
					print '<input type="checkbox" name="choix'.$i.'" value="1" ';
					if ($car == '1') {
						print 'checked';
					}
					print '>';
				}
				if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno') {
					$arraychoice = array('2'=>'&nbsp;', '0'=>$langs->trans("No"), '1'=>$langs->trans("Yes"));
					print $form->selectarray("choix".$i, $arraychoice, $car);
				}
				if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst') {
					$arraychoice = array('2'=>'&nbsp;', '0'=>$langs->trans("Against"), '1'=>$langs->trans("For"));
					print $form->selectarray("choix".$i, $arraychoice, $car);
				}
				print '</td>'."\n";
			}
		} else {
			for ($i = 0; $i < $nbcolonnes; $i++) {
				$car = substr($ensemblereponses, $i, 1);
				if (empty($listofanswers[$i]['format']) || !in_array($listofanswers[$i]['format'], array('yesno', 'foragainst'))) {
					if (((string) $car) == "1") {
						print '<td class="ok">OK</td>'."\n";
					} else {
						print '<td class="non">KO</td>'."\n";
					}
					// Total
					if (!isset($sumfor[$i])) {
						$sumfor[$i] = 0;
					}
					if (((string) $car) == "1") {
						$sumfor[$i]++;
					}
				}
				if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno') {
					if (((string) $car) == "1") {
						print '<td class="ok">'.$langs->trans("For").'</td>'."\n";
					} elseif (((string) $car) == "0") {
						print '<td class="non">'.$langs->trans("Against").'</td>'."\n";
					} else {
						print '<td class="vide">&nbsp;</td>'."\n";
					}
					// Total
					if (!isset($sumfor[$i])) {
						$sumfor[$i] = 0;
					}
					if (!isset($sumagainst[$i])) {
						$sumagainst[$i] = 0;
					}
					if (((string) $car) == "1") {
						$sumfor[$i]++;
					}
					if (((string) $car) == "0") {
						$sumagainst[$i]++;
					}
				}
				if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst') {
					if (((string) $car) == "1") {
						print '<td class="ok">'.$langs->trans("For").'</td>'."\n";
					} elseif (((string) $car) == "0") {
						print '<td class="non">'.$langs->trans("Against").'</td>'."\n";
					} else {
						print '<td class="vide">&nbsp;</td>'."\n";
					}
					// Total
					if (!isset($sumfor[$i])) {
						$sumfor[$i] = 0;
					}
					if (!isset($sumagainst[$i])) {
						$sumagainst[$i] = 0;
					}
					if (((string) $car) == "1") {
						$sumfor[$i]++;
					}
					if (((string) $car) == "0") {
						$sumagainst[$i]++;
					}
				}
			}
		}
	}

	// Button edit at end of line
	if ($compteur != $ligneamodifier && $mod_ok) {
		$currentusername = $obj->name;
		print '<td class="casevide"><input type="submit" class="button small" name="modifierligne'.$compteur.'" value="'.dol_escape_htmltag($langs->trans("Edit")).'"></td>'."\n";
	}

	//demande de confirmation pour modification de ligne
	for ($i = 0; $i < $nblines; $i++) {
		if (GETPOSTISSET("modifierligne".$i)) {
			if ($compteur == $i) {
				print '<td class="casevide">';
				print '<input type="hidden" name="idtomodify'.$compteur.'" value="'.$obj->id_users.'">';
				print '<input type="submit" class="button button-save small" name="validermodifier'.$compteur.'" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print '</td>'."\n";
			}
		}
	}

	$compteur++;
	print '</tr>'."\n";
}

// Add line to add new record
if ($ligneamodifier < 0 && (!isset($_SESSION['nom']))) {
	print '<tr>'."\n";
	print '<td class="nom">'."\n";
	if (isset($_SESSION['nom'])) {
		print '<input type=hidden name="nom" value="'.$_SESSION['nom'].'">'.$_SESSION['nom']."\n";
	} else {
		print '<input type="text" name="nom" placeholder="'.dol_escape_htmltag($langs->trans("Name")).'" maxlength="64" class=" minwidth175" value="">'."\n";
	}
	print '</td>'."\n";

	// show cell form checkbox for a new choice
	for ($i = 0; $i < $nbcolonnes; $i++) {
		print '<td class="vide">';
		if (empty($listofanswers[$i]['format']) || !in_array($listofanswers[$i]['format'], array('yesno', 'foragainst'))) {
			print '<input type="checkbox" name="choix'.$i.'" value="1"';
			if (GETPOSTISSET('choix'.$i) && GETPOST('choix'.$i) == '1') {
				print ' checked';
			}
			print '>';
		}
		if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno') {
			$arraychoice = array('2'=>'&nbsp;', '0'=>$langs->trans("No"), '1'=>$langs->trans("Yes"));
			print $form->selectarray("choix".$i, $arraychoice, GETPOST('choix'.$i));
		}
		if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst') {
			$arraychoice = array('2'=>'&nbsp;', '0'=>$langs->trans("Against"), '1'=>$langs->trans("For"));
			print $form->selectarray("choix".$i, $arraychoice, GETPOST('choix'.$i));
		}
		print '</td>'."\n";
	}

	// Show button to add a new line into database
	print '<td><input type="image" class="borderimp" name="boutonp" value="'.$langs->trans("Vote").'" src="'.img_picto('', 'edit_add', '', false, 1).'"></td>'."\n";
	print '</tr>'."\n";
}

// Select value of best choice (for checkbox columns only)
$nbofcheckbox = 0;
for ($i = 0; $i < $nbcolonnes; $i++) {
	if (empty($listofanswers[$i]['format']) || !in_array($listofanswers[$i]['format'], array('yesno', 'foragainst'))) {
		$nbofcheckbox++;
	}
	if (isset($sumfor[$i])) {
		if ($i == 0) {
			$meilleurecolonne = $sumfor[$i];
		}
		if (!isset($meilleurecolonne) || $sumfor[$i] > $meilleurecolonne) {
			$meilleurecolonne = $sumfor[$i];
		}
	}
}

if ($object->allow_spy) {
	// Show line total
	print '<tr>'."\n";
	print '<td class="center">'.$langs->trans("Total").'</td>'."\n";
	for ($i = 0; $i < $nbcolonnes; $i++) {
		$showsumfor = isset($sumfor[$i]) ? $sumfor[$i] : '';
		$showsumagainst = isset($sumagainst[$i]) ? $sumagainst[$i] : '';
		if (empty($showsumfor)) {
			$showsumfor = 0;
		}
		if (empty($showsumagainst)) {
			$showsumagainst = 0;
		}

		print '<td>';
		if (empty($listofanswers[$i]['format']) || !in_array($listofanswers[$i]['format'], array('yesno', 'foragainst'))) {
			print $showsumfor;
		}
		if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'yesno') {
			print $langs->trans("Yes").': '.$showsumfor.'<br>'.$langs->trans("No").': '.$showsumagainst;
		}
		if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst') {
			print $langs->trans("For").': '.$showsumfor.'<br>'.$langs->trans("Against").': '.$showsumagainst;
		}
		print '</td>'."\n";
	}
	print '</tr>';
	// Show picto winner
	if ($nbofcheckbox >= 2) {
		print '<tr>'."\n";
		print '<td class="somme"></td>'."\n";
		for ($i = 0; $i < $nbcolonnes; $i++) {
			//print 'xx'.(!empty($listofanswers[$i]['format'])).'-'.$sumfor[$i].'-'.$meilleurecolonne;
			if (empty($listofanswers[$i]['format']) || !in_array($listofanswers[$i]['format'], array('yesno', 'foragainst')) && isset($sumfor[$i]) && isset($meilleurecolonne) && $sumfor[$i] == $meilleurecolonne) {
				print '<td class="somme"><img src="'.dol_buildpath('/opensurvey/img/medaille.png', 1).'"></td>'."\n";
			} else {
				print '<td class="somme"></td>'."\n";
			}
		}
		print '</tr>'."\n";
	}
}
print '</table>'."\n";
print '</div>'."\n";

print '</div>'."\n";

if ($object->allow_spy) {
	$toutsujet = explode(",", $object->sujet);
	$toutsujet = str_replace("°", "'", $toutsujet);

	$compteursujet = 0;
	$meilleursujet = '';

	for ($i = 0; $i < $nbcolonnes; $i++) {
		if (isset($sumfor[$i]) && isset($meilleurecolonne) && $sumfor[$i] == $meilleurecolonne) {
			$meilleursujet .= ($meilleursujet ? ", " : "");
			if ($object->format == "D") {
				if (strpos($toutsujet[$i], '@') !== false) {
					$toutsujetdate = explode("@", $toutsujet[$i]);
					$meilleursujet .= dol_print_date($toutsujetdate[0], 'daytext').' ('.dol_print_date($toutsujetdate[0], '%A').') - '.$toutsujetdate[1];
				} else {
					$meilleursujet .= dol_print_date((empty($toutsujet[$i]) ? 0 : $toutsujet[$i]), 'daytext').' ('.dol_print_date((empty($toutsujet[$i]) ? 0 : $toutsujet[$i]), '%A').')';
				}
			} else {
				$tmps = explode('@', $toutsujet[$i]);
				$meilleursujet .= dol_htmlentities($tmps[0]);
			}

			$compteursujet++;
		}
	}

	//$meilleursujet = substr($meilleursujet, 1);
	$meilleursujet = str_replace("°", "'", $meilleursujet);

	// Show best choice
	if ($nbofcheckbox >= 2) {
		$vote_str = $langs->trans('votes');
		print '<p class="affichageresultats">'."\n";

		if (isset($meilleurecolonne) && $compteursujet == "1") {
			print '<img src="'.dol_buildpath('/opensurvey/img/medaille.png', 1).'"> '.$langs->trans('TheBestChoice').": <b>".$meilleursujet."</b> - <b>".$meilleurecolonne."</b> ".$vote_str.".\n";
		} elseif (isset($meilleurecolonne)) {
			print '<img src="'.dol_buildpath('/opensurvey/img/medaille.png', 1).'"> '.$langs->trans('TheBestChoices').": <b>".$meilleursujet."</b> - <b>".$meilleurecolonne."</b> ".$vote_str.".\n";
		}

		print '</p><br>'."\n";
	}
}

print '<br>';


// Comment list
$comments = $object->getComments();

if ($comments) {
	print '<br>'.img_picto('', 'note', 'class="pictofixedwidth"').'<span class="bold opacitymedium">'.$langs->trans("CommentsOfVoters").':</span><br>'."\n";

	foreach ($comments as $obj) {
		// ligne d'un usager pré-authentifié
		//$mod_ok = (in_array($obj->name, $listofvoters));

		print '<div class="comment"><span class="usercomment">';
		if (in_array($obj->usercomment, $listofvoters)) {
			print '<a href="'.$_SERVER["PHP_SELF"].'?deletecomment='.$obj->id_comment.'&sondage='.$numsondage.'"> '.img_picto('', 'delete.png', '', false, 0, 0, '', 'nomarginleft').'</a> ';
		}
		//else print img_picto('', 'ellipsis-h', '', false, 0, 0, '', 'nomarginleft').' ';
		print img_picto('', 'user', 'class="pictofixedwidth"').dol_htmlentities($obj->usercomment).':</span> <span class="comment">'.dol_nl2br(dol_htmlentities($obj->comment))."</span></div>";
	}
}

// Form to add comment
if ($object->allow_comments && $currentusername) {
	print '<br><div class="addcomment"><span class="opacitymedium">'.$langs->trans("AddACommentForPoll")."</span><br>\n";

	print '<textarea name="comment" rows="'.ROWS_2.'" class="quatrevingtpercent">'.dol_escape_htmltag(GETPOST('comment', 'alphanohtml'), 0, 1).'</textarea><br>'."\n";
	print $langs->trans("Name").': ';
	print '<input type="text" name="commentuser" maxlength="64" value="'.dol_escape_htmltag(GETPOSTISSET('commentuser') ? GETPOST('commentuser', 'alphanohtml') : (empty($_SESSION['nom']) ? $currentusername : $_SESSION['nom'])).'"> &nbsp; '."\n";
	print '<input type="submit" class="button smallpaddingimp" name="ajoutcomment" value="'.dol_escape_htmltag($langs->trans("AddComment")).'"><br>'."\n";
	print '</form>'."\n";

	print '</div>'."\n"; // div add comment
}

print '<br><br>';

print '<a name="bas"></a>'."\n";

llxFooterSurvey();

$db->close();
