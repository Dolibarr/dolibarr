<?php
/* Copyright (C) 2001      Fabien Seisen        <seisen@linuxfr.org>
 * Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/lib/pgsql.lib.php
		\brief      Fichier de la classe permettant de gérér une base pgsql
		\author     Fabien Seisen
		\author     Rodolphe Quiedeville.
		\author	    Laurent Destailleur.
		\author     Sébastien Di Cintio
		\author     Benoit Mortier
		\version    $Revision$
*/


/**
        \class      DoliDb
        \brief      Classe permettant de gérér la database de dolibarr
*/

class DoliDb
{
    var $db;                      // Handler de base
    var $type='pgsql';            // Nom du gestionnaire
   //! Charset
    var $forcecharset='latin1';
	var $versionmin=array(8,1,0);	// Version min database

    var $results;                 // Resultset de la dernière requete

    var $connected;               // 1 si connecté, 0 sinon
    var $database_selected;       // 1 si base sélectionné, 0 sinon
    var $database_name;			  // Nom base sélectionnée
  var $database_user;	   //! Nom user base
    var $transaction_opened;      // 1 si une transaction est en cours, 0 sinon
    var $lastquery;
	var $lastqueryerror;		// Ajout d'une variable en cas d'erreur

    var $ok;
    var $error;
 


    /**
        \brief      Ouverture d'une connexion vers le serveur et une database.
        \param		type		type de base de données (mysql ou pgsql)
        \param		host		addresse de la base de données
    	\param	    user		nom de l'utilisateur autorisé
        \param		pass		mot de passe
        \param		name		nom de la database
        \return		int			1 en cas de succès, 0 sinon
    */
    function DoliDb($type='pgsql', $host, $user, $pass, $name='')
    {
    	global $conf,$langs;

        /* Ce test est inutile. En effet, si DOL_DOCUMENT_ROOT est défini, cela signifie      */
		/* obligatoirement que le fichier conf a deja été chargée puisque cette constante est */
		/* definie a partir du contenu du fichier conf.php                                    */
		/* Et toutes les infos sont chargés dans l'objet conf                                 */
		/*
        if (file_exists($conffile)) {
	    	include($conffile);
	    	$this->forcecharset=$character_set_database;
	    	$this->forcecollate=$collation_connection;
	    	$this->db_user=$dolibarr_main_db_user;
		}
		*/
		$this->forcecharset=$conf->character_set_client;
	    $this->forcecollate=$conf->collation_connection;
	    $this->db_user=$conf->db->user;

        $this->transaction_opened=0;

        //print "Name DB: $host,$user,$pass,$name<br>";

        if (! function_exists("pg_connect"))
        {
        	$this->connected = 0;
        	$this->ok = 0;
            $this->error="Pgsql PHP functions are not available in this version of PHP";
        	dolibarr_syslog("DoliDB::DoliDB : Pgsql PHP functions are not available in this version of PHP");
            return $this->ok;
        }

        if (! $host)
        {
        	$this->connected = 0;
        	$this->ok = 0;
            $this->error=$langs->trans("ErrorWrongHostParameter");
        	dolibarr_syslog("DoliDB::DoliDB : Erreur Connect, wrong host parameters");
            return $this->ok;
        }

        // Essai connexion serveur
        $this->db = $this->connect($host, $user, $pass, $name);

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
            if ($this->select_db($name))
            {
                $this->database_selected = 1;
                $this->database_name = $name;
                $this->ok = 1;
            }
            else
            {
                $this->database_selected = 0;
                $this->database_name = '';
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
        \param		database		nom de la database
        \return		boolean         true si ok, false si ko
        \remarks 	Ici postgresql n'a aucune fonction equivalente de mysql_select_db
        \remarks 	On compare juste manuellement si la database choisie est bien celle activée par la connexion
    */
    function select_db($database)
    {
        if ($database == $this->database_name)
        	return true;
        else
        	return false;
    }

    /**
        \brief      Connection vers le serveur
        \param		host		addresse de la base de données
        \param		login		nom de l'utilisateur autoris
        \param		passwd		mot de passe
        \param		name		nom de la database (ne sert pas sous mysql, sert sous pgsql)
        \return		resource	handler d'accès à la base
    */
    function connect($host, $login, $passwd, $name)
    {
    	if (!$name){
    		$name="postgres";
    	}
        $con_string = "host=$host dbname=$name user=$login password=$passwd";
        $this->db = pg_connect($con_string);
        if ($this->db)
        {
            $this->database_name = $name;
        }
        return $this->db;
    }


    /**
            \brief          Renvoie la version du serveur
            \return	        string      Chaine version
    */
    function getVersion()
    {
    	$resql=$this->query('SHOW server_version');
	    $liste=$this->fetch_array($resql);
	    return $liste['server_version'];
    }

    /**
     \brief          Renvoie la version du serveur sous forme de nombre
     \return	        string      Chaine version
	  */
	  function getIntVersion()
	  {
			$version=	$this->getVersion();
			$vlist=split('[.-]',$version);
			if (strlen($vlist[1])==1){
				$vlist[1]="0".$vlist[1];
			}
			if (strlen($vlist[2])==1){
				$vlist[2]="0".$vlist[2];
			}
			return $vlist[0].$vlist[1].$vlist[2];
	  }
  
    /**
            \brief          Renvoie la version du serveur dans un tableau
            \return	        array  		Tableau de chaque niveau de version
    */
    function getVersionArray()
    {
        return split('\.',$this->getVersion());
    }

    /**
            \brief      Fermeture d'une connexion vers une database.
            \return	    resource
    */
    function close()
    {
        return pg_close($this->db);
    }


    /**
        \brief      Debut d'une transaction.
        \return	    int         1 si ouverture transaction ok ou deja ouverte, 0 en cas d'erreur
    */
    function begin()
    {
        if (! $this->transaction_opened)
        {
            $ret=$this->query("BEGIN;");
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
        if ($this->transaction_opened<=1)
        {
            $ret=$this->query("COMMIT;");
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
        if ($this->transaction_opened<=1)
        {
            $ret=$this->query("ROLLBACK;");
            $this->transaction_opened=0;
            return $ret;
        }
        else
        {
            $this->transaction_opened--;
            return 1;
        }
    }


    /**
        \brief      Effectue une requete et renvoi le resultset de réponse de la base
        \param		query		Contenu de la query
        \return	    resource    Resultset de la reponse
    */
    function query($query)
    {
        $query = trim($query);
        
		if ($this->forcecharset=="UTF-8"){
					$buffer=utf8_encode ($buffer);
		}
		$ret = pg_query($this->db, $query);	
        if (! eregi("^COMMIT",$query) && ! eregi("^ROLLBACK",$query))
        {
            // Si requete utilisateur, on la sauvegarde ainsi que son resultset
			if (! $ret)
			{
				$this->lastqueryerror = $query;
            	$this->lasterror = $this->error();
            	$this->lasterrno = $this->errno();
            }
            $this->lastquery=$query;
            $this->results = $ret;
        }

        return $ret;
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
        return pg_fetch_object($resultset);
    }

    /**
        \brief      Renvoie les données dans un tableau.
        \param      resultset   Curseur de la requete voulue
        \return		array
    */
    function fetch_array($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilisé sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return pg_fetch_array($resultset);
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
        return pg_fetch_row($resultset);
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
        return pg_num_rows($resultset);
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
        // pgsql necessite un resultset pour cette fonction contrairement
        // a mysql qui prend un link de base
        return pg_affected_rows($resultset);
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
        if (is_resource($resultset)) pg_free_result($resultset);
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
        \brief      Défini le tri de la requète.
        \param	    sortfield   liste des champ de tri
        \param	    sortorder   ordre du tri
        \return	    string      chaine exprimant la syntax sql de l'ordre de tri
		\TODO		A mutualiser dans classe mere
    */
    function order($sortfield=0,$sortorder=0)
    {
		if ($sortfield)
		{
			$return='';
			$fields=split(',',$sortfield);
			foreach($fields as $val)
			{
				if (! $return) $return.='ORDER BY ';
				else $return.=',';
				
				$return.=$val;
				if ($sortorder) $return.=' '.$sortorder;
			}
			return $return;
		}
		else
		{
			return '';
		}
    }
	
	
    /**
        \brief      Formatage (par la base de données) d'un champ de la base au format tms ou Date (YYYY-MM-DD HH:MM:SS)
                    afin de retourner une donnée toujours au format universel date tms unix.
                    Fonction à utiliser pour générer les SELECT.
        \param	    param       Date au format text à convertir
        \return	    date        Date au format tms.
    */
    function pdate($param)
    {
        return "unix_timestamp(".$param.")";
    }

    /**
        \brief      Formatage (par PHP) de la date en texte qui s'insere dans champ date.
                    Fonction à utiliser pour générer les INSERT.
        \param	    param       Date tms à convertir
        \return	    date        Date au format text YYYYMMDDHHMMSS.
    */
    function idate($param)
    {
        return strftime("%Y%m%d%H%M%S",$param);
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
        \brief      Renvoie la derniere requete en erreur
        \return	    string	lastqueryerror
    */
	function lastqueryerror()
	{
		return $this->lastqueryerror;
	}

    /**
        \brief      Renvoie le libelle derniere erreur
        \return	    string	lasterror
    */
	function lasterror()
	{
		return $this->lasterror;
	}

    /**
        \brief      Renvoie le code derniere erreur
        \return	    string	lasterrno
    */
	function lasterrno()
	{
		return $this->lasterrno;
	}

    /**
         \brief     Renvoie le code erreur generique de l'operation precedente.
         \return    error_num       (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
    */
    function errno()
    {
        static $error_regexps;
        if (empty($error_regexps)) {
            $error_regexps = array(
                '/(Table does not exist\.|Relation [\"\'].*[\"\'] does not exist|sequence does not exist|class ".+" not found)$/' => 'DB_ERROR_NOSUCHTABLE',
                '/table [\"\'].*[\"\'] does not exist/' => 'DB_ERROR_NOSUCHTABLE',
                '/Relation [\"\'].*[\"\'] already exists|Cannot insert a duplicate key into (a )?unique index.*/'      => 'DB_ERROR_RECORD_ALREADY_EXISTS',
                '/divide by zero$/'                     => 'DB_ERROR_DIVZERO',
                '/pg_atoi: error in .*: can\'t parse /' => 'DB_ERROR_INVALID_NUMBER',
                '/ttribute [\"\'].*[\"\'] not found$|Relation [\"\'].*[\"\'] does not have attribute [\"\'].*[\"\']/' => 'DB_ERROR_NOSUCHFIELD',
                '/parser: parse error at or near \"/'   => 'DB_ERROR_SYNTAX',
                '/referential integrity violation/'     => 'DB_ERROR_CONSTRAINT'
            );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, pg_last_error($this->db))) {
                return $code;
            }
        }
        $errno=pg_last_error($this->db);
        return ($errno?'DB_ERROR':'0');
    }

    /**
        \brief 		Renvoie le texte de l'erreur pgsql de l'operation precedente.
        \return		error_text
    */
    function error()
    {
        return pg_last_error($this->db);
    }

    /**
        \brief      Récupère l'id genéré par le dernier INSERT.
        \param     	tab     Nom de la table concernée par l'insert. Ne sert pas sous MySql mais requis pour compatibilité avec Postgresql
        \return     int     id
    */
    function last_insert_id($tab)
    {
        $result = pg_query($this->db,"SELECT MAX(rowid) FROM ".$tab." ;");
        $nbre = pg_num_rows($result);
        $row = pg_fetch_result($result,0,0);
        return $row;
    }

	// Next function are not required. Only minor features use them.
	//--------------------------------------------------------------



    /**
            \brief          Renvoie l'id de la connexion
            \return	        string      Id connexion
    */
    function getConnectId()
    {
        return '?';
    }


    /**
            \brief          Renvoie la commande sql qui donne les droits à user sur toutes les tables
            \param          databaseuser    User à autoriser
            \return	        string          Requete sql
    */
    function getGrantForUserQuery($databaseuser)
    {
        // Scan tables pour générer le grant
        /*$dir = DOL_DOCUMENT_ROOT."/pgsql/tables";

        $handle=opendir($dir);
        $table_list="";
        while (($file = readdir($handle))!==false)
        {
            if (! ereg("\.key\.sql",$file) && ereg("^(.*)\.sql",$file,$reg))
            {
                if ($table_list) {
                    $table_list.=", ".$reg[0];
                }
                else {
                    $table_list.=$reg[0];
                }
            }
        }

        // Genere le grant_query
        $grant_query = 'GRANT ALL ON '.$table_list.' TO "'.$databaseuser.'";';
        return $grant_query;
        */
        return '';
    }

    /**
        \brief      Retourne le dsn pear
        \return     dsn
    */
    function getDSN($db_type,$db_user,$db_pass,$db_host,$db_name)
    {
        return $db_type.'://'.$db_user.':'.$db_pass.'@'.$db_host.'/'.$db_name;
    }


    /**
            \brief          Création d'une nouvelle base de donnée
            \param	        database		nom de la database à créer
            \return	        resource		resource définie si ok, null si ko
            \remarks        Ne pas utiliser les fonctions xxx_create_db (xxx=mysql, ...) car elles sont deprecated
    */
    function DDLCreateDb($database)
    {
        $ret=$this->query('CREATE DATABASE '.$database.' OWNER '.$this->db_user.' ENCODING \''.$this->forcecharset.'\' ;');
        return $ret;
    }

    /**
        \brief      Liste des tables dans une database.
        \param	    database	Nom de la database
        \return	    resource
    */
    function DDLListTables($database)
    {
        $this->results = pg_query($this->db, "SHOW TABLES;");
        return  $this->results;
    }

	
	/**
			\brief      Crée un utilisateur
			\param	    dolibarr_main_db_host 		Ip serveur
			\param	    dolibarr_main_db_user 		Nom user à créer
			\param	    dolibarr_main_db_pass 		Mot de passe user à créer
			\return	    int							<0 si KO, >=0 si OK
	*/
	function DDLCreateUser($dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass)
	{
		$sql = "create user \"".$dolibarr_main_db_user."\" with password '".$dolibarr_main_db_pass."'";

		dolibarr_syslog("mysql.lib::DDLCreateUser sql=".$sql);
		$resql=$this->query($sql);
		if (! $resql)
		{
			return -1;
		}
		
		return 1;
	}
	
	function getDefaultCharacterSetDatabase(){
		 $resql=$this->query('SHOW SERVER_ENCODING');
	    $liste=$this->fetch_array($resql);
	    return $liste['server_encoding'];
	}
	
	function getListOfCharacterSet(){
		 $resql=$this->query('SHOW CHARSET');
		$liste = array();
		if ($resql) 
			{
			$i = 0;
			while ($obj = $this->fetch_object($resql) )
			{
				$liste[$i]['charset'] = $obj->Charset;
				$liste[$i]['description'] = $obj->Description;
				$i++;           
			}   
	    	$this->free($resql);
	  	} else {
	  		// version Mysql < 4.1.1
	   		return null;
	  	}
    	return $liste;
	}
	
	function getDefaultCollationConnection(){
		$resql=$this->query('SHOW VARIABLES LIKE \'collation_database\'');
		 if (!$resql)
	      {
			// version Mysql < 4.1.1
			return $this->forcecollate;
	      }
	    $liste=$this->fetch_array($resql);
	    return $liste['Value'];
	}
	
	function getListOfCollation(){
		 $resql=$this->query('SHOW COLLATION');
		$liste = array();
		if ($resql) 
			{
			$i = 0;
			while ($obj = $this->fetch_object($resql) )
			{
				$liste[$i]['collation'] = $obj->Collation;
				$i++;           
			}   
	    	$this->free($resql);
	  	} else {
	  		// version Mysql < 4.1.1
	   		return null;
	  	}
    	return $liste;
	}
	
}
?>
