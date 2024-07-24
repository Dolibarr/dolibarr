<?php

// Move fields of totalizable into the common array pos and val
if (!empty($totalarray['totalizable']) && is_array($totalarray['totalizable'])) {
	foreach ($totalarray['totalizable'] as $keytotalizable => $valtotalizable) {
		$totalarray['pos'][$valtotalizable['pos']] = $keytotalizable;
		$totalarray['val'][$keytotalizable] = isset($valtotalizable['total']) ? $valtotalizable['total'] : 0;
	}
}
// Show total line
if (isset($totalarray['pos'])) {
	print '<tr class="liste_total">';
	$i = 0;
	while ($i < $totalarray['nbfield']) {
		$i++;
		if (!empty($totalarray['pos'][$i])) {
			// if $totalarray['type'] not present we consider it as number
			if (empty($totalarray['type'][$i])) {
				$totalarray['type'][$i] = 'real';
			}
			switch ($totalarray['type'][$i]) {
				case 'duration':
					print '<td class="right">';
					print (!empty($totalarray['val'][$totalarray['pos'][$i]]) ? convertSecondToTime($totalarray['val'][$totalarray['pos'][$i]], 'allhourmin') : 0);
					print '</td>';
					break;
				case 'string':	// This type is no more used. type is now varchar(x)
					print '<td class="left">';
					print (!empty($totalarray['val'][$totalarray['pos'][$i]]) ? $totalarray['val'][$totalarray['pos'][$i]] : '');
					print '</td>';
					break;
				case 'stock':
					print '<td class="right">';
					print price2num(!empty($totalarray['val'][$totalarray['pos'][$i]]) ? $totalarray['val'][$totalarray['pos'][$i]] : 0, 'MS');
					print '</td>';
					break;
				default:
					print '<td class="right">';
					print price(!empty($totalarray['val'][$totalarray['pos'][$i]]) ? $totalarray['val'][$totalarray['pos'][$i]] : 0);
					print '</td>';
					break;
			}
		} else {
			if ($i == 1) {
				if (is_null($limit) || $num < $limit) {
					print '<td>'.$langs->trans("Total").'</td>';
				} else {
					print '<td>';
					if (is_object($form)) {
						print $form->textwithpicto($langs->trans("Total"), $langs->transnoentitiesnoconv("Totalforthispage"));
					} else {
						print $langs->trans("Totalforthispage");
					}
					print '</td>';
				}
			} else {
				print '<td></td>';
			}
		}
	}
	print '</tr>';
}
