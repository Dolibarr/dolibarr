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
 * $Source$
 */

$thermlib = "../../lib/thermometer.php";

if (file_exists ($thermlib))
{
  include($thermlib);

  $posten_file = "/var/www/www.eucd.info/htdocs/posten.txt";
  $totaal_file = "/var/www/www.eucd.info/htdocs/totaal.txt";


  /*
   * Read Values
   */

  if (file_exists ($posten_file))
    {
      if (file_exists ($totaal_file))
	{

	  /* lees posten uit file */
	  $fp = fopen($posten_file, 'r' );
	  
	  
	  if ($fp)
	    {
	      $post_donaties = fgets( $fp, 10 );
	      $post_sponsoring = fgets( $fp, 10 );
	      $post_intent = fgets( $fp, 10 );
	      fclose( $fp ); 
	    }
	  
	  /* lees posten uit file  */
	  $fp = fopen( $totaal_file, 'r' );
	  if ($fp)
	    {
	      $totaal_ontvangen = fgets( $fp, 10 );
	      $totaal_pending = fgets( $fp, 10 );
	      fclose( $fp ); 
	    }
	}
    }
  
  /* 
   * Graph thermometer
   */

  $conf = new Conf();
  $db = new Db();
  $don = new Don($db);

  $actualValue = $don->sum_actual();
  $pendingValue = $don->sum_pending();
  $intentValue = $don->sum_intent();
 
  print moneyMeter($actualValue, $pendingValue, $intentValue);

}
?>
