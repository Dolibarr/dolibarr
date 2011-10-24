<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *		\file		htdocs/commande/apercu.php
 *		\ingroup	commande
 *		\brief		Page de l'onglet apercu d'une commande
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

$langs->load('orders');
$langs->load('propal');
$langs->load("bills");
$langs->load('compta');
$langs->load('sendings');

// Security check
$socid=0;
$id = GETPOST("id");
$ref = GETPOST("ref");
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'commande',$id,'');


/*
 * View
 */

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

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

        // Ref
        print '<tr><td width="18%">'.$langs->trans("Ref")."</td>";
        print '<td colspan="2">'.$object->ref.'</td>';
        print '<td width="50%">'.$langs->trans("Source").' : '.$object->getLabelSource();
        if ($object->source == 0)
        {
            // Propale
            $propal = new Propal($db);
            $propal->fetch($object->propale_id);
            print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?id='.$propal->id.'">'.$propal->ref.'</a>';
        }
        print "</td></tr>";

        // Ref cde client
		print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
        print '</td>';
        print '</tr></table>';
		print '</td>';
        print '<td colspan="2">';
		print $object->ref_client;
        print '</td>';
        $nbrow=6;
		print '<td rowspan="'.$nbrow.'" valign="top">';

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

			print "<tr $bc[$var]><td>".$langs->trans("Order")." PDF</td>";

			print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=commande&file='.urlencode($relativepath).'">'.$object->ref.'.pdf</a></td>';
			print '<td align="right">'.dol_print_size(dol_filesize($file)).'</td>';
			print '<td align="right">'.dol_print_date(dol_filemtime($file),'dayhour').'</td>';
			print '</tr>';

			// Si fichier detail PDF existe
			// TODO obsolete ?
			if (file_exists($filedetail))
			{
				print "<tr $bc[$var]><td>Commande detaillee</td>";

				print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=commande&file='.urlencode($relativepathdetail).'">'.$object->ref.'-detail.pdf</a></td>';
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

        // Client
        print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="2">';
        print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a>';
        print '</td>';
        print '</tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td>';
        print "<td colspan=\"2\">".$object->getLibStatut(4)."</td>\n";
        print '</tr>';

        // Date
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print "<td colspan=\"2\">".dol_print_date($object->date,"daytext")."</td>\n";
		print '</tr>';

		// ligne 6
		// partie Gauche
		print '<tr><td height="10" nowrap>'.$langs->trans('GlobalDiscount').'</td>';
		print '<td colspan="2">'.$object->remise_percent.'%</td>';
		print '</tr>';

		// ligne 7
		// partie Gauche
		print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
		print '<td align="right" colspan="1"><b>'.price($object->total_ht).'</b></td>';
		print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
		print '</table>';
	}
	else
	{
		// Object not found
		print $langs->trans("ErrorOrderNotFound",$id);
	}
}

// Si fichier png PDF d'1 page trouve
if (file_exists($fileimage))
{
	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercucommande&file='.urlencode($relativepathimage).'">';
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
			print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercucommande&file='.urlencode($preview).'"><p>';
		}
	}
}

print '</div>';

$db->close();

llxFooter();
?>
