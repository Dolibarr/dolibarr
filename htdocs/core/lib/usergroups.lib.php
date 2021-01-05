<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015	    Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet       <fmarcet@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */


/**
 *	    \file       htdocs/core/lib/usergroups.lib.php
 *		\brief      Set of function to manage users, groups and permissions
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function user_prepare_head($object)
{
	global $langs, $conf, $user, $db;

	$langs->load("users");

	$canreadperms = true;
	if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
		$canreadperms = ($user->admin || ($user->id != $object->id && $user->rights->user->user_advance->readperms) || ($user->id == $object->id && $user->rights->user->self_advance->readperms));
	}

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/user/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("User");
	$head[$h][2] = 'user';
	$h++;

	if ((!empty($conf->ldap->enabled) && !empty($conf->global->LDAP_SYNCHRO_ACTIVE))
		&& (empty($conf->global->MAIN_DISABLE_LDAP_TAB) || !empty($user->admin))) {
		$langs->load("ldap");
		$head[$h][0] = DOL_URL_ROOT.'/user/ldap.php?id='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	if ($canreadperms) {
		$head[$h][0] = DOL_URL_ROOT.'/user/perms.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Rights").(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '<span class="badge marginleftonlyshort">'.($object->nb_rights).'</span>' : '');
		$head[$h][2] = 'rights';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/user/param_ihm.php?id='.$object->id;
	$head[$h][1] = $langs->trans("UserGUISetup");
	$head[$h][2] = 'guisetup';
	$h++;

	if (!empty($conf->agenda->enabled)) {
		if (empty($conf->global->AGENDA_EXT_NB)) $conf->global->AGENDA_EXT_NB = 5;
		$MAXAGENDA = $conf->global->AGENDA_EXT_NB;

		$i = 1;
		$nbagenda = 0;
		while ($i <= $MAXAGENDA) {
			$key = $i;
			$name = 'AGENDA_EXT_NAME_'.$object->id.'_'.$key;
			$src = 'AGENDA_EXT_SRC_'.$object->id.'_'.$key;
			$offsettz = 'AGENDA_EXT_OFFSETTZ_'.$object->id.'_'.$key;
			$color = 'AGENDA_EXT_COLOR_'.$object->id.'_'.$key;
			$i++;

			if (!empty($object->conf->$name)) $nbagenda++;
		}

		$head[$h][0] = DOL_URL_ROOT.'/user/agenda_extsites.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ExtSites").($nbagenda ? '<span class="badge marginleftonlyshort">'.$nbagenda.'</span>' : '');
		$head[$h][2] = 'extsites';
		$h++;
	}

	if (!empty($conf->clicktodial->enabled)) {
		$head[$h][0] = DOL_URL_ROOT.'/user/clicktodial.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ClickToDial");
		$head[$h][2] = 'clicktodial';
		$h++;
	}

	// Notifications
	if ($user->socid == 0 && !empty($conf->notification->enabled)) {
		$nbNote = 0;
		$sql = "SELECT COUNT(n.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."notify_def as n";
		$sql .= " WHERE fk_user = ".$object->id;
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$nbNote = $obj->nb;
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		$langs->load("mails");
		$head[$h][0] = DOL_URL_ROOT.'/user/notify/card.php?id='.$object->id;
		$head[$h][1] = $langs->trans("NotificationsAuto");
		if ($nbNote > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		$head[$h][2] = 'notify';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'user');

	if ((!empty($conf->salaries->enabled) && !empty($user->rights->salaries->read))
		|| (!empty($conf->hrm->enabled) && !empty($user->rights->hrm->employee->read))
		|| (!empty($conf->expensereport->enabled) && !empty($user->rights->expensereport->lire) && ($user->id == $object->id || $user->rights->expensereport->readall))
		|| (!empty($conf->holiday->enabled) && !empty($user->rights->holiday->read) && ($user->id == $object->id || $user->rights->holiday->readall))
		) {
		// Bank
		$head[$h][0] = DOL_URL_ROOT.'/user/bank.php?id='.$object->id;
		$head[$h][1] = $langs->trans("HRAndBank");
		$head[$h][2] = 'bank';
		$h++;
	}

	// Such info on users is visible only by internal user
	if (empty($user->socid)) {
		// Notes
		$nbNote = 0;
		if (!empty($object->note)) $nbNote++;
		$head[$h][0] = DOL_URL_ROOT.'/user/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Note");
		if ($nbNote > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		$head[$h][2] = 'note';
		$h++;

		// Attached files
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->user->dir_output."/".$object->id;
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $object->element, $object->id);
		$head[$h][0] = DOL_URL_ROOT.'/user/document.php?userid='.$object->id;
		$head[$h][1] = $langs->trans("Documents");
		if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		$head[$h][2] = 'document';
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/user/info.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Info");
		$head[$h][2] = 'info';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'user', 'remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param 	UserGroup $object		Object group
 * @return	array				    Array of tabs
 */
function group_prepare_head($object)
{
	global $langs, $conf, $user;

	$canreadperms = true;
	if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS)) {
		$canreadperms = ($user->admin || $user->rights->user->group_advance->readperms);
	}

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/user/group/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("GroupCard");
	$head[$h][2] = 'group';
	$h++;

	if ((!empty($conf->ldap->enabled) && !empty($conf->global->LDAP_SYNCHRO_ACTIVE))
		&& (empty($conf->global->MAIN_DISABLE_LDAP_TAB) || !empty($user->admin))) {
		$langs->load("ldap");
		$head[$h][0] = DOL_URL_ROOT.'/user/group/ldap.php?id='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	if ($canreadperms) {
		$head[$h][0] = DOL_URL_ROOT.'/user/group/perms.php?id='.$object->id;
		$head[$h][1] = $langs->trans("GroupRights").'<span class="badge marginleftonlyshort">'.($object->nb_rights).'</span>';
		$head[$h][2] = 'rights';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'group');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'group', 'remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function user_admin_prepare_head()
{
	global $langs, $conf, $user;

	$langs->load("users");
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/user.php';
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/usergroup.php';
	$head[$h][1] = $langs->trans("Group");
	$head[$h][2] = 'usergroupcard';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/user/admin/user_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields")." (".$langs->trans("Users").")";
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/user/admin/group_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields")." (".$langs->trans("Groups").")";
	$head[$h][2] = 'attributes_group';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'useradmin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'useradmin', 'remove');

	return $head;
}

/**
 * 	Show list of themes. Show all thumbs of themes
 *
 * 	@param	User|null	$fuser				User concerned or null for global theme
 * 	@param	int			$edit				1 to add edit form
 * 	@param	boolean		$foruserprofile		Show for user profile view
 * 	@return	void
 */
function showSkins($fuser, $edit = 0, $foruserprofile = false)
{
	global $conf, $langs, $db, $form;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

	$formother = new FormOther($db);

	$dirthemes = array('/theme');
	if (!empty($conf->modules_parts['theme'])) {		// Using this feature slow down application
		foreach ($conf->modules_parts['theme'] as $reldir) {
			$dirthemes = array_merge($dirthemes, (array) ($reldir.'theme'));
		}
	}
	$dirthemes = array_unique($dirthemes);
	// Now dir_themes=array('/themes') or dir_themes=array('/theme','/mymodule/theme')

	$selected_theme = '';
	if (empty($foruserprofile)) $selected_theme = $conf->global->MAIN_THEME;
	else $selected_theme = ((is_object($fuser) && !empty($fuser->conf->MAIN_THEME)) ? $fuser->conf->MAIN_THEME : '');

	$hoverdisabled = '';
	if (empty($foruserprofile)) $hoverdisabled = (isset($conf->global->THEME_ELDY_USE_HOVER) && $conf->global->THEME_ELDY_USE_HOVER == '0');
	else $hoverdisabled = (is_object($fuser) ? (empty($fuser->conf->THEME_ELDY_USE_HOVER) || $fuser->conf->THEME_ELDY_USE_HOVER == '0') : '');

	$checkeddisabled = '';
	if (empty($foruserprofile)) $checkeddisabled = (isset($conf->global->THEME_ELDY_USE_CHECKED) && $conf->global->THEME_ELDY_USE_CHECKED == '0');
	else $checkeddisabled = (is_object($fuser) ? (empty($fuser->conf->THEME_ELDY_USE_CHECKED) || $fuser->conf->THEME_ELDY_USE_CHECKED == '0') : '');

	$colspan = 2;
	if ($foruserprofile) $colspan = 4;

	$thumbsbyrow = 6;
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent'.($edit ? ' editmode' : '').' tableforfield">';

	// Title
	if ($foruserprofile) {
		print '<tr class="liste_titre"><th class="titlefield">'.$langs->trans("Parameter").'</th><th>'.$langs->trans("DefaultValue").'</th>';
		print '<th colspan="2">&nbsp;</th>';
		print '</tr>';

		print '<tr>';
		print '<td>'.$langs->trans("DefaultSkin").'</td>';
		print '<td>'.$conf->global->MAIN_THEME.'</td>';
		print '<td class="nowrap left" width="20%"><input id="check_MAIN_THEME" name="check_MAIN_THEME"'.($edit ? '' : ' disabled').' type="checkbox" '.($selected_theme ? " checked" : "").'> '.$langs->trans("UsePersonalValue").'</td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
	} else {
		$dirthemestring = '';
		foreach ($dirthemes as $dirtheme) {
			$dirthemestring .= '"'.$dirtheme.'" ';
		}

		print '<tr class="liste_titre"><th class="titlefield">';
		print $form->textwithpicto($langs->trans("DefaultSkin"), $langs->trans("ThemeDir").' : '.$dirthemestring);
		print '</th>';
		print '<th class="right">';
		$url = 'https://www.dolistore.com/9-skins';
		print '<a href="'.$url.'" target="_blank">';
		print $langs->trans('DownloadMoreSkins');
		print '</a>';
		print '</th></tr>';
	}

	print '<tr><td colspan="'.$colspan.'">';

	print '<table class="nobordernopadding" width="100%"><tr><td><div class="center">';

	$i = 0;
	foreach ($dirthemes as $dir) {
		//print $dirroot.$dir;exit;
		$dirtheme = dol_buildpath($dir, 0); // This include loop on $conf->file->dol_document_root
		$urltheme = dol_buildpath($dir, 1);

		if (is_dir($dirtheme)) {
			$handle = opendir($dirtheme);
			if (is_resource($handle)) {
				while (($subdir = readdir($handle)) !== false) {
					if (is_dir($dirtheme."/".$subdir) && substr($subdir, 0, 1) <> '.'
							&& substr($subdir, 0, 3) <> 'CVS' && !preg_match('/common|phones/i', $subdir)) {
						// Disable not stable themes (dir ends with _exp or _dev)
						if ($conf->global->MAIN_FEATURES_LEVEL < 2 && preg_match('/_dev$/i', $subdir)) continue;
						if ($conf->global->MAIN_FEATURES_LEVEL < 1 && preg_match('/_exp$/i', $subdir)) continue;

						print '<div class="inline-block" style="margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;">';
						$file = $dirtheme."/".$subdir."/thumb.png";
						$url = $urltheme."/".$subdir."/thumb.png";
						if (!file_exists($file)) $url = DOL_URL_ROOT.'/public/theme/common/nophoto.png';
						print '<a href="'.$_SERVER["PHP_SELF"].($edit ? '?action=edit&theme=' : '?theme=').$subdir.(GETPOST('optioncss', 'alpha', 1) ? '&optioncss='.GETPOST('optioncss', 'alpha', 1) : '').($fuser ? '&id='.$fuser->id : '').'" style="font-weight: normal;" alt="'.$langs->trans("Preview").'">';
						if ($subdir == $conf->global->MAIN_THEME) $title = $langs->trans("ThemeCurrentlyActive");
						else $title = $langs->trans("ShowPreview");
						print '<img class="img-skinthumb shadow" src="'.$url.'" alt="'.$title.'" title="'.$title.'" style="border: none; margin-bottom: 5px;">';
						print '</a><br>';
						if ($subdir == $selected_theme) {
							print '<input '.($edit ? '' : 'disabled').' type="radio" class="themethumbs" style="border: 0px;" checked name="main_theme" value="'.$subdir.'"> <b>'.$subdir.'</b>';
						} else {
							print '<input '.($edit ? '' : 'disabled').' type="radio" class="themethumbs" style="border: 0px;" name="main_theme" value="'.$subdir.'"> '.$subdir;
						}
						print '</div>';

						$i++;
					}
				}
			}
		}
	}

	print '</div></td></tr></table>';

	print '</td></tr>';

	// Set variables of theme
	$colorbackhmenu1 = '';
	$colorbackvmenu1 = '';
	$colortexttitlenotab = '';
	$colortexttitlelink = '';
	$colorbacktitle1 = '';
	$colortexttitle = '';
	$colorbacklineimpair1 = '';
	$colorbacklinepair1 = '';
	$colortextlink = '';
	$colorbacklinepairhover = '';
	$colorbacklinepairhover = '';
	$colorbacklinepairchecked = '';
	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php')) {
		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
	}

	// Show logo
	if ($foruserprofile) {
		// Nothing
	} else {
		// Show logo
		print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("EnableShowLogo").'</td><td>';
		if ($edit) {
			print ajax_constantonoff('MAIN_SHOW_LOGO', array(), null, 0, 0, 1);
			//print $form->selectyesno('MAIN_SHOW_LOGO', $conf->global->MAIN_SHOW_LOGO, 1);
		} else {
			print  yn($conf->global->MAIN_SHOW_LOGO);
		}
		print '</td>';
		print '</tr>';
		/*
		print '<tr class="oddeven"><td>'.$langs->trans("EnableShowLogo").'</td><td>' . yn($conf->global->MAIN_SHOW_LOGO) . '</td>';
		print "</tr>";*/
	}


	// TopMenuDisableImages
	if ($foruserprofile) {
		/*
         print '<tr class="oddeven">';
         print '<td>'.$langs->trans("TopMenuDisableImages").'</td>';
         print '<td>'.($conf->global->THEME_TOPMENU_DISABLE_IMAGE?$conf->global->THEME_TOPMENU_DISABLE_IMAGE:$langs->trans("Default")).'</td>';
         print '<td class="left" class="nowrap" width="20%"><input name="check_THEME_TOPMENU_DISABLE_IMAGE" id="check_THEME_TOPMENU_DISABLE_IMAGE" type="checkbox" '.(! empty($object->conf->THEME_ELDY_TEXTLINK)?" checked":"");
         print (empty($dolibarr_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
         print '> '.$langs->trans("UsePersonalValue").'</td>';
         print '<td>';
         if ($edit)
         {
         print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_TOPMENU_DISABLE_IMAGE,array()),''),'THEME_TOPMENU_DISABLE_IMAGE','',1).' ';
         }
         else
         {
         $color = colorArrayToHex(colorStringToArray($conf->global->THEME_TOPMENU_DISABLE_IMAGE,array()),'');
         if ($color) print '<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
         else print '';
         }
         if ($edit) print '<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
         print '</td>';*/
	} else {
		$default = $langs->trans('No');
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("TopMenuDisableImages").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print ajax_constantonoff('THEME_TOPMENU_DISABLE_IMAGE', array(), null, 0, 0, 1);
			//print $form->selectyesno('THEME_TOPMENU_DISABLE_IMAGE', $conf->global->THEME_TOPMENU_DISABLE_IMAGE, 1);
		} else {
			print yn($conf->global->THEME_TOPMENU_DISABLE_IMAGE);
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
		print '</tr>';
	}

	// Background color THEME_ELDY_BACKBODY
	if ($foruserprofile) {
		/*
	    print '<tr class="oddeven">';
	    print '<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
        print '<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_TOPMENU_BACK1:$langs->trans("Default")).'</td>';
        print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TOPMENU_BACK1" id="check_THEME_ELDY_TOPMENU_BACK1" type="checkbox" '.(! empty($object->conf->THEME_ELDY_TOPMENU_BACK1)?" checked":"");
        print (empty($dolibarr_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
        print '> '.$langs->trans("UsePersonalValue").'</td>';
        print '<td>';
	    if ($edit)
	    {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1,array()),''),'THEME_ELDY_TOPMENU_BACK1','',1).' ';
	    }
	   	else
	   	{
	   		$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1,array()),'');
			if ($color) print '<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			else print '';
	   	}
    	if ($edit) print '<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
	    print '</td>';*/
	} else {
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("BackgroundColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		//var_dump($conf->global->THEME_ELDY_BACKBODY);
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BACKBODY, array()), ''), 'THEME_ELDY_BACKBODY', '', 1, '', '', 'colorbackbody').' ';
		} else {
	   		$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BACKBODY, array()), '');
			if ($color) print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			else print $langs->trans("Default");
	   	}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>ffffff</strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
		print '</tr>';
	}

	// TopMenuBackgroundColor
	if ($foruserprofile) {
		/*
	    print '<tr class="oddeven">';
	    print '<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
        print '<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_TOPMENU_BACK1:$langs->trans("Default")).'</td>';
        print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TOPMENU_BACK1" id="check_THEME_ELDY_TOPMENU_BACK1" type="checkbox" '.(! empty($object->conf->THEME_ELDY_TOPMENU_BACK1)?" checked":"");
        print (empty($dolibarr_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
        print '> '.$langs->trans("UsePersonalValue").'</td>';
        print '<td>';
	    if ($edit)
	    {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1,array()),''),'THEME_ELDY_TOPMENU_BACK1','',1).' ';
	    }
	   	else
	   	{
	   		$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1,array()),'');
			if ($color) print '<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			else print '';
	   	}
    	if ($edit) print '<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
	    print '</td>';*/
	} else {
		$default = (empty($colorbackhmenu1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbackhmenu1)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1, array()), ''), 'THEME_ELDY_TOPMENU_BACK1', '', 1, '', '', 'colorbackhmenu1').' ';
		} else {
	   		$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1, array()), '');
			if ($color) print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			else print $langs->trans("Default");
	   	}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
		print '</tr>';
	}

	// LeftMenuBackgroundColor
	if ($foruserprofile) {
		/*
		 print '<tr class="oddeven">';
		 print '<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		 print '<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_VERMENU_BACK1:$langs->trans("Default")).'</td>';
		 print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_VERMENU_BACK1" id="check_THEME_ELDY_VERMENU_BACK1" type="checkbox" '.(! empty($object->conf->THEME_ELDY_TOPMENU_BACK1)?" checked":"");
		 print (empty($dolibarr_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
		 print '> '.$langs->trans("UsePersonalValue").'</td>';
		 print '<td>';
		 if ($edit)
		 {
		 print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_VERMENU_BACK1,array()),''),'THEME_ELDY_VERMENU_BACK1','',1).' ';
		 }
		 else
		 {
		 $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_VERMENU_BACK1,array()),'');
		 if ($color) print '<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		 else print '';
		 }
		 if ($edit) print '<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		 print '</td>';*/
	} else {
		$default = (empty($colorbackvmenu1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbackvmenu1)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("LeftMenuBackgroundColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_VERMENU_BACK1, array()), ''), 'THEME_ELDY_VERMENU_BACK1', '', 1, '', '', 'colorbackvmenu1').' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_VERMENU_BACK1, array()), '');
			if ($color) print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			else print $langs->trans("Default");
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
		print '</tr>';
	}

	// TextTitleColor for title of Pages
	if ($foruserprofile) {
	} else {
		$default = (empty($colortexttitlenotab) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colortexttitlenotab)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("TextTitleColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTTITLENOTAB, array()), ''), 'THEME_ELDY_TEXTTITLENOTAB', '', 1, '', '', 'colortexttitlenotab').' ';
		} else {
			print $formother->showColor($conf->global->THEME_ELDY_TEXTTITLENOTAB, $langs->trans("Default"));
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #'.$default.'">'.$default.'</span></strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';

		print '</tr>';
	}

	// BackgroundTableTitleColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colorbacktitle1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacktitle1)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("BackgroundTableTitleColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BACKTITLE1, array()), ''), 'THEME_ELDY_BACKTITLE1', '', 1, '', '', 'colorbacktitle1').' ';
		} else {
	   		print $formother->showColor($conf->global->THEME_ELDY_BACKTITLE1, $langs->trans("Default"));
	   	}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> '; // $colorbacktitle1 in CSS
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';

		print '</tr>';
	}

	// TextTitleColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colortexttitle) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colortexttitle)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("BackgroundTableTitleTextColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTTITLE, array()), ''), 'THEME_ELDY_TEXTTITLE', '', 1, '', '', 'colortexttitle').' ';
		} else {
			print $formother->showColor($conf->global->THEME_ELDY_TEXTTITLE, $langs->trans("Default"));
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #'.$default.'">'.$default.'</span></strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';

		print '</tr>';
	}

	// TextTitleLinkColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colortexttitlelink) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colortexttitlelink)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("BackgroundTableTitleTextlinkColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTTITLELINK, array()), ''), 'THEME_ELDY_TEXTTITLELINK', '', 1, '', '', 'colortexttitlelink').' ';
		} else {
			print $formother->showColor($conf->global->THEME_ELDY_TEXTTITLELINK, $langs->trans("Default"));
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #'.$default.'">'.$default.'</span></strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</span>';
		print '</td>';

		print '</tr>';
	}

	// BackgroundTableLineOddColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colorbacklineimpair1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacklineimpair1)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("BackgroundTableLineOddColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_LINEIMPAIR1, array()), ''), 'THEME_ELDY_LINEIMPAIR1', '', 1, '', '', 'colorbacklinepair2').' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_LINEIMPAIR1, array()), '');
			if ($color) print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			else print $langs->trans("Default");
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
		print '</tr>';
	}

	// BackgroundTableLineEvenColor
	if ($foruserprofile) {
	} else {
		$default = (empty($colorbacklinepair1) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacklinepair1)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("BackgroundTableLineEvenColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_LINEPAIR1, array()), ''), 'THEME_ELDY_LINEPAIR1', '', 1, '', '', 'colorbacklineimpair2').' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_LINEPAIR1, array()), '');
			if ($color) print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			else print $langs->trans("Default");
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
		print '</tr>';
	}

	// Text LinkColor
	if ($foruserprofile) {
		/*
	     print '<tr class="oddeven">';
	     print '<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
	     print '<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_TEXTLINK:$langs->trans("Default")).'</td>';
	     print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TEXTLINK" id="check_THEME_ELDY_TEXTLINK" type="checkbox" '.(! empty($object->conf->THEME_ELDY_TEXTLINK)?" checked":"");
	     print (empty($dolibarr_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
	     print '> '.$langs->trans("UsePersonalValue").'</td>';
	     print '<td>';
	     if ($edit)
	     {
	     print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTLINK,array()),''),'THEME_ELDY_TEXTLINK','',1).' ';
	     }
	     else
	     {
	     $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTLINK,array()),'');
	     if ($color) print '<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
	     else print '';
	     }
	    	if ($edit) print '<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
	    	print '</td>';*/
	} else {
		$default = (empty($colortextlink) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colortextlink)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("LinkColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTLINK, array()), ''), 'THEME_ELDY_TEXTLINK', '', 1, '', '', 'colortextlink').' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTLINK, array()), '');
			if ($color) print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			else {
				//print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$defaultcolor.'" value="'.$langs->trans("Default").'">';
				//print '<span style="color: #000078">'.$langs->trans("Default").'</span>';
				print $langs->trans("Default");
			}
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #'.$default.'">'.$default.'</span></strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
		print '</tr>';
	}

	// Use Hover
	if ($foruserprofile) {
		/* Must first change option to choose color of highlight instead of yes or no.
	     print '<tr class="oddeven">';
	     print '<td>'.$langs->trans("HighlightLinesOnMouseHover").'</td>';
	     print '<td><input name="check_THEME_ELDY_USE_HOVER" disabled="disabled" type="checkbox" '.($conf->global->THEME_ELDY_USE_HOVER?" checked":"").'></td>';
	     print '<td class="nowrap left" width="20%"><input name="check_MAIN_THEME"'.($edit?'':' disabled').' type="checkbox" '.($selected_theme?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
	     print '<td><input name="check_THEME_ELDY_USE_HOVER"'.($edit?'':' disabled="disabled"').' type="checkbox" '.($hoverdisabled?"":" checked").'>';
	     print ' &nbsp; ('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
	     print '</td>';
	     print '</tr>';
	     */
	} else {
		$default = (empty($colorbacklinepairhover) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacklinepairhover)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("HighlightLinesColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		//print '<input name="check_THEME_ELDY_USE_HOVER"'.($edit?'':' disabled').' type="checkbox" '.($hoverdisabled?"":" checked").'>';
		//print ' &nbsp; ('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		if ($edit) {
			if ($conf->global->THEME_ELDY_USE_HOVER == '1') $color = colorArrayToHex(colorStringToArray($colorbacklinepairhover));
			else $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_USE_HOVER, array()), '');
			print $formother->selectColor($color, 'THEME_ELDY_USE_HOVER', '', 1, '', '', 'colorbacklinepairhover').' ';
		} else {
			if ($conf->global->THEME_ELDY_USE_HOVER == '1') $color = colorArrayToHex(colorStringToArray($colorbacklinepairhover));
			else $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_USE_HOVER, array()), '');
			if ($color) {
				if ($color != colorArrayToHex(colorStringToArray($colorbacklinepairhover))) print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
				else print $langs->trans("Default");
			} else print $langs->trans("Default");
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
	}

	// Use Checked
	if ($foruserprofile) {
		/* Must first change option to choose color of highlight instead of yes or no.
	     print '<tr class="oddeven">';
	     print '<td>'.$langs->trans("HighlightLinesOnMouseHover").'</td>';
	     print '<td><input name="check_THEME_ELDY_USE_HOVER" disabled="disabled" type="checkbox" '.($conf->global->THEME_ELDY_USE_HOVER?" checked":"").'></td>';
	     print '<td class="nowrap left" width="20%"><input name="check_MAIN_THEME"'.($edit?'':' disabled').' type="checkbox" '.($selected_theme?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
	     print '<td><input name="check_THEME_ELDY_USE_HOVER"'.($edit?'':' disabled="disabled"').' type="checkbox" '.($hoverdisabled?"":" checked").'>';
	     print ' &nbsp; ('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
	     print '</td>';
	     print '</tr>';
	     */
	} else {
		$default = (empty($colorbacklinepairchecked) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($colorbacklinepairchecked)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("HighlightLinesChecked").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		//print '<input name="check_THEME_ELDY_USE_HOVER"'.($edit?'':' disabled').' type="checkbox" '.($hoverdisabled?"":" checked").'>';
		//print ' &nbsp; ('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
		if ($edit) {
			if ($conf->global->THEME_ELDY_USE_CHECKED == '1') $color = 'e6edf0';
			else $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_USE_CHECKED, array()), '');
			print $formother->selectColor($color, 'THEME_ELDY_USE_CHECKED', '', 1, '', '', 'colorbacklinepairchecked').' ';
		} else {
			if ($conf->global->THEME_ELDY_USE_CHECKED == '1') $color = 'e6edf0';
			else $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_USE_CHECKED, array()), '');
			if ($color) {
				if ($color != 'e6edf0') print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
				else print $langs->trans("Default");
			} else print $langs->trans("Default");
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
		print '</tr>';
	}

	// Use MAIN_OPTIMIZEFORTEXTBROWSER
	if ($foruserprofile) {
		//$default=yn($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER);
		$default = $langs->trans('No');
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("MAIN_OPTIMIZEFORTEXTBROWSER").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		//print ajax_constantonoff("MAIN_OPTIMIZEFORTEXTBROWSER", array(), null, 0, 0, 1, 0);
   		if ($edit) {
   			print $form->selectyesno('MAIN_OPTIMIZEFORTEXTBROWSER', $fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER, 1);
   		} else {
   			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
   				print yn($fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER);
   			} else {
   				print yn(1);
   				if (empty($fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER)) print ' ('.$langs->trans("ForcedByGlobalSetup").')';
   			}
   		}
   		print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("MAIN_OPTIMIZEFORTEXTBROWSERDesc"));
		print '</td>';
		print '</tr>';
	} else {
		/*var_dump($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER);
	    $default=$langs->trans('No');
	    print '<tr class="oddeven">';
	    print '<td>'.$langs->trans("MAIN_OPTIMIZEFORTEXTBROWSER").'</td>';
	    print '<td colspan="'.($colspan-1).'">';
	    if ($edit)
	    {
	        print $form->selectyesno('MAIN_OPTIMIZEFORTEXTBROWSER', $conf->global->MAIN_OPTIMIZEFORTEXTBROWSER, 1);
	    }
	    else
	    {
	        print yn($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER);
	    }
	    print ' &nbsp; wspan class="opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
	    print $form->textwithpicto('', $langs->trans("MAIN_OPTIMIZEFORTEXTBROWSERDesc"));
	    print '</span>';
	    print '</td>';
	    print '</tr>';
	    */
	}


	// Use MAIN_OPTIMIZEFORTEXTBROWSER
	if ($foruserprofile) {
		//$default=yn($conf->global->MAIN_OPTIMIZEFORCOLORBLIND);
		$default = $langs->trans('No');
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("MAIN_OPTIMIZEFORCOLORBLIND").'</td>';
		print '<td colspan="'.($colspan - 1).'">';

		$colorBlindOptions = array(
			0 => $langs->trans('No'),
			'flashy' => $langs->trans('Flashy'),
			'protanopia' => $langs->trans('Protanopia'),
			'deuteranopes' => $langs->trans('Deuteranopes'),
			'tritanopes' => $langs->trans('Tritanopes'),
		);

		if ($edit) {
			print $form->selectArray('MAIN_OPTIMIZEFORCOLORBLIND', $colorBlindOptions, $fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND, 0);
		} else {
			if (!empty($fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND) && isset($colorBlindOptions[$fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND])) {
				print $colorBlindOptions[$fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND];
			} else {
				print yn(0);
			}
		}
		print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("MAIN_OPTIMIZEFORCOLORBLINDDesc"));
		print '</td>';
		print '</tr>';
	} else {
	}
	print '</table>';
	print '</div>';
}
