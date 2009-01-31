<?php
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
 */

/*
		\file       htdocs/boutique/critiques/critique.class.php
		\ingroup    prelevement
		\brief      Fichier de la classe des critiques OSCommerce
		\version    $Revision$
*/


/**
		\class 		Critique
		\brief      Classe permettant la gestion des critiques OSCommerce
*/

class Critique {
  var $db ;

  var $id ;
  var $nom;

  function Critique($DB, $id=0) {
    $this->db = $DB;
    $this->id = $id ;
  }
  /*
   *
   *
   *
   */
  function fetch ($id) {
	global $conf;

    $sql = "SELECT r.reviews_id, r.reviews_rating, d.reviews_text, p.products_name";

    $sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."reviews as r, ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."reviews_description as d";
    $sql .= " ,".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_description as p";

    $sql .= " WHERE r.reviews_id = d.reviews_id AND r.products_id=p.products_id";
    $sql .= " AND p.language_id = ".$conf->global->OSC_LANGUAGE_ID. " AND d.languages_id=".$conf->global->OSC_LANGUAGE_ID;
    $sql .= " AND r.reviews_id=$id";

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$result = $this->db->fetch_array();

	$this->id           = $result["reviews_id"];
	$this->product_name = stripslashes($result["products_name"]);
	$this->text         = stripslashes($result["reviews_text"]);

	$this->db->free();
      }
    else
      {
	print $this->db->error();
	print "<p>$sql";
      }

    return $result;
  }

}
?>
