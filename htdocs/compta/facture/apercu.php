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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
 * 	    \file       htdocs/compta/facture/apercu.php
 * 		\ingroup    facture
 * 		\brief      Page de l'onglet apercu d'une facture
 * 		\version    $Revision$
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

$langs->load("bills");

// Security check
$socid=0;
$id = GETPOST("id");
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
        $soc = new Societe($db, $object->socid);
        $soc->fetch($object->socid);
        
        $author = new User($db);
        if ($object->user_author)
        {
            $author->fetch($object->user_author);
        }

		$head = facture_prepare_head($object);
        dol_fiche_head($head, 'preview', $langs->trans("InvoiceCustomer"), 0, 'bill');


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

        // Dates
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="3">'.dol_print_date($object->date,"daytext").'</td>';
        print '<td>'.$langs->trans("DateMaxPayment").'</td><td>' . dol_print_date($object->date_lim_reglement,"daytext");
        if ($object->paye == 0 && $object->date_lim_reglement < ($now - $conf->facture->client->warning_delay)) print img_warning($langs->trans("Late"));
        print "</td></tr>";

        // Conditions et modes de reglement
        print '<tr><td>'.$langs->trans("PaymentConditions").'</td><td colspan="3">';
        $html->form_conditions_reglement($_SERVER["PHP_SELF"]."?facid=$object->id",$object->cond_reglement_id,"none");
        print '</td>';
        print '<td width="25%">'.$langs->trans("PaymentMode").'</td><td width="25%">';
        $html->form_modes_reglement($_SERVER["PHP_SELF"]."?facid=$object->id",$object->mode_reglement_id,"none");
        print '</td></tr>';

		// Remise globale
		print '<tr><td>'.$langs->trans('GlobalDiscount').'</td>';
		print '<td colspan="3">'.$object->remise_percent.'%</td>';

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
        $relativepath = "${objectref}/${objectref}.pdf";
        $relativepathdetail = "${objectref}/${objectref}-detail.pdf";

        // Chemin vers png apercus
        $fileimage = $file.".png";          // Si PDF d'1 page
        $fileimagebis = $file."-0.png";     // Si PDF de plus d'1 page

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
		print '<tr><td>'.$langs->trans('Status').'</td><td align="left" colspan="3">'.($object->getLibStatut()).'</td></tr>';

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
	$multiple = $relativepath . "-";
	
	for ($i = 0; $i < 20; $i++)
	{
		$preview = $multiple.$i.'.png';
		
		if (file_exists($dir_output.$preview))
		{
			print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufacture&file='.urlencode($preview).'"><p>';
		}
	}
}

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
