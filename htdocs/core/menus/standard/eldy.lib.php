<?php
/* Copyright (C) 2010-2022	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2024	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2012-2015	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2015		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2018		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France		<frederic.france@free.fr>
 * Copyright (C) 2021		Gauthier VERDOL		<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *  \file		htdocs/core/menus/standard/eldy.lib.php
 *  \brief		Library for file eldy menus
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';


/**
 * Core function to output top menu eldy
 *
 * @param 	DoliDB	$db				Database handler
 * @param 	string	$atarget		Target (Example: '' or '_top')
 * @param 	int		$type_user     	0=Menu for backoffice, 1=Menu for front office
 * @param  	array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,level?:int,prefix:string}> 	$tabMenu        If array with menu entries already loaded, we put this array here (in most cases, it's empty). For eldy menu, it contains menu entries loaded from database.
 * @param	Menu	$menu			Object Menu to return back list of menu entries
 * @param	int		$noout			1=Disable output (Initialise &$menu only).
 * @param	string	$mode			'top', 'topnb', 'left', 'jmobile'
 * @return	int						0
 */
function print_eldy_menu($db, $atarget, $type_user, &$tabMenu, &$menu, $noout = 0, $mode = '')
{
	global $user, $conf, $langs, $mysoc;
	global $dolibarr_main_db_name;

	$mainmenu = (empty($_SESSION["mainmenu"]) ? '' : $_SESSION["mainmenu"]);
	$leftmenu = (empty($_SESSION["leftmenu"]) ? '' : $_SESSION["leftmenu"]);

	$id = 'mainmenu';
	$listofmodulesforexternal = explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL'));

	$substitarray = getCommonSubstitutionArray($langs, 0, null, null);

	if (empty($noout)) {
		print_start_menu_array();
	}

	global $usemenuhider;
	$usemenuhider = 1;

	// Show/Hide vertical menu. The hamburger icon for .menuhider action.
	if ($mode != 'jmobile' && $mode != 'topnb' && $usemenuhider && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		$showmode = 1;
		$classname = 'class="tmenu menuhider nohover"';
		$idsel = 'menu';

		$menu->add('#', (getDolGlobalInt('THEME_TOPMENU_DISABLE_IMAGE') == 1 ? '<span class="fas fa-bars size12x"></span>' : ''), 0, $showmode, $atarget, "xxx", '', 0, $id, $idsel, $classname, '<span class="fas fa-bars size12x"></span>');
	}

	$menu_arr = array();

	// Home
	$menu_arr[] = array(
		'name' => 'Home',
		'link' => '/index.php?mainmenu=home&amp;leftmenu=home',
		'title' => "Home",
		'level' => 0,
		'enabled' => $showmode = 1,
		'target' => $atarget,
		'mainmenu' => "home",
		'leftmenu' => '',
		'position' => 10,
		'id' => $id,
		'idsel' => 'home',
		'classname' =>  $classname = (empty($_SESSION["mainmenu"]) || $_SESSION["mainmenu"] == "home") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => '<span class="fas fa-home fa-fw"></span>',
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "home") ? 0 : 1),
		'loadLangs' => array(),
		'submenus' => array(),
	);

	// Members
	$tmpentry = array(
		'enabled' => isModEnabled('member'),
		'perms' => $user->hasRight('adherent', 'lire'),
		'module' => 'adherent'
	);
	$menu_arr[] = array(
		'name' => 'Members',
		'link' => '/adherents/index.php?mainmenu=members&amp;leftmenu=',
		'title' => "MenuMembers",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "members",
		'leftmenu' => '',
		'position' => 18,
		'id' => $id,
		'idsel' => 'members',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "members") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'member', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "members") ? 0 : 1),
		'loadLangs' => array("members"),
		'submenus' => array(),
	);

	// Third parties
	$tmpentry = array(
		'enabled' => (
			(
				isModEnabled('societe') &&
			(!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') || !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS'))
			)
			|| (isModEnabled('supplier_proposal') || isModEnabled('supplier_order') || isModEnabled('supplier_invoice'))
		),
		'perms' => ($user->hasRight('societe', 'lire') || $user->hasRight('societe', 'contact', 'lire') || $user->hasRight('fournisseur', 'lire') || $user->hasRight('supplier_order', 'lire') || $user->hasRight('supplier_invoice', 'lire') || $user->hasRight('supplier_proposal', 'lire')),
		'module' => 'societe|fournisseur'
	);

	$menu_arr[] = array(
		'name' => 'Companies',
		'link' => '/societe/index.php?mainmenu=companies&amp;leftmenu=',
		'title' => "ThirdParties",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "companies",
		'leftmenu' => '',
		'position' => 20,
		'id' => $id,
		'idsel' => 'companies',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "companies") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'company', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "companies") ? 0 : 1),
		'loadLangs' => array("companies", "suppliers"),
		'submenus' => array(),
	);

	// Products-Services
	$tmpentry = array(
		'enabled' => (isModEnabled('product') || isModEnabled('service') || isModEnabled('shipping')),
		'perms' => ($user->hasRight('product', 'read') || $user->hasRight('service', 'read') || $user->hasRight('expedition', 'lire')),
		'module' => 'product|service'
	);
	$menu_arr[] = array(
		'name' => 'Products',
		'link' => '/product/index.php?mainmenu=products&amp;leftmenu=',
		'title' => (isModEnabled('product') && isModEnabled('service'))
		? (array("TMenuProducts", " | ", "TMenuServices"))
		: (isModEnabled('product') ? "TMenuProducts" : "TMenuServices"),
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "products",
		'leftmenu' => '',
		'position' => 30,
		'id' => $id,
		'idsel' => 'products',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "products") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'product', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "products") ? 0 : 1),
		'loadLangs' => array("products", "stocks"),
		'submenus' => array(),
	);

	// MRP - GPAO
	$tmpentry = array(
		'enabled' => (isModEnabled('bom') || isModEnabled('mrp')),
		'perms' => ($user->hasRight('bom', 'read') || $user->hasRight('mrp', 'read')),
		'module' => 'bom|mrp'
	);
	$menu_arr[] = array(
		'name' => 'TMenuMRP',
		'link' => '/mrp/index.php?mainmenu=mrp&amp;leftmenu=',
		'title' => "TMenuMRP",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "mrp",
		'leftmenu' => '',
		'position' => 31,
		'id' => $id,
		'idsel' => 'mrp',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "mrp") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'mrp', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "mrp") ? 0 : 1),
		'loadLangs' => array("mrp"),
		'submenus' => array(),
	);

	// Projects
	$tmpentry = array(
		'enabled' => (isModEnabled('project') ? 1 : 0),
		'perms' => ($user->hasRight('projet', 'lire') ? 1 : 0),
		'module' => 'projet'
	);

	if ($mode == 'jmobile') {
		$titleboth = $langs->trans("LeadsOrProjects");
	} else {
		$titleboth = $langs->trans("Projects");
	}
	if (!getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
		$titleboth = $langs->trans("Projects");
	}
	if (getDolGlobalInt('PROJECT_USE_OPPORTUNITIES') == 2) {	// 2 = leads only
		$titleboth = $langs->trans("Leads");
	}

	$menu_arr[] = array(
		'name' => 'Project',
		'link' => '/projet/index.php?mainmenu=project&amp;leftmenu=',
		'title' => $titleboth,
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "project",
		'leftmenu' => '',
		'position' => 35,
		'id' => $id,
		'idsel' => 'project',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "project") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'project', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "project") ? 0 : 1),
		'loadLangs' => array("projects"),
		'submenus' => array(),
	);

	// Commercial (propal, commande, supplier_proposal, supplier_order, contrat, ficheinter)
	$tmpentry = array(
		'enabled' => (
			isModEnabled('propal')
			|| isModEnabled('order')
			|| isModEnabled('fournisseur')
			|| isModEnabled('supplier_proposal')
			|| isModEnabled('supplier_order')
			|| isModEnabled('contract')
			|| isModEnabled('intervention')
		) ? 1 : 0,
		'perms' => (
			$user->hasRight('propal', 'read')
			|| $user->hasRight('commande', 'lire')
			|| $user->hasRight('supplier_proposal', 'lire')
			|| $user->hasRight('fournisseur', 'lire')
			|| $user->hasRight('fournisseur', 'commande', 'lire')
			|| $user->hasRight('supplier_order', 'lire')
			|| $user->hasRight('contrat', 'lire')
			|| $user->hasRight('ficheinter', 'lire')
		),
		'module' => 'propal|commande|supplier_proposal|supplier_order|contrat|ficheinter'
	);

	$onlysupplierorder = $user->hasRight('fournisseur', 'commande', 'lire') &&
	!$user->hasRight('propal', 'lire') &&
	!$user->hasRight('commande', 'lire') &&
	!$user->hasRight('supplier_order', 'lire') &&
	!$user->hasRight('supplier_proposal', 'lire') &&
	!$user->hasRight('contrat', 'lire') &&
	!$user->hasRight('ficheinter', 'lire');

	$menu_arr[] = array(
		'name' => 'Commercial',
		'link' => ($onlysupplierorder ? '/fourn/commande/index.php?mainmenu=commercial&amp;leftmenu=' : '/comm/index.php?mainmenu=commercial&amp;leftmenu='),
		'title' => "Commercial",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "commercial",
		'leftmenu' => '',
		'position' => 40,
		'id' => $id,
		'idsel' => 'commercial',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "commercial") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'contract', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "commercial") ? 0 : 1),
		'loadLangs' => array("commercial"),
		'submenus' => array(),
	);

	// Billing - Financial
	$tmpentry = array(
		'enabled' => (
			isModEnabled('invoice') ||
			isModEnabled('don') ||
			isModEnabled('tax') ||
			isModEnabled('salaries') ||
			isModEnabled('supplier_invoice') ||
			isModEnabled('loan') ||
			isModEnabled('margin')
		) ? 1 : 0,
		'perms' => ($user->hasRight('facture', 'lire') || $user->hasRight('don', 'contact', 'lire')
			|| $user->hasRight('tax', 'charges', 'lire') || $user->hasRight('salaries', 'read')
			|| $user->hasRight('fournisseur', 'facture', 'lire') || $user->hasRight('loan', 'read') || $user->hasRight('margins', 'liretous')),
		'module' => 'facture|supplier_invoice|don|tax|salaries|loan'
	);
	$menu_arr[] = array(
		'name' => 'Compta',
		'link' => '/compta/index.php?mainmenu=billing&amp;leftmenu=',
		'title' =>  "MenuFinancial",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "billing",
		'leftmenu' => '',
		'position' => 50,
		'id' => $id,
		'idsel' => 'billing',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "billing") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'bill', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "billing") ? 0 : 1),
		'loadLangs' => array("compta"),
		'submenus' => array(),
	);

	// Bank
	$tmpentry = array(
		'enabled' => (isModEnabled('bank') || isModEnabled('prelevement')),
		'perms' => ($user->hasRight('banque', 'lire') || $user->hasRight('prelevement', 'lire') || $user->hasRight('paymentbybanktransfer', 'read')),
		'module' => 'banque|prelevement|paymentbybanktransfer'
	);
	$menu_arr[] = array(
		'name' => 'Bank',
		'link' => '/compta/bank/list.php?mainmenu=bank&amp;leftmenu=&amp;search_status=opened',
		'title' =>  "MenuBankCash",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "bank",
		'leftmenu' => '',
		'position' => 52,
		'id' => $id,
		'idsel' => 'bank',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "bank") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'bank_account', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "bank") ? 0 : 1),
		'loadLangs' => array("compta", "banks"),
		'submenus' => array(),
	);

	// Accounting
	$tmpentry = array(
		'enabled' => (isModEnabled('comptabilite') || isModEnabled('accounting') || isModEnabled('asset') || isModEnabled('intracommreport')),
		'perms' => ($user->hasRight('compta', 'resultat', 'lire') || $user->hasRight('accounting', 'comptarapport', 'lire') || $user->hasRight('accounting', 'mouvements', 'lire') || $user->hasRight('asset', 'read') || $user->hasRight('intracommreport', 'read')),
		'module' => 'comptabilite|accounting|asset|intracommreport'
	);
	$menu_arr[] = array(
		'name' => 'Accounting',
		'link' => '/accountancy/index.php?mainmenu=accountancy&amp;leftmenu=',
		'title' =>  "MenuAccountancy",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "accountancy",
		'leftmenu' => '',
		'position' => 54,
		'id' => $id,
		'idsel' => 'accountancy',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "accountancy") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'accountancy', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "accountancy") ? 0 : 1),
		'loadLangs' => array("compta", "accountancy", "assets", "intracommreport"),
		'submenus' => array(),
	);

	// HRM
	$tmpentry = array(
		'enabled' => (isModEnabled('hrm') || (isModEnabled('holiday')) || isModEnabled('deplacement') || isModEnabled('expensereport') || isModEnabled('recruitment')),
		'perms' => ($user->hasRight('user', 'user', 'lire') || $user->hasRight('holiday', 'read') || $user->hasRight('deplacement', 'lire') || $user->hasRight('expensereport', 'lire') || $user->hasRight('recruitment', 'recruitmentjobposition', 'read')),
		'module' => 'hrm|holiday|deplacement|expensereport|recruitment'
	);

	$menu_arr[] = array(
		'name' => 'HRM',
		'link' => '/hrm/index.php?mainmenu=hrm&amp;leftmenu=',
		'title' =>  "HRM",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "hrm",
		'leftmenu' => '',
		'position' => 80,
		'id' => $id,
		'idsel' => 'hrm',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "hrm") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'hrm', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "hrm") ? 0 : 1),
		'loadLangs' => array("hrm", "holiday"),
		'submenus' => array(),
	);

	// Tickets and Knowledge base
	$tmpentry = array(
		'enabled' => (isModEnabled('ticket') || isModEnabled('knowledgemanagement')),
		'perms' => ($user->hasRight('ticket', 'read') || $user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')),
		'module' => 'ticket|knowledgemanagement'
	);
	$link = '';
	if (isModEnabled('ticket')) {
		$link = '/ticket/index.php?mainmenu=ticket&amp;leftmenu=';
	} else {
		$link = '/knowledgemanagement/knowledgerecord_list.php?mainmenu=ticket&amp;leftmenu=';
	}
	$menu_arr[] = array(
		'name' => 'Ticket',
		'link' => $link,
		'title' =>  isModEnabled('ticket') ? "Tickets" : "MenuKnowledgeRecordShort",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "ticket",
		'leftmenu' => '',
		'position' => 88,
		'id' => $id,
		'idsel' => 'ticket',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "ticket") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'ticket', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "ticket") ? 0 : 1),
		'loadLangs' => array("ticket", "knowledgemanagement"),
		'submenus' => array(),
	);

	// Tools
	$tmpentry = array(
		'enabled' => 1,
		'perms' => 1,
		'module' => ''
	);
	$menu_arr[] = array(
		'name' => 'Tools',
		'link' => '/core/tools.php?mainmenu=tools&amp;leftmenu=',
		'title' =>  "Tools",
		'level' => 0,
		'enabled' => $showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal),
		'target' => $atarget,
		'mainmenu' => "tools",
		'leftmenu' => '',
		'position' => 90,
		'id' => $id,
		'idsel' => 'tools',
		'classname' =>  $classname = (!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "tools") ? 'class="tmenusel"' : 'class="tmenu"',
		'prefix' => img_picto('', 'tools', 'class="fa-fw pictofixedwidth"'),
		'session' => ((!empty($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "tools") ? 0 : 1),
		'loadLangs' => array("other"),
		'submenus' => array(),
	);

	// Add menus
	foreach ($menu_arr as $key => $smenu) {
		$smenu = (object) $smenu;

		if ($smenu->enabled) {
			if ($smenu->session) {
				$_SESSION['idmenu'] = '';
			}

			// Load Langue
			if (!empty($smenu->loadLangs)) {
				$langs->loadLangs($smenu->loadLangs);
			}

			// Trans title
			$mtitle = '';
			if (is_array($smenu->title)) {
				foreach ($smenu->title as $item) {
					$mtitle .= $langs->trans($item);
				}
			} else {
				$mtitle = $langs->trans($smenu->title);
			}
			// Add item
			$menu->add($smenu->link, $mtitle, $smenu->level, $smenu->enabled, $smenu->target, $smenu->mainmenu, $smenu->leftmenu, $smenu->position, $smenu->id, $smenu->idsel, $smenu->classname, $smenu->prefix);
		}
	}

	// Show personalized menus
	$menuArbo = new Menubase($db, 'eldy');

	$newTabMenu = $menuArbo->menuTopCharger('', '', $type_user, 'eldy', $tabMenu); // Return tabMenu with only top entries

	$num = count($newTabMenu);
	for ($i = 0; $i < $num; $i++) {
		//var_dump($type_user.' '.$newTabMenu[$i]['url'].' '.$showmode.' '.$newTabMenu[$i]['perms']);
		$idsel = (empty($newTabMenu[$i]['mainmenu']) ? 'none' : $newTabMenu[$i]['mainmenu']);

		$newTabMenu[$i]['url'] = make_substitutions($newTabMenu[$i]['url'], $substitarray);

		// url = url from host, shorturl = relative path into dolibarr sources
		$url = $shorturl = $newTabMenu[$i]['url'];
		if (!preg_match("/^(http:\/\/|https:\/\/)/i", $newTabMenu[$i]['url'])) {	// Do not change url content for external links
			$tmp = explode('?', $newTabMenu[$i]['url'], 2);
			$url = $shorturl = $tmp[0];
			$param = (isset($tmp[1]) ? $tmp[1] : '');

			if ((!preg_match('/mainmenu/i', $param)) || !preg_match('/leftmenu/i', $param)) {
				// @phan-suppress-next-line PhanTypeSuspiciousStringExpression
				$param .= ($param ? '&' : '').'mainmenu='.$newTabMenu[$i]['mainmenu'].'&leftmenu=';
			}
			//$url.="idmenu=".$newTabMenu[$i]['rowid'];    // Already done by menuLoad
			$url = dol_buildpath($url, 1).($param ? '?'.$param : '');
			//$shorturl = $shorturl.($param?'?'.$param:'');
			$shorturl = $url;
			if (DOL_URL_ROOT) {
				$shorturl = preg_replace('/^'.preg_quote(DOL_URL_ROOT, '/').'/', '', $shorturl);
			}
		}

		$showmode = isVisibleToUserType($type_user, $newTabMenu[$i], $listofmodulesforexternal);
		if ($showmode == 1) {
			// Define the class (top menu selected or not)
			if (!empty($_SESSION['idmenu']) && $newTabMenu[$i]['rowid'] == $_SESSION['idmenu']) {
				$classname = 'class="tmenusel"';
			} elseif (!empty($_SESSION["mainmenu"]) && $newTabMenu[$i]['mainmenu'] == $_SESSION["mainmenu"]) {
				$classname = 'class="tmenusel"';
			} else {
				$classname = 'class="tmenu"';
			}
		} elseif ($showmode == 2) {
			$classname = 'class="tmenu"';
		}

		$menu->add(
			$shorturl,
			$newTabMenu[$i]['titre'],
			0,
			$showmode,
			($newTabMenu[$i]['target'] ? $newTabMenu[$i]['target'] : $atarget),
			($newTabMenu[$i]['mainmenu'] ? $newTabMenu[$i]['mainmenu'] : $newTabMenu[$i]['rowid']),
			($newTabMenu[$i]['leftmenu'] ? $newTabMenu[$i]['leftmenu'] : ''),
			$newTabMenu[$i]['position'],
			$id,
			$idsel,
			$classname,
			$newTabMenu[$i]['prefix']
		);
	}

	// Sort on position
	$menu->liste = dol_sort_array($menu->liste, 'position');

	// Output menu entries
	// Show logo company
	if (!getDolGlobalString('MAIN_MENU_INVERT') && empty($noout) && getDolGlobalString('MAIN_SHOW_LOGO') && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		//$mysoc->logo_mini=(empty($conf->global->MAIN_INFO_SOCIETE_LOGO_MINI)?'':$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI);
		$mysoc->logo_squarred_mini = (!getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI') ? '' : $conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI);

		$logoContainerAdditionalClass = 'backgroundforcompanylogo';
		if (getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_NO_BACKGROUND')) {
			$logoContainerAdditionalClass = '';
		}

		if (!empty($mysoc->logo_squarred_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_squarred_mini)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini);
			/*} elseif (!empty($mysoc->logo_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini))
			{
			$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini);
			}*/
		} else {
			$urllogo = DOL_URL_ROOT.'/theme/dolibarr_512x512_white.png';
			$logoContainerAdditionalClass = '';
		}
		$title = $langs->trans("GoIntoSetupToChangeLogo");

		print "\n".'<!-- Show logo on menu -->'."\n";
		print_start_menu_entry('companylogo', 'class="tmenu tmenucompanylogo nohover"', 1);

		print '<div class="center '.$logoContainerAdditionalClass.' menulogocontainer"><img class="mycompany" title="'.dol_escape_htmltag($title).'" alt="" src="'.$urllogo.'" style="max-width: 100px"></div>'."\n";

		print_end_menu_entry(4);
	}

	//var_dump($menu->liste);
	if (empty($noout)) {
		foreach ($menu->liste as $menuval) {
			print_start_menu_entry($menuval['idsel'], $menuval['classname'], $menuval['enabled']);
			print_text_menu_entry($menuval['titre'], $menuval['enabled'], (($menuval['url'] != '#' && !preg_match('/^(http:\/\/|https:\/\/)/i', $menuval['url'])) ? DOL_URL_ROOT : '').$menuval['url'], $menuval['id'], $menuval['idsel'], $menuval['classname'], ($menuval['target'] ? $menuval['target'] : $atarget), $menuval);
			print_end_menu_entry($menuval['enabled']);
		}
	}

	$showmode = 1;
	if (empty($noout) && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
		print_start_menu_entry('', 'class="tmenuend"', $showmode);
		print_end_menu_entry($showmode);
		print_end_menu_array();
	}

	return 0;
}


/**
 * Output start menu array
 *
 * @return	void
 */
function print_start_menu_array()
{
	print '<div class="tmenudiv">';
	print '<ul role="navigation" class="tmenu"'.(getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? ' title="Top menu"' : '').'>';
}

/**
 * Output start menu entry
 *
 * @param	string	$idsel		Text
 * @param	string	$classname	String to add a css class
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_start_menu_entry($idsel, $classname, $showmode)
{
	if ($showmode) {
		print '<li '.$classname.' id="mainmenutd_'.$idsel.'">';
		//print '<div class="tmenuleft tmenusep"></div>';
		print '<div class="tmenucenter">';
	}
}

/**
 * Output menu entry
 *
 * @param	string	$text			Text
 * @param	int		$showmode		0 = hide, 1 = allowed or 2 = not allowed
 * @param	string	$url			Url
 * @param	string	$id				Id
 * @param	string	$idsel			Id sel
 * @param	string	$classname		Class name
 * @param	string	$atarget		Target
 * @param	array{}|array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,level?:int,prefix:string} 	$menuval		All the $menuval array
 * @return	void
 */
function print_text_menu_entry($text, $showmode, $url, $id, $idsel, $classname, $atarget, $menuval = array())
{
	global $langs;

	$classnameimg = str_replace('class="', 'class="tmenuimage ', $classname);
	$classnametxt = str_replace('class="', 'class="tmenulabel ', $classname);

	//$conf->global->THEME_TOPMENU_DISABLE_IMAGE=1;
	if ($showmode == 1) {
		print '<a '.$classnameimg.' tabindex="-1" href="'.$url.'"'.($atarget ? ' target="'.$atarget.'"' : '').' title="'.dol_escape_htmltag($text).'">';
		print '<div class="'.$id.' '.$idsel.' topmenuimage">';
		if (!empty($menuval['prefix']) && strpos($menuval['prefix'], '<span') === 0) {
			print $menuval['prefix'];
		} elseif (!empty($menuval['prefix']) && strpos($menuval['prefix'], 'fa-') === 0) {
			print '<span class="'.$id.' '.$menuval['prefix'].'" id="mainmenuspan_'.$idsel.'"></span>';
		} else {
			print '<span class="'.$id.' tmenuimageforpng" id="mainmenuspan_'.$idsel.'"></span>';
		}
		print '</div>';
		print '</a>';
		if (!getDolGlobalString('THEME_TOPMENU_DISABLE_TEXT')) {
			print '<a '.$classnametxt.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($atarget ? ' target="'.$atarget.'"' : '').' title="'.dol_escape_htmltag($text).'">';
			print '<span class="mainmenuaspan">';
			print $text;
			print '</span>';
			print '</a>';
		}
	} elseif ($showmode == 2) {
		print '<div '.$classnameimg.' title="'.dol_escape_htmltag($text.' - '.$langs->trans("NotAllowed")).'">';
		print '<div class="'.$id.' '.$idsel.' topmenuimage tmenudisabled">';
		if (!empty($menuval['prefix']) && strpos($menuval['prefix'], '<span') === 0) {
			print $menuval['prefix'];
		} else {
			print '<span class="'.$id.' tmenuimageforpng tmenudisabled" id="mainmenuspan_'.$idsel.'"></span>';
		}
		print '</div>';
		print '</div>';
		if (!getDolGlobalString('THEME_TOPMENU_DISABLE_TEXT')) {
			print '<span '.$classnametxt.' id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($text.' - '.$langs->trans("NotAllowed")).'">';
			print '<span class="mainmenuaspan tmenudisabled">';
			print $text;
			print '</span>';
			print '</span>';
		}
	}
}

/**
 * Output end menu entry
 *
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_end_menu_entry($showmode)
{
	if ($showmode) {
		print '</div></li>';
	}
	print "\n";
}

/**
 * Output menu array
 *
 * @return	void
 */
function print_end_menu_array()
{
	print '</ul>';
	print '</div>';
	print "\n";
}

/**
 * Core function to output left menu eldy
 * Fill &$menu (example with $forcemainmenu='home' $forceleftmenu='all', return left menu tree of Home)
 *
 * @param	DoliDB		$db                 Database handler
 * @param 	array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,level?:int,prefix:string}> 	$menu_array_before  Table of menu entries to show before entries of menu handler (menu->liste filled with menu->add)
 * @param   array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,level?:int,prefix:string}>		$menu_array_after   Table of menu entries to show after entries of menu handler (menu->liste filled with menu->add)
 * @param	array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,level?:int,prefix:string}> 	$tabMenu       		If array with menu entries already loaded, we put this array here (in most cases, it's empty)
 * @param	Menu		$menu				Object Menu to return back list of menu entries
 * @param	int<0,1>	$noout				Disable output (Initialise &$menu only).
 * @param	string		$forcemainmenu		'x'=Force mainmenu to mainmenu='x'
 * @param	string		$forceleftmenu		'all'=Force leftmenu to '' (= all). If value come being '', we change it to value in session and 'none' if not defined in session.
 * @param	?array<string,string>	$moredata	An array with more data to output
 * @param 	int<0,1>	$type_user     		0=Menu for backoffice, 1=Menu for front office
 * @return	int								Nb of menu entries
 */
function print_left_eldy_menu($db, $menu_array_before, $menu_array_after, &$tabMenu, &$menu, $noout = 0, $forcemainmenu = '', $forceleftmenu = '', $moredata = null, $type_user = 0)
{
	global $user, $conf, $langs, $hookmanager;

	//var_dump($tabMenu);

	$newmenu = $menu;

	$mainmenu = ($forcemainmenu ? $forcemainmenu : $_SESSION["mainmenu"] ?? '');
	$leftmenu = ($forceleftmenu ? '' : (empty($_SESSION["leftmenu"]) ? 'none' : $_SESSION["leftmenu"] ?? ''));

	if (empty($mainmenu)) {
		$mainmenu = 'home';
	}

	global $usemenuhider;
	$usemenuhider = 0;

	if (is_array($moredata) && !empty($moredata['searchform'])) {	// searchform can contains select2 code or link to show old search form or link to switch on search page
		print "\n";
		print "<!-- Begin SearchForm -->\n";
		print '<div id="blockvmenusearch" class="blockvmenusearch">'."\n";
		print $moredata['searchform'];
		print '</div>'."\n";
		print "<!-- End SearchForm -->\n";
	}

	if (is_array($moredata) && !empty($moredata['bookmarks'])) {
		print "\n";
		print "<!-- Begin Bookmarks -->\n";
		print '<div id="blockvmenubookmarks" class="blockvmenubookmarks">'."\n";
		print $moredata['bookmarks'];
		print '</div>'."\n";
		print "<!-- End Bookmarks -->\n";
	}

	$substitarray = getCommonSubstitutionArray($langs, 0, null, null);

	$listofmodulesforexternal = explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL'));

	/**
	 * We update newmenu with entries found into database
	 * --------------------------------------------------
	 */
	if ($mainmenu) {	// If this is empty, loading hard coded menu and loading personalised menu will fail
		/*
		 * Menu HOME
		 */
		if ($mainmenu == 'home') {
			get_left_menu_home($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu THIRDPARTIES
		 */
		if ($mainmenu == 'companies') {
			get_left_menu_thridparties($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu COMMERCIAL (propal, commande, supplier_proposal, supplier_order, contrat, ficheinter)
		 */
		if ($mainmenu == 'commercial') {
			get_left_menu_commercial($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu COMPTA-FINANCIAL
		 */
		if ($mainmenu == 'billing') {
			get_left_menu_billing($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu COMPTA-FINANCIAL
		 */
		if ($mainmenu == 'accountancy') {
			get_left_menu_accountancy($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu BANK
		 */
		if ($mainmenu == 'bank') {
			get_left_menu_bank($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu PRODUCTS-SERVICES
		 */
		if ($mainmenu == 'products') {
			get_left_menu_products($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu PRODUCTS-SERVICES MRP - GPAO
		 */
		if ($mainmenu == 'mrp') {
			get_left_menu_mrp($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu PROJECTS
		 */
		if ($mainmenu == 'project') {
			get_left_menu_projects($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu HRM
		 */
		if ($mainmenu == 'hrm') {
			get_left_menu_hrm($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}
		/*
		 * Menu TOOLS
		 */
		if ($mainmenu == 'tools') {
			get_left_menu_tools($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		/*
		 * Menu MEMBERS
		 */
		if ($mainmenu == 'members') {
			get_left_menu_members($mainmenu, $newmenu, $usemenuhider, $leftmenu, $type_user);
		}

		// Add personalized menus and modules menus
		//var_dump($newmenu->liste);    //
		$menuArbo = new Menubase($db, 'eldy');
		$newmenu = $menuArbo->menuLeftCharger($newmenu, $mainmenu, $leftmenu, (empty($user->socid) ? 0 : 1), 'eldy', $tabMenu);
		//var_dump($newmenu->liste);    //

		if (isModEnabled('ftp') && $mainmenu == 'ftp') {	// Entry for FTP
			$MAXFTP = 20;
			$i = 1;
			while ($i <= $MAXFTP) {
				$paramkey = 'FTP_NAME_'.$i;
				//print $paramkey;
				if (getDolGlobalString($paramkey)) {
					$link = "/ftp/index.php?idmenu=".$_SESSION["idmenu"]."&numero_ftp=".$i;
					$newmenu->add($link, dol_trunc($conf->global->$paramkey, 24));
				}
				$i++;
			}
		}
	}

	//var_dump($tabMenu);
	//var_dump($newmenu->liste);

	// Build final $menu_array = $menu_array_before +$newmenu->liste + $menu_array_after
	//var_dump($menu_array_before);exit;
	//var_dump($menu_array_after);exit;
	$menu_array = $newmenu->liste;
	if (is_array($menu_array_before)) {
		$menu_array = array_merge($menu_array_before, $menu_array);
	}
	if (is_array($menu_array_after)) {
		$menu_array = array_merge($menu_array, $menu_array_after);
	}
	//var_dump($menu_array);exit;
	if (!is_array($menu_array)) {
		return 0;
	}

	// Allow the $menu_array of the menu to be manipulated by modules
	$parameters = array(
		'mainmenu' => $mainmenu,
	);
	$hook_items = $menu_array;
	$reshook = $hookmanager->executeHooks('menuLeftMenuItems', $parameters, $hook_items); // Note that $action and $object may have been modified by some hooks

	if (is_numeric($reshook)) {
		if ($reshook == 0 && !empty($hookmanager->resArray)) {
			$menu_array[] = $hookmanager->resArray; // add
		} elseif ($reshook == 1) {
			$menu_array = $hookmanager->resArray; // replace
		}

		// @todo Sort menu items by 'position' value
		//      $position = array();
		//      foreach ($menu_array as $key => $row) {
		//          $position[$key] = $row['position'];
		//      }
		//		$array1_sort_order = SORT_ASC;
		//      array_multisort($position, $array1_sort_order, $menu_array);
	}

	// TODO Use the position property in menu_array to reorder the $menu_array
	//var_dump($menu_array);
	/*$new_menu_array = array();
	 $level=0; $cusor=0; $position=0;
	 $nbentry = count($menu_array);
	 while (findNextEntryForLevel($menu_array, $cursor, $position, $level))
	 {

	 $cursor++;
	 }*/

	// Force the typing at this point to get useful analysis below:
	'@phan-var-force array<array{rowid:string,fk_menu:string,langs:string,enabled:int<0,2>,type:string,fk_mainmenu:string,fk_leftmenu:string,url:string,titre:string,perms:string,target:string,mainmenu:string,leftmenu:string,position:int,level?:int,prefix:string}> $menu_array';


	// Show menu
	$invert = !getDolGlobalString('MAIN_MENU_INVERT') ? "" : "invert";
	if (empty($noout)) {
		$altok = 0;
		$blockvmenuopened = false;
		$lastlevel0 = '';
		$num = count($menu_array);
		foreach (array_keys($menu_array) as $i) {     // Loop on each menu entry (got better static analysis)
			$showmenu = true;
			if (getDolGlobalString('MAIN_MENU_HIDE_UNAUTHORIZED') && empty($menu_array[$i]['enabled'])) {
				$showmenu = false;
			}

			// Begin of new left menu block
			if (empty($menu_array[$i]['level']) && $showmenu) {
				$altok++;
				$blockvmenuopened = true;
				$lastopened = true;
				for ($j = ($i + 1); $j < $num; $j++) {
					if (empty($menu_array[$j]['level'])) {
						$lastopened = false;
					}
				}
				if ($altok % 2 == 0) {
					print '<div class="blockvmenu blockvmenuimpair'.$invert.($lastopened ? ' blockvmenulast' : '').($altok == 1 ? ' blockvmenufirst' : '').'">'."\n";
				} else {
					print '<div class="blockvmenu blockvmenupair'.$invert.($lastopened ? ' blockvmenulast' : '').($altok == 1 ? ' blockvmenufirst' : '').'">'."\n";
				}
			}

			// Add tabulation
			$tabstring = '';
			$tabul = ($menu_array[$i]['level'] - 1);
			if ($tabul > 0) {
				for ($j = 0; $j < $tabul; $j++) {
					$tabstring .= '&nbsp;&nbsp;&nbsp;';
				}
			}

			// $menu_array[$i]['url'] can be a relative url, a full external url. We try substitution

			$menu_array[$i]['url'] = make_substitutions($menu_array[$i]['url'], $substitarray);

			$url = $shorturl = $shorturlwithoutparam = $menu_array[$i]['url'];
			if (!preg_match("/^(http:\/\/|https:\/\/)/i", $menu_array[$i]['url'])) {
				$tmp = explode('?', $menu_array[$i]['url'], 2);
				$url = $shorturl = $tmp[0];
				$param = (isset($tmp[1]) ? $tmp[1] : ''); // params in url of the menu link

				// Complete param to force leftmenu to '' to close open menu when we click on a link with no leftmenu defined.
				if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && !empty($menu_array[$i]['mainmenu'])) {
					$param .= ($param ? '&' : '').'mainmenu='.$menu_array[$i]['mainmenu'].'&leftmenu=';
				}
				if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && empty($menu_array[$i]['mainmenu'])) {
					$param .= ($param ? '&' : '').'leftmenu=';
				}
				//$url.="idmenu=".$menu_array[$i]['rowid'];    // Already done by menuLoad
				$url = dol_buildpath($url, 1).($param ? '?'.$param : '');
				$shorturlwithoutparam = $shorturl;
				$shorturl .= ($param ? '?'.$param : '');
			}


			print '<!-- Process menu entry with mainmenu='.$menu_array[$i]['mainmenu'].', leftmenu='.$menu_array[$i]['leftmenu'].', level='.$menu_array[$i]['level'].' enabled='.$menu_array[$i]['enabled'].', position='.$menu_array[$i]['position'].' -->'."\n";

			// Menu level 0
			if ($menu_array[$i]['level'] == 0) {
				if ($menu_array[$i]['enabled']) {     // Enabled so visible
					print '<div class="menu_titre">'.$tabstring;
					if ($shorturlwithoutparam) {
						print '<a class="vmenu" title="'.dol_escape_htmltag(dol_string_nohtmltag($menu_array[$i]['titre'])).'" href="'.$url.'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>';
					} else {
						print '<span class="vmenu">';
					}
					if (!empty($menu_array[$i]['prefix'])) {
						if (preg_match('/^fa\-[a-zA-Z0-9\-_]+$/', $menu_array[$i]['prefix'])) {
							print '<span class="fas '.$menu_array[$i]['prefix'].' paddingright pictofixedwidth"></span>';
						} else {
							print $menu_array[$i]['prefix'];
						}
					}
					print $menu_array[$i]['titre'];
					if ($shorturlwithoutparam) {
						print '</a>';
					} else {
						print '</span>';
					}
					print '</div>'."\n";
					$lastlevel0 = 'enabled';
				} elseif ($showmenu) {                 // Not enabled but visible (so greyed)
					print '<div class="menu_titre">'.$tabstring;
					print '<span class="vmenudisabled" title="'.dolPrintHTMLForAttribute($menu_array[$i]['titre']).'">';
					if (!empty($menu_array[$i]['prefix'])) {
						print $menu_array[$i]['prefix'];
					}
					print $menu_array[$i]['titre'];
					print '</span>';
					print '</div>'."\n";
					$lastlevel0 = 'greyed';
				} else {
					$lastlevel0 = 'hidden';
				}
				if ($showmenu) {
					print '<div class="menu_top"></div>'."\n";
				}
			}

			// Menu level > 0
			if ($menu_array[$i]['level'] > 0) {
				$cssmenu = '';
				if ($menu_array[$i]['url']) {
					$cssmenu = ' menu_contenu'.dol_string_nospecial(preg_replace('/\.php.*$/', '', $menu_array[$i]['url']));
				}

				if ($menu_array[$i]['enabled'] && $lastlevel0 == 'enabled') {
					// Enabled so visible, except if parent was not enabled.
					print '<div class="menu_contenu'.$cssmenu.'">';
					print $tabstring;
					if ($shorturlwithoutparam) {
						print '<a class="vsmenu" title="'.dol_escape_htmltag(dol_string_nohtmltag($menu_array[$i]['titre'])).'" href="'.$url.'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>';
					} else {
						print '<span class="vsmenu" title="'.dol_escape_htmltag($menu_array[$i]['titre']).'">';
					}
					print $menu_array[$i]['titre'];
					if ($shorturlwithoutparam) {
						print '</a>';
					} else {
						print '</span>';
					}
					// If title is not pure text and contains a table, no carriage return added
					if (!strstr($menu_array[$i]['titre'], '<table')) {
						print '<br>';
					}
					print '</div>'."\n";
				} elseif ($showmenu && $lastlevel0 == 'enabled') {
					// Not enabled but visible (so greyed), except if parent was not enabled.
					print '<div class="menu_contenu'.$cssmenu.'">';
					print $tabstring;
					print '<span class="vsmenudisabled vsmenudisabledmargin" title="'.dolPrintHTMLForAttribute($menu_array[$i]['titre']).'">'.$menu_array[$i]['titre'].'</span><br>';
					print '</div>'."\n";
				}
			}

			// If next is a new block or if there is nothing after
			if (empty($menu_array[$i + 1]['level'])) {               // End menu block
				if ($showmenu) {
					print '<div class="menu_end"></div>'."\n";
				}
				if ($blockvmenuopened) {
					print '</div>'."\n";
					$blockvmenuopened = false;
				}
			}
		}

		if ($altok) {
			print '<div class="blockvmenuend"></div>'; // End menu block
		}
	}

	return count($menu_array);
}


/**
 * Get left Menu HOME
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of user
 * @return	void
 */
function get_left_menu_home($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'home') {
		$langs->load("users");

		// Home - dashboard
		$newmenu->add("/index.php?mainmenu=home&amp;leftmenu=home", $langs->trans("MyDashboard"), 0, 1, '', $mainmenu, 'home', 0, '', '', '', '<i class="fas fa-chart-bar fa-fw paddingright pictofixedwidth"></i>');

		// Setup
		$newmenu->add("/admin/index.php?mainmenu=home&amp;leftmenu=setup", $langs->trans("Setup"), 0, $user->admin, '', $mainmenu, 'setup', 0, '', '', '', '<i class="fa fa-tools fa-fw paddingright pictofixedwidth"></i>');

		if ($usemenuhider || empty($leftmenu) || $leftmenu == "setup") {
			// Define $nbmodulesnotautoenabled - TODO This code is at different places
			$nbmodulesnotautoenabled = count($conf->modules);
			$listofmodulesautoenabled = array('agenda', 'fckeditor', 'export', 'import');
			foreach ($listofmodulesautoenabled as $moduleautoenable) {
				if (in_array($moduleautoenable, $conf->modules)) {
					$nbmodulesnotautoenabled--;
				}
			}

			// Load translation files required by the page
			$langs->loadLangs(array("admin", "help"));
			$warnpicto = '';
			if (!getDolGlobalString('MAIN_INFO_SOCIETE_NOM') || !getDolGlobalString('MAIN_INFO_SOCIETE_COUNTRY') || getDolGlobalString('MAIN_INFO_SOCIETE_SETUP_TODO_WARNING')) {
				$langs->load("errors");
				$warnpicto = img_warning($langs->trans("WarningMandatorySetupNotComplete"));
			}
			$newmenu->add("/admin/company.php?mainmenu=home", $langs->trans("MenuCompanySetup").$warnpicto, 1);
			$warnpicto = '';
			if ($nbmodulesnotautoenabled <= getDolGlobalInt('MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING', 1)) {	// If only user module enabled
				$langs->load("errors");
				$warnpicto = img_warning($langs->trans("WarningMandatorySetupNotComplete"));
			}
			$newmenu->add("/admin/modules.php?mainmenu=home", $langs->trans("Modules").$warnpicto, 1);
			$newmenu->add("/admin/ihm.php?mainmenu=home", $langs->trans("GUISetup"), 1);
			$newmenu->add("/admin/menus.php?mainmenu=home", $langs->trans("Menus"), 1);

			$newmenu->add("/admin/translation.php?mainmenu=home", $langs->trans("Translation"), 1);
			$newmenu->add("/admin/defaultvalues.php?mainmenu=home", $langs->trans("DefaultValues"), 1);
			$newmenu->add("/admin/boxes.php?mainmenu=home", $langs->trans("Boxes"), 1);
			$newmenu->add("/admin/delais.php?mainmenu=home", $langs->trans("MenuWarnings"), 1);
			$newmenu->add("/admin/security_other.php?mainmenu=home", $langs->trans("Security"), 1);
			$newmenu->add("/admin/limits.php?mainmenu=home", $langs->trans("MenuLimits"), 1);
			$newmenu->add("/admin/pdf.php?mainmenu=home", $langs->trans("PDF"), 1);

			$warnpicto = '';
			if (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail' && !getDolGlobalString('MAIN_HIDE_WARNING_TO_ENCOURAGE_SMTP_SETUP')) {
				$langs->load("errors");
				$warnpicto = img_warning($langs->trans("WarningPHPMailD"));
			}
			if (getDolGlobalString('MAIN_MAIL_SENDMODE') && in_array($conf->global->MAIN_MAIL_SENDMODE, array('smtps', 'swiftmail')) && !getDolGlobalString('MAIN_MAIL_SMTP_SERVER')) {
				$langs->load("errors");
				$warnpicto = img_warning($langs->trans("ErrorSetupOfEmailsNotComplete"));
			}

			$newmenu->add("/admin/mails.php?mainmenu=home", $langs->trans("Emails").$warnpicto, 1);
			$newmenu->add("/admin/sms.php?mainmenu=home", $langs->trans("SMS"), 1);
			$newmenu->add("/admin/dict.php?mainmenu=home", $langs->trans("Dictionary"), 1);
			$newmenu->add("/admin/const.php?mainmenu=home", $langs->trans("OtherSetup"), 1);
		}

		// System tools
		$newmenu->add("/admin/tools/index.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("AdminTools"), 0, $user->admin, '', $mainmenu, 'admintools', 0, '', '', '', '<i class="fa fa-server fa-fw paddingright pictofixedwidth"></i>');
		if ($usemenuhider || empty($leftmenu) || preg_match('/^admintools/', $leftmenu)) {
			// Load translation files required by the page
			$langs->loadLangs(array('admin', 'help'));

			$newmenu->add('/admin/system/dolibarr.php?mainmenu=home&amp;leftmenu=admintools_info', $langs->trans('InfoDolibarr'), 1);
			if ($usemenuhider || empty($leftmenu) || $leftmenu == 'admintools_info') {
				$newmenu->add('/admin/system/modules.php?mainmenu=home&amp;leftmenu=admintools_info', $langs->trans('Modules'), 2);
				$newmenu->add('/admin/triggers.php?mainmenu=home&amp;leftmenu=admintools_info', $langs->trans('Triggers'), 2);
				$newmenu->add('/admin/system/filecheck.php?mainmenu=home&amp;leftmenu=admintools_info', $langs->trans('FileCheck'), 2);
			}
			$newmenu->add('/admin/system/browser.php?mainmenu=home&amp;leftmenu=admintools', $langs->trans('InfoBrowser'), 1);
			$newmenu->add('/admin/system/os.php?mainmenu=home&amp;leftmenu=admintools', $langs->trans('InfoOS'), 1);
			$newmenu->add('/admin/system/web.php?mainmenu=home&amp;leftmenu=admintools', $langs->trans('InfoWebServer'), 1);
			$newmenu->add('/admin/system/phpinfo.php?mainmenu=home&amp;leftmenu=admintools', $langs->trans('InfoPHP'), 1);
			$newmenu->add('/admin/system/database.php?mainmenu=home&amp;leftmenu=admintools', $langs->trans('InfoDatabase'), 1);
			$newmenu->add("/admin/system/perf.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("InfoPerf"), 1);
			$newmenu->add("/admin/system/security.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("InfoSecurity"), 1);
			$newmenu->add("/admin/tools/listevents.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("Audit"), 1);
			$newmenu->add("/admin/tools/listsessions.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("Sessions"), 1);
			$newmenu->add("/admin/tools/dolibarr_export.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("Backup"), 1);
			$newmenu->add("/admin/tools/dolibarr_import.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("Restore"), 1);
			$newmenu->add("/admin/tools/update.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("MenuUpgrade"), 1);
			$newmenu->add("/admin/tools/purge.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("Purge"), 1);
			$newmenu->add('/admin/system/about.php?mainmenu=home&amp;leftmenu=admintools', $langs->trans('ExternalResources'), 1);

			if (isModEnabled('product') || isModEnabled('service')) {
				$langs->load("products");
				$newmenu->add("/product/admin/product_tools.php?mainmenu=home&amp;leftmenu=admintools", $langs->trans("ProductVatMassChange"), 1, $user->admin);
			}
		}

		$newmenu->add("/user/home.php?leftmenu=users", $langs->trans("MenuUsersAndGroups"), 0, $user->hasRight('user', 'user', 'read'), '', $mainmenu, 'users', 0, '', '', '', img_picto('', 'user', 'class="paddingright pictofixedwidth"'));
		if ($user->hasRight('user', 'user', 'read')) {
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "users") {
				$newmenu->add("", $langs->trans("Users"), 1, $user->hasRight('user', 'user', 'read') || $user->admin);
				$newmenu->add("/user/card.php?leftmenu=users&action=create", $langs->trans("NewUser"), 2, ($user->hasRight("user", "user", "write") || $user->admin) && !(isModEnabled('multicompany') && !empty($user->entity) && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')), '', 'home');
				$newmenu->add("/user/list.php?leftmenu=users", $langs->trans("ListOfUsers"), 2, $user->hasRight('user', 'user', 'read') || $user->admin);
				$newmenu->add("/user/hierarchy.php?leftmenu=users", $langs->trans("HierarchicView"), 2, $user->hasRight('user', 'user', 'read') || $user->admin);
				if (isModEnabled('category')) {
					$langs->load("categories");
					$newmenu->add("/categories/index.php?leftmenu=users&type=7", $langs->trans("UsersCategoriesShort"), 2, $user->hasRight('categorie', 'read'), '', $mainmenu, 'cat');
				}
				$newmenu->add("", $langs->trans("Groups"), 1, ($user->hasRight('user', 'user', 'read') || $user->admin) && !(isModEnabled('multicompany') && !empty($user->entity) && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')));
				$newmenu->add("/user/group/card.php?leftmenu=users&action=create", $langs->trans("NewGroup"), 2, ((getDolGlobalString('MAIN_USE_ADVANCED_PERMS') ? $user->hasRight("user", "group_advance", "write") : $user->hasRight("user", "user", "write")) || $user->admin) && !(isModEnabled('multicompany') && !empty($user->entity) && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')));
				$newmenu->add("/user/group/list.php?leftmenu=users", $langs->trans("ListOfGroups"), 2, ((getDolGlobalString('MAIN_USE_ADVANCED_PERMS') ? $user->hasRight('user', 'group_advance', 'read') : $user->hasRight('user', 'user', 'read')) || $user->admin));
			}
		}
	}
}

/**
 * Get left Menu THIRDPARTIES
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_thridparties($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'companies') {
		// Societes
		if (isModEnabled('societe')) {
			$langs->load("companies");
			$newmenu->add("/societe/index.php?leftmenu=thirdparties", $langs->trans("ThirdParty"), 0, $user->hasRight('societe', 'lire'), '', $mainmenu, 'thirdparties', 0, '', '', '', img_picto('', 'company', 'class="paddingright pictofixedwidth"'));

			if ($user->hasRight('societe', 'creer')) {
				$newmenu->add("/societe/card.php?action=create", $langs->trans("MenuNewThirdParty"), 1);
				if (!$conf->use_javascript_ajax) {
					$newmenu->add("/societe/card.php?action=create&amp;private=1", $langs->trans("MenuNewPrivateIndividual"), 1);
				}
			}
		}

		$newmenu->add("/societe/list.php?leftmenu=thirdparties", $langs->trans("List"), 1, $user->hasRight('societe', 'lire'), '', $mainmenu, 'thirdparties_list', 2);

		// Prospects
		if (isModEnabled('societe') && !getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
			$langs->load("commercial");
			$newmenu->add("/societe/list.php?type=p&amp;leftmenu=prospects", $langs->trans("Prospects"), 2, $user->hasRight('societe', 'lire'), '', $mainmenu, 'prospects', 5);

			$newmenu->add("/societe/card.php?leftmenu=prospects&amp;action=create&amp;type=p", $langs->trans("MenuNewProspect"), 3, $user->hasRight('societe', 'creer'));
		}

		// Customers/Prospects
		if (isModEnabled('societe') && !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
			$langs->load("commercial");
			$newmenu->add("/societe/list.php?type=c&amp;leftmenu=customers", $langs->trans("Customers"), 2, $user->hasRight('societe', 'lire'), '', $mainmenu, 'customers', 10);

			$newmenu->add("/societe/card.php?leftmenu=customers&amp;action=create&amp;type=c", $langs->trans("MenuNewCustomer"), 3, $user->hasRight('societe', 'creer'));
		}

		// Suppliers
		if (isModEnabled('societe') && (isModEnabled('supplier_order') || isModEnabled('supplier_invoice') || isModEnabled('supplier_proposal'))) {
			$langs->load("suppliers");
			$newmenu->add("/societe/list.php?type=f&amp;leftmenu=suppliers", $langs->trans("Suppliers"), 2, ($user->hasRight('fournisseur', 'lire') || $user->hasRight('supplier_order', 'lire') || $user->hasRight('supplier_invoice', 'lire') || $user->hasRight('supplier_proposal', 'lire')), '', $mainmenu, 'suppliers', 15);

			$newmenu->add("/societe/card.php?leftmenu=suppliers&amp;action=create&amp;type=f", $langs->trans("MenuNewSupplier"), 3, $user->hasRight('societe', 'creer') && ($user->hasRight('fournisseur', 'lire') || $user->hasRight('supplier_order', 'lire') || $user->hasRight('supplier_invoice', 'lire') || $user->hasRight('supplier_proposal', 'lire')));
		}

		// Categories
		if (isModEnabled('category')) {
			$langs->load("categories");
			if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') || !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
				// Categories prospects/customers
				$menutoshow = $langs->trans("CustomersProspectsCategoriesShort");
				if (getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
					$menutoshow = $langs->trans("CustomersCategoriesShort");
				}
				if (getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
					$menutoshow = $langs->trans("ProspectsCategoriesShort");
				}
				$newmenu->add("/categories/index.php?leftmenu=cat&amp;type=2", $menutoshow, 1, $user->hasRight('categorie', 'lire'), '', $mainmenu, 'cat');
			}
			// Categories suppliers
			if (isModEnabled('supplier_proposal') || isModEnabled('supplier_order') || isModEnabled('supplier_invoice')) {
				$newmenu->add("/categories/index.php?leftmenu=catfournish&amp;type=1", $langs->trans("SuppliersCategoriesShort"), 1, $user->hasRight('categorie', 'lire'));
			}
		}

		// Contacts
		$newmenu->add("/societe/index.php?leftmenu=thirdparties", (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses")), 0, $user->hasRight('societe', 'contact', 'lire'), '', $mainmenu, 'contacts', 0, '', '', '', img_picto('', 'contact', 'class="paddingright pictofixedwidth"'));

		$newmenu->add("/contact/card.php?leftmenu=contacts&amp;action=create", (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("NewContact") : $langs->trans("NewContactAddress")), 1, $user->hasRight('societe', 'contact', 'creer'));
		$newmenu->add("/contact/list.php?leftmenu=contacts", $langs->trans("List"), 1, $user->hasRight('societe', 'contact', 'lire'));
		if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
			$newmenu->add("/contact/list.php?leftmenu=contacts&type=p", $langs->trans("Prospects"), 2, $user->hasRight('societe', 'contact', 'lire'));
		}
		if (!getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
			$newmenu->add("/contact/list.php?leftmenu=contacts&type=c", $langs->trans("Customers"), 2, $user->hasRight('societe', 'contact', 'lire'));
		}
		if (isModEnabled('supplier_proposal') || isModEnabled('supplier_order') || isModEnabled('supplier_invoice')) {
			$newmenu->add("/contact/list.php?leftmenu=contacts&type=f", $langs->trans("Suppliers"), 2, $user->hasRight('fournisseur', 'lire'));
		}
		$newmenu->add("/contact/list.php?leftmenu=contacts&type=o", $langs->trans("ContactOthers"), 2, $user->hasRight('societe', 'contact', 'lire'));
		//$newmenu->add("/contact/list.php?userid=$user->id", $langs->trans("MyContacts"), 1, $user->hasRight('societe',  'contact', 'lire'));

		// Categories
		if (isModEnabled('category')) {
			$langs->load("categories");
			// Categories Contact
			$newmenu->add("/categories/index.php?leftmenu=catcontact&amp;type=4", $langs->trans("ContactCategoriesShort"), 1, $user->hasRight('categorie', 'lire'), '', $mainmenu, 'cat');
		}
	}
}

/**
 * Get left Menu COMMERCIAL (propal, commande, supplier_proposal, supplier_order, contrat, ficheinter)
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_commercial($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $langs;

	if ($mainmenu == 'commercial') {
		$langs->load("companies");

		// Customer proposal
		if (isModEnabled('propal')) {
			$langs->load("propal");
			$newmenu->add("/comm/propal/index.php?leftmenu=propals", $langs->trans("Proposals"), 0, $user->hasRight('propal', 'read'), '', $mainmenu, 'propals', 100, '', '', '', img_picto('', 'propal', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/comm/propal/card.php?action=create&amp;leftmenu=propals", $langs->trans("NewPropal"), 1, $user->hasRight('propal', 'write'));
			$newmenu->add("/comm/propal/list.php?leftmenu=propals", $langs->trans("List"), 1, $user->hasRight('propal', 'read'));
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "propals") {
				$newmenu->add("/comm/propal/list.php?leftmenu=propals&search_status=0", $langs->trans("PropalsDraft"), 2, $user->hasRight('propal', 'read'));
				$newmenu->add("/comm/propal/list.php?leftmenu=propals&search_status=1", $langs->trans("PropalsOpened"), 2, $user->hasRight('propal', 'read'));
				$newmenu->add("/comm/propal/list.php?leftmenu=propals&search_status=2", $langs->trans("PropalStatusSigned"), 2, $user->hasRight('propal', 'read'));
				$newmenu->add("/comm/propal/list.php?leftmenu=propals&search_status=3", $langs->trans("PropalStatusNotSigned"), 2, $user->hasRight('propal', 'read'));
				$newmenu->add("/comm/propal/list.php?leftmenu=propals&search_status=4", $langs->trans("PropalStatusBilled"), 2, $user->hasRight('propal', 'read'));
				//$newmenu->add("/comm/propal/list.php?leftmenu=propals&search_status=2,3,4", $langs->trans("PropalStatusClosedShort"), 2, $user->hasRight('propal',  'read'));
			}
			$newmenu->add("/comm/propal/stats/index.php?leftmenu=propals", $langs->trans("Statistics"), 1, $user->hasRight('propal', 'read'));
		}

		// Customers orders
		if (isModEnabled('order')) {
			$langs->load("orders");
			$newmenu->add("/commande/index.php?leftmenu=orders", $langs->trans("CustomersOrders"), 0, $user->hasRight('commande', 'lire'), '', $mainmenu, 'orders', 200, '', '', '', img_picto('', 'order', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/commande/card.php?action=create&amp;leftmenu=orders", $langs->trans("NewOrder"), 1, $user->hasRight('commande', 'creer'));
			$newmenu->add("/commande/list.php?leftmenu=orders", $langs->trans("List"), 1, $user->hasRight('commande', 'lire'));
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "orders") {
				$newmenu->add("/commande/list.php?leftmenu=orders&search_status=0", $langs->trans("StatusOrderDraftShort"), 2, $user->hasRight('commande', 'lire'));
				$newmenu->add("/commande/list.php?leftmenu=orders&search_status=1", $langs->trans("StatusOrderValidated"), 2, $user->hasRight('commande', 'lire'));
				if (isModEnabled('shipping')) {
					$newmenu->add("/commande/list.php?leftmenu=orders&search_status=2", $langs->trans("StatusOrderSentShort"), 2, $user->hasRight('commande', 'lire'));
				}
				$newmenu->add("/commande/list.php?leftmenu=orders&search_status=3", $langs->trans("StatusOrderDelivered"), 2, $user->hasRight('commande', 'lire'));
				//$newmenu->add("/commande/list.php?leftmenu=orders&search_status=4", $langs->trans("StatusOrderProcessed"), 2, $user->hasRight('commande',  'lire'));
				$newmenu->add("/commande/list.php?leftmenu=orders&search_status=-1", $langs->trans("StatusOrderCanceledShort"), 2, $user->hasRight('commande', 'lire'));
			}
			if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 1 && empty($user->socid)) {
				$newmenu->add("/commande/list_det.php?leftmenu=orders", $langs->trans("ListOrderLigne"), 1, $user->hasRight('commande', 'lire'));
			}
			if (getDolGlobalInt('MAIN_NEED_EXPORT_PERMISSION_TO_READ_STATISTICS')) {
				$newmenu->add("/commande/stats/index.php?leftmenu=orders", $langs->trans("Statistics"), 1, $user->hasRight('commande', 'commande', 'export'));
			} else {
				$newmenu->add("/commande/stats/index.php?leftmenu=orders", $langs->trans("Statistics"), 1, $user->hasRight('commande', 'lire'));
			}

			// Categories
			if (isModEnabled('categorie')) {
				$langs->load("categories");
				$newmenu->add("/categories/index.php?leftmenu=cat&amp;type=16", $langs->trans("Categories"), 1, $user->hasRight('categorie', 'lire'), '', $mainmenu, 'cat');
			}
		}

		// Supplier proposal
		if (isModEnabled('supplier_proposal')) {
			$langs->load("supplier_proposal");
			$newmenu->add("/supplier_proposal/index.php?leftmenu=propals_supplier", $langs->trans("SupplierProposalsShort"), 0, $user->hasRight('supplier_proposal', 'lire'), '', $mainmenu, 'propals_supplier', 300, '', '', '', img_picto('', 'supplier_proposal', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/supplier_proposal/card.php?action=create&amp;leftmenu=supplier_proposals", $langs->trans("SupplierProposalNew"), 1, $user->hasRight('supplier_proposal', 'creer'));
			$newmenu->add("/supplier_proposal/list.php?leftmenu=supplier_proposals", $langs->trans("List"), 1, $user->hasRight('supplier_proposal', 'lire'));
			$newmenu->add("/comm/propal/stats/index.php?leftmenu=supplier_proposals&amp;mode=supplier", $langs->trans("Statistics"), 1, $user->hasRight('supplier_proposal', 'lire'));
		}

		// Suppliers orders
		if (isModEnabled('supplier_order')) {
			$langs->load("orders");
			$newmenu->add("/fourn/commande/index.php?leftmenu=orders_suppliers", $langs->trans("SuppliersOrders"), 0, $user->hasRight('fournisseur', 'commande', 'lire'), '', $mainmenu, 'orders_suppliers', 400, '', '', '', img_picto('', 'supplier_order', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/fourn/commande/card.php?action=create&amp;leftmenu=orders_suppliers", $langs->trans("NewSupplierOrderShort"), 1, $user->hasRight('fournisseur', 'commande', 'creer'));
			$newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers", $langs->trans("List"), 1, $user->hasRight('fournisseur', 'commande', 'lire'));

			if ($usemenuhider || empty($leftmenu) || $leftmenu == "orders_suppliers") {
				$newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers&statut=0", $langs->trans("StatusSupplierOrderDraftShort"), 2, $user->hasRight('fournisseur', 'commande', 'lire'));
				if (!getDolGlobalString('SUPPLIER_ORDER_HIDE_VALIDATED')) {
					$newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers&statut=1", $langs->trans("StatusSupplierOrderValidated"), 2, $user->hasRight('fournisseur', 'commande', 'lire'));
				}
				$newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers&statut=2", $langs->trans("StatusSupplierOrderApprovedShort"), 2, $user->hasRight('fournisseur', 'commande', 'lire'));
				$newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers&statut=3", $langs->trans("StatusSupplierOrderOnProcessShort"), 2, $user->hasRight('fournisseur', 'commande', 'lire'));
				$newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers&statut=4", $langs->trans("StatusSupplierOrderReceivedPartiallyShort"), 2, $user->hasRight('fournisseur', 'commande', 'lire'));
				$newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers&statut=5", $langs->trans("StatusSupplierOrderReceivedAll"), 2, $user->hasRight('fournisseur', 'commande', 'lire'));
				$newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers&statut=6,7", $langs->trans("StatusSupplierOrderCanceled"), 2, $user->hasRight('fournisseur', 'commande', 'lire'));
				$newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers&statut=9", $langs->trans("StatusSupplierOrderRefused"), 2, $user->hasRight('fournisseur', 'commande', 'lire'));
			}
			// Billed is another field. We should add instead a dedicated filter on list. if ($usemenuhider || empty($leftmenu) || $leftmenu=="orders_suppliers") $newmenu->add("/fourn/commande/list.php?leftmenu=orders_suppliers&billed=1", $langs->trans("Billed"), 2, $user->hasRight('fournisseur',  'commande', 'lire'));

			if (getDolGlobalInt('MAIN_NEED_EXPORT_PERMISSION_TO_READ_STATISTICS')) {
				$newmenu->add("/commande/stats/index.php?leftmenu=orders_suppliers&amp;mode=supplier", $langs->trans("Statistics"), 1, $user->hasRight('fournisseur', 'commande', 'export'));
			} else {
				$newmenu->add("/commande/stats/index.php?leftmenu=orders_suppliers&amp;mode=supplier", $langs->trans("Statistics"), 1, $user->hasRight('fournisseur', 'commande', 'lire'));
			}
		}

		// Contrat
		if (isModEnabled('contract')) {
			$langs->load("contracts");
			$newmenu->add("/contrat/index.php?leftmenu=contracts", $langs->trans("ContractsSubscriptions"), 0, $user->hasRight('contrat', 'lire'), '', $mainmenu, 'contracts', 2000, '', '', '', img_picto('', 'contract', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/contrat/card.php?action=create&amp;leftmenu=contracts", $langs->trans("NewContractSubscription"), 1, $user->hasRight('contrat', 'creer'));
			$newmenu->add("/contrat/list.php?leftmenu=contracts", $langs->trans("List"), 1, $user->hasRight('contrat', 'lire'));
			$newmenu->add("/contrat/services_list.php?leftmenu=contracts", $langs->trans("MenuServices"), 1, $user->hasRight('contrat', 'lire'));
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "contracts") {
				$newmenu->add("/contrat/services_list.php?leftmenu=contracts&amp;search_status=0", $langs->trans("MenuInactiveServices"), 2, $user->hasRight('contrat', 'lire'));
				$newmenu->add("/contrat/services_list.php?leftmenu=contracts&amp;search_status=4", $langs->trans("MenuRunningServices"), 2, $user->hasRight('contrat', 'lire'));
				$newmenu->add("/contrat/services_list.php?leftmenu=contracts&amp;search_status=4%26filter=expired", $langs->trans("MenuExpiredServices"), 2, $user->hasRight('contrat', 'lire'));
				$newmenu->add("/contrat/services_list.php?leftmenu=contracts&amp;search_status=5", $langs->trans("MenuClosedServices"), 2, $user->hasRight('contrat', 'lire'));
			}
		}

		// Interventions
		if (isModEnabled('intervention')) {
			$langs->load("interventions");
			$newmenu->add("/fichinter/index.php?leftmenu=ficheinter", $langs->trans("Interventions"), 0, $user->hasRight('ficheinter', 'lire'), '', $mainmenu, 'ficheinter', 2200, '', '', '', img_picto('', 'intervention', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/fichinter/card.php?action=create&amp;leftmenu=ficheinter", $langs->trans("NewIntervention"), 1, $user->hasRight('ficheinter', 'creer'), '', '', '', 201);
			$newmenu->add("/fichinter/list.php?leftmenu=ficheinter", $langs->trans("List"), 1, $user->hasRight('ficheinter', 'lire'), '', '', '', 202);
			if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
				$newmenu->add("/fichinter/card-rec.php?leftmenu=ficheinter", $langs->trans("ListOfTemplates"), 1, $user->hasRight('ficheinter', 'lire'), '', '', '', 203);
			}
			$newmenu->add("/fichinter/stats/index.php?leftmenu=ficheinter", $langs->trans("Statistics"), 1, $user->hasRight('ficheinter', 'lire'));
		}
	}
}

/**
 * Get left COMPTA-FINANCIAL
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_billing($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'billing') {
		$langs->load("companies");

		// Customers invoices
		if (isModEnabled('invoice')) {
			$langs->load("bills");
			$newmenu->add("/compta/facture/index.php?leftmenu=customers_bills", $langs->trans("BillsCustomers"), 0, $user->hasRight('facture', 'lire'), '', $mainmenu, 'customers_bills', 0, '', '', '', img_picto('', 'bill', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/compta/facture/card.php?action=create", $langs->trans("NewBill"), 1, $user->hasRight('facture', 'creer'));
			$newmenu->add("/compta/facture/list.php?leftmenu=customers_bills", $langs->trans("List"), 1, $user->hasRight('facture', 'lire'), '', $mainmenu, 'customers_bills_list');

			if ($usemenuhider || empty($leftmenu) || preg_match('/customers_bills(|_draft|_notpaid|_paid|_canceled)$/', $leftmenu)) {
				$newmenu->add("/compta/facture/list.php?leftmenu=customers_bills_draft&amp;search_status=0", $langs->trans("BillShortStatusDraft"), 2, $user->hasRight('facture', 'lire'));
				$newmenu->add("/compta/facture/list.php?leftmenu=customers_bills_notpaid&amp;search_status=1", $langs->trans("BillShortStatusNotPaid"), 2, $user->hasRight('facture', 'lire'));
				$newmenu->add("/compta/facture/list.php?leftmenu=customers_bills_paid&amp;search_status=2", $langs->trans("BillShortStatusPaid"), 2, $user->hasRight('facture', 'lire'));
				$newmenu->add("/compta/facture/list.php?leftmenu=customers_bills_canceled&amp;search_status=3", $langs->trans("BillShortStatusCanceled"), 2, $user->hasRight('facture', 'lire'));
			}
			$newmenu->add("/compta/facture/invoicetemplate_list.php?leftmenu=customers_bills_templates", $langs->trans("ListOfTemplates"), 1, $user->hasRight('facture', 'creer'), '', $mainmenu, 'customers_bills_templates'); // No need to see recurring invoices, if user has no permission to create invoice.

			$newmenu->add("/compta/paiement/list.php?leftmenu=customers_bills_payment", $langs->trans("Payments"), 1, $user->hasRight('facture', 'lire'), '', $mainmenu, 'customers_bills_payment');

			if (getDolGlobalString('BILL_ADD_PAYMENT_VALIDATION') && preg_match('/customers_bills_payment/', $leftmenu)) {
				$newmenu->add("/compta/paiement/tovalidate.php?leftmenu=customers_bills_payment_tovalid", $langs->trans("MenuToValid"), 2, $user->hasRight('facture', 'lire'), '', $mainmenu, 'customers_bills_payment_tovalid');
			}
			if ($usemenuhider || empty($leftmenu) || preg_match('/customers_bills_payment/', $leftmenu)) {
				$newmenu->add("/compta/paiement/rapport.php?leftmenu=customers_bills_payment_report", $langs->trans("Reportings"), 2, $user->hasRight('facture', 'lire'), '', $mainmenu, 'customers_bills_payment_report');
			}

			$newmenu->add("/compta/facture/stats/index.php?leftmenu=customers_bills_stats", $langs->trans("Statistics"), 1, $user->hasRight('facture', 'lire'), '', $mainmenu, 'customers_bills_stats');
		}

		// Suppliers invoices
		if (isModEnabled('societe') && isModEnabled('supplier_invoice') && !getDolGlobalString('SUPPLIER_INVOICE_MENU_DISABLED')) {
			$langs->load("bills");
			$newmenu->add("/fourn/facture/index.php?leftmenu=suppliers_bills", $langs->trans("BillsSuppliers"), 0, $user->hasRight('fournisseur', 'facture', 'lire'), '', $mainmenu, 'suppliers_bills', 0, '', '', '', img_picto('', 'supplier_invoice', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/fourn/facture/card.php?leftmenu=suppliers_bills&amp;action=create", $langs->trans("NewBill"), 1, ($user->hasRight('fournisseur', 'facture', 'creer') || $user->hasRight('supplier_invoice', 'creer')), '', $mainmenu, 'suppliers_bills_create');
			$newmenu->add("/fourn/facture/list.php?leftmenu=suppliers_bills", $langs->trans("List"), 1, $user->hasRight('fournisseur', 'facture', 'lire'), '', $mainmenu, 'suppliers_bills_list');

			if ($usemenuhider || empty($leftmenu) || preg_match('/suppliers_bills/', $leftmenu)) {
				$newmenu->add("/fourn/facture/list.php?leftmenu=suppliers_bills_draft&amp;search_status=0", $langs->trans("BillShortStatusDraft"), 2, $user->hasRight('fournisseur', 'facture', 'lire'), '', $mainmenu, 'suppliers_bills_draft');
				$newmenu->add("/fourn/facture/list.php?leftmenu=suppliers_bills_notpaid&amp;search_status=1", $langs->trans("BillShortStatusNotPaid"), 2, $user->hasRight('fournisseur', 'facture', 'lire'), '', $mainmenu, 'suppliers_bills_notpaid');
				$newmenu->add("/fourn/facture/list.php?leftmenu=suppliers_bills_paid&amp;search_status=2", $langs->trans("BillShortStatusPaid"), 2, $user->hasRight('fournisseur', 'facture', 'lire'), '', $mainmenu, 'suppliers_bills_paid');
			}

			$newmenu->add("/fourn/facture/list-rec.php?leftmenu=supplierinvoicestemplate_list", $langs->trans("ListOfTemplates"), 1, $user->hasRight('fournisseur', 'facture', 'lire'), '', $mainmenu, 'supplierinvoicestemplate_list');

			$newmenu->add("/fourn/paiement/list.php?leftmenu=suppliers_bills_payment", $langs->trans("Payments"), 1, $user->hasRight('fournisseur', 'facture', 'lire'), '', $mainmenu, 'suppliers_bills_payment');

			if ($usemenuhider || empty($leftmenu) || preg_match('/suppliers_bills/', $leftmenu)) {
				$newmenu->add("/fourn/facture/rapport.php?leftmenu=suppliers_bills_payment_report", $langs->trans("Reportings"), 2, $user->hasRight('fournisseur', 'facture', 'lire'), '', $mainmenu, 'suppliers_bills_payment_report');
			}

			$newmenu->add("/compta/facture/stats/index.php?mode=supplier&amp;leftmenu=suppliers_bills_stats", $langs->trans("Statistics"), 1, $user->hasRight('fournisseur', 'facture', 'lire'), '', $mainmenu, 'suppliers_bills_stats');
		}

		// Orders
		if (isModEnabled('order')) {
			$langs->load("orders");
			if (isModEnabled('invoice')) {
				$newmenu->add("/commande/list.php?leftmenu=orders&amp;search_status=-3&amp;search_billed=0&amp;contextpage=billableorders", $langs->trans("MenuOrdersToBill2"), 0, $user->hasRight('commande', 'lire'), '', $mainmenu, 'orders', 0, '', '', '', img_picto('', 'order', 'class="paddingright pictofixedwidth"'));
			}
			//if ($usemenuhider || empty($leftmenu) || $leftmenu=="orders") $newmenu->add("/commande/", $langs->trans("StatusOrderToBill"), 1, $user->hasRight('commande',  'lire'));
		}

		// Supplier Orders to bill
		if (isModEnabled('supplier_invoice')) {
			if (getDolGlobalString('SUPPLIER_MENU_ORDER_RECEIVED_INTO_INVOICE')) {
				$langs->load("supplier");
				$newmenu->add("/fourn/commande/list.php?leftmenu=orders&amp;search_status=5&amp;search_billed=0", $langs->trans("MenuOrdersSupplierToBill"), 0, $user->hasRight('commande', 'lire'), '', $mainmenu, 'orders', 0, '', '', '', img_picto('', 'supplier_order', 'class="paddingright pictofixedwidth"'));
				//if ($usemenuhider || empty($leftmenu) || $leftmenu=="orders") $newmenu->add("/commande/", $langs->trans("StatusOrderToBill"), 1, $user->hasRight('commande',  'lire'));
			}
		}


		// Donations
		if (isModEnabled('don')) {
			$langs->load("donations");
			$newmenu->add("/don/index.php?leftmenu=donations&amp;mainmenu=billing", $langs->trans("Donations"), 0, $user->hasRight('don', 'lire'), '', $mainmenu, 'donations', 0, '', '', '', img_picto('', 'donation', 'class="paddingright pictofixedwidth"'));
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "donations") {
				$newmenu->add("/don/card.php?leftmenu=donations&amp;action=create", $langs->trans("NewDonation"), 1, $user->hasRight('don', 'creer'));
				$newmenu->add("/don/list.php?leftmenu=donations", $langs->trans("List"), 1, $user->hasRight('don', 'lire'));
				$newmenu->add("/don/paiement/list.php?leftmenu=donations", $langs->trans("Payments"), 1, $user->hasRight('don', 'lire'));
				$newmenu->add("/don/stats/index.php", $langs->trans("Statistics"), 1, $user->hasRight('don', 'lire'));
			}
			// if ($leftmenu=="donations") $newmenu->add("/don/stats/index.php",$langs->trans("Statistics"), 1, $user->hasRight('don',  'lire'));
		}

		// Taxes and social contributions
		if (isModEnabled('tax')) {
			$newmenu->add("/compta/charges/index.php?leftmenu=tax&amp;mainmenu=billing", $langs->trans("MenuTaxesAndSpecialExpenses"), 0, $user->hasRight('tax', 'charges', 'lire'), '', $mainmenu, 'tax', 0, '', '', '', img_picto('', 'payment', 'class="paddingright pictofixedwidth"'));

			$newmenu->add("/compta/sociales/list.php?leftmenu=tax_social", $langs->trans("MenuSocialContributions"), 1, $user->hasRight('tax', 'charges', 'lire'));
			if ($usemenuhider || empty($leftmenu) || preg_match('/^tax_social/i', $leftmenu)) {
				$newmenu->add("/compta/sociales/card.php?leftmenu=tax_social&action=create", $langs->trans("MenuNewSocialContribution"), 2, $user->hasRight('tax', 'charges', 'creer'));
				$newmenu->add("/compta/sociales/list.php?leftmenu=tax_social", $langs->trans("List"), 2, $user->hasRight('tax', 'charges', 'lire'));
				$newmenu->add("/compta/sociales/payments.php?leftmenu=tax_social&amp;mainmenu=billing", $langs->trans("Payments"), 2, $user->hasRight('tax', 'charges', 'lire'));
			}
			// VAT
			if (!getDolGlobalString('TAX_DISABLE_VAT_MENUS')) {
				global $mysoc;

				$newmenu->add("/compta/tva/list.php?leftmenu=tax_vat&amp;mainmenu=billing", $langs->transcountry("VAT", $mysoc->country_code), 1, $user->hasRight('tax', 'charges', 'lire'), '', $mainmenu, 'tax_vat');
				if ($usemenuhider || empty($leftmenu) || preg_match('/^tax_vat/i', $leftmenu)) {
					$newmenu->add("/compta/tva/card.php?leftmenu=tax_vat&action=create", $langs->trans("New"), 2, $user->hasRight('tax', 'charges', 'creer'));
					$newmenu->add("/compta/tva/list.php?leftmenu=tax_vat", $langs->trans("List"), 2, $user->hasRight('tax', 'charges', 'lire'));
					$newmenu->add("/compta/tva/payments.php?mode=tvaonly&amp;leftmenu=tax_vat", $langs->trans("Payments"), 2, $user->hasRight('tax', 'charges', 'lire'));
					$newmenu->add("/compta/tva/index.php?leftmenu=tax_vat", $langs->trans("ReportByMonth"), 2, $user->hasRight('tax', 'charges', 'lire'));
					$newmenu->add("/compta/tva/clients.php?leftmenu=tax_vat", $langs->trans("ReportByThirdparties"), 2, $user->hasRight('tax', 'charges', 'lire'));
					$newmenu->add("/compta/tva/quadri_detail.php?leftmenu=tax_vat", $langs->trans("ReportByQuarter"), 2, $user->hasRight('tax', 'charges', 'lire'));
				}

				//Local Taxes 1
				if ($mysoc->useLocalTax(1) && (isset($mysoc->localtax1_assuj) && $mysoc->localtax1_assuj == "1")) {
					$newmenu->add("/compta/localtax/list.php?leftmenu=tax_1_vat&amp;mainmenu=billing&amp;localTaxType=1", $langs->transcountry("LT1", $mysoc->country_code), 1, $user->hasRight('tax', 'charges', 'lire'));
					if ($usemenuhider || empty($leftmenu) || preg_match('/^tax_1_vat/i', $leftmenu)) {
						$newmenu->add("/compta/localtax/card.php?leftmenu=tax_1_vat&action=create&amp;localTaxType=1", $langs->trans("New"), 2, $user->hasRight('tax', 'charges', 'creer'));
						$newmenu->add("/compta/localtax/list.php?leftmenu=tax_1_vat&amp;localTaxType=1", $langs->trans("List"), 2, $user->hasRight('tax', 'charges', 'lire'));
						$newmenu->add("/compta/localtax/index.php?leftmenu=tax_1_vat&amp;localTaxType=1", $langs->trans("ReportByMonth"), 2, $user->hasRight('tax', 'charges', 'lire'));
						$newmenu->add("/compta/localtax/clients.php?leftmenu=tax_1_vat&amp;localTaxType=1", $langs->trans("ReportByThirdparties"), 2, $user->hasRight('tax', 'charges', 'lire'));
						$newmenu->add("/compta/localtax/quadri_detail.php?leftmenu=tax_1_vat&amp;localTaxType=1", $langs->trans("ReportByQuarter"), 2, $user->hasRight('tax', 'charges', 'lire'));
					}
				}
				//Local Taxes 2
				if ($mysoc->useLocalTax(2) && (isset($mysoc->localtax2_assuj) && $mysoc->localtax2_assuj == "1")) {
					$newmenu->add("/compta/localtax/list.php?leftmenu=tax_2_vat&amp;mainmenu=billing&amp;localTaxType=2", $langs->transcountry("LT2", $mysoc->country_code), 1, $user->hasRight('tax', 'charges', 'lire'));
					if ($usemenuhider || empty($leftmenu) || preg_match('/^tax_2_vat/i', $leftmenu)) {
						$newmenu->add("/compta/localtax/card.php?leftmenu=tax_2_vat&action=create&amp;localTaxType=2", $langs->trans("New"), 2, $user->hasRight('tax', 'charges', 'creer'));
						$newmenu->add("/compta/localtax/list.php?leftmenu=tax_2_vat&amp;localTaxType=2", $langs->trans("List"), 2, $user->hasRight('tax', 'charges', 'lire'));
						$newmenu->add("/compta/localtax/index.php?leftmenu=tax_2_vat&amp;localTaxType=2", $langs->trans("ReportByMonth"), 2, $user->hasRight('tax', 'charges', 'lire'));
						$newmenu->add("/compta/localtax/clients.php?leftmenu=tax_2_vat&amp;localTaxType=2", $langs->trans("ReportByThirdparties"), 2, $user->hasRight('tax', 'charges', 'lire'));
						$newmenu->add("/compta/localtax/quadri_detail.php?leftmenu=tax_2_vat&amp;localTaxType=2", $langs->trans("ReportByQuarter"), 2, $user->hasRight('tax', 'charges', 'lire'));
					}
				}
			}
		}

		// Salaries
		if (isModEnabled('salaries')) {
			$langs->load("salaries");
			$newmenu->add("/salaries/list.php?leftmenu=tax_salary&amp;mainmenu=billing", $langs->trans("Salaries"), 0, $user->hasRight('salaries', 'read'), '', $mainmenu, 'tax_salary', 0, '', '', '', img_picto('', 'salary', 'class="paddingright pictofixedwidth"'));
			if ($usemenuhider || empty($leftmenu) || preg_match('/^tax_salary/i', $leftmenu)) {
				$newmenu->add("/salaries/card.php?leftmenu=tax_salary&action=create", $langs->trans("New"), 1, $user->hasRight('salaries', 'write'));
				$newmenu->add("/salaries/list.php?leftmenu=tax_salary", $langs->trans("List"), 1, $user->hasRight('salaries', 'read'));
				$newmenu->add("/salaries/payments.php?leftmenu=tax_salary", $langs->trans("Payments"), 1, $user->hasRight('salaries', 'read'));
				$newmenu->add("/salaries/stats/index.php?leftmenu=tax_salary", $langs->trans("Statistics"), 1, $user->hasRight('salaries', 'read'));
			}
		}

		// Loan
		if (isModEnabled('loan')) {
			$langs->load("loan");
			$newmenu->add("/loan/list.php?leftmenu=tax_loan&amp;mainmenu=billing", $langs->trans("Loans"), 0, $user->hasRight('loan', 'read'), '', $mainmenu, 'tax_loan', 0, '', '', '', img_picto('', 'loan', 'class="paddingright pictofixedwidth"'));
			if ($usemenuhider || empty($leftmenu) || preg_match('/^tax_loan/i', $leftmenu)) {
				$newmenu->add("/loan/card.php?leftmenu=tax_loan&action=create", $langs->trans("NewLoan"), 1, $user->hasRight('loan', 'write'));
				//$newmenu->add("/loan/payment/list.php?leftmenu=tax_loan",$langs->trans("Payments"),2,$user->hasRight('loan',  'read'));
			}
		}

		// Various payment
		if (isModEnabled('bank') && !getDolGlobalString('BANK_USE_OLD_VARIOUS_PAYMENT')) {
			$langs->load("banks");
			$newmenu->add("/compta/bank/various_payment/list.php?leftmenu=tax_various&amp;mainmenu=billing", $langs->trans("MenuVariousPayment"), 0, $user->hasRight('banque', 'lire'), '', $mainmenu, 'tax_various', 0, '', '', '', img_picto('', 'payment', 'class="paddingright pictofixedwidth"'));
			if ($usemenuhider || empty($leftmenu) || preg_match('/^tax_various/i', $leftmenu)) {
				$newmenu->add("/compta/bank/various_payment/card.php?leftmenu=tax_various&action=create", $langs->trans("New"), 1, $user->hasRight('banque', 'modifier'));
				$newmenu->add("/compta/bank/various_payment/list.php?leftmenu=tax_various", $langs->trans("List"), 1, $user->hasRight('banque', 'lire'));
			}
		}
	}
}

/**
 * Get left COMPTA-FINANCIAL (accountancy)
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_accountancy($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;
	global $db;

	if ($mainmenu == 'accountancy') {
		$langs->load("companies");

		// Accounting (Double entries)
		if (isModEnabled('accounting')) {
			//$permtoshowmenu = (isModEnabled('accounting') || $user->hasRight('accounting',  'bind', 'write') || $user->hasRight('compta',  'resultat', 'lire'));
			//$newmenu->add("/accountancy/index.php?leftmenu=accountancy", $langs->trans("MenuAccountancy"), 0, $permtoshowmenu, '', $mainmenu, 'accountancy');

			// Configuration
			$newmenu->add("/accountancy/index.php?leftmenu=accountancy_admin", $langs->trans("Setup"), 0, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin', 1, '', '', '', img_picto('', 'technic', 'class="paddingright pictofixedwidth"'));
			if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy_admin/', $leftmenu)) {
				global $mysoc;
				$newmenu->add("/accountancy/admin/index.php?mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("General"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_general', 10);

				$newmenu->add("/accountancy/admin/journals_list.php?id=35&mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("AccountingJournals"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_journal', 30);
				$newmenu->add("/accountancy/admin/accountmodel.php?id=31&mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("Pcg_version"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_chartmodel', 40);
				$newmenu->add("/accountancy/admin/account.php?mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("Chartofaccounts"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_chart', 41);
				$newmenu->add("/accountancy/admin/subaccount.php?mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("ChartOfSubaccounts"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_chart', 41);

				// Fiscal year
				$newmenu->add("/accountancy/admin/fiscalyear.php?mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("FiscalPeriod"), 1, $user->hasRight('accounting', 'fiscalyear', 'write'), '', $mainmenu, 'fiscalyear', 45);

				$newmenu->add("/accountancy/admin/defaultaccounts.php?mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("MenuDefaultAccounts"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_default', 60);
				if (isModEnabled('bank')) {
					$newmenu->add("/compta/bank/list.php?mainmenu=accountancy&leftmenu=accountancy_admin&search_status=-1", $langs->trans("MenuBankAccounts"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_bank', 70);
				}
				if (isModEnabled('invoice') || isModEnabled('supplier_invoice')) {
					$newmenu->add("/admin/dict.php?id=10&from=accountancy&search_country_id=".$mysoc->country_id."&mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("MenuVatAccounts"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_default', 80);
				}
				if (isModEnabled('tax')) {
					$newmenu->add("/admin/dict.php?id=7&from=accountancy&search_country_id=".$mysoc->country_id."&mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("MenuTaxAccounts"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_default', 90);
				}
				if (isModEnabled('expensereport')) {
					$newmenu->add("/admin/dict.php?id=17&from=accountancy&mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("MenuExpenseReportAccounts"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_default', 100);
				}
				$newmenu->add("/accountancy/admin/productaccount.php?mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("MenuProductsAccounts"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_product', 110);
				$newmenu->add("/accountancy/admin/closure.php?mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("MenuClosureAccounts"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_closure', 120);
				$newmenu->add("/accountancy/admin/categories_list.php?id=32&search_country_id=".$mysoc->country_id."&mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("AccountingCategory"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_chart', 125);
				$newmenu->add("/accountancy/admin/export.php?mainmenu=accountancy&leftmenu=accountancy_admin", $langs->trans("ExportOptions"), 1, $user->hasRight('accounting', 'chartofaccount'), '', $mainmenu, 'accountancy_admin_export', 130);
			}

			// Transfer in accounting
			$newmenu->add("/accountancy/index.php?leftmenu=accountancy_transfer", $langs->trans("TransferInAccounting"), 0, $user->hasRight('accounting', 'bind', 'write'), '', $mainmenu, 'transfer', 1, '', '', '', img_picto('', 'long-arrow-alt-right', 'class="paddingright pictofixedwidth"'));

			// Binding
			// $newmenu->add("", $langs->trans("Binding"), 0, $user->hasRight('accounting',  'bind', 'write'), '', $mainmenu, 'dispatch');
			if (isModEnabled('invoice') && !getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_SALES')) {
				$newmenu->add("/accountancy/customer/index.php?leftmenu=accountancy_dispatch_customer&amp;mainmenu=accountancy", $langs->trans("CustomersVentilation"), 1, $user->hasRight('accounting', 'bind', 'write'), '', $mainmenu, 'dispatch_customer');
				if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy_dispatch_customer/', $leftmenu)) {
					$newmenu->add("/accountancy/customer/list.php?mainmenu=accountancy&amp;leftmenu=accountancy_dispatch_customer", $langs->trans("ToBind"), 2, $user->hasRight('accounting', 'bind', 'write'));
					$newmenu->add("/accountancy/customer/lines.php?mainmenu=accountancy&amp;leftmenu=accountancy_dispatch_customer", $langs->trans("Binded"), 2, $user->hasRight('accounting', 'bind', 'write'));
				}
			}
			if (isModEnabled('supplier_invoice') && !getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_PURCHASES')) {
				$newmenu->add("/accountancy/supplier/index.php?leftmenu=accountancy_dispatch_supplier&amp;mainmenu=accountancy", $langs->trans("SuppliersVentilation"), 1, $user->hasRight('accounting', 'bind', 'write'), '', $mainmenu, 'dispatch_supplier');
				if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy_dispatch_supplier/', $leftmenu)) {
					$newmenu->add("/accountancy/supplier/list.php?mainmenu=accountancy&amp;leftmenu=accountancy_dispatch_supplier", $langs->trans("ToBind"), 2, $user->hasRight('accounting', 'bind', 'write'));
					$newmenu->add("/accountancy/supplier/lines.php?mainmenu=accountancy&amp;leftmenu=accountancy_dispatch_supplier", $langs->trans("Binded"), 2, $user->hasRight('accounting', 'bind', 'write'));
				}
			}
			if (isModEnabled('expensereport') && !getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS')) {
				$newmenu->add("/accountancy/expensereport/index.php?leftmenu=accountancy_dispatch_expensereport&amp;mainmenu=accountancy", $langs->trans("ExpenseReportsVentilation"), 1, $user->hasRight('accounting', 'bind', 'write'), '', $mainmenu, 'dispatch_expensereport');
				if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy_dispatch_expensereport/', $leftmenu)) {
					$newmenu->add("/accountancy/expensereport/list.php?mainmenu=accountancy&amp;leftmenu=accountancy_dispatch_expensereport", $langs->trans("ToBind"), 2, $user->hasRight('accounting', 'bind', 'write'));
					$newmenu->add("/accountancy/expensereport/lines.php?mainmenu=accountancy&amp;leftmenu=accountancy_dispatch_expensereport", $langs->trans("Binded"), 2, $user->hasRight('accounting', 'bind', 'write'));
				}
			}

			// Journals
			if (isModEnabled('accounting') && $user->hasRight('accounting', 'comptarapport', 'lire') && $mainmenu == 'accountancy') {
				$newmenu->add('', $langs->trans("RegistrationInAccounting"), 1, $user->hasRight('accounting', 'comptarapport', 'lire'), '', $mainmenu, 'accountancy_journal');

				// Multi journal
				$sql = "SELECT rowid, code, label, nature";
				$sql .= " FROM ".MAIN_DB_PREFIX."accounting_journal";
				$sql .= " WHERE entity = ".((int) $conf->entity);
				$sql .= " AND active = 1";
				$sql .= " ORDER BY nature ASC, label DESC";

				$resql = $db->query($sql);
				if ($resql) {
					$numr = $db->num_rows($resql);
					$i = 0;

					if ($numr > 0) {
						while ($i < $numr) {
							$objp = $db->fetch_object($resql);

							$nature = '';

							// Must match array $sourceList defined into journals_list.php
							if ($objp->nature == 2 && isModEnabled('invoice') && !getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_SALES')) {
								$nature = "sells";
							}
							if ($objp->nature == 3
								&& isModEnabled('supplier_invoice')
								&& !getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_PURCHASES')) {
								$nature = "purchases";
							}
							if ($objp->nature == 4 && isModEnabled('bank')) {
								$nature = "bank";
							}
							if ($objp->nature == 5 && isModEnabled('expensereport') && !getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS')) {
								$nature = "expensereports";
							}
							if ($objp->nature == 1 && isModEnabled('asset')) {
								$nature = "various";	// Warning: The page /accountancy/journal/variousjournal.php is bugged. It read tables that does not exists.
							}
							if ($objp->nature == 8) {
								$nature = "inventory";
							}
							if ($objp->nature == 9) {
								$nature = "hasnew";
							}

							// To enable when page exists
							if (!getDolGlobalString('ACCOUNTANCY_SHOW_DEVELOP_JOURNAL')) {
								if ($nature == 'hasnew' || $nature == 'inventory') {
									$nature = '';
								}
							}

							if ($nature) {
								$langs->load('accountancy');
								$journallabel = '';
								$journallabelwithoutspan = '';
								if ($objp->label) {
									$journallabelwithoutspan = $langs->trans($objp->label);
									$journallabel = '<span class="opacitymedium">('.$langs->trans($objp->label).')</span>'; // Label of bank account in llx_accounting_journal
								}

								$key = $langs->trans("AccountingJournalType".$objp->nature);	// $objp->nature is 1, 2, 3 ...
								$transferlabel = (($objp->nature && $key != "AccountingJournalType".$objp->nature) ? $key.($journallabelwithoutspan != $key ? ' '.$journallabel : '') : $journallabel);

								$newmenu->add('/accountancy/journal/'.$nature.'journal.php?mainmenu=accountancy&leftmenu=accountancy_journal&id_journal='.$objp->rowid, $transferlabel, 2, $user->hasRight('accounting', 'comptarapport', 'lire'));
							}
							$i++;
						}
					} else {
						// Should not happen. Entries are added
						$newmenu->add('', $langs->trans("NoJournalDefined"), 2, $user->hasRight('accounting', 'comptarapport', 'lire'));
					}
				} else {
					dol_print_error($db);
				}
				$db->free($resql);
			}

			// Files
			if (!getDolGlobalString('ACCOUNTANCY_HIDE_EXPORT_FILES_MENU')) {
				$newmenu->add("/compta/accounting-files.php?mainmenu=accountancy&amp;leftmenu=accountancy_files", $langs->trans("AccountantFiles"), 1, $user->hasRight('accounting', 'mouvements', 'lire'));
			}


			// Accounting
			$newmenu->add("/accountancy/index.php?leftmenu=accountancy_accountancy", $langs->trans("MenuAccountancy"), 0, $user->hasRight('accounting', 'mouvements', 'lire') || $user->hasRight('accounting', 'comptarapport', 'lire'), '', $mainmenu, 'accountancy', 1, '', '', '', img_picto('', 'accountancy', 'class="paddingright pictofixedwidth"'));

			// General Ledger
			$newmenu->add("/accountancy/bookkeeping/listbyaccount.php?mainmenu=accountancy&amp;leftmenu=accountancy_accountancy", $langs->trans("Bookkeeping"), 1, $user->hasRight('accounting', 'mouvements', 'lire'));

			// Journals
			$newmenu->add("/accountancy/bookkeeping/list.php?mainmenu=accountancy&amp;leftmenu=accountancy_accountancy", $langs->trans("Journals"), 1, $user->hasRight('accounting', 'mouvements', 'lire'));

			// Account Balance
			$newmenu->add("/accountancy/bookkeeping/balance.php?mainmenu=accountancy&amp;leftmenu=accountancy_accountancy", $langs->trans("AccountBalance"), 1, $user->hasRight('accounting', 'mouvements', 'lire'));

			// Export accountancy
			$newmenu->add("/accountancy/bookkeeping/export.php?mainmenu=accountancy&amp;leftmenu=accountancy_accountancy", $langs->trans("MenuExportAccountancy"), 1, $user->hasRight('accounting', 'mouvements', 'lire'));

			// Closure
			$newmenu->add("/accountancy/closure/index.php?mainmenu=accountancy&amp;leftmenu=accountancy_closure", $langs->trans("MenuAccountancyClosure"), 1, $user->hasRight('accounting', 'fiscalyear', 'write'), '', $mainmenu, 'closure');

			// Reports
			$newmenu->add("/accountancy/index.php?leftmenu=accountancy_report", $langs->trans("Reportings"), 1, $user->hasRight('accounting', 'comptarapport', 'lire'), '', $mainmenu, 'ca');

			if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy_report/', $leftmenu)) {
				$newmenu->add("/compta/resultat/index.php?leftmenu=accountancy_report", $langs->trans("MenuReportInOut"), 2, $user->hasRight('accounting', 'comptarapport', 'lire'));
				$newmenu->add("/compta/resultat/clientfourn.php?leftmenu=accountancy_report", $langs->trans("ByPredefinedAccountGroups"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
				$newmenu->add("/compta/resultat/result.php?leftmenu=accountancy_report", $langs->trans("ByPersonalizedAccountGroups"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
			}

			$modecompta = 'CREANCES-DETTES';
			if (isModEnabled('accounting') && $user->hasRight('accounting', 'comptarapport', 'lire') && $mainmenu == 'accountancy') {
				$modecompta = 'BOOKKEEPING'; // Not yet implemented. Should be BOOKKEEPINGCOLLECTED
			}
			if ($modecompta) {
				if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy_report/', $leftmenu)) {
					$newmenu->add("/compta/stats/index.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ReportTurnover"), 2, $user->hasRight('accounting', 'comptarapport', 'lire'));
					$newmenu->add("/compta/stats/casoc.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByCompanies"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
					$newmenu->add("/compta/stats/cabyuser.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByUsers"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
					$newmenu->add("/compta/stats/cabyprodserv.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByProductsAndServices"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
					$newmenu->add("/compta/stats/byratecountry.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByVatRate"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
				}
			}

			$modecompta = 'RECETTES-DEPENSES';
			//if (isModEnabled('accounting') && $user->hasRight('accounting',  'comptarapport', 'lire') && $mainmenu == 'accountancy') $modecompta='';	// Not yet implemented. Should be BOOKKEEPINGCOLLECTED
			if ($modecompta) {
				if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy_report/', $leftmenu)) {
					$newmenu->add("/compta/stats/index.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ReportTurnoverCollected"), 2, $user->hasRight('accounting', 'comptarapport', 'lire'));
					$newmenu->add("/compta/stats/casoc.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByCompanies"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
					$newmenu->add("/compta/stats/cabyuser.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByUsers"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
					//$newmenu->add("/compta/stats/cabyprodserv.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByProductsAndServices"),3,$user->hasRight('accounting',  'comptarapport', 'lire'));
					//$newmenu->add("/compta/stats/byratecountry.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByVatRate"),3,$user->hasRight('accounting',  'comptarapport', 'lire'));
				}
			}

			$modecompta = 'CREANCES-DETTES';
			if (isModEnabled('accounting') && $user->hasRight('accounting', 'comptarapport', 'lire') && $mainmenu == 'accountancy') {
				$modecompta = 'BOOKKEEPING'; // Not yet implemented.
			}
			if ($modecompta && isModEnabled('supplier_invoice')) {
				if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy_report/', $leftmenu)) {
					$newmenu->add("/compta/stats/supplier_turnover.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ReportPurchaseTurnover"), 2, $user->hasRight('accounting', 'comptarapport', 'lire'));
					$newmenu->add("/compta/stats/supplier_turnover_by_thirdparty.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByCompanies"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
					$newmenu->add("/compta/stats/supplier_turnover_by_prodserv.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByProductsAndServices"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
				}
			}

			$modecompta = 'RECETTES-DEPENSES';
			if (isModEnabled('accounting') && $user->hasRight('accounting', 'comptarapport', 'lire') && $mainmenu == 'accountancy') {
				$modecompta = 'BOOKKEEPINGCOLLECTED'; // Not yet implemented.
			}
			if ($modecompta && ((isModEnabled('fournisseur') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || isModEnabled('supplier_invoice'))) {
				if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy_report/', $leftmenu)) {
					$newmenu->add("/compta/stats/supplier_turnover.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ReportPurchaseTurnoverCollected"), 2, $user->hasRight('accounting', 'comptarapport', 'lire'));
					$newmenu->add("/compta/stats/supplier_turnover_by_thirdparty.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByCompanies"), 3, $user->hasRight('accounting', 'comptarapport', 'lire'));
				}
			}
		}

		// Accountancy (simple)
		if (isModEnabled('comptabilite')) {
			// Files
			if (!getDolGlobalString('ACCOUNTANCY_HIDE_EXPORT_FILES_MENU')) {
				$newmenu->add("/compta/accounting-files.php?mainmenu=accountancy&leftmenu=accountancy_files", $langs->trans("AccountantFiles"), 0, $user->hasRight('compta', 'resultat', 'lire'), '', $mainmenu, 'files', 0, '', '', '', img_picto('', 'accountancy', 'class="paddingright pictofixedwidth"'));
			}

			// Bilan, resultats
			$newmenu->add("/compta/resultat/index.php?leftmenu=report&mainmenu=accountancy", $langs->trans("Reportings"), 0, $user->hasRight('compta', 'resultat', 'lire'), '', $mainmenu, 'ca', 0, '', '', '', img_picto('', 'accountancy', 'class="paddingright pictofixedwidth"'));

			if ($usemenuhider || empty($leftmenu) || preg_match('/report/', $leftmenu)) {
				$newmenu->add("/compta/resultat/index.php?leftmenu=report", $langs->trans("MenuReportInOut"), 1, $user->hasRight('compta', 'resultat', 'lire'));
				$newmenu->add("/compta/resultat/clientfourn.php?leftmenu=report", $langs->trans("ByPredefinedAccountGroups"), 2, $user->hasRight('compta', 'resultat', 'lire'));
				/* On verra ca avec module compabilite expert
				 $newmenu->add("/compta/resultat/compteres.php?leftmenu=report","Compte de resultat",2,$user->hasRight('compta',  'resultat', 'lire'));
				 $newmenu->add("/compta/resultat/bilan.php?leftmenu=report","Bilan",2,$user->hasRight('compta',  'resultat', 'lire'));
				 */

				/*
				 $newmenu->add("/compta/stats/cumul.php?leftmenu=report","Cumule",2,$user->hasRight('compta',  'resultat', 'lire'));
				 if (isModEnabled('propal')) {
				 $newmenu->add("/compta/stats/prev.php?leftmenu=report","Previsionnel",2,$user->hasRight('compta',  'resultat', 'lire'));
				 $newmenu->add("/compta/stats/comp.php?leftmenu=report","Transferred",2,$user->hasRight('compta',  'resultat', 'lire'));
				 }
				 */

				$modecompta = 'CREANCES-DETTES';
				$newmenu->add("/compta/stats/index.php?leftmenu=report&modecompta=".$modecompta, $langs->trans("ReportTurnover"), 1, $user->hasRight('compta', 'resultat', 'lire'));
				$newmenu->add("/compta/stats/casoc.php?leftmenu=report&modecompta=".$modecompta, $langs->trans("ByCompanies"), 2, $user->hasRight('compta', 'resultat', 'lire'));
				$newmenu->add("/compta/stats/cabyuser.php?leftmenu=report&modecompta=".$modecompta, $langs->trans("ByUsers"), 2, $user->hasRight('compta', 'resultat', 'lire'));
				$newmenu->add("/compta/stats/cabyprodserv.php?leftmenu=report&modecompta=".$modecompta, $langs->trans("ByProductsAndServices"), 2, $user->hasRight('compta', 'resultat', 'lire'));
				$newmenu->add("/compta/stats/byratecountry.php?leftmenu=report&modecompta=".$modecompta, $langs->trans("ByVatRate"), 2, $user->hasRight('compta', 'resultat', 'lire'));

				$modecompta = 'RECETTES-DEPENSES';
				$newmenu->add("/compta/stats/index.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ReportTurnoverCollected"), 1, $user->hasRight('compta', 'resultat', 'lire'));
				$newmenu->add("/compta/stats/casoc.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByCompanies"), 2, $user->hasRight('compta', 'resultat', 'lire'));
				$newmenu->add("/compta/stats/cabyuser.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByUsers"), 2, $user->hasRight('compta', 'resultat', 'lire'));

				//Achats
				$modecompta = 'CREANCES-DETTES';
				$newmenu->add("/compta/stats/supplier_turnover.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ReportPurchaseTurnover"), 1, $user->hasRight('compta', 'resultat', 'lire'));
				$newmenu->add("/compta/stats/supplier_turnover_by_thirdparty.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByCompanies"), 2, $user->hasRight('compta', 'resultat', 'lire'));
				$newmenu->add("/compta/stats/supplier_turnover_by_prodserv.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByProductsAndServices"), 2, $user->hasRight('compta', 'resultat', 'lire'));

				/*
				 $modecompta = 'RECETTES-DEPENSES';
				 $newmenu->add("/compta/stats/index.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ReportPurchaseTurnoverCollected"), 1, $user->hasRight('compta',  'resultat', 'lire'));
				 $newmenu->add("/compta/stats/casoc.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByCompanies"), 2, $user->hasRight('compta',  'resultat', 'lire'));
				 $newmenu->add("/compta/stats/cabyuser.php?leftmenu=accountancy_report&modecompta=".$modecompta, $langs->trans("ByUsers"), 2, $user->hasRight('compta',  'resultat', 'lire'));
				 */

				// Journals
				$newmenu->add("/compta/journal/sellsjournal.php?leftmenu=report", $langs->trans("SellsJournal"), 1, $user->hasRight('compta', 'resultat', 'lire'), '', '', '', 50);
				$newmenu->add("/compta/journal/purchasesjournal.php?leftmenu=report", $langs->trans("PurchasesJournal"), 1, $user->hasRight('compta', 'resultat', 'lire'), '', '', '', 51);
			}
			//if ($leftmenu=="ca") $newmenu->add("/compta/journaux/index.php?leftmenu=ca",$langs->trans("Journals"),1,$user->hasRight('compta',  'resultat', 'lire')||$user->hasRight('accounting',  'comptarapport', 'lire'));
		}

		// Intracomm report
		if (isModEnabled('intracommreport')) {
			$newmenu->add("/intracommreport/list.php?leftmenu=intracommreport", $langs->trans("MenuIntracommReport"), 0, $user->hasRight('intracommreport', 'read'), '', $mainmenu, 'intracommreport', 60, '', '', '', img_picto('', 'intracommreport', 'class="paddingright pictofixedwidth"'));
			if ($usemenuhider || empty($leftmenu) || preg_match('/intracommreport/', $leftmenu)) {
				// DEB / DES
				$newmenu->add("/intracommreport/card.php?action=create&leftmenu=intracommreport", $langs->trans("MenuIntracommReportNew"), 1, $user->hasRight('intracommreport', 'write'), '', $mainmenu, 'intracommreport', 1);
				$newmenu->add("/intracommreport/list.php?leftmenu=intracommreport", $langs->trans("MenuIntracommReportList"), 1, $user->hasRight('intracommreport', 'read'), '', $mainmenu, 'intracommreport', 1);
			}
		}

		// Assets
		if (isModEnabled('asset')) {
			$newmenu->add("/asset/list.php?leftmenu=asset&amp;mainmenu=accountancy", $langs->trans("MenuAssets"), 0, $user->hasRight('asset', 'read'), '', $mainmenu, 'asset', 100, '', '', '', img_picto('', 'payment', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/asset/card.php?leftmenu=asset&amp;action=create", $langs->trans("MenuNewAsset"), 1, $user->hasRight('asset', 'write'));
			$newmenu->add("/asset/list.php?leftmenu=asset&amp;mainmenu=accountancy", $langs->trans("MenuListAssets"), 1, $user->hasRight('asset', 'read'));
			$newmenu->add("/asset/model/list.php?leftmenu=asset_model", $langs->trans("MenuAssetModels"), 1, (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'read')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'model_advance', 'read')), '', $mainmenu, 'asset_model');
			if ($usemenuhider || empty($leftmenu) || preg_match('/asset_model/', $leftmenu)) {
				$newmenu->add("/asset/model/card.php?leftmenu=asset_model&amp;action=create", $langs->trans("MenuNewAssetModel"), 2, (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'write')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'model_advance', 'write')));
				$newmenu->add("/asset/model/list.php?leftmenu=asset_model", $langs->trans("MenuListAssetModels"), 2, (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'read')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('asset', 'model_advance', 'read')));
			}
		}
	}
}

/**
 * Get left Menu BANK
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_bank($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'bank') {
		// Load translation files required by the page
		$langs->loadLangs(array("withdrawals", "banks", "bills", "categories"));

		// Bank-Cash account
		if (isModEnabled('bank')) {
			$newmenu->add("/compta/bank/list.php?leftmenu=bank&amp;mainmenu=bank&amp;search_status=opened", $langs->trans("MenuBankCash"), 0, $user->hasRight('banque', 'lire'), '', $mainmenu, 'bank', 0, '', '', '', img_picto('', 'bank_account', 'class="paddingright pictofixedwidth"'));

			$newmenu->add("/compta/bank/card.php?action=create", $langs->trans("MenuNewFinancialAccount"), 1, $user->hasRight('banque', 'configurer'));
			$newmenu->add("/compta/bank/list.php?leftmenu=bank&amp;mainmenu=bank&amp;search_status=opened", $langs->trans("List"), 1, $user->hasRight('banque', 'lire'), '', $mainmenu, 'bank');
			$newmenu->add("/compta/bank/bankentries_list.php", $langs->trans("ListTransactions"), 1, $user->hasRight('banque', 'lire'));
			$newmenu->add("/compta/bank/budget.php", $langs->trans("ListTransactionsByCategory"), 1, $user->hasRight('banque', 'lire'));

			$newmenu->add("/compta/bank/transfer.php", $langs->trans("MenuBankInternalTransfer"), 1, $user->hasRight('banque', 'transfer'));
		}

		if (isModEnabled('category')) {
			$langs->load("categories");
			$newmenu->add("/categories/index.php?type=5", $langs->trans("Rubriques"), 1, $user->hasRight('categorie', 'creer'), '', $mainmenu, 'tags');
			$newmenu->add("/compta/bank/categ.php", $langs->trans("RubriquesTransactions"), 1, $user->hasRight('banque', 'configurer'), '', $mainmenu, 'tags');
		}

		// Direct debit order
		if (isModEnabled('prelevement')) {
			$newmenu->add("/compta/prelevement/index.php?leftmenu=withdraw&amp;mainmenu=bank", $langs->trans("PaymentByDirectDebit"), 0, $user->hasRight('prelevement', 'bons', 'lire'), '', $mainmenu, 'withdraw', 0, '', '', '', img_picto('', 'payment', 'class="paddingright pictofixedwidth"'));

			if ($usemenuhider || empty($leftmenu) || $leftmenu == "withdraw") {
				$newmenu->add("/compta/prelevement/create.php?mainmenu=bank", $langs->trans("NewStandingOrder"), 1, $user->hasRight('prelevement', 'bons', 'creer'));

				$newmenu->add("/compta/prelevement/orders_list.php?mainmenu=bank", $langs->trans("WithdrawalsReceipts"), 1, $user->hasRight('prelevement', 'bons', 'lire'));
				$newmenu->add("/compta/prelevement/list.php?mainmenu=bank", $langs->trans("WithdrawalsLines"), 1, $user->hasRight('prelevement', 'bons', 'lire'));
				$newmenu->add("/compta/prelevement/rejets.php?mainmenu=bank", $langs->trans("Rejects"), 1, $user->hasRight('prelevement', 'bons', 'lire'));
				$newmenu->add("/compta/prelevement/stats.php?mainmenu=bank", $langs->trans("Statistics"), 1, $user->hasRight('prelevement', 'bons', 'lire'));
			}
		}

		// Bank transfer order
		if (isModEnabled('paymentbybanktransfer')) {
			$newmenu->add("/compta/paymentbybanktransfer/index.php?leftmenu=banktransfer&amp;mainmenu=bank", $langs->trans("PaymentByBankTransfer"), 0, $user->hasRight('paymentbybanktransfer', 'read'), '', $mainmenu, 'banktransfer', 0, '', '', '', img_picto('', 'payment', 'class="paddingright pictofixedwidth"'));

			if ($usemenuhider || empty($leftmenu) || $leftmenu == "banktransfer") {
				$newmenu->add("/compta/prelevement/create.php?type=bank-transfer&mainmenu=bank", $langs->trans("NewPaymentByBankTransfer"), 1, $user->hasRight('paymentbybanktransfer', 'create'));

				$newmenu->add("/compta/prelevement/orders_list.php?type=bank-transfer&mainmenu=bank", $langs->trans("PaymentByBankTransferReceipts"), 1, $user->hasRight('paymentbybanktransfer', 'read'));
				$newmenu->add("/compta/prelevement/list.php?type=bank-transfer&mainmenu=bank", $langs->trans("PaymentByBankTransferLines"), 1, $user->hasRight('paymentbybanktransfer', 'read'));
				$newmenu->add("/compta/prelevement/rejets.php?type=bank-transfer&mainmenu=bank", $langs->trans("Rejects"), 1, $user->hasRight('paymentbybanktransfer', 'read'));
				$newmenu->add("/compta/prelevement/stats.php?type=bank-transfer&mainmenu=bank", $langs->trans("Statistics"), 1, $user->hasRight('paymentbybanktransfer', 'read'));
			}
		}

		// Management of checks
		if (!getDolGlobalString('BANK_DISABLE_CHECK_DEPOSIT') && isModEnabled('bank') && (isModEnabled('invoice') || getDolGlobalString('MAIN_MENU_CHEQUE_DEPOSIT_ON'))) {
			$newmenu->add("/compta/paiement/cheque/index.php?leftmenu=checks&amp;mainmenu=bank", $langs->trans("MenuChequeDeposits"), 0, $user->hasRight('banque', 'cheque'), '', $mainmenu, 'checks', 0, '', '', '', img_picto('', 'payment', 'class="paddingright pictofixedwidth"'));
			if (preg_match('/checks/', $leftmenu)) {
				$newmenu->add("/compta/paiement/cheque/card.php?leftmenu=checks_bis&amp;action=new&amp;mainmenu=bank", $langs->trans("NewChequeDeposit"), 1, $user->hasRight('banque', 'cheque'));
				$newmenu->add("/compta/paiement/cheque/list.php?leftmenu=checks_bis&amp;mainmenu=bank", $langs->trans("List"), 1, $user->hasRight('banque', 'cheque'));
			}
		}

		// Cash Control
		if (isModEnabled('takepos') || isModEnabled('cashdesk')) {
			$permtomakecashfence = ($user->hasRight('cashdesk', 'run') || $user->hasRight('takepos', 'run'));
			$newmenu->add("/compta/cashcontrol/cashcontrol_list.php", $langs->trans("CashControl"), 0, $permtomakecashfence, '', $mainmenu, 'cashcontrol', 0, '', '', '', img_picto('', 'pos', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/compta/cashcontrol/cashcontrol_card.php?action=create", $langs->trans("NewCashFence"), 1, $permtomakecashfence);
			$newmenu->add("/compta/cashcontrol/cashcontrol_list.php", $langs->trans("List"), 1, $permtomakecashfence);
		}
	}
}

/**
 * Get left Menu PRODUCTS-SERVICES
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_products($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'products') {
		// Products
		if (isModEnabled('product')) {
			$newmenu->add("/product/index.php?leftmenu=product", $langs->trans("Products"), 0, $user->hasRight('product', 'read'), '', $mainmenu, 'product', 0, '', '', '', img_picto('', 'product', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/product/card.php?leftmenu=product&amp;action=create&amp;type=0", $langs->trans("NewProduct"), 1, $user->hasRight('product', 'creer'));
			$newmenu->add("/product/list.php?leftmenu=product&amp;type=0", $langs->trans("List"), 1, $user->hasRight('product', 'read'));
			if (isModEnabled('stock')) {
				$newmenu->add("/product/reassort.php?type=0", $langs->trans("MenuStocks"), 1, $user->hasRight('product', 'read') && $user->hasRight('stock', 'lire'));
			}
			if (isModEnabled('productbatch')) {
				$langs->load("stocks");
				$newmenu->add("/product/reassortlot.php?type=0&search_subjecttolotserial=1", $langs->trans("StocksByLotSerial"), 1, $user->hasRight('product', 'read') && $user->hasRight('stock', 'lire'));
				$newmenu->add("/product/stock/productlot_list.php", $langs->trans("LotSerial"), 1, $user->hasRight('product', 'read') && $user->hasRight('stock', 'lire'));
			}
			if (isModEnabled('variants')) {
				$newmenu->add("/variants/list.php", $langs->trans("VariantAttributes"), 1, $user->hasRight('product', 'read'));
			}
			if (isModEnabled('propal') || isModEnabled('order') || isModEnabled('invoice') || isModEnabled('supplier_proposal') || isModEnabled('supplier_order') || isModEnabled('supplier_invoice')) {
				$newmenu->add("/product/stats/card.php?id=all&leftmenu=stats&type=0", $langs->trans("Statistics"), 1, $user->hasRight('product', 'read'));
			}

			// Categories
			if (isModEnabled('category')) {
				$langs->load("categories");
				$newmenu->add("/categories/index.php?leftmenu=cat&amp;type=0", $langs->trans("Categories"), 1, $user->hasRight('categorie', 'lire'), '', $mainmenu, 'cat');
				//if ($usemenuhider || empty($leftmenu) || $leftmenu=="cat") $newmenu->add("/categories/list.php", $langs->trans("List"), 1, $user->hasRight('categorie',  'lire'));
			}
		}

		// Services
		if (isModEnabled('service')) {
			$newmenu->add("/product/index.php?leftmenu=service", $langs->trans("Services"), 0, $user->hasRight('service', 'read'), '', $mainmenu, 'service', 0, '', '', '', img_picto('', 'service', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/product/card.php?leftmenu=service&amp;action=create&amp;type=1", $langs->trans("NewService"), 1, $user->hasRight('service', 'creer'));
			$newmenu->add("/product/list.php?leftmenu=service&amp;type=1", $langs->trans("List"), 1, $user->hasRight('service', 'read'));

			if (isModEnabled('stock') && getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
				$newmenu->add("/product/reassort.php?type=1", $langs->trans("MenuStocks"), 1, $user->hasRight('service', 'read') && $user->hasRight('stock', 'lire'));
			}
			if (isModEnabled('variants')) {
				$newmenu->add("/variants/list.php", $langs->trans("VariantAttributes"), 1, $user->hasRight('service', 'read'));
			}
			if (isModEnabled('propal') || isModEnabled('order') || isModEnabled('invoice') || isModEnabled('supplier_proposal') || isModEnabled('supplier_order') || isModEnabled('supplier_invoice')) {
				$newmenu->add("/product/stats/card.php?id=all&leftmenu=stats&type=1", $langs->trans("Statistics"), 1, $user->hasRight('service', 'read'));
			}
			// Categories
			if (isModEnabled('category')) {
				$langs->load("categories");
				$newmenu->add("/categories/index.php?leftmenu=cat&amp;type=0", $langs->trans("Categories"), 1, $user->hasRight('categorie', 'lire'), '', $mainmenu, 'cat');
				//if ($usemenuhider || empty($leftmenu) || $leftmenu=="cat") $newmenu->add("/categories/list.php", $langs->trans("List"), 1, $user->hasRight('categorie',  'lire'));
			}
		}

		// Warehouse
		if (isModEnabled('stock')) {
			$langs->load("stocks");
			$newmenu->add("/product/index.php?leftmenu=stock", $langs->trans("Warehouses"), 0, $user->hasRight('stock', 'lire'), '', $mainmenu, 'stock', 0, '', '', '', img_picto('', 'stock', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/product/stock/card.php?action=create", $langs->trans("MenuNewWarehouse"), 1, $user->hasRight('stock', 'creer'));
			$newmenu->add("/product/stock/list.php", $langs->trans("List"), 1, $user->hasRight('stock', 'lire'));
			$newmenu->add("/product/stock/movement_list.php", $langs->trans("Movements"), 1, $user->hasRight('stock', 'mouvement', 'lire'));

			$newmenu->add("/product/stock/massstockmove.php?init=1", $langs->trans("MassStockTransferShort"), 1, $user->hasRight('stock', 'mouvement', 'creer'));
			if (isModEnabled('supplier_order')) {
				$newmenu->add("/product/stock/replenish.php", $langs->trans("Replenishment"), 1, $user->hasRight('stock', 'mouvement', 'creer') && $user->hasRight('fournisseur', 'lire'));
			}
			$newmenu->add("/product/stock/stockatdate.php", $langs->trans("StockAtDate"), 1, $user->hasRight('product', 'read') && $user->hasRight('stock', 'lire'));

			// Categories for warehouses
			if (isModEnabled('category')) {
				$newmenu->add("/categories/index.php?leftmenu=stock&amp;type=9", $langs->trans("Categories"), 1, $user->hasRight('categorie', 'lire'), '', $mainmenu, 'cat');
			}
		}

		if (isModEnabled('stocktransfer')) {
			$newmenu->add('/product/stock/stocktransfer/stocktransfer_list.php', $langs->trans("ModuleStockTransferName"), 0, $user->hasRight('stocktransfer', 'stocktransfer', 'read'), '', $mainmenu, 'stocktransfer', 0, '', '', '', img_picto('', 'stock', 'class="paddingright pictofixedwidth"'));
			$newmenu->add('/product/stock/stocktransfer/stocktransfer_card.php?action=create', $langs->trans('StockTransferNew'), 1, $user->hasRight('stocktransfer', 'stocktransfer', 'write'));
			$newmenu->add('/product/stock/stocktransfer/stocktransfer_list.php', $langs->trans('List'), 1, $user->hasRight('stocktransfer', 'stocktransfer', 'read'));
		}

		// Inventory
		if (isModEnabled('stock')) {
			$langs->load("stocks");
			if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
				$newmenu->add("/product/inventory/list.php?leftmenu=stock_inventories", $langs->trans("Inventories"), 0, $user->hasRight('stock', 'lire'), '', $mainmenu, 'stock', 0, '', '', '', img_picto('', 'inventory', 'class="paddingright pictofixedwidth"'));
				if ($usemenuhider || empty($leftmenu) || $leftmenu == "stock_inventories") {
					$newmenu->add("/product/inventory/card.php?action=create&leftmenu=stock_inventories", $langs->trans("NewInventory"), 1, $user->hasRight('stock', 'creer'));
					$newmenu->add("/product/inventory/list.php?leftmenu=stock_inventories", $langs->trans("List"), 1, $user->hasRight('stock', 'lire'));
				}
			} else {
				$newmenu->add("/product/inventory/list.php?leftmenu=stock_inventories", $langs->trans("Inventories"), 0, $user->hasRight('stock', 'inventory_advance', 'read'), '', $mainmenu, 'stock', 0, '', '', '', img_picto('', 'inventory', 'class="paddingright pictofixedwidth"'));
				if ($usemenuhider || empty($leftmenu) || $leftmenu == "stock_inventories") {
					$newmenu->add("/product/inventory/card.php?action=create&leftmenu=stock_inventories", $langs->trans("NewInventory"), 1, $user->hasRight('stock', 'inventory_advance', 'write'));
					$newmenu->add("/product/inventory/list.php?leftmenu=stock_inventories", $langs->trans("List"), 1, $user->hasRight('stock', 'inventory_advance', 'read'));
				}
			}
		}

		// Shipments
		if (isModEnabled('shipping')) {
			$langs->load("sendings");
			$newmenu->add("/expedition/index.php?leftmenu=sendings", $langs->trans("Shipments"), 0, $user->hasRight('expedition', 'lire'), '', $mainmenu, 'sendings', 0, '', '', '', img_picto('', 'shipment', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/expedition/card.php?action=create2&amp;leftmenu=sendings", $langs->trans("NewSending"), 1, $user->hasRight('expedition', 'creer'));
			$newmenu->add("/expedition/list.php?leftmenu=sendings", $langs->trans("List"), 1, $user->hasRight('expedition', 'lire'));
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "sendings") {
				$newmenu->add("/expedition/list.php?leftmenu=sendings&search_status=0", $langs->trans("StatusSendingDraftShort"), 2, $user->hasRight('expedition', 'lire'));
				$newmenu->add("/expedition/list.php?leftmenu=sendings&search_status=1", $langs->trans("StatusSendingValidatedShort"), 2, $user->hasRight('expedition', 'lire'));
				$newmenu->add("/expedition/list.php?leftmenu=sendings&search_status=2", $langs->trans("StatusSendingProcessedShort"), 2, $user->hasRight('expedition', 'lire'));
			}
			$newmenu->add("/expedition/stats/index.php?leftmenu=sendings", $langs->trans("Statistics"), 1, $user->hasRight('expedition', 'lire'));
		}

		// Receptions
		if (isModEnabled('reception')) {
			$langs->load("receptions");
			$newmenu->add("/reception/index.php?leftmenu=receptions", $langs->trans("Receptions"), 0, $user->hasRight('reception', 'lire'), '', $mainmenu, 'receptions', 0, '', '', '', img_picto('', 'dollyrevert', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/reception/card.php?action=create2&amp;leftmenu=receptions", $langs->trans("NewReception"), 1, $user->hasRight('reception', 'creer'));
			$newmenu->add("/reception/list.php?leftmenu=receptions", $langs->trans("List"), 1, $user->hasRight('reception', 'lire'));
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "receptions") {
				$newmenu->add("/reception/list.php?leftmenu=receptions&search_status=0", $langs->trans("StatusReceptionDraftShort"), 2, $user->hasRight('reception', 'lire'));
			}
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "receptions") {
				$newmenu->add("/reception/list.php?leftmenu=receptions&search_status=1", $langs->trans("StatusReceptionValidatedShort"), 2, $user->hasRight('reception', 'lire'));
			}
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "receptions") {
				$newmenu->add("/reception/list.php?leftmenu=receptions&search_status=2", $langs->trans("StatusReceptionProcessedShort"), 2, $user->hasRight('reception', 'lire'));
			}
			$newmenu->add("/reception/stats/index.php?leftmenu=receptions", $langs->trans("Statistics"), 1, $user->hasRight('reception', 'lire'));
		}
	}
}

/**
 * Get left Menu PRODUCTS-SERVICES MRP - GPAO
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_mrp($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'mrp') {
		// BOM
		if (isModEnabled('bom') || isModEnabled('mrp')) {
			$langs->load("mrp");

			$newmenu->add("", $langs->trans("MenuBOM"), 0, $user->hasRight('bom', 'read'), '', $mainmenu, 'bom', 0, '', '', '', img_picto('', 'bom', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/bom/bom_card.php?leftmenu=bom&amp;action=create", $langs->trans("NewBOM"), 1, $user->hasRight('bom', 'write'), '', $mainmenu, 'bom');
			$newmenu->add("/bom/bom_list.php?leftmenu=bom", $langs->trans("List"), 1, $user->hasRight('bom', 'read'), '', $mainmenu, 'bom');
		}

		if (isModEnabled('mrp')) {
			$langs->load("mrp");

			$newmenu->add("", $langs->trans("MenuMRP"), 0, $user->hasRight('mrp', 'read'), '', $mainmenu, 'mrp', 0, '', '', '', img_picto('', 'mrp', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/mrp/mo_card.php?leftmenu=mo&amp;action=create", $langs->trans("NewMO"), 1, $user->hasRight('mrp', 'write'), '', $mainmenu, '');
			$newmenu->add("/mrp/mo_list.php?leftmenu=mo", $langs->trans("List"), 1, $user->hasRight('mrp', 'read'), '', $mainmenu, '');
		}
	}
}

/**
 * Get left Menu PROJECTS
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_projects($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'project') {
		if (isModEnabled('project')) {
			$langs->load("projects");

			$search_project_user = GETPOSTINT('search_project_user');

			$tmpentry = array(
				'enabled' => isModEnabled('project'),
				'perms' => $user->hasRight('projet', 'lire'),
				'module' => 'projet'
			);
			$listofmodulesforexternal = explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL'));
			$showmode = isVisibleToUserType($type_user, $tmpentry, $listofmodulesforexternal);

			$titleboth = $langs->trans("LeadsOrProjects");
			$titlenew = $langs->trans("NewLeadOrProject"); // Leads and opportunities by default
			if (!getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
				$titleboth = $langs->trans("Projects");
				$titlenew = $langs->trans("NewProject");
			}
			if (getDolGlobalInt('PROJECT_USE_OPPORTUNITIES') == 2) {	// 2 = leads only
				$titleboth = $langs->trans("Leads");
				$titlenew = $langs->trans("NewLead");
			}

			// Project assigned to user
			$newmenu->add("/projet/index.php?leftmenu=projects".($search_project_user ? '&search_project_user='.$search_project_user : ''), $titleboth, 0, $user->hasRight('projet', 'lire'), '', $mainmenu, 'projects', 0, '', '', '', img_picto('', 'project', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/projet/card.php?leftmenu=projects&action=create".($search_project_user ? '&search_project_user='.$search_project_user : ''), $titlenew, 1, $user->hasRight('projet', 'creer'));

			if (!getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
				$newmenu->add("/projet/list.php?leftmenu=projects".($search_project_user ? '&search_project_user='.$search_project_user : '').'&search_status=99', $langs->trans("List"), 1, $showmode, '', 'project', 'list');
			} elseif (getDolGlobalInt('PROJECT_USE_OPPORTUNITIES') == 1) {
				$newmenu->add("/projet/list.php?leftmenu=projects".($search_project_user ? '&search_project_user='.$search_project_user : ''), $langs->trans("List"), 1, $showmode, '', 'project', 'list');
				$newmenu->add('/projet/list.php?mainmenu=project&amp;leftmenu=list&search_usage_opportunity=1&search_status=99&search_opp_status=openedopp&contextpage=lead', $langs->trans("ListOpenLeads"), 2, $showmode);
				$newmenu->add('/projet/list.php?mainmenu=project&amp;leftmenu=list&search_opp_status=notopenedopp&search_status=99&contextpage=project', $langs->trans("ListOpenProjects"), 2, $showmode);
			} elseif (getDolGlobalInt('PROJECT_USE_OPPORTUNITIES') == 2) {	// 2 = leads only
				$newmenu->add('/projet/list.php?mainmenu=project&amp;leftmenu=list&search_usage_opportunity=1&search_status=99', $langs->trans("List"), 2, $showmode);
			}

			$newmenu->add("/projet/stats/index.php?leftmenu=projects", $langs->trans("Statistics"), 1, $user->hasRight('projet', 'lire'));

			// Categories
			if (isModEnabled('category')) {
				$langs->load("categories");
				$newmenu->add("/categories/index.php?leftmenu=cat&amp;type=6", $langs->trans("Categories"), 1, $user->hasRight('categorie', 'lire'), '', $mainmenu, 'cat');
			}

			if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
				// Project assigned to user
				$newmenu->add("/projet/activity/index.php?leftmenu=tasks".($search_project_user ? '&search_project_user='.$search_project_user : ''), $langs->trans("Activities"), 0, $user->hasRight('projet', 'lire'), '', 'project', 'tasks', 0, '', '', '', img_picto('', 'projecttask', 'class="paddingright pictofixedwidth"'));
				$newmenu->add("/projet/tasks.php?leftmenu=tasks&action=create", $langs->trans("NewTask"), 1, $user->hasRight('projet', 'creer'));
				$newmenu->add("/projet/tasks/list.php?leftmenu=tasks".($search_project_user ? '&search_project_user='.$search_project_user : ''), $langs->trans("List"), 1, $user->hasRight('projet', 'lire'));
				$newmenu->add("/projet/tasks/stats/index.php?leftmenu=projects", $langs->trans("Statistics"), 1, $user->hasRight('projet', 'lire'));

				$newmenu->add("/projet/activity/perweek.php?leftmenu=tasks".($search_project_user ? '&search_project_user='.$search_project_user : ''), $langs->trans("TimeEntry"), 0, $user->hasRight('projet', 'lire'), '', 'project', 'timespent', 0, '', '', '', img_picto('', 'timespent', 'class="paddingright pictofixedwidth"'));
				$newmenu->add("/projet/tasks/time.php?leftmenu=tasks".($search_project_user ? '&search_project_user='.$search_project_user : ''), $langs->trans("List"), 1, $user->hasRight('projet', 'lire'));
			}
		}
	}
}

/**
 * Get left Menu HRM
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_hrm($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'hrm') {
		// HRM module
		if (isModEnabled('hrm')) {
			$langs->load("hrm");

			$newmenu->add("/user/list.php?mainmenu=hrm&leftmenu=hrm&contextpage=employeelist", $langs->trans("Employees"), 0, $user->hasRight('user', 'user', 'read'), '', $mainmenu, 'hrm', 0, '', '', '', img_picto('', 'user', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/user/card.php?mainmenu=hrm&leftmenu=hrm&action=create&employee=1", $langs->trans("NewEmployee"), 1, $user->hasRight('user', 'user', 'write'));
			$newmenu->add("/user/list.php?mainmenu=hrm&leftmenu=hrm&contextpage=employeelist", $langs->trans("List"), 1, $user->hasRight('user', 'user', 'read'));

			$newmenu->add("/hrm/skill_list.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("SkillsManagement"), 0, $user->hasRight('hrm', 'all', 'read'), '', $mainmenu, 'hrm_sm', 0, '', '', '', img_picto('', 'shapes', 'class="paddingright pictofixedwidth"'));

			// Skills
			$newmenu->add("/hrm/skill_list.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("Skills"), 1, $user->hasRight('hrm', 'all', 'read'), '', $mainmenu, 'hrm_sm', 0, '', '', '', img_picto('', 'shapes', 'class="paddingright pictofixedwidth"'));
			//$newmenu->add("/hrm/skill_card.php?mainmenu=hrm&leftmenu=hrm_sm&action=create", $langs->trans("NewSkill"), 1, $user->hasRight('hrm',  'all', 'write'));
			//$newmenu->add("/hrm/skill_list.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("List"), 1, $user->hasRight('hrm',  'all', 'read'));

			// Job (Description of work to do and skills required)
			$newmenu->add("/hrm/job_list.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("JobsProfiles"), 1, $user->hasRight('hrm', 'all', 'read'), '', $mainmenu, 'hrm_sm', 0, '', '', '', img_picto('', 'technic', 'class="paddingright pictofixedwidth"'));
			//$newmenu->add("/hrm/job_card.php?mainmenu=hrm&leftmenu=hrm_sm&action=create", $langs->transnoentities("NewObject", $langs->trans("Job")), 1, $user->hasRight('hrm',  'all', 'write'));
			//$newmenu->add("/hrm/job_list.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("List"), 1, $user->hasRight('hrm',  'all', 'read'));

			// Position = Link job - user
			$newmenu->add("/hrm/position_list.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("EmployeePositions"), 1, $user->hasRight('hrm', 'all', 'read'), '', $mainmenu, 'hrm_sm', 0, '', '', '', img_picto('', 'user-cog', 'class="paddingright pictofixedwidth"'));
			//$newmenu->add("/hrm/position.php?mainmenu=hrm&leftmenu=hrm_sm&action=create", $langs->transnoentities("NewObject", $langs->trans("Position")), 1, $user->hasRight('hrm',  'all', 'write'));
			//$newmenu->add("/hrm/position_list.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("List"), 1, $user->hasRight('hrm',  'all', 'read'));

			// Evaluation
			$newmenu->add("/hrm/evaluation_list.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("Evals"), 1, $user->hasRight('hrm', 'evaluation', 'read'), '', $mainmenu, 'hrm_sm', 0, '', '', '', img_picto('', 'user', 'class="paddingright pictofixedwidth"'));
			//$newmenu->add("/hrm/evaluation_card.php?mainmenu=hrm&leftmenu=hrm_sm&action=create", $langs->trans("NewEval"), 1, $user->hasRight('hrm',  'evaluation', 'write'));
			//$newmenu->add("/hrm/evaluation_list.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("List"), 1, $user->hasRight('hrm', 'evaluation', 'read'));
			$newmenu->add("/hrm/compare.php?mainmenu=hrm&leftmenu=hrm_sm", $langs->trans("SkillComparison"), 1, $user->hasRight('hrm', 'evaluation', 'read') || $user->hasRight('hrm', 'compare_advance', 'read'));
		}

		// Leave/Holiday/Vacation module
		if (isModEnabled('holiday')) {
			// Load translation files required by the page
			$langs->loadLangs(array("holiday", "trips"));

			$newmenu->add("/holiday/list.php?mainmenu=hrm&leftmenu=holiday", $langs->trans("CPTitreMenu"), 0, $user->hasRight('holiday', 'read'), '', $mainmenu, 'holiday', 0, '', '', '', img_picto('', 'holiday', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/holiday/card.php?mainmenu=hrm&leftmenu=holiday&action=create", $langs->trans("New"), 1, $user->hasRight('holiday', 'write'), '', $mainmenu);
			$newmenu->add("/holiday/card_group.php?mainmenu=hrm&leftmenu=holiday&action=create", $langs->trans("NewHolidayForGroup"), 1, ($user->hasRight('holiday', 'writeall') && $user->hasRight('holiday', 'readall')), '', $mainmenu, 'holiday_sm');
			$newmenu->add("/holiday/list.php?mainmenu=hrm&leftmenu=holiday", $langs->trans("List"), 1, $user->hasRight('holiday', 'read'), '', $mainmenu);
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "holiday") {
				$newmenu->add("/holiday/list.php?search_status=1&mainmenu=hrm&leftmenu=holiday", $langs->trans("DraftCP"), 2, $user->hasRight('holiday', 'read'), '', $mainmenu, 'holiday_sm');
				$newmenu->add("/holiday/list.php?search_status=2&mainmenu=hrm&leftmenu=holiday", $langs->trans("ToReviewCP"), 2, $user->hasRight('holiday', 'read'), '', $mainmenu, 'holiday_sm');
				$newmenu->add("/holiday/list.php?search_status=3&mainmenu=hrm&leftmenu=holiday", $langs->trans("ApprovedCP"), 2, $user->hasRight('holiday', 'read'), '', $mainmenu, 'holiday_sm');
				$newmenu->add("/holiday/list.php?search_status=4&mainmenu=hrm&leftmenu=holiday", $langs->trans("CancelCP"), 2, $user->hasRight('holiday', 'read'), '', $mainmenu, 'holiday_sm');
				$newmenu->add("/holiday/list.php?search_status=5&mainmenu=hrm&leftmenu=holiday", $langs->trans("RefuseCP"), 2, $user->hasRight('holiday', 'read'), '', $mainmenu, 'holiday_sm');
			}
			$newmenu->add("/holiday/define_holiday.php?mainmenu=hrm", $langs->trans("MenuConfCP"), 1, $user->hasRight('holiday', 'read'), '', $mainmenu, 'holiday_sm');
			$newmenu->add("/holiday/month_report.php?mainmenu=hrm&leftmenu=holiday", $langs->trans("MenuReportMonth"), 1, $user->hasRight('holiday', 'readall'), '', $mainmenu, 'holiday_sm');
			$newmenu->add("/holiday/view_log.php?mainmenu=hrm&leftmenu=holiday", $langs->trans("MenuLogCP"), 1, $user->hasRight('holiday', 'define_holiday'), '', $mainmenu, 'holiday_sm');
		}

		// Trips and expenses (old module)
		if (isModEnabled('deplacement')) {
			$langs->load("trips");
			$newmenu->add("/compta/deplacement/index.php?leftmenu=tripsandexpenses&amp;mainmenu=hrm", $langs->trans("TripsAndExpenses"), 0, $user->hasRight('deplacement', 'lire'), '', $mainmenu, 'tripsandexpenses', 0, '', '', '', img_picto('', 'trip', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/compta/deplacement/card.php?action=create&amp;leftmenu=tripsandexpenses&amp;mainmenu=hrm", $langs->trans("New"), 1, $user->hasRight('deplacement', 'creer'));
			$newmenu->add("/compta/deplacement/list.php?leftmenu=tripsandexpenses&amp;mainmenu=hrm", $langs->trans("List"), 1, $user->hasRight('deplacement', 'lire'));
			$newmenu->add("/compta/deplacement/stats/index.php?leftmenu=tripsandexpenses&amp;mainmenu=hrm", $langs->trans("Statistics"), 1, $user->hasRight('deplacement', 'lire'));
		}

		// Expense report
		if (isModEnabled('expensereport')) {
			$langs->loadLangs(array("trips", "bills"));
			$newmenu->add("/expensereport/index.php?leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("TripsAndExpenses"), 0, $user->hasRight('expensereport', 'lire'), '', $mainmenu, 'expensereport', 0, '', '', '', img_picto('', 'expensereport', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/expensereport/card.php?action=create&amp;leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("New"), 1, $user->hasRight('expensereport', 'creer'));
			$newmenu->add("/expensereport/list.php?leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("List"), 1, $user->hasRight('expensereport', 'lire'));
			if ($usemenuhider || empty($leftmenu) || $leftmenu == "expensereport") {
				$newmenu->add("/expensereport/list.php?search_status=0&amp;leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("Draft"), 2, $user->hasRight('expensereport', 'lire'));
				$newmenu->add("/expensereport/list.php?search_status=2&amp;leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("Validated"), 2, $user->hasRight('expensereport', 'lire'));
				$newmenu->add("/expensereport/list.php?search_status=5&amp;leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("Approved"), 2, $user->hasRight('expensereport', 'lire'));
				$newmenu->add("/expensereport/list.php?search_status=6&amp;leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("Paid"), 2, $user->hasRight('expensereport', 'lire'));
				$newmenu->add("/expensereport/list.php?search_status=4&amp;leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("Canceled"), 2, $user->hasRight('expensereport', 'lire'));
				$newmenu->add("/expensereport/list.php?search_status=99&amp;leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("Refused"), 2, $user->hasRight('expensereport', 'lire'));
			}
			$newmenu->add("/expensereport/payment/list.php?leftmenu=expensereport_payments&amp;mainmenu=hrm", $langs->trans("Payments"), 1, ($user->hasRight('expensereport', 'lire')) && isModEnabled('bank'));
			$newmenu->add("/expensereport/stats/index.php?leftmenu=expensereport&amp;mainmenu=hrm", $langs->trans("Statistics"), 1, $user->hasRight('expensereport', 'lire'));
		}

		if (isModEnabled('project')) {
			if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
				$langs->load("projects");

				$search_project_user = GETPOSTINT('search_project_user');

				$newmenu->add("/projet/activity/perweek.php?leftmenu=tasks".($search_project_user ? '&search_project_user='.$search_project_user : ''), $langs->trans("TimeEntry"), 0, $user->hasRight('projet', 'lire'), '', $mainmenu, 'timespent', 0, '', '', '', img_picto('', 'timespent', 'class="paddingright pictofixedwidth"'));
			}
		}
	}
}


/**
 * Get left Menu TOOLS
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_tools($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'tools') {
		if (empty($user->socid)) { // limit to internal users
			$langs->load("mails");
			$newmenu->add("/admin/mails_templates.php?leftmenu=email_templates", $langs->trans("EMailTemplates"), 0, 1, '', $mainmenu, 'email_templates', 0, '', '', '', img_picto('', 'email', 'class="paddingright pictofixedwidth"'));
		}

		if (isModEnabled('mailing')) {
			$titleindex = $langs->trans("EMailings");
			$titlenew = $langs->trans("NewMailing");
			$titlelist = $langs->trans("List");
			if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
				$titleindex .= ' | '.$langs->trans("SMSings");
				$titlenew .= ' | '.$langs->trans("NewSMSing");
			}
			$newmenu->add("/comm/mailing/index.php?leftmenu=mailing", $titleindex, 0, $user->hasRight('mailing', 'lire'), '', $mainmenu, 'mailing', 0, '', '', '', img_picto('', 'email', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/comm/mailing/card.php?leftmenu=mailing&amp;action=create", $titlenew, 1, $user->hasRight('mailing', 'creer'));
			$newmenu->add("/comm/mailing/list.php?leftmenu=mailing", $titlelist, 1, $user->hasRight('mailing', 'lire'));
		}

		if (isModEnabled('import')) {
			$langs->load("exports");
			$newmenu->add("/imports/index.php?leftmenu=import", $langs->trans("FormatedImport"), 0, $user->hasRight('import', 'run'), '', $mainmenu, 'import', 0, '', '', '', img_picto('', 'technic', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/imports/import.php?leftmenu=import", $langs->trans("NewImport"), 1, $user->hasRight('import', 'run'));
		}

		if (isModEnabled('export')) {
			$langs->load("exports");
			$newmenu->add("/exports/index.php?leftmenu=export", $langs->trans("FormatedExport"), 0, $user->hasRight('export', 'lire'), '', $mainmenu, 'export', 0, '', '', '', img_picto('', 'technic', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/exports/export.php?leftmenu=export", $langs->trans("NewExport"), 1, $user->hasRight('export', 'creer'));
			//$newmenu->add("/exports/export.php?leftmenu=export",$langs->trans("List"),1, $user->hasRight('export',  'lire'));
		}
	}
}

/**
 * Get left Menu MEMBERS
 *
 * @param	string		$mainmenu		Main menu
 * @param	Menu 		$newmenu		Object Menu to return back list of menu entries
 * @param	int 		$usemenuhider	Use menu hider
 * @param	string 		$leftmenu		Left menu
 * @param	int 		$type_user		Type of targeted user for menu
 * @return	void
 */
function get_left_menu_members($mainmenu, &$newmenu, $usemenuhider = 1, $leftmenu = 'none', $type_user = 0)
{
	global $user, $conf, $langs;

	if ($mainmenu == 'members') {
		if (isModEnabled('member')) {
			// Load translation files required by the page
			$langs->loadLangs(array("members", "compta"));

			$newmenu->add("/adherents/index.php?leftmenu=members&amp;mainmenu=members", $langs->trans("Members"), 0, $user->hasRight('adherent', 'read'), '', $mainmenu, 'members', 0, '', '', '', img_picto('', 'member', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/adherents/card.php?leftmenu=members&amp;action=create", $langs->trans("NewMember"), 1, $user->hasRight('adherent', 'write'));
			$newmenu->add("/adherents/list.php?leftmenu=members", $langs->trans("List"), 1, $user->hasRight('adherent', 'read'));
			$newmenu->add("/adherents/list.php?leftmenu=members&amp;statut=-1", $langs->trans("MenuMembersToValidate"), 2, $user->hasRight('adherent', 'read'));
			$newmenu->add("/adherents/list.php?leftmenu=members&amp;statut=1", $langs->trans("MenuMembersValidated"), 2, $user->hasRight('adherent', 'read'));
			$newmenu->add("/adherents/list.php?leftmenu=members&amp;statut=1&amp;filter=waitingsubscription", $langs->trans("WaitingSubscription"), 3, $user->hasRight('adherent', 'read'));
			$newmenu->add("/adherents/list.php?leftmenu=members&amp;statut=1&amp;filter=uptodate", $langs->trans("UpToDate"), 3, $user->hasRight('adherent', 'read'));
			$newmenu->add("/adherents/list.php?leftmenu=members&amp;statut=1&amp;filter=outofdate", $langs->trans("OutOfDate"), 3, $user->hasRight('adherent', 'read'));
			$newmenu->add("/adherents/list.php?leftmenu=members&amp;statut=0", $langs->trans("MenuMembersResiliated"), 2, $user->hasRight('adherent', 'read'));
			$newmenu->add("/adherents/list.php?leftmenu=members&amp;statut=-2", $langs->trans("MenuMembersExcluded"), 2, $user->hasRight('adherent', 'read'));
			$newmenu->add("/adherents/stats/index.php?leftmenu=members", $langs->trans("MenuMembersStats"), 1, $user->hasRight('adherent', 'read'));

			$newmenu->add("/adherents/cartes/carte.php?leftmenu=export", $langs->trans("MembersCards"), 1, $user->hasRight('adherent', 'export'));

			if (isModEnabled('category')) {
				$langs->load("categories");
				$newmenu->add("/categories/index.php?leftmenu=cat&amp;type=3", $langs->trans("Categories"), 1, $user->hasRight('categorie', 'read'), '', $mainmenu, 'cat');
			}

			$newmenu->add("/adherents/index.php?leftmenu=members&amp;mainmenu=members", $langs->trans("Subscriptions"), 0, $user->hasRight('adherent', 'cotisation', 'read'), '', $mainmenu, 'members', 0, '', '', '', img_picto('', 'payment', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/adherents/list.php?leftmenu=members&amp;statut=-1,1&amp;mainmenu=members", $langs->trans("NewMembership"), 1, $user->hasRight('adherent', 'cotisation', 'write'));
			$newmenu->add("/adherents/subscription/list.php?leftmenu=members", $langs->trans("List"), 1, $user->hasRight('adherent', 'cotisation', 'read'));
			$newmenu->add("/adherents/stats/index.php?leftmenu=members", $langs->trans("MenuMembersStats"), 1, $user->hasRight('adherent', 'read'));

			//$newmenu->add("/adherents/index.php?leftmenu=export&amp;mainmenu=members",$langs->trans("Tools"),0,$user->hasRight('adherent',  'export'), '', $mainmenu, 'export');
			//if (isModEnabled('export') && ($usemenuhider || empty($leftmenu) || $leftmenu=="export")) $newmenu->add("/exports/index.php?leftmenu=export",$langs->trans("Datas"),1,$user->hasRight('adherent',  'export'));

			// Type
			$newmenu->add("/adherents/type.php?leftmenu=setup&amp;mainmenu=members", $langs->trans("MembersTypes"), 0, $user->hasRight('adherent', 'configurer'), '', $mainmenu, 'setup', 0, '', '', '', img_picto('', 'members', 'class="paddingright pictofixedwidth"'));
			$newmenu->add("/adherents/type.php?leftmenu=setup&amp;mainmenu=members&amp;action=create", $langs->trans("New"), 1, $user->hasRight('adherent', 'configurer'));
			$newmenu->add("/adherents/type.php?leftmenu=setup&amp;mainmenu=members", $langs->trans("List"), 1, $user->hasRight('adherent', 'configurer'));
		}
	}
}
