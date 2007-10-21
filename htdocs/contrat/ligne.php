<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once(DOL_DOCUMENT_ROOT.'/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/project.class.php");
if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");

$user->getrights('contrat');

if (!$user->rights->contrat->lire)
accessforbidden();



/*
 * Sécurité accés client
 */
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
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




llxHeader('',$langs->trans("ContractCard"),"Contrat");

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

$lineid = $_GET["ligne"];
if ($lineid > 0)
{
	$line = new ContratLigne($db);
	$result=$line->fetch($lineid);

    $contrat = New Contrat($db);
    $result=$contrat->fetch($line->fk_contrat);
	
	if ($result > 0)
    {
        $soc = new Societe($db);
        $soc->fetch($contrat->socid);

        $author = new User($db);
        $author->id = $contrat->user_author_id;
        $author->fetch();

	    $head = contract_prepare_head($contrat);
		$h=sizeof($head);
		
        // On ajout onglet service
        $head[$h][0] = DOL_URL_ROOT.'/contrat/ligne.php?id='.$contrat->id."&ligne=".$line->id;
        $head[$h][1] = $langs->trans($langs->trans("EditServiceLine"));
        $hselected = $h;

        dolibarr_fiche_head($head, $hselected, $langs->trans("Contract"));


        /*
         * Confirmation de la validation activation
         */
        if ($_GET["action"] == 'active' && $user->rights->contrat->activer)
        {
            $dateactstart = dolibarr_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
            $dateactend   = dolibarr_mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
            $html->form_confirm("ligne.php?id=".$contrat->id."&amp;ligne=".$_GET["ligne"]."&amp;date=".$dateactstart."&amp;dateend=".$dateactend,$langs->trans("ActivateService"),$langs->trans("ConfirmActivateService",strftime("%A %d %B %Y", $dateactstart)),"confirm_active");
            print '<br />';
        }

        /*
         * Confirmation de la validation fermeture
         */
        if ($_GET["action"] == 'close' && $user->rights->contrat->activer)
        {
			$dateactstart = dolibarr_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
            $dateactend   = dolibarr_mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
            $html->form_confirm("ligne.php?id=".$contrat->id."&amp;ligne=".$_GET["ligne"]."&amp;date=".$dateactstart."&amp;dateend=".$dateactend,$langs->trans("CloseService"),$langs->trans("ConfirmCloseService",strftime("%A %d %B %Y", $dateactstart)),"confirm_close");
            print '<br />';
        }


        /*
         *   Contrat
         */
        print '<table class="border" width="100%">';

        // Reference du contrat
        print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
        print $contrat->ref;
        print "</td></tr>";

        // Customer
        print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="3">'.$soc->getNomUrl(1).'</td></tr>';

		// Ligne info remises tiers
        print '<tr><td>'.$langs->trans('Discount').'</td><td>';
		if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		$absolute_discount=$soc->getCurrentDiscount();
		print '. ';
		if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
		else print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print '.';
		print '</td></tr>';

        // Statut contrat
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
        print $contrat->getLibStatut(2);
        print "</td></tr>";

        // Date
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="3">'.dolibarr_print_date($contrat->date_contrat,"dayhour")."</td></tr>\n";

            // Factures associées
            /*
            TODO
            */

        // Projet
        if ($conf->projet->enabled)
        {
            $langs->load("projects");
            print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans("Project");
            print '</td>';
            if ($_GET["action"] != "classer") print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classer&amp;id='.$id.'&amp;ligne='.$_GET["ligne"].'">'.img_edit($langs->trans("SetProject")).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($_GET["action"] == "classer")
            {
                    $html->form_project($_SERVER["PHP_SELF"]."?id=$id&amp;ligne=".$_GET["ligne"],$contrat->fk_soc,$contrat->fk_projet,"projetid");
            }
            else
            {
                    $html->form_project($_SERVER["PHP_SELF"]."?id=$id&amp;ligne=".$_GET["ligne"],$contrat->fk_soc,$contrat->fk_projet,"none");
            }
            print "</td></tr>";
        }

        print "</table>";


        /*
         * Lignes de contrats
         */
        print '<br><table class="noborder" width="100%">';

        $sql = "SELECT cd.statut, cd.label, cd.fk_product, cd.description, cd.price_ht, cd.qty, cd.rowid, cd.tva_tx, cd.remise_percent, cd.subprice,";
        $sql.= " ".$db->pdate("cd.date_ouverture_prevue")." as date_debut, ".$db->pdate("cd.date_ouverture")." as date_debut_reelle,";
        $sql.= " ".$db->pdate("cd.date_fin_validite")." as date_fin, ".$db->pdate("cd.date_cloture")." as date_fin_reelle,";
        $sql.= " p.ref, p.label";
        $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
        $sql.= " WHERE cd.fk_contrat = ".$contrat->id;
        $sql.= " AND cd.rowid = ".$line->id;
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
                print '<td width="50" align="right">'.$langs->trans("ReductionShort").'</td>';
                print '<td width="16">&nbsp;</td>';
                print '<td width="30" align="center">'.$langs->trans("Status").'</td>';
                print "</tr>\n";
            }
			
		    $contratlignestatic = new ContratLigne($db);

            $var=true;
            while ($i < $num)
            {
                $objp = $db->fetch_object($result);

                $var=!$var;
                print "<tr $bc[$var] valign=\"top\">\n";

                    // Libelle
                    if ($objp->fk_product > 0)
                    {
                        print '<td>';
                        print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
                        print img_object($langs->trans("ShowService"),"service").' '.$objp->ref.'</a>';
                        print $objp->label?' - '.$objp->label:'';
                        if ($objp->description) print '<br />'.stripslashes(nl2br($objp->description));
                        print '</td>';
                    }
                    else
                    {
                        print "<td>".stripslashes(nl2br($objp->description))."</td>\n";
                    }

                // TVA
                print '<td align="center">'.vatrate($objp->tva_tx).'%</td>';

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

                print '<td>&nbsp;</td>';

                // Statut
                print '<td align="center">';
				print $contratlignestatic->LibStatut($objp->statut,3);
				print '</td>';

                print "</tr>\n";

                if ($objp->date_debut) $dateactstart=$objp->date_debut;
                if ($objp->date_fin) $dateactend=$objp->date_fin;

                    // Dates de en service prévues et effectives
                    
                    print '<tr '.$bc[$var].'>';
                    print '<td colspan="7">';

                    // Date prévues
                    print $langs->trans("DateStartPlanned").': ';
                    if ($objp->date_debut) {
                        print dolibarr_print_date($objp->date_debut);
                        // Warning si date prevu passée et pas en service
                        if ($objp->statut == 0 && $objp->date_debut < time() - $conf->contrat->warning_delay) { print " ".img_warning($langs->trans("Late")); }
                    }
                    else print $langs->trans("Unknown");
                    print ' &nbsp;-&nbsp; ';
                    print $langs->trans("DateEndPlanned").': ';
                    if ($objp->date_fin) {
                        print dolibarr_print_date($objp->date_fin);
                        if ($objp->statut == 4 && $objp->date_fin < time() - $conf->contrat->warning_delay) { print " ".img_warning($langs->trans("Late")); }
                    }
                    else print $langs->trans("Unknown");

                    print '<br>';

                    // Si pas encore activé
                    if (! $objp->date_debut_reelle) {
                        print $langs->trans("DateStartReal").': ';
                        if ($objp->date_debut_reelle) print dolibarr_print_date($objp->date_debut_reelle);
                        else print $langs->trans("ContractStatusNotRunning");
                    }
                    // Si activé et en cours
                    if ($objp->date_debut_reelle && ! $objp->date_fin_reelle) {
                        print $langs->trans("DateStartReal").': ';
                        print dolibarr_print_date($objp->date_debut_reelle);
                    }
                    // Si désactivé
                    if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                        print $langs->trans("DateStartReal").': ';
                        print dolibarr_print_date($objp->date_debut_reelle);
                        print ' &nbsp;-&nbsp; ';
                        print $langs->trans("DateEndReal").': ';
                        print dolibarr_print_date($objp->date_fin_reelle);
                    }
                    print '</td>';
                    print '</tr>';  


                $i++;
            }
            $db->free($result);
        }
        else
        {
            dolibarr_print_error($db);
        }

        print '</table>';
        print '</div>';

        if ($user->rights->contrat->activer && $contrat->statut == 1 && $objp->statut <> 4)
        {
            /**
             * Activer la ligne de contrat
             */
            $form = new Form($db);

            print '<form name="active" action="ligne.php?id='.$contrat->id.'&amp;ligne='.$_GET["ligne"].'&amp;action=active" method="post">';

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
            print $form->select_date($dateactstart,'','','','',"active");
            print '</td>';

            print '<td>'.$langs->trans("DateEndPlanned").'</td><td>';
            print $form->select_date($dateactend,"end",'','','',"active");
            print '</td>';
            
            print '<td align="center" rowspan="2" valign="middle"><input type="submit" class="button" value="'.$langs->trans("Activate").'"></td>';

            print '</tr>';

            print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td colspan="3"><input size="80" type="text" name="commentaire" value="'.$_POST["commentaire"].'"></td></tr>';

            print '</table>';

            print '</form>';
        }

        if ($user->rights->contrat->activer && $contrat->statut == 1 && $objp->statut == 4)
        {
            /**
             * Désactiver la ligne de contrat
             */
            $form = new Form($db);

            print '<form name="close" action="ligne.php?id='.$contrat->id.'&amp;ligne='.$line->id.'&amp;action=close" method="post">';

            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("CloseService").'</td></tr>';

            // Definie date debut et fin par defaut
            if ($_POST["remonth"]) $dateactstart = dolibarr_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
            elseif (! $dateactstart) $dateactstart = time();

            if ($_POST["endmonth"]) $dateactend = dolibarr_mktime(12, 0 , 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
            elseif (! $dateactend)
            {
                if ($objp->fk_product > 0)
                {
                    $product=new Product($db);
                    $product->fetch($objp->fk_product);
                    $dateactend = dolibarr_time_plus_duree (time(), $product->duration_value, $product->duration_unit);
                }
            }
            $now=mktime();
			if ($dateactend > $now) $dateactend=$now;

			
            print '<tr '.$bc[$var].'><td>'.$langs->trans("DateEndReal").'</td><td>';
            print $form->select_date($dateactend,"end",'','','',"close");
            print '</td>';

            print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Close").'"></td></tr>';

            print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td colspan="3"><input size="70" type="text" class="flat" name="commentaire" value="'.$_POST["commentaire"].'"></td></tr>';
            print '</table>';

            print '</form>';
        }
        
    }
    else
    {
            // Contrat non trouvé
        print "Contrat inexistant ou accés refusé";
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
