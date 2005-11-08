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
   \file       htdocs/telephonie/script/facturation-analyse.php
   \ingroup    telephonie
   \brief      Analyse de la facturation
   \version    $Revision$
*/


require ("../../master.inc.php");

/*
 *
 */

$datetime = time();
$date = strftime("%d%h%Y%Hh%Mm%S",$datetime);
$month = strftime("%m", $datetime);
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

$sql  = "SELECT fk_tarif, fk_client, temporel, fixe, fk_user, datec ";
$sql .= " FROM llx_telephonie_tarif_client_log";
$sql .= " WHERE date_format(datec,'%m%Y')='".$month.year."'";
$sql .= " ORDER BY datec ASC;";

$re2sql = $db->query($sql) ;

if ( $re2sql )
{
  $nu2m = $db->num_rows($re2sql);
  print "$nu2m tarifs modifiés\n";
  $j = 0;
  while ($j < $nu2m)
    {
      $row = $db->fetch_row($re2sql);

      $sqli  = "SELECT fk_tarif, fk_client, temporel, fixe, fk_user, datec ";
      $sqli .= " FROM llx_telephonie_tarif_client_log";
      $sqli .= " WHERE fk_tarif = ".$row[0];
      $sqli .= " AND fk_client = ".$row[1];
      $sqli .= " ORDER BY datec ASC";

      $resqli = $db->query($sqli) ;

      if ($resqli )
	{
	  $numi = $db->num_rows($resqli);      
	  if ($numi > 2)
	    {
	      while ( $rowi = $db->fetch_row($resqli))
		{
		  print $rowi[0]." ".$rowi[2]."\n";
		}
	    }
	}

      $j++;
    }
}
else
{
  print $db->error();
}


$db->close();
?>
