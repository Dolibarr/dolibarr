<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file 		htdocs/adherents/cartes/carte.php
 \ingroup    adherent
 \brief      Page de creation d'une carte PDF
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/cartes/PDF_card.class.php");


// liste des patterns remplacable dans le texte a imprimer
$patterns = array (
'/%PRENOM%/',
'/%NOM%/',
'/%SERVEUR%/',
'/%SOCIETE%/',
'/%ADRESSE%/',
'/%CP%/',
'/%VILLE%/',
'/%PAYS%/',
'/%EMAIL%/',
'/%NAISS%/',
'/%PHOTO%/',
'/%TYPE%/',
'/%ID%/',
'/%ANNEE%/'
		   );

/*
 *-------------------------------------------------
 * Pour cr�er l'objet on a 2 moyens :
 * Soit on donne les valeurs en les passant dans un tableau (sert pour un format personnel)
 * Soit on donne le type d'�tiquette au format AVERY
 *-------------------------------------------------
 */

//$pdf = new PDF_Label(array('name'=>'perso1', 'marginLeft'=>1, 'marginTop'=>1, 'NX'=>2, 'NY'=>7, 'SpaceX'=>0, 'SpaceY'=>0, 'width'=>99.1, 'height'=>'38.1', 'metric'=>'mm', 'font-size'=>14), 1, 2);
$pdf = new PDF_card('CARD', 1, 1);

$pdf->Open();
$pdf->AddPage();

// Choix de l'annee d'impression ou annee courante.
if (!isset($annee)){
	$now = getdate();
	$annee=$now['year'];
}

// requete en prenant que les adherents a jour de cotisation
$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, ".$db->pdate("d.datefin")." as datefin,";
$sql.= " d.adresse, d.cp, d.ville, d.naiss, d.email, d.photo,";
$sql.= " t.libelle as type,";
$sql.= " p.libelle as pays";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON d.pays = p.rowid";
$sql.= " WHERE d.fk_adherent_type = t.rowid AND d.statut = 1 AND datefin >= ".$db->idate(mktime());
$sql.= " ORDER BY d.rowid ASC";

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);
		// attribut a remplacer
		$replace = array (
		ucfirst(strtolower($objp->prenom)),
		strtoupper($objp->nom),
		"http://".$_SERVER["SERVER_NAME"]."/",
		$objp->societe,
		ucwords(strtolower($objp->adresse)),
		$objp->cp,
		strtoupper($objp->ville),
		ucfirst(strtolower($objp->pays)),
		$objp->email,
		$objp->naiss,
		$objp->photo,
		$objp->type,
		$objp->rowid,
		$annee
		);

		// imprime le texte specifique sur la carte
		$pdf->Add_PDF_card(preg_replace ($patterns, $replace, $conf->global->ADHERENT_CARD_TEXT),
		preg_replace ($patterns, $replace, $conf->global->ADHERENT_CARD_HEADER_TEXT),
		preg_replace ($patterns, $replace, $conf->global->ADHERENT_CARD_FOOTER_TEXT));
		$i++;
	}

	$db->close();
	
	// Output to http strem
	$pdf->Output();
}
else
{
	dolibarr_print_error($db);

	llxFooter('$Date$ - $Revision$');
}

?>
