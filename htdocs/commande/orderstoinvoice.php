<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville   	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur   	<eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo  	<marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin          	<regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Andreu Bisquerra Gaya  	<jove@bisquerra.com>
 * Copyright (C) 2012	   David Rodriguez Martinez <davidrm146@gmail.com>
 * Copyright (C) 2012	   Juanjo Menent			<jmenent@2byte.es>
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
 *	\file       htdocs/commande/orderstoinvoice.php
 *	\ingroup    commande
 *	\brief      Page to invoice multiple orders
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->load('orders');
$langs->load('deliveries');
$langs->load('companies');

if (! $user->rights->facture->creer)
	accessforbidden();

$id				= (GETPOST('id')?GETPOST("id"):GETPOST("facid"));  // For backward compatibility
$ref			= GETPOST('ref','alpha');
$action			= GETPOST('action','alpha');
$confirm		= GETPOST('confirm','alpha');
$sref			= GETPOST('sref');
$sref_client	= GETPOST('sref_client');
$sall			= GETPOST('sall');
$socid			= GETPOST('socid','int');
$selected		= GETPOST('orders_to_invoice');
$sortfield		= GETPOST("sortfield",'alpha');
$sortorder		= GETPOST("sortorder",'alpha');
$viewstatut		= GETPOST('viewstatut');

if (! $sortfield) $sortfield='c.rowid';
if (! $sortorder) $sortorder='DESC';

$now = dol_now();
$date_start = dol_mktime(0,0,0,$_REQUEST["date_startmonth"],$_REQUEST["date_startday"],$_REQUEST["date_startyear"]);	// Date for local PHP server
$date_end = dol_mktime(23,59,59,$_REQUEST["date_endmonth"],$_REQUEST["date_endday"],$_REQUEST["date_endyear"]);
$date_starty = dol_mktime(0,0,0,$_REQUEST["date_start_delymonth"],$_REQUEST["date_start_delyday"],$_REQUEST["date_start_delyyear"]);	// Date for local PHP server
$date_endy = dol_mktime(23,59,59,$_REQUEST["date_end_delymonth"],$_REQUEST["date_end_delyday"],$_REQUEST["date_end_delyyear"]);

if ($action == 'create')
{
	if (is_array($selected) == false)
	{
		$mesgs = array('<div class="error">'.$langs->trans('Error_OrderNotChecked').'</div>');
	}
	else
	{
		$origin = GETPOST('origin');
		$originid = GETPOST('originid');
	}
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('orderstoinvoice'));


/*
 * Actions
 */

if (($action == 'create' || $action == 'add') && empty($mesgs))
{
	require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
	if (! empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	
	$langs->load('bills');
	$langs->load('products');
	$langs->load('main');
	if (isset($_GET['orders_to_invoice']))
	{
		$orders_id = $_GET['orders_to_invoice'];
		$n        = count($orders_id);
		$i        = 0;

		$originid=$orders_id[0];
		$_GET['originid']=$orders_id[0];

	}
	if (isset($_POST['orders_to_invoice']))
	{
		$orders_id = $_POST['orders_to_invoice'];
		$nn        = count($orders_id);
		$ii        = 0;

		$originid=$orders_id[0];
		$_POST['originid']=$orders_id[0];

	}

	$projectid		= GETPOST('projectid','int')?GETPOST('projectid','int'):0;
	$lineid			= GETPOST('lineid','int');
	$userid			= GETPOST('userid','int');
	$search_ref		= GETPOST('sf_ref')?GETPOST('sf_ref'):GETPOST('search_ref');
	$closeOrders	= GETPOST('autocloseorders') ? true : false;

	// Security check
	$fieldid = GETPOST('ref','alpha')?'facnumber':'rowid';
	if ($user->societe_id) $socid=$user->societe_id;
	$result = restrictedArea($user, 'facture', $id,'','','fk_soc',$fieldid);

	$usehm=$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;
	$object=new Facture($db);

	// Insert new invoice in database
	if ($action == 'add' && $user->rights->facture->creer)
	{
		$object->socid=GETPOST('socid');
		$db->begin();
		$error=0;

		// Standard or deposit or proforma invoice
		if ($_POST['type'] == 0 )
		{
			$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($datefacture))
			{
				$datefacture = dol_mktime(date("h"), date("M"), 0, date("m"), date("d"), date("Y"));
			}
			if (! $error)
			{
				// Si facture standard
				$object->socid				= $_POST['socid'];
				$object->type				= $_POST['type'];
				$object->number				= $_POST['facnumber'];
				$object->date				= $datefacture;
				$object->note_public		= trim($_POST['note_public']);
				$object->note				= trim($_POST['note']);
				$object->ref_client			= $_POST['ref_client'];
				$object->ref_int			= $_POST['ref_int'];
				$object->modelpdf			= $_POST['model'];
				$object->fk_project			= $_POST['projectid'];
				$object->cond_reglement_id	= ($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
				$object->mode_reglement_id	= $_POST['mode_reglement_id'];
				$object->amount				= $_POST['amount'];
				$object->remise_absolue		= $_POST['remise_absolue'];
				$object->remise_percent		= $_POST['remise_percent'];

				if ($_POST['origin'] && $_POST['originid'])
				{
					$object->origin    = $_POST['origin'];
					$object->origin_id = $orders_id[$ii];
					$object->linked_objects = $orders_id;
					$id = $object->create($user);

					if ($id>0)
					{
						foreach($orders_id as $origin => $origin_id)
						{
							$origin_id = (! empty($origin_id) ? $origin_id : $object->origin_id);
							$db->begin();
							$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
							$sql.= "fk_source";
							$sql.= ", sourcetype";
							$sql.= ", fk_target";
							$sql.= ", targettype";
							$sql.= ") VALUES (";
							$sql.= $origin_id;
							$sql.= ", '".$object->origin."'";
							$sql.= ", ".$id;
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
						}

						while ($ii < $nn)
						{
							include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
							$objectsrc = new Commande($db);
							dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
							$result=$objectsrc->fetch($orders_id[$ii]);
							if ($result > 0)
							{
								if ($closeOrders) 
								{
									$objectsrc->classifyBilled();
									$objectsrc->setStatut(3);
								}
								$lines = $objectsrc->lines;
								if (empty($lines) && method_exists($objectsrc,'fetch_lines'))  $lines = $objectsrc->fetch_lines();
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
											$mesgs[]=$discount->error;
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
												$lines[$i]->rang,
												$lines[$i]->special_code,
												$object->origin,
												$lines[$i]->rowid,
												$fk_parent_line,
												$lines[$i]->fk_fournprice,
												$lines[$i]->pa_ht
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
							else
							{
								$mesgs[]=$objectsrc->error;
								$error++;
							}
							$ii++;
						}
					}
					else
					{
						$mesgs[]=$object->error;
						$error++;
					}
				}
			}
		}

		// End of object creation, we show it
		if ($id > 0 && ! $error)
		{
			$db->commit();
			header('Location: '.DOL_URL_ROOT.'/compta/facture.php?facid='.$id);
			exit;
		}
		else
		{
			$db->rollback();
			$action='create';
			$_GET["origin"]=$_POST["origin"];
			$_GET["originid"]=$_POST["originid"];
			$mesgs[]='<div class="error">'.$object->error.'</div>';
		}
	}
}


/*
 * View
 */

$html = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);

// Mode creation
if ($action == 'create' && empty($mesgs))
{
	$facturestatic=new Facture($db);

	llxHeader();
	print_fiche_titre($langs->trans('NewBill'));

	$soc = new Societe($db);
	if ($socid) $res=$soc->fetch($socid);
	if ($res)
	{
		$cond_reglement_id 	= $soc->cond_reglement_id;
		$mode_reglement_id 	= $soc->mode_reglement_id;
		$remise_percent 	= $soc->remise_percent;
	}
	$remise_absolue 	= 0;
	$dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;

	$absolute_discount=$soc->getAvailableDiscounts();
	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
	print '<input name="facnumber" type="hidden" value="provisoire">';
	print '<input name="ref_client" type="hidden" value="'.$ref_client.'">';
	print '<input name="ref_int" type="hidden" value="'.$ref_int.'">';
	print '<input type="hidden" name="origin" value="'.GETPOST('origin').'">';
	print '<input type="hidden" name="originid" value="'.GETPOST('originid').'">';
	print '<input type="hidden" name="autocloseorders" value="'.GETPOST('autocloseorders').'">';
	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';

	// Third party
	print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td><td colspan="2">';
	print $soc->getNomUrl(1);
	print '<input type="hidden" name="socid" value="'.$soc->id.'">';
	print '</td>';
	print '</tr>'."\n";

	// Type
	print '<tr><td valign="top" class="fieldrequired">'.$langs->trans('Type').'</td><td colspan="2">';
	print '<table class="nobordernopadding">'."\n";

	// Standard invoice
	print '<tr height="18"><td width="16px" valign="middle">';
	print '<input type="radio" name="type" value="0"'.(GETPOST('type')==0?' checked="true"':'').'>';
	print '</td><td valign="middle">';
	$desc=$html->textwithpicto($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
	print $desc;
	print '</td></tr>'."\n";
	print '</table>';

	// Date invoice
	print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
	$html->select_date(0,'','','','',"add",1,1);
	print '</td></tr>';
	// Payment term
	print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$html->select_conditions_paiements(isset($_POST['cond_reglement_id'])?$_POST['cond_reglement_id']:$cond_reglement_id,'cond_reglement_id');
	print '</td></tr>';
	// Payment mode
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$html->select_types_paiements(isset($_POST['mode_reglement_id'])?$_POST['mode_reglement_id']:$mode_reglement_id,'mode_reglement_id');
	print '</td></tr>';
	// Project
	if (! empty($conf->projet->enabled))
	{
		$formproject=new FormProjets($db);

		$langs->load('projects');
		print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
		$formproject->select_projects($soc->id, $projectid, 'projectid');
		print '</td></tr>';
	}

	include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
	$objectsrc = new Commande($db);
	$listoforders = array();
	foreach ($selected as $sel)
	{
		$result=$objectsrc->fetch($sel);
		if ($result > 0)
		{
			$listoforders[] = $objectsrc->ref;
		}
	}

	// Other attributes
	$parameters=array('objectsrc' => $objectsrc, 'idsrc' => $listoforders, 'colspan' => ' colspan="3"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

	// Modele PDF
	print '<tr><td>'.$langs->trans('Model').'</td>';
	print '<td>';
	include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
	$liste=ModelePDFFactures::liste_modeles($db);
	print $html->selectarray('model',$liste,$conf->global->FACTURE_ADDON_PDF);
	print "</td></tr>";

	// Public note
	print '<tr>';
	print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
	print '<td valign="top" colspan="2">';
	print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';

	print $langs->trans("Orders").": ".implode(', ', $listoforders);

	print '</textarea></td></tr>';
	// Private note
	if (! $user->societe_id)
	{
		print '<tr>';
		print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
		print '<td valign="top" colspan="2">';
		print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';

		print '</textarea></td></tr>';
	}

	print '</table>';

	while ($i < $n)
	{
		print '<input type="hidden" name="orders_to_invoice[]" value="'.$orders_id[$i].'">';

		$i++;
	}

	// Button "Create Draft"
	print '<br><center><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'" /></center>';
	print "</form>\n";

	print '</td></tr>';
	print "</table>\n";
}


//Mode liste
if (($action != 'create' && $action != 'add') || ! empty($mesgs))
{
	llxHeader();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
		jQuery("#checkall").click(function() {
			jQuery(".checkformerge").attr('checked', true);
		});
		jQuery("#checknone").click(function() {
			jQuery(".checkformerge").attr('checked', false);
		});
	});
	</script>
	<?php

	$sql = 'SELECT s.nom, s.rowid as socid, s.client, c.rowid, c.ref, c.total_ht, c.ref_client,';
	$sql.= ' c.date_valid, c.date_commande, c.date_livraison, c.fk_statut, c.facture as facturee';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
	$sql.= ', '.MAIN_DB_PREFIX.'commande as c';
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= ' WHERE c.entity = '.$conf->entity;
	$sql.= ' AND c.fk_soc = s.rowid';

	// Show orders with status validated, shipping started and delivered (well any order we can bill)
	$sql.= " AND ((c.fk_statut IN (1,2)) OR (c.fk_statut = 3 AND c.facture = 0))";

	if ($socid)	$sql.= ' AND s.rowid = '.$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($sref)
	{
		$sql.= " AND c.ref LIKE '%".$db->escape($sref)."%'";
	}
	if ($sall)
	{
		$sql.= " AND (c.ref LIKE '%".$db->escape($sall)."%' OR c.note LIKE '%".$db->escape($sall)."%')";
	}

	//Date filter
	if ($date_start && $date_end) $sql.= " AND c.date_commande >= '".$db->idate($date_start)."' AND c.date_commande <= '".$db->idate($date_end)."'";
	if ($date_starty && $date_endy) $sql.= " AND c.date_livraison >= '".$db->idate($date_starty)."' AND c.date_livraison <= '".$db->idate($date_endy)."'";

	if (!empty($sref_client))
	{
		$sql.= ' AND c.ref_client LIKE \'%'.$db->escape($sref_client).'%\'';
	}
	$sql.= ' ORDER BY '.$sortfield.' '.$sortorder;
	$resql = $db->query($sql);

	if ($resql)
	{
		if ($socid)
		{
			$soc = new Societe($db);
			$soc->fetch($socid);
		}
		$title = $langs->trans('ListOfOrders');
		$title.=' - '.$langs->trans('StatusOrderValidated').', '.$langs->trans("StatusOrderSent").', '.$langs->trans('StatusOrderToBill');
		$num = $db->num_rows($resql);
		print_fiche_titre($title);
		$i = 0;
		$period=$html->select_date($date_start,'date_start',0,0,1,'',1,0,1).' - '.$html->select_date($date_end,'date_end',0,0,1,'',1,0,1);
		$periodely=$html->select_date($date_starty,'date_start_dely',0,0,1,'',1,0,1).' - '.$html->select_date($date_endy,'date_end_dely',0,0,1,'',1,0,1);

		if (! empty($socid))
		{
			// Company
			$companystatic->id=$socid;
			$companystatic->nom=$soc->nom;
			print '<h3>'.$companystatic->getNomUrl(1,'customer').'</h3>';
		}

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Ref'),'orderstoinvoice.php','c.ref','','&amp;socid='.$socid,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('RefCustomerOrder'),'orderstoinvoice.php','c.ref_client','','&amp;socid='.$socid,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('OrderDate'),'orderstoinvoice.php','c.date_commande','','&amp;socid='.$socid, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('DeliveryDate'),'orderstoinvoice.php','c.date_livraison','','&amp;socid='.$socid, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Status'),'','','','','align="right"');
		print_liste_field_titre($langs->trans('GenerateBill'),'','','','','align="center"');
		print '</tr>';

		// Lignes des champs de filtre
		print '<form method="get" action="orderstoinvoice.php">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">';
		//REF
		print '<input class="flat" size="10" type="text" name="sref" value="'.$sref.'">';
		print '</td>';
		//print '<td class="liste_titre">';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="10" name="sref_client" value="'.$sref_client.'">';

		//DATE ORDER
		print '<td class="liste_titre" align="center">';
		print $period;
		print '</td>';

		//DATE DELIVERY
		print '<td class="liste_titre" align="center">';
		print $periodely;
		print '</td>';

		//SEARCH BUTTON
		print '</td><td align="right" class="liste_titre">';
		print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';

		//ALL/NONE
		print '<td class="liste_titre" align="center">';
		if ($conf->use_javascript_ajax) print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a>';
		print '</td>';

		print '</td></tr>';
		print '</form>';

		print '<form name="orders2invoice" action="orderstoinvoice.php" method="GET">';
		$var=True;
		$generic_commande = new Commande($db);

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td class="nowrap">';

			$generic_commande->id=$objp->rowid;
			$generic_commande->ref=$objp->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding nowrap">';
			print $generic_commande->getNomUrl(1,$objp->fk_statut);
			print '</td>';

			print '<td width="20" class="nobordernopadding nowrap">';
			if (($objp->fk_statut > 0) && ($objp->fk_statut < 3) && $db->jdate($objp->date_valid) < ($now - $conf->commande->client->warning_delay)) print img_picto($langs->trans("Late"),"warning");
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
			$filename=dol_sanitizeFileName($objp->ref);
			$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($objp->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->rowid;
			print $formfile->getDocumentsLink($generic_commande->element, $filename, $filedir);
			print '</td></tr></table>';
			print '</td>';

			print '<td>'.$objp->ref_client.'</td>';

			// Order date
			print '<td align="center" nowrap>';
			print dol_print_date($db->jdate($objp->date_commande),'day');
			print '</td>';

			//Delivery date
			print '<td align="center" nowrap>';
			print dol_print_date($db->jdate($objp->date_livraison),'day');
			print '</td>';

			// Statut
			print '<td align="right" class="nowrap">'.$generic_commande->LibStatut($objp->fk_statut,$objp->facturee,5).'</td>';

			// Checkbox
			print '<td align="center">';
			print '<input class="flat checkformerge" type="checkbox" name="orders_to_invoice[]" value="'.$objp->rowid.'">';
			print '</td>' ;

			print '</tr>';

			$total = $total + $objp->price;
			$subtotal = $subtotal + $objp->price;
			$i++;
		}
		print '</table>';

		/*
		 * Boutons actions
		*/
		print '<center><br><input type="checkbox" checked="checked" name="autocloseorders"> '.$langs->trans("CloseProcessedOrdersAutomatically");
		print '<div align="right">';
		print '<input type="hidden" name="socid" value="'.$socid.'">';
		print '<input type="hidden" name="action" value="create">';
		print '<input type="hidden" name="origin" value="commande"><br>';
		//print '<a class="butAction" href="index.php">'.$langs->trans("GoBack").'</a>';
		print '<input type="submit" class="butAction" value="'.$langs->trans("GenerateBill").'">';
		print '</div>';
		print '</form>';
		$db->free($resql);
	}
	else
	{
		print dol_print_error($db);
	}

}

dol_htmloutput_mesg($mesg,$mesgs);

llxFooter();
$db->close();
?>
