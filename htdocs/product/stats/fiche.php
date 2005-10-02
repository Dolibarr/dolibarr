<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/product/stats/fiche.php
        \ingroup    product
        \brief      Page des stats produits
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("products");
$langs->load("bills");

$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");

if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}
else
{
  $socid = 0;
}


llxHeader('',$langs->trans("Statistics"));

$mesg = '';


/*
 *
 */

if ($_GET["id"])
{
    $product = new Product($db);
    $result = $product->fetch($_GET["id"]);

    if ($result)
    {
        // Efface rep obsolete
        if(is_dir(DOL_DOCUMENT_ROOT."/document/produits"))
        rmdir(DOL_DOCUMENT_ROOT."/document/produits");

        // Création répertoire pour images générées
        // $conf->produit->dir_images définit dans master.inc.php

        $dir = $conf->produit->dir_images."/".$product->id;

        if (! file_exists($dir))
        {
            if (create_exdir($dir) < 0)
            {
                $mesg = $langs->trans("ErrorCanNotCreateDir",$dir);
            }
        }

        $img_propal_name = "propal12mois.png";
        $filenbpropal = $dir . "/" . $img_propal_name;
        $filenbvente  = $dir . "/vente12mois.png";
        $filenbpiece  = $dir . "/vendu12mois.png";

        $WIDTH=380;
        $HEIGHT=200;
        
        $px = new BarGraph();
        $mesg = $px->isGraphKo();
        if (! $mesg)
        {
            $graph_data = $product->get_num_vente($socid);
            $px->SetData($graph_data);
            $px->SetMaxValue($px->GetMaxValue());
            $px->SetWidth($WIDTH);
            $px->SetHeight($HEIGHT);
            $px->draw($filenbvente);

            $px = new BarGraph();
            $graph_data = $product->get_nb_vente($socid);
            $px->SetData($graph_data);
            $px->SetMaxValue($px->GetMaxValue());
            $px->SetWidth($WIDTH);
            $px->SetHeight($HEIGHT);
            $px->draw($filenbpiece);

            $px = new BarGraph();
            $graph_data = $product->get_num_propal($socid);
            $px->SetData($graph_data);
            $px->SetMaxValue($px->GetMaxValue());
            $px->SetWidth($WIDTH);
            $px->SetHeight($HEIGHT);
            $px->draw($filenbpropal);

            $mesg = $langs->trans("ChartGenerated");
        }


        $h=0;

        $head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Card");
        $h++;

        $head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Price");
        $h++;

        if($product->type == 0)
        {
           if ($user->rights->barcode->lire)
           {
               if ($conf->barcode->enabled)
               {
                   $head[$h][0] = DOL_URL_ROOT."/product/barcode.php?id=".$product->id;
                   $head[$h][1] = $langs->trans("BarCode");
                   $h++;
               }
            }
        }


        $head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Photos");
        $h++;

        if($product->type == 0)
        {
            if ($conf->stock->enabled)
            {
                $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
                $head[$h][1] = $langs->trans('Stock');
                $h++;
            }
        }

        if ($conf->fournisseur->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
            $head[$h][1] = $langs->trans("Suppliers");
            $h++;
        }

        $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Statistics");
        $hselected=$h;
        $h++;
	
    	//erics: pour créer des produits composés de x 'sous' produits
    	$head[$h][0] = DOL_URL_ROOT."/product/pack.php?id=".$product->id;
    	$head[$h][1] = $langs->trans('Packs');
    	$h++;
	
        $head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
        $head[$h][1] = $langs->trans('Referers');
        $h++;

        $head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
        $head[$h][1] = $langs->trans('Documents');
        $h++;

        dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);


        print '<table class="border" width="100%">';
        print '<tr>';
        print '<td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">'.$product->ref.'</td>';
        print '</tr>';
        print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td></tr>';

        print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="3">'.price($product->price).'</td>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
        if ($product->envente) print $langs->trans("OnSell");
        else print $langs->trans("NotOnSell");
        print '</td></tr>';

        print '<tr><td valign="top" width="25%">'.$langs->trans("Referers").'</td>';
        print '<td align="right" width="25%">'.$langs->trans("NbOfCustomers").'</td>';
        print '<td align="right" width="25%">'.$langs->trans("NbOfReferers").'</td>';
        print '<td align="right" width="25%">'.$langs->trans("TotalQuantity").'</td>';
        print '</tr>';

        // Propals
        if ($conf->propal->enabled)
        {
            $ret=$product->load_stats_propale($socid);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("propal");
            print '<tr><td>';
            print '<a href="propal.php?id='.$product->id.'">'.$langs->trans("Proposals").'</a>';
            print '</td><td align="right">';
            print $product->stats_propale['customers'];
            print '</td><td align="right">';
            print $product->stats_propale['nb'];
            print '</td><td align="right">';
            print $product->stats_propale['qty'];
            print '</td>';
            print '</tr>';
        }
        // Commandes
        if ($conf->commande->enabled)
        {
            $ret=$product->load_stats_commande($socid);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("orders");
            print '<tr><td>';
            print '<a href="commande.php?id='.$product->id.'">'.$langs->trans("Orders").'</a>';
            print '</td><td align="right">';
            print $product->stats_commande['customers'];
            print '</td><td align="right">';
            print $product->stats_commande['nb'];
            print '</td><td align="right">';
            print $product->stats_commande['qty'];
            print '</td>';
            print '</tr>';
        }
        // Contrats
        if ($conf->contrat->enabled)
        {
            $ret=$product->load_stats_contrat($socid);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("contracts");
            print '<tr><td>';
            print '<a href="contrat.php?id='.$product->id.'">'.$langs->trans("Contracts").'</a>';
            print '</td><td align="right">';
            print $product->stats_contrat['customers'];
            print '</td><td align="right">';
            print $product->stats_contrat['nb'];
            print '</td><td align="right">';
            print $product->stats_contrat['qty'];
            print '</td>';
            print '</tr>';
        }
        // Factures
        if ($conf->facture->enabled)
        {
            $ret=$product->load_stats_facture($socid);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("bills");
            print '<tr><td>';
            print '<a href="facture.php?id='.$product->id.'">'.$langs->trans("Bills").'</a>';
            print '</td><td align="right">';
            print $product->stats_facture['customers'];
            print '</td><td align="right">';
            print $product->stats_facture['nb'];
            print '</td><td align="right">';
            print $product->stats_facture['qty'];
            print '</td>';
            print '</tr>';
        }

        print '</table>';
        print '</div>';
        
        
        // Juste pour éviter bug IE qui réorganise mal div précédents si celui-ci absent
        print '<div class="tabsAction">';
        print '</div>';


        print '<table class="border" width="100%">';

        // Ligne de graph
        print '<tr class="liste_titre"><td width="50%" colspan="2" align="center">Nombre de propositions commerciales<br>sur les 12 derniers mois</td>';
        print '<td align="center" width="50%" colspan="2">-</td></tr>';
        // Place pour autre graphique
        print '<tr><td align="center" colspan="2">';
        $file=$product->id.'/'.$img_propal_name;
        print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=productstats&file='.urlencode($file).'" alt="Nombre de propales sur les 12 derniers mois">';
        print '</td><td align="center" colspan="2">&nbsp;</td></tr>';
        print '<tr>';
        if (file_exists($filenbpropal) && filemtime($filenbpropal) && ! $px->isGraphKo())
        {
            print '<td>'.$langs->trans("GeneratedOn",dolibarr_print_date(filemtime($filenbpropal),"%d %b %Y %H:%M:%S")).'</td>';
        }
        else
        {
            print '<td>'.($mesg?'<font class="error">'.$mesg.'</font>':$langs->trans("ChartNotGenerated")).'</td>';
        }
        print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">'.$langs->trans("ReCalculate").'</a>]</td>';
        if (file_exists($filenbpiece) && filemtime($filenbpiece) && ! $px->isGraphKo())
        {
            print '<td>'.$langs->trans("GeneratedOn",dolibarr_print_date(filemtime($filenbpiece),"%d %b %Y %H:%M:%S")).'</td>';
        }
        else
        {
            print '<td>'.($mesg?'<font class="error">'.$mesg.'</font>':$langs->trans("ChartNotGenerated")).'</td>';
        }
        print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">'.$langs->trans("ReCalculate").'</a>]</td></tr>';
        
        // Ligne de graph
        print '<tr class="liste_titre"><td width="50%" colspan="2" align="center">Nombre de ventes<br>sur les 12 derniers mois</td>';
        print '<td align="center" width="50%" colspan="2">Nombre de pièces vendues</td></tr>';
        print '<tr><td align="center" colspan="2">';
        $file=$product->id.'/vente12mois.png';
        print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=productstats&file='.urlencode($file).'" alt="Ventes sur les 12 derniers mois">';
        print '</td><td align="center" colspan="2">';
        $file=$product->id.'/vendu12mois.png';
        print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=productstats&file='.urlencode($file).'" alt="Nombre de pièces vendues sur les 12 derniers mois">';
        print '</td></tr>';
        print '<tr>';
        if (file_exists($filenbvente) && filemtime($filenbvente) && ! $px->isGraphKo())
        {
            print '<td>'.$langs->trans("GeneratedOn",dolibarr_print_date(filemtime($filenbvente),"%d %b %Y %H:%M:%S")).'</td>';
        }
        else
        {
            print '<td>'.($mesg?'<font class="error">'.$mesg.'</font>':$langs->trans("ChartNotGenerated")).'</td>';
        }
        print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">'.$langs->trans("ReCalculate").'</a>]</td>';
        if (file_exists($filenbpiece) && filemtime($filenbpiece) && ! $px->isGraphKo())
        {
            print '<td>'.$langs->trans("GeneratedOn",dolibarr_print_date(filemtime($filenbpiece),"%d %b %Y %H:%M:%S")).'</td>';
        }
        else
        {
            print '<td>'.($mesg?'<font class="error">'.$mesg.'</font>':$langs->trans("ChartNotGenerated")).'</td>';
        }
        print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">'.$langs->trans("ReCalculate").'</a>]</td></tr>';



        print '</table>';
        
    }
}
else
{
  dolibarr_print_error();
}


$db->close();

llxFooter('$Date$ - $Revision$');

?>
