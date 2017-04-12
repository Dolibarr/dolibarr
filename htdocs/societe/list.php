<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2016       Josep Lluis Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2016       Ferran Marcet      		<fmarcet@2byte.es>
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
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';

$langs->load("companies");
$langs->load("commercial");
$langs->load("customers");
$langs->load("suppliers");
$langs->load("bills");
$langs->load("compta");
$langs->load('commercial');

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user,'societe',$socid,'');

$search_all=trim(GETPOST("sall"));
$search_nom=trim(GETPOST("search_nom"));
$search_nom_only=trim(GETPOST("search_nom_only"));
$search_barcode=trim(GETPOST("sbarcode"));
$search_customer_code=trim(GETPOST('search_customer_code'));
$search_supplier_code=trim(GETPOST('search_supplier_code'));
$search_account_customer_code=trim(GETPOST('search_account_customer_code'));
$search_account_supplier_code=trim(GETPOST('search_account_supplier_code'));
$search_town=trim(GETPOST("search_town"));
$search_zip=trim(GETPOST("search_zip"));
$search_state=trim(GETPOST("search_state"));
$search_email=trim(GETPOST('search_email'));
$search_phone=trim(GETPOST('search_phone'));
$search_url=trim(GETPOST('search_url'));
$search_idprof1=trim(GETPOST('search_idprof1'));
$search_idprof2=trim(GETPOST('search_idprof2'));
$search_idprof3=trim(GETPOST('search_idprof3'));
$search_idprof4=trim(GETPOST('search_idprof4'));
$search_idprof5=trim(GETPOST('search_idprof5'));
$search_idprof6=trim(GETPOST('search_idprof6'));
$search_sale=trim(GETPOST("search_sale",'int'));
$search_categ=trim(GETPOST("search_categ",'int'));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$search_status=GETPOST("search_status",'int');
$search_type=GETPOST('search_type','alpha');
$search_level_from = GETPOST("search_level_from","alpha");
$search_level_to   = GETPOST("search_level_to","alpha");
$search_stcomm=GETPOST('search_stcomm','int');

$type=GETPOST('type');
$optioncss=GETPOST('optioncss','alpha');
$mode=GETPOST("mode");
$action=GETPOST('action');

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield=GETPOST("sortfield",'alpha');
$sortorder=GETPOST("sortorder",'alpha');
$page=GETPOST("page",'int');
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";
if (empty($page) || $page == -1) { $page = 0 ; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='thirdpartylist';
/*if ($search_type == '1,3') { $contextpage='customerlist'; $type='c'; }
if ($search_type == '2,3') { $contextpage='prospectlist'; $type='p'; }
if ($search_type == '4') { $contextpage='supplierlist'; $type='f'; }
*/
if ($type == 'c') { $contextpage='customerlist'; if ($search_type=='') $search_type='1,3'; }
if ($type == 'p') { $contextpage='prospectlist'; if ($search_type=='') $search_type='2,3'; }
if ($type == 'f') { $contextpage='supplierlist'; if ($search_type=='') $search_type='4'; }

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('societe');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	's.nom'=>"ThirdPartyName",
	's.name_alias'=>"AliasNameShort",
	's.code_client'=>"CustomerCode",
    "s.code_fournisseur"=>"SupplierCode",
	's.email'=>"EMail",
	's.url'=>"URL",
    's.tva_intra'=>"VATIntra",
    's.siren'=>"ProfId1",
    's.siret'=>"ProfId2",
    's.ape'=>"ProfId3",
);
if (($tmp = $langs->transnoentities("ProfId4".$mysoc->country_code)) && $tmp != "ProfId4".$mysoc->country_code && $tmp != '-') $fieldstosearchall['s.idprof4']='ProfId4';
if (($tmp = $langs->transnoentities("ProfId5".$mysoc->country_code)) && $tmp != "ProfId5".$mysoc->country_code && $tmp != '-') $fieldstosearchall['s.idprof5']='ProfId5';
if (($tmp = $langs->transnoentities("ProfId6".$mysoc->country_code)) && $tmp != "ProfId6".$mysoc->country_code && $tmp != '-') $fieldstosearchall['s.idprof6']='ProfId6';
if (!empty($conf->barcode->enabled)) $fieldstosearchall['s.barcode']='Gencod';

// Define list of fields to show into list
$checkedcustomercode=(in_array($contextpage, array('thirdpartylist', 'customerlist', 'prospectlist')) ? 1 : 0);
$checkedsuppliercode=(in_array($contextpage, array('supplierlist')) ? 1 : 0);
$checkedcustomeraccountcode=(in_array($contextpage, array('customerlist')) ? 1 : 0);
$checkedsupplieraccountcode=(in_array($contextpage, array('supplierlist')) ? 1 : 0);
$checkedtypetiers=1;
$checkedprofid1=0;
$checkedprofid2=0;
$checkedprofid3=0;
$checkedprofid4=0;
$checkedprofid5=0;
$checkedprofid6=0;
//$checkedprofid4=((($tmp = $langs->transnoentities("ProfId4".$mysoc->country_code)) && $tmp != "ProfId4".$mysoc->country_code && $tmp != '-') ? 1 : 0);
//$checkedprofid5=((($tmp = $langs->transnoentities("ProfId5".$mysoc->country_code)) && $tmp != "ProfId5".$mysoc->country_code && $tmp != '-') ? 1 : 0);
//$checkedprofid6=((($tmp = $langs->transnoentities("ProfId6".$mysoc->country_code)) && $tmp != "ProfId6".$mysoc->country_code && $tmp != '-') ? 1 : 0);
$checkprospectlevel=(in_array($contextpage, array('prospectlist')) ? 1 : 0);
$checkstcomm=(in_array($contextpage, array('prospectlist')) ? 1 : 0);
$arrayfields=array(
    's.nom'=>array('label'=>$langs->trans("Company"), 'checked'=>1),
    's.barcode'=>array('label'=>$langs->trans("Gencod"), 'checked'=>1, 'enabled'=>(! empty($conf->barcode->enabled))),
    's.code_client'=>array('label'=>$langs->trans("CustomerCodeShort"), 'checked'=>$checkedcustomercode),
    's.code_fournisseur'=>array('label'=>$langs->trans("SupplierCodeShort"), 'checked'=>$checkedsuppliercode, 'enabled'=>(! empty($conf->fournisseur->enabled))),
    's.code_compta'=>array('label'=>$langs->trans("CustomerAccountancyCodeShort"), 'checked'=>$checkedcustomeraccountcode),
    's.code_compta_fournisseur'=>array('label'=>$langs->trans("SupplierAccountancyCodeShort"), 'checked'=>$checkedsupplieraccountcode, 'enabled'=>(! empty($conf->fournisseur->enabled))),
    's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
    's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
    'state.nom'=>array('label'=>$langs->trans("State"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
    's.email'=>array('label'=>$langs->trans("Email"), 'checked'=>0),
    's.url'=>array('label'=>$langs->trans("Url"), 'checked'=>0),
    's.phone'=>array('label'=>$langs->trans("Phone"), 'checked'=>1),
    'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
    's.siren'=>array('label'=>$langs->trans("ProfId1Short"), 'checked'=>$checkedprofid1),
    's.siret'=>array('label'=>$langs->trans("ProfId2Short"), 'checked'=>$checkedprofid2),
    's.ape'=>array('label'=>$langs->trans("ProfId3Short"), 'checked'=>$checkedprofid3),
    's.idprof4'=>array('label'=>$langs->trans("ProfId4Short"), 'checked'=>$checkedprofid4),
    's.idprof5'=>array('label'=>$langs->trans("ProfId5Short"), 'checked'=>$checkedprofid5),
    's.idprof6'=>array('label'=>$langs->trans("ProfId6Short"), 'checked'=>$checkedprofid6),
    'customerorsupplier'=>array('label'=>$langs->trans('Nature'), 'checked'=>1),
    's.fk_prospectlevel'=>array('label'=>$langs->trans("ProspectLevelShort"), 'checked'=>$checkprospectlevel),
	's.fk_stcomm'=>array('label'=>$langs->trans("StatusProsp"), 'checked'=>$checkstcomm),
    's.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    's.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    's.status'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
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
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

    // Do we click on purge search criteria ?
    if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
    {
        $search_nom='';
        $search_categ=0;
        $search_sale='';
    	$search_barcode="";
        $search_customer_code='';
        $search_supplier_code='';
        $search_account_customer_code='';
        $search_account_supplier_code='';
    	$search_town="";
    	$search_zip="";
    	$search_state="";
    	$search_country='';
    	$search_email='';
    	$search_phone='';
    	$search_url='';
    	$search_idprof1='';
    	$search_idprof2='';
    	$search_idprof3='';
    	$search_idprof4='';
    	$search_idprof5='';
    	$search_idprof6='';
    	$search_type='';
    	$search_type_thirdparty='';
    	$search_status=-1;
    	$search_stcomm='';
     	$search_level_from='';
     	$search_level_to='';
    	$search_array_options=array();
    }

    if ($action == 'setstcomm')
    {
        $object = new Client($db);
        $result=$object->fetch(GETPOST('stcommsocid'));
        $object->stcomm_id=dol_getIdFromCode($db, GETPOST('stcomm','alpha'), 'c_stcomm');
        $result=$object->update($object->id, $user);
        if ($result < 0) setEventMessages($object->error,$object->errors,'errors');

        $action='';
    }
}

if ($search_status=='') $search_status=1; // always display active thirdparty first



/*
 * View
 */

/*
 REM: Rules on permissions to see thirdparties
 Internal or External user + No permission to see customers => See nothing
 Internal user socid=0 + Permission to see ALL customers    => See all thirdparties
 Internal user socid=0 + No permission to see ALL customers => See only thirdparties linked to user that are sale representative
 External user socid=x + Permission to see ALL customers    => Can see only himself
 External user socid=x + No permission to see ALL customers => Can see only himself
 */

$form=new Form($db);
$formother=new FormOther($db);
$companystatic=new Societe($db);
$formcompany=new FormCompany($db);
$prospectstatic=new Client($db);
$prospectstatic->client=2;
$prospectstatic->loadCacheOfProspStatus();


$title=$langs->trans("ListOfThirdParties");
if ($type == 'c' && (empty($search_type) || ($search_type == '1,3'))) $title=$langs->trans("ListOfCustomers");
if ($type == 'p' && (empty($search_type) || ($search_type == '2,3'))) $title=$langs->trans("ListOfProspects");
if ($type == 'f' && (empty($search_type) || ($search_type == '4'))) $title=$langs->trans("ListOfSuppliers");

// If both parameters are set, search for everything BETWEEN them
if ($search_level_from != '' && $search_level_to != '')
{
	// Ensure that these parameters are numbers
	$search_level_from = (int) $search_level_from;
	$search_level_to = (int) $search_level_to;

	// If from is greater than to, reverse orders
	if ($search_level_from > $search_level_to)
	{
		$tmp = $search_level_to;
		$search_level_to = $search_level_from;
		$search_level_from = $tmp;
	}

	// Generate the SQL request
	$sortwhere = '(sortorder BETWEEN '.$search_level_from.' AND '.$search_level_to.') AS is_in_range';
}
// If only "from" parameter is set, search for everything GREATER THAN it
else if ($search_level_from != '')
{
	// Ensure that this parameter is a number
	$search_level_from = (int) $search_level_from;

	// Generate the SQL request
	$sortwhere = '(sortorder >= '.$search_level_from.') AS is_in_range';
}
// If only "to" parameter is set, search for everything LOWER THAN it
else if ($search_level_to != '')
{
	// Ensure that this parameter is a number
	$search_level_to = (int) $search_level_to;

	// Generate the SQL request
	$sortwhere = '(sortorder <= '.$search_level_to.') AS is_in_range';
}
// If no parameters are set, dont search for anything
else
{
	$sortwhere = '0 as is_in_range';
}

// Select every potentiels, and note each potentiels which fit in search parameters
dol_syslog('societe/list.php',LOG_DEBUG);
$sql = "SELECT code, label, sortorder, ".$sortwhere;
$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
$sql.= " WHERE active > 0";
$sql.= " ORDER BY sortorder";

$resql = $db->query($sql);
if ($resql)
{
	$tab_level = array();
	$search_levels = array();

	while ($obj = $db->fetch_object($resql))
	{
		// Compute level text
		$level=$langs->trans($obj->code);
		if ($level == $obj->code) $level=$langs->trans($obj->label);

		// Put it in the array sorted by sortorder
		$tab_level[$obj->sortorder] = $level;

		// If this potentiel fit in parameters, add its code to the $search_levels array
		if ($obj->is_in_range == 1)
		{
			$search_levels[] = '"'.preg_replace('[^A-Za-z0-9_-]', '', $obj->code).'"';
		}
	}

	// Implode the $search_levels array so that it can be use in a "IN (...)" where clause.
	// If no paramters was set, $search_levels will be empty
	$search_levels = implode(',', $search_levels);
}
else dol_print_error($db);

$sql = "SELECT s.rowid, s.nom as name, s.name_alias, s.barcode, s.town, s.zip, s.datec, s.code_client, s.code_fournisseur, ";
$sql.= " st.libelle as stcomm, s.fk_stcomm as stcomm_id, s.fk_prospectlevel, s.prefix_comm, s.client, s.fournisseur, s.canvas, s.status as status,";
$sql.= " s.email, s.phone, s.url, s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4 as idprof4, s.fk_pays,";
$sql.= " s.tms as date_update, s.datec as date_creation,";
$sql.= " s.code_compta,s.code_compta_fournisseur,";
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name";
// We'll need these fields in order to filter by sale (including the case where the user can only see his prospects)
if ($search_sale) $sql .= ", sc.fk_soc, sc.fk_user";
// We'll need these fields in order to filter by categ
if ($search_categ) $sql .= ", cs.fk_categorie, cs.fk_soc";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as ef on (s.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
// We'll need this table joined to the select in order to filter by categ
if (! empty($search_categ)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_".($type=='f'?"fournisseur":"societe")." as cs ON s.rowid = cs.fk_soc"; // We'll need this table joined to the select in order to filter by categ
$sql.= " ,".MAIN_DB_PREFIX."c_stcomm as st";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale || (!$user->rights->societe->client->voir && !$socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
if (! $user->rights->societe->client->voir && ! $socid)	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)           $sql.= " AND s.rowid = ".$socid;
if ($search_sale)     $sql.= " AND s.rowid = sc.fk_soc";        // Join for the needed table to filter by sale
if (! $user->rights->fournisseur->lire) $sql.=" AND (s.fournisseur <> 1 OR s.client <> 0)";    // client=0, fournisseur=0 must be visible
if ($search_sale)     $sql.= " AND sc.fk_user = ".$db->escape($search_sale);
if ($search_categ > 0)    $sql.= " AND cs.fk_categorie = ".$db->escape($search_categ);
if ($search_categ == -2)  $sql.= " AND cs.fk_categorie IS NULL";
if ($search_all)      $sql.= natural_search(array_keys($fieldstosearchall), $search_all);
if ($search_nom)      $sql.= natural_search("s.nom",$search_nom);
if ($search_nom_only) $sql.= natural_search("s.nom",$search_nom_only);
if ($search_customer_code) $sql.= natural_search("s.code_client",$search_customer_code);
if ($search_supplier_code) $sql.= natural_search("s.code_fournisseur",$search_supplier_code);
if ($search_account_customer_code) $sql.= natural_search("s.code_compta",$search_account_customer_code);
if ($search_account_supplier_code) $sql.= natural_search("s.code_compta_fournisseur",$search_account_supplier_code);
if ($search_town)     $sql.= natural_search("s.town",$search_town);
if ($search_zip)      $sql.= natural_search("s.zip",$search_zip);
if ($search_state)    $sql.= natural_search("state.nom",$search_state);
if ($search_country)  $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_email)    $sql.= natural_search("s.email",$search_email);
if ($search_phone)    $sql.= natural_search("s.phone",$search_phone);
if ($search_url)      $sql.= natural_search("s.url",$search_url);
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
if ($search_status!='' && $search_status >= 0) $sql .= " AND s.status = ".$db->escape($search_status);
if (!empty($conf->barcode->enabled) && $search_barcode) $sql.= " AND s.barcode LIKE '%".$db->escape($search_barcode)."%'";
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_levels)  $sql .= " AND s.fk_prospectlevel IN (".$search_levels.')';
if ($search_stcomm != '' && $search_stcomm != -2) $sql.= natural_search("s.fk_stcomm",$search_stcomm,2);
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

$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit+1, $offset);

$resql = $db->query($sql);
if (! $resql)
{
    dol_print_error($db);
    exit;
}

$num = $db->num_rows($resql);

if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all && $action != 'list')
{
    $obj = $db->fetch_object($resql);
    $id = $obj->rowid;
    header("Location: ".DOL_URL_ROOT.'/societe/soc.php?socid='.$id);
    exit;
}

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("ThirdParty"),$help_url);

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($search_all != '') $param = "&amp;sall=".urlencode($search_all);
if ($sall != '') $param .= "&amp;sall=".urlencode($sall);
if ($search_categ > 0) $param.='&amp;search_categ='.urlencode($search_categ);
if ($search_sale > 0)	$param.='&amp;search_sale='.urlencode($search_sale);
if ($search_nom != '') $param.= "&amp;search_nom=".urlencode($search_nom);
if ($search_town != '') $param.= "&amp;search_town=".urlencode($search_town);
if ($search_zip != '') $param.= "&amp;search_zip=".urlencode($search_zip);
if ($search_state != '') $param.= "&amp;search_state=".urlencode($search_state);
if ($search_country != '') $param.= "&amp;search_country=".urlencode($search_country);
if ($search_customer_code != '') $param.= "&amp;search_customer_code=".urlencode($search_customer_code);
if ($search_supplier_code != '') $param.= "&amp;search_supplier_code=".urlencode($search_supplier_code);
if ($search_account_customer_code != '') $param.= "&amp;search_account_customer_code=".urlencode($search_account_customer_code);
if ($search_account_supplier_code != '') $param.= "&amp;search_account_supplier_code=".urlencode($search_account_supplier_code);
if ($search_barcode != '') $param.= "&amp;sbarcode=".urlencode($search_barcode);
if ($search_idprof1 != '') $param.= '&amp;search_idprof1='.urlencode($search_idprof1);
if ($search_idprof2 != '') $param.= '&amp;search_idprof2='.urlencode($search_idprof2);
if ($search_idprof3 != '') $param.= '&amp;search_idprof3='.urlencode($search_idprof3);
if ($search_idprof4 != '') $param.= '&amp;search_idprof4='.urlencode($search_idprof4);
if ($search_idprof5 != '') $param.= '&amp;search_idprof5='.urlencode($search_idprof5);
if ($search_idprof6 != '') $param.= '&amp;search_idprof6='.urlencode($search_idprof6);
if ($search_country != '') $param.='&amp;search_country='.urlencode($search_country);
if ($search_type_thirdparty != '') $param.='&amp;search_type_thirdparty='.urlencode($search_type_thirdparty);
if ($optioncss != '') $param.='&amp;optioncss='.urlencode($optioncss);
if ($search_status != '') $param.='&amp;search_status='.urlencode($search_status);
if ($search_stcomm != '') $param.='&search_stcomm='.$search_stcomm;
if ($search_level_from != '') $param.='&search_level_from='.$search_level_from;
if ($search_level_to != '') $param.='&search_level_to='.$search_level_to;
if ($type != '') $param.='&amp;type='.urlencode($type);
// Add $param from extra fields
foreach ($search_array_options as $key => $val)
{
    $crit=$val;
    $tmpkey=preg_replace('/search_options_/','',$key);
    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
}

// Show delete result message
if (GETPOST('delsoc'))
{
    setEventMessages($langs->trans("CompanyDeleted",GETPOST('delsoc')), null, 'mesgs');
}

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies', 0, '', '', $limit);

$langs->load("other");
$textprofid=array();
foreach(array(1,2,3,4,5,6) as $key)
{
	$label=$langs->transnoentities("ProfId".$key.$mysoc->country_code);
	$textprofid[$key]='';
	if ($label != "ProfId".$key.$mysoc->country_code)
	{	// Get only text between ()
		if (preg_match('/\((.*)\)/i',$label,$reg)) $label=$reg[1];
		$textprofid[$key]=$langs->trans("ProfIdShortDesc",$key,$mysoc->country_code,$label);
	}
}

if ($search_all)
{
    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
    print $langs->trans("FilterOnInto", $search_all) . join(', ',$fieldstosearchall);
}

// Filter on categories
$moreforfilter='';
if ($type == 'c' || $type == 'p')
{
	if (! empty($conf->categorie->enabled))
	{
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$moreforfilter.='<div class="divsearchfield">';
	 	$moreforfilter.=$langs->trans('Categories'). ': ';
		$moreforfilter.=$formother->select_categories('customer',$search_categ,'search_categ',1);
	 	$moreforfilter.='</div>';
	}
}
if ($type == 'f')
{
    if (! empty($conf->categorie->enabled))
    {
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
        $moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.=$langs->trans('Categories'). ': ';
        $moreforfilter.=$formother->select_categories('supplier',$search_categ,'search_categ',1);
        $moreforfilter.='</div>';
    }
}

// If the user can view prospects other than his'
if ($user->rights->societe->client->voir || $socid)
{
 	$moreforfilter.='<div class="divsearchfield">';
 	$moreforfilter.=$langs->trans('SalesRepresentatives'). ': ';
	$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user, 0, 1, 'maxwidth300');
	$moreforfilter.='</div>';
}
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

if (empty($arrayfields['customerorsupplier']['checked'])) print '<input type="hidden" name="type" value="'.$type.'">';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '<tr class="liste_titre">';
if (! empty($arrayfields['s.nom']['checked']))                     print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['s.barcode']['checked']))                 print_liste_field_titre($arrayfields['s.barcode']['label'], $_SERVER["PHP_SELF"], "s.barcode",$param,'','',$sortfield,$sortorder);
if (! empty($arrayfields['s.code_client']['checked']))             print_liste_field_titre($arrayfields['s.code_client']['label'],$_SERVER["PHP_SELF"],"s.code_client","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.code_fournisseur']['checked']))        print_liste_field_titre($arrayfields['s.code_fournisseur']['label'],$_SERVER["PHP_SELF"],"s.code_fournisseur","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.code_compta']['checked']))             print_liste_field_titre($arrayfields['s.code_compta']['label'],$_SERVER["PHP_SELF"],"s.code_compta","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.code_compta_fournisseur']['checked'])) print_liste_field_titre($arrayfields['s.code_compta_fournisseur']['label'],$_SERVER["PHP_SELF"],"s.code_compta_fournisseur","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],"s.town","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],"s.zip","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['s.email']['checked']))          print_liste_field_titre($arrayfields['s.email']['label'],$_SERVER["PHP_SELF"],"s.email","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.phone']['checked']))          print_liste_field_titre($arrayfields['s.phone']['label'],$_SERVER["PHP_SELF"],"s.phone","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.url']['checked']))            print_liste_field_titre($arrayfields['s.url']['label'],$_SERVER["PHP_SELF"],"s.url","",$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.siren']['checked']))          print_liste_field_titre($form->textwithpicto($langs->trans("ProfId1Short"),$textprofid[1],1,0),$_SERVER["PHP_SELF"],"s.siren","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.siret']['checked']))          print_liste_field_titre($form->textwithpicto($langs->trans("ProfId2Short"),$textprofid[2],1,0),$_SERVER["PHP_SELF"],"s.siret","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.ape']['checked']))            print_liste_field_titre($form->textwithpicto($langs->trans("ProfId3Short"),$textprofid[3],1,0),$_SERVER["PHP_SELF"],"s.ape","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.idprof4']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId4Short"),$textprofid[4],1,0),$_SERVER["PHP_SELF"],"s.idprof4","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.idprof5']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId5Short"),$textprofid[4],1,0),$_SERVER["PHP_SELF"],"s.idprof5","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.idprof6']['checked']))        print_liste_field_titre($form->textwithpicto($langs->trans("ProfId6Short"),$textprofid[4],1,0),$_SERVER["PHP_SELF"],"s.idprof6","",$param,'class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['customerorsupplier']['checked']))        print_liste_field_titre($arrayfields['customerorsupplier']['label']);   // type of customer
if (! empty($arrayfields['s.fk_prospectlevel']['checked']))        print_liste_field_titre($arrayfields['s.fk_prospectlevel']['label'],$_SERVER["PHP_SELF"],"s.fk_prospectlevel","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['s.fk_stcomm']['checked']))               print_liste_field_titre($arrayfields['s.fk_stcomm']['label'],$_SERVER["PHP_SELF"],"s.fk_stcomm","",$param,'align="center"',$sortfield,$sortorder);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
       if (! empty($arrayfields["ef.".$key]['checked']))
       {
			$align=$extrafields->getAlignFlag($key);
			print_liste_field_titre($langs->trans($extralabels[$key]),$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
       }
   }
}
// Hook fields
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['s.datec']['checked']))  print_liste_field_titre($arrayfields['s.datec']['label'],$_SERVER["PHP_SELF"],"s.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.tms']['checked']))    print_liste_field_titre($arrayfields['s.tms']['label'],$_SERVER["PHP_SELF"],"s.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['s.status']['checked'])) print_liste_field_titre($arrayfields['s.status']['label'],$_SERVER["PHP_SELF"],"s.status","",$param,'align="center"',$sortfield,$sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";

// Fields title search
print '<tr class="liste_titre">';
if (! empty($arrayfields['s.nom']['checked']))
{
	print '<td class="liste_titre">';
	if (! empty($search_nom_only) && empty($search_nom)) $search_nom=$search_nom_only;
	print '<input class="flat searchstring" type="text" name="search_nom" size="8" value="'.dol_escape_htmltag($search_nom).'">';
	print '</td>';
}
// Barcode
if (! empty($arrayfields['s.barcode']['checked']))
{
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" type="text" name="sbarcode" size="6" value="'.dol_escape_htmltag($search_barcode).'">';
	print '</td>';
}
// Customer code
if (! empty($arrayfields['s.code_client']['checked']))
{
    print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="8" type="text" name="search_customer_code" value="'.dol_escape_htmltag($search_customer_code).'">';
	print '</td>';
}
// Supplier code
if (! empty($arrayfields['s.code_fournisseur']['checked']))
{
    print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="8" type="text" name="search_supplier_code" value="'.dol_escape_htmltag($search_supplier_code).'">';
	print '</td>';
}
// Account Customer code
if (! empty($arrayfields['s.code_compta']['checked']))
{
    print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="8" type="text" name="search_account_customer_code" value="'.dol_escape_htmltag($search_account_customer_code).'">';
	print '</td>';
}
// Account Supplier code
if (! empty($arrayfields['s.code_compta_fournisseur']['checked']))
{
    print '<td class="liste_titre">';
	print '<input class="flat" size="8" type="text" name="search_account_supplier_code" value="'.dol_escape_htmltag($search_account_supplier_code).'">';
	print '</td>';
}
// Town
if (! empty($arrayfields['s.town']['checked']))
{
    print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="6" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'">';
	print '</td>';
}
// Zip
if (! empty($arrayfields['s.zip']['checked']))
{
    print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'">';
	print '</td>';
}
// State
if (! empty($arrayfields['state.nom']['checked']))
{
    print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
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
if (! empty($arrayfields['s.email']['checked']))
{
    // Email
	print '<td class="liste_titre">';
	print '<input class="flat searchemail" size="4" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'">';
	print '</td>';
}
if (! empty($arrayfields['s.phone']['checked']))
{
    // Phone
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_phone" value="'.dol_escape_htmltag($search_phone).'">';
	print '</td>';
}
if (! empty($arrayfields['s.url']['checked']))
{
    // Url
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_url" value="'.dol_escape_htmltag($search_url).'">';
	print '</td>';
}
if (! empty($arrayfields['s.siren']['checked']))
{
    // IdProf1
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_idprof1" value="'.dol_escape_htmltag($search_idprof1).'">';
	print '</td>';
}
if (! empty($arrayfields['s.siret']['checked']))
{
    // IdProf2
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_idprof2" value="'.dol_escape_htmltag($search_idprof2).'">';
	print '</td>';
}
if (! empty($arrayfields['s.ape']['checked']))
{
    // IdProf3
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_idprof3" value="'.dol_escape_htmltag($search_idprof3).'">';
	print '</td>';
}
if (! empty($arrayfields['s.idprof4']['checked']))
{
    // IdProf4
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_idprof4" value="'.dol_escape_htmltag($search_idprof4).'">';
	print '</td>';
}
if (! empty($arrayfields['s.idprof5']['checked']))
{
    // IdProf5
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_idprof5" value="'.dol_escape_htmltag($search_idprof5).'">';
	print '</td>';
}
if (! empty($arrayfields['s.idprof6']['checked']))
{
    // IdProf6
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" size="4" type="text" name="search_idprof6" value="'.dol_escape_htmltag($search_idprof6).'">';
	print '</td>';
}

// Type (customer/prospect/supplier)
if (! empty($arrayfields['customerorsupplier']['checked']))
{
    print '<td class="liste_titre maxwidthonsmartphone" align="middle">';
    if ($type != '') print '<input type="hidden" name="type" value="'.$type.'">';
    print '<select class="flat" name="search_type">';
    print '<option value="-1"'.($search_type==''?' selected':'').'>&nbsp;</option>';
    if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1,3"'.($search_type=='1,3'?' selected':'').'>'.$langs->trans('Customer').'</option>';
    if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="2,3"'.($search_type=='2,3'?' selected':'').'>'.$langs->trans('Prospect').'</option>';
    //if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="3"'.($search_type=='3'?' selected':'').'>'.$langs->trans('ProspectCustomer').'</option>';
    print '<option value="4"'.($search_type=='4'?' selected':'').'>'.$langs->trans('Supplier').'</option>';
    print '<option value="0"'.($search_type=='0'?' selected':'').'>'.$langs->trans('Others').'</option>';
    print '</select></td>';
}
if (! empty($arrayfields['s.fk_prospectlevel']['checked']))
{
    // Prospect level
 	print '<td class="liste_titre" align="center">';
 	$options_from = '<option value="">&nbsp;</option>';	 	// Generate in $options_from the list of each option sorted
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_from .= '<option value="'.$tab_level_sortorder.'"'.($search_level_from == $tab_level_sortorder ? ' selected':'').'>';
 		$options_from .= $langs->trans($tab_level_label);
 		$options_from .= '</option>';
 	}
 	array_reverse($tab_level, true);	// Reverse the list
 	$options_to = '<option value="">&nbsp;</option>';		// Generate in $options_to the list of each option sorted in the reversed order
 	foreach ($tab_level as $tab_level_sortorder => $tab_level_label)
 	{
 		$options_to .= '<option value="'.$tab_level_sortorder.'"'.($search_level_to == $tab_level_sortorder ? ' selected':'').'>';
 		$options_to .= $langs->trans($tab_level_label);
 		$options_to .= '</option>';
 	}

    // Print these two select
 	print $langs->trans("From").' <select class="flat" name="search_level_from">'.$options_from.'</select>';
 	print ' ';
 	print $langs->trans("to").' <select class="flat" name="search_level_to">'.$options_to.'</select>';

    print '</td>';
}

if (! empty($arrayfields['s.fk_stcomm']['checked']))
{
    // Prospect status
    print '<td class="liste_titre maxwidthonsmartphone" align="center">';
    $arraystcomm=array();
	foreach($prospectstatic->cacheprospectstatus as $key => $val)
	{
        $arraystcomm[$val['id']]=($langs->trans("StatusProspect".$val['id']) != "StatusProspect".$val['id'] ? $langs->trans("StatusProspect".$val['id']) : $val['label']);
	}
    print $form->selectarray('search_stcomm', $arraystcomm, $search_stcomm, -2);
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
// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (! empty($arrayfields['s.datec']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Date modification
if (! empty($arrayfields['s.tms']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Status
if (! empty($arrayfields['s.status']['checked']))
{
    print '<td class="liste_titre maxwidthonsmartphone" align="center">';
    print $form->selectarray('search_status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')), $search_status, 1);
    print '</td>';
}
// Action column
print '<td class="liste_titre" align="right">';
$searchpitco=$form->showFilterAndCheckAddButtons(0);
print $searchpitco;
print '</td>';

print "</tr>\n";

$var=True;
$i = 0;
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($resql);
	$var=!$var;

	$companystatic->id=$obj->rowid;
	$companystatic->name=$obj->name;
	$companystatic->canvas=$obj->canvas;
	$companystatic->client=$obj->client;
	$companystatic->status=$obj->status;
	$companystatic->fournisseur=$obj->fournisseur;
	$companystatic->code_client=$obj->code_client;
	$companystatic->code_fournisseur=$obj->code_fournisseur;
    $companystatic->fk_prospectlevel=$obj->fk_prospectlevel;
    $companystatic->name_alias=$obj->name_alias;

	print "<tr ".$bc[$var].">";
	if (! empty($arrayfields['s.nom']['checked']))
	{
		print "<td>";
		print $companystatic->getNomUrl(1,'',100);
		print "</td>\n";
	}
	// Barcode
    if (! empty($arrayfields['s.barcode']['checked']))
	{
		print '<td>'.$obj->barcode.'</td>';
	}
	// Customer code
    if (! empty($arrayfields['s.code_client']['checked']))
	{
		print '<td>'.$obj->code_client.'</td>';
	}
    // Supplier code
    if (! empty($arrayfields['s.code_fournisseur']['checked']))
	{
		print '<td>'.$obj->code_fournisseur.'</td>';
	}
	// Account customer code
    if (! empty($arrayfields['s.code_compta']['checked']))
	{
		print '<td>'.$obj->code_compta.'</td>';
	}
    // Account supplier code
    if (! empty($arrayfields['s.code_compta_fournisseur']['checked']))
	{
		print '<td>'.$obj->code_compta_fournisseur.'</td>';
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
    // State
    if (! empty($arrayfields['state.nom']['checked']))
    {
        print "<td>".$obj->state_name."</td>\n";
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
    if (! empty($arrayfields['s.email']['checked']))
    {
        print "<td>".$obj->email."</td>\n";
    }
    if (! empty($arrayfields['s.phone']['checked']))
    {
        print "<td>".$obj->phone."</td>\n";
    }
    if (! empty($arrayfields['s.url']['checked']))
    {
        print "<td>".$obj->url."</td>\n";
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
    if (! empty($arrayfields['s.idprof5']['checked']))
    {
        print "<td>".$obj->idprof5."</td>\n";
    }
    if (! empty($arrayfields['s.idprof6']['checked']))
    {
        print "<td>".$obj->idprof6."</td>\n";
    }
    // Type
    if (! empty($arrayfields['customerorsupplier']['checked']))
    {
        print '<td align="center">';
    	$s='';
    	if (($obj->client==1 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
    	{
      		$companystatic->name=$langs->trans("Customer");
      		$companystatic->name_alias='';
    	    $s.=$companystatic->getNomUrl(0,'customer');
    	}
    	if (($obj->client==2 || $obj->client==3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
    	{
            if ($s) $s.=" / ";
    	    $companystatic->name=$langs->trans("Prospect");
      		$companystatic->name_alias='';
    	    $s.=$companystatic->getNomUrl(0,'prospect');
    	}
    	if (! empty($conf->fournisseur->enabled) && $obj->fournisseur)
    	{
    		if ($s) $s.=" / ";
            $companystatic->name=$langs->trans("Supplier");
      		$companystatic->name_alias='';
            $s.=$companystatic->getNomUrl(0,'supplier');
    	}
    	print $s;
    	print '</td>';
    }

    if (! empty($arrayfields['s.fk_prospectlevel']['checked']))
    {
		// Prospect level
		print '<td align="center">';
		print $companystatic->getLibProspLevel();
		print "</td>";
    }

    if (! empty($arrayfields['s.fk_stcomm']['checked']))
    {
        // Prospect status
		print '<td align="center" class="nowrap"><div class="nowrap">';
		print '<div class="inline-block">'.$companystatic->LibProspCommStatut($obj->stcomm_id,2,$prospectstatic->cacheprospectstatus[$obj->stcomm_id]['label']);
		print '</div> - <div class="inline-block">';
		foreach($prospectstatic->cacheprospectstatus as $key => $val)
		{
			$titlealt='default';
			if (! empty($val['code']) && ! in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) $titlealt=$val['label'];
			if ($obj->stcomm_id != $val['id']) print '<a class="pictosubstatus" href="'.$_SERVER["PHP_SELF"].'?stcommsocid='.$obj->rowid.'&stcomm='.$val['code'].'&action=setstcomm'.$param.($page?'&page='.urlencode($page):'').'">'.img_action($titlealt,$val['code']).'</a>';
		}
		print '</div></div></td>';
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
			}
	   }
	}
    // Fields from hook
    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
	$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    // Date creation
    if (! empty($arrayfields['s.datec']['checked']))
    {
        print '<td align="center" class="nowrap">';
        print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
        print '</td>';
    }
    // Date modification
    if (! empty($arrayfields['s.tms']['checked']))
    {
        print '<td align="center" class="nowrap">';
        print dol_print_date($db->jdate($obj->date_update), 'dayhour');
        print '</td>';
    }
    // Status
    if (! empty($arrayfields['s.status']['checked']))
    {
        print '<td align="center" class="nowrap">'.$companystatic->getLibStatut(3).'</td>';
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
print "</div>";

print '</form>';

llxFooter();
$db->close();
