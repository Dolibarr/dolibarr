<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
		\file		htdocs/comm/propal/apercu.php
		\ingroup	propal
		\brief		Page de l'onglet aperçu d'une propal
		\version	$Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");
if ($conf->projet->enabled) {
	require_once(DOL_DOCUMENT_ROOT."/project.class.php");
}

$langs->load('propal');
$langs->load("bills");
$langs->load('compta');

$propalid = isset($_GET["propalid"])?$_GET["propalid"]:'';

// Sécurité d'accès client et commerciaux
$socid = restrictedArea($user, 'propale', $propalid, 'propal');

llxHeader();

$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */

if ($_GET["propalid"] > 0)
{
	$propal = new Propal($db);

	if ( $propal->fetch($_GET["propalid"], $user->societe_id) > 0)
	{
		$soc = new Societe($db, $propal->socid);
		$soc->fetch($propal->socid);

		$head = propal_prepare_head($propal);
		dolibarr_fiche_head($head, 'preview', $langs->trans('Proposal'));


		/*
		*   Propal
		*/
		$sql = 'SELECT s.nom, s.rowid, p.price, p.fk_projet, p.remise, p.tva, p.total, p.ref, p.fk_statut, '.$db->pdate('p.datep').' as dp, p.note,';
		$sql.= ' p.fk_user_author, p.fk_user_valid, p.fk_user_cloture, p.datec, p.date_valid, p.date_cloture';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'propal as p';
		$sql.= ' WHERE p.fk_soc = s.rowid AND p.rowid = '.$propal->id;

		$result = $db->query($sql);

		if ($result) {
			if ($db->num_rows($result)) {
				$obj = $db->fetch_object($result);

				$societe = new Societe($db);
				$societe->fetch($obj->rowid);

				print '<table class="border" width="100%">';

				// Ref
		        print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td colspan="5">'.$propal->ref.'</td></tr>';

				// Ref client
				print '<tr><td>';
				print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
				print $langs->trans('RefCustomer').'</td><td align="left">';
				print '</td>';
				print '</tr></table>';
				print '</td><td colspan="5">';
				print $propal->ref_client;
				print '</td>';
				print '</tr>';

				$rowspan=2;

				// Tiers
				print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$societe->getNomUrl(1).'</td>';
				print '</tr>';

				// Ligne info remises tiers
			    print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
				if ($societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$societe->remise_client);
				else print $langs->trans("CompanyHasNoRelativeDiscount");
				$absolute_discount=$societe->getAvailableDiscounts();
				print '. ';
				if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
				else print $langs->trans("CompanyHasNoAbsoluteDiscount");
				print '.';
				print '</td></tr>';

				// ligne
				// partie Gauche
				print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
				print dolibarr_print_date($propal->date,'daytext');
				print '</td>';

				// partie Droite sur $rowspan lignes
				print '<td colspan="2" rowspan="'.$rowspan.'" valign="top" width="50%">';

				/*
  				 * Documents
 				 */
				$propalref = sanitize_string($propal->ref);
				$dir_output = $conf->propal->dir_output . "/";
				$filepath = $dir_output . $propalref . "/";
				$file = $filepath . $propalref . ".pdf";
				$filedetail = $filepath . $propalref . "-detail.pdf";
				$relativepath = "${propalref}/${propalref}.pdf";
				$relativepathdetail = "${propalref}/${propalref}-detail.pdf";

                // Chemin vers png aperçus
				$relativepathimage = "${propalref}/${propalref}.pdf.png";
				$fileimage = $file.".png";          // Si PDF d'1 page
				$fileimagebis = $file.".png.0";     // Si PDF de plus d'1 page

				$var=true;

				// Si fichier PDF existe
				if (file_exists($file))
				{
					$encfile = urlencode($file);
					print_titre($langs->trans("Documents"));
					print '<table class="border" width="100%">';

					print "<tr $bc[$var]><td>".$langs->trans("Propal")." PDF</td>";

					print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=propal&file='.urlencode($relativepath).'">'.$propal->ref.'.pdf</a></td>';
					print '<td align="right">'.filesize($file). ' bytes</td>';
					print '<td align="right">'.dolibarr_print_date(filemtime($file),'dayhour').'</td>';
					print '</tr>';

					// Si fichier detail PDF existe
					if (file_exists($filedetail)) { // propal détaillée supplémentaire
						print "<tr $bc[$var]><td>Propal détaillée</td>";

						print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=propal&file='.urlencode($relativepathdetail).'">'.$propal->ref.'-detail.pdf</a></td>';
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
				
				print "</td>";
				print '</tr>';

				print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
				print '<td align="right" colspan="2"><b>'.price($propal->price).'</b></td>';
				print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
				print '</table>';
			}
		} else {
			dolibarr_print_error($db);
		}
	} else {
	// Propal non trouvée
	print $langs->trans("ErrorPropalNotFound",$_GET["propalid"]);
	}
}

// Si fichier png PDF d'1 page trouvé
if (file_exists($fileimage))
	{
	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercupropal&file='.urlencode($relativepathimage).'">';
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
      	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercupropal&file='.urlencode($preview).'"><p>';
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
