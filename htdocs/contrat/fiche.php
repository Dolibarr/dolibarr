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
        \file       htdocs/contrat/fiche.php
        \ingroup    contrat
        \brief      Fiche contrat
        \version    $Revision$
*/

require("./pre.inc.php");
if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/project.class.php");
if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");

$user->getrights('contrat');

if (! $user->rights->contrat->lire)
accessforbidden();

$startyear=isset($_POST["date_startyear"])&&$_POST["date_startyear"]?$_POST["date_startyear"]:0;
$endyear=isset($_POST["date_endyear"])&&$_POST["date_endyear"]?$_POST["date_endyear"]:0;
$date_start_update=mktime(12, 0 , 0, $_POST["date_start_updatemonth"], $_POST["date_start_updateday"], $startyear);
$date_end_update=mktime(12, 0 , 0, $_POST["date_end_updatemonth"], $_POST["date_end_updateday"], $startyear);
$date_start=mktime(12, 0 , 0, $_POST["date_startmonth"], $_POST["date_startday"], $endyear);
$date_end=mktime(12, 0 , 0, $_POST["date_endmonth"], $_POST["date_endday"], $endyear);

// Sécurité accés client
if ($user->societe_id > 0)
{
    $action = '';
    $socidp = $user->societe_id;
}


/*
 * Actions
 */
if ($_POST["action"] == 'add')
{
    $datecontrat = mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

    $contrat = new Contrat($db);

    $contrat->soc_id         = $_POST["soc_id"];
    $contrat->date_contrat   = $datecontrat;
    $contrat->commercial_suivi_id      = $_POST["commercial_suivi_id"];
    $contrat->commercial_signature_id  = $_POST["commercial_signature_id"];
    $contrat->note           = $_POST["note"];
    $contrat->projetid       = $_POST["projetid"];
    $contrat->remise_percent = $_POST["remise_percent"];

    /*
    $contrat->add_product($_POST["idprod1"],$_POST["qty1"],$_POST["remise_percent1"]);
    $contrat->add_product($_POST["idprod2"],$_POST["qty2"],$_POST["remise_percent2"]);
    $contrat->add_product($_POST["idprod3"],$_POST["qty3"],$_POST["remise_percent3"]);
    $contrat->add_product($_POST["idprod4"],$_POST["qty4"],$_POST["remise_percent4"]);
    */
    $result = $contrat->create($user,$langs,$conf);
    if ($result >= 0)
    {
        Header("Location: fiche.php?id=".$contrat->id);
        exit;
    }
    else {
        $mesg='<div class="error">'.$contrat->error.'</div>';
    }

    $_GET["socid"]=$_POST["soc_id"];
    $_GET["action"]='create';
    $action = '';
}

if ($_POST["action"] == 'classin')
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);
    $contrat->classin($_POST["projetid"]);
}

if ($_POST["action"] == 'addligne' && $user->rights->contrat->creer)
{
    if ($_POST["pqty"] && (($_POST["pu"] && $_POST["desc"]) || $_POST["p_idprod"]))
    {
        $result = 0;
        $contrat = new Contrat($db);
        $result=$contrat->fetch($_GET["id"]);
        if (($_POST["p_idprod"] > 0 && $_POST["mode"]=='predefined') || ($_POST["mode"]=='libre'))
        {
            //print $_POST["desc"]." - ".$_POST["pu"]." - ".$_POST["pqty"]." - ".$_POST["tva_tx"]." - ".$_POST["p_idprod"]." - ".$_POST["premise"]; exit;
            $result = $contrat->addline(
                $_POST["desc"],
                $_POST["pu"],
                $_POST["pqty"],
                $_POST["tva_tx"],
                $_POST["p_idprod"],
                $_POST["premise"],
                $date_start,
                $date_end
                );
        }
    
        if ($result >= 0)
        {
            Header("Location: fiche.php?id=".$contrat->id);
            exit;
        }
        else
        {
            $mesg='<div class="error">'.$contrat->error.'</div>';
        }
    }
}

if ($_POST["action"] == 'updateligne' && $user->rights->contrat->creer)
{
    $contrat = new Contrat($db,"",$_GET["id"]);
    if ($contrat->fetch($_GET["id"]))
    {
        $result = $contrat->updateline($_POST["elrowid"],
            $_POST["eldesc"],
            $_POST["elprice"],
            $_POST["elqty"],
            $_POST["elremise_percent"],
            $date_start_update,
            $date_end_update,
            $_POST["eltva_tx"]
        );
        if ($result > 0)
        {
            $db->commit();
        }
        else
        {
            dolibarr_print_error($db,"result=$result");
            $db->rollback();
        }        
    }
    else
    {
        dolibarr_print_error($db);
    }
}

if ($_GET["action"] == 'deleteline' && $user->rights->contrat->creer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);
    $result = $contrat->delete_line($_GET["lineid"]);

    if ($result == 0)
    {
        Header("Location: fiche.php?id=".$contrat->id);
    }
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == 'yes' && $user->rights->contrat->creer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);
    $soc = new Societe($db);
    $soc->fetch($contrat->soc_id);
    $result = $contrat->validate($user);
}

if ($_POST["action"] == 'confirm_close' && $_POST["confirm"] == 'yes' && $user->rights->contrat->creer)
{
    $contrat = new Contrat($db);
    $contrat->fetch($_GET["id"]);
    $result = $contrat->cloture($user);
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
    if ($user->rights->contrat->supprimer )
    {
        $contrat = new Contrat($db);
        $contrat->id = $_GET["id"];
        $contrat->delete($user,$lang,$conf);
        Header("Location: index.php");
        return;
    }
}




llxHeader('',$langs->trans("ContractCard"),"Contrat");

$html = new Form($db);


/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($_GET["action"] == 'create')
{
    dolibarr_fiche_head($head, $a, $langs->trans("AddContract"));

    if ($mesg) print $mesg;

    $new_contrat = new Contrat($db);

    $sql = "SELECT s.nom, s.prefix_comm, s.idp ";
    $sql .= "FROM ".MAIN_DB_PREFIX."societe as s ";
    $sql .= "WHERE s.idp = ".$_GET["socid"];

    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        if ($num)
        {
            $obj = $db->fetch_object($resql);

            $soc = new Societe($db);
            $soc->fetch($obj->idp);

            print '<form action="fiche.php" method="post">';
            print '<input type="hidden" name="action" value="add">';
            print '<input type="hidden" name="soc_id" value="'.$soc->id.'">'."\n";
            print '<input type="hidden" name="remise_percent" value="0">';

            print '<table class="border" width="100%">';

            print '<tr><td>'.$langs->trans("Customer").':</td><td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$obj->nom.'</a></td></tr>';

            // Commercial suivi
            print '<tr><td width="20%" nowrap>'.$langs->trans("SalesRepresentativeFollowUp").'</td><td>';
            print '<select name="commercial_suivi_id">';
            print '<option value="-1">&nbsp;</option>';

            $sql = "SELECT rowid, name, firstname FROM ".MAIN_DB_PREFIX."user";
            $sql.= " ORDER BY name ";
            $resql=$db->query( $sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                if ( $num > 0 )
                {
                    $i = 0;
                    while ($i < $num)
                    {
                        $row = $db->fetch_row($resql);
                        print '<option value="'.$row[0].'">'.$row[1] . " " . $row[2].'</option>';
                        $i++;
                    }
                }
                $db->free($resql);

            }
            print '</select></td></tr>';

            // Commercial signature
            print '<tr><td width="20%" nowrap>'.$langs->trans("SalesRepresentativeSignature").'</td><td>';
            print '<select name="commercial_signature_id">';
            print '<option value="-1">&nbsp;</option>';
            $sql = "SELECT rowid, name, firstname FROM ".MAIN_DB_PREFIX."user";
            $sql.= " ORDER BY name";
            $resql=$db->query( $sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                if ( $num > 0 )
                {
                    $i = 0;
                    while ($i < $num)
                    {
                        $row = $db->fetch_row($resql);
                        print '<option value="'.$row[0].'">'.$row[1] . " " . $row[2].'</option>';
                        $i++;
                    }
                }
                $db->free($resql);

            }
            print '</select></td></tr>';

            print '<tr><td>'.$langs->trans("Date").' :</td><td>';
            $html->select_date();
            print "</td></tr>";

            if ($conf->projet->enabled)
            {
                print '<tr><td>'.$langs->trans("Project").' :</td><td>';
                $proj = new Project($db);
                $html->select_array("projetid",$proj->liste_array($soc->id),0,1);
                print "</td></tr>";
            }

            /*
            *
            * Liste des elements
            *
            *
            print '<tr><td colspan="3">'.$langs->trans("Services").'/'.$langs->trans("Products").'</td></tr>';
            print '<tr><td colspan="3">';

            $sql = "SELECT p.rowid,p.label,p.ref,p.price FROM ".MAIN_DB_PREFIX."product as p ";
            $sql .= " WHERE envente = 1";
            $sql .= " ORDER BY p.nbvente DESC LIMIT 20";
            if ( $db->query($sql) )
            {
            $opt = "<option value=\"0\" selected></option>";
            if ($result)
            {
            $num = $db->num_rows();	$i = 0;
            while ($i < $num)
            {
            $objp = $db->fetch_object();
            $opt .= "<option value=\"$objp->rowid\">[$objp->ref] $objp->label : $objp->price</option>\n";
            $i++;
            }
            }
            $db->free();
            }
            else
            {
            print $db->error();
            }

            print '<table class="noborder" cellspacing="0">';
            print '<tr><td>20 Produits les plus vendus</td><td>Quan.</td><td>Remise</td></tr>';
            for ($i = 1 ; $i < 5 ; $i++)
            {
            print '<tr><td><select name="idprod'.$i.'">'.$opt.'</select></td>';
            print '<td><input type="text" size="3" name="qty'.$i.'" value="1"></td>';
            print '<td><input type="text" size="4" name="remise_percent'.$i.'" value="0">%</td></tr>';
            }

            print '</table>';
            print '</td></tr>';
            */
            print '<tr><td>'.$langs->trans("Comment").'</td><td valign="top">';
            print '<textarea name="note" wrap="soft" cols="60" rows="3"></textarea></td></tr>';

            print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Create").'"></td></tr>';
            print "</form>\n";
            print "</table>\n";

            if ($propalid)
            {
                /*
                 * Produits
                 */
                print '<br>';
                print_titre($langs->trans("Products"));

                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre"><td>'.$langs->trans("Ref").'</td><td>'.$langs->trans("Product").'</td>';
                print '<td align="right">'.$langs->trans("Price").'</td><td align="center">'.$langs->trans("Discount").'</td><td align="center">'.$langs->trans("Qty").'</td></tr>';

                $sql = "SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid, pt.remise_percent";
                $sql .= " FROM ".MAIN_DB_PREFIX."propaldet as pt, ".MAIN_DB_PREFIX."product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = $propalid";
                $sql .= " ORDER BY pt.rowid ASC";
                $result = $db->query($sql);
                if ($result)
                {
                    $num = $db->num_rows($result);
                    $i = 0;
                    $var=True;
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($result);
                        $var=!$var;
                        print "<tr $bc[$var]><td>[$objp->ref]</td>\n";
                        print '<td>'.$objp->product.'</td>';
                        print "<td align=\"right\">".price($objp->price)."</td>";
                        print '<td align="center">'.$objp->remise_percent.'%</td>';
                        print "<td align=\"center\">".$objp->qty."</td></tr>\n";
                        $i++;
                    }
                }
                $sql = "SELECT pt.rowid, pt.description as product, pt.price, pt.qty, pt.remise_percent";
                $sql.= " FROM ".MAIN_DB_PREFIX."propaldet as pt";
                $sql.= " WHERE  pt.fk_propal = $propalid AND pt.fk_product = 0";
                $sql.= " ORDER BY pt.rowid ASC";
                $result=$db->query($sql);
                if ($result)
                {
                    $num = $db->num_rows($result);
                    $i = 0;
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($result);
                        $var=!$var;
                        print "<tr $bc[$var]><td>&nbsp;</td>\n";
                        print '<td>'.$objp->product.'</td>';
                        print '<td align="right">'.price($objp->price).'</td>';
                        print '<td align="center">'.$objp->remise_percent.'%</td>';
                        print "<td align=\"center\">".$objp->qty."</td></tr>\n";
                        $i++;
                    }
                }
                else
                {
                    dolibarr_print_error($db);
                }

                print '</table>';
            }
        }
    }
    else
    {
        dolibarr_print_error($db);
    }
    
    print '</div>';
}
else
/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
{
    $id = $_GET["id"];
    if ($id > 0)
    {
        $contrat = New Contrat($db);
        if ( $contrat->fetch($id) > 0)
        {

            if ($mesg) print $mesg;

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
            $hselected = $h;
            $h++;

            $head[$h][0] = DOL_URL_ROOT.'/contrat/info.php?id='.$contrat->id;
            $head[$h][1] = $langs->trans("Info");
            $h++;      

            dolibarr_fiche_head($head, $hselected, $langs->trans("Contract").': '.$contrat->id);


            /*
             * Confirmation de la suppression du contrat
             */
            if ($_GET["action"] == 'delete')
            {
                $html->form_confirm("fiche.php?id=$id",$langs->trans("DeleteAContract"),$langs->trans("ConfirmDeleteAContract"),"confirm_delete");
                print '<br>';
            }

            /*
             * Confirmation de la validation
             */
            if ($_GET["action"] == 'valid')
            {
                //$numfa = contrat_get_num($soc);
                $html->form_confirm("fiche.php?id=$id",$langs->trans("ValidateAContract"),$langs->trans("ConfirmValidateContract"),"confirm_valid");
                print '<br>';
            }

            /*
             * Confirmation de la fermeture
             */
            if ($_GET["action"] == 'close')
            {
                $html->form_confirm("fiche.php?id=$id",$langs->trans("CloseAContract"),$langs->trans("ConfirmCloseContract"),"confirm_close");
                print '<br>';
            }

            /*
             *   Contrat
             */
            if ($contrat->brouillon == 1 && $user->rights->contrat->creer)
            {
                print '<form action="fiche.php?id='.$id.'" method="post">';
                print '<input type="hidden" name="action" value="setremise">';
            }

            print '<table class="border" width="100%">';

            // Reference du contrat
            print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="3">';
            print $contrat->ref;
            print "</td></tr>";

            // Customer
            print "<tr><td>".$langs->trans("Customer")."</td>";
            print '<td colspan="3">';
            print '<b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$contrat->societe->id.'">'.$contrat->societe->nom.'</a></b></td></tr>';

            // Statut contrat
            print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
            print $contrat->statuts[$contrat->statut];
            print "</td></tr>";

            // Date
            print '<tr><td>'.$langs->trans("Date").'</td>';
            print '<td colspan="3">'.dolibarr_print_date($contrat->date_contrat,"%A %d %B %Y")."</td></tr>\n";

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
                if ($_GET["action"] != "classer") print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classer&amp;id='.$id.'">'.img_edit($langs->trans("SetProject")).'</a></td>';
                print '</tr></table>';
                print '</td><td colspan="3">';
                if ($_GET["action"] == "classer")
                {
                    $html->form_project("fiche.php?id=$id",$contrat->fk_soc,$contrat->fk_projet,"projetid");
                }
                else
                {
                    $html->form_project("fiche.php?id=$id",$contrat->fk_soc,$contrat->fk_projet,"none");
                }
                print "</td></tr>";
            }

            print '<tr><td width="25%">'.$langs->trans("SalesRepresentativeFollowUp").'</td><td>'.$commercial_suivi->fullname.'</td>';
            print '<td width="25%">'.$langs->trans("SalesRepresentativeSignature").'</td><td>'.$commercial_signature->fullname.'</td></tr>';
            print "</table>";

            if ($contrat->brouillon == 1 && $user->rights->contrat->creer)
            {
                print '</form>';
            }

            /*
             * Lignes de contrats
             */
            echo '<br><table class="noborder" width="100%">';

            $sql = "SELECT cd.statut, cd.label, cd.fk_product, cd.description, cd.price_ht, cd.qty, cd.rowid, cd.tva_tx, cd.remise_percent, cd.subprice,";
            $sql.= " ".$db->pdate("cd.date_ouverture_prevue")." as date_debut, ".$db->pdate("cd.date_ouverture")." as date_debut_reelle,";
            $sql.= " ".$db->pdate("cd.date_fin_validite")." as date_fin, ".$db->pdate("cd.date_cloture")." as date_fin_reelle";
            $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
            $sql.= " WHERE cd.fk_contrat = ".$id;
            $sql.= " ORDER BY cd .rowid";

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
                    print '<td width="30">&nbsp;</td>';
                    print '<td width="30" align="center">'.$langs->trans("Status").'</td>';
                    print "</tr>\n";
                }
                $var=true;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);

                    $var=!$var;

                    if ($_GET["action"] != 'editline' || $_GET["rowid"] != $objp->rowid)
                    {

                        print '<tr '.$bc[$var].' valign="top">';
                        if ($objp->fk_product > 0)
                        {
                            print '<td>';
                            print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
                            print img_object($langs->trans("ShowService"),"service").' '.$objp->label.'</a>';
                            if ($objp->description) print '<br />'.stripslashes(nl2br($objp->description));
                            print '</td>';
                        }
                        else
                        {
                            print "<td>".stripslashes(nl2br($objp->description))."</td>\n";
                        }
                        // TVA
                        print '<td align="center">'.$objp->tva_tx.'%</td>';
                        // Prix
                        print '<td align="right">'.price($objp->subprice)."</td>\n";
                        // Quantité
                        print '<td align="center">'.$objp->qty.'</td>';
                        // Remise
                        if ($objp->remise_percent > 0)
                        {
                            print '<td align="right">'.$objp->remise_percent."%</td>\n";
                        }
                        else
                        {
                            print '<td>&nbsp;</td>';
                        }
                        // Icon update et delete (statut contrat 0=brouillon,1=validé,2=fermé)
                        print '<td align="center" nowrap>';
                        if ($contrat->statut != 2  && $user->rights->contrat->creer)
                        {
                            print '<a href="fiche.php?id='.$id.'&amp;action=editline&amp;rowid='.$objp->rowid.'">';
                            print img_edit();
                            print '</a>';
                        }
                        else {
                            print '&nbsp;';
                        }
                        if ($contrat->statut == 0  && $user->rights->contrat->creer)
                        {
                            print '&nbsp;';
                            print '<a href="fiche.php?id='.$id.'&amp;action=deleteline&amp;lineid='.$objp->rowid.'">';
                            print img_delete();
                            print '</a>';
                        }
                        print '</td>';
            
                        // Statut
                        print '<td align="center">';
                        if ($contrat->statut > 0) print '<a href="'.DOL_URL_ROOT.'/contrat/ligne.php?id='.$contrat->id.'&amp;ligne='.$objp->rowid.'">';;
                        print img_statut($objp->statut);
                        if ($contrat->statut > 0) print '</a>';
                        print '</td>';

                        print "</tr>\n";
    
                        // Dates mise en service
                        print '<tr '.$bc[$var].'>';
                        print '<td colspan="7">';
                        // Si pas encore activé
                        if (! $objp->date_debut_reelle) {
                            print $langs->trans("DateStartPlanned").': ';
                            if ($objp->date_debut) print dolibarr_print_date($objp->date_debut);
                            else print $langs->trans("Unknown");
                        }
                        // Si activé
                        if ($objp->date_debut_reelle) {
                            print $langs->trans("DateStartReal").': ';
                            if ($objp->date_debut_reelle) print dolibarr_print_date($objp->date_debut_reelle);
                            else print $langs->trans("ContractStatusNotRunning");
                        }

                        print ' &nbsp;-&nbsp; ';
    
                        // Si pas encore activé
                        if (! $objp->date_debut_reelle) {
                            print $langs->trans("DateEndPlanned").': ';
                            if ($objp->date_fin) {
                                print dolibarr_print_date($objp->date_fin);
                            }
                            else print $langs->trans("Unknown");
                        }
                        // Si activé
                        if ($objp->date_debut_reelle && ! $objp->date_fin_reelle) {
                            print $langs->trans("DateEndPlanned").': ';
                            if ($objp->date_fin) {
                                print dolibarr_print_date($objp->date_fin);
                                if ($objp->date_fin < time()) { print " ".img_warning($langs->trans("Late")); }
                            }
                            else print $langs->trans("Unknown");
                        }
                        // Si désactivé
                        if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                            print $langs->trans("DateEndReal").': ';
                            print dolibarr_print_date($objp->date_fin_reelle);
                        }
                        print '</td>';
                        print '</tr>';                    
                    }

                    // Ligne en mode update
                    else
                    {
                        print "<form action=\"fiche.php?id=$id\" method=\"post\">";
                        print '<input type="hidden" name="action" value="updateligne">';
                        print '<input type="hidden" name="elrowid" value="'.$_GET["rowid"].'">';
                        // Ligne carac
                        print "<tr $bc[$var]>";
                        print '<td>';
                        print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
                        if ($objp->label)
                        {
                            print img_object($langs->trans("ShowService"),"service").' '.$objp->label.'</a><br>';
                        }
                        print '<textarea name="eldesc" cols="60" rows="1">'.$objp->description.'</textarea></td>';
                        print '<td align="right">';
                        print $html->select_tva("eltva_tx",$objp->tva_tx);
                        print '</td>';
                        print '<td align="right"><input size="6" type="text" name="elprice" value="'.price($objp->subprice).'"></td>';
                        print '<td align="center"><input size="3" type="text" name="elqty" value="'.$objp->qty.'"></td>';
                        print '<td align="right"><input size="1" type="text" name="elremise_percent" value="'.$objp->remise_percent.'">%</td>';
                        print '<td align="center" colspan="3"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';
                        // Ligne dates prévues
                        print "<tr $bc[$var]>";
                        print '<td colspan="8">';
                        print $langs->trans("DateStartPlanned").' ';
                        $html->select_date($objp->date_debut,"date_start_update",0,0,($objp->date_debut>0?0:1));
                        print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
                        $html->select_date($objp->date_fin,"date_end_update",0,0,($objp->date_fin>0?0:1));
                        print '</td>';
                        print '</tr>';

                        print "</form>\n";
                    }
                    $i++;
                }
                $db->free($result);
            }
            else
            {
                dolibarr_print_error($db);
            }


            /*
             * Ajouter une ligne produit/service
             */
            if ($user->rights->contrat->creer && $contrat->statut == 0)
            {
                print "<tr class=\"liste_titre\">";
                print '<td>'.$langs->trans("Service").'</td>';
                print '<td align="center">'.$langs->trans("VAT").'</td>';
                print '<td align="right">'.$langs->trans("PriceUHT").'</td>';
                print '<td align="center">'.$langs->trans("Qty").'</td>';
                print '<td align="right">'.$langs->trans("Discount").'</td>';
                print '<td>&nbsp;</td>';
                print '<td>&nbsp;</td>';
                print "</tr>\n";

                $var=false;

                print '<form action="fiche.php?id='.$id.'" method="post">';
                print '<input type="hidden" name="action" value="addligne">';
                print '<input type="hidden" name="mode" value="predefined">';
                print '<input type="hidden" name="id" value="'.$id.'">';

                print "<tr $bc[$var]>";
                print '<td colspan="3">';
                $html->select_produits('','p_idprod','',0);
                print '</td>';

                print '<td align="center"><input type="text" class="flat" size="2" name="pqty" value="1"></td>';
                print '<td align="right" nowrap><input type="text" class="flat" size="1" name="premise" value="0">%</td>';
                print '<td align="center" colspan="3" rowspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';

                print "<tr $bc[$var]>";
                print '<td colspan="8">';
                print $langs->trans("DateStartPlanned").' ';
                $html->select_date('',"date_start",0,0,1);
                print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
                $html->select_date('',"date_end",0,0,1);
                print '</td>';
                print '</tr>';
                
                print '</tr>';
                
                print "</form>";

                $var=!$var;
                
                print '<form action="fiche.php?id='.$id.'" method="post">';
                print '<input type="hidden" name="action" value="addligne">';
                print '<input type="hidden" name="mode" value="libre">';
                print '<input type="hidden" name="id" value="'.$id.'">';

                print "<tr $bc[$var]>";
                print '<td><textarea name="desc" cols="50" rows="1"></textarea></td>';

                print '<td>';
                $html->select_tva("tva_tx",$conf->defaulttx);
                print '</td>';
                print '<td align="right"><input type="text" class="flat" size="4" name="pu" value=""></td>';
                print '<td align="center"><input type="text" class="flat" size="2" name="pqty" value="1"></td>';
                print '<td align="right" nowrap><input type="text" class="flat" size="1" name="premise" value="0">%</td>';
                print '<td align="center" colspan="2" rowspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';

                print "</tr>\n";

                print '</tr>';

                print "<tr $bc[$var]>";
                print '<td colspan="8">';
                print $langs->trans("DateStartPlanned").' ';
                $html->select_date('',"date_start",0,0,1);
                print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
                $html->select_date('',"date_end",0,0,1);
                print '</td>';
                print '</tr>';
                
                print "</form>";
            }
            print "</table>";
            /*
             * Fin Ajout ligne
             */

            print '</div>';

            /*************************************************************
             * Boutons Actions
             *************************************************************/

            if ($user->societe_id == 0)
            {
                print '<div class="tabsAction">';

                if ($contrat->statut == 0 && $num)
                {
                    print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=valid">'.$langs->trans("Valid").'</a>';
                }

                $numclos=$contrat->array_detail(5); // Tableau des lignes au statut clos
                if ($contrat->statut == 1 && $num == sizeof($numclos))
                {
                    print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=close">'.$langs->trans("Close").'</a>';
                }

                if ($contrat->statut == 0 && $user->rights->contrat->supprimer)
                {
                    print '<a class="butActionDelete" href="fiche.php?id='.$id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
                }

                print "</div>";
            }

        }
        else
        {
            // Contrat non trouvé
            print "Contrat inexistant ou accés refusé";
        }
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
