<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/opensurvey/fonction.php
 *	\ingroup    opensurvey
 *	\brief      Functions for module
 */



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
	global $user, $conf, $langs, $mysoc;

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
	global $user, $conf, $langs, $mysoc;

	// Print logo
	$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';

	if (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
	}
	elseif (! empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
		$width=128;
	}
	elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
	{
		$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
	}
	print '<center>';
	print '<img alt="Logo" id="logosubscribe" title="" src="'.$urllogo.'" style="max-width: 120px" /><br>';
	print '<strong>'.$langs->trans("OpenSurvey").'</strong>';
	print '</center><br>';
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
 * is_error
 *
 * @param unknown_type $cerr error number
 * @return 	boolean				Error key found or not
 */
function is_error($cerr)
{
	global $err;
	if ( $err == 0 ) {
		return false;
	}

	return (($err & $cerr) != 0 );
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
		$url = get_server_name().'adminstuds_preview.php?sondage='.$id;
	} else {
		$url = get_server_name().'/public/studs.php?sondage='.$id;
	}

	return $url;
}


/**
 * 	Generate a random id
 *
 *	@param	string	$car	Char to generate key
 * 	@return	void
 */
function dol_survey_random($car)
{
	$string = "";
	$chaine = "abcdefghijklmnopqrstuvwxyz123456789";
	srand((double) microtime()*1000000);
	for($i=0; $i<$car; $i++) {
		$string .= $chaine[rand()%strlen($chaine)];
	}
	return $string;
}

/**
 * Add a poll
 *
 * @param	string	$origin		Origin of poll creation
 * @return	void
 */
function ajouter_sondage($origin)
{
	global $conf, $db;

	$sondage=dol_survey_random(16);
	$sondage_admin=$sondage.dol_survey_random(8);

	if ($_SESSION["formatsondage"]=="A"||$_SESSION["formatsondage"]=="A+") {
		//extraction de la date de fin choisie
		if ($_SESSION["champdatefin"]) {
			if ($_SESSION["champdatefin"]>time()+250000) {
				$date_fin=$_SESSION["champdatefin"];
			}
		} else {
			$date_fin=time()+15552000;
		}
	}

	if ($_SESSION["formatsondage"]=="D"||$_SESSION["formatsondage"]=="D+") {
		//Calcul de la date de fin du sondage
		$taille_tableau=count($_SESSION["totalchoixjour"])-1;
		$date_fin=$_SESSION["totalchoixjour"][$taille_tableau]+200000;
	}

	if (is_numeric($date_fin) === false) {
		$date_fin = time()+15552000;
	}
	$canedit=empty($_SESSION['formatcanedit'])?'0':'1';

	// Insert survey
	$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'opensurvey_sondage';
	$sql.= '(id_sondage, commentaires, mail_admin, nom_admin, titre, id_sondage_admin, date_fin, format, mailsonde, canedit, origin, sujet)';
	$sql.= " VALUES ('".$db->escape($sondage)."', '".$db->escape($_SESSION['commentaires'])."', '".$db->escape($_SESSION['adresse'])."', '".$db->escape($_SESSION['nom'])."',";
	$sql.= " '".$db->escape($_SESSION['titre'])."', '".$sondage_admin."', '".$db->idate($date_fin)."', '".$_SESSION['formatsondage']."', '".$db->escape($_SESSION['mailsonde'])."',";
	$sql.= " '".$canedit."', '".$db->escape($origin)."',";
	$sql.= " '".$db->escape($_SESSION['toutchoix'])."'";
	$sql.= ")";
	dol_syslog($sql);
	$resql=$db->query($sql);

	if ($origin == 'dolibarr') $urlback=dol_buildpath('/opensurvey/adminstuds_preview.php',1).'?sondage='.$sondage_admin;
	else
	{
		// Define $urlwithroot
		$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
		$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		$url=$urlwithouturlroot.dol_buildpath('/opensurvey/public/studs.php',1).'?sondage='.$sondage;

		$urlback=$url;

		//var_dump($urlback);exit;
	}

	unset($_SESSION["titre"]);
	unset($_SESSION["nom"]);
	unset($_SESSION["adresse"]);
	unset($_SESSION["commentaires"]);
	unset($_SESSION["canedit"]);
	unset($_SESSION["mailsonde"]);

	header("Location: ".$urlback);
	exit();
}



define('COMMENT_EMPTY',         0x0000000001);
define('COMMENT_USER_EMPTY',    0x0000000010);
define('COMMENT_INSERT_FAILED', 0x0000000100);
define('NAME_EMPTY',            0x0000001000);
define('NAME_TAKEN',            0x0000010000);
define('NO_POLL',               0x0000100000);
define('NO_POLL_ID',            0x0001000000);
define('INVALID_EMAIL',         0x0010000000);
define('TITLE_EMPTY',           0x0100000000);
define('INVALID_DATE',          0x1000000000);
$err = 0;

?>
