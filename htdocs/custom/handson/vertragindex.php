<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       handson/handsonindex.php
 *	\ingroup    handson
 *	\brief      Home page of handson top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/handson/class/programm.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/handson/class/saison.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/handson/class/region.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/handson/class/contaokategorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array("handson@handson"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->handson->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$programmstatic = new Programm($db);
$saisonstatic = new Saison($db);
$regionstatic = new Region($db);
$contaokategoriestatic = new Contaokategorie($db);


llxHeader("", $langs->trans("HandsOnArea"));

print load_fiche_titre($langs->trans("Verträge, die erneuert werden müssen"), '', 'handson.png@handson');

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
print '<br>';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Verträge").'</th></tr>';
print '<tr class="oddeven"><td class="center" colspan="2">';
$sql = "SELECT nom, season";
$sql .= " FROM ".MAIN_DB_PREFIX."handson_vertrag";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe ON fk_soc=rowid";
//$sql .= " WHERE season = '1,2'";
//$sql .= " AND c.entity IN (".getEntity('category').")";
$sql .= " GROUP BY season";
$total = 0;
$result = $db->query($sql);
echo "kommt";
var_dump($result);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	/*if (!empty($conf->use_javascript_ajax))
	{
		$dataseries = array();
		$rest = 0;
		$nbmax = 10;
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			if ($i < $nbmax)
			{
				$dataseries[] = array($obj->label, round($obj->nb));
			} else {
				$rest += $obj->nb;
			}
			$total += $obj->nb;
			$i++;
		}
		if ($i > $nbmax)
		{
			$dataseries[] = array($langs->trans("Other"), round($rest));
		}

		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
		$dolgraph = new DolGraph();
		$dolgraph->SetData($dataseries);
		$dolgraph->setShowLegend(2);
		$dolgraph->setShowPercent(1);
		$dolgraph->SetType(array('pie'));
		$dolgraph->setHeight('200');
		$dolgraph->draw('idstatscategproduct');
		print $dolgraph->show($total ? 0 : 1);
	} else {*/
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			print '<tr><td>'.$obj->nom.'</td><td>'.$obj->season.'</td></tr>';
			$total += $obj->nb;
			$i++;
		}
	//}
}
print '</td></tr>';
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">';
print $total;
print '</td></tr>';
print '</table>';
print '</div>';

/*
print '<div class="fichecenter"><div class="fichehalfleft">';

if (! empty($conf->handson->enabled) && $user->rights->handson->programm->read)
{
	$langs->load("Programme");

	$sql = "SELECT c.rowid, c.ref";
	$sql.= " FROM ".MAIN_DB_PREFIX."handson_programm as c";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("Programme").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$programmstatic->id=$obj->rowid;
				$programmstatic->ref=$obj->ref;
				$obj->total_ttc = 25;


				print $programmstatic->getNomUrl(0);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '</tr>';
				$i++;
			}

		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

print '</div><div class="fichehalfright">';

if (! empty($conf->handson->enabled) && $user->rights->handson->saison->read)
{
	$langs->load("Programme");

	$sql = "SELECT c.rowid, c.ref";
	$sql.= " FROM ".MAIN_DB_PREFIX."handson_saison as c";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("Saisons").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$saisonstatic->id=$obj->rowid;
				$saisonstatic->ref=$obj->ref;

				print $saisonstatic->getNomUrl(0);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '</tr>';
				$i++;
			}

		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

print '</div></div><div class="fichecenter"><div class="fichehalfleft">';

if (! empty($conf->handson->enabled) && $user->rights->handson->saison->read)
{
	$langs->load("Programme");

	$sql = "SELECT c.rowid, c.ref";
	$sql.= " FROM ".MAIN_DB_PREFIX."handson_saison as c";

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("Saisons").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$saisonstatic->id=$obj->rowid;
				$saisonstatic->ref=$obj->ref;

				print $saisonstatic->getNomUrl(0);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '</tr>';
				$i++;
			}

		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

print '</div></div>';*/

// End of page
llxFooter();
$db->close();
