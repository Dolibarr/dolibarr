<!-- file home.tpl.php -->
<?php
/* Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026       charlene Benke     		<charlene@patas-monkey.com>
 */
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
'@phan-var-force Context $context';
/**
 * @var Context $context
 * @var Translate $langs
 */

if ($context->userIsLog()) {
	// menu propal
	if (isModEnabled('propal') && getDolGlobalInt('WEBPORTAL_PROPAL_LIST_ACCESS')) {
		$navMenu['propal_list'] = array(
			'id' => 'propal_list',
			'rank' => 10,
			'url' => $context->getControllerUrl('propallist'),
			'name' => $langs->trans('WebPortalPropalListMenu'),
			'group' => 'administrative' // group identifier for the group if necessary
		);
	}
	// menu orders
	if (isModEnabled('order') && getDolGlobalInt('WEBPORTAL_ORDER_LIST_ACCESS')) {
		$navMenu['order_list'] = array(
			'id' => 'order_list',
			'rank' => 20,
			'url' => $context->getControllerUrl('orderlist'),
			'name' => $langs->trans('WebPortalOrderListMenu'),
			'group' => 'administrative' // group identifier for the group if necessary
		);
	}
	// menu invoices
	if (isModEnabled('invoice') && getDolGlobalInt('WEBPORTAL_INVOICE_LIST_ACCESS')) {
		$navMenu['invoice_list'] = array(
			'id' => 'invoice_list',
			'rank' => 30,
			'url' => $context->getControllerUrl('invoicelist'),
			'name' => $langs->trans('WebPortalInvoiceListMenu'),
			'group' => 'administrative' // group identifier for the group if necessary
		);
	}

	// menu documents (GED)
	if (getDolGlobalInt('WEBPORTAL_DOCUMENT_LIST_ACCESS')) {
		$navMenu['document_list'] = array(
			'id' => 'document_list',
			'rank' => 40,
			'url' => $context->getControllerUrl('documentlist'),
			'name' => $langs->trans('MyDocuments'), // CORRIGÉ : Clé de traduction correcte
			'group' => 'administrative' // group identifier for the group if necessary
		);
	}
	// Shared documents menu
	if (getDolGlobalInt('WEBPORTAL_SHARED_DOCUMENT_ACCESS')) {
		$navMenu['shared_documents'] = array(
			'id' => 'shared_documents',
			'rank' => 50,
			'url' => $context->getControllerUrl('shareddocuments'),
			'name' => $langs->trans('SharedDocuments'),
			'group' => 'administrative'
		);
	}
	// menu member
	$cardAccess = getDolGlobalString('WEBPORTAL_MEMBER_CARD_ACCESS');
	if (isModEnabled('member')
		&& in_array($cardAccess, array('visible', 'edit'))
		&& $context->logged_member
		&& $context->logged_member->id > 0
	) {
		$navMenu['member_card'] = array(
			'id' => 'member_card',
			'rank' => 110,
			'url' => $context->getControllerUrl('membercard'),
			'name' => $langs->trans('WebPortalMemberCardMenu'),
			'group' => 'administrative' // group identifier for the group if necessary
		);
	}
	// menu partnership
	$cardAccess = getDolGlobalString('WEBPORTAL_PARTNERSHIP_CARD_ACCESS');
	if (isModEnabled('partnership')
		&& in_array($cardAccess, array('visible', 'edit'))
		&& $context->logged_partnership
		&& $context->logged_partnership->id > 0
	) {
		$navMenu['partnership_card'] = array(
			'id' => 'partnership_card',
			'rank' => 120,
			'url' => $context->getControllerUrl('partnershipcard'),
			'name' => $langs->trans('WebPortalPartnershipCardMenu'),
			'group' => 'administrative' // group identifier for the group if necessary
		);
	}
}

$parameters = array(
	'controller' => $context->controller,
	'Tmenu' => & $navMenu,
	'TUserMenu' => & $navUserMenu,
	'TGroupMenu' => & $navGroupMenu,
	'maxTopMenu' => & $maxTopMenu
);

$reshook = $hookmanager->executeHooks('PrintTopMenu', $parameters, $context, $context->action);    // Note that $action and $object may have been modified by hook
if ($reshook < 0) {
	$context->setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	if (!empty($hookmanager->resArray)) {
		// @phan-suppress-next-line PhanPluginSuspiciousParamOrderInternal
		$navMenu = array_replace($navMenu, $hookmanager->resArray);
	}

	if (!empty($navMenu)) {
		// Sorting
		uasort($navMenu, 'menuSortInv');

		if (!empty($maxTopMenu) && $maxTopMenu < count($navMenu)) {
			// AFFECT MENU ITEMS TO GROUPS
			foreach ($navMenu as $menuId => $menuItem) {
				// assign items to group menu
				if (!empty($menuItem['group']) && !empty($navGroupMenu[$menuItem['group']])) {
					$goupId = $menuItem['group'];

					// set item to group
					$navGroupMenu[$goupId]['children'][$menuId] = $menuItem;

					// apply rank
					if (!empty($navGroupMenu[$goupId]['rank']) && $navGroupMenu[$goupId]['rank'] > 0) {
						// minimum rank of group determine rank of group
						$navGroupMenu[$goupId]['rank'] = min(abs($navGroupMenu[$goupId]['rank']), abs($menuItem['rank'])); // @phpstan-ignore-line
					}
				}
			}
			// add grouped items to this menu
			foreach ($navGroupMenu as $groupId => $groupItem) {
				// If group have more than 1 item, group is valid
				if (!empty($groupItem['children']) && count($groupItem['children']) > 1) {
					// ajout du group au menu
					$navMenu[$groupId] = $groupItem;

					// suppression des items enfant du group du menu
					foreach ($groupItem['children'] as $menuId => $menuItem) {
						if (isset($navMenu[$menuId])) {
							unset($navMenu[$menuId]);
						}
					}
				}
			}

			// final sorting
			uasort($navMenu, 'menuSortInv');
		}
	}
}
print '<main class="container">';
print '<div class="home-links-grid grid">';
foreach ($navMenu as $menuId => $menuItem) {
	print '<article class="home-links-card --' . $menuId . '">';
	print '<div class="home-links-card__icon" ></div>';
	print '<a class="home-links-card__link" href="' . $menuItem['url'] . '" title="' . $menuItem['name'] . '">' . $menuItem['name'] . '</a>';
	print '</article>';
}
print '</div></main>';
