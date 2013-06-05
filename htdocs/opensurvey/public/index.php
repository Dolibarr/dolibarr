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


//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1'); // If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
define("NOLOGIN",1);		// This means this output page does not require to be logged.
define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/fonctions.php");

$origin=GETPOST('origin','alpha');

$langs->load("opensurvey");


/*
 * View
 */

$arrayofjs=array();
$arrayofcss=array('/opensurvey/css/style.css');
llxHeaderSurvey($langs->trans("OpenSurvey"), "", 0, 0, $arrayofjs, $arrayofcss);

print '<center>
<form name="formulaire" action="create_survey.php" method="POST">
<input type="hidden" name="origin" value="'.dol_escape_htmltag($origin).'">
<div id="interface-header" style="">
<p id="application-description" class="pp-gris-fonce2">'.$langs->trans("OpenSurveyDesc").' '.$langs->trans("OpenSurveyNoRegistration").'</p>
</div><br>';
print $langs->trans("OrganizeYourMeetingEasily").'
<div class="corps">
<br>
<div class="index_date"><div><img class="opacity" src="images/date.png" onclick="document.formulaire.date.click()"></div><button id="date" name="choix_sondage" value="date" type="submit" class="button orange bigrounded"><img src="images/calendar-32.png" alt=""><strong>&nbsp;Créer un sondage spécial dates</strong></button></div><div class="index_sondage"><div><img class="opacity" src="images/sondage2.png" onclick="document.formulaire.autre.click()"></div><button id="autre" name="choix_sondage" value="autre" type="submit" class="button blue bigrounded"><img src="images/chart-32.png" alt=""><strong>&nbsp;Créer un sondage classique</strong></button></div><div style="clear:both;"></div>
</div>
</form></center>';

llxFooterSurvey();

$db->close();
?>