<?php
/* Copyright (C) 2013       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
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
 *	\file       htdocs/opensurvey/wizard/choix_date.php
 *	\ingroup    opensurvey
 *	\brief      Page to create a new survey (date selection)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/fonctions.php";

// Security check
if (!$user->rights->opensurvey->write) accessforbidden();

// Survey type is DATE
$_SESSION["formatsondage"] = "D";

$erreur = false;

/*
 * Actions
 */

// Insert survey
if (GETPOST('confirmation'))
{

	// We save hours entered
	if (issetAndNoEmpty('totalchoixjour', $_SESSION) === true && issetAndNoEmpty('nbrecaseshoraires', $_SESSION) === true)
	{
		$nbofchoice=count($_SESSION["totalchoixjour"]);

		for ($i = 0; $i < $nbofchoice; $i++)
		{
			// Show hours choices
			for ($j = 0; $j < $_SESSION["nbrecaseshoraires"]; $j++)
			{
				$_SESSION["horaires$i"][$j] = $_POST["horaires$i"][$j];

				$case = $j + 1;

				if (isset($_POST['horaires'.$i]) === false || isset($_POST['horaires'.$i][$j]) === false) {
					$errheure[$i][$j]=true;
					$erreur=true;
					continue;
				}

				//si c'est un creneau type 8:00-11:00
				if (preg_match("/(\d{1,2}:\d{2})-(\d{1,2}:\d{2})/", $_POST["horaires$i"][$j], $creneaux)) {
					//on recupere les deux parties du preg_match qu'on redécoupe autour des ":"
					$debutcreneau=explode(":", $creneaux[1]);
					$fincreneau=explode(":", $creneaux[2]);

					//comparaison des heures de fin et de debut
					//si correctes, on entre les données dans la variables de session
					if ($debutcreneau[0] < 24 && $fincreneau[0] < 24 && $debutcreneau[1] < 60 && $fincreneau[1] < 60 && ($debutcreneau[0] < $fincreneau[0] || ($debutcreneau[0] == $fincreneau[0] && $debutcreneau[1] < $fincreneau[1]))) {
						$_SESSION["horaires$i"][$j] = $creneaux[1].'-'.$creneaux[2];
					} else { //sinon message d'erreur et nettoyage de la case
						$errheure[$i][$j]=true;
						$erreur=true;
					}
				} elseif (preg_match(";^(\d{1,2}h\d{0,2})-(\d{1,2}h\d{0,2})$;i", $_POST["horaires$i"][$j], $creneaux)) { //si c'est un creneau type 8h00-11h00
					//on recupere les deux parties du preg_match qu'on redécoupe autour des "H"
					$debutcreneau=preg_split("/h/i", $creneaux[1]);
					$fincreneau=preg_split("/h/i", $creneaux[2]);

					//comparaison des heures de fin et de debut
					//si correctes, on entre les données dans la variables de session
					if ($debutcreneau[0] < 24 && $fincreneau[0] < 24 && $debutcreneau[1] < 60 && $fincreneau[1] < 60 && ($debutcreneau[0] < $fincreneau[0] || ($debutcreneau[0] == $fincreneau[0] && $debutcreneau[1] < $fincreneau[1]))) {
						$_SESSION["horaires$i"][$j] = $creneaux[1].'-'.$creneaux[2];
					} else { //sinon message d'erreur et nettoyage de la case
						$errheure[$i][$j]=true;
						$erreur=true;
					}
				} elseif (preg_match(";^(\d{1,2}):(\d{2})$;", $_POST["horaires$i"][$j], $heures)) { //si c'est une heure simple type 8:00
					//si valeures correctes, on entre les données dans la variables de session
					if ($heures[1] < 24 && $heures[2] < 60) {
						$_SESSION["horaires$i"][$j] = $heures[0];
					} else { //sinon message d'erreur et nettoyage de la case
						$errheure[$i][$j]=true;
						$erreur=true;
					}
				} elseif (preg_match(";^(\d{1,2})h(\d{0,2})$;i", $_POST["horaires$i"][$j], $heures)) { //si c'est une heure encore plus simple type 8h
					//si valeures correctes, on entre les données dans la variables de session
					if ($heures[1] < 24 && $heures[2] < 60) {
						$_SESSION["horaires$i"][$j] = $heures[0];
					} else { //sinon message d'erreur et nettoyage de la case
						$errheure[$i][$j]=true;
						$erreur=true;
					}
				} elseif (preg_match(";^(\d{1,2})-(\d{1,2})$;", $_POST["horaires$i"][$j], $heures)) { //si c'est un creneau simple type 8-11
					//si valeures correctes, on entre les données dans la variables de session
					if ($heures[1] < $heures[2] && $heures[1] < 24 && $heures[2] < 24) {
						$_SESSION["horaires$i"][$j] = $heures[0];
					} else { //sinon message d'erreur et nettoyage de la case
						$errheure[$i][$j]=true;
						$erreur=true;
					}
				} elseif (preg_match(";^(\d{1,2})h-(\d{1,2})h$;", $_POST["horaires$i"][$j], $heures)) { //si c'est un creneau H type 8h-11h
					//si valeures correctes, on entre les données dans la variables de session
					if ($heures[1] < $heures[2] && $heures[1] < 24 && $heures[2] < 24) {
						$_SESSION["horaires$i"][$j] = $heures[0];
					} else { //sinon message d'erreur et nettoyage de la case
						$errheure[$i][$j]=true;
						$erreur=true;
					}
				} elseif ($_POST["horaires$i"][$j]=="") { //Si la case est vide
					unset($_SESSION["horaires$i"][$j]);
				} else { //pour tout autre format, message d'erreur
					$errheure[$i][$j]=true;
					$erreur=true;
				}

				if (issetAndNoEmpty('horaires'.$i, $_SESSION) === false || issetAndNoEmpty($j, $_SESSION['horaires'.$i]) === false) {
					if (issetAndNoEmpty('horaires'.$i, $_SESSION) === true) {
						$_SESSION["horaires$i"][$j] = '';
					} else {
						$_SESSION["horaires$i"] = array();
						$_SESSION["horaires$i"][$j] = '';
					}
				}
			}

			if ($_SESSION["horaires$i"][0] == "" && $_SESSION["horaires$i"][1] == "" && $_SESSION["horaires$i"][2] == "" && $_SESSION["horaires$i"][3] == "" && $_SESSION["horaires$i"][4] == "") {
				$choixdate.=",";
				$choixdate .= $_SESSION["totalchoixjour"][$i];
			} else {
				for ($j=0;$j<$_SESSION["nbrecaseshoraires"];$j++) {
					if ($_SESSION["horaires$i"][$j]!="") {
						$choixdate.=",";
						$choixdate .= $_SESSION["totalchoixjour"][$i];
						$choixdate.="@";
						// On remplace la virgule et l'arobase pour ne pas avoir de problème par la suite
						$choixdate .= str_replace(array(',', '@'), array('&#44;', '&#64;'), $_SESSION["horaires$i"][$j]);
					}
				}
			}
		}

		if (isset($errheure)) {
			setEventMessages($langs->trans("ErrorBadFormat"), null, 'errors');
		}
	}

	//If just one day and no other time options, error message
	if (count($_SESSION["totalchoixjour"])=="1" && $_POST["horaires0"][0]=="" && $_POST["horaires0"][1]=="" && $_POST["horaires0"][2]=="" && $_POST["horaires0"][3]=="" && $_POST["horaires0"][4]=="") {
		setEventMessages($langs->trans("MoreChoices"), null, 'errors');
		$erreur=true;
	}

	// Add survey into database
	if (!$erreur)
	{
		$_SESSION["toutchoix"]=substr("$choixdate", 1);

		ajouter_sondage();
	}
}

// Reset days
if (GETPOST('reset'))
{
	$nbofchoice=count($_SESSION["totalchoixjour"]);
	for ($i = 0; $i < $nbofchoice; $i++)
	{
		for ($j = 0; $j < $_SESSION["nbrecaseshoraires"]; $j++) {
			unset($_SESSION["horaires$i"][$j]);
		}
	}

	unset($_SESSION["totalchoixjour"]);
	unset($_SESSION["nbrecaseshoraires"]);
}



/*
 * View
 */

if (! isset($_SESSION['commentaires']) && ! isset($_SESSION['mail']))
{
	dol_print_error('', $langs->trans('ErrorOpenSurveyFillFirstSection'));
	exit;
}

$arrayofjs=array();
$arrayofcss=array('/opensurvey/css/style.css');
llxHeader('', $langs->trans("OpenSurvey"), "", '', 0, 0, $arrayofjs, $arrayofcss);

//nombre de cases par défaut
if (! isset($_SESSION["nbrecaseshoraires"]))
{
	$_SESSION["nbrecaseshoraires"]=5;
}
elseif (GETPOST('ajoutcases') && $_SESSION["nbrecaseshoraires"] == 5)
{
	$_SESSION["nbrecaseshoraires"]=10;
}

//valeurs de la date du jour actuel
$jourAJ=date("j");
$moisAJ=date("n");
$anneeAJ=date("Y");

// Initialisation des jour, mois et année
if (! isset($_SESSION['jour'])) $_SESSION['jour']= date('j');
if (! isset($_SESSION['mois'])) $_SESSION['mois']= date('n');
if (! isset($_SESSION['annee'])) $_SESSION['annee']= date('Y');

//mise a jour des valeurs de session si bouton retour a aujourd'hui
if (!issetAndNoEmpty('choixjourajout') && !issetAndNoEmpty('choixjourretrait') && (issetAndNoEmpty('retourmois') || issetAndNoEmpty('retourmois_x'))) {
	$_SESSION["jour"]=date("j");
	$_SESSION["mois"]=date("n");
	$_SESSION["annee"]=date("Y");
}

//mise a jour des valeurs de session si mois avant
if (issetAndNoEmpty('moisavant_x') || issetAndNoEmpty('moisavant')) {
	if ($_SESSION["mois"] == 1) {
		$_SESSION["mois"]   = 12;
		$_SESSION["annee"]  = $_SESSION["annee"]-1;
	} else {
		$_SESSION["mois"] -= 1;
	}

	//On sauvegarde les heures deja entrées
	if (issetAndNoEmpty('totalchoixjour', $_SESSION) === true)
	{
		$nbofchoice=count($_SESSION["totalchoixjour"]);
		for ($i = 0; $i < $nbofchoice; $i++) {
			//affichage des 5 cases horaires
			for ($j = 0; $j < $_SESSION["nbrecaseshoraires"]; $j++) {
				$_SESSION["horaires$i"][$j] = $_POST["horaires$i"][$j];
			}
		}
	}
}

//mise a jour des valeurs de session si mois apres
if (issetAndNoEmpty('moisapres_x') || issetAndNoEmpty('moisapres')) {
	if ($_SESSION["mois"] == 12) {
		$_SESSION["mois"] = 1;
		$_SESSION["annee"] += 1;
	} else {
		$_SESSION["mois"] += 1;
	}

	//On sauvegarde les heures deja entrées
	if (issetAndNoEmpty('totalchoixjour', $_SESSION) === true)
	{
		$nbofchoice=count($_SESSION["totalchoixjour"]);
		for ($i = 0; $i < $nbofchoice; $i++)
		{
			//affichage des 5 cases horaires
			for ($j = 0; $j < $_SESSION["nbrecaseshoraires"]; $j++) {
				$_SESSION["horaires$i"][$j] = $_POST["horaires$i"][$j];
			}
		}
	}
}

//mise a jour des valeurs de session si annee avant
if (issetAndNoEmpty('anneeavant_x') || issetAndNoEmpty('anneeavant')) {
	$_SESSION["annee"] -= 1;

	//On sauvegarde les heures deja entrées
	if (issetAndNoEmpty('totalchoixjour', $_SESSION) === true)
	{
		$nbofchoice=count($_SESSION["totalchoixjour"]);
		for ($i = 0; $i < $nbofchoice; $i++) {
			//affichage des 5 cases horaires
			for ($j = 0; $j < $_SESSION["nbrecaseshoraires"]; $j++) {
				$_SESSION["horaires$i"][$j] = $_POST["horaires$i"][$j];
			}
		}
	}
}

//mise a jour des valeurs de session si annee apres
if (issetAndNoEmpty('anneeapres_x') || issetAndNoEmpty('anneeapres')) {
	$_SESSION["annee"] += 1;

	//On sauvegarde les heures deja entrées
	if (issetAndNoEmpty('totalchoixjour', $_SESSION) === true)
	{
		$nbofchoice=count($_SESSION["totalchoixjour"]);
		for ($i = 0; $i < $nbofchoice; $i++) {
			//affichage des 5 cases horaires
			for ($j = 0;$j < $_SESSION["nbrecaseshoraires"]; $j++) {
				$_SESSION["horaires$i"][$j] = $_POST["horaires$i"][$j];
			}
		}
	}
}

//valeurs du nombre de jour dans le mois et du premier jour du mois
$nbrejourmois = date("t", mktime(0, 0, 0, $_SESSION["mois"], 1, $_SESSION["annee"]));
$premierjourmois = date("N", mktime(0, 0, 0, $_SESSION["mois"], 1, $_SESSION["annee"])) - 1;

//traduction de la valeur du mois
if (is_integer($_SESSION["mois"]) && $_SESSION["mois"] > 0 && $_SESSION["mois"] < 13)
{
	$motmois=dol_print_date(mktime(0, 0, 0, $_SESSION["mois"], 10), '%B');
}
else
{
	$motmois=dol_print_date(dol_now(), '%B');
}


//Debut du formulaire et bandeaux de tete
print '<form name="formulaire" action="" method="POST">'."\n";

print load_fiche_titre($langs->trans("CreatePoll").' (2 / 2)');

//affichage de l'aide pour les jours
print '<div class="bodydate">'."\n";
print $langs->trans("OpenSurveyStep2")."\n";
print '</div>'."\n";

//debut du tableau qui affiche le calendrier
print '<div class="corps">'."\n";
print '<div class="center">'."\n";
print '<table align="center">'."\n";	// The div class=center has no effect on table, so we must keep the align=center for table
print '<tr><td><input type="image" name="anneeavant" value="<<" src="../img/rewind.png"></td><td><input type="image" name="moisavant" value="<" src="../img/previous.png"></td>';
print '<td width="150px" align="center"> '.$motmois.' '.$_SESSION["annee"].'<br>';
print '<input type="image" name="retourmois" alt="'.dol_escape_htmltag($langs->trans("BackToCurrentMonth")).'" title="'.dol_escape_htmltag($langs->trans("BackToCurrentMonth")).'" value="" src="'.img_picto('', 'refresh', '', 0, 1).'">';
print '</td><td><input type="image" name="moisapres" value=">" src="../img/next.png"></td>';
print '<td><input type="image" name="anneeapres" value=">>" src="../img/fforward.png"></td><td></td><td></td><td></td><td></td><td></td><td>';
print '</td></tr>'."\n";
print '</table>'."\n";
print '</div>'."\n";

print '<div class="center">'."\n";
print '<table align="center">'."\n";	// The div class=center has no effect on table, so we must keep the align=center for table
print '<tr>'."\n";

//affichage des jours de la semaine en haut du tableau
for($i = 0; $i < 7; $i++)
{
	print '<td align="center" class="joursemaine">'. dol_print_date(mktime(0, 0, 0, 0, $i, 10), '%A') .'</td>';
}

print '</tr>'."\n";

//ajout d'une entrée dans la variable de session qui contient toutes les dates
if (issetAndNoEmpty('choixjourajout')) {
	if (!isset($_SESSION["totalchoixjour"])) {
		$_SESSION["totalchoixjour"]=array();
	}

	// Test pour éviter les doublons dans la variable qui contient toutes les dates
	$journeuf = true;
	if (issetAndNoEmpty('totalchoixjour', $_SESSION) === true && issetAndNoEmpty('choixjourajout') === true)
	{
		$nbofchoice=count($_SESSION["totalchoixjour"]);
		for ($i = 0; $i < $nbofchoice; $i++) {
			if ($_SESSION["totalchoixjour"][$i] == mktime(0, 0, 0, $_SESSION["mois"], $_POST["choixjourajout"][0], $_SESSION["annee"])) {
				$journeuf=false;
			}
		}
	}

	// Si le test est passé, alors on insere la valeur dans la variable de session qui contient les dates
	if ($journeuf && issetAndNoEmpty('choixjourajout') === true) {
		array_push($_SESSION["totalchoixjour"], dol_mktime(0, 0, 0, $_SESSION["mois"], $_POST["choixjourajout"][0], $_SESSION["annee"]));
		sort($_SESSION["totalchoixjour"]);
		$cle=array_search(dol_mktime(0, 0, 0, $_SESSION["mois"], $_POST["choixjourajout"][0], $_SESSION["annee"]), $_SESSION["totalchoixjour"]);

		//On sauvegarde les heures deja entrées
		for ($i = 0; $i < $cle; $i++) {
			for ($j = 0; $j < $_SESSION["nbrecaseshoraires"]; $j++) {
				if (issetAndNoEmpty('horaires'.$i) === true && issetAndNoEmpty($i, $_POST['horaires'.$i]) === true) {
					$_SESSION["horaires$i"][$j] = $_POST["horaires$i"][$j];
				}
			}
		}

		$nbofchoice=count($_SESSION["totalchoixjour"]);
		for ($i = $cle; $i < $nbofchoice; $i++) {
			$k = $i + 1;
			if (issetAndNoEmpty('horaires'.$i) === true && issetAndNoEmpty($i, $_POST['horaires'.$i]) === true) {
				for ($j = 0; $j < $_SESSION["nbrecaseshoraires"]; $j++) {
					$_SESSION["horaires$k"][$j] = $_POST["horaires$i"][$j];
				}
			}
		}

		unset($_SESSION["horaires$cle"]);
	}
}

//retrait d'une entrée dans la variable de session qui contient toutes les dates
if (issetAndNoEmpty('choixjourretrait')) {
	//On sauvegarde les heures deja entrées
	$nbofchoice=count($_SESSION["totalchoixjour"]);
	for ($i = 0; $i < $nbofchoice; $i++) {
		//affichage des 5 cases horaires
		for ($j = 0; $j < $_SESSION["nbrecaseshoraires"]; $j++) {
			$_SESSION["horaires$i"][$j] = $_POST["horaires$i"][$j];
		}
	}

	for ($i = 0; $i < $nbofchoice; $i++)
	{
		if ($_SESSION["totalchoixjour"][$i] == mktime(0, 0, 0, $_SESSION["mois"], $_POST["choixjourretrait"][0], $_SESSION["annee"]))
		{
			for ($j = $i; $j < $nbofchoice; $j++) {
				$k = $j+1;
				$_SESSION["horaires$j"] = $_SESSION["horaires$k"];
			}

			array_splice($_SESSION["totalchoixjour"], $i, 1);
		}
	}
}

//report des horaires dans toutes les cases
if (issetAndNoEmpty('reporterhoraires')) {
	$_SESSION["horaires0"] = $_POST["horaires0"];
	$nbofchoice=count($_SESSION["totalchoixjour"]);
	for ($i = 0; $i < $nbofchoice; $i++) {
		$j = $i+1;
		$_SESSION["horaires$j"] = $_SESSION["horaires$i"];
	}
}

//report des horaires dans toutes les cases
if (issetAndNoEmpty('resethoraires')) {
	$nbofchoice=count($_SESSION["totalchoixjour"]);
	for ($i = 0; $i < $nbofchoice; $i++) {
		unset($_SESSION["horaires$i"]);
	}
}

// affichage du calendrier
print '<tr>'."\n";

for ($i = 0; $i < $nbrejourmois + $premierjourmois; $i++) {
	$numerojour = $i-$premierjourmois+1;

	// On saute a la ligne tous les 7 jours
	if (($i%7) == 0 && $i != 0) {
		print '</tr><tr>'."\n";
	}

	// On affiche les jours precedants en gris et incliquables
	if ($i < $premierjourmois) {
		print '<td class="avant"></td>'."\n";
	} else {
		if (issetAndNoEmpty('totalchoixjour', $_SESSION) === true)
		{
			$nbofchoice=count($_SESSION["totalchoixjour"]);
			for ($j = 0; $j < $nbofchoice; $j++) {
				// show red buttons
				if (date("j", $_SESSION["totalchoixjour"][$j]) == $numerojour && date("n", $_SESSION["totalchoixjour"][$j]) == $_SESSION["mois"] && date("Y", $_SESSION["totalchoixjour"][$j]) == $_SESSION["annee"]) {
					print '<td align="center" class="choisi"><input type="submit" class="bouton OFF" name="choixjourretrait[]" value="'.$numerojour.'"></td>'."\n";
					$dejafait = $numerojour;
				}
			}
		}

		// If no red button, we show green or grey button with number of day
		if (isset($dejafait) === false || $dejafait != $numerojour){
			// green button
			if (($numerojour >= $jourAJ && $_SESSION["mois"] == $moisAJ && $_SESSION["annee"] == $anneeAJ) || ($_SESSION["mois"] > $moisAJ && $_SESSION["annee"] == $anneeAJ) || $_SESSION["annee"] > $anneeAJ) {
				print '<td align="center" class="libre"><input type="submit" class="bouton ON" name="choixjourajout[]" value="'.$numerojour.'"></td>'."\n";
			} else {
                // grey button
				print '<td align="center" class="avant">'.$numerojour.'</td>'."\n";
			}
		}
	}
}

//fin du tableau
print '</tr>'."\n";
print '</table>'."\n";
print '</div></div>'."\n";

print '<div class="bodydate"><div class="center">'."\n";

// affichage de tous les jours choisis
if (issetAndNoEmpty('totalchoixjour', $_SESSION) || $erreur)
{
	//affichage des jours
	print '<br>'."\n";
	print '<div align="left">';
	print '<strong>'. $langs->trans("SelectedDays") .':</strong>'."<br>\n";
	print $langs->trans("SelectDayDesc")."<br>\n";
	print '</div><br>';

	print '<table>'."\n";
	print '<tr>'."\n";
	print '<td></td>'."\n";

	for ($i = 0; $i < $_SESSION["nbrecaseshoraires"]; $i++) {
		$j = $i+1;
		print '<td classe="somme"><div class="center">'. $langs->trans("Time") .' '.$j.'</div></td>'."\n";
	}

	if ($_SESSION["nbrecaseshoraires"] < 10) {
		print '<td classe="somme"><input type="image" name="ajoutcases" src="../img/add-16.png"></td>'."\n";
	}

	print '</tr>'."\n";

	//affichage de la liste des jours choisis
	$nbofchoice=count($_SESSION["totalchoixjour"]);

	for ($i=0; $i<$nbofchoice; $i++)
	{
		print '<tr>'."\n";
		print '<td>'.dol_print_date($_SESSION["totalchoixjour"][$i], 'daytext').' ('.dol_print_date($_SESSION["totalchoixjour"][$i], '%A').')</td>';

		//affichage des cases d'horaires
		for ($j=0;$j<$_SESSION["nbrecaseshoraires"];$j++) {
			//si on voit une erreur, le fond de la case est rouge
			if (isset($errheure[$i][$j]) && $errheure[$i][$j]) {
				print '<td><input type=text size="10" maxlength="11" name=horaires'.$i.'[] value="'.$_SESSION["horaires$i"][$j].'" style="background-color:#FF6666;"></td>'."\n";
			} else { //sinon la case est vide normalement

				print '<td><input type=text size="10" maxlength="11" name=horaires'.$i.'[] value="'.$_SESSION["horaires$i"][$j].'"></td>'."\n";
			}
		}
		print '</tr>'."\n";
	}

	print '</table>'."\n";

	// show buttons to cancel, delete days or create survey
	print '<table>'."\n";
	print '<tr>'."\n";
	print '<td><input type="submit" class="button" name="reset" value="'. dol_escape_htmltag($langs->trans("RemoveAllDays")) .'"></td><td><input type="submit" class="button" name="reporterhoraires" value="'. dol_escape_htmltag($langs->trans("CopyHoursOfFirstDay")) .'"></td><td><input type="submit" class="button" name="resethoraires" value="'. dol_escape_htmltag($langs->trans("RemoveAllHours")) .'"></td></tr>'."\n";
	print'<tr><td colspan="3"><br><br></td></tr>'."\n";
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="confirmation" value="'. $langs->trans("CreatePoll"). '"></td></tr>'."\n";
	print '</table>'."\n";
}

print '</tr>'."\n";
print '</table>'."\n";
print '<a name=bas></a>'."\n";
//fin du formulaire et bandeau de pied
print '</form>'."\n";
//bandeau de pied
print '<br><br><br><br>'."\n";
print '</div></div>'."\n";

// End of page
llxFooter();
$db->close();
