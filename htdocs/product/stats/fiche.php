<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/product/stats/fiche.php
        \ingroup    product
        \brief      Page des stats produits
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");

$langs->load("companies");
$langs->load("products");
$langs->load("bills");

if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}
else
{
  $socid = 0;
}


$mesg = '';


/*
 *
 */
$html = new Form($db);

if ($_GET["id"] || $_GET["ref"])
{
	$product = new Product($db);
	if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
	if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

	llxHeader("","",$langs->trans("CardProduct".$product->type));

	if ($result)
	{
		// Efface rep obsolete
		if(is_dir(DOL_DOCUMENT_ROOT."/document/produits"))
		rmdir(DOL_DOCUMENT_ROOT."/document/produits");


		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		dolibarr_fiche_head($head, 'stats', $titre);


		print '<table class="border" width="100%">';

		// Reference
		print '<tr>';
		print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $html->showrefnav($product,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Libelle
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td></tr>';

		print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="3">';
		if ($product->price_base_type == 'TTC')
		{
			print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
		}
		else
		{
			print price($product->price).' '.$langs->trans($product->price_base_type);
		}
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
		print $product->getLibStatut(2);
		print '</td></tr>';

		// Stock
		if ($product->isproduct() && $conf->stock->enabled)
		{
			print '<tr><td>'.$langs->trans("Stock").'</td>';
			if ($product->no_stock)
			{
				print "<td>".$langs->trans("NoStockForThisProduct");
			}
			else
			{
				if ($product->stock_reel <= $product->seuil_stock_alerte)
				{
					print '<td>'.img_warning().' '.$product->stock_reel.' Seuil : '.$product->seuil_stock_alerte;
				}
				else
				{
					print "<td>".$product->stock_reel;
				}
			}
			print '</td></tr>';
		}	
		//show_stats_for_company($product,$socid);

		// Graphs additionels generes pas les cron
		$year = strftime('%Y',time());
		$file = get_exdir($product->id, 3) . "ventes-".$year."-".$product->id.".png";	
		if (file_exists (DOL_DATA_ROOT.'/graph/product/'.$file) )
		{
			print '<tr><td>Ventes</td><td>';
			
			$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_product&amp;file='.$file;
			print '<img src="'.$url.'" alt="Ventes">';
			$file = get_exdir($product->id, 3) . "ventes-".$product->id.".png";
			$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_product&amp;file='.$file;
			print '<img src="'.$url.'" alt="Ventes">';
			print '</td></tr>';
		}

		print '</table>';
		print '</div>';

		
		
		print '<table width="100%">';

		// Generation des graphs
		$WIDTH=380;
		$HEIGHT=160;
		$dir = $conf->produit->dir_temp;
		if (! file_exists($dir.'/'.$product->id))
		{
			if (create_exdir($dir.'/'.$product->id) < 0)
			{
				$mesg = $langs->trans("ErrorCanNotCreateDir",$dir);
			}
		}
		
		$graphfiles=array(
		'propal'           =>array('modulepart'=>'productstats_proposals', 
		'file' => $product->id.'/propal12m.png', 
		'label' => $langs->trans("Nombre propal sur les 12 derniers mois")),
		'orders'           =>array('modulepart'=>'productstats_orders', 
		'file' => $product->id.'/orders12m.png', 
		'label' => $langs->trans("Nombre commande clients sur les 12 derniers mois")),
		'invoices'         =>array('modulepart'=>'productstats_invoices', 
		'file' => $product->id.'/invoices12m.png', 
		'label' => $langs->trans("Nombre facture clients sur les 12 derniers mois")),
		'invoicessuppliers'=>array('modulepart'=>'productstats_invoicessuppliers', 
		'file' => $product->id.'/invoicessuppliers12m.png', 
		'label' => $langs->trans("Nombre facture fournisseurs sur les 12 derniers mois")),

		//			'orderssuppliers'  =>array('modulepart'=>'productstats_orderssuppliers', 'file' => $product->id.'/orderssuppliers12m.png', 'label' => $langs->trans("Nombre commande fournisseurs sur les 12 derniers mois")),
		//			'contracts'        =>array('modulepart'=>'productstats_contracts', 'file' => $product->id.'/contracts12m.png', 'label' => $langs->trans("Nombre contrats sur les 12 derniers mois")),

		);
		
		$px = new DolGraph();
		$mesg = $px->isGraphKo();
		if (! $mesg)
		{
			foreach($graphfiles as $key => $val)
			{
				if (! $graphfiles[$key]['file']) continue;
				
				$graph_data = array();
				
				// \todo Test si deja existant et recent, on ne genere pas
				if ($key == 'propal')            $graph_data = $product->get_nb_propal($socid);
				if ($key == 'orders')            $graph_data = $product->get_nb_order($socid);
				if ($key == 'invoices')          $graph_data = $product->get_nb_vente($socid);
				if ($key == 'invoicessuppliers') $graph_data = $product->get_nb_achat($socid);
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
					dolibarr_print_error($db,'Error for calculating graph on key='.$key.' - '.$product->error);
				}
			}
		}
		
		$mesg = $langs->trans("ChartGenerated");
		
		// Affichage graphs
		$i=0;
		foreach($graphfiles as $key => $val)
		{
			if (! $graphfiles[$key]['file']) continue;
			
			if ($graphfiles == 'propal' && ! $user->right->propale->lire) continue;
			if ($graphfiles == 'order' && ! $user->right->commande->lire) continue;
			if ($graphfiles == 'invoices' && ! $user->right->facture->lire) continue;
			if ($graphfiles == 'invoices_suppliers' && ! $user->right->fournisseur->facture->lire) continue;
			
			
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
			// Date génération
			print '<tr>';
			if (file_exists($dir."/".$graphfiles[$key]['file']) && filemtime($dir."/".$graphfiles[$key]['file']) && ! $px->isGraphKo())
			{
				print '<td>'.$langs->trans("GeneratedOn",dolibarr_print_date(filemtime($dir."/".$graphfiles[$key]['file']),"dayhour")).'</td>';
			}
			else
			{
				print '<td>'.($mesg?'<font class="error">'.$mesg.'</font>':$langs->trans("ChartNotGenerated")).'</td>';
			}
			print '<td align="center"><a href="fiche.php?id='.$product->id.'&amp;action=recalcul">'.img_picto($langs->trans("ReCalculate"),'refresh').'</a></td>';
			print '</tr>';
			print '</table>';
			
			print '</td>';
			
			if ($i % 2 == 1) print '</tr>';
			
			
			$i++;
		}
		
		if ($i % 2 == 1) print '<td>&nbsp;</td></tr>';
		
		print '</table>';
		
		// Juste pour éviter bug IE qui réorganise mal div précédents si celui-ci absent en fin de page
		print '<div class="tabsAction">';
		print '</div>';
		
	}
}
else
{
	dolibarr_print_error();
}


$db->close();

llxFooter('$Date$ - $Revision$');

?>
