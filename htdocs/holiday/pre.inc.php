<?php
/* Copyright (C) 2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011	Dimitri Mouillard	<dmouillard@teclib.com>
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
 *   	\file       pre.inc.php
 *		\ingroup    holiday
 *		\brief      Load files and menus.
 */

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");         // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");   // For "custom" directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

dol_include_once("/holiday/class/holiday.class.php");

$langs->load("user");
$langs->load("other");
$langs->load("holiday");


if (empty($conf->holiday->enabled))
{
    llxHeader('',$langs->trans('CPTitreMenu'));
    print '<div class="tabBar">';
    print '<span style="color: #FF0000;">'.$langs->trans('NotActiveModCP').'</span>';
    print '</div>';
    llxFooter();
    exit();
}


$verifConf.= "SELECT value";
$verifConf.= " FROM ".MAIN_DB_PREFIX."holiday_config";
$verifConf.= " WHERE name = 'userGroup'";

$result = $db->query($verifConf);
$obj = $db->fetch_object($result);

if($obj->value == NULL)
{
    llxHeader('',$langs->trans('CPTitreMenu'));
    print '<div class="tabBar">';
    print '<span style="color: #FF0000;">'.$langs->trans('NotConfigModCP').'</span>';
    print '</div>';
    llxFooter();
    exit();
}



function llxHeader($title)
{
    global $user, $conf, $langs;

    top_htmlhead('',$title);
    top_menu($head);

    $menu = new Menu();

    $menu->add("/holiday/index.php?mainmenu=holiday",$langs->trans("CPTitreMenu"));
    if($user->rights->holiday->write) {
        $menu->add("/holiday/fiche.php?mainmenu=holiday&action=request",$langs->trans("MenuAddCP"),2);
    }
    if($user->rights->holiday->define_holiday) {
        $menu->add("/holiday/define_holiday.php?leftmenu=setup&mainmenu=holiday",$langs->trans("MenuConfCP"),2);
    }
    if($user->rights->holiday->view_log) {
        $menu->add("/holiday/view_log.php?mainmenu=holiday",$langs->trans("MenuLogCP"),2);
    }
    if($user->rights->holiday->view_log) {
        $menu->add("/holiday/month_report.php?mainmenu=holiday",$langs->trans("MenuReportMonth"),2);
    }

    if(in_array('employees', $conf->modules) && $user->rights->employees->module_access)
    {
        $menu->add("/employees/index.php",$langs->trans("Menu_Title_EMPLOYEE"));
        $menu->add("/employees/index.php",$langs->trans("Menu_List_EMPLOYEE"),2);
        $menu->add("/employees/fiche.php?action=create",$langs->trans("Menu_Add_EMPLOYEE"),2);
        $menu->add("/employees/hire.php?action=create",$langs->trans("Menu_Add_HIRE"),2);
        $menu->add("/employees/salary.php?action=create",$langs->trans("Menu_Add_SALARY"),2);
        $menu->add("/employees/job.php?action=create",$langs->trans("Menu_Add_JOB"),2);
        $menu->add("/employees/disease.php?action=create",$langs->trans("Menu_Add_DISEASE"),2);
        $menu->add("/employees/month_report_disease.php",$langs->trans("Menu_Report_Disease"),2);
        $menu->add("/employees/set_hire_type.php",$langs->trans("Menu_Set_Hire_type"),2);

        if(!isset($_SESSION['employees_passphrase'])){
            $menu->add("/employees/store_secure.php",$langs->trans("Menu_Store_Secure"),2);
        }
    }

    left_menu($menu->liste);
}

?>
