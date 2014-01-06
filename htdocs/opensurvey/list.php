<?php
/* Copyright (C) 2013      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Marcos García				<marcosgdf@gmail.com>
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
 *	\file       htdocs/opensurvey/list.php
 *	\ingroup    opensurvey
 *	\brief      Page to list surveys
 */

require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

// Security check
if (!$user->rights->opensurvey->read) accessforbidden();

$action=GETPOST('action');
$id=GETPOST('id');
$numsondage= $id;

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="p.titre";
if ($page < 0) {
	$page = 0;
}
$limit = $conf->liste_limit;
$offset = $limit * $page;


/*
 * View
 */

$form=new Form($db);

$langs->load("opensurvey");
llxHeader();

print '<div class=corps>'."\n";

print_fiche_titre($langs->trans("OpenSurveyArea"));

// tableau qui affiche tous les sondages de la base
print '<table class="liste">'."\n";
print '<tr class="liste_titre"><td>'. $langs->trans("Ref").'</td><td>'. $langs->trans("Title") .'</td><td>'. $langs->trans("Type") .'</td><td>'. $langs->trans("Author") .'</td><td align="center">'. $langs->trans("ExpireDate") .'</td><td align="center">'. $langs->trans("NbOfVoters") .'</td>'."\n";

$sql = "SELECT id_sondage, fk_user_creat, u.login, format, date_fin, titre, nom_admin";
$sql.= " FROM ".MAIN_DB_PREFIX."opensurvey_sondage as p";
$sql.= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = p.fk_user_creat";
// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}
$sql.= " ORDER BY $sortfield $sortorder ";
$sql.= " ".$db->plimit($conf->liste_limit+1, $offset);

$resql=$db->query($sql);
if (! $resql) dol_print_error($db);

$num=$db->num_rows($resql);

$i = 0; $var = true;
while ($i < min($num,$limit))
{
	$obj=$db->fetch_object($resql);

	$sql2='select COUNT(*) as nb from '.MAIN_DB_PREFIX."opensurvey_user_studs where id_sondage='".$db->escape($obj->id_sondage)."'";
	$resql2=$db->query($sql2);
	if ($resql2)
	{
		$obj2=$db->fetch_object($resql2);
		$nbuser=$obj2->nb;
	}
	else dol_print_error($db);

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>';
	print '<a href="'.dol_buildpath('/opensurvey/card.php',1).'?id='.$obj->id_sondage.'">'.img_picto('','object_opensurvey').' '.$obj->id_sondage.'</a>';
	print '</td><td>'.dol_htmlentities($obj->titre).'</td><td>';
	$type=($obj->format=='A')?'classic':'date';
	print img_picto('',dol_buildpath('/opensurvey/img/'.($type == 'classic'?'chart-32.png':'calendar-32.png'),1),'width="16"',1);
	print ' '.$langs->trans($type=='classic'?"TypeClassic":"TypeDate");
	print '</td><td>';
	
	// Author
	if ($obj->fk_user_creat) {
		$userstatic = new User($db);
		$userstatic->id = $obj->fk_user_creat;
		$userstatic->login = $obj->login;
		
		print $userstatic->getLoginUrl(1);
	} else {
		print dol_htmlentities($obj->nom_admin);
	}
	
	print '</td>';

	print '<td align="center">'.dol_print_date($db->jdate($obj->date_fin),'day');
	if ($db->jdate($obj->date_fin) < time()) { print ' '.img_warning(); }
	print '</td>';

	print'<td align="center">'.$nbuser.'</td>'."\n";

	print '</tr>'."\n";
	$i++;
}

print '</table>'."\n";
print '</div>'."\n";

llxFooter();

$db->close();
?>