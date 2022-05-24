<?php
// Move fields of totalizable into the common array pos and val
if (!empty($totalarray['totalizable']) && is_array($totalarray['totalizable'])) {
	foreach ($totalarray['totalizable'] as $keytotalizable => $valtotalizable) {
		$totalarray['pos'][$valtotalizable['pos']] = $keytotalizable;
		$totalarray['val'][$keytotalizable] = $valtotalizable['total'];
	}
}
// Show total line
if (isset($totalarray['pos'])) {
	print '<tr class="liste_total">';
	$i = 0;
	while ($i < $totalarray['nbfield']) {
		$i++;
		if (!empty($totalarray['pos'][$i])) {
			print '<td class="right">';
			if (isset($totalarray['type']) && $totalarray['type'][$i] == 'duration') {
				print (!empty($totalarray['val'][$totalarray['pos'][$i]])?convertSecondToTime($totalarray['val'][$totalarray['pos'][$i]], 'allhourmin'):0);
			} else {
				print price(!empty($totalarray['val'][$totalarray['pos'][$i]])?$totalarray['val'][$totalarray['pos'][$i]]:0);
			}
			print '</td>';
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
