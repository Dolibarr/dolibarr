<?php
/* Copyright (C) 2013-2014      Jean-François Ferry     <jfefe@aternatik.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       resource/index.php
 *              \ingroup    resource
 *              \brief      Page to manage resource objects
 */


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';

// Load translations files required by page
$langs->load("resource");
$langs->load("companies");
$langs->load("other");

// Get parameters
$id                     = GETPOST('id','int');
$action         = GETPOST('action','alpha');

$lineid                         = GETPOST('lineid','int');
$element                        = GETPOST('element','alpha');
$element_id                     = GETPOST('element_id','int');
$resource_id            = GETPOST('resource_id','int');

$sortorder      = GETPOST('sortorder','alpha');
$sortfield      = GETPOST('sortfield','alpha');

// Initialize context for list
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'resourcelist';

$object = new Dolresource($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');
$search_ref=GETPOST("search_ref");
$search_type=GETPOST("search_type");

$filter=array();

if ($search_ref != ''){
	$param.='&search_ref='.$search_ref;
	$filter['t.ref']=$search_ref;
}
if ($search_type != ''){
	$param.='&search_type='.$search_type;
	$filter['ty.label']=$search_type;
}
if ($search_label != '') 		$param.='&search_label='.$search_label;
// Add $param from extra fields
foreach ($search_array_options as $key => $val)
{
	$crit=$val;
	$tmpkey=preg_replace('/search_options_/','',$key);
	$typ=$extrafields->attribute_type[$tmpkey];
	if ($val != '') {
		$param.='&search_options_'.$tmpkey.'='.urlencode($val);
	}
	$mode=0;
	if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
	if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit)))
	{
		$filter['ef.'.$tmpkey]=natural_search('ef.'.$tmpkey, $crit, $mode);
	}
}
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;


$hookmanager->initHooks(array('resource_list'));

if (empty($sortorder)) $sortorder="ASC";
if (empty($sortfield)) $sortfield="t.rowid";
if (empty($arch)) $arch = 0;

$page           = GETPOST('page','int');
if ($page == -1) {
	$page = 0 ;
}
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if( ! $user->rights->resource->read) {
        accessforbidden();
}
$arrayfields = array(
		't.ref' => array(
				'label' => $langs->trans("Ref"),
				'checked' => 1
		),
		'ty.label' => array(
				'label' => $langs->trans("ResourceType"),
				'checked' => 1
		),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
	foreach ( $extrafields->attribute_label as $key => $val ) {
		$typeofextrafield=$extrafields->attribute_type[$key];
		if ($typeofextrafield!='separate') {
			$arrayfields["ef." . $key] = array(
					'label' => $extrafields->attribute_label[$key],
					'checked' => $extrafields->attribute_list[$key],
					'position' => $extrafields->attribute_pos[$key],
					'enabled' => $extrafields->attribute_perms[$key]
			);
		}
	}
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_ref="";
	$search_label="";
	$search_array_options=array();
	$filter=array();
}

/*
 * Action
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


/*
 * View
 */

$form=new Form($db);

$pagetitle=$langs->trans('ResourcePageIndex');
llxHeader('',$pagetitle,'');

// Confirmation suppression resource line
if ($action == 'delete_resource')
{
	print $form->formconfirm($_SERVER['PHP_SELF']."?element=".$element."&element_id=".$element_id."&lineid=".$lineid,$langs->trans("DeleteResource"),$langs->trans("ConfirmDeleteResourceElement"),"confirm_delete_resource",'','',1);
}

// Load object list
$ret = $object->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);
if($ret == -1) {
        dol_print_error($db,$object->error);
        exit;
} else {
    print_barre_liste($pagetitle, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $ret+1, $object->num_all,'title_generic.png');
}

$var=true;

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '<tr class="liste_titre">';
if (! empty($arrayfields['t.ref']['checked']))           print_liste_field_titre($arrayfields['t.ref']['label'],$_SERVER["PHP_SELF"],"t.ref","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['ty.label']['checked']))        print_liste_field_titre($arrayfields['ty.label']['label'],$_SERVER["PHP_SELF"],"t.code","",$param,"",$sortfield,$sortorder);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($arrayfields["ef.".$key]['checked']))
		{
			$align=$extrafields->getAlignFlag($key);
			print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
		}
	}
}
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";

print '<tr class="liste_titre">';
if (! empty($arrayfields['t.ref']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="6">';
	print '</td>';
}
if (! empty($arrayfields['ty.label']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_type" value="'.$search_type.'" size="6">';
	print '</td>';
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
			if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')))
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
// Action column
print '<td class="liste_titre" align="right">';
$searchpitco=$form->showFilterAndCheckAddButtons(0);
print $searchpitco;
print '</td>';
print "</tr>\n";

if ($ret)
{
    foreach ($object->lines as $resource)
    {
            $var=!$var;

            $style='';
            if ($resource->id == GETPOST('lineid')) $style='style="background: orange;"';

            print '<tr '.$bc[$var].' '.$style.'>';

            if (! empty($arrayfields['t.ref']['checked']))
            {
            	print '<td>';
            	print $resource->getNomUrl(5);
            	print '</td>';
            }

            if (! empty($arrayfields['ty.label']['checked']))
            {
            	print '<td>';
            	print $resource->type_label;
            	print '</td>';
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
            			print $extrafields->showOutputField($key, $resource->array_options[$tmpkey], '', 1);
            			print '</td>';
            		}
            	}
            	if (! $i) $totalarray['nbfield']++;
            }

            print '<td align="center">';
            print '<a href="./card.php?action=edit&id='.$resource->id.'">';
            print img_edit();
            print '</a>';
            print '&nbsp;';
            print '<a href="./card.php?action=delete&id='.$resource->id.'">';
            print img_delete();
            print '</a>';
            print '</td>';

            print '</tr>';
    }

    print '</table>';
    print "</form>\n";
}
else
{
    print '<tr><td class="opacitymedium">'.$langs->trans('NoResourceInDatabase').'</td></tr>';
}

llxFooter();

$db->close();
