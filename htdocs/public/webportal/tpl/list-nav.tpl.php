<!-- file list-nav.tpl.php -->
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

// Get nb pages
$nbPages = 0;
if ($formList->limit > 0) {
	$nbPages = ceil($formList->nbtotalofrecords / $formList->limit);
}
if ($nbPages <= 0) {
	$nbPages = 1;
}

$maxPaginationItem = min($nbPages, 5);
$minPageNum = max(1, $formList->page - 3);
$maxPageNum = min($nbPages, $formList->page + 3);

$params = $formList->params . '&amp;sortfield=' . $formList->sortfield . '&amp;sortorder=' . $formList->sortorder;
$params = preg_replace('/^(&|&amp;)/i', '', $params); // remove first & or &amp;
$url = $context->getControllerUrl($context->controller);
$url .= (preg_match('/\?/', $url) ? '&amp;' : '?') . $params;

?>

<input type="hidden" name="page" value="<?php print dolPrintHTMLForAttribute((string) $formList->page) ?>">
<nav id="webportal-<?php print dolPrintHTMLForAttribute($formList->object->element) ?>-pagination">
	<ul>
		<li><strong><?php print $langs->trans($formList->titleKey) ?></strong> (<?php print $formList->nbtotalofrecords ?>)</li>
	</ul>

	<?php if ($nbPages > 1) { ?>
	<ul class="pages-nav-list">
		<?php if ($formList->page > 1) { ?>
		<li><a class="pages-nav-list__icon --prev" aria-label="<?php print dolPrintHTMLForAttribute((string) $langs->trans('AriaPrevPage')) ?>" href="<?php print $url . '&amp;page=' . ($formList->page - 1) ?>"<?php // print ($formList->page <= 1 ? ' disabled' : '') ?>></a></li>
		<?php } ?>

		<?php if ($minPageNum > 1) { ?>
			<li><a class="pages-nav-list__link <?php print ($formList->page == 1 ? '--active' : '') ?>" aria-label="<?php print dolPrintHTMLForAttribute((string) $langs->trans('AriaPageX', 1)) ?>" href="<?php print $url . '&amp;page=1' ?>">1</a></li>
			<li>&hellip;</li>
		<?php } ?>

		<?php for ($p = $minPageNum; $p <= $maxPageNum; $p++) { ?>
			<li><a class="pages-nav-list__link <?php print ($formList->page === $p ? '--active' : '') ?>" aria-label="<?php print dolPrintHTMLForAttribute((string) $langs->trans('AriaPageX', $p)) ?>"  href="<?php print $url . '&amp;page=' . $p ?>"><?php print $p ?></a></li>
		<?php } ?>

		<?php if ($maxPaginationItem < $nbPages) { ?>
			<li>&hellip;</li>
			<li><a class="pages-nav-list__link <?php print ($formList->page == $nbPages ? '--active' : '') ?>" aria-label="<?php print dolPrintHTMLForAttribute((string) $langs->trans('AriaPageX', $nbPages)) ?>" href="<?php print $url . '&amp;page=' . $nbPages ?>"><?php print $nbPages ?></a></li>
		<?php } ?>

		<?php if ($formList->page < $nbPages) { ?>
			<li><a class="pages-nav-list__icon --next" aria-label="<?php print dolPrintHTMLForAttribute((string) $langs->trans('AriaNextPage')) ?>" href="<?php print $url . '&amp;page=' . ($formList->page + 1) ?>"<?php // print ($formList->page >= $nbPages ? ' disabled' : '') ?>></a></li>
		<?php } ?>
	</ul>
	<?php } ?>
</nav>
