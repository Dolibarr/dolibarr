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

?>

<header>
	<div class="header-card-left-block inline-block" style="width: 75%;">
		<div>
			<div class="inline-block floatleft valignmiddle">
				<div class="floatleft inline-block valignmiddle divphotoref">
					<?php
					if ($formCard->object->element == 'member') {
						print $formCard->form->showphoto('memberphoto', $formCard->object, 0, 0, 0, 'photowithmargin photoref', 'small', 1, 0);
					}
					?>
				</div>
			</div>

			<div class="header-card-main-information inline-block valignmiddle">
				<div><strong><?php print $langs->trans("Ref") . ' : ' . dol_escape_htmltag($formCard->object->ref) ?></strong></div>

				<?php if ($formCard->object->element == 'member') {
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
					<div><strong><?php print $out ?></strong></div>
				<?php } ?>

				<?php
				if (method_exists($formCard->object, 'getBannerAddressForWebPortal')) {
					$moreaddress = $formCard->object->getBannerAddressForWebPortal('refaddress');
					if ($moreaddress) { ?>
						<div class="refidno refaddress"><?php print $moreaddress ?></div>
					<?php }
				}
				?>
			</div>
		</div>
	</div>

	<div class="header-card-right-block inline-block" style="width: 24%;">
		<?php
		$htmlStatus = $formCard->object->getLibStatut(6);
		if (empty($htmlStatus) || $htmlStatus == $formCard->object->getLibStatut(3)) {
			$htmlStatus = $formCard->object->getLibStatut(5);
		}
		print $htmlStatus;
		?>
	</div>
</header>
