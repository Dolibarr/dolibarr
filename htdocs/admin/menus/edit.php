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
 *
 * $Id$
 */

/**
		\file       htdocs/admin/menus/edit.php
		\ingroup    core
		\brief      Edition des menus
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/menubase.class.php");


$langs->load("admin");

if (! $user->admin)
  accessforbidden();

$dirtop = "../../includes/menus/barre_top";
$dirleft = "../../includes/menus/barre_left";

$mesg=$_GET["mesg"];

$menu_handler_top=$conf->global->MAIN_MENU_BARRETOP;
$menu_handler_left=$conf->global->MAIN_MENU_BARRELEFT;
$menu_handler_top=eregi_replace('_backoffice\.php','',$menu_handler_top);
$menu_handler_top=eregi_replace('_frontoffice\.php','',$menu_handler_top);
$menu_handler_left=eregi_replace('_backoffice\.php','',$menu_handler_left);
$menu_handler_left=eregi_replace('_frontoffice\.php','',$menu_handler_left);

$menu_handler=$menu_handler_left;

if ($_REQUEST["handler_origine"]) $menu_handler=$_REQUEST["handler_origine"];
if ($_REQUEST["menu_handler"])    $menu_handler=$_REQUEST["menu_handler"];



/*
* Actions
*/

if (isset($_GET["action"]) && $_GET["action"] == 'update')
{	
	if (! $_POST['cancel'])
	{		
		$menu = new Menubase($db);
		$result=$menu->fetch($_POST['menuId']);
		if ($result > 0)
		{
			$menu->titre=$_POST['titre'];
			$menu->leftmenu=$_POST['leftmenu'];
			$menu->url=$_POST['url'];
			$menu->langs=$_POST['langs'];
			$menu->position=$_POST['position'];
			$menu->perms=$_POST['perms'];
			$menu->target=$_POST['target'];
			$menu->user=$_POST['user'];
			$result=$menu->update($user);
			if ($result > 0)
			{
				$mesg='<div class="ok">'.$langs->trans("RecordModifiedSuccessfully").'</div>';
			}
			else
			{
				$mesg='<div class="error">'.$menu->error.'</div>';
			}
		}
		else
		{
			$mesg='<div class="error">'.$menu->error.'</div>';
		}
		$_GET["menuId"]=$_POST['menuId'];
		$_GET["action"]="edit";
	}
	else
	{
		header("Location: ".DOL_URL_ROOT."/admin/menus/index.php?menu_handler=".$menu_handler);
		exit;
	}
	
	if ($_GET['return'])
	{
		header("Location: ".DOL_URL_ROOT."/admin/menus/index.php?menu_handler=".$menu_handler);
		exit;
	}
} 

if (isset($_GET["action"]) && $_GET["action"] == 'add')
{	
	if ($_POST['cancel'])
	{
		header("Location: ".DOL_URL_ROOT."/admin/menus/index.php?menu_handler=".$menu_handler);
		exit;
	}

	$langs->load("errors");

	$error=0;
	if (! $error && ! $_POST['menu_handler'])
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("MenuHandler")).'</div>';
		$_GET["action"] = 'create';
		$error++;
	}
	if (! $error && ! $_POST['type'])
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Type")).'</div>';
		$_GET["action"] = 'create';
		$error++;
	}
	if (! $error && ! $_POST['url'])
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Url")).'</div>';
		$_GET["action"] = 'create';
		$error++;
	}
	if (! $error && ! $_POST['titre'])
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Title")).'</div>';
		$_GET["action"] = 'create';
		$error++;
	}
	if (! $error && $_POST['menuId'] && $_POST['type'] == 'top')
	{
		$mesg='<div class="error">'.$langs->trans("ErrorTopMenuMustHaveAParentWithId0").'</div>';
		$_GET["action"] = 'create';
		$error++;
	}
	if (! $error && ! $_POST['menuId'] && $_POST['type'] == 'left')
	{
		$mesg='<div class="error">'.$langs->trans("ErrorLeftMenuMustHaveAParentId").'</div>';
		$_GET["action"] = 'create';
		$error++;
	}
	if (! $error)
	{		
		$sql = "SELECT max(m.rowid) as maxId FROM ".MAIN_DB_PREFIX."menu as m";
		$result = $db->query($sql);
		$obj = $db->fetch_object($result);	
		$rowid = $obj->maxId + 1;
		
		// On prend le max de toutes celles qui auront le meme pere fk_menu
		$sql = "SELECT max(m.position) as maxOrder FROM ".MAIN_DB_PREFIX."menu as m WHERE m.fk_menu = ".$_POST['menuId'];
		$result = $db->query($sql);
		$obj = $db->fetch_object($result);	
		if ($obj) $position = $obj->maxOrder + 1;
		else
		{
			dolibarr_print_error($db);
		}
	
		if ($rowid == $_POST['menuId'])
		{
			$mesg='<div class="error">'.'FailedToInsertParentdoesNotExist sql='.$sql.'</div>';
			$_GET["action"] = 'create';
			$error++;
		}
	}

	if (! $error)
	{		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."menu(rowid,menu_handler, type, mainmenu, leftmenu, fk_menu, url, titre, level, langs, perms`, target, user, position)";
		$sql.=" VALUES(".$rowid.",'".$_POST['menu_handler']."','".$_POST['type']."','".$_POST['mainmenu']."','".$_POST['leftmenu']."',".$_POST['menuId'].",'".$_POST['url']."','".$_POST['titre']."','".$_POST['level']."','".$_POST['langs']."','".$_POST['perms']."','".$_POST['target']."',".$_POST['user'].", ".$position.")";
		
		dolibarr_syslog("edit: insert menu entry sql=".$sql);
		$result=$db->query($sql);		
		if ($result > 0)
		{
			header("Location: ".DOL_URL_ROOT."/admin/menus/index.php");
			exit;
		}
		else
		{
			$mesg='<div class="error">Error '.$db->lasterror().' sql='.$sql.'</div>';
			$_GET["action"] = 'create';
		}
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
	exit;	
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
	exit;
}

// Suppression
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
	$this->db->begin();
	
	$sql = "SELECT c.rowid, c.fk_constraint FROM ".MAIN_DB_PREFIX."menu_const as c WHERE c.fk_menu = ".$_GET['menuId'];
	$res  = $db->query($sql);
	if ($res)
	{

		while ($obj = $db->fetch_object($res))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu_const WHERE rowid = ".$obj->rowid;
			$result = $db->query($sql);
			
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

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid = ".$_GET['menuId'];
	$db->query($sql);

	if ($result == 0)
	{
		$this->db->commit();

		llxHeader();
		print '<div class="ok">'.$langs->trans("MenuDeleted").'</div>';
		llxFooter();
		exit ;
	}
	else
	{
		$this->db->rollback();

		$reload = 0;
		$_GET["action"]='';
	}
}
  
  

/*
 * Affichage page
 */

$html=new Form($db);
$htmladmin=new FormAdmin($db);

llxHeader();



if (isset($_GET["action"]) && $_GET["action"] == 'create')
{
	print_titre($langs->trans("NewMenu"),'','setup');
	
	if ($mesg) print $mesg;
	else print '<br>';
	
	print '<form action="./edit.php?action=add&menuId='.$_GET['menuId'].'" method="post" name="formmenucreate">';

	print '<table class="border" width="100%">';
	
	// Id
	$parent_rowid = $_GET['menuId'];
	if ($_GET['menuId'])
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

	// MenuId Parent
	print '<tr><td><b>'.$langs->trans('MenuIdParent').'</b></td>';
	print '<td><input type="text" size="10" name="menuId" value="'.$parent_rowid.'"></td>';
	print '<td>'.$langs->trans('DetailMenuIdParent').'</td></tr>';

	// Handler
	print '<tr><td><b>'.$langs->trans('MenuHandler').'</b></td>';
	print '<td>';
	print $htmladmin->select_menu_families($menu_handler,'menu_handler',$dirleft);
	print '</td>';
	print '<td>'.$langs->trans('DetailMenuHandler').'</td></tr>';

	//User
	print '<tr><td nowrap="nowrap"><b>'.$langs->trans('MenuForUsers').'</b></td>';
	print '<td><select class="flat" name="user">';
	print '<option value="2" selected>'.$langs->trans("AllMenus").'</option>';
	print '<option value="0">'.$langs->trans('Interne').'</option>';
	print '<option value="1">'.$langs->trans('Externe').'</option>';
	print '</select></td>';
	print '<td>'.$langs->trans('DetailUser').'</td></tr>';

	// Type
	print '<tr><td><b>'.$langs->trans('Type').'</b></td><td>';
	print '<select name="type" class="flat">';
	print '<option value="">&nbsp;</option>';
	print '<option value="top">Top</option>';
	print '<option value="left">Left</option>';
	print '</select>';
	//	print '<input type="text" size="50" name="type" value="'.$type.'">';
	print '</td><td>'.$langs->trans('DetailType').'</td></tr>';

	//Titre
	print '<tr><td><b>'.$langs->trans('Title').'</b></td><td><input type="text" size="30" name="titre" value=""></td><td>'.$langs->trans('DetailTitre').'</td></tr>';

	//Langs
	print '<tr><td>'.$langs->trans('LangFile').'</td><td><input type="text" size="30" name="langs" value="'.$parent_langs.'"></td><td>'.$langs->trans('DetailLangs').'</td></tr>';

	//Position
	print '<tr><td>'.$langs->trans('Position').'</td><td><input type="text" size="5" name="position" value="'.$parent_langs.'"></td><td>'.$langs->trans('DetailPosition').'</td></tr>';

	//URL
	print '<tr><td><b>'.$langs->trans('URL').'</b></td><td><input type="text" size="60" name="url" value=""></td><td>'.$langs->trans('DetailUrl').'</td></tr>';

	//Target
	print '<tr><td>'.$langs->trans('Target').'</td><td><select class="flat" name="target">';
	print '<option value=""'.($menu->target==""?' selected="true"':'').'>'.$langs->trans('').'</option>';
	print '<option value="_new"'.($menu->target=="_new"?' selected="true"':'').'>'.$langs->trans('_new').'</option>';
	print '</select></td></td><td>'.$langs->trans('DetailTarget').'</td></tr>';
	//Perms
	print '<tr><td>'.$langs->trans('Rights').'</td><td><input type="text" size="60" name="perms" value=""></td><td>'.$langs->trans('DetailRight').'</td></tr>';

	// Boutons
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';	

	print '</table>';

	print '</form>';
}

elseif (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
	print_titre($langs->trans("ModifMenu"),'','setup');
	print '<br>';
	
	print '<form action="./edit.php?action=update" method="post" name="formmenuedit">';
	
	print '<table class="border" width="100%">';
	
	$menu = new Menubase($db);
	$result=$menu->fetch($_GET['menuId']);

	// MenuId Parent
	print '<tr><td>'.$langs->trans('MenuIdParent').'</td>';
	//$menu_handler
	//print '<td><input type="text" size="50" name="handler" value="all"></td>';
	print '<td>'.$menu->fk_menu.'</td>';
	print '<td>'.$langs->trans('DetailMenuIdParent').'</td></tr>';
	print '<input type="hidden" name="menuId" value="'.$_GET['menuId'].'">';

	// Id
	print '<tr><td>'.$langs->trans('Id').'</td><td>'.$menu->id.'</td><td>'.$langs->trans('DetailId').'</td></tr>';

	// Handler
	print '<tr><td>'.$langs->trans('MenuHandler').'</td><td>'.$menu->menu_handler.'</td><td>'.$langs->trans('DetailMenuHandler').'</td></tr>';

	// Type
	print '<tr><td>'.$langs->trans('Type').'</td><td>'.$menu->type.'</td><td>'.$langs->trans('DetailType').'</td></tr>';

	// User
	print '<tr><td nowrap="nowrap">'.$langs->trans('MenuForUsers').'</td><td><select class="flat" name="user">';
	print '<option value="2"'.($menu->user==2?' selected="true"':'').'>'.$langs->trans("All").'</option>';
	print '<option value="0"'.($menu->user==0?' selected="true"':'').'>'.$langs->trans('Interne').'</option>';
	print '<option value="1"'.($menu->user==1?' selected="true"':'').'>'.$langs->trans('Externe').'</option>';
	print '</select></td><td>'.$langs->trans('DetailUser').'</td></tr>';

	// Niveau
	//print '<tr><td>'.$langs->trans('Level').'</td><td>'.$menu->level.'</td><td>'.$langs->trans('DetailLevel').'</td></tr>';

	// Titre
	print '<tr><td>'.$langs->trans('Title').'</td><td><input type="text" size="30" name="titre" value="'.$menu->titre.'"></td><td>'.$langs->trans('DetailTitre').'</td></tr>';

	// Langs
	print '<tr><td>'.$langs->trans('LangFile').'</td><td><input type="text" size="30" name="langs" value="'.$menu->langs.'"></td><td>'.$langs->trans('DetailLangs').'</td></tr>';

	// Position
	print '<tr><td>'.$langs->trans('Position').'</td><td><input type="text" size="5" name="position" value="'.$menu->position.'"></td><td>'.$langs->trans('DetailPosition').'</td></tr>';

	// Url
	print '<tr><td>'.$langs->trans('URL').'</td><td><input type="text" size="60" name="url" value="'.$menu->url.'"></td><td>'.$langs->trans('DetailUrl').'</td></tr>';

	// Target
	print '<tr><td>'.$langs->trans('Target').'</td><td><select class="flat" name="target">';
	print '<option value=""'.($menu->target==""?' selected="true"':'').'>'.$langs->trans('').'</option>';
	print '<option value="_new"'.($menu->target=="_new"?' selected="true"':'').'>'.$langs->trans('_new').'</option>';
	print '</select></td></td><td>'.$langs->trans('DetailTarget').'</td></tr>';
	
	// Perms
	print '<tr><td>'.$langs->trans('Rights').'</td><td><input type="text" size="60" name="perms" value="'.$menu->perms.'"></td><td>'.$langs->trans('DetailRight').'</td></tr>';

	// Bouton			
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';

	print '</table>';
	
	print '</form>';
	
	print '<br>';

	if ($mesg) print $mesg.'<br>';
	
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
		print '<td>'.$langs->trans('Constraints').'</td>';
		print '<td width="250">'.$langs->trans('User').'</td>';
		print '<td width="16">&nbsp;</td>';
		print "</tr>\n";
		
		
		// Ajout de contraintes personalisees
		print '<form action="edit.php?action=add_const" method="post">';
		print '<input type="hidden" name="menuId" value="'.$_GET['menuId'].'">';
		print '<input type="hidden" name="type" value="perso">';

		$var=true;
		print '<tr '.$bc[$var].'>';
		print '  <td><textarea cols="70" name="constraint" rows="1"></textarea></td>';
		print '<td>';
		print '<select name="user">';
    	print '<option value="0"'.($menu->user==0?' selected="true"':'').'>'.$langs->trans('Interne').'</option>';
    	print '<option value="0"'.($menu->user==0?' selected="true"':'').'>'.$langs->trans('Interne').'</option>';
    	print '<option value="1"'.($menu->user==1?' selected="true"':'').'>'.$langs->trans('Externe').'</option>';
    	print '<option value="2"'.($menu->user==2?' selected="true"':'').'>Tous</option>';		
		print '</td>';
		print '<td align="center"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
		print '</tr>';
		print '</form>';
		
		
		// Ajout de contraintes predefinis
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
			print '<option value="'.$objc->rowid.'">'.dolibarr_trunc($objc->action,70).'</option>';
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
		print '<td align="center"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
		print '</tr>';

		print '</form>';

		print '</table>';
		$db->free($resql);
	}
	
	
	print '</table>';
	

}

print '</div>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
