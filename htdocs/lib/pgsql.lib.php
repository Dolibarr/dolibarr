<?PHP
/* Copyright (C) 2001      Fabien Seisen        <seisen@linuxfr.org>
 * Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier			  <benoit.mortier@opensides.be>
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

/*!	\file pgsql.lib.php
		\brief Classe permettant de gérér la database postgresql de dolibarr.
		\author Fabien Seisen
		\author Rodolphe Quiedeville.
		\author	Laurent Destailleur.
		\author Sébastien Di Cintio
		\author Benoit Mortier
		\version $Revision$

		Ensemble des fonctions permettant de gérer la database de dolibarr.
*/

/*! \class DoliDb
		\brief Classe permettant de gérér la database de dolibarr

		Ensemble des fonctions permettant de gérer la database de dolibarr
*/




class DoliDb 
{
  var $db, $results, $ok, $connected, $database_selected;
	
	  
/*!
		\brief ouverture d'une connection vers le serveur et/ou une database.
		\param	type		type de base de données (mysql ou pgsql)
		\param	host		addresse de la base de données
		\param	user		nom de l'utilisateur autorisé
		\param	pass		mot de passe
		\param	name		nom de la database
*/

  function DoliDb($type = 'pgsql', $host = '', $user = '', $pass = '', $name = '')

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
			
			$this->db = $this->connect($host, $user, $pass,$name);

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

/*!
		\brief selectionne une database.
		\param	database		nom de la database
		\return	resource
		\remarks ici postgresql n'a aucune fonction equivalente de mysql_select_db
		\remarks comparaison manuel si la database est bien celle choisie par l'utilisateur
		\remarks en cas de succes renverra 1 ou 0
*/

	

  function select_db($database)
    {
					if($database == "dolibarr")
						return 1;
					else
						return 0;
    }

/*!
		\brief connection vers une database.
		\param	host			addresse de la base de données
		\param	login			nom de l'utilisateur autorisé
		\param	passwd		mot de passe
		\param	name			nom de la database
		\return	resource
*/

  function connect($host, $login, $passwd,$name)
    {
       $con_string = "host=$host dbname=$name user=$login password=$passwd ";
			 $this->db = pg_connect($con_string);
			 return $this->db;
    }

/*!
		\brief création d'une database.
		\param	database		nom de la database
		\return	result			resultat 1 pour ok, 0 pour non ok
*/

  function create_db($database)
  {
			if(createdb($database,$this->db))
				return 1;
			else
				return 0;
		}
  

/*!
		\brief copie d'une database.
		\return	resource
*/

	function clone()
    {
      $db2 = new DoliDb("", "", "", "", "");
      $db2->db = $this->db;
      return $db2;
    }

/*!
		\brief ouverture d'une connection persistante vers une database.
		\param	host			addresse de la base de données
		\param	login			nom de l'utilisateur autorisé
		\param	passwd		mot de passe
		\param	name			nom de la database
		\return	resource
*/

  function pconnect($host, $login, $passwd,$name)
    {
			 $con_string = "host=$host dbname=$name user=$login password=$passwd";
			 $this->db = pg_pconnect($con_string);
    }

/*!
		\brief fermeture d'une connection vers une database.
		\return	resource
*/

  function close()
  	{
			return pg_close($this->db);
  	}

/*!
		\brief debut d'une transaction.
		\param	do
		\return	string
*/

  function begin($do=1)
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

/*!
		\brief écriture d'une transaction.
		\param	do
		\return	string
*/

  function commit($do=1)
  {
    if ($do)
      {
				return $this->query("COMMIT;");
      }
    else
      {
				return 1;
      }
  }


/*!
		\brief éffacement d'une transaction et retour au ancienne valeurs.
		\param	do
		\return	string
*/

  function rollback($do=1)
  {
    if ($do)
      {
				return $this->query("ROLLBACK;");
      }
    else
      {
				return 1;
      }
  }

/*!
		\brief requete vers une database.
		\param	query		contenu de la query
		\param	limit
		\param	offset
		\return	resource
*/

  function query($query, $limit="", $offset="")
    {
      $query = trim($query);
			
      //print "<p>$query</p>\n";
      
			$this->lastquery=$query;

			$this->results = pg_query($this->db,$query);

      return $this->results;
    }

/*!
		\brief liste des tables dans une database.
		\param	database		nom de la database
		\return	resource
*/

  function list_tables($database)
  {
    $this->results = pg_query($this->db, "SHOW TABLES;");
    return  $this->results;
  }

	
/*!
		\brief renvoie les données de la requete.
		\param	nb				contenu de la query
		\param	fieldname	nom du champ
		\return	resource
*/

  function result($nb, $fieldname)
    {
			return pg_fetch_result($this->results,$nb,$fieldname);
    }

		
/*!
		\brief désalloue la memoire de la requete.
		\return	resource
*/

  function free()
    {
      return pg_free_result($this->results);
    }

/*!
		\brief renvoie les données comme un objet.
		\return	resource
*/

  function fetch_object()
    {
			return pg_fetch_object($this->results);
  	}

/*!
		\brief défini les limites de la requète.
		\param	limit
		\param	offset
		\return	limit
*/

  function plimit($limit=0,$offset=0)
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

/*!
		\brief formatage de la date en format unix.
		\param	fname
		\return	date
*/


 	function pdate($fname)
    {
      return "unix_timestamp($fname)";
    }

/*!
		\brief formatage de la date en fonction des locales.
		\param	fname
		\return	date
*/

  function idate($fname)
    {
      return strftime("%Y%m%d%H%M%S",$fname);
    }

/*!
		\brief renvoie les données dans un tableau.
		\return	array
*/

  function fetch_array()
    {
			return pg_fetch_array($this->results);
    }

/*!
		\brief renvoie les données comme un tableau.
		\return	array
*/

  function fetch_row()
    {
			return pg_fetch_row($this->results);
    }

/*!
		\brief obtient les données d'un colonne et renvoie les données sous forme d'objet.
*/

  function fetch_field()
    {
			return pg_field_name($this->results);
    }


/*!
		\brief renvoie le nombre de lignes dans le resultat de la requete.
		\return	int
*/

  function num_rows()
    {
			return pg_num_rows($this->results);
    }

/*!
		\brief renvoie le nombre de champs dans le resultat de la requete.
		\return	int
*/

  function num_fields()
    {
			return pg_num_fields($this->results);
    }

/*!
		\brief renvoie la derniere requete soumise par la methode query()
		\return	error_text
*/

  function lastquery()
    {
      return $this->lastquery;
    }

/*!
		\brief renvoie le texte de l'erreur mysql de l'operation precedente.
		\return	error_text
*/

  function error()
    {
			return pg_last_error($this->db);
    }

/*!
		\brief renvoie la valeur numerique de l'erreur mysql de l'operation precedente.
		\return error_num
*/

  function errno()
    {
			return pg_result_status($this->db);
    }

/*!
		\brief obtient l'id genéré par le précedent INSERT.
		\return id
*/

  function last_insert_id()
    {
			return pg_last_oid($this->results);
    }

/*!
		\brief obtient le nombre de lignes affectées dans la précédente opération.
		\return rows
*/

  function affected_rows()
    {
			return pg_affected_rows($this->results); 
    }

/*!
		\brief construit une chaine de connection pear.
		\return peardsn
*/

	function getdsn($db_type,$db_user,$db_pass,$db_host,$dbname)
	{
		$pear = $db_type.'://'.$db_user.':'.$db_pass.'@'.
		$db_host.'/'.$db_name;
			
		return $pear;
	}
			
}
?>
