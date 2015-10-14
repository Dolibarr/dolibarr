<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Florian Henry      		<florian.henry@open-concept.pro>
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
 *	\file       htdocs/societe/list.php
 *	\ingroup    societe
 *	\brief      Page to show list of third parties
 */

require_once '../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load("companies");
$langs->load("customers");
$langs->load("suppliers");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$search_nom=trim(GETPOST("search_nom"));
$search_nom_only=trim(GETPOST("search_nom_only"));
$search_all=trim(GETPOST("search_all"));
$sbarcode=trim(GETPOST("sbarcode"));
$search_town=trim(GETPOST("search_town"));
$search_zip=trim(GETPOST("search_zip"));
$socname=trim(GETPOST("socname"));
$search_idprof1=trim(GETPOST('search_idprof1'));
$search_idprof2=trim(GETPOST('search_idprof2'));
$search_idprof3=trim(GETPOST('search_idprof3'));
$search_idprof4=trim(GETPOST('search_idprof4'));
$search_idprof5=trim(GETPOST('search_idprof5'));
$search_idprof6=trim(GETPOST('search_idprof6'));
$search_sale=trim(GETPOST("search_sale"));
$search_categ=trim(GETPOST("search_categ"));
$search_type=trim(GETPOST('search_type'));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$search_status=GETPOST("search_status",'int');

$optioncss=GETPOST('optioncss','alpha');
$mode=GETPOST("mode");
$modesearch=GETPOST("mode_search");

$sortfield=GETPOST("sortfield",'alpha');
$sortorder=GETPOST("sortorder",'alpha');
$page=GETPOST("page",'int');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='thirdpartylist';
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('thirdparty');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// special search
if ($mode == 'search')
{
	$search_nom=$socname;

	$sql = "SELECT s.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if ($search_sale || (!$user->rights->societe->client->voir && !$socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    // We'll need this table joined to the select in order to filter by categ
    if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
    $sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";

    // For natural search
    $scrit = explode(' ', $socname);

	$fields = array(
		's.nom',
		's.code_client',
		's.email',
		's.url',
		's.siren',
		's.name_alias'
	);

	if (!empty($conf->barcode->enabled)) {
		$fields[] = 's.barcode';
	}

    foreach ($scrit as $crit) {
        $sql.= natural_search($fields, $crit);
    }

	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
    if ($search_sale) $sql.= " AND s.rowid = sc.fk_soc";        // Join for the needed table to filter by sale
    if ($search_categ) $sql.= " AND s.rowid = cs.fk_soc";   // Join for the needed table to filter by categ
	if (! $user->rights->societe->lire || ! $user->rights->fournisseur->lire)
	{
		if (! $user->rights->fournisseur->lire) $sql.=" AND s.fournisseur != 1";
	}
    // Insert sale filter
    if ($search_sale)
    {
        $sql .= " AND sc.fk_user = ".$search_sale;
    }
    // Insert categ filter
    if ($search_categ)
    {
        $sql .= " AND cs.fk_categorie = ".$search_categ;
    }
    // Filter on type of thirdparty
	if ($search_type > 0 && in_array($search_type,array('1,3','2,3'))) $sql .= " AND s.client IN (".$db->escape($search_type).")";
	if ($search_type > 0 && in_array($search_type,array('4')))         $sql .= " AND s.fournisseur = 1";
	if ($search_type == '0') $sql .= " AND s.client = 0 AND s.fournisseur = 0";

	$result=$db->query($sql);
	if ($result)
	{
		if ($db->num_rows($result) == 1)
		{
			$obj = $db->fetch_object($result);
			$socid = $obj->rowid;
			header("Location: ".DOL_URL_ROOT."/societe/soc.php?socid=".$socid);
			exit;
		}
		$db->free($result);
	}
}



/*
 * View
 */

$form=new Form($db);
$htmlother=new FormOther($db);
$companystatic=new Societe($db);
$formcompany=new FormCompany($db);

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);


// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_categ='';
    $search_sale='';
    $socname="";
	$search_nom="";
	$sbarcode="";
	$search_town="";
	$search_zip="";
	$search_idprof1='';
	$search_idprof2='';
	$search_idprof3='';
	$search_idprof4='';
	$search_type='';
	$search_country='';
	$search_type_thirdparty='';
	$search_status='';
	$search_array_options=array();
}

if ($search_status=='') $search_status=1; // always display active thirdparty first

if ($socname)
{
	$search_nom=$socname;
}


/*
 * Mode List
 */

/*
 REM: Rules on permissions to see thirdparties
 Internal or External user + No permission to see customers => See nothing
 Internal user socid=0 + Permission to see ALL customers    => See all thirdparties
 Internal user socid=0 + No permission to see ALL customers => See only thirdparties linked to user that are sale representative
 External user socid=x + Permission to see ALL customers    => Can see only himself
 External user socid=x + No permission to see ALL customers => Can see only himself
 */
$title=$langs->trans("ListOfThirdParties");

$sql = "SELECT s.rowid, s.nom as name, s.barcode, s.town, s.zip, s.datec, s.code_client, s.code_fournisseur, ";
$sql.= " st.libelle as stcomm, s.prefix_comm, s.client, s.fournisseur, s.canvas, s.status as status,";
$sql.= " s.siren as idprof1, s.siret as idprof2, ape as idprof3, idprof4 as idprof4,";
$sql.= " s.fk_pays, s.tms as date_update, s.datec as date_creation,";
$sql.= " typent.code as typent_code";
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
// We'll need these fields in order to filter by categ
if ($search_categ) $sql .= ", cs.fk_categorie, cs.fk_soc";
// Add fields from extrafields
foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (is_array($extrafields->attribute_list) && count($extrafields->attribute_list)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as ef on (s.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays) ";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent) ";
$sql.= " ,".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale || (!$user->rights->societe->client->voir && !$socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_societe as cs";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
if (! $user->rights->societe->client->voir && ! $socid)	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)           $sql.= " AND s.rowid = ".$socid;
if ($search_sale)     $sql.= " AND s.rowid = sc.fk_soc";        // Join for the needed table to filter by sale
if ($search_categ)    $sql.= " AND s.rowid = cs.fk_soc";   // Join for the needed table to filter by categ
if (! $user->rights->fournisseur->lire) $sql.=" AND (s.fournisseur <> 1 OR s.client <> 0)";    // client=0, fournisseur=0 must be visible
if ($search_sale)     $sql.= " AND sc.fk_user = ".$db->escape($search_sale);
if ($search_categ)    $sql.= " AND cs.fk_categorie = ".$db->escape($search_categ);
if ($search_nom_only) $sql.= natural_search("s.nom",$search_nom_only);
if ($search_all)      $sql.= natural_search(array("s.nom", "s.name_alias", "s.code_client", "s.code_fournisseur", "s.email", "s.url","s.siren","s.siret","s.ape","s.idprof4","s.idprof5","s.idprof6"), $search_all);
if ($search_nom)      $sql.= natural_search(array("s.nom", "s.name_alias", "s.code_client", "s.code_fournisseur", "s.email", "s.url","s.siren","s.siret","s.ape","s.idprof4","s.idprof5","s.idprof6"), $search_nom);
if ($search_town)     $sql.= natural_search("s.town",$search_town);
if ($search_zip)      $sql.= natural_search("s.zip",$search_zip);
if ($search_idprof1)  $sql.= natural_search("s.siren",$search_idprof1);
if ($search_idprof2)  $sql.= natural_search("s.siret",$search_idprof2);
if ($search_idprof3)  $sql.= natural_search("s.ape",$search_idprof3);
if ($search_idprof4)  $sql.= natural_search("s.idprof4",$search_idprof4);
if ($search_idprof5)  $sql.= natural_search("s.idprof5",$search_idprof5);
if ($search_idprof6)  $sql.= natural_search("s.idprof6",$search_idprof6);
// Filter on type of thirdparty
if ($search_type > 0 && in_array($search_type,array('1,3','2,3'))) $sql .= " AND s.client IN (".$db->escape($search_type).")";
if ($search_type > 0 && in_array($search_type,array('4')))         $sql .= " AND s.fournisseur = 1";
if ($search_type == '0') $sql .= " AND s.client = 0 AND s.fournisseur = 0";
if ($search_status!='') $sql .= " AND s.status = ".$db->escape($search_status);
if (!empty($conf->barcode->enabled) && $sbarcode) $sql.= " AND s.barcode LIKE '%".$db->escape($sbarcode)."%'";
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
// Add where from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    $typ=$extrafields->attribute_type[$tmpkey];
    $mode=0;
    if (in_array($typ, array('int'))) $mode=1;    // Search on a numeric
    if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit))) 
    {
        $sql .= natural_search('ef.'.$tmpkey, $crit, $mode);
    }
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param = "&amp;socname=".urlencode($socname);
	$param.= "&amp;search_nom=".urlencode($search_nom);
	$param.= "&amp;search_town=".urlencode($search_town);
	$param.= "&amp;search_zip=".urlencode($search_zip);
	$param.= ($sbarcode?"&amp;sbarcode=".urlencode($sbarcode):"");
	$param.= '&amp;search_idprof1='.urlencode($search_idprof1);
	$param.= '&amp;search_idprof2='.urlencode($search_idprof2);
	$param.= '&amp;search_idprof3='.urlencode($search_idprof3);
	$param.= '&amp;search_idprof4='.urlencode($search_idprof4);
	if ($search_country != '') $param.='&amp;search_country='.urlencode($search_country);
	if ($search_type_thirdparty != '') $param.='&amp;search_type_thirdparty='.urlencode($search_type_thirdparty);
	if ($optioncss != '') $param.='&amp;optioncss='.urlencode($optioncss);
    if ($search_status != '') $params.='&amp;search_status='.urlencode($search_status);
    // Add $param from extra fields
    foreach ($search_array_options as $key => $val)
    {
        $crit=$val;
        $tmpkey=preg_replace('/search_options_/','',$key);
        $param.='&search_options_'.$tmpkey.'='.urlencode($val);
    } 	
    
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies');

    // Show delete result message
    if (GETPOST('delsoc'))
    {
	    setEventMessage($langs->trans("CompanyDeleted",GETPOST('delsoc')));
    }

	$langs->load("other");
	$textprofid=array();
	foreach(array(1,2,3,4) as $key)
	{
		$label=$langs->transnoentities("ProfId".$key.$mysoc->country_code);
		$textprofid[$key]='';
		if ($label != "ProfId".$key.$mysoc->country_code)
		{	// Get only text between ()
			if (preg_match('/\((.*)\)/i',$label,$reg)) $label=$reg[1];
			$textprofid[$key]=$langs->trans("ProfIdShortDesc",$key,$mysoc->country_code,$label);
		}
	}

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	
    // Filter on categories
    /* Not possible in this page because list is for ALL third parties type
	$moreforfilter='';
    if (! empty($conf->categorie->enabled))
    {
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('Categories'). ': ';
        $moreforfilter.=$htmlother->select_categories(Categories::TYPE_CUSTOMER,$search_categ,'search_categ');
        $moreforfilter.='</div>';
    }
    // If the user can view prospects other than his'
    if ($user->rights->societe->client->voir || $socid)
    {
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
        $moreforfilter.=$htmlother->select_salesrepresentatives($search_sale,'search_sale',$user);
        $moreforfilter.='</div>'; 
    }
	*/
	if (! empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
    	$parameters=array();
    	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	    print $hookmanager->resPrint;
	    print '</div>';
	}

	// Define list of fields to show into list
    $arrayfields=array(
        's.nom'=>array('label'=>$langs->trans("Company"), 'checked'=>1),
        's.barcode'=>array('label'=>$langs->trans("BarCode"), 'checked'=>1, 'enabled'=>(! empty($conf->barcode->enabled))),
        's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
        's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
        'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>1),
        'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>1),
        's.siren'=>array('label'=>$langs->trans("ProfId1Short"), 'checked'=>1),
        's.siret'=>array('label'=>$langs->trans("ProfId2Short"), 'checked'=>1),
        's.ape'=>array('label'=>$langs->trans("ProfId3Short"), 'checked'=>1),
        's.idprof4'=>array('label'=>$langs->trans("ProfId4Short"), 'checked'=>1),
        's.status'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>200),
        's.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
        's.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    );
    $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
    $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);
	print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
    if (! empty($arrayfields['s.barcode']['checked']))        print_liste_field_titre($langs->trans("BarCode"), $_SERVER["PHP_SELF"], "s.barcode",$param,'','',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.town","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($langs->trans("Zip"),$_SERVER["PHP_SELF"],"s.zip","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($langs->trans("Country"),$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($langs->trans("ThirdPartyType"),$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['s.siren']['checked']))          print_liste_field_titre($form->textwithpicto($langs->trans("ProfId1Short"),$textprofid[1],1,0),$_SERVER["PHP_SELF"],"s.siren","",$param,'class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['s.siret']['checked']))          print_liste_field_titre($form->textwithpicto($langs->trans("ProfId2Short"),$textprofid[2],1,0),$_SERVER["PHP_SELF"],"s.siret","",$param,'class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['s.ape']['checked']))            print_liste_field_titre($form->textwithpicto($langs->trans("ProfId3Short"),$textprofid[3],1,0),$_SERVER["PHP_SELF"],"s.ape","",$param,'class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['s.idprof4']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId4Short"),$textprofid[4],1,0),$_SERVER["PHP_SELF"],"s.idprof4","",$param,'class="nowrap"',$sortfield,$sortorder);
	print_liste_field_titre('');   // type of customer
    // Extra fields
	if (is_array($extrafields->attribute_list) && count($extrafields->attribute_list))
	{
	   foreach($extrafields->attribute_list as $key => $val) 
	   {
	       if ($val)
	       {
	           if (! empty($arrayfields["ef.".$key]['checked'])) print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,"",$sortfield,$sortorder);
	       }
	   }
	}
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if (! empty($arrayfields['s.status']['checked'])) print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"s.status","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['s.datec']['checked']))  print_liste_field_titre($langs->trans("DateCreationShort"),$_SERVER["PHP_SELF"],"s.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['s.tms']['checked']))    print_liste_field_titre($langs->trans("DateModificationShort"),$_SERVER["PHP_SELF"],"s.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	// Fields title search
	print '<tr class="liste_titre">';
    if (! empty($arrayfields['s.nom']['checked']))
    {
    	print '<td class="liste_titre">';
    	if (! empty($search_nom_only) && empty($search_nom)) $search_nom=$search_nom_only;
    	print '<input class="flat" type="text" name="search_nom" size="8" value="'.dol_escape_htmltag($search_nom).'">';
    	print '</td>';
    }
	// Barcode
    if (! empty($arrayfields['s.barcode']['checked']))
    {
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="sbarcode" size="6" value="'.dol_escape_htmltag($sbarcode).'">';
		print '</td>';
    }
	// Town
    if (! empty($arrayfields['s.town']['checked']))
    {
        print '<td class="liste_titre">';
    	print '<input class="flat" size="8" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'">';
    	print '</td>';
    }
	// Zip
    if (! empty($arrayfields['s.zip']['checked']))
    {
        print '<td class="liste_titre">';
    	print '<input class="flat" size="8" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'">';
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
        print '<td class="liste_titre" align="center">';
    	print $form->selectarray("search_type_thirdparty", $formcompany->typent_array(0), $search_type_thirdparty, 0, 0, 0, '', 0, 0, 0, (empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT));
    	print '</td>';
    }
	if (! empty($arrayfields['s.siren']['checked']))
	{
	    // IdProf1
    	print '<td class="liste_titre">';
    	print '<input class="flat" size="4" type="text" name="search_idprof1" value="'.dol_escape_htmltag($search_idprof1).'">';
    	print '</td>';
	}
    if (! empty($arrayfields['s.siret']['checked']))
    {
        // IdProf2
    	print '<td class="liste_titre">';
    	print '<input class="flat" size="4" type="text" name="search_idprof2" value="'.dol_escape_htmltag($search_idprof2).'">';
    	print '</td>';
    }
    if (! empty($arrayfields['s.ape']['checked']))
    {
        // IdProf3
    	print '<td class="liste_titre">';
    	print '<input class="flat" size="4" type="text" name="search_idprof3" value="'.dol_escape_htmltag($search_idprof3).'">';
    	print '</td>';
    }
    if (! empty($arrayfields['s.idprof4']['checked']))
    {
        // IdProf4
    	print '<td class="liste_titre">';
    	print '<input class="flat" size="4" type="text" name="search_idprof4" value="'.dol_escape_htmltag($search_idprof4).'">';
    	print '</td>';
    }
	// Type (customer/prospect/supplier)
    print '<td class="liste_titre" align="middle">';
	print '<select class="flat" name="search_type">';
	print '<option value="-1"'.($search_type==''?' selected':'').'>&nbsp;</option>';
	if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1,3"'.($search_type=='1,3'?' selected':'').'>'.$langs->trans('Customer').'</option>';
	if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="2,3"'.($search_type=='2,3'?' selected':'').'>'.$langs->trans('Prospect').'</option>';
	//if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="3"'.($search_type=='3'?' selected':'').'>'.$langs->trans('ProspectCustomer').'</option>';
	print '<option value="4"'.($search_type=='4'?' selected':'').'>'.$langs->trans('Supplier').'</option>';
	print '<option value="0"'.($search_type=='0'?' selected':'').'>'.$langs->trans('Others').'</option>';
	print '</select></td>';
    // Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (! empty($arrayfields['s.status']['checked']))
    {
        // Status
        print '<td class="liste_titre" align="right">';
        print $form->selectarray('search_status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$search_status);
        print '</td>';
    }
    if (! empty($arrayfields['s.datec']['checked']))
    {
        // Date creation
        print '<td class="liste_titre">';
        print '</td>';
    }
    if (! empty($arrayfields['s.tms']['checked']))
    {
        // Date modification
        print '<td class="liste_titre">';
        print '</td>';
    }
    // Action column
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';

	print "</tr>\n";

	$var=True;

	while ($i < min($num,$conf->liste_limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		print "<tr ".$bc[$var].">";
		if (! empty($arrayfields['s.nom']['checked']))
		{
    		print "<td>";
    		$companystatic->id=$obj->rowid;
    		$companystatic->name=$obj->name;
    		$companystatic->canvas=$obj->canvas;
            $companystatic->client=$obj->client;
            $companystatic->status=$obj->status;
            $companystatic->fournisseur=$obj->fournisseur;
            $companystatic->code_client=$obj->code_client;
            $companystatic->code_fournisseur=$obj->code_fournisseur;
    		print $companystatic->getNomUrl(1,'',100);
    		print "</td>\n";
		}
		// Barcode
        if (! empty($arrayfields['s.barcode']['checked']))
		{
			print '<td>'.$objp->barcode.'</td>';
		}
		// Town
        if (! empty($arrayfields['s.town']['checked']))
        {
            print "<td>".$obj->town."</td>\n";
        }
        // Zip
	    if (! empty($arrayfields['s.zip']['checked']))
        {
            print "<td>".$obj->zip."</td>\n";
        }        
		// Country
        if (! empty($arrayfields['country.code_iso']['checked']))
        {
            print '<td align="center">';
    		$tmparray=getCountry($obj->fk_pays,'all');
    		print $tmparray['label'];
    		print '</td>';
        }
		// Type ent
        if (! empty($arrayfields['typent.code']['checked']))
        {
            print '<td align="center">';
    		if (count($typenArray)==0) $typenArray = $formcompany->typent_array(1);
    		print $typenArray[$obj->typent_code];
    		print '</td>';
        }
        if (! empty($arrayfields['s.siren']['checked']))
        {
            print "<td>".$obj->idprof1."</td>\n";
        }
        if (! empty($arrayfields['s.siret']['checked']))
        {
            print "<td>".$obj->idprof2."</td>\n";
        }
        if (! empty($arrayfields['s.ape']['checked']))
        {
            print "<td>".$obj->idprof3."</td>\n";
        }
        if (! empty($arrayfields['s.idprof4']['checked']))
        {
            print "<td>".$obj->idprof4."</td>\n";
        }
        print '<td align="center">';
		$s='';
		if (($obj->client==1 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
		{
	  		$companystatic->name=$langs->trans("Customer");
		    $s.=$companystatic->getNomUrl(0,'customer');
		}
		if (($obj->client==2 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
		{
            if ($s) $s.=" / ";
		    $companystatic->name=$langs->trans("Prospect");
            $s.=$companystatic->getNomUrl(0,'prospect');
		}
		if (! empty($conf->fournisseur->enabled) && $obj->fournisseur)
		{
			if ($s) $s.=" / ";
            $companystatic->name=$langs->trans("Supplier");
            $s.=$companystatic->getNomUrl(0,'supplier');
		}
		print $s;
		print '</td>';
        // Fields from hook
	    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        // Status
        if (! empty($arrayfields['s.status']['checked']))
        {
            print '<td align="right">'.$companystatic->getLibStatut(5).'</td>';
        }
	    if (! empty($arrayfields['s.datec']['checked']))
        {
            // Date creation
            print '<td align="center">';
            print dol_print_date($obj->date_creation, 'dayhour');
            print '</td>';
        }
        if (! empty($arrayfields['s.tms']['checked']))
        {
            // Date modification
            print '<td align="center">';
            print dol_print_date($obj->date_update, 'dayhour');
            print '</td>';
        }
        // Action column
        print '<td></td>';

		print '</tr>'."\n";
		$i++;
	}

	$db->free($resql);

	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>";

	print '</form>';

}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();

