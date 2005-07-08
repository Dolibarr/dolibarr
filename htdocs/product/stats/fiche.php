<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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
 *
 */

/**
        \file       htdocs/product/stats/fiche.php
        \ingroup    product
        \brief      Page des stats produits
        \version    $Revision$
*/

require("./pre.inc.php");
require("../../propal.class.php");

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

        $px = new BarGraph();
        $mesg = $px->isGraphKo();
        if (! $mesg)
        {
            $graph_data = $product->get_num_vente($socid);
            $px->draw($filenbvente, $graph_data);

            $px = new BarGraph();
            $graph_data = $product->get_nb_vente($socid);
            $px->draw($filenbpiece, $graph_data);

            $px = new BarGraph();
            $graph_data = $product->get_num_propal($socid);
            $px->draw($filenbpropal, $graph_data);

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

        $head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
        $head[$h][1] = $langs->trans('Bills');
        $h++;

        dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);


        print '<table class="border" width="100%">';
        print '<tr>';
        print '<td width="10%">'.$langs->trans("Ref").'</td><td colspan="2" width="40%">'.$product->ref.'</td>';
        print '</tr>';
        print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td></tr>';

        print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>'.price($product->price).'</td>';
        print '<td valign="top" rowspan="2">';
        // Propals
        if ($conf->propal->enabled)
        {
            $langs->load("propal");
            print '<a href="propal.php?id='.$product->id.'">'.$langs->trans("Proposals").'</a> : '.$product->count_propale($socid);
            print " (Proposé à ".$product->count_propale_client($socid)." clients)<br>";
        }
        // Commande
        if ($conf->commande->enabled)
        {
            $langs->load("orders");
            print '<a href="commande.php?id='.$product->id.'">'.$langs->trans("Orders").'</a> : '.$product->count_facture($socid)."<br>";
        }
        // Factures
        if ($conf->facture->enabled)
        {
            $langs->load("bills");
            print '<a href="facture.php?id='.$product->id.'">'.$langs->trans("Bills").'</a> : '.$product->count_facture($socid);
        }
        print '</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td>';
        if ($product->envente) print $langs->trans("OnSell");
        else print $langs->trans("NotOnSell");
        print '</td></tr>';

        print "</table>";

        print '<br><table class="border" width="100%">';
        print '<tr class="liste_titre"><td width="50%" colspan="2" align="center">Nombre de ventes<br>sur les 12 derniers mois</td>';
        print '<td align="center" width="50%" colspan="2">Nombre de pièces vendues</td></tr>';

        print '<tr><td align="center" colspan="2">';
        $file=$product->id.'/vente12mois.png';
        print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=productstats&file='.urlencode($file).'" alt="Ventes sur les 12 derniers mois">';

        print '</td><td align="center" colspan="2">';
        $file=$product->id.'/vendu12mois.png';
        print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=productstats&file='.urlencode($file).'" alt="Nombre de pièces vendues sur les 12 derniers mois">';

        print '</td></tr><tr>';
        if (file_exists($filenbvente) && filemtime($filenbvente))
        {
            print '<td>'.$langs->trans("GeneratedOn",dolibarr_print_date(filemtime($filenbvente),"%d %b %Y %H:%M:%S")).'</td>';
        }
        else
        {
            print '<td>'.$langs->trans("ChartNotGenerated").'</td>';
        }
        print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">'.$langs->trans("ReCalculate").'</a>]</td>';
        if (file_exists($filenbpiece) && filemtime($filenbpiece))
        {
            print '<td>'.$langs->trans("GeneratedOn",dolibarr_print_date(filemtime($filenbpiece),"%d %b %Y %H:%M:%S")).'</td>';
        }
        else
        {
            print '<td>'.$langs->trans("ChartNotGenerated").'</td>';
        }
        print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">'.$langs->trans("ReCalculate").'</a>]</td></tr>';
        print '<tr><td colspan="4">Statistiques effectuées sur les factures payées uniquement</td></tr>';

        print '<tr class="liste_titre"><td width="50%" colspan="2" align="center">Nombre de propositions commerciales<br>sur les 12 derniers mois</td>';
        print '<td align="center" width="50%" colspan="2">-</td></tr>';

        print '<tr><td align="center" colspan="2">';
        $file=$product->id.'/'.$img_propal_name;
        print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=productstats&file='.urlencode($file).'" alt="Nombre de propales sur les 12 derniers mois">';

        print '</td><td align="center" colspan="2">TODO AUTRE GRAPHIQUE';

        print '</td></tr><tr>';
        if (file_exists($filenbpropal) && filemtime($filenbpropal))
        {
            print '<td>'.$langs->trans("GeneratedOn",dolibarr_print_date(filemtime($filenbpropal),"%d %b %Y %H:%M:%S")).'</td>';
        }
        else
        {
            print '<td>'.$langs->trans("ChartNotGenerated").'</td>';
        }
        print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">'.$langs->trans("ReCalculate").'</a>]</td>';
        if (file_exists($filenbpiece) && filemtime($filenbpiece33))
        {
            print '<td>'.$langs->trans("GeneratedOn",dolibarr_print_date(filemtime($filenbpiece),"%d %b %Y %H:%M:%S")).'</td>';
        }
        else
        {
            print '<td>'.$langs->trans("ChartNotGenerated").'</td>';
        }
        print '<td align="center">[<a href="fiche.php?id='.$product->id.'&amp;action=recalcul">'.$langs->trans("ReCalculate").'</a>]</td></tr>';

        print '</table><br>';

    }
}
else
{
  dolibarr_print_error();
}


$db->close();

llxFooter('$Date$ - $Revision$');

?>
