<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2016	   Ferran Marcet         <fmarcet@2byte.es>
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
 *	\file       	htdocs/supplier_proposal/list.php
 *	\ingroup    	supplier_proposal
 *	\brief      	Page of supplier proposals card and list
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
if (! empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load('companies');
$langs->load('supplier_proposal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');

$socid=GETPOST('socid','int');

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');
$search_author=GETPOST('search_author','alpha');
$search_status=GETPOST('viewstatut','alpha')?GETPOST('viewstatut','alpha'):GETPOST('search_status','int');
$object_statut=$db->escape(GETPOST('supplier_proposal_statut'));

$sall=GETPOST("sall");
$mesg=(GETPOST("msg") ? GETPOST("msg") : GETPOST("mesg"));
$year=GETPOST("year");
$month=GETPOST("month");
$yearvalid=GETPOST("yearvalid");
$monthvalid=GETPOST("monthvalid");

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='sp.date_livraison';
if (! $sortorder) $sortorder='DESC';

if ($object_statut != '') $search_status=$object_statut;

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='supplierproposallist';

// Security check
$module='supplier_proposal';
$dbtable='';
$objectid='';
if (! empty($user->societe_id))	$socid=$user->societe_id;
if (! empty($socid))
{
	$objectid=$socid;
	$module='societe';
	$dbtable='&societe';
}
$result = restrictedArea($user, $module, $objectid, $dbtable);

$diroutputmassaction=$conf->supplier_proposal->dir_output . '/temp/massgeneration/'.$user->id;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('supplier_proposallist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('supplier_proposal');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');


// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'p.ref'=>'Ref',
    's.nom'=>'Supplier',
    'pd.description'=>'Description',
    'p.note_private'=>"NotePrivate",
    'p.note_public'=>'NotePublic',
);

// TODO Use field of supplier proposal
$arrayfields=array(
    'p.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    's.nom'=>array('label'=>$langs->trans("Supplier"), 'checked'=>1),
    's.town'=>array('label'=>$langs->trans("Town"), 'checked'=>1),
    's.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>1),
    'state.nom'=>array('label'=>$langs->trans("StateShort"), 'checked'=>0),
    'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
    'typent.code'=>array('label'=>$langs->trans("ThirdPartyType"), 'checked'=>$checkedtypetiers),
    'p.date'=>array('label'=>$langs->trans("Date"), 'checked'=>1),
    'p.fin_validite'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1),
    'p.total_ht'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
    'p.total_vat'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>0),
    'p.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>0),
    'u.login'=>array('label'=>$langs->trans("Author"), 'checked'=>1, 'position'=>10),
    'p.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'p.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
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

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") ||GETPOST("button_removefilter")) // All tests are required to be compatible with all browsers
{
    $search_categ='';
    $search_user='';
    $search_sale='';
    $search_ref='';
    $search_societe='';
    $search_montant_ht='';
    $search_author='';
    $yearvalid='';
    $monthvalid='';
    $year='';
    $month='';
    $search_status='';
    $object_statut='';
}

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    $objectclass='SupplierProposal';
    $objectlabel='SupplierProposals';
    $permtoread = $user->rights->supplier_proposal->lire;
    $permtodelete = $user->rights->supplier_proposal->supprimer;
    $uploaddir = $conf->supplier_proposal->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}



/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$companystatic=new Societe($db);
$formcompany=new FormCompany($db);

llxHeader('',$langs->trans('CommRequest'),'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur');

$sql = 'SELECT';
if ($sall || $search_product_category > 0) $sql = 'SELECT DISTINCT';
$sql.= ' s.rowid as socid, s.nom as name, s.town, s.client, s.code_client,';
$sql.= " typent.code as typent_code,";
$sql.= " state.code_departement as state_code, state.nom as state_name,";
$sql.= ' sp.rowid, sp.note_private, sp.total_ht, sp.ref, sp.fk_statut, sp.fk_user_author, sp.date_valid, sp.date_livraison as dp,';
$sql.= " p.rowid as project_id, p.ref as project_ref,";
$sql.= " u.firstname, u.lastname, u.photo, u.login";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = s.fk_pays)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_typent as typent on (typent.id = s.fk_typent)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = s.fk_departement)";
$sql.= ', '.MAIN_DB_PREFIX.'supplier_proposal as sp';
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."supplier_proposal_extrafields as ef on (sp.rowid = ef.fk_object)";
if ($sall || $search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'supplier_proposaldet as pd ON sp.rowid=pd.fk_supplier_proposal';
if ($search_product_category > 0) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=pd.fk_product';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON sp.fk_user_author = u.rowid';
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = sp.fk_projet";
// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as c";
    $sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}
$sql.= ' WHERE sp.fk_soc = s.rowid';
$sql.= ' AND sp.entity IN ('.getEntity('supplier_proposal', 1).')';
if (! $user->rights->societe->client->voir && ! $socid) //restriction
{
	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
}
if ($search_ref)     $sql .= natural_search('sp.ref', $search_ref);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_author)  $sql .= natural_search('u.login', $search_author);
if ($search_montant_ht) $sql.= " AND sp.total_ht='".$db->escape(price2num(trim($search_montant_ht)))."'";
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($socid) $sql.= ' AND s.rowid = '.$socid;
if ($search_status >= 0 && $search_status != '') $sql.= ' AND sp.fk_statut IN ('.$search_status.')';
if ($month > 0)
{
    if ($year > 0 && empty($day))
    $sql.= " AND sp.date_livraison BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    $sql.= " AND sp.date_livraison BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    $sql.= " AND date_format(sp.date_livraison, '%m') = '".$month."'";
}
else if ($year > 0)
{
	$sql.= " AND sp.date_livraison BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($monthvalid > 0)
{
    if ($yearvalid > 0 && empty($dayvalid))
    $sql.= " AND sp.date_valid BETWEEN '".$db->idate(dol_get_first_day($yearvalid,$monthvalid,false))."' AND '".$db->idate(dol_get_last_day($yearvalid,$monthvalid,false))."'";
    else if ($yearvalid > 0 && ! empty($dayvalid))
    $sql.= " AND sp.date_valid BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $monthvalid, $dayvalid, $yearvalid))."' AND '".$db->idate(dol_mktime(23, 59, 59, $monthvalid, $dayvalid, $yearvalid))."'";
    else
    $sql.= " AND date_format(sp.date_valid, '%m') = '".$monthvalid."'";
}
else if ($yearvalid > 0)
{
	$sql.= " AND sp.date_valid BETWEEN '".$db->idate(dol_get_first_day($yearvalid,1,false))."' AND '".$db->idate(dol_get_last_day($yearvalid,12,false))."'";
}
if ($search_sale > 0) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
    $sql.= " AND c.fk_c_type_contact = tc.rowid AND tc.element='supplier_proposal' AND tc.source='internal' AND c.element_id = sp.rowid AND c.fk_socpeople = ".$search_user;
}

$sql.= $db->order($sortfield,$sortorder);
$sql.=', sp.ref DESC';

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit + 1,$offset);

$result=$db->query($sql);
if ($result)
{
	$objectstatic=new SupplierProposal($db);
	$userstatic=new User($db);

	if ($socid > 0)
	{
	    $soc = new Societe($db);
	    $soc->fetch($socid);
	    $title = $langs->trans('ListOfProposals') . ' - '.$soc->name;
	}
	else
	{
	    $title = $langs->trans('ListOfProposals');
	}
	
	$num = $db->num_rows($result);

	$arrayofselected=is_array($toselect)?$toselect:array();
	
	$param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($sall)				 $param.='&sall='.$sall;
	if ($month)              $param.='&month='.$month;
	if ($year)               $param.='&year='.$year;
    if ($search_ref)         $param.='&search_ref=' .$search_ref;
    if ($search_societe)     $param.='&search_societe=' .$search_societe;
	if ($search_user > 0)    $param.='&search_user='.$search_user;
	if ($search_sale > 0)    $param.='&search_sale='.$search_sale;
	if ($search_montant_ht)  $param.='&search_montant_ht='.$search_montant_ht;
	if ($search_author)  	 $param.='&search_author='.$search_author;
	if ($socid > 0)          $param.='&socid='.$socid;
	if ($search_status != '') $param.='&search_status='.$search_status;
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
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
	    'builddoc'=>$langs->trans("PDFMerge"),
	);
	if ($user->rights->supplier_proposal->supprimer) $arrayofmassactions['delete']=$langs->trans("Delete");
	if ($massaction == 'presend') $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);
	
	// Lignes des champs de filtre
	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_commercial.png', 0, '', '', $limit);

	if ($massaction == 'presend')
	{
	    $langs->load("mails");
	
	    if (! GETPOST('cancel'))
	    {
	        $objecttmp=new SupplierProposal($db);
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
	
	    $topicmail="SendProposalRef";
	    $modelmail="propal_send";
	
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
	
	if ($sall)
	{
	    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	    print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
	}
	
	$i = 0;

	$moreforfilter='';

 	// If the user can view prospects other than his'
 	if ($user->rights->societe->client->voir || $socid)
 	{
 		$langs->load("commercial");
	 	$moreforfilter.='<div class="divsearchfield">';
	 	$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user, 0, 1, 'maxwidth300');
	 	$moreforfilter.='</div>';
 	}
	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
	{
	    $moreforfilter.='<div class="divsearchfield">';
	    $moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
	    $moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
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
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">';
    
	// Fields title
    print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Ref'),$_SERVER["PHP_SELF"],'sp.ref','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Supplier'),$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Date'),$_SERVER["PHP_SELF"],'sp.date_valid','',$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('SupplierProposalDate'),$_SERVER["PHP_SELF"],'sp.date_livraison','',$param, 'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('AmountHT'),$_SERVER["PHP_SELF"],'sp.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Author'),$_SERVER["PHP_SELF"],'u.login','',$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('Status'),$_SERVER["PHP_SELF"],'sp.fk_statut','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('', $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="12" name="search_societe" value="'.$search_societe.'">';
	print '</td>';

	// Date valid
	print '<td class="liste_titre" colspan="1" align="center">';
	//print $langs->trans('Month').': ';
	print '<input class="flat" type="text" size="1" maxlength="2" name="monthvalid" value="'.$monthvalid.'">';
	//print '&nbsp;'.$langs->trans('Year').': ';
	$syearvalid = $yearvalid;
	$formother->select_year($syearvalid,'yearvalid',1, 20, 5);
	print '</td>';
	
	// Date
	print '<td class="liste_titre" colspan="1" align="center">';
	//print $langs->trans('Month').': ';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	//print '&nbsp;'.$langs->trans('Year').': ';
	$syear = $year;
	$formother->select_year($syear,'year',1, 20, 5);
	print '</td>';

	// Amount
	print '<td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$search_montant_ht.'">';
	print '</td>';
	// Author
	print '<td class="liste_titre" align="center">';
	print '<input class="flat" size="10" type="text" name="search_author" value="'.$search_author.'">';
	print '</td>';
	print '<td class="liste_titre" align="right">';
	$formpropal->selectProposalStatus($search_status,1,0,1,'supplier','search_status');
	print '</td>';
	// Check boxes
	print '<td class="liste_titre" align="middle">';
	$searchpitco=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
	print $searchpitco;
	print '</td>';

	print "</tr>\n";

	$now = dol_now();
	$var=true;
	$total=0;
	$subtotal=0;
	$totalarray=array();
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($result);
		$var=!$var;

		$objectstatic->id=$obj->rowid;
		$objectstatic->ref=$obj->ref;

		print '<tr '.$bc[$var].'>';
		print '<td class="nowrap">';

		print '<table class="nobordernopadding"><tr class="nocellnopadd">';
		// Picto + Ref
		print '<td class="nobordernopadding nowrap">';
		print $objectstatic->getNomUrl(1);
		print '</td>';
		// Warning
		$warnornote='';
		if ($obj->fk_statut == 1 && $db->jdate($obj->date_valid) < ($now - $conf->supplier_proposal->warning_delay)) $warnornote.=img_warning($langs->trans("Late"));
		if (! empty($obj->note_private))
		{
			$warnornote.=($warnornote?' ':'');
			$warnornote.= '<span class="note">';
			$warnornote.= '<a href="note.php?id='.$obj->rowid.'">'.img_picto($langs->trans("ViewPrivateNote"),'object_generic').'</a>';
			$warnornote.= '</span>';
		}
		if ($warnornote)
		{
			print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
			print $warnornote;
			print '</td>';
		}
		// Other picto tool
		print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
		$filename=dol_sanitizeFileName($obj->ref);
		$filedir=$conf->supplier_proposal->dir_output . '/' . dol_sanitizeFileName($obj->ref);
		$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
		print $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
		print '</td></tr></table>';

		print "</td>\n";

		$url = DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid;

		// Company
		$companystatic->id=$obj->socid;
		$companystatic->name=$obj->name;
		$companystatic->client=$obj->client;
		$companystatic->code_client=$obj->code_client;
		print '<td>';
		print $companystatic->getNomUrl(1,'customer');
		print '</td>';

		// Date
		print '<td align="center">';
		print dol_print_date($db->jdate($obj->date_valid), 'day');
		print "</td>\n";
		
		// Date delivery
		print '<td align="center">';
		print dol_print_date($db->jdate($obj->dp), 'day');
		print "</td>\n";

		print '<td align="right">'.price($obj->total_ht)."</td>\n";

		$userstatic->id=$obj->fk_user_author;
		$userstatic->login=$obj->login;
		print '<td align="center">';
		if ($userstatic->id) print $userstatic->getLoginUrl(1);
		else print '&nbsp;';
		print "</td>\n";

		print '<td align="right">'.$objectstatic->LibStatut($obj->fk_statut,5)."</td>\n";

        // Action column
        print '<td class="nowrap" align="center">';
        if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
        {
            $selected=0;
    		if (in_array($obj->rowid, $arrayofselected)) $selected=1;
    		print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
        }
        print '</td>';

		print "</tr>\n";

		$total += $obj->total_ht;
		$subtotal += $obj->total_ht;

		$i++;
	}

	if ($total>0)
	{
		if($num<$limit){
			$var=!$var;
			print '<tr class="liste_total"><td align="left">'.$langs->trans("TotalHT").'</td>';
			print '<td colspan="4" align="right">'.price($total).'</td><td colspan="3"></td>';
			print '</tr>';
		}
		else
		{
			$var=!$var;
			print '<tr class="liste_total"><td align="left">'.$langs->trans("TotalHTforthispage").'</td>';
			print '<td colspan="4" align="right">'.price($total).'</td><td colspan="3"></td>';
			print '</tr>';
		}

	}

	$db->free($resql);
	
	$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	
	print '</table>'."\n";
    print '</div>'."\n";

	print '</form>'."\n";
	
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files)
	{
	    /*
	     * Show list of available documents
	     */
	    $urlsource=$_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	    $urlsource.=str_replace('&amp;','&',$param);
	
	    $filedir=$diroutputmassaction;
	    $genallowed=$user->rights->propal->lire;
	    $delallowed=$user->rights->propal->lire;
	
	    print $formfile->showdocuments('massfilesarea_proposals','',$filedir,$urlsource,0,$delallowed,'',1,1,0,48,1,$param,'','');
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
