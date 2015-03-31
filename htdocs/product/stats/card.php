<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (c) 2004-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2013		Juanjo Menent			<jmenent@2byte.es>
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
 *       \file       htdocs/product/stats/card.php
 *       \ingroup    product
 *       \brief      Page of product statistics
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$WIDTH=DolGraph::getDefaultGraphSizeForStats('width',380);
$HEIGHT=DolGraph::getDefaultGraphSizeForStats('height',160);

$langs->load("companies");
$langs->load("products");
$langs->load("bills");
$langs->load("other");

$id		= GETPOST('id','int');
$ref	= GETPOST('ref');
$mode	= (GETPOST('mode') ? GETPOST('mode') : 'byunit');
$error	= 0;
$mesg	= '';

$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;

// Security check
$fieldvalue = (! empty($id) ? $id : $ref);
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);


/*
 *	View
 */
$form = new Form($db);

if (! empty($id) || ! empty($ref))
{
	$object = new Product($db);
	$result = $object->fetch($id,$ref);

	llxHeader("","",$langs->trans("CardProduct".$object->type));

	if ($result)
	{
		$head=product_prepare_head($object, $user);
		$titre=$langs->trans("CardProduct".$object->type);
		$picto=($object->type==Product::TYPE_SERVICE?'service':'product');

		dol_fiche_head($head, 'stats', $titre, 0, $picto);

		print '<table class="border" width="100%">';

		// Reference
		print '<tr>';
		print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$object->libelle.'</td></tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
		print $object->getLibStatut(2,0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
		print $object->getLibStatut(2,1);
		print '</td></tr>';

		print '</table>';

		dol_fiche_end();


		// Choice of stats
		if (! empty($conf->dol_use_jmobile)) print "\n".'<div class="fichecenter"><div class="nowrap">'."\n";

		if ($mode == 'bynumber') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&mode=byunit">';
		else print img_picto('','tick').' ';
		print $langs->trans("StatsByNumberOfUnits");
		if ($mode == 'bynumber') print '</a>';

		if (! empty($conf->dol_use_jmobile)) print '</div>'."\n".'<div class="nowrap">'."\n";
		else print ' &nbsp; / &nbsp; ';

		if ($mode == 'byunit') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&mode=bynumber">';
		else print img_picto('','tick').' ';
		print $langs->trans("StatsByNumberOfEntities");
		if ($mode == 'byunit') print '</a>';

		if (! empty($conf->dol_use_jmobile)) print '</div></div>';
		else print '<br>';
		print '<br>';

		//print '<table width="100%">';

		// Generation des graphs
		$dir = (! empty($conf->product->multidir_temp[$object->entity])?$conf->product->multidir_temp[$object->entity]:$conf->service->multidir_temp[$object->entity]);
		if (! file_exists($dir.'/'.$object->id))
		{
			if (dol_mkdir($dir.'/'.$object->id) < 0)
			{
				$mesg = $langs->trans("ErrorCanNotCreateDir",$dir);
				$error++;
			}
		}

		$graphfiles=array(
		'propal'           =>array('modulepart'=>'productstats_proposals',
		'file' => $object->id.'/propal12m.png',
		'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsProposals"):$langs->transnoentitiesnoconv("NumberOfProposals"))),
		'orders'           =>array('modulepart'=>'productstats_orders',
		'file' => $object->id.'/orders12m.png',
		'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsCustomerOrders"):$langs->transnoentitiesnoconv("NumberOfCustomerOrders"))),
		'invoices'         =>array('modulepart'=>'productstats_invoices',
		'file' => $object->id.'/invoices12m.png',
		'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsCustomerInvoices"):$langs->transnoentitiesnoconv("NumberOfCustomerInvoices"))),
		'orderssuppliers'=>array('modulepart'=>'productstats_orderssuppliers',
		'file' => $object->id.'/orderssuppliers12m.png',
		'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsSupplierOrders"):$langs->transnoentitiesnoconv("NumberOfSupplierOrders"))),
		'invoicessuppliers'=>array('modulepart'=>'productstats_invoicessuppliers',
		'file' => $object->id.'/invoicessuppliers12m.png',
		'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsSupplierInvoices"):$langs->transnoentitiesnoconv("NumberOfSupplierInvoices"))),
		);

		$px = new DolGraph();

		if (! $error)
		{
			$mesg = $px->isGraphKo();
			if (! $mesg)
			{
				foreach($graphfiles as $key => $val)
				{
					if (! $graphfiles[$key]['file']) continue;

					$graph_data = array();

					// TODO Test si deja existant et recent, on ne genere pas
					if ($key == 'propal')            $graph_data = $object->get_nb_propal($socid,$mode);
					if ($key == 'orders')            $graph_data = $object->get_nb_order($socid,$mode);
					if ($key == 'invoices')          $graph_data = $object->get_nb_vente($socid,$mode);
					if ($key == 'invoicessuppliers') $graph_data = $object->get_nb_achat($socid,$mode);
					if ($key == 'orderssuppliers')   $graph_data = $object->get_nb_ordersupplier($socid,$mode);

					if (is_array($graph_data))
					{
						$px->SetData($graph_data);
						$px->SetYLabel($graphfiles[$key]['label']);
						$px->SetMaxValue($px->GetCeilMaxValue()<0?0:$px->GetCeilMaxValue());
						$px->SetMinValue($px->GetFloorMinValue()>0?0:$px->GetFloorMinValue());
						$px->SetWidth($WIDTH);
						$px->SetHeight($HEIGHT);
						$px->SetHorizTickIncrement(1);
						$px->SetPrecisionY(0);
						$px->SetShading(3);
						//print 'x '.$key.' '.$graphfiles[$key]['file'];

						$url=DOL_URL_ROOT.'/viewimage.php?modulepart='.$graphfiles[$key]['modulepart'].'&entity='.$object->entity.'&file='.urlencode($graphfiles[$key]['file']);
						$px->draw($dir."/".$graphfiles[$key]['file'],$url);

						$graphfiles[$key]['output']=$px->show();
					}
					else
					{
						dol_print_error($db,'Error for calculating graph on key='.$key.' - '.$object->error);
					}
				}
			}

			$mesg = $langs->trans("ChartGenerated");
		}

		// Show graphs
		$i=0;
		foreach($graphfiles as $key => $val)
		{
			if (! $graphfiles[$key]['file']) continue;

			if ($graphfiles == 'propal' && ! $user->rights->propale->lire) continue;
			if ($graphfiles == 'order' && ! $user->rights->commande->lire) continue;
			if ($graphfiles == 'invoices' && ! $user->rights->facture->lire) continue;
			if ($graphfiles == 'invoices_suppliers' && ! $user->rights->fournisseur->facture->lire) continue;
			if ($graphfiles == 'orders_suppliers' && ! $user->rights->fournisseur->commande->lire) continue;


			if ($i % 2 == 0)
			{
				print "\n".'<div class="fichecenter"><div class="fichehalfleft">'."\n";
			}
			else
			{
				print "\n".'<div class="fichehalfright"><div class="ficheaddleft">'."\n";
			}

			// Show graph

			print '<table class="border" width="100%">';
			// Label
			print '<tr class="liste_titre"><td colspan="2">';
			print $graphfiles[$key]['label'];
			print '</td></tr>';
			// Image
			print '<tr><td colspan="2" align="center">';
			print $graphfiles[$key]['output'];
			print '</td></tr>';
			// Date generation
			print '<tr>';
			if ($graphfiles[$key]['output'] && ! $px->isGraphKo())
			{
			    if (file_exists($dir."/".$graphfiles[$key]['file']) && filemtime($dir."/".$graphfiles[$key]['file'])) print '<td>'.$langs->trans("GeneratedOn",dol_print_date(filemtime($dir."/".$graphfiles[$key]['file']),"dayhour")).'</td>';
			    else print '<td>'.$langs->trans("GeneratedOn",dol_print_date(dol_now()),"dayhour").'</td>';
			}
			else
			{
				print '<td>'.($mesg?'<font class="error">'.$mesg.'</font>':$langs->trans("ChartNotGenerated")).'</td>';
			}
			print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=recalcul&amp;mode='.$mode.'">'.img_picto($langs->trans("ReCalculate"),'refresh').'</a></td>';
			print '</tr>';
			print '</table>';

			if ($i % 2 == 0)
			{
				print "\n".'</div>'."\n";
			}
			else
			{
				print "\n".'</div></div></div>';
				print '<div class="clear"><div class="fichecenter"><br></div></div>'."\n";
			}

			$i++;
		}
		// div not closed
		if ($i % 2 == 1)
		{
			print "\n".'<div class="fichehalfright"><div class="ficheaddleft">'."\n";
			print "\n".'</div></div></div>';
			print '<div class="clear"><div class="fichecenter"><br></div></div>'."\n";
		}

		print '<div class="tabsAction">';
		print '</div>';

	}
}
else
{
	dol_print_error();
}

llxFooter();

$db->close();
