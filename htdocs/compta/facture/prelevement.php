<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/facture/prelevement.php
 *	\ingroup    facture
 *	\brief      Gestion des prelevement d'une facture
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/prelevement/class/bon-prelevement.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');

if (!$user->rights->facture->lire)
accessforbidden();

$langs->load("bills");
$langs->load("banks");
$langs->load("withdrawals");

// Security check
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}


/*
 * Actions
 */

if ($_GET["action"] == "new")
{
    $fact = new Facture($db);
    if ($fact->fetch($_GET["facid"]))
    {
        $result = $fact->demande_prelevement($user);
        if ($result > 0)
        {
            Header("Location: prelevement.php?facid=".$fact->id);
            exit;
        }
        else
        {
            $mesg='<div class="error">'.$fact->error.'</div>';
        }
    }
}

if ($_GET["action"] == "delete")
{
    $fact = new Facture($db);
    if ($fact->fetch($_GET["facid"]))
    {
        $result = $fact->demande_prelevement_delete($user,$_GET["did"]);
        if ($result == 0)
        {
            Header("Location: prelevement.php?facid=".$fact->id);
            exit;
        }
    }
}


/*
 * View
 */

$now=dol_now();

llxHeader('',$langs->trans("Bill"));

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($_REQUEST["facid"] > 0 || $_REQUEST["ref"])
{
    $fac = new Facture($db);
    if ($fac->fetch($_REQUEST["facid"], $_REQUEST["ref"]) > 0)
    {
        if ($mesg) print $mesg.'<br>';

        $soc = new Societe($db);
        $soc->fetch($fac->socid);

        $totalpaye  = $fac->getSommePaiement();
        $totalcreditnotes = $fac->getSumCreditNotesUsed();
        $totaldeposits = $fac->getSumDepositsUsed();
        //print "totalpaye=".$totalpaye." totalcreditnotes=".$totalcreditnotes." totaldeposts=".$totaldeposits;

        // We can also use bcadd to avoid pb with floating points
        // For example print 239.2 - 229.3 - 9.9; does not return 0.
        //$resteapayer=bcadd($fac->total_ttc,$totalpaye,$conf->global->MAIN_MAX_DECIMALS_TOT);
        //$resteapayer=bcadd($resteapayer,$totalavoir,$conf->global->MAIN_MAX_DECIMALS_TOT);
        $resteapayer = price2num($fac->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits,'MT');

        if ($fac->paye) $resteapayer=0;
        $resteapayeraffiche=$resteapayer;

        $absolute_discount=$soc->getAvailableDiscounts('','fk_facture_source IS NULL');
        $absolute_creditnote=$soc->getAvailableDiscounts('','fk_facture_source IS NOT NULL');
        $absolute_discount=price2num($absolute_discount,'MT');
        $absolute_creditnote=price2num($absolute_creditnote,'MT');

        $author = new User($db);
        if ($fac->user_author)
        {
            $author->fetch($fac->user_author);
        }

        $head = facture_prepare_head($fac);

        dol_fiche_head($head, 'standingorders', $langs->trans('InvoiceCustomer'),0,'bill');

        /*
         *   Facture
         */
        print '<table class="border" width="100%">';

        // Ref
        print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="5">';
        $morehtmlref='';
        $discount=new DiscountAbsolute($db);
        $result=$discount->fetch(0,$fac->id);
        if ($result > 0)
        {
            $morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
        }
        if ($result < 0)
        {
            dol_print_error('',$discount->error);
        }
        print $html->showrefnav($fac,'ref','',1,'facnumber','ref',$morehtmlref);
        print "</td></tr>";

        // Third party
        print '<tr><td>'.$langs->trans('Company').'</td>';
        print '<td colspan="5">'.$soc->getNomUrl(1,'compta');
        print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/compta/facture.php?socid='.$fac->socid.'">'.$langs->trans('OtherBills').'</a>)</td>';
        print '</tr>';

        // Type
        print '<tr><td>'.$langs->trans('Type').'</td><td colspan="5">';
        print $fac->getLibType();
        if ($fac->type == 1)
        {
            $facreplaced=new Facture($db);
            $facreplaced->fetch($fac->fk_facture_source);
            print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
        }
        if ($fac->type == 2)
        {
            $facusing=new Facture($db);
            $facusing->fetch($fac->fk_facture_source);
            print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
        }

        $facidavoir=$fac->getListIdAvoirFromInvoice();
        if (count($facidavoir) > 0)
        {
            print ' ('.$langs->transnoentities("InvoiceHasAvoir");
            $i=0;
            foreach($facidavoir as $id)
            {
                if ($i==0) print ' ';
                else print ',';
                $facavoir=new Facture($db);
                $facavoir->fetch($id);
                print $facavoir->getNomUrl(1);
            }
            print ')';
        }
        if ($facidnext > 0)
        {
            $facthatreplace=new Facture($db);
            $facthatreplace->fetch($facidnext);
            print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
        }
        print '</td></tr>';

        // Discounts
        print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
        if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
        else print $langs->trans("CompanyHasNoRelativeDiscount");
        print '. ';
        if ($absolute_discount > 0)
        {
            if ($fac->statut > 0 || $fac->type == 2 || $fac->type == 3)
            {
                if ($fac->statut == 0)
                {
                    print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->monnaie)).'. ';
                }
                else
                {
                    if ($fac->statut < 1 || $fac->type == 2 || $fac->type == 3)
                    {
                        $text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->monnaie));
                        print '<br>'.$text.'.<br>';
                    }
                    else
                    {
                        $text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->monnaie));
                        $text2=$langs->trans("AbsoluteDiscountUse");
                        print $html->textwithpicto($text,$text2);
                    }
                }
            }
            else
            {
                // Remise dispo de type non avoir
                $filter='fk_facture_source IS NULL';
                print '<br>';
                $html->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$fac->id,0,'remise_id',$soc->id,$absolute_discount,$filter,$resteapayer);
            }
        }
        if ($absolute_creditnote > 0)
        {
            // If validated, we show link "add credit note to payment"
            if ($fac->statut != 1 || $fac->type == 2 || $fac->type == 3)
            {
                if ($fac->statut == 0 && $fac->type != 3)
                {
                    $text=$langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->monnaie));
                    print $html->textwithpicto($text,$langs->trans("CreditNoteDepositUse"));
                }
                else print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->monnaie)).'.';
            }
            else
            {
                // Remise dispo de type avoir
                $filter='fk_facture_source IS NOT NULL';
                if (! $absolute_discount) print '<br>';
                $html->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$fac->id,0,'remise_id_for_payment',$soc->id,$absolute_creditnote,$filter,$resteapayer);
            }
        }
        if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
        print '</td></tr>';

        // Date invoice
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Date');
        print '</td>';
        if ($fac->type != 2 && $_GET['action'] != 'editinvoicedate' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editinvoicedate&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
        print '</tr></table>';
        print '</td><td colspan="3">';

        if ($fac->type != 2)
        {
            if ($_GET['action'] == 'editinvoicedate')
            {
                $html->form_date($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->date,'invoicedate');
            }
            else
            {
                print dol_print_date($fac->date,'daytext');
            }
        }
        else
        {
            print dol_print_date($fac->date,'daytext');
        }
        print '</td>';
        print '</tr>';

        // Date payment term
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('DateMaxPayment');
        print '</td>';
        if ($fac->type != 2 && $_GET['action'] != 'editpaymentterm' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
        print '</tr></table>';
        print '</td><td colspan="3">';
        if ($fac->type != 2)
        {
            if ($_GET['action'] == 'editpaymentterm')
            {
                $html->form_date($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->date_lim_reglement,'paymentterm');
            }
            else
            {
                print dol_print_date($fac->date_lim_reglement,'daytext');
                if ($fac->date_lim_reglement < ($now - $conf->facture->client->warning_delay) && ! $fac->paye && $fac->statut == 1 && ! $fac->am) print img_warning($langs->trans('Late'));
            }
        }
        else
        {
            print '&nbsp;';
        }
        print '</td></tr>';

        // Conditions de reglement
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('PaymentConditionsShort');
        print '</td>';
        if ($fac->type != 2 && $_GET['action'] != 'editconditions' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
        print '</tr></table>';
        print '</td><td colspan="3">';
        if ($fac->type != 2)
        {
            if ($_GET['action'] == 'editconditions')
            {
                $html->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->cond_reglement_id,'cond_reglement_id');
            }
            else
            {
                $html->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->cond_reglement_id,'none');
            }
        }
        else
        {
            print '&nbsp;';
        }
        print '</td></tr>';

        // Mode de reglement
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('PaymentMode');
        print '</td>';
        if ($_GET['action'] != 'editmode' && $fac->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;facid='.$fac->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
        print '</tr></table>';
        print '</td><td colspan="3">';
        if ($_GET['action'] == 'editmode')
        {
            $html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'mode_reglement_id');
        }
        else
        {
            $html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'none');
        }
        print '</td></tr>';

        // Montants
        print '<tr><td>'.$langs->trans('AmountHT').'</td>';
        print '<td align="right" colspan="2" nowrap>'.price($fac->total_ht).'</td>';
        print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
        print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_tva).'</td>';
        print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

        // Amount Local Taxes
        if ($mysoc->pays_code=='ES')
        {
            if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
            {
                print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->pays_code).'</td>';
                print '<td align="right" colspan="2" nowrap>'.price($fac->total_localtax1).'</td>';
                print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
            }
            if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
            {
                print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->pays_code).'</td>';
                print '<td align="right" colspan="2" nowrap>'.price($fac->total_localtax2).'</td>';
                print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
            }
        }

        print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_ttc).'</td>';
        print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans('Status').'</td>';
        print '<td align="left" colspan="3">'.($fac->getLibStatut(4,$totalpaye)).'</td></tr>';

        print '<tr><td>'.$langs->trans("RIB").'</td><td colspan="5">';
        print $soc->display_rib();
        print '</td></tr>';

        print '</table>';

        dol_fiche_end();



        /*
         * Withdrawal request
         */

        $sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande as date_demande";
        $sql .= " , pfd.date_traite as date_traite";
        $sql .= " , pfd.amount";
        $sql .= " , u.rowid as user_id, u.name, u.firstname, u.login";
        $sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
        $sql .= " , ".MAIN_DB_PREFIX."user as u";
        $sql .= " WHERE fk_facture = ".$fac->id;
        $sql .= " AND pfd.fk_user_demande = u.rowid";
        $sql .= " AND pfd.traite = 0";
        $sql .= " ORDER BY pfd.date_demande DESC";

        $result_sql = $db->query($sql);
        if ($result_sql)
        {
            $num = $db->num_rows($result_sql);
        }


        /*
         * Buttons
         */
        print "\n<div class=\"tabsAction\">\n";

        // Add a withdraw request
        if ($fac->statut > 0 && $fac->paye == 0 && $num == 0)
        {
            if ($user->rights->prelevement->bons->creer)
            {
                print '<a class="butAction" href="prelevement.php?facid='.$fac->id.'&amp;action=new">'.$langs->trans("MakeWithdrawRequest").'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#">'.$langs->trans("MakeWithdrawRequest").'</a>';
            }
        }

        print "</div><br>\n";


        print $langs->trans("DoStandingOrdersBeforePayments").'<br>';


        /*
         * Withdrawals
         */
        print '<table class="noborder" width="100%">';

        print '<tr class="liste_titre">';
        print '<td align="left">'.$langs->trans("DateRequest").'</td>';
        print '<td align="center">'.$langs->trans("DateProcess").'</td>';
        print '<td align="center">'.$langs->trans("Amount").'</td>';
        print '<td align="center">'.$langs->trans("WithdrawalReceipt").'</td>';
        print '<td align="center">'.$langs->trans("User").'</td><td>&nbsp;</td><td>&nbsp;</td>';
        print '</tr>';
        $var=True;

        if ($result_sql)
        {
            $i = 0;

            while ($i < $num)
            {
                $obj = $db->fetch_object($result_sql);
                $var=!$var;

                print "<tr ".$bc[$var].">";
                print '<td align="left">'.dol_print_date($db->jdate($obj->date_demande),'day')."</td>\n";
                print '<td align="center">'.$langs->trans("OrderWaiting").'</td>';
                print '<td align="center">'.price($obj->amount).'</td>';
                print '<td align="center">-</td>';
                print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';
                print '<td>&nbsp;</td>';
                print '<td>';
                print '<a href="prelevement.php?facid='.$fac->id.'&amp;action=delete&amp;did='.$obj->rowid.'">';
                print img_delete();
                print '</a></td>';
                print "</tr>\n";
                $i++;
            }

            $db->free($result_sql);
        }
        else
        {
            dol_print_error($db);
        }

        $sql = "SELECT pfd.rowid, pfd.traite, pfd.date_demande,";
        $sql.= " pfd.date_traite, pfd.fk_prelevement_bons, pfd.amount,";
        $sql.= " pb.ref,";
        $sql.= " u.rowid as user_id, u.name, u.firstname, u.login";
        $sql.= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd,";
        $sql.= " ".MAIN_DB_PREFIX."prelevement_bons as pb,";
        $sql.= " ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE fk_facture = ".$fac->id;
        $sql.= " AND pfd.fk_user_demande = u.rowid";
        $sql.= " AND pb.rowid = pfd.fk_prelevement_bons";
        $sql.= " AND pfd.traite = 1";
        $sql.= " ORDER BY pfd.date_demande DESC";

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $var=!$var;

                print "<tr $bc[$var]>";

                print '<td align="left">'.dol_print_date($db->jdate($obj->date_demande),'day')."</td>\n";

                print '<td align="center">'.dol_print_date($db->jdate($obj->date_traite),'day')."</td>\n";

                print '<td align="center">'.price($obj->amount).'</td>';

                print '<td align="center">';
                $withdrawreceipt=new BonPrelevement($db);
                $withdrawreceipt->id=$obj->fk_prelevement_bons;
                $withdrawreceipt->ref=$obj->ref;
                print $withdrawreceipt->getNomUrl(1);
                print "</td>\n";

                print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a></td>';

                print '<td>&nbsp;</td>';
                print '<td>&nbsp;</td>';

                print "</tr>\n";
                $i++;
            }

            $db->free($result);
        }
        else
        {
            dol_print_error($db);
        }

        print "</table>";

    }
    else
    {
        /* Invoice not found */
        print $langs->trans("ErrorBillNotFound",$_GET["facid"]);
    }
}


$db->close();

llxFooter();
?>
