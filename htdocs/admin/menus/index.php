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
   \file       htdocs/admin/menus/index.php
   \ingroup    core
   \brief      Index page for menu editor
   \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formadmin.class.php");

$langs->load("other");
$langs->load("admin");

if (! $user->admin)
  accessforbidden();

$dirtop = "../../includes/menus/barre_top";
$dirleft = "../../includes/menus/barre_left";

$mesg=$_GET["mesg"];

$menu_handler_top=eregi_replace('\.php','',$conf->global->MAIN_MENU_BARRETOP);
$menu_handler_left=eregi_replace('\.php','',$conf->global->MAIN_MENU_BARRELEFT);
$menu_handler_top=eregi_replace('_backoffice','',$menu_handler_top);
$menu_handler_top=eregi_replace('_frontoffice','',$menu_handler_top);
$menu_handler_left=eregi_replace('_backoffice','',$menu_handler_left);
$menu_handler_left=eregi_replace('_frontoffice','',$menu_handler_left);

$menu_handler=$menu_handler_left;

if ($_REQUEST["menu_handler"]) $menu_handler=$_REQUEST["menu_handler"];


/*
* Actions
*/

if (isset($_GET["action"]) && ($_GET["action"] == 'up'))
{
	$sql = "SELECT m.rowid, m.position FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE m.rowid = ".$_GET["menuId"];
	$result = $db->query($sql);	
	
	$num = $db->num_rows($result);
	$i = 0;
	
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$precedent['rowid'] = $obj->rowid;
		$precedent['order'] = $obj->position;
		$i++;
	}
	
	// Menu top
	$sql = "SELECT m.rowid, m.position FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE m.position = ".($precedent['order'] - 1)." AND m.type = 'top'";
	$sql.= " AND menu_handler='".$menu_handler_top."'";
	$result = $db->query($sql);	
	
	$num = $db->num_rows($result);
	$i = 0;
	
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$suivant['rowid'] = $obj->rowid;
		$suivant['order'] = $obj->position;
		$i++;
	}
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m";
	$sql.= " SET m.position = ".$suivant['order'];
	$sql.= " WHERE m.rowid = ".$precedent['rowid'].""; // Monte celui select
	$db->query($sql);	
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m";
	$sql.= " SET m.position = ".$precedent['order'];
	$sql.= " WHERE m.rowid = ".$suivant['rowid'].""; // Descend celui du dessus
	$db->query($sql);		
}

if (isset($_GET["action"]) && $_GET["action"] == 'down')
{

	$sql = "SELECT m.rowid, m.position FROM ".MAIN_DB_PREFIX."menu as m WHERE m.rowid = ".$_GET["menuId"];
	$result = $db->query($sql);	
	
	$num = $db->num_rows($result);
	$i = 0;
	
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$precedent['rowid'] = $obj->rowid;
		$precedent['order'] = $obj->position;
		$i++;
	}
	
	$sql = "SELECT m.rowid, m.position";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE m.position = ".($precedent['order'] + 1)." AND type='top'";
	$result = $db->query($sql);	
	
	$num = $db->num_rows($result);
	$i = 0;
	
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$suivant['rowid'] = $obj->rowid;
		$suivant['order'] = $obj->position;
		$i++;
	}
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m SET m.position = ".$suivant['order']." WHERE m.rowid = ".$precedent['rowid'].""; // Monte celui select
	$db->query($sql);	
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m SET m.position = ".$precedent['order']." WHERE m.rowid = ".$suivant['rowid'].""; // Descend celui du dessus
	$db->query($sql);		
}    

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
	$db->begin();
	
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

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid = ".$_GET['menuId'];
	$resql=$db->query($sql);
	if ($resql)
	{
		$db->commit();

		Header("Location: ".DOL_URL_ROOT.'/admin/menus/index.php?mesg='.urlencode($langs->trans("MenuDeleted")));
		exit ;
	}
	else
	{
		$db->rollback();

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


print_fiche_titre($langs->trans("Menus"),'','setup');

print $langs->trans("MenusEditorDesc")."<br>\n";
print "<br>\n";

if ($mesg) print '<div class="ok">'.$mesg.'.</div><br>';


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/menus.php";
$head[$h][1] = $langs->trans("MenuHandlers");
$head[$h][2] = 'handler';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/menus/index.php";
$head[$h][1] = $langs->trans("MenuAdmin");
$head[$h][2] = 'editor';
$h++;

dolibarr_fiche_head($head, 'editor', $langs->trans("Menus"));

// Confirmation de la suppression menu
if ($_GET["action"] == 'delete')
{
	$sql = "SELECT m.titre FROM ".MAIN_DB_PREFIX."menu as m WHERE m.rowid = ".$_GET['menuId'];
	$result = $db->query($sql);
	$obj = $db->fetch_object($result);
    
    $html = new Form($db);
    $html->form_confirm("index.php?menu_handler=".$menu_handler."&menuId=".$_GET['menuId'],$langs->trans("DeleteMenu"),$langs->trans("ConfirmDeleteMenu",$obj->titre),"confirm_delete");
    print "<br>\n";
}


print '<form name="newmenu" class="nocellnopadding" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" action="change_menu_handler">';
print $langs->trans("MenuHandler").': ';
print $htmladmin->select_menu_families($menu_handler,'menu_handler',$dirleft);
print ' &nbsp; <input type="submit" class="button" value="'.$langs->trans("Refresh").'">';
print '</form>';

print '<br>';

print '<table class="border" width="100%">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("TreeMenuPersonalized").'</td>';
print '</tr>';

print '<tr>';
print '<td>';

// ARBORESCENCE	

$rangLast = 0;
$idLast = -1;
if ($conf->use_javascript_ajax)
{
	print '<script src="menu.js" type="text/javascript"></script>';

	/*-------------------- MAIN -----------------------
	tableau des éléments de l'arbre:
	c'est un tableau à 2 dimensions.
	Une ligne représente un élément : data[$x]
	chaque ligne est décomposée en 3 données:
	  - l'index de l'élément
	  - l'index de l'élément parent
	  - la chaîne à afficher
	ie: data[]= array (index, index parent, chaine )    
	*/
	//il faut d'abord déclarer un élément racine de l'arbre

	$data[] = array(0,-1,"racine");

	//puis tous les éléments enfants


	$sql = "SELECT m.rowid, m.fk_menu, m.titre, m.langs";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE menu_handler='".$menu_handler."'";
	$sql.= " ORDER BY m.position, m.rowid";
	$res  = $db->query($sql);

	if ($res)
	{
		$num = $db->num_rows($res);

		$i = 1;
		while ($menu = $db->fetch_array ($res))
		{
			$langs->load($menu['langs']);
			$titre = $langs->trans($menu['titre']);
			$data[] = array($menu['rowid'],$menu['fk_menu'],$titre);
			$i++;		
		}
	}

	//appelle de la fonction récursive (ammorce)
	//avec recherche depuis la racine.
	print '<ul class="arbre">';
	recur($data,0,0);
	print '<script type="text/javascript">imgDel('.$idLast.')</script>';
	print '</ul>';

	print '</td>';

	print '</tr>';

	print '</table>';


	print '</div>';


	/*
	 * Boutons actions
	 */
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/admin/menus/edit.php?menuId=0&amp;action=create&amp;menu_handler='.urlencode($menu_handler).'">'.$langs->trans("NewMenu").'</a>';
	print '</div>';
}
else
{
	$langs->load("errors");
	print '<div class="error">'.$langs->trans("ErrorFeatureNeedJavascript").'</div>';
}

$db->close();

print '<br>';

llxFooter('$Date$ - $Revision$');



/* cette fonction gère le décallage des éléments
   suivant leur position dans l'arborescence
*/
function affiche($tab,$rang) 
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
	print '<strong>';
	print '<a href="edit.php?menu_handler='.$menu_handler.'&action=edit&menuId='.$tab[0].'">'.$tab[2].'</a></strong>';
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
    $pere :index de l'élément courrant
    $rang :décallage de l'élément
*/
function recur($tab,$pere,$rang) {

	if ($rang > 10)	return;	// Protection contre boucle infinie
	
	//ballayage du tableau
	for ($x=0;$x<count($tab);$x++)
	{
		//si un élément a pour père : $pere
		if ($tab[$x][1]==$pere) {

		   //on l'affiche avec le décallage courrant
			affiche($tab[$x],$rang);
			
		   /*et on recherche ses fils
			 en rappelant la fonction recur()
		   (+ incrémentation du décallage)*/
		   recur($tab,$tab[$x][0],$rang+1);
		}
	}
}

?>

