<?php
// Protection to avoid direct call of template
if (empty($context) || ! is_object($context)) {
	print "Error, template page can't be called as URL";
	exit;
}

global $conf, $hookmanager, $langs;

$Tmenu=$TGroupMenu=array();

$maxTopMenu = 0;

if ($context->userIsLog())
{
    // menu propal
    if (isModEnabled('propal') && getDolGlobalInt('WEBPORTAL_PROPAL_LIST_ACCESS')) {
        $Tmenu['propal_list'] = array(
            'id' => 'propal_list',
            'rank' => 10,
            'url' => $context->getControllerUrl('propallist'),
            'name' => $langs->trans('WebPortalPropalListMenu'),
            'group' => 'administrative' // group identifier for the group if necessary
        );
    }

    // menu orders
	if (isModEnabled('commande') && getDolGlobalInt('WEBPORTAL_ORDER_LIST_ACCESS')) {
		$Tmenu['order_list'] = array(
			'id' => 'order_list',
			'rank' => 20,
            'url' => $context->getControllerUrl('orderlist'),
			'name' => $langs->trans('WebPortalOrderListMenu'),
			'group' => 'administrative' // group identifier for the group if necessary
		);
	}

    // menu invoices
    if (isModEnabled('facture') && getDolGlobalInt('WEBPORTAL_INVOICE_LIST_ACCESS')) {
        $Tmenu['invoice_list'] = array(
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
        $Tmenu['member_card'] = array(
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
        $Tmenu['partnership_card'] = array(
            'id' => 'partnership_card',
            'rank' => 120,
            'url' => $context->getControllerUrl('partnershipcard'),
            'name' => $langs->trans('WebPortalPartnershipCardMenu'),
            'group' => 'administrative' // group identifier for the group if necessary
        );
    }

    // menu user with logout
    $Tmenu['user_logout'] = array(
        'id' => 'user_logout',
        'rank' => 200,
        'url' => $context->getControllerUrl().'logout.php',
        'name' => $langs->trans('Logout'),
    );

}

// GROUP MENU
$TGroupMenu = array(
	'administrative' => array(
		'id' => 'administrative',
		'rank' => -1, // negative value for undefined, it will be set by the min item rank for this group
		'url' => '',
		'name' => $langs->trans('GroupMenuAdministrative'),
		'children' => array()
	),
	'technical' => array(
		'id' => 'technical',
		'rank' => -1, // negative value for undefined, it will be set by the min item rank for this group
		'url' => '',
		'name' => $langs->trans('GroupMenuTechnical'),
		'children' => array()
	),
);

$parameters=array(
    'controller' => $context->controller,
    'Tmenu' =>& $Tmenu,
    'TGroupMenu' =>& $TGroupMenu,
	'maxTopMenu' =>& $maxTopMenu
);

$reshook=$hookmanager->executeHooks('PrintTopMenu', $parameters, $context, $context->action);    // Note that $action and $object may have been modified by hook
if ($reshook < 0) $context->setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)){
    if (!empty($hookmanager->resArray)){
        $Tmenu = array_replace($Tmenu, $hookmanager->resArray);
    }

    if (!empty($Tmenu)){
		// Sorting
		uasort($Tmenu, 'menuSortInv');

		if (!empty($maxTopMenu) && $maxTopMenu < count($Tmenu)){
			// AFFECT MENU ITEMS TO GROUPS
			foreach ($Tmenu as $menuId => $menuItem){
				// affectation des items de menu au groupement
				if (!empty($menuItem['group']) && !empty($TGroupMenu[$menuItem['group']])){
					$goupId = $menuItem['group'];

					// Affectation de l'item au groupe
					$TGroupMenu[$goupId]['children'][$menuId] = $menuItem;

					// Application du rang
					if (!empty($TGroupMenu[$goupId]['rank']) && $TGroupMenu[$goupId]['rank']>0){
						// le rang mini des items du groupe défini le rang du groupe
						$TGroupMenu[$goupId]['rank'] = min(abs($TGroupMenu[$goupId]['rank']), abs($menuItem['rank']));
					}
				}
			}

			// INSERTION DES GROUPES DANS LE MENU
			foreach ($TGroupMenu as $groupId => $groupItem){
				// If group have more than 1 item, group is valid
				if (!empty($groupItem['children']) && count($groupItem['children']) > 1){
					// ajout du group au menu
					$Tmenu[$groupId] = $groupItem;

					// suppression des items enfant du group du menu
					foreach ($groupItem['children'] as $menuId => $menuItem){
						if (isset($Tmenu[$menuId])){ unset($Tmenu[$menuId]); }
					}
				}
			}

			// final sorting
			uasort($Tmenu, 'menuSortInv');
		}
    }
}
?>
<nav class="container-fluid">
    <ul>
        <li class="brand">
            <img src="./tpl/dolibarr_logo.svg" >
        </li>
        <li>
            <strong>
                <?php
                if (!empty($context->title)) {
                    print $context->title;
                }
                ?>
            </strong>
        </li>
    </ul>
    <ul>
        <?php
        if (empty($context->doNotDisplayMenu) && empty($reshook) && !empty($Tmenu)){
            // show menu
            print getNav($Tmenu);
        }
        ?>
    </ul>
</nav>