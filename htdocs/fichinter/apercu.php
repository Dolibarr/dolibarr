<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 * $Source$
 */

/**
		\file		htdocs/fichinter/apercu.php
		\ingroup	fichinter
		\brief		Page de l'onglet aperçu d'une fiche d'intervention
		\version	$Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fichinter.lib.php");

if (!$user->rights->ficheinter->lire)
	accessforbidden();

$langs->load('interventions');

require_once(DOL_DOCUMENT_ROOT.'/fichinter/fichinter.class.php');

if ($conf->projet->enabled) 
{
	require_once(DOL_DOCUMENT_ROOT."/project.class.php");
}


/*
 * Sécurité accés client
*/
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

llxHeader();

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($_GET["id"] > 0) {
	$fichinter = new Fichinter($db);

	if ( $fichinter->fetch($_GET["id"], $user->societe_id) > 0)
		{
		$soc = new Societe($db, $fichinter->socid);
		$soc->fetch($fichinter->socid);


		$head = fichinter_prepare_head($fichinter);
    dolibarr_fiche_head($head, 'preview', $langs->trans("InterventionCard"));


		/*
		 *   Fiche intervention
		 */
		$sql = 'SELECT s.nom, s.rowid, fi.fk_projet, fi.ref, fi.description, fi.fk_statut, '.$db->pdate('fi.datei').' as di,';
		$sql.= ' fi.fk_user_author, fi.fk_user_valid, fi.datec, fi.date_valid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'fichinter as fi';
		$sql.= ' WHERE fi.fk_soc = s.rowid';
		$sql.= ' AND fi.rowid = '.$fichinter->id;
		if ($socid) $sql .= ' AND s.rowid = '.$socid;

		$result = $db->query($sql);

		if ($result)
		{
			if ($db->num_rows($result))
			{
				$obj = $db->fetch_object($result);

				$societe = new Societe($db);
				$societe->fetch($obj->rowid);

				print '<table class="border" width="100%">';

		    // Ref
		    print '<tr><td width="18%">'.$langs->trans("Ref")."</td>";
		    print '<td colspan="2">'.$fichinter->ref.'</td>';

		    $nbrow=4;
				print '<td rowspan="'.$nbrow.'" valign="top" width="50%">';

				/*
  			 * Documents
 				 */
				$fichinterref = sanitize_string($fichinter->ref);
				$dir_output = $conf->fichinter->dir_output . "/";
				$filepath = $dir_output . $fichinterref . "/";
				$file = $filepath . $fichinterref . ".pdf";
				$filedetail = $filepath . $fichinterref . "-detail.pdf";
				$relativepath = "${fichinterref}/${fichinterref}.pdf";
				$relativepathdetail = "${fichinterref}/${fichinterref}-detail.pdf";

        // Chemin vers png aperçus
				$relativepathimage = "${fichinterref}/${fichinterref}.pdf.png";
				$fileimage = $file.".png";          // Si PDF d'1 page
				$fileimagebis = $file.".png.0";     // Si PDF de plus d'1 page

				$var=true;

				// Si fichier PDF existe
				if (file_exists($file))
				{
					$encfile = urlencode($file);
					print_titre($langs->trans("Documents"));
					print '<table class="border" width="100%">';

					print "<tr $bc[$var]><td>".$langs->trans("Intervention")." PDF</td>";

					print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=ficheinter&file='.urlencode($relativepath).'">'.$fichinter->ref.'.pdf</a></td>';
					print '<td align="right">'.filesize($file). ' bytes</td>';
					print '<td align="right">'.dolibarr_print_date(filemtime($file),'dayhour').'</td>';
					print '</tr>';

					// Si fichier detail PDF existe
					if (file_exists($filedetail)) { // fichinter détaillée supplémentaire
						print "<tr $bc[$var]><td>Fiche d'intervention détaillée</td>";

						print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=ficheinter&file='.urlencode($relativepathdetail).'">'.$fichinter->ref.'-detail.pdf</a></td>';
						print '<td align="right">'.filesize($filedetail). ' bytes</td>';
						print '<td align="right">'.dolibarr_print_date(filemtime($filedetail),'dayhour').'</td>';
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
						} else {
							$langs->load("other");
							print '<font class="error">'.$langs->trans("ErrorNoImagickReadimage").'</font>';
						}
					}
				}

				print "</td></tr>";
				

		        // Client
		        print "<tr><td>".$langs->trans("Customer")."</td>";
		        print '<td colspan="2">';
		        print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$societe->id.'">'.$societe->nom.'</a>';
		        print '</td>';
		        print '</tr>';

		        // Statut
		        print '<tr><td>'.$langs->trans("Status").'</td>';
		        print "<td colspan=\"2\">".$fichinter->getLibStatut(4)."</td>\n";
		        print '</tr>';

		        // Date
		        print '<tr><td>'.$langs->trans("Date").'</td>';
		        print "<td colspan=\"2\">".dolibarr_print_date($fichinter->date,"daytext")."</td>\n";
		        print '</tr>';

				print '</table>';
			}
		} else {
			dolibarr_print_error($db);
		}
	} else {
	// Intervention non trouvée
	print $langs->trans("ErrorFichinterNotFound",$_GET["id"]);
	}
}

// Si fichier png PDF d'1 page trouvé
if (file_exists($fileimage))
	{
	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufichinter&file='.urlencode($relativepathimage).'">';
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
      	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercufichinter&file='.urlencode($preview).'"><p>';
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
