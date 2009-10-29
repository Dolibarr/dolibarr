<?php
/* Copyright (C) 2001      Fabien Seisen        <seisen@linuxfr.org>
 * Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier		<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/lib/databases/pgsql.lib.php
 *	\brief      Fichier de la classe permettant de gerer une base pgsql
 *	\version	$Id$
 */
// For compatibility during upgrade
if (! defined('DOL_DOCUMENT_ROOT'))	 define('DOL_DOCUMENT_ROOT', '../..');
if (! defined('ADODB_DATE_VERSION')) include_once(DOL_DOCUMENT_ROOT."/includes/adodbtime/adodb-time.inc.php");


/**
 *	\class      DoliDb
 *	\brief      Classe permettant de gerer la database de dolibarr
 */
class DoliDb
{
	var $db;                      // Database handler
	var $type='pgsql';            // Name of manager
	var $label='PostgreSQL';      // Label of manager
	//! Charset
	var $forcecharset='latin1';
	var $versionmin=array(8,1,0);	// Version min database

	var $results;                 // Resultset de la derniere requete

	var $connected;               // 1 si connecte, 0 sinon
	var $database_selected;       // 1 si base selectionne, 0 sinon
	var $database_name;			  // Nom base selectionnee
	var $database_user;	   //! Nom user base
	var $transaction_opened;      // 1 si une transaction est en cours, 0 sinon
	var $lastquery;
	var $lastqueryerror;		// Ajout d'une variable en cas d'erreur

	var $ok;
	var $error;
	var $lasterror;



	/**
	 * \brief      Ouverture d'une connexion vers le serveur et une database.
	 * \param		type		type de base de donnees (mysql ou pgsql)
	 * \param		host		addresse de la base de donnees
	 * \param	    user		nom de l'utilisateur autorise
	 * \param		pass		mot de passe
	 * \param		name		nom de la database
	 * \param	    port		Port of database server
	 * \return		int			1 en cas de succes, 0 sinon
	 */
	function DoliDb($type='pgsql', $host, $user, $pass, $name='', $port=0)
	{
		global $conf,$langs;

		$this->forcecharset=$conf->file->character_set_client;
		$this->forcecollate=$conf->db->dolibarr_main_db_collation;
		$this->database_user=$user;

		$this->transaction_opened=0;

		//print "Name DB: $host,$user,$pass,$name<br>";

		if (! function_exists("pg_connect"))
		{
			$this->connected = 0;
			$this->ok = 0;
			$this->error="Pgsql PHP functions are not available in this version of PHP";
			dol_syslog("DoliDB::DoliDB : Pgsql PHP functions are not available in this version of PHP",LOG_ERR);
			return $this->ok;
		}

		if (! $host)
		{
			$this->connected = 0;
			$this->ok = 0;
			$this->error=$langs->trans("ErrorWrongHostParameter");
			dol_syslog("DoliDB::DoliDB : Erreur Connect, wrong host parameters",LOG_ERR);
			return $this->ok;
		}

		// Essai connexion serveur
		//print "$host, $user, $pass, $name, $port";
		$this->db = $this->connect($host, $user, $pass, $name, $port);
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
			$this->error='Host, login or password incorrect';
			dol_syslog("DoliDB::DoliDB : Erreur Connect ".$this->error,LOG_ERR);
		}

		// Si connexion serveur ok et si connexion base demandee, on essaie connexion base
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
				dol_syslog("DoliDB::DoliDB : Erreur Select_db ".$this->error,LOG_ERR);
			}
		}
		else
		{
			// Pas de selection de base demandee, ok ou ko
			$this->database_selected = 0;
		}

		return $this->ok;
	}


	/**
	 *	\brief		Convert a SQL request in Mysql syntax to PostgreSQL syntax
	 * 	\param		line		SQL request line to convert
	 * 	\return		string		SQL request line converted
	 */
	function convertSQLFromMysql($line)
	{
		# comments or empty lines
		if (preg_match('/^--\s\$Id/i',$line)) {
			return '';
		}
		# comments or empty lines
		if (preg_match('/^#/i',$line) || preg_match('/^$/i',$line) || preg_match('/^--/i',$line))
		{
			return $line;
		}
		if ($line != "")
		{
			# we are inside create table statement so lets process datatypes
			if (preg_match('/(ISAM|innodb)/i',$line)) { # end of create table sequence
				$line=preg_replace('/\)[\s\t]*type=(MyISAM|innodb);/i',');',$line);
				$line=preg_replace('/\)[\s\t]*engine=(MyISAM|innodb);/i',');',$line);
				$line=preg_replace('/,$/','',$line);
			}

			if (preg_match('/[\s\t]*(\w*)\s*.*int.*auto_increment/i',$line,$reg)) {
				$line=preg_replace('/[\s\t]*([a-zA-Z_0-9]*)[\s\t]*.*int.*auto_increment[^,]*/i','\\1 SERIAL PRIMARY KEY',$line);
			}

			# tinyint type conversion
			$line=str_replace('tinyint','smallint',$line);

			# nuke unsigned
			$line=preg_replace('/(int\w+|smallint)\s+unsigned/i','\\1',$line);

			# blob -> text
			$line=preg_replace('/\w*blob/i','text',$line);

			# tinytext/mediumtext -> text
			$line=preg_replace('/tinytext/i','text',$line);
			$line=preg_replace('/mediumtext/i','text',$line);

			# char -> varchar
			# PostgreSQL would otherwise pad with spaces as opposed
			# to MySQL! Your user interface may depend on this!
			//    		s/(\s+)char/${1}varchar/gi;

			# nuke date representation (not supported in PostgreSQL)
			//    		s/datetime default '[^']+'/datetime/i;
			//    		s/date default '[^']+'/datetime/i;
			//    		s/time default '[^']+'/datetime/i;

			# change not null datetime field to null valid ones
			# (to support remapping of "zero time" to null
			$line=preg_replace('/datetime not null/i','datetime',$line);
			$line=preg_replace('/datetime/i','timestamp',$line);

			# double -> numeric
			$line=preg_replace('/^double/i','numeric',$line);
			$line=preg_replace('/(\s*)double/i','\\1numeric',$line);
			# float -> numeric
			$line=preg_replace('/^float/i','numeric',$line);
			$line=preg_replace('/(\s*)float/i','\\1numeric',$line);

			# unique index(field1,field2)
			if (preg_match('/unique index\s*\((\w+\s*,\s*\w+)\)/i',$line))
			{
				$line=preg_replace('/unique index\s*\((\w+\s*,\s*\w+)\)/i','UNIQUE\(\\1\)',$line);
			}

			# alter table add primary key (field1, field2 ...) -> We remove the primary key name not accepted by PostGreSQL
			# ALTER TABLE llx_dolibarr_modules ADD PRIMARY KEY pk_dolibarr_modules (numero, entity);
			if (preg_match('/ALTER\s+TABLE\s*(.*)\s*ADD\s+PRIMARY\s+KEY\s*(.*)\s*\((.*)$/i',$line,$reg))
			{
				$line = "-- ".$line." replaced by --\n";
				$line.= "ALTER TABLE ".$reg[1]." ADD PRIMARY KEY (".$reg[3];
			}

			# alter table add [unique] [index] (field1, field2 ...)
			# ALTER TABLE llx_accountingaccount ADD INDEX idx_accountingaccount_fk_pcg_version (fk_pcg_version)
			if (preg_match('/ALTER\s+TABLE\s*(.*)\s*ADD\s+(UNIQUE INDEX|INDEX|UNIQUE)\s+(.*)\s*\(([\w,\s]+)\)/i',$line,$reg))
			{
				$fieldlist=$reg[4];
				$idxname=$reg[3];
				$tablename=$reg[1];
				$line = "-- ".$line." replaced by --\n";
				$line.= "CREATE ".(preg_match('/UNIQUE/',$reg[2])?'UNIQUE ':'')."INDEX ".$idxname." ON ".$tablename." (".$fieldlist.")";
			}

			// Remove () in the tables in FROM
			//$line=preg_replace('/FROM\s*\((([a-z_]+)\s+as\s+([a-z_]+)\s*,\s*([a-z_]+)\s+as\s+([a-z_]+)\s*)\)/i','FROM \\1',$line);
			//print $line;
		} #  END of if ($create_sql ne "") i.e. were inside create table statement so processed datatypes
		else {	# not inside create table
			#---- fix data in inserted data: (from MS world)
			# FIX: disabled for now
			/*    		if (00 && /insert into/i) {
			s!\x96!-!g;	# --
			s!\x93!"!g;	# ``
			s!\x94!"!g;	# ''
			s!\x85!... !g;	# \ldots
			s!\x92!`!g;
			}
			*/
			# fix dates '0000-00-00 00:00:00' (should be null)
			/*    		s/'0000-00-00 00:00:00'/null/gi;
			s/'0000-00-00'/null/gi;
			s/'00:00:00'/null/gi;
			s/([12]\d\d\d)([01]\d)([0-3]\d)([0-2]\d)([0-6]\d)([0-6]\d)/'$1-$2-$3 $4:$5:$6'/;

			if (/create\s+table\s+(\w+)/i) {
			$create_sql = $_;
			/create\s*table\s*(\w+)/i;
			$table=$1 if (defined($1));
			} else {
			print OUT $_;
			}
			*/
		} # end of if inside create_table


		return $line;
	}

	/**
	 * \brief      Selectionne une database.
	 * \param		database		nom de la database
	 * \return		boolean         true si ok, false si ko
	 * \remarks 	Ici postgresql n'a aucune fonction equivalente de mysql_select_db
	 * \remarks 	On compare juste manuellement si la database choisie est bien celle activee par la connexion
	 */
	function select_db($database)
	{
		if ($database == $this->database_name)
		return true;
		else
		return false;
	}

	/**
	 * \brief      Connection vers le serveur
	 * \param		host		addresse de la base de donnees
	 * \param		login		nom de l'utilisateur autorise
	 * \param		passwd		mot de passe
	 * \param		name		nom de la database (ne sert pas sous mysql, sert sous pgsql)
	 * \param		port		Port of database server
	 * \return		resource	handler d'acces a la base
	 */
	function connect($host, $login, $passwd, $name, $port=0)
	{
		if (!$name){
			$name="postgres";
		}
		$con_string = "host=$host port=$port dbname=$name user=$login password=$passwd";
		$this->db = pg_connect($con_string);
		if ($this->db)
		{
			$this->database_name = $name;
			pg_set_error_verbosity($this->db, PGSQL_ERRORS_VERBOSE);	// Set verbosity to max

		}
		return $this->db;
	}

	/**
	 * \brief          	Return label of manager
	 * \return			string      Label
	 */
	function getLabel()
	{
		return $this->label;
	}

	/**
	 * \brief          Renvoie la version du serveur
	 * \return	        string      Chaine version
	 */
	function getVersion()
	{
		$resql=$this->query('SHOW server_version');
		$liste=$this->fetch_array($resql);
		return $liste['server_version'];
	}

	/**
	 * \brief		Renvoie la version du serveur sous forme de nombre
	 * \return		string      Chaine version
	 */
	function getIntVersion()
	{
		$version = $this->getVersion();
		$vlist = preg_split('/[.-]/',$version);
		if (strlen($vlist[1])==1){
			$vlist[1]="0".$vlist[1];
		}
		if (strlen($vlist[2])==1){
			$vlist[2]="0".$vlist[2];
		}
		return $vlist[0].$vlist[1].$vlist[2];
	}

	/**
	 * \brief		Renvoie la version du serveur dans un tableau
	 * \return		array  		Tableau de chaque niveau de version
	 */
	function getVersionArray()
	{
		return explode('.',$this->getVersion());
	}

	/**
	 * \brief      Fermeture d'une connexion vers une database.
	 * \return	    resource
	 */
	function close()
	{
		dol_syslog("DoliDB::disconnect",LOG_DEBUG);
		return pg_close($this->db);
	}

	/**
	 * \brief      Debut d'une transaction.
	 * \return	    int         1 si ouverture transaction ok ou deja ouverte, 0 en cas d'erreur
	 */
	function begin()
	{
		if (! $this->transaction_opened)
		{
			$ret=$this->query("BEGIN;");
			if ($ret)
			{
				$this->transaction_opened++;
				dol_syslog("BEGIN Transaction",LOG_DEBUG);
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
	 * \brief      Validation d'une transaction
	 * \return	    int         1 si validation ok ou niveau de transaction non ouverte, 0 en cas d'erreur
	 */
	function commit()
	{
		if ($this->transaction_opened<=1)
		{
			$ret=$this->query("COMMIT;");
			if ($ret)
			{
				$this->transaction_opened=0;
				dol_syslog("COMMIT Transaction",LOG_DEBUG);
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
	 * \brief      Annulation d'une transaction et retour aux anciennes valeurs
	 * \return	    int         1 si annulation ok ou transaction non ouverte, 0 en cas d'erreur
	 */
	function rollback()
	{
		if ($this->transaction_opened<=1)
		{
			$ret=$this->query("ROLLBACK;");
			$this->transaction_opened=0;
			dol_syslog("ROLLBACK Transaction",LOG_DEBUG);
			return $ret;
		}
		else
		{
			$this->transaction_opened--;
			return 1;
		}
	}


	/**
	 * \brief      	Convert request to PostgreSQL syntax, execute it and return the resultset
	 * \param		query		query string
	 * \return	    resource    Resultset of answer
	 */
	function query($query)
	{
		$query = trim($query);

		// Convert MySQL syntax to PostgresSQL syntax
		$query=$this->convertSQLFromMysql($query);
		//print "FF\n".$query."<br>\n";

		// Fix bad formed requests. If request contains a date without quotes, we fix this but this should not occurs.
		$loop=true;
		while ($loop)
		{
			if (preg_match('/([^\'])([0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9])/',$query))
			{
				$query=preg_replace('/([^\'])([0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9])/','\\1\'\\2\'',$query);
				dol_syslog("Warning: Bad formed request converted into ".$query,LOG_WARNING);
			}
			else $loop=false;
		}

		$ret = @pg_query($this->db, $query);
		if (! preg_match("/^COMMIT/i",$query) && ! preg_match("/^ROLLBACK/i",$query))
		{
			// Si requete utilisateur, on la sauvegarde ainsi que son resultset
			if (! $ret)
			{
				$this->lastqueryerror = $query;
				$this->lasterror = $this->error();
				$this->lasterrno = $this->errno();
				//print "\n>> ".$query."<br>\n";
				//print '>> '.$this->lasterrno.' - '.$this->lasterror.' - '.$this->lastqueryerror."<br>\n";
			}
			$this->lastquery=$query;
			$this->results = $ret;
		}

		return $ret;
	}

	/**
	 * \brief      Renvoie la ligne courante (comme un objet) pour le curseur resultset.
	 * \param      resultset   Curseur de la requete voulue
	 * \return	    resource
	 */
	function fetch_object($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->results; }
		return pg_fetch_object($resultset);
	}

	/**
	 * \brief      Renvoie les donnees dans un tableau.
	 * \param      resultset   Curseur de la requete voulue
	 * \return		array
	 */
	function fetch_array($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->results; }
		return pg_fetch_array($resultset);
	}

	/**
	 * \brief      Renvoie les donnees comme un tableau.
	 * \param      resultset   Curseur de la requete voulue
	 * \return	    array
	 */
	function fetch_row($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->results; }
		return pg_fetch_row($resultset);
	}

	/**
	 * \brief      Renvoie le nombre de lignes dans le resultat d'une requete SELECT
	 * \see    	affected_rows
	 * \param      resultset   Curseur de la requete voulue
	 * \return     int		    Nombre de lignes
	 */
	function num_rows($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->results; }
		return pg_num_rows($resultset);
	}

	/**
	 * \brief      Renvoie le nombre de lignes dans le resultat d'une requete INSERT, DELETE ou UPDATE
	 * \see    	num_rows
	 * \param      resultset   Curseur de la requete voulue
	 * \return     int		    Nombre de lignes
	 */
	function affected_rows($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->results; }
		// pgsql necessite un resultset pour cette fonction contrairement
		// a mysql qui prend un link de base
		return pg_affected_rows($resultset);
	}


	/**
	 * \brief      Libere le dernier resultset utilise sur cette connexion.
	 * \param      resultset   Curseur de la requete voulue
	 */
	function free($resultset=0)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->results; }
		// Si resultset en est un, on libere la m�moire
		if (is_resource($resultset)) pg_free_result($resultset);
	}


	/**
	 * \brief      Defini les limites de la requete.
	 * \param	    limit       nombre maximum de lignes retournees
	 * \param	    offset      numero de la ligne a partir de laquelle recuperer les lignes
	 * \return	    string      chaine exprimant la syntax sql de la limite
	 */
	function plimit($limit=0,$offset=0)
	{
		global $conf;
		if (! $limit) $limit=$conf->liste_limit;
		if ($offset > 0) return " LIMIT $offset,$limit ";
		else return " LIMIT $limit ";
	}


	/**
	 * \brief      Defini le tri de la requete.
	 * \param	    sortfield   liste des champ de tri
	 * \param	    sortorder   ordre du tri
	 * \return	    string      chaine exprimant la syntax sql de l'ordre de tri
	 * \TODO		A mutualiser dans classe mere
	 */
	function order($sortfield=0,$sortorder=0)
	{
		if ($sortfield)
		{
			$return='';
			$fields=explode(',',$sortfield);
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
	 * \brief      Escape a string to insert data.
	 * \param	    stringtoencode		String to escape
	 * \return	    string				String escaped
	 */
	function escape($stringtoencode)
	{
		return addslashes($stringtoencode);
	}


	/**
	 *   \brief      Formatage (par la base de donnees) d'un champ de la base au format tms ou Date (YYYY-MM-DD HH:MM:SS)
	 *               afin de retourner une donnee toujours au format universel date tms unix.
	 *               Fonction a utiliser pour generer les SELECT.
	 *   \param	    param       Date au format text a convertir
	 *   \return	    date        Date au format tms.
	 */
	function pdate($param)
	{
		return "unix_timestamp(".$param.")";
	}

	/**
	 *   \brief     Convert (by PHP) a GM Timestamp date into a GM string date to insert into a date field.
	 *              Function to use to build INSERT, UPDATE or WHERE predica
	 *   \param	    param       Date TMS to convert
	 *   \return	string      Date in a string YYYYMMDDHHMMSS
	 */
	function idate($param)
	{
		return adodb_strftime("%Y-%m-%d %H:%M:%S",$param);
	}

	/**
	 *	\brief  	Convert (by PHP) a PHP server TZ string date into a GM Timestamps date
	 * 				19700101020000 -> 3600 with TZ+1
	 * 	\param		string			Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 *	\return		date			Date TMS
	 */
	function jdate($string)
	{
		$string=preg_replace('/([^0-9])/i','',$string);
		$tmp=$string.'000000';
		$date=dol_mktime(substr($tmp,8,2),substr($tmp,10,2),substr($tmp,12,2),substr($tmp,4,2),substr($tmp,6,2),substr($tmp,0,4));
		return $date;
	}

	/**
	 *   \brief      Formatage d'un if SQL
	 *   \param		test            chaine test
	 *   \param		resok           resultat si test egal
	 *   \param		resko           resultat si test non egal
	 *   \return		string          chaine formate SQL
	 */
	function ifsql($test,$resok,$resko)
	{
		return 'IF('.$test.','.$resok.','.$resko.')';
	}


	/**
	 *   \brief      Renvoie la derniere requete soumise par la methode query()
	 *   \return	    lastquery
	 */
	function lastquery()
	{
		return $this->lastquery;
	}

	/**
	 *   \brief      Renvoie la derniere requete en erreur
	 *   \return	    string	lastqueryerror
	 */
	function lastqueryerror()
	{
		return $this->lastqueryerror;
	}

	/**
	 * \brief      Renvoie le libelle derniere erreur
	 * \return	    string	lasterror
	 */
	function lasterror()
	{
		return $this->lasterror;
	}

	/**
	 * \brief      Renvoie le code derniere erreur
	 * \return	    string	lasterrno
	 */
	function lasterrno()
	{
		return $this->lasterrno;
	}

	/**
	 * \brief     Renvoie le code erreur generique de l'operation precedente.
	 * \return    error_num       (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
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
			'42P07' => 'DB_ERROR_TABLE_OR_KEY_ALREADY_EXISTS',
			'42703' => 'DB_ERROR_NOSUCHFIELD',
			1060 => 'DB_ERROR_COLUMN_ALREADY_EXISTS',
			'42710' => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
			'23505' => 'DB_ERROR_RECORD_ALREADY_EXISTS',
			'42704' => 'DB_ERROR_SYNTAX',
			'42601' => 'DB_ERROR_SYNTAX',
			'42P16' => 'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS',
			1075 => 'DB_ERROR_CANT_DROP_PRIMARY_KEY',
			1091 => 'DB_ERROR_NOSUCHFIELD',
			1100 => 'DB_ERROR_NOT_LOCKED',
			1136 => 'DB_ERROR_VALUE_COUNT_ON_ROW',
			'42P01' => 'DB_ERROR_NOSUCHTABLE',
			'23503' => 'DB_ERROR_NO_PARENT',
			1217 => 'DB_ERROR_CHILD_EXISTS',
			1451 => 'DB_ERROR_CHILD_EXISTS'
			);

			$errorlabel=pg_last_error($this->db);
			$errorcode='';
			if (preg_match('/: *([0-9P]+):/',$errorlabel,$reg))
			{
				$errorcode=$reg[1];
				if (isset($errorcode_map[$errorcode]))
				{
					return $errorcode_map[$errorcode];
				}
			}
			$errno=$errorcode?$errorcode:$errorlabel;
			return ($errno?'DB_ERROR_'.$errno:'0');
		}
		//                '/(Table does not exist\.|Relation [\"\'].*[\"\'] does not exist|sequence does not exist|class ".+" not found)$/' => 'DB_ERROR_NOSUCHTABLE',
		//                '/table [\"\'].*[\"\'] does not exist/' => 'DB_ERROR_NOSUCHTABLE',
		//                '/Relation [\"\'].*[\"\'] already exists|Cannot insert a duplicate key into (a )?unique index.*/'      => 'DB_ERROR_RECORD_ALREADY_EXISTS',
		//                '/divide by zero$/'                     => 'DB_ERROR_DIVZERO',
		//                '/pg_atoi: error in .*: can\'t parse /' => 'DB_ERROR_INVALID_NUMBER',
		//                '/ttribute [\"\'].*[\"\'] not found$|Relation [\"\'].*[\"\'] does not have attribute [\"\'].*[\"\']/' => 'DB_ERROR_NOSUCHFIELD',
		//                '/parser: parse error at or near \"/'   => 'DB_ERROR_SYNTAX',
		//                '/referential integrity violation/'     => 'DB_ERROR_CONSTRAINT'
	}

	/**
	 * \brief 		Renvoie le texte de l'erreur pgsql de l'operation precedente.
	 * \return		error_text
	 */
	function error()
	{
		return pg_last_error($this->db);
	}

	/**
	 * \brief		Get last ID after an insert INSERT.
	 * \param     	tab     Table name concerned by insert. Ne sert pas sous MySql mais requis pour compatibilite avec Postgresql
	 * \return     	int     id
	 */
	function last_insert_id($tab,$fieldid='rowid')
	{
		$result = pg_query($this->db,"SELECT MAX(".$fieldid.") FROM ".$tab." ;");
		$nbre = pg_num_rows($result);
		$row = pg_fetch_result($result,0,0);
		return $row;
	}

	/**
	 *	\brief          Encrypt sensitive data in database
	 *	\param	        fieldorvalue	Field name or value to encrypt
	 * 	\param			cryptType		Type of encryption (2: AES (recommended), 1: DES , 0: no encryption)
	 * 	\param			cryptKey		Encryption key
	 * 	\param			withQuotes		Return string with quotes
	 * 	\return	        return			XXX(field) or XXX('value') or field or 'value'
	 */
	function encrypt($fieldorvalue, $cryptType=0, $cryptKey='', $withQuotes=0)
	{
		$return = $fieldorvalue;
		return ($withQuotes?"'":"").$return.($withQuotes?"'":"");
	}


	/**
	 *	\brief          Decrypt sensitive data in database
	 *	\param	        field			Field name to decrypt
	 * 	\param			cryptType		Type of encryption (2: AES (recommended), 1: DES , 0: no encryption)
	 * 	\param			cryptKey		Encryption key
	 * 	\return	        return			Field to decrypt if used
	 */
	function decrypt($field, $cryptType=0, $cryptKey='')
	{
		$return = $field;
		return $return;
	}


	// Next function are not required. Only minor features use them.
	//--------------------------------------------------------------


	/**
	 * \brief          Renvoie l'id de la connexion
	 * \return	        string      Id connexion
	 */
	function DDLGetConnectId()
	{
		return '?';
	}



	/**
	 *	\brief          Create a new database
	 *	\param	        database		Database name to create
	 * 	\param			charset			Charset used to store data
	 * 	\param			collation		Charset used to sort data
	 * 	\param			owner			Username of database owner
	 * 	\return	        resource		resource defined if OK, null if KO
	 *  \remarks        Ne pas utiliser les fonctions xxx_create_db (xxx=mysql, ...) car elles sont deprecated
	 */
	function DDLCreateDb($database,$charset='',$collation='',$owner='')
	{
		if (empty($charset))   $charset=$this->forcecharset;
		if (empty($collation)) $collation=$this->collation;

		$ret=$this->query('CREATE DATABASE '.$database.' OWNER '.$owner.' ENCODING \''.$charset.'\'');
		return $ret;
	}

	/**
	 * \brief      Liste des tables dans une database.
	 * \param	    database	Nom de la database
	 * \return	    resource
	 */
	function DDLListTables($database)
	{
		$this->results = pg_query($this->db, "SHOW TABLES");
		return  $this->results;
	}

	/**
	 *	\brief     	Liste les informations des champs d'une table.
	 *	\param	    table			Nom de la table
	 *	\return	    array			Tableau des informations des champs de la table
	 *	TODO modifier pour postgresql
	 */
	function DDLInfoTable($table)
	{
		/*
		 $infotables=array();

		 $sql="SHOW FULL COLUMNS FROM ".$table.";";

		 dol_syslog($sql,LOG_DEBUG);
		 $result = $this->pg_query($this->db,$sql);
		 while($row = $this->fetch_row($result))
		 {
			$infotables[] = $row;
			}
			return $infotables;
			*/
	}


	/**
	 * 	\brief      Create a user
	 *	\param	    dolibarr_main_db_host 		Ip serveur
	 *	\param	    dolibarr_main_db_user 		Nom user a creer
	 *	\param	    dolibarr_main_db_pass 		Mot de passe user a creer
	 *	\param		dolibarr_main_db_name		Database name where user must be granted
	 *	\return	    int							<0 si KO, >=0 si OK
	 */
	function DDLCreateUser($dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name)
	{
		$sql = "create user \"".$dolibarr_main_db_user."\" with password '".$dolibarr_main_db_pass."'";

		dol_syslog("pgsql.lib::DDLCreateUser", LOG_DEBUG);	// No sql to avoid password in log
		$resql=$this->query($sql);
		if (! $resql)
		{
			return -1;
		}

		return 1;
	}

	/**
	 *	\brief      Insert a new field in table
	 *	\param	    table 			Nom de la table
	 *	\param		field_name 		Nom du champ a inserer
	 *	\param	    field_desc 		Tableau associatif de description du champ a inserer[nom du parametre][valeur du parametre]
	 *	\param	    field_position 	Optionnel ex.: "after champtruc"
	 *	\return	    int				<0 si KO, >0 si OK
	 */
	function DDLAddField($table,$field_name,$field_desc,$field_position="")
	{
		// cles recherchees dans le tableau des descriptions (field_desc) : type,value,attribute,null,default,extra
		// ex. : $field_desc = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
		$sql= "ALTER TABLE ".$table." ADD ".$field_name." ";
		$sql .= $field_desc['type'];
		if( preg_match("/^[^\s]/i",$field_desc['value']))
		$sql  .= "(".$field_desc['value'].")";
		if( preg_match("/^[^\s]/i",$field_desc['attribute']))
		$sql  .= " ".$field_desc['attribute'];
		if( preg_match("/^[^\s]/i",$field_desc['null']))
		$sql  .= " ".$field_desc['null'];
		if( preg_match("/^[^\s]/i",$field_desc['default']))
		if(preg_match("/null/i",$field_desc['default']))
		$sql  .= " default ".$field_desc['default'];
		else
		$sql  .= " default '".$field_desc['default']."'";
		if( preg_match("/^[^\s]/i",$field_desc['extra']))
		$sql  .= " ".$field_desc['extra'];
		$sql .= " ".$field_position;

		dol_syslog($sql,LOG_DEBUG);
		if(! $this -> query($sql))
		return -1;
		else
		return 1;
	}

	/**
	 *	\brief      Drop a field in table
	 *	\param	    table 			Nom de la table
	 *	\param		field_name 		Nom du champ a inserer
	 *	\return	    int				<0 si KO, >0 si OK
	 */
	function DDLDropField($table,$field_name)
	{
		$sql= "ALTER TABLE ".$table." DROP COLUMN `".$field_name."`";
		dol_syslog($sql,LOG_DEBUG);
		if (! $this->query($sql))
		{
			$this->error=$this->lasterror();
			return -1;
		}
		else return 1;
	}

	/**
	 *	\brief		Return charset used to store data in database
	 *	\return		string		Charset
	 */
	function getDefaultCharacterSetDatabase()
	{
		$resql=$this->query('SHOW SERVER_ENCODING');
		$liste=$this->fetch_array($resql);
		return $liste['server_encoding'];
	}

	/**
	 *	\brief		Return list of available charset that can be used to store data in database
	 *	\return		array		List of Charset
	 */
	function getListOfCharacterSet()
	{
		$resql=$this->query('SHOW SERVER_ENCODING');
		$liste = array();
		if ($resql)
		{
			$i = 0;
			while ($obj = $this->fetch_object($resql) )
			{
				$liste[$i]['charset'] = $obj->server_encoding;
				$liste[$i]['description'] = 'Default database charset';
				$i++;
			}
			$this->free($resql);
		} else {
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
		$resql=$this->query('SHOW LC_COLLATE');
		$liste=$this->fetch_array($resql);
		return $liste['lc_collate'];
	}

	/**
	 *	\brief		Return list of available collation that can be used for database
	 *	\return		array		Liste of Collation
	 */
	function getListOfCollation()
	{
		$resql=$this->query('SHOW LC_COLLATE');
		$liste = array();
		if ($resql)
		{
			$i = 0;
			while ($obj = $this->fetch_object($resql) )
			{
				$liste[$i]['collation'] = $obj->lc_collate;
				$i++;
			}
			$this->free($resql);
		} else {
			return null;
		}
		return $liste;
	}

}
?>
