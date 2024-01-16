<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015	    Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet       <fmarcet@2byte.es>
 * Copyright (C) 2021-2023  Anthony Berton      <anthony.berton@bb2a.fr>
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
 * @param   User	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function user_prepare_head(User $object)
{
	global $langs, $conf, $user, $db;

	$langs->load("users");

	$canreadperms = true;
	if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
		$canreadperms = ($user->admin || ($user->id != $object->id && $user->hasRight('user', 'user_advance', 'readperms')) || ($user->id == $object->id && $user->hasRight('user', 'self_advance', 'readperms')));
	}

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/user/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("User");
	$head[$h][2] = 'user';
	$h++;

	if ((!empty($conf->ldap->enabled) && getDolGlobalString('LDAP_SYNCHRO_ACTIVE'))
		&& (!getDolGlobalString('MAIN_DISABLE_LDAP_TAB') || !empty($user->admin))) {
		$langs->load("ldap");
		$head[$h][0] = DOL_URL_ROOT.'/user/ldap.php?id='.$object->id;
		$head[$h][1] = $langs->trans("LDAPCard");
		$head[$h][2] = 'ldap';
		$h++;
	}

	if ($canreadperms) {
		$head[$h][0] = DOL_URL_ROOT.'/user/perms.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Rights").(!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.($object->nb_rights).'</span>' : '');
		$head[$h][2] = 'rights';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/user/param_ihm.php?id='.$object->id;
	$head[$h][1] = $langs->trans("UserGUISetup");
	$head[$h][2] = 'guisetup';
	$h++;

	if (isModEnabled('agenda')) {
		if (!getDolGlobalString('AGENDA_EXT_NB')) {
			$conf->global->AGENDA_EXT_NB = 5;
		}
		$MAXAGENDA = getDolGlobalString('AGENDA_EXT_NB');

		$i = 1;
		$nbagenda = 0;
		while ($i <= $MAXAGENDA) {
			$key = $i;
			$name = 'AGENDA_EXT_NAME_'.$object->id.'_'.$key;
			$src = 'AGENDA_EXT_SRC_'.$object->id.'_'.$key;
			$offsettz = 'AGENDA_EXT_OFFSETTZ_'.$object->id.'_'.$key;
			$color = 'AGENDA_EXT_COLOR_'.$object->id.'_'.$key;
			$i++;

			if (!empty($object->conf->$name)) {
				$nbagenda++;
			}
		}

		$head[$h][0] = DOL_URL_ROOT.'/user/agenda_extsites.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ExtSites").($nbagenda ? '<span class="badge marginleftonlyshort">'.$nbagenda.'</span>' : '');
		$head[$h][2] = 'extsites';
		$h++;
	}

	if (isModEnabled('clicktodial')) {
		$head[$h][0] = DOL_URL_ROOT.'/user/clicktodial.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ClickToDial");
		$head[$h][2] = 'clicktodial';
		$h++;
	}

	// Notifications
	if ($user->socid == 0 && isModEnabled('notification')) {
		$nbNote = 0;
		$sql = "SELECT COUNT(n.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."notify_def as n";
		$sql .= " WHERE fk_user = ".((int) $object->id);
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
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'notify';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'user');

	if ((isModEnabled('salaries') && $user->hasRight('salaries', 'read'))
		|| (isModEnabled('hrm') && $user->hasRight('hrm', 'employee', 'read'))
		|| (isModEnabled('expensereport') && $user->hasRight('expensereport', 'lire') && ($user->id == $object->id || $user->hasRight('expensereport', 'readall')))
		|| (isModEnabled('holiday') && $user->hasRight('holiday', 'read') && ($user->id == $object->id || $user->hasRight('holiday', 'readall')))
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
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/user/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Note");
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
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
		if (($nbFiles + $nbLinks) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
		}
		$head[$h][2] = 'document';
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/user/info.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Events");
		if (isModEnabled('agenda')&& ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
			$nbEvent = 0;
			// Enable caching of thirdparty count actioncomm
			require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
			$cachekey = 'count_events_user_'.$object->id;
			$dataretrieved = dol_getcache($cachekey);
			if (!is_null($dataretrieved)) {
				$nbEvent = $dataretrieved;
			} else {
				$sql = "SELECT COUNT(id) as nb";
				$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
				$sql .= " WHERE fk_user_done = ".((int) $object->id);
				$sql .= " AND entity IN (".getEntity('agenda').")";
				$resql = $db->query($sql);
				if ($resql) {
					$obj = $db->fetch_object($resql);
					$nbEvent = $obj->nb;
				} else {
					dol_syslog('Failed to count actioncomm '.$db->lasterror(), LOG_ERR);
				}
				dol_setcache($cachekey, $nbEvent, 120);		// If setting cache fails, this is not a problem, so we do not test result.
			}

			$head[$h][1] .= '/';
			$head[$h][1] .= $langs->trans("Agenda");
			if ($nbEvent > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbEvent.'</span>';
			}
		}
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
	if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
		$canreadperms = ($user->admin || $user->rights->user->group_advance->readperms);
	}

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/user/group/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'group';
	$h++;

	if ((!empty($conf->ldap->enabled) && getDolGlobalString('LDAP_SYNCHRO_ACTIVE'))
		&& (!getDolGlobalString('MAIN_DISABLE_LDAP_TAB') || !empty($user->admin))) {
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
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('user');
	$extrafields->fetch_name_optionals_label('usergroup');

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
	$nbExtrafields = $extrafields->attributes['user']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/user/admin/group_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields")." (".$langs->trans("Groups").")";
	$nbExtrafields = $extrafields->attributes['usergroup']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
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
	if (empty($foruserprofile)) {
		$selected_theme = getDolGlobalString('MAIN_THEME');
	} else {
		$selected_theme = ((is_object($fuser) && !empty($fuser->conf->MAIN_THEME)) ? $fuser->conf->MAIN_THEME : '');
	}

	$hoverdisabled = '';
	if (empty($foruserprofile)) {
		$hoverdisabled = (getDolGlobalString('THEME_ELDY_USE_HOVER') == '0');
	} else {
		$hoverdisabled = (is_object($fuser) ? (empty($fuser->conf->THEME_ELDY_USE_HOVER) || $fuser->conf->THEME_ELDY_USE_HOVER == '0') : '');
	}

	$checkeddisabled = '';
	if (empty($foruserprofile)) {
		$checkeddisabled = (getDolGlobalString('THEME_ELDY_USE_CHECKED') == '0');
	} else {
		$checkeddisabled = (is_object($fuser) ? (empty($fuser->conf->THEME_ELDY_USE_CHECKED) || $fuser->conf->THEME_ELDY_USE_CHECKED == '0') : '');
	}

	$colspan = 2;
	if ($foruserprofile) {
		$colspan = 4;
	}

	$thumbsbyrow = 6;
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent'.($edit ? ' editmodeforshowskin' : '').'">';

	// Title
	if ($foruserprofile) {
		print '<tr class="liste_titre"><th class="titlefieldmiddle">'.$langs->trans("Parameter").'</th><th>'.$langs->trans("DefaultValue").'</th>';
		print '<th colspan="2">&nbsp;</th>';
		print '</tr>';

		print '<tr>';
		print '<td>'.$langs->trans("DefaultSkin").'</td>';
		print '<td>' . getDolGlobalString('MAIN_THEME').'</td>';
		print '<td class="nowrap left"><input id="check_MAIN_THEME" name="check_MAIN_THEME"'.($edit ? '' : ' disabled').' type="checkbox" '.($selected_theme ? " checked" : "").'> <label for="check_MAIN_THEME">'.$langs->trans("UsePersonalValue").'</label></td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
	} else {
		$dirthemestring = '';
		foreach ($dirthemes as $dirtheme) {
			$dirthemestring .= '"'.$dirtheme.'" ';
		}

		print '<tr class="liste_titre"><th class="titlefieldmiddle">';
		print $form->textwithpicto($langs->trans("DefaultSkin"), $langs->trans("ThemeDir").' : '.$dirthemestring);
		print '</th>';
		print '<th class="right">';
		$url = 'https://www.dolistore.com/9-skins';
		print '<a href="'.$url.'" target="_blank" rel="noopener noreferrer external">';
		print $langs->trans('DownloadMoreSkins');
		print img_picto('', 'globe', 'class="paddingleft"');
		print '</a>';
		print '</th></tr>';
	}

	print '<tr><td colspan="'.$colspan.'" class="center">';

	if (getDolGlobalString('MAIN_FORCETHEME')) {
		$langs->load("errors");
		print $langs->trans("WarningThemeForcedTo", getDolGlobalString('MAIN_FORCETHEME'));
	}

	print '<table class="nobordernopadding centpercent"><tr><td><div class="center">';

	$i = 0;
	foreach ($dirthemes as $dir) {
		//print $dirroot.$dir;exit;
		$dirtheme = dol_buildpath($dir, 0); // This include loop on $conf->file->dol_document_root
		$urltheme = dol_buildpath($dir, 1);

		if (is_dir($dirtheme)) {
			$handle = opendir($dirtheme);
			if (is_resource($handle)) {
				while (($subdir = readdir($handle)) !== false) {
					if (is_dir($dirtheme."/".$subdir) && substr($subdir, 0, 1) != '.'
							&& substr($subdir, 0, 3) != 'CVS' && !preg_match('/common|phones/i', $subdir)) {
						// Disable not stable themes (dir ends with _exp or _dev)
						if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2 && preg_match('/_dev$/i', $subdir)) {
							continue;
						}
						if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1 && preg_match('/_exp$/i', $subdir)) {
							continue;
						}

						print '<div class="inline-block" style="margin-top: 10px; margin-bottom: 10px; margin-right: 20px; margin-left: 20px;">';
						$file = $dirtheme."/".$subdir."/thumb.png";
						$url = $urltheme."/".$subdir."/thumb.png";
						if (!file_exists($file)) {
							$url = DOL_URL_ROOT.'/public/theme/common/nophoto.png';
						}
						print '<a href="'.$_SERVER["PHP_SELF"].($edit ? '?action=edit&token='.newToken().'&mode=template&theme=' : '?theme=').$subdir.(GETPOST('optioncss', 'alpha', 1) ? '&optioncss='.GETPOST('optioncss', 'alpha', 1) : '').($fuser ? '&id='.$fuser->id : '').'" style="font-weight: normal;" alt="'.$langs->trans("Preview").'">';
						if ($subdir == $conf->global->MAIN_THEME) {
							$title = $langs->trans("ThemeCurrentlyActive");
						} else {
							$title = $langs->trans("ShowPreview");
						}
						print '<img class="img-skinthumb shadow" src="'.$url.'" alt="'.dol_escape_htmltag($title).'" title="'.dol_escape_htmltag($title).'" style="border: none; margin-bottom: 5px;">';
						print '</a><br>';
						if ($subdir == $selected_theme) {
							print '<input '.($edit ? '' : 'disabled').' type="radio" class="themethumbs" style="border: 0px;" id="main_theme'.$subdir.'" checked name="main_theme" value="'.$subdir.'"><label for="main_theme'.$subdir.'"> <b>'.$subdir.'</b></label>';
						} else {
							print '<input '.($edit ? '' : 'disabled').' type="radio" class="themethumbs" style="border: 0px;" id="main_theme'.$subdir.'" name="main_theme" value="'.$subdir.'"><label for="main_theme'.$subdir.'"> '.$subdir.'</label>';
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
	$colorbacklineimpair2 = '';
	$colorbacklinepair1 = '';
	$colorbacklinepair2 = '';
	$colortextlink = '';
	$colorbacklinepairhover = '';
	$colorbacklinepairhover = '';
	$colorbacklinepairchecked = '';
	$butactionbg = '';
	$textbutaction = '';
	// Set the variables with the default value
	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php')) {
		include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
	}

	// Dark mode
	if ($foruserprofile) {
		//Nothing
	} else {
		$listofdarkmodes = array(
			'0' => $langs->trans("AlwaysDisabled"),
			'1' => $langs->trans("AccordingToBrowser"),
			'2' => $langs->trans("AlwaysEnabled")
		);
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("DarkThemeMode").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $form->selectarray('THEME_DARKMODEENABLED', $listofdarkmodes, isset($conf->global->THEME_DARKMODEENABLED) ? $conf->global->THEME_DARKMODEENABLED : 0);
		} else {
			print $listofdarkmodes[isset($conf->global->THEME_DARKMODEENABLED) ? $conf->global->THEME_DARKMODEENABLED : 0];
		}
		print $form->textwithpicto('', $langs->trans("DoesNotWorkWithAllThemes"));
		print '</tr>';
	}


	// TopMenuDisableImages
	if ($foruserprofile) {
		/*
		 print '<tr class="oddeven">';
		 print '<td>'.$langs->trans("TopMenuDisableImages").'</td>';
		 print '<td>'.($conf->global->THEME_TOPMENU_DISABLE_IMAGE?$conf->global->THEME_TOPMENU_DISABLE_IMAGE:$langs->trans("Default")).'</td>';
		 print '<td class="left" class="nowrap" width="20%"><input name="check_THEME_TOPMENU_DISABLE_IMAGE" id="check_THEME_TOPMENU_DISABLE_IMAGE" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TEXTLINK)?" checked":"");
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
		$listoftopmenumodes = array(
			'0' => $langs->transnoentitiesnoconv("IconAndText"),
			'1' => $langs->transnoentitiesnoconv("TextOnly"),
			'2' => $langs->transnoentitiesnoconv("IconOnlyAllTextsOnHover"),
			'3' => $langs->transnoentitiesnoconv("IconOnlyTextOnHover"),
			'4' => $langs->transnoentitiesnoconv("IconOnly"),
		);
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("TopMenuDisableImages").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			//print ajax_constantonoff('THEME_TOPMENU_DISABLE_IMAGE', array(), null, 0, 0, 1);
			print $form->selectarray('THEME_TOPMENU_DISABLE_IMAGE', $listoftopmenumodes, isset($conf->global->THEME_TOPMENU_DISABLE_IMAGE) ? $conf->global->THEME_TOPMENU_DISABLE_IMAGE : 0, 0, 0, 0, '', 0, 0, 0, '', 'widthcentpercentminusx maxwidth500');
		} else {
			$listoftopmenumodes[getDolGlobalString('THEME_TOPMENU_DISABLE_IMAGE')];
			//print yn($conf->global->THEME_TOPMENU_DISABLE_IMAGE);
		}
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes"));
		print '</td>';
		print '</tr>';
	}

	// Show logo
	if ($foruserprofile) {
		// Nothing
	} else {
		// Show logo
		print '<tr class="oddeven"><td class="titlefieldmiddle">'.$langs->trans("EnableShowLogo").'</td>';
		print '<td colspan="'.($colspan - 1).'" class="valignmiddle">';
		if ($edit) {
			print ajax_constantonoff('MAIN_SHOW_LOGO', array(), null, 0, 0, 1);
			//print $form->selectyesno('MAIN_SHOW_LOGO', $conf->global->MAIN_SHOW_LOGO, 1);
		} else {
			print yn(getDolGlobalString('MAIN_SHOW_LOGO'));
		}
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes"), 1, 'help', 'inline-block');
		print '</td>';
		print '</tr>';
		/*
		 print '<tr class="oddeven"><td>'.$langs->trans("EnableShowLogo").'</td><td>' . yn($conf->global->MAIN_SHOW_LOGO) . '</td>';
		 print "</tr>";*/
	}

	// BorderTableActive
	if ($foruserprofile) {
	} else {
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("UseBorderOnTable").'</td>';
		print '<td colspan="'.($colspan - 1).'" class="valignmiddle">';
		if ($edit) {
			print ajax_constantonoff('THEME_ELDY_USEBORDERONTABLE', array(), null, 0, 0, 1);
			//print $form->selectyesno('THEME_ELDY_USEBORDERONTABLE', $conf->global->THEME_ELDY_USEBORDERONTABLE, 1);
		} else {
			print yn(getDolGlobalString('THEME_ELDY_USEBORDERONTABLE'));
		}
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes"), 1, 'help', 'inline-block');
		print '</td>';
		print '</tr>';
	}

	// Table line height
	/* removed. height of column must use padding of td and not lineheight that has bad side effect
	if ($foruserprofile) {
	} else {
		$listoftopmenumodes = array(
			'0' => $langs->transnoentitiesnoconv("Normal"),
			'1' => $langs->transnoentitiesnoconv("LargeModern"),
		);
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("TableLineHeight").'</td>';
		print '<td colspan="'.($colspan - 1).'" class="valignmiddle">';
		if ($edit) {
			//print ajax_constantonoff('THEME_ELDY_USECOMOACTROW', array(), null, 0, 0, 1);
			print $form->selectarray('THEME_ELDY_USECOMOACTROW', $listoftopmenumodes, getDolGlobalString('THEME_ELDY_USECOMOACTROW'), 0, 0, 0, '', 0, 0, 0, '', 'widthcentpercentminusx maxwidth300');
		} else {
			print $listoftopmenumodes[getDolGlobalString('THEME_ELDY_USECOMOACTROW')];
		}
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes"), 1, 'help', 'inline-block');
		print '</td>';
		print '</tr>';
	}
	*/

	// Background color THEME_ELDY_BACKBODY
	if ($foruserprofile) {
		/*
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		print '<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_TOPMENU_BACK1:$langs->trans("Default")).'</td>';
		print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TOPMENU_BACK1" id="check_THEME_ELDY_TOPMENU_BACK1" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TOPMENU_BACK1)?" checked":"");
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
		$default = 'ffffff';
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("BackgroundColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		//var_dump($conf->global->THEME_ELDY_BACKBODY);
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_BACKBODY') ? $conf->global->THEME_ELDY_BACKBODY : ''), array()), ''), 'THEME_ELDY_BACKBODY', '', 1, '', '', 'colorbackbody', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BACKBODY, array()), '');
			if ($color) {
				print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				print $langs->trans("Default");
			}
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
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
		print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TOPMENU_BACK1" id="check_THEME_ELDY_TOPMENU_BACK1" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TOPMENU_BACK1)?" checked":"");
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
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TOPMENU_BACK1') ? $conf->global->THEME_ELDY_TOPMENU_BACK1 : ''), array()), ''), 'THEME_ELDY_TOPMENU_BACK1', '', 1, '', '', 'colorbackhmenu1', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1, array()), '');
			if ($color) {
				print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				print $langs->trans("Default");
			}
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
		 print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_VERMENU_BACK1" id="check_THEME_ELDY_VERMENU_BACK1" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TOPMENU_BACK1)?" checked":"");
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
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_VERMENU_BACK1') ? $conf->global->THEME_ELDY_VERMENU_BACK1 : ''), array()), ''), 'THEME_ELDY_VERMENU_BACK1', '', 1, '', '', 'colorbackvmenu1', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_VERMENU_BACK1, array()), '');
			if ($color) {
				print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				print $langs->trans("Default");
			}
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
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTTITLENOTAB') ? $conf->global->THEME_ELDY_TEXTTITLENOTAB : ''), array()), ''), 'THEME_ELDY_TEXTTITLENOTAB', '', 1, '', '', 'colortexttitlenotab', $default).' ';
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
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_BACKTITLE1') ? $conf->global->THEME_ELDY_BACKTITLE1 : ''), array()), ''), 'THEME_ELDY_BACKTITLE1', '', 1, '', '', 'colorbacktitle1', $default).' ';
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
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTTITLE') ? $conf->global->THEME_ELDY_TEXTTITLE : ''), array()), ''), 'THEME_ELDY_TEXTTITLE', '', 1, '', '', 'colortexttitle', $default).' ';
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
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTTITLELINK') ? $conf->global->THEME_ELDY_TEXTTITLELINK : ''), array()), ''), 'THEME_ELDY_TEXTTITLELINK', '', 1, '', '', 'colortexttitlelink', $default).' ';
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
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_LINEIMPAIR1') ? $conf->global->THEME_ELDY_LINEIMPAIR1 : ''), array()), ''), 'THEME_ELDY_LINEIMPAIR1', '', 1, '', '', 'colorbacklineimpair2', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_LINEIMPAIR1, array()), '');
			if ($color) {
				print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				print $langs->trans("Default");
			}
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
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_LINEPAIR1') ? $conf->global->THEME_ELDY_LINEPAIR1 : ''), array()), ''), 'THEME_ELDY_LINEPAIR1', '', 1, '', '', 'colorbacklinepair2', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_LINEPAIR1, array()), '');
			if ($color) {
				print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				print $langs->trans("Default");
			}
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
		 print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TEXTLINK" id="check_THEME_ELDY_TEXTLINK" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TEXTLINK)?" checked":"");
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
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTLINK') ? $conf->global->THEME_ELDY_TEXTLINK : ''), array()), ''), 'THEME_ELDY_TEXTLINK', '', 1, '', '', 'colortextlink', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTLINK, array()), '');
			if ($color) {
				print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
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
			if (getDolGlobalString('THEME_ELDY_USE_HOVER') == '1') {
				$color = colorArrayToHex(colorStringToArray($colorbacklinepairhover));
			} else {
				$color = colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_USE_HOVER') ? $conf->global->THEME_ELDY_USE_HOVER : ''), array()), '');
			}
			print $formother->selectColor($color, 'THEME_ELDY_USE_HOVER', '', 1, '', '', 'colorbacklinepairhover', $default).' ';
		} else {
			if (getDolGlobalString('THEME_ELDY_USE_HOVER') == '1') {
				$color = colorArrayToHex(colorStringToArray($colorbacklinepairhover));
			} else {
				$color = colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_USE_HOVER') ? $conf->global->THEME_ELDY_USE_HOVER : ''), array()), '');
			}
			if ($color) {
				if ($color != colorArrayToHex(colorStringToArray($colorbacklinepairhover))) {
					print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
				} else {
					print $langs->trans("Default");
				}
			} else {
				print $langs->trans("Default");
			}
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
			if (getDolGlobalString('THEME_ELDY_USE_CHECKED') == '1') {
				$color = 'e6edf0';
			} else {
				$color = colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_USE_CHECKED') ? $conf->global->THEME_ELDY_USE_CHECKED : ''), array()), '');
			}
			print $formother->selectColor($color, 'THEME_ELDY_USE_CHECKED', '', 1, '', '', 'colorbacklinepairchecked', $default).' ';
		} else {
			if (getDolGlobalString('THEME_ELDY_USE_CHECKED') == '1') {
				$color = 'e6edf0';
			} else {
				$color = colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_USE_CHECKED') ? $conf->global->THEME_ELDY_USE_CHECKED : ''), array()), '');
			}
			if ($color) {
				if ($color != 'e6edf0') {
					print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
				} else {
					print $langs->trans("Default");
				}
			} else {
				print $langs->trans("Default");
			}
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis"));
		print '</td>';
		print '</tr>';
	}

	// Btn action
	if ($foruserprofile) {
		/*
		 print '<tr class="oddeven">';
		 print '<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		 print '<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_BTNACTION:$langs->trans("Default")).'</td>';
		 print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_BTNACTION" id="check_THEME_ELDY_BTNACTION" type="checkbox" '.(!empty($object->conf->THEME_ELDY_BTNACTION)?" checked":"");
		 print (empty($dolibarr_main_demo) && $edit)?'':' disabled="disabled"';	// Disabled for demo
		 print '> '.$langs->trans("UsePersonalValue").'</td>';
		 print '<td>';
		 if ($edit)
		 {
		 print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BTNACTION,array()),''),'THEME_ELDY_BTNACTION','',1).' ';
		 }
		 else
		 {
		 $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BTNACTION,array()),'');
		 if ($color) print '<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		 else print '';
		 }
			if ($edit) print '<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
			print '</td>';*/
	} else {
		$default = (empty($butactionbg) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($butactionbg)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("BtnActionColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_BTNACTION') ? $conf->global->THEME_ELDY_BTNACTION : ''), array()), ''), 'THEME_ELDY_BTNACTION', '', 1, '', '', 'butactionbg', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BTNACTION, array()), '');
			if ($color) {
				print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
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

	// Text btn action
	if ($foruserprofile) {
		/*
		 print '<tr class="oddeven">';
		 print '<td>'.$langs->trans("TopMenuBackgroundColor").'</td>';
		 print '<td>'.($conf->global->THEME_ELDY_TOPMENU_BACK1?$conf->global->THEME_ELDY_TEXTBTNACTION:$langs->trans("Default")).'</td>';
		 print '<td class="nowrap left" width="20%"><input name="check_THEME_ELDY_TEXTBTNACTION" id="check_THEME_ELDY_TEXTBTNACTION" type="checkbox" '.(!empty($object->conf->THEME_ELDY_TEXTBTNACTION)?" checked":"");
		 print (empty($dolibarr_main_demo) && $edit)?'':' disabled="disabled"'; // Disabled for demo
		 print '> '.$langs->trans("UsePersonalValue").'</td>';
		 print '<td>';
		 if ($edit)
		 {
		 print $formother->selectColor(colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTBTNACTION,array()),''),'THEME_ELDY_TEXTBTNACTION','',1).' ';
		 }
		 else
		 {
		 $color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_BTNACTION,array()),'');
		 if ($color) print '<input type="text" class="colorthumb" disabled style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
		 else print '';
		 }
			if ($edit) print '<br>('.$langs->trans("NotSupportedByAllThemes").', '.$langs->trans("PressF5AfterChangingThis").')';
			print '</td>';*/
	} else {
		$default = (empty($textbutaction) ? $langs->trans("Unknown") : colorArrayToHex(colorStringToArray($textbutaction)));

		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("TextBtnActionColor").'</td>';
		print '<td colspan="'.($colspan - 1).'">';
		if ($edit) {
			print $formother->selectColor(colorArrayToHex(colorStringToArray((getDolGlobalString('THEME_ELDY_TEXTBTNACTION') ? $conf->global->THEME_ELDY_TEXTBTNACTION : ''), array()), ''), 'THEME_ELDY_TEXTBTNACTION', '', 1, '', '', 'textbutaction', $default).' ';
		} else {
			$color = colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TEXTBTNACTION, array()), '');
			if ($color) {
				print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$color.'" value="'.$color.'">';
			} else {
				//print '<input type="text" class="colorthumb" disabled="disabled" style="padding: 1px; margin-top: 0; margin-bottom: 0; background-color: #'.$defaultcolor.'" value="'.$langs->trans("Default").'">';
				//print '<span style="color: #000078">'.$langs->trans("Default").'</span>';
				print $langs->trans("Default");
			}
		}
		print ' &nbsp; <span class="nowraponall opacitymedium">'.$langs->trans("Default").'</span>: <strong><span style="color: #000">'.$default.'</span></strong> ';
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
			print $form->selectyesno('MAIN_OPTIMIZEFORTEXTBROWSER', (isset($fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER) ? $fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER : 0), 1);
		} else {
			if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				print yn(isset($fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER) ? $fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER : 0);
			} else {
				print yn(1);
				if (empty($fuser->conf->MAIN_OPTIMIZEFORTEXTBROWSER)) {
					print ' ('.$langs->trans("ForcedByGlobalSetup").')';
				}
			}
		}
		print ' &nbsp; <span class="opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("MAIN_OPTIMIZEFORTEXTBROWSERDesc"));
		print '</td>';
		print '</tr>';
	} else {
		//var_dump($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER);
		/*
		$default=$langs->trans('No');
		print '<tr class="oddeven">';
		print '<td>'.$langs->trans("MAIN_OPTIMIZEFORTEXTBROWSER").'</td>';
		print '<td colspan="'.($colspan-1).'">';
		if ($edit) {
			print $form->selectyesno('MAIN_OPTIMIZEFORTEXTBROWSER', $conf->global->MAIN_OPTIMIZEFORTEXTBROWSER, 1);
		} else {
			print yn($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER);
		}
		print ' &nbsp; wspan class="opacitymedium">'.$langs->trans("Default").'</span>: <strong>'.$default.'</strong> ';
		print $form->textwithpicto('', $langs->trans("MAIN_OPTIMIZEFORTEXTBROWSERDesc"));
		print '</span>';
		print '</td>';
		print '</tr>';
		*/
	}


	// Use MAIN_OPTIMIZEFORCOLORBLIND
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
			print $form->selectArray('MAIN_OPTIMIZEFORCOLORBLIND', $colorBlindOptions, (isset($fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND) ? $fuser->conf->MAIN_OPTIMIZEFORCOLORBLIND : 0), 0);
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

/**
 *    	Show html area with actions (done or not, ignore the name of function).
 *      Note: Global parameter $param must be defined.
 *
 * 		@param	Conf		       $conf		   Object conf
 * 		@param	Translate	       $langs		   Object langs
 * 		@param	DoliDB		       $db			   Object db
 * 		@param	mixed			   $filterobj	   Filter on object Adherent|Societe|Project|Product|CommandeFournisseur|Dolresource|Ticket... to list events linked to an object
 * 		@param	Contact		       $objcon		   Filter on object contact to filter events on a contact
 *      @param  int			       $noprint        Return string but does not output it
 *      @param  string|string[]    $actioncode     Filter on actioncode
 *      @param  string             $donetodo       Filter on event 'done' or 'todo' or ''=nofilter (all).
 *      @param  array              $filters        Filter on other fields
 *      @param  string             $sortfield      Sort field
 *      @param  string             $sortorder      Sort order
 *      @param	string			   $module		   You can add module name here if elementtype in table llx_actioncomm is objectkey@module
 *      @return	string|void				           Return html part or void if noprint is 1
 */
function show_actions_done_user($conf, $langs, $db, $filterobj, $objcon = '', $noprint = 0, $actioncode = '', $donetodo = 'done', $filters = array(), $sortfield = 'a.datep,a.id', $sortorder = 'DESC', $module = '')
{
	global $user, $conf, $hookmanager;
	global $form;
	global $param, $massactionbutton;

	$start_year = GETPOST('dateevent_startyear', 'int');
	$start_month = GETPOST('dateevent_startmonth', 'int');
	$start_day = GETPOST('dateevent_startday', 'int');
	$end_year = GETPOST('dateevent_endyear', 'int');
	$end_month = GETPOST('dateevent_endmonth', 'int');
	$end_day = GETPOST('dateevent_endday', 'int');
	$tms_start = '';
	$tms_end = '';

	if (!empty($start_year) && !empty($start_month) && !empty($start_day)) {
		$tms_start = dol_mktime(0, 0, 0, $start_month, $start_day, $start_year, 'tzuserrel');
	}
	if (!empty($end_year) && !empty($end_month) && !empty($end_day)) {
		$tms_end = dol_mktime(23, 59, 59, $end_month, $end_day, $end_year, 'tzuserrel');
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
		$tms_start = '';
		$tms_end = '';
	}
	dol_include_once('/comm/action/class/actioncomm.class.php');

	// Check parameters
	if (!is_object($filterobj) && !is_object($objcon)) {
		dol_print_error('', 'BadParameter');
	}

	$out = '';
	$histo = array();
	$numaction = 0;
	$now = dol_now('tzuser');

	// Open DSI -- Fix order by -- Begin
	$sortfield_list = explode(',', $sortfield);
	$sortfield_label_list = array('a.id' => 'id', 'a.datep' => 'dp', 'a.percent' => 'percent');
	$sortfield_new_list = array();
	foreach ($sortfield_list as $sortfield_value) {
		$sortfield_new_list[] = $sortfield_label_list[trim($sortfield_value)];
	}
	$sortfield_new = implode(',', $sortfield_new_list);

	$sql = '';

	if (isModEnabled('agenda')) {
		// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
		$hookmanager->initHooks(array('agendadao'));

		// Recherche histo sur actioncomm
		if (is_object($objcon) && $objcon->id > 0) {
			$sql = "SELECT DISTINCT a.id, a.label as label,";
		} else {
			$sql = "SELECT a.id, a.label as label,";
		}
		$sql .= " a.datep as dp,";
		$sql .= " a.datep2 as dp2,";
		$sql .= " a.percent as percent, 'action' as type,";
		$sql .= " a.fk_element, a.elementtype,";
		$sql .= " c.code as acode, c.libelle as alabel, c.picto as apicto,";
		$sql .= " u.rowid as user_id, u.login as user_login, u.photo as user_photo, u.firstname as user_firstname, u.lastname as user_lastname";
		if (is_object($filterobj) && in_array(get_class($filterobj), array('Societe', 'Client', 'Fournisseur'))) {
			$sql .= ", sp.lastname, sp.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Dolresource') {
			/* Nothing */
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Project') {
			/* Nothing */
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Adherent') {
			$sql .= ", m.lastname, m.firstname";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'CommandeFournisseur') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Product') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Ticket') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'BOM') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && get_class($filterobj) == 'Contrat') {
			$sql .= ", o.ref";
		} elseif (is_object($filterobj) && is_array($filterobj->fields) && is_array($filterobj->fields['rowid']) && $filterobj->table_element && $filterobj->element) {
			if (!empty($filterobj->fields['ref'])) {
				$sql .= ", o.ref";
			} elseif (!empty($filterobj->fields['label'])) {
				$sql .= ", o.label";
			}
		}

		// Fields from hook
		$parameters = array('sql' => &$sql, 'filterobj' => $filterobj, 'objcon' => $objcon);
		$reshook = $hookmanager->executeHooks('showActionsDoneListSelect', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql.= $hookmanager->resPrint;
		}

		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_action";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action = c.id";
		$force_filter_contact = false;
		if (is_object($objcon) && $objcon->id > 0) {
			$force_filter_contact = true;
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm_resources as r ON a.id = r.fk_actioncomm";
			$sql .= " AND r.element_type = '".$db->escape($objcon->table_element)."' AND r.fk_element = ".((int) $objcon->id);
		}


		// Fields from hook
		$parameters = array('sql' => &$sql, 'filterobj' => $filterobj, 'objcon' => $objcon);
		$reshook = $hookmanager->executeHooks('showActionsDoneListFrom', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql.= $hookmanager->resPrint;
		}

		$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
		$sql .= " AND u.rowid = ".((int) $filterobj->id);


		if (!empty($tms_start) && !empty($tms_end)) {
			$sql .= " AND ((a.datep BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."') OR (a.datep2 BETWEEN '".$db->idate($tms_start)."' AND '".$db->idate($tms_end)."'))";
		} elseif (empty($tms_start) && !empty($tms_end)) {
			$sql .= " AND ((a.datep <= '".$db->idate($tms_end)."') OR (a.datep2 <= '".$db->idate($tms_end)."'))";
		} elseif (!empty($tms_start) && empty($tms_end)) {
			$sql .= " AND ((a.datep >= '".$db->idate($tms_start)."') OR (a.datep2 >= '".$db->idate($tms_start)."'))";
		}
		if (is_array($actioncode) && !empty($actioncode)) {
			$sql .= ' AND (';
			foreach ($actioncode as $key => $code) {
				if ($key != 0) {
					$sql .= " OR ";
				}
				if (!empty($code)) {
					addEventTypeSQL($sql, $code, "");
				}
			}
			$sql .= ')';
		} elseif (!empty($actioncode)) {
			addEventTypeSQL($sql, $actioncode);
		}

		addOtherFilterSQL($sql, $donetodo, $now, $filters);

		// Fields from hook
		$parameters = array('sql' => &$sql, 'filterobj' => $filterobj, 'objcon' => $objcon, 'module' => $module);
		$reshook = $hookmanager->executeHooks('showActionsDoneListWhere', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) {
			$sql.= $hookmanager->resPrint;
		}

		if (is_array($actioncode)) {
			foreach ($actioncode as $code) {
				$sql2 = addMailingEventTypeSQL($code, $objcon, $filterobj);
				if (!empty($sql2)) {
					if (!empty($sql)) {
						$sql = $sql." UNION ".$sql2;
					} elseif (empty($sql)) {
						$sql = $sql2;
					}
					break;
				}
			}
		} else {
			$sql2 = addMailingEventTypeSQL($actioncode, $objcon, $filterobj);
			if (!empty($sql) && !empty($sql2)) {
				$sql = $sql." UNION ".$sql2;
			} elseif (empty($sql) && !empty($sql2)) {
				$sql = $sql2;
			}
		}
	}

	//TODO Add limit in nb of results
	if ($sql) {
		$sql .= $db->order($sortfield_new, $sortorder);

		dol_syslog("usergroups.lib::show_actions_dones", LOG_DEBUG);

		$resql = $db->query($sql);
		if ($resql) {
			$i = 0;
			$num = $db->num_rows($resql);

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if ($obj->type == 'action') {
					$contactaction = new ActionComm($db);
					$contactaction->id = $obj->id;
					$result = $contactaction->fetchResources();
					if ($result < 0) {
						dol_print_error($db);
						setEventMessage("user.lib::show_actions_done Error fetch resource", 'errors');
					}

					//if ($donetodo == 'todo') $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep > '".$db->idate($now)."'))";
					//elseif ($donetodo == 'done') $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep <= '".$db->idate($now)."'))";
					$tododone = '';
					if (($obj->percent >= 0 and $obj->percent < 100) || ($obj->percent == -1 && (!empty($obj->datep) && $obj->datep > $now))) {
						$tododone = 'todo';
					}

					$histo[$numaction] = array(
						'type'=>$obj->type,
						'tododone'=>$tododone,
						'id'=>$obj->id,
						'datestart'=>$db->jdate($obj->dp),
						'dateend'=>$db->jdate($obj->dp2),
						'note'=>$obj->label,
						'percent'=>$obj->percent,

						'userid'=>$obj->user_id,
						'login'=>$obj->user_login,
						'userfirstname'=>$obj->user_firstname,
						'userlastname'=>$obj->user_lastname,
						'userphoto'=>$obj->user_photo,

						'contact_id'=>$obj->fk_contact,
						'socpeopleassigned' => $contactaction->socpeopleassigned,
						'lastname' => empty($obj->lastname) ? '' : $obj->lastname,
						'firstname' => empty($obj->firstname) ? '' : $obj->firstname,
						'fk_element'=>$obj->fk_element,
						'elementtype'=>$obj->elementtype,
						// Type of event
						'acode'=>$obj->acode,
						'alabel'=>$obj->alabel,
						'libelle'=>$obj->alabel, // deprecated
						'apicto'=>$obj->apicto
					);
				} else {
					$histo[$numaction] = array(
						'type'=>$obj->type,
						'tododone'=>'done',
						'id'=>$obj->id,
						'datestart'=>$db->jdate($obj->dp),
						'dateend'=>$db->jdate($obj->dp2),
						'note'=>$obj->label,
						'percent'=>$obj->percent,
						'acode'=>$obj->acode,

						'userid'=>$obj->user_id,
						'login'=>$obj->user_login,
						'userfirstname'=>$obj->user_firstname,
						'userlastname'=>$obj->user_lastname,
						'userphoto'=>$obj->user_photo
					);
				}

				$numaction++;
				$i++;
			}
		} else {
			dol_print_error($db);
		}
	}

	if (isModEnabled('agenda')|| (isModEnabled('mailing') && !empty($objcon->email))) {
		$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

		$formactions = new FormActions($db);

		$actionstatic = new ActionComm($db);
		$userstatic = new User($db);
		$userlinkcache = array();
		$contactstatic = new Contact($db);
		$elementlinkcache = array();

		$out .= '<form name="listactionsfilter" class="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'?id='.$filterobj->id.'" method="POST">';
		$out .= '<input type="hidden" name="token" value="'.newToken().'">';
		if ($objcon && get_class($objcon) == 'User' &&
			(is_null($filterobj) || get_class($filterobj) == 'User')) {
			$out .= '<input type="hidden" name="id" value="'.$objcon->id.'" />';
		} else {
			$out .= '<input type="hidden" name="id" value="'.$filterobj->id.'" />';
		}
		if ($filterobj && get_class($filterobj) == 'User') {
			$out .= '<input type="hidden" name="id" value="'.$filterobj->id.'" />';
		}

		$out .= "\n";

		$out .= '<div class="div-table-responsive-no-min">';
		$out .= '<table class="noborder centpercent">';

		$out .= '<tr class="liste_titre">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<th class="liste_titre width50 middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</th>';
		}

		if ($donetodo) {
			$out .= '<td class="liste_titre"></td>';
		}

		$out .= '<td class="liste_titre"><input type="text" class="width50" name="search_rowid" value="'.(isset($filters['search_rowid']) ? $filters['search_rowid'] : '').'"></td>';
		$out .= '<td class="liste_titre"></td>';
		$out .= '<td class="liste_titre">';
		$out .= $formactions->select_type_actions($actioncode, "actioncode", '', !getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 1 : -1, 0, (!getDolGlobalString('AGENDA_USE_MULTISELECT_TYPE') ? 0 : 1), 1, 'minwidth100 maxwidth150');
		$out .= '</td>';
		$out .= '<td class="liste_titre maxwidth100onsmartphone"><input type="text" class="maxwidth100onsmartphone" name="search_agenda_label" value="'.$filters['search_agenda_label'].'"></td>';
		$out .= '<td class="liste_titre center">';
		$out .= $form->selectDateToDate($tms_start, $tms_end, 'dateevent', 1);
		$out .= '</td>';
		$out .= '<td class="liste_titre"></td>';
		$out .= '<td class="liste_titre"></td>';
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= '<td class="liste_titre" align="middle">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			$out .= $searchpicto;
			$out .= '</td>';
		}
		$out .= '</tr>';

		$out .= '<tr class="liste_titre">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
		}
		if ($donetodo) {
			$tmp = '';
			if (get_class($filterobj) == 'User') {
				$tmp .= '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&userid='.$filterobj->id.'&status=done">';
			}
			$tmp .= ($donetodo != 'done' ? $langs->trans("ActionsToDoShort") : '');
			$tmp .= ($donetodo != 'done' && $donetodo != 'todo' ? ' / ' : '');
			$tmp .= ($donetodo != 'todo' ? $langs->trans("ActionsDoneShort") : '');
			//$out.=$langs->trans("ActionsToDoShort").' / '.$langs->trans("ActionsDoneShort");
			if (get_class($filterobj) == 'User') {
				$tmp .= '</a>';
			}
			$out .= getTitleFieldOfList($tmp);
		}
		$out .= getTitleFieldOfList("Ref", 0, $_SERVER["PHP_SELF"], 'a.id', '', $param, '', $sortfield, $sortorder);
		$out .= getTitleFieldOfList("Owner");
		$out .= getTitleFieldOfList("Type");
		$out .= getTitleFieldOfList("Label", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
		$out .= getTitleFieldOfList("Date", 0, $_SERVER["PHP_SELF"], 'a.datep,a.id', '', $param, '', $sortfield, $sortorder, 'center ');
		$out .= getTitleFieldOfList("Status", 0, $_SERVER["PHP_SELF"], 'a.percent', '', $param, '', $sortfield, $sortorder, 'center ');
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$out .= getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'maxwidthsearch ');
		}
		$out .= '<td></td>';
		$out .= '</tr>';

		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
		$caction = new CActionComm($db);
		$arraylist = $caction->liste_array(1, 'code', '', (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? 1 : 0), '', 1);

		foreach ($histo as $key => $value) {
			$actionstatic->fetch($histo[$key]['id']); // TODO Do we need this, we already have a lot of data of line into $histo

			$actionstatic->type_picto = $histo[$key]['apicto'];
			$actionstatic->type_code = $histo[$key]['acode'];

			$out .= '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				$out .= '<td></td>';
			}

			// Done or todo
			if ($donetodo) {
				$out .= '<td class="nowrap">';
				$out .= '</td>';
			}

			// Ref
			$out .= '<td class="nowraponall">';
			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$out .= $histo[$key]['id'];
				$out .= '</a>';
			} else {
				$out .= $actionstatic->getNomUrl(1, -1);
			}
			$out .= '</td>';

			// Author of event
			$out .= '<td class="tdoverflowmax125">';
			if ($histo[$key]['userid'] > 0) {
				if (isset($userlinkcache[$histo[$key]['userid']])) {
					$link = $userlinkcache[$histo[$key]['userid']];
				} else {
					$userstatic->fetch($histo[$key]['userid']);
					$link = $userstatic->getNomUrl(-1, '', 0, 0, 16, 0, 'firstelselast', '');
					$userlinkcache[$histo[$key]['userid']] = $link;
				}
				$out .= $link;
			}
			$out .= '</td>';

			// Type
			$labeltype = $actionstatic->type_code;
			if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') && empty($arraylist[$labeltype])) {
				$labeltype = 'AC_OTH';
			}
			if (!empty($actionstatic->code) && preg_match('/^TICKET_MSG/', $actionstatic->code)) {
				$labeltype = $langs->trans("Message");
			} else {
				if (!empty($arraylist[$labeltype])) {
					$labeltype = $arraylist[$labeltype];
				}
				if ($actionstatic->type_code == 'AC_OTH_AUTO' && ($actionstatic->type_code != $actionstatic->code) && $labeltype && !empty($arraylist[$actionstatic->code])) {
					$labeltype .= ' - '.$arraylist[$actionstatic->code]; // Use code in priority on type_code
				}
			}
			$out .= '<td class="tdoverflowmax125" title="'.$labeltype.'">';
			$out .= $actionstatic->getTypePicto();
			//if (empty($conf->dol_optimize_smallscreen)) {
			$out .= $labeltype;
			//}
			$out .= '</td>';

			// Title/Label of event
			$out .= '<td class="tdoverflowmax300"';
			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'action') {
				$transcode = $langs->trans("Action".$histo[$key]['acode']);
				//$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : $histo[$key]['alabel']);
				$libelle = $histo[$key]['note'];
				$actionstatic->id = $histo[$key]['id'];
				$out .= ' title="'.dol_escape_htmltag($libelle).'">';
				$out .= dol_trunc($libelle, 120);
			}
			if (isset($histo[$key]['type']) && $histo[$key]['type'] == 'mailing') {
				$out .= '<a href="'.DOL_URL_ROOT.'/comm/mailing/card.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"), "email").' ';
				$transcode = $langs->trans("Action".$histo[$key]['acode']);
				$libelle = ($transcode != "Action".$histo[$key]['acode'] ? $transcode : 'Send mass mailing');
				$out .= ' title="'.dol_escape_htmltag($libelle).'">';
				$out .= dol_trunc($libelle, 120);
			}
			$out .= '</td>';

			// Date
			$out .= '<td class="center nowraponall">';
			$out .= dol_print_date($histo[$key]['datestart'], 'dayhour', 'tzuserrel');
			if ($histo[$key]['dateend'] && $histo[$key]['dateend'] != $histo[$key]['datestart']) {
				$tmpa = dol_getdate($histo[$key]['datestart'], true);
				$tmpb = dol_getdate($histo[$key]['dateend'], true);
				if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) {
					$out .= '-'.dol_print_date($histo[$key]['dateend'], 'hour', 'tzuserrel');
				} else {
					$out .= '-'.dol_print_date($histo[$key]['dateend'], 'dayhour', 'tzuserrel');
				}
			}
			$late = 0;
			if ($histo[$key]['percent'] == 0 && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] == 0 && !$histo[$key]['datestart'] && $histo[$key]['dateend'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && $histo[$key]['dateend'] && $histo[$key]['dateend'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($histo[$key]['percent'] > 0 && $histo[$key]['percent'] < 100 && !$histo[$key]['dateend'] && $histo[$key]['datestart'] && $histo[$key]['datestart'] < ($now - $delay_warning)) {
				$late = 1;
			}
			if ($late) {
				$out .= img_warning($langs->trans("Late")).' ';
			}
			$out .= "</td>\n";

			// Status
			$out .= '<td class="nowrap center">'.$actionstatic->LibStatut($histo[$key]['percent'], 2, 0, $histo[$key]['datestart']).'</td>';
			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				$out .= '<td></td>';
			}

			$out .= "</tr>\n";
			$i++;
		}
		if (empty($histo)) {
			$colspan = 9;
			$out .= '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
		}

		$out .= "</table>\n";
		$out .= "</div>\n";

		$out .= '</form>';
	}

	if ($noprint) {
		return $out;
	} else {
		print $out;
	}
}
