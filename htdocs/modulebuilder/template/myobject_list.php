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

//if (! defined('NOREQUIREUSER'))          define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))            define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))           define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))          define('NOREQUIRETRAN','1');
//if (! defined('NOSCANGETFORINJECTION'))  define('NOSCANGETFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOSCANPOSTFORINJECTION')) define('NOSCANPOSTFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOCSRFCHECK'))            define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))           define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))         define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))          define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))          define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))          define('NOREQUIREAJAX','1');         // Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)


// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/mymodule/class/myobject.class.php');

// Load traductions files requiredby by page
$langs->loadLangs(array("mymodule@mymodule","other"));

$action     = GETPOST('action','alpha');
$massaction = GETPOST('massaction','alpha');
$show_files = GETPOST('show_files','int');
$confirm    = GETPOST('confirm','alpha');
$cancel     = GETPOST('cancel', 'alpha');
$toselect   = GETPOST('toselect', 'array');
$contextpage= GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'myobjectlist';   // To manage different context of search

$id			= GETPOST('id','int');
$backtopage = GETPOST('backtopage');
$optioncss  = GETPOST('optioncss','alpha');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object=new MyObject($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->mymodule->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('myobjectlist'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('myobject');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Default sort order (if not yet defined by previous GETPOST)
if (! $sortfield) $sortfield="t.".key($object->fields);   // Set here default search field. By default 1st field in definition.
if (! $sortorder) $sortorder="ASC";

// Protection if external user
$socid=0;
if ($user->societe_id > 0)
{
    //$socid = $user->societe_id;
	accessforbidden();
}

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
    if (! empty($val['visible'])) $arrayfields['t.'.$key]=array('label'=>$val['label'], 'checked'=>(($val['visible']<0)?0:1), 'enabled'=>$val['enabled']);
}
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
    }
}




/*
 * ACTIONS
 *
 * Put here all code to do according to value of "$action" parameter
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
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
 * VIEW
 *
 * Put here all code to build page
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
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql=preg_replace('/, $/','', $sql);
$sql.= " FROM ".MAIN_DB_PREFIX."myobject as t";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."myobject_extrafields as ef on (t.rowid = ef.fk_object)";
$sql.= " WHERE t.entity IN (".getEntity('myobject').")";
foreach($search as $key => $val)
{
	$mode=(($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key]))?1:0);
	if ($search[$key] != '') $sql.=natural_search($key, $search[$key], (($key == 'status')?2:$mode));
}
if ($search_all) $sql.= natural_search(array_keys($fieldstosearchall), $search_all);
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double','real'))) $mode=1;    // Search on a numeric
    if ($crit != '' && (! in_array($typ, array('select')) || $crit != '0'))
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.=$db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);

dol_syslog($script_file, LOG_DEBUG);
$resql=$db->query($sql);
if (! $resql)
{
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

// Direct jump if only one record found
if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all)
{
    $obj = $db->fetch_object($resql);
    $id = $obj->rowid;
    header("Location: ".DOL_URL_ROOT.'/mymodule/myobject_card.php?id='.$id);
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
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
foreach($search as $key => $val)
{
    $param.= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param.='&optioncss='.$optioncss;
// Add $param from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
}

// List of mass actions available
$arrayofmassactions =  array(
    //'presend'=>$langs->trans("SendByMail"),
    //'builddoc'=>$langs->trans("PDFMerge"),
);
if ($user->rights->mymodule->delete) $arrayofmassactions['delete']=$langs->trans("Delete");
if ($massaction == 'presend') $arrayofmassactions=array();
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

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_companies', 0, '', '', $limit);

if ($sall)
{
    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
    print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
}

$moreforfilter = '';
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
$moreforfilter.= '</div>';

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
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
$selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";


// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach($object->fields as $key => $val)
{
    if (in_array($key, array('date_creation', 'tms', 'import_key', 'status'))) continue;
    $align='';
    if (in_array($val['type'], array('date','datetime','timestamp'))) $align='center';
    if (in_array($val['type'], array('timestamp'))) $align.=' nowrap';
    if ($key == 'status') $align.=($align?' ':'').'center';
    if (! empty($arrayfields['t.'.$key]['checked'])) print '<td class="liste_titre'.($align?' '.$align:'').'"><input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'"></td>';
}
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        if (! empty($arrayfields["ef.".$key]['checked']))
        {
            $align=$extrafields->getAlignFlag($key);
            $typeofextrafield=$extrafields->attribute_type[$key];
            print '<td class="liste_titre'.($align?' '.$align:'').'">';
            if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')) && empty($extrafields->attribute_computed[$key]))
            {
                $crit=$val;
                $tmpkey=preg_replace('/search_options_/','',$key);
                $searchclass='';
                if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
                if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
                print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
            }
            print '</td>';
        }
    }
}
// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Rest of fields search
foreach($object->fields as $key => $val)
{
    if (! in_array($key, array('date_creation', 'tms', 'import_key', 'status'))) continue;
    $align='';
    if (in_array($val['type'], array('date','datetime','timestamp'))) $align='center';
    if (in_array($val['type'], array('timestamp'))) $align.=' nowrap';
    if ($key == 'status') $align.=($align?' ':'').'center';
    if (! empty($arrayfields['t.'.$key]['checked'])) print '<td class="liste_titre'.($align?' '.$align:'').'"><input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'"></td>';
}
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
    if (in_array($key, array('date_creation', 'tms', 'import_key', 'status'))) continue;
    $align='';
    if (in_array($val['type'], array('date','datetime','timestamp'))) $align='center';
    if (in_array($val['type'], array('timestamp'))) $align.='nowrap';
    if ($key == 'status') $align.=($align?' ':'').'center';
    if (! empty($arrayfields['t.'.$key]['checked'])) print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($align?'class="'.$align.'"':''), $sortfield, $sortorder, $align.' ')."\n";
}
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
       if (! empty($arrayfields["ef.".$key]['checked']))
       {
			$align=$extrafields->getAlignFlag($key);
			$sortonfield = "ef.".$key;
			if (! empty($extrafields->attribute_computed[$key])) $sortonfield='';
			print getTitleFieldOfList($langs->trans($extralabels[$key]), 0, $_SERVER["PHP_SELF"], $sortonfield, "", $param, ($align?'align="'.$align.'"':''), $sortfield, $sortorder)."\n";
       }
   }
}
// Hook fields
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Rest of fields title
foreach($object->fields as $key => $val)
{
    if (! in_array($key, array('date_creation', 'tms', 'import_key', 'status'))) continue;
    $align='';
    if (in_array($val['type'], array('date','datetime','timestamp'))) $align='center';
    if (in_array($val['type'], array('timestamp'))) $align.=' nowrap';
    if ($key == 'status') $align.=($align?' ':'').'center';
    if (! empty($arrayfields['t.'.$key]['checked'])) print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($align?'class="'.$align.'"':''), $sortfield, $sortorder, $align.' ')."\n";
}
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ')."\n";
print '</tr>'."\n";


// Detect if we need a fetch on each output line
$needToFetchEachLine=0;
foreach ($extrafields->attribute_computed as $key => $val)
{
    if (preg_match('/\$object/',$val)) $needToFetchEachLine++;  // There is at least one compute field that use $object
}


// Loop on record
// --------------------------------------------------------------------
$i=0;
$totalarray=array();
while ($i < min($num, $limit))
{
    $obj = $db->fetch_object($resql);
    if ($obj)
    {
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
            if (in_array($key, array('date_creation', 'tms', 'import_key', 'status'))) continue;
            $align='';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align='center';
            if (in_array($val['type'], array('timestamp'))) $align.='nowrap';
            if ($key == 'status') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['t.'.$key]['checked']))
            {
                print '<td'.($align?' class="'.$align.'"':'').'>';
                if (in_array($val['type'], array('date','datetime','timestamp'))) print dol_print_date($db->jdate($obj->$key), 'dayhour');
                elseif ($key == 'ref') print $object->getNomUrl(1, '', 0, '', 1);
                elseif ($key == 'status') print $object->getLibStatut(3);
                else print $obj->$key;
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
		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
		{
		   foreach($extrafields->attribute_label as $key => $val)
		   {
				if (! empty($arrayfields["ef.".$key]['checked']))
				{
					print '<td';
					$align=$extrafields->getAlignFlag($key);
					if ($align) print ' align="'.$align.'"';
					print '>';
					$tmpkey='options_'.$key;
					print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
					print '</td>';
		            if (! $i) $totalarray['nbfield']++;
		            if (! empty($val['isameasure']))
		            {
		            	if (! $i) $totalarray['pos'][$totalarray['nbfield']]='ef.'.$tmpkey;
		            	$totalarray['val']['ef.'.$tmpkey] += $obj->$tmpkey;
		            }
				}
		   }
		}
        // Fields from hook
	    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        // Rest of fields
        foreach($object->fields as $key => $val)
        {
            if (! in_array($key, array('date_creation', 'tms', 'import_key', 'status'))) continue;
            $align='';
            if (in_array($val['type'], array('date','datetime','timestamp'))) $align.=($align?' ':'').'center';
            if (in_array($val['type'], array('timestamp'))) $align.=($align?' ':'').'nowrap';
            if ($key == 'status') $align.=($align?' ':'').'center';
            if (! empty($arrayfields['t.'.$key]['checked']))
            {
                print '<td'.($align?' class="'.$align.'"':'').'>';
                if (in_array($val['type'], array('date','datetime','timestamp'))) print dol_print_date($db->jdate($obj->$key), 'dayhour');
                elseif ($key == 'status') print $object->getLibStatut(3);
                else print $obj->$key;
                print '</td>';
                if (! $i) $totalarray['nbfield']++;
                if (! empty($val['isameasure']))
                {
	                if (! $i) $totalarray['pos'][$totalarray['nbfield']]='t.'.$key;
	               	$totalarray['val']['t.'.$key] += $obj->$key;
                }
            }
        }
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
    }
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
        	print '<td></td>';
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
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if ($nbtotalofrecords === '' || $nbtotalofrecords)
{
    if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files)
    {
        require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php');
        $formfile = new FormFile($db);

        // Show list of available documents
        $urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
        $urlsource.=str_replace('&amp;','&',$param);

        $filedir=$diroutputmassaction;
        $genallowed=$user->rights->mymodule->read;
        $delallowed=$user->rights->mymodule->read;

        print $formfile->showdocuments('massfilesarea_mymodule','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'');
    }
    else
    {
        print '<br><a name="show_files"></a><a href="'.$_SERVER["PHP_SELF"].'?show_files=1'.$param.'#show_files">'.$langs->trans("ShowTempMassFilesArea").'</a>';
    }
}

// End of page
llxFooter();
$db->close();
