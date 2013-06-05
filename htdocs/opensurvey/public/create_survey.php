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
 *	\file       htdocs/opensurvey/public/create_survey.php
 *	\ingroup    opensurvey
 *	\brief      Page to create a new survey
 */

define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/fonctions.php");

$langs->load("opensurvey");

$origin=GETPOST('origin','alpha');


// On teste toutes les variables pour supprimer l'ensemble des warnings PHP
// On transforme en entites html les données afin éviter les failles XSS
$post_var = array('titre', 'nom', 'adresse', 'commentaires', 'canedit', 'mailsonde', 'creation_sondage_date', 'creation_sondage_date_x', 'creation_sondage_autre', 'creation_sondage_autre_x');
foreach ($post_var as $var)
{
	$$var = GETPOST($var);
}

// On initialise egalement la session car sinon bonjour les warning :-)
$session_var = array('titre', 'nom', 'adresse', 'commentaires', 'mailsonde', 'canedit');
foreach ($session_var as $var)
{
	if (isset($_SESSION[$var])) $_SESSION[$var] = null;
}

// On initialise également les autres variables
$erreur_adresse = false;
$erreur_injection_titre = false;
$erreur_injection_nom = false;
$erreur_injection_commentaires = false;
$cocheplus = '';
$cochemail = '';

// Jump to correct page
if (GETPOST("creation_sondage_date") || GETPOST("creation_sondage_autre") || GETPOST("creation_sondage_date_x") || GETPOST("creation_sondage_autre_x"))
{
	$_SESSION["titre"] = $titre;
	$_SESSION["nom"] = $nom;
	$_SESSION["adresse"] = $adresse;
	$_SESSION["commentaires"] = $commentaires;

	unset($_SESSION["canedit"]);
	$_SESSION["canedit"] = $canedit;

	unset($_SESSION["mailsonde"]);
	if ($mailsonde !== null) {
		$_SESSION["mailsonde"] = true;
	} else {
		$_SESSION["mailsonde"] = false;
	}

	if (! isValidEmail($adresse)) $erreur_adresse = true;

	//var_dump($titre.' - '.$nom.' - '.$adresse.' - '.!$erreur_adresse.' - '.! $erreur_injection_titre.' - '.! $erreur_injection_commentaires.' - '.! $erreur_injection_nom.' - '.$creation_sondage_date.' - '.$creation_sondage_autre); exit;

	if ($titre && $nom && $adresse && !$erreur_adresse && ! $erreur_injection_titre && ! $erreur_injection_commentaires && ! $erreur_injection_nom)
	{
		if (! empty($creation_sondage_date))
		{
			header("Location: choix_date.php".($origin?'?origin='.$origin:''));
			exit();
		}

		if (! empty($creation_sondage_autre))
		{
			header("Location: choix_autre.php".($origin?'?origin='.$origin:''));
			exit();
		}
	}
}




/*
 * View
 */

$arrayofjs=array();
$arrayofcss=array('/opensurvey/css/style.css');
llxHeaderSurvey($langs->trans("OpenSurvey"), "", 0, 0, $arrayofjs, $arrayofcss);


print '<div class="bandeautitre">'. $langs->trans("CreatePoll").' (1 / 2)' .'</div>'."\n";


//debut du formulaire
print '<form name="formulaire" action="create_survey.php" method="POST" onkeypress="javascript:process_keypress(event)">'."\n";
print '<input type="hidden" name="origin" value="'.dol_escape_htmltag($origin).'">';

print '<div class=corps>'."\n";
print '<br>'. $langs->trans("YouAreInPollCreateArea") .'<br><br>'."\n";

//Affichage des différents champs textes a remplir
print '<table>'."\n";

print '<tr><td class="fieldrequired">'. $langs->trans("PollTitle") .'</td><td><input type="text" name="titre" size="40" maxlength="80" value="'.$_SESSION["titre"].'"></td>'."\n";
if (! $_SESSION["titre"] && (GETPOST('creation_sondage_date') || GETPOST('creation_sondage_autre') || GETPOST('creation_sondage_date_x') || GETPOST('creation_sondage_autre_x')))
{
	print "<td><font color=\"#FF0000\">" . $langs->trans("FieldMandatory") . "</font></td>"."\n";
}

print '</tr>'."\n";
print '<tr><td>'. $langs->trans("Description") .'</td><td><textarea name="commentaires" rows="7" cols="40">'.$_SESSION["commentaires"].'</textarea></td>'."\n";
print '</tr>'."\n";
print '<tr><td class="fieldrequired">'. $langs->trans("OpenSurveyYourName") .'</td><td>';

print '<input type="text" name="nom" size="40" maxlength="40" value="'.$_SESSION["nom"].'"></td>'."\n";

if (! $_SESSION["nom"] && (GETPOST('creation_sondage_date') || GETPOST('creation_sondage_autre') || GETPOST('creation_sondage_date_x') || GETPOST('creation_sondage_autre_x')))
{
	print "<td><font color=\"#FF0000\">" . $langs->trans("FieldMandatory")  . "</font></td>"."\n";
}

print '</tr>'."\n";
print '<tr><td class="fieldrequired">'.  $langs->trans("OpenSurveyYourEMail")  .'</td><td>';

print '<input type="text" name="adresse" size="40" maxlength="64" value="'.$_SESSION["adresse"].'"></td>'."\n";

if (!$_SESSION["adresse"] && (GETPOST('creation_sondage_date') || GETPOST('creation_sondage_autre') || GETPOST('creation_sondage_date_x') || GETPOST('creation_sondage_autre_x')))
{
  print "<td><font color=\"#FF0000\">" .$langs->trans("FieldMandatory")  . " </font></td>"."\n";
} elseif ($erreur_adresse && (GETPOST('creation_sondage_date') || GETPOST('creation_sondage_autre') || GETPOST('creation_sondage_date_x') || GETPOST('creation_sondage_autre_x')))
{
  print "<td><font color=\"#FF0000\">" . _("The address is not correct! (You should enter a valid email address in order to receive the link to your poll)") . "</font></td>"."\n";
}

print '</tr>'."\n";
print '</table>'."\n";

//focus javascript sur le premier champ
print '<script type="text/javascript">'."\n";
print 'document.formulaire.titre.focus();'."\n";
print '</script>'."\n";

print '<br>'."\n";

// Check or not
$cocheplus='';
if ($_SESSION["canedit"]) $cocheplus="checked";

print '<input type="checkbox" name="canedit" '.$cocheplus.'>'. $langs->trans("VotersCanModify") .'<br>'."\n";

if ($_SESSION["mailsonde"]) $cochemail="checked";

print '<input type=checkbox name=mailsonde '.$cochemail.'>'. $langs->trans("ToReceiveEMailForEachVote") .'<br>'."\n";

if (GETPOST('choix_sondage'))
{
	if (GETPOST('choix_sondage') == 'date') print '<input type="hidden" name="creation_sondage_date" value="date">';
	else print '<input type="hidden" name="creation_sondage_autre" value="autre">';
	print '<input type="hidden" name="choix_sondage" value="'.GETPOST('choix_sondage').'">';
	print '<br><input type="submit" class="button" name="submit" value="'.$langs->trans("CreatePoll").' ('.(GETPOST('choix_sondage') == 'date'?$langs->trans("TypeDate"):$langs->trans("TypeClassic")).')">';
}
else
{
	//affichage des boutons pour choisir sondage date ou autre
	print '<br><table >'."\n";
	print '<tr><td>'. _("Schedule an event") .'</td><td></td> '."\n";
	print '<td><input type="image" name="creation_sondage_date" value="Trouver une date" src="images/calendar-32.png"></td></tr>'."\n";
	print '<tr><td>'. _("Make a choice") .'</td><td></td> '."\n";
	print '<td><input type="image" name="creation_sondage_autre" value="'. _('Make a poll') . '" src="images/chart-32.png"></td></tr>'."\n";
	print '</table>'."\n";
}
print '<br><br><br>'."\n";
print '</div>'."\n";
print '</form>'."\n";

llxFooterSurvey();

$db->close();
?>
