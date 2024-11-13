<?php
/* Copyright (C) 2003-2007  Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014  Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio  	<sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier       	<benoit.mortier@opensides.be>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *   \file       htdocs/admin/system/database.php
 *   \brief      Page with system information of database
 */

// Load Dolibarr environment
require '../../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_db_pass
 */

$langs->load("admin");

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'convertutf8unicode') {			// Test on permission already done.
	$sql = "ALTER DATABASE ".$db->sanitize($db->database_name)." CHARACTER SET utf8 COLLATE utf8_unicode_ci";
	$db->query($sql);
}
if ($action == 'convertutf8mb4unicode') {		// Test on permission already done.
	$sql = "ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
	$db->query($sql);
}
if ($action == 'convertutf8general') {			// Test on permission already done.
	$sql = "ALTER DATABASE ".$db->sanitize($db->database_name)." CHARACTER SET utf8 COLLATE utf8_general_ci";
	$db->query($sql);
}
if ($action == 'convertutf8mb4general') {		// Test on permission already done.
	$sql = "ALTER DATABASE ".$db->sanitize($db->database_name)." CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
	$db->query($sql);
}


/*
 * View
 */

$form = new Form($db);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-system_database');

print load_fiche_titre($langs->trans("InfoDatabase"), '', 'title_setup');

// Database
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Database").'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("Version").'</td><td>'.$db::LABEL.' '.$db->getVersion().'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("DatabaseServer").'</td><td>'.$conf->db->host.'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("DatabasePort").'</td><td>'.(empty($conf->db->port) ? $langs->trans("Default") : $conf->db->port).'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("DatabaseName").'</td><td>'.$conf->db->name.'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("DriverType").'</td><td>'.$conf->db->type.($db->getDriverInfo() ? ' ('.$db->getDriverInfo().')' : '').'</td></tr>'."\n";
// @phan-suppress-next-line PhanTypeSuspiciousStringExpression  (user is defined in the stdClass)
print '<tr class="oddeven"><td width="300">'.$langs->trans("User").'</td><td>'.$conf->db->user.'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("Password").'</td><td>'.preg_replace('/./i', '*', $dolibarr_main_db_pass).'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("DBStoringCharset").'</td><td>'.$db->getDefaultCharacterSetDatabase();
if ($db->type == 'mysqli') {
	$tooltipexample = "<br>SHOW VARIABLES LIKE 'character_set_database' (cached)<br>You can avoid cache effect with:<br>SELECT DEFAULT_CHARACTER_SET_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '".$db->escape($conf->db->name)."'";
	print ' '.$form->textwithpicto('', $langs->transnoentitiesnoconv("HelpMariaDBToGetValue", $tooltipexample.'<br>'.$langs->transnoentitiesnoconv("HelpMariaDBToGetPossibleValues", "<br>SHOW CHARSET")));
	// We can use $db->getDefaultCharacterSetDatabase(),  $db->getListOfCharacterSet(),
}
print '</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("DBSortingCharset").'</td><td>';
$defaultcollation = $db->getDefaultCollationDatabase();
print dolPrintHTML($defaultcollation);
global $dolibarr_main_db_collation;
if ($db->type == 'mysqli') {
	if ($defaultcollation != $conf->db->dolibarr_main_db_collation) {
		print img_warning('The database default value of collation '.$defaultcollation.' differs from conf setup '.$conf->db->dolibarr_main_db_collation);
	}
	$tooltipexample = "<br>SHOW VARIABLES LIKE 'collation_database' (cached)<br>You can avoid cache effect with:<br>SELECT DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '".$db->escape($conf->db->name)."'";
	print ' '.$form->textwithpicto('', $langs->transnoentitiesnoconv("HelpMariaDBToGetValue", $tooltipexample.'<br>'.$langs->transnoentitiesnoconv("HelpMariaDBToGetPossibleValues", "<br>SHOW COLLATION")));
	// We can use $db->getDefaultCollationDatabase(), $db->getListOfCollation();

	print ' &nbsp; &nbsp; &nbsp; <span class="opacitymedium small">'.$langs->trans("ConvertInto");
	if (!in_array($defaultcollation, array("utf8_unicode_ci"))) {
		print ' &nbsp; <a class="reposition" href="'.DOL_URL_ROOT.'/admin/system/database.php?action=convertutf8unicode&token='.newToken().'">utf8 unicode</a>';
	}
	if (!in_array($defaultcollation, array("utf8_general_ci"))) {
		print ' &nbsp;  <a class="reposition" href="'.DOL_URL_ROOT.'/admin/system/database.php?action=convertutf8general&token='.newToken().'">utf8 general</a>';
	}
	if (!in_array($defaultcollation, array("utf8mb4_unicode_ci"))) {
		print ' &nbsp; <a class="reposition" href="'.DOL_URL_ROOT.'/admin/system/database.php?action=convertutf8mb4unicode&&token='.newToken().'">utf8mb4 unicode</a>';
	}
	if (!in_array($defaultcollation, array("utf8mb4_general_ci"))) {
		print ' &nbsp; <a class="reposition" href="'.DOL_URL_ROOT.'/admin/system/database.php?action=convertutf8mb4general&&token='.newToken().'">utf8mb4 general</a>';
	}
}
print '</td></tr>'."\n";
print '</table>';
print '</div>';

// Tables
print '<br>';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Tables").'</td></tr>'."\n";
print '<tr class="oddeven"><td class=""><a href="'.DOL_URL_ROOT.'/admin/system/database-tables.php?mainmenu=home">'.img_picto('', 'list', 'class="pictofixedwidth"').$langs->trans("List").'</a></td></tr>'."\n";
print '</table>';
print '</div>';

$listofvars = $db->getServerParametersValues();
$listofstatus = $db->getServerStatusValues();
$arraylist = array('listofvars', 'listofstatus');

if (!count($listofvars) && !count($listofstatus)) {
	print $langs->trans("FeatureNotAvailableWithThisDatabaseDriver");
} else {
	foreach ($arraylist as $listname) {
		print '<br>';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td width="300">'.$langs->trans("Parameters").'</td>';
		print '<td></td>';
		print '</tr>'."\n";

		// arraytest is an array of test to do
		$arraytest = array();
		if (preg_match('/mysql/i', $db->type)) {
			$arraytest = array(
				'character_set_database' => array('var' => 'dolibarr_main_db_character_set', 'valifempty' => 'utf8'),
				'collation_database' => array('var' => 'dolibarr_main_db_collation', 'valifempty' => 'utf8_unicode_ci')
			);
		}

		$listtouse = array();
		if ($listname == 'listofvars') {
			$listtouse = $listofvars;
		}
		if ($listname == 'listofstatus') {
			$listtouse = $listofstatus;
		}

		foreach ($listtouse as $param => $paramval) {
			print '<tr class="oddeven">';
			print '<td>';
			print $param;
			print '</td>';
			print '<td class="wordbreak">';
			$show = 0;
			$text = '';
			foreach ($arraytest as $key => $val) {
				if ($key != $param) {
					continue;
				}
				$tmpvar = $val['var'];
				$val2 = ${$tmpvar};
				$text = 'Should be in line with value of param <b>'.$val['var'].'</b> thas is <b>'.($val2 ? $val2 : "'' (=".$val['valifempty'].")").'</b>';
				$show = 1;
			}
			if ($show == 0) {
				print $paramval;
			}
			if ($show == 1) {
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				print $form->textwithpicto($paramval, $text);
			}
			if ($show == 2) {
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				print $form->textwithpicto($paramval, $text, 1, 'warning');
			}
			print '</td>';
			print '</tr>'."\n";
		}
		print '</table>'."\n";
		print '</div>';
	}
}

// End of page
llxFooter();
$db->close();
