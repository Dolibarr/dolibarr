<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *   	\file       htdocs/modulebuilder/template/myobject_list.php
 *		\ingroup    mymodule
 *		\brief      List page for myobject
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB','1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER','1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC','1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN','1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION','1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK','1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL','1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK','1');				// Do not check style html tag into posted data
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');						// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include substr($tmp, 0, ($i+1))."/main.inc.php";
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include dirname(substr($tmp, 0, ($i+1)))."/main.inc.php";
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include "../main.inc.php";
if (! $res && file_exists("../../main.inc.php")) $res=@include "../../main.inc.php";
if (! $res && file_exists("../../../main.inc.php")) $res=@include "../../../main.inc.php";
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/mymodule/class/myobject.class.php');

// Load translation files required by the page
$langs->loadLangs(array("mymodule@mymodule","other"));

$action     = GETPOST('action','aZ09')?GETPOST('action','aZ09'):'view';				// The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction','alpha');											// The bulk action (combo box choice into lists)
$show_files = GETPOST('show_files','int');												// Show files area generated by bulk actions ?
$confirm    = GETPOST('confirm','alpha');												// Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha');												// We click on a Cancel button
$toselect   = GETPOST('toselect', 'array');												// Array of ids of elements selected into a list
$contextpage= GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'myobjectlist';   // To manage different context of search
$backtopage = GETPOST('backtopage','alpha');											// Go back to a dedicated page
$optioncss  = GETPOST('optioncss','aZ');												// Option for the css output (always '' except when 'print')

$id			= GETPOST('id','int');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1 || GETPOST('button_search','alpha') || GETPOST('button_removefilter','alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
//if (! $sortfield) $sortfield="p.date_fin";
//if (! $sortorder) $sortorder="DESC";

// Initialize technical objects
$object = new MyObject($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mymodule->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('myobjectlist'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('myobject');	// Load $extrafields->attributes['myobject']
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element,'','search_');

// Default sort order (if not yet defined by previous GETPOST)
if (! $sortfield) $sortfield="t.".key($object->fields);   // Set here default search field. By default 1st field in definition.
if (! $sortorder) $sortorder="ASC";

// Security check
$socid=0;
if ($user->societe_id > 0)	// Protection if external user
{
	//$socid = $user->societe_id;
	accessforbidden();
}
//$result = restrictedArea($user, 'mymodule', $id, '');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all",'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach($object->fields as $key => $val)
{
	if ($val['searchall']) $fieldstosearchall['t.'.$key]=$val['label'];
}

// Definition of fields for list
$arrayfields=array();
foreach($object->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (! empty($val['visible'])) $arrayfields['t.'.$key]=array('label'=>$val['label'], 'checked'=>(($val['visible']<0)?0:1), 'enabled'=>$val['enabled'], 'position'=>$val['position']);
}
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (! empty($extrafields->attributes[$object->table_element]['list'][$key]))
			$arrayfields["ef.".$key]=array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key]<0)?0:1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key])!=3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');



/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{
		foreach($object->fields as $key => $val)
		{
			$search[$key]='';
		}
		$toselect='';
		$search_array_options=array();
	}
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')
		|| GETPOST('button_search_x','alpha') || GETPOST('button_search.x','alpha') || GETPOST('button_search','alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass='MyObject';
	$objectlabel='MyObject';
	$permtoread = $user->rights->mymodule->read;
	$permtodelete = $user->rights->mymodule->delete;
	$uploaddir = $conf->mymodule->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$form=new Form($db);

$now=dol_now();

//$help_url="EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url='';
$title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("MyObjects"));


// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT ';
foreach($object->fields as $key => $val)
{
	$sql.='t.'.$key.', ';
}
// Add fields from extrafields
if (! empty($extrafields->attributes[$object->table_element]['label']))
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql=preg_replace('/, $/','', $sql);
$sql.= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
if ($object->ismultientitymanaged == 1) $sql.= " WHERE t.entity IN (".getEntity($object->element).")";
else $sql.=" WHERE 1 = 1";
foreach($search as $key => $val)
{
	if ($key == 'status' && $search[$key] == -1) continue;
	$mode_search=(($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key]))?1:0);
	if ($search[$key] != '') $sql.=natural_search($key, $search[$key], (($key == 'status')?2:$mode_search));
}
if ($search_all) $sql.= natural_search(array_keys($fieldstosearchall), $search_all);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters, $object);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

/* If a group by is required
$sql.= " GROUP BY "
foreach($object->fields as $key => $val)
{
	$sql.='t.'.$key.', ';
}
// Add fields from extrafields
if (! empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.', ' : '');
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListGroupBy',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql=preg_replace('/, $/','', $sql);
*/

$sql.=$db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords)
{
	$num = $nbtotalofrecords;
}
else
{
	$sql.= $db->plimit($limit+1, $offset);

	$resql=$db->query($sql);
	if (! $resql)
	{
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

// Direct jump if only one record found
if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".dol_buildpath('/mymodule/myobject_card.php', 1).'?id='.$id);
	exit;
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

// Example : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_myfunc();
	});
});
</script>';

$arrayofselected=is_array($toselect)?$toselect:array();

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
foreach($search as $key => $val)
{
	$param.= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions =  array(
	//'presend'=>$langs->trans("SendByMail"),
	//'builddoc'=>$langs->trans("PDFMerge"),
);
if ($user->rights->mymodule->delete) $arrayofmassactions['predelete']=$langs->trans("Delete");
if (GETPOST('nomassaction','int') || in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$newcardbutton='';
//if ($user->rights->mymodule->creer)
//{
	$newcardbutton='<a class="butActionNew" href="myobject_card.php?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']).'"><span class="valignmiddle">'.$langs->trans('New').'</span>';
	$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
	$newcardbutton.= '</a>';
//}
//else
//{
//    $newcardbutton='<a class="butActionNewRefused" href="#">'.$langs->trans('New');
//    $newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
//    $newcardbutton.= '</a>';
//}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_companies', 0, $newcardbutton, '', $limit);

// Add code for pre mass action (confirmation or email presend form)
$topicmail="SendMyObjectRef";
$modelmail="myobject";
$objecttmp=new MyObject($db);
$trackid='xxxx'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($sall)
{
	foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall).'</div>';
}

$moreforfilter = '';
/*$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
$moreforfilter.= '</div>';*/

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (! empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
$selectedfields.=(count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";


// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach($object->fields as $key => $val)
{
	$cssforfield='';
	if (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
	if (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
	if ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';
	if (! empty($arrayfields['t.'.$key]['checked'])) print '<td class="liste_titre'.($cssforfield?' '.$cssforfield:'').'"><input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'"></td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print '<td class="liste_titre" align="right">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach($object->fields as $key => $val)
{
	$cssforfield='';
	if (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
	if (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
	if ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';
	if (! empty($arrayfields['t.'.$key]['checked']))
	{
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield?'class="'.$cssforfield.'"':''), $sortfield, $sortorder, ($cssforfield?$cssforfield.' ':''))."\n";
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
$reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"],'','','','align="center"',$sortfield,$sortorder,'maxwidthsearch ')."\n";
print '</tr>'."\n";


// Detect if we need a fetch on each output line
$needToFetchEachLine=0;
if (is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val)
	{
		if (preg_match('/\$object/',$val)) $needToFetchEachLine++;  // There is at least one compute field that use $object
	}
}


// Loop on record
// --------------------------------------------------------------------
$i=0;
$totalarray=array();
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break;		// Should not happen

	// Store properties in $object
	$object->id = $obj->rowid;
	foreach($object->fields as $key => $val)
	{
		if (isset($obj->$key)) $object->$key = $obj->$key;
	}

	// Show here line of result
	print '<tr class="oddeven">';
	foreach($object->fields as $key => $val)
	{
	    $cssforfield='';
	    if (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
	    elseif ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';
	    
	    if (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
	    elseif ($key == 'ref') $cssforfield.=($cssforfield?' ':'').'nowrap';
	    
	    if (! empty($arrayfields['t.'.$key]['checked']))
		{
			print '<td';
			if ($cssforfield || $val['css']) print ' class="';
			print $cssforfield;
			if ($cssforfield && $val['css']) print ' ';
			print $val['css'];
			if ($cssforfield || $val['css']) print '"';
			print '>';
			print $object->showOutputField($val, $key, $obj->$key, '');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! empty($val['isameasure']))
			{
				if (! $i) $totalarray['pos'][$totalarray['nbfield']]='t.'.$key;
				$totalarray['val']['t.'.$key] += $obj->$key;
			}
		}
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
	$reshook=$hookmanager->executeHooks('printFieldListValue', $parameters, $object);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="nowrap" align="center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected=0;
		if (in_array($obj->rowid, $arrayofselected)) $selected=1;
		print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
	}
	print '</td>';
	if (! $i) $totalarray['nbfield']++;

	print '</tr>';

	$i++;
}

// Show total line
if (isset($totalarray['pos']))
{
	print '<tr class="liste_total">';
	$i=0;
	while ($i < $totalarray['nbfield'])
	{
		$i++;
		if (! empty($totalarray['pos'][$i]))  print '<td align="right">'.price($totalarray['val'][$totalarray['pos'][$i]]).'</td>';
		else
		{
			if ($i == 1)
			{
				if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
				else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
			}
			else print '<td></td>';
		}
	}
	print '</tr>';
}

// If no record found
if ($num == 0)
{
	$colspan=1;
	foreach($arrayfields as $key => $val) { if (! empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}


$db->free($resql);

$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc',$arrayofmassactions) && ($nbtotalofrecords === '' || $nbtotalofrecords))
{
	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	$filedir=$diroutputmassaction;
	$genallowed=$user->rights->mymodule->read;
	$delallowed=$user->rights->mymodule->create;

	print $formfile->showdocuments('massfilesarea_mymodule','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'','','',null,$hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
