<?php
/* Copyright (C) 2007-2020	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2017       Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 */

/**
 *       \file       htdocs/admin/translation.php
 *       \brief      Page to show translation information
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "products", "admin", "sms", "other", "errors"));

if (!$user->admin) {
	accessforbidden();
}

$id = GETPOSTINT('rowid');
$action = GETPOST('action', 'aZ09');
$optioncss = GETPOST('optionscss', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ09');

$mode = GETPOST('mode', 'aZ09') ? GETPOST('mode', 'aZ09') : 'searchkey';

$langcode = GETPOST('langcode', 'alphanohtml');
$transkey = GETPOST('transkey', 'alphanohtml');
if ($mode == 'searchkey') {
	$transvalue = GETPOST('transvalue', 'alphanohtml');
} else {
	$transvalue = GETPOST('transvalue', 'restricthtml');
}

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'lang,transkey';
}
if (!$sortorder) {
	$sortorder = 'ASC,ASC';
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('admintranslation', 'globaladmin'));


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && !empty($massaction) && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$transkey = '';
	$transvalue = '';
	$toselect = array();
	$search_array_options = array();
}

if ($action == 'setMAIN_ENABLE_OVERWRITE_TRANSLATION') {
	if (GETPOST('value')) {
		dolibarr_set_const($db, 'MAIN_ENABLE_OVERWRITE_TRANSLATION', 1, 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_set_const($db, 'MAIN_ENABLE_OVERWRITE_TRANSLATION', 0, 'chaine', 0, '', $conf->entity);
	}
}

if ($action == 'update') {
	if ($transkey == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TranslationKey")), null, 'errors');
		$error++;
	}
	if ($transvalue == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NewTranslationStringToShow")), null, 'errors');
		$error++;
	}
	if (!$error) {
		$db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."overwrite_trans set transkey = '".$db->escape($transkey)."', transvalue = '".$db->escape($transvalue)."' WHERE rowid = ".(GETPOSTINT('rowid'));
		$result = $db->query($sql);
		if ($result) {
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$action = "";
			$transkey = "";
			$transvalue = "";
		} else {
			$db->rollback();
			if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				setEventMessages($langs->trans("WarningAnEntryAlreadyExistForTransKey"), null, 'warnings');
			} else {
				setEventMessages($db->lasterror(), null, 'errors');
			}
			$action = '';
		}
	}
}

if ($action == 'add') {
	$error = 0;

	if (empty($langcode)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Language")), null, 'errors');
		$error++;
	}
	if ($transkey == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TranslationKey")), null, 'errors');
		$error++;
	}
	if ($transvalue == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NewTranslationStringToShow")), null, 'errors');
		$error++;
	}
	if (!$error) {
		$db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."overwrite_trans(lang, transkey, transvalue, entity) VALUES ('".$db->escape($langcode)."','".$db->escape($transkey)."','".$db->escape($transvalue)."', ".((int) $conf->entity).")";
		$result = $db->query($sql);
		if ($result) {
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$action = "";
			$transkey = "";
			$transvalue = "";
		} else {
			$db->rollback();
			if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				setEventMessages($langs->trans("WarningAnEntryAlreadyExistForTransKey"), null, 'warnings');
			} else {
				setEventMessages($db->lasterror(), null, 'errors');
			}
			$action = '';
		}
	}
}

// Delete line from delete picto
if ($action == 'delete') {
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."overwrite_trans WHERE rowid = ".((int) $id);
	$result = $db->query($sql);
	if ($result) {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	} else {
		dol_print_error($db);
	}
}





/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

$wikihelp = 'EN:Setup_Translation|FR:Paramétrage_Traduction|ES:Configuración_Traducción';
llxHeader('', $langs->trans("Setup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-translation');

$param = '&mode='.urlencode($mode);

$enabledisablehtml = '';
$enabledisablehtml .= $langs->trans("EnableOverwriteTranslation").' ';
if (!getDolGlobalString('MAIN_ENABLE_OVERWRITE_TRANSLATION')) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMAIN_ENABLE_OVERWRITE_TRANSLATION&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMAIN_ENABLE_OVERWRITE_TRANSLATION&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}

print load_fiche_titre($langs->trans("Translation"), $enabledisablehtml, 'title_setup');

$current_language_code = $langs->defaultlang;
$s = picto_from_langcode($current_language_code);
print $form->textwithpicto('<span class="opacitymedium">'.$langs->trans("CurrentUserLanguage").':</span> <strong>'.$s.' '.$current_language_code.'</strong>', $langs->trans("TranslationDesc")).'</span><br>';

print '<br>';

if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if (isset($optioncss) && $optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($langcode) {
	$param .= '&langcode='.urlencode($langcode);
}
if ($transkey) {
	$param .= '&transkey='.urlencode($transkey);
}
if ($transvalue) {
	$param .= '&transvalue='.urlencode($transvalue);
}


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
if (isset($optioncss) && $optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

$head = translation_prepare_head();

print dol_get_fiche_head($head, $mode, '', -1, '');


$langcode = GETPOSTISSET('langcode') ? GETPOST('langcode') : $langs->defaultlang;

$newlang = new Translate('', $conf);
$newlang->setDefaultLang($langcode);

$langsenfileonly = new Translate('', $conf);
$langsenfileonly->setDefaultLang('en_US');

$newlangfileonly = new Translate('', $conf);
$newlangfileonly->setDefaultLang($langcode);

$recordtoshow = array();

// Search modules dirs
$modulesdir = dolGetModulesDirs();

$listoffiles = array();
$listoffilesexternalmodules = array();

// Search into dir of modules (the $modulesdir is already a list that loop on $conf->file->dol_document_root)
$i = 0;
foreach ($modulesdir as $keydir => $tmpsearchdir) {
	$searchdir = $tmpsearchdir; // $searchdir can be '.../htdocs/core/modules/' or '.../htdocs/custom/mymodule/core/modules/'

	// Directory of translation files
	$dir_lang = dirname(dirname($searchdir))."/langs/".$langcode; // The 2 dirname is to go up in dir for 2 levels
	$dir_lang_osencoded = dol_osencode($dir_lang);

	$filearray = dol_dir_list($dir_lang_osencoded, 'files', 0, '', '', "name", SORT_ASC, 1);

	foreach ($filearray as $file) {
		$tmpfile = preg_replace('/.lang/i', '', basename($file['name']));
		$moduledirname = (basename(dirname(dirname($dir_lang))));

		$langkey = $tmpfile;
		if ($i > 0) {
			$langkey .= '@'.$moduledirname;
		}
		//var_dump($i.' - '.$keydir.' - '.$dir_lang_osencoded.' -> '.$moduledirname . ' / ' . $tmpfile.' -> '.$langkey);

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$result = $newlang->load($langkey, 0, 0, '', 0); // Load translation files + database overwrite
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$result = $newlangfileonly->load($langkey, 0, 0, '', 1); // Load translation files only
		if ($result < 0) {
			print 'Failed to load language file '.$tmpfile.'<br>'."\n";
		} else {
			$listoffiles[$langkey] = $tmpfile;
			if (strpos($langkey, '@') !== false) {
				$listoffilesexternalmodules[$langkey] = $tmpfile;
			}
		}
		//print 'After loading lang '.$langkey.', newlang has '.count($newlang->tab_translate).' records<br>'."\n";

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$result = $langsenfileonly->load($langkey, 0, 0, '', 1); // Load translation files only
	}
	$i++;
}

$nbtotaloffiles = count($listoffiles);
$nbtotaloffilesexternal = count($listoffilesexternalmodules);

if ($mode == 'overwrite') {
	print '<input type="hidden" name="page" value="'.$page.'">';

	$disabled = '';
	if ($action == 'edit' || !getDolGlobalString('MAIN_ENABLE_OVERWRITE_TRANSLATION')) {
		$disabled = ' disabled="disabled"';
	}
	$disablededit = '';
	if ($action == 'edit' || !getDolGlobalString('MAIN_ENABLE_OVERWRITE_TRANSLATION')) {
		$disablededit = ' disabled';
	}

	print '<div class="justify"><span class="opacitymedium">';
	print img_info().' '.$langs->trans("SomeTranslationAreUncomplete");
	$urlwikitranslatordoc = 'https://wiki.dolibarr.org/index.php/Translator_documentation';
	print ' ('.str_replace('{s1}', '<a href="'.$urlwikitranslatordoc.'" target="_blank" rel="noopener noreferrer external">'.$langs->trans("Here").'</a>', $langs->trans("SeeAlso", '{s1}')).')<br>';
	print $langs->trans("TranslationOverwriteDesc", $langs->transnoentitiesnoconv("Language"), $langs->transnoentitiesnoconv("TranslationKey"), $langs->transnoentitiesnoconv("NewTranslationStringToShow"))."\n";
	print ' ('.$langs->trans("TranslationOverwriteDesc2").').'."<br>\n";
	print '</span></div>';

	print '<br>';


	print '<input type="hidden" name="action" value="'.($action == 'edit' ? 'update' : 'add').'">';
	print '<input type="hidden" id="mode" name="mode" value="'.$mode.'">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Language_en_US_es_MX_etc", $_SERVER["PHP_SELF"], 'lang,transkey', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("TranslationKey", $_SERVER["PHP_SELF"], 'transkey', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("NewTranslationStringToShow", $_SERVER["PHP_SELF"], 'transvalue', '', $param, '', $sortfield, $sortorder);
	//if (isModEnabled('multicompany') && !$user->entity) print_liste_field_titre("Entity", $_SERVER["PHP_SELF"], 'entity,transkey', '', $param, '', $sortfield, $sortorder);
	print '<td align="center"></td>';
	print "</tr>\n";


	// Line to add new record
	print "\n";

	print '<tr class="oddeven"><td>';
	print $formadmin->select_language(GETPOST('langcode'), 'langcode', 0, null, 1, 0, $disablededit ? 1 : 0, 'maxwidth250', 1);
	print '</td>'."\n";
	print '<td>';
	print '<input type="text" class="flat maxwidthonsmartphone"'.$disablededit.' name="transkey" id="transkey" value="'.(!empty($transkey) ? $transkey : "").'">';
	print '</td><td>';
	print '<input type="text" class="quatrevingtpercent"'.$disablededit.' name="transvalue" id="transvalue" value="'.(!empty($transvalue) ? $transvalue : "").'">';
	print '</td>';
	print '<td class="center">';
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
	print '<input type="submit" class="button"'.$disabled.' value="'.$langs->trans("Add").'" name="add" title="'.dol_escape_htmltag($langs->trans("YouMustEnableTranslationOverwriteBefore")).'">';
	print "</td>\n";
	print '</tr>';


	// Show constants
	$sql = "SELECT rowid, entity, lang, transkey, transvalue";
	$sql .= " FROM ".MAIN_DB_PREFIX."overwrite_trans";
	$sql .= " WHERE 1 = 1";
	$sql .= " AND entity IN (".getEntity('overwrite_trans').")";
	$sql .= $db->order($sortfield, $sortorder);

	dol_syslog("translation::select from table", LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$i = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($result);

			print "\n";

			print '<tr class="oddeven">';

			print '<td>'.dol_escape_htmltag($obj->lang).'</td>'."\n";
			print '<td>';
			if ($action == 'edit' && $obj->rowid == GETPOSTINT('rowid')) {
				print '<input type="text" class="quatrevingtpercent" name="transkey" value="'.dol_escape_htmltag($obj->transkey).'">';
			} else {
				print dol_escape_htmltag($obj->transkey);
			}
			print '</td>'."\n";

			// Value
			print '<td class="small">';
			/*print '<input type="hidden" name="const['.$i.'][rowid]" value="'.$obj->rowid.'">';
			print '<input type="hidden" name="const['.$i.'][lang]" value="'.$obj->lang.'">';
			print '<input type="hidden" name="const['.$i.'][name]" value="'.$obj->transkey.'">';
			print '<input type="text" id="value_'.$i.'" class="flat inputforupdate" size="30" name="const['.$i.'][value]" value="'.dol_escape_htmltag($obj->transvalue).'">';
			*/
			if ($action == 'edit' && $obj->rowid == GETPOSTINT('rowid')) {
				print '<input type="text" class="quatrevingtpercent" name="transvalue" value="'.dol_escape_htmltag($obj->transvalue).'">';
			} else {
				//print $obj->transkey.' '.$langsenfileonly->tab_translate[$obj->transkey];
				$titleforvalue = $langs->trans("Translation").' en_US for key '.$obj->transkey.':<br>'.(!empty($langsenfileonly->tab_translate[$obj->transkey]) ? $langsenfileonly->trans($obj->transkey) : '<span class="opacitymedium">'.$langs->trans("None").'</span>');
				/*if ($obj->lang != 'en_US') {
					$titleforvalue .= '<br>'.$langs->trans("Translation").' '.$obj->lang.' '...;
				}*/
				print '<span title="'.dol_escape_htmltag($titleforvalue).'" class="classfortooltip">';
				print dol_escape_htmltag($obj->transvalue);
				print '</span>';
			}
			print '</td>';

			print '<td class="center">';
			if ($action == 'edit' && $obj->rowid == GETPOSTINT('rowid')) {
				print '<input type="hidden" class="button" name="rowid" value="'.$obj->rowid.'">';
				print '<input type="submit" class="button buttongen button-save" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
				print ' &nbsp; ';
				print '<input type="submit" class="button buttongen button-cancel" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
			} else {
				print '<a class="reposition editfielda paddingrightonly" href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&mode='.urlencode($mode).'&action=edit&token='.newToken().'">'.img_edit().'</a>';
				print ' &nbsp; ';
				print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&mode='.urlencode($mode).'&action=delete&token='.newToken().'">'.img_delete().'</a>';
			}
			print '</td>';

			print "</tr>\n";
			print "\n";
			$i++;
		}
	}

	print '</table>';
	print '</div>';
}

if ($mode == 'searchkey') {
	$nbempty = 0;
	//var_dump($langcode);
	//var_dump($transkey);
	//var_dump($transvalue);
	if (empty($langcode) || $langcode == '-1') {
		$nbempty++;
	}
	if (empty($transkey)) {
		$nbempty++;
	}
	if (empty($transvalue)) {
		$nbempty++;
	}

	if ($action == 'search' && ($nbempty > 999)) {    // 999 to disable this
		setEventMessages($langs->trans("WarningAtLeastKeyOrTranslationRequired"), null, 'warnings');
	} else {
		// Now search into translation array
		foreach ($newlang->tab_translate as $key => $val) {
			$newtranskey = preg_replace('/\$$/', '', preg_replace('/^\^/', '', $transkey));
			$newtranskeystart = preg_match('/^\^/', $transkey);
			$newtranskeyend = preg_match('/\$$/', $transkey);
			$regexstring = ($newtranskeystart ? '^' : '').preg_quote($newtranskey, '/').($newtranskeyend ? '$' : '');
			if ($transkey && !preg_match('/'.$regexstring.'/i', $key)) {
				continue;
			}
			if ($transvalue && !preg_match('/'.preg_quote($transvalue, '/').'/i', $val)) {
				continue;
			}
			$recordtoshow[$key] = $val;
		}
	}

	//print '<br>';
	$nbtotalofrecordswithoutfilters = count($newlang->tab_translate);
	$nbtotalofrecords = count($recordtoshow);
	$num = $limit + 1;
	if (($offset + $num) > $nbtotalofrecords) {
		$num = $limit;
	}

	//print 'param='.$param.' $_SERVER["PHP_SELF"]='.$_SERVER["PHP_SELF"].' num='.$num.' page='.$page.' nbtotalofrecords='.$nbtotalofrecords." sortfield=".$sortfield." sortorder=".$sortorder;
	$title = $langs->trans("Translation");
	if ($nbtotalofrecords > 0) {
		$title .= ' <span class="opacitymedium colorblack paddingleft">('.$nbtotalofrecords.' / '.$nbtotalofrecordswithoutfilters.' - <span title="'.dol_escape_htmltag(($nbtotaloffiles - $nbtotaloffilesexternal).' core - '.($nbtotaloffilesexternal).' external').'">'.$nbtotaloffiles.' '.$langs->trans("Files").'</span>)</span>';
	}
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, -1 * $nbtotalofrecords, '', 0, '', '', $limit, 0, 0, 1);

	$massactionbutton = '';

	print '<input type="hidden" id="action" name="action" value="search">';
	print '<input type="hidden" id="mode" name="mode" value="'.$mode.'">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre_filter"><td>';
	//print $formadmin->select_language($langcode,'langcode',0,null,$langs->trans("All"),0,0,'',1);
	print $formadmin->select_language($langcode, 'langcode', 0, null, 0, 0, 0, 'maxwidth250', 1);
	print '</td>'."\n";
	print '<td>';
	print '<input type="text" class="flat maxwidthonsmartphone" name="transkey" value="'.dol_escape_htmltag($transkey).'">';
	print '</td><td>';
	print '<input type="text" class="quatrevingtpercent" name="transvalue" value="'.dol_escape_htmltag($transvalue).'">';
	// Limit to superadmin
	/*if (isModEnabled('multicompany') && !$user->entity)
	{
		print '</td><td>';
		print '<input type="text" class="flat" size="1" name="entitysearch" value="'.$conf->entity.'">';
	}
	else
	{*/
	print '<input type="hidden" name="entitysearch" value="'.$conf->entity.'">';
	//}
	print '</td>';
	// Action column
	print '<td class="right nowraponall">';
	$searchpicto = $form->showFilterAndCheckAddButtons(!empty($massactionbutton) ? 1 : 0, 'checkforselect', 1);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre("Language_en_US_es_MX_etc", $_SERVER["PHP_SELF"], 'lang,transkey', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("TranslationKey", $_SERVER["PHP_SELF"], 'transkey', '', $param, '', $sortfield, $sortorder);
	print_liste_field_titre("CurrentTranslationString", $_SERVER["PHP_SELF"], 'transvalue', '', $param, '', $sortfield, $sortorder);
	//if (isModEnabled('multicompany') && !$user->entity) print_liste_field_titre("Entity", $_SERVER["PHP_SELF"], 'entity,transkey', '', $param, '', $sortfield, $sortorder);
	print '<td align="center"></td>';
	print "</tr>\n";


	if ($sortfield == 'transkey' && strtolower($sortorder) == 'asc') {
		ksort($recordtoshow);
	}
	if ($sortfield == 'transkey' && strtolower($sortorder) == 'desc') {
		krsort($recordtoshow);
	}
	if ($sortfield == 'transvalue' && strtolower($sortorder) == 'asc') {
		asort($recordtoshow);
	}
	if ($sortfield == 'transvalue' && strtolower($sortorder) == 'desc') {
		arsort($recordtoshow);
	}

	// Show result
	$i = 0;
	foreach ($recordtoshow as $key => $val) {
		$i++;
		if ($i <= $offset) {
			continue;
		}
		if ($limit && $i > ($offset + $limit)) {
			break;
		}
		print '<tr class="oddeven"><td>'.$langcode.'</td><td>'.$key.'</td><td class="small">';
		$titleforvalue = $langs->trans("Translation").' en_US for key '.$key.':<br>'.(!empty($langsenfileonly->tab_translate[$key]) ? $langsenfileonly->trans($key) : '<span class="opacitymedium">'.$langs->trans("None").'</span>');
		print '<span title="'.dol_escape_htmltag($titleforvalue).'" class="classfortooltip">';
		print dol_escape_htmltag($val);
		print '</span>';
		print '</td>';
		print '<td class="right nowraponall">';
		if (!empty($newlangfileonly->tab_translate[$key])) {
			if ($val != $newlangfileonly->tab_translate[$key]) {
				// retrieve rowid
				$sql = "SELECT rowid";
				$sql .= " FROM ".MAIN_DB_PREFIX."overwrite_trans";
				$sql .= " WHERE entity IN (".getEntity('overwrite_trans').")";
				$sql .= " AND transkey = '".$db->escape($key)."'";
				dol_syslog("translation::select from table", LOG_DEBUG);
				$result = $db->query($sql);
				if ($result) {
					$obj = $db->fetch_object($result);
				}
				print '<a class="editfielda reposition marginrightonly" href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$conf->entity.'&mode=overwrite&action=edit&token='.newToken().'">'.img_edit().'</a>';
				print ' ';
				print '<a class="marginleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$conf->entity.'&mode='.urlencode($mode).'&action=delete&token='.newToken().'&mode='.urlencode($mode).'">'.img_delete().'</a>';
				print '&nbsp;&nbsp;';
				$htmltext = $langs->trans("OriginalValueWas", '<i>'.$newlangfileonly->tab_translate[$key].'</i>');
				print $form->textwithpicto('', $htmltext, 1, 'info');
			} elseif (getDolGlobalString('MAIN_ENABLE_OVERWRITE_TRANSLATION')) {
				//print $key.'-'.$val;
				print '<a class="reposition paddingrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=overwrite&langcode='.urlencode($langcode).'&transkey='.urlencode($key).'">'.img_edit_add($langs->trans("TranslationOverwriteKey")).'</a>';
			}

			if (getDolGlobalInt('MAIN_FEATURES_LEVEL')) {
				$transifexlangfile = '$'; // $ means 'All'
				//$transifexurl = 'https://www.transifex.com/dolibarr-association/dolibarr/translate/#'.$langcode.'/'.$transifexlangfile.'?key='.$key;
				$transifexurl = 'https://www.transifex.com/dolibarr-association/dolibarr/translate/#'.$langcode.'/'.$transifexlangfile.'?q=key%3A'.$key;

				print ' &nbsp; <a href="'.$transifexurl.'" target="transifex">'.img_picto($langs->trans('FixOnTransifex'), 'globe').'</a>';
			}
		} else {
			// retrieve rowid
			$sql = "SELECT rowid";
			$sql .= " FROM ".MAIN_DB_PREFIX."overwrite_trans";
			$sql .= " WHERE entity IN (".getEntity('overwrite_trans').")";
			$sql .= " AND transkey = '".$db->escape($key)."'";
			dol_syslog("translation::select from table", LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$obj = $db->fetch_object($result);
			}
			print '<a class="editfielda reposition marginrightonly" href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$conf->entity.'&mode=overwrite&action=edit&token='.newToken().'">'.img_edit().'</a>';
			print ' ';
			print '<a class="marginleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$conf->entity.'&mode='.urlencode($mode).'&action=delete&token='.newToken().'&mode='.urlencode($mode).'">'.img_delete().'</a>';
			print '&nbsp;&nbsp;';

			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$htmltext = $langs->trans("TransKeyWithoutOriginalValue", $key);
			print $form->textwithpicto('', $htmltext, 1, 'warning');
		}
		/*if (isModEnabled('multicompany') && !$user->entity)
		{
			print '<td>'.$val.'</td>';
		}*/
		print '</td></tr>'."\n";
	}

	if (empty($recordtoshow)) {
		print '<tr><td colspan="4"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print '</table>';
	print '</div>';
}

print dol_get_fiche_end();

print "</form>\n";

if (!empty($langcode)) {
	dol_set_focus('#transvalue');
}

// End of page
llxFooter();
$db->close();
