<?php
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $conf, $langs;
?>
<main class="container">
	<figure>
		<div class="grid">
			<?php
			if (isModEnabled('propal') && getDolGlobalInt('WEBPORTAL_PROPAL_LIST_ACCESS')) {
				?>
				<div><?php print '<a href="' . $context->getControllerUrl('propallist') . '" title="' . $langs->trans('WebPortalPropalListDesc') . '">' . $langs->trans('WebPortalPropalListTitle') . '</a>'; ?></div>
				<?php
			}
			?>
			<?php
			if (isModEnabled('commande') && getDolGlobalInt('WEBPORTAL_ORDER_LIST_ACCESS')) {
				?>
				<div><?php print '<a href="' . $context->getControllerUrl('orderlist') . '" title="' . $langs->trans('WebPortalOrderListDesc') . '">' . $langs->trans('WebPortalOrderListTitle') . '</a>'; ?></div>
				<?php
			}
			?>
			<?php
			if (isModEnabled('facture') && getDolGlobalInt('WEBPORTAL_INVOICE_LIST_ACCESS')) {
				?>
				<div><?php print '<a href="' . $context->getControllerUrl('invoicelist') . '" title="' . $langs->trans('WebPortalInvoiceListDesc') . '">' . $langs->trans('WebPortalInvoiceListTitle') . '</a>'; ?></div>
				<?php
			}
			?>
		</div>
	</figure>
</main>
