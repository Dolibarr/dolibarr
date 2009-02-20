<?PHP
/* Copyright (C) 2005-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Analyse de la facturation
 *
 */

/**
   \file       htdocs/telephonie/script/facturation-analyse.php
   \ingroup    telephonie
   \brief      Analyse de la facturation
   \version    $Revision$
*/

require ("../../master.inc.php");

$verbose = 0;
$month = 0;
$year = 0;

//loop through our arguments and see what the user selected
for ($i = 1; $i < sizeof($GLOBALS["argv"]); $i++)
{
  switch($GLOBALS["argv"][$i])
    {
    case "--month":
      $month  = $GLOBALS["argv"][$i+1];
      break;
    case "--year":
      $year  = $GLOBALS["argv"][$i+1];
      break;
    case "-v":
      $verbose = 1;
      break;
    case "-vv":
      $verbose = 2;
      break;
    case "--no-xls":
      $no_xls = 1;
      break;
    case "--version":
      echo  $GLOBALS['argv'][0]." $Revision$\n";
      exit;
      break;
    case "--help":
      print $GLOBALS['argv'][0].
	"\n\t--help\t\tprint this help\n".
	"\t--version\tprint version\n".
	"\t-v\t\tverbose mode\n".
	"\t--month int\n".
	"\t--year  int\n";
	"\t--no-xls  int\n";
      break;
    }
}

/*
 * Analyse ratio cout fournisseur
 *
 */

$datetime = time();
$date = strftime("%d%h%Y%Hh%Mm%S",$datetime);
if ($month == 0)
  $month = strftime("%m", $datetime);
if ($year == 0)
  $year = strftime("%Y", $datetime);

if ($month == 1)
{
  $month = "12";
  $year = $year - 1;
}
else
{
  $month = substr("00".($month - 1), -2) ;
}

$month = substr("00".$month, -2) ;

if ($verbose > 0)
  print "Analyse $month/$year\n";

$sql = "SELECT cd.fk_fournisseur, sum(cd.fourn_montant), sum(cd.cout_vente)";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facture as tf";
$sql .= " ,    ".MAIN_DB_PREFIX."telephonie_communications_details as cd";

$sql .= " WHERE tf.date = '".$year."-".$month."-01'";
$sql .= " AND tf.rowid = cd.fk_telephonie_facture";
$sql .= " GROUP BY cd.fk_fournisseur";

$re2sql = $db->query($sql) ;

if ($verbose > 1)
  print $sql."\n";

if ( $re2sql )
{
  $nu2m = $db->num_rows($re2sql);      
  if ($verbose > 1)
    print "Num $nu2m\n";
  $j = 0;
  while ($j < $nu2m)
    {
      $row = $db->fetch_row($re2sql);

      $sqli = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_analyse_fournisseur";
      $sqli.= " (fk_fournisseur,mois,achat,vente)";
      $sqli .= " VALUES (".$row[0].",'".$year.$month."',".$row[1].",".$row[2].")";

      $resqli = $db->query($sqli) ;

      if ($verbose > 1)
	print $resqli."\n";

      if (! $resqli )
	{
	  print $db->error($resqli);
	}

      $j++;
    }
}
else
{
  print $db->error();
}


if($no_xls)
  exit;

/*
 * Partie 2
 *
 */

require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php");
require_once (DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php");

$error = 0;
$dir = "/tmp/";

$fname = $dir . "facturation-analyse.xls";

if ($verbose > 0)
  dol_syslog("Open $fname");
    
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
