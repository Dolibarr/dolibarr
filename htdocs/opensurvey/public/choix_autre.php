<?php
/* Copyright (C) 2013      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *	\file       htdocs/opensurvey/public/choix_autre.php
 *	\ingroup    opensurvey
 *	\brief      Page to create a new survey (choice selection)
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/fonctions.php");

$erreur = false;
$testdate = true;
$date_selected = '';

$origin=GETPOST('origin','alpha');



/*
 * Action
 */

// Set session vars
$erreur_injection = false;
if (isset($_SESSION["nbrecases"])) {
	for ($i = 0; $i < $_SESSION["nbrecases"]; $i++) {
		if (isset($_POST["choix"][$i])) {
			$_SESSION["choix$i"]=$_POST["choix"][$i];
		}
		if (isset($_POST["typecolonne"][$i])) {
			$_SESSION["typecolonne$i"]=$_POST["typecolonne"][$i];
		}
	}
} else { //nombre de cases par défaut
	$_SESSION["nbrecases"]=5;
}

if (isset($_POST["ajoutcases"]) || isset($_POST["ajoutcases_x"])) {
	$_SESSION["nbrecases"]=$_SESSION["nbrecases"]+5;
}

// Create survey into database
if (isset($_POST["confirmecreation"]) || isset($_POST["confirmecreation_x"]))
{
	//recuperation des données de champs textes
	$toutchoix = '';
	for ($i = 0; $i < $_SESSION["nbrecases"] + 1; $i++)
	{
		if (! empty($_POST["choix"][$i]))
		{
			$toutchoix.=',';
			$toutchoix.=str_replace(array(",","@"), " ", $_POST["choix"][$i]).(empty($_POST["typecolonne"][$i])?'':'@'.$_POST["typecolonne"][$i]);
		}
	}

	$toutchoix=substr("$toutchoix",1);
	$_SESSION["toutchoix"]=$toutchoix;

	if (GETPOST('champdatefin'))
	{
		$registredate=explode("/",$_POST["champdatefin"]);
		if (is_array($registredate) === false || count($registredate) !== 3) {
			$testdate = false;
			$date_selected = $_POST["champdatefin"];
		} else {
			$time = mktime(0,0,0,$registredate[1],$registredate[0],$registredate[2]);
			if ($time === false || date('d/m/Y', $time) !== $_POST["champdatefin"]) {
				$testdate = false;
				$date_selected = $_POST["champdatefin"];
			} else {
				if (mktime(0,0,0,$registredate[1],$registredate[0],$registredate[2]) > time() + 250000) {
					$_SESSION["champdatefin"]=mktime(0,0,0,$registredate[1],$registredate[0],$registredate[2]);
				}
			}
		}
	} else {
		$_SESSION["champdatefin"]=time()+15552000;
	}

	if ($testdate === true)
	{
		//format du sondage AUTRE
		$_SESSION["formatsondage"]="A";
		$_SESSION["caneditsondage"]=$_SESSION["canedit"];

		// Add into database
		ajouter_sondage($origin);
	} else {
		$_POST["fin_sondage_autre"] = 'ok';
	}
}




/*
 * View
 */

$form=new Form($db);

$arrayofjs=array();
$arrayofcss=array('/opensurvey/css/style.css');
llxHeaderSurvey($langs->trans("OpenSurvey"), "", 0, 0, $arrayofjs, $arrayofcss);

if (empty($_SESSION['titre']) || empty($_SESSION['nom']) || empty($_SESSION['adresse']))
{
	dol_print_error('',"You haven't filled the first section of the poll creation");
	llxFooterSurvey();
	exit;
}


//partie creation du sondage dans la base SQL
//On prépare les données pour les inserer dans la base

print '<form name="formulaire" action="#bas" method="POST" onkeypress="javascript:process_keypress(event)">'."\n";
print '<input type="hidden" name="origin" value="'.dol_escape_htmltag($origin).'">';

print '<div class="bandeautitre">'. $langs->trans("CreatePoll")." (2 / 2)" .'</div>'."\n";

print '<div class=corps>'."\n";
print '<br>'. $langs->trans("PollOnChoice") .'<br><br>'."\n";
print '<table>'."\n";

//affichage des cases texte de formulaire
for ($i = 0; $i < $_SESSION["nbrecases"]; $i++) {
	$j = $i + 1;
	if (isset($_SESSION["choix$i"]) === false) {
		$_SESSION["choix$i"] = '';
	}
	print '<tr><td>'. $langs->trans("TitleChoice") .' '.$j.' : </td><td><input type="text" name="choix[]" size="40" maxlength="40" value="'.dol_escape_htmltag($_SESSION["choix$i"]).'" id="choix'.$i.'">';
	$tmparray=array('checkbox'=>$langs->trans("CheckBox"),'yesno'=>$langs->trans("YesNoList"),'foragainst'=>$langs->trans("PourContreList"));
	print ' &nbsp; '.$langs->trans("Type").' '.$form->selectarray("typecolonne[]", $tmparray, $_SESSION["typecolonne$i"]);
	print '</td></tr>'."\n";
}

print '</table>'."\n";

//ajout de cases supplementaires
print '<table><tr>'."\n";
print '<td>'. $langs->trans("5MoreChoices") .'</td><td><input type="image" name="ajoutcases" value="Retour" src="images/add-16.png"></td>'."\n";
print '</tr></table>'."\n";
print'<br>'."\n";

print '<table><tr>'."\n";
print '<td></td><td><input type="submit" class="button" name="fin_sondage_autre" value="'.dol_escape_htmltag($langs->trans("NextStep")).'" src="images/next-32.png"></td>'."\n";
print '</tr></table>'."\n";

//test de remplissage des cases
$testremplissage = '';
for ($i=0;$i<$_SESSION["nbrecases"];$i++)
{
	if (isset($_POST["choix"][$i]))
	{
		$testremplissage="ok";
	}
}

//message d'erreur si aucun champ renseigné
if ($testremplissage != "ok" && (isset($_POST["fin_sondage_autre"]) || isset($_POST["fin_sondage_autre_x"]))) {
	print "<br><font color=\"#FF0000\">" . $langs->trans("Enter at least one choice") . "</font><br><br>"."\n";
	$erreur = true;
}

//message d'erreur si mauvaise date
if ($testdate === false) {
	print "<br><font color=\"#FF0000\">" . _("Date must be have the format DD/MM/YYYY") . "</font><br><br>"."\n";
}

if ($erreur_injection) {
	print "<font color=#FF0000>" . _("Characters \" < and > are not permitted") . "</font><br><br>\n";
}

if ((isset($_POST["fin_sondage_autre"]) || isset($_POST["fin_sondage_autre_x"])) && !$erreur && !$erreur_injection) {
	//demande de la date de fin du sondage
	print '<br>'."\n";
	print '<div class=presentationdatefin>'."\n";
	print '<br>'. _("Your poll will be automatically removed after 6 months.<br> You can fix another removal date for it.") .'<br><br>'."\n";
	print _("Removal date (optional)") .' : <input type="text" name="champdatefin" value="'.$date_selected.'" size="10" maxlength="10"> '. _("(DD/MM/YYYY)") ."\n";
	print '</div>'."\n";
	print '<div class=presentationdatefin>'."\n";
	print '<font color=#FF0000>'. $langs->trans("InfoAfterCreate") .'</font>'."\n";
	print '</div>'."\n";
	print '<br>'."\n";
	print '<table>'."\n";
	print '<tr><td>'. $langs->trans("CreatePoll") .'</td><td><input type="image" name="confirmecreation" src="images/add.png"></td></tr>'."\n";
	print '</table>'."\n";
}

//fin du formulaire et bandeau de pied
print '</form>'."\n";


print '<a name=bas></a>'."\n";
print '<br><br><br>'."\n";
print '</div>'."\n";

llxFooterSurvey();

$db->close();
?>