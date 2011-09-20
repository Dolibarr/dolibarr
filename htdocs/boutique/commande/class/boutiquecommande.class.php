<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/boutique/commande/class/boutiquecommande.class.php
 *	\ingroup    osc
 *	\brief      Fichier de la classe des commandes de la boutique online
 */

require_once(DOL_DOCUMENT_ROOT ."/societe/class/address.class.php");
include_once(DOL_DOCUMENT_ROOT.'/boutique/commande/class/boutiquecommande.class.php');


/**
 *	\class      BoutiqueCommande
 *	\brief      Classe permettant la gestion des commandes OSC
 */
class BoutiqueCommande
{
	var $db ;

	var $id ;
	var $nom;

	function BoutiqueCommande($DB, $id=0)
	{
		$this->db = $DB;
		$this->id = $id ;

		$this->billing_adr = new Address();
		$this->delivry_adr = new Address();

		$this->total_ot_subtotal = 0;
		$this->total_ot_shipping = 0;
	}

	/**
	 *	\brief      Get object and lines from database
	 *	\param      rowid       id of object to load
	 * 	\param		ref			Ref of order
	 *	\return     int         >0 si ok, <0 si ko
	 */
	function fetch ($id,$ref='')
	{
		global $conf;

		$sql = "SELECT orders_id, customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method, cc_type, cc_owner, cc_number, cc_expires, last_modified, date_purchased, orders_status, orders_date_finished, currency, currency_value";
		$sql.= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."orders";
		$sql.= " WHERE orders_id = ".$id;

		$result = $this->db->query($sql);
		if ( $result )
		{
			$array = $this->db->fetch_array($result);

			$this->id          = $array["orders_id"];
			$this->client_id   = stripslashes($array["customers_id"]);
			$this->client_name = stripslashes($array["customers_name"]);

			$this->payment_method = stripslashes($array["payment_method"]);

			$this->date = $this->db->jdate($array["date_purchased"]);

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
			$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."orders_total WHERE orders_id = $id";

			$result = $this->db->query($sql);
			if ( $result )
			{
				$num = $this->db->num_rows($result);

				while ($i < $num)
				{
					$array = $this->db->fetch_array($result);
					if ($array["class"] == 'ot_total')
					{
						$this->total_ot_total = $array["value"];
					}
					if ($array["class"] == 'ot_shipping')
					{
						$this->total_ot_shipping = $array["value"];
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
