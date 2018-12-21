<?php
/* Copyright (C) 2010-2013	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/menus/standard/auguria.lib.php
 *  \brief		Library for file auguria menus
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';



/**
 * Core function to output top menu auguria
 *
 * @param 	DoliDB	$db				Database handler
 * @param 	string	$atarget		Target
 * @param 	int		$type_user     	0=Menu for backoffice, 1=Menu for front office
 * @param  	array	$tabMenu        If array with menu entries already loaded, we put this array here (in most cases, it's empty)
 * @param	Menu	$menu			Object Menu to return back list of menu entries
 * @param	int		$noout			Disable output (Initialise &$menu only).
 * @param	string	$mode			'top', 'topnb', 'left', 'jmobile'
 * @return	int						0
 */
function print_auguria_menu($db,$atarget,$type_user,&$tabMenu,&$menu,$noout=0,$mode='')
{
	global $user,$conf,$langs,$dolibarr_main_db_name;

	$mainmenu=$_SESSION["mainmenu"];
	$leftmenu=$_SESSION["leftmenu"];

	$id='mainmenu';
	$listofmodulesforexternal=explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL);

	// Show personalized menus
	$menuArbo = new Menubase($db,'auguria');
	$newTabMenu = $menuArbo->menuTopCharger('', '', $type_user, 'auguria',$tabMenu);

	if (empty($noout)) print_start_menu_array_auguria();

	$usemenuhider = (GETPOST('testmenuhider','int') || ! empty($conf->global->MAIN_TESTMENUHIDER));

	// Show/Hide vertical menu
	if ($mode != 'jmobile' && $mode != 'topnb' && $usemenuhider && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
	{
	    $showmode=1;
	    $classname = 'class="tmenu menuhider"';
	    $idsel='menu';

	    $menu->add('#', '', 0, $showmode, $atarget, "xxx", '', 0, $id, $idsel, $classname);
	}

	$num = count($newTabMenu);
	for($i = 0; $i < $num; $i++)
	{
		$idsel=(empty($newTabMenu[$i]['mainmenu'])?'none':$newTabMenu[$i]['mainmenu']);

		$showmode=dol_auguria_showmenu($type_user,$newTabMenu[$i],$listofmodulesforexternal);
		if ($showmode == 1)
		{
			$substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
			$substitarray['__USERID__'] = $user->id;	// For backward compatibility
			$newTabMenu[$i]['url'] = make_substitutions($newTabMenu[$i]['url'], $substitarray);

			$url = $shorturl = $newTabMenu[$i]['url'];

			if (! preg_match("/^(http:\/\/|https:\/\/)/i",$newTabMenu[$i]['url']))
			{
			    $tmp=explode('?',$newTabMenu[$i]['url'],2);
				$url = $shorturl = $tmp[0];
				$param = (isset($tmp[1])?$tmp[1]:'');

				// Complete param to force leftmenu to '' to close open menu when we click on a link with no leftmenu defined.
			    if ((! preg_match('/mainmenu/i',$param)) && (! preg_match('/leftmenu/i',$param)) && ! empty($newTabMenu[$i]['url']))
			    {
			        $param.=($param?'&':'').'mainmenu='.$newTabMenu[$i]['mainmenu'].'&leftmenu=';
			    }
			    if ((! preg_match('/mainmenu/i',$param)) && (! preg_match('/leftmenu/i',$param)) && empty($newTabMenu[$i]['url']))
			    {
			        $param.=($param?'&':'').'leftmenu=';
			    }
				//$url.="idmenu=".$newTabMenu[$i]['rowid'];    // Already done by menuLoad
				$url = dol_buildpath($url,1).($param?'?'.$param:'');
				//$shorturl = $shorturl.($param?'?'.$param:'');
				$shorturl = $url;

				if (DOL_URL_ROOT) $shorturl = preg_replace('/^'.preg_quote(DOL_URL_ROOT,'/').'/','',$shorturl);
			}

			// TODO Find a generic solution
			if (preg_match('/search_project_user=__search_project_user__/', $shorturl))
			{
			    $search_project_user = GETPOST('search_project_user','int');
			    if ($search_project_user) $shorturl=preg_replace('/search_project_user=__search_project_user__/', 'search_project_user='.$search_project_user, $shorturl);
			    else $shorturl=preg_replace('/search_project_user=__search_project_user__/', '', $shorturl);
			}

			// Define the class (top menu selected or not)
			if (! empty($_SESSION['idmenu']) && $newTabMenu[$i]['rowid'] == $_SESSION['idmenu']) $classname='class="tmenusel"';
			else if (! empty($_SESSION["mainmenu"]) && $newTabMenu[$i]['mainmenu'] == $_SESSION["mainmenu"]) $classname='class="tmenusel"';
			else $classname='class="tmenu"';
		}
		else if ($showmode == 2) $classname='class="tmenu"';

		$menu->add($shorturl, $newTabMenu[$i]['titre'], 0, $showmode, ($newTabMenu[$i]['target']?$newTabMenu[$i]['target']:$atarget), ($newTabMenu[$i]['mainmenu']?$newTabMenu[$i]['mainmenu']:$newTabMenu[$i]['rowid']), ($newTabMenu[$i]['leftmenu']?$newTabMenu[$i]['leftmenu']:''), $newTabMenu[$i]['position'], $id, $idsel, $classname);
	}

	// Sort on position
	$menu->liste = dol_sort_array($menu->liste, 'position');

	// Output menu entries
	foreach($menu->liste as $menkey => $menuval)
	{
        if (empty($noout)) print_start_menu_entry_auguria($menuval['idsel'],$menuval['classname'],$menuval['enabled']);
	    if (empty($noout)) print_text_menu_entry_auguria($menuval['titre'], $menuval['enabled'], ($menuval['url']!='#'?DOL_URL_ROOT:'').$menuval['url'], $menuval['id'], $menuval['idsel'], $menuval['classname'], ($menuval['target']?$menuval['target']:$atarget));
	    if (empty($noout)) print_end_menu_entry_auguria($menuval['enabled']);
	}

	$showmode=1;
	if (empty($noout)) print_start_menu_entry_auguria('','class="tmenuend"',$showmode);
	if (empty($noout)) print_end_menu_entry_auguria($showmode);

	if (empty($noout)) print_end_menu_array_auguria();

	print "\n";

	return 0;
}


/**
 * Output start menu array
 *
 * @return	void
 */
function print_start_menu_array_auguria()
{
    global $conf;

	print '<div class="tmenudiv">';
	print '<ul class="tmenu"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)?'':' title="Top menu"').'>';
}

/**
 * Output start menu entry
 *
 * @param	string	$idsel		Text
 * @param	string	$classname	String to add a css class
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_start_menu_entry_auguria($idsel,$classname,$showmode)
{
	if ($showmode)
	{
		print '<li '.$classname.' id="mainmenutd_'.$idsel.'">';
		//print '<div class="tmenuleft tmenusep"></div>';
		print '<div class="tmenucenter">';
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
function print_text_menu_entry_auguria($text, $showmode, $url, $id, $idsel, $classname, $atarget)
{
	global $langs;

	if ($showmode == 1)
	{
		print '<a class="tmenuimage" tabindex="-1" href="'.$url.'"'.($atarget?' target="'.$atarget.'"':'').'>';
		print '<div class="'.$id.' '.$idsel.' topmenuimage"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '</a>';
		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($atarget?' target="'.$atarget.'"':'').'>';
		print '<span class="mainmenuaspan">';
		print $text;
		print '</span>';
		print '</a>';
	}
	if ($showmode == 2)
	{
		print '<div class="'.$id.' '.$idsel.' topmenuimage tmenudisabled"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
		print '<span class="mainmenuaspan">';
		print $text;
		print '</span>';
		print '</a>';
	}
}

/**
 * Output end menu entry
 *
 * @param	int		$showmode	0 = hide, 1 = allowed or 2 = not allowed
 * @return	void
 */
function print_end_menu_entry_auguria($showmode)
{
	if ($showmode)
	{
		print '</div></li>';
	}
	print "\n";
}

/**
 * Output menu array
 *
 * @return	void
 */
function print_end_menu_array_auguria()
{
	print '</ul>';
	print '</div>';
	print "\n";
}



/**
 * Core function to output left menu auguria
 *
 * @param	DoliDB		$db                 Database handler
 * @param 	array		$menu_array_before  Table of menu entries to show before entries of menu handler
 * @param   array		$menu_array_after   Table of menu entries to show after entries of menu handler
 * @param  	array		$tabMenu       		If array with menu entries already loaded, we put this array here (in most cases, it's empty)
 * @param	Menu		$menu				Object Menu to return back list of menu entries
 * @param	int			$noout				Disable output (Initialise &$menu only).
 * @param	string		$forcemainmenu		'x'=Force mainmenu to mainmenu='x'
 * @param	string		$forceleftmenu		'all'=Force leftmenu to '' (= all)
 * @param	array		$moredata			An array with more data to output
 * @return	int								Nb of entries
 */
function print_left_auguria_menu($db,$menu_array_before,$menu_array_after,&$tabMenu,&$menu,$noout=0,$forcemainmenu='',$forceleftmenu='',$moredata=null)
{
	global $user,$conf,$langs,$dolibarr_main_db_name,$mysoc;

	$newmenu = $menu;

	$mainmenu=($forcemainmenu?$forcemainmenu:$_SESSION["mainmenu"]);
	$leftmenu=($forceleftmenu?'':(empty($_SESSION["leftmenu"])?'none':$_SESSION["leftmenu"]));

	$usemenuhider = (GETPOST('testmenuhider','int') || ! empty($conf->global->MAIN_TESTMENUHIDER));
	global $usemenuhider;

	// Show logo company
	if (empty($noout) && ! empty($conf->global->MAIN_SHOW_LOGO) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
	{
		$mysoc->logo_mini=$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI;
		if (! empty($mysoc->logo_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini))
		{
			$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_mini);
		}
		else
		{
			$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
		}
		$title=$langs->trans("GoIntoSetupToChangeLogo");
		print "\n".'<!-- Show logo on menu -->'."\n";
		print '<div class="blockvmenuimpair blockvmenulogo">'."\n";
		print '<div class="menu_titre" id="menu_titre_logo"></div>';
		print '<div class="menu_top" id="menu_top_logo"></div>';
		print '<div class="menu_contenu" id="menu_contenu_logo">';
		print '<div class="center"><img title="'.dol_escape_htmltag($title).'" alt="" src="'.$urllogo.'" style="max-width: 70%"></div>'."\n";
		print '</div>';
		print '<div class="menu_end" id="menu_end_logo"></div>';
		print '</div>'."\n";
	}

	if (is_array($moredata) && ! empty($moredata['searchform']))	// searchform can contains select2 code or link to show old search form or link to switch on search page
	{
        print "\n";
        print "<!-- Begin SearchForm -->\n";
        print '<div id="blockvmenusearch" class="blockvmenusearch">'."\n";
        print $moredata['searchform'];
        print '</div>'."\n";
        print "<!-- End SearchForm -->\n";
	}

	if (is_array($moredata) && ! empty($moredata['bookmarks']))
	{
	    print "\n";
	    print "<!-- Begin Bookmarks -->\n";
	    print '<div id="blockvmenubookmarks" class="blockvmenubookmarks">'."\n";
	    print $moredata['bookmarks'];
	    print '</div>'."\n";
	    print "<!-- End Bookmarks -->\n";
	}

	// We update newmenu with entries found into database
	$menuArbo = new Menubase($db,'auguria');
	$newmenu = $menuArbo->menuLeftCharger($newmenu,$mainmenu,$leftmenu,($user->societe_id?1:0),'auguria',$tabMenu);

	// We update newmenu for special dynamic menus
	if ($conf->banque->enabled && $user->rights->banque->lire && $mainmenu == 'bank')	// Entry for each bank account
	{
	    include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';     // Required for to get Account::TYPE_CASH for example

		$sql = "SELECT rowid, label, courant, rappro, courant";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " AND clos = 0";
		$sql.= " ORDER BY label";

		$resql = $db->query($sql);
		if ($resql)
		{
			$numr = $db->num_rows($resql);
			$i = 0;

			if ($numr > 0) 	$newmenu->add('/compta/bank/list.php',$langs->trans("BankAccounts"),0,$user->rights->banque->lire);

			while ($i < $numr)
			{
				$objp = $db->fetch_object($resql);
				$newmenu->add('/compta/bank/card.php?id='.$objp->rowid,$objp->label,1,$user->rights->banque->lire);
				if ($objp->rappro && $objp->courant != Account::TYPE_CASH && empty($objp->clos))  // If not cash account and not closed and can be reconciliate
				{
					$newmenu->add('/compta/bank/bankentries_list.php?id='.$objp->rowid,$langs->trans("Conciliate"),2,$user->rights->banque->consolidate);
				}
				$i++;
			}
		}
		else dol_print_error($db);
		$db->free($resql);
	}

	if (! empty($conf->accounting->enabled) && !empty($user->rights->accounting->mouvements->lire) && $mainmenu == 'accountancy') 	// Entry in accountancy journal for each bank account
	{
		$newmenu->add('',$langs->trans("Journalization"),0,$user->rights->accounting->comptarapport->lire,'','accountancy','accountancy');

		// Multi journal
		$sql = "SELECT rowid, code, label, nature";
		$sql.= " FROM ".MAIN_DB_PREFIX."accounting_journal";
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " AND active = 1";
		$sql.= " ORDER BY label DESC";

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

					$nature='';

					// Must match array $sourceList defined into journals_list.php
					if ($objp->nature == 2 && ! empty($conf->facture->enabled)) $nature="sells";
					if ($objp->nature == 3 && ! empty($conf->fournisseur->enabled)) $nature="purchases";
					if ($objp->nature == 4 && ! empty($conf->banque->enabled)) $nature="bank";
					if ($objp->nature == 5 && ! empty($conf->expensereport->enabled)) $nature="expensereports";
					if ($objp->nature == 1) $nature="various";
					if ($objp->nature == 8) $nature="inventory";
					if ($objp->nature == 9) $nature="hasnew";

					// To enable when page exists
					if (! empty($conf->global->ACCOUNTANCY_SHOW_DEVELOP_JOURNAL))
					{
						if ($nature == 'various' || $nature == 'hasnew' || $nature == 'inventory') $nature='';
					}

					if ($nature)
					{
						if ($usemenuhider || empty($leftmenu) || preg_match('/accountancy/',$leftmenu)) $newmenu->add('/accountancy/journal/'.$nature.'journal.php?mainmenu=accountancy&leftmenu=accountancy_journal&id_journal='.$objp->rowid,dol_trunc($objp->label,25),2,$user->rights->accounting->comptarapport->lire);
					}
					$i++;
				}
			}
			else
			{
				// Should not happend. Entries are added
				$newmenu->add('',$langs->trans("NoJournalDefined"), 2, $user->rights->accounting->comptarapport->lire);
			}
		}
		else dol_print_error($db);
		$db->free($resql);

		/*
		$sql = "SELECT rowid, label, accountancy_journal";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " AND clos = 0";
		$sql.= " ORDER BY label";

		$resql = $db->query($sql);
		if ($resql)
		{
			$numr = $db->num_rows($resql);
			$i = 0;

			if ($numr > 0)
			while ($i < $numr)
			{
				$objp = $db->fetch_object($resql);
				$newmenu->add('/accountancy/journal/bankjournal.php?id_account='.$objp->rowid, $langs->trans("Journal").' - '.$objp->label, 1, $user->rights->accounting->comptarapport->lire,'','accountancy','accountancy_journal');
				$i++;
			}
		}
		else dol_print_error($db);
		$db->free($resql);

		// Add other journal
		$newmenu->add("/accountancy/journal/sellsjournal.php?leftmenu=journal",$langs->trans("SellsJournal"),1,$user->rights->accounting->comptarapport->lire,'','accountancy','accountancy_journal');
		$newmenu->add("/accountancy/journal/purchasesjournal.php?leftmenu=journal",$langs->trans("PurchasesJournal"),1,$user->rights->accounting->comptarapport->lire,'','accountancy','accountancy_journal');
		$newmenu->add("/accountancy/journal/expensereportsjournal.php?leftmenu=journal",$langs->trans("ExpenseReportsJournal"),1,$user->rights->accounting->comptarapport->lire,'','accountancy','accountancy_journal');
		*/
	}

	if ($conf->ftp->enabled && $mainmenu == 'ftp')	// Entry for FTP
	{
		$MAXFTP=20;
		$i=1;
		while ($i <= $MAXFTP)
		{
			$paramkey='FTP_NAME_'.$i;
			//print $paramkey;
			if (! empty($conf->global->$paramkey))
			{
				$link="/ftp/index.php?idmenu=".$_SESSION["idmenu"]."&numero_ftp=".$i;

				$newmenu->add($link, dol_trunc($conf->global->$paramkey,24));
			}
			$i++;
		}
	}


	// Build final $menu_array = $menu_array_before +$newmenu->liste + $menu_array_after
	//var_dump($menu_array_before);exit;
	//var_dump($menu_array_after);exit;
	$menu_array=$newmenu->liste;
	if (is_array($menu_array_before)) $menu_array=array_merge($menu_array_before, $menu_array);
	if (is_array($menu_array_after))  $menu_array=array_merge($menu_array, $menu_array_after);
	//var_dump($menu_array);exit;
	if (! is_array($menu_array)) return 0;

	// Show menu
	$invert=empty($conf->global->MAIN_MENU_INVERT)?"":"invert";
	if (empty($noout))
	{
		$altok=0; $blockvmenuopened=false; $lastlevel0='';
		$num=count($menu_array);
		for ($i = 0; $i < $num; $i++)     // Loop on each menu entry
		{
			$showmenu=true;
			if (! empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED) && empty($menu_array[$i]['enabled'])) 	$showmenu=false;

			// Begin of new left menu block
			if (empty($menu_array[$i]['level']) && $showmenu)
			{
				$altok++;
				$blockvmenuopened=true;
				$lastopened=true;
				for($j = ($i + 1); $j < $num; $j++)
				{
				    if (empty($menu_array[$j]['level'])) $lastopened=false;
				}
				if ($altok % 2 == 0)
				{
					print '<div class="blockvmenu blockvmenuimpair'.$invert.($lastopened?' blockvmenulast':'').($altok == 1 ? ' blockvmenufirst':'').'">'."\n";
				}
				else
				{
					print '<div class="blockvmenu blockvmenupair'.$invert.($lastopened?' blockvmenulast':'').($altok == 1 ? ' blockvmenufirst':'').'">'."\n";
				}
			}

			// Add tabulation
			$tabstring='';
			$tabul=($menu_array[$i]['level'] - 1);
			if ($tabul > 0)
			{
				for ($j=0; $j < $tabul; $j++)
				{
					$tabstring.='&nbsp;&nbsp;&nbsp;';
				}
			}

			// $menu_array[$i]['url'] can be a relative url, a full external url. We try substitution
			$substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
			$substitarray['__USERID__'] = $user->id;	// For backward compatibility
			$menu_array[$i]['url'] = make_substitutions($menu_array[$i]['url'], $substitarray);

			$url = $shorturl = $shorturlwithoutparam = $menu_array[$i]['url'];
			if (! preg_match("/^(http:\/\/|https:\/\/)/i",$menu_array[$i]['url']))
			{
				$tmp=explode('?',$menu_array[$i]['url'],2);
				$url = $shorturl = $tmp[0];
				$param = (isset($tmp[1])?$tmp[1]:'');    // params in url of the menu link

				// Complete param to force leftmenu to '' to close open menu when we click on a link with no leftmenu defined.
				if ((! preg_match('/mainmenu/i',$param)) && (! preg_match('/leftmenu/i',$param)) && ! empty($menu_array[$i]['mainmenu']))
				{
					$param.=($param?'&':'').'mainmenu='.$menu_array[$i]['mainmenu'].'&leftmenu=';
				}
				if ((! preg_match('/mainmenu/i',$param)) && (! preg_match('/leftmenu/i',$param)) && empty($menu_array[$i]['mainmenu']))
				{
					$param.=($param?'&':'').'leftmenu=';
				}
				//$url.="idmenu=".$menu_array[$i]['rowid'];    // Already done by menuLoad
				$url = dol_buildpath($url,1).($param?'?'.$param:'');
				$shorturlwithoutparam = $shorturl;
				$shorturl = $shorturl.($param?'?'.$param:'');
			}


			print '<!-- Process menu entry with mainmenu='.$menu_array[$i]['mainmenu'].', leftmenu='.$menu_array[$i]['leftmenu'].', level='.$menu_array[$i]['level'].' enabled='.$menu_array[$i]['enabled'].', position='.$menu_array[$i]['position'].' -->'."\n";

			// Menu level 0
			if ($menu_array[$i]['level'] == 0)
			{
				if ($menu_array[$i]['enabled'])     // Enabled so visible
				{
					print '<div class="menu_titre">'.$tabstring;
					if ($shorturlwithoutparam) print '<a class="vmenu" href="'.$url.'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>';
					else print '<span class="vmenu">';
					print ($menu_array[$i]['prefix']?$menu_array[$i]['prefix']:'').$menu_array[$i]['titre'];
					if ($shorturlwithoutparam) print '</a>';
					else print '</span>';
					print '</div>'."\n";
					$lastlevel0='enabled';
				}
				else if ($showmenu)                 // Not enabled but visible (so greyed)
				{
					print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$menu_array[$i]['titre'].'</font></div>'."\n";
					$lastlevel0='greyed';
				}
				else
				{
				    $lastlevel0='hidden';
				}
				if ($showmenu)
				{
					print '<div class="menu_top"></div>'."\n";
				}
			}

			// Menu level > 0
			if ($menu_array[$i]['level'] > 0)
			{
				$cssmenu = '';
				if ($menu_array[$i]['url']) $cssmenu = ' menu_contenu'.dol_string_nospecial(preg_replace('/\.php.*$/','',$menu_array[$i]['url']));

				if ($menu_array[$i]['enabled'] && $lastlevel0 == 'enabled')     // Enabled so visible, except if parent was not enabled.
				{
					print '<div class="menu_contenu'.$cssmenu.'">'.$tabstring;
					if ($shorturlwithoutparam) print '<a class="vsmenu" href="'.$url.'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>';
					else print '<span class="vsmenu">';
					print $menu_array[$i]['titre'];
					if ($shorturlwithoutparam) print '</a>';
					else print '</span>';
					// If title is not pure text and contains a table, no carriage return added
					if (! strstr($menu_array[$i]['titre'],'<table')) print '<br>';
					print '</div>'."\n";
				}
				else if ($showmenu && $lastlevel0 == 'enabled')       // Not enabled but visible (so greyed), except if parent was not enabled.
				{
					print '<div class="menu_contenu'.$cssmenu.'">'.$tabstring.'<font class="vsmenudisabled vsmenudisabledmargin">'.$menu_array[$i]['titre'].'</font><br></div>'."\n";
				}
			}

			// If next is a new block or if there is nothing after
			if (empty($menu_array[$i+1]['level']))               // End menu block
			{
				if ($showmenu)
					print '<div class="menu_end"></div>'."\n";
				if ($blockvmenuopened) { print '</div>'."\n"; $blockvmenuopened=false; }
			}
		}

		if ($altok) print '<div class="blockvmenuend"></div>';    // End menu block
	}

	return count($menu_array);
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
	if (empty($menuentry['enabled'])) return 0;	// Entry disabled by condition
	if ($type_user && $menuentry['module'])
	{
		$tmploops=explode('|',$menuentry['module']);
		$found=0;
		foreach($tmploops as $tmploop)
		{
			if (in_array($tmploop, $listofmodulesforexternal)) {
				$found++; break;
			}
		}
		if (! $found) return 0;	// Entry is for menus all excluded to external users
	}
	if (! $menuentry['perms'] && $type_user) return 0; 											// No permissions and user is external
	if (! $menuentry['perms'] && ! empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))	return 0;	// No permissions and option to hide when not allowed, even for internal user, is on
	if (! $menuentry['perms']) return 2;															// No permissions and user is external
	return 1;
}
