<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/product/stock/fiche-valo.php
 *	\ingroup    stock
 *	\brief      Page fiche de valorisation du stock dans l'entrepot
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'companies'));
$mesg = '';

// Security check
$result=restrictedArea($user,'stock');


/*
 * View
 */

$form=new Form($db);

$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
llxHeader("",$langs->trans("WarehouseCard"),$help_url);

if ($_GET["id"])
{
	if ($mesg) print $mesg;

	$entrepot = new Entrepot($db);
	$result = $entrepot->fetch($_GET["id"]);
	if ($result < 0)
	{
		dol_print_error($db);
	}


	$head = stock_prepare_head($entrepot);

	dol_fiche_head($head, 'value', $langs->trans("Warehouse"), 0, 'stock');


	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($entrepot,'id','',1,'rowid','libelle');
	print '</td>';

	print '<tr><td>'.$langs->trans("LocationSummary").'</td><td colspan="3">'.$entrepot->lieu.'</td></tr>';

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">'.nl2br($entrepot->description).'</td></tr>';

	print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3">';
	print $entrepot->address;
	print '</td></tr>';

	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$entrepot->zip.'</td>';
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$entrepot->town.'</td></tr>';

	print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
	print $entrepot->country;
	print '</td></tr>';

	// Statut
	print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">'.$entrepot->getLibStatut(4).'</td></tr>';

	$calcproducts=$entrepot->nb_products();

	// Nb of products
	print '<tr><td class="tdtop">'.$langs->trans("NumberOfProducts").'</td><td colspan="3">';
	print empty($calcproducts['nb'])?'0':$calcproducts['nb'];
	print "</td></tr>";

	// Value
	print '<tr><td class="tdtop">'.$langs->trans("EstimatedStockValueShort").'</td><td colspan="3">';
	print empty($calcproducts['value'])?'0':$calcproducts['value'];
	print "</td></tr>";

	print "</table>";
	print '</div>';


	/* ************************************************************************** */
	/*                                                                            */
	/* Graph                                                                      */
	/*                                                                            */
	/* ************************************************************************** */

	print "<div class=\"graph\">\n";
	$year = strftime("%Y",time());

	$file=$conf->stock->dir_temp.'/entrepot-'.$entrepot->id.'-'.($year).'.png';

	// TODO Build graph in $file from a table called llx_stock_log






	if (file_exists($file))
	{
		$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_stock&amp;file=entrepot-'.$entrepot->id.'-'.$year.'.png';
		print '<img src="'.$url.'" alt="Valorisation du stock annee '.($year).'">';

		if (file_exists(DOL_DATA_ROOT.'/entrepot/temp/entrepot-'.$entrepot->id.'-'.($year-1).'.png'))
		{
			$url=DOL_URL_ROOT.'/viewimage.php?modulepart=graph_stock&amp;file=entrepot-'.$entrepot->id.'-'.($year-1).'.png';
			print '<br><img src="'.$url.'" alt="Valorisation du stock annee '.($year-1).'">';
		}
	}
	else
	{
		$langs->load("errors");
		print $langs->trans("FeatureNotYetAvailable");
	}

	print "</div>";
}

llxFooter();
$db->close();
