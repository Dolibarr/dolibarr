<!-- file list.tpl.php -->
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

?>

<form method="POST" id="searchFormList" action="<?php print $context->getControllerUrl($context->controller, '', false) ?>">
	<?php print $context->getFormToken() ?>
	<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">
	<input type="hidden" name="action" value="list">
	<input type="hidden" name="sortfield" value="<?php print dolPrintHTMLForAttribute($formList->sortfield) ?>">
	<input type="hidden" name="sortorder" value="<?php print dolPrintHTMLForAttribute($formList->sortorder) ?>">
	<input type="hidden" name="contextpage" value="<?php print dolPrintHTMLForAttribute($formList->contextpage) ?>">

	<?php $this->loadTemplate('list-nav') ?>

	<?php $this->loadTemplate('list-additional-filters') ?>

	<table id="webportal-<?php print dolPrintHTMLForAttribute($formList->object->element) ?>-list" responsive="scroll" role="grid">
		<thead>
			<?php $this->loadTemplate('list-filters') ?>

			<?php $this->loadTemplate('list-titles') ?>
		</thead>

		<tbody>
			<?php $this->loadTemplate('list-lines') ?>
		</tbody>

		<tfoot>
			<?php $this->loadTemplate('list-footer') ?>
		</tfoot>
	</table>
</form>
