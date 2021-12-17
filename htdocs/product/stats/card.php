<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (c) 2004-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2013		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2019		Thibault FOUCART		<support@ptibogxiv.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width', 380);
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height', 160);

// Load translation files required by the page
$langs->loadLangs(array('companies', 'products', 'stocks', 'bills', 'other'));

$id		= GETPOST('id', 'int'); // For this page, id can also be 'all'
$ref	= GETPOST('ref', 'alpha');
$mode = (GETPOST('mode', 'alpha') ? GETPOST('mode', 'alpha') : 'byunit');
$search_year   = GETPOST('search_year', 'int');
$search_categ  = GETPOST('search_categ', 'int');
$notab = GETPOST('notab', 'int');

$error = 0;
$mesg = '';
$graphfiles = array();

$socid = '';
if (!empty($user->socid)) {
	$socid = $user->socid;
}

// Security check
$fieldvalue = ($id > 0 ? $id : $ref);
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('productstatscard', 'globalcard'));

$tmp = dol_getdate(dol_now());
$currentyear = $tmp['year'];
if (empty($search_year)) {
	$search_year = $currentyear;
}
$moreforfilter = "";

$object = new Product($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
}

$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);


/*
 * Actions
 */

// None


/*
 *	View
 */

$form = new Form($db);
$htmlother = new FormOther($db);

if (!($id > 0) && empty($ref) || $notab) {
	$notab = 1;

	llxHeader("", $langs->trans("ProductStatistics"));

	$type = GETPOST('type', 'int');

	$helpurl = '';
	if ($type == '0') {
		$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
		//$title=$langs->trans("StatisticsOfProducts");
		$title = $langs->trans("Statistics");
	} elseif ($type == '1') {
		$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
		//$title=$langs->trans("StatisticsOfServices");
		$title = $langs->trans("Statistics");
	} else {
		$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
		//$title=$langs->trans("StatisticsOfProductsOrServices");
		$title = $langs->trans("Statistics");
	}

	$picto = 'product';
	if ($type == 1) {
		$picto = 'service';
	}

	print load_fiche_titre($title, $mesg, $picto);
} else {
	$result = $object->fetch($id, $ref);

	$title = $langs->trans('ProductServiceCard');
	$helpurl = '';
	$shortlabel = dol_trunc($object->label, 16);
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
		$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Statistics');
		$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
		$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Statistics');
		$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
	}

	llxHeader('', $title, $helpurl);
}


if ($result && ($id > 0 || !empty($ref)) && empty($notab)) {
	$head = product_prepare_head($object);
	$titre = $langs->trans("CardProduct".$object->type);
	$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

	print dol_get_fiche_head($head, 'stats', $titre, -1, $picto);

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', '', '', '', 0, '', '', 1);

	print dol_get_fiche_end();
}
if ((!($id > 0) && empty($ref)) || $notab) {
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/product/stats/card.php'.($type != '' ? '?type='.((int) $type) : '');
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

	$head[$h][0] = DOL_URL_ROOT.'/product/popuprop.php'.($type != '' ? '?type='.((int) $type) : '');
	$head[$h][1] = $langs->trans("ProductsPerPopularity");
	$head[$h][2] = 'popularity';
	$h++;

	print dol_get_fiche_head($head, 'chart', $langs->trans("Statistics"), -1);
}


if ($result || !($id > 0)) {
	print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	if (empty($id) || $notab) {
		print '<input type="hidden" name="notab" value="1">';
	}

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';

	if (!($id > 0) || $notab) {
		// Type
		print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td>';
		$array = array('-1'=>'&nbsp;', '0'=>$langs->trans('Product'), '1'=>$langs->trans('Service'));
		print $form->selectarray('type', $array, $type, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100');
		print '</td></tr>';

		// Product
		print '<tr><td class="titlefield">'.$langs->trans("ProductOrService").'</td><td>';
		print img_picto('', 'product', 'class="pictofixedwidth"');
		print $form->select_produits($id, 'id', '', 0, 0, 1, 2, '', ($conf->dol_optimize_smallscreen ? 1 : 0), array(), 0, '1', 0, 'widthcentpercentminusx maxwidth400');
		print '</td></tr>';

		// Tag
		if ($conf->categorie->enabled) {
			print '<tr><td class="titlefield">'.$langs->trans("Categories").'</td><td>';
			$moreforfilter .= img_picto($langs->trans("Categories"), 'category', 'class="pictofixedwidth"');
			$moreforfilter .= $htmlother->select_categories(Categorie::TYPE_PRODUCT, $search_categ, 'search_categ', 1, 1, 'widthcentpercentminusx maxwidth400');
			print $moreforfilter;
			print '</td></tr>';
		}
	} else {
		print '<input type="hidden" name="id" value="'.$id.'">';
	}

	// Year
	print '<tr><td class="titlefield">'.$langs->trans("Year").'</td><td>';
	$arrayyears = array();
	for ($year = $currentyear - 25; $year < $currentyear; $year++) {
		$arrayyears[$year] = $year;
	}
	if (!in_array($year, $arrayyears)) {
		$arrayyears[$year] = $year;
	}
	if (!in_array($currentyear, $arrayyears)) {
		$arrayyears[$currentyear] = $currentyear;
	}
	arsort($arrayyears);
	print $form->selectarray('search_year', $arrayyears, $search_year, 1, 0, 0, '', 0, 0, 0, '', 'width75');
	print '</td></tr>';
	print '</table>';
	print '<div class="center"><input type="submit" name="submit" class="button small" value="'.$langs->trans("Refresh").'"></div>';
	print '</form><br>';

	print '<br>';


	// Choice of stats mode (byunit or bynumber)
	if (!empty($conf->dol_use_jmobile)) {
		print "\n".'<div class="fichecenter"><div class="nowrap">'."\n";
	}

	if ($mode == 'bynumber') {
		print '<a class="a-mesure-disabled marginleftonly marginrightonly reposition" href="'.$_SERVER["PHP_SELF"].'?'.(GETPOSTISSET('id') ? 'id='.GETPOST('id', 'int') : 'id='.$object->id).(($type != '' && $type != '-1') ? '&type='.((int) $type) : '').'&mode=byunit&search_year='.((int) $search_year).($notab ? '&notab='.$notab : '').'">';
	} else {
		print '<span class="a-mesure marginleftonly marginrightonly">';
	}
	print $langs->trans("StatsByNumberOfUnits");
	if ($mode == 'bynumber') {
		print '</a>';
	} else {
		print '</span>';
	}

	if (!empty($conf->dol_use_jmobile)) {
		print '</div>'."\n".'<div class="nowrap">'."\n";
	}

	if ($mode == 'byunit') {
		print '<a class="a-mesure-disabled marginleftonly marginrightonly reposition" href="'.$_SERVER["PHP_SELF"].'?'.(GETPOSTISSET('id') ? 'id='.GETPOST('id', 'int') : 'id='.$object->id).(($type != '' && $type != '-1') ? '&type='.((int) $type) : '').'&mode=bynumber&search_year='.((int) $search_year).($notab ? '&notab='.$notab : '').'">';
	} else {
		print '<span class="a-mesure marginleftonly marginrightonly">';
	}
	print $langs->trans("StatsByNumberOfEntities");
	if ($mode == 'byunit') {
		print '</a>';
	} else {
		print '</span>';
	}

	if (!empty($conf->dol_use_jmobile)) {
		print '</div></div>';
	} else {
		print '<br>';
	}
	print '<br>';

	// Generation of graphs
	$dir = (!empty($conf->product->multidir_temp[$conf->entity]) ? $conf->product->multidir_temp[$conf->entity] : $conf->service->multidir_temp[$conf->entity]);
	if ($object->id > 0) {  // We are on statistics for a dedicated product
		if (!file_exists($dir.'/'.$object->id)) {
			if (dol_mkdir($dir.'/'.$object->id) < 0) {
				$mesg = $langs->trans("ErrorCanNotCreateDir", $dir);
				$error++;
			}
		}
	}

	if ($conf->propal->enabled) {
		$graphfiles['propal'] = array('modulepart'=>'productstats_proposals',
		'file' => $object->id.'/propal12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.($search_year > 0 ? '_year'.$search_year : '').'.png',
		'label' => ($mode == 'byunit' ? $langs->transnoentitiesnoconv("NumberOfUnitsProposals") : $langs->transnoentitiesnoconv("NumberOfProposals")));
	}

	if ($conf->supplier_proposal->enabled) {
		$graphfiles['proposalssuppliers'] = array('modulepart'=>'productstats_proposalssuppliers',
		'file' => $object->id.'/proposalssuppliers12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.($search_year > 0 ? '_year'.$search_year : '').'.png',
		'label' => ($mode == 'byunit' ? $langs->transnoentitiesnoconv("NumberOfUnitsSupplierProposals") : $langs->transnoentitiesnoconv("NumberOfSupplierProposals")));
	}

	if ($conf->order->enabled) {
		$graphfiles['orders'] = array('modulepart'=>'productstats_orders',
		'file' => $object->id.'/orders12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.($search_year > 0 ? '_year'.$search_year : '').'.png',
		'label' => ($mode == 'byunit' ? $langs->transnoentitiesnoconv("NumberOfUnitsCustomerOrders") : $langs->transnoentitiesnoconv("NumberOfCustomerOrders")));
	}

	if ($conf->supplier_order->enabled) {
		$graphfiles['orderssuppliers'] = array('modulepart'=>'productstats_orderssuppliers',
		'file' => $object->id.'/orderssuppliers12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.($search_year > 0 ? '_year'.$search_year : '').'.png',
		'label' => ($mode == 'byunit' ? $langs->transnoentitiesnoconv("NumberOfUnitsSupplierOrders") : $langs->transnoentitiesnoconv("NumberOfSupplierOrders")));
	}

	if ($conf->facture->enabled) {
		$graphfiles['invoices'] = array('modulepart'=>'productstats_invoices',
		'file' => $object->id.'/invoices12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.($search_year > 0 ? '_year'.$search_year : '').'.png',
		'label' => ($mode == 'byunit' ? $langs->transnoentitiesnoconv("NumberOfUnitsCustomerInvoices") : $langs->transnoentitiesnoconv("NumberOfCustomerInvoices")));
	}

	if ($conf->supplier_invoice->enabled) {
		$graphfiles['invoicessuppliers'] = array('modulepart'=>'productstats_invoicessuppliers',
		'file' => $object->id.'/invoicessuppliers12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.($search_year > 0 ? '_year'.$search_year : '').'.png',
		'label' => ($mode == 'byunit' ? $langs->transnoentitiesnoconv("NumberOfUnitsSupplierInvoices") : $langs->transnoentitiesnoconv("NumberOfSupplierInvoices")));
	}

	if ($conf->contrat->enabled) {
		$graphfiles['contracts'] = array('modulepart'=>'productstats_contracts',
			'file' => $object->id.'/contracts12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.($search_year > 0 ? '_year'.$search_year : '').'.png',
			'label' => ($mode == 'byunit' ? $langs->transnoentitiesnoconv("NumberOfUnitsContracts") : $langs->transnoentitiesnoconv("NumberOfContracts")));
	}

	if ($conf->mrp->enabled) {
		$graphfiles['mrp'] = array('modulepart'=>'productstats_mrp',
			'file' => $object->id.'/mos12m'.((string) $type != '' ? '_type'.$type : '').'_'.$mode.($search_year > 0 ? '_year'.$search_year : '').'.png',
			'label' => ($mode == 'byunit' ? $langs->transnoentitiesnoconv("NumberOfUnitsMos") : $langs->transnoentitiesnoconv("NumberOfMos")));
	}

	$px = new DolGraph();

	if (!$error && count($graphfiles) > 0) {
		$mesg = $px->isGraphKo();
		if (!$mesg) {
			foreach ($graphfiles as $key => $val) {
				if (!$graphfiles[$key]['file']) {
					continue;
				}

				$graph_data = array();

				if (dol_is_file($dir.'/'.$graphfiles[$key]['file'])) {
					// TODO Load cachefile $graphfiles[$key]['file']
				} else {
					$morefilters = '';
					if ($search_categ > 0) {
						$categ = new Categorie($db);
						$categ->fetch($search_categ);
						$listofprodids = $categ->getObjectsInCateg('product', 1);
						$morefilters = ' AND d.fk_product IN ('.$db->sanitize((is_array($listofprodids) && count($listofprodids)) ? join(',', $listofprodids) : '0').')';
					}
					if ($search_categ == -2) {
						$morefilters = ' AND d.fk_product NOT IN (SELECT cp.fk_product from '.MAIN_DB_PREFIX.'categorie_product as cp)';
					}

					if ($key == 'propal') {
						$graph_data = $object->get_nb_propal($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters);
					}
					if ($key == 'orders') {
						$graph_data = $object->get_nb_order($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters);
					}
					if ($key == 'invoices') {
						$graph_data = $object->get_nb_vente($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters);
					}
					if ($key == 'proposalssuppliers') {
						$graph_data = $object->get_nb_propalsupplier($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters);
					}
					if ($key == 'invoicessuppliers') {
						$graph_data = $object->get_nb_achat($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters);
					}
					if ($key == 'orderssuppliers') {
						$graph_data = $object->get_nb_ordersupplier($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters);
					}
					if ($key == 'contracts') {
						$graph_data = $object->get_nb_contract($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters);
					}
					if ($key == 'mrp') {
						$graph_data = $object->get_nb_mos($socid, $mode, ((string) $type != '' ? $type : -1), $search_year, $morefilters);
					}

					// TODO Save cachefile $graphfiles[$key]['file']
				}

				if (is_array($graph_data)) {
					$px->SetData($graph_data);
					$px->SetYLabel($graphfiles[$key]['label']);
					$px->SetMaxValue($px->GetCeilMaxValue() < 0 ? 0 : $px->GetCeilMaxValue());
					$px->SetMinValue($px->GetFloorMinValue() > 0 ? 0 : $px->GetFloorMinValue());
					$px->setShowLegend(0);
					$px->SetWidth($WIDTH);
					$px->SetHeight($HEIGHT);
					$px->SetHorizTickIncrement(1);
					$px->SetShading(3);
					//print 'x '.$key.' '.$graphfiles[$key]['file'];

					$url = DOL_URL_ROOT.'/viewimage.php?modulepart='.$graphfiles[$key]['modulepart'].'&entity='.((int) $object->entity).'&file='.urlencode($graphfiles[$key]['file']).($notab ? '&notab='.$notab : '');
					$px->draw($dir."/".$graphfiles[$key]['file'], $url);

					$graphfiles[$key]['total'] = $px->total();
					$graphfiles[$key]['output'] = $px->show();
				} else {
					dol_print_error($db, 'Error for calculating graph on key='.$key.' - '.$object->error);
				}
			}

			//setEventMessages($langs->trans("ChartGenerated"), null, 'mesgs');
		}
	}

	// Show graphs
	$i = 0;
	if (count($graphfiles) > 0) {
		foreach ($graphfiles as $key => $val) {
			if (!$graphfiles[$key]['file']) {
				continue;
			}

			if ($graphfiles == 'propal' && !$user->rights->propale->lire) {
				continue;
			}
			if ($graphfiles == 'order' && !$user->rights->commande->lire) {
				continue;
			}
			if ($graphfiles == 'invoices' && !$user->rights->facture->lire) {
				continue;
			}
			if ($graphfiles == 'proposals_suppliers' && !$user->rights->supplier_proposal->lire) {
				continue;
			}
			if ($graphfiles == 'invoices_suppliers' && empty($user->rights->fournisseur->facture->lire)) {
				continue;
			}
			if ($graphfiles == 'orders_suppliers' && empty($user->rights->fournisseur->commande->lire)) {
				continue;
			}
			if ($graphfiles == 'mrp' && empty($user->rights->mrp->mo->read)) {
				continue;
			}


			if ($i % 2 == 0) {
				print "\n".'<div class="fichecenter"><div class="fichehalfleft">'."\n";
			} else {
				print "\n".'<div class="fichehalfright">'."\n";
			}

			// Date generation
			if ($graphfiles[$key]['output'] && !$px->isGraphKo()) {
				if (file_exists($dir."/".$graphfiles[$key]['file']) && filemtime($dir."/".$graphfiles[$key]['file'])) {
					$dategenerated = $langs->trans("GeneratedOn", dol_print_date(filemtime($dir."/".$graphfiles[$key]['file']), "dayhour"));
				} else {
					$dategenerated = $langs->trans("GeneratedOn", dol_print_date(dol_now(), "dayhour"));
				}
			} else {
				$dategenerated = ($mesg ? '<span class="error">'.$mesg.'</span>' : $langs->trans("ChartNotGenerated"));
			}
			$linktoregenerate = '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?'.(GETPOSTISSET('id') ? 'id='.GETPOST('id', 'int') : 'id='.$object->id).(((string) $type != '' && $type != '-1') ? '&type='.((int) $type) : '').'&action=recalcul&mode='.urlencode($mode).'&search_year='.((int) $search_year).($search_categ > 0 ? '&search_categ='.((int) $search_categ) : '').'">';
			$linktoregenerate .= img_picto($langs->trans("ReCalculate").' ('.$dategenerated.')', 'refresh');
			$linktoregenerate .= '</a>';


			// Show graph
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			// Label
			print '<tr class="liste_titre"><td>';
			print $graphfiles[$key]['label'];
			print ' <span class="opacitymedium">('.$graphfiles[$key]['total'].')</span></td>';
			print '<td align="right">'.$linktoregenerate.'</td>';
			print '</tr>';
			// Image
			print '<tr class="impair"><td colspan="2" class="nohover" align="center">';
			print $graphfiles[$key]['output'];
			print '</td></tr>';
			print '</table>';
			print '</div>';

			if ($i % 2 == 0) {
				print "\n".'</div>'."\n";
			} else {
				print "\n".'</div></div>';
				print '<div class="clear"><div class="fichecenter"><br></div></div>'."\n";
			}

			$i++;
		}
	}
	// div not closed
	if ($i % 2 == 1) {
		print "\n".'<div class="fichehalfright">'."\n";
		print "\n".'</div></div>';
		print '<div class="clear"><div class="fichecenter"><br></div></div>'."\n";
	}
}

if (!($id > 0)) {
	print dol_get_fiche_end();
}

// End of page
llxFooter();
$db->close();
