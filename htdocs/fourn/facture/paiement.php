<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles  <ccomb@free.fr>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
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
 *	\file       htdocs/fourn/facture/paiement.php
 *	\ingroup    fournisseur,facture
 *	\brief      Payment page for suppliers invoices
 */

require("../../main.inc.php");
require(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php');
require(DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php');
require(DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php');

$langs->load('companies');
$langs->load('bills');
$langs->load('banks');

$facid=GETPOST('facid','int');
$action=GETPOST('action');
$socid=GETPOST('socid','int');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";

$amounts = array();

// Security check
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}




/*
 * Actions
 */
if ($action == 'add_paiement')
{
    $error = 0;

    $datepaye = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
    $paiement_id = 0;
    $total = 0;

    // Genere tableau des montants amounts
    foreach ($_POST as $key => $value)
    {
        if (substr($key,0,7) == 'amount_')
        {
            $other_facid = substr($key,7);
            $amounts[$other_facid] = $_POST[$key];
            $total = $total + $amounts[$other_facid];
        }
    }

    // Effectue les verifications des parametres
    if ($_POST['paiementid'] <= 0)
    {
        $mesg = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('PaymentMode')).'</div>';
        $error++;
    }

    if ($conf->banque->enabled)
    {
        // Si module bank actif, un compte est obligatoire lors de la saisie
        // d'un paiement
        if (! $_POST['accountid'])
        {
            $mesg = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('AccountToCredit')).'</div>';
            $error++;
        }
    }

    if ($total == 0)
    {
        $mesg = '<div class="error">'.$langs->transnoentities('ErrorFieldRequired',$langs->trans('PaymentAmount')).'</div>';
        $error++;
    }

    if (empty($datepaye))
    {
        $mesg = '<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Date')).'</div>';
        $error++;
    }

    if (! $error)
    {
        $db->begin();

        // Creation de la ligne paiement
        $paiement = new PaiementFourn($db);
        $paiement->datepaye     = $datepaye;
        $paiement->amounts      = $amounts;   // Array of amounts
        $paiement->paiementid   = $_POST['paiementid'];
        $paiement->num_paiement = $_POST['num_paiement'];
        $paiement->note         = $_POST['comment'];
        if (! $error)
        {
            $paiement_id = $paiement->create($user,(GETPOST('closepaidinvoices')=='on'?1:0));
            if ($paiement_id < 0)
            {
                $errmsg='<div class="error">'.$paiement->error.'</div>';
                $error++;
            }
        }

        if (! $error)
        {
            $result=$paiement->addPaymentToBank($user,'payment_supplier','(SupplierInvoicePayment)',$_POST['accountid'],'','');
            if ($result < 0)
            {
                $errmsg='<div class="error">'.$paiement->error.'</div>';
                $error++;
            }
        }

        if (! $error)
        {
            $db->commit();

            // If payment dispatching on more than one invoice, we keep on summary page, otherwise go on invoice card
            $invoiceid=0;
            foreach ($paiement->amounts as $key => $amount)
            {
                $facid = $key;
                if (is_numeric($amount) && $amount <> 0)
                {
                    if ($invoiceid != 0) $invoiceid=-1; // There is more than one invoice payed by this payment
                    else $invoiceid=$facid;
                }
            }
            if ($invoiceid > 0) $loc = DOL_URL_ROOT.'/fourn/facture/fiche.php?facid='.$invoiceid;
            else $loc = DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$paiement_id;
            Header('Location: '.$loc);
            exit;
        }
        else
        {
            $db->rollback();
        }
    }
}


/*
 * View
 */

$supplierstatic=new Societe($db);
$invoicesupplierstatic = new FactureFournisseur($db);

llxHeader();

$form=new Form($db);

if ($action == 'create' || $action == 'add_paiement')
{
    $facture = new FactureFournisseur($db);
    $facture->fetch($facid);

    $datefacture=dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
    $dateinvoice=($datefacture==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0):$datefacture);

    $sql = 'SELECT s.nom, s.rowid as socid,';
    $sql.= ' f.rowid as ref, f.facnumber, f.amount, f.total_ttc as total';
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
    $sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'facture_fourn as f';
    if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql .= ' WHERE f.fk_soc = s.rowid';
    $sql .= ' AND f.rowid = '.$facid;
    if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        if ($num)
        {
            $obj = $db->fetch_object($resql);
            $total = $obj->total;

            print_fiche_titre($langs->trans('DoPayment'));

            if ($mesg)   dol_htmloutput_mesg($mesg);
            if ($errmsg) dol_htmloutput_errors($errmsg);

            print '<form name="addpaiement" action="paiement.php" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="add_paiement">';
            print '<input type="hidden" name="facid" value="'.$facid.'">';
            print '<input type="hidden" name="facnumber" value="'.$obj->facnumber.'">';
            print '<input type="hidden" name="socid" value="'.$obj->socid.'">';
            print '<input type="hidden" name="societe" value="'.$obj->nom.'">';

            print '<table class="border" width="100%">';

            print '<tr class="liste_titre"><td colspan="3">'.$langs->trans('Payment').'</td>';
            print '<tr><td>'.$langs->trans('Company').'</td><td colspan="2">';
            $supplierstatic->id=$obj->socid;
            $supplierstatic->name=$obj->nom;
            print $supplierstatic->getNomUrl(1,'supplier');
            print '</td></tr>';
            print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td>';
            $form->select_date($dateinvoice,'','','','',"addpaiement",1,1);
            print '</td>';
            print '<td>'.$langs->trans('Comments').'</td></tr>';
            print '<tr><td class="fieldrequired">'.$langs->trans('PaymentMode').'</td><td>';
            $form->select_types_paiements(empty($_POST['paiementid'])?'':$_POST['paiementid'],'paiementid');
            print '</td>';
            print '<td rowspan="3" valign="top">';
            print '<textarea name="comment" wrap="soft" cols="60" rows="'._ROWS_3.'">'.(empty($_POST['comment'])?'':$_POST['comment']).'</textarea></td></tr>';
            print '<tr><td>'.$langs->trans('Numero').'</td><td><input name="num_paiement" type="text" value="'.(empty($_POST['num_paiement'])?'':$_POST['num_paiement']).'"></td></tr>';
            if ($conf->banque->enabled)
            {
                print '<tr><td class="fieldrequired">'.$langs->trans('Account').'</td><td>';
                $form->select_comptes(empty($_POST['accountid'])?'':$_POST['accountid'],'accountid',0,'',2);
                print '</td></tr>';
            }
            else
            {
                print '<tr><td colspan="2">&nbsp;</td></tr>';
            }
            print '</table>';

            /*
             * Autres factures impayees
             */
            $sql = 'SELECT f.rowid as facid,f.rowid as ref,f.facnumber,f.total_ttc, f.datef as df';
            $sql .= ', sum(pf.amount) as am';
            $sql .= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as f';
            $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid';
            $sql .= ' WHERE f.fk_soc = '.$facture->socid;
            $sql .= ' AND f.paye = 0';
            $sql .= ' AND f.fk_statut = 1';  // Statut=0 => non validee, Statut=2 => annulee
            $sql .= ' GROUP BY f.rowid,f.facnumber,f.total_ttc,f.datef';
            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                if ($num > 0)
                {
                    $i = 0;
                    print '<br>';

                    print $langs->trans('Invoices').'<br>';
                    print '<table class="noborder" width="100%">';
                    print '<tr class="liste_titre">';
                    print '<td>'.$langs->trans('Ref').'</td>';
                    print '<td>'.$langs->trans('RefSupplier').'</td>';
                    print '<td align="center">'.$langs->trans('Date').'</td>';
                    print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
                    print '<td align="right">'.$langs->trans('AlreadyPaid').'</td>';
                    print '<td align="right">'.$langs->trans('RemainderToPay').'</td>';
                    print '<td align="center">'.$langs->trans('Amount').'</td>';
                    print '</tr>';

                    $var=True;
                    $total=0;
                    $totalrecu=0;
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($resql);
                        $var=!$var;
                        print '<tr '.$bc[$var].'>';
                        print '<td><a href="fiche.php?facid='.$objp->facid.'">'.img_object($langs->trans('ShowBill'),'bill').' '.$objp->ref;
                        print '</a></td>';
                        print '<td>'.$objp->facnumber.'</td>';
                        if ($objp->df > 0 )
                        {
                            print '<td align="center">';
                            print dol_print_date($db->jdate($objp->df)).'</td>';
                        }
                        else
                        {
                            print '<td align="center"><b>!!!</b></td>';
                        }
                        print '<td align="right">'.price($objp->total_ttc).'</td>';
                        print '<td align="right">'.price($objp->am).'</td>';
                        print '<td align="right">'.price($objp->total_ttc - $objp->am).'</td>';
                        print '<td align="center">';
                        $namef = 'amount_'.$objp->facid;
                        print '<input type="text" size="8" name="'.$namef.'" value="'.GETPOST($namef).'">';
                        print "</td></tr>\n";
                        $total+=$objp->total;
                        $total_ttc+=$objp->total_ttc;
                        $totalrecu+=$objp->am;
                        $i++;
                    }
                    if ($i > 1)
                    {
                        // Print total
                        print '<tr class="liste_total">';
                        print '<td colspan="3" align="left">'.$langs->trans('TotalTTC').':</td>';
                        print '<td align="right"><b>'.price($total_ttc).'</b></td>';
                        print '<td align="right"><b>'.price($totalrecu).'</b></td>';
                        print '<td align="right"><b>'.price($total_ttc - $totalrecu).'</b></td>';
                        print '<td align="center">&nbsp;</td>';
                        print "</tr>\n";
                    }
                    print "</table>\n";
                }
                $db->free($resql);
            }
            else
            {
                dol_print_error($db);
            }

			//			print '<tr><td colspan="3" align="center">';
			print '<center><br><input type="checkbox" checked="checked" name="closepaidinvoices"> '.$langs->trans("ClosePaidInvoicesAutomatically");
			print '<br><input type="submit" class="button" value="'.$langs->trans('Save').'"></center>';
			//			print '</td></tr>';

            print '</form>';
        }
    }
}

/*
 * Show list
 */
if (! $_GET['action'] && ! $_POST['action'])
{
    if ($page == -1) $page = 0 ;
    $limit = $conf->liste_limit;
    $offset = $limit * $page ;

    if (! $sortorder) $sortorder='DESC';
    if (! $sortfield) $sortfield='p.datep';

    $sql = 'SELECT p.rowid as pid, p.datep as dp, p.amount as pamount, p.num_paiement,';
    $sql.= ' s.rowid as socid, s.nom,';
    $sql.= ' c.libelle as paiement_type,';
    $sql.= ' ba.rowid as bid, ba.label,';
    if (!$user->rights->societe->client->voir) $sql .= ' sc.fk_soc, sc.fk_user,';
    $sql.= ' SUM(f.amount)';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn AS p';
    if (!$user->rights->societe->client->voir) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn AS pf ON p.rowid=pf.fk_paiementfourn';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn AS f ON f.rowid=pf.fk_facturefourn';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement AS c ON p.fk_paiement = c.id';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe AS s ON s.rowid = f.fk_soc';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
    $sql.= ' WHERE 1=1';
    if (!$user->rights->societe->client->voir) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid)
    {
        $sql .= ' AND f.fk_soc = '.$socid;
    }
    // Search criteria
    if ($_REQUEST["search_ref"])
    {
        $sql .= ' AND p.rowid='.$db->escape($_REQUEST["search_ref"]);
    }
    if ($_REQUEST["search_account"])
    {
        $sql .= ' AND b.fk_account='.$db->escape($_REQUEST["search_account"]);
    }
    if ($_REQUEST["search_paymenttype"])
    {
        $sql .= " AND c.code='".$db->escape($_REQUEST["search_paymenttype"])."'";
    }
    if ($_REQUEST["search_amount"])
    {
        $sql .= " AND p.amount=".price2num($_REQUEST["search_amount"]);
    }
    if ($_REQUEST["search_company"])
    {
        $sql .= " AND s.nom LIKE '%".$db->escape($_REQUEST["search_company"])."%'";
    }
    $sql.= " GROUP BY p.rowid, p.datep, p.amount, p.num_paiement, s.rowid, s.nom, c.libelle, ba.rowid, ba.label";
    if (!$user->rights->societe->client->voir) $sql .= ", sc.fk_soc, sc.fk_user";
    $sql.= $db->order($sortfield,$sortorder);
    $sql.= $db->plimit($limit+1, $offset);

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var=True;

        $paramlist='';
        $paramlist.=($_REQUEST["search_ref"]?"&search_ref=".$_REQUEST["search_ref"]:"");
        $paramlist.=($_REQUEST["search_company"]?"&search_company=".$_REQUEST["search_company"]:"");
        $paramlist.=($_REQUEST["search_amount"]?"&search_amount=".$_REQUEST["search_amount"]:"");

        print_barre_liste($langs->trans('SupplierPayments'), $page, 'paiement.php',$paramlist,$sortfield,$sortorder,'',$num);

        if ($mesg) dol_htmloutput_mesg($mesg);
        if ($errmsg) dol_htmloutput_errors($errmsg);

        print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans('RefPayment'),'paiement.php','p.rowid','',$paramlist,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Date'),'paiement.php','dp','',$paramlist,'align="center"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('ThirdParty'),'paiement.php','s.nom','',$paramlist,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Type'),'paiement.php','c.libelle','',$paramlist,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Account'),'paiement.php','ba.label','',$paramlist,'',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Amount'),'paiement.php','f.amount','',$paramlist,'align="right"',$sortfield,$sortorder);
        //print_liste_field_titre($langs->trans('Invoice'),'paiement.php','facnumber','',$paramlist,'',$sortfield,$sortorder);
        print "</tr>\n";

        // Lines for filters fields
        print '<tr class="liste_titre">';
        print '<td align="left">';
        print '<input class="fat" type="text" size="4" name="search_ref" value="'.$_REQUEST["search_ref"].'">';
        print '</td>';
        print '<td>&nbsp;</td>';
        print '<td align="left">';
        print '<input class="fat" type="text" size="6" name="search_company" value="'.$_REQUEST["search_company"].'">';
        print '</td>';
        print '<td>';
        $form->select_types_paiements($_REQUEST["search_paymenttype"],'search_paymenttype','',2,1,1);
        print '</td>';
        print '<td>';
        $form->select_comptes($_REQUEST["search_account"],'search_account',0,'',1);
        print '</td>';
        print '<td align="right">';
        print '<input class="fat" type="text" size="4" name="search_amount" value="'.$_REQUEST["search_amount"].'">';
        print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
        print '</td>';
        print "</tr>\n";

        while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;
            print '<tr '.$bc[$var].'>';

            // Ref payment
            print '<td nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$objp->pid.'">'.img_object($langs->trans('ShowPayment'),'payment').' '.$objp->pid.'</a></td>';

            // Date
            print '<td nowrap="nowrap" align="center">'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";

            print '<td>';
            if ($objp->socid) print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$objp->socid.'">'.img_object($langs->trans('ShowCompany'),'company').' '.dol_trunc($objp->nom,32).'</a>';
            else print '&nbsp;';
            print '</td>';

            print '<td>'.dol_trunc($objp->paiement_type.' '.$objp->num_paiement,32)."</td>\n";

            print '<td>';
            if ($objp->bid) print '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.dol_trunc($objp->label,24).'</a>';
            else print '&nbsp;';
            print '</td>';

            print '<td align="right">'.price($objp->pamount).'</td>';

            // Ref invoice
            /*$invoicesupplierstatic->ref=$objp->facnumber;
            $invoicesupplierstatic->id=$objp->facid;
            print '<td nowrap="nowrap">';
            print $invoicesupplierstatic->getNomUrl(1);
            print '</td>';*/

            print '</tr>';
            $i++;
        }
        print "</table>";
        print "</form>\n";
    }
    else
    {
        dol_print_error($db);
    }
}

$db->close();

llxFooter();
?>
