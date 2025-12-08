<!-- file list-titles.tpl.php -->
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

$url = $context->getControllerUrl($context->controller);
$url .= (preg_match('/\?/', $url) ? '&amp;' : '?') . preg_replace('/^(&|&amp;)/i', '', $formList->params/* . "&amp;page=" . urlencode($formList->page)*/);

// Make array[sort field => sort order] for this list
$sortList = array_combine(explode(",", $formList->sortfield), explode(",", $formList->sortorder));

$formList->nbColumn = 0;

?>

<!-- Fields title label -->
<tr>
	<th data-col="row-checkbox"></th>
	<?php $formList->nbColumn++ ?>

	<?php foreach ($formList->object->fields as $key => $val) {
		$alias = $val['alias'] ?? 't.';
		if (array_key_exists($alias . $key, $formList->arrayfields) && !empty($formList->arrayfields[$alias . $key]['checked'])) {
			$order = array_key_exists($alias . $key, $sortList) ? strtolower(trim($sortList[$alias . $key])) : '';
			$link_url = $url . '&amp;sortfield=' . urlencode($alias . $key) . '&amp;sortorder=' . ($order == 'desc' ? 'asc' : 'desc');
			$cssforfield = $formList->getClasseCssList($key, $val);
			$cssforfield = preg_replace('/small\s*/', '', $cssforfield);
			?>
			<th <?php print empty($cssforfield) ? '' : 'class="' . dolPrintHTMLForAttribute($cssforfield) . '" ' ?>data-col="<?php print dolPrintHTMLForAttribute((string) $key) ?>" scope="col"<?php print (!empty($order) ? ' table-order="' . dolPrintHTMLForAttribute($order) . '"' : '') ?>>
				<a href="<?php print dolPrintHTMLForAttribute($link_url) ?>"><?php print $langs->trans((string) $formList->arrayfields[$alias . $key]['label']) ?></a>
			</th>
			<?php $formList->nbColumn++;
		}
	}

	// Hook fields
	$parameters = array('sortList' => $sortList);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $context);
	print $hookmanager->resPrint;

	// Remain to pay
	if (array_key_exists('remain_to_pay', $formList->arrayfields) && !empty($formList->arrayfields['remain_to_pay']['checked'])) { ?>
		<th scope="col"><?php print  $langs->trans((string) $formList->arrayfields['remain_to_pay']['label']) ?></th>
		<?php $formList->nbColumn++;
	}

	// Download link
	if (array_key_exists('download_link', $formList->arrayfields) && !empty($formList->arrayfields['download_link']['checked'])) { ?>
		<th scope="col"><?php print  $langs->trans((string) $formList->arrayfields['download_link']['label']) ?></th>
		<?php $formList->nbColumn++;
	}

	// Signature link
	if (array_key_exists('signature_link', $formList->arrayfields) && !empty($formList->arrayfields['signature_link']['checked'])) { ?>
		<th scope="col"><?php print  $langs->trans((string) $formList->arrayfields['signature_link']['label']) ?></th>
		<?php $formList->nbColumn++;
	} ?>
</tr>
