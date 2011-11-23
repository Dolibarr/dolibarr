<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010      Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/compta/propal.php
 *	\ingroup    propale
 *	\brief      Page liste des propales (vision compta)
 */

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/propal.lib.php");
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');

$langs->load('companies');
$langs->load('compta');
$langs->load('orders');
$langs->load('bills');

$id=GETPOST('id');
$ref=GETPOST('ref');
$socid=GETPOST('socid');
$action=GETPOST('action');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$viewstatut=$_GET['viewstatut'];
$propal_statut = $_GET['propal_statut'];
if($propal_statut != '')
$viewstatut=$propal_statut;

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.datep";

$module='propale';
if (! empty($_GET["socid"]))
{
	$objectid=$_GET["socid"];
	$module='societe';
	$dbtable='';
}
else if (! empty($id))
{
	$objectid=$id;
	$module='propale';
	$dbtable='propal';
}

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, $module, $objectid, $dbtable);

$object = new Propal($db);


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($action == 'setstatut')
{
	// Close proposal
	$object->id = $id;
	$object->cloture($user, $_GET["statut"], $note);

}

// Set project
if ($action == 'classin')
{
	$object->fetch($id);
	$object->setProject($_POST['projectid']);
}



/*
 * View
 */

$now=gmmktime();

llxHeader();

$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$societestatic=new Societe($db);
$propalstatic=new Propal($db);

$now=gmmktime();

if ($id > 0 || ! empty($ref))
{
	if ($mesg) print "$mesg<br>";

	$product_static=new Product($db);

	$object->fetch($id,$ref);

	$societe = new Societe($db);
	$societe->fetch($object->socid);

	$head = propal_prepare_head($object);
	dol_fiche_head($head, 'compta', $langs->trans('Proposal'), 0, 'propal');


	/*
	 * Proposal card
	 */
	print '<table class="border" width="100%">';

	$linkback="<a href=\"".$_SERVER["PHP_SELF"]."?page=$page&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("BackToList")."</a>";

	// Ref
	print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="5">';
	print $form->showrefnav($object,'ref',$linkback,1,'ref','ref','');
	print '</td></tr>';

	// Ref client
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
	print $langs->trans('RefCustomer').'</td><td align="left">';
	print '</td>';
	if ($action != 'refclient' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refclient&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="5">';
	print $object->ref_client;
	print '</td>';
	print '</tr>';

	$rowspan=8;

	// Company
	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$societe->getNomUrl(1).'</td></tr>';

	// Ligne info remises tiers
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
	if ($societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$societe->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	$absolute_discount=$societe->getAvailableDiscounts();
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

	// Dates
	print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
	print dol_print_date($object->date,'daytext');
	print '</td>';

	if ($conf->projet->enabled) $rowspan++;

	//Local taxes
	if ($mysoc->pays_code=='ES')
	{
		if($mysoc->localtax1_assuj=="1") $rowspan++;
		if($mysoc->localtax2_assuj=="1") $rowspan++;
	}

	// Note
	print '<td valign="top" colspan="2" width="50%" rowspan="'.$rowspan.'">'.$langs->trans('NotePublic').' :<br>'. nl2br($object->note_public).'</td>';
	print '</tr>';

	// Date fin propal
	print '<tr>';
	print '<td>'.$langs->trans('DateEndPropal').'</td><td colspan="3">';
	if ($object->fin_validite)
	{
		print dol_print_date($object->fin_validite,'daytext');
		if ($object->statut == 1 && $object->fin_validite < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
	}
	else
	{
		print $langs->trans("Unknown");
	}
	print '</td>';
	print '</tr>';

	// Payment term
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($action != 'editconditions' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editconditions')
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'cond_reglement_id');
	}
	else
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'none');
	}
	print '</td>';

	// Payment mode
	print '<tr>';
	print '<td width="25%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editmode')
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
	}
	else
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'none');
	}
	print '</td></tr>';

	// Project
	if ($conf->projet->enabled)
	{
		$langs->load("projects");
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project').'</td>';
		if (1 == 2 && $user->rights->propale->creer)
		{
			if ($action != 'classify') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($action == 'classify')
			{
				$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'projectid');
			}
			else
			{
				$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none');
			}
			print '</td></tr>';
		}
		else
		{
			print '</td></tr></table>';
			if (!empty($object->fk_project))
			{
				print '<td colspan="3">';
				$project = new Project($db);
				$project->fetch($object->fk_project);
				print '<a href="../projet/fiche.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
				print $project->ref;
				print '</a>';
				print '</td>';
			}
			else
			{
				print '<td colspan="3">&nbsp;</td>';
			}
		}
		print '</tr>';
	}

	// Amount
	print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
	print '<td align="right" colspan="2"><b>'.price($object->total_ht).'</b></td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

	print '<tr><td height="10">'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2">'.price($object->total_tva).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

	// Amount Local Taxes
	if ($mysoc->pays_code=='ES')
	{
		if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
		{
			print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->pays_code).'</td>';
			print '<td align="right" colspan="2">'.price($object->total_localtax1).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
		}
		if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
		{
			print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->pays_code).'</td>';
			print '<td align="right" colspan="2">'.price($object->total_localtax2).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
		}
	}


	print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2">'.price($object->total_ttc).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';


	// Statut
	print '<tr><td height="10">'.$langs->trans('Status').'</td><td align="left" colspan="3">'.$object->getLibStatut(4).'</td></tr>';
	print '</table><br>';

	/*
	 * Lines
	 */
	print '<table class="noborder" width="100%">';

	$sql = 'SELECT pt.rowid, pt.description, pt.price, pt.fk_product, pt.fk_remise_except,';
	$sql.= ' pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, pt.info_bits,';
	$sql.= ' pt.total_ht, pt.total_tva, pt.total_ttc,';
	$sql.= ' pt.product_type,';
	$sql.= ' p.rowid as prodid, p.label as product_label, p.ref, p.fk_product_type, ';
	$sql.= ' p.description as product_desc';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
	$sql.= ' WHERE pt.fk_propal = '.$object->id;
	$sql.= ' ORDER BY pt.rang ASC, pt.rowid';
	$resql = $db->query($sql);
	if ($resql)
	{
		$num_lignes = $db->num_rows($resql);
		$i = 0;
		$total = 0;

		if ($num_lignes)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Description').'</td>';
			print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
			print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
			print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
			print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
			print '<td align="right" nowrap="nowrap">'.$langs->trans('AmountHT').'</td>';
			print '<td width="16">&nbsp;</td>';
			print '<td width="16">&nbsp;</td>';
			print '<td width="16">&nbsp;</td>';
			print "</tr>\n";
		}
		$var=true;
		while ($i < $num_lignes)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;

			// Show product and description
			$type=$objp->product_type?$objp->product_type:$objp->fk_product_type;
			// Try to enhance type detection using date_start and date_end for free lines where type
			// was not saved.
			if (! empty($objp->date_start)) $type=1;
			if (! empty($objp->date_end)) $type=1;

			if ($action != 'editline' || $_GET['rowid'] != $objp->rowid)
			{
				print '<tr '.$bc[$var].'>';
				if ($objp->fk_product > 0)
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne;

					// Show product and description
					$product_static->type=$objp->fk_product_type;
					$product_static->id=$objp->fk_product;
					$product_static->ref=$objp->ref;
					$product_static->libelle=$objp->product_label;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$objp->product_label;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description));
					print $form->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($objp->date_start,$objp->date_end);

					// Add description in form
					if ($conf->global->PRODUIT_DESC_IN_FORM)
					{
						print ($objp->description && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
					}
					print '</td>';
				}
				else
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
					if (($objp->info_bits & 2) == 2)
					{
						print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$object->socid.'">';
						print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
						print '</a>';
						if ($objp->description)
						{
							if ($objp->description == '(CREDIT_NOTE)')
							{
								$discount=new DiscountAbsolute($db);
								$discount->fetch($objp->fk_remise_except);
								print ' - '.$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
							}
							else
							{
								print ' - '.nl2br($objp->description);
							}
						}
					}
					else
					{
						if ($type==1) $text = img_object($langs->trans('Service'),'service');
						else $text = img_object($langs->trans('Product'),'product');
						print $text.' '.nl2br($objp->description);

						// Show range
						print_date_range($objp->date_start,$objp->date_end);
					}
					print "</td>\n";
				}
				print '<td align="right">'.vatrate($objp->tva_tx).'%</td>';
				print '<td align="right">'.price($objp->subprice)."</td>\n";

				// Qty
				print '<td align="right">';
				if (($objp->info_bits & 2) != 2)
				{
					print $objp->qty;
				}
				else print '&nbsp;';
				print '</td>';

				if ($objp->remise_percent > 0)
				{
					print '<td align="right">'.$objp->remise_percent."%</td>\n";
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				print '<td align="right">'.price($objp->total_ht)."</td>\n";

				print '<td colspan="3">&nbsp;</td>';

				print '</tr>';
			}

			$i++;
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print '</table>';


	print '</div>';


	/*
	 * Boutons Actions
	 */
	print '<div class="tabsAction">';

	if ($object->statut <> 4 && $user->societe_id == 0)
	{
		if ($object->statut == 2 && $user->rights->facture->creer)
		{
			print '<a class="butAction" href="facture.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid.'">'.$langs->trans("BuildBill").'</a>';
		}

		$arraypropal=$object->getInvoiceArrayList();
		if ($object->statut == 2 && is_array($arraypropal) && count($arraypropal) > 0)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=setstatut&statut=4&socid='.$object->socid.'">'.$langs->trans("ClassifyBilled").'</a>';
		}
	}
	print "</div>";
	print "<br>\n";



	print '<table width="100%"><tr><td width="50%" valign="top">';

	/*
	 * Documents generes
	 */
	$filename=dol_sanitizeFileName($object->ref);
	$filedir=$conf->propale->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
	$genallowed=0;
	$delallowed=0;

	$var=true;

	$somethingshown=$formfile->show_documents('propal',$filename,$filedir,$urlsource,$genallowed,$delallowed);


	/*
	 * Linked object block
	 */
	$somethingshown=$object->showLinkedObjectBlock();

	print '</td><td valign="top" width="50%">';

	// List of actions on element
	include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
	$formactions=new FormActions($db);
	$somethingshown=$formactions->showactions($object,'propal',$socid);

	print '</td></tr></table>';


}
else
{
	/**
	 *
	 * Mode List
	 *
	 */

	$now=dol_now();

	$limit = $conf->liste_limit;
	$offset = $limit * $page ;
	$pageprev = $page - 1;
	$pagenext = $page + 1;

	$year = $_REQUEST["year"];
	$month = $_REQUEST["month"];

	$sql = "SELECT s.nom, s.rowid as socid, s.client,";
	$sql.= " p.rowid as propalid, p.ref, p.fk_statut,";
	$sql.= " p.total_ht, p.tva, p.total,";
	$sql.= " p.datep as dp,";
	$sql.= " p.fin_validite as dfin";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."propal as p";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE p.fk_soc = s.rowid";
	$sql.= " AND p.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
	if ($viewstatut <> '') $sql.= " AND p.fk_statut in ($viewstatut)"; // viewstatut peut etre combinaisons separe par virgules
	if ($month > 0)
	{
		if ($year > 0)
		$sql.= " AND date_format(p.datep, '%Y-%m') = '".$year."-".$month."'";
		else
		$sql.= " AND date_format(p.datep, '%m') = '".$month."'";
	}
	if ($year > 0)         $sql .= " AND date_format(p.datep, '%Y') = '".$year."'";
	if (!empty($_GET['search_ref']))
	{
		$sql.= " AND p.ref LIKE '%".$db->escape($_GET['search_ref'])."%'";
	}
	if (!empty($_GET['search_societe']))
	{
		$sql.= " AND s.nom LIKE '%".$db->escape($_GET['search_societe'])."%'";
	}
	if (!empty($_GET['search_montant_ht']))
	{
		$sql.= " AND p.price='".$db->escape($_GET['search_montant_ht'])."'";
	}
	$sql.= " ORDER BY $sortfield $sortorder, p.rowid DESC ";
	$sql.= $db->plimit($limit + 1,$offset);

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);

		print_barre_liste($langs->trans("Proposals"), $page, "propal.php","&socid=$socid&month=$month&year=$year&search_ref=$search_ref&search_societe=$search_societe&search_montant_ht=$search_montant_ht".'&amp;viewstatut='.$viewstatut,$sortfield,$sortorder,'',$num);

		$i = 0;
		$var=true;

		print "<table class=\"noborder\" width=\"100%\">";
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Ref"),"propal.php","p.ref","","&year=$year&viewstatut=$viewstatut",'width=20%',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Company"),"propal.php","s.nom","&viewstatut=$viewstatut","",'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Date"),"propal.php","p.datep","&viewstatut=$viewstatut","",'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("AmountHT"),"propal.php","p.price","&viewstatut=$viewstatut","",'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Status"),"propal.php","p.fk_statut","&viewstatut=$viewstatut","",'align="right"',$sortfield,$sortorder);
		print '<td class="liste_titre">&nbsp;</td>';
		print "</tr>\n";

		// Lignes des champs de filtre
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

		print '<tr class="liste_titre">';
		print '<td class="liste_titre" valign="right">';
		print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
		print '</td>';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="40" name="search_societe" value="'.$_GET['search_societe'].'">';
		print '</td>';
		print '<td class="liste_titre" colspan="1" align="right">';
		print $langs->trans('Month').': <input class="flat" type="text" size="2" maxlength="2" name="month" value="'.$month.'">';
		print '&nbsp;'.$langs->trans('Year').': ';
		$syear = $year;
		if($syear == '')
		$syear = date("Y");
		$htmlother->select_year($syear,'year',1, 20, 5);
		print '</td>';
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET['search_montant_ht'].'">';
		print '</td>';
		print '<td class="liste_titre" align="right">';
		$form->select_propal_statut($viewstatut);
		print '</td>';
		print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '</td>';
		print "</tr>\n";
		print '</form>';

		while ($i < min($num, $limit))
		{
			$objp = $db->fetch_object($result);

			$var=!$var;
			print "<tr $bc[$var]>";
			print '<td nowrap="nowrap">';

			$propalstatic->id=$objp->propalid;
			$propalstatic->ref=$objp->ref;

			//Ref
			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="90" class="nobordernopadding" nowrap="nowrap">';
			print $propalstatic->getNomUrl(1, '', "&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder");
			print '</td>';

			print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
			if ($objp->fk_statut == 1 && $db->jdate($objp->dfin) < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding">';

			$filename=dol_sanitizeFileName($objp->ref);
			$filedir=$conf->propale->dir_output . '/' . dol_sanitizeFileName($objp->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->propalid;
			$formfile->show_documents('propal',$filename,$filedir,$urlsource,'','','',1,'',1);

			print '</td></tr></table>';

			print "</td>\n";

			// Societe
			print "<td>";
			$societestatic->nom=$objp->nom;
			$societestatic->id=$objp->socid;
			$societestatic->client=$objp->client;
			print $societestatic->getNomUrl(1,'customer',44);
			print "</td>";

			// Date
			print "<td align=\"right\">";
			$y = dol_print_date($db->jdate($objp->dp),"%Y");
			$m = dol_print_date($db->jdate($objp->dp),"%m");
			$mt = dol_print_date($db->jdate($objp->dp),"%b");
			$d = dol_print_date($db->jdate($objp->dp),"%d");
			print $d."\n";
			print " <a href=\"propal.php?year=$y&month=$m\">";
			print $mt."</a>\n";
			print " <a href=\"propal.php?year=$y\">";
			print $y."</a></td>\n";

			// Prix
			print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
			print "<td align=\"right\">".$propalstatic->LibStatut($objp->fk_statut,5)."</td>\n";
			print "<td>&nbsp;</td>";
			print "</tr>\n";

			$i++;
		}

		print "</table>";
		$db->free($result);
	}
	else
	{
		dol_print_error($db);
	}
}
$db->close();


llxFooter();

?>
