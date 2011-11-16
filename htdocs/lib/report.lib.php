<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file       	htdocs/lib/report.lib.php
 *  \brief      	Set of functions for reporting
 *  \version		$Id: report.lib.php,v 1.6 2011/07/31 23:25:39 eldy Exp $
 */


/**
*    Show header of a VAT report
*
*    @param     $nom            Name of report
*    @param     $variante       Link for alternate report
*    @param     $period         Period of report
*    @param     $periodlink     Link to switch period
*    @param     $description    Description
*    @param     $builddate      Date generation
*    @param     $exportlink     Link for export or ''
*    @param		$moreparam		Array with list of params to add into form
*/
function report_header($nom,$variante='',$period,$periodlink,$description,$builddate,$exportlink='',$moreparam=array())
{
	global $langs;

	print "\n\n<!-- debut cartouche rapport -->\n";

	$h=0;
	$head[$h][0] = $_SERVER["PHP_SELF"];
	$head[$h][1] = $langs->trans("Report");
	dol_fiche_head($head, $hselected, $societe->nom);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	foreach($moreparam as $key => $value)
	{
	     print '<input type="hidden" name="'.$key.'" value="'.$value.'">';
	}
	print '<table width="100%" class="border">';

	// Ligne de titre
	print '<tr>';
	print '<td valign="top" width="110">'.$langs->trans("ReportName").'</td>';
	if (! $variante) print '<td colspan="3">';
	else print '<td>';
	print $nom;
	if ($variante) print '</td><td colspan="2">'.$variante;
	print '</td>';
	print '</tr>';

	// Ligne de la periode d'analyse du rapport
	print '<tr>';
	print '<td>'.$langs->trans("ReportPeriod").'</td>';
	if (! $periodlink) print '<td colspan="3">';
	else print '<td>';
	if ($period) print $period;
	if ($periodlink) print '</td><td colspan="2">'.$periodlink;
	print '</td>';
	print '</tr>';

	// Ligne de description
	print '<tr>';
	print '<td valign="top">'.$langs->trans("ReportDescription").'</td>';
	print '<td colspan="3">'.$description.'</td>';
	print '</tr>';

	// Ligne d'export
	print '<tr>';
	print '<td>'.$langs->trans("GeneratedOn").'</td>';
	if (! $exportlink) print '<td colspan="3">';
	else print '<td>';
	print dol_print_date($builddate);
	if ($exportlink) print '</td><td>'.$langs->trans("Export").'</td><td>'.$exportlink;
	print '</td></tr>';

	print '<tr>';
	print '<td colspan="4" align="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("Refresh").'"></td>';
	print '</tr>';

	print '</table>';

	print '</form>';

	print '</div>';
	print "\n<!-- fin cartouche rapport -->\n\n";
}

?>
