<?php
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $conf, $hookmanager, $langs;

$navMenu = $navGroupMenu = $navUserMenu = array();

$maxTopMenu = 0;

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
	if (isModEnabled('commande') && getDolGlobalInt('WEBPORTAL_ORDER_LIST_ACCESS')) {
		$navMenu['order_list'] = array(
			'id' => 'order_list',
			'rank' => 20,
			'url' => $context->getControllerUrl('orderlist'),
			'name' => $langs->trans('WebPortalOrderListMenu'),
			'group' => 'administrative' // group identifier for the group if necessary
		);
	}

	// menu invoices
	if (isModEnabled('facture') && getDolGlobalInt('WEBPORTAL_INVOICE_LIST_ACCESS')) {
		$navMenu['invoice_list'] = array(
			'id' => 'invoice_list',
			'rank' => 30,
			'url' => $context->getControllerUrl('invoicelist'),
			'name' => $langs->trans('WebPortalInvoiceListMenu'),
			'group' => 'administrative' // group identifier for the group if necessary
		);
	}

	// menu member
	$cardAccess = getDolGlobalString('WEBPORTAL_MEMBER_CARD_ACCESS');
	if (isModEnabled('adherent')
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

	// menu user with logout
	$navUserMenu['user_logout'] = array(
		'id' => 'user_logout',
		'rank' => 99999,
		'url' => $context->getControllerUrl() . 'logout.php',
		'name' => $langs->trans('Logout'),
	);
}

// GROUP MENU
$navGroupMenu = array(
	'administrative' => array(
		'id' => 'administrative',
		'rank' => -1, // negative value for undefined, it will be set by the min item rank for this group
		'url' => '',
		'name' => $langs->trans('WebPortalGroupMenuAdmin'),
		'children' => array()
	),
	'technical' => array(
		'id' => 'technical',
		'rank' => -1, // negative value for undefined, it will be set by the min item rank for this group
		'url' => '',
		'name' => $langs->trans('WebPortalGroupMenuTechnical'),
		'children' => array()
	),
);

$parameters = array(
	'controller' => $context->controller,
	'Tmenu' => & $navMenu,
	'TGroupMenu' => & $navGroupMenu,
	'maxTopMenu' => & $maxTopMenu
);

$reshook = $hookmanager->executeHooks('PrintTopMenu', $parameters, $context, $context->action);    // Note that $action and $object may have been modified by hook
if ($reshook < 0) $context->setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if (!empty($hookmanager->resArray)) {
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
						$navGroupMenu[$goupId]['rank'] = min(abs($navGroupMenu[$goupId]['rank']), abs($menuItem['rank']));
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
?>
<nav class="primary-top-nav container-fluid">
	<ul>
		<li class="brand">
		<?php
		$brandTitle = getDolGlobalString('WEBPORTAL_TITLE') ? getDolGlobalString('WEBPORTAL_TITLE') : getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
		print '<a class="brand__logo-link"  href="'.$context->getControllerUrl().'" >';
		if (!empty($context->theme->menuLogoUrl)) {
			print '<img class="brand__logo-img" src="' . dol_escape_htmltag($context->theme->menuLogoUrl) . '" alt="' . dol_escape_htmltag($brandTitle) . '" >';
		} else {
			print '<span class="brand__name">' . $brandTitle . '</span>';
		}
		print '</a>';
		?>
		</li>
	</ul>
	<ul>
	<?php
	if (empty($context->doNotDisplayMenu) && empty($reshook) && !empty($navMenu)) {
		// show menu
		print getNav($navMenu);
	}
	?>
	</ul>
	<ul>
	<?php
	if (empty($context->doNotDisplayMenu) && empty($reshook) && !empty($navUserMenu)) {
		// show menu
		uasort($navUserMenu, 'menuSortInv');
		print getNav($navUserMenu);
	}
	?>
	</ul>
</nav>
