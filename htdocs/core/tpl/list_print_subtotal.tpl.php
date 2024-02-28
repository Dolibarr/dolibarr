<?php

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
