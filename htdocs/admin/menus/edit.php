<?php
/* Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2016      Meziane Sof          <virtualsof@yahoo.fr>
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
 */

/**
 *		\file       htdocs/admin/menus/edit.php
 *		\ingroup    core
 *		\brief      Tool to edit menus
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';


$langs->load("admin");
$langs->load('other');

if (! $user->admin) accessforbidden();

$dirstandard = array();
$dirsmartphone = array();
$dirmenus=array_merge(array("/core/menus/"),(array) $conf->modules_parts['menus']);
foreach($dirmenus as $dirmenu)
{
    $dirstandard[]=$dirmenu.'standard';
    $dirsmartphone[]=$dirmenu.'smartphone';
}

$action=GETPOST('action');

$menu_handler_top=$conf->global->MAIN_MENU_STANDARD;
$menu_handler_smartphone=$conf->global->MAIN_MENU_SMARTPHONE;
$menu_handler_top=preg_replace('/_backoffice.php/i','',$menu_handler_top);
$menu_handler_top=preg_replace('/_frontoffice.php/i','',$menu_handler_top);
$menu_handler_smartphone=preg_replace('/_backoffice.php/i','',$menu_handler_smartphone);
$menu_handler_smartphone=preg_replace('/_frontoffice.php/i','',$menu_handler_smartphone);

$menu_handler=$menu_handler_top;

if (GETPOST("handler_origine")) $menu_handler=GETPOST("handler_origine");
if (GETPOST("menu_handler"))    $menu_handler=GETPOST("menu_handler");



/*
 * Actions
 */

if ($action == 'update')
{
    if (! $_POST['cancel'])
    {
        $leftmenu=''; $mainmenu='';
        if (! empty($_POST['menuIdParent']) && ! is_numeric($_POST['menuIdParent']))
        {
            $tmp=explode('&',$_POST['menuIdParent']);
            foreach($tmp as $s)
            {
                if (preg_match('/fk_mainmenu=/',$s))
                {
                    $mainmenu=preg_replace('/fk_mainmenu=/','',$s);
                }
                if (preg_match('/fk_leftmenu=/',$s))
                {
                    $leftmenu=preg_replace('/fk_leftmenu=/','',$s);
                }
            }
        }
        
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
            if (is_numeric($_POST['menuIdParent']))
            {
            	$menu->fk_menu=$_POST['menuIdParent'];
            }
            else
            {
    	       	if ($_POST['type'] == 'top') $menu->fk_menu=0;
    	       	else $menu->fk_menu=-1;
            	$menu->fk_mainmenu=$mainmenu;
            	$menu->fk_leftmenu=$leftmenu;
            }

            $result=$menu->update($user);
            if ($result > 0)
            {
	            setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
            }
            else
            {
	            setEventMessages($menu->error, $menu->errors, 'errors');
            }
        }
        else
        {
	        setEventMessages($menu->error, $menu->errors, 'errors');
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

    $leftmenu=''; $mainmenu='';
    if (! empty($_POST['menuId']) && ! is_numeric($_POST['menuId']))
    {
	    $tmp=explode('&',$_POST['menuId']);
	    foreach($tmp as $s)
	    {
	    	if (preg_match('/fk_mainmenu=/',$s))
	    	{
				$mainmenu=preg_replace('/fk_mainmenu=/','',$s);
	    	}
	    	if (preg_match('/fk_leftmenu=/',$s))
	    	{
	    		$leftmenu=preg_replace('/fk_leftmenu=/','',$s);
	    	}
	    }
    }

    $langs->load("errors");

    $error=0;
    if (! $error && ! $_POST['menu_handler'])
    {
	    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("MenuHandler")), null, 'errors');
        $action = 'create';
        $error++;
    }
    if (! $error && ! $_POST['type'])
    {
	    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Type")), null, 'errors');
        $action = 'create';
        $error++;
    }
    if (! $error && ! $_POST['url'])
    {
	    setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("URL")), null, 'errors');
        $action = 'create';
        $error++;
    }
    if (! $error && ! $_POST['titre'])
    {
	    setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Title")), null, 'errors');
        $action = 'create';
        $error++;
    }
    if (! $error && $_POST['menuId'] && $_POST['type'] == 'top')
    {
	    setEventMessages($langs->trans("ErrorTopMenuMustHaveAParentWithId0"), null, 'errors');
        $action = 'create';
        $error++;
    }
    if (! $error && empty($_POST['menuId']) && $_POST['type'] == 'left')
    {
	    setEventMessages($langs->trans("ErrorLeftMenuMustHaveAParentId"), null, 'errors');
        $action = 'create';
        $error++;
    }

    if (! $error)
    {
        $menu = new Menubase($db);
        $menu->menu_handler=preg_replace('/_menu$/','',$_POST['menu_handler']);
        $menu->type=$_POST['type'];
        $menu->titre=$_POST['titre'];
        $menu->url=$_POST['url'];
        $menu->langs=$_POST['langs'];
        $menu->position=$_POST['position'];
        $menu->enabled=$_POST['enabled'];
        $menu->perms=$_POST['perms'];
        $menu->target=$_POST['target'];
        $menu->user=$_POST['user'];
        if (is_numeric($_POST['menuId']))
        {
        	$menu->fk_menu=$_POST['menuId'];
        }
        else
       {
	       	if ($_POST['type'] == 'top') $menu->fk_menu=0;
	       	else $menu->fk_menu=-1;
        	$menu->fk_mainmenu=$mainmenu;
        	$menu->fk_leftmenu=$leftmenu;
       }

        $result=$menu->create($user);
        if ($result > 0)
        {
            header("Location: ".DOL_URL_ROOT."/admin/menus/index.php?menu_handler=".$_POST['menu_handler']);
            exit;
        }
        else
        {
            $action = 'create';
	        setEventMessages($menu->error, $menu->errors, 'errors');
        }
    }
}

// delete
if ($action == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
    $this->db->begin();

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."menu WHERE rowid = ".GETPOST('menuId', 'int');
    $result=$db->query($sql);

    if ($result == 0)
    {
        $this->db->commit();

        llxHeader();
	    setEventMessages($langs->trans("MenuDeleted"), null, 'mesgs');
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
				jQuery("#menuId").prop("disabled", true);
	    		jQuery("#menuId").val(\'\');
			}
    		else
    		{
				jQuery("#menuId").removeAttr("disabled");
    		}
    	}
    	init_topleft();
    	jQuery("#topleft").click(function() {
    		init_topleft();
    	});
    });
    </script>';

    print load_fiche_titre($langs->trans("NewMenu"),'','title_setup');
    
    print '<form action="./edit.php?action=add&menuId='.$_GET['menuId'].'" method="post" name="formmenucreate">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    dol_fiche_head();
    
    print '<table class="border" width="100%">';

    // Id
    $parent_rowid = $_GET['menuId'];
    if ($_GET['menuId'])
    {
        $sql = "SELECT m.rowid, m.mainmenu, m.leftmenu, m.level, m.langs FROM ".MAIN_DB_PREFIX."menu as m WHERE m.rowid = ".GETPOST('menuId', 'int');
        $res  = $db->query($sql);
        if ($res)
        {

            while ($menu = $db->fetch_array($res))
            {
                $parent_rowid = $menu['rowid'];
                $parent_mainmenu = $menu['mainmenu'];
                $parent_leftmenu = $menu['leftmenu'];
                $parent_langs = $menu['langs'];
                $parent_level = $menu['level'];
            }
        }
    }

    // Handler
    print '<tr><td class="fieldrequired">'.$langs->trans('MenuHandler').'</td>';
    print '<td>';
    $formadmin->select_menu_families($menu_handler.(preg_match('/_menu/',$menu_handler)?'':'_menu'),'menu_handler',array_merge($dirstandard,$dirsmartphone));
    print '</td>';
    print '<td>'.$langs->trans('DetailMenuHandler').'</td></tr>';

    //User
    print '<tr><td class="nowrap fieldrequired">'.$langs->trans('MenuForUsers').'</td>';
    print '<td><select class="flat" name="user">';
    print '<option value="2" selected>'.$langs->trans("AllMenus").'</option>';
    print '<option value="0">'.$langs->trans('Internal').'</option>';
    print '<option value="1">'.$langs->trans('External').'</option>';
    print '</select></td>';
    print '<td>'.$langs->trans('DetailUser').'</td></tr>';

    // Type
    print '<tr><td class="fieldrequired">'.$langs->trans('Type').'</td><td>';
    if ($parent_rowid)
    {
        print $langs->trans('Left');
        print '<input type="hidden" name="type" value="left">';
    }
    else
    {
        print '<select name="type" class="flat" id="topleft">';
        print '<option value="">&nbsp;</option>';
        print '<option value="top"'.($_POST["type"] && $_POST["type"]=='top'?' selected':'').'>'.$langs->trans('Top').'</option>';
        print '<option value="left"'.($_POST["type"] && $_POST["type"]=='left'?' selected':'').'>'.$langs->trans('Left').'</option>';
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
        print '<td><input type="text" size="48" id="menuId" name="menuId" value="'.($_POST["menuId"]?$_POST["menuId"]:'').'"></td>';
    }
    print '<td>'.$langs->trans('DetailMenuIdParent');
    print ', '.$langs->trans("Example").': fk_mainmenu=abc&fk_leftmenu=def';
    print '</td></tr>';

    // Title
    print '<tr><td class="fieldrequired">'.$langs->trans('Title').'</td><td><input type="text" size="30" name="titre" value="'.$_POST["titre"].'"></td><td>'.$langs->trans('DetailTitre').'</td></tr>';

    // URL
    print '<tr><td class="fieldrequired">'.$langs->trans('URL').'</td><td><input type="text" size="60" name="url" value="'.$_POST["url"].'"></td><td>'.$langs->trans('DetailUrl').'</td></tr>';

    // Langs
    print '<tr><td>'.$langs->trans('LangFile').'</td><td><input type="text" size="30" name="langs" value="'.$parent_langs.'"></td><td>'.$langs->trans('DetailLangs').'</td></tr>';

    // Position
    print '<tr><td>'.$langs->trans('Position').'</td><td><input type="text" size="5" name="position" value="'.(isset($_POST["position"])?$_POST["position"]:100).'"></td><td>'.$langs->trans('DetailPosition').'</td></tr>';

    // Target
    print '<tr><td>'.$langs->trans('Target').'</td><td><select class="flat" name="target">';
    print '<option value=""'.($menu->target==""?' selected':'').'>'.$langs->trans('').'</option>';
    print '<option value="_blank"'.($menu->target=="_blank"?' selected':'').'>'.$langs->trans('_blank').'</option>';
    print '</select></td></td><td>'.$langs->trans('DetailTarget').'</td></tr>';

    // Enabled
    print '<tr><td>'.$langs->trans('Enabled').'</td><td><input type="text" size="60" name="enabled" value="'.$_POST["enabled"].'"></td><td>'.$langs->trans('DetailEnabled').'</td></tr>';

    // Perms
    print '<tr><td>'.$langs->trans('Rights').'</td><td><input type="text" size="60" name="perms" value="'.$_POST["perms"].'"></td><td>'.$langs->trans('DetailRight').'</td></tr>';

    print '</table>';

    dol_fiche_end();
    
    // Boutons
    print '<div class="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

    print '</form>';
}
elseif ($action == 'edit')
{
    print load_fiche_titre($langs->trans("ModifMenu"),'','title_setup');
    print '<br>';

    print '<form action="./edit.php?action=update" method="POST" name="formmenuedit">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="handler_origine" value="'.$menu_handler.'">';
    print '<input type="hidden" name="menuId" value="'.$_GET['menuId'].'">';

    dol_fiche_head();
    
    print '<table class="border" width="100%">';

    $menu = new Menubase($db);
    $result=$menu->fetch($_GET['menuId']);
    //var_dump($menu);

    // Id
    print '<tr><td>'.$langs->trans('Id').'</td><td>'.$menu->id.'</td><td>'.$langs->trans('DetailId').'</td></tr>';

    // Module
    print '<tr><td>'.$langs->trans('MenuModule').'</td><td>'.$menu->module.'</td><td>'.$langs->trans('DetailMenuModule').'</td></tr>';

    // Handler
    if ($menu->menu_handler == 'all') $handler = $langs->trans('AllMenus');
    else $handler = $menu->menu_handler;
    print '<tr><td class="fieldrequired">'.$langs->trans('MenuHandler').'</td><td>'.$handler.'</td><td>'.$langs->trans('DetailMenuHandler').'</td></tr>';

    // User
    print '<tr><td class="nowrap fieldrequired">'.$langs->trans('MenuForUsers').'</td><td><select class="flat" name="user">';
    print '<option value="2"'.($menu->user==2?' selected':'').'>'.$langs->trans("AllMenus").'</option>';
    print '<option value="0"'.($menu->user==0?' selected':'').'>'.$langs->trans('Internal').'</option>';
    print '<option value="1"'.($menu->user==1?' selected':'').'>'.$langs->trans('External').'</option>';
    print '</select></td><td>'.$langs->trans('DetailUser').'</td></tr>';

    // Type
    print '<tr><td class="fieldrequired">'.$langs->trans('Type').'</td><td>'.$langs->trans(ucfirst($menu->type)).'</td><td>'.$langs->trans('DetailType').'</td></tr>';

    // MenuId Parent
    print '<tr><td class="fieldrequired">'.$langs->trans('MenuIdParent');
    print '</td>';
    $valtouse=$menu->fk_menu;
    if ($menu->fk_mainmenu) $valtouse='fk_mainmenu='.$menu->fk_mainmenu;
    if ($menu->fk_leftmenu) $valtouse.='&fk_leftmenu='.$menu->fk_leftmenu;
    print '<td><input type="text" name="menuIdParent" value="'.$valtouse.'" size="48"></td>';
    print '<td>'.$langs->trans('DetailMenuIdParent');
    print ', '.$langs->trans("Example").': fk_mainmenu=abc&fk_leftmenu=def';
    print '</td></tr>';

    // Niveau
    //print '<tr><td>'.$langs->trans('Level').'</td><td>'.$menu->level.'</td><td>'.$langs->trans('DetailLevel').'</td></tr>';

    // Title
    print '<tr><td class="fieldrequired">'.$langs->trans('Title').'</td><td><input type="text" size="30" name="titre" value="'.$menu->titre.'"></td><td>'.$langs->trans('DetailTitre').'</td></tr>';

    // Url
    print '<tr><td class="fieldrequired">'.$langs->trans('URL').'</td><td><input type="text" class="quatrevingtpercent" name="url" value="'.$menu->url.'"></td><td>'.$langs->trans('DetailUrl').'</td></tr>';

    // Langs
    print '<tr><td>'.$langs->trans('LangFile').'</td><td><input type="text" size="30" name="langs" value="'.$menu->langs.'"></td><td>'.$langs->trans('DetailLangs').'</td></tr>';

    // Position
    print '<tr><td>'.$langs->trans('Position').'</td><td><input type="text" size="5" name="position" value="'.$menu->position.'"></td><td>'.$langs->trans('DetailPosition').'</td></tr>';

    // Target
    print '<tr><td>'.$langs->trans('Target').'</td><td><select class="flat" name="target">';
    print '<option value=""'.($menu->target==""?' selected':'').'>'.$langs->trans('').'</option>';
    print '<option value="_blank"'.($menu->target=="_blank"?' selected':'').'>'.$langs->trans('_blank').'</option>';
    print '</select></td><td>'.$langs->trans('DetailTarget').'</td></tr>';

    // Enabled
    print '<tr><td>'.$langs->trans('Enabled').'</td><td><input type="text" size="60" name="enabled" value="'.dol_escape_htmltag($menu->enabled).'"></td><td>'.$langs->trans('DetailEnabled');
    if (! empty($menu->enabled)) print ' ('.$langs->trans("ConditionIsCurrently").': '.yn(dol_eval($menu->enabled,1)).')';
    print '</td></tr>';

    // Perms
    print '<tr><td>'.$langs->trans('Rights').'</td><td><input type="text" size="60" name="perms" value="'.dol_escape_htmltag($menu->perms).'"></td><td>'.$langs->trans('DetailRight');
    if (! empty($menu->perms)) print ' ('.$langs->trans("ConditionIsCurrently").': '.yn(dol_eval($menu->perms,1)).')';
    print '</td></tr>';

    print '</table>';

    dol_fiche_end();
    
    // Bouton
    print '<div class="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

    print '</form>';

    print '<br>';
}

llxFooter();
$db->close();
