<?PHP
/* Copyright (C) 2001 Fabien Seisen <seisen@linuxfr.org>
 * Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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

class DoliDb {
  var $db, $results, $ok;



  Function DoliDb($type = 'mysql', $host = '', $user = '', $pass = '', $name = '') 
    {

      //      print "Name DB : $host, $user, $pass, $name<br>";


      global $conf; 
      
      if ($host == '')
	{
	  $host = $conf->db->host;
	}
      
      if ($user == '')
	{
	  $user = $conf->db->user;
	}
      
      if ($pass == '')
	{
	  $pass = $conf->db->pass;
	}

      if ($name == '')
	{
	  $name = $conf->db->name;
	}


		
      $this->db = $this->connect($host, $user, $pass);
      
      if (! $this->db)
	{
	  print "Db->Db() raté<br>\n";
	  $this->ok = 0;
	  return 0;
	}
      
      $ret = $this->select_db($name);
      
      $this->ok = 1;

      return $ret;

    }
  /*
   *
   */
  Function select_db($database)
    {
      return mysql_select_db($database, $this->db);
    }
  /*
   *
   */
  Function connect($host, $login, $passwd)
    {
      $this->db  = mysql_connect($host, $login, $passwd);
      return $this->db;
    }
  /*
   *
   */  
  Function clone()
    {
      $db2 = new DoliDb("", "", "", "", "");
      $db2->db = $this->db;
      return $db2;
    }

  Function pconnect($host, $login, $passwd)
    {
      $this->db  = mysql_pconnect($host, $login, $passwd);
      return $this->db;
    }

  Function close()
  {
    $this->ret = mysql_close($this->db);
    return $this->ret;
  }

  Function begin($do=1)
  {
    if ($do)
      {
	return $this->query("BEGIN");
      }
    else
      {
	return 1;
      }
  }

  Function commit($do=1)
  {
    if ($do)
      {
	return $this->query("COMMIT");
      }
    else
      {
	return 1;
      }
  }

  Function rollback($do=1)
  {
    if ($do)
      {
	return $this->query("ROLLBACK");
      }
    else
      {
	return 1;
      }
  }

  Function query($query, $limit="", $offset="")
    {
      $query = trim($query);
      //print "<p>$query</p>\n";
      $this->results = mysql_query($query, $this->db);
      return $this->results;
    }

  Function result($nb, $fieldname)
    {
      return mysql_result($this->results, $nb, $fieldname);
    }
  
  Function free()
    {
      return mysql_free_result($this->results);
    }

  Function fetch_object()
    {
      return mysql_fetch_object($this->results);
  }
  
  Function plimit($limit=0,$offset=0)
    {
      if ($offset > 0) {
	return " LIMIT $offset,$limit ";
      } else {
	return " LIMIT $limit ";
      }
    }

  Function pdate($fname)
    {
      return "unix_timestamp($fname)";
    }
  
  Function idate($fname)
    {
      return strftime("%Y%m%d%H%M%S",$fname);
    }

  Function fetch_array()
    {
      return mysql_fetch_array($this->results);
    }

  Function fetch_row()
    {
      return mysql_fetch_row($this->results);
    }

  Function fetch_field()
    {
      return mysql_fetch_field($this->results);
    }

  Function num_rows()
    {
      return mysql_num_rows($this->results);
    }

  Function num_fields()
    {
      return mysql_num_fields($this->results);
    }

  Function error()
    {
      return mysql_error($this->db);
    }

  Function last_insert_id()
    {
      return mysql_insert_id();
    }

  Function affected_rows()
    {
      return mysql_affected_rows();
    }

}

?>
