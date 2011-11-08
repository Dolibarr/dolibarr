<?php
/* Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file       htdocs/admin/menus/edit.php
 *		\ingroup    core
 *		\brief      Tool to edit menus
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/menubase.class.php");


$langs->load("admin");

if (! $user->admin)
accessforbidden();

$dirtop = "/core/menus/standard";
$dirleft = "/core/menus/standard";
$dirsmartphone = "/core/menus/smartphone";

$dirmenu = array($dirleft,$dirsmartphone);

$action=GETPOST('action');
$mesg=GETPOST('mesg');

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

if ($action == 'update')
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
            $menu->enabled=$_POST['enabled'];
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
        $_GET["menuId"] = $_POST['menuId'];
        $action = "edit";
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

if ($action == 'add')
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
        $action = 'create';
        $error++;
    }
    if (! $error && ! $_POST['type'])
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Type")).'</div>';
        $action = 'create';
        $error++;
    }
    if (! $error && ! $_POST['url'])
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Url")).'</div>';
        $action = 'create';
        $error++;
    }
    if (! $error && ! $_POST['titre'])
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("Title")).'</div>';
        $action = 'create';
        $error++;
    }
    if (! $error && $_POST['menuId'] && $_POST['type'] == 'top')
    {
        $mesg='<div class="error">'.$langs->trans("ErrorTopMenuMustHaveAParentWithId0").'</div>';
        $action = 'create';
        $error++;
    }
    if (! $error && ! $_POST['menuId'] && $_POST['type'] == 'left')
    {
        $mesg='<div class="error">'.$langs->trans("ErrorLeftMenuMustHaveAParentId").'</div>';
        $action = 'create';
        $error++;
    }

    if (! $error)
    {
        $menu = new Menubase($db);
        $menu->fk_menu=$_POST['menuId'];
        $menu->menu_handler=$_POST['menu_handler'];
        $menu->type=$_POST['type'];
        $menu->titre=$_POST['titre'];
        $menu->leftmenu=$_POST['leftmenu'];
        $menu->url=$_POST['url'];
        $menu->langs=$_POST['langs'];
        $menu->position=$_POST['position'];
        $menu->enabled=$_POST['enabled'];
        $menu->perms=$_POST['perms'];
        $menu->target=$_POST['target'];
        $menu->user=$_POST['user'];
        $result=$menu->create($user);
        if ($result > 0)
        {
            header("Location: ".DOL_URL_ROOT."/admin/menus/index.php?menu_handler=".$_POST['menu_handler']);
            exit;
        }
        else
        {
            $action = 'create';
            $mesg='<div class="error">'.$menu->error.'</div>';
        }
    }
}

// delete
if ($action == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
    $this->db->begin();

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
 * View
 */

$form=new Form($db);
$formadmin=new FormAdmin($db);

llxHeader('',$langs->trans("Menu"));


if ($action == 'create')
{
    print '<script type="text/javascript" language="javascript">
    jQuery(document).ready(function() {
    	function init_topleft()
    	{
    		if (jQuery("#topleft").val() == \'top\')
    		{
	    		jQuery("#menuId").attr(\'disabled\',\'disabled\');
	    		jQuery("#menuId").val(\'\');
			}
    		else
    		{
    			jQuery("#menuId").removeAttr(\'disabled\');
    		}
    	}
    	init_topleft();
    	jQuery("#topleft").click(function() {
    		init_topleft();
    	});
    });
    </script>';

    print_fiche_titre($langs->trans("NewMenu"),'','setup');

    print '<form action="./edit.php?action=add&menuId='.$_GET['menuId'].'" method="post" name="formmenucreate">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<table class="border" width="100%">';

    // Id
    $parent_rowid = $_GET['menuId'];
    if ($_GET['menuId'])
    {
        $sql = "SELECT m.rowid, m.mainmenu, m.level, m.langs FROM ".MAIN_DB_PREFIX."menu as m WHERE m.rowid = ".$_GET['menuId'];
        $res  = $db->query($sql);
        if ($res)
        {

            while ($menu = $db->fetch_array($res))
            {
                $parent_rowid = $menu['rowid'];
                $parent_mainmenu = $menu['mainmenu'];
                $parent_langs = $menu['langs'];
                $parent_level = $menu['level'];
            }
        }
    }

    // Handler
    print '<tr><td class="fieldrequired">'.$langs->trans('MenuHandler').'</td>';
    print '<td>';
    print $formadmin->select_menu_families($menu_handler,'menu_handler',$dirmenu);
    print '</td>';
    print '<td>'.$langs->trans('DetailMenuHandler').'</td></tr>';

    //User
    print '<tr><td nowrap="nowrap" class="fieldrequired">'.$langs->trans('MenuForUsers').'</td>';
    print '<td><select class="flat" name="user">';
    print '<option value="2" selected>'.$langs->trans("AllMenus").'</option>';
    print '<option value="0">'.$langs->trans('Interne').'</option>';
    print '<option value="1">'.$langs->trans('Externe').'</option>';
    print '</select></td>';
    print '<td>'.$langs->trans('DetailUser').'</td></tr>';

    // Type
    print '<tr><td class="fieldrequired">'.$langs->trans('Type').'</td><td>';
    if ($parent_rowid)
    {
        print 'Left';
        print '<input type="hidden" name="type" value="left">';
    }
    else
    {
        print '<select name="type" class="flat" id="topleft">';
        print '<option value="">&nbsp;</option>';
        print '<option value="top"'.($_POST["type"] && $_POST["type"]=='top'?' selected="true"':'').'>Top</option>';
        print '<option value="left"'.($_POST["type"] && $_POST["type"]=='left'?' selected="true"':'').'>Left</option>';
        print '</select>';
    }
    //	print '<input type="text" size="50" name="type" value="'.$type.'">';
    print '</td><td>'.$langs->trans('DetailType').'</td></tr>';

    // MenuId Parent
    print '<tr><td class="fieldrequired">'.$langs->trans('MenuIdParent').'</td>';
    if ($parent_rowid)
    {
        print '<td>'.$parent_rowid.'<input type="hidden" name="menuId" value="'.$parent_rowid.'"></td>';
    }
    else
    {
        print '<td><input type="text" size="8" id="menuId" name="menuId" value="'.($parent_rowid?$parent_rowid:'').'"></td>';
    }
    print '<td>'.$langs->trans('DetailMenuIdParent').'</td></tr>';

    // Title
    print '<tr><td class="fieldrequired">'.$langs->trans('Title').'</td><td><input type="text" size="30" name="titre" value="'.$_POST["titre"].'"></td><td>'.$langs->trans('DetailTitre').'</td></tr>';

    // Langs
    print '<tr><td>'.$langs->trans('LangFile').'</td><td><input type="text" size="30" name="langs" value="'.$parent_langs.'"></td><td>'.$langs->trans('DetailLangs').'</td></tr>';

    // Position
    print '<tr><td>'.$langs->trans('Position').'</td><td><input type="text" size="5" name="position" value="'.(isset($_POST["position"])?$_POST["position"]:100).'"></td><td>'.$langs->trans('DetailPosition').'</td></tr>';

    // URL
    print '<tr><td class="fieldrequired">'.$langs->trans('URL').'</td><td><input type="text" size="60" name="url" value="'.$_POST["url"].'"></td><td>'.$langs->trans('DetailUrl').'</td></tr>';

    // Target
    print '<tr><td>'.$langs->trans('Target').'</td><td><select class="flat" name="target">';
    print '<option value=""'.($menu->target==""?' selected="true"':'').'>'.$langs->trans('').'</option>';
    print '<option value="_blank"'.($menu->target=="_blank"?' selected="true"':'').'>'.$langs->trans('_blank').'</option>';
    print '</select></td></td><td>'.$langs->trans('DetailTarget').'</td></tr>';

    // Enabled
    print '<tr><td>'.$langs->trans('Enabled').'</td><td><input type="text" size="60" name="enabled" value="'.$_POST["enabled"].'"></td><td>'.$langs->trans('DetailEnabled').'</td></tr>';

    // Perms
    print '<tr><td>'.$langs->trans('Rights').'</td><td><input type="text" size="60" name="perms" value="'.$_POST["perms"].'"></td><td>'.$langs->trans('DetailRight').'</td></tr>';

    print '</table>';

    // Boutons
    print '<center><br><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
    print ' &nbsp; &nbsp; ';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

    print '</form>';

    dol_htmloutput_mesg($mesg);
}
elseif ($action == 'edit')
{
    print_fiche_titre($langs->trans("ModifMenu"),'','setup');
    print '<br>';

    print '<form action="./edit.php?action=update" method="POST" name="formmenuedit">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="handler_origine" value="'.$menu_handler.'">';
    print '<input type="hidden" name="menuId" value="'.$_GET['menuId'].'">';

    print '<table class="border" width="100%">';

    $menu = new Menubase($db);
    $result=$menu->fetch($_GET['menuId']);
    //var_dump($menu);

    // Id
    print '<tr><td>'.$langs->trans('Id').'</td><td>'.$menu->id.'</td><td>'.$langs->trans('DetailId').'</td></tr>';

    // Module
    print '<tr><td>'.$langs->trans('MenuModule').'</td><td>'.$menu->module.'</td><td>'.$langs->trans('DetailMenuModule').'</td></tr>';

    // Handler
    print '<tr><td class="fieldrequired">'.$langs->trans('MenuHandler').'</td><td>'.$menu->menu_handler.'</td><td>'.$langs->trans('DetailMenuHandler').'</td></tr>';

    // User
    print '<tr><td nowrap="nowrap" class="fieldrequired">'.$langs->trans('MenuForUsers').'</td><td><select class="flat" name="user">';
    print '<option value="2"'.($menu->user==2?' selected="true"':'').'>'.$langs->trans("All").'</option>';
    print '<option value="0"'.($menu->user==0?' selected="true"':'').'>'.$langs->trans('Interne').'</option>';
    print '<option value="1"'.($menu->user==1?' selected="true"':'').'>'.$langs->trans('Externe').'</option>';
    print '</select></td><td>'.$langs->trans('DetailUser').'</td></tr>';

    // Type
    print '<tr><td class="fieldrequired">'.$langs->trans('Type').'</td><td>'.$menu->type.'</td><td>'.$langs->trans('DetailType').'</td></tr>';

    // MenuId Parent
    print '<tr><td class="fieldrequired">'.$langs->trans('MenuIdParent').'</td>';
    //$menu_handler
    //print '<td><input type="text" size="50" name="handler" value="all"></td>';
    print '<td>'.$menu->fk_menu.'</td>';
    print '<td>'.$langs->trans('DetailMenuIdParent').'</td></tr>';

    // Niveau
    //print '<tr><td>'.$langs->trans('Level').'</td><td>'.$menu->level.'</td><td>'.$langs->trans('DetailLevel').'</td></tr>';

    // Titre
    print '<tr><td class="fieldrequired">'.$langs->trans('Title').'</td><td><input type="text" size="30" name="titre" value="'.$menu->titre.'"></td><td>'.$langs->trans('DetailTitre').'</td></tr>';

    // Langs
    print '<tr><td>'.$langs->trans('LangFile').'</td><td><input type="text" size="30" name="langs" value="'.$menu->langs.'"></td><td>'.$langs->trans('DetailLangs').'</td></tr>';

    // Position
    print '<tr><td>'.$langs->trans('Position').'</td><td><input type="text" size="5" name="position" value="'.$menu->position.'"></td><td>'.$langs->trans('DetailPosition').'</td></tr>';

    // Url
    print '<tr><td class="fieldrequired">'.$langs->trans('URL').'</td><td><input type="text" size="60" name="url" value="'.$menu->url.'"></td><td>'.$langs->trans('DetailUrl').'</td></tr>';

    // Target
    print '<tr><td>'.$langs->trans('Target').'</td><td><select class="flat" name="target">';
    print '<option value=""'.($menu->target==""?' selected="true"':'').'>'.$langs->trans('').'</option>';
    print '<option value="_blank"'.($menu->target=="_blank"?' selected="true"':'').'>'.$langs->trans('_blank').'</option>';
    print '</select></td><td>'.$langs->trans('DetailTarget').'</td></tr>';

    // Enabled
    print '<tr><td>'.$langs->trans('Enabled').'</td><td><input type="text" size="60" name="enabled" value="'.$menu->enabled.'"></td><td>'.$langs->trans('DetailEnabled').'</td></tr>';

    // Perms
    print '<tr><td>'.$langs->trans('Rights').'</td><td><input type="text" size="60" name="perms" value="'.$menu->perms.'"></td><td>'.$langs->trans('DetailRight').'</td></tr>';

    print '</table>';

    // Bouton
    print '<center><br><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
    print ' &nbsp; &nbsp; ';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

    print '</form>';

    print '<br>';

    dol_htmloutput_mesg($mesg);
}


$db->close();

llxFooter();
?>
