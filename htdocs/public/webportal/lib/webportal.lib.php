<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */

/**
 * \file        htdocs/public/webportal/lib/webportal.lib.php
 * \ingroup     webportal
 * \brief       Lib for public access of WebPortal
 */

/**
 * Get nav menu
 *
 * @param	array<string,array{id:string,rank:int,url:string,name:string,group:string,override?:int<0,1>,children?:array<array{id:string,rank:int,url:string,name:string,group:string,override?:int<0,1>}>}>	$Tmenu	Array of menu
 * @return  string
 */
function getNav($Tmenu)
{
	$menu = '';

	foreach ($Tmenu as $item) {
		$menu .= getNavItem($item);
	}

	return $menu;
}

/**
 * Get nav item
 *
 * TODO : Dropdown is actually not css implemented
 * @param	array{id:string,rank:int,url:string,name:string,group:string,override?:int<0,1>,children?:array<array{id:string,rank:int,url:string,name:string,group:string,override?:int<0,1>,active?:bool,separator?:bool}>}	$item	Item of menu
 * @param	int		$deep	Level of deep
 * @return  string
 */
function getNavItem($item, $deep = 0)
{
	$context = Context::getInstance();

	$menu = '';

	$itemDefault = array(
		'active' => false,
		'separator' => false,
	);

	$item = array_replace($itemDefault, $item); // applique les valeurs par default

	if ($context->menuIsActive($item['id'])) {
		$item['active'] = true;
	}

	if (!isset($item['class'])) {
		$item['class'] = '--item-' . preg_replace('/[^a-z0-9 ]/i', '-', $item['id']);
	}


	if (!empty($item['override'])) {
		$menu .= $item['override'];
	} elseif (!empty($item['children'])) {
		$menuChildren = '';
		$haveChildActive = false;

		foreach ($item['children'] as $child) {
			$item = array_replace($itemDefault, $item); // applique les valeurs par default
			'@phan-var-force array{id:string,rank:int,url:string,name:string,group:string,override?:int<0,1>,active?:bool,separator?:bool}	$child';

			if (!empty($child['separator'])) {
				$menuChildren .= '<li role="separator" class="divider"></li>';
			}

			if ($context->menuIsActive($child['id'])) {
				$child['active'] = true;
				$haveChildActive = true;
			}

			if (!empty($child['children'])) {
				$menuChildren .= "\n\r" . '<!-- print sub menu -->' . "\n\r";
				$menuChildren .= getNavItem($child, $deep + 1);
				$menuChildren .= "\n\r" . '<!-- print sub menu -->' . "\n\r";
			} else {
				$menuChildren .= '<li class="dropdown-item '.$item['class'].'" data-deep="' . $deep . '" ><a href="' . $child['url'] . '" class="' . (!empty($child['active']) ? 'active' : '') . '" ">' . $child['name'] . '</a></li>';
			}
		}

		$active = '';
		if ($haveChildActive || $item['active']) {
			$active = 'active';
		}

		$menu .= '<li data-deep="' . $deep . '" class="'.$item['class'].' dropdown ' . ($deep > 0 ? 'dropdown-item dropdown-submenu' : 'nav-item') . '  ' . $active . '">';
		$menu .= '<a href="#" class="' . ($deep > 0 ? '' : 'nav-link') . ' dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">' . $item['name'] . ' <span class="caret"></span></a>';
		$menu .= '<ul class="dropdown-menu ">' . $menuChildren . '</ul>';
		$menu .= '</li>';
	} else {
		$menu .= '<li data-deep="' . $deep . '" class="'.$item['class'].' ' . ($deep > 0 ? 'dropdown-item' : 'nav-item ') . ' ' . ($item['active'] ? 'active' : '') . '"><a  href="' . $item['url'] . '" class="' . ($deep > 0 ? '' : 'nav-link') . '" >' . $item['name'] . '</a></li>';
	}

	return $menu;
}

/**
 * Sort menu
 * uasort callback function to Sort menu fields
 *
 * @param	array{rank?:int} $a	PDF lines array fields configs
 * @param 	array{rank?:int} $b	PDF lines array fields configs
 * @return 	int<-1,1>           Return compare result
 *
 * 	// Sorting
 * 	uasort ( $this->cols, array( $this, 'menuSort' ) );
 *
 */
function menuSortInv($a, $b)
{

	if (empty($a['rank'])) {
		$a['rank'] = 0;
	}
	if (empty($b['rank'])) {
		$b['rank'] = 0;
	}
	if ($a['rank'] == $b['rank']) {
		return 0;
	}

	return ($a['rank'] < $b['rank']) ? -1 : 1;
}
