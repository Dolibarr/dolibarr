<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/contrat/ligne.php
        \ingroup    contrat
        \brief      Fiche contrat
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");

$user->getrights('contrat');

if (!$user->rights->contrat->lire)
accessforbidden();

require("../project.class.php");
require("../propal.class.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0)
{
    $action = '';
    $socidp = $user->societe_id;
}


/*
 * Actions
 */
if ($_POST["action"] == 'confirm_active' && $_POST["confirm"] == 'yes' && $user->rights->contrat->activer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);

    $result = $contrat->active_line($user, $_GET["ligne"], $_GET["date"], $_GET["dateend"]);

    if ($result > 0)
    {
        Header("Location: fiche.php?id=".$contrat->id);
        exit;
    }
    else {
        $mesg=$contrat->error;   
    }
}

if ($_POST["action"] == 'confirm_close' && $_POST["confirm"] == 'yes' && $user->rights->contrat->activer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);

    $result = $contrat->close_line($user, $_GET["ligne"], $_GET["dateend"]);

    if ($result > 0)
    {
        Header("Location: fiche.php?id=".$contrat->id);
        exit;
    }
    else {
        $mesg=$contrat->error;   
    }
}



llxHeader('',$langs->trans("Contract"),"Contrat");

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

$id = $_GET["id"];
if ($id > 0)
{
    $contrat = New Contrat($db);
    if ( $contrat->fetch($id) > 0)
    {

        $author = new User($db);
        $author->id = $contrat->user_author_id;
        $author->fetch();

        $commercial_signature = new User($db);
        $commercial_signature->id = $contrat->commercial_signature_id;
        $commercial_signature->fetch();

        $commercial_suivi = new User($db);
        $commercial_suivi->id = $contrat->commercial_suivi_id;
        $commercial_suivi->fetch();

        $h = 0;
        $head[$h][0] = DOL_URL_ROOT.'/contrat/fiche.php?id='.$contrat->id;
        $head[$h][1] = $langs->trans("ContractCard");
        $h++;

        $head[$h][0] = DOL_URL_ROOT.'/contrat/info.php?id='.$contrat->id;
        $head[$h][1] = $langs->trans("Info");
        $hselected = $h;
        $h++;      
        
        $head[$h][0] = DOL_URL_ROOT.'/contrat/ligne.php?id='.$contrat->id."&ligne=".$_GET["ligne"];
        $head[$h][1] = $langs->trans($langs->trans("EditServiceLine"));
        $hselected = $h;

        dolibarr_fiche_head($head, $hselected, $langs->trans("Contract").': '.$contrat->id);



        /*
        *   Contrat
        */

        print '<table class="border" width="100%">';

        // Reference du contrat
        print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="3">';
        print $contrat->ref;
        print "</td></tr>";

        // Customer
        print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="3">';
        print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$contrat->societe->id.'">'.$contrat->societe->nom.'</a></b></td></tr>';

        // Status
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
        print $contrat->statuts[$contrat->statut];
        print "</td></tr>";

        // Date
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="3">'.strftime("%A %d %B %Y",$contrat->date_contrat)."</td></tr>\n";

        if ($conf->projet->enabled)
        {
            print '<tr><td>'.$langs->trans("Project").'</td><td colspan="3">';
            if ($contrat->projet_id > 0)
            {
                $projet = New Project($db);
                $projet->fetch($contrat->projet_id);
                print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$contrat->projet_id.'">'.$projet->title.'</a>';
            }
            else
            {
                print '<a href="fiche.php?id='.$id.'&amp;action=classer">Classer le contrat</a>';
            }
            print "</td></tr>";
        }

        print '<tr><td width="25%">'.$langs->trans("SalesRepresentativeFollowUp").'</td><td>'.$commercial_suivi->fullname.'</td>';
        print '<td width="25%">'.$langs->trans("SalesRepresentativeSignature").'</td><td>'.$commercial_signature->fullname.'</td></tr>';
        print "</table>";


        /*
         * Confirmation de la validation activation
         */
        if ($_GET["action"] == 'active' && $user->rights->contrat->activer)
        {
            print '<br />';
            $dateactstart = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
            $dateactend   = mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
            $html->form_confirm("ligne.php?id=".$contrat->id."&amp;ligne=".$_GET["ligne"]."&amp;date=".$dateactstart."&amp;dateend=".$dateactend,$langs->trans("ActivateService"),$langs->trans("ConfirmActivateService",strftime("%A %d %B %Y", $dateactstart)),"confirm_active");
        }

        /*
         * Confirmation de la validation fermeture
         */
        if ($_GET["action"] == 'close' && $user->rights->contrat->activer)
        {
            print '<br />';
            $dateactstart = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
            $dateactend   = mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
            $html->form_confirm("ligne.php?id=".$contrat->id."&amp;ligne=".$_GET["ligne"]."&amp;date=".$dateactstart."&amp;dateend=".$dateactend,$langs->trans("CloseService"),$langs->trans("ConfirmCloseService",strftime("%A %d %B %Y", $dateactstart)),"confirm_close");
        }


        /*
         * Lignes de contrats
         */
        print '<br><table class="noborder" width="100%">';

        $sql = "SELECT cd.statut, cd.label, cd.fk_product, cd.description, cd.price_ht, cd.qty, cd.rowid, cd.tva_tx, cd.remise_percent, cd.subprice,";
        $sql.= " ".$db->pdate("cd.date_ouverture_prevue")." as date_debut, ".$db->pdate("cd.date_ouverture")." as date_debut_reelle,";
        $sql.= " ".$db->pdate("cd.date_fin_validite")." as date_fin, ".$db->pdate("cd.date_cloture")." as date_fin_reelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
        $sql.= " WHERE cd.fk_contrat = ".$id;
        $sql.= " AND rowid = ".$_GET["ligne"];
        $sql.= " ORDER BY cd.rowid";

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0; $total = 0;

            if ($num)
            {
                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Service").'</td>';
                print '<td width="50" align="center">'.$langs->trans("VAT").'</td>';
                print '<td width="50" align="right">'.$langs->trans("PriceUHT").'</td>';
                print '<td width="30" align="center">'.$langs->trans("Qty").'</td>';
                print '<td width="50" align="right">'.$langs->trans("Discount").'</td>';
                print '<td width="16">&nbsp;</td>';
                print '<td width="16">&nbsp;</td>';
                print '<td width="30" align="center">'.$langs->trans("Status").'</td>';
                print "</tr>\n";
            }
            $var=true;
            while ($i < $num)
            {
                $objp = $db->fetch_object($result);

                $var=!$var;
                print "<tr $bc[$var] valign=\"top\">\n";

                // Libell
                if ($objp->fk_product > 0)
                {
                    print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowService"),"service").' '.$objp->label.'</a>';

                    if ($objp->description)
                    {
                        print '<br />'.stripslashes(nl2br($objp->description));
                    }

                    print '</td>';
                }
                else
                {
                    print '<td>'.stripslashes(nl2br($objp->description))."</td>\n";
                }
                print '<td align="center">'.$objp->tva_tx.'%</td>';

                print '<td align="right">'.price($objp->subprice)."</td>\n";

                print '<td align="center">'.$objp->qty.'</td>';

                if ($objp->remise_percent > 0)
                {
                    print '<td align="right">'.$objp->remise_percent."%</td>\n";
                }
                else
                {
                    print '<td>&nbsp;</td>';
                }

                print '<td>&nbsp;</td><td>&nbsp;</td>';

                // Statut
                print '<td align="center">'.img_statut($objp->statut,$langs->trans("ServiceStatusInitial")).'</td>';

                print "</tr>\n";

                if ($objp->date_debut) $dateactstart=$objp->date_debut;
                if ($objp->date_fin) $dateactend=$objp->date_fin;

                // Dates mise en service
                print '<tr '.$bc[$var].'>';
                print '<td colspan="7">';
                // Si pas encore activ
                if (! $objp->date_debut_reelle) {
                    print $langs->trans("DateStartPlanned").': ';
                    if ($objp->date_debut) print dolibarr_print_date($objp->date_debut);
                    else print $langs->trans("Unknown");
                }
                // Si activ
                if ($objp->date_debut_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    if ($objp->date_debut_reelle) print dolibarr_print_date($objp->date_debut_reelle);
                    else print $langs->trans("ContractStatusNotRunning");
                }

                print ' &nbsp;-&nbsp; ';

                // Si pas encore activ
                if (! $objp->date_debut_reelle) {
                    print $langs->trans("DateEndPlanned").': ';
                    if ($objp->date_fin) {
                        print dolibarr_print_date($objp->date_fin);
                    }
                    else print $langs->trans("Unknown");
                }
                // Si activ
                if ($objp->date_debut_reelle && ! $objp->date_fin_reelle) {
                    print $langs->trans("DateEndPlanned").': ';
                    if ($objp->date_fin) {
                        print dolibarr_print_date($objp->date_fin);
                        if ($objp->date_fin < time()) { print " ".img_warning($langs->trans("Late")); }
                    }
                    else print $langs->trans("Unknown");
                }
                if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                    print $langs->trans("DateEndReal").': ';
                    dolibarr_print_date($objp->date_fin_reelle);
                }
                print '</td>';
                print '<td>&nbsp;</td>';
                print '</tr>';


                $i++;
            }
            $db->free($result);
        }
        else
        {
            dolibarr_print_error($db);
        }

        print '</table><br>';
        print '</div>';

        if ($user->rights->contrat->activer && $contrat->statut == 1 && $objp->statut <> 4)
        {
            /**
             * Activer la ligne de contrat
             */
            $form = new Form($db);

            print '<form action="ligne.php?id='.$contrat->id.'&amp;ligne='.$_GET["ligne"].'&amp;action=active" method="post">';

            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("ActivateService").'</td></tr>';

            // Definie date debut et fin par defaut
            if ($_POST["remonth"]) $dateactstart = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
            elseif (! $dateactstart) $dateactstart = time();

            if ($_POST["endmonth"]) $dateactend = mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
            elseif (! $dateactend)
            {
                if ($objp->fk_product > 0)
                {
                    $product=new Product($db);
                    $product->fetch($objp->fk_product);
                    $dateactend = dolibarr_time_plus_duree (time(), $product->duration_value, $product->duration_unit);
                }
            }

            print '<tr '.$bc[$var].'><td>'.$langs->trans("DateServiceActivate").'</td><td>';
            print $form->select_date($dateactstart);
            print '</td>';

            print '<td>'.$langs->trans("DateEndPlanned").'</td><td>';
            print $form->select_date($dateactend,"end");
            print '</td>';
            
            print '<td align="center" rowspan="2" valign="middle"><input type="submit" class="button" value="'.$langs->trans("Activate").'"></td>';

            print '</tr>';

            print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td colspan="3"><input size="70" type="text" name="commentaire" value="'.$_POST["commentaire"].'"></td></tr>';

            print '</table>';

            print '</form>';
        }

        if ($user->rights->contrat->activer && $contrat->statut == 1 && $objp->statut == 4)
        {
            /**
             * Désactiver la ligne de contrat
             */
            $form = new Form($db);

            print '<form action="ligne.php?id='.$contrat->id.'&amp;ligne='.$_GET["ligne"].'&amp;action=close" method="post">';

            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("CloseService").'</td></tr>';

            // Definie date debut et fin par defaut
            if ($_POST["remonth"]) $dateactstart = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
            elseif (! $dateactstart) $dateactstart = time();

            if ($_POST["endmonth"]) $dateactend = mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
            elseif (! $dateactend)
            {
                if ($objp->fk_product > 0)
                {
                    $product=new Product($db);
                    $product->fetch($objp->fk_product);
                    $dateactend = dolibarr_time_plus_duree (time(), $product->duration_value, $product->duration_unit);
                }
            }

            print '<tr '.$bc[$var].'><td>'.$langs->trans("DateEndReal").'</td><td>';
            print $form->select_date($dateactend,"end");
            print '</td>';

            print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Close").'"></td></tr>';

            print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td colspan="3"><input size="70" type="text" name="commentaire" value="'.$_POST["commentaire"].'"></td></tr>';
            print '</table>';

            print '</form>';
        }
        
    }
    else
    {
        /* Contrat non trouvée */
        print "Contrat inexistant ou accés refusé";
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
