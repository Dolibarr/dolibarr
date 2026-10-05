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
 * @var mixed 					$vars  TPL vars
 */
$formCard = $this->formCard;

?>

<header class="object-card-view" data-element="<?php print dolPrintHTMLForAttribute($formCard->object->element); ?>" >
	<div class="header-card-block">

		<?php
		// TODO: CODE QUALITY: Avoid defining object-specific logic directly inside the template.
		//  If object-specific handling is required, create a dedicated template file or pass the necessary variables in $vars so the template remains generic.
		//  also you can create a getBannerAddressForWebPortal method into object (who extend Dolibarr object) but its not a good way
		if ($formCard->object->element == 'member') {
			print '<div class="header-card-photo">';
			print $formCard->form->showphoto('memberphoto', $formCard->object, 0, 0, 0, 'photowithmargin photoref', 'small', 1, 0);
			print '</div>';
		}
		?>

		<div class="header-card-main-information">
			<?php if (!empty($formCard->object->ref)) : ?>
			<div class="header-card-ref"><?php print $langs->trans("Ref") . ' : ' . dol_escape_htmltag($formCard->object->ref) ?></div>
			<?php endif; ?>

			<?php
			// TODO: CODE QUALITY: Avoid defining object-specific logic directly inside the template.
			//  If object-specific handling is required, create a dedicated template file or pass the necessary variables so the template remains generic.
			//  also you can create a getBannerAddressForWebPortal method into webportal object (who extend Dolibarr object) but its not a good way

			if ($formCard->object->element == 'member') {
				$object = $formCard->object;
				'@phan-var-force Adherent $object';
				/**
				 * @var Adherent	$object
				 */
				$addgendertxt = '';
				//if (property_exists($object, 'gender') && !empty($object->gender)) {
				//    switch ($object->gender) {
				//        case 'man':
				//            $addgendertxt .= '<i class="fas fa-mars"></i>';
				//            break;
				//        case 'woman':
				//            $addgendertxt .= '<i class="fas fa-venus"></i>';
				//            break;
				//        case 'other':
				//            $addgendertxt .= '<i class="fas fa-transgender"></i>';
				//            break;
				//    }
				//}
				$fullname = '';
				if (method_exists($object, 'getFullName')) {
					$fullname = $object->getFullName($langs);
				}
				if ($object->morphy == 'mor' && !empty($object->company)) {
					$out = dol_htmlentities($object->company);
					$out .= (!empty($fullname) && $object->company != $fullname) ? ' (' . dol_htmlentities($fullname) . $addgendertxt . ')' : '';
				} else {
					$out = dol_htmlentities($fullname) . $addgendertxt;
					if (empty($object->socid)) {
						$out .= (!empty($object->company) && $object->company != $fullname) ? ' (' . dol_htmlentities($object->company) . ')' : '';
					}
				} ?>
				<div class="header-card-company" ><?php print $out ?></div>
			<?php } ?>

			<?php
			// TODO : use TPL $vars not the object, there is a controller so use it instead to pass $vars.
			if (method_exists($formCard->object, 'getBannerAddressForWebPortal')) {
				$moreaddress = $formCard->object->getBannerAddressForWebPortal('refaddress');
				if ($moreaddress) { ?>
					<div class="header-card-address"><?php print $moreaddress ?></div>
				<?php }
			}
			?>
		</div>

		<div class="header-card-status" >
			<?php
			$htmlStatus = $formCard->object->getLibStatut(6);
			if (empty($htmlStatus) || $htmlStatus == $formCard->object->getLibStatut(3)) {
				$htmlStatus = $formCard->object->getLibStatut(5);
			}
			print $htmlStatus;
			?>
		</div>
	</div>


</header>
