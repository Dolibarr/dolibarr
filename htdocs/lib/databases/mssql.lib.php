<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Simon Desee          <simon@dedisoft.com>
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
		\file       	htdocs/lib/databases/mssql.lib.php
		\brief			Fichier de la classe permettant de gérer une base mssql
		\version		$Id$
*/
// Pour compatibilité lors de l'upgrade
if (! defined('DOL_DOCUMENT_ROOT'))	 define('DOL_DOCUMENT_ROOT', '../..');
if (! defined('ADODB_DATE_VERSION')) include_once(DOL_DOCUMENT_ROOT."/includes/adodbtime/adodb-time.inc.php");


/**
		\class      DoliDb
		\brief      Classe de gestion de la database de dolibarr
*/
class DoliDb
{
  //! Handler de base
  var $db;
  //! Nom du gestionnaire
  var $type='mssql';
  //! Charset
  var $forcecharset='latin1';
  //! Collate
  var $forcecollate='latin1_swedish_ci';
  //! Version min database
  var $versionmin=array(2000);	
  //! Resultset de la dernière requete  
  var $results;
  //! 1 si connecté, 0 sinon  
  var $connected;             
  //! 1 si base sélectionné, 0 sinon
  var $database_selected; 
  //! Nom base sélectionnée
  var $database_name;			
  //! Nom user base
  var $database_user;		
  //! 1 si une transaction est en cours, 0 sinon
  var $transaction_opened;	
  //! Derniere requete exécutée
  var $lastquery;			
  //! Derniere requete exécutée avec echec
  var $lastqueryerror;		
  //! Message erreur mysql
  var $lasterror;		
  //! Message erreur mysql
  var $lasterrno;
  
  var $ok;
  var $error;
  

  // Constantes pour conversion code erreur MSSql en code erreur générique
  var $errorcode_map = array(
			     1004 => 'DB_ERROR_CANNOT_CREATE',
			     1005 => 'DB_ERROR_CANNOT_CREATE',
			     1006 => 'DB_ERROR_CANNOT_CREATE',
			     1007 => 'DB_ERROR_ALREADY_EXISTS',
			     1008 => 'DB_ERROR_CANNOT_DROP',
			     1025 => 'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
			     1046 => 'DB_ERROR_NODBSELECTED',
			     1048 => 'DB_ERROR_CONSTRAINT',
			     2714 => 'DB_ERROR_TABLE_ALREADY_EXISTS',
			     1051 => 'DB_ERROR_NOSUCHTABLE',
			     1054 => 'DB_ERROR_NOSUCHFIELD',
			     1060 => 'DB_ERROR_COLUMN_ALREADY_EXISTS',
			     1061 => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
			     2627 => 'DB_ERROR_RECORD_ALREADY_EXISTS',
			     102  => 'DB_ERROR_SYNTAX',
			     8120 => 'DB_ERROR_GROUP_BY_SYNTAX',
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
  
  
  /**
     \brief      Ouverture d'une connexion vers le serveur et éventuellement une database.
     \param      type		Type de base de données (mysql ou pgsql)
     \param	    host		Addresse de la base de données
     \param	    user		Nom de l'utilisateur autorisé
     \param	    pass		Mot de passe
     \param	    name		Nom de la database
     \param	    port		Port of database server
     \return     int			1 en cas de succès, 0 sinon
  */
  function DoliDb($type='mssql', $host, $user, $pass, $name='', $port=0)
  {
    global $conf,$langs;

	$this->database_user=$user;
    $this->transaction_opened=0;
        
    if (! function_exists("mssql_connect"))
    {
    	$this->connected = 0;
    	$this->ok = 0;
    	$this->error="Mssql PHP functions for using MSSql driver are not available in this version of PHP";
    	dolibarr_syslog("DoliDB::DoliDB : MSsql PHP functions for using MSsql driver are not available in this version of PHP",LOG_ERR);
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
    	// Si client connecté avec charset different de celui de la base Dolibarr
    	// (La base Dolibarr a été forcée en this->forcecharset à l'install)
    	$this->connected = 1;
    	$this->ok = 1;
    }
    else
    {
    	// host, login ou password incorrect
    	$this->connected = 0;
    	$this->ok = 0;
    	$this->error=mssql_get_last_message();
		dolibarr_syslog("DoliDB::DoliDB : Erreur Connect mssql_get_last_message=".$this->error,LOG_ERR);
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
    		dolibarr_syslog("DoliDB::DoliDB : Erreur Select_db ".$this->error,LOG_ERR);
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
     \param	    database		Nom de la database
     \return	    boolean         true si ok, false si ko
  */
  function select_db($database)
  {
    return mssql_select_db($database, $this->db);
  }

  /**
     \brief		Connection vers le serveur
     \param	    host		addresse de la base de données
     \param	    login		nom de l'utilisateur autoris
     \param	    passwd		mot de passe
     \param		name		nom de la database (ne sert pas sous mysql, sert sous pgsql)
	 \param		port		Port of database server
     \return	resource	handler d'accès à la base
     \seealso	close
  */
  function connect($host, $login, $passwd, $name, $port=0)
  {
    dolibarr_syslog("DoliDB::connect host=$host, port=$port, login=$login, passwd=--hidden--, name=$name");
	$newhost=$host;
	if ($port) $newhost.=':'.$port;
    $this->db  = @mssql_connect($newhost, $login, $passwd);
    //force les enregistrement en latin1 si la base est en utf8 par défaut
    // Supprimé car plante sur mon PHP-Mysql. De plus, la base est forcement en latin1 avec
	// les nouvelles version de Dolibarr car forcé par l'install Dolibarr.
	//$this->query('SET NAMES '.$this->forcecharset);
    //print "Resultat fonction connect: ".$this->db;
    return $this->db;
  }
    
  /**
     \brief          Renvoie la version du serveur
     \return	        string      Chaine version
  */
  function getVersion()
  {
    $resql=$this->query("SELECT @@VERSION");
    $version=$this->fetch_array($resql);
	  return $version['computed'];
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
    return mssql_close($this->db);
  }
  

	/**
		\brief      Debut d'une transaction.
		\return	    int         1 si ouverture transaction ok ou deja ouverte, 0 en cas d'erreur
	*/
	function begin()
	{
		if (! $this->transaction_opened)
		{
			$ret=$this->query("BEGIN TRANSACTION");
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
		if ($this->transaction_opened <= 1)
		{
			$ret=$this->query("COMMIT TRANSACTION");
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
	$ret=$this->query("ROLLBACK TRANSACTION");
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
     \brief      Effectue une requete et renvoi le resultset de réponse de la base
     \param	    query	    Contenu de la query
     \return	    resource    Resultset de la reponse
  */
  function query($query)
  {
    $query = trim($query);
    
    // Conversion syntaxe MySql vers MSDE.
    $query = str_ireplace("now()", "getdate()", $query);
	// Erreur SQL: cannot update timestamp field
    $query = str_ireplace(", tms = tms", "", $query);
	// Voir si l'on peut directement utiliser $query = str_ireplace("file", "[file]", $query);
	// au lieu des 3 lignes ci-dessous
    $query = str_ireplace(".file", ".[file]", $query);
    $query = str_ireplace(" file ", " [file] ", $query);
    $query = str_ireplace(" file,", " [file],", $query);
	// Idem file
    $query = str_ireplace(".percent", ".[percent]", $query);
    $query = str_ireplace(" percent ", " [percent] ", $query);
    $query = str_ireplace("percent,", "[percent],", $query);
    $query = str_ireplace("percent=", "[percent]=", $query);
    $query = str_ireplace("\'", "''", $query);
    
    
    $itemfound = stripos($query, " limit ");
    if ($itemfound !== false) {
	    // Extraire le nombre limite
	    $number = stristr($query, " limit ");
	    $number = substr($number, 7);
	    // Insérer l'instruction TOP et le nombre limite
	    $query = str_ireplace("select ", "select top ".$number." ", $query);
	    // Supprimer l'instruction MySql
	    $query = str_ireplace(" limit ".$number, "", $query);
    }
    
    $itemfound = stripos($query, " week(");
    if ($itemfound !== false) {
	    // Recréer une requête sans instruction Mysql
	    $positionMySql = stripos($query, " week(");
	    $newquery = substr($query, 0, $positionMySql);

	    // Récupérer la date passée en paramètre
	    $extractvalue = stristr($query, " week(");
	    $extractvalue = substr($extractvalue, 6);
	    $positionMySql = stripos($extractvalue, ")");
	    // Conserver la fin de la requête
	    $endofquery = substr($extractvalue, $positionMySql);
	    $extractvalue = substr($extractvalue, 0, $positionMySql);

	    // Remplacer l'instruction MySql en Sql Server
	    // Insérer la date en paramètre et le reste de la requête
	    $query = $newquery." DATEPART(week, ".$extractvalue.$endofquery;
    }
    
    //print "<!--".$query."-->";
    
    if (! $this->database_name)
    {
    	// Ordre SQL ne nécessitant pas de connexion à une base (exemple: CREATE DATABASE)
	    $ret = mssql_query($query, $this->db);
    }
    else
    {
    	$ret = mssql_query($query, $this->db);
    }
    
    if (! eregi("^COMMIT",$query) && ! eregi("^ROLLBACK",$query))
    {
    	// Si requete utilisateur, on la sauvegarde ainsi que son resultset
	    if (! $ret)
	    {
	      $this->lastqueryerror = $query;

        $result = mssql_query("SELECT @@ERROR as code", $this->db);
        $row = mssql_fetch_array($result);
	    
	      $this->lasterror = $this->error();
	      $this->lasterrno = $row["code"];
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
        return mssql_fetch_object($resultset);
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
        return mssql_fetch_array($resultset);
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
        return @mssql_fetch_row($resultset);
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
        return mssql_num_rows($resultset);
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
      // mssql necessite un link de base pour cette fonction contrairement
      // a pqsql qui prend un resultset
      $rsRows = mssql_query("select @@rowcount as rows", $this->db);
      return mssql_result($rsRows, 0, "rows");
      //return mssql_affected_rows($this->db);
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
        if (is_resource($resultset)) mssql_free_result($resultset);
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
        \brief      Formatage (par la base de données) d'un champ de la base au format TMS ou Date (YYYY-MM-DD HH:MM:SS)
                    afin de retourner une donnée toujours au format universel date TMS unix.
                    Fonction à utiliser pour générer les SELECT.
        \param	    param       Nom champ base de type date ou chaine 'YYYY-MM-DD HH:MM:SS'
        \return	    date        Date au format TMS.
    */
    function pdate($param)
    {
        return "dbo.unix_timestamp(".$param.")";
    }

    /**
        \brief      Formatage (par PHP) d'une date vers format texte pour insertion dans champ date.
                    Fonction à utiliser pour générer les INSERT.
        \param	    param       Date TMS à convertir
        \return	    date        Date au format texte YYYYMMDDHHMMSS.
    */
    function idate($param)
    {
        //return "dbo.from_unixtime(".$param.")";
        return adodb_strftime("%d/%m/%Y %H:%M:%S",$param);
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
        if (! $this->connected) {
            // Si il y a eu echec de connexion, $this->db n'est pas valide.
            return 'DB_ERROR_FAILED_TO_CONNECT';
        }
        else {
            if (isset($this->errorcode_map[$this->lasterrno])) {
                return $this->errorcode_map[$this->lasterrno];
            }
            $errno=$this->lasterrno;
            return ($errno?'DB_ERROR_'.$errno:'0');
        }
    }

    /**
        \brief     Renvoie le texte de l'erreur mssql de l'operation precedente.
        \return    error_text
    */
    function error()
    {
        if (! $this->connected) {
            // Si il y a eu echec de connexion, $this->db n'est pas valide pour mssql_get_last_message.
            return 'Not connected. Check setup parameters in conf/conf.php file and your mssql client and server versions';
        }
        else {
            return mssql_get_last_message($this->db);
        }
    }

    /**
        \brief     Récupère l'id genéré par le dernier INSERT.
        \param     tab     Nom de la table concernée par l'insert. Ne sert pas sous mssql mais requis pour compatibilité avec Postgresql
        \return    int     id
    */
    function last_insert_id($tab)
    {
    	$res = $this->query("SELECT @@IDENTITY as id");
    	if ($data = $this->fetch_array($res))
    	{
    		return $data["id"];
    	}
    	else
    	{
    		return -1;
    	}
    }


  // Next function are not required. Only minor features use them.
  //--------------------------------------------------------------



  /**
     \brief          Renvoie l'id de la connexion
     \return	        string      Id connexion
  */
  function getConnectId()
  {
    $resql=$this->query('SELECT CONNECTION_ID()');
    $row=$this->fetch_row($resql);
    return $row[0];
  }

  /**
     \brief          Renvoie la commande sql qui donne les droits à user sur toutes les tables
     \param          databaseuser    User à autoriser
     \return	        string          Requete sql
  */
  function getGrantForUserQuery($databaseuser)
  {
	  /*
	  $query = "DECLARE @tables TABLE(ROWID int IDENTITY(1,1), SQLSTR varchar(500)) INSERT INTO @tables SELECT '"
	  $query .= "GRANT SELECT ON '+NAME+' TO ".$databaseuser." FROM sysobjects WHERE TYPE = 'U' AND "
	  $query .= "NAME NOT LIKE 'SYNC%' DECLARE   @rowid int, @sqlstr varchar(500) SET @rowid = 0 "
	  $query .= "SET @sqlstr = '' DECLARE grant_tbl_cursor CURSOR FOR SELECT ROWID, SQLSTR FROM @tables ORDER BY ROWID"
	  $query .= "OPEN grant_tbl_cursor FETCH NEXT FROM grant_tbl_cursor INTO @rowid,@sqlstr WHILE @@FETCH_STATUS = 0 "
	  $query .= "BEGIN EXECUTE (@sqlstr) FETCH NEXT FROM grant_tbl_cursor INTO @rowid,@sqlstr END CLOSE grant_tbl_cursor "
	  $query .= "DEALLOCATE grant_tbl_cursor"
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
     \remarks        Ne pas utiliser les fonctions xxx_create_db (xxx=mssql, ...) car elles sont deprecated
     On force creation de la base avec le charset forcecharset
  */
  function DDLCreateDb($database)
  {
    // ALTER DATABASE dolibarr_db DEFAULT CHARACTER SET latin DEFAULT COLLATE latin1_swedish_ci
    $sql = 'CREATE DATABASE '.$database;
    $sql.= ' DEFAULT CHARACTER SET '.$this->forcecharset.' DEFAULT COLLATE '.$this->forcecollate;
    $ret=$this->query($sql);
    if (! $ret)
      {
	// On réessaie pour compatibilité avec mssql < 5.0
	$sql = 'CREATE DATABASE '.$database;
	$ret=$this->query($sql);
      }
    
    return $ret;
  }
  
  /**
     \brief      Liste des tables dans une database.
     \param	    database	Nom de la database
     \return	    resource
  */
  function DDLListTables($database)
  {
    $this->results = mssql_list_tables($database, $this->db);
    return $this->results;
  }
  
  /**
     \brief      Crée une table
     \param	    table 			Nom de la table
     \param	    fields 			Tableau associatif [nom champ][tableau des descriptions]
     \param	    primary_key 	Nom du champ qui sera la clef primaire
     \param	    unique_keys 	Tableau associatifs Nom de champs qui seront clef unique => valeur
     \param	    fulltext 		Tableau des Nom de champs qui seront indexés en fulltext
     \param	    key 			Tableau des champs clés noms => valeur
     \param	    type 			Type de la table
     \return	    int				<0 si KO, >=0 si OK
  */
	function DDLCreateTable($table,$fields,$primary_key,$type,$unique_keys="",$fulltext_keys="",$keys="")
	{
		// clés recherchées dans le tableau des descriptions (fields) : type,value,attribute,null,default,extra
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
	
		dolibarr_syslog($sql);
		if(! $this -> query($sql))
			return -1;
		else
			return 1;
	}

	/**
        \brief      décrit une table dans une database.
		\param	    table	Nom de la table
		\param	    field	Optionnel : Nom du champ si l'on veut la desc d'un champ
        \return	    resource
    */
	function DDLDescTable($table,$field="")
    {
		$sql="DESC ".$table." ".$field;

		dolibarr_syslog($sql);
		$this->results = $this->query($sql);
		return $this->results;
    }

	/**
		\brief      Insère un nouveau champ dans une table
		\param	    table 			Nom de la table
		\param		field_name 		Nom du champ à insérer
		\param	    field_desc 		Tableau associatif de description duchamp à insérer[nom du paramètre][valeur du paramètre]
		\param	    field_position 	Optionnel ex.: "after champtruc"
		\return	    int				<0 si KO, >0 si OK
    */
	function DDLAddField($table,$field_name,$field_desc,$field_position="")
	{
		// clés recherchées dans le tableau des descriptions (field_desc) : type,value,attribute,null,default,extra
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

		if(! $this -> query($sql))
			return -1;
		else
			return 1;
	}
	
	function getDefaultCharacterSetDatabase(){
		 /*
		 $resql=$this->query('SHOW VARIABLES LIKE \'character_set_database\'');
		  if (!$resql)
	    {
			  return $this->forcecharset;
	    }
	    $liste=$this->fetch_array($resql);
	    return $liste['Value'];
	    */
	    return '';
	}
	
	function getListOfCharacterSet(){
		/*
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
	   		return null;
	  	}
    	return $liste;
    	*/
    	return ''; // attente débuggage
	}
	
	function getDefaultCollationDatabase(){
		$resql=$this->query("SELECT SERVERPROPERTY('collation')");
		 if (!$resql)
	   {
			return $this->forcecollate;
	   }
	   $liste=$this->fetch_array($resql);
	   return $liste['computed'];
	}
	
	function getListOfCollation(){
		/*
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
	   		return null;
	  	}
    	return $liste;
    	*/
    	return ''; // attente débugage
	}
	
}

?>
