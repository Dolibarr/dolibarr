<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file       htdocs/projet/jsgantt_language.js.php
 *		\brief      Fichier de javascript de traduction pour JSGantt
 */

if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (! defined('NOLOGIN'))         define('NOLOGIN', 1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU', 1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

require_once __DIR__.'/../main.inc.php';

// Define mime type
top_httphead('text/javascript');

global $langs;
$langs->load("projects");
?>

var vLangs={'<?php print $langs->getDefaultLang(1);?>':
	{
	'format':'<?php print $langs->transnoentities('Period');?>','hour':'"<?php print $langs->transnoentities('Hour'); ?>','day':'<?php print $langs->transnoentities('Day'); ?>',
    'week':'<?php print $langs->transnoentities('Week'); ?>','month':'<?php print $langs->transnoentities('Month'); ?>','quarter':'<?php print $langs->transnoentities('Quadri'); ?>',
    'hours':'<?php print $langs->transnoentities('Hours'); ?>','days':'<?php print $langs->transnoentities('Days'); ?>','weeks':'<?php print $langs->transnoentities('Weeks');?>',
    'months':'<?php print $langs->transnoentities('Months'); ?>','quarters':'<?php print $langs->transnoentities('Quadri'); ?>','hr':'Hr','dy':'<?php print $langs->transnoentities('Day'); ?>','wk':'<?php print $langs->transnoentities('Week'); ?>','mth':'<?php print $langs->transnoentities('Month'); ?>','qtr':'<?php print $langs->transnoentities('Quadri'); ?>','hrs':'<?php print $langs->transnoentities('Hours'); ?>',
    'dys':'<?php print $langs->transnoentities('Days'); ?>','wks':'<?php print $langs->transnoentities('Weeks'); ?>','mths':'<?php print $langs->transnoentities('Months'); ?>','qtrs':'<?php print $langs->transnoentities('Quadri'); ?>','resource':'<?php print dol_escape_js($langs->transnoentities('Resource')); ?>','duration':'<?php print dol_escape_js($langs->transnoentities('Duration')); ?>','comp':'%',
    'completion':'<?php print $langs->transnoentities('Total'); ?>','startdate':'<?php print $langs->transnoentities('DateStart'); ?>','enddate':'<?php print $langs->transnoentities('DateEnd'); ?>','moreinfo':'<?php print dol_escape_js($langs->transnoentities('ShowTask')); ?>',
    'notes':'<?php print $langs->transnoentities('NotePublic'); ?>',
    'january':'<?php print $langs->transnoentities('January'); ?>','february':'<?php print $langs->transnoentities('February'); ?>','march':'<?php print $langs->transnoentities('March'); ?>','april':'<?php print $langs->transnoentities('April'); ?>','maylong':'<?php print $langs->transnoentities('May'); ?>','june':'<?php print $langs->transnoentities('June'); ?>','july':'<?php print $langs->transnoentities('July'); ?>',
    'august':'<?php print $langs->transnoentities('August'); ?>','september':'<?php print $langs->transnoentities('September'); ?>','october':'<?php print $langs->transnoentities('October'); ?>','november':'<?php print $langs->transnoentities('November'); ?>','december':'<?php print $langs->transnoentities('December'); ?>',
    'jan':'<?php print $langs->transnoentities('MonthShort01'); ?>','feb':'<?php print $langs->transnoentities('MonthShort02'); ?>','mar':'<?php print $langs->transnoentities('MonthShort03'); ?>','apr':'<?php print $langs->transnoentities('MonthShort04'); ?>','may':'<?php print $langs->transnoentities('MonthShort05'); ?>','jun':'<?php print $langs->transnoentities('MonthShort06'); ?>','jul':'<?php print $langs->transnoentities('MonthShort07'); ?>',
    'aug':'<?php print $langs->transnoentities('MonthShort08'); ?>','sep':'<?php print $langs->transnoentities('MonthShort09'); ?>','oct':'<?php print $langs->transnoentities('MonthShort10'); ?>','nov':'<?php print $langs->transnoentities('MonthShort11'); ?>','dec':'<?php print $langs->transnoentities('MonthShort12'); ?>',
    'sunday':'<?php print $langs->transnoentities('Sunday'); ?>','monday':'<?php print $langs->transnoentities('Monday'); ?>','tuesday':'<?php print $langs->transnoentities('Tuesday'); ?>','wednesday':'<?php print $langs->transnoentities('Wednesday'); ?>','thursday':'<?php print $langs->transnoentities('Thursday'); ?>','friday':'<?php print $langs->transnoentities('Friday'); ?>','saturday':'<?php print $langs->transnoentities('Saturday'); ?>',
    'sun':'<?php print $langs->transnoentities('SundayMin'); ?>','mon':'<?php print $langs->transnoentities('MondayMin'); ?>','tue':'<?php print $langs->transnoentities('TuesdayMin'); ?>','wed':'<?php print $langs->transnoentities('WednesdayMin'); ?>','thu':'<?php print $langs->transnoentities('ThursdayMin'); ?>','fri':'<?php print $langs->transnoentities('FridayMin'); ?>','sat':'<?php print $langs->transnoentities('SaturdayMin'); ?>'
    }
};
var vLang='<?php print $langs->getDefaultLang(1);?>';
<?php
if (is_object($db)) $db->close();
