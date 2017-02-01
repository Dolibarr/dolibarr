<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2016	   Ferran Marcet        <fmarcet@2byte.es>
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
 *      \file       htdocs/expedition/list.php
 *      \ingroup    expedition
 *      \brief      Page to list all shipments
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->load("sendings");
$langs->load("deliveries");
$langs->load('companies');

$socid=GETPOST('socid','int');
// Security check
$expeditionid = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expedition',$expeditionid,'');

$diroutputmassaction=$conf->expedition->dir_output . '/temp/massgeneration/'.$user->id;

$search_ref_exp = GETPOST("search_ref_exp");
$search_ref_liv = GETPOST('search_ref_liv');
$search_company = GETPOST("search_company");
$search_town=GETPOST('search_town','alpha');
$search_zip=GETPOST('search_zip','alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$sall = GETPOST('sall');
$optioncss = GETPOST('optioncss','alpha');

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (! $sortfield) $sortfield="e.ref";
if (! $sortorder) $sortorder="DESC";
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='shipmentlist';

$viewstatut=GETPOST('viewstatut');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('shipmentlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('expedition');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'e.ref'=>"Ref",
    's.nom'=>"ThirdParty",
    'e.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["e.note_private"]="NotePrivate";

$checkedtypetiers=0;
$arrayfields=array(
    'e.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    'e.ref_customer'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
    's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
    's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
    's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
    'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
    'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
    'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
    'e.date_delivery'=>array('label'=>$langs->trans("DateDeliveryPlanned"), 'checked'=>1),
    'e.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'e.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'e.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
    'l.ref'=>array('label'=>$langs->trans("DeliveryRef"), 'checked'=>1, 'enabled'=>$conf->livraison_bon->enabled),
    'l.date_delivery'=>array('label'=>$langs->trans("DateReceived"), 'checked'=>1, 'enabled'=>$conf->livraison_bon->enabled)
);

// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
    }
}


/*
 * Actions
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction')) { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
{
    $search_ref_exp='';
    $search_ref_liv='';
    $search_company='';
    $search_town='';
	$search_zip="";
    $search_state="";
	$search_type='';
	$search_country='';
	$search_type_thirdparty='';
    $viewstatut='';
    $search_array_options=array();
}

if (empty($reshook))
{
    // Mass actions. Controls on number of lines checked
    $maxformassaction=1000;
    if (! empty($massaction) && count($toselect) < 1)
    {
        $error++;
        setEventMessages($langs->trans("NoLineChecked"), null, "warnings");
    }
    if (! $error && count($toselect) > $maxformassaction)
    {
        setEventMessages($langs->trans('TooManyRecordForMassAction',$maxformassaction), null, 'errors');
        $error++;
    }

}
    
    
    

/*
 * View
 */

$form=new Form($db);
$companystatic=new Societe($db);
$shipment=new Expedition($db);
$formcompany=new FormCompany($db);

$helpurl='EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('',$langs->trans('ListOfSendings'),$helpurl);

$sql = "SELECT e.rowid, e.ref, e.ref_customer, e.date_expedition as date_expedition, e.date_delivery as date_livraison, l.date_delivery as date_reception, e.fk_statut,";
$sql.= ' s.rowid as socid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client, ';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= ' e.date_creation as date_creation, e.tms as date_update';
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."expedition_extrafields as ef on (e.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee ON e.rowid = ee.fk_source AND ee.sourcetype = 'shipping' AND ee.targettype = 'delivery'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.rowid = ee.fk_target";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql.= " WHERE e.entity IN (".getEntity('expedition', 1).")";
if (!$user->rights->societe->client->voir && !$socid)	// Internal user with no permission to see all
{
	$sql.= " AND e.fk_soc = sc.fk_soc";
	$sql.= " AND sc.fk_user = " .$user->id;
}
if ($socid)
{
	$sql.= " AND e.fk_soc = ".$socid;
}
if ($viewstatut <> '' && $viewstatut >= 0) {
	$sql.= " AND e.fk_statut = ".$viewstatut;
}
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip",$search_zip);
if ($search_state) $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_ref_exp) $sql .= natural_search('e.ref', $search_ref_exp);
if ($search_ref_liv) $sql .= natural_search('l.ref', $search_ref_liv);
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);

// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit)))
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1,$offset);

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$expedition = new Expedition($db);

	$param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall) $param.= "&amp;sall=".$sall;
	if ($search_ref_exp) $param.= "&amp;search_ref_exp=".$search_ref_exp;
	if ($search_ref_liv) $param.= "&amp;search_ref_liv=".$search_ref_liv;
	if ($search_company) $param.= "&amp;search_company=".$search_company;
	if ($optioncss != '') $param.='&amp;optioncss='.$optioncss;
	// Add $param from extra fields
	foreach ($search_array_options as $key => $val)
	{
	    $crit=$val;
	    $tmpkey=preg_replace('/search_options_/','',$key);
	    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
	}
	
	//$massactionbutton=$form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail"), 'builddoc'=>$langs->trans("PDFMerge")));
	
	$i = 0;
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">'."\n";
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    
	print_barre_liste($langs->trans('ListOfSendings'), $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num, $nbtotalofrecords, '', 0, '', '', $limit);

    if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
    
    $moreforfilter='';
    if (! empty($moreforfilter))
    {
        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        $parameters=array('type'=>$type);
        $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        print '</div>';
    }

    $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
    $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre">';

	if (! empty($arrayfields['e.ref']['checked']))            print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"],"e.ref","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.ref_customer']['checked']))   print_liste_field_titre($arrayfields['e.ref_customer']['label'], $_SERVER["PHP_SELF"],"e.ref_customer","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"],"s.nom", "", $param,'align="left"',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.date_delivery']['checked']))  print_liste_field_titre($arrayfields['e.date_delivery']['label'], $_SERVER["PHP_SELF"],"e.date_delivery","",$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['l.ref']['checked']))            print_liste_field_titre($arrayfields['l.ref']['label'], $_SERVER["PHP_SELF"],"l.ref","",$param, '',$sortfield,$sortorder);
   	if (! empty($arrayfields['l.date_delivery']['checked']))  print_liste_field_titre($arrayfields['l.date_delivery']['label'], $_SERVER["PHP_SELF"],"l.date_delivery","",$param, 'align="center"',$sortfield,$sortorder);
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
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if (! empty($arrayfields['e.datec']['checked']))  print_liste_field_titre($arrayfields['e.datec']['label'],$_SERVER["PHP_SELF"],"e.date_creation","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.tms']['checked']))    print_liste_field_titre($arrayfields['e.tms']['label'],$_SERVER["PHP_SELF"],"e.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.fk_statut']['checked'])) print_liste_field_titre($arrayfields['e.fk_statut']['label'],$_SERVER["PHP_SELF"],"e.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['l.fk_statut']['checked'])) print_liste_field_titre($arrayfields['l.fk_statut']['label'], $_SERVER["PHP_SELF"],"l.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	// Ref
	if (! empty($arrayfields['e.ref']['checked']))            
	{
	    print '<td class="liste_titre">';
    	print '<input class="flat" size="6" type="text" name="search_ref_exp" value="'.$search_ref_exp.'">';
        print '</td>';
	}
	// Ref customer
	if (! empty($arrayfields['e.ref_customer']['checked']))            
	{
	    print '<td class="liste_titre">';
    	print '<input class="flat" size="6" type="text" name="search_ref_customer" value="'.$search_ref_customer.'">';
        print '</td>';
	}
	// Thirdparty
	if (! empty($arrayfields['s.nom']['checked']))            
	{
    	print '<td class="liste_titre" align="left">';
    	print '<input class="flat" type="text" size="8" name="search_company" value="'.dol_escape_htmltag($search_company).'">';
    	print '</td>';
	}
	// Town
	if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_town" value="'.$search_town.'"></td>';
	// Zip
	if (! empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="6" name="search_zip" value="'.$search_zip.'"></td>';
	// State
    if (! empty($arrayfields['state.nom']['checked']))
    {
        print '<td class="liste_titre">';
    	print '<input class="flat" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
    	print '</td>';
    }
    // Country
    if (! empty($arrayfields['country.code_iso']['checked']))
    {
        print '<td class="liste_titre" align="center">';
    	print $form->select_country($search_country,'search_country','',0,'maxwidth100');
    	print '</td>';
    }
	// Company type
    if (! empty($arrayfields['typent.code']['checked']))
    {
        print '<td class="liste_titre maxwidthonsmartphone" align="center">';
    	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT));
    	print '</td>';
    }
	// Date delivery planned
	if (! empty($arrayfields['e.date_delivery']['checked']))            
	{
    	print '<td class="liste_titre">&nbsp;</td>';
	}
	if (! empty($arrayfields['l.ref']['checked']))            
	{
    	// Delivery ref
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_ref_liv" value="'.$search_ref_liv.'"';
		print '</td>';
	}
	if (! empty($arrayfields['l.date_delivery']['checked']))            
	{
	    // Date received
		print '<td class="liste_titre">&nbsp;</td>';
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
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (! empty($arrayfields['e.datec']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Date modification
	if (! empty($arrayfields['e.tms']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Status
	if (! empty($arrayfields['e.fk_statut']['checked']))
	{
	    print '<td class="liste_titre maxwidthonsmartphone" align="right">';
	    print $form->selectarray('viewstatut', array('0'=>$langs->trans('StatusSendingDraftShort'),'1'=>$langs->trans('StatusSendingValidatedShort'),'2'=>$langs->trans('StatusSendingProcessedShort')),$viewstatut,1);
	    print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpitco=$form->showFilterAndCheckAddButtons(0);
	print $searchpitco;
    print '</td>';
	print "</tr>\n";

	$i=0;
	$var=true;
	$totalarray=array();
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);

    	$shipment->id=$obj->rowid;
    	$shipment->ref=$obj->ref;
    	
    	$companystatic->id=$obj->socid;
    	$companystatic->ref=$obj->name;
    	$companystatic->name=$obj->name;
		
		$var=!$var;

    	print "<tr ".$bc[$var].">";
    	 
		// Ref
		if (! empty($arrayfields['e.ref']['checked']))
		{
    		print "<td>";
    		print $shipment->getNomUrl(1);
    		print "</td>\n";
    		if (! $i) $totalarray['nbfield']++;
		}
		
		// Ref customer
		if (! empty($arrayfields['e.ref_customer']['checked']))
		{
		    print "<td>";
		    print $obj->ref_customer;
		    print "</td>\n";
		    if (! $i) $totalarray['nbfield']++;
		}		
		
		// Third party
		if (! empty($arrayfields['s.nom']['checked']))
		{
    		print '<td>';
    		print $companystatic->getNomUrl(1);
    		print '</td>';
    		if (! $i) $totalarray['nbfield']++;
		}
		// Town
		if (! empty($arrayfields['s.town']['checked']))
		{
		    print '<td class="nocellnopadd">';
		    print $obj->town;
		    print '</td>';
		    if (! $i) $totalarray['nbfield']++;
		}
		// Zip
		if (! empty($arrayfields['s.zip']['checked']))
		{
		    print '<td class="nocellnopadd">';
		    print $obj->zip;
		    print '</td>';
		    if (! $i) $totalarray['nbfield']++;
		}
		// State
		if (! empty($arrayfields['state.nom']['checked']))
		{
		    print "<td>".$obj->state_name."</td>\n";
		    if (! $i) $totalarray['nbfield']++;
		}
		// Country
		if (! empty($arrayfields['country.code_iso']['checked']))
		{
		    print '<td align="center">';
		    $tmparray=getCountry($obj->fk_pays,'all');
		    print $tmparray['label'];
		    print '</td>';
		    if (! $i) $totalarray['nbfield']++;
		}
		// Type ent
		if (! empty($arrayfields['typent.code']['checked']))
		{
		    print '<td align="center">';
		    if (count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
		    print $typenArray[$obj->typent_code];
		    print '</td>';
		    if (! $i) $totalarray['nbfield']++;
		}
		
		// Date delivery planed
		if (! empty($arrayfields['e.date_delivery']['checked']))
		{
    		print '<td align="center">';
    		print dol_print_date($db->jdate($obj->date_livraison),"day");
    		/*$now = time();
    		if ( ($now - $db->jdate($obj->date_expedition)) > $conf->warnings->lim && $obj->statutid == 1 )
    		{
    		}*/
    		print "</td>\n";
		}
		
		if (! empty($arrayfields['l.ref']['checked']) || ! empty($arrayfields['l.date_delivery']['checked']))
        {
		    $shipment->fetchObjectLinked($shipment->id,$shipment->element);
            $receiving='';
            if (count($shipment->linkedObjects['delivery']) > 0) $receiving=reset($shipment->linkedObjects['delivery']);

    		if (! empty($arrayfields['l.ref']['checked']))
            {
                // Ref
                print '<td>';
                print !empty($receiving) ? $receiving->getNomUrl($db) : '';
                print '</td>';
            }
            
    		if (! empty($arrayfields['l.date_delivery']['checked']))
            {
                // Date received
            	print '<td align="center">';
    			print dol_print_date($db->jdate($obj->date_reception),"day");
    			print '</td>'."\n";
            }
		}

		// Extra fields
		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
		{
		    foreach($extrafields->attribute_label as $key => $val)
		    {
		        if (! empty($arrayfields["ef.".$key]['checked']))
		        {
		            print '<td class="tdofextrafield"';
		            $align=$extrafields->getAlignFlag($key);
		            if ($align) print ' align="'.$align.'"';
		            print '>';
		            $tmpkey='options_'.$key;
		            print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
		            print '</td>';
		            if (! $i) $totalarray['nbfield']++;
		        }
		    }
		}
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (! empty($arrayfields['e.datec']['checked']))
		{
		    print '<td align="center" class="nowrap">';
		    print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
		    print '</td>';
		    if (! $i) $totalarray['nbfield']++;
		}
		// Date modification
		if (! empty($arrayfields['e.tms']['checked']))
		{
		    print '<td align="center" class="nowrap">';
		    print dol_print_date($db->jdate($obj->date_update), 'dayhour');
		    print '</td>';
		    if (! $i) $totalarray['nbfield']++;
		}
		// Status
		if (! empty($arrayfields['e.fk_statut']['checked']))
		{
		    print '<td align="right" class="nowrap">'.$shipment->LibStatut($obj->fk_statut,5).'</td>';
		    if (! $i) $totalarray['nbfield']++;
		}
		// Action column
		print '<td></td>';
		if (! $i) $totalarray['nbfield']++;
		
		print "</tr>\n";

		$i++;
	}

	print "</table>";
	print "</div>";
	print '</form>';
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();
$db->close();
