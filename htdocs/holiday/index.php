<?php
/* Copyright (C) 2011	Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 *   	\file       htdocs/holiday/index.php
 *		\ingroup    holiday
 *		\brief      List of holiday.
 */

require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/common.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

$langs->load('users');
$langs->load('holidays');

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

if (! $sortfield) $sortfield="cp.rowid";
if (! $sortorder) $sortorder="DESC";
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$id = GETPOST('id');

$search_ref      = GETPOST('search_ref');
$month_create    = GETPOST('month_create');
$year_create     = GETPOST('year_create');
$month_start     = GETPOST('month_start');
$year_start      = GETPOST('year_start');
$month_end       = GETPOST('month_end');
$year_end        = GETPOST('year_end');
$search_employe  = GETPOST('search_employe');
$search_valideur = GETPOST('search_valideur');
$search_statut   = GETPOST('select_statut');


/*
 * Actions
 */

// None



/*
 * View
 */

$holiday = new Holiday($db);
$holidaystatic=new Holiday($db);
$fuser = new User($db);

// Update sold
$holiday->updateSold();

$max_year = 5;
$min_year = 10;
$filter='';

llxHeader(array(),$langs->trans('CPTitreMenu'));

$order = $db->order($sortfield,$sortorder).$db->plimit($conf->liste_limit + 1, $offset);

// WHERE
if(!empty($search_ref))
{
    $filter.= " AND cp.rowid LIKE '%".$db->escape($search_ref)."%'\n";
}

// DATE START
if($year_start > 0) {
    if($month_start > 0) {
    	$filter .= " AND (cp.date_debut BETWEEN '".$db->idate(dol_get_first_day($year_start,$month_start,1))."' AND '".$db->idate(dol_get_last_day($year_start,$month_start,1))."')";
    	//$filter.= " AND date_format(cp.date_debut, '%Y-%m') = '$year_start-$month_start'";
    } else {
    	$filter .= " AND (cp.date_debut BETWEEN '".$db->idate(dol_get_first_day($year_start,1,1))."' AND '".$db->idate(dol_get_last_day($year_start,12,1))."')";
    	//$filter.= " AND date_format(cp.date_debut, '%Y') = '$year_start'";
    }
} else {
    if($month_start > 0) {
        $filter.= " AND date_format(cp.date_debut, '%m') = '$month_start'";
    }
}

// DATE FIN
if($year_end > 0) {
    if($month_end > 0) {
    	$filter .= " AND (cp.date_fin BETWEEN '".$db->idate(dol_get_first_day($year_end,$month_end,1))."' AND '".$db->idate(dol_get_last_day($year_end,$month_end,1))."')";
    	//$filter.= " AND date_format(cp.date_fin, '%Y-%m') = '$year_end-$month_end'";
    } else {
    	$filter .= " AND (cp.date_fin BETWEEN '".$db->idate(dol_get_first_day($year_end,1,1))."' AND '".$db->idate(dol_get_last_day($year_end,12,1))."')";
    	//$filter.= " AND date_format(cp.date_fin, '%Y') = '$year_end'";
    }
} else {
    if($month_end > 0) {
        $filter.= " AND date_format(cp.date_fin, '%m') = '$month_end'";
    }
}

// DATE CREATE
if($year_create > 0) {
    if($month_create > 0) {
    	$filter .= " AND (cp.date_create BETWEEN '".$db->idate(dol_get_first_day($year_create,$month_create,1))."' AND '".$db->idate(dol_get_last_day($year_create,$month_create,1))."')";
    	//$filter.= " AND date_format(cp.date_create, '%Y-%m') = '$year_create-$month_create'";
    } else {
    	$filter .= " AND (cp.date_create BETWEEN '".$db->idate(dol_get_first_day($year_create,1,1))."' AND '".$db->idate(dol_get_last_day($year_create,12,1))."')";
    	//$filter.= " AND date_format(cp.date_create, '%Y') = '$year_create'";
    }
} else {
    if($month_create > 0) {
        $filter.= " AND date_format(cp.date_create, '%m') = '$month_create'";
    }
}

// EMPLOYE
if(!empty($search_employe) && $search_employe != -1) {
    $filter.= " AND cp.fk_user = '".$db->escape($search_employe)."'\n";
}

// VALIDEUR
if(!empty($search_valideur) && $search_valideur != -1) {
    $filter.= " AND cp.fk_validator = '".$db->escape($search_valideur)."'\n";
}

// STATUT
if(!empty($search_statut) && $search_statut != -1) {
    $filter.= " AND cp.statut = '".$db->escape($search_statut)."'\n";
}

/*************************************
 * Fin des filtres de recherche
*************************************/

// Récupération de l'ID de l'utilisateur
$user_id = $user->id;

if ($id > 0)
{
	// Charge utilisateur edite
	$fuser->fetch($id);
	$fuser->getrights();
	$user_id = $fuser->id;
}
// Récupération des congés payés de l'utilisateur ou de tous les users
if (!$user->rights->holiday->lire_tous || $id > 0)
{
	$holiday_payes = $holiday->fetchByUser($user_id,$order,$filter);
}
else
{
    $holiday_payes = $holiday->fetchAll($order,$filter);
}
// Si erreur SQL
if ($holiday_payes == '-1')
{
    print_fiche_titre($langs->trans('CPTitreMenu'));

    print '<div class="tabBar">';
    print '<span>'.$langs->trans('CPErrorSQL');
    print ' '.$holiday->error.'</span>';
    print '</div>';
    exit();
}

/*************************************
 * Affichage du tableau des congés payés
*************************************/

$var=true; $num = count($holiday->holiday);
$form = new Form($db);
$formother = new FormOther($db);

if ($id > 0)
{
	$head = user_prepare_head($fuser);

	$title = $langs->trans("User");
	dol_fiche_head($head, 'paidholidays', $title, 0, 'user');

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
	print '<td colspan="2">';
	print $form->showrefnav($fuser,'id','',$user->rights->user->user->lire || $user->admin);
	print '</td>';
	print '</tr>';

	// LastName
	print '<tr><td width="25%" valign="top">'.$langs->trans("LastName").'</td>';
	print '<td colspan="2">'.$fuser->lastname.'</td>';
	print "</tr>\n";

	// FirstName
	print '<tr><td width="25%" valign="top">'.$langs->trans("FirstName").'</td>';
	print '<td colspan="2">'.$fuser->firstname.'</td>';
	print "</tr>\n";

	print '</table><br>';
}
else
{
	print_barre_liste($langs->trans("ListeCP"), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, "", $num);

	print '<div class="tabBar">';
}


$nbaquis=$holiday->getCPforUser($user_id);
$nbdeduced=$holiday->getConfCP('nbHolidayDeducted');
$nb_holiday = $nbaquis / $nbdeduced;
print $langs->trans('SoldeCPUser',round($nb_holiday,2)).($nbdeduced != 1 ? ' ('.$nbaquis.' / '.$nbdeduced.')' : '');

if ($id > 0)
{
	dol_fiche_end();
	print '</br>';
}
else {
	print '</div>';
} 

print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<table class="noborder" width="100%;">';
print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"cp.rowid","",'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DateCreateCP"),$_SERVER["PHP_SELF"],"cp.date_create","",'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Employe"),$_SERVER["PHP_SELF"],"cp.fk_user","",'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("ValidatorCP"),$_SERVER["PHP_SELF"],"cp.fk_validator","",'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DateDebCP"),$_SERVER["PHP_SELF"],"cp.date_debut","",'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DateFinCP"),$_SERVER["PHP_SELF"],"cp.date_fin","",'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Duration"));
print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"cp.statut","",'','align="center"',$sortfield,$sortorder);
print '<td></td>';
print "</tr>\n";

// FILTRES
print '<tr class="liste_titre">';
print '<td class="liste_titre" align="left" width="50">';
print '<input class="flat" size="4" type="text" name="search_ref" value="'.$search_ref.'">';
print '</td>';

// DATE CREATE
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_create" value="'.$month_create.'">';
$formother->select_year($year_create,'year_create',1, $min_year, $max_year);
print '</td>';

// UTILISATEUR
if($user->rights->holiday->lire_tous) {
    print '<td class="liste_titre" align="left">';
    $form->select_users($search_employe,"search_employe",1,"",0,'');
    print '</td>';
} else {
    print '<td class="liste_titre">&nbsp;</td>';
}

// VALIDEUR
if($user->rights->holiday->lire_tous)
{
    print '<td class="liste_titre" align="left">';

    $validator = new UserGroup($db);
    $excludefilter=$user->admin?'':'u.rowid <> '.$user->id;
    $valideurobjects = $validator->listUsersForGroup($excludefilter);
    $valideurarray = array();
    foreach($valideurobjects as $val) $valideurarray[$val->id]=$val->id;
    $form->select_users($search_valideur,"search_valideur",1,"",0,$valideurarray,'');
    print '</td>';
}
else 
{
    print '<td class="liste_titre">&nbsp;</td>';
}

// DATE DEBUT
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_start" value="'.$month_start.'">';
$formother->select_year($year_start,'year_start',1, $min_year, $max_year);
print '</td>';

// DATE FIN
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
$formother->select_year($year_end,'year_end',1, $min_year, $max_year);
print '</td>';

// DUREE
print '<td>&nbsp;</td>';

// STATUT
print '<td class="liste_titre" width="70px;" align="center">';
$holiday->selectStatutCP($search_statut);
print '</td>';

// ACTION
print '<td align="right">';
print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans('Search').'">';
print '</td>';

print "</tr>\n";


// Lines
if (! empty($holiday->holiday))
{
	$userstatic = new User($db);
	$approbatorstatic = new User($db);

	foreach($holiday->holiday as $infos_CP)
	{
		$var=!$var;

		// Utilisateur
		$userstatic->id=$infos_CP['fk_user'];
		$userstatic->lastname=$infos_CP['user_lastname'];
		$userstatic->firstname=$infos_CP['user_firstname'];

		// Valideur
		$approbatorstatic->id=$infos_CP['fk_validator'];
		$approbatorstatic->lastname=$infos_CP['validator_lastname'];
		$approbatorstatic->firstname=$infos_CP['validator_firstname'];

		$date = $infos_CP['date_create'];

		print '<tr '.$bc[$var].'>';
		print '<td>';
		$holidaystatic->id=$infos_CP['rowid'];
		$holidaystatic->ref=$infos_CP['rowid'];
		print $holidaystatic->getNomUrl(1);
		print '</td>';
		print '<td style="text-align: center;">'.dol_print_date($date,'day').'</td>';
		print '<td>'.$userstatic->getNomUrl('1').'</td>';
		print '<td>'.$approbatorstatic->getNomUrl('1').'</td>';
		print '<td align="center">'.dol_print_date($infos_CP['date_debut'],'day').'</td>';
		print '<td align="center">'.dol_print_date($infos_CP['date_fin'],'day').'</td>';
		print '<td align="right">';
		$nbopenedday=num_open_day($infos_CP['date_debut'], $infos_CP['date_fin'], 0, 1, $infos_CP['halfday']);
		print $nbopenedday.' '.$langs->trans('DurationDays');
		print '<td align="right" colspan="2">'.$holidaystatic->LibStatut($infos_CP['statut'],5).'</td>';
		print '</tr>'."\n";

	}
}

// Si il n'y a pas d'enregistrement suite à une recherche
if($holiday_payes == '2')
{
    print '<tr>';
    print '<td colspan="9" class="pair" style="text-align: center; padding: 5px;">'.$langs->trans('None').'</td>';
    print '</tr>';
}

print '</table>';
print '</form>';

if ($user_id == $user->id)
{
	print '<br>';
	print '<div style="float: right; margin-top: 8px;">';
	print '<a href="./fiche.php?action=request" class="butAction">'.$langs->trans('AddCP').'</a>';
	print '</div>';
}

llxFooter();

$db->close();
?>
