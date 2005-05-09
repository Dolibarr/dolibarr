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

require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php");

$error = 0;
$dir = "/tmp/";

$fname = $dir . "facturation-analyse.xls";

dolibarr_syslog("Open $fname");
    
$workbook = &new writeexcel_workbook($fname);

$page = &$workbook->addworksheet("Analyse");

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
$page->set_column(1,4,16); // A

$clients = array();

$page->write(1, 0,  "Date", $fnc);
$page->write(1, 1,  "Gain", $fnc);
$page->write(1, 2,  "CA iBreizh", $fnc);
$page->write(1, 3,  "Coût fournisseurs", $fnc);



$sql = "SELECT sum(f.fourn_montant) as fourn_montant, sum(f.cout_vente) as cout_vente, f.date";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as f";
$sql .= " ,    ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " ,    ".MAIN_DB_PREFIX."telephonie_contrat as c";

$sql .= " WHERE f.fk_facture IS NOT NULL";
$sql .= " AND f.isfacturable = 'oui'"; 
$sql .= " AND f.fk_ligne = l.rowid ";
$sql .= " AND l.fk_contrat = c.rowid";  
$sql .= " GROUP BY f.date DESC";

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

      $ki = $k+1;
      $page->write($k, 1,  "=C$ki-D$ki", $fn);

      $page->write($k, 2,  $obj->cout_vente, $fn);
      $page->write($k, 3,  $obj->fourn_montant, $fn);

      
      $k++;
      $j++;
    }
}
else
{
  print $db->error();
}
/*
 *
 *
 */
$workbook->close();
$db->close();
?>
