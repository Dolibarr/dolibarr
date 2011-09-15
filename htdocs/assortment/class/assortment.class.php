<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *      \file       dev/skeletons/assortment.class.php
 *      \ingroup    mymodule othermodule1 othermodule2
 *      \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *		\version    $Id: assortment.class.php,v 1.29 2010/04/29 14:54:13 grandoc Exp $
 *		\author		Put author name here
 *		\remarks	Initialy built by build_class_from_table on 2011-05-21 20:20
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");


/**
 *      \class      Assortment
 *      \brief      Put here description of your class
 *		\remarks	Initialy built by build_class_from_table on 2011-05-21 20:20
 */
class Assortment // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='assortment';			//!< Id that identify managed objects
	//var $table_element='assortment';	//!< Name of table without prefix where object is stored
    
    var $id;
    
	var $label;
	var $datec='';
	var $tms='';
	var $fk_user_author;
	var $fk_user_mod;
	var $fk_soc;
	var $fk_prod;
	
	//assortment complaiant variable
	var $s_soc_name;
	var $s_prod_name;
	var $s_pathCateg;
    var $s_prod_ref;

	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Assortment($DB) 
    {
        $this->db = $DB;
        return 1;
    }

 	function __toString() {
        return ' Assotiment id='.$this->id.' label='.$this->label.' soc='.$this->fk_soc.' prod='.$this->fk_prod.'<BR>';
    }
	
    /**
     *      \brief      Create in database
     *      \param      user        	User that create
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;
    	
		// Clean parameters
        
		if (isset($this->label)) $this->label=trim($this->label);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);
		if (isset($this->fk_soc)) $this->fk_soc=trim($this->fk_soc);
		if (isset($this->fk_prod)) $this->fk_prod=trim($this->fk_prod);
		
		//Check if assortment already exist
		$sql = "SELECT count(*)";
		$sql.= " FROM ".MAIN_DB_PREFIX."assortment";
		$sql.= " WHERE fk_soc = '" .$this->fk_soc."'";
		$sql.= " AND fk_prod = '" .$this->fk_prod."'";

		$result = $this->db->query($sql) ;
		if ($result)
		{
			$row = $this->db->fetch_array($result);
			if ($row[0] == 0)
			{
		        $now=dol_now();
		
				// Check parameters
				// Put here code to add control on parameters values
				
		        // Insert request
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."assortment(";
				
				$sql.= "label,";
				$sql.= "datec,";
				$sql.= "fk_user_author,";
				$sql.= "fk_user_mod,";
				$sql.= "fk_soc,";
				$sql.= "fk_prod";
		
				
		        $sql.= ") VALUES (";
		        
				$sql.= " ".(! isset($this->label)?'NULL':"'".addslashes($this->label)."'").",";
				$sql.= "'".$this->db->idate($now)."',";
				$sql.= "'".$user->id."',";
				$sql.= "'".$user->id."',";
				$sql.= " ".(! isset($this->fk_soc)?'NULL':"'".$this->fk_soc."'").",";
				$sql.= " ".(! isset($this->fk_prod)?'NULL':"'".$this->fk_prod."'")."";
		
		        
				$sql.= ")";
		
				$this->db->begin();
				
			   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		        
		        $resql=$this->db->query($sql);
		    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		        
				if (! $error)
		        {
		            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."assortment");
		    
					if (! $notrigger)
					{
			            // Uncomment this and change MYOBJECT to your own tag if you
			            // want this action call a trigger.
			            
			            //// Call triggers
			            //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			            //$interface=new Interfaces($this->db);
			            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
			            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
			            //// End call triggers
					}
		        }
		
		        // Commit or rollback
		        if ($error)
				{
					foreach($this->errors as $errmsg)
					{
			            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
			            $this->error.=($this->error?', '.$errmsg:$errmsg);
					}	
					$this->db->rollback();
					return -1*$error;
				}
				else
				{
					$this->db->commit();
		            return $this->id;
				}
			}
			
			$this->db->free($result);
			return 1;
		}
		
    }

    
    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " t.label,";
		$sql.= " t.datec,";
		$sql.= " t.tms,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.fk_soc,";
		$sql.= " t.fk_prod";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."assortment as t";
        $sql.= " WHERE t.rowid = ".$id;
    
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                
				$this->label = $obj->label;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->fk_soc = $obj->fk_soc;
				$this->fk_prod = $obj->fk_prod;

                
            }
            else
            {
            	return 0;
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }
    

    /**
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;
    	
		// Clean parameters
        
		if (isset($this->label)) $this->label=trim($this->label);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);
		if (isset($this->fk_soc)) $this->fk_soc=trim($this->fk_soc);
		if (isset($this->fk_prod)) $this->fk_prod=trim($this->fk_prod);


		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."assortment SET";
        
		$sql.= " label=".(isset($this->label)?"'".addslashes($this->label)."'":"null").",";
		$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql.= " fk_user_mod=''".$user->id."'',";
		$sql.= " fk_soc=".(isset($this->fk_soc)?$this->fk_soc:"null").",";
		$sql.= " fk_prod=".(isset($this->fk_prod)?$this->fk_prod:"null")."";

        
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();
        
		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        
		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.
				
	            //// Call triggers
	            //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
	    	}
		}
		
        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}	
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}		
    }
  
  
 	/**
	 *   \brief      Delete object in database
     *	\param      user        	User that delete
     *   \param      notrigger	    0=launch triggers after, 1=disable triggers
	 *	\return		int				<0 if KO, >1 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."assortment";
		$sql.= " WHERE rowid=".$this->id;
	
		$this->db->begin();
		
		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		
        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}	
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *		\brief		Get all assortment 
	 * 	 	\return		string				HTML preformated string
	 */	
	function get_all_assortment()
	{
		dol_syslog(get_class($this).' functions_dolibarr::get_all_assortment', LOG_INFO);
		$sql = "SELECT assort.rowid,"; 
		$sql .= "	assort.label,";
		$sql .= "	assort.datec,";
		$sql .= "	assort.tms,";
		$sql .= "	userAuth.login as CreaUserLogin,";
		$sql .= "	userMod.login as ModUserLogin,";
		$sql .= "	soc.nom as SocName,";
		$sql .= "	prod.label as ProdLabel";
		$sql .= "	FROM ".MAIN_DB_PREFIX."assortment as assort"; 
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."user as userAuth ON userAuth.rowid=assort.fk_user_author";
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."user as userMod ON userMod.rowid=assort.fk_user_mod";
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."product as prod ON prod.rowid=assort.fk_prod";
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid=assort.fk_soc";

		dol_syslog(get_class($this)."::get_all_assortment sql=".$sql, LOG_DEBUG);
		$res = $this->db->query ($sql);
		
		if ($res)
		{
			$assorts = array ();
			$assorts[] = ' <DIV><TABLE><TR><TD>';
			while ($record = $this->db->fetch_array ($res))
			{	
				$ouput = '<TR><TD>';
				$ouput .= ' Assotiment id='.$record['rowid'];
				$ouput .= ' label='.$record['label'];
				$ouput .= ' soc='.$record['SocName'];
				$ouput .= ' prod='.$record['ProdLabel'];
				$ouput .= ' UserCreat='.$record['CreaUserLogin'];
				$ouput .= ' UserMod='.$record['ModUserLogin'];
				$ouput .= ' datec='.$record['datec'];
				$ouput .= ' tms='.$record['tms'];
				$ouput .= '</TR></TD>';
				
				$assorts[] = $ouput;
			}
			$assorts[] = ' </TABLE></DIV>';
			
			$this->db->free($res);
			
			return $assorts;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}	

	/**
	 *		\brief		Get all assortment for a thirds party
	 *		\param      objectid     	Id of a thirdparty
	 * 	 	\return		table			Assortments for the customer
	 */	
	function get_assortment_for_thirdparty($objectid)
	{
		global $conf;
		
		dol_syslog(get_class($this).' functions_dolibarr::get_assortment_for_thirdparty trdprtyID='.$objectid, LOG_DEBUG);
		$sql = "SELECT assort.rowid,"; 
		$sql .= "	assort.label,";
		$sql .= "	soc.nom as SocName,";
		$sql .= "	prod.label as ProdLabel,";
		$sql .= "	prod.ref as ProdRef,";
		$sql .= "	assort.fk_soc,";
		$sql .= "	assort.fk_prod";
		$sql .= "	FROM ".MAIN_DB_PREFIX."assortment as assort"; 
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."user as userAuth ON userAuth.rowid=assort.fk_user_author";
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."user as userMod ON userMod.rowid=assort.fk_user_mod";
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."product as prod ON prod.rowid=assort.fk_prod";
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid=assort.fk_soc";
		$sql .= "	WHERE soc.rowid='".$objectid."';";

		dol_syslog(get_class($this)."::get_assortment_for_thirdparty sql=".$sql, LOG_DEBUG);
		$res = $this->db->query ($sql);
		
		if ($res)
		{
			$assorts = array ();
			
			while ($record = $this->db->fetch_array ($res))
			{	
				//define base attribute
				$assort = new Assortment($this->db);
				$assort->id=$record['rowid'];
				$assort->s_soc_name=$record['SocName'];
				$assort->s_prod_name=$record['ProdLabel'];
				$assort->fk_soc=$record['fk_soc'];
				$assort->fk_prod=$record['fk_prod'];
				$assort->label=$record['label'];
				$assort->s_prod_ref=$record['ProdRef'];
				//find the category path
				if ($conf->global->ASSORTMENT_BY_CAT == 1)
				{
					$sql1 = "SELECT fk_categorie"; 
					$sql1 .= "	FROM ".MAIN_DB_PREFIX."categorie_product"; 
					$sql1 .= "	WHERE fk_product='".$assort->fk_prod."';";
			
					dol_syslog(get_class($this)."::get_assortment_for_thirdparty sql=".$sql1, LOG_DEBUG);
					$res1 = $this->db->query ($sql1);
					if ($res1)
					{
						while ($record1 = $this->db->fetch_array ($res1))
						{			
							$cat = new Categorie($this->db);
							$cat->fetch($record1['fk_categorie']);
							$s_ways='';
							$ways = $cat->print_all_ways();
							foreach ($ways as &$way)
							{
								$s_ways.=$way.' ';	
							} 
							$assort->s_pathCateg=$s_ways;
						}
						$this->db->free($res1);
					}
					else
					{
						dol_print_error ($this->db);
						return -1;
					}
				
					
				}
				$assorts[] = $assort;
			}
			
			$this->db->free($res);
			
			return $assorts;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}


	/**
	 *		\brief		Get all assortment for a thirds party
	 *		\param      objectid     	Id of a product
	 *		\param      $type	     	supplier for display supplier assortment, customer to display customer list 
	 * 	 	\return		table			Assortments for the customer
	 */	
	function get_assortment_for_product($objectid,$type)
	{
		global $conf;
		
		dol_syslog(get_class($this).' functions_dolibarr::get_assortment_for_product productid='.$objectid, LOG_DEBUG);
		$sql = "SELECT assort.rowid,"; 
		$sql .= "	assort.label,";
		$sql .= "	soc.nom as SocName,";
		$sql .= "	prod.label as ProdLabel,";
		$sql .= "	prod.ref as ProdRef,";
		$sql .= "	assort.fk_soc,";
		$sql .= "	assort.fk_prod";
		$sql .= "	FROM ".MAIN_DB_PREFIX."assortment as assort"; 
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."user as userAuth ON userAuth.rowid=assort.fk_user_author";
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."user as userMod ON userMod.rowid=assort.fk_user_mod";
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."product as prod ON prod.rowid=assort.fk_prod";
		$sql .= "	INNER JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid=assort.fk_soc";
		$sql .= "	WHERE prod.rowid='".$objectid."'";
		if ($type=='supplier') $sql .= "	AND soc.fournisseur<>'0';";
		if ($type=='customer') $sql .= "	AND soc.client<>'0';";

		dol_syslog(get_class($this)."::get_assortment_for_product sql=".$sql, LOG_DEBUG);
		$res = $this->db->query ($sql);
		
		if ($res)
		{
			$assorts = array ();
			
			while ($record = $this->db->fetch_array ($res))
			{	
				//define base attribute
				$assort = new Assortment($this->db);
				$assort->id=$record['rowid'];
				$assort->s_soc_name=$record['SocName'];
				$assort->s_prod_name=$record['ProdLabel'];
				$assort->fk_soc=$record['fk_soc'];
				$assort->fk_prod=$record['fk_prod'];
				$assort->label=$record['label'];
				$assort->s_prod_ref=$record['ProdRef'];
				
				//find the category path
				if ($conf->global->ASSORTMENT_BY_CAT == 1)
				{
					$sql1 = "SELECT fk_categorie"; 
					if ($type=='supplier') $sql1 .= "	FROM ".MAIN_DB_PREFIX."categorie_fournisseur"; 
					if ($type=='customer') $sql1 .= "	FROM ".MAIN_DB_PREFIX."categorie_societe";
					$sql1 .= "	WHERE fk_societe='".$assort->fk_soc."';";
			
					dol_syslog(get_class($this)."::get_assortment_for_product sql=".$sql1, LOG_DEBUG);
					$res1 = $this->db->query ($sql1);
					if ($res1)
					{
						while ($record1 = $this->db->fetch_array ($res1))
						{			
							$cat = new Categorie($this->db);
							$cat->fetch($record1['fk_categorie']);
							$waystb = array();
							$ways = $cat->print_all_ways();
							foreach ($ways as $way)
							{
								//Add way if it not already exists in the table
								if (!in_array($way,$waystb))
								{
									$waystb[]=$way;	
								}
							} 
							$assort->s_pathCateg=implode (' ',$waystb);
						}
						$this->db->free($res1);
					}
					else
					{
						dol_print_error ($this->db);
						return -1;
					}
				
					
				}
				$assorts[] = $assort;
			}
			
			$this->db->free($res);
			
			return $assorts;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}


	/**
	 *		\brief		Return a table of product Id that are linked to product category id
	 *		\param      idcat     	Id of the product category 
	 * 	 	\return		table		Table of product Id that are diretly linked to product category id
	 */	
	function get_all_prod_by_categ($idcat)
	{
		global $conf;
		
		dol_syslog('functions::get_all_prod_by_categ Category='.$idcat, LOG_DEBUG);
		
		$prods=array();
		if ($conf->global->ASSORTMENT_BY_CAT_RECURSIVE == 1)
		{
			//Get all product from child branch before the direct one.
			$prods = $this->get_all_prod_by_categ_child($idcat);
		}
		
		//Get product direct associate with the product
		$sql = "SELECT fk_product"; 
		$sql .= "	FROM ".MAIN_DB_PREFIX."categorie_product"; 
		$sql .= "	WHERE fk_categorie='".$idcat."'";
		
		dol_syslog(get_class($this)."::get_all_prod_by_categ sql=".$sql, LOG_DEBUG);
		$res = $this->db->query ($sql);
		
		if ($res)
		{
			while ($record = $this->db->fetch_array ($res))
			{	
				//Add product key to return table if it not always set in the table
				if (!in_array($record["fk_product"],$prods))
				{
					$prods[]=$record["fk_product"];
				}
			}
			$this->db->free($res);
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
		return $prods;
	}

	/**
	 *		\brief		Return a table of product Id that are recursivly linked to product category id child
	 *		\param      idcat     	Id of the product category 
	 * 	 	\return		table		Table of product Id that are linked to product category id
	 */		
	function get_all_prod_by_categ_child($idcat)
	{
		dol_syslog('functions_dolibarr::get_all_prod_by_categ_child Mother Category='.$idcat, LOG_DEBUG);
		
		$sql = "SELECT catprod.fk_product, relcat.fk_categorie_fille"; 
		$sql .= "	FROM ".MAIN_DB_PREFIX."categorie_association as relcat";
		$sql .= "	LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_product as catprod ON relcat.fk_categorie_fille=catprod.fk_categorie";
		$sql .= "	WHERE relcat.fk_categorie_mere='".$idcat."'";
	
		dol_syslog(get_class($this)."::get_all_prod_by_categ_child sql=".$sql, LOG_DEBUG);
		$res = $this->db->query ($sql);
		
		if ($res)
		{ 
			$prods = array ();
			
			while ($record = $this->db->fetch_array ($res))
			{
				if ($record["fk_product"])
				{	
					//Add prodct key to return table if it not always set in the table
					if (!in_array($record["fk_product"],$prods))
					{
						$prods[]=$record["fk_product"];
					}
				}
				
				//recursive call to find all product link to child category
				$prodchild=$this->get_all_prod_by_categ_child($record["fk_categorie_fille"]);
			
				foreach($prodchild as &$val)
				{
					//Add prodct key to return table if it not always set in the table
					if (!in_array($val,$prods))
					{
						$prods[]=$val;
					}
				}
				
			}			
			$this->db->free($res);
			
			return $prods;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}
	
	/**
	 *		\brief		Return a table of thirdparty ID that arelinked to Customer/Supplier category id child
	 *		\param      idcat     	Id of the Customer/Supplier category 
	 * 	 	\return		table		Table of thirdparty ID that are linked to Customer/Supplier category id child
	 * 
	 */	
	function get_all_customer_by_categ($idcat)
	{
		global $conf;
		
		dol_syslog('functions::get_all_customer_by_categ Category='.$idcat, LOG_DEBUG);
		
		$custs=array();
		
		if ($conf->global->ASSORTMENT_BY_CAT_RECURSIVE == 1)
		{
			//Get all product from child branch before the direct one.
			$custs = $this->get_all_customer_by_categ_child($idcat);
		}
		
		//Get product direct associate with the product
		$sql = "(SELECT fk_societe"; 
		$sql .= "	FROM ".MAIN_DB_PREFIX."categorie_fournisseur"; 
		$sql .= "	WHERE fk_categorie='".$idcat."')";
		$sql .= " UNION ";
		$sql .= "(SELECT fk_societe"; 
		$sql .= "	FROM ".MAIN_DB_PREFIX."categorie_societe"; 
		$sql .= "	WHERE fk_categorie='".$idcat."')";
		
		dol_syslog(get_class($this)."::get_all_customer_by_categ sql=".$sql, LOG_DEBUG);
		$res = $this->db->query ($sql);
		
		if ($res)
		{
			while ($record = $this->db->fetch_array ($res))
			{	
				//Add product key to return table if it not always set in the table
				if (!in_array($record["fk_societe"],$custs))
				{
					$custs[]=$record["fk_societe"];
				}
			}
			$this->db->free($res);
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
		return $custs;
	}

	/**
	 *		\brief		Return a table of thirdparty ID that are recursivly linked to Customer/Supplier category id child
	 *		\param      idcat     	Id of the Customer/Supplier category 
	 * 	 	\return		table		Table of thirdparty ID that are recursivly linked to Customer/Supplier category id child
	 */		
	function get_all_customer_by_categ_child($idcat)
	{
		dol_syslog('functions_dolibarr::get_all_customer_by_categ_child Mother Category='.$idcat, LOG_DEBUG);
		
		$sql = "(SELECT catfour.fk_societe, relcat.fk_categorie_fille"; 
		$sql .= "	FROM ".MAIN_DB_PREFIX."categorie_association as relcat";
		$sql .= "	LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_fournisseur as catfour ON relcat.fk_categorie_fille=catfour.fk_categorie";
		$sql .= "	WHERE relcat.fk_categorie_mere='".$idcat."')";
		$sql .= " UNION ";
		$sql .= "(SELECT catsoc.fk_societe, relcat.fk_categorie_fille"; 
		$sql .= "	FROM ".MAIN_DB_PREFIX."categorie_association as relcat";
		$sql .= "	LEFT OUTER JOIN ".MAIN_DB_PREFIX."categorie_societe as catsoc ON relcat.fk_categorie_fille=catsoc.fk_categorie";
		$sql .= "	WHERE relcat.fk_categorie_mere='".$idcat."')";
	
		dol_syslog(get_class($this)."::get_all_customer_by_categ_child sql=".$sql, LOG_DEBUG);
		$res = $this->db->query ($sql);
		
		if ($res)
		{ 
			$custs = array ();
			
			while ($record = $this->db->fetch_array ($res))
			{
				if ($record["fk_societe"])
				{	
					//Add prodct key to return table if it not always set in the table
					if (!in_array($record["fk_societe"],$custs))
					{
						$custs[]=$record["fk_societe"];
					}
				}
				
				//recursive call to find all product link to child category
				$custschild=$this->get_all_customer_by_categ_child($record["fk_categorie_fille"]);
			
				foreach($custschild as &$val)
				{
					//Add prodct key to return table if it not always set in the table
					if (!in_array($val,$custs))
					{
						$custs[]=$val;
					}
				}
				
			}			
			$this->db->free($res);
			
			return $custs;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}
	
	/**
	 *		\brief		Remove all product of an assortment by category 
	 *		\param      user     		User that delete
	 *		\param      catToRemove     Category id to link with product to remove to assortment
	 *		\param      typecat     type cat if 0:category customer,1: category product,2:category supplier
	 *		\return		int				<0 if KO, >1 if OK
	 */		
	function remove_category($user,$catToRemove,$typecat)
	{
		global $conf, $langs;
		$error=0;
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."assortment";
				
		if ($typecat=="RemoveCatProd") // remove product category from customer/supplier assortment
		{
			$sql.= " WHERE fk_prod IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie='".$catToRemove."')";
			$sql.= " AND fk_soc='".$this->fk_soc."'";
		}
		if ($typecat=="RemoveCatCustomer")
		{
			$sql.= " WHERE fk_soc IN (SELECT fk_societe FROM ".MAIN_DB_PREFIX."categorie_societe WHERE fk_categorie='".$catToRemove."')";
			$sql.= " AND fk_prod='".$this->fk_prod."'";
		}
		if ($typecat=="RemoveCatSupplier")
		{
			$sql.= " WHERE fk_soc IN (SELECT fk_societe FROM ".MAIN_DB_PREFIX."categorie_fournisseur WHERE fk_categorie='".$catToRemove."')";
			$sql.= " AND fk_prod='".$this->fk_prod."'";
		}
		
		$this->db->begin();
		
		dol_syslog(get_class($this)."::delete remove_category sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		
        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete remove_category ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}	
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	
	}
	
	function getSocNomUrl()
	{
		$cust = new Societe($this->db);
		$cust->fetch($this->fk_soc);
		
		return $cust->getNomUrl(1);
	}
	
	function getProdNomUrl()
	{
		$prod = new Product($this->db);
		$prod->fetch($this->fk_prod);
		
		return $prod->getNomUrl(1);
	}
	
	
}
?>
