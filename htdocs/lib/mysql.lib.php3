<?PHP
/* Copyright (C) 2001 Fabien Seisen <seisen@linuxfr.org>
 * Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
 *
 * Classe Db 
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
 */

class Db {
  var $db, $results, $ok;

  Function Db() {
    global $conf;
		
    $this->db = $this->connect($conf->db->host, $conf->db->user, $conf->db->pass);

    if (! $this->db) {
      print "Db->Db() raté<br>\n";
      $this->ok = 0;
      return 0;
    }
    $ret = $this->select_db($conf->db->name, $this->db);
    $this->ok = 1;
    return $ret;
  }

  Function select_db($database) {
    return mysql_select_db($database, $this->db);
  }
  
  Function clone() {
    $db2 = new Db("", "", "", "", "");
    $db2->db = $this->db;
    return $db2;
  }

  Function connect($host, $login, $passwd) {
    $this->db  = mysql_connect($host, $login, $passwd);
    return $this->db;
  }

  Function pconnect($host, $login, $passwd) {
    $this->db  = mysql_pconnect($host, $login, $passwd);
    return $this->db;
  }

  Function close() {
    $this->ret = mysql_close($this->db);
    return $this->ret;
  }

  Function query($query, $limit="", $offset="") {
    $query = trim($query);
    $this->results = mysql_query($query, $this->db);
    return $this->results;
  }

  Function result($nb, $fieldname) {
    return mysql_result($this->results, $nb, $fieldname);
  }

  Function free() {
    return mysql_free_result($this->results);
  }

  Function fetch_object() {
    return mysql_fetch_object($this->results);
  }

  Function plimit($limit=0,$offset=0) {
    if ($offset > 0) {
      return " LIMIT $offset,$limit ";
    } else {
      return " LIMIT $limit ";
    }
  }

  Function pdate($fname) {
    return "unix_timestamp($fname)";
  }

  Function idate($fname) {
    return strftime("%Y%m%d%H%M%S",$fname);
  }

  Function fetch_array() {
    return mysql_fetch_array($this->results);
  }
  Function fetch_row() {
    return mysql_fetch_row($this->results);
  }
  Function fetch_field() {
    return mysql_fetch_field($this->results);
  }
  Function num_rows() {
    return mysql_num_rows($this->results);
  }
  Function num_fields() {
    return mysql_num_fields($this->results);
  }
  Function error() {
    return mysql_error($this->db);
  }
  Function last_insert_id() {
    return mysql_insert_id();
  }
  Function affected_rows() {
    return mysql_affected_rows();
  }
}

?>
