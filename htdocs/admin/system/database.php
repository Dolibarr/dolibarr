<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 *   \file       htdocs/admin/system/database.php
 *   \brief      Page with system information of database
 */

require '../../main.inc.php';

$langs->load("admin");

if (!$user->admin) accessforbidden();



/*
 * View
 */

$form=new Form($db);

llxHeader();

print load_fiche_titre($langs->trans("InfoDatabase"),'','title_setup');

// Database
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Database").'</td></tr>'."\n";
print '<tr '.$bc[0].'><td width="300">'.$langs->trans("Version").'</td><td>'.$db::LABEL.' '.$db->getVersion().'</td></tr>'."\n";
print '<tr '.$bc[1].'><td width="300">'.$langs->trans("DatabaseServer").'</td><td>'.$conf->db->host.'</td></tr>'."\n";
print '<tr '.$bc[0].'><td width="300">'.$langs->trans("DatabasePort").'</td><td>'.(empty($conf->db->port)?$langs->trans("Default"):$conf->db->port).'</td></tr>'."\n";
print '<tr '.$bc[1].'><td width="300">'.$langs->trans("DatabaseName").'</td><td>'.$conf->db->name.'</td></tr>'."\n";
print '<tr '.$bc[0].'><td width="300">'.$langs->trans("DriverType").'</td><td>'.$conf->db->type . ($db->getDriverInfo() ? ' ('.$db->getDriverInfo().')':'').'</td></tr>'."\n";
print '<tr '.$bc[1].'><td width="300">'.$langs->trans("User").'</td><td>'.$conf->db->user.'</td></tr>'."\n";
print '<tr '.$bc[0].'><td width="300">'.$langs->trans("Password").'</td><td>'.preg_replace('/./i','*',$dolibarr_main_db_pass).'</td></tr>'."\n";
print '<tr '.$bc[1].'><td width="300">'.$langs->trans("DBStoringCharset").'</td><td>'.$db->getDefaultCharacterSetDatabase().'</td></tr>'."\n";
print '<tr '.$bc[0].'><td width="300">'.$langs->trans("DBSortingCharset").'</td><td>'.$db->getDefaultCollationDatabase().'</td></tr>'."\n";
print '</table>';

// Tables
print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Tables").'</td></tr>'."\n";
print '<tr '.$bc[0].'><td width="300"><a href="'.DOL_URL_ROOT.'/admin/system/database-tables.php?mainmenu=home">'.$langs->trans("List").'</a></td></tr>'."\n";
print '</table>';


$listofvars=$db->getServerParametersValues();
$listofstatus=$db->getServerStatusValues();
$arraylist=array('listofvars','listofstatus');

if (! count($listofvars) && ! count($listofstatus))
{
	print $langs->trans("FeatureNotAvailableWithThisDatabaseDriver");
}
else
{
	foreach($arraylist as $listname)
	{
		print '<br>';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td width="300">'.$langs->trans("Parameters").'</td>';
		print '<td>'.$langs->trans("Value").'</td>';
		print '</tr>'."\n";

		// arraytest is an array of test to do
		$arraytest=array();
		if (preg_match('/mysql/i',$db->type))
		{
			$arraytest=array(
				'character_set_database'=>array('var'=>'dolibarr_main_db_character_set','valifempty'=>'utf8'),
				'collation_database'=>array('var'=>'dolibarr_main_db_collation','valifempty'=>'utf8_general_ci')
			);
		}

		$listtouse=array();
		if ($listname == 'listofvars') $listtouse=$listofvars;
		if ($listname == 'listofstatus') $listtouse=$listofstatus;

		$var=true;
		foreach($listtouse as $param => $paramval)
		{
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td>';
			print $param;
			print '</td>';
			print '<td>';
			$show=0;$text='';
			foreach($arraytest as $key => $val)
			{
				if ($key != $param) continue;
				$val2=${$val['var']};
				$text='Should be in line with value of param <b>'.$val['var'].'</b> thas is <b>'.($val2?$val2:"'' (=".$val['valifempty'].")").'</b>';
				$show=1;
			}
			if ($show==0) print $paramval;
			if ($show==1) print $form->textwithpicto($paramval,$text);
			if ($show==2) print $form->textwithpicto($paramval,$text,1,'warning');
			print '</td>';
			print '</tr>'."\n";
		}
		print '</table>'."\n";
	}
}

llxFooter();

$db->close();
