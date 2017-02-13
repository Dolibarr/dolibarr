<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Marcos García			<marcosgdf@gmail.com>
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
 *	\file       htdocs/opensurvey/fonctions.php
 *	\ingroup    opensurvey
 *	\brief      Functions for module
 */

/**
 * Returns an array with the tabs for the "Opensurvey poll" section
 * It loads tabs from modules looking for the entity Opensurveyso
 *
 * @param Opensurveysondage $object Current viewing poll
 * @return array Tabs for the opensurvey section
 */
function opensurvey_prepare_head(Opensurveysondage $object) {

	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[0][0] = 'card.php?id='.$object->id_sondage;
	$head[0][1] = $langs->trans("Card");
	$head[0][2] = 'general';
	$h++;

	$head[1][0] = 'results.php?id='.$object->id_sondage;
	$head[1][1] = $langs->trans("SurveyResults");
	$head[1][2] = 'preview';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'opensurveypoll');

	complete_head_from_modules($conf,$langs,$object,$head,$h,'opensurveypoll', 'remove');

	return $head;
}

/**
 * Show header for new member
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	array  		$arrayofjs			Array of complementary js files
 * @param 	array  		$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderSurvey($title, $head="", $disablejs=0, $disablehead=0, $arrayofjs='', $arrayofcss='')
{
	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers
	print '<body id="mainbody" class="publicnewmemberform" style="margin-top: 10px;">';

	showlogo();

	print '<div style="margin-left: 50px; margin-right: 50px;">';
}

/**
 * Show footer for new member
 *
 * @return	void
 */
function llxFooterSurvey()
{
	print '</div>';

	printCommonFooter('public');

	dol_htmloutput_events();

	print "</body>\n";
	print "</html>\n";
}


/**
 * Show logo
 *
 * @return	void
 */
function showlogo()
{
	global $conf, $mysoc;

	// Print logo
	if ($mysoc->logo) {
		if (file_exists($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
			$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file=thumbs/'.urlencode($mysoc->logo_small);
		}
	}

	if (!$urllogo && (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png')))
	{
		$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
	}

	print '<div style="text-align:center"><img alt="Logo" id="logosubscribe" title="" src="'.$urllogo.'"/></div>';
	print '<br>';
}


/**
 * get_server_name
 *
 * @return	string		URL to use
 */
function get_server_name()
{
	global $dolibarr_main_url_root;

	$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
	$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	$url=$urlwithouturlroot.dol_buildpath('/opensurvey/',1);

	if (!preg_match("|/$|", $url)) {
		$url = $url."/";
	}

	return $url;
}

/**
 * Fonction vérifiant l'existance et la valeur non vide d'une clé d'un tableau
 *
 * @param   string  $name       La clé à tester
 * @param   array   $tableau    Le tableau où rechercher la clé ($_POST par défaut)
 * @return  bool                Vrai si la clé existe et renvoie une valeur non vide
 */
function issetAndNoEmpty($name, $tableau = null)
{
	if ($tableau === null) {
		$tableau = $_POST;
	}

	return (isset($tableau[$name]) === true && empty($tableau[$name]) === false);
}


/**
 * Fonction permettant de générer les URL pour les sondage
 *
 * @param   string    $id     L'identifiant du sondage
 * @param   bool      $admin  True pour générer une URL pour l'administration d'un sondage, False pour un URL publique
 * @return  string            L'url pour le sondage
 */
function getUrlSondage($id, $admin = false)
{
	if ($admin === true) {
		$url = get_server_name().'results.php?id='.$id;
	} else {
		$url = get_server_name().'/public/studs.php?sondage='.$id;
	}

	return $url;
}


/**
 * 	Generate a random id
 *
 *	@param	string	$car	Char to generate key
 * 	@return	string
 */
function dol_survey_random($car)
{
	$string = "";
	$chaine = "abcdefghijklmnopqrstuvwxyz123456789";
	mt_srand((double) microtime()*1000000);
	for($i=0; $i<$car; $i++) {
		$string .= $chaine[mt_rand() % strlen($chaine)];
	}
	return $string;
}

/**
 * Add a poll
 *
 * @return	void
 */
function ajouter_sondage()
{
	global $db, $user;

	require_once DOL_DOCUMENT_ROOT.'/opensurvey/class/opensurveysondage.class.php';

	$sondage=dol_survey_random(16);

	$allow_comments = empty($_SESSION['allow_comments']) ? 0 : 1;
	$allow_spy = empty($_SESSION['allow_spy']) ? 0 : 1;

	// Insert survey
	$opensurveysondage = new Opensurveysondage($db);
	$opensurveysondage->id_sondage = $sondage;
	$opensurveysondage->commentaires = $_SESSION['commentaires'];
	$opensurveysondage->mail_admin = $_SESSION['adresse'];
	$opensurveysondage->nom_admin = $_SESSION['nom'];
	$opensurveysondage->titre = $_SESSION['titre'];
	$opensurveysondage->date_fin = $_SESSION['champdatefin'];
	$opensurveysondage->format = $_SESSION['formatsondage'];
	$opensurveysondage->mailsonde = $_SESSION['mailsonde'];
	$opensurveysondage->allow_comments = $allow_comments;
	$opensurveysondage->allow_spy = $allow_spy;
	$opensurveysondage->sujet = $_SESSION['toutchoix'];

	$res = $opensurveysondage->create($user);

	if ($res < 0) {
		dol_print_error($db);
	}

	unset($_SESSION["titre"]);
	unset($_SESSION["nom"]);
	unset($_SESSION["adresse"]);
	unset($_SESSION["commentaires"]);
	unset($_SESSION["mailsonde"]);
	unset($_SESSION['allow_comments']);
	unset($_SESSION['allow_spy']);
	unset($_SESSION['toutchoix']);
	unset($_SESSION['totalchoixjour']);
	unset($_SESSION['champdatefin']);

	$urlback=dol_buildpath('/opensurvey/card.php',1).'?id='.$sondage;

	header("Location: ".$urlback);
	exit();
}
