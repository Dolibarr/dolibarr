<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Commande {
  var $db ;

  var $id ;
  var $nom;

  Function Commande($DB, $id=0) {
    $this->db = $DB;
    $this->id = $id ;

    $this->billing_adr = New Address();
    $this->delivry_adr = New Address();

    $this->total_ot_subtotal = 0;
    $this->total_ot_shipping = 0;
  }  
  /*
   *
   *
   *
   */
  Function fetch ($id) {

    $sql = "SELECT orders_id, customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method, cc_type, cc_owner, cc_number, cc_expires, last_modified, date_purchased, orders_status, orders_date_finished, currency, currency_value";

    
    $sql .= " FROM ".DB_NAME_OSC.".orders WHERE orders_id = $id";

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$array = $this->db->fetch_array();

	$this->id          = $array["orders_id"];
	$this->client_id   = stripslashes($array["customers_id"]);
	$this->client_name = stripslashes($array["customers_name"]);
	

	$this->delivery_adr->name = stripslashes($array["delivery_name"]);
	$this->delivery_adr->street = stripslashes($array["delivery_street_address"]);
	$this->delivery_adr->cp = stripslashes($array["delivery_postcode"]);
	$this->delivery_adr->city = stripslashes($array["delivery_city"]);
	$this->delivery_adr->country = stripslashes($array["delivery_country"]);

	$this->billing_adr->name = stripslashes($array["billing_name"]);
	$this->billing_adr->street = stripslashes($array["billing_street_address"]);
	$this->billing_adr->cp = stripslashes($array["billing_postcode"]);
	$this->billing_adr->city = stripslashes($array["billing_city"]);
	$this->billing_adr->country = stripslashes($array["billing_country"]);

	$this->db->free();

	/*
	 * Totaux
	 */
	$sql = "SELECT value, class ";
	$sql .= " FROM ".DB_NAME_OSC.".orders_total WHERE orders_id = $id";

	$result = $this->db->query($sql)  ;

	if ( $result )
	  {
	    $num = $this->db->num_rows();

	    while ($i < $num)
	      {
		$array = $this->db->fetch_array($i);
		if ($array["class"] == 'ot_total')
		  {
		    $this->total_ot_total = $array["value"];
		  }
		$i++;
	      }
	  }
	else
	  {
	    print $this->db->error();
	  }

      }
    else
      {
	print $this->db->error();
      }
    
    return $result;
  }

}
?>
