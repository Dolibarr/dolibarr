<?php
/* Copyright (C) 2001      Fabien Seisen        <seisen@linuxfr.org>
 * Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/lib/databases/mysqli.lib.php
 *	\brief      Class file to manage Dolibarr database access for a Mysql database
 *	\version	$Id$
 */
// For compatibility during upgrade
if (! defined('DOL_DOCUMENT_ROOT'))	 define('DOL_DOCUMENT_ROOT', '../..');
if (! defined('ADODB_DATE_VERSION')) include_once(DOL_DOCUMENT_ROOT."/includes/adodbtime/adodb-time.inc.php");


/**
 *	\class      DoliDb
 *	\brief      Class to manage Dolibarr database access for a Mysql database
 */
class DoliDb
{
	//! Database handler
	var $db;
	//! Database type
	var $type='mysqli';
	//! Charset used to force charset when creating database
	var $forcecharset='latin1';
	//! Collate used to force collate when creating database
	var $forcecollate='latin1_swedish_ci';
	//! Version min database
	var $versionmin=array(4,1,0);
	//! Resultset of last request
	var $results;
	//! 1 if connected, 0 else  
	var $connected;
	//! 1 if database selected, 0 else
	var $database_selected;
	//! Database name selected
	var $database_name;
	//! Nom user base
	var $database_user;
	//! 1 si une transaction est en cours, 0 sinon
	var $transaction_opened;
	//! Last executed request
	var $lastquery;
	//! Last failed executed request
	var $lastqueryerror;
	//! Message erreur mysql
	var $lasterror;
	//! Message erreur mysql
	var $lasterrno;

	var $ok;
	var $error;


	/**
	 \brief     Ouverture d'une connexion vers le serveur et �ventuellement une database.
	 \param     type		Type de base de donn�es (mysql ou pgsql)
	 \param	    host		Addresse de la base de donn�es
	 \param	    user		Nom de l'utilisateur autoris�
	 \param	    pass		Mot de passe
	 \param	    name		Nom de la database
	 \param	    port		Port of database server
	 \return    int			1 en cas de succes, 0 sinon
	 */
	function DoliDb($type='mysqli', $host, $user, $pass, $name='', $port=0)
	{
		global $conf,$langs;

		if (! empty($conf->db->character_set)) $this->forcecharset=$conf->db->character_set;
		if (! empty($conf->db->dolibarr_main_db_collation)) $this->forcecollate=$conf->db->dolibarr_main_db_collation;

		$this->database_user=$user;

		$this->transaction_opened=0;

		//print "Name DB: $host,$user,$pass,$name<br>";

		if (! function_exists("mysqli_connect"))
		{
			$this->connected = 0;
			$this->ok = 0;
			$this->error="Mysqli PHP functions for using Mysqli driver are not available in this version of PHP. Try to use another driver.";
			dolibarr_syslog("DoliDB::DoliDB : Mysqli PHP functions for using Mysqli driver are not available in this version of PHP. Try to use another driver.",LOG_ERR);
			return $this->ok;
		}

		if (! $host)
		{
			$this->connected = 0;
			$this->ok = 0;
			$this->error=$langs->trans("ErrorWrongHostParameter");
			dolibarr_syslog("DoliDB::DoliDB : Erreur Connect, wrong host parameters",LOG_ERR);
			return $this->ok;
		}

		// Essai connexion serveur
		// We do not try to connect to database, only to server. Connect to database is done later in constrcutor
		$this->db = $this->connect($host, $user, $pass, '', $port);

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
			$this->error=mysqli_connect_error();
			dolibarr_syslog("DoliDB::DoliDB : Erreur Connect mysqli_connect_error=".$this->error,LOG_ERR);
		}

		// Si connexion serveur ok et si connexion base demandee, on essaie connexion base
		if ($this->connected && $name)
		{
			if ($this->select_db($name))
			{
				$this->database_selected = 1;
				$this->database_name = $name;
				$this->ok = 1;

				// If client connected with different charset than Dolibarr HTML output
				$clientmustbe='';
				if (eregi('UTF-8',$conf->character_set_client))      $clientmustbe='utf8';
				if (eregi('ISO-8859-1',$conf->character_set_client)) $clientmustbe='latin1';
				if (mysqli_client_encoding($this->db) != $clientmustbe)
				{
					$this->query("SET NAMES '".$clientmustbe."'", $this->db);
					//$this->query("SET CHARACTER SET ". $this->forcecharset);
				}
			}
			else
			{
				$this->database_selected = 0;
				$this->database_name = '';
				$this->ok = 0;
				$this->error=$this->error();
				dolibarr_syslog("DoliDB::DoliDB : Erreur Select_db ".$this->error,LOG_ERR);
			}
		}
		else
		{
			// Pas de selection de base demandee, ok ou ko
			$this->database_selected = 0;
			
			if ($this->connected)
			{
				// If client connected with different charset than Dolibarr HTML output
				$clientmustbe='';
				if (eregi('UTF-8',$conf->character_set_client))      $clientmustbe='utf8';
				if (eregi('ISO-8859-1',$conf->character_set_client)) $clientmustbe='latin1';
				if (mysqli_client_encoding($this->db) != $clientmustbe)
				{
					$this->query("SET NAMES '".$clientmustbe."'", $this->db);
					//$this->query("SET CHARACTER SET ". $this->forcecharset);
				}
			}
		}

		return $this->ok;
	}


	/**
	 *	\brief		Convert a SQL request in mysql syntax to database syntax
	 * 	\param		line		SQL request line to convert
	 * 	\return		string		SQL request line converted
	 */
	function convertSQLFromMysql($line)
	{
		return $line;
	}

	/**
	 *	\brief      Selectionne une database.
	 *	\param	    database		Nom de la database
	 *	\return	    boolean         true si ok, false si ko
	 */
	function select_db($database)
	{
		dolibarr_syslog("DoliDB::select_db database=".$database, LOG_DEBUG);
		return mysqli_select_db($this->db,$database);
	}


	/**
	 *	\brief      Connexion to server
	 *	\param	    host		database server host
	 *	\param	    login		login
	 *	\param	    passwd		password
	 *	\param		name		nom de la database (ne sert pas sous mysql, sert sous pgsql)
	 *	\param		port		Port of database server
	 *	\return		resource	Database access handler
	 *	\seealso	close
	 */
	function connect($host, $login, $passwd, $name, $port=0)
	{
		dolibarr_syslog("DoliDB::connect host=$host, port=$port, login=$login, passwd=--hidden--, name=$name",LOG_DEBUG);

		$newhost=$host;
		$newport=$port;

		// With mysqli, port must be in connect parameters
		if (! $newport) $newport=3306;

		$this->db  = @mysqli_connect($newhost, $login, $passwd, $name, $newport);

		//print "Resultat fonction connect: ".$this->db;
		return $this->db;
	}

	/**
	 \brief          Renvoie la version du serveur
	 \return	        string      Chaine version
	 */
	function getVersion()
	{
		//        $resql=$this->query('SELECT VERSION()');
		//        $row=$this->fetch_row($resql);
		//        return $row[0];
		return mysqli_get_server_info($this->db);
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
	 \seealso	connect
	 */
	function close()
	{
		return mysqli_close($this->db);
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
			if ($ret)
			{
				$this->transaction_opened++;
				dolibarr_syslog("BEGIN Transaction",LOG_DEBUG);
			}
			return $ret;
		}
		else
		{
			$this->transaction_opened++;
			return 1;
		}
	}

	/**
	 *	\brief      Validation d'une transaction
	 * 	\param		log			Add more log to default log line
	 * 	\return	    int         1 si validation ok ou niveau de transaction non ouverte, 0 en cas d'erreur
	 */
	function commit($log='')
	{
		if ($this->transaction_opened<=1)
		{
			$ret=$this->query("COMMIT");
			if ($ret)
			{
				$this->transaction_opened=0;
				dolibarr_syslog("COMMIT Transaction".($log?' '.$log:''),LOG_DEBUG);
			}
			return $ret;
		}
		else
		{
			$this->transaction_opened--;
			return 1;
		}
	}

	/**
	 *	\brief      Annulation d'une transaction et retour aux anciennes valeurs
	 * 	\param		log			Add more log to default log line
	 * 	\return	    int         1 si annulation ok ou transaction non ouverte, 0 en cas d'erreur
	 */
	function rollback($log='')
	{
		if ($this->transaction_opened<=1)
		{
			$ret=$this->query("ROLLBACK");
			$this->transaction_opened=0;
			dolibarr_syslog("ROLLBACK Transaction".($log?' '.$log:''),LOG_DEBUG);
			return $ret;
		}
		else
		{
			$this->transaction_opened--;
			return 1;
		}
	}

	/**
	 \brief      Effectue une requete et renvoi le resultset de r�ponse de la base
	 \param	    query	    Contenu de la query
	 \return	    resource    Resultset de la reponse
	 */
	function query($query)
	{
		$query = trim($query);
		if (! $this->database_name)
		{
			// Ordre SQL ne necessitant pas de connexion a une base (exemple: CREATE DATABASE)
			$ret = mysqli_query($this->db,$query);
		}
		else
		{
			$ret = mysqli_query($this->db,$query);
		}

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
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_object($resultset)) { $resultset=$this->results; }
		return mysqli_fetch_object($resultset);
	}


	/**
	 *	\brief      Renvoie les donnees dans un tableau.
	 *	\param      resultset   Curseur de la requete voulue
	 *	\return	    array
	 */
	function fetch_array($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_object($resultset)) { $resultset=$this->results; }
		return mysqli_fetch_array($resultset);
	}

	/**
	 *	\brief      Renvoie les donnees comme un tableau.
	 *	\param      resultset   Curseur de la requete voulue
	 *	\return	    array
	 */
	function fetch_row($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_bool($resultset))
		{
			if (! is_object($resultset)) { $resultset=$this->results; }
			return mysqli_fetch_row($resultset);
		}
		else
		{
			// si le curseur est un booleen on retourne la valeur 0
			return 0;
		}
	}

	/**
	 \brief      Renvoie le nombre de lignes dans le resultat d'une requete SELECT
	 \see    	affected_rows
	 \param      resultset   Curseur de la requete voulue
	 \return     int		    Nombre de lignes
	 */
	function num_rows($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilis� sur cette connexion
		if (! is_object($resultset)) { $resultset=$this->results; }
		return mysqli_num_rows($resultset);
	}

	/**
	 \brief      Renvoie le nombre de lignes dans le resultat d'une requete INSERT, DELETE ou UPDATE
	 \see    	num_rows
	 \param      resultset   Curseur de la requete voulue
	 \return     int		    Nombre de lignes
	 */

	function affected_rows($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilis� sur cette connexion
		if (! is_object($resultset)) { $resultset=$this->results; }
		// mysql necessite un link de base pour cette fonction contrairement
		// a pqsql qui prend un resultset
		return mysqli_affected_rows($this->db);
	}


	/**
	 \brief      Lib�re le dernier resultset utilis� sur cette connexion.
	 \param      resultset   Curseur de la requete voulue
	 */
	function free($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilis� sur cette connexion
		if (! is_object($resultset)) { $resultset=$this->results; }
		// Si resultset en est un, on libere la m�moire
		if (is_object($resultset)) mysqli_free_result($resultset);
	}


	/**
	 \brief      D�fini les limites de la requ�te.
	 \param	    limit       nombre maximum de lignes retourn�es
	 \param	    offset      num�ro de la ligne � partir de laquelle recup�rer les ligne
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
	 \brief      D�fini le tri de la requ�te.
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
				if (! $return) $return.=' ORDER BY ';
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
	 *	\brief      Escape a string to insert data.
	 *	\param	    stringtoencode		String to escape
	 *	\return	    string				String escaped
	 */
	function escape($stringtoencode)
	{
		return addslashes($stringtoencode);
	}


	/**
	 *   \brief     Formatage (par la base de donnees) d'un champ de la base au format TMS ou Date (YYYY-MM-DD HH:MM:SS)
	 *              afin de retourner une donnee toujours au format universel date TMS unix.
	 *              Fonction a utiliser pour generer les SELECT.
	 *   \param	    param       Nom champ base de type date ou chaine 'YYYY-MM-DD HH:MM:SS'
	 *   \return	date        Date au format TMS.
	 *	 \TODO		Remove unix_timestamp functions so use jdate instead
	 */
	function pdate($param)
	{
		return "unix_timestamp(".$param.")";
	}

	/**
	 *   \brief     Convert (by PHP) a GM Timestamp date into a PHP server TZ to insert into a date field.
	 *              Function to use to build INSERT, UPDATE or WHERE predica
	 *   \param	    param       Date TMS to convert
	 *   \return	string      Date in a string YYYYMMDDHHMMSS
	 */
	function idate($param)
	{
		return adodb_strftime("%Y%m%d%H%M%S",$param);
	}

	/**
	 *	\brief  	Convert (by PHP) a PHP server TZ string date into a GM Timestamps date
	 *	\param		string			Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 *	\return		date			Date TMS
	 * 	\example	19700101020000 -> 3600 with TZ+1
	 */
	function jdate($string)
	{
		$string=eregi_replace('[^0-9]','',$string);
		$tmp=$string.'000000';
		$date=dolibarr_mktime(substr($tmp,8,2),substr($tmp,10,2),substr($tmp,12,2),substr($tmp,4,2),substr($tmp,6,2),substr($tmp,0,4));
		return $date;
	}
	
	/**
	 *   \brief     Convert (by PHP) a GM Timestamp date into a GM string date to insert into a date field.
	 *              Function to use to build INSERT, UPDATE or WHERE predica
	 *   \param	    param       Date TMS to convert
	 *   \return	string      Date in a string YYYYMMDDHHMMSS
	 */
	/*function gmtosdate($param)
	{
		return adodb_strftime("%Y%m%d%H%M%S",$param,true);
	}*/
	
	/**
	 *	\brief  	Convert (by PHP) a GM string date into a GM Timestamps date
	 *	\param		string			Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 *	\return		date			Date TMS
	 * 	\example	19700101020000 -> 7200
	 */
	/*function gmtotdate($string)
	{
		$string=eregi_replace('[^0-9]','',$string);
		$tmp=$string.'000000';
		$date=dolibarr_mktime(substr($tmp,8,2),substr($tmp,10,2),substr($tmp,12,2),substr($tmp,4,2),substr($tmp,6,2),substr($tmp,0,4),1);
		return $date;
	}
	*/

	/**
	 *	\brief      Formatage d'un if SQL
	 *	\param		test            chaine test
	 *	\param		resok           resultat si test egal
	 *	\param		resko           resultat si test non egal
	 *	\return		string          chaine formatee SQL
	 */
	function ifsql($test,$resok,$resko)
	{
		return 'IF('.$test.','.$resok.','.$resko.')';
	}


	/**
	 *	\brief      Renvoie la derniere requete soumise par la methode query()
	 *	\return	    lastquery
	 */
	function lastquery()
	{
		return $this->lastquery;
	}

	/**
	 *	\brief      Renvoie la derniere requete en erreur
	 *	\return	    string	lastqueryerror
	 */
	function lastqueryerror()
	{
		return $this->lastqueryerror;
	}

	/**
	 *	\brief      Renvoie le libelle derniere erreur
	 *	\return	    string	lasterror
	 */
	function lasterror()
	{
		return $this->lasterror;
	}

	/**
	 *	\brief      Renvoie le code derniere erreur
	 *	\return	    string	lasterrno
	 */
	function lasterrno()
	{
		return $this->lasterrno;
	}

	/**
	 *	\brief     Renvoie le code erreur generique de l'operation precedente.
	 *	\return    error_num       (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
	 */
	function errno()
	{
		if (! $this->connected) {
			// Si il y a eu echec de connexion, $this->db n'est pas valide.
			return 'DB_ERROR_FAILED_TO_CONNECT';
		}
		else {
			// Constants to convert a MySql error code to a generic Dolibarr error code
			$errorcode_map = array(
			1004 => 'DB_ERROR_CANNOT_CREATE',
			1005 => 'DB_ERROR_CANNOT_CREATE',
			1006 => 'DB_ERROR_CANNOT_CREATE',
			1007 => 'DB_ERROR_ALREADY_EXISTS',
			1008 => 'DB_ERROR_CANNOT_DROP',
			1025 => 'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
			1044 => 'DB_ERROR_ACCESSDENIED',
			1046 => 'DB_ERROR_NODBSELECTED',
			1048 => 'DB_ERROR_CONSTRAINT',
			1050 => 'DB_ERROR_TABLE_ALREADY_EXISTS',
			1051 => 'DB_ERROR_NOSUCHTABLE',
			1054 => 'DB_ERROR_NOSUCHFIELD',
			1060 => 'DB_ERROR_COLUMN_ALREADY_EXISTS',
			1061 => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
			1062 => 'DB_ERROR_RECORD_ALREADY_EXISTS',
			1064 => 'DB_ERROR_SYNTAX',
			1068 => 'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS',
			1075 => 'DB_ERROR_CANT_DROP_PRIMARY_KEY',
			1091 => 'DB_ERROR_NOSUCHFIELD',
			1100 => 'DB_ERROR_NOT_LOCKED',
			1136 => 'DB_ERROR_VALUE_COUNT_ON_ROW',
			1146 => 'DB_ERROR_NOSUCHTABLE',
			1216 => 'DB_ERROR_NO_PARENT',
			1217 => 'DB_ERROR_CHILD_EXISTS',
			1451 => 'DB_ERROR_CHILD_EXISTS'
		     );
		
			if (isset($errorcode_map[mysqli_errno($this->db)]))
			{
				return $errorcode_map[mysqli_errno($this->db)];
			}
			$errno=mysqli_errno($this->db);
			return ($errno?'DB_ERROR_'.$errno:'0');
		}
	}

	/**
	 \brief     Renvoie le texte de l'erreur mysql de l'operation precedente.
	 \return    error_text
	 */
	function error()
	{
		if (! $this->connected) {
			// Si il y a eu echec de connexion, $this->db n'est pas valide pour mysqli_error.
			return 'Not connected. Check setup parameters in conf/conf.php file and your mysql client and server versions';
		}
		else {
			return mysqli_error($this->db);
		}
	}

	/**
	 \brief     R�cup�re l'id gen�r� par le dernier INSERT.
	 \param     tab     Nom de la table concern�e par l'insert. Ne sert pas sous MySql mais requis pour compatibilit� avec Postgresql
	 \return    int     id
	 */
	function last_insert_id($tab)
	{
		return mysqli_insert_id($this->db);
	}



	// Next function are not required. Only minor features use them.
	//--------------------------------------------------------------



	/**
	 \brief          Renvoie l'id de la connexion
	 \return	        string      Id connexion
	 */
	function DDLGetConnectId()
	{
		$resql=$this->query('SELECT CONNECTION_ID()');
		$row=$this->fetch_row($resql);
		return $row[0];
	}


	/**
	 *	\brief          Create a new database
	 *	\param	        database		Database name to create
	 * 	\param			charset			Charset used to store data
	 * 	\param			collation		Charset used to sort data
	 * 	\return	        resource		resource defined if OK, null if KO
	 *	\remarks        Do not use function xxx_create_db (xxx=mysql, ...) as they are deprecated
	 *					We force to create database with charset this->forcecharset and collate this->forcecollate
	 */
	function DDLCreateDb($database,$charset='',$collation='')
	{
		if (empty($charset))   $charset=$this->forcecharset;
		if (empty($collation)) $collation=$this->collation;
		
		// ALTER DATABASE dolibarr_db DEFAULT CHARACTER SET latin DEFAULT COLLATE latin1_swedish_ci
		$sql = 'CREATE DATABASE '.$database;
		$sql.= ' DEFAULT CHARACTER SET '.$charset.' DEFAULT COLLATE '.$collation;

		dolibarr_syslog($sql,LOG_DEBUG);
		$ret=$this->query($sql);
		if (! $ret)
		{
			// We try again for compatibility with Mysql < 4.1.1
			$sql = 'CREATE DATABASE '.$database;
			$ret=$this->query($sql);
			dolibarr_syslog($sql,LOG_DEBUG);
		}
		return $ret;
	}

	/**
		\brief     	Liste des tables dans une database.
		\param	    database		Nom de la database
		\param	    table   		Filtre sur tables � rechercher
		\return	    array			Tableau des tables de la base
		*/
	function DDLListTables($database, $table='')
	{
		$listtables=array();

		$like = '';
		if ($table) $like = "LIKE '".$table."'";
		$sql="SHOW TABLES FROM ".$database." ".$like.";";
		//print $sql;
		$result = $this->query($sql);
		while($row = $this->fetch_row($result))
		{
			$listtables[] = $row[0];
		}
		return $listtables;
	}

	/**
		\brief      Cr�e une table
		\param	    table 			Nom de la table
		\param	    fields 			Tableau associatif [nom champ][tableau des descriptions]
		\param	    primary_key 	Nom du champ qui sera la clef primaire
		\param	    unique_keys 	Tableau associatifs Nom de champs qui seront clef unique => valeur
		\param	    fulltext 		Tableau des Nom de champs qui seront index�s en fulltext
		\param	    key 			Tableau des champs cl�s noms => valeur
		\param	    type 			Type de la table
		\return	    int				<0 si KO, >=0 si OK
		*/
	function DDLCreateTable($table,$fields,$primary_key,$type,$unique_keys="",$fulltext_keys="",$keys="")
	{
		// cl�s recherch�es dans le tableau des descriptions (fields) : type,value,attribute,null,default,extra
		// ex. : $fields['rowid'] = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
		$sql = "create table ".$table."(";
		$i=0;
		foreach($fields as $field_name => $field_desc)
		{
			$sqlfields[$i] = $field_name." ";
			$sqlfields[$i]  .= $field_desc['type'];
			if( eregi("^[^ ]",$field_desc['value']))
			$sqlfields[$i]  .= "(".$field_desc['value'].")";
			else if( eregi("^[^ ]",$field_desc['attribute']))
			$sqlfields[$i]  .= " ".$field_desc['attribute'];
			else if( eregi("^[^ ]",$field_desc['default']))
			{
				if(eregi("null",$field_desc['default']))
				$sqlfields[$i]  .= " default ".$field_desc['default'];
				else
				$sqlfields[$i]  .= " default '".$field_desc['default']."'";
			}
			else if( eregi("^[^ ]",$field_desc['null']))
			$sqlfields[$i]  .= " ".$field_desc['null'];

			else if( eregi("^[^ ]",$field_desc['extra']))
			$sqlfields[$i]  .= " ".$field_desc['extra'];
			$i++;
		}
		if($primary_key != "")
		$pk = "primary key(".$primary_key.")";

		if($unique_keys != "")
		{
			$i = 0;
			foreach($unique_keys as $key => $value)
			{
				$sqluq[$i] = "UNIQUE KEY '".$key."' ('".$value."')";
				$i++;
			}
		}
		if($keys != "")
		{
			$i = 0;
			foreach($keys as $key => $value)
			{
				$sqlk[$i] = "KEY ".$key." (".$value.")";
				$i++;
			}
		}
		$sql .= implode(',',$sqlfields);
		if($primary_key != "")
		$sql .= ",".$pk;
		if($unique_keys != "")
		$sql .= ",".implode(',',$sqluq);
		if($keys != "")
		$sql .= ",".implode(',',$sqlk);
		$sql .=") type=".$type;

		dolibarr_syslog($sql,LOG_DEBUG);
		if(! $this -> query($sql))
		return -1;
		else
		return 1;
	}

	/**
		\brief      d�crit une table dans une database.
		\param	    table	Nom de la table
		\param	    field	Optionnel : Nom du champ si l'on veut la desc d'un champ
		\return	    resource
		*/
	function DDLDescTable($table,$field="")
	{
		$sql="DESC ".$table." ".$field;

		dolibarr_syslog($sql,LOG_DEBUG);
		$this->results = $this->query($sql);
		return $this->results;
	}

	/**
		\brief      Ins�re un nouveau champ dans une table
		\param	    table 			Nom de la table
		\param		field_name 		Nom du champ � ins�rer
		\param	    field_desc 		Tableau associatif de description duchamp � ins�rer[nom du param�tre][valeur du param�tre]
		\param	    field_position 	Optionnel ex.: "after champtruc"
		\return	    int				<0 si KO, >0 si OK
		*/
	function DDLAddField($table,$field_name,$field_desc,$field_position="")
	{
		// cl�s recherch�es dans le tableau des descriptions (field_desc) : type,value,attribute,null,default,extra
		// ex. : $field_desc = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
		$sql= "ALTER TABLE ".$table." ADD ".$field_name." ";
		$sql .= $field_desc['type'];
		if( eregi("^[^ ]",$field_desc['value']))
		$sql  .= "(".$field_desc['value'].")";
		if( eregi("^[^ ]",$field_desc['attribute']))
		$sql  .= " ".$field_desc['attribute'];
		if( eregi("^[^ ]",$field_desc['null']))
		$sql  .= " ".$field_desc['null'];
		if( eregi("^[^ ]",$field_desc['default']))
		if(eregi("null",$field_desc['default']))
		$sql  .= " default ".$field_desc['default'];
		else
		$sql  .= " default '".$field_desc['default']."'";
		if( eregi("^[^ ]",$field_desc['extra']))
		$sql  .= " ".$field_desc['extra'];
		$sql .= " ".$field_position;

		dolibarr_syslog($sql,LOG_DEBUG);
		if(! $this -> query($sql))
		return -1;
		else
		return 1;
	}

	/**
	 \brief      Create a user
	 \param	    dolibarr_main_db_host 		Ip serveur
	 \param	    dolibarr_main_db_user 		Nom user � cr�er
	 \param	    dolibarr_main_db_pass 		Mot de passe user � cr�er
	 \param		dolibarr_main_db_name		Database name where user must be granted
	 \return	    int							<0 si KO, >=0 si OK
	 */
	function DDLCreateUser($dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name)
	{
		$sql = "INSERT INTO user ";
		$sql.= "(Host,User,password,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Index_Priv,Alter_priv)";
		$sql.= " VALUES ('$dolibarr_main_db_host','$dolibarr_main_db_user',password('$dolibarr_main_db_pass')";
		$sql.= ",'Y','Y','Y','Y','Y','Y','Y','Y');";

		dolibarr_syslog("mysqli.lib::DDLCreateUser", LOG_DEBUG);	// No sql to avoid password in log
		$resql=$this->query($sql);
		if (! $resql)
		{
			return -1;
		}

		$sql = "INSERT INTO db ";
		$sql.= "(Host,Db,User,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Index_Priv,Alter_priv)";
		$sql.= " VALUES ('$dolibarr_main_db_host','$dolibarr_main_db_name','$dolibarr_main_db_user'";
		$sql.= ",'Y','Y','Y','Y','Y','Y','Y','Y');";

		dolibarr_syslog("mysqli.lib::DDLCreateUser sql=".$sql);
		$resql=$this->query($sql);
		if (! $resql)
		{
			return -1;
		}

		$sql="FLUSH Privileges";

		dolibarr_syslog("mysqli.lib::DDLCreateUser sql=".$sql);
		$resql=$this->query($sql);

		return 1;
	}

	/**
	 *	\brief		Return charset used to store data in database
	 *	\return		string		Charset
	 */
	function getDefaultCharacterSetDatabase()
	{
		$resql=$this->query('SHOW VARIABLES LIKE \'character_set_database\'');
		if (!$resql)
		{
			// version Mysql < 4.1.1
			return $this->forcecharset;
		}
		$liste=$this->fetch_array($resql);
		return $liste['Value'];
	}

	/**
	 *	\brief		Return list of available charset that can be used to store data in database
	 *	\return		array		List of Charset
	 */
	function getListOfCharacterSet()
	{
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

	/**
	 *	\brief		Return collation used in database
	 *	\return		string		Collation value
	 */
	function getDefaultCollationDatabase()
	{
		$resql=$this->query('SHOW VARIABLES LIKE \'collation_database\'');
		if (!$resql)
		{
			// version Mysql < 4.1.1
			return $this->forcecollate;
		}
		$liste=$this->fetch_array($resql);
		return $liste['Value'];
	}

	/**
	 *	\brief		Return list of available collation that can be used for database
	 *	\return		array		Liste of Collation
	 */
	function getListOfCollation()
	{
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
