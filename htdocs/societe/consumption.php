<?php
/* Copyright (C) 2012-2013 Philippe Berthet     <berthet@systune.be>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * Version V1.1 Initial version of Philippe Berthet
 * Version V2   Change to be compatible with 3.4 and enhanced to be more generic
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
 *	\file       htdocs/societe/consumption.php
 *  \ingroup    societe
 *	\brief      Add a tab on thirpdarty view to list all products/services bought or sells by thirdparty
 */

require("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid, '&societe');
$object = new Societe($db);
if ($socid > 0) $object->fetch($socid);

// Sort & Order fields
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='datePrint';
$limit = $conf->liste_limit;

// Search fields
$sref=GETPOST("sref");
$sprod_fulldescr=GETPOST("sprod_fulldescr");
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');

// Clean up on purge search criteria ?
if (GETPOST("button_removefilter"))
{
    $sref='';
    $sprod_fulldescr='';
    $year='';
    $month='';
}
// Customer or supplier selected in drop box
$thirdTypeSelect = GETPOST("third_select_id");
$type_element = GETPOST('type_element')?GETPOST('type_element'):'invoice';

$langs->load("companies");
$langs->load("bills");
$langs->load("orders");
$langs->load("suppliers");


/*
 * Actions
 */



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$productstatic=new Product($db);

$titre = $langs->trans("Referer",$object->name);
llxHeader('',$titre,'');

if (empty($socid))
{
	dol_print_error($db);
	exit;
}

$head = societe_prepare_head($object);
dol_fiche_head($head, 'consumption', $langs->trans("ThirdParty"),0,'company');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="socid" value="'.$socid.'">';

print '<table class="border" width="100%">';
print '<tr><td width="25%">'.$langs->trans('ThirdPartyName').'</td>';
print '<td colspan="3">';
print $form->showrefnav($object,'socid','',($user->societe_id?0:1),'rowid','nom');
print '</td></tr>';

if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
{
	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$object->prefix_comm.'</td></tr>';
}

if ($object->client)
{
	print '<tr><td>';
	print $langs->trans('CustomerCode').'</td><td colspan="3">';
	print $object->code_client;
	if ($object->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
	print '</td></tr>';
	$sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."facture where fk_soc = ".$socid;
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);

	$obj = $db->fetch_object($resql);
	$nbFactsClient = $obj->nb;
	$thirdTypeArray['customer']=$langs->trans("customer");
	if ($conf->facture->enabled) $elementTypeArray['invoice']=$langs->trans('Invoices');
	if ($conf->commande->enabled) $elementTypeArray['order']=$langs->trans('Orders');
}

if ($object->fournisseur)
{
	print '<tr><td>';
	print $langs->trans('SupplierCode').'</td><td colspan="3">';
	print $object->code_fournisseur;
	if ($object->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
	print '</td></tr>';
	$sql = "SELECT count(*) as nb from ".MAIN_DB_PREFIX."commande_fournisseur where fk_soc = ".$socid;
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);

	$obj = $db->fetch_object($resql);
	$nbCmdsFourn = $obj->nb;
	$thirdTypeArray['supplier']=$langs->trans("supplier");
	if ($conf->fournisseur->enabled) $elementTypeArray['supplier_invoice']=$langs->trans('SuppliersInvoices');
	if ($conf->fournisseur->enabled) $elementTypeArray['supplier_order']=$langs->trans('SuppliersOrders');
}
print '</table>';

dol_fiche_end();
print '<br>';


$sql_select='';
if ($type_element == 'invoice')
{ // Customer : show products from invoices
$documentstatic=new Facture($db);
$sql_select = 'SELECT f.rowid as doc_id, f.facnumber as doc_number, f.type as doc_type, f.datef as datePrint, ';
$tables_from = MAIN_DB_PREFIX."facture as f,".MAIN_DB_PREFIX."facturedet as d";
$where = " WHERE f.fk_soc = s.rowid AND s.rowid = ".$socid;
$where.= " AND d.fk_facture = f.rowid";
$where.= " AND f.entity = ".$conf->entity;
$datePrint = 'f.datef';
$doc_number='f.facnumber';
$thirdTypeSelect='customer';
}
if ($type_element == 'order')
{
	// TODO

}
if ($type_element == 'supplier_order')
{ // Supplier : Show products from orders.
$documentstatic=new CommandeFournisseur($db);
$sql_select = 'SELECT c.rowid as doc_id, c.ref as doc_number, "1" as doc_type, c.date_valid as datePrint, ';
$tables_from = MAIN_DB_PREFIX."commande_fournisseur as c,".MAIN_DB_PREFIX."commande_fournisseurdet as d";
$where = " WHERE c.fk_soc = s.rowid AND s.rowid = ".$socid;
$where.= " AND d.fk_commande = c.rowid";
$datePrint = 'c.date_creation';
$doc_number='c.ref';
$thirdTypeSelect='supplier';
}
if ($type_element == 'supplier_invoice')
{
	// TODO

}

$sql = $sql_select;
$sql.= ' d.fk_product as product_id, d.fk_product as fk_product, d.label, d.description as description, d.info_bits, d.date_start, d.date_end, d.qty, d.qty as prod_qty,';
$sql.= ' p.ref as ref, p.rowid as prod_id, p.rowid as fk_product, p.fk_product_type as prod_type, p.fk_product_type as fk_product_type,';
$sql.= " s.rowid as socid, p.ref as prod_ref, p.label as product_label";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".$tables_from;
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON d.fk_product = p.rowid ';
$sql.= $where;
if ($month > 0) {
	if ($year > 0) {
		$start = dol_mktime(0, 0, 0, $month, 1, $year);
		$end = dol_time_plus_duree($start,1,'m') - 1;
		$sql.= " AND ".$datePrint." BETWEEN '".$db->idate($start)."' AND '".$db->idate($end)."'";
	} else {
		$sql.= " AND date_format(".$datePrint.", '%m') = '".sprintf('%02d',$month)."'";
	}
} else if ($year > 0) {
	$start = dol_mktime(0, 0, 0, 1, 1, $year);
	$end = dol_time_plus_duree($start,1,'y') - 1;
	$sql.= " AND ".$datePrint." BETWEEN '".$db->idate($start)."' AND '".$db->idate($end)."'";
}
if ($sref) $sql.= " AND ".$doc_number." LIKE '%".$sref."%'";
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1, $offset);


// Define type of elements
$typeElementString = $form->selectarray("type_element",$elementTypeArray,GETPOST('type_element'));
$button = '<input type="submit" class="button" name="button_third" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
$param="&amp;sref=".$sref."&amp;month=".$month."&amp;year=".$year."&amp;sprod_fulldescr=".$sprod_fulldescr."&amp;socid=".$socid;

print_barre_liste($langs->trans('ProductsIntoElements', $typeElementString.' '.$button), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num, '', '');

if ($sql_select)
{
	dol_syslog("sql=".$sql);
	$resql=$db->query($sql);
	if (!$resql) dol_print_error($db);
}

print '<table class="liste" width="100%">'."\n";
// Titles with sort buttons
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'doc_number','',$param,'align="left"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'datePrint','',$param,'align="center" width="150"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Product'),$_SERVER['PHP_SELF'],'','',$param,'align="left"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans('Quantity'),$_SERVER['PHP_SELF'],'prod_qty','',$param,'align="right"',$sortfield,$sortorder);
// Filters
print '<tr class="liste_titre">';
print '<td class="liste_titre" align="left">';
print '<input class="flat" type="text" name="sref" size="8" value="'.$sref.'">';
print '</td>';
print '<td class="liste_titre">'; // date
print $formother->select_month($month?$month:-1,'month',1);
$formother->select_year($year?$year:-1,'year',1, 20, 1);
print '</td>';
print '<td class="liste_titre" align="left">';
print '<input class="flat" type="text" name="sprod_fulldescr" size="15" value="'.$sprod_fulldescr.'">';
print '</td>';
print '<td class="liste_titre" align="right">';
print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("resetFilters")).'" title="'.dol_escape_htmltag($langs->trans("resetFilters")).'">';
print '</td>';
print '</tr>';

if ($sql_select)
{
	$var=true;
	$num = $db->num_rows($resql);
	$i = 0;
	while (($objp = $db->fetch_object($resql)) && $i < $conf->liste_limit )
	{
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td class="nobordernopadding nowrap" width="100">';
		$documentstatic->id=$objp->doc_id;
		$documentstatic->ref=$objp->doc_number;
		$documentstatic->type=$objp->type;
		print $documentstatic->getNomUrl(1);
		print '</td>';
		print '<td align="center" width="80">'.dol_print_date($db->jdate($objp->datePrint),'day').'</td>';

		print '<td>';

		// Define text, description and type
		$text=''; $description=''; $type=0;

		// Code to show product duplicated from commonobject->printObjectLine
		if ($objp->fk_product > 0)
		{
			$product_static = new Product($db);

			$product_static->type=$objp->fk_product_type;
			$product_static->id=$objp->fk_product;
			$product_static->ref=$objp->ref;
			$text=$product_static->getNomUrl(1);
		}

		// Product
		if ($objp->fk_product > 0)
		{
			// Define output language
			if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
			{
				$prod = new Product($db);
				$prod->fetch($objp->fk_product);

				$outputlangs = $langs;
				$newlang='';
				if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
				if (empty($newlang)) $newlang=$object->default_lang;
				if (! empty($newlang))
				{
					$outputlangs = new Translate("",$conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$label = (! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $objp->product_label;
			}
			else
			{
				$label = $objp->product_label;
			}

			$text.= ' - '.(! empty($objp->label)?$objp->label:$label);
			$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($objp->description));
		}

		if (($objp->info_bits & 2) == 2) { ?>
			<a href="<?php echo DOL_URL_ROOT.'/comm/remx.php?id='.$object->id; ?>">
			<?php
			$txt='';
			print img_object($langs->trans("ShowReduc"),'reduc').' ';
			if ($objp->description == '(DEPOSIT)') $txt=$langs->trans("Deposit");
			//else $txt=$langs->trans("Discount");
			print $txt;
			?>
			</a>
			<?php
			if ($objp->description)
			{
				if ($objp->description == '(CREDIT_NOTE)' && $objp->fk_remise_except > 0)
				{
					$discount=new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo ($txt?' - ':'').$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
				}
				elseif ($objp->description == '(DEPOSIT)' && $objp->fk_remise_except > 0)
				{
					$discount=new DiscountAbsolute($db);
					$discount->fetch($objp->fk_remise_except);
					echo ($txt?' - ':'').$langs->transnoentities("DiscountFromDeposit",$discount->getNomUrl(0));
					// Add date of deposit
					if (! empty($conf->global->INVOICE_ADD_DEPOSIT_DATE)) echo ' ('.dol_print_date($discount->datec).')';
				}
				else
				{
					echo ($txt?' - ':'').dol_htmlentitiesbr($objp->description);
				}
			}
		}
		else
		{
			if ($objp->fk_product > 0) {

				echo $form->textwithtooltip($text,$description,3,'','',$i,0,'');

				// Show range
				echo get_date_range($objp->date_start, $objp->date_end);

				// Add description in form
				if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
				{
					print (! empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
				}

			} else {

				//if (! empty($objp->fk_parent_line)) echo img_picto('', 'rightarrow');
				if ($type==1) $text = img_object($langs->trans('Service'),'service');
				else $text = img_object($langs->trans('Product'),'product');

				if (! empty($objp->label)) {
					$text.= ' <strong>'.$objp->label.'</strong>';
					echo $form->textwithtooltip($text,dol_htmlentitiesbr($objp->description),3,'','',$i,0,'');
				} else {
					echo $text.' '.dol_htmlentitiesbr($objp->description);
				}

				// Show range
				echo get_date_range($objp->date_start,$objp->date_end);
			}
		}

		/*
		$prodreftxt='';
		if ($objp->prod_id > 0)
		{
			$productstatic->id = $objp->prod_id;
			$productstatic->ref = $objp->prod_ref;
			$productstatic->status = $objp->prod_type;
			$prodreftxt = $productstatic->getNomUrl(0);
			if(!empty($objp->product_label)) $prodreftxt .= ' - '.$objp->product_label;
		}
		// Show range
		$prodreftxt .= get_date_range($objp->date_start, $objp->date_end);
		// Add description in form
		if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
		{
			$prodreftxt .= (! empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
		}
		*/
		print '</td>';

		//print '<td align="left">'.$prodreftxt.'</td>';

		print '<td align="right">'.$objp->prod_qty.'</td>';

		print "</tr>\n";
		$i++;
	}
	if ($num > $conf->liste_limit) {
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num);
	}
	$db->free($resql);
}
else {
	print '<tr><td colspan="4">'.$langs->trans("FeatureNotYetAvailable").'</td></tr>';
}

print "</table>";
print "</form>";


/*
 * Errors
 */

dol_htmloutput_errors($warning);
dol_htmloutput_errors($error,$errors);

llxFooter();

$db->close();
?>
