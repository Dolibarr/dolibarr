<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville   <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin          <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Juanjo Menent          <jmenent@2byte.es>
 * Copyright (C) 2013      Christophe Battarel    <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador        <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
 * Copyright (C) 2015      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2015      Jean-François Ferry    <jfefe@aternatik.fr>
 * Copyright (C) 2016	   Ferran Marcet		  <fmarcet@2byte.es>
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
 *	\file       htdocs/commande/list.php
 *	\ingroup    commande
 *	\brief      Page to list orders
 */


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load('orders');
$langs->load('sendings');
$langs->load('deliveries');
$langs->load('companies');
$langs->load('compta');
$langs->load('bills');

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

$orderyear=GETPOST("orderyear","int");
$ordermonth=GETPOST("ordermonth","int");
$orderday=GETPOST("orderday","int");
$deliveryyear=GETPOST("deliveryyear","int");
$deliverymonth=GETPOST("deliverymonth","int");
$deliveryday=GETPOST("deliveryday","int");
$search_product_category=GETPOST('search_product_category','int');
$search_ref=GETPOST('search_ref','alpha')!=''?GETPOST('search_ref','alpha'):GETPOST('sref','alpha');
$search_ref_customer=GETPOST('search_ref_customer','alpha');
$search_company=GETPOST('search_company','alpha');
$search_town=GETPOST('search_town','alpha');
$search_zip=GETPOST('search_zip','alpha');
$search_state=trim(GETPOST("search_state"));
$search_country=GETPOST("search_country",'int');
$search_type_thirdparty=GETPOST("search_type_thirdparty",'int');
$sall=GETPOST('sall');
$socid=GETPOST('socid','int');
$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_total_ht=GETPOST('search_total_ht','alpha');
$optioncss = GETPOST('optioncss','alpha');
$billed = GETPOST('billed','int');
$viewstatut=GETPOST('viewstatut');

// Security check
$id = (GETPOST('orderid')?GETPOST('orderid','int'):GETPOST('id','int'));
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande', $id,'');

$diroutputmassaction=$conf->commande->dir_output . '/temp/massgeneration/'.$user->id;

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='c.ref';
if (! $sortorder) $sortorder='DESC';

// Initialize technical object to manage context to save list fields
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'orderlist';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('commande');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'c.ref'=>'Ref',
    'c.ref_client'=>'RefCustomerOrder',
    'pd.description'=>'Description',
    's.nom'=>"ThirdParty",
    'c.note_public'=>'NotePublic',
);
if (empty($user->socid)) $fieldstosearchall["c.note_private"]="NotePrivate";

$checkedtypetiers=0;
$arrayfields=array(
    'c.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    'c.ref_client'=>array('label'=>$langs->trans("RefCustomerOrder"), 'checked'=>1),
    's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
    's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
    's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
    'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
    'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
    'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
    'c.date_commande'=>array('label'=>$langs->trans("OrderDateShort"), 'checked'=>1),
    'c.date_delivery'=>array('label'=>$langs->trans("DateDeliveryPlanned"), 'checked'=>1, 'enabled'=>empty($conf->global->ORDER_DISABLE_DELIVERY_DATE)),
    'c.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
    'c.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
    'c.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
    'c.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'c.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'c.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
    'c.facture'=>array('label'=>$langs->trans("Billed"), 'checked'=>1, 'position'=>1000, 'enabled'=>(empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT)))
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
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Selection of new fields
    include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
    
    // Purge search criteria
    if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
    {
        $search_categ='';
        $search_user='';
        $search_sale='';
        $search_product_category='';
        $search_ref='';
        $search_ref_customer='';
        $search_company='';
        $search_town='';
    	$search_zip="";
        $search_state="";
    	$search_type='';
    	$search_country='';
    	$search_type_thirdparty='';
        $search_total_ht='';
        $search_total_vat='';
        $search_total_ttc='';
        $orderyear='';
        $ordermonth='';
    	$orderday='';
    	$deliveryday='';
    	$deliverymonth='';
        $deliveryyear='';
        $viewstatut='';
        $billed='';
        $toselect='';
        $search_array_options=array();
    }

    // Mass actions
    $objectclass='Commande';
    $objectlabel='Orders';
    $permtoread = $user->rights->commande->lire;
    $permtodelete = $user->rights->commande->supprimer;
    $uploaddir = $conf->commande->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


if ($massaction == 'confirm_createbills') {
	
	$orders = GETPOST('toselect');
	$createbills_onebythird = GETPOST('createbills_onebythird', 'int');
	$validate_invoices = GETPOST('valdate_invoices', 'int');
	
	$TFact = array();
	$TFactThird = array();
	
	$nb_bills_created = 0;
	
	$db->begin();
	
	foreach($orders as $id_order) {
		
		$cmd = new Commande($db);
		if($cmd->fetch($id_order) <= 0) continue;
		
		$object = new Facture($db);
		if(!empty($createbills_onebythird) && !empty($TFactThird[$cmd->socid])) $object = $TFactThird[$cmd->socid]; // If option "one bill per third" is set, we use already created order.
		else {
			
			$object->socid = $cmd->socid;
			$object->type = Facture::TYPE_STANDARD;
			$object->cond_reglement_id	= $cmd->cond_reglement_id;
			$object->mode_reglement_id	= $cmd->mode_reglement_id;
			$object->fk_project			= $cmd->fk_project;
			
			$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($datefacture))
			{
				$datefacture = dol_mktime(date("h"), date("M"), 0, date("m"), date("d"), date("Y"));
			}
			
			$object->date = $datefacture;
			$object->origin    = 'commande';
			$object->origin_id = $id_order;
			
			$res = $object->create($user);
			
			if($res > 0) $nb_bills_created++;
			
		}
		
		if($object->id > 0) {
			
			$db->begin();
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
			$sql.= "fk_source";
			$sql.= ", sourcetype";
			$sql.= ", fk_target";
			$sql.= ", targettype";
			$sql.= ") VALUES (";
			$sql.= $id_order;
			$sql.= ", '".$object->origin."'";
			$sql.= ", ".$object->id;
			$sql.= ", '".$object->element."'";
			$sql.= ")";

			if ($db->query($sql))
			{
				$db->commit();
			}
			else
			{
				$db->rollback();
			}
			
			$lines = $cmd->lines;
			if (empty($lines) && method_exists($cmd, 'fetch_lines'))
			{
				$cmd->fetch_lines();
				$lines = $cmd->lines;
			}
			
			$fk_parent_line=0;
			$num=count($lines);
			
			for ($i=0;$i<$num;$i++)
			{
				$desc=($lines[$i]->desc?$lines[$i]->desc:$lines[$i]->libelle);
				if ($lines[$i]->subprice < 0)
				{
					// Negative line, we create a discount line
					$discount = new DiscountAbsolute($db);
					$discount->fk_soc=$object->socid;
					$discount->amount_ht=abs($lines[$i]->total_ht);
					$discount->amount_tva=abs($lines[$i]->total_tva);
					$discount->amount_ttc=abs($lines[$i]->total_ttc);
					$discount->tva_tx=$lines[$i]->tva_tx;
					$discount->fk_user=$user->id;
					$discount->description=$desc;
					$discountid=$discount->create($user);
					if ($discountid > 0)
					{
						$result=$object->insert_discount($discountid);
						//$result=$discount->link_to_invoice($lineid,$id);
					}
					else
					{
						setEventMessages($discount->error, $discount->errors, 'errors');
						$error++;
						break;
					}
				}
				else
				{
					// Positive line
					$product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);
					// Date start
					$date_start=false;
					if ($lines[$i]->date_debut_prevue) $date_start=$lines[$i]->date_debut_prevue;
					if ($lines[$i]->date_debut_reel) $date_start=$lines[$i]->date_debut_reel;
					if ($lines[$i]->date_start) $date_start=$lines[$i]->date_start;
					//Date end
					$date_end=false;
					if ($lines[$i]->date_fin_prevue) $date_end=$lines[$i]->date_fin_prevue;
					if ($lines[$i]->date_fin_reel) $date_end=$lines[$i]->date_fin_reel;
					if ($lines[$i]->date_end) $date_end=$lines[$i]->date_end;
					// Reset fk_parent_line for no child products and special product
					if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9)
					{
						$fk_parent_line = 0;
					}
					$result = $object->addline(
							$desc,
							$lines[$i]->subprice,
							$lines[$i]->qty,
							$lines[$i]->tva_tx,
							$lines[$i]->localtax1_tx,
							$lines[$i]->localtax2_tx,
							$lines[$i]->fk_product,
							$lines[$i]->remise_percent,
							$date_start,
							$date_end,
							0,
							$lines[$i]->info_bits,
							$lines[$i]->fk_remise_except,
							'HT',
							0,
							$product_type,
							$ii,
							$lines[$i]->special_code,
							$object->origin,
							$lines[$i]->rowid,
							$fk_parent_line,
							$lines[$i]->fk_fournprice,
							$lines[$i]->pa_ht,
							$lines[$i]->label
					);
					if ($result > 0)
					{
						$lineid=$result;
					}
					else
					{
						$lineid=0;
						$error++;
						break;
					}
					// Defined the new fk_parent_line
					if ($result > 0 && $lines[$i]->product_type == 9)
					{
						$fk_parent_line = $result;
					}
				}
			}			
			
		}
		 
		$cmd->classifyBilled($user);

		if(!empty($createbills_onebythird) && empty($TFactThird[$cmd->socid])) $TFactThird[$cmd->socid] = $object;
		else $TFact[$object->id] = $object;
	}
	
	// Build doc with all invoices
	$TAllFact = empty($createbills_onebythird) ? $TFact : $TFactThird;
	$toselect = array();
	
	if(!empty($validate_invoices)) {
		
		$massaction = $action = 'builddoc';
		
		foreach($TAllFact as &$object) {
			$object->validate($user);
			$toselect[] = $object->id; // For builddoc action
			
			// Fac builddoc
			$upload_dir = $conf->facture->dir_output;
		    $permissioncreate=$user->rights->facture->creer;
		    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
		}
		
		$objectclass='Facture';
	    $objectlabel='Invoice';
	    $permtoread = $user->rights->facture->lire;
	    $permtodelete = $user->rights->facture->supprimer;
	    $uploaddir = $conf->facture->dir_output;
		include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
		
	}
	
	if (! $error)
	{
		$db->commit();
		setEventMessage($langs->trans('BillCreated', $nb_bills_created));
	}
	else
	{
		$db->rollback();
		$action='create';
		$_GET["origin"]=$_POST["origin"];
		$_GET["originid"]=$_POST["originid"];
		setEventMessages($object->error, $object->errors, 'errors');
		$error++;
	}
	
}


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);
$formcompany=new FormCompany($db);

$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo_Pedidos_de_clientes";
llxHeader('',$langs->trans("Orders"),$help_url);

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' s.rowid as socid, s.nom as name, s.town, s.zip, s.fk_pays, s.client, s.code_client,';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= ' c.rowid, c.ref, c.total_ht, c.tva as total_tva, c.total_ttc, c.ref_client,';
$sql.= ' c.date_valid, c.date_commande, c.note_private, c.date_livraison as date_delivery, c.fk_statut, c.facture as billed,';
$sql.= ' c.date_creation as date_creation, c.tms as date_update';
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql.= ', '.MAIN_DB_PREFIX.'commande as c';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_extrafields as ef on (c.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'commandedet as pd ON c.rowid=pd.fk_commande';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ec";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE c.fk_soc = s.rowid';
$sql.= ' AND c.entity IN ('.getEntity('commande', 1).')';
if ($search_product_category > 0) $sql.=" AND cp.fk_categorie = ".$search_product_category;
if ($socid > 0) $sql.= ' AND s.rowid = '.$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_ref) $sql .= natural_search('c.ref', $search_ref);
if ($search_ref_customer) $sql.= natural_search('c.ref_client', $search_ref_customer);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($billed != '' && $billed >= 0) $sql.=' AND c.facture = '.$billed;
if ($viewstatut <> '')
{
	if ($viewstatut < 4 && $viewstatut > -3)
	{
		if ($viewstatut == 1 && empty($conf->expedition->enabled)) $sql.= ' AND c.fk_statut IN (1,2)';	// If module expedition disabled, we include order with status 'sending in process' into 'validated'
		else $sql.= ' AND c.fk_statut = '.$viewstatut; // brouillon, validee, en cours, annulee
	}
	if ($viewstatut == 4)
	{
		$sql.= ' AND c.facture = 1'; // invoice created
	}
	if ($viewstatut == -2)	// To process
	{
		//$sql.= ' AND c.fk_statut IN (1,2,3) AND c.facture = 0';
		$sql.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";    // If status is 2 and facture=1, it must be selected
	}
	if ($viewstatut == -3)	// To bill
	{
		//$sql.= ' AND c.fk_statut in (1,2,3)';
		//$sql.= ' AND c.facture = 0'; // invoice not created
		$sql .= ' AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))'; // validated, in process or closed but not billed
	}
}
if ($ordermonth > 0)
{
    if ($orderyear > 0 && empty($orderday))
    $sql.= " AND c.date_commande BETWEEN '".$db->idate(dol_get_first_day($orderyear,$ordermonth,false))."' AND '".$db->idate(dol_get_last_day($orderyear,$ordermonth,false))."'";
    else if ($orderyear > 0 && ! empty($orderday))
    $sql.= " AND c.date_commande BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $ordermonth, $orderday, $orderyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $ordermonth, $orderday, $orderyear))."'";
    else
    $sql.= " AND date_format(c.date_commande, '%m') = '".$ordermonth."'";
}
else if ($orderyear > 0)
{
    $sql.= " AND c.date_commande BETWEEN '".$db->idate(dol_get_first_day($orderyear,1,false))."' AND '".$db->idate(dol_get_last_day($orderyear,12,false))."'";
}
if ($deliverymonth > 0)
{
    if ($deliveryyear > 0 && empty($deliveryday))
    $sql.= " AND c.date_livraison BETWEEN '".$db->idate(dol_get_first_day($deliveryyear,$deliverymonth,false))."' AND '".$db->idate(dol_get_last_day($deliveryyear,$deliverymonth,false))."'";
    else if ($deliveryyear > 0 && ! empty($deliveryday))
    $sql.= " AND c.date_livraison BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $deliverymonth, $deliveryday, $deliveryyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $deliverymonth, $deliveryday, $deliveryyear))."'";
    else
    $sql.= " AND date_format(c.date_livraison, '%m') = '".$deliverymonth."'";
}
else if ($deliveryyear > 0)
{
    $sql.= " AND c.date_livraison BETWEEN '".$db->idate(dol_get_first_day($deliveryyear,1,false))."' AND '".$db->idate(dol_get_last_day($deliveryyear,12,false))."'";
}
if ($search_town)  $sql.= natural_search('s.town', $search_town);
if ($search_zip)   $sql.= natural_search("s.zip",$search_zip);
if ($search_state) $sql.= natural_search("state.nom",$search_state);
if ($search_country) $sql .= " AND s.fk_pays IN (".$search_country.')';
if ($search_type_thirdparty) $sql .= " AND s.fk_typent IN (".$search_type_thirdparty.')';
if ($search_company) $sql .= natural_search('s.nom', $search_company);
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0) $sql.= " AND ec.fk_c_type_contact = tc.rowid AND tc.element='commande' AND tc.source='internal' AND ec.element_id = c.rowid AND ec.fk_socpeople = ".$search_user;
if ($search_total_ht != '') $sql.= natural_search('c.total_ht', $search_total_ht, 1);
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

$sql.= $db->plimit($limit + 1,$offset);

//print $sql;
$resql = $db->query($sql);
if ($resql)
{
	if ($socid > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($socid);
		$title = $langs->trans('ListOfOrders') . ' - '.$soc->name;
	}
	else
	{
		$title = $langs->trans('ListOfOrders');
	}
	if (strval($viewstatut) == '0')
	$title.=' - '.$langs->trans('StatusOrderDraftShort');
	if ($viewstatut == 1)
	$title.=' - '.$langs->trans('StatusOrderValidatedShort');
	if ($viewstatut == 2)
	$title.=' - '.$langs->trans('StatusOrderSentShort');
	if ($viewstatut == 3)
	$title.=' - '.$langs->trans('StatusOrderToBillShort');
	if ($viewstatut == 4)
	$title.=' - '.$langs->trans('StatusOrderProcessedShort');
	if ($viewstatut == -1)
	$title.=' - '.$langs->trans('StatusOrderCanceledShort');
	if ($viewstatut == -2)
	$title.=' - '.$langs->trans('StatusOrderToProcessShort');
	if ($viewstatut == -3)
	$title.=' - '.$langs->trans('StatusOrderValidated').', '.(empty($conf->expedition->enabled)?'':$langs->trans("StatusOrderSent").', ').$langs->trans('StatusOrderToBill');

	$num = $db->num_rows($resql);
	
	$arrayofselected=is_array($toselect)?$toselect:array();
	
	$param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall)					$param.='&sall='.$sall;
	if ($socid > 0)             $param.='&socid='.$socid;
	if ($viewstatut != '')      $param.='&viewstatut='.$viewstatut;
	if ($orderday)      		$param.='&orderday='.$orderday;
	if ($ordermonth)      		$param.='&ordermonth='.$ordermonth;
	if ($orderyear)       		$param.='&orderyear='.$orderyear;
	if ($deliveryday)   		$param.='&deliveryday='.$deliveryday;
	if ($deliverymonth)   		$param.='&deliverymonth='.$deliverymonth;
	if ($deliveryyear)    		$param.='&deliveryyear='.$deliveryyear;
	if ($search_ref)      		$param.='&search_ref='.$search_ref;
	if ($search_company)  		$param.='&search_company='.$search_company;
	if ($search_ref_customer)	$param.='&search_ref_customer='.$search_ref_customer;
	if ($search_user > 0) 		$param.='&search_user='.$search_user;
	if ($search_sale > 0) 		$param.='&search_sale='.$search_sale;
	if ($search_total_ht != '') $param.='&search_total_ht='.$search_total_ht;
	if ($search_total_vat != '') $param.='&search_total_vat='.$search_total_vat;
	if ($search_total_ttc != '') $param.='&search_total_ttc='.$search_total_ttc;
    if ($show_files)            $param.='&show_files=' .$show_files;
    if ($optioncss != '')       $param.='&optioncss='.$optioncss;
	if ($billed != '')			$param.='&billed='.$billed;

	// Add $param from extra fields
	foreach ($search_array_options as $key => $val)
	{
	    $crit=$val;
	    $tmpkey=preg_replace('/search_options_/','',$key);
	    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
	}
	
	// List of mass actions available
	$arrayofmassactions =  array(
	    'presend'=>$langs->trans("SendByMail"),
	    'builddoc'=>$langs->trans("PDFMerge"),
	);
	if($user->rights->facture->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
	if ($user->rights->commande->supprimer) $arrayofmassactions['delete']=$langs->trans("Delete");
	if ($massaction == 'presend' || $massaction == 'createbills') $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	// Lines of title fields
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_commercial.png', 0, '', '', $limit);
	
	if ($massaction == 'presend')
	{
	    $langs->load("mails");
	
	    if (! GETPOST('cancel'))
	    {
	        $objecttmp=new Commande($db);
	        $listofselectedid=array();
	        $listofselectedthirdparties=array();
	        $listofselectedref=array();
	        foreach($arrayofselected as $toselectid)
	        {
	            $result=$objecttmp->fetch($toselectid);
	            if ($result > 0)
	            {
	                $listofselectedid[$toselectid]=$toselectid;
	                $thirdpartyid=$objecttmp->fk_soc?$objecttmp->fk_soc:$objecttmp->socid;
	                $listofselectedthirdparties[$thirdpartyid]=$thirdpartyid;
	                $listofselectedref[$thirdpartyid][$toselectid]=$objecttmp->ref;
	            }
	        }
	    }
	
	    print '<input type="hidden" name="massaction" value="confirm_presend">';
	
	    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	    $formmail = new FormMail($db);
	
	    dol_fiche_head(null, '', '');
	
	    $topicmail="SendOrderRef";
	    $modelmail="order_send";
	
	    // Cree l'objet formulaire mail
	    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	    $formmail = new FormMail($db);
	    $formmail->withform=-1;
        $formmail->fromtype = (GETPOST('fromtype')?GETPOST('fromtype'):(!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE)?$conf->global->MAIN_MAIL_DEFAULT_FROMTYPE:'user'));

        if($formmail->fromtype === 'user'){
            $formmail->fromid = $user->id;

        }
	    if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
	    {
	        $formmail->trackid='ord'.$object->id;
	    }
	    if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
	    {
	        include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	        $formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'ord'.$object->id);
	    }
	    $formmail->withfrom=1;
	    $liste=$langs->trans("AllRecipientSelected");
	    if (count($listofselectedthirdparties) == 1)
	    {
	        $liste=array();
	        $thirdpartyid=array_shift($listofselectedthirdparties);
	        $soc=new Societe($db);
	        $soc->fetch($thirdpartyid);
	        foreach ($soc->thirdparty_and_contact_email_array(1) as $key=>$value)
	        {
	            $liste[$key]=$value;
	        }
	        $formmail->withtoreadonly=0;
	    }
	    else
	    {
	        $formmail->withtoreadonly=1;
	    }
	    $formmail->withto=$liste;
	    $formmail->withtofree=0;
	    $formmail->withtocc=1;
	    $formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
	    $formmail->withtopic=$langs->transnoentities($topicmail, '__REF__', '__REFCLIENT__');
	    $formmail->withfile=$langs->trans("OnlyPDFattachmentSupported");
	    $formmail->withbody=1;
	    $formmail->withdeliveryreceipt=1;
	    $formmail->withcancel=1;
	    // Tableau des substitutions
	    $formmail->substit['__REF__']='__REF__';	// We want to keep the tag
	    $formmail->substit['__SIGNATURE__']=$user->signature;
	    $formmail->substit['__REFCLIENT__']='__REFCLIENT__';	// We want to keep the tag
	    $formmail->substit['__PERSONALIZED__']='';
	    $formmail->substit['__CONTACTCIVNAME__']='';
	
	    // Tableau des parametres complementaires du post
	    $formmail->param['action']=$action;
	    $formmail->param['models']=$modelmail;
	    $formmail->param['models_id']=GETPOST('modelmailselected','int');
	    $formmail->param['id']=join(',',$arrayofselected);
	    //$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;
	
	    print $formmail->get_form();
	
	    dol_fiche_end();
	}
	elseif ($massaction == 'createbills')
	{
		//var_dump($_REQUEST);
		print '<input type="hidden" name="massaction" value="confirm_createbills">';
		
		print '<table class="border" width="100%" >';
		print '<tr>';
		print '<td class="titlefieldmiddle">';
		print $langs->trans('DateInvoice');
		print '</td>';
		print '<td>';
		print $form->select_date('', '', '', '', '', '', 1, 1);
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td>';
		print $langs->trans('CreateOneBillByThird');
		print '</td>';
		print '<td>';
		print $form->selectyesno('createbills_onebythird', '', 1);
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td>';
		print $langs->trans('ValidateInvoices');
		print '</td>';
		print '<td>';
		print $form->selectyesno('valdate_invoices', 1, 1);
		print '</td>';
		print '</tr>';
		print '</table>';
		
		print '<br>';
		print '<div class="center">';
		print '<input type="submit" class="button" id="createbills" name="createbills" value="'.$langs->trans('CreateInvoiceForThisCustomer').'">  ';
		print '<input type="submit" class="button" id="cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
		print '</div>';
		print '<br>';
		
	}
	
	if ($sall)
    {
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
    }
	
	$moreforfilter='';

 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
		$moreforfilter.='<div class="divsearchfield">';
 		$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth300');
	 	$moreforfilter.='</div>';
 	}
	// If the user can view other users
	if ($user->rights->user->user->lire)
	{
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
	    $moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	 	$moreforfilter.='</div>';
	}
	// If the user can view prospects other than his'
	if ($conf->categorie->enabled && ($user->rights->produit->lire || $user->rights->service->lire))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('IncludingProductWithTag'). ': ';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, null, 'parent', null, null, 1);
		$moreforfilter.=$form->selectarray('search_product_category', $cate_arbo, $search_product_category, 1, 0, 0, '', 0, 0, 0, 0, '', 1);
		$moreforfilter.='</div>';
	}
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
	
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	// Fields title
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['c.ref']['checked']))            print_liste_field_titre($arrayfields['c.ref']['label'],$_SERVER["PHP_SELF"],'c.ref','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['c.ref_client']['checked']))     print_liste_field_titre($arrayfields['c.ref_client']['label'],$_SERVER["PHP_SELF"],'c.ref_client','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))            print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.town']['checked']))           print_liste_field_titre($arrayfields['s.town']['label'],$_SERVER["PHP_SELF"],'s.town','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['s.zip']['checked']))            print_liste_field_titre($arrayfields['s.zip']['label'],$_SERVER["PHP_SELF"],'s.zip','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['state.nom']['checked']))        print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['country.code_iso']['checked'])) print_liste_field_titre($arrayfields['country.code_iso']['label'],$_SERVER["PHP_SELF"],"country.code_iso","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['typent.code']['checked']))      print_liste_field_titre($arrayfields['typent.code']['label'],$_SERVER["PHP_SELF"],"typent.code","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.date_commande']['checked']))  print_liste_field_titre($arrayfields['c.date_commande']['label'],$_SERVER["PHP_SELF"],'c.date_commande','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.date_delivery']['checked']))  print_liste_field_titre($arrayfields['c.date_delivery']['label'],$_SERVER["PHP_SELF"],'c.date_livraison','',$param, 'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.total_ht']['checked']))       print_liste_field_titre($arrayfields['c.total_ht']['label'],$_SERVER["PHP_SELF"],'c.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.total_vat']['checked']))      print_liste_field_titre($arrayfields['c.total_vat']['label'],$_SERVER["PHP_SELF"],'c.tva','',$param, 'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.total_ttc']['checked']))      print_liste_field_titre($arrayfields['c.total_ttc']['label'],$_SERVER["PHP_SELF"],'c.total_ttc','',$param, 'align="right"',$sortfield,$sortorder);
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
	if (! empty($arrayfields['c.datec']['checked']))     print_liste_field_titre($arrayfields['c.datec']['label'],$_SERVER["PHP_SELF"],"c.date_creation","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.tms']['checked']))       print_liste_field_titre($arrayfields['c.tms']['label'],$_SERVER["PHP_SELF"],"c.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.fk_statut']['checked'])) print_liste_field_titre($arrayfields['c.fk_statut']['label'],$_SERVER["PHP_SELF"],"c.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
	if (! empty($arrayfields['c.facture']['checked']))   print_liste_field_titre($arrayfields['c.facture']['label'],$_SERVER["PHP_SELF"],'c.facture','',$param,'align="center"',$sortfield,$sortorder,'');
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print '</tr>'."\n";

	print '<tr class="liste_titre">';
	// Ref
	if (! empty($arrayfields['c.ref']['checked'])) 
	{
	    print '<td class="liste_titre">';
    	print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
	    print '</td>';
	}
	// Ref customer
	if (! empty($arrayfields['c.ref_client']['checked'])) 
	{
    	print '<td class="liste_titre" align="left">';
    	print '<input class="flat" type="text" size="6" name="search_ref_customer" value="'.$search_ref_customer.'">';
    	print '</td>';
	}
	// Thirpdarty
	if (! empty($arrayfields['s.nom']['checked'])) 
	{
    	print '<td class="liste_titre" align="left">';
    	print '<input class="flat" type="text" name="search_company" value="'.$search_company.'">';
    	print '</td>';
	}
	// Town
	if (! empty($arrayfields['s.town']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_town" value="'.$search_town.'"></td>';
	// Zip
	if (! empty($arrayfields['s.zip']['checked'])) print '<td class="liste_titre"><input class="flat" type="text" size="4" name="search_zip" value="'.$search_zip.'"></td>';
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
	// Date order
	if (! empty($arrayfields['c.date_commande']['checked']))
	{
    	print '<td class="liste_titre" align="center">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="orderday" value="'.$orderday.'">';
        print '<input class="flat" type="text" size="1" maxlength="2" name="ordermonth" value="'.$ordermonth.'">';
        $formother->select_year($orderyear?$orderyear:-1,'orderyear',1, 20, 5);
    	print '</td>';
	}
	if (! empty($arrayfields['c.date_delivery']['checked'])) 
	{
    	print '<td class="liste_titre" align="center">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="deliveryday" value="'.$deliveryday.'">';
        print '<input class="flat" type="text" size="1" maxlength="2" name="deliverymonth" value="'.$deliverymonth.'">';
        $formother->select_year($deliveryyear?$deliveryyear:-1,'deliveryyear',1, 20, 5);
    	print '</td>';
	}
	if (! empty($arrayfields['c.total_ht']['checked']))
	{
    	// Amount
    	print '<td class="liste_titre" align="right">';
    	print '<input class="flat" type="text" size="4" name="search_total_ht" value="'.$search_total_ht.'">';
    	print '</td>';
	}
	if (! empty($arrayfields['c.total_vat']['checked']))
	{
    	// Amount
    	print '<td class="liste_titre" align="right">';
    	print '<input class="flat" type="text" size="4" name="search_total_vat" value="'.$search_total_vat.'">';
    	print '</td>';
	}
	if (! empty($arrayfields['c.total_ttc']['checked']))
	{
    	// Amount
    	print '<td class="liste_titre" align="right">';
    	print '<input class="flat" type="text" size="5" name="search_total_ttc" value="'.$search_total_ttc.'">';
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
	if (! empty($arrayfields['c.datec']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Date modification
	if (! empty($arrayfields['c.tms']['checked']))
	{
	    print '<td class="liste_titre">';
	    print '</td>';
	}
	// Status
	if (! empty($arrayfields['c.fk_statut']['checked']))
	{
	    print '<td class="liste_titre maxwidthonsmartphone" align="right">';
    	$liststatus=array(
    	    Commande::STATUS_DRAFT=>$langs->trans("StatusOrderDraftShort"), 
    	    Commande::STATUS_VALIDATED=>$langs->trans("StatusOrderValidated"), 
    	    Commande::STATUS_ACCEPTED=>$langs->trans("StatusOrderSentShort"), 
    	    Commande::STATUS_CLOSED=>$langs->trans("StatusOrderDelivered"), 
    	    Commande::STATUS_CANCELED=>$langs->trans("StatusOrderCanceledShort")
    	);
    	print $form->selectarray('viewstatut', $liststatus, $viewstatut, -4);
	    print '</td>';
	}
	// Status billed
	if (! empty($arrayfields['c.facture']['checked']))
	{
	    print '<td class="liste_titre maxwidthonsmartphone" align="right">';
	    print $form->selectyesno('billed', $billed, 1, 0, 1);
	    print '</td>';
	}
	// Action column
	print '<td class="liste_titre" align="middle">';
	$searchpitco=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
	print $searchpitco;
	print '</td>';
	
    print "</tr>\n";

	$total=0;
	$subtotal=0;
    $productstat_cache=array();
    
    $generic_commande = new Commande($db);
    $generic_product = new Product($db);
	
    $i=0;
	$var=true;
	$totalarray=array();
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;
        print '<tr '.$bc[$var].'>';

        $notshippable=0;
        $warning = 0;
        $text_info='';
        $text_warning='';
        $nbprod=0;
        
        // Ref
        if (! empty($arrayfields['c.ref']['checked']))
        {
            print '<td class="nowrap">';
            $generic_commande->id=$obj->rowid;
            $generic_commande->ref=$obj->ref;
    	    $generic_commande->statut = $obj->fk_statut;
    	    $generic_commande->date_commande = $db->jdate($obj->date_commande);
    	    $generic_commande->date_livraison = $db->jdate($obj->date_delivery);
            $generic_commande->ref_client = $obj->ref_client;
            $generic_commande->total_ht = $obj->total_ht;
            $generic_commande->total_tva = $obj->total_tva;
            $generic_commande->total_ttc = $obj->total_ttc;
            $generic_commande->lines=array();
            $generic_commande->getLinesArray();
    
            print '<table class="nobordernopadding"><tr class="nocellnopadd">';
            print '<td class="nobordernopadding nowrap">';
            print $generic_commande->getNomUrl(1,($viewstatut != 2?0:$obj->fk_statut));
            print '</td>';
    		
            // Show shippable Icon (create subloop, so may be slow)
            if ($conf->stock->enabled)
            {
            	$langs->load("stocks");
	            if (($obj->fk_statut > 0) && ($obj->fk_statut < 3))
    	        {
                    $numlines = count($generic_commande->lines); // Loop on each line of order
                    for ($lig=0; $lig < $numlines; $lig++) 
                    {
                        if ($generic_commande->lines[$lig]->product_type == 0 && $generic_commande->lines[$lig]->fk_product > 0)  // If line is a product and not a service
                        {
                            $nbprod++; // order contains real products
                            $generic_product->id = $generic_commande->lines[$lig]->fk_product;

                            // Get local and virtual stock and store it into cache
                            if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product])) {
                                $generic_product->load_stock('nobatch');
                                //$generic_product->load_virtual_stock();   Already included into load_stock
                                $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_reel;
                                $productstat_cachevirtual[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_theorique;
                            } else {
                                $generic_product->stock_reel = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stock_reel'];
                                $generic_product->stock_theorique = $productstat_cachevirtual[$generic_commande->lines[$lig]->fk_product]['stock_reel'] = $generic_product->stock_theorique;
                            }

                            if (empty($conf->global->SHIPPABLE_ORDER_ICON_IN_LIST))  // Default code. Default is when this option is not set, setting it create strange result
                            {
                                $text_info .= $generic_commande->lines[$lig]->qty.' X '.$generic_commande->lines[$lig]->ref.'&nbsp;'.dol_trunc($generic_commande->lines[$lig]->product_label, 25);
                                $text_info .= ' - '.$langs->trans("Stock").': '.$generic_product->stock_reel;
                                $text_info .= ' - '.$langs->trans("VirtualStock").': '.$generic_product->stock_theorique;
                                $text_info .= '<br>';
                                
                                if ($generic_commande->lines[$lig]->qty > $generic_product->stock_reel) 
                                {
                                    $notshippable++;
                                }
                            }
                            else {  // Detailed code, looks bugged
                                // stock order and stock order_supplier
                                $stock_order=0;
                                $stock_order_supplier=0;
                                if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT) || ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE))    // What about other options ?
                                {
                                    if (! empty($conf->commande->enabled))
                                    {
                                        if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'])) {
                                            $generic_product->load_stats_commande(0,'1,2');
                                            $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'] = $generic_product->stats_commande['qty'];
                                        } else {
                                            $generic_product->stats_commande['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_customer'];
                                        }
                                        $stock_order=$generic_product->stats_commande['qty'];
                                    }
                                    if (! empty($conf->fournisseur->enabled))
                                    {
                                        if (empty($productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'])) {
                                            $generic_product->load_stats_commande_fournisseur(0,'3');
                                            $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'] = $generic_product->stats_commande_fournisseur['qty'];
                                        } else {
                                            $generic_product->stats_commande_fournisseur['qty'] = $productstat_cache[$generic_commande->lines[$lig]->fk_product]['stats_order_supplier'];
                                        }
                                        $stock_order_supplier=$generic_product->stats_commande_fournisseur['qty'];
                                    }
                                }
                                $text_info .= $generic_commande->lines[$lig]->qty.' X '.$generic_commande->lines[$lig]->ref.'&nbsp;'.dol_trunc($generic_commande->lines[$lig]->product_label, 25);
                                $text_stock_reel = $generic_product->stock_reel.'/'.$stock_order;
                                if ($stock_order > $generic_product->stock_reel && ! ($generic_product->stock_reel < $generic_commande->lines[$lig]->qty)) {
                                    $warning++;
                                    $text_warning.='<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
                                }
                                if ($generic_product->stock_reel < $generic_commande->lines[$lig]->qty) {
                                    $notshippable++;
                                    $text_info.='<span class="warning">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
                                } else {
                                    $text_info.='<span class="ok">'.$langs->trans('Available').'&nbsp;:&nbsp;'.$text_stock_reel.'</span>';
                                }
                                if (! empty($conf->fournisseur->enabled)) {
                                    $text_info.= '&nbsp;'.$langs->trans('SupplierOrder').'&nbsp;:&nbsp;'.$stock_order_supplier.'<br>';
                                } else {
                                    $text_info.= '<br>';
                                }
                            }
                        }
                    }
                    if ($notshippable==0) {
                        $text_icon = img_picto('', 'object_sending');
                        $text_info = $langs->trans('Shippable').'<br>'.$text_info;
                    } else {
                        $text_icon = img_picto('', 'error');
                        $text_info = $langs->trans('NonShippable').'<br>'.$text_info;
                    }
                }
                
                print '<td>';
                if ($nbprod)
                {
                    print $form->textwithtooltip('',$text_info,2,1,$text_icon,'',2);
                }
                if ($warning) {     // Always false in default mode
                    print $form->textwithtooltip('', $langs->trans('NotEnoughForAllOrders').'<br>'.$text_warning, 2, 1, img_picto('', 'error'),'',2);
                }
                print '</td>';
            }
    
            // Warning late icon and note
    		print '<td class="nobordernopadding nowrap">';
    		if ($generic_commande->hasDelay()) {
    			print img_picto($langs->trans("Late").' : '.$generic_commande->showDelay(), "warning");
    		}
    		if (!empty($obj->note_private) || !empty($obj->note_public))
    		{
    			print ' <span class="note">';
    			print '<a href="'.DOL_URL_ROOT.'/commande/note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
    			print '</span>';
    		}
    		print '</td>';
    
    		print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
    		$filename=dol_sanitizeFileName($obj->ref);
    		$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
    		$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
    		print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);
    		print '</td>';
    		print '</tr></table>';
    
    		print '</td>';
    		if (! $i) $totalarray['nbfield']++;
        }
        
		// Ref customer
		if (! empty($arrayfields['c.ref_client']['checked']))
		{
            print '<td>'.$obj->ref_client.'</td>';
    		if (! $i) $totalarray['nbfield']++;
		}

		$companystatic->id=$obj->socid;
        $companystatic->code_client = $obj->code_client;
		$companystatic->name=$obj->name;
		$companystatic->client=$obj->client;

		// Third party
		if (! empty($arrayfields['s.nom']['checked']))
		{
    		print '<td>';
    		print $companystatic->getNomUrl(1,'customer');
    
    		// If module invoices enabled and user with invoice creation permissions
    		if (! empty($conf->facture->enabled) && ! empty($conf->global->ORDER_BILLING_ALL_CUSTOMER))
    		{
    			if ($user->rights->facture->creer)
    			{
    				if (($obj->fk_statut > 0 && $obj->fk_statut < 3) || ($obj->fk_statut == 3 && $obj->billed == 0))
    				{
    					print '&nbsp;<a href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$companystatic->id.'">';
    					print img_picto($langs->trans("CreateInvoiceForThisCustomer").' : '.$companystatic->name, 'object_bill', 'hideonsmartphone').'</a>';
    				}
    			}
    		}
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
		
		// Order date
		if (! empty($arrayfields['c.date_commande']['checked']))
		{
    		print '<td align="center">';
    		print dol_print_date($db->jdate($obj->date_commande), 'day');
    		print '</td>';
    		if (! $i) $totalarray['nbfield']++;
		}
		// Plannned date of delivery
		if (! empty($arrayfields['c.date_delivery']['checked']))
		{
    		print '<td align="center">';
    		print dol_print_date($db->jdate($obj->date_delivery), 'day');
    		print '</td>';
    		if (! $i) $totalarray['nbfield']++;
		}
        // Amount HT
        if (! empty($arrayfields['c.total_ht']['checked']))
        {
		      print '<td align="right">'.price($obj->total_ht)."</td>\n";
		      if (! $i) $totalarray['nbfield']++;
		      if (! $i) $totalarray['totalhtfield']=$totalarray['nbfield'];
		      $totalarray['totalht'] += $obj->total_ht;
        }
        // Amount VAT
        if (! empty($arrayfields['c.total_vat']['checked']))
        {
            print '<td align="right">'.price($obj->total_tva)."</td>\n";
            if (! $i) $totalarray['nbfield']++;
		    if (! $i) $totalarray['totalvatfield']=$totalarray['nbfield'];
		    $totalarray['totalvat'] += $obj->total_tva;
        }
        // Amount TTC
        if (! empty($arrayfields['c.total_ttc']['checked']))
        {
            print '<td align="right">'.price($obj->total_ttc)."</td>\n";
            if (! $i) $totalarray['nbfield']++;
		    if (! $i) $totalarray['totalttcfield']=$totalarray['nbfield'];
		    $totalarray['totalttc'] += $obj->total_ttc;
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
                }
            }
        }
        // Fields from hook
        $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
        $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        // Date creation
        if (! empty($arrayfields['c.datec']['checked']))
        {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Date modification
        if (! empty($arrayfields['c.tms']['checked']))
        {
            print '<td align="center" class="nowrap">';
            print dol_print_date($db->jdate($obj->date_update), 'dayhour');
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Status
        if (! empty($arrayfields['c.fk_statut']['checked']))
        {
            print '<td align="right" class="nowrap">'.$generic_commande->LibStatut($obj->fk_statut, $obj->billed, 5, 1).'</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		// Billed
        if (! empty($arrayfields['c.facture']['checked']))
        {
            print '<td align="center">'.yn($obj->billed).'</td>';
            if (! $i) $totalarray['nbfield']++;
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
		
		print "</tr>\n";
        
		$total+=$obj->total_ht;
		$subtotal+=$obj->total_ht;
		$i++;
	}

	// Show total line
    	if (isset($totalarray['totalhtfield'])
 	   || isset($totalarray['totalvatfield'])
 	   || isset($totalarray['totalttcfield'])
 	   || isset($totalarray['totalamfield'])
 	   || isset($totalarray['totalrtpfield'])
 	   )
	{
	    print '<tr class="liste_total">';
	    $i=0;
	    while ($i < $totalarray['nbfield'])
	    {
	        $i++;
	        if ($i == 1)
	        {
	            if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
	            else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
	        }
	        elseif ($totalarray['totalhtfield'] == $i) print '<td align="right">'.price($totalarray['totalht']).'</td>';
	        elseif ($totalarray['totalvatfield'] == $i) print '<td align="right">'.price($totalarray['totalvat']).'</td>';
	        elseif ($totalarray['totalttcfield'] == $i) print '<td align="right">'.price($totalarray['totalttc']).'</td>';
	        else print '<td></td>';
	    }
	    print '</tr>';
	}

	$db->free($resql);
	
	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
				
	print '</table>'."\n";
	print '</div>';
	
	print '</form>'."\n";

	//print '<br>'.img_help(1,'').' '.$langs->trans("ToBillSeveralOrderSelectCustomer", $langs->transnoentitiesnoconv("CreateInvoiceForThisCustomer")).'<br>';
	
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files)
	{
	    /*
	     * Show list of available documents
	     */
	    $urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	    $urlsource.=str_replace('&amp;','&',$param);
	
	    $filedir=$diroutputmassaction;
	    $genallowed=$user->rights->commande->lire;
	    $delallowed=$user->rights->commande->lire;
	
	    print $formfile->showdocuments('massfilesarea_orders','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,$title,'');
	}
	else
	{
	    print '<br><a name="show_files"></a><a href="'.$_SERVER["PHP_SELF"].'?show_files=1'.$param.'#show_files">'.$langs->trans("ShowTempMassFilesArea").'</a>';
	}
	
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
