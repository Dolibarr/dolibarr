<!-- file card-edit.tpl.php -->
<?php
/* Copyright (C) 2025		Open-Dsi							<support@open-dsi.fr>
 */
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
'@phan-var-force Context $context';
'@phan-var-force AbstractCardController $this';

/**
 * @var Conf					$conf
 * @var HookManager				$hookmanager
 * @var Translate				$langs
 * @var Context					$context
 * @var AbstractCardController 	$this
 * @var FormCardWebPortal 		$formCard
 */
$formCard = $this->formCard;

?>

<form method="POST" action="<?php print $context->getControllerUrl($context->controller, [ "id" => $formCard->object->id ], false) ?>">
	<?php print $context->getFormToken() ?>
	<input type="hidden" name="action" value="update">
	<?php if ($formCard->backtopage) { ?>
		<input type="hidden" name="backtopage" value="<?php print dolPrintHTMLForAttribute($formCard->backtopage) ?>">
	<?php }
	if ($formCard->backtopageforcancel) { ?>
		<input type="hidden" name="backtopageforcancel" value="<?php print dolPrintHTMLForAttribute($formCard->backtopageforcancel) ?>">
	<?php } ?>

	<article>
		<?php $this->loadTemplate('card-edit-header') ?>

		<?php $this->loadTemplate('card-edit-properties') ?>

		<?php $this->loadTemplate('card-edit-lines') ?>
	</article>

	<?php $this->loadTemplate('card-edit-actions-buttons') ?>

	<?php $this->loadTemplate('card-edit-footer') ?>
</form>
