<?php
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

$thermlib = "../../lib/thermometer.php";

if (file_exists ($thermlib))
{
  include($thermlib);

  if (1)
    {


      $dons_file = "/var/run/dolibarr.don.eucd";

      /*
       * Read Values
       */
      
      if (file_exists ($dons_file))
	{
	  
	  $fp = fopen($dons_file, 'r' );
	  
	  
	  if ($fp)
	    {
	      $intentValue = fgets( $fp, 10 );
	      $pendingValue = fgets( $fp, 10 );
	      $actualValue = fgets( $fp, 10 );
	      fclose( $fp ); 
	    }
	  
	}
      
    }
  else
    {



      /*
       * Read Values
       */
      
      $conf = new Conf();
    $conf->db->type = $dolibarr_main_db_type;
    $conf->db->port = $dolibarr_main_db_port;
    $conf->db->host = $dolibarr_main_db_host;
    $conf->db->name = $dolibarr_main_db_name;
    $conf->db->user = $dolibarr_main_db_user;
    $conf->db->pass = $dolibarr_main_db_pass;

      $dbt = new DoliDb($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

      $dontherm = new Don($dbt);
      
      $intentValue  = $dontherm->sum_donations(1);
      $pendingValue = $dontherm->sum_donations(2);
      $actualValue  = $dontherm->sum_donations(3);

      $dbt->close();
    }


  /* 
   * Graph thermometer
   */
  
  print moneyMeter($actualValue, $pendingValue, $intentValue);


}
?>
