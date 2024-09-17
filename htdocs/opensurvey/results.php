<?php
/* Copyright (C) 2013-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024	Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/opensurvey/results.php
 *	\ingroup    opensurvey
 *	\brief      Page to preview votes of a survey
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveysondage.class.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/lib/opensurvey.lib.php";

// Security check
if (!$user->hasRight('opensurvey', 'read')) {
	accessforbidden();
}

// Init vars
$action = GETPOST('action', 'aZ09');
$numsondage = GETPOST("id", 'alphanohtml');

$object = new Opensurveysondage($db);
$result = $object->fetch(0, $numsondage);
if ($result <= 0) {
	dol_print_error(null, 'Failed to get survey id '.$numsondage);
}

$nblines = $object->fetch_lines();


/*
 * Actions
 */

// Return to the results
if (GETPOST('cancel')) {
	header('Location: results.php?id='.(GETPOSTISSET('id_sondage') ? GETPOST('id_sondage', 'aZ09') : GETPOST('id', 'alphanohtml')));
	exit;
}

$nbcolonnes = substr_count($object->sujet, ',') + 1;

// Add vote
if (GETPOST("boutonp") || GETPOST("boutonp.x") || GETPOST("boutonp_x")) {		// boutonp for chrome, boutonp.x for firefox
	if (GETPOST('nom')) {
		$erreur_prenom = false;

		$nouveauchoix = '';
		for ($i = 0; $i < $nbcolonnes; $i++) {
			if (GETPOSTISSET("choix$i") && GETPOST("choix$i") == '1') {
				$nouveauchoix .= "1";
			} elseif (GETPOSTISSET("choix$i") && GETPOST("choix$i") == '2') {
				$nouveauchoix .= "2";
			} else { // sinon c'est 0
				$nouveauchoix .= "0";
			}
		}

		$nom = substr(GETPOST("nom", 'alphanohtml'), 0, 64);

		// Check if vote already exists
		$sql = 'SELECT id_users, nom as name';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'opensurvey_user_studs';
		$sql .= " WHERE id_sondage='".$db->escape($numsondage)."' AND nom = '".$db->escape($nom)."'";
		$sql .= ' ORDER BY id_users';
		$resql = $db->query($sql);
		$num_rows = $db->num_rows($resql);
		if ($num_rows > 0) {
			setEventMessages($langs->trans("VoteNameAlreadyExists"), null, 'errors');
			$error++;
		} else {
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'opensurvey_user_studs (nom, id_sondage, reponses, date_creation)';
			$sql .= " VALUES ('".$db->escape($nom)."', '".$db->escape($numsondage)."', '".$db->escape($nouveauchoix)."', '".$db->idate(dol_now())."')";
			$resql = $db->query($sql);
			if (!$resql) {
				dol_print_error($db);
			}
		}
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

	//test pour voir si une ligne est a modifier
	if (GETPOSTISSET('validermodifier'.$i)) {
		$modifier = $i;
		$testmodifier = true;
	}
}
if ($testmodifier) {
	// Security check
	if (!$user->hasRight('opensurvey', 'write')) {
		accessforbidden();
	}

	$nouveauchoix = '';
	for ($i = 0; $i < $nbcolonnes; $i++) {
		if (GETPOSTISSET("choix$i") && GETPOST("choix$i") == '1') {
			$nouveauchoix .= "1";
		} elseif (GETPOSTISSET("choix$i") && GETPOST("choix$i") == '2') {
			$nouveauchoix .= "2";
		} else { // sinon c'est 0
			$nouveauchoix .= "0";
		}
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

// Add column (not for date)
if (GETPOST("ajoutercolonne") && GETPOST('nouvellecolonne') && $object->format == "A") {
	// Security check
	if (!$user->hasRight('opensurvey', 'write')) {
		accessforbidden();
	}

	$nouveauxsujets = $object->sujet;

	//on rajoute la valeur a la fin de tous les sujets deja entrés
	$nouveauxsujets .= ',';
	$nouveauxsujets .= str_replace(array(",", "@"), " ", GETPOST("nouvellecolonne")).(!GETPOST("typecolonne") ? '' : '@'.GETPOST("typecolonne"));

	//mise a jour avec les nouveaux sujets dans la base
	$sql = 'UPDATE '.MAIN_DB_PREFIX."opensurvey_sondage";
	$sql .= " SET sujet = '".$db->escape($nouveauxsujets)."'";
	$sql .= " WHERE id_sondage = '".$db->escape($numsondage)."'";
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	} else {
		header('Location: results.php?id='.$object->id_sondage);
		exit;
	}
}

// Add column (with format date)
if (GETPOSTISSET("ajoutercolonne") && $object->format == "D") {
	// Security check
	if (!$user->hasRight('opensurvey', 'write')) {
		accessforbidden();
	}

	$nouveauxsujets = $object->sujet;

	if (GETPOSTISSET("nouveaujour") && GETPOST("nouveaujour") != "vide" &&
		GETPOSTISSET("nouveaumois") && GETPOST("nouveaumois") != "vide" &&
		GETPOSTISSET("nouvelleannee") && GETPOST("nouvelleannee") != "vide") {
		$nouvelledate = dol_mktime(0, 0, 0, GETPOST("nouveaumois"), GETPOST("nouveaujour"), GETPOST("nouvelleannee"));

		if (GETPOSTISSET("nouvelleheuredebut") && GETPOST("nouvelleheuredebut") != "vide") {
			$nouvelledate .= "@";
			$nouvelledate .= GETPOST("nouvelleheuredebut");
			$nouvelledate .= "h";

			if (GETPOST("nouvelleminutedebut") != "vide") {
				$nouvelledate .= GETPOST("nouvelleminutedebut");
			}
		}

		if (GETPOSTISSET("nouvelleheurefin") && GETPOST("nouvelleheurefin") != "vide") {
			$nouvelledate .= "-";
			$nouvelledate .= GETPOST("nouvelleheurefin");
			$nouvelledate .= "h";

			if (GETPOST("nouvelleminutefin") != "vide") {
				$nouvelledate .= GETPOST("nouvelleminutefin");
			}
		}

		if (GETPOST("nouvelleheuredebut") == "vide" || (GETPOSTISSET("nouvelleheuredebut") && GETPOSTISSET("nouvelleheurefin")
			&& (GETPOST("nouvelleheuredebut") < GETPOST("nouvelleheurefin") || (GETPOST("nouvelleheuredebut") == GETPOST("nouvelleheurefin")
				&& (GETPOST("nouvelleminutedebut") < GETPOST("nouvelleminutefin")))))) {
			$erreur_ajout_date = false;
		} else {
			$erreur_ajout_date = "yes";
		}

		//on rajoute la valeur dans les valeurs
		$datesbase = explode(",", $object->sujet);
		$taillebase = count($datesbase);

		//recherche de l'endroit de l'insertion de la nouvelle date dans les dates deja entrées dans le tableau
		if ($nouvelledate < $datesbase[0]) {
			$cleinsertion = 0;
		} elseif ($nouvelledate > $datesbase[$taillebase - 1]) {
			$cleinsertion = count($datesbase);
		} else {
			$nbdatesbase = count($datesbase);
			for ($i = 0; $i < $nbdatesbase; $i++) {
				$j = $i + 1;
				if ($nouvelledate > $datesbase[$i] && $nouvelledate < $datesbase[$j]) {
					$cleinsertion = $j;
				}
			}
		}

		array_splice($datesbase, $cleinsertion, 0, $nouvelledate);
		$cle = array_search($nouvelledate, $datesbase);
		$dateinsertion = '';
		$nbofdates = count($datesbase);
		for ($i = 0; $i < $nbofdates; $i++) {
			$dateinsertion .= ",";
			$dateinsertion .= $datesbase[$i];
		}

		$dateinsertion = substr("$dateinsertion", 1);

		// update with new topics into database
		if (isset($erreur_ajout_date) && empty($erreur_ajout_date)) {
			$sql = 'UPDATE '.MAIN_DB_PREFIX."opensurvey_sondage";
			$sql .= " SET sujet = '".$db->escape($dateinsertion)."'";
			$sql .= " WHERE id_sondage = '".$db->escape($numsondage)."'";
			$resql = $db->query($sql);
			if (!$resql) {
				dol_print_error($db);
			} else {
				header('Location: results.php?id='.$object->id_sondage);
			}
		}
		if ($cleinsertion >= 0) {
			$sql = 'SELECT s.reponses';
			$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_user_studs as s";
			$sql .= " WHERE id_sondage = '".$db->escape($numsondage)."'";
			$resql = $db->query($sql);
			if (!$resql) {
				dol_print_error($db);
			} else {
				$num = $db->num_rows($resql);
				$compteur = 0;
				while ($compteur < $num) {
					$obj = $db->fetch_object($resql);
					$sql = 'UPDATE '.MAIN_DB_PREFIX."opensurvey_user_studs";
					if ($cleinsertion == 0) {
						$sql .= " SET reponses = '0".$db->escape($obj->reponses)."'";
					} else {
						$reponsesadd = str_split($obj->reponses);
						$lengthresponses = count($reponsesadd);
						for ($cpt = $lengthresponses; $cpt > $cleinsertion; $cpt--) {
							$reponsesadd[$cpt] = $reponsesadd[$cpt - 1];
						}
						$reponsesadd[$cleinsertion] = '0';
						$reponsesadd = implode($reponsesadd);
						$sql .= " SET reponses = '".$db->escape($reponsesadd)."'";
					}
					$sql .= " WHERE id_sondage = '".$db->escape($numsondage)."'";
					$resql = $db->query($sql);
					if (!$resql) {
						dol_print_error($db);
					}
					$compteur++;
				}
			}
		}
		$adresseadmin = $object->mail_admin;
	} else {
		$erreur_ajout_date = "yes";
	}
}

// Delete line
for ($i = 0; $i < $nblines; $i++) {
	if (GETPOST("effaceligne".$i) || GETPOST("effaceligne".$i."_x") || GETPOST("effaceligne".$i.".x")) {	// effacelignei for chrome, effacelignei_x for firefox
		// Security check
		if (!$user->hasRight('opensurvey', 'write')) {
			accessforbidden();
		}

		$compteur = 0;

		// Loop on each answer
		$compteur = 0;
		$sql = "SELECT id_users, nom as name, id_sondage, reponses";
		$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
		$sql .= " WHERE id_sondage = '".$db->escape($numsondage)."'";
		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}
		$num = $db->num_rows($resql);
		while ($compteur < $num) {
			$obj = $db->fetch_object($resql);

			if ($compteur == $i) {
				$sql2 = 'DELETE FROM '.MAIN_DB_PREFIX.'opensurvey_user_studs';
				$sql2 .= " WHERE id_users = ".((int) $obj->id_users);
				$resql2 = $db->query($sql2);
			}

			$compteur++;
		}
	}
}

// Delete column
for ($i = 0; $i < $nbcolonnes; $i++) {
	if ((GETPOST("effacecolonne".$i) || GETPOST("effacecolonne".$i."_x") || GETPOST("effacecolonne".$i.".x"))
		&& $nbcolonnes > 1) {	// effacecolonnei for chrome, effacecolonnei_x for firefox
		// Security check
		if (!$user->hasRight('opensurvey', 'write')) {
			accessforbidden();
		}

		$db->begin();

		$toutsujet = explode(",", $object->sujet);
		$j = 0;
		$nouveauxsujets = '';

		//parcours de tous les sujets actuels
		while (isset($toutsujet[$j])) {
			// If the subject is not the deleted subject, then concatenate the current subject
			if ($i != $j) {
				if (!empty($nouveauxsujets)) {
					$nouveauxsujets .= ',';
				}
				$nouveauxsujets .= $toutsujet[$j];
			}

			$j++;
		}

		// Mise a jour des sujets dans la base
		$sql = 'UPDATE '.MAIN_DB_PREFIX."opensurvey_sondage";
		$sql .= " SET sujet = '".$db->escape($nouveauxsujets)."' WHERE id_sondage = '".$db->escape($numsondage)."'";
		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
		}

		// Clean current answer to remove deleted columns
		$compteur = 0;
		$sql = "SELECT id_users, nom as name, id_sondage, reponses";
		$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
		$sql .= " WHERE id_sondage = '".$db->escape($numsondage)."'";
		dol_syslog('sql='.$sql);
		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
			exit;
		}
		$num = $db->num_rows($resql);
		while ($compteur < $num) {
			$obj = $db->fetch_object($resql);

			$newcar = '';
			$ensemblereponses = $obj->reponses;

			// parcours de toutes les réponses actuelles
			for ($j = 0; $j < $nbcolonnes; $j++) {
				$car = substr($ensemblereponses, $j, 1);
				//si les reponses ne concerne pas la colonne effacée, on concatenate
				if ($i != $j) {
					$newcar .= $car;
				}
			}

			// mise a jour des reponses utilisateurs dans la base
			$sql2 = 'UPDATE '.MAIN_DB_PREFIX.'opensurvey_user_studs';
			$sql2 .= " SET reponses = '".$db->escape($newcar)."'";
			$sql2 .= " WHERE id_users = '".$db->escape($obj->id_users)."'";
			//print $sql2;
			dol_syslog('sql='.$sql2);
			$resql2 = $db->query($sql2);

			$compteur++;
		}

		$db->commit();
	}
}



/*
 * View
 */

$form = new Form($db);

if ($object->fk_user_creat) {
	$userstatic = new User($db);
	$userstatic->fetch($object->fk_user_creat);
}

$result = $object->fetch(0, $numsondage);
if ($result <= 0) {
	dol_print_error($db, $object->error);
	exit;
}

$title = $object->title." - ".$langs->trans('Card');
$helpurl = '';
$arrayofjs = array();
$arrayofcss = array('/opensurvey/css/style.css');

llxHeader('', $title, $helpurl, '', 0, 0, $arrayofjs, $arrayofcss);


// Define format of choices
$toutsujet = explode(",", $object->sujet);
$listofanswers = array();
foreach ($toutsujet as $value) {
	$tmp = explode('@', $value);
	$listofanswers[] = array('label' => $tmp[0], 'format' => (!empty($tmp[1]) ? $tmp[1] : 'checkbox'));
}
$toutsujet = str_replace("@", "<br>", $toutsujet);
$toutsujet = str_replace("°", "'", $toutsujet);


print '<form name="formulaire4" action="#" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="id" value="'.GETPOST('id').'">';

$head = opensurvey_prepare_head($object);

print dol_get_fiche_head($head, 'preview', $langs->trans("Survey"), -1, 'poll');

$morehtmlref = '';

$linkback = '<a href="'.DOL_URL_ROOT.'/opensurvey/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, 1, 'id_sondage', 'id_sondage', $morehtmlref);


print '<div class="fichecenter">';

print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border tableforfield centpercent">';

// Type
$type = ($object->format == "A") ? 'classic' : 'date';
print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td>';
print img_picto('', dol_buildpath('/opensurvey/img/'.($type == 'classic' ? 'chart-32.png' : 'calendar-32.png'), 1), 'width="16"', 1);
print ' '.$langs->trans($type == 'classic' ? "TypeClassic" : "TypeDate").'</td></tr>';

// Title
print '<tr><td>';
$adresseadmin = $object->mail_admin;
print $langs->trans("Title").'</td><td>';
if ($action == 'edit') {
	print '<input type="text" name="nouveautitre" size="40" value="'.dol_escape_htmltag($object->title).'">';
} else {
	print dol_htmlentities($object->title);
}
print '</td></tr>';

// Description
print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td class="wordbreak">';
if ($action == 'edit') {
	$doleditor = new DolEditor('nouveauxcommentaires', $object->description, '', 120, 'dolibarr_notes', 'In', 1, 1, 1, ROWS_7, '90%');
	$doleditor->Create(0, '');
} else {
	print(dol_textishtml($object->description) ? $object->description : dol_nl2br($object->description, 1, true));
}
print '</td></tr>';

// EMail
//If linked user, then emails are going to be sent to users' email
if (!$object->fk_user_creat) {
	print '<tr><td>'.$langs->trans("EMail").'</td><td>';
	if ($action == 'edit') {
		print '<input type="text" name="nouvelleadresse" class="minwith200" value="'.$object->mail_admin.'">';
	} else {
		print dol_print_email($object->mail_admin, 0, 0, 1, 0, 1, 1);
	}
	print '</td></tr>';
}

print '</table>';

print '</div>';
print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border tableforfield centpercent">';

// Expire date
print '<tr><td>'.$langs->trans('ExpireDate').'</td><td>';
if ($action == 'edit') {
	print $form->selectDate($expiredate ? $expiredate : $object->date_fin, 'expire', 0, 0, 0, '', 1, 0);
} else {
	print dol_print_date($object->date_fin, 'day');
	if ($object->date_fin && $object->date_fin < dol_now() && $object->status == Opensurveysondage::STATUS_VALIDATED) {
		print img_warning($langs->trans("Expired"));
	}
}
print '</td></tr>';

// Author
print '<tr><td>';
print $langs->trans("Author").'</td><td>';
if ($object->fk_user_creat) {
	print $userstatic->getLoginUrl(-1);
} else {
	print dol_htmlentities($object->nom_admin);
}
print '</td></tr>';

// Link
print '<tr><td>'.$langs->trans("UrlForSurvey", '').'</td><td>';

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

$url = $urlwithouturlroot.dol_buildpath('/public/opensurvey/studs.php', 1).'?sondage='.$object->id_sondage;
$urllink = '<input type="text" class="quatrevingtpercent" '.($action == 'edit' ? 'disabled' : '').' id="opensurveyurl" name="opensurveyurl" value="'.$url.'">';
print $urllink;
if ($action != 'edit') {
	print ajax_autoselect("opensurveyurl", $url, 'image');
}

print '</td></tr>';

print '</table>';
print '</div>';

print '</div>';
print '<div class="clearboth"></div>';

print dol_get_fiche_end();

print '</form>'."\n";


// Buttons

print '<div class="tabsAction">';

print '<a class="butAction" href="exportcsv.php?id='.urlencode($numsondage).'">'.$langs->trans("ExportSpreadsheet").' (.CSV)</a>';

print '</div>';


// Show form to add a new field/column
if (GETPOST('ajoutsujet')) {
	// Security check
	if (!$user->hasRight('opensurvey', 'write')) {
		accessforbidden();
	}

	print '<form name="formulaire" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="backtopage" value="'.GETPOST('backtopage', 'alpha').'">';
	print '<input type="hidden" name="id" value="'.GETPOST('id', 'alpha').'">';
	print '<input type="hidden" name="ajoutsujet" value="1">';

	print '<div class="center">'."\n";
	print "<br><br>\n";

	// Add new column
	if ($object->format == "A") {
		print $langs->trans("AddNewColumn").':<br><br>';
		print $langs->trans("Title").' <input type="text" name="nouvellecolonne" size="40"><br>';
		$tmparray = array('checkbox' => $langs->trans("CheckBox"), 'yesno' => $langs->trans("YesNoList"), 'foragainst' => $langs->trans("PourContreList"));
		print $langs->trans("Type").' '.$form->selectarray("typecolonne", $tmparray, GETPOST('typecolonne')).'<br><br>';
		print '<input type="submit" class="button" name="ajoutercolonne" value="'.dol_escape_htmltag($langs->trans("Add")).'">';
		print '<input type="hidden" name="id_sondage" value="'.dol_escape_htmltag($object->id_sondage).'">';
		print ' &nbsp; &nbsp; ';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
		print '<br><br>'."\n";
	} else {
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

		$formother = new FormOther($db);
		//ajout d'une date avec creneau horaire
		print $langs->trans("AddADate").':<br><br>'."\n";
		print '<select name="nouveaujour"> '."\n";
		print '<OPTION VALUE="vide">&nbsp;</OPTION>'."\n";
		for ($i = 1; $i < 32; $i++) {
			print '<OPTION VALUE="'.$i.'">'.$i.'</OPTION>'."\n";
		}
		print '</select>'."\n";

		print $formother->select_month('', 'nouveaumois', 1);

		print '&nbsp;';

		print $formother->selectyear('', 'nouvelleannee', 1, 0, 5, 0, 1);

		print '<br><br>'.$langs->trans("AddStartHour").': <br><br>'."\n";
		print '<select name="nouvelleheuredebut"> '."\n";
		print '<OPTION VALUE="vide">&nbsp;</OPTION>'."\n";
		for ($i = 0; $i < 24; $i++) {
			print '<OPTION VALUE="'.$i.'">'.$i.' H</OPTION>'."\n";
		}
		print '</select>'."\n";
		print '<select name="nouvelleminutedebut"> '."\n";
		print '<OPTION VALUE="vide">&nbsp;</OPTION>'."\n";
		print '<OPTION VALUE="00">00</OPTION>'."\n";
		print '<OPTION VALUE="15">15</OPTION>'."\n";
		print '<OPTION VALUE="30">30</OPTION>'."\n";
		print '<OPTION VALUE="45">45</OPTION>'."\n";
		print '</select>'."\n";
		print '<br><br>'.$langs->trans("AddEndHour").': <br><br>'."\n";
		print '<select name="nouvelleheurefin"> '."\n";
		print '<OPTION VALUE="vide">&nbsp;</OPTION>'."\n";
		for ($i = 0; $i < 24; $i++) {
			print '<OPTION VALUE="'.$i.'">'.$i.' H</OPTION>'."\n";
		}
		print '</SELECT>'."\n";
		print '<select name="nouvelleminutefin"> '."\n";
		print '<OPTION VALUE="vide">&nbsp;</OPTION>'."\n";
		print '<OPTION VALUE="00">00</OPTION>'."\n";
		print '<OPTION VALUE="15">15</OPTION>'."\n";
		print '<OPTION VALUE="30">30</OPTION>'."\n";
		print '<OPTION VALUE="45">45</OPTION>'."\n";
		print '</select>'."\n";

		print '<br><br>';
		print' <input type="submit" class="button" name="ajoutercolonne" value="'.dol_escape_htmltag($langs->trans("Add")).'">'."\n";
		print '&nbsp; &nbsp;';
		print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	}

	print '</form>'."\n";
	print '<br><br><br><br>'."\n";
	print '</div>'."\n";

	exit;
}

if ($user->hasRight('opensurvey', 'write')) {
	print '<span class="opacitymedium">';
	$s = $langs->trans("PollAdminDesc", '{s1}', $langs->trans("Add"));
	print str_replace('{s1}', img_picto('', 'delete'), $s);
	print '</span><br>';
}

$nbcolonnes = substr_count($object->sujet, ',') + 1;

print '<form name="formulaire" action="" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="page_y" value="">';

print '<div class="cadre div-table-responsive-no-min"> '."\n";

// Start to show survey result
print '<table class="resultats margintoponly">'."\n";

//reformatage des données des sujets du sondage
$toutsujet = explode(",", $object->sujet);
$toutsujet = str_replace("°", "'", $toutsujet);

print '<tr>'."\n";
print '<td></td>'."\n";
print '<td></td>'."\n";

// loop to show the delete link
if ($user->hasRight('opensurvey', 'write')) {
	for ($i = 0; isset($toutsujet[$i]); $i++) {
		print '<td class=somme><input type="image" class="buttonwebsite" name="effacecolonne'.$i.'" src="'.img_picto('', 'delete.png', '', false, 1).'"></td>'."\n";
	}
}

print '</tr>'."\n";


// Show choice titles
if ($object->format == "D") {
	//affichage des sujets du sondage
	print '<tr>'."\n";
	print '<td></td>'."\n";
	print '<td></td>'."\n";

	//affichage des années
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

	if ($user->hasRight('opensurvey', 'write')) {
		print '<td class="annee">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?ajoutsujet=1&id='.$object->id_sondage.'">'.$langs->trans("Add").'</a></td>'."\n";
	}

	print '</tr>'."\n";
	print '<tr>'."\n";
	print '<td></td>'."\n";
	print '<td></td>'."\n";

	//affichage des mois
	$colspan = 1;
	for ($i = 0; $i < $nbofsujet; $i++) {
		$cur = intval($toutsujet[$i]); // intval() est utiliser pour supprimer le suffixe @* qui déplaît logiquement à strftime()

		if (!isset($toutsujet[$i + 1])) {
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

	if ($user->hasRight('opensurvey', 'write')) {
		print '<td class="mois"><a href="'.$_SERVER["PHP_SELF"].'?ajoutsujet=1&id='.$object->id_sondage.'">'.$langs->trans("Add").'</a></td>'."\n";
	}

	print '</tr>'."\n";
	print '<tr>'."\n";
	print '<td></td>'."\n";
	print '<td></td>'."\n";

	//affichage des jours
	$colspan = 1;
	for ($i = 0; $i < $nbofsujet; $i++) {
		$cur = intval($toutsujet[$i]);
		if (!isset($toutsujet[$i + 1])) {
			$next = false;
		} else {
			$next = intval($toutsujet[$i + 1]);
		}
		if ($next && dol_print_date($cur, "%a %d") == dol_print_date($next, "%a %d") && dol_print_date($cur, "%B") == dol_print_date($next, "%B")) {
			$colspan++;
		} else {
			print '<td colspan='.$colspan.' class="jour">'.dol_print_date($cur, "%a %d").'</td>'."\n";

			$colspan = 1;
		}
	}

	if ($user->hasRight('opensurvey', 'write')) {
		print '<td class="jour"><a href="'.$_SERVER["PHP_SELF"].'?ajoutsujet=1&id='.$object->id_sondage.'">'.$langs->trans("Add").'</a></td>'."\n";
	}
	print '</tr>'."\n";

	//affichage des horaires
	if (strpos($object->sujet, '@') !== false) {
		print '<tr>'."\n";
		print '<td></td>'."\n";
		print '<td></td>'."\n";

		for ($i = 0; isset($toutsujet[$i]); $i++) {
			$heures = explode('@', $toutsujet[$i]);
			if (isset($heures[1])) {
				print '<td class="heure">'.dol_htmlentities($heures[1]).'</td>'."\n";
			} else {
				print '<td class="heure"></td>'."\n";
			}
		}

		if ($user->hasRight('opensurvey', 'write')) {
			print '<td class="heure"><a href="'.$_SERVER["PHP_SELF"].'?ajoutsujet=1&id='.$object->id_sondage.'">'.$langs->trans("Add").'</a></td>'."\n";
		}

		print '</tr>'."\n";
	}
} else {
	// Show titles
	print '<tr>'."\n";
	print '<td></td>'."\n";
	print '<td></td>'."\n";

	for ($i = 0; isset($toutsujet[$i]); $i++) {
		$tmp = explode('@', $toutsujet[$i]);
		print '<td class="sujet">'.dol_htmlentities($tmp[0]).'</td>'."\n";
	}

	print '<td class="sujet"><a href="'.$_SERVER["PHP_SELF"].'?id='.$numsondage.'&ajoutsujet=1&backtopage='.urlencode($_SERVER["PHP_SELF"]).'"><span class="fa fa-plus-circle valignmiddle btnTitle-icon"></span></a></td>'."\n";
	print '</tr>'."\n";
}


// Loop on each answer
$sumfor = array();
$sumagainst = array();
$compteur = 0;
$sql = "SELECT id_users, nom as name, id_sondage, reponses";
$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
$sql .= " WHERE id_sondage = '".$db->escape($numsondage)."'";
dol_syslog('sql='.$sql);
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}
$num = $db->num_rows($resql);
while ($compteur < $num) {
	$obj = $db->fetch_object($resql);

	$ensemblereponses = $obj->reponses;

	print '<tr><td>'."\n";

	if ($user->hasRight('opensurvey', 'write')) {
		print '<input type="image" class="reposition" name="effaceligne'.$compteur.'" src="'.img_picto('', 'delete.png', '', false, 1).'">'."\n";
	}

	// Name
	print '</td><td class="nom">'.dol_htmlentities($obj->name).'</td>'."\n";

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
		// Else, replace the user's choices with a line of checkboxes to retrieve new values
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
					$arraychoice = array('2' => '&nbsp;', '0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
					print $form->selectarray("choix".$i, $arraychoice, $car);
				}
				if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst') {
					$arraychoice = array('2' => '&nbsp;', '0' => $langs->trans("Against"), '1' => $langs->trans("For"));
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
	if ($compteur != $ligneamodifier && ($user->hasRight('opensurvey', 'write'))) {
		print '<td class="casevide"><input type="submit" class="button reposition" name="modifierligne'.$compteur.'" value="'.dol_escape_htmltag($langs->trans("Edit")).'"></td>'."\n";
	}

	//demande de confirmation pour modification de ligne
	for ($i = 0; $i < $nblines; $i++) {
		if (GETPOSTISSET("modifierligne".$i)) {
			if ($compteur == $i) {
				print '<td class="casevide">';
				print '<input type="hidden" name="idtomodify'.$compteur.'" value="'.$obj->id_users.'">';
				print '<input type="submit" class="button button-save reposition" name="validermodifier'.$compteur.'" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print '</td>'."\n";
			}
		}
	}

	$compteur++;
	print '</tr>'."\n";
}

// Add line to add new record
if (empty($testligneamodifier)) {
	print '<tr>'."\n";
	print '<td></td>'."\n";
	print '<td class="nom">'."\n";
	print '<input type="text" class="maxwidthonsmartphone" placeholder="'.dol_escape_htmltag($langs->trans("Name")).'" name="nom" maxlength="64">'."\n";
	print '</td>'."\n";

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
			$arraychoice = array('2' => '&nbsp;', '0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
			print $form->selectarray("choix".$i, $arraychoice);
		}
		if (!empty($listofanswers[$i]['format']) && $listofanswers[$i]['format'] == 'foragainst') {
			$arraychoice = array('2' => '&nbsp;', '0' => $langs->trans("Against"), '1' => $langs->trans("For"));
			print $form->selectarray("choix".$i, $arraychoice);
		}
		print '</td>'."\n";
	}

	// Affichage du bouton de formulaire pour inscrire un nouvel utilisateur dans la base
	print '<td><input type="image" name="boutonp" class="borderimp" value="'.$langs->trans("Vote").'" src="'.img_picto('', 'edit_add', '', false, 1).'"></td>'."\n";
	print '</tr>'."\n";
}

// Select value of best choice (for checkbox columns only)
$nbofcheckbox = 0;
for ($i = 0; $i < $nbcolonnes + 1; $i++) {
	if (empty($listofanswers[$i]['format']) || !in_array($listofanswers[$i]['format'], array('yesno', 'foragainst'))) {
		$nbofcheckbox++;
	}
	if (isset($sumfor[$i])) {
		if ($i == 0) {
			$meilleurecolonne = $sumfor[$i];
		}
		if (isset($sumfor[$i]) && $sumfor[$i] > $meilleurecolonne) {
			$meilleurecolonne = $sumfor[$i];
		}
	}
}


// Show line total
print '<tr>'."\n";
print '<td></td>'."\n";
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
	print '<td></td>'."\n";
	print '<td></td>'."\n";
	for ($i = 0; $i < $nbcolonnes; $i++) {
		if (empty($listofanswers[$i]['format']) || !in_array($listofanswers[$i]['format'], array('yesno', 'foragainst')) && isset($sumfor[$i]) && isset($meilleurecolonne) && $sumfor[$i] == $meilleurecolonne) {
			print '<td class="somme"><img src="'.dol_buildpath('/opensurvey/img/medaille.png', 1).'"></td>'."\n";
		} else {
			print '<td class="somme"></td>'."\n";
		}
	}
	print '</tr>'."\n";
}

// S'il a oublié de remplir un nom
if (GETPOSTISSET("boutonp") && GETPOST("nom") == "") {
	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Name")), null, 'errors');
}

if (isset($erreur_prenom) && $erreur_prenom) {
	setEventMessages($langs->trans('VoteNameAlreadyExists'), null, 'errors');
}

if (isset($erreur_ajout_date) && $erreur_ajout_date) {
	setEventMessages($langs->trans("ErrorWrongDate"), null, 'errors');
}

//fin du tableau
print '</table>'."\n";
print '</div>'."\n";


$toutsujet = explode(",", $object->sujet); // With old versions, this field was not set

$compteursujet = 0;
$meilleursujet = '';
for ($i = 0; $i < $nbcolonnes; $i++) {
	if (isset($sumfor[$i]) && isset($meilleurecolonne) && ($sumfor[$i] == $meilleurecolonne)) {
		$meilleursujet .= ($meilleursujet ? ", " : "");

		if ($object->format == "D") {
			//var_dump($toutsujet);
			if (strpos($toutsujet[$i], '@') !== false) {
				$toutsujetdate = explode("@", $toutsujet[$i]);
				$meilleursujet .= dol_print_date($toutsujetdate[0], 'daytext').($toutsujetdate[0] ? ' ('.dol_print_date($toutsujetdate[0], '%A').')' : '').' - '.$toutsujetdate[1];
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
		print '<img src="'.DOL_URL_ROOT.'/opensurvey/img/medaille.png"> '.$langs->trans('TheBestChoice').": <b>".$meilleursujet."</b> - <b>".$meilleurecolonne."</b> ".$vote_str.".\n";
	} elseif (isset($meilleurecolonne)) {
		print '<img src="'.DOL_URL_ROOT.'/opensurvey/img/medaille.png"> '.$langs->trans('TheBestChoices').": <b>".$meilleursujet."</b> - <b>".$meilleurecolonne."</b> ".$vote_str.".\n";
	}
	print '<br></p><br>'."\n";
}

print '</form>'."\n";

print '<a name="bas"></a>'."\n";

llxFooter();

$db->close();
