<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent        <jmenent@2byte.es>
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
 * 		\file		htdocs/fichinter/apercu.php
 * 		\ingroup	fichinter
 * 		\brief		Page de l'onglet apercu d'une fiche d'intervention
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
if (! empty($conf->projet->enabled))	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load('interventions');


// Security check
$socid=0;
$id = GETPOST('id','int');
$ref = GETPOST('ref','alpha');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'ficheinter', $id, 'fichinter');


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
	$object = new Fichinter($db);

	if ($object->fetch($id,$ref) > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$head = fichinter_prepare_head($object);
		dol_fiche_head($head, 'preview', $langs->trans("InterventionCard"), 0, 'intervention');

		/*
		 *   Fiche intervention
		 */
		print '<table class="border" width="100%">';

    	$linkback = '<a href="'.DOL_URL_ROOT.'/fichinter/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
    
    	// Ref
    	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
    	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
    	print '</td></tr>';

		$nbrow=3;
		// Client
		print "<tr><td>".$langs->trans("Customer")."</td>";
		print '<td>';
		print '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$soc->id.'">'.$soc->name.'</a>';
		print '</td>';
		print '<td rowspan="'.$nbrow.'" valign="top" width="50%">';
		
		/*
		 * Documents
		 */
		$objectref = dol_sanitizeFileName($object->ref);
		$dir_output = $conf->ficheinter->dir_output . "/";
		$filepath = $dir_output . $objectref . "/";
		$file = $filepath . $objectref . ".pdf";
		$filedetail = $filepath . $objectref . "-detail.pdf";
		$relativepath = "${objectref}/${objectref}.pdf";
		$relativepathdetail = "${objectref}/${objectref}-detail.pdf";

        // Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
        $fileimage = $file.'_preview.png';          	// If PDF has 1 page
        $fileimagebis = $file.'_preview-0.pdf.png';     // If PDF has more than one page
        $relativepathimage = $relativepath.'_preview.png';

		$var=true;

		// Si fichier PDF existe
		if (file_exists($file))
		{
			$encfile = urlencode($file);
			print load_fiche_titre($langs->trans("Documents"));
			print '<table class="border" width="100%">';

			print "<tr ".$bc[$var]."><td>".$langs->trans("Intervention")." PDF</td>";

			print '<td><a data-ajax="false" href="'.DOL_URL_ROOT . '/document.php?modulepart=ficheinter&file='.urlencode($relativepath).'">'.$object->ref.'.pdf</a></td>';
			print '<td align="right">'.dol_print_size(dol_filesize($file)).'</td>';
			print '<td align="right">'.dol_print_date(dol_filemtime($file),'dayhour').'</td>';
			print '</tr>';

			// Si fichier detail PDF existe
			if (file_exists($filedetail))
			{
				print "<tr ".$bc[$var]."><td>Fiche d'intervention detaillee</td>";

				print '<td><a data-ajax="false" href="'.DOL_URL_ROOT . '/document.php?modulepart=ficheinter&file='.urlencode($relativepathdetail).'">'.$object->ref.'-detail.pdf</a></td>';
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

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td>';
		print "<td>".$object->getLibStatut(4)."</td>\n";
		print '</tr>';

		// Date
		print '<tr><td>'.$langs->trans("Date").'</td>';
		print "<td>".dol_print_date($object->datec,"daytext")."</td>\n";
		print '</tr>';

		print '</table>';

		dol_fiche_end();
	}
	else
	{
		// Object not found
		print $langs->trans("ErrorFichinterNotFound",$id);
	}
}

// Si fichier png PDF d'1 page trouve
if (file_exists($fileimage))
{
	print '<img style="background: #FFF" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufichinter&file='.urlencode($relativepathimage).'">';
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
			print '<img style="background: #FFF" src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufichinter&file='.urlencode($preview).'"><p>';
		}
	}
}


llxFooter();

$db->close();
