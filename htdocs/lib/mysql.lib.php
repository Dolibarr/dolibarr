<?php
/* Copyright (C) 2001      Fabien Seisen        <seisen@linuxfr.org>
 * Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
        \file       htdocs/lib/mysql.lib.php
        \brief      Fichier de la classe permettant de gérer une base mysql
        \author     Fabien Seisen
        \author     Rodolphe Quiedeville.
        \author     Laurent Destailleur.
        \version    $Revision$
*/


/**
        \class      DoliDb
        \brief      Classe permettant de gérér la database de dolibarr
*/

class DoliDb
{
    var $db;                      // Handler de base
    var $type='mysql';            // Nom du gestionnaire

    var $results;                 // Resultset de la dernière requete

    var $connected;               // 1 si connecté, 0 sinon
    var $database_selected;       // 1 si base sélectionné, 0 sinon
    var $database_name;			  // Nom base sélectionnée
    var $transaction_opened;      // 1 si une transaction est en cours, 0 sinon
    var $lastquery;
    
    var $ok;
    var $error;
    
    // Constantes pour conversion code erreur MySql en code erreur générique
    var $errorcode_map = array(
    1004 => 'DB_ERROR_CANNOT_CREATE',
    1005 => 'DB_ERROR_CANNOT_CREATE',
    1006 => 'DB_ERROR_CANNOT_CREATE',
    1007 => 'DB_ERROR_ALREADY_EXISTS',
    1008 => 'DB_ERROR_CANNOT_DROP',
    1046 => 'DB_ERROR_NODBSELECTED',
    1050 => 'DB_ERROR_TABLE_ALREADY_EXISTS',
    1051 => 'DB_ERROR_NOSUCHTABLE',
    1054 => 'DB_ERROR_NOSUCHFIELD',
    1061 => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS', 
    1062 => 'DB_ERROR_RECORD_ALREADY_EXISTS',
    1064 => 'DB_ERROR_SYNTAX',
    1100 => 'DB_ERROR_NOT_LOCKED',
    1136 => 'DB_ERROR_VALUE_COUNT_ON_ROW',
    1146 => 'DB_ERROR_NOSUCHTABLE',
    1048 => 'DB_ERROR_CONSTRAINT',
    1216 => 'DB_ERROR_NO_PARENT',
    1217 => 'DB_ERROR_CHILD_EXISTS'
    );

    /**
            \brief      Ouverture d'une connection vers le serveur et éventuellement une database.
            \param      type		Type de base de données (mysql ou pgsql)
            \param	    host		Addresse de la base de données
    	    \param	    user		Nom de l'utilisateur autorisé
            \param	    pass		Mot de passe
            \param	    name		Nom de la database
            \param	    newlink     ???
            \return     int			1 en cas de succès, 0 sinon
    */
    function DoliDb($type='mysql', $host, $user, $pass, $name='', $newlink=0)
    {
        global $conf,$langs;
        $this->transaction_opened=0;

        //print "Name DB: $host,$user,$pass,$name<br>";
        if (! $host)
        {
        	$this->connected = 0;
        	$this->ok = 0;
            $this->error=$langs->trans("ErrorWrongHostParameter");
        	dolibarr_syslog("DoliDB::DoliDB : Erreur Connect, wrong host parameters");
            return $this->ok;
        }

        // Essai connexion serveur
        $this->db = $this->connect($host, $user, $pass, $name, $newlink);

        if ($this->db)
        {
            $this->connected = 1;
            $this->ok = 1;
        }
        else
        {
            // host, login ou password incorrect
            $this->connected = 0;
            $this->ok = 0;
            dolibarr_syslog("DoliDB::DoliDB : Erreur Connect");
        }

        // Si connexion serveur ok et si connexion base demandée, on essaie connexion base
        if ($this->connected && $name)
        {
            if ($this->select_db($name) == 1)
            {
                $this->database_selected = 1;
                $this->database_name = $name;
                $this->ok = 1;
            }
            else
            {
                $this->database_selected = 0;
                $this->ok = 0;
                $this->error=$this->error();
                dolibarr_syslog("DoliDB::DoliDB : Erreur Select_db");
            }
        }
        else
        {
            // Pas de selection de base demandée, ok ou ko
            $this->database_selected = 0;
        }

        return $this->ok;
    }

    /**
            \brief      Selectionne une database.
            \param	    database		nom de la database
            \return	    resource
    */
    function select_db($database)
    {
        return mysql_select_db($database, $this->db);
    }

    /**
            \brief      Connection vers le serveur
            \param	    host		addresse de la base de données
            \param	    login		nom de l'utilisateur autoris
            \param	    passwd		mot de passe
            \param		name		nom de la database (ne sert pas sous mysql, sert sous pgsql)
            \return		resource	handler d'accès à la base
    */
    function connect($host, $login, $passwd, $name)
    {
        $this->db  = @mysql_connect($host, $login, $passwd);
        //print "Resultat fonction connect: ".$this->db;
        return $this->db;
    }

    /**
            \brief          Création d'une nouvelle base de donnée
            \param	        database		nom de la database à créer
            \return	        resource		resource définie si ok, null si ko
            \remarks        Ne pas utiliser les fonctions xxx_create_db (xxx=mysql, ...) car elles sont deprecated
    */
    function create_db($database)
    {
        $ret=$this->query('CREATE DATABASE '.$database);
        return $ret;
    }
        
       
    /**
        \brief      Copie d'un handler de database.
        \return	    resource
    */

    function dbclone()
    {
        $db2 = new DoliDb("", "", "", "", "");
        $db2->db = $this->db;
        return $db2;
    }

    /**
        	\brief      Ouverture d'une connection vers une database.
        	\param	    host		Adresse de la base de données
            \param		login		Nom de l'utilisateur autorisé
        	\param	    passwd		Mot de passe
            \param		name		Nom de la database (ne sert pas sous mysql, sert sous pgsql)
        	\return		resource	Handler d'accès à la base
    */

    function pconnect($host, $login, $passwd, $name)
    {
        $this->db  = mysql_pconnect($host, $login, $passwd);
        return $this->db;
    }

    /**
        \brief      Fermeture d'une connection vers une database.
        \return	    resource
    */

    function close()
    {
        return mysql_close($this->db);
    }


    /**
        \brief      Debut d'une transaction.
        \return	    int         1 si ouverture transaction ok ou deja ouverte, 0 en cas d'erreur
    */

    function begin()
    {
        if (! $this->transaction_opened)
        {
            $ret=$this->query("BEGIN");
            if ($ret) $this->transaction_opened++;
            return $ret;
        }
        else
        {
            $this->transaction_opened++;
            return 1;
        }
    }

    /**
        \brief      Validation d'une transaction
        \return	    int         1 si validation ok ou niveau de transaction non ouverte, 0 en cas d'erreur
    */

    function commit()
    {
        if ($this->transaction_opened==1)
        {
            $ret=$this->query("COMMIT");
            if ($ret) $this->transaction_opened=0;
            return $ret;
        }
        else
        {
            $this->transaction_opened--;
            return 1;
        }
    }

    /**
        \brief      Annulation d'une transaction et retour aux anciennes valeurs
        \return	    int         1 si annulation ok ou transaction non ouverte, 0 en cas d'erreur
    */

    function rollback()
    {
        if ($this->transaction_opened)
        {
            $ret=$this->query("ROLLBACK");
            $this->transaction_opened=0;
            return $ret;
        }
        else
        {
            return 1;
        }
    }

    /**
        \brief      Effectue une requete et renvoi le resultset de réponse de la base
        \param	    query	    Contenu de la query
        \return	    resource    Resultset de la reponse
    */

    function query($query)
    {
        $query = trim($query);
        $ret = mysql_query($query, $this->db);

        if (! eregi("^COMMIT",$query) && ! eregi("^ROLLBACK",$query)) {
            // Si requete utilisateur, on la sauvegarde ainsi que son resultset
            $this->lastquery=$query;
            $this->results = $ret;
        }

        return $ret;
    }

    /**
        \brief      Renvoie les données de la requete.
        \param	    nb			Contenu de la query
        \param	    fieldname	Nom du champ
        \return	    resource
    */

    function result($nb, $fieldname)
    {
        return mysql_result($this->results, $nb, $fieldname);
    }

    /**
        \brief      Renvoie la ligne courante (comme un objet) pour le curseur resultset.
        \param      resultset   Curseur de la requete voulue
        \return	    resource
    */

    function fetch_object($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return mysql_fetch_object($resultset);
    }

    /**
        \brief      Renvoie les données dans un tableau.
        \param      resultset   Curseur de la requete voulue
        \return	    array
    */

    function fetch_array($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return mysql_fetch_array($resultset);
    }

    /**
        \brief      Renvoie les données comme un tableau.
        \param      resultset   Curseur de la requete voulue
        \return	    array
    */

    function fetch_row($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return mysql_fetch_row($resultset);
    }

    /**
        \brief      Obtient les données d'un colonne et renvoie les données sous forme d'objet.
        \param      resultset   Curseur de la requete voulue
        \return     array
    */

    function fetch_field($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return mysql_fetch_field($resultset);
    }

    /**
        \brief      Renvoie le nombre de lignes dans le resultat d'une requete SELECT
        \see    	affected_rows
        \param      resultset   Curseur de la requete voulue
        \return     int		    Nombre de lignes
    */

    function num_rows($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return mysql_num_rows($resultset);
    }

    /**
        \brief      Renvoie le nombre de lignes dans le resultat d'une requete INSERT, DELETE ou UPDATE
        \see    	num_rows
        \param      resultset   Curseur de la requete voulue
        \return     int		    Nombre de lignes
    */

    function affected_rows($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        // mysql necessite un link de base pour cette fonction contrairement
        // a pqsql qui prend un resultset
        return mysql_affected_rows($this->db);
    }


    /**
        \brief      Renvoie le nombre de champs dans le resultat de la requete.
        \param      resultset   Curseur de la requete voulue
        \return	    int
    */

    function num_fields($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return mysql_num_fields($resultset);
    }

    /**
        \brief      Libère le dernier resultset utilisé sur cette connexion.
        \param      resultset   Curseur de la requete voulue
    */

    function free($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        // Si resultset en est un, on libere la mémoire
        if (is_resource($resultset)) mysql_free_result($resultset);
    }

    /**
        \brief      Défini les limites de la requète.
        \param	    limit       nombre maximum de lignes retournées
        \param	    offset      numéro de la ligne à partir de laquelle recupérer les lignes
        \return	    string      chaine exprimant la syntax sql de la limite
    */

    function plimit($limit=0,$offset=0)
    {
        global $conf;
        if (! $limit) $limit=$conf->liste_limit;
        if ($offset > 0) return " LIMIT $offset,$limit ";
        else return " LIMIT $limit ";
    }


    /**
        \brief      Formatage par la base de données d'un champ de la base au format Timestamp ou Date (YYYY-MM-DD HH:MM:SS)
                    afin de retourner une donnée toujours au format universel date tms unix.
        \param	    fname
        \return	    date
    */
    function pdate($fname)
    {
        return "unix_timestamp($fname)";
    }

    /**
        \brief      Formatage de la date en fonction des locales.
        \param	    fname
        \return	    date
    */
    function idate($fname)
    {
        return strftime("%Y%m%d%H%M%S",$fname);
    }


    /**
        \brief      Formatage d'un if SQL
        \param		test            chaine test
        \param		resok           resultat si test egal
        \param		resko           resultat si test non egal
        \return		string          chaine formaté SQL
    */
    function ifsql($test,$resok,$resko)
    {
        return 'IF('.$test.','.$resok.','.$resko.')';
    }
    

    /**
        \brief      Renvoie la derniere requete soumise par la methode query()
        \return	    lastquery
    */

    function lastquery()
    {
        return $this->lastquery;
    }

    /**
        \brief     Renvoie le code erreur generique de l'operation precedente.
        \return    error_num       (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
    */

    function errno()
    {
        if (! $this->connected) {
            // Si il y a eu echec de connection, $this->db n'est pas valide.
            return 'DB_ERROR_FAILED_TO_CONNECT';
        }
        else {
            if (isset($this->errorcode_map[mysql_errno($this->db)])) {
                return $this->errorcode_map[mysql_errno($this->db)];
            }
            return 'DB_ERROR_'.mysql_errno($this->db);
        }
    }

    /**
        \brief     Renvoie le texte de l'erreur mysql de l'operation precedente.
        \return    error_text
    */

    function error()
    {
        if (! $this->connected) {
            // Si il y a eu echec de connection, $this->db n'est pas valide pour mysql_error.
            return 'Not connected. Check setup parameters in conf/conf.php file and your mysql client and server versions';
        }
        else {
            return mysql_error($this->db);
        }
    }

    /**
        \brief     Récupère l'id genéré par le dernier INSERT.
        \param     tab     Nom de la table concernée par l'insert. Ne sert pas sous MySql mais requis pour compatibilité avec Postgresql
        \return    int     id
    */

    function last_insert_id($tab)
    {
        return mysql_insert_id($this->db);
    }

    /**
        \brief      Retourne le dsn pear
        \return     dsn
    */

    function getdsn($db_type,$db_user,$db_pass,$db_host,$dbname)
    {
        $pear = $db_type.'://'.$db_user.':'.$db_pass.'@'.
        $db_host.'/'.$db_name;

        return $pear;
    }

    /**
        \brief      Liste des tables dans une database.
        \param	    database	Nom de la database
        \return	    resource
    */

    function list_tables($database)
    {
        $this->results = mysql_list_tables($database, $this->db);
        return $this->results;
    }


    function setLastQuery($s)
    {
        $this->lastquery=$s;
    }


    /**
        \brief      Renvoie toutes les données comme un tableau.
        \param      sql requete sql
        \return	    array
    */

    function fetch_all_rows($sql, &$datas)
    {
      $datas = array();

      $resql = $this->query($sql);
      if ($resql)
	{
	  $i = 0;
	  $num = $this->num_rows($resql);

	  while ($i < $num)
	    {
	      $row = $this->fetch_row($resql);
	      array_push($datas, $row[0]);
	      $i++;
	    }
	}
      else
	{
	  print $this->error();
	}
      return $result;
    }
}

?>
