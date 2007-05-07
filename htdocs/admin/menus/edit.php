<?php
/* Copyright (C) 2007 Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
		\file       htdocs/admin/menus/edit.php
		\ingroup    core,menudb
		\brief      Edition des menus
		\version    $Revision$
*/

require("./pre.inc.php");
 
 
if (!$user->admin)
  accessforbidden();


/*
* Actions
*/

if (isset($_GET["action"]) && $_GET["action"] == 'update')
{	


	if(!$_POST['cancel'])
	{		

		$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m";
		$sql.="	SET m.titre = '".$_POST['titre']."', m.leftmenu = '".$_POST['leftmenu']."', m.url = '".$_POST['url']."', m.langs = '".$_POST['langs']."', m.right = '".$_POST['right']."',m.target = '".$_POST['target']."', m.user = ".$_POST['user'];
		$sql.=" WHERE m.rowid = ".$_POST['menuId'];
		$db->query($sql);	
	}
	
	if($_GET['return'])
	{
		header("location: index.php");
	}
	else
	{
		header("location: edit.php?action=edit&menuId=".$_POST['menuId']);
	}
	

} 

if (isset($_GET["action"]) && $_GET["action"] == 'add')
{	

	if($_POST['cancel'])
	{
		header("location:index.php");
	}
	else
	{		
	
		if($_POST['level'] == -1)
		{
			$sql = "SELECT max(m.rowid) as maxId FROM ".MAIN_DB_PREFIX."menu as m WHERE m.level = ".$_POST['level'];
			$result = $db->query($sql);
			$lastMenu = $db->fetch_object($result);	
			$rowid = $lastMenu->maxId + 1;
			
			$sql = "SELECT max(m.order) as maxOrder FROM ".MAIN_DB_PREFIX."menu as m WHERE m.level = ".$_POST['level'];
			$result = $db->query($sql);
			$lastMenu = $db->fetch_object($result);	
			$order = $lastMenu->maxOrder + 1;			
				
		}
		elseif($_POST['level'] == 0)
		{

				$sql = "SELECT max(m.rowid) as maxId FROM ".MAIN_DB_PREFIX."menu as m WHERE m.level = ".$_POST['level'];
				$result = $db->query($sql);
				$lastMenu = $db->fetch_object($result);		
				
				$rowid = $lastMenu->maxId + 100	;
				
				$sql = "SELECT max(m.order) as maxOrder FROM ".MAIN_DB_PREFIX."menu as m WHERE m.fk_menu = ".$_GET['menuId'];
				$result = $db->query($sql);
				$lastMenu = $db->fetch_object($result);				
				if(isset($lastMenu->maxOrder))
				{
					$order = ($lastMenu->maxOrder) + 1;
				}
				else
				{
					$order = 0;
				}
			
		}
		elseif($_POST['level'] > 0)
		{
			
			$parentId = round($_GET['menuId'] / 100);

			$sql = "SELECT max(m.rowid) as maxId FROM ".MAIN_DB_PREFIX."menu as m WHERE m.rowid LIKE '".$parentId."__' AND m.rowid <> '".$parentId."00'";
			$result = $db->query($sql);
			$lastMenu = $db->fetch_object($result);	
			
			if(isset($lastMenu->maxId))
			{
				$rowid = ($lastMenu->maxId) + 1;
			}
			else
			{
				$rowid = ($parentId * 100) + 1;
			}
			
			$sql = "SELECT max(m.order) as maxOrder FROM ".MAIN_DB_PREFIX."menu as m WHERE m.fk_menu = ".$_GET['menuId'];
			$result = $db->query($sql);
			$lastMenu = $db->fetch_object($result);	
			if(isset($lastMenu->maxOrder))
			{
				$order = ($lastMenu->maxOrder) + 1;
			}
			else
			{
				$order = 0;
			}			

		}

		
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."menu(rowid, menu_handler, type, mainmenu, leftmenu, fk_menu, url, titre, level, langs, right, target, user, order)";
		$sql.=
		$sql.=" VALUES($rowid, '".$_POST['menu_hanlder']."','".$_POST['type']."','".$_POST['mainmenu']."','".$_POST['leftmenu']."',".$_GET['menuId'].",'".$_POST['url']."','".$_POST['titre']."',".$_POST['level'].",'".$_POST['langs']."','".$_POST['right']."','".$_POST['target']."',".$_POST['user'].",".$order.")";
		$result=$db->query($sql);		
		
		header("location: edit.php?action=edit&menuId=".$rowid);
	}
} 


if (isset($_GET["action"]) && $_GET["action"] == 'add_const')
{	

	if($_POST['type'] == 'prede')
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."menu_const(fk_menu, fk_constraint, user) VALUES(".$_POST['menuId'].",".$_POST['constraint'].",".$_POST['user'].")";
	}
	else
	{
	
		$sql = "SELECT max(rowid) as maxId FROM ".MAIN_DB_PREFIX."menu_constraint";
		$result = $db->query($sql);
		$objc = $db->fetch_object($result);
		$constraint = ($objc->maxId)  + 1;
	
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."menu_constraint(rowid,action) VALUES(".$constraint.",'".$_POST['constraint']."')";
		$db->query($sql);
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."menu_const(fk_menu, fk_constraint, user) VALUES(".$_POST['menuId'].",".$constraint.",".$_POST['user'].")";
	}
	
	$db->query($sql);	
	
	header("location:edit.php?action=edit&menuId=".$_POST['menuId']);	
	
}

if (isset($_GET["action"]) && $_GET["action"] == 'del_const')
{	
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu_const WHERE fk_menu = ".$_GET['menuId']." AND fk_constraint = ".$_GET['constId'];
	$db->query($sql);

	$sql = "SELECT count(rowid) as countId FROM ".MAIN_DB_PREFIX."menu_const WHERE fk_constraint = ".$_GET['constId'];
	$result = $db->query($sql);
	$objc = $db->fetch_object($result);
	if($objc->countId == 0)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu_constraint WHERE rowid = ".$_GET['constId'];
		$db->query($sql);
	}	
	
	header("location:edit.php?action=edit&menuId=".$_GET['menuId']);
}



if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{


	$sql = "SELECT c.rowid, c.fk_constraint FROM ".MAIN_DB_PREFIX."menu_const as c WHERE c.fk_menu = ".$_GET['menuId'];
	$res  = $db->query($sql);
	if ($res)
	{

		while ($obj = $db->fetch_object ($res))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu_const WHERE rowid = ".$obj->rowid;
			$db->query($sql);
			
			$sql = "SELECT count(rowid) as countId FROM ".MAIN_DB_PREFIX."menu_const WHERE fk_constraint = ".$obj->fk_constraint;
			$result = $db->query($sql);
			$objc = $db->fetch_object($result);
			
			if($objc->countId == 0)
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu_constraint WHERE rowid = ".$obj->fk_constraint;
				$db->query($sql);
			}	
		}
		

		
	}
;
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid = ".$_GET['menuId'];
	$db->query($sql);

	if ($result == 0)
	{
		llxHeader();
		print '<div class="ok">'.$langs->trans("MenuDeleted").'</div>';
		llxFooter();
		exit ;
	}
	else
	{
		$reload = 0;
		$_GET["action"]='';
	}
}
  
  

/*
 * Affichage page
 */

llxHeader();



if (isset($_GET["action"]) && $_GET["action"] == 'create')
{
	print_titre($langs->trans("NewMenu"),'','setup');
	
	print '<form action="./edit.php?action=add&menuId='.$_GET['menuId'].'" method="post" name="formmenucreate">';
	
	print '<table class="border" width="100%">';
	
	// Id
	if($_GET['menuId'] == 0)
	{
		$parent_rowid = $_GET['menuId'];
		$parent_level = -2;
	}
	else
	{
		$sql = "SELECT m.rowid, m.mainmenu, m.level, m.langs FROM ".MAIN_DB_PREFIX."menu as m WHERE m.rowid = ".$_GET['menuId'];
		$res  = $db->query($sql);
		
		if ($res)
		{
	
			while ($menu = $db->fetch_array ($res))
			{
				$parent_rowid = $menu['rowid'];
				$parent_mainmenu = $menu['mainmenu'];
				$parent_langs = $menu['langs'];
				$parent_level = $menu['level'];	
			}
		}
	}

	//Handler
	print '<tr><td>'.$langs->trans('MenuHandler').'</td><td><input type="text" size="50" name="handler" value="'.$handler.'"></td><td>'.$langs->trans('DetailMenuHandler').'</td></tr>';
	//Handler
	print '<tr><td>'.$langs->trans('Type').'</td><td><input type="text" size="50" name="type" value="'.$type.'"></td><td>'.$langs->trans('DetailType').'</td></tr>';
	//User
	print '<tr><td>'.$langs->trans('User').'</td>';
	print '<td><select class="flat" name="user">';
	print '<option value="0">'.$langs->trans('Interne').'</option>';
	print '<option value="1">'.$langs->trans('Externe').'</option>';
	print '<option value="2" selected>Tous</option>';
	print '</select></td>';
	print '<td>'.$langs->trans('DetailUser').'</td></tr>';
	//Level
	print '<input type="hidden" size="50" name="level" value="'.($parent_level + 1).'">';
	//Titre
	print '<tr><td>'.$langs->trans('Title').'</td><td><input type="text" size="50" name="titre" value=""></td><td>'.$langs->trans('DetailTitre').'</td></tr>';
	//Langs
	print '<tr><td>'.$langs->trans('Langs').'</td><td><input type="text" size="50" name="langs" value="'.$parent_langs.'"></td><td>'.$langs->trans('DetailLangs').'</td></tr>';
	//URL
	print '<tr><td>'.$langs->trans('URL').'</td><td><input type="text" size="50" name="url" value=""></td><td>'.$langs->trans('DetailUrl').'</td></tr>';
	//Target
	print '<tr><td>'.$langs->trans('Target').'</td><td><select class="flat" name="target">';
	print '<option value=""'.($menu->target==""?' selected="true"':'').'>'.$langs->trans('').'</option>';
	print '<option value="_new"'.($menu->target=="_new"?' selected="true"':'').'>'.$langs->trans('_new').'</option>';
	print '</select></td></td><td>'.$langs->trans('DetailTarget').'</td></tr>';
	//Right
	print '<tr><td>'.$langs->trans('Right').'</td><td><input type="text" size="50" name="right" value=""></td><td>'.$langs->trans('DetailRight').'</td></tr>';

	//Mainmenu = group
	print '<tr><td>'.$langs->trans('Group').'</td><td><input type="text" size="50" name="mainmenu" value="'.$mainmenu.'"></td><td>'.$langs->trans('DetailMainmenu').'</td></tr>';
	//Leftmenu
	print '<tr><td>'.$langs->trans('Leftmenu').'</td><td><input type="text" size="50" name="leftmenu" value=""></td><td>'.$langs->trans('DetailLeftmenu').'</td></tr>';

	// Boutons
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="save" value="Enregistrer">';
	print '&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="Annuler"></td></tr>';	

	print '</table>';

	print '</form>';
}

elseif (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
	print_titre($langs->trans("ModifMenu"),'','setup');
	
	print '<form action="./edit.php?action=update" method="post" name="formmenuedit">';
	
	print '<table class="border" width="100%">';
	
	
	$sql = "SELECT m.rowid, m.menu_handler, m.type, m.titre, m.mainmenu, m.leftmenu, m.fk_menu, m.url, m.langs, m.level, m.right, m.target, m.user, m.order";
	$sql.="	FROM ".MAIN_DB_PREFIX."menu as m WHERE m.rowid = ".$_GET['menuId'];
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows();
		$i = 0;

		while($i < $num)
		{
			$menu = $db->fetch_object($result);

			
			// Id
			print '<input type="hidden" name="menuId" value="'.$_GET['menuId'].'">';
			print '<tr><td>'.$langs->trans('rowid').'</td><td>'.$menu->rowid.'</td><td>'.$langs->trans('DetailId').'</td></tr>';

			// Handler
			print '<tr><td>'.$langs->trans('MenuHandler').'</td><td>'.$menu->menu_handler.'</td><td>'.$langs->trans('DetailMenuHandler').'</td></tr>';

			// user
			print '<tr><td>'.$langs->trans('User').'</td><td><select class="flat" name="user">';
        	print '<option value="0"'.($menu->user==0?' selected="true"':'').'>'.$langs->trans('Interne').'</option>';
        	print '<option value="1"'.($menu->user==1?' selected="true"':'').'>'.$langs->trans('Externe').'</option>';
        	print '<option value="2"'.($menu->user==2?' selected="true"':'').'>Tous</option>';
        	print '</select></td><td>'.$langs->trans('DetailUser').'</td></tr>';

			// Type
			print '<tr><td>'.$langs->trans('Type').'</td><td>'.$menu->type.'</td><td>'.$langs->trans('DetailType').'</td></tr>';

			// Niveau
			print '<tr><td>'.$langs->trans('Level').'</td><td>'.$menu->level.'</td><td>'.$langs->trans('DetailLevel').'</td></tr>';

			// Titre
			print '<tr><td>'.$langs->trans('Title').'</td><td><input type="text" size="70" name="titre" value="'.$menu->titre.'"></td><td>'.$langs->trans('DetailTitre').'</td></tr>';
			// Langs
			print '<tr><td>'.$langs->trans('Langs').'</td><td><input type="text" size="70" name="langs" value="'.$menu->langs.'"></td><td>'.$langs->trans('DetailLangs').'</td></tr>';

			// Url
			print '<tr><td>'.$langs->trans('URL').'</td><td><input type="text" size="70" name="url" value="'.$menu->url.'"></td><td>'.$langs->trans('DetailUrl').'</td></tr>';

        	// Target
			print '<tr><td>'.$langs->trans('Target').'</td><td><select class="flat" name="target">';
        	print '<option value=""'.($menu->target==""?' selected="true"':'').'>'.$langs->trans('').'</option>';
        	print '<option value="_new"'.($menu->target=="_new"?' selected="true"':'').'>'.$langs->trans('_new').'</option>';
        	print '</select></td></td><td>'.$langs->trans('DetailTarget').'</td></tr>';
			
			// Right
			print '<tr><td>'.$langs->trans('Right').'</td><td><input type="text" size="70" name="right" value="'.$menu->right.'"></td><td>'.$langs->trans('DetailRight').'</td></tr>';

			// Leftmenu
			print '<tr><td>'.$langs->trans('Leftmenu').'</td><td><input type="text" size="70" name="leftmenu" value="'.htmlentities($menu->leftmenu).'"></td><td>'.$langs->trans('DetailLeftmenu').'</td></tr>';
			// Mainmenu = group
			print '<tr><td>'.$langs->trans('Group').'</td><td><input type="text" size="70" name="mainmenu" value="'.$menu->mainmenu.'"></td><td>'.$langs->trans('DetailMainmenu').'</td></tr>';

			// Bouton			
			print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="save" value="Enregistrer">';
			print '&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="Annuler"></td></tr>';

			$i++;
		}
	}
	
	print '</form>';
		
		/*
	* Lignes de contraintes
	*/
	$sql = 'SELECT c.rowid, c.action, mc.user ';
	$sql.= 'FROM '.MAIN_DB_PREFIX.'menu_constraint as c, '.MAIN_DB_PREFIX.'menu_const as mc ';
	$sql.= 'WHERE c.rowid = mc.fk_constraint ';
	$sql.= 'AND mc.fk_menu = '.$_GET['menuId'];

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		
		print '<table class="noborder" width="100%">';
		if ($num)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Constraint').'</td>';
			print '<td>'.$langs->trans('User').'</td>';
			print '<td width="16">&nbsp;</td>';
			print "</tr>\n";
		}
		$var=true;
		
		$var = true;
		while ($i < $num)
		{
			$objc = $db->fetch_object($resql);
			
			$var = !$var;
			print '<tr '.$bc[$var].'>';
			print '<td>'.$objc->action.'</td>';	
			print '<td>';

			switch ($objc->user)
			{
				case 0: print 'Interne';
					break;
				case 1: print 'Externe';
					break;
				case 2: print 'Tous';
					break;			
			}
			print '</td>';
			print '<td align="center"><a href="edit.php?action=del_const&menuId='.$_GET['menuId'].'&constId='.$objc->rowid.'">'.img_delete().'</a></td>';

			$i++;
		}
		
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Constraint').'</td>';
		print '<td width="250">'.$langs->trans('User').'</td>';
		print '<td width="16">&nbsp;</td>';
		print "</tr>\n";
		
		
		// Ajout de contraintes personalisés
		print '<form action="edit.php?action=add_const" method="post">';
		print '<input type="hidden" name="menuId" value="'.$_GET['menuId'].'">';
		print '<input type="hidden" name="type" value="perso">';

		$var=true;
		print '<tr '.$bc[$var].'>';
		print '  <td><textarea cols="70" name="constraint" rows="1"></textarea></td>';
		print '<td>';
		print '<select name="user">';
    	print '<option value="0"'.($menu->user==0?' selected="true"':'').'>'.$langs->trans('Interne').'</option>';
    	print '<option value="1"'.($menu->user==1?' selected="true"':'').'>'.$langs->trans('Externe').'</option>';
    	print '<option value="2"'.($menu->user==2?' selected="true"':'').'>Tous</option>';		
		print '</td>';
		print '<td align="center"><input type="submit" class="button"></td>';
		print '</tr>';
		print '</form>';
		
		
		// Ajout de contraintes prédéfinis
		print '<form action="edit.php?action=add_const" method="post">';
		print '<input type="hidden" name="menuId" value="'.$_GET['menuId'].'">';
		print '<input type="hidden" name="type" value="prede">';

		$var=!$var;
		print '<tr '.$bc[$var].'>';
		print '<td>';
		print '<select name="constraint">';
		$sql = 'SELECT c.rowid, c.action FROM '.MAIN_DB_PREFIX.'menu_constraint as c ORDER BY c.action';
		$resql = $db->query($sql);
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$objc = $db->fetch_object($resql);
			print '<option value="'.$objc->rowid.'">'.$objc->action.'</option>';
			$i++;
			
		}
			
		
		print '</select>';
		print '</td>';
		print '<td>';
		print '<select name="user">';
    	print '<option value="0"'.($menu->user==0?' selected="true"':'').'>'.$langs->trans('Interne').'</option>';
    	print '<option value="1"'.($menu->user==1?' selected="true"':'').'>'.$langs->trans('Externe').'</option>';
    	print '<option value="2"'.($menu->user==2?' selected="true"':'').'>Tous</option>';		
		print '</td>';
		print '<td align="center"><input type="submit" class="button""></td>';
		print '</tr>';

		print '</form>';

		print '</table>';
		$db->free($resql);
	}
	
	
	print '</table>';
	


	print '<div class="tabsAction">';

	print '<a class="butAction" href="edit.php?action=update&menuId='.$_GET['menuId'].'&amp;return=1">'.$langs->trans('Valid').'</a>';
	print '<a class="butActionDelete" href="edit.php?menuId='.$_GET['menuId'].'&amp;action=delete">'.$langs->trans('Supprimer').'</a>';
	print '</div>';


}


	
			
 
?>
