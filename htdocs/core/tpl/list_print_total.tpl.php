<?php

'@phan-var-force array{nbfield:int,type?:array<int,string>,pos?:array<int,int>,val?:array<int,float>} $totalarray';

// Move fields of totalizable into the common array pos and val
if (!empty($totalarray['totalizable']) && is_array($totalarray['totalizable'])) {
	foreach ($totalarray['totalizable'] as $keytotalizable => $valtotalizable) {
		$totalarray['pos'][$valtotalizable['pos']] = $keytotalizable;
		$totalarray['val'][$keytotalizable] = isset($valtotalizable['total']) ? $valtotalizable['total'] : 0;
	}
}
// Show total line
if (isset($totalarray['pos'])) {
	print '<tfoot>';
	print '<tr class="liste_total">';
	$i = 0;
	while ($i < $totalarray['nbfield']) {
		$i++;
		if (!empty($totalarray['pos'][$i])) {
			printTotalValCell($totalarray['type'][$i] ?? '', empty($totalarray['val'][$totalarray['pos'][$i]]) ? 0 : $totalarray['val'][$totalarray['pos'][$i]]);
		} else {
			if ($i == 1) {
				if ((is_null($limit) || $num < $limit) && empty($offset)) {
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
	// Add grand total if necessary ie only if different of page total already printed above
	if (getDolGlobalString('MAIN_GRANDTOTAL_LIST_SHOW') && (!(is_null($limit) || $num < $limit))) {
		if (isset($totalarray['pos']) && is_array($totalarray['pos']) && count($totalarray['pos']) > 0) {
			$sumsarray = false;
			$tbsumfields = [];
			foreach ($totalarray['pos'] as $field) {
				$fieldforsum = preg_replace('/[^a-z0-9]/', '', $field);
				$tbsumfields[] = "sum($field) as $fieldforsum";
			}
			if (isset($sqlfields)) { // In project, commande list, this var is defined
				$sqlforgrandtotal = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT '. implode(",", $tbsumfields), $sql);
			} else {
				$sqlforgrandtotal = preg_replace('/^SELECT[a-zA-Z0-9\._\s\(\),=<>\:\-\']+\sFROM/', 'SELECT '. implode(",", $tbsumfields). ' FROM ', $sql);
			}
			$sqlforgrandtotal = preg_replace('/GROUP BY .*$/', '', $sqlforgrandtotal). '';
			$resql = $db->query($sqlforgrandtotal);
			if ($resql) {
				$sumsarray = $db->fetch_array($resql);
			} else {
				//dol_print_error($db); // as we're not sure it's ok for ALL lists, we don't print sq errors, they'll be in logs
			}
			if (is_array($sumsarray) && count($sumsarray) > 0) {
				print '<tr class="liste_grandtotal">';
				$i = 0;
				while ($i < $totalarray['nbfield']) {
					$i++;
					if (!empty($totalarray['pos'][$i])) {
						printTotalValCell($totalarray['type'][$i], $sumsarray[$totalarray['pos'][$i]]);
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
	print '</tfoot>';
}

/** print a total cell value according to its type
 *
 * @param string $type of field (duration, string..)
 * @param string $val the value to display
 *
 * @return void (direct print)
 */
function printTotalValCell($type, $val)
{
	// if $totalarray['type'] not present we consider it as number
	if (empty($type)) {
		$type = 'real';
	}
	switch ($type) {
		case 'duration':
			print '<td class="right">';
			print(!empty($val) ? convertSecondToTime($val, 'allhourmin') : 0);
			print '</td>';
			break;
		case 'string':	// This type is no more used. type is now varchar(x)
			print '<td class="left">';
			print(!empty($val) ? $val : '');
			print '</td>';
			break;
		case 'stock':
			print '<td class="right">';
			print price2num(!empty($val) ? $val : 0, 'MS');
			print '</td>';
			break;
		default:
			print '<td class="right">';
			print price(!empty($val) ? $val : 0);
			print '</td>';
			break;
	}
}
