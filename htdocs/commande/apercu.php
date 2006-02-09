<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
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

$user->getrights('commande');
$user->getrights('expedition');

if (!$user->rights->commande->lire)
	accessforbidden();

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
	$socidp = $user->societe_id;
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
		$soc = new Societe($db, $commande->socidp);
		$soc->fetch($commande->socidp);

		$h=0;

		if ($conf->commande->enabled && $user->rights->commande->lire)
			{
				$head[$h][0] = DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id;
				$head[$h][1] = $langs->trans('OrderCard');
				$h++;
			}

		if ($conf->expedition->enabled && $user->rights->expedition->lire)
			{
				$head[$h][0] = DOL_URL_ROOT.'/expedition/commande.php?id='.$commande->id;
				$head[$h][1] = $langs->trans('SendingCard');
				$h++;
			}
			
		if ($conf->compta->enabled)
			{
				$head[$h][0] = DOL_URL_ROOT.'/compta/commande/fiche.php?id='.$commande->id;
				$head[$h][1] = $langs->trans('ComptaCard');
				$h++;
			}

		if ($conf->use_preview_tabs)
		 {
    		$head[$h][0] = DOL_URL_ROOT.'/commande/apercu.php?id='.$commande->id;
    		$head[$h][1] = $langs->trans("Preview");
    		$hselected=$h;
    		$h++;
      }
        
		$head[$h][0] = DOL_URL_ROOT.'/commande/info.php?id='.$commande->id;
		$head[$h][1] = $langs->trans('Info');
		$h++;

		dolibarr_fiche_head($head, $hselected, $langs->trans('Order').': '.$commande->ref);


		/*
		*   Commande
		*/
/*		
		$sql = 'SELECT s.nom, s.idp, c.amount_ht, c.fk_projet, c.remise, c.tva, c.total_ttc, c.ref, c.fk_statut, '.$db->pdate('c.date_commande').' as dp, c.note,';
		$sql.= ' x.firstname, x.name, x.fax, x.phone, x.email, c.fk_user_author, c.fk_user_valid, c.fk_user_cloture, c.date_creation, c.date_valid, c.date_cloture';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'socpeople as x';
		$sql.= ' WHERE c.fk_soc = s.idp AND c.fk_soc_contact = x.idp AND c.rowid = '.$commande->id;
		if ($socidp) $sql .= ' AND s.idp = '.$socidp;

		$result = $db->query($sql);




		if ($result) {
			if ($db->num_rows($result)) {
				$obj = $db->fetch_object($result);
*/
				$societe = new Societe($db);
				$societe->fetch($obj->idp);

				print '<table class="border" width="100%">';
				$rowspan=3;
				// ligne 1
				// partie Gauche
				print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">';
				if ($societe->client == 1)
				{
                    $url = DOL_URL_ROOT.'/comm/fiche.php?socid='.$societe->id;
				}
				else
				{
					$url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$societe->id;
				}
				print '<a href="'.$url.'">'.$societe->nom.'</a></td>';
				// partie Droite
				print '<td align="left">Conditions de réglement</td>';
				print '<td>'.'&nbsp;'.'</td>';
				print '</tr>';

				// ligne 2
				// partie Gauche
				print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
				print dolibarr_print_date($commande->date,'%a %e %B %Y');
				print '</td>';

				// partie Droite
				print '<td>'.$langs->trans('DateEndPropal').'</td><td>';
				if ($commande->fin_validite) {
					print dolibarr_print_date($commande->fin_validite,'%a %d %B %Y');
				} else {
					print $langs->trans("Unknown");
				}
				print '</td>';
				print '</tr>';

				// Destinataire
				$langs->load('mails');
				// ligne 3
				print '<tr>';
				// partie Gauche
				print '<td>'.$langs->trans('MailTo').'</td>';

				$dests=$societe->contact_array($societe->id);
				$numdest = count($dests);
				print '<td colspan="3">';
				if ($numdest==0) {
					print '<font class="error">Cette societe n\'a pas de contact, veuillez en créer un avant de faire votre proposition commerciale</font><br>';
					print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create&amp;backtoreferer=1">'.$langs->trans('AddContact').'</a>';
				} else {
					if (!empty($commande->contactid)) {
						require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
						$contact=new Contact($db);
						$contact->fetch($commande->contactid);
						print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$commande->contactid.'" title="'.$langs->trans('ShowContact').'">';
						print $contact->firstname.' '.$contact->name;
						print '</a>';
					} else {
						print '&nbsp;';
					}
				}
				print '</td>';

				// partie Droite sur $rowspan lignes
				print '<td colspan="2" rowspan="'.$rowspan.'" valign="top" width="50%">';


				/*
  				 * Documents
 				 */
				$commanderef = sanitize_string($commande->ref);
				$file = $conf->commande->dir_output . "/" . $commanderef . "/" . $commanderef . ".pdf";
				$filedetail = $conf->commande->dir_output . "/" . $commanderef . "/" . $commanderef . "-detail.pdf";
				$relativepath = "${commanderef}/${commanderef}.pdf";
				$relativepathdetail = "${commanderef}/${commanderef}-detail.pdf";

                // Chemin vers png aperçus
				$relativepathimage = "${commanderef}/${commanderef}.pdf.png";
				$relativepathimagebis = "${commanderef}/${commanderef}.pdf.png.0";
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

					print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=commande&file='.urlencode($relativepath).'">'.$commande->ref.'.pdf</a></td>';
					print '<td align="right">'.filesize($file). ' bytes</td>';
					print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
					print '</tr>';

					// Si fichier detail PDF existe
					if (file_exists($filedetail)) { // commande détaillée supplémentaire
						print "<tr $bc[$var]><td>Commande détaillée</td>";

						print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=commande&file='.urlencode($relativepathdetail).'">'.$commande->ref.'-detail.pdf</a></td>';
						print '<td align="right">'.filesize($filedetail). ' bytes</td>';
						print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($filedetail)).'</td>';
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
							imagick_writeimage( $handle, $file .".png");
						} else {
							$langs->load("other");
							print '<font class="error">'.$langs->trans("ErrorNoImagickReadimage").'</font>';
						}
					}
				}
				print "</td></tr>";


				// ligne 4
				// partie Gauche
				print '<tr><td height="10" nowrap>'.$langs->trans('GlobalDiscount').'</td>';
				print '<td colspan="3">'.$commande->remise_percent.'%</td>';
				print '</tr>';

				// ligne 5
				// partie Gauche
				print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
				print '<td align="right" colspan="2"><b>'.price($commande->price).'</b></td>';
				print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
				print '</table>';
/*			}
		} else {
			dolibarr_print_error($db);
*/		}
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
	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercucommande&file='.urlencode($relativepathimagebis).'">';
	}


print '</div>';


// Juste pour éviter bug IE qui réorganise mal div précédents si celui-ci absent
print '<div class="tabsAction">';
print '</div>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
