<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
		\file		htdocs/commande/apercu.php
		\ingroup	commande
		\brief		Page de l'onglet aperçu d'une commande
		\version	$Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");

if (!$user->rights->commande->lire)
	accessforbidden();

$langs->load('orders');
$langs->load('propal');
$langs->load("bills");
$langs->load('compta');
$langs->load('sendings');


require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');

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
	$commande = new Commande($db);

	if ( $commande->fetch($_GET["id"], $user->societe_id) > 0)
		{
		$soc = new Societe($db, $commande->socid);
		$soc->fetch($commande->socid);


		$head = commande_prepare_head($commande);
        dolibarr_fiche_head($head, 'preview', $langs->trans("CustomerOrder"));


		/*
		 *   Commande
		 */
		$sql = 'SELECT s.nom, s.rowid, c.amount_ht, c.fk_projet, c.remise, c.tva, c.total_ttc, c.ref, c.fk_statut, '.$db->pdate('c.date_commande').' as dp, c.note,';
		$sql.= ' c.fk_user_author, c.fk_user_valid, c.fk_user_cloture, c.date_creation, c.date_valid, c.date_cloture';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'commande as c';
		$sql.= ' WHERE c.fk_soc = s.rowid';
		$sql.= ' AND c.rowid = '.$commande->id;
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
		        print '<td colspan="2">'.$commande->ref.'</td>';
		        print '<td width="50%">'.$langs->trans("Source").' : '.$commande->getLabelSource();
		        if ($commande->source == 0)
		        {
		            // Propale
		            $propal = new Propal($db);
		            $propal->fetch($commande->propale_id);
		            print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
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
				print $commande->ref_client;
		        print '</td>';
		        $nbrow=6;
				print '<td rowspan="'.$nbrow.'" valign="top">';

				/*
  				 * Documents
 				 */
				$commanderef = sanitize_string($commande->ref);
				$dir_output = $conf->commande->dir_output . "/";
				$filepath = $dir_output . $commanderef . "/";
				$file = $filepath . $commanderef . ".pdf";
				$filedetail = $filepath . $commanderef . "-detail.pdf";
				$relativepath = "${commanderef}/${commanderef}.pdf";
				$relativepathdetail = "${commanderef}/${commanderef}-detail.pdf";

                // Chemin vers png aperçus
				$relativepathimage = "${commanderef}/${commanderef}.pdf.png";
				$fileimage = $file.".png";          // Si PDF d'1 page
				$fileimagebis = $file.".png.0";     // Si PDF de plus d'1 page

				$var=true;

				// Si fichier PDF existe
				if (file_exists($file))
				{
					$encfile = urlencode($file);
					print_titre($langs->trans("Documents"));
					print '<table class="border" width="100%">';

					print "<tr $bc[$var]><td>".$langs->trans("Order")." PDF</td>";

					print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=commande&file='.urlencode($relativepath).'">'.$commande->ref.'.pdf</a></td>';
					print '<td align="right">'.filesize($file). ' bytes</td>';
					print '<td align="right">'.dolibarr_print_date(filemtime($file),'dayhour').'</td>';
					print '</tr>';

					// Si fichier detail PDF existe
					if (file_exists($filedetail)) { // commande détaillée supplémentaire
						print "<tr $bc[$var]><td>Commande détaillée</td>";

						print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=commande&file='.urlencode($relativepathdetail).'">'.$commande->ref.'-detail.pdf</a></td>';
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
		        print "<td colspan=\"2\">".$commande->getLibStatut(4)."</td>\n";
		        print '</tr>';

		        // Date
		        print '<tr><td>'.$langs->trans("Date").'</td>';
		        print "<td colspan=\"2\">".dolibarr_print_date($commande->date,"daytext")."</td>\n";
		        print '</tr>';

				// ligne 6
				// partie Gauche
				print '<tr><td height="10" nowrap>'.$langs->trans('GlobalDiscount').'</td>';
				print '<td colspan="2">'.$commande->remise_percent.'%</td>';
				print '</tr>';

				// ligne 7
				// partie Gauche
				print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
				print '<td align="right" colspan="1"><b>'.price($commande->total_ht).'</b></td>';
				print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
				print '</table>';
			}
		} else {
			dolibarr_print_error($db);
		}
	} else {
	// Commande non trouvée
	print $langs->trans("ErrorPropalNotFound",$_GET["id"]);
	}
}

// Si fichier png PDF d'1 page trouvé
if (file_exists($fileimage))
	{
	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercucommande&file='.urlencode($relativepathimage).'">';
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
      	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercucommande&file='.urlencode($preview).'"><p>';
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
