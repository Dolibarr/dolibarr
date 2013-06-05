<?php
/* Copyright (C) 2005 	   Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 	   Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Juanjo Menent 	    <jmenent@2byte.es>
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
 *      \file       htdocs/compta/prelevement/bon.php
 *      \ingroup    prelevement
 *      \brief      Fiche apercu du bon de prelevement
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("bills");
$langs->load("categories");

// Security check
$socid=0;
$id = GETPOST('id','int');
$ref = GETPOST('ref','alpha');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement', $id);


llxHeader('','Bon de prelevement');

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$object = new BonPrelevement($db,"");

	if ($object->fetch($id) == 0)
    {
		$head = prelevement_prepare_head($object);
		dol_fiche_head($head, 'preview', 'Prelevement : '. $object->ref);

		print '<table class="border" width="100%">';

		print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td>'.$object->ref.'</td></tr>';
		print '<tr><td width="20%">'.$langs->trans("Amount").'</td><td>'.price($object->amount).'</td></tr>';
		print '<tr><td width="20%">'.$langs->trans("File").'</td><td>';

		$relativepath = 'bon/'.$object->ref;

		print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart=prelevement&amp;file='.urlencode($relativepath).'">'.$object->ref.'</a>';

		print '</td></tr>';
		print '</table><br>';

		$fileimage = $conf->prelevement->dir_output.'/receipts/'.$object->ref.'.ps.png.0';
		$fileps = $conf->prelevement->dir_output.'/receipts/'.$object->ref.'.ps';

		// Conversion du PDF en image png si fichier png non existant
		if (!file_exists($fileimage))
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

		if (file_exists($fileimage))
		{
			print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=prelevement&file='.urlencode(basename($fileimage)).'">';

		}
	}
	else
	{
		dol_print_error($db);
    }
}

print "</div>";

llxFooter();
?>
