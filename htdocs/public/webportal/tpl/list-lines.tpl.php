<!-- file list-lines.tpl.php -->
<?php
/* Copyright (C) 2025		Open-Dsi							<support@open-dsi.fr>
 */
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
'@phan-var-force Context $context';
'@phan-var-force AbstractListController $this';

/**
 * @var Conf					$conf
 * @var HookManager				$hookmanager
 * @var Translate				$langs
 * @var Context					$context
 * @var AbstractListController 	$this
 * @var FormListWebPortal 		$formList
 */
$formList = &$this->formList;

if (!empty($formList->records)) {
	$totalarray = [
		'nbfield' => 0,
		'totalizable' => [],
	];

	foreach ($formList->records as $i => $record) {
		// Store properties in $object
		$formList->setVarsFromFetchObj($record); ?>
		<tr data-rowid="<?php print dolPrintHTMLForAttribute((string) $formList->object->id) ?>">
			<td></td>
			<?php $formList->setTotalValue('', [], $record, $i, $totalarray) ?>

			<?php foreach ($formList->object->fields as $key => $val) {
				$alias = $val['alias'] ?? 't.';
				if (array_key_exists($alias . $key, $formList->arrayfields) && !empty($formList->arrayfields[$alias . $key]['checked'])) {
					$cssforfield = $formList->getClasseCssList($key, $val, true);
					if (preg_match('/tdoverflow/', $cssforfield)) $cssforfield .= ' classfortooltip';
					$title = '';
					if (preg_match('/tdoverflow/', $cssforfield) && !is_numeric($formList->object->$key)) {
						$title = ' title="' . dolPrintHTMLForAttribute((string) $formList->object->$key) . '"';
					}
					?>
					<td <?php print (empty($cssforfield) ? '' : 'class="' . dolPrintHTMLForAttribute($cssforfield) . '" '); print $title ?>data-label="<?php print dolPrintHTMLForAttribute((string) $formList->arrayfields[$alias . $key]['label']) ?>" data-col="<?php print dolPrintHTMLForAttribute((string) $key) ?>">
						<?php print $formList->printValue($key, $val, $record, $i, $totalarray);
						$formList->setTotalValue($key, $val, $record, $i, $totalarray) ?>
					</td>
				<?php }
			}

			// Fields from hook
			$parameters = array('record' => $record, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $context);
			print $hookmanager->resPrint;

			// Remain to pay
			if (array_key_exists('remain_to_pay', $formList->arrayfields) && !empty($formList->arrayfields['remain_to_pay']['checked'])) { ?>
				<td class="nowraponall" data-label="<?php print dolPrintHTMLForAttribute((string) $formList->arrayfields['remain_to_pay']['label']) ?>" data-col="remain_to_pay">
					<?php print $formList->printValue('remain_to_pay', [], $record, $i, $totalarray);
					$formList->setTotalValue('remain_to_pay', [], $record, $i, $totalarray) ?>
				</td>
			<?php }

			// Download link
			if (array_key_exists('download_link', $formList->arrayfields) && !empty($formList->arrayfields['download_link']['checked'])) { ?>
				<td data-label="<?php print dolPrintHTMLForAttribute((string) $formList->arrayfields['download_link']['label']) ?>" data-col="download_link">
					<?php print $formList->printValue('download_link', [], $record, $i, $totalarray);
					$formList->setTotalValue('download_link', [], $record, $i, $totalarray) ?>
				</td>
			<?php }

			// Signature link
			if (array_key_exists('signature_link', $formList->arrayfields) && !empty($formList->arrayfields['signature_link']['checked'])) { ?>
				<td data-label="<?php print dolPrintHTMLForAttribute((string) $formList->arrayfields['signature_link']['label']) ?>" data-col="signature_link">
					<?php print $formList->printValue('signature_link', [], $record, $i, $totalarray);
					$formList->setTotalValue('signature_link', [], $record, $i, $totalarray) ?>
				</td>
			<?php } ?>
		</tr>
	<?php }

	// Move fields of totalizable into the common array pos and val
	if (!empty($totalarray['totalizable']) && is_array($totalarray['totalizable'])) {
		foreach ($totalarray['totalizable'] as $keytotalizable => $valtotalizable) {
			$totalarray['pos'][$valtotalizable['pos']] = $keytotalizable;
			$totalarray['val'][$keytotalizable] = isset($valtotalizable['total']) ? $valtotalizable['total'] : 0;
		}
	}
	// Show total line
	if (isset($totalarray['pos'])) { ?>
		<tr>
		<?php $i = 0;
		while ($i < $totalarray['nbfield']) {
			$i++;
			if (!empty($totalarray['pos'][$i])) { ?>
				<td class="nowraponall essai">
					<?php print price(!empty($totalarray['val'][$totalarray['pos'][$i]]) ? $totalarray['val'][$totalarray['pos'][$i]] : 0) ?>
				</td>
			<?php } else {
				if ($i == 1) { ?>
					<td><?php print $langs->trans("Total") ?></td>
				<?php } else { ?>
					<td></td>
				<?php }
			}
		} ?>
		</tr>
	<?php }
} else { // If no record found ?>
<tr><td colspan="<?php print $formList->nbColumn ?>"><span class="opacitymedium"><?php print $langs->trans("NoRecordFound") ?></span></td></tr>
<?php } ?>
