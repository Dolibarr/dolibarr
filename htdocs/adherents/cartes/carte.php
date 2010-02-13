<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *	\file 		htdocs/adherents/cartes/carte.php
 *	\ingroup    adherent
 *	\brief      Page to output members business cards
 *	\version    $Id$
 */
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/member/cards/modules_cards.php");


// liste des patterns remplacable dans le texte a imprimer
$patterns = array (
'/%PRENOM%/',
'/%NOM%/',
'/%LOGIN%/',
'/%SERVEUR%/',
'/%SOCIETE%/',
'/%ADRESSE%/',
'/%CP%/',
'/%VILLE%/',
'/%PAYS%/',
'/%EMAIL%/',
'/%NAISS%/',
'/%TYPE%/',
'/%ID%/',
'/%ANNEE%/',	// For backward compatibility
'/%YEAR%/',
'/%MONTH%/',
'/%DAY%/'
);


// Choix de l'annee d'impression ou annee courante.
$now = dol_now();
$year=dol_print_date($now,'%Y');
$month=dol_print_date($now,'%m');
$day=dol_print_date($now,'%d');


$arrayofmembers=array();

// requete en prenant que les adherents a jour de cotisation
$sql = "SELECT d.rowid, d.prenom, d.nom, d.login, d.societe, d.datefin,";
$sql.= " d.adresse, d.cp, d.ville, d.naiss, d.email, d.photo,";
$sql.= " t.libelle as type,";
$sql.= " p.libelle as pays";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON d.pays = p.rowid";
$sql.= " WHERE d.fk_adherent_type = t.rowid AND d.statut = 1";
$sql.= " ORDER BY d.rowid ASC";

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);

		if ($objp->pays == '-') $objp->pays='';

		// List of values to scan for a replacement
		$replace = array (
		$objp->prenom,
		$objp->nom,
		$objp->login,
		"http://".$_SERVER["SERVER_NAME"]."/",
		$objp->societe,
		$objp->adresse,
		$objp->cp,
		$objp->ville,
		$objp->pays,
		$objp->email,
		$objp->naiss,
		$objp->type,
		$objp->rowid,
		$year,
		$year,
		$month,
		$day
		);

		$textleft=preg_replace ($patterns, $replace, $conf->global->ADHERENT_CARD_TEXT);
		$textheader=preg_replace ($patterns, $replace, $conf->global->ADHERENT_CARD_HEADER_TEXT);
		$textfooter=preg_replace ($patterns, $replace, $conf->global->ADHERENT_CARD_FOOTER_TEXT);
		$textright=preg_replace ($patterns, $replace, $conf->global->ADHERENT_CARD_TEXT_RIGHT);

		$arrayofmembers[]=array('textleft'=>$textleft,
								'textheader'=>$textheader,
								'textfooter'=>$textfooter,
								'textright'=>$textright,
								'id'=>$objp->rowid,
								'photo'=>$objp->photo);

		$i++;
	}

	// Build and output PDF
	$result=members_card_pdf_create($db, $arrayofmembers, '', $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
}
else
{
	dol_print_error($db);

	llxFooter('$Date$ - $Revision$');
}

?>
