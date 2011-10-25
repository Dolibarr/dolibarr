<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \file		htdocs/core/menus/smartphone/smartphone.lib.php
 *  \brief		Library for file smartphone menus
 */


/**
 * Core function to output top menu smartphone
 *
 * @param $db
 * @param $atarget
 * @param $type_user     0=Internal,1=External,2=All
 * @param $limitmenuto	 To limit menu to a top or left menu value
 */
function print_smartphone_menu($db,$atarget,$type_user,$limitmenuto)
{
	require_once(DOL_DOCUMENT_ROOT."/core/class/menubase.class.php");

	global $user,$conf,$langs,$dolibarr_main_db_name;

	$submenus='';

	$tabMenu=array();

	$menutop = new Menubase($db,'smartphone','top');
	$menuleft = new Menubase($db,'smartphone','left');
	$newTabMenu = $menutop->menuTopCharger('', '', $type_user, 'smartphone',$tabMenu);
	$numTabMenu = count($newTabMenu);

	print_start_menu_array();
    for($i=0; $i<$numTabMenu; $i++)
	{
		if ($newTabMenu[$i]['enabled'] == true)
		{
			if ($newTabMenu[$i]['right'] == true)	// Is allowed
			{
				// Define url
				if (preg_match("/^(http:\/\/|https:\/\/)/i",$newTabMenu[$i]['url']))
				{
					$url = dol_buildpath($newTabMenu[$i]['url'],1);
					$url=preg_replace('/&amp.*/i','',$url);
				}
				else
				{
					$menus='';

					if ($limitmenuto > 0)
					{
						$newmenu = new Menu();
						$leftmenu = $menuleft->menuLeftCharger($newmenu,$newTabMenu[$i]['mainmenu'],'',($user->societe_id?1:0),'smartphone',$tabMenu);
						$menus = $leftmenu->liste;
						//var_dump($menus);
					}

					print_start_menu_entry();

					if (is_array($menus) && !empty($menus) && $limitmenuto > 0)
					{
						$title=$newTabMenu[$i]['titre'];
						// To remove & and special chars: $title=dol_string_unaccent(dol_string_nospecial(dol_html_entity_decode($newTabMenu[$i]['titre'],ENT_QUOTES,'UTF-8'),'',array('&')));
						print_text_menu_entry($title);

						$num = count($menus);
						//var_dump($menus);

					    if ($num > 0) print_start_submenu_array();

                        for($j=0; $j<$num; $j++)
						{
							$url=dol_buildpath($menus[$j]['url'],1);
							$url=preg_replace('/&amp.*/i','',$url);

							if ($menus[$j]['level'] == 0)
							{
								print_start_menu_entry();
								if (empty($menus[$j+1]['level'])) print '<a href="'.$url.'"'.($menus[$j]['atarget']?" target='".$menus[$j]['atarget']."'":($atarget?" target=$atarget":'')).'>';
								$title=$menus[$j]['titre'];
								// To remove & and special chars: $title=dol_string_unaccent(dol_string_nospecial(dol_html_entity_decode($menus[$j]['titre'],ENT_QUOTES,'UTF-8'),'',array('&')));
								print_text_menu_entry($title);
								if (empty($menus[$j+1]['level'])) print '</a>';
							}

							if ($menus[$j]['level'] > 0)
							{
								if ($menus[$j-1]['level'] == 0) print_start_submenu_array();

								print_start_menu_entry();
								print '<a href="'.$url.'"'.($menus[$j]['atarget']?" target='".$menus[$j]['atarget']."'":($atarget?" target=$atarget":'')).'>';
								$title=$menus[$j]['titre'];
								// To remove & and special chars: $title=dol_string_unaccent(dol_string_nospecial(dol_html_entity_decode($menus[$j]['titre'],ENT_QUOTES,'UTF-8'),'',array('&')));
								print_text_menu_entry($title);
								print '</a>';
								print_end_menu_entry();

								if (empty($menus[$j+1]['level'])) print_end_menu();
							}

							if (empty($menus[$j+1]['level'])) print_end_menu_entry();
						}

                    	if ($num > 0) print_end_menu();
					}
					else
					{
						$url=dol_buildpath($newTabMenu[$i]['url'],1);
						$url=preg_replace('/&amp.*/i','',$url);

						print '<a href="'.$url.'"'.($newTabMenu[$i]['atarget']?" target='".$newTabMenu[$i]['atarget']."'":($atarget?" target=$atarget":'')).'>';
						$title=$newTabMenu[$i]['titre'];
						// To remove & and special chars: $title=dol_string_unaccent(dol_string_nospecial(dol_html_entity_decode($newTabMenu[$i]['titre'],ENT_QUOTES,'UTF-8'),'',array('&')));
						print_text_menu_entry($title);
						print '</a>';
					}

					print_end_menu_entry();
				}
			}
		}
	}

	print_end_menu();
	print "\n";
}

function print_start_menu_array($theme='c')
{
	print '<ul data-role="listview" data-theme="'.$theme.'">';
	print "\n";
}

function print_start_submenu_array()
{
	print '<ul>';
	print "\n";
}

function print_start_menu_entry()
{
	print '<li>';
}

function print_text_menu_entry($text)
{
	print $text;
}

function print_end_menu_entry()
{
	print '</li>';
	print "\n";
}

function print_end_menu()
{
	print '</ul>';
	print "\n";
}

?>
