<?php
/* Copyright (C) 2007-2020	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2017       Frédéric France     <frederic.france@free.fr>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "products", "admin", "sms", "other", "errors"));

if (!$user->admin) accessforbidden();

$id = GETPOST('rowid', 'int');
$action = GETPOST('action', 'alpha');

$langcode = GETPOST('langcode', 'alphanohtml');
$transkey = GETPOST('transkey', 'alphanohtml');
$transvalue = GETPOST('transvalue', 'none');


$mode = GETPOST('mode', 'aZ09') ?GETPOST('mode', 'aZ09') : 'searchkey';

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'lang,transkey';
if (!$sortorder) $sortorder = 'ASC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admintranslation', 'globaladmin'));


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
    $transkey = '';
    $transvalue = '';
    $toselect = '';
    $search_array_options = array();
}

if ($action == 'setMAIN_ENABLE_OVERWRITE_TRANSLATION')
{
    if (GETPOST('value')) dolibarr_set_const($db, 'MAIN_ENABLE_OVERWRITE_TRANSLATION', 1, 'chaine', 0, '', $conf->entity);
    else dolibarr_set_const($db, 'MAIN_ENABLE_OVERWRITE_TRANSLATION', 0, 'chaine', 0, '', $conf->entity);
}

if ($action == 'update')
{
	if ($transvalue == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NewTranslationStringToShow")), null, 'errors');
		$error++;
	}
	if (!$error)
	{
		$db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."overwrite_trans set transvalue = '".$db->escape($transvalue)."' WHERE rowid = ".GETPOST('rowid', 'int');
		$result = $db->query($sql);
		if ($result > 0)
		{
			$db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$action = "";
			$transkey = "";
			$transvalue = "";
		}
		else
		{
			$db->rollback();
			if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				setEventMessages($langs->trans("WarningAnEntryAlreadyExistForTransKey"), null, 'warnings');
			}
			else
			{
				setEventMessages($db->lasterror(), null, 'errors');
			}
			$action = '';
		}
	}
}

if ($action == 'add')
{
	$error = 0;

	if (empty($langcode))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Language")), null, 'errors');
		$error++;
	}
	if ($transkey == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Key")), null, 'errors');
		$error++;
	}
	if ($transvalue == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NewTranslationStringToShow")), null, 'errors');
		$error++;
	}
	if (!$error)
	{
	    $db->begin();

	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."overwrite_trans(lang, transkey, transvalue, entity) VALUES ('".$db->escape($langcode)."','".$db->escape($transkey)."','".$db->escape($transvalue)."', ".$db->escape($conf->entity).")";
		$result = $db->query($sql);
		if ($result > 0)
		{
		    $db->commit();
		    setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$action = "";
			$transkey = "";
			$transvalue = "";
		}
		else
		{
		    $db->rollback();
		    if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
		    {
		        setEventMessages($langs->trans("WarningAnEntryAlreadyExistForTransKey"), null, 'warnings');
		    }
		    else
		    {
		        setEventMessages($db->lasterror(), null, 'errors');
            }
			$action = '';
		}
	}
}

// Delete line from delete picto
if ($action == 'delete')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."overwrite_trans WHERE rowid = ".$db->escape($id);
	$result = $db->query($sql);
	if ($result >= 0)
	{
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	}
	else
	{
		dol_print_error($db);
	}
}





/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

$wikihelp = 'EN:Setup Translation|FR:Paramétrage traduction|ES:Configuración';
llxHeader('', $langs->trans("Setup"), $wikihelp);

$param = '&mode='.$mode;

$enabledisablehtml = '';
$enabledisablehtml .= $langs->trans("EnableOverwriteTranslation").' ';
if (empty($conf->global->MAIN_ENABLE_OVERWRITE_TRANSLATION))
{
    // Button off, click to enable
    $enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMAIN_ENABLE_OVERWRITE_TRANSLATION&value=1'.$param.'">';
    $enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
    $enabledisablehtml .= '</a>';
}
else
{
    // Button on, click to disable
    $enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMAIN_ENABLE_OVERWRITE_TRANSLATION&value=0'.$param.'">';
    $enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
    $enabledisablehtml .= '</a>';
}

print load_fiche_titre($langs->trans("Translation"), $enabledisablehtml, 'title_setup');

//print '<span class="opacitymedium">'.$langs->trans("TranslationDesc")."</span><br>\n";
//print "<br>\n";

$current_language_code = $langs->defaultlang;
$s = picto_from_langcode($current_language_code);
print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("CurrentUserLanguage").': <strong>'.$s.' '.$current_language_code.'</strong>', $langs->trans("TranslationDesc")).'</span><br>';

print '<br>';

if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
if ($optioncss != '') $param .= '&optioncss='.urlencode($optioncss);
if ($langcode)        $param .= '&langcode='.urlencode($langcode);
if ($transkey)        $param .= '&transkey='.urlencode($transkey);
if ($transvalue)      $param .= '&transvalue='.urlencode($transvalue);


print '<form action="'.$_SERVER["PHP_SELF"].((empty($user->entity) && $debug) ? '?debug=1' : '').'" method="POST">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

$head = translation_prepare_head();

dol_fiche_head($head, $mode, '', -1, '');

if ($mode == 'overwrite')
{
	print '<input type="hidden" name="page" value="'.$page.'">';

	$disabled = '';
	if ($action == 'edit' || empty($conf->global->MAIN_ENABLE_OVERWRITE_TRANSLATION)) $disabled = ' disabled="disabled"';
	$disablededit = '';
	if ($action == 'edit' || empty($conf->global->MAIN_ENABLE_OVERWRITE_TRANSLATION)) $disablededit = ' disabled';

	print '<div class="justify"><span class="opacitymedium">';
    print img_info().' '.$langs->trans("SomeTranslationAreUncomplete");
    $urlwikitranslatordoc = 'https://wiki.dolibarr.org/index.php/Translator_documentation';
    print ' ('.$langs->trans("SeeAlso", '<a href="'.$urlwikitranslatordoc.'" target="_blank">'.$langs->trans("Here").'</a>').')<br>';
    print $langs->trans("TranslationOverwriteDesc", $langs->transnoentitiesnoconv("Language"), $langs->transnoentitiesnoconv("Key"), $langs->transnoentitiesnoconv("NewTranslationStringToShow"))."\n";
    print ' ('.$langs->trans("TranslationOverwriteDesc2").').'."<br>\n";
    print '</span></div>';

    print '<br>';


	print '<input type="hidden" name="action" value="'.($action == 'edit' ? 'update' : 'add').'">';
    print '<input type="hidden" id="mode" name="mode" value="'.$mode.'">';

	print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print_liste_field_titre("Language_en_US_es_MX_etc", $_SERVER["PHP_SELF"], 'lang,transkey', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre("Key", $_SERVER["PHP_SELF"], 'transkey', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre("NewTranslationStringToShow", $_SERVER["PHP_SELF"], 'transvalue', '', $param, '', $sortfield, $sortorder);
    //if (! empty($conf->multicompany->enabled) && !$user->entity) print_liste_field_titre("Entity", $_SERVER["PHP_SELF"], 'entity,transkey', '', $param, '', $sortfield, $sortorder);
    print '<td align="center"></td>';
    print "</tr>\n";


    // Line to add new record
    print "\n";

    print '<tr class="oddeven"><td>';
    print $formadmin->select_language(GETPOST('langcode'), 'langcode', 0, null, 1, 0, $disablededit ? 1 : 0, 'maxwidthonsmartphone', 1);
    print '</td>'."\n";
    print '<td>';
    print '<input type="text" class="flat maxwidthonsmartphone"'.$disablededit.' name="transkey" id="transkey" value="'.(!empty($transkey) ? $transkey : "").'">';
    print '</td><td>';
    print '<input type="text" class="quatrevingtpercent"'.$disablededit.' name="transvalue" id="transvalue" value="'.(!empty($transvalue) ? $transvalue : "").'">';
    print '</td>';
    // Limit to superadmin
    /*if (! empty($conf->multicompany->enabled) && !$user->entity)
    {
    	print '<td>';
    	print '<input type="text" class="flat" size="1" name="entity" value="'.$conf->entity.'">';
    	print '</td>';
    	print '<td class="center">';
    }
    else
    {*/
    	print '<td class="center">';
    	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
    //}
    print '<input type="submit" class="button"'.$disabled.' value="'.$langs->trans("Add").'" name="add" title="'.dol_escape_htmltag($langs->trans("YouMustEnabledTranslationOverwriteBefore")).'">';
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
    if ($result)
    {
    	$num = $db->num_rows($result);
    	$i = 0;

    	while ($i < $num)
    	{
    		$obj = $db->fetch_object($result);

    		print "\n";

    		print '<tr class="oddeven">';

    		print '<td>'.$obj->lang.'</td>'."\n";
    		print '<td>'.$obj->transkey.'</td>'."\n";

    		// Value
    		print '<td>';
    		/*print '<input type="hidden" name="const['.$i.'][rowid]" value="'.$obj->rowid.'">';
    		print '<input type="hidden" name="const['.$i.'][lang]" value="'.$obj->lang.'">';
    		print '<input type="hidden" name="const['.$i.'][name]" value="'.$obj->transkey.'">';
    		print '<input type="text" id="value_'.$i.'" class="flat inputforupdate" size="30" name="const['.$i.'][value]" value="'.dol_escape_htmltag($obj->transvalue).'">';
    		*/
    		if ($action == 'edit' && $obj->rowid == GETPOST('rowid', 'int'))
    		{
    			print '<input type="text" class="quatrevingtpercent" name="transvalue" value="'.dol_escape_htmltag($obj->transvalue).'">';
    		}
    		else
    		{
    			print dol_escape_htmltag($obj->transvalue);
    		}
    		print '</td>';

    		print '<td class="center">';
    		if ($action == 'edit' && $obj->rowid == GETPOST('rowid', 'int'))
    		{
    			print '<input type="hidden" class="button" name="rowid" value="'.$obj->rowid.'">';
    			print '<input type="submit" class="button buttongen" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'">';
    			print ' &nbsp; ';
    			print '<input type="submit" class="button buttongen" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
    		}
    		else
    		{
    			print '<a class="reposition editfielda paddingrightonly" href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&action=edit'.((empty($user->entity) && $debug) ? '&debug=1' : '').'">'.img_edit().'</a>';
				print ' &nbsp; ';
    			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&action=delete'.((empty($user->entity) && $debug) ? '&debug=1' : '').'">'.img_delete().'</a>';
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

if ($mode == 'searchkey')
{
    $langcode = GETPOST('langcode') ?GETPOST('langcode') : $langs->defaultlang;

    $newlang = new Translate('', $conf);
    $newlang->setDefaultLang($langcode);

    $newlangfileonly = new Translate('', $conf);
    $newlangfileonly->setDefaultLang($langcode);

    $recordtoshow = array();

    // Search modules dirs
    $modulesdir = dolGetModulesDirs();

    $nbtotaloffiles = 0;
    $nbempty = 0;
    /*var_dump($langcode);
     var_dump($transkey);
     var_dump($transvalue);*/
    if (empty($langcode) || $langcode == '-1') $nbempty++;
    if (empty($transkey)) $nbempty++;
    if (empty($transvalue)) $nbempty++;
    if ($action == 'search' && ($nbempty > 999))    // 999 to disable this
    {
        setEventMessages($langs->trans("WarningAtLeastKeyOrTranslationRequired"), null, 'warnings');
    }
    else
    {
        // Search into dir of modules (the $modulesdir is already a list that loop on $conf->file->dol_document_root)
        $i = 0;
        foreach ($modulesdir as $keydir => $tmpsearchdir)
        {
        	$searchdir = $tmpsearchdir; // $searchdir can be '.../htdocs/core/modules/' or '.../htdocs/custom/mymodule/core/modules/'

        	// Directory of translation files
        	$dir_lang = dirname(dirname($searchdir))."/langs/".$langcode; // The 2 dirname is to go up in dir for 2 levels
        	$dir_lang_osencoded = dol_osencode($dir_lang);

        	$filearray = dol_dir_list($dir_lang_osencoded, 'files', 0, '', '', $sortfield, (strtolower($sortorder) == 'asc' ?SORT_ASC:SORT_DESC), 1);
        	foreach ($filearray as $file)
        	{
				$tmpfile = preg_replace('/.lang/i', '', basename($file['name']));
				$moduledirname = (basename(dirname(dirname($dir_lang))));

				$langkey = $tmpfile;
				if ($i > 0) $langkey .= '@'.$moduledirname;
				//var_dump($i.' - '.$keydir.' - '.$dir_lang_osencoded.' -> '.$moduledirname . ' / ' . $tmpfile.' -> '.$langkey);

				$result = $newlang->load($langkey, 0, 0, '', 0); // Load translation files + database overwrite
				$result = $newlangfileonly->load($langkey, 0, 0, '', 1); // Load translation files only
				if ($result < 0) print 'Failed to load language file '.$tmpfile.'<br>'."\n";
				else $nbtotaloffiles++;
				//print 'After loading lang '.$langkey.', newlang has '.count($newlang->tab_translate).' records<br>'."\n";
        	}
        	$i++;
        }

        // Now search into translation array
        foreach ($newlang->tab_translate as $key => $val)
        {
            if ($transkey && !preg_match('/'.preg_quote($transkey, '/').'/i', $key)) continue;
            if ($transvalue && !preg_match('/'.preg_quote($transvalue, '/').'/i', $val)) continue;
            $recordtoshow[$key] = $val;
        }
    }

    //print '<br>';
    $nbtotalofrecordswithoutfilters = count($newlang->tab_translate);
    $nbtotalofrecords = count($recordtoshow);
    $num = $limit + 1;
    if (($offset + $num) > $nbtotalofrecords) $num = $limit;

    //print 'param='.$param.' $_SERVER["PHP_SELF"]='.$_SERVER["PHP_SELF"].' num='.$num.' page='.$page.' nbtotalofrecords='.$nbtotalofrecords." sortfield=".$sortfield." sortorder=".$sortorder;
    $title = $langs->trans("TranslationKeySearch");
    if ($nbtotalofrecords > 0) $title .= ' <span class="opacitymedium colorblack paddingleft">('.$nbtotalofrecords.' / '.$nbtotalofrecordswithoutfilters.' - '.$nbtotaloffiles.' '.$langs->trans("Files").')</span>';
    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, -1 * $nbtotalofrecords, '', 0, '', '', $limit, 0, 0, 1);

    print '<input type="hidden" id="action" name="action" value="search">';
    print '<input type="hidden" id="mode" name="mode" value="'.$mode.'">';

	print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print_liste_field_titre("Language_en_US_es_MX_etc", $_SERVER["PHP_SELF"], 'lang,transkey', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre("Key", $_SERVER["PHP_SELF"], 'transkey', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre("CurrentTranslationString", $_SERVER["PHP_SELF"], 'transvalue', '', $param, '', $sortfield, $sortorder);
    //if (! empty($conf->multicompany->enabled) && !$user->entity) print_liste_field_titre("Entity", $_SERVER["PHP_SELF"], 'entity,transkey', '', $param, '', $sortfield, $sortorder);
    print '<td align="center"></td>';
    print "</tr>\n";

    // Line to search new record
    print "\n";

    print '<tr class="oddeven"><td>';
    //print $formadmin->select_language($langcode,'langcode',0,null,$langs->trans("All"),0,0,'',1);
    print $formadmin->select_language($langcode, 'langcode', 0, null, 0, 0, 0, 'maxwidthonsmartphone', 1);
    print '</td>'."\n";
    print '<td>';
    print '<input type="text" class="flat maxwidthonsmartphone" name="transkey" value="'.$transkey.'">';
    print '</td><td>';
    print '<input type="text" class="quatrevingtpercent" name="transvalue" value="'.$transvalue.'">';
    // Limit to superadmin
    /*if (! empty($conf->multicompany->enabled) && !$user->entity)
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
    print '<td class="nowrap right">';
    $searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
    print $searchpicto;
    print '</td>';
    print '</tr>';

    if ($sortfield == 'transkey' && strtolower($sortorder) == 'asc') ksort($recordtoshow);
    if ($sortfield == 'transkey' && strtolower($sortorder) == 'desc') krsort($recordtoshow);
    if ($sortfield == 'transvalue' && strtolower($sortorder) == 'asc') asort($recordtoshow);
    if ($sortfield == 'transvalue' && strtolower($sortorder) == 'desc') arsort($recordtoshow);

    // Show result
    $i = 0;
    foreach ($recordtoshow as $key => $val)
    {
        $i++;
        if ($i <= $offset) continue;
        if ($i > ($offset + $limit)) break;
        print '<tr class="oddeven"><td>'.$langcode.'</td><td>'.$key.'</td><td>';
        print dol_escape_htmltag($val);
        print '</td><td class="right nowraponall">';
        if (!empty($newlangfileonly->tab_translate[$key]))
        {
            if ($val != $newlangfileonly->tab_translate[$key])
            {
                // retrieve rowid
                $sql = "SELECT rowid";
                $sql .= " FROM ".MAIN_DB_PREFIX."overwrite_trans";
                $sql .= " WHERE transkey = '".$key."'";
                $sql .= " AND entity IN (".getEntity('overwrite_trans').")";
                dol_syslog("translation::select from table", LOG_DEBUG);
                $result = $db->query($sql);
                if ($result)
                {
                    $obj = $db->fetch_object($result);
                }
                print '<a class="editfielda reposition paddingrightonly" href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$conf->entity.'&action=edit">'.img_edit().'</a>';
                print ' ';
                print '<a href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$conf->entity.'&action=delete">'.img_delete().'</a>';
                print '&nbsp;&nbsp;';
                $htmltext = $langs->trans("OriginalValueWas", '<i>'.$newlangfileonly->tab_translate[$key].'</i>');
                print $form->textwithpicto('', $htmltext, 1, 'info');
            }
            elseif (!empty($conf->global->MAIN_ENABLE_OVERWRITE_TRANSLATION))
            {
            	//print $key.'-'.$val;
                print '<a class="reposition paddingrightonly" href="'.$_SERVER['PHP_SELF'].'?mode=overwrite&amp;langcode='.$langcode.'&amp;transkey='.$key.'">'.img_edit_add($langs->trans("Overwrite")).'</a>';
            }

            if (!empty($conf->global->MAIN_FEATURES_LEVEL))
            {
            	$transifexlangfile = '$'; // $ means 'All'
            	//$transifexurl = 'https://www.transifex.com/dolibarr-association/dolibarr/translate/#'.$langcode.'/'.$transifexlangfile.'?key='.$key;
            	$transifexurl = 'https://www.transifex.com/dolibarr-association/dolibarr/translate/#'.$langcode.'/'.$transifexlangfile.'?q=key%3A'.$key;

            	print ' &nbsp; <a href="'.$transifexurl.'" target="transifex">'.img_picto($langs->trans('FixOnTransifex'), 'globe').'</a>';
            }
        }
        else
        {
            $htmltext = $langs->trans("TransKeyWithoutOriginalValue", $key);
            print $form->textwithpicto('', $htmltext, 1, 'warning');
        }
        /*if (! empty($conf->multicompany->enabled) && !$user->entity)
        {
            print '<td>'.$val.'</td>';
        }*/
        print '</td></tr>'."\n";
    }

    print '</table>';
    print '</div>';
}

dol_fiche_end();

print "</form>\n";

if (!empty($langcode))
{
	dol_set_focus('#transvalue');
}

// End of page
llxFooter();
$db->close();
