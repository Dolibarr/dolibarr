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
'@phan-var-force Controller $this';

/**
 * @var Conf				$conf
 * @var HookManager			$hookmanager
 * @var Translate			$langs
 * @var Context				$context
 * @var Controller 			$this
 * @var FormCardWebPortal 	$formCard
 */
$formCard = $this->formCard;

?>

<footer>
</footer>
