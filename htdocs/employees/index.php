<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		    Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			    <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014  Alexandre Spangaro    <alexandre.spangaro@gmail.com> 
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
 *       \file       htdocs/employees/index.php
 *       \ingroup    employee
 *       \brief      Page accueil module salariés
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee_type.class.php';

$langs->load("companies");
$langs->load("employees");

// Security check
$result=restrictedArea($user,'employee');


/*
 * View
 */

llxHeader('',$langs->trans("Employees"),'EN:Module_Employees|FR:Module_Salariés|ES:M&oacute;dulo_Asalariados');

$staticemployee=new Employee($db);
$statictype=new EmployeeType($db);

print_fiche_titre($langs->trans("EmployeesArea"));


$var=True;

$Employees=array();
$EmployeesAValider=array();
$EmployeesDeactivate=array();

$EmployeeType=array();

// Liste les employees
$sql = "SELECT t.rowid, t.label,";
$sql.= " d.statut, count(d.rowid) as somme";
$sql.= " FROM ".MAIN_DB_PREFIX."employee_type as t";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."employee as d";
$sql.= " ON t.rowid = d.fk_employee_type";
$sql.= " AND d.entity IN (".getEntity().")";
$sql.= " WHERE t.entity IN (".getEntity().")";
$sql.= " GROUP BY t.rowid, t.label, d.statut";

dol_syslog("index.php::select nb of employees by type sql=".$sql, LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);

		$emptype=new EmployeeType($db);
		$emptype->id=$objp->rowid;
		$emptype->label=$objp->label;
		$EmployeeType[$objp->rowid]=$emptype;

		if ($objp->statut == 0) { $EmployeeToValidate[$objp->rowid]=$objp->somme; }
		if ($objp->statut == 1) { $EmployeesValidated[$objp->rowid]=$objp->somme; }
		if ($objp->statut == 2) { $EmployeesDeactivated[$objp->rowid]=$objp->somme; }

		$i++;
	}
	$db->free($result);
}

$now=dol_now();

// List employees up to date
// current rule: uptodate = the end date is in future whatever is type
// old rule: uptodate = if type does not need payment, that end date is null, if type need payment that end date is in future)
$sql = "SELECT count(*) as somme , d.fk_employee_type";
$sql.= " FROM ".MAIN_DB_PREFIX."employee as d, ".MAIN_DB_PREFIX."employee_type as t";
$sql.= " WHERE d.entity IN (".getEntity().")";
$sql.= " AND d.statut = 1";
$sql.= " AND t.rowid = d.fk_employee_type";
$sql.= " GROUP BY d.fk_employee_type";

dol_syslog("index.php::select nb of uptodate employees by type sql=".$sql, LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);
		$EmployeeUpToDate[$objp->fk_employee_type]=$objp->somme;
		$i++;
	}
	$db->free();
}


//print '<tr><td width="30%" class="notopnoleft" valign="top">';
print '<div class="fichecenter"><div class="fichethirdleft">';


// Formulaire recherche employee
print '<form action="liste.php" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="search">';
print '<table class="noborder nohover" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("SearchAnEmployee").'</td>';
print "</tr>\n";
$var=false;
print "<tr ".$bc[$var].">";
print '<td>';
print $langs->trans("Ref").':</td><td><input type="text" name="search_ref" class="flat" size="16">';
print '</td><td rowspan="3"><input class="button" type="submit" value="'.$langs->trans("Search").'"></td></tr>';
print "<tr ".$bc[$var].">";
print '<td>';
print $langs->trans("Name").':</td><td><input type="text" name="search_lastname" class="flat" size="16">';
print '</td></tr>';
print "<tr ".$bc[$var].">";
print '<td>';
print $langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="16">';
print '</td></tr>';
print "</table></form>";


/*
 * Statistics
 */

if ($conf->use_javascript_ajax)
{
    print '<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
    print '<tr><td align="center">';

    $SommeA=0;
    $SommeB=0;
    $SommeC=0;
    $dataval=array();
    $datalabels=array();
    $i=0;
    foreach ($EmployeeType as $key => $emptype)
    {
        $datalabels[]=array($i,$emptype->getNomUrl(0,dol_size(16)));
        $dataval['draft'][]=array($i,isset($EmployeeToValidate[$key])?$EmployeeToValidate[$key]:0);
        $dataval['validated'][]=array($i,isset($EmployeeValidated[$key])?$EmployeeValidated[$key]:0);
        $dataval['deactivated'][]=array($i,isset($EmployeesDeactivated[$key])?$EmployeesDeactivated[$key]:0);
        $SommeA+=isset($EmployeeToValidate[$key])?$EmployeeToValidate[$key]:0;
        $SommeB+=isset($EmployeesValidated[$key])?$EmployeesValidated[$key]:0;
        $SommeC+=isset($EmployeesDeactivated[$key])?$EmployeesDeactivated[$key]:0;
        $i++;
    }

    $dataseries=array();
    $dataseries[]=array('label'=>$langs->trans("EmployeeStatusToValidate"),'data'=>round($SommeA));
    $dataseries[]=array('label'=>$langs->trans("EmployeeStatusDeactivated"),'data'=>round($SommeC));
    $dataseries[]=array('label'=>$langs->trans("EmployeeStatusValidated"),'data'=>round($SommeB));
    $data=array('series'=>$dataseries);
    dol_print_graph('stats',300,180,$data,1,'pie',1);
    print '</td></tr>';
    print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
    print $SommeA+$SommeB+$SommeC;
    print '</td></tr>';
    print '</table>';
}

//print '</td><td class="notopnoleftnoright" valign="top">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

$var=true;

/*
 * Last modified employees
 */
$max=5;

$sql = "SELECT e.rowid, e.statut, e.lastname, e.firstname, e.fk_user, e.tms as datem,";
$sql.= " te.rowid as typeid, te.label";
$sql.= " FROM ".MAIN_DB_PREFIX."employee as e, ".MAIN_DB_PREFIX."employee_type as te";
$sql.= " WHERE e.entity IN (".getEntity().")";
$sql.= " AND e.fk_employee_type = te.rowid";
$sql.= $db->order("e.tms","DESC");
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4">'.$langs->trans("LastEmployeesModified",$max).'</td></tr>';

	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		$var = True;
		while ($i < $num)
		{
			$var=!$var;
			$obj = $db->fetch_object($resql);
			print "<tr ".$bc[$var].">";
			$staticemployee->id=$obj->rowid;
			$staticemployee->lastname=$obj->lastname;
			$staticemployee->firstname=$obj->firstname;
			if (! empty($obj->fk_user)) {
				$staticemployee->socid = $obj->fk_user;
				$staticemployee->fetch_thirdparty();
				$staticemployee->name=$staticemployee->thirdparty->name;
			} else {
				$staticemployee->name=$obj->company;
			}
			$staticemployee->ref=$staticemployee->getFullName($langs);
			$statictype->id=$obj->typeid;
			$statictype->label=$obj->label;
			print '<td>'.$staticemployee->getNomUrl(1,32).'</td>';
			print '<td>'.$statictype->getNomUrl(1,32).'</td>';
			print '<td>'.dol_print_date($db->jdate($obj->datem),'dayhour').'</td>';
			// print '<td align="right">'.$staticemployee->LibStatut($obj->statut,($obj->cotisation=='yes'?1:0),$db->jdate($obj->date_end_subscription),5).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table><br>";
}
else
{
	dol_print_error($db);
}


// Summary of employees by type
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("EmployeesTypes").'</td>';
print '<td align=right>'.$langs->trans("EmployeeStatusToValid").'</td>';
print '<td align=right>'.$langs->trans("EmployeeUpToDate").'</td>';
print '<td align=right>'.$langs->trans("EmployeeStatusDeactivated").'</td>';
print "</tr>\n";

foreach ($EmployeeType as $key => $emptype)
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td>'.$emptype->getNomUrl(1, dol_size(32)).'</td>';
	print '<td align="right">'.(isset($EmployeeToValidate[$key]) && $EmployeeToValidate[$key] > 0?$EmployeeToValidate[$key]:'').' '.$staticemployee->LibStatut(-1,$emptype->cotisation,0,3).'</td>';
	print '<td align="right">'.(isset($EmployeesValidated[$key]) && ($EmployeesValidated[$key]-(isset($EmployeeUpToDate[$key])?$EmployeeUpToDate[$key]:0) > 0) ? $EmployeesValidated[$key]-(isset($EmployeeUpToDate[$key])?$EmployeeUpToDate[$key]:0):'').' '.$staticemployee->LibStatut(1,$emptype->cotisation,0,3).'</td>';
  print '<td align="right">'.(isset($EmployeesDeactivated[$key]) && $EmployeesDeactivated[$key]> 0 ?$EmployeesDeactivated[$key]:'').' '.$staticemployee->LibStatut(0,0,0,3).'</td>';
	print "</tr>\n";
}
print '<tr class="liste_total">';
print '<td class="liste_total">'.$langs->trans("Total").'</td>';
print '<td class="liste_total" align="right">'.$SommeA.' '.$staticemployee->LibStatut(0,0,0,3).'</td>';
print '<td class="liste_total" align="right">'.$SommeB.' '.$staticemployee->LibStatut(1,0,0,3).'</td>';
print '<td class="liste_total" align="right">'.$SommeC.' '.$staticemployee->LibStatut(2,0,0,3).'</td>';
print '</tr>';

print "</table>\n";
print "<br>\n";

//print '</td></tr></table>';
print '</div></div></div>';


llxFooter();
$db->close();
?>
