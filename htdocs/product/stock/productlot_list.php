<?php
/* Copyright (C) 2007-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 * \file        product/stock/productlot_list.php
 * \ingroup     stock
 * \brief       This file is an example of a php page
 *              Initialy built by build_class_from_table on 2016-05-17 12:22
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

// Load translation files required by the page
$langs->loadLangs(array('stocks', 'productbatch', 'other', 'users'));

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$myparam = GETPOST('myparam', 'alpha');
$toselect = GETPOST('toselect', 'array');


$search_entity=GETPOST('search_entity', 'int');
$search_product=GETPOST('search_product', 'alpha');
$search_batch=GETPOST('search_batch', 'alpha');
$search_fk_user_creat=GETPOST('search_fk_user_creat', 'int');
$search_fk_user_modif=GETPOST('search_fk_user_modif', 'int');
$search_import_key=GETPOST('search_import_key', 'int');


$search_myfield=GETPOST('search_myfield');
$optioncss = GETPOST('optioncss', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="t.batch"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

// Protection if external user
$socid=0;
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
	//accessforbidden();
}

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$object = new Productlot($db);
$hookmanager->initHooks(array('product_lotlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('product_lot');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	't.ref'=>'Ref',
	't.note_public'=>'NotePublic',
);

// Definition of fields for list
$arrayfields=array(
	//'t.entity'=>array('label'=>$langs->trans("Fieldentity"), 'checked'=>1),
	't.batch'=>array('label'=>$langs->trans("Batch"), 'checked'=>1),
	't.fk_product'=>array('label'=>$langs->trans("Product"), 'checked'=>1),
	't.sellby'=>array('label'=>$langs->trans("SellByDate"), 'checked'=>1),
	't.eatby'=>array('label'=>$langs->trans("EatByDate"), 'checked'=>1),
	//'t.import_key'=>array('label'=>$langs->trans("ImportKey"), 'checked'=>1),
	//'t.entity'=>array('label'=>$langs->trans("Entity"), 'checked'=>1, 'enabled'=>(! empty($conf->multicompany->enabled) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))),
	//'t.fk_user_creat'=>array('label'=>$langs->trans("UserCreationShort"), 'checked'=>0, 'position'=>500),
	//'t.fk_user_modif'=>array('label'=>$langs->trans("UserModificationShort"), 'checked'=>0, 'position'=>500),
	't.datec'=>array('label'=>$langs->trans("DateCreationShort"), 'checked'=>0, 'position'=>500),
	't.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	//'t.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
   }
}

// Load object if id or ref is provided as parameter
if (($id > 0 || ! empty($ref)) && $action != 'add')
{
	$result=$object->fetch($id, $ref);
	if ($result < 0) dol_print_error($db);
}



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction', 'alpha') && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') ||GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_entity='';
	$search_product='';
	$search_batch='';
	$search_fk_user_creat='';
	$search_fk_user_modif='';
	$search_import_key='';
	$search_date_creation='';
	$search_date_update='';
	$toselect=array();
	$search_array_options=array();
}


if (empty($reshook))
{
	$objectclass='ProductLot';
	$objectlabel='LotSerial';
	$permtoread = $user->rights->stock->read;
	$permtodelete = $user->rights->stock->delete;
	$uploaddir = $conf->stock->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}




/*
 * VIEW
 */

$now=dol_now();

$form=new Form($db);
$productstatic=new Product($db);

//$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
$help_url='';
$title = $langs->trans('LotSerialList');
llxHeader('', $title, $help_url);

// Put here content of your page

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


$sql = "SELECT";
$sql.= " t.rowid,";
$sql.= " t.entity,";
$sql.= " t.fk_product,";
$sql.= " t.batch,";
$sql.= " t.sellby,";
$sql.= " t.eatby,";
$sql.= " t.datec as date_creation,";
$sql.= " t.tms as date_update,";
$sql.= " t.fk_user_creat,";
$sql.= " t.fk_user_modif,";
$sql.= " t.import_key,";
$sql.= " p.fk_product_type as product_type,";
$sql.= " p.ref as product_ref,";
$sql.= " p.label as product_label,";
$sql.= " p.tobatch";
// Add fields for extrafields
foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."product_lot as t";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot_extrafields as ef on (t.rowid = ef.fk_object)";
$sql.= ", ".MAIN_DB_PREFIX."product as p";
$sql.= " WHERE p.rowid = t.fk_product";
$sql.= " AND p.entity IN (".getEntity('product').")";

if ($search_entity) $sql.= natural_search("entity", $search_entity);
if ($search_product) $sql.= natural_search("p.ref", $search_product);
if ($search_batch) $sql.= natural_search("batch", $search_batch);
if ($search_fk_user_creat) $sql.= natural_search("fk_user_creat", $search_fk_user_creat);
if ($search_fk_user_modif) $sql.= natural_search("fk_user_modif", $search_fk_user_modif);
if ($search_import_key) $sql.= natural_search("import_key", $search_import_key);

if ($sall) $sql.= natural_search(array_keys($fieldstosearchall), $sall);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere', $parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.=$db->order($sortfield, $sortorder);
//$sql.= $db->plimit($conf->liste_limit+1, $offset);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql.= $db->plimit($limit+1, $offset);


dol_syslog($script_file, LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	$param='';
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($search_entity != '') $param.= '&amp;search_entity='.urlencode($search_entity);
	if ($search_product != '') $param.= '&amp;search_product='.urlencode($search_product);
	if ($search_batch != '') $param.= '&amp;search_batch='.urlencode($search_batch);
	if ($search_fk_user_creat != '') $param.= '&amp;search_fk_user_creat='.urlencode($search_fk_user_creat);
	if ($search_fk_user_modif != '') $param.= '&amp;search_fk_user_modif='.urlencode($search_fk_user_modif);
	if ($search_import_key != '') $param.= '&amp;search_import_key='.urlencode($search_import_key);
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	$arrayofmassactions =  array(
		//'presend'=>$langs->trans("SendByMail"),
		//'builddoc'=>$langs->trans("PDFMerge"),
	);
	//if ($user->rights->stock->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_products', 0, '', '', $limit);

	$topicmail="Information";
	$modelmail="productlot";
	$objecttmp=new Productlot($db);
	$trackid='lot'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall)
	{
		foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ', $fieldstosearchall).'</div>';
	}

	/*$moreforfilter = '';
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
    $moreforfilter.= '</div>';*/

	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldPreListTitle', $parameters);    // Note that $action and $object may have been modified by hook
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

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	// Fields title search
	print '<tr class="liste_titre_filter">';
	if (! empty($arrayfields['t.entity']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_entity" value="'.$search_entity.'" size="8"></td>';
	if (! empty($arrayfields['t.batch']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_batch" value="'.$search_batch.'" size="8"></td>';
	if (! empty($arrayfields['t.fk_product']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_product" value="'.$search_product.'" size="8"></td>';
	if (! empty($arrayfields['t.sellby']['checked'])) print '<td class="liste_titre"></td>';
	if (! empty($arrayfields['t.eatby']['checked'])) print '<td class="liste_titre"></td>';
	if (! empty($arrayfields['t.fk_user_creat']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_fk_user_creat" value="'.$search_fk_user_creat.'" size="10"></td>';
	if (! empty($arrayfields['t.fk_user_modif']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_fk_user_modif" value="'.$search_fk_user_modif.'" size="10"></td>';
	if (! empty($arrayfields['t.import_key']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_import_key" value="'.$search_import_key.'" size="10"></td>';
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['t.datec']['checked']))
	{
		// Date creation
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (! empty($arrayfields['t.tms']['checked']))
	{
		// Date modification
		print '<td class="liste_titre">';
		print '</td>';
	}
	/*if (! empty($arrayfields['u.statut']['checked']))
     {
     // Status
     print '<td class="liste_titre center">';
     print $form->selectarray('search_statut', array('-1'=>'','0'=>$langs->trans('Disabled'),'1'=>$langs->trans('Enabled')),$search_statut);
     print '</td>';
     }*/
	// Action column
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
	print $searchpicto;
	print '</td>';
	print '</tr>'."\n";

	// Fields title
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['t.entity']['checked']))        print_liste_field_titre($arrayfields['t.entity']['label'], $_SERVER['PHP_SELF'], 't.entity', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['t.batch']['checked']))         print_liste_field_titre($arrayfields['t.batch']['label'], $_SERVER['PHP_SELF'], 't.batch', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['t.fk_product']['checked']))    print_liste_field_titre($arrayfields['t.fk_product']['label'], $_SERVER['PHP_SELF'], 't.fk_product', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['t.sellby']['checked']))        print_liste_field_titre($arrayfields['t.sellby']['label'], $_SERVER['PHP_SELF'], 't.sellby', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['t.eatby']['checked']))         print_liste_field_titre($arrayfields['t.eatby']['label'], $_SERVER['PHP_SELF'], 't.eatby', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['t.fk_user_creat']['checked'])) print_liste_field_titre($arrayfields['t.fk_user_creat']['label'], $_SERVER['PHP_SELF'], 't.fk_user_creat', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['t.fk_user_modif']['checked'])) print_liste_field_titre($arrayfields['t.fk_user_modif']['label'], $_SERVER['PHP_SELF'], 't.fk_user_modif', '', $param, '', $sortfield, $sortorder);
	if (! empty($arrayfields['t.import_key']['checked']))    print_liste_field_titre($arrayfields['t.import_key']['label'], $_SERVER['PHP_SELF'], 't.import_key', '', $param, '', $sortfield, $sortorder);
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
    if (! empty($arrayfields['t.datec']['checked'])) {
        print_liste_field_titre($arrayfields['t.datec']['label'], $_SERVER["PHP_SELF"], "t.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
    }
    if (! empty($arrayfields['t.tms']['checked'])) {
        print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER["PHP_SELF"], "t.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
    }
    //if (! empty($arrayfields['t.status']['checked'])) {
    //    print_liste_field_titre($arrayfields['t.status']['label'], $_SERVER["PHP_SELF"], "t.status", "", $param, '', $sortfield, $sortorder, 'center ');
    //}
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
    print '</tr>'."\n";

	$productlot = new Productlot($db);

	$i=0;
	$totalarray=array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);
		if ($obj)
		{

			$productlot->id = $obj->rowid;
			$productlot->batch = $obj->batch;

			// You can use here results
			print '<tr class="oddeven">';
			if (! empty($arrayfields['t.entity']['checked']))
			{
				print '<td>'.$obj->entity.'</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['t.batch']['checked']))
			{
				print '<td>'.$productlot->getNomUrl(1).'</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['t.fk_product']['checked']))
			{
				$productstatic->id=$obj->fk_product;
				$productstatic->type=$obj->product_type;
				$productstatic->ref=$obj->product_ref;
				$productstatic->label=$obj->product_label;
				$productstatic->status_batch = $obj->tobatch;
				print '<td>'.$productstatic->getNomUrl(1).'</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['t.sellby']['checked']))
			{
				print '<td>'.dol_print_date($db->jdate($obj->sellby), 'day').'</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['t.eatby']['checked']))
			{
				print '<td>'.dol_print_date($db->jdate($obj->eatby), 'day').'</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['t.fk_user_creat']['checked']))
			{
				print '<td>'.$obj->fk_user_creat.'</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['t.fk_user_modif']['checked']))
			{
				print '<td>'.$obj->fk_user_modif.'</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			if (! empty($arrayfields['t.import_key']['checked']))
			{
				print '<td>'.$obj->import_key.'</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			// Fields from hook
			$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
			$reshook=$hookmanager->executeHooks('printFieldListValue', $parameters);    // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (! empty($arrayfields['t.datec']['checked']))
			{
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Date modification
			if (! empty($arrayfields['t.tms']['checked']))
			{
				print '<td class="center">';
				print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
			}
			// Status
			/*
            if (! empty($arrayfields['u.statut']['checked']))
            {
    		  $userstatic->statut=$obj->statut;
              print '<td class="center">'.$userstatic->getLibStatut(3).'</td>';
            }*/

			// Action column
			print '<td class="nowrap center">';
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
	if (isset($totalarray['totalhtfield']))
	{
		print '<tr class="liste_total">';
		$i=0;
		while ($i < $totalarray['nbfield'])
		{
			$i++;
			if ($i == 1)
			{
				if ($num < $limit && empty($offset)) print '<td class="left">'.$langs->trans("Total").'</td>';
				else print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
			}
			elseif ($totalarray['totalhtfield'] == $i) print '<td class="right">'.price($totalarray['totalht']).'</td>';
			elseif ($totalarray['totalvatfield'] == $i) print '<td class="right">'.price($totalarray['totalvat']).'</td>';
			elseif ($totalarray['totalttcfield'] == $i) print '<td class="right">'.price($totalarray['totalttc']).'</td>';
			else print '<td></td>';
		}
		print '</tr>';
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>'."\n";
	print '</div>';

	print '</form>'."\n";

	/*
	$hidegeneratedfilelistifempty=1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) $hidegeneratedfilelistifempty=0;

    // Show list of available documents
	$urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource.=str_replace('&amp;','&',$param);

	$filedir=$diroutputmassaction;
	$genallowed=$user->rights->facture->lire;
	$delallowed=$user->rights->facture->creer;

	print $formfile->showdocuments('massfilesarea_orders','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'','','',null,$hidegeneratedfilelistifempty);
	*/
}
else
{
	$error++;
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
