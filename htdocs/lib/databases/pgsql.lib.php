<?php
/* Copyright (C) 2001      Fabien Seisen        <seisen@linuxfr.org>
 * Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/lib/databases/pgsql.lib.php
		\brief      Fichier de la classe permettant de g�r�r une base pgsql
		\version	$Id$
*/
// For compatibility during upgrade
if (! defined('DOL_DOCUMENT_ROOT'))	 define('DOL_DOCUMENT_ROOT', '../..');
if (! defined('ADODB_DATE_VERSION')) include_once(DOL_DOCUMENT_ROOT."/includes/adodbtime/adodb-time.inc.php");


/**
        \class      DoliDb
        \brief      Classe permettant de g�r�r la database de dolibarr
*/
class DoliDb
{
    var $db;                      // Handler de base
    var $type='pgsql';            // Nom du gestionnaire
   //! Charset
    var $forcecharset='latin1';
	var $versionmin=array(8,1,0);	// Version min database

    var $results;                 // Resultset de la derni�re requete

    var $connected;               // 1 si connect�, 0 sinon
    var $database_selected;       // 1 si base s�lectionn�, 0 sinon
    var $database_name;			  // Nom base s�lectionn�e
    var $database_user;	   //! Nom user base
    var $transaction_opened;      // 1 si une transaction est en cours, 0 sinon
    var $lastquery;
	var $lastqueryerror;		// Ajout d'une variable en cas d'erreur

    var $ok;
    var $error;
    var $lasterror;
 


    /**
        \brief      Ouverture d'une connexion vers le serveur et une database.
        \param		type		type de base de donn�es (mysql ou pgsql)
        \param		host		addresse de la base de donn�es
    	\param	    user		nom de l'utilisateur autoris�
        \param		pass		mot de passe
        \param		name		nom de la database
		\param	    port		Port of database server
        \return		int			1 en cas de succ�s, 0 sinon
    */
    function DoliDb($type='pgsql', $host, $user, $pass, $name='', $port=0)
    {
    	global $conf,$langs;

		$this->forcecharset=$conf->character_set_client;
	    $this->forcecollate=$conf->db->dolibarr_main_db_collation;
	    $this->database_user=$user;

        $this->transaction_opened=0;

        //print "Name DB: $host,$user,$pass,$name<br>";

        if (! function_exists("pg_connect"))
        {
        	$this->connected = 0;
        	$this->ok = 0;
            $this->error="Pgsql PHP functions are not available in this version of PHP";
        	dolibarr_syslog("DoliDB::DoliDB : Pgsql PHP functions are not available in this version of PHP",LOG_ERR);
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
            dolibarr_syslog("DoliDB::DoliDB : Erreur Connect ".$this->error,LOG_ERR);
        }

        // Si connexion serveur ok et si connexion base demand�e, on essaie connexion base
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
                dolibarr_syslog("DoliDB::DoliDB : Erreur Select_db ".$this->error,LOG_ERR);
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
	 *	\brief		Convert a SQL request in mysql syntax to database syntax
	 * 	\param		line		SQL request line to convert
	 * 	\return		string		SQL request line converted
	 */
	function convertSQLFromMysql($line)
	{
		# comments or empty lines
    	if (eregi('^-- \$Id',$line)) { 
    		return '';
		}
		# comments or empty lines
    	if (eregi('^#',$line) || eregi('^$',$line) || eregi('^--',$line))
    	{
    		return $line;
    	}
    	if ($create_sql != "")
    	{ 		# we are inside create table statement so lets process datatypes
    		if (eregi('(ISAM|innodb)',$line)) { # end of create table sequence
    			$line=eregi_replace('\) *type=(MyISAM|innodb);',');');  
    			$line=eregi_replace('\) *engine=(MyISAM|innodb);',');');  
    		} 

            # int, auto_increment -> serial
//    		} elsif (/^[\s\t]*(\w*)\s*.*int.*auto_increment/i) { 		
//    			$seq = qq~${table}_${1}_seq~;
//    			s/[\s\t]*([a-zA-Z_0-9]*)\s*.*int.*auto_increment[^,]*/  $1 SERIAL PRIMARY KEY/ig;
//    			$create_sql.=$_;

    		# int type conversion
/*    		} elsif (/(\w*)int\(\d+\)/i) {
    			$size=$1;
    			$size =~ tr [A-Z] [a-z];
    			if ($size eq "tiny" || $size eq "small") {
    				$out = "int2";
    			} elsif ($size eq "big") {
    				$out = "int8";
    			} else {
    				$out = "int4";
    			}
    			s/\w*int\(\d+\)/$out/g;
    		}
*/
    		$line=eregi_replace('tinyint','smallint');  
    
    		# nuke unsigned
    		if (eregi_replace('(int\w+|smallint)\s+unsigned','smallint',$reg))
    		{
    			$line=eregi_replace('(int\w+|smallint)\s+unsigned',$reg[1]);  
    		}

    
    		# blob -> text
   			$line=eregi_replace('\w*blob','text');  

    		# tinytext/mediumtext -> text
   			$line=eregi_replace('tinytext','text');  
   			$line=eregi_replace('mediumtext','text');  
    
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
   			$line=eregi_replace('datetime not null','datetime');  
   			$line=eregi_replace('datetime','timestamp');  
    
    		# nuke size of timestamp
//    		s/timestamp\([^)]*\)/timestamp/i;
    
    		# double -> real
//    		s/^double/real/i;
//    		s/(\s*)double/${1}real/i;
    
    		# unique key(field1,field2)
/*    		if (/unique key\s*\((\w+\s*,\s*\w+)\)/i) {
    		    s/unique key\s*\((\w+\s*,\s*\w+)\)/UNIQUE\($1\)/i;
                $create_sql.=$_;
    		    next;
    		}
*/
    		# unique index(field1,field2)
/*    		if (/unique index\s*\((\w+\s*,\s*\w+)\)/i) {
                s/unique index\s*\((\w+\s*,\s*\w+)\)/UNIQUE\($1\)/i;
                $create_sql.=$_;
    		    next;
    		}
*/    
            # unique key [name] (field)
/*            if (/unique key\s*(\w*)\s*\((\w+)\)/i) {
                s/unique key\s*(\w*)\s*\((\w+)\)/UNIQUE\($2\)/i;
                my $idxname=($1?"$1":"idx_${table}_$2");
                $create_sql.=$_;
                $create_index .= "CREATE INDEX $idxname ON $table ($2);\n";
                next;
            }
*/
            # unique index [name] (field)
/*            if (/unique index\s*(\w*)\s*\((\w+)\)/i) {
                s/unique index\s*(\w*)\s*\((\w+)\)/UNIQUE\($2\)/i;
                my $idxname=($1?"$1":"idx_${table}_$2");
                $create_sql.=$_;
                $create_index .= "CREATE INDEX $idxname ON $table ($2);\n";
                next;
            }
*/
            # unique (field) et unique (field1, field2 ...)
/*            if (/unique\s*\(([\w,\s]+)\)/i) {
                s/unique\s*\(([\w,\s]+)\)/UNIQUE\($1\)/i;
                my $fieldlist="$1";
                my $idxname="idx_${table}_${fieldlist}";
                $idxname =~ s/\W/_/g; $idxname =~ tr/_/_/s;
                $create_sql.=$_;
                $create_index .= "CREATE INDEX $idxname ON $table ($fieldlist);\n";
                next;
            }
*/            
            # index(field)
/*            if (/index\s*(\w*)\s*\((\w+)\)/i) {
                my $idxname=($1?"$1":"idx_${table}_$2");
                $create_index .= "CREATE INDEX $idxname ON $table ($2);\n";
                next;
            }
*/            
            # primary key
/*    		if (/\bkey\b/i && !/^\s+primary key\s+/i) {
    			s/KEY(\s+)[^(]*(\s+)/$1 UNIQUE $2/i;		 # hack off name of the non-primary key
    		}
*/    
            # key(xxx)
/*            if (/key\s*\((\w+)\)/i) {
                my $idxname="idx_${table}_$1";
                $create_index .= "CREATE INDEX $idxname ON $table ($1);\n";
                next;
            }
*/            
    		# Quote column names
/*    		s/(^\s*)([^\s\-\(]+)(\s*)/$1"$2"$3/gi if (!/\bkey\b/i);
*/  
    		# Remap colums with names of existing system attribute 
/*    		if (/"oid"/i) {
    			s/"oid"/"_oid"/g;
    			print STDERR "WARNING: table $table uses column \"oid\" which is renamed to \"_oid\"\nYou should fix application manually! Press return to continue.";
    			my $wait=<STDIN>;
    		}
    		s/oid/_oid/i if (/key/i && /oid/i); # fix oid in key
    		$create_sql.=$_;
*/
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
        \brief      Selectionne une database.
        \param		database		nom de la database
        \return		boolean         true si ok, false si ko
        \remarks 	Ici postgresql n'a aucune fonction equivalente de mysql_select_db
        \remarks 	On compare juste manuellement si la database choisie est bien celle activ�e par la connexion
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
        \param		host		addresse de la base de donn�es
        \param		login		nom de l'utilisateur autoris
        \param		passwd		mot de passe
        \param		name		nom de la database (ne sert pas sous mysql, sert sous pgsql)
		\param		port		Port of database server
        \return		resource	handler d'acc�s � la base
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
        \brief      Validation d'une transaction
        \return	    int         1 si validation ok ou niveau de transaction non ouverte, 0 en cas d'erreur
    */
    function commit()
    {
        if ($this->transaction_opened<=1)
        {
            $ret=$this->query("COMMIT;");
			if ($ret) 
			{
				$this->transaction_opened=0;
				dolibarr_syslog("COMMIT Transaction",LOG_DEBUG);
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
        \brief      Annulation d'une transaction et retour aux anciennes valeurs
        \return	    int         1 si annulation ok ou transaction non ouverte, 0 en cas d'erreur
    */
    function rollback()
    {
        if ($this->transaction_opened<=1)
        {
            $ret=$this->query("ROLLBACK;");
			$this->transaction_opened=0;
			dolibarr_syslog("ROLLBACK Transaction",LOG_DEBUG);
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
        // Si le resultset n'est pas fourni, on prend le dernier utilis� sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return pg_fetch_object($resultset);
    }

    /**
        \brief      Renvoie les donn�es dans un tableau.
        \param      resultset   Curseur de la requete voulue
        \return		array
    */
    function fetch_array($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilis� sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        return pg_fetch_array($resultset);
    }

    /**
        \brief      Renvoie les donn�es comme un tableau.
        \param      resultset   Curseur de la requete voulue
        \return	    array
    */
    function fetch_row($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilis� sur cette connexion
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
        // Si le resultset n'est pas fourni, on prend le dernier utilis� sur cette connexion
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
        // Si le resultset n'est pas fourni, on prend le dernier utilis� sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        // pgsql necessite un resultset pour cette fonction contrairement
        // a mysql qui prend un link de base
        return pg_affected_rows($resultset);
    }


    /**
        \brief      Lib�re le dernier resultset utilis� sur cette connexion.
        \param      resultset   Curseur de la requete voulue
    */
    function free($resultset=0)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilis� sur cette connexion
        if (! is_resource($resultset)) { $resultset=$this->results; }
        // Si resultset en est un, on libere la m�moire
        if (is_resource($resultset)) pg_free_result($resultset);
    }


    /**
        \brief      D�fini les limites de la requ�te.
        \param	    limit       nombre maximum de lignes retourn�es
        \param	    offset      num�ro de la ligne � partir de laquelle recup�rer les lignes
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
        \brief      Escape a string to insert data.
        \param	    stringtoencode		String to escape
        \return	    string				String escaped
    */
    function escape($stringtoencode)
	{
		return addslashes($stringtoencode);
	}


    /**
    *   \brief      Formatage (par la base de donn�es) d'un champ de la base au format tms ou Date (YYYY-MM-DD HH:MM:SS)
    *               afin de retourner une donn�e toujours au format universel date tms unix.
    *               Fonction � utiliser pour g�n�rer les SELECT.
    *   \param	    param       Date au format text � convertir
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
		return adodb_strftime("%Y%m%d%H%M%S",$param);
	}

	/**
	 *	\brief  	Convert (by PHP) a GM string date into a GM Timestamps date
	 *	\param		string			Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 *	\return		date			Date TMS
	 * 	\example	19700101020000 -> 7200
	 */
	function jdate($string)
	{
		$string=eregi_replace('[^0-9]','',$string);
		$tmp=$string.'000000';
		$date=dolibarr_mktime(substr($tmp,8,2),substr($tmp,10,2),substr($tmp,12,2),substr($tmp,4,2),substr($tmp,6,2),substr($tmp,0,4),1);
		return $date;
	}

    /**
     *   \brief      Formatage d'un if SQL
     *   \param		test            chaine test
     *   \param		resok           resultat si test egal
     *   \param		resko           resultat si test non egal
     *   \return		string          chaine format� SQL
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
        if (empty($error_regexps))
        {
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
        \brief      R�cup�re l'id gen�r� par le dernier INSERT.
        \param     	tab     Nom de la table concern�e par l'insert. Ne sert pas sous MySql mais requis pour compatibilit� avec Postgresql
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
    function DDLGetConnectId()
    {
        return '?';
    }


    /**
	 *	\brief          Create a new database
 	 *	\param	        database		Database name to create
	 * 	\param			charset			Charset used to store data
	 * 	\param			collation		Charset used to sort data
	 * 	\return	        resource		resource defined if OK, null if KO
	 *  \remarks        Ne pas utiliser les fonctions xxx_create_db (xxx=mysql, ...) car elles sont deprecated
     */
    function DDLCreateDb($database,$charset='',$collation='')
    {
		if (empty($charset))   $charset=$this->forcecharset;
		if (empty($collation)) $collation=$this->collation;
		
    	$ret=$this->query('CREATE DATABASE '.$database.' OWNER '.$this->db_user.' ENCODING \''.$charset.'\' ;');
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
			\brief      Create a user
			\param	    dolibarr_main_db_host 		Ip serveur
			\param	    dolibarr_main_db_user 		Nom user � cr�er
			\param	    dolibarr_main_db_pass 		Mot de passe user � cr�er
			\param		dolibarr_main_db_name		Database name where user must be granted
			\return	    int							<0 si KO, >=0 si OK
	*/
	function DDLCreateUser($dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name)
	{
		$sql = "create user \"".$dolibarr_main_db_user."\" with password '".$dolibarr_main_db_pass."'";

		dolibarr_syslog("pgsql.lib::DDLCreateUser", LOG_DEBUG);	// No sql to avoid password in log
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
	
	function getDefaultCollationDatabase(){
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
