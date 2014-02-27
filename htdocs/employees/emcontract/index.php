<?php
/* Copyright (C) 2011	     Dimitri Mouillard	  <dmouillard@teclib.com>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
 * Copyright (C) 2013	     Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2013	     Regis Houssin		    <regis.houssin@capnetworks.com>
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
 *   	\file       htdocs/employees/emcontract/index.php
 *		\ingroup    employee contract
 *		\brief      List of employment contract.
 */

$res=@include("../main.inc.php");
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
// require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/emcontract/class/emcontract.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';

$langs->load('users');
$langs->load('employee');

// Security check
$result=restrictedArea($user,'employee',$id);

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

if (! $sortfield) $sortfield="em.rowid";
if (! $sortorder) $sortorder="DESC";
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$id = GETPOST('id');

$search_ref           = GETPOST('search_ref');
//$month_create       = GETPOST('month_create');
//$year_create        = GETPOST('year_create');
$month_start          = GETPOST('month_start');
$year_start           = GETPOST('year_start');
$month_end            = GETPOST('month_end');
$year_end             = GETPOST('year_end');
$search_employee      = GETPOST('search_employee');
$search_type_contract = GETPOST('search_type_contract');

/*
 * Actions
 */

// None



/*
 * View
 */

$emcontract = new Emcontract($db);
$emcontractstatic=new Emcontract($db);
$emp = new Employee($db);

// Parameters
$max_year = 5;
$min_year = 10;
$filter='';

llxHeader(array(),$langs->trans('ContractTitle'));

$order = $db->order($sortfield,$sortorder).$db->plimit($conf->liste_limit + 1, $offset);

// Where
if(!empty($search_ref))
{
    $filter.= " AND em.rowid LIKE '%".$db->escape($search_ref)."%'\n";
}

// Date Start
if($year_start > 0) {
    if($month_start > 0) {
    	$filter .= " AND (em.date_start_contract BETWEEN '".$db->idate(dol_get_first_day($year_start,$month_start,1))."' AND '".$db->idate(dol_get_last_day($year_start,$month_start,1))."')";
    	//$filter.= " AND date_format(em.date_start_contract, '%Y-%m') = '$year_start-$month_start'";
    } else {
    	$filter .= " AND (em.date_start_contract BETWEEN '".$db->idate(dol_get_first_day($year_start,1,1))."' AND '".$db->idate(dol_get_last_day($year_start,12,1))."')";
    	//$filter.= " AND date_format(em.date_start_contract, '%Y') = '$year_start'";
    }
} else {
    if($month_start > 0) {
        $filter.= " AND date_format(em.date_start_contract, '%m') = '$month_start'";
    }
}

// Date End
if($year_end > 0) {
    if($month_end > 0) {
    	$filter .= " AND (em.date_end_contract BETWEEN '".$db->idate(dol_get_first_day($year_end,$month_end,1))."' AND '".$db->idate(dol_get_last_day($year_end,$month_end,1))."')";
    	//$filter.= " AND date_format(em.date_end_contract, '%Y-%m') = '$year_end-$month_end'";
    } else {
    	$filter .= " AND (em.date_end_contract BETWEEN '".$db->idate(dol_get_first_day($year_end,1,1))."' AND '".$db->idate(dol_get_last_day($year_end,12,1))."')";
    	//$filter.= " AND date_format(em.date_end_contract, '%Y') = '$year_end'";
    }
} else {
    if($month_end > 0) {
        $filter.= " AND date_format(em.date_end_contract, '%m') = '$month_end'";
    }
}

// Date create
/*
if($year_create > 0) {
    if($month_create > 0) {
    	$filter .= " AND (em.datec BETWEEN '".$db->idate(dol_get_first_day($year_create,$month_create,1))."' AND '".$db->idate(dol_get_last_day($year_create,$month_create,1))."')";
    	//$filter.= " AND date_format(em.datec, '%Y-%m') = '$year_create-$month_create'";
    } else {
    	$filter .= " AND (em.datec BETWEEN '".$db->idate(dol_get_first_day($year_create,1,1))."' AND '".$db->idate(dol_get_last_day($year_create,12,1))."')";
    	//$filter.= " AND date_format(em.datec, '%Y') = '$year_create'";
    }
} else {
    if($month_create > 0) {
        $filter.= " AND date_format(em.datec, '%m') = '$month_create'";
    }
}
*/

// Employee
if(!empty($search_employee) && $search_employee != -1) {
    $filter.= " AND em.fk_employee = '".$db->escape($search_employee)."'\n";
}

// Type contract
if(!empty($search_type_contract) && $search_type_contract != -1) {
    $filter.= " AND em.type_contract = '".$db->escape($search_type_contract)."'\n";
}

/*************************************
 * Fin des filtres de recherche
*************************************/

// Récupération de l'ID de l'utilisateur
$user_id = $user->id;

if ($id > 0)
{
	// Charge utilisateur edite
	$emp->fetch($id);
	//$emp->getrights();
	$user_id = $emp->rowid;
}

// Récupération des contrats de l'utilisateur ou de tous les users
if (!$user->rights->employee->lire || $id > 0)
{
	$emcontract2 = $emcontract->fetchByUser($user_id,$order,$filter);
}
else
{
  $emcontract2 = $emcontract->fetchAll($order,$filter);
}
// Si erreur SQL
if ($emcontract2 == '-1')
{
    print_fiche_titre($langs->trans('ListContract'));

    print '<div class="tabBar">';
    print '<span>'.$langs->trans('ErrorSQL');
    print ' '.$emcontract->error.'</span>';
    print '</div>';
    exit();
}

/*************************************
 * Affichage du tableau des contrats
*************************************/

$form = new Form($db);
$formother = new FormOther($db);
$em = new Emcontract($db);

print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";

if ($id > 0)
{
	$head = employee_prepare_head($emp);

	$title = $langs->trans("Employee");
	dol_fiche_head($head, 'contract', $title, 0, 'user');

	print '<table class="border" width="100%">';

  $linkback = '<a href="'.DOL_URL_ROOT.'/employees/liste.php">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
	print '<td colspan="2">';
	print $form->showrefnav($emp, 'id', $linkback);
	print '</td>';
	print '</tr>';
  
  // Civility
	print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$emp->getCivilityLabel().'&nbsp;</td></tr>';

	// LastName
	print '<tr><td width="25%" valign="top">'.$langs->trans("LastName").'</td>';
	print '<td colspan="2">'.$emp->lastname.'</td>';
	print "</tr>\n";

	// FirstName
	print '<tr><td width="25%" valign="top">'.$langs->trans("FirstName").'</td>';
	print '<td colspan="2">'.$emp->firstname.'</td>';
	print "</tr>\n";
  
  // Status
	print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$emp->getLibStatut(4).'</td></tr>';

	print '</table>';
  
  /*
   * Actions
   */
   
   if($user->rights->employee->creer)
   {
      print '</div>';
      print '<div style="float: right; margin-top: 8px;">';
      print '<a href="./fiche.php?action=add" class="butAction">'.$langs->trans('AddContract').'</a>';
      print '</div>';
      print '<br><br><br>';
   }
}
else
{
	print_barre_liste($langs->trans("ListContract"), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, "", $num);
}
print '</table>';

print '<table class="noborder" width="100%;">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"em.rowid","",'','',$sortfield,$sortorder);
//print_liste_field_titre($langs->trans("DateCreate"),$_SERVER["PHP_SELF"],"em.datec","",'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Employee"),$_SERVER["PHP_SELF"],"em.fk_employee",'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Typecontract"),$_SERVER["PHP_SELF"],"em.type_contract","",'','',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],"em.date_start_contract","",'','align="center"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"],"em.date_end_contract","",'','align="center"',$sortfield,$sortorder);
print_liste_field_titre("&nbsp;");
print "</tr>\n";

// Filters
print '<tr class="liste_titre">';
print '<td class="liste_titre" align="left" width="50">';
print '<input class="flat" size="4" type="text" name="search_ref" value="'.$search_ref.'">';

// Date create
/*
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_create" value="'.$month_create.'">';
$formother->select_year($year_create,'year_create',1, $min_year, $max_year);
print '</td>';
*/

// Employee
if($user->rights->employee->lire) {
    print '<td class="liste_titre" align="left">';
    $form->select_employees($search_employee,"search_employee",1,"",0,'');
    print '</td>';
} 
else {
    print '<td class="liste_titre">&nbsp;</td>';
}

// Type of contract
print '<td class="liste_titre" colspan="1" align="left">';
print $em->select_typec($search_type_contract,'search_type_contract',0);
print '</td>';
        
// Date Start
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_start" value="'.$month_start.'">';
$formother->select_year($year_start,'year_start',1, $min_year, $max_year);
print '</td>';

// Date End
print '<td class="liste_titre" colspan="1" align="center">';
print '<input class="flat" type="text" size="1" maxlength="2" name="month_end" value="'.$month_end.'">';
$formother->select_year($year_end,'year_end',1, $min_year, $max_year);
print '</td>';

// Action
print '<td align="right" width="18">';
print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans('Search').'">';
print "</td></tr>\n";


// Lines
if (! empty($emcontract->emcontract))
{
	$userstatic = new User($db);
  
	foreach($emcontract->emcontract as $infos_em)
	{
		$var=!$var;

		// Utilisateur
		$userstatic->id=$infos_em['fk_employee'];
		$userstatic->lastname=$infos_em['user_lastname'];
		$userstatic->firstname=$infos_em['user_firstname'];

		print '<tr '.$bc[$var].'>';
		print '<td>';
		$emcontractstatic->id=$infos_em['rowid'];
		$emcontractstatic->ref=$infos_em['rowid'];
		print $emcontractstatic->getNomUrl(1);
		print '</td>';
		//print '<td style="text-align: center;">'.dol_print_date($infos_em['datec'],'day').'</td>';
		print '<td>'.$userstatic->getNomUrl('1').'</td>';
		//print '<td>'.$emcontractstatic->getNomUrl('1').'</td>';
    print '<td>'.$em->LibTypeContract($infos_em['type_contract']).'</td>';
		print '<td align="center">'.dol_print_date($infos_em['date_start_contract'],'day').'</td>';
		print '<td align="center">'.dol_print_date($infos_em['date_end_contract'],'day').'</td>';
		print '<td>&nbsp;</td>';
		print '</tr>'."\n";

	}
}

// Si il n'y a pas d'enregistrement suite à une recherche
// xxx

if (empty ($id))
{
  /*
   * Actions
   */
   
   if($user->rights->employee->creer)
   {
      print '</div>';
      print '<div style="float: right; margin-top: 8px;">';
      print '<a href="./fiche.php?action=add" class="butAction">'.$langs->trans('AddContract').'</a>';
      print '</div>';
   }
}
   
print '</table>';
print '</form>';

llxFooter();

$db->close();
?>
