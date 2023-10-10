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
	$i = 0;
	while ($i < $totalarray['nbfield']) {
		$i++;
		if (!empty($totalarray['pos'][$i])) {
			switch ($totalarray['pos'][$i]) {
				case 'duration';
					print '<td class="right">';
					print (!empty($subtotalarray['val'][$totalarray['pos'][$i]]) ? convertSecondToTime($subtotalarray['val'][$totalarray['pos'][$i]], 'allhourmin') : 0);
					print '</td>';
					break;
				case 'string';
					print '<td class="left">';
					print (!empty($subtotalarray['val'][$totalarray['pos'][$i]]) ? $subtotalarray['val'][$totalarray['pos'][$i]] : '');
					print '</td>';
					break;
				case 'stock';
					print '<td class="right">';
					print price2num(!empty($subtotalarray['val'][$totalarray['pos'][$i]]) ? $subtotalarray['val'][$totalarray['pos'][$i]] : 0, 'MS');
					print '</td>';
					break;
				default;
					print '<td class="right">';
					print price(!empty($subtotalarray['val'][$totalarray['pos'][$i]]) ? $subtotalarray['val'][$totalarray['pos'][$i]] : 0);
					print '</td>';
					break;
			}
		} else {
			if ($i == 1) {
				print '<td>'.$langs->trans("SubTotal").'</td>';
			} else {
				print '<td></td>';
			}
		}
		$subtotalarray['val'][$totalarray['pos'][$i]] = 0;
	}
	print '</tr>';
}
