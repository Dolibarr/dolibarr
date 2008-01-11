<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * Script de facturation
 * Verification des factures négatives
 *
 */

/**
   \file       htdocs/telephonie/script/facturation-emission.php
   \ingroup    telephonie
   \brief      Emission des factures
   \version    $Revision$
*/


require ("../../master.inc.php");

$opt = getopt("l:c:");

$limit = $opt['l'];
$optcontrat = $opt['c'];

require_once (DOL_DOCUMENT_ROOT."/societe.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/dolibarrmail.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/facturetel.class.php");
require_once (DOL_DOCUMENT_ROOT."/telephonie/telephonie.contrat.class.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php");


$error = 0;

/*
 * Lecture du batch
 *
 */

$sql = "SELECT MAX(rowid) FROM ".MAIN_DB_PREFIX."telephonie_facturation_batch";

$resql = $db->query($sql);
  
if ( $resql )
{
  $row = $db->fetch_row($resql);

  $batch_id = $row[0];

  $db->free($resql);
}
else
{
  $error = 1;
  dolibarr_syslog("Erreur ".$error);
}

/*
 * Traitements
 *
 */



$dir = "/tmp/";
$error = 0;

$fname = $dir . "alertes-factures.xls";

dolibarr_syslog("Open $fname");
    
$workbook = &new writeexcel_workbook($fname);

$page = &$workbook->addworksheet("Pertes");
  
$fnb =& $workbook->addformat();
$fnb->set_align('vcenter');
$fnb->set_align('right');

$fns =& $workbook->addformat();
$fns->set_align('vcenter');
$fns->set_align('left');

$fnc =& $workbook->addformat();
$fnc->set_align('vcenter');
$fnc->set_align('center');

$fn =& $workbook->addformat();
$fn->set_align('vcenter');

$page->set_column(0,0,36); // A
$page->set_column(1,1,16); // A
$page->set_column(2,2,20); // A

$page->write(1, 0,  "Client", $fns);
$page->write(1, 1,  "Contrat", $fnc);
$page->write(1, 2,  "Ligne", $fnc);
$page->write(1, 3,  "Perte", $fn);
$page->write(1, 4,  "Fournisseur", $fn);
$page->write(1, 5,  "iBreizh", $fn);

if (!$error)
{
  $sql = "SELECT f.fourn_montant, f.cout_vente";
  $sql .= " , c.ref, s.nom, l.ligne as numero";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
  $sql .= " ,    ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
  $sql .= " ,    ".MAIN_DB_PREFIX."telephonie_contrat as c";
  $sql .= " ,    ".MAIN_DB_PREFIX."societe as s";

  $sql .= " WHERE f.fk_facture IS NOT NULL";
  $sql .= " AND f.fk_batch = ".$batch_id;
  $sql .= " AND f.isfacturable = 'oui'"; 
  $sql .= " AND f.fk_ligne = l.rowid ";
  $sql .= " AND l.fk_contrat = c.rowid";  
  $sql .= " AND c.fk_client_comm = s.rowid";
  $sql .= " AND f.fourn_montant > f.cout_vente";
  $sql .= " ORDER BY s.rowid ASC, c.rowid ASC";
    
  $resql = $db->query($sql) ;

  if ( $resql )
    {
      $num = $db->num_rows($resql);      
      $i = 0;
      $j = 2;
      
      while ($i < $num)
	{
	  $obj = $db->fetch_object($resql);

	  if ($i == 0)
	    {
	      $oldc = $obj->ref;
	      $total = 0;
	    }

	  if ($oldc <> $obj->ref)
	    {
	      $page->write($j, 3,  $total, $fnb);
	      $total = 0;
	      $j++;
	      $oldc = $obj->ref;
	    }

	  $page->write_string($j, 0,  $obj->nom, $fns);
	  $page->write_string($j, 1,  $obj->ref, $fnc);
	  $page->write_string($j, 2,  $obj->numero, $fnc);

	  $perte = ($obj->fourn_montant - $obj->cout_vente);
	  $total += $perte;

	  $page->write($j, 3,  $perte, $fn);
	  $page->write($j, 4,  $obj->fourn_montant, $fn);
	  $page->write($j, 5,  $obj->cout_vente, $fn);


	  $j++;
	  $i++;

	  print $obj->nom . " " . $perte ."\n";

	}            
      $db->free();
    }
  else
    {
      $error = 2;
      dolibarr_syslog("Erreur $error ".$db->error());
    }
}
/*
 *
 *
 */

if (!$error)
{
  $sql = "SELECT distinct(s.rowid), s.nom";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
  $sql .= " ,    ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
  $sql .= " ,    ".MAIN_DB_PREFIX."telephonie_contrat as c";
  $sql .= " ,    ".MAIN_DB_PREFIX."societe as s";

  $sql .= " WHERE f.fk_facture IS NOT NULL";
  $sql .= " AND f.fk_batch = ".$batch_id;
  $sql .= " AND f.isfacturable = 'oui'"; 
  $sql .= " AND f.fk_ligne = l.rowid ";
  $sql .= " AND l.fk_contrat = c.rowid";  
  $sql .= " AND c.fk_client_comm = s.rowid";
  $sql .= " AND f.fourn_montant > f.cout_vente";
  $sql .= " GROUP BY s.nom ASC";

  $resql = $db->query($sql) ;

  if ( $resql )
    {
      $num = $db->num_rows($resql);      
      $i = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);

	  $page = &$workbook->addworksheet($row[1]);
  
	  $fnb =& $workbook->addformat();
	  $fnb->set_align('vcenter');
	  $fnb->set_align('right');
	  
	  $fns =& $workbook->addformat();
	  $fns->set_align('vcenter');
	  $fns->set_align('left');
	  
	  $fnc =& $workbook->addformat();
	  $fnc->set_align('vcenter');
	  $fnc->set_align('center');
	  
	  $fn =& $workbook->addformat();
	  $fn->set_align('vcenter');
	  
	  $page->set_column(0,0,10); // A
	  $page->set_column(1,1,16); // A
	  $page->set_column(2,2,22); // A
	  
	  $clients = array();
	  
	  $page->write(1, 0,  "Date", $fnc);
	  $page->write(1, 1,  "Contrat", $fnc);
	  $page->write(1, 2,  "Ligne", $fnc);
	  $page->write(1, 3,  "Perte", $fn);
	  $page->write(1, 4,  "Fournisseur", $fn);
	  $page->write(1, 5,  "iBreizh", $fn);


	  $sql = "SELECT f.fourn_montant, f.cout_vente, f.date";
	  $sql .= " , c.ref, s.nom, l.ligne as numero";
	  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
	  $sql .= " ,    ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
	  $sql .= " ,    ".MAIN_DB_PREFIX."telephonie_contrat as c";
	  $sql .= " ,    ".MAIN_DB_PREFIX."societe as s";
	  
	  $sql .= " WHERE f.fk_facture IS NOT NULL";
	  $sql .= " AND s.rowid = ".$row[0];
	  $sql .= " AND f.isfacturable = 'oui'"; 
	  $sql .= " AND f.fk_ligne = l.rowid ";
	  $sql .= " AND l.fk_contrat = c.rowid";  
	  $sql .= " AND c.fk_client_comm = s.rowid";
	  $sql .= " ORDER BY f.date DESC";
	  
	  $re2sql = $db->query($sql) ;
	  
	  if ( $re2sql )
	    {
	      $nu2m = $db->num_rows($re2sql);      
	      $j = 0;
	      $k=2;
	      while ($j < $nu2m)
		{
		  $obj = $db->fetch_object($re2sql);
		  
		  $page->write_string($k, 0,  $obj->date, $fns);
		  $page->write_string($k, 1,  $obj->ref, $fnc);
		  $page->write_string($k, 2,  $obj->numero, $fnc);
		  
		  $perte = ($obj->cout_vente - $obj->fourn_montant );
		  $total += $perte;
		  
		  $ki = $k+1;

		  $page->write($k, 3,  "=E$ki-F$ki", $fn);
		  $page->write($k, 4,  $obj->fourn_montant, $fn);
		  $page->write($k, 5,  $obj->cout_vente, $fn);
		  
		  $k++;
		  $j++;
		}
	    }

	  
	  $i++;

	  print $obj->nom . " " . $perte ."\n";

	}            

    }
  else
    {
      $error = 2;
      dolibarr_syslog("Erreur $error ".$db->error());
    }
}




/*
 *
 *
 */
$workbook->close();
$db->close();
?>
