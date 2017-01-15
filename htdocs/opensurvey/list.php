<?php
/* Copyright (C) 2013-2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Marcos García       <marcosgdf@gmail.com>
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
require_once(DOL_DOCUMENT_ROOT."/opensurvey/class/opensurveysondage.class.php");

// Security check
if (!$user->rights->opensurvey->read) accessforbidden();

$action=GETPOST('action');
$id=GETPOST('id','alpha');
$numsondage= $id;
$surveytitle=GETPOST('surveytitle');
$status=GETPOST('status');
//if (! isset($_POST['status']) && ! isset($_GET['status'])) $status='opened';	// If filter unknown, we choose 'opened'

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="p.date_fin";
if (! $sortorder) $sortorder="DESC";
if ($page < 0) {
	$page = 0;
}

$langs->load("opensurvey");

/*
 * Actions
 */

if (GETPOST('button_removefilter'))
{
	$status='';
	$surveytitle='';
}


/*
 * View
 */

$form=new Form($db);
$opensurvey_static = new Opensurveysondage($db);

$now = dol_now();

llxHeader();

$param='';
$fieldtosortuser=empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)?'firstname':'lastname';

print load_fiche_titre($langs->trans("OpenSurveyArea"));

// List of surveys into database

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.id_sondage",$param,"","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Title"), $_SERVER["PHP_SELF"], "p.titre",$param,"","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Type"));
print_liste_field_titre($langs->trans("Author"), $_SERVER["PHP_SELF"], "u.".$fieldtosortuser,$param,"","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("NbOfVoters"));
print_liste_field_titre($langs->trans("ExpireDate"), $_SERVER["PHP_SELF"], "p.date_fin",$param,"",'align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "p.status",$param,"",'align="center"',$sortfield,$sortorder);
print_liste_field_titre('');
print '</tr>'."\n";

print '<tr class="liste_titre">';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"><input type="text" class="maxwidth100onsmartphone" name="surveytitle" value="'.dol_escape_htmltag($surveytitle).'"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
$arraystatus=array(''=>'&nbsp;','expired'=>$langs->trans("Expired"),'opened'=>$langs->trans("Opened"));
print '<td class="liste_titre" align="center">'. $form->selectarray('status', $arraystatus, $status).'</td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre" align="right">';
$searchpitco=$form->showFilterAndCheckAddButtons(0);
print $searchpitco;
print '</td>';
print '</tr>'."\n";

$sql = "SELECT p.id_sondage, p.fk_user_creat, p.format, p.date_fin, p.status, p.titre, p.nom_admin,";
$sql.= " u.login, u.firstname, u.lastname";
$sql.= " FROM ".MAIN_DB_PREFIX."opensurvey_sondage as p";
$sql.= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."user u ON u.rowid = p.fk_user_creat";
// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}
$sql.= " WHERE p.entity = ".getEntity('survey',1);
if ($status == 'expired') $sql.=" AND date_fin < '".$db->idate($now)."'";
if ($status == 'opened') $sql.=" AND date_fin >= '".$db->idate($now)."'";
if ($surveytitle) $sql.=" AND titre LIKE '%".$db->escape($surveytitle)."%'";
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

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

	$opensurvey_static->id=$obj->id_sondage;
	$opensurvey_static->status=$obj->status;
	
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
		$userstatic->firstname = $obj->firstname;
		$userstatic->lastname = $obj->lastname;
		$userstatic->login = $userstatic->getFullName($langs, 0, -1, 48);

		print $userstatic->getLoginUrl(1);
	} else {
		print dol_htmlentities($obj->nom_admin);
	}

	print '</td>';

	print'<td align="center">'.$nbuser.'</td>'."\n";
	
	print '<td align="center">'.dol_print_date($db->jdate($obj->date_fin),'day');
	if ($db->jdate($obj->date_fin) < time()) { print ' ('.$langs->trans("Expired").')'; }
	print '</td>';

	print'<td align="center">'.$opensurvey_static->getLibStatut(5).'</td>'."\n";
	
	print'<td align="center"></td>'."\n";

	print '</tr>'."\n";
	$i++;
}

print '</table>'."\n";
print '</div>';
print '</form>';

llxFooter();

$db->close();
