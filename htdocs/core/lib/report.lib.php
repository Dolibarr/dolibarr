<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@inodbox.com>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file       	htdocs/core/lib/report.lib.php
 *  \brief      	Set of functions for reporting
 */


/**
 *	Show header of a report
 *
 *	@param	string				$reportname     Name of report
 *	@param 	string				$notused        Not used
 *	@param 	string				$period         Period of report
 *	@param 	string				$periodlink     Link to switch period
 *	@param 	string				$description    Description
 *	@param 	integer	            $builddate      Date generation
 *	@param 	string				$exportlink     Link for export or ''
 *	@param	array				$moreparam		Array with list of params to add into form
 *	@param	string				$calcmode		Calculation mode
 *  @param  string              $varlink        Add a variable into the address of the page
 *	@return	void
 */
function report_header($reportname, $notused, $period, $periodlink, $description, $builddate, $exportlink = '', $moreparam = array(), $calcmode = '', $varlink = '')
{
	global $langs;

	print "\n\n<!-- start banner of report -->\n";

	if(! empty($varlink)) $varlink = '?'.$varlink;

	$head = array();

	$h=0;
	$head[$h][0] = $_SERVER["PHP_SELF"].$varlink;
	$head[$h][1] = $langs->trans("Report");
	$head[$h][2] = 'report';

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].$varlink.'">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">'."\n";

	dol_fiche_head($head, 'report');

	foreach($moreparam as $key => $value)
	{
		 print '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
	}

	print '<table class="border tableforfield centpercent">'."\n";

	$variante = ($periodlink || $exportlink);

	// Ligne de titre
	print '<tr>';
	print '<td width="110">'.$langs->trans("ReportName").'</td>';
	print '<td>';
	print $reportname;
	print '</td>';
	if ($variante) print '<td></td>';
	print '</tr>'."\n";

	// Calculation mode
	if ($calcmode)
	{
		print '<tr>';
		print '<td width="110">'.$langs->trans("CalculationMode").'</td>';
		print '<td>';
		print $calcmode;
		if ($variante) print '<td></td>';
		print '</td>';
		print '</tr>'."\n";
	}

	// Ligne de la periode d'analyse du rapport
	print '<tr>';
	print '<td>'.$langs->trans("ReportPeriod").'</td>';
	print '<td>';
	if ($period) print $period;
	if ($variante) print '<td class="nowraponall">'.$periodlink.'</td>';
	print '</td>';
	print '</tr>'."\n";

	// Ligne de description
	print '<tr>';
	print '<td>'.$langs->trans("ReportDescription").'</td>';
	print '<td>'.$description.'</td>';
	if ($variante) print '<td></td>';
	print '</tr>'."\n";

	// Ligne d'export
	print '<tr>';
	print '<td>'.$langs->trans("GeneratedOn").'</td>';
	print '<td>';
	print dol_print_date($builddate, 'dayhour');
	print '</td>';
	if ($variante) print '<td>'.($exportlink ? $langs->trans("Export").': '.$exportlink : '').'</td>';
	print '</tr>'."\n";

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="submit" value="'.$langs->trans("Refresh").'"></div>';

	print '</form>';
	print '<br>';

	print "\n<!-- end banner of report -->\n\n";
}
