<?PHP
/* Copyright (C) 2001      Fabien Seisen        <seisen@linuxfr.org>
 * Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
  * classe DoliDb
	*
	* Classe contenant les fonctions pour gere la database de dolibarr
	*
  * @package mysql.lib.php
	* @author Fabien Seisen
	* @author Rodolphe Quiedeville
	* @author Laurent Destailleur
	* @version 1.2
	*
	*/

class DoliDb {
  var $db, $results, $ok, $connected, $database_selected;

  // Constantes pour code erreurs
  var $ERROR_DUPLICATE=1062;
  var $ERROR_TABLEEXISTS=1050;


/**
 * ouverture d'une connection vers le serveur et/ou une database
 *
 * @access public
 * @param string $type
 * @param string $host
 * @param string $user
 * @param string $pass
 * @param string $name
 */

  Function DoliDb($type = 'mysql', $host = '', $user = '', $pass = '', $name = '')

	// Se connecte au serveur et éventuellement à une base (si spécifié)
  // Renvoie 1 en cas de succès, 0 sinon

		{
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

			//print "Name DB: $host,$user,$pass,$name<br>";

			// Essai connexion serveur

			$this->db = $this->connect($host, $user, $pass);

			if ($this->db)
				{
					$this->connected = 1;
					$this->ok = 1;
				}
	  	else
				{
					$this->connected = 0;
					$this->ok = 0;
				}

			// Si connexion serveur ok et si connexion base demandée, on essaie connexion base

			if ($this->connected && $name)
				{

					if ($this->select_db($name) == 1)
						{
							$this->database_selected = 1;
							$this->ok = 1;
						}
					else
						{
							$this->database_selected = 0;
							$this->ok = 0;
						}

      	}
      else
				{
					// Pas de selection de base demandée, mais tout est ok

					$this->database_selected = 0;
					$this->ok = 1;
      	}

      return $this->ok;
    }

/**
 * selectionne une database
 *
 * @access public
 * @param string $database
 * @return resource
 */

  Function select_db($database)
    {
      return mysql_select_db($database, $this->db);
    }

/**
 * connection vers une database
 *
 * @access public
 * @param string $host
 * @param string $login
 * @param string $passwd
 * @return resource
 */

  Function connect($host, $login, $passwd)
    {
      $this->db  = @mysql_connect($host, $login, $passwd);
      //print "Resultat fonction connect: ".$this->db;
      return $this->db;
    }

/**
 * création d'une database
 *
 * @access public
 * @param string $database
 * @return integer
 */

  Function create_db($database)
  {
    if (mysql_create_db ($database, $this->db))
      {
				return 1;
      }
    else
      {
				return 0;
      }
  }

/**
 * copie d'une database
 *
 * @access public
 * @return resource
 */

	Function clone()
    {
      $db2 = new DoliDb("", "", "", "", "");
      $db2->db = $this->db;
      return $db2;
    }

/**
 * ouverture d'une connection vers une database
 *
 * @access public
 * @param string $host
 * @param string $login
 * @param string $passwd
 * @return resource
 */

  Function pconnect($host, $login, $passwd)
    {
      $this->db  = mysql_pconnect($host, $login, $passwd);
      return $this->db;
    }

/**
 * fermeture d'une connection vers une database
 *
 * @access public
 * @return resource
 */

  Function close()
  {
    return mysql_close($this->db);
  }

/**
 * debut d'un transaction
 *
 * @access public
 * @param integer $do
 * @return string
 */

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

/**
 * écriture d'un transaction
 *
 * @access public
 * @param integer $do
 * @return string
 */

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

/**
 * effacement d'un transaction et retour au ancienne valeurs
 *
 * @access public
 * @param integer $do
 * @return string
 */

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

/**
 * requete vers une database
 *
 * @access public
 * @param string $query
 * @param string $limit
 * @param string $offset
 * @return resource
 */

  Function query($query, $limit="", $offset="")
    {
      $query = trim($query);
      //print "<p>$query</p>\n";
      $this->results = mysql_query($query, $this->db);
      return $this->results;
    }

/**
 * liste des tables vers une database
 *
 * @access public
 * @param string $database
 * @return resource
 */

  Function list_tables($database)
  {
    $this->results = mysql_list_tables($database, $this->db);
    return  $this->results;
  }

/**
 * renvoie les données de la requete
 *
 * @access public
 * @param integer $nb
 * @param string $fieldname
 * @return resource
 */

  Function result($nb, $fieldname)
    {
      return mysql_result($this->results, $nb, $fieldname);
    }

/**
 * désalloue la memoire de la requete
 *
 * @access public
 * @return resource
 */

  Function free()
    {
      return mysql_free_result($this->results);
    }

/**
 * renvoie les données comme un objet
 *
 * @access public
 * @return resource
 */

  Function fetch_object()
    {
      return mysql_fetch_object($this->results);
  	}

/**
 * défini les limites de la requète
 *
 * @access public
 * @param integer $limit
 * @param integer $offset
 * @return string
 */

  Function plimit($limit=0,$offset=0)
    {
      if ($offset > 0)
				{
					return " LIMIT $offset,$limit ";
      	}
			else
				{
					return " LIMIT $limit ";
      	}
    }


  Function pdate($fname)
    {
      return "unix_timestamp($fname)";
    }

/**
 * formatage de la date en fonction des locales
 *
 * @access public
 * @param integer $fname
 * @return string
 */

  Function idate($fname)
    {
      return strftime("%Y%m%d%H%M%S",$fname);
    }

/**
 * renvoie les données dans un tableau
 *
 * @access public
 * @return array
 */

  Function fetch_array()
    {
      return mysql_fetch_array($this->results);
    }

/**
 * renvoie les données comme un tableau
 *
 * @access public
 * @return array
 */

  Function fetch_row()
    {
      return mysql_fetch_row($this->results);
    }

/**
 * Get column information from a result and return as an object
 *
 * @access public
 * @return object
 */

  Function fetch_field()
    {
      return mysql_fetch_field($this->results);
    }

/**
 * renvoie le nombre de lignes dans le resultat de la requete
 *
 * @access public
 * @return int
 */

  Function num_rows()
    {
      return mysql_num_rows($this->results);
    }

/**
 * renvoie le nombre de champs dans le resultat de la requete
 *
 * @access public
 * @return int
 */

  Function num_fields()
    {
      return mysql_num_fields($this->results);
    }

/**
 * renvoie le texte de l'erreur mysql de l'operation precedente
 *
 * @access public
 * @return string
 */

  Function error()
    {
      return mysql_error($this->db);
    }

/**
 * renvoie la valeur numerique de l'erreur mysql de l'operation precedente
 *
 * @access public
 * @return int
 */

  Function errno()
    {
      // $ERROR_DUPLICATE=1062;
      // $ERROR_TABLEEXISTS=1050;

      return mysql_errno($this->db);
    }

/**
 * obtient l'id genéré par le précedent INSERT
 *
 * @access public
 * @return int
 */

  Function last_insert_id()
    {
      return mysql_insert_id();
    }

/**
 * obtient le nombre de lignes affectées dans la précédente opération
 *
 * @access public
 * @return int
 */

  Function affected_rows()
    {
      return mysql_affected_rows();
    }

}

?>
