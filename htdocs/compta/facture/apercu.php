<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *
 */

/**
 * 	    \file       htdocs/compta/facture/apercu.php
 * 		\ingroup    facture
 * 		\brief      Page de l'onglet apercu d'une facture
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

$langs->load("bills");

// Security check
$socid=0;
$id = GETPOST("facid");
$ref = GETPOST("ref");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id);


/*
 * View
 */

$now=dol_now();

llxHeader('',$langs->trans("Bill"),'Facture');

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || ! empty($ref))
{
    $object = New Facture($db);

    if ($object->fetch($id,$ref) > 0)
    {
        $soc = new Societe($db);
        $soc->fetch($object->socid);

        $author = new User($db);
        if ($object->user_author)
        {
            $author->fetch($object->user_author);
        }

        $head = facture_prepare_head($object);
        dol_fiche_head($head, 'preview', $langs->trans("InvoiceCustomer"), 0, 'bill');


        $totalpaye  = $object->getSommePaiement();

        /*
         *   Facture
         */
        print '<table class="border" width="100%">';
        $rowspan=3;

        // Reference
        print '<tr><td width="20%">'.$langs->trans('Ref').'</td><td colspan="5">'.$object->ref.'</td></tr>';

        // Societe
        print '<tr><td>'.$langs->trans("Company").'</td>';
        print '<td colspan="5">'.$soc->getNomUrl(1,'compta').'</td>';
        print '</tr>';

        // Type
        print '<tr><td>'.$langs->trans('Type').'</td><td colspan="5">';
        print $object->getLibType();
        if ($object->type == 1)
        {
            $facreplaced=new Facture($db);
            $facreplaced->fetch($object->fk_facture_source);
            print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
        }
        if ($object->type == 2)
        {
            $facusing=new Facture($db);
            $facusing->fetch($object->fk_facture_source);
            print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
        }

        $facidavoir=$object->getListIdAvoirFromInvoice();
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
        if ($objectidnext > 0)
        {
            $facthatreplace=new Facture($db);
            $facthatreplace->fetch($objectidnext);
            print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
        }
        print '</td></tr>';

        // Relative and absolute discounts
        $addabsolutediscount=' <a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("AddGlobalDiscount").'</a>';
        $addcreditnote=' <a href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&socid='.$soc->id.'&type=2&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("AddCreditNote").'</a>';

        print '<tr><td>'.$langs->trans('Discounts');
        print '</td><td colspan="5">';
        if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
        else print $langs->trans("CompanyHasNoRelativeDiscount");

        if ($absolute_discount > 0)
        {
            print '. ';
            if ($object->statut > 0 || $object->type == 2 || $object->type == 3)
            {
                if ($object->statut == 0)
                {
                    print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->monnaie));
                    print '. ';
                }
                else
                {
                    if ($object->statut < 1 || $object->type == 2 || $object->type == 3)
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
                // Remise dispo de type remise fixe (not credit note)
                $filter='fk_facture_source IS NULL';
                print '<br>';
                $html->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0,  'remise_id',$soc->id, $absolute_discount, $filter, $resteapayer, ' - '.$addabsolutediscount);
            }
        }
        else
        {
            if ($absolute_creditnote > 0)    // If not linke will be added later
            {
                if ($object->statut == 0 && $object->type != 2 && $object->type != 3) print ' - '.$addabsolutediscount.'<br>';
                else print '.';
            }
            else print '. ';
        }
        if ($absolute_creditnote > 0)
        {
            // If validated, we show link "add credit note to payment"
            if ($object->statut != 1 || $object->type == 2 || $object->type == 3)
            {
                if ($object->statut == 0 && $object->type != 3)
                {
                    $text=$langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->monnaie));
                    print $html->textwithpicto($text,$langs->trans("CreditNoteDepositUse"));
                }
                else
                {
                    print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->monnaie)).'.';
                }
            }
            else
            {
                // Remise dispo de type avoir
                $filter='fk_facture_source IS NOT NULL';
                if (! $absolute_discount) print '<br>';
                $html->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filter, $resteapayer);
            }
        }
        if (! $absolute_discount && ! $absolute_creditnote)
        {
            print $langs->trans("CompanyHasNoAbsoluteDiscount");
            if ($object->statut == 0 && $object->type != 2 && $object->type != 3) print ' - '.$addabsolutediscount.'<br>';
            else print '. ';
        }
        /*if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
         {
         if (! $absolute_discount && ! $absolute_creditnote) print '<br>';
         //print ' &nbsp; - &nbsp; ';
         print $addabsolutediscount;
         //print ' &nbsp; - &nbsp; '.$addcreditnote;      // We disbale link to credit note
         }*/
        print '</td></tr>';

        // Dates
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="5">'.dol_print_date($object->date,"daytext").'</td>';
        print "</tr>";

        // Date payment term
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('DateMaxPayment');
        print '</td>';
        if ($object->type != 2 && $action != 'editpaymentterm' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editpaymentterm&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
        print '</tr></table>';
        print '</td><td colspan="5">';
        if ($object->type != 2)
        {
            if ($action == 'editpaymentterm')
            {
                $html->form_date($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->date_lim_reglement,'paymentterm');
            }
            else
            {
                print dol_print_date($object->date_lim_reglement,'daytext');
                if ($object->date_lim_reglement < ($now - $conf->facture->client->warning_delay) && ! $object->paye && $object->statut == 1 && ! $object->am) print img_warning($langs->trans('Late'));
            }
        }
        else
        {
            print '&nbsp;';
        }
        print '</td></tr>';

        // Conditions reglement
        print '<tr><td>'.$langs->trans("PaymentConditionsShort").'</td><td colspan="5">';
        $html->form_conditions_reglement($_SERVER["PHP_SELF"]."?facid=$object->id",$object->cond_reglement_id,"none");
        print '</td>';
        print '</td></tr>';

        // Mode de reglement
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('PaymentMode');
        print '</td>';
        if ($action != 'editmode' && $object->brouillon && $user->rights->facture->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;facid='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
        print '</tr></table>';
        print '</td><td colspan="3">';
        if ($action == 'editmode')
        {
            $html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
        }
        else
        {
            $html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id,$object->mode_reglement_id,'none');
        }
        print '</td>';

        $nbrows=5;
        if ($conf->projet->enabled) $nbrows++;
        print '<td rowspan="'.$nbrows.'" colspan="2" valign="top">';

        /*
         * Documents
         */
        $objectref = dol_sanitizeFileName($object->ref);
        $dir_output = $conf->facture->dir_output . "/";
        $filepath = $dir_output . $objectref . "/";
        $file = $filepath . $objectref . ".pdf";
        $filedetail = $filepath . $objectref . "-detail.pdf";
        $relativepath = $objectref.'/'.$objectref.'.pdf';
        $relativepathdetail = $objectref.'/'.$objectref.'-detail.pdf';

        // Chemin vers png apercus
        $fileimage = $file.".png";          // Si PDF d'1 page
        $fileimagebis = $file."-0.png";     // Si PDF de plus d'1 page
        $relativepathimage = $relativepath.'.png';

        $var=true;

        // Si fichier PDF existe
        if (file_exists($file))
        {
            $encfile = urlencode($file);
            print_titre($langs->trans("Documents"));
            print '<table class="border" width="100%">';

            print "<tr $bc[$var]><td>".$langs->trans("Bill")." PDF</td>";

            print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=facture&file='.urlencode($relativepath).'">'.$object->ref.'.pdf</a></td>';
            print '<td align="right">'.dol_print_size(dol_filesize($file)). '</td>';
            print '<td align="right">'.dol_print_date(dol_filemtime($file),'dayhour').'</td>';
            print '</tr>';

            // Si fichier detail PDF existe
            if (file_exists($filedetail)) // facture detaillee supplementaire
            {
                print "<tr $bc[$var]><td>Facture detaillee</td>";

                print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=facture&file='.urlencode($relativepathdetail).'">'.$object->ref.'-detail.pdf</a></td>';
                print '<td align="right">'.dol_print_size(dol_filesize($filedetail)).'</td>';
                print '<td align="right">'.dol_print_date(dol_filemtime($filedetail),'dayhour').'</td>';
                print '</tr>';
            }

            print "</table>\n";

            // Conversion du PDF en image png si fichier png non existant
            if (! file_exists($fileimage) && ! file_exists($fileimagebis))
            {
                if (class_exists("Imagick"))
                {
                    $ret = dol_convert_file($file);
                    if ($ret < 0) $error++;
                }
                else
                {
                    $langs->load("other");
                    print '<font class="error">'.$langs->trans("ErrorNoImagickReadimage").'</font>';
                }
            }
        }
        print "</td></tr>";

        print '<tr><td>'.$langs->trans("AmountHT").'</td>';
        print '<td align="right" colspan="2"><b>'.price($object->total_ht).'</b></td>';
        print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

        print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2" nowrap>'.price($object->total_tva).'</td>';
        print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
        print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($object->total_ttc).'</td>';
        print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans('Status').'</td><td align="left" colspan="3">'.($object->getLibStatut(4,$totalpaye)).'</td></tr>';

        // Projet
        if ($conf->projet->enabled)
        {
            $langs->load("projects");
            print '<tr>';
            print '<td>'.$langs->trans("Project").'</td><td colspan="3">';
            if ($object->fk_project > 0)
            {
                $project = New Project($db);
                $project->fetch($object->fk_project);
                print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$object->fk_project.'">'.$project->title.'</a>';
            }
            else
            {
                print '&nbsp;';
            }
            print '</td></tr>';
        }

        print '</table>';

        dol_fiche_end();
    }
    else
    {
        // Facture non trouvee
        print $langs->trans("ErrorBillNotFound",$_GET["facid"]);
    }
}

// Si fichier png PDF d'1 page trouve
if (file_exists($fileimage))
{
    print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufacture&file='.urlencode($relativepathimage).'">';
}
// Si fichier png PDF de plus d'1 page trouve
elseif (file_exists($fileimagebis))
{
    $multiple = preg_replace('/\.png/','',$relativepath) . "-";

    for ($i = 0; $i < 20; $i++)
    {
        $preview = $multiple.$i.'.png';

        if (file_exists($dir_output.$preview))
        {
            print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufacture&file='.urlencode($preview).'"><p>';
        }
    }
}


$db->close();

llxFooter();
?>
