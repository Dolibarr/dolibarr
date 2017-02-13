<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (c) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$WIDTH=DolGraph::getDefaultGraphSizeForStats('width',380);
$HEIGHT=DolGraph::getDefaultGraphSizeForStats('height',160);

$langs->load("companies");
$langs->load("products");
$langs->load("stocks");
$langs->load("bills");
$langs->load("other");

$id		= GETPOST('id','int');         // For this page, id can also be 'all'
$ref	= GETPOST('ref');
$mode	= (GETPOST('mode') ? GETPOST('mode') : 'byunit');
$error	= 0;
$mesg	= '';
$graphfiles=array();

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

if (! empty($id) || ! empty($ref) || GETPOST('id') == 'all')
{
	$object = new Product($db);
    if (GETPOST('id') == 'all')
    {
        llxHeader("",$langs->trans("ProductStatistics"));

   	    $type = GETPOST('type');

       	$helpurl='';
        if ($type == '0')
        {
            $helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
            //$title=$langs->trans("StatisticsOfProducts");
            $title=$langs->trans("Statistics");
        }
        else if ($type == '1')
        {
            $helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
            //$title=$langs->trans("StatisticsOfServices");
            $title=$langs->trans("Statistics");
        }
        else
        {
            $helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
            //$title=$langs->trans("StatisticsOfProductsOrServices");
            $title=$langs->trans("Statistics");
        }

        print load_fiche_titre($title, $mesg,'title_products.png');
    }
    else
    {
        $result = $object->fetch($id,$ref);

		$title = $langs->trans('ProductServiceCard');
		$helpurl = '';
		$shortlabel = dol_trunc($object->label,16);
		if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
		{
			$title = $langs->trans('Product')." ". $shortlabel ." - ".$langs->trans('Statistics');
			$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
		}
		if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
		{
			$title = $langs->trans('Service')." ". $shortlabel ." - ".$langs->trans('Statistics');
			$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
		}

		llxHeader('', $title, $helpurl);
    }


	if ($result && (! empty($id) || ! empty($ref)))
	{
		$head=product_prepare_head($object);
		$titre=$langs->trans("CardProduct".$object->type);
		$picto=($object->type==Product::TYPE_SERVICE?'service':'product');

		dol_fiche_head($head, 'stats', $titre, 0, $picto);

		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php">'.$langs->trans("BackToList").'</a>';
		
        dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref', '', '', '', 0, '', '', 1);

        dol_fiche_end();
	}
	if (GETPOST('id') == 'all')
	{
        $h=0;
        $head = array();

        $head[$h][0] = DOL_URL_ROOT.'/product/stats/card.php?id=all';
        $head[$h][1] = $langs->trans("Chart");
        $head[$h][2] = 'chart';
        $h++;

    	$title = $langs->trans("ListProductServiceByPopularity");
        if ((string) $type == '1') {
        	$title = $langs->trans("ListServiceByPopularity");
        }
        if ((string) $type == '0') {
        	$title = $langs->trans("ListProductByPopularity");
        }

        $head[$h][0] = DOL_URL_ROOT.'/product/popuprop.php'.($type != ''?'?type='.$type:'');
        $head[$h][1] = $title;
        $head[$h][2] = 'popularityprop';
        $h++;

        dol_fiche_head($head,'chart',$langs->trans("Statistics"));
	}


	if ($result || GETPOST('id') == 'all')
	{
	    if (GETPOST('id') == 'all')
	    {
    		// Choice of type of product
    		if (! empty($conf->dol_use_jmobile)) print "\n".'<div class="fichecenter"><div class="nowrap">'."\n";

    		if ((string) $type != '0') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.(GETPOST('id')?GETPOST('id'):$object->id).'&type=0'.($mode?'&mode='.$mode:'').'">';
    		else print img_picto('','tick').' ';
    		print $langs->trans("Products");
    		if ((string) $type != '0') print '</a>';

    		if (! empty($conf->dol_use_jmobile)) print '</div>'."\n".'<div class="nowrap">'."\n";
    		else print ' &nbsp; / &nbsp; ';

    		if ((string) $type != '1') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.(GETPOST('id')?GETPOST('id'):$object->id).'&type=1'.($mode?'&mode='.$mode:'').'">';
    		else print img_picto('','tick').' ';
    		print $langs->trans("Services");
    		if ((string) $type != '1') print '</a>';

    		if (! empty($conf->dol_use_jmobile)) print '</div>'."\n".'<div class="nowrap">'."\n";
    		else print ' &nbsp; / &nbsp; ';

    		if ((string) $type == '0' || (string) $type == '1') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.(GETPOST('id')?GETPOST('id'):$object->id).($mode?'&mode='.$mode:'').'">';
    		else print img_picto('','tick').' ';
    		print $langs->trans("ProductsAndServices");
    		if ((string) $type == '0' || (string) $type == '1') print '</a>';

    		if (! empty($conf->dol_use_jmobile)) print '</div></div>';
    		else print '<br>';
    		print '<br>';
	    }

		// Choice of stats mode (byunit or bynumber)
		if (! empty($conf->dol_use_jmobile)) print "\n".'<div class="fichecenter"><div class="nowrap">'."\n";

		if ($mode == 'bynumber') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.(GETPOST('id')?GETPOST('id'):$object->id).($type != '' ? '&type='.$type:'').'&mode=byunit">';
		else print img_picto('','tick').' ';
		print $langs->trans("StatsByNumberOfUnits");
		if ($mode == 'bynumber') print '</a>';

		if (! empty($conf->dol_use_jmobile)) print '</div>'."\n".'<div class="nowrap">'."\n";
		else print ' &nbsp; / &nbsp; ';

		if ($mode == 'byunit') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.(GETPOST('id')?GETPOST('id'):$object->id).($type != '' ? '&type='.$type:'').'&mode=bynumber">';
		else print img_picto('','tick').' ';
		print $langs->trans("StatsByNumberOfEntities");
		if ($mode == 'byunit') print '</a>';

		if (! empty($conf->dol_use_jmobile)) print '</div></div>';
		else print '<br>';
		print '<br>';

		//print '<table width="100%">';

		// Generation des graphs
    	$dir = (! empty($conf->product->multidir_temp[$object->entity])?$conf->product->multidir_temp[$object->entity]:$conf->service->multidir_temp[$object->entity]);
		if ($object->id > 0)  // We are on statistics for a dedicated product
		{
    		if (! file_exists($dir.'/'.$object->id))
    		{
    			if (dol_mkdir($dir.'/'.$object->id) < 0)
    			{
    				$mesg = $langs->trans("ErrorCanNotCreateDir",$dir);
    				$error++;
    			}
    		}
		}

		if($conf->propal->enabled) {
			$graphfiles['propal']=array('modulepart'=>'productstats_proposals',
			'file' => $object->id.'/propal12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.'.png',
			'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsProposals"):$langs->transnoentitiesnoconv("NumberOfProposals")));
		}

		if($conf->supplier_proposal->enabled) {
			$graphfiles['proposalssuppliers']=array('modulepart'=>'productstats_proposalssuppliers',
			'file' => $object->id.'/proposalssuppliers12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.'.png',
			'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsSupplierProposals"):$langs->transnoentitiesnoconv("NumberOfSupplierProposals")));
		}

		if($conf->order->enabled) {
			$graphfiles['orders']=array('modulepart'=>'productstats_orders',
			'file' => $object->id.'/orders12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.'.png',
			'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsCustomerOrders"):$langs->transnoentitiesnoconv("NumberOfCustomerOrders")));
		}

		if($conf->fournisseur->enabled) {
			$graphfiles['orderssuppliers']=array('modulepart'=>'productstats_orderssuppliers',
			'file' => $object->id.'/orderssuppliers12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.'.png',
			'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsSupplierOrders"):$langs->transnoentitiesnoconv("NumberOfSupplierOrders")));
		}

		if($conf->facture->enabled) {
			$graphfiles['invoices']=array('modulepart'=>'productstats_invoices',
			'file' => $object->id.'/invoices12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.'.png',
			'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsCustomerInvoices"):$langs->transnoentitiesnoconv("NumberOfCustomerInvoices")));

			$graphfiles['invoicessuppliers']=array('modulepart'=>'productstats_invoicessuppliers',
			'file' => $object->id.'/invoicessuppliers12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.'.png',
			'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsSupplierInvoices"):$langs->transnoentitiesnoconv("NumberOfSupplierInvoices")));
		}

		$px = new DolGraph();

		if (! $error && count($graphfiles)>0)
		{
			$mesg = $px->isGraphKo();
			if (! $mesg)
			{
				foreach($graphfiles as $key => $val)
				{
					if (! $graphfiles[$key]['file']) continue;

					$graph_data = array();

					if (dol_is_file($dir . '/' . $graphfiles[$key]['file']))
					{
    					// TODO Load cachefile $graphfiles[$key]['file']
					}
					else
					{
    					if ($key == 'propal')             $graph_data = $object->get_nb_propal($socid,$mode,((string) $type != '' ? $type : -1));
    					if ($key == 'orders')             $graph_data = $object->get_nb_order($socid,$mode,((string) $type != '' ? $type : -1));
    					if ($key == 'invoices')           $graph_data = $object->get_nb_vente($socid,$mode,((string) $type != '' ? $type : -1));
    					if ($key == 'proposalssuppliers') $graph_data = $object->get_nb_propalsupplier($socid,$mode,((string) $type != '' ? $type : -1));
    					if ($key == 'invoicessuppliers')  $graph_data = $object->get_nb_achat($socid,$mode,((string) $type != '' ? $type : -1));
    					if ($key == 'orderssuppliers')    $graph_data = $object->get_nb_ordersupplier($socid,$mode,((string) $type != '' ? $type : -1));

    					// TODO Save cachefile $graphfiles[$key]['file']
					}

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
		if ( count($graphfiles)>0)
		{
			foreach($graphfiles as $key => $val)
			{
				if (! $graphfiles[$key]['file']) continue;
	
				if ($graphfiles == 'propal' && ! $user->rights->propale->lire) continue;
				if ($graphfiles == 'order' && ! $user->rights->commande->lire) continue;
				if ($graphfiles == 'invoices' && ! $user->rights->facture->lire) continue;
				if ($graphfiles == 'proposals_suppliers' && ! $user->rights->supplier_proposal->lire) continue;
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
	
				// Date generation
				if ($graphfiles[$key]['output'] && ! $px->isGraphKo())
				{
				    if (file_exists($dir."/".$graphfiles[$key]['file']) && filemtime($dir."/".$graphfiles[$key]['file'])) $dategenerated=$langs->trans("GeneratedOn",dol_print_date(filemtime($dir."/".$graphfiles[$key]['file']),"dayhour"));
				    else $dategenerated=$langs->trans("GeneratedOn",dol_print_date(dol_now(),"dayhour"));
				}
				else
				{
				    print $dategenerated=($mesg?'<font class="error">'.$mesg.'</font>':$langs->trans("ChartNotGenerated"));
				}
				$linktoregenerate='<a href="'.$_SERVER["PHP_SELF"].'?id='.(GETPOST('id')?GETPOST('id'):$object->id).((string) $type != ''?'&amp;type='.$type:'').'&amp;action=recalcul&amp;mode='.$mode.'">'.img_picto($langs->trans("ReCalculate").' ('.$dategenerated.')','refresh').'</a>';
				
				// Show graph
				print '<table class="noborder" width="100%">';
				// Label
				print '<tr class="liste_titre"><td>';
				print $graphfiles[$key]['label'];
				print '</td>';
				print '<td align="right">'.$linktoregenerate.'</td>';
				print '</tr>';
				// Image
				print '<tr class="impair"><td colspan="2" class="nohover" align="center">';
				print $graphfiles[$key]['output'];
				print '</td></tr>';
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
		}
		// div not closed
		if ($i % 2 == 1)
		{
			print "\n".'<div class="fichehalfright"><div class="ficheaddleft">'."\n";
			print "\n".'</div></div></div>';
			print '<div class="clear"><div class="fichecenter"><br></div></div>'."\n";
		}
	}

	if (GETPOST('id') == 'all')
	{
	    dol_fiche_end();
	}
}
else
{
	dol_print_error();
}

llxFooter();

$db->close();
