<?php
/* Copyright (C) 2011	Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012	Regis Houssin		<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *   	\file       index.php
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

$holiday = new Holiday($db);

/*
 * Actions
 */

// None



/*
 * View
 */

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
        $filter.= " AND date_format(cp.date_debut, '%Y-%m') = '$year_start-$month_start'";
    } else {
        $filter.= " AND date_format(cp.date_debut, '%Y') = '$year_start'";
    }
} else {
    if($month_start > 0) {
        $filter.= " AND date_format(cp.date_debut, '%m') = '$month_start'";
    }
}

// DATE FIN
if($year_end > 0) {
    if($month_end > 0) {
        $filter.= " AND date_format(cp.date_fin, '%Y-%m') = '$year_end-$month_end'";
    } else {
        $filter.= " AND date_format(cp.date_fin, '%Y') = '$year_end'";
    }
} else {
    if($month_end > 0) {
        $filter.= " AND date_format(cp.date_fin, '%m') = '$month_end'";
    }
}

// DATE CREATE
if($year_create > 0) {
    if($month_create > 0) {
        $filter.= " AND date_format(cp.date_create, '%Y-%m') = '$year_create-$month_create'";
    } else {
        $filter.= " AND date_format(cp.date_create, '%Y') = '$year_create'";
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

// Récupération des congés payés de l'utilisateur ou de tous les users
if (!$user->rights->holiday->lire_tous)
{
    $holiday_payes = $holiday->fetchByUser($user_id,$order,$filter);
}
else
{
    $holiday_payes = $holiday->fetchAll($order,$filter);
}

// Si pas de congés payés
if ($holiday_payes == 0)
{
    print_fiche_titre($langs->trans('CPTitreMenu'));

    print '<div class="tabBar">';
    print '<span>'.$langs->trans('NoCPforUser').'<br /><br />';
    print '<a href="./fiche.php?mainmenu=agenda&action=request" class="butAction">'.$langs->trans('AddCP').'</a></span>';
    print '</div>';
    exit();
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
$html = new Form($db);
$htmlother = new FormOther($db);
print_barre_liste($langs->trans("ListeCP"), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, "", $num);

print '<div class="tabBar">';

$nbaquis=$holiday->getCPforUser($user->id);
$nbdeduced=$holiday->getConfCP('nbHolidayDeducted');
$nb_holiday = $nbaquis / $nbdeduced;
print $langs->trans('SoldeCPUser',round($nb_holiday,2)).($nbdeduced != 1 ? ' ('.$nbaquis.' / '.$nbdeduced.')' : '');
print '</div>';

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
print_liste_field_titre($langs->trans("Statut"),$_SERVER["PHP_SELF"],"cp.statut","",'','align="center"',$sortfield,$sortorder);
print "</tr>\n";

// FILTRES
print '<tr class="liste_titre">';
print '<td class="liste_titre" align="left" width="50">';
print '<input class="flat" size="4" type="text" name="search_ref" value="'.$search_ref.'">';

// DATE CREATE
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_create" value="'.$month_create.'">';
$htmlother->select_year($year_create,'year_create',1, $min_year, $max_year);
print '</td>';

// UTILISATEUR
if($user->rights->holiday->lire_tous) {
    print '<td class="liste_titre" align="left">';
    $html->select_users($search_employe,"search_employe",1,"",0,'');
    print '</td>';
} else {
    print '<td class="liste_titre">&nbsp;</td>';
}

// VALIDEUR
if($user->rights->holiday->lire_tous){
    print '<td class="liste_titre" align="left">';

    // Liste des utiliseurs du groupes Comptabilité

    $idGroupValid = $holiday->getConfCP('userGroup');

    $validator = new UserGroup($db,$idGroupValid);
    $valideur = $validator->listUsersForGroup();

    $html->select_users($search_valideur,"search_valideur",1,"",0,$valideur,'');
    print '</td>';
} else {
    print '<td class="liste_titre">&nbsp;</td>';
}

// DATE DEBUT
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_start" value="'.$month_start.'">';
$htmlother->select_year($year_start,'year_start',1, $min_year, $max_year);
print '</td>';

// DATE FIN
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
$htmlother->select_year($year_end,'year_end',1, $min_year, $max_year);
print '</td>';

// DUREE
print '<td>&nbsp;</td>';

// STATUT
print '<td class="liste_titre" width="70px;" align="center">';
$holiday->selectStatutCP($search_statut);
print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans('Search').'">';
print "</td></tr>\n";

if (! empty($holiday->holiday))
{
	foreach($holiday->holiday as $infos_CP)
	{
		$var=!$var;

		// Utilisateur
		$user = new User($db);
		$user->fetch($infos_CP['fk_user']);

		// Valideur
		$validator = new User($db);
		$validator->fetch($infos_CP['fk_validator']);

		$date = $infos_CP['date_create'];

		$statut = $holiday->getStatutCP($infos_CP['statut']);

		print '<tr '.$bc[$var].'>';
		print '<td><a href="./fiche.php?id='.$infos_CP['rowid'].'">CP '.$infos_CP['rowid'].'</a></td>';
		print '<td style="text-align: center;">'.dol_print_date($date,'day').'</td>';
		print '<td>'.$user->getNomUrl('1').'</td>';
		print '<td>'.$validator->getNomUrl('1').'</td>';
		print '<td style="text-align: center;">'.dol_print_date($infos_CP['date_debut'],'day').'</td>';
		print '<td style="text-align: center;">'.dol_print_date($infos_CP['date_fin'],'day').'</td>';
		print '<td>';
		$nbopenedday=num_open_day($infos_CP['date_debut'],$infos_CP['date_fin'],0,1);
		print $nbopenedday;
		print '<td align="center"><a href="./fiche.php?id='.$infos_CP['rowid'].'">'.$statut.'</a></td>';
		print '</tr>'."\n";

	}
}

// Si il n'y a pas d'enregistrement suite à une recherche
if($holiday_payes == '2')
{
    print '<tr>';
    print '<td colspan="8" class="pair" style="text-align: center; padding: 5px;">'.$langs->trans('None').'</td>';
    print '</tr>';
}

print '</table>';
print '</form>';

print '<br>';
print '<div style="float: right; margin-top: 8px;">';
print '<a href="./fiche.php?action=request" class="butAction">'.$langs->trans('AddCP').'</a>';
print '</div>';

llxFooter();

$db->close();
?>
