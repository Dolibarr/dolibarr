<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *		\file		htdocs/commande/apercu.php
 *		\ingroup	commande
 *		\brief		Preview tab of order
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

$langs->load('orders');
$langs->load("bills");
$langs->load('compta');

// Security check
$socid=0;
$id = GETPOST('id','int');
$ref = GETPOST("ref");
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'commande',$id,'');


/*
 * View Mode
 */

$form = new Form($db);

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

if ($id > 0 || ! empty($ref))
{
	$object = new Commande($db);

	if ($object->fetch($id,$ref) > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($object->socid);


        $head = commande_prepare_head($object);
        dol_fiche_head($head, 'preview', $langs->trans("CustomerOrder"), 0, 'order');

        print '<table class="border" width="100%">';

    	//$linkback = '<a href="' . DOL_URL_ROOT . '/comm/propal/list.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
    
    	// Ref
    	print '<tr><td>' . $langs->trans('Ref') . '</td><td colspan="5">';
    	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
    	print '</td></tr>';

        // Ref cde client
        print '<tr><td>'.$langs->trans('RefCustomer').'</td>';
        print '<td colspan="5">'.$object->ref_client.'</td>';
        print '</tr>';

        // Client
        print '<tr><td>'.$langs->trans("Customer").'</td>';
        print '<td colspan="5">'.$soc->getNomUrl(1).'</td>';
        print '</tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td>';
        print '<td colspan="5">'.$object->getLibStatut(4).'</td>';
        print '</tr>';

        // Discount - left part
        print '<tr><td>'.$langs->trans('Discounts').'</td>';
        print '<td colspan="5">'.$object->remise_percent.'%</td>';
        print '</tr>';

        // Date - left part
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="3">'.dol_print_date($object->date,"daytext").'</td>';

        // Right part with $rowspan lines
        $rowspan=4;
        print '<td rowspan="'.$rowspan.'" valign="top" width="50%">';

        /*
         * Documents
         */
		$objectref = dol_sanitizeFileName($object->ref);
		$dir_output = $conf->commande->dir_output . "/";
		$filepath = $dir_output . $objectref . "/";
		$file = $filepath . $objectref . ".pdf";
		$filedetail = $filepath . $objectref . "-detail.pdf";
        $relativepath = $objectref.'/'.$objectref.'.pdf';
        $relativepathdetail = $objectref.'/'.$objectref.'-detail.pdf';

        // Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
        $fileimage = $file.'_preview.png';          	// If PDF has 1 page
        $fileimagebis = $file.'_preview-0.pdf.png';     // If PDF has more than one page
        $relativepathimage = $relativepath.'_preview.png';

		$var=true;

		// if PDF file exist
		if (file_exists($file))
		{
			$encfile = urlencode($file);
            print '<table class="nobordernopadding" width="100%">';
            print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("Documents").'</td></tr>';

			print "<tr ".$bc[$var]."><td>".$langs->trans("Order")." PDF</td>";

			print '<td><a data-ajax="false" href="'.DOL_URL_ROOT . '/document.php?modulepart=commande&amp;file='.urlencode($relativepath).'">'.$object->ref.'.pdf</a></td>';
			print '<td align="right">'.dol_print_size(dol_filesize($file)).'</td>';
			print '<td align="right">'.dol_print_date(dol_filemtime($file),'dayhour').'</td>';
			print '</tr>';

			// Si fichier detail PDF existe
			// TODO deprecated ?
			if (file_exists($filedetail))
			{
				print "<tr ".$bc[$var]."><td>Commande detaillee</td>";

				print '<td><a data-ajax="false" href="'.DOL_URL_ROOT . '/document.php?modulepart=commande&amp;file='.urlencode($relativepathdetail).'">'.$object->ref.'-detail.pdf</a></td>';
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

        print '</td></tr>';

        // Total HT - left part
        print '<tr><td>'.$langs->trans('AmountHT').'</td>';
        print '<td align="right" class="nowrap"><b>' . price($object->total_ht, '', $langs, 0, - 1, - 1, $conf->currency) . '</b></td>';
        print '</tr>';

        // Total VAT - left part
        print '<tr><td>'.$langs->trans('AmountVAT').'</td>';
        print '<td align="right" class="nowrap"><b>' . price($object->total_tva, '', $langs, 0, - 1, - 1, $conf->currency) . '</b></td>';
        print '</tr>';

        // Total TTC - left part
        print '<tr><td>'.$langs->trans('AmountTTC').'</td>';
        print '<td align="right" class="nowrap"><b>' . price($object->total_ttc, '', $langs, 0, - 1, - 1, $conf->currency) . '</b></td>';
        print '</tr>';

        print '</table>';

		dol_fiche_end();
	}
	else
	{
		// Object not found
		print $langs->trans("ErrorOrderNotFound",$id);
	}
}

print '<table class="border" width="100%">';
print '<tr><td>';
print '<div class="photolist">';
// Si fichier png PDF d'1 page trouve
if (file_exists($fileimage))
{
	print '<img class="photo photowithmargin" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercucommande&amp;file='.urlencode($relativepathimage).'">';
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
			print '<img class="photo photowithmargin" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercucommande&amp;file='.urlencode($preview).'"><p>';
		}
	}
}
print '</div>';
print '</td></tr>';
print '</table>';


llxFooter();

$db->close();
