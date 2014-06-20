<?php
/*
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
 *	\file       htdocs/multicurrency/index.php
 *	\ingroup    multicurrency
 *	\brief      multicurrency
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/multicurrency.class.php';

$page = GETPOST('page','int');
if ($page < 0) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

llxHeader("",$langs->trans("Currencies"));

print_barre_liste($langs->trans("CurrenciesRates"), $page, 'index.php');

$sql = "SELECT cur_from, cur_to, rate, valid, source FROM ".MAIN_DB_PREFIX."c_currencies_rate ORDER BY cur_from ASC LIMIT ".$offset.",".$limit.";";
$result=$db->query($sql);

if (! $result)
{
    dol_print_error('',"erreur de connexion ");
} else {
    $num = count($result);
    $var=True;
    $comm=array();
    if ($num > 0)
    {
		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Currencyfrom").'</td>';
		print '<td>'.$langs->trans("Currencyto").'</td>';
		print '<td>'.$langs->trans("Rates").'</td>';
		print '<td>'.$langs->trans("Validity").'</td>';
		print '<td>'.$langs->trans("Source").'</td>';
		print "</tr>\n";
			
		while ($rate = $db->fetch_object($result))
		{
			// Détail rate currency
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td>'.$rate->cur_from."</td>\n";
			print '<td>'.$rate->cur_to."</td>\n";
			print '<td>'.$rate->rate."</td>\n";
			print '<td>'.$rate->valid."</td>\n";
			print '<td>'.$rate->source."</td>\n";
			print '</tr>'."\n";
		}
		print "</table></p>";
	} else {
		dol_print_error('',"Aucune cours de devise trouv&eacute;");
	}
	
}
llxFooter();

$db->close();
