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
 *	\file       htdocs/opensurvey/list.php
 *	\ingroup    opensurvey
 *	\brief      Page to list surveys
 */

require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

$action=GETPOST('action');
$id=GETPOST('id');
$numsondage=substr($id, 0, 16);

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="p.titre";
if ($page < 0) {
	$page = 0;
}
$limit = $conf->liste_limit;
$offset = $limit * $page;


/*
 * Actions
 */

if ($action == 'delete_confirm')
{
	$db->begin();

	$object=new Opensurveysondage($db);
	
	$result=$object->delete($user,'',$numsondageadmin);
	
	$db->commit();
}



/*
 * View
 */

$form=new Form($db);

$langs->load("opensurvey");
llxHeader();

print '<div class=corps>'."\n";

print_fiche_titre($langs->trans("OpenSurveyArea"));


if ($action == 'delete')
{
	print $form->formconfirm($_SERVER["PHP_SELF"].'?&id='.$id, $langs->trans("RemovePoll"), $langs->trans("ConfirmRemovalOfPoll",$id), 'delete_confirm', '', '', 1);
}


// tableau qui affiche tous les sondages de la base
print '<table class="liste">'."\n";
print '<tr class="liste_titre"><td>'. $langs->trans("Survey").'</td><td>'. $langs->trans("Type") .'</td><td>'. $langs->trans("Title") .'</td><td>'. $langs->trans("Author") .'</td><td align="center">'. $langs->trans("ExpireDate") .'</td><td align="center">'. $langs->trans("NbOfVoters") .'</td><td colspan=2>&nbsp;</td>'."\n";

$sql = "SELECT id_sondage, id_sondage_admin, mail_admin, format, origin, date_fin, titre, nom_admin";
$sql.= " FROM ".MAIN_DB_PREFIX."opensurvey_sondage as p";
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
	print '<a href="'.dol_buildpath('/opensurvey/adminstuds.php',1).'?sondage='.$obj->id_sondage_admin.'">'.img_picto('','object_opensurvey').' '.$obj->id_sondage.'</a>';
	print '</td><td>';
	$type=($obj->format=='A' || $obj->format=='A+')?'classic':'date';
	print img_picto('',dol_buildpath('/opensurvey/img/'.($type == 'classic'?'chart-32.png':'calendar-32.png'),1),'width="16"',1);
	print ' '.$langs->trans($type=='classic'?"TypeClassic":"TypeDate");
	print '</td><td>'.$obj->titre.'</td><td>'.$obj->nom_admin.'</td>';

	print '<td align="center">'.dol_print_date($db->jdate($obj->date_fin),'day');
	if ($db->jdate($obj->date_fin) < time()) { print ' '.img_warning(); }
	print '</td>';

	print'<td align="center">'.$nbuser.'</td>'."\n";
	print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?id='.$obj->id_sondage_admin.'&action=delete">'.img_picto('', 'delete.png').'</a></td>'."\n";

	print '</tr>'."\n";
	$i++;
}

print '</table>'."\n";
print '</div>'."\n";

llxFooter();

$db->close();
?>