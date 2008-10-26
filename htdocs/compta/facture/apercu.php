<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
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
 */

/**
	    \file       htdocs/compta/facture/apercu.php
		\ingroup    facture
		\brief      Page de l'onglet aperçu d'une facture
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/project.class.php");


if (!$user->rights->facture->lire)
  accessforbidden();

$langs->load("bills");


// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


llxHeader('',$langs->trans("Bill"),'Facture');

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($_GET["facid"] > 0)
{
    $fac = New Facture($db);
    if ( $fac->fetch($_GET["facid"], $user->societe_id) > 0)
    {
        $soc = new Societe($db, $fac->socid);
        $soc->fetch($fac->socid);
        $author = new User($db);
        if ($fac->user_author)
        {
            $author->id = $fac->user_author;
            $author->fetch();
        }
        
		$head = facture_prepare_head($fac);
        dolibarr_fiche_head($head, 'preview', $langs->trans("InvoiceCustomer"));


        /*
         *   Facture
         */
        print '<table class="border" width="100%">';
		$rowspan=3;
        
        // Reference
        print '<tr><td width="20%">'.$langs->trans('Ref').'</td><td colspan="5">'.$fac->ref.'</td></tr>';

		// Societe
        print '<tr><td>'.$langs->trans("Company").'</td>';
        print '<td colspan="5">'.$soc->getNomUrl(1,'compta').'</td>';
        print '</tr>';

        // Dates
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="3">'.dolibarr_print_date($fac->date,"daytext").'</td>';
        print '<td>'.$langs->trans("DateMaxPayment").'</td><td>' . dolibarr_print_date($fac->date_lim_reglement,"daytext");
        if ($fac->paye == 0 && $fac->date_lim_reglement < (time() - $conf->facture->client->warning_delay)) print img_warning($langs->trans("Late"));
        print "</td></tr>";

        // Conditions et modes de réglement
        print '<tr><td>'.$langs->trans("PaymentConditions").'</td><td colspan="3">';
        $html->form_conditions_reglement($_SERVER["PHP_SELF"]."?facid=$fac->id",$fac->cond_reglement_id,"none");
        print '</td>';
        print '<td width="25%">'.$langs->trans("PaymentMode").'</td><td width="25%">';
        $html->form_modes_reglement($_SERVER["PHP_SELF"]."?facid=$fac->id",$fac->mode_reglement_id,"none");
        print '</td></tr>';

		// Remise globale
		print '<tr><td>'.$langs->trans('GlobalDiscount').'</td>';
/*
		if ($fac->brouillon == 1 && $user->rights->facture->creer)
		{
			print '<form action="facture.php?facid='.$fac->id.'" method="post">';
			print '<input type="hidden" name="action" value="setremise">';
			print '<td colspan="3"><input type="text" name="remise" size="1" value="'.$fac->remise_percent.'">% ';
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'"></td>';
			print '</form>';
		}
		else
		{
*/
			print '<td colspan="3">'.$fac->remise_percent.'%</td>';
//		}

        $nbrows=5;
        if ($conf->global->FAC_USE_CUSTOMER_ORDER_REF) $nbrows++;
        if ($conf->projet->enabled) $nbrows++;
		    print '<td rowspan="'.$nbrows.'" colspan="2" valign="top">';

        /*
         * Documents
         */
        $facref = sanitizeFileName($fac->ref);
        $dir_output = $conf->facture->dir_output . "/";
				$filepath = $dir_output . $facref . "/";
        $file = $filepath . $facref . ".pdf";
        $filedetail = $filepath . $facref . "-detail.pdf";
        $relativepath = "${facref}/${facref}.pdf";
        $relativepathdetail = "${facref}/${facref}-detail.pdf";

        // Chemin vers png aperçus
        $relativepathimage = "${facref}/${facref}.pdf.png";
        $fileimage = $file.".png";          // Si PDF d'1 page
        $fileimagebis = $file.".png.0";     // Si PDF de plus d'1 page


        $var=true;

        // Si fichier PDF existe
        if (file_exists($file))
        {
            $encfile = urlencode($file);
            print_titre($langs->trans("Documents"));
            print '<table class="border" width="100%">';

            print "<tr $bc[$var]><td>".$langs->trans("Bill")." PDF</td>";

            print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=facture&file='.urlencode($relativepath).'">'.$fac->ref.'.pdf</a></td>';
            print '<td align="right">'.filesize($file). ' bytes</td>';
            print '<td align="right">'.dolibarr_print_date(filemtime($file),'dayhour').'</td>';
            print '</tr>';

            // Si fichier detail PDF existe
            if (file_exists($filedetail)) // facture détaillée supplémentaire
            {
                print "<tr $bc[$var]><td>Facture détaillée</td>";

                print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=facture&file='.urlencode($relativepathdetail).'">'.$fac->ref.'-detail.pdf</a></td>';
                print '<td align="right">'.filesize($filedetail). ' bytes</td>';
                print '<td align="right">'.dolibarr_print_date(filemtime($filedetail),"%d %b %Y %H:%M:%S").'</td>';
                print '</tr>';
            }

            print "</table>\n";

            // Conversion du PDF en image png si fichier png non existant
					if (! file_exists($fileimage) && ! file_exists($fileimagebis))
					{
						if (function_exists("imagick_readimage"))
            {
                    $handle = imagick_readimage( $file ) ;
                    if ( imagick_iserror( $handle ) )
                    {
                        $reason      = imagick_failedreason( $handle ) ;
                        $description = imagick_faileddescription( $handle ) ;

                        print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n";
                    }

                    imagick_convert( $handle, "PNG" ) ;

                    if ( imagick_iserror( $handle ) )
                    {
                        $reason      = imagick_failedreason( $handle ) ;
                        $description = imagick_faileddescription( $handle ) ;

                        print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n";
                    }

                    imagick_writeimages( $handle, $file .".png");
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
        print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
        print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

		print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_tva).'</td>';
		print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2" nowrap>'.price($fac->total_ttc).'</td>';
		print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans('Status').'</td><td align="left" colspan="3">'.($fac->getLibStatut()).'</td></tr>';

		// Projet
        if ($conf->projet->enabled)
        {
            $langs->load("projects");
	        print '<tr>';
            print '<td>'.$langs->trans("Project").'</td><td colspan="3">';
            if ($fac->projetid > 0)
            {
                $projet = New Project($db);
                $projet->fetch($fac->projetid);
                print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$fac->projetid.'">'.$projet->title.'</a>';
            }
            else
            {
                print '&nbsp;';
            }
			print '</td></tr>';
        }

        print '</table>';

    }
    else
    {
        // Facture non trouvée
        print $langs->trans("ErrorBillNotFound",$_GET["facid"]);
    }
} 

// Si fichier png PDF d'1 page trouvé
if (file_exists($fileimage))
{	  
  print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufacture&file='.urlencode($relativepathimage).'">';
}
// Si fichier png PDF de plus d'1 page trouvé
elseif (file_exists($fileimagebis))
	{
		$multiple = $relativepathimage . ".";

		for ($i = 0; $i < 20; $i++)
		{
			$preview = $multiple.$i;
			
			if (file_exists($dir_output.$preview))
      {
      	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufacture&file='.urlencode($preview).'"><p>';
      }
	}
}


print '</div>';


// Juste pour éviter bug IE qui réorganise mal div précédents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
