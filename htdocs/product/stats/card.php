<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (c) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2013		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

//$width = DolChartjs::getDefaultGraphSizeForStats('width', 70);
//$height = DolChartjs::getDefaultGraphSizeForStats('height', 25);
$width = 70;
$height = 25;

// Load translation files required by the page
$langs->loadLangs(array('companies', 'products', 'stocks', 'bills', 'other'));

$id = GETPOST('id', 'int'); // For this page, id can also be 'all'
$ref = GETPOST('ref');
$mode = (GETPOST('mode') ? GETPOST('mode') : 'byunit');
$search_year = GETPOST('search_year', 'int');
$search_categ = GETPOST('search_categ', 'int');

$error = 0;
$graphfiles = array();

$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;

// Security check
$fieldvalue = (! empty($id) ? $id : $ref);
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

$tmp = dol_getdate(dol_now());
$currentyear=$tmp['year'];
if (empty($search_year)) $search_year=$currentyear;


/*
 * Actions
 */

// None


/*
 *	View
 */

$form = new Form($db);
$htmlother = new FormOther($db);
$object = new Product($db);

if (! $id && empty($ref))
{
    llxHeader("",$langs->trans("ProductStatistics"));

    $type = (int) GETPOST('type','int');

   	$helpurl='';
    if ($type == 0) {
        $helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
        //$title=$langs->trans("StatisticsOfProducts");
        $title=$langs->trans("Statistics");
    } elseif ($type == 1) {
        $helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
        //$title=$langs->trans("StatisticsOfServices");
        $title = $langs->trans("Statistics");
    } else {
        $helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
        //$title=$langs->trans("StatisticsOfProductsOrServices");
        $title = $langs->trans("Statistics");
    }

    print load_fiche_titre($title, '','title_products.png');
}
else
{
    $result = $object->fetch($id, $ref);

	$title = $langs->trans('ProductServiceCard');
	$helpurl = '';
	$shortlabel = dol_trunc($object->label,16);
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
		$title = $langs->trans('Product')." ". $shortlabel ." - ".$langs->trans('Statistics');
		$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
		$title = $langs->trans('Service')." ". $shortlabel ." - ".$langs->trans('Statistics');
		$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
	}

	llxHeader('', $title, $helpurl);
}


if ($result && (! empty($id) || ! empty($ref))) {
	$head=product_prepare_head($object);
	$titre=$langs->trans("CardProduct".$object->type);
	$picto=($object->type==Product::TYPE_SERVICE?'service':'product');

	dol_fiche_head($head, 'stats', $titre, -1, $picto);

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'ref', $linkback, ($user->societe_id?0:1), 'ref', '', '', '', 0, '', '', 1);

    dol_fiche_end();
}
if (empty($id) & empty($ref)) {
    $h=0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/product/stats/card.php'.($type != ''?'?type='.$type:'');
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

    dol_fiche_head($head, 'chart', $langs->trans("Statistics"), -1);
}


if ($result || empty($id)) {
    print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="id" value="'.$id.'">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';

	if (empty($id)) {
    	// Type
		print '<tr><td class="titlefield">'.$langs->trans("ProductsAndServices").'</td><td>';
		$array=array('-1'=>'&nbsp;', '0'=>$langs->trans('Product'), '1'=>$langs->trans('Service'));
		print $form->selectarray('type', $array, $type);
		print '</td></tr>';

		// Tag
		if ($conf->categorie->enabled)
		{
    		print '<tr><td class="titlefield">'.$langs->trans("Categories").'</td><td>';
    		//$moreforfilter.='<div class="divsearchfield">';
    		$moreforfilter.=$htmlother->select_categories(Categorie::TYPE_PRODUCT,$search_categ,'search_categ',1);
    		//$moreforfilter.='</div>';
    		print $moreforfilter;
    		print '</td></tr>';
		}
	}

	// Year
	print '<tr><td class="titlefield">'.$langs->trans("Year").'</td><td>';
	$arrayyears = array();
	for ($year = $currentyear - 10; $year < $currentyear + 10 ; $year++)
	{
	    $arrayyears[$year]=$year;
	}
	if (! in_array($year,$arrayyears)) $arrayyears[$year]=$year;
	if (! in_array($nowyear,$arrayyears)) $arrayyears[$nowyear]=$nowyear;
	arsort($arrayyears);
	print $form->selectarray('search_year', $arrayyears, $search_year, 0);
	print '</td></tr>';
	print '</table>';
	print '<div class="center"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></div>';
	print '</form>';

	print '<br>';


	// Choice of stats mode (byunit or bynumber)
	if (! empty($conf->dol_use_jmobile)) print "\n".'<div class="fichecenter"><div class="nowrap">'."\n";

	if ($mode == 'bynumber') {
        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.(GETPOST('id')?GETPOST('id'):$object->id).($type != '' ? '&type='.$type:'').'&mode=byunit&search_year='.$search_year.'">';
    } else {
        print img_picto('', 'tick').' ';
    }
	print $langs->trans("StatsByNumberOfUnits");
	if ($mode == 'bynumber') print '</a>';

	if (! empty($conf->dol_use_jmobile)) print '</div>'."\n".'<div class="nowrap">'."\n";
	else print ' &nbsp; / &nbsp; ';

	if ($mode == 'byunit') print '<a href="'.$_SERVER["PHP_SELF"].'?id='.(GETPOST('id')?GETPOST('id'):$object->id).($type != '' ? '&type='.$type:'').'&mode=bynumber&search_year='.$search_year.'">';
	else print img_picto('', 'tick').' ';
	print $langs->trans("StatsByNumberOfEntities");
	if ($mode == 'byunit') print '</a>';

	if (! empty($conf->dol_use_jmobile)) print '</div></div>';
	else print '<br>';
	print '<br>';

	//print '<table width="100%">';

	if ($conf->propal->enabled) {
        $graphfiles['propal'] = array(
            'modulepart'=>'productstats_proposals',
            'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsProposals"):$langs->transnoentitiesnoconv("NumberOfProposals")),
        );
	}

    if ($conf->supplier_proposal->enabled) {
		$graphfiles['proposalssuppliers'] = array(
            'modulepart'=>'productstats_proposalssuppliers',
            'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsSupplierProposals"):$langs->transnoentitiesnoconv("NumberOfSupplierProposals")),
        );
    }

    if ($conf->order->enabled) {
        $graphfiles['orders'] = array(
            'modulepart'=>'productstats_orders',
            'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsCustomerOrders"):$langs->transnoentitiesnoconv("NumberOfCustomerOrders")),
        );
	}

	if ($conf->fournisseur->enabled) {
		$graphfiles['orderssuppliers'] = array(
            'modulepart'=>'productstats_orderssuppliers',
            'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsSupplierOrders"):$langs->transnoentitiesnoconv("NumberOfSupplierOrders")),
        );
	}

    if ($conf->facture->enabled) {
        $graphfiles['invoices'] = array(
            'modulepart'=>'productstats_invoices',
            'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsCustomerInvoices"):$langs->transnoentitiesnoconv("NumberOfCustomerInvoices")),
        );

        $graphfiles['invoicessuppliers'] = array(
            'modulepart'=>'productstats_invoicessuppliers',
            'label' => ($mode=='byunit'?$langs->transnoentitiesnoconv("NumberOfUnitsSupplierInvoices"):$langs->transnoentitiesnoconv("NumberOfSupplierInvoices")),
        );
    }

	if (! $error && count($graphfiles)>0) {
		foreach($graphfiles as $key => $val) {
			$graph_datas = array();
            $px = new DolChartJs();

		    $morefilters='';
		    if ($search_categ > 0) {
		        $categ = new Categorie($db);
		        $categ->fetch($search_categ);
		        $listofprodids = $categ->getObjectsInCateg('product', 1);
		        $morefilters = ' AND d.fk_product IN ('.((is_array($listofprodids) && count($listofprodids)) ? join(',',$listofprodids):'0').')';
		    }
            if ($search_categ == -2) {
                $morefilters = ' AND d.fk_product NOT IN (SELECT cp.fk_product from '.MAIN_DB_PREFIX.'categorie_product as cp)';
            }

            if ($key == 'propal') {
                $graph_datas = $object->get_nb_propal($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters, 1, $px->datacolor, $px->bgdatacolor);
            } elseif ($key == 'orders') {
                $graph_datas = $object->get_nb_order($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters, 1, $px->datacolor, $px->bgdatacolor);
            } elseif ($key == 'invoices') {
                $graph_datas = $object->get_nb_vente($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters, 1, $px->datacolor, $px->bgdatacolor);
            } elseif ($key == 'proposalssuppliers') {
                $graph_datas = $object->get_nb_propalsupplier($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters, 1, $px->datacolor, $px->bgdatacolor);
            } elseif ($key == 'invoicessuppliers') {
                $graph_datas = $object->get_nb_achat($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters, 1, $px->datacolor, $px->bgdatacolor);
            } elseif ($key == 'orderssuppliers') {
                $graph_datas = $object->get_nb_ordersupplier($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters, 1, $px->datacolor, $px->bgdatacolor);
            }

            if (is_array($graph_datas)) {
                $px->element($key)
                ->setType('bar')
                ->setLabels($graph_datas['labelgroup'])
                ->setDatasets($graph_datas['dataset'])
                ->setSize(array('width' => $width, 'height' => $height))
                ->setOptions(
                    array(
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'legend' => array(
                            'display' => false,
                            'position' => 'bottom',
                        ),
                        'title' => array(
                            'display' => true,
                            'text' => $langs->transnoentities($val['label']),
                        ),
                        'scales' => array(
                            'yAxes' => array(
                                array(
                                    'gridLines' => array(
                                        'color' => 'black',
                                        'borderDash' => array(2, 3),
                                    ),
                                    'ticks' => array(
                                        'min' => 0,
                                    ),
                                    'scaleLabel' => array(
                                        'display' => true,
                                        'labelString' => $langs->transnoentitiesnoconv("Qty"),
                                        'fontColor' => 'black',
                                    ),
                                )
                            ),
                        ),
                    )
                );
                $graphfiles[$key]['output'] = $px->renderChart();
            } else {
				dol_print_error($db, 'Error for calculating graph on key='.$key.' - '.$object->error);
			}
		}
	}

	// Show graphs
	$i = 0;
	if (count($graphfiles)>0) {
		foreach($graphfiles as $key => $val) {
			if ($graphfiles == 'propal' && ! $user->rights->propale->lire) continue;
			if ($graphfiles == 'order' && ! $user->rights->commande->lire) continue;
			if ($graphfiles == 'invoices' && ! $user->rights->facture->lire) continue;
			if ($graphfiles == 'proposals_suppliers' && ! $user->rights->supplier_proposal->lire) continue;
			if ($graphfiles == 'invoices_suppliers' && ! $user->rights->fournisseur->facture->lire) continue;
			if ($graphfiles == 'orders_suppliers' && ! $user->rights->fournisseur->commande->lire) continue;


			if ($i % 2 == 0) {
				print '<div class="fichecenter"><div class="fichehalfleft">'."\n";
			} else {
				print '<div class="fichehalfright"><div class="ficheaddleft">'."\n";
			}

			$linktoregenerate='<a href="'.$_SERVER["PHP_SELF"].'?id='.(GETPOST('id')?GETPOST('id'):$object->id).((string) $type != ''?'&type='.$type:'').'&action=recalcul&mode='.$mode.'&search_year='.$search_year.'&search_categ='.$search_categ.'">'.img_picto($langs->trans("ReCalculate"), 'refresh').'</a>';

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

			if ($i % 2 == 0) {
				print "\n".'</div>'."\n";
			} else {
				print "\n".'</div></div></div>';
				print '<div class="clear"><div class="fichecenter"><br></div></div>'."\n";
			}
            $i++;
		}
	}
	// div not closed
	if ($i % 2 == 1) {
		print '<div class="fichehalfright"><div class="ficheaddleft">'."\n";
		print "</div></div></div>\n";
		print '<div class="clear"><div class="fichecenter"><br></div></div>'."\n";
	}
}

if (! $id) {
    dol_fiche_end();
}

// End of page
llxFooter();
$db->close();
