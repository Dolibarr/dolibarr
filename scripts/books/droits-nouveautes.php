#!/usr/bin/php
<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       scripts/books/droits-editeurs.php
 *  \ingroup    editeurs
 *  \brief      Script de generation des courriers pour les editeurs
 * 	\version	$Id$
 */

require_once("../../htdocs/master.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_workbook.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_worksheet.inc.php");

$error = 0;

$year = strftime('%Y', time());

$fname = "/tmp/droits-nouveautes-".$year.".xls";

$workbook = &new writeexcel_workbook($fname);

$page = &$workbook->addworksheet("Droits nouveautes ".$year);

$fcent =& $workbook->addformat(array(
				     'align' => 'center',
				     'border' => 1
				     ));

$fdroit =& $workbook->addformat(array(
				      'bg_color' => 0x0A,
				      'color' => 0x09,
				      'bold' => 1,
				      'align'  => 'center',
				      'border' => 1
				      ));

$fvent =& $workbook->addformat(array(
				      'bg_color' => 0x35,
				      'bold' => 1,
				      'align'  => 'center'
				      ));



$fdroits=& $workbook->addformat(array(
				      'bg_color' => 24,
				      'bold' => 1,
				      'align'  => 'center',
				      'border' => 1
				      ));

$page->set_column(0,0,10); // A
$page->set_column(1,1,12); // B
$page->set_column(2,2,15); // C
$page->set_column(3,3,9); // C
$page->set_column(4,8,8);


$sql = "SELECT fd.fk_product, sum(fd.qty), date_format(f.datef,'%c')>6";
$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as fd,".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE fd.fk_facture=f.rowid";
$sql .= " AND date_format(f.datef,'%Y') ='".$year."'";
$sql .= " GROUP BY fd.fk_product, date_format(f.datef,'%c')>6";
$ventes = array();
$resql=$db->query($sql);
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $ventes[$row[0]][$row[2]] = $row[1];
    }
  $db->free($resql);
}
else
{
  print "Error ";
}




$sql = "SELECT p.rowid, p.ref,p.label, p.price_ttc as pv, s.nom as fournisseur";
$sql .= ",c.quantite as droits, c.taux";
$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
$sql .= ", ".MAIN_DB_PREFIX."product_cnv_livre as pl";
$sql .= ", ".MAIN_DB_PREFIX."product_cnv_livre_contrat as c";

$sql .= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";

$sql .= " WHERE p.rowid = pl.rowid";
$sql .= " AND pf.fk_product = p.rowid AND s.rowid = pf.fk_soc";
$sql .= " AND p.rowid = c.fk_cnv_livre";
$sql .= " AND p.canvas = 'livre'";
$sql .= " ORDER BY p.rowid ASC";

if ($db->query($sql))
{
  $i = 0;
  $j = 1;

  $page->write_string(0, 0,  "Ref", $fcent);
  $page->write_string(0, 1,  "Titre", $fcent);
  $page->write_string(0, 2,  "Nom");
  $page->write_string(0, 3,  "Droits", $fdroit);
  $page->write_string(0, 4,  "Taux", $fcent);
  $page->write_string(0, 5,  "P.V.", $fcent);

  $page->write_string(0, 7,  "Droits\nverses", $fcent);
  $page->write_string(0, 8,  "Soit\npar livre", $fcent);

  $page->write_string(0, 9,  "Ventes\n1er sem", $fvent);
  $page->write_string(0, 10,  "Ventes\n2eme sem", $fvent);
  $page->write_string(0, 11,  "Total\nVentes", $fvent);
  $page->write_string(0, 12,  "Solde", $fcent);

  $page->write_string(0,13,"Droits\na payer",$fdroits);
  $page->write_string(0,14,"Droits payes\ndavance",$fdroits);

  while ($obj = $db->fetch_object())
    {
      $k = $j+1;

      $page->write_string($j, 0,  $obj->ref, $fcent);
      $page->write_string($j, 1,  stripslashes($obj->label), $fcent);
      $page->write_string($j, 2,  stripslashes($obj->fournisseur), $fcent);
      $page->write($j, 3,  $obj->droits, $fdroit);
      $page->write($j, 4,  $obj->taux, $fcent);
      $page->write($j, 5,  $obj->pv, $fcent);

      $page->write_formula($j,6,"=F$k/1.055",$fcent);
      $page->write_formula($j,7,"=F$k*E$k*D$k/100",$fcent);
      $page->write_formula($j,8,"=H$k/D$k",$fcent);

      $page->write_number($j,9,  $ventes[$obj->rowid][0],$fvent);
      $page->write_number($j,10, $ventes[$obj->rowid][1],$fvent);

      $page->write_formula($j,11,"=J$k+K$k",$fvent);
      $page->write_formula($j,12,"=L$k-D$k",$fcent);

      if ($obj->droits < ($ventes[$obj->rowid][0]+$ventes[$obj->rowid][1]))
	{
	  $page->write_formula($j,13,"=M$k*I$k",$fdroits);
	  $page->write_string($j,14,'',$fdroits);
	}
      else
	{
	  $page->write_string($j,13,'',$fdroits);
	  $page->write_formula($j,14,"=M$k*I$k",$fdroits);
	}

      $j++;
      $i++;

    }
}
else
{
  print "Error ";
}

$workbook->close();
?>
