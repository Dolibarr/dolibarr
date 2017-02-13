<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Frederic France      <frederic.france@free.fr>
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
 *
 */

/**
 *      \file       htdocs/compta/facture/apercu.php
 *      \ingroup    facture
 *      \brief      Preview Tab of invoice
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
if (! empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load("bills");

// Security check
$socid=0;
$id = GETPOST('facid','int');
$ref = GETPOST("ref");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $id);


/*
 * View
 */

$now=dol_now();

$title = $langs->trans('InvoiceCustomer') . " - " . $langs->trans('Preview');
$helpurl = "EN:Customers_Invoices|FR:Factures_Clients|ES:Facturas_a_clientes";
llxHeader('', $title, $helpurl);

$form = new Form($db);

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

        $head = facture_prepare_head($object);
        dol_fiche_head($head, 'preview', $langs->trans("InvoiceCustomer"), 0, 'bill');


        $totalpaye  = $object->getSommePaiement();

        /*
         *   Invoice
         */
        print '<table class="border" width="100%">';

    	$linkback = '<a href="' . DOL_URL_ROOT . '/compta/facture/list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
    
    	// Ref
    	print '<tr><td class="titlefield">' . $langs->trans('Ref') . '</td><td colspan="5">';
    	$morehtmlref = '';
    	$discount = new DiscountAbsolute($db);
    	$result = $discount->fetch(0, $object->id);
    	if ($result > 0) {
    		$morehtmlref = ' (' . $langs->trans("CreditNoteConvertedIntoDiscount", $discount->getNomUrl(1, 'discount')) . ')';
    	}
    	if ($result < 0) {
    		dol_print_error('', $discount->error);
    	}
    	print $form->showrefnav($object, 'ref', $linkback, 1, 'facnumber', 'ref', $morehtmlref);
    	print '</td></tr>';

        // Ref customer
        print '<tr><td>'.$langs->trans('RefCustomer').'</td>';
        print '<td colspan="5">'.$object->ref_client.'</td>';
        print '</tr>';

        // Thirdparty
        print '<tr><td>'.$langs->trans("Company").'</td>';
        print '<td colspan="5">'.$soc->getNomUrl(1,'compta').'</td>';
        print '</tr>';

        // Type
        print '<tr><td>'.$langs->trans('Type').'</td>';
        print '<td colspan="5">';
        print $object->getLibType();
        if ($object->type == Facture::TYPE_REPLACEMENT)
        {
            $facreplaced=new Facture($db);
            $facreplaced->fetch($object->fk_facture_source);
            print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
        }
        if ($object->type == Facture::TYPE_CREDIT_NOTE)
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
        print '</td>';
        print '</tr>';

        // Relative and absolute discounts
        $addabsolutediscount=' <a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$soc->id.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("AddGlobalDiscount").'</a>';
        $addcreditnote=' <a href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&socid='.$soc->id.'&type=2&backtopage='.urlencode($_SERVER["PHP_SELF"]).'?facid='.$object->id.'">'.$langs->trans("AddCreditNote").'</a>';

        print '<tr><td>'.$langs->trans('Discounts').'</td>';
        print '<td colspan="5">';
        if ($soc->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_percent);
        else print $langs->trans("CompanyHasNoRelativeDiscount");

        if ($absolute_discount > 0)
        {
            print '. ';
            if ($object->statut > Facture::STATUS_DRAFT || $object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT)
            {
                if ($object->statut == Facture::STATUS_DRAFT)
                {
                    print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
                    print '. ';
                }
                else
                {
                    if ($object->statut < Facture::STATUS_VALIDATED || $object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT)
                    {
                        $text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
                        print '<br>'.$text.'.<br>';
                    }
                    else
                    {
                        $text=$langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
                        $text2=$langs->trans("AbsoluteDiscountUse");
                        print $form->textwithpicto($text,$text2);
                    }
                }
            }
            else
            {
                // Remise dispo de type remise fixe (not credit note)
                $filter='fk_facture_source IS NULL';
                print '<br>';
                $form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0,  'remise_id',$soc->id, $absolute_discount, $filter, $resteapayer, ' - '.$addabsolutediscount, 1);
            }
        }
        else
        {
            if ($absolute_creditnote > 0)    // If not linked will be added later
            {
                if ($object->statut == Facture::STATUS_DRAFT && $object->type != Facture::TYPE_CREDIT_NOTE && $object->type != Facture::TYPE_DEPOSIT) print ' - '.$addabsolutediscount.'<br>';
                else print '.';
            }
            else print '. ';
        }
        if ($absolute_creditnote > 0)
        {
            // If validated, we show link "add credit note to payment"
            if ($object->statut != Facture::STATUS_VALIDATED || $object->type == Facture::TYPE_CREDIT_NOTE || $object->type == Facture::TYPE_DEPOSIT)
            {
                if ($object->statut == Facture::STATUS_DRAFT && $object->type != Facture::TYPE_DEPOSIT)
                {
                    $text=$langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->currency));
                    print $form->textwithpicto($text,$langs->trans("CreditNoteDepositUse"));
                }
                else
                {
                    print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->currency)).'.';
                }
            }
            else
            {
                // Remise dispo de type avoir
                $filter='fk_facture_source IS NOT NULL';
                if (! $absolute_discount) print '<br>';
                $form->form_remise_dispo($_SERVER["PHP_SELF"].'?facid='.$object->id, 0, 'remise_id_for_payment', $soc->id, $absolute_creditnote, $filter, $resteapayer, '', 1);
            }
        }
        if (! $absolute_discount && ! $absolute_creditnote)
        {
            print $langs->trans("CompanyHasNoAbsoluteDiscount");
            if ($object->statut == Facture::STATUS_DRAFT && $object->type != Facture::TYPE_CREDIT_NOTE && $object->type != Facture::TYPE_DEPOSIT) print ' - '.$addabsolutediscount.'<br>';
            else print '. ';
        }
        /*if ($object->statut == 0 && $object->type != 2 && $object->type != 3)
         {
         if (! $absolute_discount && ! $absolute_creditnote) print '<br>';
         //print ' &nbsp; - &nbsp; ';
         print $addabsolutediscount;
         //print ' &nbsp; - &nbsp; '.$addcreditnote;      // We disbale link to credit note
         }*/
        print '</td>';
        print '</tr>';

        // Dates
        print '<tr><td>'.$langs->trans("DateInvoice").'</td>';
        print '<td>'.dol_print_date($object->date,"daytext").'</td>';

        // Right part with $rowspan lines
        $rowspan=5;
        if (! empty($conf->projet->enabled)) $rowspan++;
        print '<td rowspan="'.$rowspan.'" valign="top" width="50%">';

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

        // Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
        $fileimage = $file.'_preview.png';              // If PDF has 1 page
        $fileimagebis = $file.'_preview-0.pdf.png';     // If PDF has more than one page
        $relativepathimage = $relativepath.'_preview.png';

        $var=true;

        // Si fichier PDF existe
        if (file_exists($file))
        {
            $encfile = urlencode($file);
            print '<table class="nobordernopadding" width="100%">';
            print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("Documents").'</td></tr>';

            print "<tr ".$bc[$var]."><td>".$langs->trans("Bill")." PDF</td>";

            print '<td><a data-ajax="false" href="'.DOL_URL_ROOT . '/document.php?modulepart=facture&file='.urlencode($relativepath).'">'.$object->ref.'.pdf</a></td>';
            print '<td align="right">'.dol_print_size(dol_filesize($file)). '</td>';
            print '<td align="right">'.dol_print_date(dol_filemtime($file),'dayhour').'</td>';
            print '</tr>';

            // Si fichier detail PDF existe
            if (file_exists($filedetail)) // facture detaillee supplementaire
            {
                print "<tr ".$bc[$var]."><td>Facture detaillee</td>";

                print '<td><a data-ajax="false" href="'.DOL_URL_ROOT . '/document.php?modulepart=facture&file='.urlencode($relativepathdetail).'">'.$object->ref.'-detail.pdf</a></td>';
                print '<td align="right">'.dol_print_size(dol_filesize($filedetail)).'</td>';
                print '<td align="right">'.dol_print_date(dol_filemtime($filedetail),'dayhour').'</td>';
                print '</tr>';
            }

            print "</table>\n";

            // Conversion du PDF en image png si fichier png non existant
			if ((! file_exists($fileimage) && ! file_exists($fileimagebis)) || (filemtime($fileimage) < filemtime($file)))
            {
                if (class_exists("Imagick"))
                {
                    $ret = dol_convert_file($file,'png',$fileimage);
                    if ($ret < 0) $error++;
                }
                else
                {
                    $langs->load("errors");
                    print '<font class="error">'.$langs->trans("ErrorNoImagickReadimage").'</font>';
                }
            }
        }
        print "</td></tr>";

        // Total HT
        print '<tr><td>'.$langs->trans("AmountHT").'</td>';
        print '<td align="right" class="nowrap"><b>' . price($object->total_ht, '', $langs, 0, - 1, - 1, $conf->currency) . '</b></td>';
        print '</tr>';

        // Total VAT
        print '<tr><td>'.$langs->trans('AmountVAT').'</td>';
        print '<td align="right" class="nowrap"><b>' . price($object->total_tva, '', $langs, 0, - 1, - 1, $conf->currency) . '</b></td>';
        print '</tr>';

        // Total TTC
        print '<tr><td>'.$langs->trans('AmountTTC').'</td>';
        print '<td align="right" class="nowrap"><b>' . price($object->total_ttc, '', $langs, 0, - 1, - 1, $conf->currency) . '</b></td>';
        print '</tr>';

        // Statut
        print '<tr><td>'.$langs->trans('Status').'</td>';
        print '<td align="left">'.($object->getLibStatut(4,$totalpaye)).'</td>';
        print '</tr>';

        // Projet
        if (! empty($conf->projet->enabled))
        {
            $langs->load("projects");
            print '<tr><td>'.$langs->trans("Project").'</td>';
            print '<td>';
            if ($object->fk_project > 0)
            {
                $project = New Project($db);
                $project->fetch($object->fk_project);
                print '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'">'.$project->title.'</a>';
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
        print $langs->trans("ErrorBillNotFound",$id);
    }
}

print '<table class="border" width="100%">';
print '<tr><td>';
print '<div class="photolist">';
// Si fichier png PDF d'1 page trouve
if (file_exists($fileimage))
{
    print '<img class="photo photowithmargin" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufacture&amp;file='.urlencode($relativepathimage).'">';
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
            print '<img class="photo photowithmargin" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufacture&amp;file='.urlencode($preview).'"><p>';
        }
    }
}
print '</div>';
print '</td></tr>';
print '</table>';

llxFooter();

$db->close();
