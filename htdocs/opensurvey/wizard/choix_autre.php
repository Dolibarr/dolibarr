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
 *	\file       htdocs/opensurvey/wizard/choix_autre.php
 *	\ingroup    opensurvey
 *	\brief      Page to create a new survey (choice selection)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/opensurvey/fonctions.php";

// Security check
if (!$user->rights->opensurvey->write) accessforbidden();



/*
 * Action
 */

// Set session vars
if (isset($_SESSION["nbrecases"])) {
	for ($i = 0; $i < $_SESSION["nbrecases"]; $i++) {
		if (isset($_POST["choix"][$i])) {
			$_SESSION["choix$i"] = $_POST["choix"][$i];
		}
		if (isset($_POST["typecolonne"][$i])) {
			$_SESSION["typecolonne$i"] = $_POST["typecolonne"][$i];
		}
	}
} else { //nombre de cases par défaut
	$_SESSION["nbrecases"] = 5;
}

if (GETPOST("ajoutcases") || GETPOST("ajoutcases_x"))
{
	$_SESSION["nbrecases"] = $_SESSION["nbrecases"] + 5;
}

// Create survey into database
if (isset($_POST["confirmecreation"]))
{
	//recuperation des données de champs textes
	$toutchoix = '';
	for ($i = 0; $i < $_SESSION["nbrecases"] + 1; $i++)
	{
		if (!empty($_POST["choix"][$i]))
		{
			$toutchoix .= ',';
			$toutchoix .= str_replace(array(",", "@"), " ", $_POST["choix"][$i]).(empty($_POST["typecolonne"][$i]) ? '' : '@'.$_POST["typecolonne"][$i]);
		}
	}

	$toutchoix = substr("$toutchoix", 1);
	$_SESSION["toutchoix"] = $toutchoix;

	//test de remplissage des cases
	$testremplissage = '';
	for ($i = 0; $i < $_SESSION["nbrecases"]; $i++)
	{
		if (isset($_POST["choix"][$i]))
		{
			$testremplissage = "ok";
		}
	}

	//message d'erreur si aucun champ renseigné
	if ($testremplissage != "ok" || (!$toutchoix)) {
		setEventMessages($langs->trans("ErrorOpenSurveyOneChoice"), null, 'errors');
	} else {
		//format du sondage AUTRE
		$_SESSION["formatsondage"] = "A";

		// Add into database
		ajouter_sondage();
	}
}




/*
 * View
 */

$form = new Form($db);

$arrayofjs = array();
$arrayofcss = array('/opensurvey/css/style.css');
llxHeader('', $langs->trans("OpenSurvey"), "", '', 0, 0, $arrayofjs, $arrayofcss);

if (empty($_SESSION['titre']))
{
	dol_print_error('', $langs->trans('ErrorOpenSurveyFillFirstSection'));
	llxFooter();
	exit;
}


//partie creation du sondage dans la base SQL
//On prépare les données pour les inserer dans la base

print '<form name="formulaire" action="#bas" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';

print load_fiche_titre($langs->trans("CreatePoll").' (2 / 2)');


print '<br>'.$langs->trans("PollOnChoice").'<br><br>'."\n";

print '<div class=corps>'."\n";
print '<table>'."\n";

//affichage des cases texte de formulaire
for ($i = 0; $i < $_SESSION["nbrecases"]; $i++) {
	$j = $i + 1;
	if (isset($_SESSION["choix$i"]) === false) {
		$_SESSION["choix$i"] = '';
	}
	print '<tr><td>'.$langs->trans("TitleChoice").' '.$j.': </td><td><input type="text" name="choix[]" size="40" maxlength="40" value="'.dol_escape_htmltag($_SESSION["choix$i"]).'" id="choix'.$i.'">';
	$tmparray = array('checkbox'=>$langs->trans("CheckBox"), 'yesno'=>$langs->trans("YesNoList"), 'foragainst'=>$langs->trans("PourContreList"));
	print ' &nbsp; '.$langs->trans("Type").' '.$form->selectarray("typecolonne[]", $tmparray, $_SESSION["typecolonne$i"]);
	print '</td></tr>'."\n";
}

print '</table>'."\n";

//ajout de cases supplementaires
print '<table><tr>'."\n";
print '<td>'.$langs->trans("5MoreChoices").'</td><td><input type="image" name="ajoutcases" src="../img/add-16.png"></td>'."\n";
print '</tr></table>'."\n";
print'<br>'."\n";

print '<table><tr>'."\n";
print '<td></td><td><input type="submit" class="button" name="confirmecreation" value="'.dol_escape_htmltag($langs->trans("CreatePoll")).'"></td>'."\n";
print '</tr></table>'."\n";

//fin du formulaire et bandeau de pied
print '</form>'."\n";


print '<a name=bas></a>'."\n";
print '<br><br><br>'."\n";
print '</div>'."\n";

// End of page
llxFooter();
$db->close();
