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
	if (!(is_null($limit) || $num < $limit)) { // we print grand total only if different of page total already printed above
		/*$totalarray ex. Array (
		[nbfield] => 8
		[val] => Array
			(
				[p.budget_amount] => 1094013.07
			)

		[pos] => Array
			(
				[6] => p.budget_amount
			)
		)*/
		if (isset($totalarray['pos']) && is_array($totalarray['pos']) && count($totalarray['pos']) > 0) {
			$tbsumfields = [];
			foreach ($totalarray['pos'] as $field) {
				$tbsumfields[] = "sum($field) as `$field`";
			}
			if (isset($sqlfields)) { // In project list, this var is defined
				$sqlforgrandtotal = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT '. implode(",", $tbsumfields), $sql);
			} else { // copied from $sqlforcount in facture list
				$sqlforgrandtotal = preg_replace('/^SELECT[a-zA-Z0-9\._\s\(\),=<>\:\-\']+\sFROM/', 'SELECT '. implode(",", $tbsumfields). ' FROM ', $sql);
			}
			$sqlforgrandtotal = preg_replace('/GROUP BY .*$/', '', $sqlforgrandtotal). '';
			//echo $sqlforgrandtotal;
			$resql = $db->query($sqlforgrandtotal);
			if ($resql) {
				$sumsarray = $db->fetch_array($resql);
			} else {
				//dol_print_error($db); // as we're not sure it's ok for ALL listings, we don't print sql errors, they'll be in logs
			}
			if (is_array($sumsarray) && count($sumsarray) >0) {
				// Show grand total line
				print '<tr class="liste_grandtotal">';
				$i = 0;
				while ($i < $totalarray['nbfield']) {
					$i++;
					if (!empty($totalarray['pos'][$i])) {
						print '<td class="right">';
						if (isset($totalarray['type']) && $totalarray['type'][$i] == 'duration') {
							print (!empty($sumsarray[$totalarray['pos'][$i]]) ? convertSecondToTime($sumsarray[$totalarray['pos'][$i]], 'allhourmin') : 0);
						} else {
							print price(!empty($sumsarray[$totalarray['pos'][$i]]) ? $sumsarray[$totalarray['pos'][$i]] : 0);
						}
						print '</td>';
					} else {
						if ($i == 1) {
							print '<td>';
							if (is_object($form)) {
								print $form->textwithpicto($langs->trans("GrandTotal"), $langs->transnoentitiesnoconv("TotalforAllPages"));
							} else {
								print $langs->trans("GrandTotal");
							}
							print '</td>';
						} else {
							print '<td></td>';
						}
					}
				}
				print '</tr>';
			}
		}
	}
}
