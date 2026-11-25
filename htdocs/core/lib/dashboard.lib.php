<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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
 *	\file			htdocs/core/lib/dashboard.lib.php
 *	\brief			Functions shared by the home pages of modules (index.php with statistics and widgets)
 */


/**
 * Return the colors of status badges defined by the current theme (theme_vars.inc.php)
 *
 * @return	array<int|string,string>	Colors indexed by badge status key: 0 (draft), 1 (validated), '1b', 2, 3, 4 (ok), '4b', 5, 6, 7, 8, 9 ...
 * @see getStatusPieChart()
 */
function getThemeBadgeStatusColors()
{
	global $conf;	// Used by theme_vars.inc.php

	$colors = array();

	$theme_vars_file = dol_getThemeFilePath('theme_vars.inc.php');
	if ($theme_vars_file) {
		include $theme_vars_file;
	}

	foreach (get_defined_vars() as $name => $value) {
		if (preg_match('/^badgeStatus(\w+)$/', $name, $reg) && is_string($value)) {
			$colors[(is_numeric($reg[1]) ? (int) $reg[1] : $reg[1])] = $value;
		}
	}

	return $colors;
}


/**
 * Return the HTML table with a pie chart of values by status, as shown on the home page of a module.
 * When javascript is disabled, the chart is replaced by one row per slice.
 *
 * @param	string	$title		Title of the table (already translated)
 * @param	array<int|string,array{label:string,nb:int|float,color?:string,url?:string,labelnojs?:string}>	$series	Ordered slices. 'label' is the legend label, 'nb' the value,
 *																													'color' a color like '#rrggbb' or '-#rrggbb' for a lighter shade, 'url' the link of the value in no-js mode,
 * 																													'labelnojs' the label to show in no-js mode instead of 'label' (can contain HTML).
 * @param	array{graphid?:string,height?:int|string,width?:int|string,showpercent?:int,total?:int|float|string|false,totallabel?:string}	$options	Options:
 *                                                                                                                                                      'graphid' (default 'idgraphstatus'), 'height' (default 200), 'width', 'showpercent' (default 1),
 *                                                                                                                                                      'total' (default: sum of 'nb', false to hide the total row), 'totallabel' (default: "Total").
 * @return	string				HTML content
 * @see getThemeBadgeStatusColors()
 */
function getStatusPieChart($title, $series, $options = array())
{
	global $conf, $langs;

	$graphid = empty($options['graphid']) ? 'idgraphstatus' : $options['graphid'];
	$height = isset($options['height']) ? $options['height'] : 200;
	$showpercent = isset($options['showpercent']) ? $options['showpercent'] : 1;

	$dataseries = array();
	$colorseries = array();
	$sum = 0;
	$usecolors = true;
	foreach ($series as $slice) {
		$dataseries[] = array($slice['label'], $slice['nb']);
		if (isset($slice['color'])) {
			$colorseries[] = $slice['color'];
		} else {
			$usecolors = false;
		}
		$sum += $slice['nb'];
	}

	$total = array_key_exists('total', $options) ? $options['total'] : $sum;

	$out = '<div class="div-table-responsive-no-min">';
	$out .= '<table class="noborder nohover centpercent">';
	$out .= '<tr class="liste_titre"><th colspan="2">'.$title.'</th></tr>'."\n";

	if (!empty($conf->use_javascript_ajax)) {
		$out .= '<tr><td class="center" colspan="2">';

		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
		$dolgraph = new DolGraph();
		$dolgraph->SetData($dataseries);
		if ($usecolors) {
			$dolgraph->SetDataColor($colorseries);
		}
		$dolgraph->setShowLegend(2);
		$dolgraph->setShowPercent($showpercent);
		$dolgraph->SetType(array('pie'));
		$dolgraph->SetHeight($height);
		if (!empty($options['width'])) {
			$dolgraph->SetWidth($options['width']);
		}
		$dolgraph->draw($graphid);
		$out .= $dolgraph->show($sum ? 0 : 1);

		$out .= '</td></tr>';
	} else {
		foreach ($series as $slice) {
			$out .= '<tr class="oddeven">';
			$out .= '<td>'.(isset($slice['labelnojs']) ? $slice['labelnojs'] : dol_escape_htmltag($slice['label'])).'</td>';
			$out .= '<td class="right">';
			if (!empty($slice['url'])) {
				$out .= '<a href="'.dol_escape_htmltag($slice['url']).'">'.$slice['nb'].'</a>';
			} else {
				$out .= $slice['nb'];
			}
			$out .= '</td>';
			$out .= '</tr>'."\n";
		}
	}

	if ($total !== false) {
		$out .= '<tr class="liste_total"><td>'.(empty($options['totallabel']) ? $langs->trans("Total") : $options['totallabel']).'</td><td class="right">'.$total.'</td></tr>';
	}

	$out .= '</table></div><br>';

	return $out;
}
