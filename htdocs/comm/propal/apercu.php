<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
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
 *  \file		htdocs/comm/propal/apercu.php
 *  \ingroup	propal
 *  \brief		Page de l'onglet apercu d'une propal
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

$langs->load('propal');
$langs->load("bills");
$langs->load('compta');

// Security check
$socid=0;
$id = GETPOST('id','int');
$ref = GETPOST("ref");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'propale', $id, 'propal');


/*
 * View
 */

llxHeader();

$form = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || ! empty($ref))
{
	$object = new Propal($db);

	if ($object->fetch($id,$ref) > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$head = propal_prepare_head($object);
		dol_fiche_head($head, 'preview', $langs->trans('Proposal'), 0, 'propal');


		/*
		 *   Propal
		 */
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="5">'.$object->ref.'</td></tr>';

		// Ref client
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
		print '</td>';
		print '</tr></table>';
		print '</td><td colspan="5">';
		print $object->ref_client;
		print '</td>';
		print '</tr>';

		$rowspan=2;

		// Tiers
		print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$soc->getNomUrl(1).'</td>';
		print '</tr>';

		// Ligne info remises tiers
		print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
		if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		$absolute_discount=$soc->getAvailableDiscounts();
		print '. ';
		if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->currency));
		else print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print '.';
		print '</td></tr>';

		// ligne
		// partie Gauche
		print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
		print dol_print_date($object->date,'daytext');
		print '</td>';

		// partie Droite sur $rowspan lignes
		print '<td colspan="2" rowspan="'.$rowspan.'" valign="top" width="50%">';

		/*
		 * Documents
		 */
		$objectref = dol_sanitizeFileName($object->ref);
		$dir_output = $conf->propale->dir_output . "/";
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

			print "<tr $bc[$var]><td>".$langs->trans("Propal")." PDF</td>";

			print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=propal&file='.urlencode($relativepath).'">'.$object->ref.'.pdf</a></td>';

			print '<td align="right">'.dol_print_size(dol_filesize($file)).'</td>';
			print '<td align="right">'.dol_print_date(dol_filemtime($file),'dayhour').'</td>';
			print '</tr>';

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
					$langs->load("errors");
					print '<font class="error">'.$langs->trans("ErrorNoImagickReadimage").'</font>';
				}
			}
		}

		print "</td>";
		print '</tr>';

		print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
		print '<td align="right" colspan="2"><b>'.price($object->price).'</b></td>';
		print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
		print '</table>';
	}
	else
	{
		// Propal non trouvee
		print $langs->trans("ErrorPropalNotFound",$_GET["id"]);
	}
}

// Si fichier png PDF d'1 page trouve
if (file_exists($fileimage))
{
	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercupropal&file='.urlencode($relativepathimage).'">';
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
			print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercupropal&file='.urlencode($preview).'"><p>';
		}
	}
}

print '</div>';

$db->close();

llxFooter();
?>
