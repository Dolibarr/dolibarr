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
 *
 */

class Newsletter {
  var $db ;

  var $id ;
  var $email_from_name;
  var $email_from_email;
  var $email_replyto;
  var $email_body;
  var $status;

  function Newsletter($DB, $id=0) {
    $this->db = $DB;
    $this->id = $id ;

    $statustext[0] = "Rédaction";
    $statustext[1] = "Validé";
    $statustext[2] = "Envoi en cours";
    $statustext[3] = "Envoyé";
    $statustext[4] = "Non envoyé (erreur)";

  }  
  /*
   *
   *
   *
   */
  function create($user) {

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."newsletter (fk_user_author, datec, nbsent) VALUES (".$user->id.",now(), 0)";
    
    if ($this->db->query($sql) )
      {
	$id = $this->db->last_insert_id();
	
	if ( $this->update($id, $user) )
	  {
	    return $id;
	  }
      }    
  }

  /*
   *
   *
   */
  function update($id, $user)
  {

    if (strlen(trim($this->email_replyto))==0)
      {
	$this->email_replyto = $this->email_from_email;
      }
    $this->target = 1;

    $this->target_sql = $this->build_sql($this->target);

    $sql = "UPDATE ".MAIN_DB_PREFIX."newsletter ";
    $sql .= " SET email_subject = '" . trim($this->email_subject) ."'";
    $sql .= ", email_from_name = '" . trim($this->email_from_name) ."'";
    $sql .= ", email_from_email = '" . trim($this->email_from_email) ."'";
    $sql .= ", email_replyto = '" . trim($this->email_replyto) ."'";
    $sql .= ", email_body= '" . trim($this->email_body) ."'";
    $sql .= ", target = ".$this->target;
    $sql .= ", sql_target = '".$this->target_sql."'";
    $sql .= " WHERE rowid = " . $id;

    if ( $this->db->query($sql) ) {
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }
  }
  /*
   *
   *
   *
   */
  function fetch ($id) {
    
    $sql = "SELECT rowid, email_subject, email_from_name, email_from_email, email_replyto, email_body, target, sql_target, status, date_send_request,".$this->db->pdate("date_send_begin")." as date_send_begin,".$this->db->pdate("date_send_end")." as date_send_end, nbsent, nberror";
    $sql .= " FROM ".MAIN_DB_PREFIX."newsletter WHERE rowid=$id";

    $result = $this->db->query($sql) ;

    if ( $result )
      {
	$result = $this->db->fetch_array();

	$this->id               = $result["rowid"];
	$this->email_subject    = stripslashes($result["email_subject"]);
	$this->email_from_name  = stripslashes($result["email_from_name"]);
	$this->email_from_email = stripslashes($result["email_from_email"]);
	$this->email_replyto    = stripslashes($result["email_replyto"]);
	$this->email_body       = stripslashes($result["email_body"]);
	$this->status           = $result["status"];
	$this->nbsent           = $result["nbsent"];
	$this->nberror          = $result["nberror"];
	$this->date_send_end    = $result["date_send_end"];
	$this->date_send_begin  = $result["date_send_begin"];

	$this->status_text = $statustext[$this->status];
	
	$this->db->free();
      }
    else
      {
	print $this->db->error();
      }
    
    return $result;
  }
  /*
   *
   *
   *
   */
  function liste_array ()
  {
    $ga = array();

    $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."editeur ORDER BY nom";

    if ($this->db->query($sql) )
      {
	$nump = $this->db->num_rows();
	
	if ($nump)
	  {
	    $i = 0;
	    while ($i < $nump)
	      {
		$obj = $this->db->fetch_object($i);
		
		$ga[$obj->rowid] = $obj->nom;
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
   *
   */
  function validate($user) {

    $sql = "UPDATE ".MAIN_DB_PREFIX."newsletter SET status=1, fk_user_valid = $user->id WHERE rowid = $this->id";
    $return = $this->db->query($sql) ;

  }
  /*
   *
   *
   */
  function send($user) {

    $sql = "UPDATE ".MAIN_DB_PREFIX."newsletter SET status=2, date_send_request=now() WHERE rowid = $this->id";
    $return = $this->db->query($sql) ;

  }
  /*
   *
   *
   */
  function build_sql($target)
  {
    if ($target == 1)
      {

	$sql = "SELECT c.customers_lastname as name, c.customers_firstname as firstname, c.customers_email_address as email";
	$sql .= " FROM ".DB_NAME_OSC.".customers as c";
	$sql .= " WHERE c.customers_newsletter=1";

      }

    return $sql;
  }
  /*
   *
   *
   */
  function delete() {

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."newsletter WHERE rowid = $this->id ";
    $return = $this->db->query($sql) ;

  }
}
?>
