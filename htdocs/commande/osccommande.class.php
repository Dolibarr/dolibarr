<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 	\brief		Class order OSCommerce
 *	\version	$Id$
 */

class OscCommande
{
	var $db ;
	
	var $id ;
	var $client_name ;

	
	function OscCommande($DB, $id=0)
	{
		$this->db = $DB;
		$this->id   = $id ;
	}  
	

	/*
	 *
	 *
	 *
	 */
	function fetch ($id)
	{
		$sql = "SELECT o.orders_id, o.customers_name, o.orders_status FROM ".OSC_DB_NAME.".".OSC_DB_TABLE_PREFIX."orders as o";
		$sql .= " WHERE o.orders_id = $id";
	
		$result = $this->db->query($sql) ;
	
		if ( $result )
		{
			$result = $this->db->fetch_array();
	
			$this->id          = $result["rowid"];
			$this->client_name = $result["customers_name"];
		}
		$this->db->free();
	
		return $result;
	}
    

  /*
   *
   *
   */
  function liste_products ()
  {
    $ga = array();

    $sql = "SELECT a.rowid, a.title FROM ".MAIN_DB_PREFIX."album as a, ".MAIN_DB_PREFIX."album_to_groupart as l";
    $sql .= " WHERE a.rowid = l.fk_album AND l.fk_groupart = ".$this->id;
    $sql .= " ORDER BY a.title";

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object();
		
		$ga[$obj->rowid] = $obj->title;
		$i++;
	      }
	  }
	return $ga;
      }
    else
      {
	print $this->db->error();
      }    
  }
  /*
   *
   *
   */
}
?>
