<?php
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

/*!	    \file htdocs/lib/pgsql.lib.php
		\brief Classe permettant de gérér la database de dolibarr.
		\author Fabien Seisen
		\author Rodolphe Quiedeville.
		\author	Laurent Destailleur.
		\author Sébastien Di Cintio
		\author Benoit Mortier
		\version $Revision$

		Ensemble des fonctions permettant de gérer la database de dolibarr.
*/

/*! 	\class DoliDb
		\brief Classe permettant de gérér la database de dolibarr

		Ensemble des fonctions permettant de gérer la database de dolibarr
*/

class DoliDb 
{
  var $db, $results, $ok, $connected, $database_selected;
	
  // Constantes pour code erreurs
  var $ERROR_DUPLICATE="23505";
  var $ERROR_TABLEEXISTS='42P07';
	  
/*!
		\brief      Ouverture d'une connection vers le serveur et une database.
		\param		type		type de base de données (mysql ou pgsql)
		\param		host		addresse de la base de données
		\param		user		nom de l'utilisateur autorisé
		\param		pass		mot de passe
		\param		name		nom de la database
		\return		int			1 en cas de succès, 0 sinon
*/

  function DoliDb($type = 'pgsql', $host = '', $user = '', $pass = '', $name = '')
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
            		// Pas de selection de base demandée, ok ou ko
					$this->database_selected = 0;
      	}

      return $this->ok;
    }

/*!
		\brief      Selectionne une database.
		\param		database		nom de la database
		\return		resource
		\remarks 	ici postgresql n'a aucune fonction equivalente de mysql_select_db
		\remarks 	comparaison manuel si la database est bien celle choisie par l'utilisateur
		\remarks 	en cas de succes renverra 1 ou 0
*/

  function select_db($database)
    {
					if($database == "dolibarr")
						return 1;
					else
						return 0;
    }

/*!
		\brief      Connection vers le serveur
		\param		host		addresse de la base de données
		\param		login		nom de l'utilisateur autorisé
		\param		passwd		mot de passe
		\param		name		nom de la database
		\return		resource	handler d'accès à la base
*/

  function connect($host, $login, $passwd, $name)
    {
       $con_string = "host=$host dbname=$name user=$login password=$passwd ";
			 $this->db = pg_connect($con_string);
			 return $this->db;
    }

/*!
		\brief      Connexion sur une base de donnée
		\param		database		nom de la database
		\return		result			resultat 1 pour ok, 0 pour non ok
*/

  function create_db($database)
  {
			if(createdb($database,$this->db))
				return 1;
			else
				return 0;
  }
  

/*!
		\brief      Copie d'une database.
		\return	resource
*/

	function clone()
    {
      $db2 = new DoliDb("", "", "", "", "");
      $db2->db = $this->db;
      return $db2;
    }

/*!
		\brief      Ouverture d'une connection vers une database.
		\param		host		addresse de la base de données
		\param		login		nom de l'utilisateur autorisé
		\param		passwd		mot de passe
		\param		name		nom de la database
		\return		resource	handler d'accès à la base
*/

  function pconnect($host, $login, $passwd,$name)
    {
		 $con_string = "host=$host dbname=$name user=$login password=$passwd";
		 $this->db = pg_pconnect($con_string);
		 return $this->db;
    }

/*!
		\brief      Fermeture d'une connection vers une database.
		\return	resource
*/

  function close()
  	{
		return pg_close($this->db);
  	}

/*!
		\brief      Debut d'une transaction.
		\param		do
		\return		string
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
		\brief      Ecriture d'une transaction.
		\param		do
		\return		string
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
		\brief      Effacement d'une transaction et retour au ancienne valeurs.
		\param		do
		\return		string
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
		\brief      Effectue une requete et renvoi le resultset de réponse de la base
		\param		query		contenu de la query
		\param		limit
		\param		offset
		\return	    resource resultset
*/

  function query($query, $limit="", $offset="")
    {
      $query = trim($query);

	  $this->lastquery=$query;

	  $this->results = pg_query($this->db,$query);

      return $this->results;
    }

/*!
		\brief      Liste des tables dans une database.
		\param		database		nom de la database
		\return		resource
*/

  function list_tables($database)
  {
    $this->results = pg_query($this->db, "SHOW TABLES;");
    return  $this->results;
  }

	
/*!
		\brief      Renvoie les données de la requete.
		\param		nb				contenu de la query
		\param		fieldname	nom du champ
		\return		resource
*/

  function result($nb, $fieldname)
    {
			return pg_fetch_result($this->results, $nb, $fieldname);
    }

		
/*!
		\brief      Libère le dernier resultset utilisé sur cette connexion.
		\return	resource
*/

  function free()
    {
      return pg_free_result($this->results);
    }

/*!
		\brief      Renvoie la ligne courante (comme un objet) pour le curseur resultset.
        \param      resultset   curseur de la requete voulue
		\return		resource
*/

  function fetch_object($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return pg_fetch_object($resultset);
  	}

/*!
		\brief 		défini les limites de la requète.
		\param		limit
		\param		offset
		\return		int		limite
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
		\brief 		formatage de la date en format unix.
		\param		fname
		\return		date
*/

 	function pdate($fname)
    {
      return "unix_timestamp($fname)";
    }

/*!
		\brief 		formatage de la date en fonction des locales.
		\param		fname
		\return		date
*/

  function idate($fname)
    {
      return strftime("%Y%m%d%H%M%S",$fname);
    }

/*!
		\brief      Renvoie les données dans un tableau.
		\return		array
*/

  function fetch_array()
    {
		return pg_fetch_array($this->results);
    }

/*!
		\brief      Renvoie les données comme un tableau.
		\return	array
*/

  function fetch_row()
    {
		return pg_fetch_row($this->results);
    }

/*!
		\brief      Obtient les données d'un colonne et renvoie les données sous forme d'objet.
        \return     array
*/

  function fetch_field()
    {
		return pg_field_name($this->results);
    }


/*!
		\brief      Renvoie le nombre de lignes dans le
		            resultat d'une requete SELECT
		\seealso	affected_rows
		\return     int		nombre de lignes
*/

  function num_rows()
    {
		return pg_num_rows($this->results);
    }

/*!
		\brief      Renvoie le nombre de lignes dans le
		            resultat d'une requete INSERT, DELETE ou UPDATE
		\seealso	num_rows
		\return     int		nombre de lignes
*/

  function affected_rows()
    {
		// pgsql necessite un resultset pour cette fonction contrairement
		// a mysql qui prend un link de base
		return pg_affected_rows($this->results); 
    }

/*!
		\brief      Renvoie le nombre de champs dans le resultat de la requete.
		\return	int
*/

  function num_fields()
    {
		return pg_num_fields($this->results);
    }

/*!
		\brief 		Renvoie la derniere requete soumise par la methode query()
		\return	    lastquery
*/

  function lastquery()
    {
      return $this->lastquery;
    }

/*!
		\brief 		Renvoie le texte de l'erreur mysql de l'operation precedente.
		\return		error_text
*/

  function error()
    {
			return pg_last_error($this->db);
    }

/*!
		\brief      Renvoie la valeur numerique de l'erreur de l'operation precedente.
					pour etre exploiter par l'appelant et détecter les erreurs du genre:
					echec car doublons, table deja existante...
		\return 	error_num
		\remark		pgsql ne permet pas de renvoyer un code générique d'une erreur,
					mais juste un message. On utilise donc ces messages plutot qu'un code.
*/

  function errno()
    {
			return pg_last_error($this->db);
    }

/*!
		\brief      Obtient l'id genéré par le précedent INSERT.
		\return 	id
*/

  function last_insert_id($tab)
    {
			$result = pg_query($this->db,"select max(rowid) from ".$tab." ;");
			$nbre = pg_num_rows($result);
			$row = pg_fetch_result($result,0,0);
			return $row;
     }


/*!
		\brief      Retourne le dsn pear
		\return     dsn
*/

	function getdsn($db_type,$db_user,$db_pass,$db_host,$dbname)
	{
		$pear = $db_type.'://'.$db_user.':'.$db_pass.'@'.
		$db_host.'/'.$db_name;
			
		return $pear;
	}
			
}

?>
