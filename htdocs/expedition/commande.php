<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/expedition/commande.php
        \ingroup    expedition
        \version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('commande');
$user->getrights('expedition');
if (!$user->rights->commande->lire)
accessforbidden();

require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");

// Sécurité accés client
if ($user->societe_id > 0)
{
    $action = '';
    $socidp = $user->societe_id;
}


/*
 * Actions
 */
if ($_POST["action"] == 'confirm_cloture' && $_POST["confirm"] == 'yes')
{
    $commande = new Commande($db);
    $commande->fetch($_GET["id"]);
    $result = $commande->cloture($user);
}


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

llxHeader('','Fiche commande','');

$html = new Form($db);


if ($_GET["id"] > 0)
{
    $commande = New Commande($db);
    if ( $commande->fetch($_GET["id"]) > 0)
    {
        $commande->livraison_array();

        $soc = new Societe($db);
        $soc->fetch($commande->soc_id);
        $author = new User($db);
        $author->id = $commande->user_author_id;
        $author->fetch();

        $h=0;

        if ($conf->commande->enabled && $user->rights->commande->lire)
        {
            $head[$h][0] = DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id;
            $head[$h][1] = $langs->trans("OrderCard");
            $h++;
        }

        if ($conf->expedition->enabled && $user->rights->expedition->lire)
        {
            $head[$h][0] = DOL_URL_ROOT.'/expedition/commande.php?id='.$commande->id;
            $head[$h][1] = $langs->trans("SendingCard");
            $hselected = $h;
            $h++;
        }

        if ($conf->compta->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT.'/compta/commande/fiche.php?id='.$commande->id;
            $head[$h][1] = $langs->trans("ComptaCard");
            $h++;
        }

        dolibarr_fiche_head($head, $hselected, $langs->trans("Order").": $commande->ref");

        /*
         * Confirmation de la validation
         *
         */
        if ($_GET["action"] == 'cloture')
        {
            $html->form_confirm("commande.php?id=".$_GET["id"],"Clôturer la commande","Etes-vous sûr de vouloir clôturer cette commande ?","confirm_cloture");
            print "<br />";
        }

        print '<table class="border" width="100%">';
        print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
        print '<td width="30%">';
        print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';

        print '<td width="50%">';
        print $commande->statuts[$commande->statut];
        print "</td></tr>";

        print '<tr><td width="20%">'.$langs->trans("Date").'</td>';
        print '<td width="30%">'.strftime("%A %d %B %Y",$commande->date)."</td>\n";

        print '<td width="50%">Source : ' . $commande->sources[$commande->source] ;
        if ($commande->source == 0)
        {
            /* Propale */
            $propal = new Propal($db);
            $propal->fetch($commande->propale_id);
            print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
        }
        print "</td></tr>";

        if ($commande->note)
        {
            print '<tr><td>'.$langs->trans("Note").'</td></tr>';
            print '<tr><td colspan="3">'.nl2br($commande->note)."</td></tr>";
        }

        print '</table>';


        /*
         * Lignes de commandes
         *
         */
        echo '<br><table class="liste" width="100%">';

        $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
        $sql.= " FROM ".MAIN_DB_PREFIX."commandedet as l ";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product = p.rowid";
        $sql.= " WHERE l.fk_commande = ".$commande->id;
        $sql.= " AND p.fk_product_type <> 1";
        $sql.= " ORDER BY l.rowid";

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0; $total = 0;

            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Products").'</td>';
            print '<td align="center">Quan. Commandée</td>';
            print '<td align="center">Quan. livrée</td>';
            print '<td align="center">Reste à livrer</td>';
            if ($conf->stock->enabled)
            {
                print '<td align="center">Stock</td>';
            }
            print "</tr>\n";

            $var=true;
            $reste_a_livrer = array();
            while ($i < $num)
            {
                $objp = $db->fetch_object();

                $var=!$var;
                print "<tr $bc[$var]>";
                if ($objp->fk_product > 0)
                {

                    $product = new Product($db);
                    $product->fetch($objp->fk_product);

                    print '<td>';
                    print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a></td>';
                }
                else
                {
                    print "<td>".stripslashes(nl2br($objp->description))."</td>\n";
                }

                print '<td align="center">'.$objp->qty.'</td>';

                print '<td align="center">';
                $quantite_livree = $commande->livraisons[$objp->fk_product];
                print $quantite_livree;
                print '</td>';

                $reste_a_livrer[$objp->fk_product] = $objp->qty - $quantite_livree;
                $reste_a_livrer_x = $objp->qty - $quantite_livree;
                $reste_a_livrer_total = $reste_a_livrer_total + $reste_a_livrer_x;
                print '<td align="center">';
                print $reste_a_livrer[$objp->fk_product];
                print '</td>';

                if ($conf->stock->enabled)
                {
                    if ($product->stock_reel < $reste_a_livrer_x)
                    {
                        print '<td align="center" class="alerte">'.$product->stock_reel.'</td>';
                    }
                    else
                    {
                        print '<td align="center">'.$product->stock_reel.'</td>';
                    }
                }
                print "</tr>";

                $i++;
                $var=!$var;
            }
            $db->free();
            print "</table>";
            
            if (! $num)
            {
                print $langs->trans("None").'<br>';
            }
            
        }
        else
        {
            dolibarr_print_error($db);
        }

        /*
        *
        *
        */
        if ($reste_a_livrer_total > 0 && $commande->brouillon == 0)
        {
            print '<form method="post" action="fiche.php">';
            print '<input type="hidden" name="action" value="create">';
            print '<input type="hidden" name="commande_id" value="'.$commande->id.'">';
            print '<br /><table class="border" width="100%">';

            $entrepot = new Entrepot($db);
            $langs->load("stocks");

            print '<tr><td colspan="2">'.$langs->trans("NewSending").'</td></tr>';

            print '<tr><td width="20%">'.$langs->trans("Warehouse").'</td>';
            print '<td>';
            $html->select_array("entrepot_id",$entrepot->list_array());
            if (sizeof($entrepot->list_array()) <= 0) 
            {
                print ' &nbsp; Aucun entrepôt définit, <a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?action=create">definissez en un</a>';
            }
            print '</td></tr>';
            /*
            print '<tr><td width="20%">Mode d\'expédition</td>';
            print '<td>';
            $html->select_array("entrepot_id",$entrepot->list_array());
            print '</td></tr>';
            */

            print "</table><br>";
            print "</form>\n";
        }


        /*
         * Alerte de seuil
         */
        if ($reste_a_livrer_total > 0 && $conf->stock->enabled)
        {
            print '<br><table class="liste" width="100%"><tr>';
            foreach ($reste_a_livrer as $key => $value)
            {
                if ($value > 0)
                {
                    $sql = "SELECT e.label as entrepot, ps.reel, p.label ";
                    $sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e, ".MAIN_DB_PREFIX."product_stock as ps, ".MAIN_DB_PREFIX."product as p";
                    $sql .= " WHERE e.rowid = ps.fk_entrepot AND ps.fk_product = p.rowid AND ps.fk_product = $key";
                    $sql .= " AND e.statut = 1 AND reel < $value";

                    $resql = $db->query($sql);
                    if ($resql)
                    {
                        $num = $db->num_rows($resql);
                        $i = 0;

                        $var=True;
                        while ($i < $num)
                        {
                            $obja = $db->fetch_object($resql);
                            print "<tr $bc[$var]>";
                            print '<td width="54%">'.$obja->label.'</td><td>'.$obja->entrepot.'</td><td><b>Stock : '.$obja->reel.'</b></td>';
                            print "</tr>\n";
                            $i++;
                        }
                        $db->free($resql);
                    }
                    else {
                        dolibarr_print_date($db);
                    }

                }
            }
            print "</table>";
        }

        print '</div>';

        /*
         * Boutons Actions
         */
        if ($user->societe_id == 0)
        {
            print '<div class="tabsAction">';

            if ($user->rights->expedition->creer && $reste_a_livrer_total > 0 && $commande->brouillon == 0)
            {
                print '<a class="tabAction" href="fiche.php?action=create&commande_id='.$commande->id.'">'.$langs->trans("CreateSending").'</a>';
            }

            if ($user->rights->commande->creer && $reste_a_livrer_total == 0 && $commande->statut < 3)
            {
                print '<a class="tabAction" href="commande.php?id='.$commande->id.'&amp;action=cloture">'.$langs->trans("Close").'</a>';
            }

            print "</div>";

        }


        /*
         * Déjà livr
         */
        $sql = "SELECT cd.fk_product, cd.description, cd.rowid, cd.qty as qty_commande, ed.qty as qty_livre, e.ref, e.rowid as expedition_id";
        $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd , ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."expedition as e";
        $sql .= " WHERE cd.fk_commande = ".$commande->id." AND cd.rowid = ed.fk_commande_ligne AND ed.fk_expedition = e.rowid";
        $sql .= " ORDER BY e.rowid DESC, cd.fk_product";
        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0; $total = 0;

            if ($num)
            {
                print '<br><table class="liste" width="100%"><tr>';
                print '<tr class="liste_titre">';
                print '<td width="54%">'.$langs->trans("Description").'</td>';
                print '<td align="center">Quan. livrée</td>';
                print '<td align="center">Expédition</td>';

                print "</tr>\n";

                $var=True;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($resql);
                    print "<TR $bc[$var]>";
                    if ($objp->fk_product > 0)
                    {
                        print '<td>';
                        print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a></td>';
                    }
                    else
                    {
                        print "<td>".stripslashes(nl2br($objp->description))."</TD>\n";
                    }
                    print '<td align="center">'.$objp->qty_livre.'</td>';
                    print '<td align="center"><a href="fiche.php?id='.$objp->expedition_id.'">'.$objp->ref.'</a></td>';
                    $i++;
                }

                print '</table>';
            }
        }
        else {
            dolibarr_print_date($db);
        }
    }
    else
    {
        /* Commande non trouvée */
        print "Commande inexistante";
    }
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
