<?php
/* Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Juanjo Menent	      <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/fourn/class/fournisseur.commande.dispatch.class.php
 *  \ingroup    fournisseur stock
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *              Initialy built by build_class_from_table on 2015-02-24 10:38
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT."/reception/class/reception.class.php";


/**
 *  Class to manage table commandefournisseurdispatch
 */
class CommandeFournisseurDispatch extends CommonObject
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error;

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	/**
	 * @var string ID to identify managed object
	 */
	public $element='commandefournisseurdispatch';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='commande_fournisseur_dispatch';		//!< Name of table without prefix where object is stored
	public $lines=array();

    /**
	 * @var int ID
	 */
	public $id;

	/**
     * @var int ID
     */
	public $fk_commande;

	/**
     * @var int ID
     */
	public $fk_product;

	/**
     * @var int ID
     */
	public $fk_commandefourndet;

	public $qty;

	/**
     * @var int ID
     */
	public $fk_entrepot;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	public $datec='';
	public $comment;

	/**
	 * @var int Status
	 */
	public $status;

	public $tms='';
	public $batch;
	public $eatby='';
	public $sellby='';




    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        // List of language codes for status
        $this->statuts[0] = 'Received';
        $this->statuts[1] = 'Verified';
        $this->statuts[2] = 'Denied';
        $this->statutshort[0] = 'Received';
        $this->statutshort[1] = 'Verified';
        $this->statutshort[2] = 'Denied';
    }


    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
    	global $conf, $langs, $hookmanager;
		$error=0;

		// Clean parameters

		if (isset($this->fk_commande)) $this->fk_commande=trim($this->fk_commande);
		if (isset($this->fk_product)) $this->fk_product=trim($this->fk_product);
		if (isset($this->fk_commandefourndet)) $this->fk_commandefourndet=trim($this->fk_commandefourndet);
		if (isset($this->qty)) $this->qty=trim($this->qty);
		if (isset($this->fk_entrepot)) $this->fk_entrepot=trim($this->fk_entrepot);
		if (isset($this->fk_user)) $this->fk_user=trim($this->fk_user);
		if (isset($this->comment)) $this->comment=trim($this->comment);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->batch)) $this->batch=trim($this->batch);
		if(empty($this->datec)) $this->datec = dol_now();


		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
		$sql.= "fk_commande,";
		$sql.= "fk_product,";
		$sql.= "fk_commandefourndet,";
		$sql.= "qty,";
		$sql.= "fk_entrepot,";
		$sql.= "fk_user,";
		$sql.= "datec,";
		$sql.= "comment,";
		$sql.= "status,";
		$sql.= "batch,";
		$sql.= "eatby,";
		$sql.= "sellby,";
		$sql.= "fk_reception";


        $sql.= ") VALUES (";
		$sql.= " ".(! isset($this->fk_commande)?'NULL':"'".$this->db->escape($this->fk_commande)."'").",";
		$sql.= " ".(! isset($this->fk_product)?'NULL':"'".$this->db->escape($this->fk_product)."'").",";
		$sql.= " ".(! isset($this->fk_commandefourndet)?'NULL':"'".$this->db->escape($this->fk_commandefourndet)."'").",";
		$sql.= " ".(! isset($this->qty)?'NULL':"'".$this->db->escape($this->qty)."'").",";
		$sql.= " ".(! isset($this->fk_entrepot)?'NULL':"'".$this->db->escape($this->fk_entrepot)."'").",";
		$sql.= " ".(! isset($this->fk_user)?'NULL':"'".$this->db->escape($this->fk_user)."'").",";
		$sql.= " ".(! isset($this->datec) || dol_strlen($this->datec)==0?'NULL':"'".$this->db->idate($this->datec)."'").",";
		$sql.= " ".(! isset($this->comment)?'NULL':"'".$this->db->escape($this->comment)."'").",";
		$sql.= " ".(! isset($this->status)?'NULL':"'".$this->db->escape($this->status)."'").",";
		$sql.= " ".(! isset($this->batch)?'NULL':"'".$this->db->escape($this->batch)."'").",";
		$sql.= " ".(! isset($this->eatby) || dol_strlen($this->eatby)==0?'NULL':"'".$this->db->idate($this->eatby)."'").",";
		$sql.= " ".(! isset($this->sellby) || dol_strlen($this->sellby)==0?'NULL':"'".$this->db->idate($this->sellby)."'").",";
		$sql.= " ".(! isset($this->fk_reception)?'NULL':"'".$this->db->escape($this->fk_reception)."'")."";
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_CREATE',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			}
        }

		// Actions on extra fields (by external module or standard code)
		// TODO le hook fait double emploi avec le trigger !!
		$hookmanager->initHooks(array('commandefournisseurdispatchdao'));
		$parameters=array('id'=>$this->id);
		$reshook=$hookmanager->executeHooks('insertExtraFields', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
		if (empty($reshook))
		{
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$result=$this->insertExtraFields();

				if ($result < 0)
				{
					$error++;
				}
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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


    /**
     *  Load object in memory from the database
     *
     *  @param	int		$id    	Id object
     *  @param	string	$ref	Ref
     *  @return int          	<0 if KO, >0 if OK
     */
    public function fetch($id, $ref = '')
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.fk_commande,";
		$sql.= " t.fk_product,";
		$sql.= " t.fk_commandefourndet,";
		$sql.= " t.qty,";
		$sql.= " t.fk_entrepot,";
		$sql.= " t.fk_user,";
		$sql.= " t.datec,";
		$sql.= " t.comment,";
		$sql.= " t.status,";
		$sql.= " t.tms,";
		$sql.= " t.batch,";
		$sql.= " t.eatby,";
		$sql.= " t.sellby,";
		$sql.= " t.fk_reception";


        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        if ($ref) $sql.= " WHERE t.ref = '".$ref."'";
        else $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch");
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;

				$this->fk_commande = $obj->fk_commande;
				$this->fk_product = $obj->fk_product;
				$this->fk_commandefourndet = $obj->fk_commandefourndet;
				$this->qty = $obj->qty;
				$this->fk_entrepot = $obj->fk_entrepot;
				$this->fk_user = $obj->fk_user;
				$this->datec = $this->db->jdate($obj->datec);
				$this->comment = $obj->comment;
				$this->status = $obj->status;
				$this->tms = $this->db->jdate($obj->tms);
				$this->batch = $obj->batch;
				$this->eatby = $this->db->jdate($obj->eatby);
				$this->sellby = $this->db->jdate($obj->sellby);
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Update object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    public function update($user, $notrigger = 0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->fk_commande)) $this->fk_commande=trim($this->fk_commande);
		if (isset($this->fk_product)) $this->fk_product=trim($this->fk_product);
		if (isset($this->fk_commandefourndet)) $this->fk_commandefourndet=trim($this->fk_commandefourndet);
		if (isset($this->qty)) $this->qty=trim($this->qty);
		if (isset($this->fk_entrepot)) $this->fk_entrepot=trim($this->fk_entrepot);
		if (isset($this->fk_user)) $this->fk_user=trim($this->fk_user);
		if (isset($this->comment)) $this->comment=trim($this->comment);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->batch)) $this->batch=trim($this->batch);



		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";

		$sql.= " fk_commande=".(isset($this->fk_commande)?$this->fk_commande:"null").",";
		$sql.= " fk_product=".(isset($this->fk_product)?$this->fk_product:"null").",";
		$sql.= " fk_commandefourndet=".(isset($this->fk_commandefourndet)?$this->fk_commandefourndet:"null").",";
		$sql.= " qty=".(isset($this->qty)?$this->qty:"null").",";
		$sql.= " fk_entrepot=".(isset($this->fk_entrepot)?$this->fk_entrepot:"null").",";
		$sql.= " fk_user=".(isset($this->fk_user)?$this->fk_user:"null").",";
		$sql.= " datec=".(dol_strlen($this->datec)!=0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql.= " comment=".(isset($this->comment)?"'".$this->db->escape($this->comment)."'":"null").",";
		$sql.= " status=".(isset($this->status)?$this->status:"null").",";
		$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql.= " batch=".(isset($this->batch)?"'".$this->db->escape($this->batch)."'":"null").",";
		$sql.= " eatby=".(dol_strlen($this->eatby)!=0 ? "'".$this->db->idate($this->eatby)."'" : 'null').",";
		$sql.= " sellby=".(dol_strlen($this->sellby)!=0 ? "'".$this->db->idate($this->sellby)."'" : 'null')."";


        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(__METHOD__);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				if(empty($this->id) && !empty($this->rowid))$this->id=$this->rowid;
				$result=$this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            $result=$this->call_trigger('LINERECEPTION_UPDATE', $user);
	            if ($result < 0) $error++;
	            //// End call triggers
			}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
	 *  Delete object in database
	 *
     *	@param  User	$user        User that deletes
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_DELETE',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			}
		}

		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
    		$sql.= " WHERE rowid=".$this->id;

    		dol_syslog(__METHOD__);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
	 *	Load an object from its id and create a new one in database
	 *
 	 *  @param	User	$user		User making the clone
     *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		$error=0;

		$object=new Commandefournisseurdispatch($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{

		}

		unset($object->context['createfromclone']);

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}



    /**
     *  Return label of the status of object
     *
     *  @param      int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto
     *  @return 	string        			Label
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return label of a status
     *
     * 	@param  int		$statut		Id statut
     *  @param  int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto
     *  @return string				Label of status
     */
    public function LibStatut($statut, $mode = 0)
    {
        // phpcs:enable
        global $langs;
        $langs->load('orders');

        if ($mode == 0)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        elseif ($mode == 1)
        {
            return $langs->trans($this->statutshort[$statut]);
        }
        elseif ($mode == 2)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        elseif ($mode == 3)
        {
            if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]), 'statut0');
            elseif ($statut==1) return img_picto($langs->trans($this->statuts[$statut]), 'statut4');
            elseif ($statut==2) return img_picto($langs->trans($this->statuts[$statut]), 'statut8');
        }
        elseif ($mode == 4)
        {
            if ($statut==0) return img_picto($langs->trans($this->statuts[$statut]), 'statut0').' '.$langs->trans($this->statuts[$statut]);
            elseif ($statut==1) return img_picto($langs->trans($this->statuts[$statut]), 'statut4').' '.$langs->trans($this->statuts[$statut]);
            elseif ($statut==2) return img_picto($langs->trans($this->statuts[$statut]), 'statut8').' '.$langs->trans($this->statuts[$statut]);
        }
        elseif ($mode == 5)
        {
            if ($statut==0) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]), 'statut0');
            elseif ($statut==1) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]), 'statut4');
            elseif ($statut==2) return '<span class="hideonsmartphone">'.$langs->trans($this->statutshort[$statut]).' </span>'.img_picto($langs->trans($this->statuts[$statut]), 'statut8');
        }
    }


	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	public function initAsSpecimen()
	{
		$this->id=0;

		$this->fk_commande='';
		$this->fk_product='';
		$this->fk_commandefourndet='';
		$this->qty='';
		$this->fk_entrepot='';
		$this->fk_user='';
		$this->datec='';
		$this->comment='';
		$this->status='';
		$this->tms='';
		$this->batch='';
		$this->eatby='';
		$this->sellby='';
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int    $limit     offset limit
	 * @param int    $offset    offset limit
	 * @param array  $filter    filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

 		$sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.fk_commande,";
		$sql.= " t.fk_product,";
		$sql.= " t.fk_commandefourndet,";
		$sql.= " t.qty,";
		$sql.= " t.fk_entrepot,";
		$sql.= " t.fk_user,";
		$sql.= " t.datec,";
		$sql.= " t.comment,";
		$sql.= " t.status,";
		$sql.= " t.tms,";
		$sql.= " t.batch,";
		$sql.= " t.eatby,";
		$sql.= " t.sellby";

        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key=='t.comment') {
					$sqlwhere [] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				} elseif ($key=='t.datec' || $key=='t.tms' || $key=='t.eatby' || $key=='t.sellby' || $key=='t.batch') {
					$sqlwhere [] = $key . ' = \'' . $this->db->escape($value) . '\'';
				} else {
					$sqlwhere [] = $key . ' = ' . $this->db->escape($value);
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' '.$filtermode.' ', $sqlwhere);
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .=  ' ' . $this->db->plimit($limit, $offset);
		}
		$this->lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new self($this->db);

				$line->id    = $obj->rowid;

				$line->fk_commande = $obj->fk_commande;
				$line->fk_product = $obj->fk_product;
				$line->fk_commandefourndet = $obj->fk_commandefourndet;
				$line->qty = $obj->qty;
				$line->fk_entrepot = $obj->fk_entrepot;
				$line->fk_user = $obj->fk_user;
				$line->datec = $this->db->jdate($obj->datec);
				$line->comment = $obj->comment;
				$line->status = $obj->status;
				$line->tms = $this->db->jdate($obj->tms);
				$line->batch = $obj->batch;
				$line->eatby = $this->db->jdate($obj->eatby);
				$line->sellby = $this->db->jdate($obj->sellby);

				$this->lines[$line->id] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}
}
