<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Éric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
		\file		htdocs/comm/propal/apercu.php
		\ingroup	propal
		\brief		Page de l'onglet aperçu d'une propal
		\version	$Revision$
*/

require("./pre.inc.php");

$user->getrights('propale');

if (!$user->rights->propale->lire)
	accessforbidden();

$langs->load('propal');
$langs->load("bills");


require_once(DOL_DOCUMENT_ROOT.'/comm/propal_model_pdf.class.php');
require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');
if ($conf->projet->enabled) {
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

if ($_GET["propalid"] > 0) {
	$propal = new Propal($db);

	if ( $propal->fetch($_GET["propalid"], $user->societe_id) > 0)
		{
		$soc = new Societe($db, $propal->socidp);
		$soc->fetch($propal->socidp);

		$h=0;

		$head[$h][0] = DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans('CommercialCard');
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/compta/propal.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans('AccountancyCard');
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/apercu.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans("Preview");
		$hselected=$h;
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/note.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans('Note');
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/info.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans('Info');
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/comm/propal/document.php?propalid='.$propal->id;
		$head[$h][1] = $langs->trans('Documents');
		$h++;

		dolibarr_fiche_head($head, $hselected, $langs->trans('Proposal').': '.$propal->ref);


		/*
		*   Propal
		*/
		$sql = 'SELECT s.nom, s.idp, p.price, p.fk_projet, p.remise, p.tva, p.total, p.ref, p.fk_statut, '.$db->pdate('p.datep').' as dp, p.note,';
		$sql.= ' x.firstname, x.name, x.fax, x.phone, x.email, p.fk_user_author, p.fk_user_valid, p.fk_user_cloture, p.datec, p.date_valid, p.date_cloture';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'propal as p, '.MAIN_DB_PREFIX.'socpeople as x';
		$sql.= ' WHERE p.fk_soc = s.idp AND p.fk_soc_contact = x.idp AND p.rowid = '.$propal->id;
		if ($socidp) $sql .= ' AND s.idp = '.$socidp;

		$result = $db->query($sql);




		if ($result) {
			if ($db->num_rows($result)) {
				$obj = $db->fetch_object($result);

				$societe = new Societe($db);
				$societe->fetch($obj->idp);

				print '<table class="border" width="100%">';
				$rowspan=3;
				// ligne 1
				// partie Gauche
				print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">';
				if ($societe->client == 1) {
					$url ='fiche.php?socid='.$societe->id;
				} else {
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
				print dolibarr_print_date($propal->date,'%a %e %B %Y');
				print '</td>';

				// partie Droite
				print '<td>'.$langs->trans('DateEndPropal').'</td><td>';
				if ($propal->fin_validite) {
					print dolibarr_print_date($propal->fin_validite,'%a %d %B %Y');
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
					if (!empty($propal->contactid)) {
						require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
						$contact=new Contact($db);
						$contact->fetch($propal->contactid);
						print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$propal->contactid.'" title="'.$langs->trans('ShowContact').'">';
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
				*
				*/
				$propalref = sanitize_string($propal->ref);
				$file = $conf->propal->dir_output . "/" . $propalref . "/" . $propalref . ".pdf";
				$filedetail = $conf->propal->dir_output . "/" . $propalref . "/" . $propalref . "-detail.pdf";
				$relativepath = "${propalref}/${propalref}.pdf";
				$relativepathdetail = "${propalref}/${propalref}-detail.pdf";
				$relativepathimage = "${propalref}/${propalref}.pdf.png";

				$fileimage = $file.".png";

				$var=true;

				// Si fichier PDF existe
				if (file_exists($file)) {
					$encfile = urlencode($file);
					print_titre($langs->trans("Documents"));
					print '<table class="border" width="100%">';

					print "<tr $bc[$var]><td>".$langs->trans("Propal")." PDF</td>";

					print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=propal&file='.urlencode($relativepath).'">'.$propal->ref.'.pdf</a></td>';
					print '<td align="right">'.filesize($file). ' bytes</td>';
					print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
					print '</tr>';

					// Si fichier detail PDF existe
					if (file_exists($filedetail)) { // propal détaillée supplémentaire
						print "<tr $bc[$var]><td>Propal détaillée</td>";

						print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart=propal&file='.urlencode($relativepathdetail).'">'.$propal->ref.'-detail.pdf</a></td>';
						print '<td align="right">'.filesize($filedetail). ' bytes</td>';
						print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($filedetail)).'</td>';
						print '</tr>';
					}
					print "</table>\n";
					// Conversion du PDF en image png si fichier png non existant
					if (!file_exists($fileimage)) {
						if (function_exists(imagick_readimage)) {
							$handle = imagick_readimage( $file ) ;
							if ( imagick_iserror( $handle ) ) {
								$reason      = imagick_failedreason( $handle ) ;
								$description = imagick_faileddescription( $handle ) ;

								print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n";
							}
							imagick_convert( $handle, "PNG" ) ;
							if ( imagick_iserror( $handle ) ) {
								$reason      = imagick_failedreason( $handle ) ;
								$description = imagick_faileddescription( $handle ) ;
								print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n";
							}
							imagick_writeimage( $handle, $file .".png");
						} else {
							$langs->load("other");
							print $langs->trans("ErrorNoImagickReadimage");
						}
					}
				}
				print "</td></tr>";


				// ligne 4
				// partie Gauche
				print '<tr><td height="10" nowrap>'.$langs->trans('GlobalDiscount').'</td>';
				print '<td colspan="3">'.$propal->remise_percent.'%</td>';
				print '</tr>';

				// ligne 5
				// partie Gauche
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

if (file_exists($fileimage))
	{
	print '<img src="'.DOL_URL_ROOT . '/viewimage.php?modulepart=apercupropal&file='.urlencode($relativepathimage).'">';
	}
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
