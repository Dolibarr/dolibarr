<?php
/* Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2011 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *  \file       htdocs/admin/menus/index.php
 *  \ingroup    core
 *  \brief      Index page for menu editor
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/treeview.lib.php");

$langs->load("other");
$langs->load("admin");

if (! $user->admin) accessforbidden();

$dirstandard = array("/core/menus/standard");
$dirsmartphone = array("/core/menus/smartphone");
foreach($conf->menus_modules as $dir)
{
    $dirstandard[]=$dir.'/standard';
    $dirsmartphone[]=$dir.'/standard';
}

$mesg=$_GET["mesg"];

$menu_handler_top=$conf->global->MAIN_MENU_STANDARD;
$menu_handler_smartphone=$conf->global->MAIN_MENU_SMARTPHONE;
$menu_handler_top=preg_replace('/_backoffice.php/i','',$menu_handler_top);
$menu_handler_top=preg_replace('/_frontoffice.php/i','',$menu_handler_top);
$menu_handler_smartphone=preg_replace('/_backoffice.php/i','',$menu_handler_smartphone);
$menu_handler_smartphone=preg_replace('/_frontoffice.php/i','',$menu_handler_smartphone);

$menu_handler=$menu_handler_top;

if ($_REQUEST["handler_origine"]) $menu_handler=$_REQUEST["handler_origine"];
if ($_REQUEST["menu_handler"])    $menu_handler=$_REQUEST["menu_handler"];


/*
* Actions
*/

if (isset($_GET["action"]) && ($_GET["action"] == 'up'))
{
	$current=array();
	$previous=array();

	// Get current position
	$sql = "SELECT m.rowid, m.position, m.type, m.fk_menu";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE m.rowid = ".$_GET["menuId"];
	dol_syslog("admin/menus/index.php ".$sql);
	$result = $db->query($sql);
	$num = $db->num_rows($result);
	$i = 0;
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$current['rowid'] = $obj->rowid;
		$current['order'] = $obj->position;
		$current['type'] = $obj->type;
		$current['fk_menu'] = $obj->fk_menu;
		$i++;
	}

	// Menu before
	$sql = "SELECT m.rowid, m.position";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE (m.position < ".($current['order'])." OR (m.position = ".($current['order'])." AND rowid < ".$_GET["menuId"]."))";
	$sql.= " AND m.menu_handler='".$menu_handler."'";
	$sql.= " AND m.entity = ".$conf->entity;
	$sql.= " AND m.type = '".$current['type']."'";
	$sql.= " AND m.fk_menu = '".$current['fk_menu']."'";
	$sql.= " ORDER BY m.position, m.rowid";
	dol_syslog("admin/menus/index.php ".$sql);
	$result = $db->query($sql);
	$num = $db->num_rows($result);
	$i = 0;
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$previous['rowid'] = $obj->rowid;
		$previous['order'] = $obj->position;
		$i++;
	}

	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m";
	$sql.= " SET m.position = ".$previous['order'];
	$sql.= " WHERE m.rowid = ".$current['rowid']; // Up the selected entry
	dol_syslog("admin/menus/index.php ".$sql);
	$db->query($sql);
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m";
	$sql.= " SET m.position = ".($current['order']!=$previous['order']?$current['order']:$current['order']+1);
	$sql.= " WHERE m.rowid = ".$previous['rowid']; // Descend celui du dessus
	dol_syslog("admin/menus/index.php ".$sql);
	$db->query($sql);
}

if (isset($_GET["action"]) && $_GET["action"] == 'down')
{
	$current=array();
	$next=array();

	// Get current position
	$sql = "SELECT m.rowid, m.position, m.type, m.fk_menu";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE m.rowid = ".$_GET["menuId"];
	dol_syslog("admin/menus/index.php ".$sql);
	$result = $db->query($sql);
	$num = $db->num_rows($result);
	$i = 0;
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$current['rowid'] = $obj->rowid;
		$current['order'] = $obj->position;
		$current['type'] = $obj->type;
		$current['fk_menu'] = $obj->fk_menu;
		$i++;
	}

	// Menu after
	$sql = "SELECT m.rowid, m.position";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE (m.position > ".($current['order'])." OR (m.position = ".($current['order'])." AND rowid > ".$_GET["menuId"]."))";
	$sql.= " AND m.menu_handler='".$menu_handler."'";
	$sql.= " AND m.entity = ".$conf->entity;
	$sql.= " AND m.type = '".$current['type']."'";
	$sql.= " AND m.fk_menu = '".$current['fk_menu']."'";
	$sql.= " ORDER BY m.position, m.rowid";
	dol_syslog("admin/menus/index.php ".$sql);
	$result = $db->query($sql);
	$num = $db->num_rows($result);
	$i = 0;
	while($i < $num)
	{
		$obj = $db->fetch_object($result);
		$next['rowid'] = $obj->rowid;
		$next['order'] = $obj->position;
		$i++;
	}

	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m";
	$sql.= " SET m.position = ".($current['order']!=$next['order']?$next['order']:$current['order']+1); // Down the selected entry
	$sql.= " WHERE m.rowid = ".$current['rowid'];
	dol_syslog("admin/menus/index.php ".$sql);
	$db->query($sql);
	$sql = "UPDATE ".MAIN_DB_PREFIX."menu as m";	// Up the next entry
	$sql.= " SET m.position = ".$current['order'];
	$sql.= " WHERE m.rowid = ".$next['rowid'];
	dol_syslog("admin/menus/index.php ".$sql);
	$db->query($sql);
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
	$db->begin();

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu";
	$sql.= " WHERE rowid = ".$_GET['menuId'];
	$resql=$db->query($sql);
	if ($resql)
	{
		$db->commit();

		Header("Location: ".DOL_URL_ROOT.'/admin/menus/index.php?menu_handler='.$menu_handler.'&mesg='.urlencode($langs->trans("MenuDeleted")));
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
 * View
 */

$form=new Form($db);
$formadmin=new FormAdmin($db);
$arrayofjs=array('/admin/menus/menu.js.php?lang='.$langs->defaultlang);

llxHeader('',$langs->trans("Menus"),'','',0,0,$arrayofjs);


print_fiche_titre($langs->trans("Menus"),'','setup');


dol_htmloutput_mesg($mesg);


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/menus.php";
$head[$h][1] = $langs->trans("MenuHandlers");
$head[$h][2] = 'handler';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/menus/index.php";
$head[$h][1] = $langs->trans("MenuAdmin");
$head[$h][2] = 'editor';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/menus/other.php";
$head[$h][1] = $langs->trans("Miscellanous");
$head[$h][2] = 'misc';
$h++;

dol_fiche_head($head, 'editor', $langs->trans("Menus"));

print $langs->trans("MenusEditorDesc")."<br>\n";
print "<br>\n";


// Confirmation for remove menu entry
if ($_GET["action"] == 'delete')
{
	$sql = "SELECT m.titre";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE m.rowid = ".$_GET['menuId'];
	$result = $db->query($sql);
	$obj = $db->fetch_object($result);

    $ret=$form->form_confirm("index.php?menu_handler=".$menu_handler."&menuId=".$_GET['menuId'],$langs->trans("DeleteMenu"),$langs->trans("ConfirmDeleteMenu",$obj->titre),"confirm_delete");
    if ($ret == 'html') print '<br>';
}


print '<form name="newmenu" class="nocellnopadd" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" action="change_menu_handler">';
print $langs->trans("MenuHandler").': ';
print $formadmin->select_menu_families($menu_handler,'menu_handler',array_merge($dirstandard,$dirsmartphone));
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
	/*-------------------- MAIN -----------------------
	tableau des elements de l'arbre:
	c'est un tableau a 2 dimensions.
	Une ligne represente un element : data[$x]
	chaque ligne est decomposee en 3 donnees:
	  - l'index de l'élément
	  - l'index de l'élément parent
	  - la chaine a afficher
	ie: data[]= array (index, index parent, chaine )
	*/
	//il faut d'abord declarer un element racine de l'arbre

	$data[] = array(0,-1,"racine");

	//puis tous les elements enfants


	$sql = "SELECT m.rowid, m.fk_menu, m.titre, m.langs";
	$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
	$sql.= " WHERE menu_handler = '".$menu_handler."'";
	$sql.= " AND entity = ".$conf->entity;
	$sql.= " AND fk_menu >= 0";
	$sql.= " ORDER BY m.position, m.rowid";		// Order is position then rowid (because we need a sort criteria when position is same)
	$res  = $db->query($sql);

	if ($res)
	{
		$num = $db->num_rows($res);

		$i = 1;
		while ($menu = $db->fetch_array($res))
		{
			if (! empty($menu['langs'])) $langs->load($menu['langs']);
			$titre = $langs->trans($menu['titre']);
			$data[] = array($menu['rowid'],$menu['fk_menu'],$titre);
			$i++;
		}
	}

	// Appelle de la fonction recursive (ammorce)
	// avec recherche depuis la racine.
	// array($menu['rowid'],$menu['fk_menu'],$titre);
	tree_recur($data,0,0);

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

llxFooter();