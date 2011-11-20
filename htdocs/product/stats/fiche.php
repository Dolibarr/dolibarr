<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (c) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011	Regis Houssin			<regis@dolibarr.fr>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
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
 *       \file       htdocs/product/stats/fiche.php
 *       \ingroup    product
 *       \brief      Page of product statistics
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");

$langs->load("companies");
$langs->load("products");
$langs->load("bills");
$langs->load("other");

$id		= GETPOST('id');
$ref	= GETPOST('ref');
$mode	= (GETPOST('mode') ? GETPOST('mode') : 'byunit');
$error	= 0;
$mesg	= '';

// Security check
$fieldid = (! empty($id) ? $id : $ref);
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$fieldid,'product','','',$fieldtype);


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
		$picto=($object->type==1?'service':'product');
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
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')'.'</td><td>';
		print $object->getLibStatut(2,0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')'.'</td><td>';
		print $object->getLibStatut(2,1);
		print '</td></tr>';


		// Graphs additionels generes pas le script product-graph.php
		$year = strftime('%Y',time());
		$file = get_exdir($object->id, 3) . "ventes-".$year."-".$object->id.".png";
		if (file_exists (DOL_DATA_ROOT.'/product/temp/'.$file) )
		{
			print '<tr><td>Ventes</td><td>';

			$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_product&amp;file='.$file;
			print '<img src="'.$url.'" alt="Ventes">';
			$file = get_exdir($object->id, 3) . "ventes-".$object->id.".png";
			$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_product&amp;file='.$file;
			print '<img src="'.$url.'" alt="Ventes">';
			print '</td></tr>';
		}


		print '</table>';
		print '</div>';


		// Choice of stats
		if ($mode == 'bynumber') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&mode=byunit">';
		else print img_picto('','tick').' ';
		print $langs->trans("StatsByNumberOfUnits");
		if ($mode == 'bynumber') print '</a>';
		print ' &nbsp; &nbsp; &nbsp; ';
		if ($mode == 'byunit') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&mode=bynumber">';
		else print img_picto('','tick').' ';
		print $langs->trans("StatsByNumberOfEntities");
		if ($mode == 'byunit') print '</a>';

		print '<br><br>';

		print '<table width="100%">';

		// Generation des graphs
		$WIDTH=380;
		$HEIGHT=160;
		$dir = (!empty($conf->product->dir_temp)?$conf->product->dir_temp:$conf->service->dir_temp);
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
		'label' => ($mode=='byunit'?$langs->trans("NumberOfUnitsProposals"):$langs->trans("NumberOfProposals"))),
		'orders'           =>array('modulepart'=>'productstats_orders',
		'file' => $object->id.'/orders12m.png',
		'label' => ($mode=='byunit'?$langs->trans("NumberOfUnitsCustomerOrders"):$langs->trans("NumberOfCustomerOrders"))),
		'invoices'         =>array('modulepart'=>'productstats_invoices',
		'file' => $object->id.'/invoices12m.png',
		'label' => ($mode=='byunit'?$langs->trans("NumberOfUnitsCustomerInvoices"):$langs->trans("NumberOfCustomerInvoices"))),
		'invoicessuppliers'=>array('modulepart'=>'productstats_invoicessuppliers',
		'file' => $object->id.'/invoicessuppliers12m.png',
		'label' => ($mode=='byunit'?$langs->trans("NumberOfUnitsSupplierInvoices"):$langs->trans("NumberOfSupplierInvoices"))),

		//			'orderssuppliers'  =>array('modulepart'=>'productstats_orderssuppliers', 'file' => $object->id.'/orderssuppliers12m.png', 'label' => $langs->trans("Nombre commande fournisseurs sur les 12 derniers mois")),
		//			'contracts'        =>array('modulepart'=>'productstats_contracts', 'file' => $object->id.'/contracts12m.png', 'label' => $langs->trans("Nombre contrats sur les 12 derniers mois")),

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
					if (is_array($graph_data))
					{
						$px->SetData($graph_data);
						$px->SetMaxValue($px->GetCeilMaxValue()<0?0:$px->GetCeilMaxValue());
						$px->SetMinValue($px->GetFloorMinValue()>0?0:$px->GetFloorMinValue());
						$px->SetWidth($WIDTH);
						$px->SetHeight($HEIGHT);
						$px->SetHorizTickIncrement(1);
						$px->SetPrecisionY(0);
						$px->SetShading(3);
						//print 'x '.$key.' '.$graphfiles[$key]['file'];

						$px->draw($dir."/".$graphfiles[$key]['file']);
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


			if ($i % 2 == 0) print '<tr>';

			// Show graph
			print '<td width="50%" align="center">';

			print '<table class="border" width="100%">';
			// Label
			print '<tr class="liste_titre"><td colspan="2">';
			print $graphfiles[$key]['label'];
			print '</td></tr>';
			// Image
			print '<tr><td colspan="2" align="center">';
			//print $graphfiles[$key]['modulepart']."x".urlencode($graphfiles[$key]['file']);
			$url=DOL_URL_ROOT.'/viewimage.php?modulepart='.$graphfiles[$key]['modulepart'].'&file='.urlencode($graphfiles[$key]['file']);
			//print $url;
			print '<img src="'.$url.'" alt="'.$graphfiles[$key]['label'].'">';
			print '</td></tr>';
			// Date generation
			print '<tr>';
			if (file_exists($dir."/".$graphfiles[$key]['file']) && filemtime($dir."/".$graphfiles[$key]['file']) && ! $px->isGraphKo())
			{
				print '<td>'.$langs->trans("GeneratedOn",dol_print_date(filemtime($dir."/".$graphfiles[$key]['file']),"dayhour")).'</td>';
			}
			else
			{
				print '<td>'.($mesg?'<font class="error">'.$mesg.'</font>':$langs->trans("ChartNotGenerated")).'</td>';
			}
			print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=recalcul&amp;mode='.$mode.'">'.img_picto($langs->trans("ReCalculate"),'refresh').'</a></td>';
			print '</tr>';
			print '</table>';

			print '</td>';

			if ($i % 2 == 1) print '</tr>';


			$i++;
		}

		if ($i % 2 == 1) print '<td>&nbsp;</td></tr>';

		print '</table>';

		print '<div class="tabsAction">';
		print '</div>';

	}
}
else
{
	dol_print_error();
}


$db->close();

llxFooter();
?>