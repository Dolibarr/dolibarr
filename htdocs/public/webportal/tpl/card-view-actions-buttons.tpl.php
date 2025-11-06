<!-- file card-view-actions-buttons.tpl.php -->
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

$url = $context->getControllerUrl($context->controller). '&id=' . $formCard->object->id;

?>

<?php if ($formCard->action != 'presend' && $formCard->action != 'editline') { ?>
<div id="actions_buttons">
	<?php
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $context);
	if ($reshook < 0) {
		$context->setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} elseif (empty($reshook)) {
		// Edit card
		if ($formCard->permissiontoadd) { ?>
			<a href="<?php print $url . "&action=edit" ?>" role="button"><?php print $langs->trans('Modify') ?></a>
		<?php }
	}
	?>
</div>
<?php } ?>
