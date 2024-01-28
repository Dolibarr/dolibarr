<?php
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $conf, $langs;

?>

<main class="container">
		<div class="home-links-grid grid">
			<?php
			if (isModEnabled('propal') && getDolGlobalInt('WEBPORTAL_PROPAL_LIST_ACCESS')) : ?>
			<article class="home-links-card --propal-list">
				<div class="home-links-card__icon" ></div>
				<?php print '<a class="home-links-card__link" href="' . $context->getControllerUrl('propallist') . '" title="' . $langs->trans('WebPortalPropalListDesc') . '">' . $langs->trans('WebPortalPropalListTitle') . '</a>'; ?>
			</article>
			<?php endif; ?>
			<?php if (isModEnabled('commande') && getDolGlobalInt('WEBPORTAL_ORDER_LIST_ACCESS')) : ?>
			<article class="home-links-card --order-list">
				<div class="home-links-card__icon" ></div>
				<?php print '<a class="home-links-card__link" href="' . $context->getControllerUrl('orderlist') . '" title="' . $langs->trans('WebPortalOrderListDesc') . '">' . $langs->trans('WebPortalOrderListTitle') . '</a>'; ?>
			</article>
			<?php endif; ?>
			<?php if (isModEnabled('facture') && getDolGlobalInt('WEBPORTAL_INVOICE_LIST_ACCESS')) : ?>
			<article class="home-links-card --invoice-list">
				<div class="home-links-card__icon" ></div>
				<?php print '<a class="home-links-card__link" href="' . $context->getControllerUrl('invoicelist') . '" title="' . $langs->trans('WebPortalInvoiceListDesc') . '">' . $langs->trans('WebPortalInvoiceListTitle') . '</a>'; ?>
			</article>
			<?php endif; ?>
		</div>
</main>
