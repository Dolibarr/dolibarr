<?php
//define('FPDF_FONTPATH','font/');
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo <jlb@j1b.org>
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
require($GLOBALS["DOCUMENT_ROOT"]."/adherents/pre.inc.php");
require($GLOBALS["DOCUMENT_ROOT"]."/adherent.class.php");
require($GLOBALS["DOCUMENT_ROOT"]."/adherent_type.class.php");
require($GLOBALS["DOCUMENT_ROOT"]."/adherents/adherent_options.class.php");
require($GLOBALS["DOCUMENT_ROOT"]."/cotisation.class.php");
require($GLOBALS["DOCUMENT_ROOT"]."/paiement.class.php");
require($GLOBALS["DOCUMENT_ROOT"]."/adherents/XML-RPC.functions.php");

require_once("../pre.inc.php");
require_once('PDF_card.class.php');

/*-------------------------------------------------
Pour créer l'objet on a 2 moyens :
Soit on donne les valeurs en les passant dans un tableau (sert pour un format personnel)
Soit on donne le type d'étiquette au format AVERY
-------------------------------------------------*/

// Dans cet exemple on va commencer l'impression des étiquettes à partir de la seconde colonne (cf les 2 derniers paramètres 1 et 2)
//$pdf = new PDF_Label(array('name'=>'perso1', 'marginLeft'=>1, 'marginTop'=>1, 'NX'=>2, 'NY'=>7, 'SpaceX'=>0, 'SpaceY'=>0, 'width'=>99.1, 'height'=>'38.1', 'metric'=>'mm', 'font-size'=>14), 1, 2);
//$pdf = new PDF_card('L7163', 1, 2);
$pdf = new PDF_card('CARD', 1, 1);
//$db = new Db();

$pdf->Open();
$pdf->AddPage();

if (!isset($annee)){
  $now = getdate();
  $annee=$now['year'];
}
$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, ".$db->pdate("d.datefin")." as datefin, adresse,cp,ville,pays, t.libelle as type";
$sql .= " , d.email";
$sql .= " FROM llx_adherent as d, llx_adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid AND d.statut = 1 AND datefin > now()";
$sql .= " ORDER BY d.rowid ASC ";

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $pdf->Add_PDF_card(sprintf("%s\n%s\n%s\n%s\n%s, %s\n%s", $objp->type." n° ".$objp->rowid,ucfirst(strtolower($objp->prenom))." ".strtoupper($objp->nom),"<".$objp->email.">", ucwords(strtolower($objp->adresse)), $objp->cp, strtoupper($objp->ville), ucfirst(strtolower($objp->pays))),$annee,"Association FreeLUG http://www.freelug.org/");
      $i++;
    }
  // On imprime les étiquettes
  //  for($i=0;$i<$num;$i++)
  //	$pdf->Add_PDF_Label(sprintf("%s\n%s\n%s\n%s, %s, %s", "Laurent $i", 'Immeuble Titi', 'av. fragonard', '06000', 'NICE', 'FRANCE'));

  $db->close();
  $pdf->Output();
}else{
  llxHeader();
  print "Erreur mysql";
  llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
}
?> 
