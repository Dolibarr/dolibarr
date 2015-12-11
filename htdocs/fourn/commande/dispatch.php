<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric Gross         <c.gross@kreiz-it.fr>
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under the	terms of the GNU General Public	License	as published by
 * the Free Software Foundation; either	version	2 of the License, or
 * (at your option) any later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file      htdocs/fourn/commande/dispatch.php
 *	\ingroup   commande
 *	\brief     Page to dispatch receiving
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';
if (! empty($conf->projet->enabled))	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');
if (! empty($conf->productbatch->enabled)) $langs->load('productbatch');

// Security check
$id = GETPOST("id",'int');
$lineid = GETPOST('lineid', 'int');
$action = GETPOST('action');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');

if (empty($conf->stock->enabled))
{
	accessforbidden();
}

// Recuperation	de l'id	de projet
$projectid =	0;
if ($_GET["projectid"]) $projectid = GETPOST("projectid",'int');

$mesg='';


/*
 * Actions
 */

if ($action == 'checkdispatchline' &&
	! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check)))
)
{
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);
	$result=$supplierorderdispatch->fetch($lineid);
	if (! $result) dol_print_error($db);
	$result=$supplierorderdispatch->setStatut(1);
	if ($result < 0)
	{
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$error++;
		$action='';
	}
}

if ($action == 'uncheckdispatchline' &&
	! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check)))
)
{
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);
	$result=$supplierorderdispatch->fetch($lineid);
	if (! $result) dol_print_error($db);
	$result=$supplierorderdispatch->setStatut(0);
	if ($result < 0)
	{
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$error++;
		$action='';
	}
}

if ($action == 'denydispatchline' &&
	! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check)))
)
{
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);
	$result=$supplierorderdispatch->fetch($lineid);
	if (! $result) dol_print_error($db);
	$result=$supplierorderdispatch->setStatut(2);
	if ($result < 0)
	{
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$error++;
		$action='';
	}
}

if ($action == 'dispatch' && $user->rights->fournisseur->commande->receptionner)
{
	$commande = new CommandeFournisseur($db);
	$commande->fetch($id);

	$error=0;

	$db->begin();

	$pos=0;
	foreach($_POST as $key => $value)
	{
		if (preg_match('/^product_([0-9]+)$/i', $key, $reg))	// without batch module enabled
		{
			$pos++;

			//$numline=$reg[1] + 1;	// line of product
			$numline=$pos;
			$prod = "product_".$reg[1];
			$qty = "qty_".$reg[1];
			$ent = "entrepot_".$reg[1];
			$pu = "pu_".$reg[1];	// This is unit price including discount
			$fk_commandefourndet = "fk_commandefourndet_".$reg[1];

			if (GETPOST($qty) > 0)	// We ask to move a qty
			{
				if (! (GETPOST($ent,'int') > 0))
				{
					dol_syslog('No dispatch for line '.$key.' as no warehouse choosed');
					$text = $langs->transnoentities('Warehouse').', '.$langs->transnoentities('Line').' ' .($numline);
					setEventMessages($langs->trans('ErrorFieldRequired',$text), null, 'errors');
					$error++;
				}

				if (! $error)
				{
					$result = $commande->DispatchProduct($user, GETPOST($prod,'int'), GETPOST($qty), GETPOST($ent,'int'), GETPOST($pu), GETPOST('comment'), '', '', '', GETPOST($fk_commandefourndet, 'int'), $notrigger);
					if ($result < 0)
					{
						setEventMessages($commande->error, $commande->errors, 'errors');
						$error++;
					}
				}
			}
		}
		if (preg_match('/^product_([0-9]+)_([0-9]+)$/i', $key, $reg))	// with batch module enabled
		{
			$pos++;

			//eat-by date dispatch
			//$numline=$reg[2] + 1;	// line of product
			$numline=$pos;
			$prod = 'product_'.$reg[1].'_'.$reg[2];
			$qty = 'qty_'.$reg[1].'_'.$reg[2];
			$ent = 'entrepot_'.$reg[1].'_'.$reg[2];
			$pu = 'pu_'.$reg[1].'_'.$reg[2];
			$fk_commandefourndet = 'fk_commandefourndet_'.$reg[1].'_'.$reg[2];
			$lot = 'lot_number_'.$reg[1].'_'.$reg[2];
			$dDLUO = dol_mktime(12, 0, 0, $_POST['dluo_'.$reg[1].'_'.$reg[2].'month'], $_POST['dluo_'.$reg[1].'_'.$reg[2].'day'], $_POST['dluo_'.$reg[1].'_'.$reg[2].'year']);
			$dDLC = dol_mktime(12, 0, 0, $_POST['dlc_'.$reg[1].'_'.$reg[2].'month'], $_POST['dlc_'.$reg[1].'_'.$reg[2].'day'], $_POST['dlc_'.$reg[1].'_'.$reg[2].'year']);

            $fk_commandefourndet = 'fk_commandefourndet_'.$reg[1].'_'.$reg[2];
			
			if (GETPOST($qty) > 0)	// We ask to move a qty
			{
				if (! (GETPOST($ent,'int') > 0))
				{
					dol_syslog('No dispatch for line '.$key.' as no warehouse choosed');
					$text = $langs->transnoentities('Warehouse').', '.$langs->transnoentities('Line').' ' .($numline).'-'.($reg[1]+1);
					setEventMessages($langs->trans('ErrorFieldRequired',$text), null, 'errors');
					$error++;
				}

				if (! ($_POST[$lot] || $dDLUO || $dDLC))
				{
					dol_syslog('No dispatch for line '.$key.' as serial/eat-by/sellby date are not set');
					$text = $langs->transnoentities('atleast1batchfield').', '.$langs->transnoentities('Line').' ' .($numline).'-'.($reg[1]+1);
					setEventMessages($langs->trans('ErrorFieldRequired',$text), null, 'errors');
					$error++;
				}

				if (! $error)
				{
					$result = $commande->dispatchProduct($user, GETPOST($prod,'int'), GETPOST($qty), GETPOST($ent,'int'), GETPOST($pu), GETPOST('comment'), $dDLC, $dDLUO, GETPOST($lot, 'alpha'), GETPOST($fk_commandefourndet, 'int'), $notrigger);
					if ($result < 0)
					{
						setEventMessages($commande->error, $commande->errors, 'errors');
						$error++;
					}
				}
			}
		}
	}

	if (! $notrigger && ! $error)
	{
		global $conf, $langs, $user;
        // Call trigger
        $result = $commande->call_trigger('ORDER_SUPPLIER_DISPATCH', $user);
        // End call triggers

		if ($result < 0)
		{
			setEventMessages($commande->error, $commande->errors, 'errors');
			$error++;
		}
	}

	if ($result >= 0 && ! $error)
	{
		$db->commit();

		header("Location: dispatch.php?id=".$id);
		exit;
	}
	else
	{
		$db->rollback();
	}
}


/*
 * View
 */

$form =	new Form($db);
$warehouse_static = new Entrepot($db);
$supplierorderdispatch = new CommandeFournisseurDispatch($db);


$help_url='EN:CommandeFournisseur';
if (!empty($conf->productbatch->enabled))
{
	llxHeader('',$langs->trans("OrderCard"),$help_url,'',0,0,array('/core/js/lib_batch.js'));
}
else
{
	llxHeader('',$langs->trans("OrderCard"),$help_url);
}

$now=dol_now();

$id = GETPOST('id','int');
$ref= GETPOST('ref');
if ($id > 0 || ! empty($ref))
{
	//if ($mesg) print $mesg.'<br>';

	$commande = new CommandeFournisseur($db);

	$result=$commande->fetch($id,$ref);
	if ($result >= 0)
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->fetch($commande->user_author_id);

		$head = ordersupplier_prepare_head($commande);

		$title=$langs->trans("SupplierOrder");
		dol_fiche_head($head, 'dispatch', $title, 0, 'order');

		/*
		 *	Commande
		 */
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="2">';
		print $form->showrefnav($commande,'ref','',1,'ref','ref');
		print '</td>';
		print '</tr>';

		// Fournisseur
		print '<tr><td>'.$langs->trans("Supplier")."</td>";
		print '<td colspan="2">'.$soc->getNomUrl(1,'supplier').'</td>';
		print '</tr>';

		// Statut
		print '<tr>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td colspan="2">';
		print $commande->getLibStatut(4);
		print "</td></tr>";

		// Date
		if ($commande->methode_commande_id > 0)
		{
			print '<tr><td>'.$langs->trans("Date").'</td><td colspan="2">';
			if ($commande->date_commande)
			{
				print dol_print_date($commande->date_commande,"dayhourtext")."\n";
			}
			print "</td></tr>";

			if ($commande->methode_commande)
			{
				print '<tr><td>'.$langs->trans("Method").'</td><td colspan="2">'.$commande->methode_commande.'</td></tr>';
			}
		}

		// Auteur
		print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
		print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
		print '</tr>';

		print "</table>";

		//if ($mesg) print $mesg;
		print '<br>';


		$disabled=1;
		if (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) $disabled=0;

		/*
		 * Lignes de commandes
		 */
		if ($commande->statut <= 2 || $commande->statut >= 6)
		{
			print $langs->trans("OrderStatusNotReadyToDispatch");
		}

		if ($commande->statut == 3 || $commande->statut == 4 || $commande->statut == 5)
		{
			$entrepot = new Entrepot($db);
			$listwarehouses=$entrepot->list_array(1);

			print '<form method="POST" action="dispatch.php?id='.$commande->id.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="dispatch">';
			print '<table class="noborder" width="100%">';

			// Set $products_dispatched with qty dispatched for each product id
			$products_dispatched = array();
			$sql = "SELECT l.rowid, cfd.fk_product, sum(cfd.qty) as qty";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseurdet as l on l.rowid = cfd.fk_commandefourndet";
			$sql.= " WHERE cfd.fk_commande = ".$commande->id;
			$sql.= " GROUP BY l.rowid, cfd.fk_product";

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;
				
				if ($num)
				{
					while ($i < $num)
					{
						$objd = $db->fetch_object($resql);
						$products_dispatched[$objd->rowid] = price2num($objd->qty, 5);
						$i++;
					}
				}
				$db->free($resql);
			}

			$sql = "SELECT l.rowid, l.fk_product, l.subprice, l.remise_percent, SUM(l.qty) as qty,";
			$sql.= " p.ref, p.label, p.tobatch";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product=p.rowid";
			$sql.= " WHERE l.fk_commande = ".$commande->id;
			$sql.= " GROUP BY p.ref, p.label, p.tobatch, l.rowid, l.fk_product, l.subprice, l.remise_percent";	// Calculation of amount dispatched is done per fk_product so we must group by fk_product
			$sql.= " ORDER BY p.ref, p.label";

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;

				if ($num)
				{
					print '<tr class="liste_titre">';

					print '<td>'.$langs->trans("Description").'</td>';
					print '<td></td>';
					print '<td></td>';
					print '<td></td>';
					print '<td align="right">'.$langs->trans("QtyOrdered").'</td>';
					print '<td align="right">'.$langs->trans("QtyDispatchedShort").'</td>';
					print '<td align="right">'.$langs->trans("QtyToDispatchShort").'</td>';
					print '<td align="right">'.$langs->trans("Warehouse").'</td>';
					print "</tr>\n";

					if (! empty($conf->productbatch->enabled))
					{
						print '<tr class="liste_titre">';
						print '<td></td>';
						print '<td>'.$langs->trans("batch_number").'</td>';
						print '<td>'.$langs->trans("l_eatby").'</td>';
						print '<td>'.$langs->trans("l_sellby").'</td>';
						print '<td colspan="4">&nbsp;</td>';
						print "</tr>\n";
					}

				}

				$nbfreeproduct=0;
				$nbproduct=0;

				$var=true;
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);

					// On n'affiche pas les produits personnalises
					if (! $objp->fk_product > 0)
					{
						$nbfreeproduct++;
					}
					else
					{
						$remaintodispatch=price2num($objp->qty - ((float) $products_dispatched[$objp->rowid]), 5);	// Calculation of dispatched
						if ($remaintodispatch < 0) $remaintodispatch=0;

						if ($remaintodispatch || empty($conf->global->SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED))
						{
							$nbproduct++;

							$var=!$var;

							// To show detail cref and description value, we must make calculation by cref
							//print ($objp->cref?' ('.$objp->cref.')':'');
							//if ($objp->description) print '<br>'.nl2br($objp->description);
							if ((empty($conf->productbatch->enabled)) || $objp->tobatch==0)
							{
								$suffix='_'.$i;
							} else {
								$suffix='_0_'.$i;
							}


							print "\n";
							print '<!-- Line '.$suffix.' -->'."\n";
							print "<tr ".$bc[$var].">";

							$linktoprod='<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$objp->ref.'</a>';
							$linktoprod.=' - '.$objp->label."\n";

							if (! empty($conf->productbatch->enabled))
							{
								if ($objp->tobatch)
								{
									print '<td colspan="4">';
									print $linktoprod;
									print "</td>";
								}
								else
								{
									print '<td>';
									print $linktoprod;
									print "</td>";
									print '<td colspan="3">';
									print $langs->trans("ProductDoesNotUseBatchSerial");
									print '</td>';
								}
							}
							else
							{
								print '<td colspan="4">';
								print $linktoprod;
								print "</td>";
							}

							$up_ht_disc=$objp->subprice;
							if (! empty($objp->remise_percent) && empty($conf->global->STOCK_EXCLUDE_DISCOUNT_FOR_PMP)) $up_ht_disc=price2num($up_ht_disc * (100 - $objp->remise_percent) / 100, 'MU');

							// Qty ordered
							print '<td align="right">'.$objp->qty.'</td>';

							// Already dispatched
							print '<td align="right">'.$products_dispatched[$objp->rowid].'</td>';

							if (! empty($conf->productbatch->enabled) && $objp->tobatch==1)
							{
								print '<td align="right">'.img_picto($langs->trans('AddDispatchBatchLine'),'split.png','onClick="addLineBatch('.$i.')"').'</td>';	// Dispatch column
								print '<td></td>';																													// Warehouse column
								print '</tr>';

								print '<tr '.$bc[$var].' name="dluo'.$suffix.'">';
								print '<td>';
								print '<input name="fk_commandefourndet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
								print '<input name="product'.$suffix.'" type="hidden" value="'.$objp->fk_product.'">';
								print '<input name="pu'.$suffix.'" type="hidden" value="'.$up_ht_disc.'"><!-- This is a up including discount -->';
								print '</td>';

								print '<td>';
								print '<input type="text" id="lot_number'.$suffix.'" name="lot_number'.$suffix.'" size="40" value="'.GETPOST('lot_number'.$suffix).'">';
								print '</td>';
								print '<td>';
								$dlcdatesuffix=dol_mktime(0, 0, 0, GETPOST('dlc'.$suffix.'month'), GETPOST('dlc'.$suffix.'day'), GETPOST('dlc'.$suffix.'year'));
								$form->select_date($dlcdatesuffix,'dlc'.$suffix,'','',1,"");
								print '</td>';
								print '<td>';
								$dluodatesuffix=dol_mktime(0, 0, 0, GETPOST('dluo'.$suffix.'month'), GETPOST('dluo'.$suffix.'day'), GETPOST('dluo'.$suffix.'year'));
								$form->select_date($dluodatesuffix,'dluo'.$suffix,'','',1,"");
								print '</td>';
								print '<td colspan="2">&nbsp</td>';		// Qty ordered + qty already dispatached
							}

							// Dispatch
							print '<td align="right">';
							if (empty($conf->productbatch->enabled) || $objp->tobatch!=1)
							{
								print '<input name="fk_commandefourndet'.$suffix.'" type="hidden" value="'.$objp->rowid.'">';
								print '<input name="product'.$suffix.'" type="hidden" value="'.$objp->fk_product.'">';
								print '<input name="pu'.$suffix.'" type="hidden" value="'.$up_ht_disc.'"><!-- This is a up including discount -->';
							}
							print '<input id="qty'.$suffix.'" name="qty'.$suffix.'" type="text" size="8" value="'.(GETPOST('qty'.$suffix)!='' ? GETPOST('qty'.$suffix) : $remaintodispatch).'">';
							print '</td>';

							// Warehouse
							print '<td align="right">';
							if (count($listwarehouses)>1)
							{
								print $form->selectarray("entrepot".$suffix, $listwarehouses, GETPOST("entrepot".$suffix), 1, 0, 0, '', 0, 0, $disabled);
							}
							elseif  (count($listwarehouses)==1)
							{
								print $form->selectarray("entrepot".$suffix, $listwarehouses, GETPOST("entrepot".$suffix), 0, 0, 0, '', 0, 0, $disabled);
							}
							else
							{
								print $langs->trans("NoWarehouseDefined");
							}
							print "</td>\n";

							print "</tr>\n";
						}
					}
					$i++;
				}
				$db->free($resql);
			}
			else
			{
				dol_print_error($db);
			}

			print "</table>\n";
			print "<br/>\n";

			if ($nbproduct)
			{
				print $langs->trans("Comment").' : ';
				print '<input type="text" size="60" maxlength="128" name="comment" value="';
				print $_POST["comment"]?GETPOST("comment"):$langs->trans("DispatchSupplierOrder",$commande->ref);
				// print ' / '.$commande->ref_supplier;	// Not yet available
				print '" class="flat"> &nbsp; ';

				//print '<div class="center">';
				print '<input type="submit" class="button" value="'.$langs->trans("DispatchVerb").'"';
				if (count($listwarehouses) <= 0) print ' disabled';
				print '>';
				//print '</div>';
			}
			if (! $nbproduct && $nbfreeproduct)
			{
				print $langs->trans("NoPredefinedProductToDispatch");
			}

			print '</form>';
		}

		dol_fiche_end();


		// List of lines already dispatched
		$sql = "SELECT p.ref, p.label,";
		$sql.= " e.rowid as warehouse_id, e.label as entrepot,";
		$sql.= " cfd.rowid as dispatchlineid, cfd.fk_product, cfd.qty, cfd.eatby, cfd.sellby, cfd.batch, cfd.comment, cfd.status";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p,";
		$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e ON cfd.fk_entrepot = e.rowid";
		$sql.= " WHERE cfd.fk_commande = ".$commande->id;
		$sql.= " AND cfd.fk_product = p.rowid";
		$sql.= " ORDER BY cfd.rowid ASC";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num > 0)
			{
				print "<br/>\n";

				print load_fiche_titre($langs->trans("ReceivingForSameOrder"));

				print '<table class="noborder" width="100%">';

				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				if (! empty($conf->productbatch->enabled))
				{
					print '<td>'.$langs->trans("batch_number").'</td>';
					print '<td>'.$langs->trans("l_eatby").'</td>';
					print '<td>'.$langs->trans("l_sellby").'</td>';
				}
				print '<td align="right">'.$langs->trans("QtyDispatched").'</td>';
				print '<td></td>';
				print '<td>'.$langs->trans("Warehouse").'</td>';
				print '<td>'.$langs->trans("Comment").'</td>';
				if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) print '<td align="center" colspan="2">'.$langs->trans("Status").'</td>';
				print "</tr>\n";

				$var=false;

				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);

					print "<tr ".$bc[$var].">";
					print '<td>';
					print '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$objp->ref.'</a>';
					print ' - '.$objp->label;
					print "</td>\n";

					if (! empty($conf->productbatch->enabled))
					{
						print '<td>'.$objp->batch.'</td>';
						print '<td>'.dol_print_date($db->jdate($objp->eatby),'day').'</td>';
						print '<td>'.dol_print_date($db->jdate($objp->sellby),'day').'</td>';
					}

					// Qty
					print '<td align="right">'.$objp->qty.'</td>';
					print '<td>&nbsp;</td>';

					// Warehouse
					print '<td>';
					$warehouse_static->id=$objp->warehouse_id;
					$warehouse_static->libelle=$objp->entrepot;
					print $warehouse_static->getNomUrl(1);
					print '</td>';

					// Comment
					print '<td>'.dol_trunc($objp->comment).'</td>';

					// Status
					if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS))
					{
						print '<td align="right">';
						$supplierorderdispatch->status = (empty($objp->status)?0:$objp->status);
						//print $supplierorderdispatch->status;
						print $supplierorderdispatch->getLibStatut(5);
						print '</td>';

						// Add button to check/uncheck disaptching
						print '<td align="center">';
						if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner))
       					|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))
							)
						{
							if (empty($objp->status))
							{
								print '<a class="button buttonRefused" href="#">'.$langs->trans("Approve").'</a>';
								print '<a class="button buttonRefused" href="#">'.$langs->trans("Deny").'</a>';
							}
							else
							{
								print '<a class="button buttonRefused" href="#">'.$langs->trans("Disapprove").'</a>';
								print '<a class="button buttonRefused" href="#">'.$langs->trans("Deny").'</a>';
							}
						}
						else
						{
							$disabled='';
							if ($commande->statut == 5) $disabled=1;
							if (empty($objp->status))
							{
								print '<a class="button'.($disabled?' buttonRefused':'').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=checkdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Approve").'</a>';
								print '<a class="button'.($disabled?' buttonRefused':'').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=denydispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Deny").'</a>';
							}
							if ($objp->status == 1)
							{
								print '<a class="button'.($disabled?' buttonRefused':'').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=uncheckdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Reinit").'</a>';
								print '<a class="button'.($disabled?' buttonRefused':'').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=denydispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Deny").'</a>';
							}
							if ($objp->status == 2)
							{
								print '<a class="button'.($disabled?' buttonRefused':'').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=uncheckdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Reinit").'</a>';
								print '<a class="button'.($disabled?' buttonRefused':'').'" href="'.$_SERVER["PHP_SELF"]."?id=".$id."&action=checkdispatchline&lineid=".$objp->dispatchlineid.'">'.$langs->trans("Approve").'</a>';
							}
						}
						print '</td>';
					}

					print "</tr>\n";

					$i++;
					$var=!$var;
				}
				$db->free($resql);

				print "</table>\n";
			}
		}
		else
		{
			dol_print_error($db);
		}
	}
	else
	{
		// Commande	non	trouvee
		dol_print_error($db);
	}
}


llxFooter();

$db->close();
