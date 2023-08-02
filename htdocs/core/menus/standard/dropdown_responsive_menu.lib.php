<?php
/* Copyright (C) 2010-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
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

//require_once DOL_DOCUMENT_ROOT.'/core/class/menu.class.php';

/**
 * Core function to output top menu eldy
 *
 * @param 	DoliDB	$db				Database handler
 * @param 	string	$atarget		Target (Example: '' or '_top')
 * @param 	int		$type_user     	0=Menu for backoffice, 1=Menu for front office
 * @param  	array	$tabMenu        If array with menu entries already loaded, we put this array here (in most cases, it's empty)
 * @param	Menu	$menu			Object Menu to return back list of menu entries
 * @param	int		$noout			1=Disable output (Initialise &$menu only).
 * @param	string	$mode			'top', 'topnb', 'left', 'jmobile'
 * @return	int						0
 */
function print_ace_menu($db, $atarget, $type_user, &$tabMenu, &$menu, $noout = 0, $mode = '',$moredata=null)
{
	global $user, $conf, $langs, $mysoc;
	global $dolibarr_main_db_name;

	$mainmenu = (empty($_SESSION["mainmenu"]) ? '' : $_SESSION["mainmenu"]);
	$leftmenu = (empty($_SESSION["leftmenu"]) ? '' : $_SESSION["leftmenu"]);

	$id = 'mainmenu';
	$listofmodulesforexternal = explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL);

	$substitarray = getCommonSubstitutionArray($langs, 0, null, null);

    $usemenuhider = 1;


	// Show personalized menus
	$menuArbo = new Menubase($db, 'auguria');
	
	//OPEN ALL LEFT MENU
	$menuArbo->menuLoad('', 'all', ($user->socid ? 1 : 0), 'auguria', $tabMenu);
	
	$newTabMenu = $menuArbo->menuTopCharger('', '', $type_user, 'auguria', $tabMenu);

	$substitarray = getCommonSubstitutionArray($langs, 0, null, null);

	global $usemenuhider;
	$usemenuhider = 1;

	$num = count($newTabMenu);
	for ($i = 0; $i < $num; $i++)
	{
		$idsel = (empty($newTabMenu[$i]['mainmenu']) ? 'none' : $newTabMenu[$i]['mainmenu']);

		$showmode = dol_auguria_showmenu($type_user, $newTabMenu[$i], $listofmodulesforexternal);
		if ($showmode == 1)
		{
			$newTabMenu[$i]['url'] = make_substitutions($newTabMenu[$i]['url'], $substitarray);

			$url = $shorturl = $newTabMenu[$i]['url'];

			if (!preg_match("/^(http:\/\/|https:\/\/)/i", $newTabMenu[$i]['url']))
			{
			    $tmp = explode('?', $newTabMenu[$i]['url'], 2);
				$url = $shorturl = $tmp[0];
				$param = (isset($tmp[1]) ? $tmp[1] : '');

				// Complete param to force leftmenu to '' to close open menu when we click on a link with no leftmenu defined.
			    if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && !empty($newTabMenu[$i]['url']))
			    {
			        $param .= ($param ? '&' : '').'mainmenu='.$newTabMenu[$i]['mainmenu'].'&leftmenu=';
			    }
			    if ((!preg_match('/mainmenu/i', $param)) && (!preg_match('/leftmenu/i', $param)) && empty($newTabMenu[$i]['url']))
			    {
			        $param .= ($param ? '&' : '').'leftmenu=';
			    }
				//$url.="idmenu=".$newTabMenu[$i]['rowid'];    // Already done by menuLoad
				$url = dol_buildpath($url, 1).($param ? '?'.$param : '');
				//$shorturl = $shorturl.($param?'?'.$param:'');
				$shorturl = $url;

				if (DOL_URL_ROOT) $shorturl = preg_replace('/^'.preg_quote(DOL_URL_ROOT, '/').'/', '', $shorturl);
			}

			// TODO Find a generic solution
			if (preg_match('/search_project_user=__search_project_user__/', $shorturl))
			{
			    $search_project_user = GETPOST('search_project_user', 'int');
			    if ($search_project_user) $shorturl = preg_replace('/search_project_user=__search_project_user__/', 'search_project_user='.$search_project_user, $shorturl);
			    else $shorturl = preg_replace('/search_project_user=__search_project_user__/', '', $shorturl);
			}

			// Define the class (top menu selected or not)
			if (!empty($_SESSION['idmenu']) && $newTabMenu[$i]['rowid'] == $_SESSION['idmenu']) $classname = 'class="tmenusel"';
			elseif (!empty($_SESSION["mainmenu"]) && $newTabMenu[$i]['mainmenu'] == $_SESSION["mainmenu"]) $classname = 'class="tmenusel"';
			else $classname = 'class="tmenu"';
		}
		elseif ($showmode == 2) $classname = 'class="tmenu"';

		$menu->add($shorturl, $newTabMenu[$i]['titre'], 0, $showmode, ($newTabMenu[$i]['target'] ? $newTabMenu[$i]['target'] : $atarget), ($newTabMenu[$i]['mainmenu'] ? $newTabMenu[$i]['mainmenu'] : $newTabMenu[$i]['rowid']), ($newTabMenu[$i]['leftmenu'] ? $newTabMenu[$i]['leftmenu'] : ''), $newTabMenu[$i]['position'], $id, $idsel, $classname);
	}







	// Sort on position
	$menu->liste = dol_sort_array($menu->liste, 'position');





	//*********************************************************************
	//PRINT Menu
	//**********************************************************************
	print '<div class="menu-toggle">';
	
		print '<button type="button" id="logout-btn">';
			print '<a accesskey="l" href="'.dol_buildpath("/user/logout.php", 1).'">';
			print '<i class="fas fa-sign-out-alt" style="color:white"></i>';
			print '</a>';
		print '</button>';

		print '<i id="a_link_spinner" class="fas fa-spinner fa-pulse"></i>';

		print '<button type="button" id="menu-btn">';
			print '<span class="icon-bar"></span>';
			print '<span class="icon-bar"></span>';
			print '<span class="icon-bar"></span>';
		print '</button>';
	print '</div>';
	
	
	if (empty($noout)) print_start_menu_array();


    // Output menu entries
	// Show logo company

	if ($mode != "jmobile" && empty($conf->global->MAIN_MENU_INVERT) && empty($noout) && !empty($conf->global->MAIN_SHOW_LOGO) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
	{
		//$mysoc->logo_mini=(empty($conf->global->MAIN_INFO_SOCIETE_LOGO_MINI)?'':$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI);
		$mysoc->logo_squarred_mini = (empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI) ? '' : $conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI);

		$logoContainerAdditionalClass = 'backgroundforcompanylogo';
		if (!empty($conf->global->MAIN_INFO_SOCIETE_LOGO_NO_BACKGROUND)) {
			$logoContainerAdditionalClass = '';
		}

		if (!empty($mysoc->logo_squarred_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_squarred_mini))
		{
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini);
		}

		else
		{
			$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo_squarred_alpha.png';
			$logoContainerAdditionalClass = '';
		}
		$title = $langs->trans("GoIntoSetupToChangeLogo");

		print "\n".'<!-- Show logo on menu -->'."\n";
		print_start_menu_entry('companylogo', 'class="tmenu tmenucompanylogo nohover"', 1);


		print '<div class="center '.$logoContainerAdditionalClass.' menulogocontainer"><img class="mycompany" title="'.dol_escape_htmltag($title).'" alt="" src="'.$urllogo.'" style="max-width: 100px"></div>'."\n";

		print_end_menu_entry(4);
	}
	

	$i=0;
	$arrayMenu = $menu->liste;
	$num = count($arrayMenu);
	
	
	if($num > 0){
		for ($i = 0; $i < $num; $i++){
			
			if($arrayMenu[$i]['enabled'])
			{
			
				print_start_menu_entry($arrayMenu[$i]['idsel'], $arrayMenu[$i]['classname'], $arrayMenu[$i]['enabled']);
				print_text_menu_entry($arrayMenu[$i]['titre'], $arrayMenu[$i]['enabled'], (($arrayMenu[$i]['url'] != '#' && !preg_match('/^(http:\/\/|https:\/\/)/i', $arrayMenu[$i]['url'])) ? DOL_URL_ROOT:'').$arrayMenu[$i]['url'], $arrayMenu[$i]['id'], $arrayMenu[$i]['idsel'], $arrayMenu[$i]['classname'], ($arrayMenu[$i]['target'] ? $arrayMenu[$i]['target'] : $atarget));
				
				$menu_array = array();
				$mainMenuName = trim($arrayMenu[$i]['mainmenu']);
				$leftMenuName = trim($arrayMenu[$i]['leftmenu']);
				
				
				if(!empty($mainMenuName)){
					$menu_array = get_sub_menu($db,$mainMenuName,$leftMenuName,$tabMenu);
					/*
					if($i==4){
						var_dump($menu_array);
						exit();
					}
					*/
				}
				
				if(!empty($menu_array)) print_sub_menu_entry($menu_array);


				print_end_menu_entry($arrayMenu[$i]['enabled']);
			}

			
		}
	}
	

	$showmode = 1;
    if (empty($noout)) {
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
    global $conf;

	print '<div class="tmenudiv">';
	print '<ul id="dropdownMenu" role="navigation" class="dropdown-responsive-menu" data-menu-style="horizontal">';
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
	if ($showmode)
	{
		print '<li '.$classname.' id="mainmenutd_'.$idsel.'">';
	}
}

/**
 * Output menu entry
 *
 * @param	string	$text		Text
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @param	string	$url		Url
 * @param	string	$id			Id
 * @param	string	$idsel		Id sel
 * @param	string	$classname	Class name
 * @param	string	$atarget	Target
 * @return	void
 */
function print_text_menu_entry($text, $showmode, $url, $id, $idsel, $classname, $atarget)
{
	global $langs;

		print '<a class="tmenuimage" tabindex="-1" href="'.$url.'"'.($atarget ? ' target="'.$atarget.'"' : '').'>';
		print '<div class="'.$id.' '.$idsel.' topmenuimage"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '</a>';

		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($atarget ? ' target="'.$atarget.'"' : '').' title="'.dol_escape_htmltag($text).'">';
		print '<span class="mainmenuaspan">';
		print $text;
		print '</span>';
		print '</a>';

}

/**
 * Output sub menu entry
 *
 * @param	string	$text		Text
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @param	string	$url		Url
 * @param	string	$id			Id
 * @param	string	$idsel		Id sel
 * @param	string	$classname	Class name
 * @param	string	$atarget	Target
 * @return	void
 */
function print_sub_menu_entry($menu_array)
{

	global $user, $conf, $langs, $dolibarr_main_db_name, $mysoc;


	//**********************************************************
	//SHOW SUB MENU
	//**********************************************************
	
	$num = count($menu_array);
	
	//var_dump($menu_array);
	//exit();
	
	if($num > 0){
		print '<ul>';

		
		for ($i = 0; $i < $num; $i++)     // Loop on each menu entry
		{
			$showmenu = true;
			if (!empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED) && empty($menu_array[$i]['enabled'])) 	$showmenu = false;

			$url = dol_buildpath($menu_array[$i]['url'], 1);
			

			//print '<!-- Process menu entry with mainmenu='.$menu_array[$i]['mainmenu'].', leftmenu='.$menu_array[$i]['leftmenu'].', level='.$menu_array[$i]['level'].' enabled='.$menu_array[$i]['enabled'].', position='.$menu_array[$i]['position'].' -->'."\n";

			// Menu level 0
			if ($menu_array[$i]['level'] == 0)
			{
				if ($menu_array[$i]['enabled'])     // Enabled so visible
				{
				
					if($lastlevel == 0) {
						print '</li>';
						print '<li>';
					}elseif($lastlevel == 1) {
						print '</li>';
						print '</ul>';
						print '<li>';
					}elseif($lastlevel == 2) {
						print '</li>';
						print '</ul>';
						print '</li>';
						print '</ul>';
						print '<li>';
					}elseif($lastlevel == 3) {
						print '</li>';
						print '</ul>';
						print '</li>';
						print '</ul>';
						print '</li>';
						print '</ul>';
						print '<li>';
					}

					print '<a href="'.$url.'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>';					
					print ($menu_array[$i]['prefix'] ? $menu_array[$i]['prefix'] : '').$menu_array[$i]['titre'];
					print '</a>';


					$lastlevel = 0;
				}
			}

			// Menu level == 1
			if ($menu_array[$i]['level'] > 0)
			{

				if ($menu_array[$i]['enabled'])     // Enabled so visible, except if parent was not enabled.
				{
					
					
					$currentlevel = $menu_array[$i]['level'];
					
					if($currentlevel == $lastlevel){
						print '</li>';
						print '<li>';
					}elseif($currentlevel>$lastlevel){						
						print '<ul class="ul_submenu">';
						print '<li>';
						
					}elseif($currentlevel<$lastlevel){
						
						$diff = $lastlevel-$currentlevel;
						
						for ($y = 0; $y < $diff; $y++) {
							print '</li>';
							print '</ul>';
						}
						
						print '<li>';
					}				
					
					print '<a class="vmenu" href="'.$url.'"'.($menu_array[$i]['target'] ? ' target="'.$menu_array[$i]['target'].'"' : '').'>';
					
					if (stripos($menu_array[$i]['titre'], 'list') !== false) print '<i class="fas fa-list"></i>';
					elseif (stripos($menu_array[$i]['titre'], 'nouv') !== false) print '<i class="fas fa-plus"></i>';
					elseif (stripos($menu_array[$i]['titre'], 'stat') !== false) print '<i class="fas fa-chart-pie"></i>';
					elseif (stripos($menu_array[$i]['titre'], 'tags') !== false) print '<i class="fas fa-tag"></i>';
					elseif (stripos($menu_array[$i]['titre'], 'glement') !== false) print '<i class="fas fa-euro-sign"></i>';
					elseif (stripos($menu_array[$i]['titre'], 'rapport') !== false) print '<i class="fas fa-file"></i>';
					
					print ($menu_array[$i]['prefix'] ? $menu_array[$i]['prefix'] : '').$menu_array[$i]['titre'];
					print '</a>';

					$lastlevel = $currentlevel;
				}


			}
			

			// If next is a new block or if there is nothing after
			if ($i+1 == $num)               // End menu block
			{
				
				for ($y = 0; $y < $lastlevel; $y++) {
					print '</li>';
					print '</ul>';
				}
				
				print '</li>';
			}
		}
		print '</ul>';
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
	if ($showmode)
	{
		//print '</div></li>';
		print '</li>';
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
	
	print '<script type="text/javascript"> $("#dropdownMenu").dropDownResponsiveMenu(); </script>';
	
	print '<div style="clear: both;"></div>';
}



/**
 * Core function to output left menu eldy
 * Fill &$menu (example with $forcemainmenu='home' $forceleftmenu='all', return left menu tree of Home)
 *
 * @param	DoliDB		$db                 Database handler
 * @param 	array		$menu_array_before  Table of menu entries to show before entries of menu handler (menu->liste filled with menu->add)
 * @param   array		$menu_array_after   Table of menu entries to show after entries of menu handler (menu->liste filled with menu->add)
 * @param	array		$tabMenu       		If array with menu entries already loaded, we put this array here (in most cases, it's empty)
 * @param	Menu		$menu				Object Menu to return back list of menu entries
 * @param	int			$noout				Disable output (Initialise &$menu only).
 * @param	string		$forcemainmenu		'x'=Force mainmenu to mainmenu='x'
 * @param	string		$forceleftmenu		'all'=Force leftmenu to '' (= all). If value come being '', we change it to value in session and 'none' if not defined in session.
 * @param	array		$moredata			An array with more data to output
 * @return	int								Nb of menu entries
 */
function get_sub_menu($db,$mainmenu,$leftmenu,$tabMenu)
{
	global $user, $conf, $langs, $dolibarr_main_db_name, $mysoc;

	$newmenu = new Menu();

    $usemenuhider = 0;


	/**
	 * We update newmenu with entries found into database
	 * --------------------------------------------------
	 */

	$substitarray = getCommonSubstitutionArray($langs, 0, null, null);
	
	
	$menuArbo = new Menubase($db, 'auguria');
		
	$newmenu = $menuArbo->menuLeftCharger($newmenu, $mainmenu, $leftmenu, ($user->socid ? 1 : 0), 'auguria', $tabMenu);
	
	$menu_array = $newmenu->liste;




	// We update newmenu for special dynamic menus
	if ($conf->banque->enabled && $user->rights->banque->lire && $mainmenu == 'bank')	// Entry for each bank account
	{
	    include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php'; // Required for to get Account::TYPE_CASH for example

		$sql = "SELECT rowid, label, courant, rappro, courant";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql .= " WHERE entity = ".$conf->entity;
		$sql .= " AND clos = 0";
		$sql .= " ORDER BY label";

		$resql = $db->query($sql);
		if ($resql)
		{
			$numr = $db->num_rows($resql);
			$i = 0;

			if ($numr > 0) 	$newmenu->add('/compta/bank/list.php', $langs->trans("BankAccounts"), 0, $user->rights->banque->lire);

			while ($i < $numr)
			{
				$objp = $db->fetch_object($resql);
				$newmenu->add('/compta/bank/card.php?id='.$objp->rowid, $objp->label, 1, $user->rights->banque->lire);
				if ($objp->rappro && $objp->courant != Account::TYPE_CASH && empty($objp->clos))  // If not cash account and not closed and can be reconciliate
				{
					$newmenu->add('/compta/bank/bankentries_list.php?id='.$objp->rowid, $langs->trans("Conciliate"), 2, $user->rights->banque->consolidate);
				}
				$i++;
			}
		}
		else dol_print_error($db);
		$db->free($resql);
	}

	if (!empty($conf->accounting->enabled) && !empty($user->rights->accounting->comptarapport->lire) && $mainmenu == 'accountancy') 	// Entry in accountancy journal for each bank account
	{
		$newmenu->add('', $langs->trans("RegistrationInAccounting"), 1, $user->rights->accounting->comptarapport->lire, '', 'accountancy', 'accountancy', 10);

		// Multi journal
		$sql = "SELECT rowid, code, label, nature";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_journal";
		$sql .= " WHERE entity = ".$conf->entity;
		$sql .= " AND active = 1";
		$sql .= " ORDER BY label DESC";

		$resql = $db->query($sql);
		if ($resql)
		{
			$numr = $db->num_rows($resql);
			$i = 0;

			if ($numr > 0)
			{
				while ($i < $numr)
				{
					$objp = $db->fetch_object($resql);

					$nature = '';

					// Must match array $sourceList defined into journals_list.php
					if ($objp->nature == 2 && ! empty($conf->facture->enabled)) $nature="sells";
					if ($objp->nature == 3 && (!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_invoice->enabled))) $nature="purchases";
					if ($objp->nature == 4 && ! empty($conf->banque->enabled)) $nature="bank";
					if ($objp->nature == 5 && ! empty($conf->expensereport->enabled)) $nature="expensereports";
					if ($objp->nature == 1) $nature="various";
					if ($objp->nature == 8) $nature="inventory";
					if ($objp->nature == 9) $nature="hasnew";

					// To enable when page exists
					if (empty($conf->global->ACCOUNTANCY_SHOW_DEVELOP_JOURNAL))
					{
						if ($nature == 'various' || $nature == 'hasnew' || $nature == 'inventory') $nature = '';
					}

					if ($nature)
					{
                        $langs->load('accountancy');
                        $journallabel = $langs->transnoentities($objp->label); // Labels in this table are set by loading llx_accounting_abc.sql. Label can be 'ACCOUNTING_SELL_JOURNAL', 'InventoryJournal', ...
                        $newmenu->add('/accountancy/journal/'.$nature.'journal.php?mainmenu=accountancy&leftmenu=accountancy_journal&id_journal='.$objp->rowid, $journallabel, 2, $user->rights->accounting->comptarapport->lire);
					}
					$i++;
				}
			}
			else
			{
				// Should not happend. Entries are added
				$newmenu->add('', $langs->trans("NoJournalDefined"), 2, $user->rights->accounting->comptarapport->lire);
			}
		}
		else dol_print_error($db);
		$db->free($resql);
	}

	if (!empty($conf->ftp->enabled) && $mainmenu == 'ftp')	// Entry for FTP
	{
		$MAXFTP = 20;
		$i = 1;
		while ($i <= $MAXFTP)
		{
			$paramkey = 'FTP_NAME_'.$i;
			//print $paramkey;
			if (!empty($conf->global->$paramkey))
			{
				$link = "/ftp/index.php?idmenu=".$_SESSION["idmenu"]."&numero_ftp=".$i;

				$newmenu->add($link, dol_trunc($conf->global->$paramkey, 24));
			}
			$i++;
		}
	}


	// Build final $menu_array = $menu_array_before +$newmenu->liste + $menu_array_after
	//var_dump($menu_array_before);exit;
	//var_dump($menu_array_after);exit;
	$menu_array = $newmenu->liste;
	if (is_array($menu_array_before)) $menu_array = array_merge($menu_array_before, $menu_array);
	if (is_array($menu_array_after))  $menu_array = array_merge($menu_array, $menu_array_after);

	if (!is_array($menu_array) || empty($menu_array)) return 0;
	else return $menu_array;
}


/**
 * Function to test if an entry is enabled or not
 *
 * @param	string		$type_user					0=We need backoffice menu, 1=We need frontoffice menu
 * @param	array		$menuentry					Array for menu entry
 * @param	array		$listofmodulesforexternal	Array with list of modules allowed to external users
 * @return	int										0=Hide, 1=Show, 2=Show gray
 */
function dol_auguria_showmenu($type_user, &$menuentry, &$listofmodulesforexternal)
{
	global $conf;

	//print 'type_user='.$type_user.' module='.$menuentry['module'].' enabled='.$menuentry['enabled'].' perms='.$menuentry['perms'];
	//print 'ok='.in_array($menuentry['module'], $listofmodulesforexternal);
	if (empty($menuentry['enabled'])) return 0; // Entry disabled by condition
	if ($type_user && $menuentry['module'])
	{
		$tmploops = explode('|', $menuentry['module']);
		$found = 0;
		foreach ($tmploops as $tmploop)
		{
			if (in_array($tmploop, $listofmodulesforexternal)) {
				$found++; break;
			}
		}
		if (!$found) return 0; // Entry is for menus all excluded to external users
	}
	if (!$menuentry['perms'] && $type_user) return 0; // No permissions and user is external
	if (!$menuentry['perms'] && !empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))	return 0; // No permissions and option to hide when not allowed, even for internal user, is on
	if (!$menuentry['perms']) return 2; // No permissions and user is external
	return 1;
}