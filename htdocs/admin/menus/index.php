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
		\file       htdocs/admin/menus/index.php
        \ingroup    core,menudb
		\brief      Gestion des menus
		\version    $Revision$
*/
 
require("./pre.inc.php");

$langs->load("other");

if (! $user->admin)
  accessforbidden();

$dirtop = "../../includes/menus/barre_top";
$dirleft = "../../includes/menus/barre_left";

$menu_handler_top=$conf->global->MAIN_MENU_BARRETOP;
$menu_handler_left=$conf->global->MAIN_MENU_BARRELEFT;
$menu_handler_top=eregi_replace('_backoffice\.php','',$menu_handler_top);
$menu_handler_top=eregi_replace('_frontoffice\.php','',$menu_handler_top);
$menu_handler_left=eregi_replace('_backoffice\.php','',$menu_handler_left);
$menu_handler_left=eregi_replace('_frontoffice\.php','',$menu_handler_left);

$menu_handler=$menu_handler_left;

if ($_REQUEST["menu_handler"]) $menu_handler=$_REQUEST["menu_handler"];


/*
* Actions
*/

if (isset($_GET["action"]) && $_GET["action"] == 'up')
{

	$sql = "SELECT m.rowid, m.order FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE m.rowid = ".$_GET["menuId"];
	$result = $db->query($sql);	
	
	$num = $db->num_rows();
	$i = 0;
	
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$precedent['rowid'] = $obj->rowid;
		$precedent['order'] = $obj->order;
		$i++;
	}
	
	// Menu top
	$sql = "SELECT m.rowid, m.order FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE m.order = ".($precedent['order'] - 1)." AND m.type = 'top'";
	$sql.= " AND menu_handler='".$menu_handler_top."'";
	$result = $db->query($sql);	
	
	$num = $db->num_rows();
	$i = 0;
	
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$suivant['rowid'] = $obj->rowid;
		$suivant['order'] = $obj->order;
		$i++;
	}
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m";
	$sql.= " SET m.order = ".$suivant['order'];
	$sql.= " WHERE m.rowid = ".$precedent['rowid'].""; // Monte celui select
	$db->query($sql);	
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m";
	$sql.= " SET m.order = ".$precedent['order'];
	$sql.= " WHERE m.rowid = ".$suivant['rowid'].""; // Descend celui du dessus
	$db->query($sql);		
}

if (isset($_GET["action"]) && $_GET["action"] == 'down')
{

	$sql = "SELECT m.rowid, m.order FROM ".MAIN_DB_PREFIX."menu as m WHERE m.rowid = ".$_GET["menuId"];
	$result = $db->query($sql);	
	
	$num = $db->num_rows();
	$i = 0;
	
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$precedent['rowid'] = $obj->rowid;
		$precedent['order'] = $obj->order;
		$i++;
	}
	
	$sql = "SELECT m.rowid, m.order";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE m.order = ".($precedent['order'] + 1)." AND type='top'";
	$result = $db->query($sql);	
	
	$num = $db->num_rows();
	$i = 0;
	
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$suivant['rowid'] = $obj->rowid;
		$suivant['order'] = $obj->order;
		$i++;
	}
	
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m SET m.order = ".$suivant['order']." WHERE m.rowid = ".$precedent['rowid'].""; // Monte celui select
	$db->query($sql);	
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m SET m.order = ".$precedent['order']." WHERE m.rowid = ".$suivant['rowid'].""; // Descend celui du dessus
	$db->query($sql);		
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
$html=new Form($db);

llxHeader();


print_fiche_titre($langs->trans("Menus"),'','setup');

print $langs->trans("MenusEditorDesc")."<br>\n";
print "<br>\n";

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

// Confirmation de la suppression de la facture
if ($_GET["action"] == 'delete')
{
	$sql = "SELECT m.titre FROM ".MAIN_DB_PREFIX."menu as m WHERE m.rowid = ".$_GET['menuId'];
	$result = $db->query($sql);
	$obj = $db->fetch_object($result);
    
    $html = new Form($db);
    $html->form_confirm("index.php?menu_handler=".$menu_handler."&menuId=".$_GET['menuId'],$langs->trans("DeleteMenu"),$langs->trans("ConfirmDeleteMenu",$obj->titre),"confirm_delete");
    print "<br />\n";
}

print $html->textwithwarning($langs->trans("FeatureExperimental"),$langs->trans("FeatureExperimental"),-1);
print '<br>';

print '<form name="newmenu" class="nocellnopadding" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" action="change_menu_handler">';
print $langs->trans("MenuHandler").': ';
print $html->select_menu_families($menu_handler,'menu_handler',$dirleft);
print ' &nbsp; <input type="submit" class="button" value="'.$langs->trans("Refresh").'">';
print '</form>';

print '<br>';

print '<table class="border" width="100%">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("TreeMenuPersonalized").'</td>';
print '</tr>';

print '<tr>';
print '<td>';

/*************************
 *      ARBORESCENCE     *       
 *************************/	

$rangLast = 0;
$idLast = -1;
if ($conf->use_javascript_ajax)
{
	print '<script src="menu.js" type="text/javascript"></script>';
}

/* cette fonction gère le décallage des éléments
   suivant leur position dans l'arborescence
*/
function affiche($tab,$rang) 
{
	global $rangLast, $idLast, $menu_handler;
	
	if ($conf->use_javascript_ajax)
	{
		if($rang == $rangLast)
		{
			print '<script type="text/javascript">imgDel('.$idLast.');</script>';
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
	print '<div class="menuEdit"><a href="edit.php?menu_handler='.$menu_handler.'&action=edit&menuId='.$tab[0].'"><img src="../../theme/auguria/img/edit.png" class="menuEdit" id="edit'.$tab[0].'" /></a></div>';
	print '<div class="menuNew"><a href="edit.php?menu_handler='.$menu_handler.'&action=create&menuId='.$tab[0].'"><img src="../../theme/auguria/img/filenew.png" class="menuNew" id="new'.$tab[0].'" /></a></div>';
	print '<div class="menuDel"><a href="index.php?menu_handler='.$menu_handler.'&action=delete&menuId='.$tab[0].'"><img src="../../theme/auguria/img/stcomm-1.png" class="menuDel" id="del'.$tab[0].'" /></a></div>';
	print '<div class="menuFleche"><a href="index.php?menu_handler='.$menu_handler.'&action=up&menuId='.$tab[0].'">'.img_picto("Monter","1uparrow").'</a><a href="index.php?action=down&menuId='.$tab[0].'">'.img_picto("Descendre","1downarrow").'</a></div>';
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
$sql.= " ORDER BY m.order, m.rowid";
$res  = $db->query($sql);

if ($res)
{
	$num = $db->num_rows();

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
	
print '<div class="tabsAction">';
print '<a class="butAction" href="'.DOL_URL_ROOT.'/admin/menus/edit.php?menuId=0&amp;action=create">'.$langs->trans("NewMenu").'</a>';
print '</div>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>

