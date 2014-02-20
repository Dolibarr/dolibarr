<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
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
 * or see http://www.gnu.org/
 */

/**
 *	  \file       htdocs/core/lib/employee.lib.php
 *		\brief      Ensemble de fonctions de base pour les salariés
 */

/**
 *  Return array head with list of tabs to view object informations
 *
 *  @param	Object	$object         Member
 *  @return array           		head
 */
function employee_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/employees/fiche.php?rowid='.$object->id;
	$head[$h][1] = $langs->trans("EmployeeCard");
	$head[$h][2] = 'general';
	$h++;
  
  // Show contract tab
	$head[$h][0] = DOL_URL_ROOT."/employees/emcontract/index.php?id=".$object->id;
	$head[$h][1] = $langs->trans('Contract');
	$head[$h][2] = 'contract';
	$h++;
	
	// Show agenda tab
	if (! empty($conf->agenda->enabled))
	{
	    $head[$h][0] = DOL_URL_ROOT."/employees/agenda.php?id=".$object->id;
	    $head[$h][1] = $langs->trans('Agenda');
	    $head[$h][2] = 'agenda';
	    $h++;
	}

	// Show category tab
	if (! empty($conf->categorie->enabled) && ! empty($user->rights->categorie->lire))
	{
		$head[$h][0] = DOL_URL_ROOT."/categories/categorie.php?id=".$object->id.'&type=3';
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;
	}

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'employee');

    $head[$h][0] = DOL_URL_ROOT.'/employees/note.php?id='.$object->id;
  	$head[$h][1] = $langs->trans("Note");
  	$head[$h][2] = 'note';
  	$h++;

    $head[$h][0] = DOL_URL_ROOT.'/employees/document.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Documents");
    $head[$h][2] = 'document';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/employees/info.php?id='.$object->id;
  	$head[$h][1] = $langs->trans("Info");
  	$head[$h][2] = 'info';
  	$h++;

    complete_head_from_modules($conf,$langs,$object,$head,$h,'employee','remove');

	return $head;
}


/**
 *  Return array head with list of tabs to view object informations
 *
 *  @return	array		head
 */
function employee_admin_prepare_head()
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/employees/admin/employee.php';
    $head[$h][1] = $langs->trans("Miscellaneous");
    $head[$h][2] = 'general';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,'',$head,$h,'employee_admin');

    $head[$h][0] = DOL_URL_ROOT.'/employees/admin/employee_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsEmployee");
    $head[$h][2] = 'attributes';
    $h++;
    
    $head[$h][0] = DOL_URL_ROOT.'/employees/admin/employee_type_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsEmployeeType");
    $head[$h][2] = 'attributes_type';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/employees/admin/public.php';
    $head[$h][1] = $langs->trans("BlankSubscriptionForm");
    $head[$h][2] = 'public';
    $h++;

    complete_head_from_modules($conf,$langs,'',$head,$h,'employee_admin','remove');

    return $head;
}


/**
 *  Return array head with list of tabs to view object stats informations
 *
 *  @param	Object	$object         employee or null
 *  @return	array           		head
 */
function employee_stats_prepare_head($object)
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/employees/stats/geo.php?mode=employeebycountry';
    $head[$h][1] = $langs->trans("Country");
    $head[$h][2] = 'statscountry';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/employees/stats/geo.php?mode=employeebystate';
    $head[$h][1] = $langs->trans("State");
    $head[$h][2] = 'statsstate';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/employees/stats/geo.php?mode=employeebytown';
    $head[$h][1] = $langs->trans('Town');
    $head[$h][2] = 'statstown';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/employees/stats/byproperties.php';
    $head[$h][1] = $langs->trans('ByProperties');
    $head[$h][2] = 'statsbyproperties';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'employee_stats');

    complete_head_from_modules($conf,$langs,$object,$head,$h,'employee_stats','remove');

    return $head;
}
?>
