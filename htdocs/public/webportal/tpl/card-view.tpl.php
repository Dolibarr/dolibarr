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


$formconfirm = '';

// Call Hook formConfirm
$parameters = array('formConfirm' => $formconfirm);
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $context);
if (empty($reshook)) {
	$formconfirm .= $hookmanager->resPrint;
} elseif ($reshook > 0) {
	$formconfirm = $hookmanager->resPrint;
}

print $formconfirm;

?>

<article>
	<?php $this->loadTemplate('card-view-header') ?>

	<?php $this->loadTemplate('card-view-properties') ?>

	<?php $this->loadTemplate('card-view-lines') ?>
</article>

<?php $this->loadTemplate('card-view-actions-buttons') ?>

<?php $this->loadTemplate('card-view-footer') ?>
