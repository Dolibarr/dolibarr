<?php
/* Copyright (C) 2025		Frédéric France			<frederic.france@free.fr>
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
 *
 */

/**
 * @var Translate $langs
 * @var array{nbfield:int,pos?:array<int,int>,val?:array<int,float>} $totalarray
 */

'@phan-var-force array{nbfield:int,pos?:array<int,int>,val?:array<int,float>} $totalarray';

// Move fields of totalizable into the common array pos and val
if (!empty($subtotalarray['totalizable']) && is_array($subtotalarray['totalizable'])) {
	foreach ($subtotalarray['totalizable'] as $keytotalizable => $valtotalizable) {
		$totalarray['pos'][$valtotalizable['pos']] = $keytotalizable;
		$subtotalarray['val'][$keytotalizable] = isset($valtotalizable['total']) ? $valtotalizable['total'] : 0;
	}
}
// Show total line
if (isset($totalarray['pos'])) {
	print '<tr class="liste_total">';
	$j = 0;
	while ($j < $totalarray['nbfield']) {
		$j++;
		if (!empty($totalarray['pos'][$j])) {
			switch ($totalarray['pos'][$j]) {
				case 'duration':
					print '<td class="right">';
					print(!empty($subtotalarray['val'][$totalarray['pos'][$j]]) ? convertSecondToTime($subtotalarray['val'][$totalarray['pos'][$j]], 'allhourmin') : 0);
					print '</td>';
					break;
				case 'string':
					print '<td class="left">';
					print(!empty($subtotalarray['val'][$totalarray['pos'][$j]]) ? $subtotalarray['val'][$totalarray['pos'][$j]] : '');
					print '</td>';
					break;
				case 'stock':
					print '<td class="right">';
					print price2num(!empty($subtotalarray['val'][$totalarray['pos'][$j]]) ? $subtotalarray['val'][$totalarray['pos'][$j]] : 0, 'MS');
					print '</td>';
					break;
				default:
					print '<td class="right">';
					print price(!empty($subtotalarray['val'][$totalarray['pos'][$j]]) ? $subtotalarray['val'][$totalarray['pos'][$j]] : 0);
					print '</td>';
					break;
			}
			$subtotalarray['val'][$totalarray['pos'][$j]] = 0;
		} else {
			if ($j == 1) {
				print '<td>'.$langs->trans("SubTotal").'</td>';
			} else {
				print '<td></td>';
			}
		}
	}
	print '</tr>';
}
