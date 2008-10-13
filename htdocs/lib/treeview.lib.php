<?php
/* Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/lib/treeview.lib.php
 *  \ingroup    core
 *  \brief      Libraries for tree views
 *  \version    $Id$
 */


/**
 * Return if a child id is in descendance of parentid
 *
 * @param $fulltree		Full tree. Tree must be an array of records that looks like:
 *							id = id record
 *							id_mere = id record mother
 *							id_children = array of direct child id
 *							label = record label
 * 							fullpath = Full path of id
 * 							level =	Level of record
 * @param $parentid		Parent id
 * @param $childid		Child id
 * @return	int			1=Yes, 0=No
 */
function is_in_subtree($fulltree,$parentid,$childid)
{
	if ($parentid == $childid) return 1;	

	// Get fullpath of parent
	$fullpathparent='';
	foreach($fulltree as $key => $val)
	{
		//print $val['id']."-".$section."<br>";
		if ($val['id'] == $parentid)
		{
			$fullpathparent=$val['fullpath'];
			break;
		}
	}
	//print '> parent='.$parentid.' - child='.$childid.' - '.$fullpathparent.'<br>';	
	
	foreach($fulltree as $record)
	{
		if ($record['id'] == $childid)
		{
			//print $record['fullpath'].'_'.' - '.$fullpathparent.'_';
			if (eregi($fullpathparent.'_',$record['fullpath'].'_')) 
			{
				//print 'DEL='.$childid;
				return 1;
			}
		}
	}
	
	return 0;
}


/**
 * Show picto of a tree view
 *
 * @param 	fulltree	Array of entries in correct order
 * @param 	key			Key of value to show picto
 * @return	int			1
 */
function tree_showpad(&$fulltree,$key,$selected=0)
{
	$pos=1;
	while ($pos <= $fulltree[$key]['level'] && $fulltree[$key]['level'] > 0)
	{
		// Process picto for column $pos

		$atleastonofthislevelafter=0;
		$found=0;
		//print 'x'.$key;
		foreach($fulltree as $key2 => $val2)
		{
			if ($found == 1)
			{
				if ($fulltree[$key2]['level'] == $pos)
				{
					$atleastonofthislevelafter=1;
					break;
				} 
				if ($fulltree[$key2]['level'] < $pos)
				{
					break;
				} 
			}
			if ($key2 == $key) $found=1;	
		}
		//print $atleastonofthislevelafter;
		
		if ($atleastonofthislevelafter)
		{
			if ($fulltree[$key]['level'] == $pos) print img_picto_common('','treemenu/branch.gif');
			else print img_picto_common('','treemenu/line.gif');
		}
		else
		{
			if ($fulltree[$key]['level'] == $pos) print img_picto_common('','treemenu/branchbottom.gif');
			else print img_picto_common('','treemenu/linebottom.gif');
		}
		$pos++;
	}
	
	return 1;
}



// ------------------------------- Used by enu editor -----------------

/**
 * 	\brief		Ad javascript tree functions
 */
function tree_addjs()
{
	print '<script src="'.DOL_URL_ROOT.'/admin/menus/menu.js" type="text/javascript"></script>';
}


/* cette fonction gère le décallage des éléments
 suivant leur position dans l'arborescence
 */
function tree_showline($tab,$rang)
{
	global $conf, $rangLast, $idLast, $menu_handler;

	if ($conf->use_javascript_ajax)
	{
		if($rang == $rangLast)
		{
			print '<script type="text/javascript">imgDel('.$idLast.');</script>';
			//print '<a href="'.DOL_URL_ROOT.'/admin/menus/index.php?menu_handler=eldy&action=delete&menuId='.$idLast.'">aa</a>';
		}
		elseif($rang > $rangLast)
		{
				
			print '<li><ul>';

		}
		elseif($rang < $rangLast)
		{
			print '<script type="text/javascript">imgDel('.$idLast.')</script>';
				
			for($i=$rang; $i < $rangLast; $i++)
			{
				print '</ul></li>';
				echo "\n";
			}

		}
	}
	else
	{
		if($rang > $rangLast)
		{
				
			print '<li><ul>';

		}
		elseif($rang < $rangLast)
		{
				
			for($i=$rang; $i < $rangLast; $i++)
			{
				print '</ul></li>';
				echo "\n";
			}

		}
	}

	print '<li id=li'.$tab[0].'>';

	// Content of line
	print '<strong><a href="edit.php?menu_handler='.$menu_handler.'&action=edit&menuId='.$tab[0].'">'.$tab[2].'</a></strong>';
	print '<div class="menuEdit"><a href="edit.php?menu_handler='.$menu_handler.'&action=edit&menuId='.$tab[0].'">'.img_edit('default',0,'class="menuEdit" id="edit'.$tab[0].'"').'</a></div>';
	print '<div class="menuNew"><a href="edit.php?menu_handler='.$menu_handler.'&action=create&menuId='.$tab[0].'">'.img_edit_add('default',0,'class="menuNew" id="new'.$tab[0].'"').'</a></div>';
	print '<div class="menuDel"><a href="index.php?menu_handler='.$menu_handler.'&action=delete&menuId='.$tab[0].'">'.img_delete('default',0,'class="menuDel" id="del'.$tab[0].'"').'</a></div>';
	print '<div class="menuFleche"><a href="index.php?menu_handler='.$menu_handler.'&action=up&menuId='.$tab[0].'">'.img_picto("Monter","1uparrow").'</a><a href="index.php?menu_handler='.$menu_handler.'&action=down&menuId='.$tab[0].'">'.img_picto("Descendre","1downarrow").'</a></div>';

	print '</li>';
	echo "\n";

	$rangLast = $rang;
	$idLast = $tab[0];
}


/*fonction récursive d'affichage de l'arbre
 $tab  :tableau des éléments
 $pere :index de l'élément courant
 $rang :décallage de l'élément
 */
function tree_recur($tab,$pere,$rang) 
{
	if ($pere == 0) print '<ul class="arbre">';

	if ($rang > 10)	return;	// Protection contre boucle infinie

	//ballayage du tableau
	for ($x=0;$x<count($tab);$x++)
	{
		//si un élément a pour père : $pere
		if ($tab[$x][1]==$pere)
		{
			//on l'affiche avec le décallage courrant
			tree_showline($tab[$x],$rang);
				
			/*et on recherche ses fils
			 en rappelant la fonction recur()
			 (+ incrémentation du décallage)*/
			tree_recur($tab,$tab[$x][0],$rang+1);
		}
	}
	
	if ($pere == 0) print '</ul>';
}

?>