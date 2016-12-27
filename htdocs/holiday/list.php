<?php
/* Copyright (C) 2011	   Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2013-2016 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016 Regis Houssin		<regis.houssin@capnetworks.com>
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
 *   	\file       htdocs/holiday/list.php
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
$langs->load('hrm');

// Protection if external user
if ($user->societe_id > 0) accessforbidden();

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

if (! $sortfield) $sortfield="cp.rowid";
if (! $sortorder) $sortorder="DESC";
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$id = GETPOST('id','int');

$sall            = GETPOST('sall');
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
$type            = GETPOST('type','int'); 

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'cp.rowid'=>'Ref',
    'cp.description'=>'Description',
    'uu.lastname'=>'EmployeeLastname',
    'uu.firstname'=>'EmployeeFirstname'
);



/*
 * Actions
 */

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_ref="";
	$month_create="";
	$year_create="";
    $month_start="";
	$year_start="";
	$month_end="";
	$year_end="";
	$search_employe="";
	$search_valideur="";
	$search_statut="";
	$type='';
}



/*
 * View
 */

$holiday = new Holiday($db);
$holidaystatic=new Holiday($db);
$fuser = new User($db);

$childids = $user->getAllChildIds();
$childids[]=$user->id;

// Update sold
$result = $holiday->updateBalance();

$max_year = 5;
$min_year = 10;
$filter='';

llxHeader('', $langs->trans('CPTitreMenu'));

$order = $db->order($sortfield,$sortorder).$db->plimit($limit + 1, $offset);

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
// Search all
if (!empty($sall))
{
	$filter.= natural_search(array_keys($fieldstosearchall), $sall);
}

if (empty($user->rights->holiday->read_all)) $filter.=' AND cp.fk_user IN ('.join(',',$childids).')';

if ($type) $filter.=' AND cp.fk_type IN ('.$type.')';

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
if (empty($user->rights->holiday->read_all) || $id > 0)
{
	$holiday_payes = $holiday->fetchByUser($user_id,$order,$filter);	// Load array $holiday->holiday
}
else
{
    $holiday_payes = $holiday->fetchAll($order,$filter);	// Load array $holiday->holiday
}
// Si erreur SQL
if ($holiday_payes == '-1')
{
    print load_fiche_titre($langs->trans('CPTitreMenu'), '', 'title_hrm.png');

    dol_print_error($db, $langs->trans('Error').' '.$holiday->error);
    exit();
}


// Show table of vacations

$var=true;
$num = count($holiday->holiday);
$form = new Form($db);
$formother = new FormOther($db);

if ($id > 0)
{
	$title = $langs->trans("User");
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/index.php">'.$langs->trans("BackToList").'</a>';
	$head = user_prepare_head($fuser);

	dol_fiche_head($head, 'paidholidays', $title, 0, 'user');

    dol_banner_tab($fuser,'id',$linkback,$user->rights->user->user->lire || $user->admin);


    print '<div class="underbanner clearboth"></div>';

    print '<br>';

}
else
{
    //print $num;
    //print count($holiday->holiday);
	print_barre_liste($langs->trans("ListeCP"), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, "", $num, count($holiday->holiday), 'title_hrm.png', 0, '', '', $limit);

	dol_fiche_head('');
}

$alltypeleaves=$holiday->getTypes(1,-1);    // To have labels

$out='';
$typeleaves=$holiday->getTypes(1,1);
foreach($typeleaves as $key => $val)
{
	$nb_type = $holiday->getCPforUser($user_id, $val['rowid']);
	$nb_holiday += $nb_type;
	$out .= ' - '.$val['label'].': <strong>'.($nb_type?price2num($nb_type):0).'</strong><br>';
}
print $langs->trans('SoldeCPUser', round($nb_holiday,5)).'<br>';
print $out;

dol_fiche_end();


if ($id > 0) print '</br>';


print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

if ($sall)
{
    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
    print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"cp.rowid","",'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DateCreateCP"),$_SERVER["PHP_SELF"],"cp.date_create","",'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Employee"),$_SERVER["PHP_SELF"],"cp.fk_user","",'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("ValidatorCP"),$_SERVER["PHP_SELF"],"cp.fk_validator","",'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],'','','','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Duration"),$_SERVER["PHP_SELF"],'','','','align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DateDebCP"),$_SERVER["PHP_SELF"],"cp.date_debut","",'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DateFinCP"),$_SERVER["PHP_SELF"],"cp.date_fin","",'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"cp.statut","",'','align="right"',$sortfield,$sortorder);
print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";

// FILTRES
print '<tr class="liste_titre">';
print '<td class="liste_titre" align="left">';
print '<input class="flat" size="4" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
print '</td>';

// DATE CREATE
print '<td class="liste_titre" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_create" value="'.$month_create.'">';
$formother->select_year($year_create,'year_create',1, $min_year, 0);
print '</td>';

// UTILISATEUR
if ($user->rights->holiday->write_all)
{
    print '<td class="liste_titre maxwidthonsmartphone" align="left">';
    print $form->select_dolusers($search_employe,"search_employe",1,"",0,'','',0,32,0,'',0,'','maxwidth200');
    print '</td>';
}
else
{
    //print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre maxwidthonsmartphone" align="left">';
    print $form->select_dolusers($user->id,"search_employe",1,"",1,'','',0,32,0,'',0,'','maxwidth200');
    print '</td>';
}

// APPROVER
if($user->rights->holiday->write_all)
{
    print '<td class="liste_titre maxwidthonsmartphone" align="left">';

    $validator = new UserGroup($db);
    $excludefilter=$user->admin?'':'u.rowid <> '.$user->id;
    $valideurobjects = $validator->listUsersForGroup($excludefilter);
    $valideurarray = array();
    foreach($valideurobjects as $val) $valideurarray[$val->id]=$val->id;
    print $form->select_dolusers($search_valideur,"search_valideur",1,"",0,$valideurarray,'', 0, 32,0,'',0,'','maxwidth200');
    print '</td>';
}
else
{
    print '<td class="liste_titre">&nbsp;</td>';
}

// Type
print '<td class="liste_titre">';
$typeleaves=$holidaystatic->getTypes(1,-1);
$arraytypeleaves=array();
foreach($typeleaves as $key => $val)
{
    $labeltoshow = $val['label'];
    //$labeltoshow .= ($val['delay'] > 0 ? ' ('.$langs->trans("NoticePeriod").': '.$val['delay'].' '.$langs->trans("days").')':'');
    $arraytypeleaves[$val['rowid']]=$labeltoshow;
}
print $form->selectarray('type', $arraytypeleaves, (GETPOST('type')?GETPOST('type'):''), 1);
print '</td>';

// DUREE
print '<td class="liste_titre">&nbsp;</td>';

// DATE DEBUT
print '<td class="liste_titre" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_start" value="'.$month_start.'">';
$formother->select_year($year_start,'year_start',1, $min_year, $max_year);
print '</td>';

// DATE FIN
print '<td class="liste_titre" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
$formother->select_year($year_end,'year_end',1, $min_year, $max_year);
print '</td>';

// STATUT
print '<td class="liste_titre maxwidthonsmartphone maxwidth200" align="right">';
$holiday->selectStatutCP($search_statut);
print '</td>';

// ACTION
print '<td class="liste_titre" align="right">';
$searchpitco=$form->showFilterAndCheckAddButtons(0);
print $searchpitco;
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
		$userstatic->login=$infos_CP['user_login'];
		$userstatic->statut=$infos_CP['user_statut'];
		$userstatic->photo=$infos_CP['user_photo'];
		
		// Valideur
		$approbatorstatic->id=$infos_CP['fk_validator'];
		$approbatorstatic->lastname=$infos_CP['validator_lastname'];
		$approbatorstatic->firstname=$infos_CP['validator_firstname'];
		$approbatorstatic->login=$infos_CP['validator_login'];
		$approbatorstatic->statut=$infos_CP['validator_statut'];
		$approbatorstatic->photo=$infos_CP['validator_photo'];
		
		$date = $infos_CP['date_create'];

		print '<tr '.$bc[$var].'>';
		print '<td>';
		$holidaystatic->id=$infos_CP['rowid'];
		$holidaystatic->ref=$infos_CP['rowid'];
		print $holidaystatic->getNomUrl(1);
		print '</td>';
		print '<td style="text-align: center;">'.dol_print_date($date,'day').'</td>';
		print '<td>'.$userstatic->getNomUrl(-1, 'leave').'</td>';
		print '<td>'.$approbatorstatic->getNomUrl(-1).'</td>';
		print '<td>';
		$label=$alltypeleaves[$infos_CP['fk_type']]['label'];
		print $label?$label:$infos_CP['fk_type'];
		print '</td>';
		print '<td align="right">';
		$nbopenedday=num_open_day($infos_CP['date_debut_gmt'], $infos_CP['date_fin_gmt'], 0, 1, $infos_CP['halfday']);
		print $nbopenedday.' '.$langs->trans('DurationDays');
		print '</td>';
		print '<td align="center">'.dol_print_date($infos_CP['date_debut'],'day').'</td>';
		print '<td align="center">'.dol_print_date($infos_CP['date_fin'],'day').'</td>';
		print '<td align="right">'.$holidaystatic->LibStatut($infos_CP['statut'],5).'</td>';
		print '<td></td>';
		print '</tr>'."\n";

	}
}

// Si il n'y a pas d'enregistrement suite à une recherche
if($holiday_payes == '2')
{
    print '<tr '.$bc[false].'>';
    print '<td colspan="10" class="opacitymedium">'.$langs->trans('NoRecordFound').'</td>';
    print '</tr>';
}

print '</table>';
print '</div>';
print '</form>';

/*if ($user_id == $user->id)
{
	print '<br>';
	print '<div style="float: right; margin-top: 8px;">';
	print '<a href="./card.php?action=request" class="butAction">'.$langs->trans('AddCP').'</a>';
	print '</div>';
}*/

llxFooter();

$db->close();
