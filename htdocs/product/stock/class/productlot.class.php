<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    product/stock/class/productlot.class.php
 * \ingroup stock
 * \brief   This is CRUD class file to manage table productlot (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class with list of lots and properties
 */
class Productlot extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'productlot';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'product_lot';
	
	public $picto='barcode';
	
	public $isnolinkedbythird = 1;
    public $ismultientitymanaged = 1;
    
	/**
	 * @var ProductlotLine[] Lines
	 */
	public $lines = array();

	/**
	 */
	
	public $entity;
	public $fk_product;
	public $batch;
	public $eatby = '';
	public $sellby = '';
	public $datec = '';
	public $tms = '';
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;

	/**
	 */
	

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters
		
		if (isset($this->entity)) {
			 $this->entity = trim($this->entity);
		}
		if (isset($this->fk_product)) {
			 $this->fk_product = trim($this->fk_product);
		}
		if (isset($this->batch)) {
			 $this->batch = trim($this->batch);
		}
		if (isset($this->fk_user_creat)) {
			 $this->fk_user_creat = trim($this->fk_user_creat);
		}
		if (isset($this->fk_user_modif)) {
			 $this->fk_user_modif = trim($this->fk_user_modif);
		}
		if (isset($this->import_key)) {
			 $this->import_key = trim($this->import_key);
		}

		

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		$sql.= 'entity,';
		$sql.= 'fk_product,';
		$sql.= 'batch,';
		$sql.= 'eatby,';
		$sql.= 'sellby,';
		$sql.= 'datec,';
		$sql.= 'fk_user_creat,';
		$sql.= 'fk_user_modif,';
		$sql.= 'import_key';
		$sql .= ') VALUES (';
		$sql .= ' '.(! isset($this->entity)?'NULL':$this->entity).',';
		$sql .= ' '.(! isset($this->fk_product)?'NULL':$this->fk_product).',';
		$sql .= ' '.(! isset($this->batch)?'NULL':"'".$this->db->escape($this->batch)."'").',';
		$sql .= ' '.(! isset($this->eatby) || dol_strlen($this->eatby)==0?'NULL':"'".$this->db->idate($this->eatby)."'").',';
		$sql .= ' '.(! isset($this->sellby) || dol_strlen($this->sellby)==0?'NULL':"'".$this->db->idate($this->sellby)."'").',';
		$sql .= ' '."'".$this->db->idate(dol_now())."'".',';
		$sql .= ' '.(! isset($this->fk_user_creat)?'NULL':$this->fk_user_creat).',';
		$sql .= ' '.(! isset($this->fk_user_modif)?'NULL':$this->fk_user_modif).',';
		$sql .= ' '.(! isset($this->import_key)?'NULL':$this->import_key);
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

			if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action to call a trigger.

				// Call triggers
				$result=$this->call_trigger('PRODUCTLOT_CREATE',$user);
				if ($result < 0) $error++;
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  Id object
	 * @param int    $product_id  Id of product, batch number parameter required
	 * @param string $batch batch number
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id = 0, $product_id = 0, $batch = '')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.entity,";
		$sql .= " t.fk_product,";
		$sql .= " t.batch,";
		$sql .= " t.eatby,";
		$sql .= " t.sellby,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.import_key";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if ($product_id > 0 && $batch != '') {
			$sql .= ' WHERE t.batch = ' . '\'' . $this->db->escape($batch) . '\' AND t.fk_product = ' . $product_id;
		} else {
			$sql .= ' WHERE t.rowid = ' . $id;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;
				//$this->ref = $obj->fk_product.'_'.$obj->batch;
				
				$this->batch = $obj->batch;
				
				$this->entity = $obj->entity;
				$this->fk_product = $obj->fk_product;
				$this->eatby = $this->db->jdate($obj->eatby);
				$this->sellby = $this->db->jdate($obj->sellby);
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->import_key = $obj->import_key;

				// Retrieve all extrafields for invoice
				// fetch optionals attributes and labels
				require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
				$extrafields=new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
				$this->fetch_optionals($this->id,$extralabels);				
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return - 1;
		}
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
	public function fetchAll($sortorder='', $sortfield='', $limit=0, $offset=0, array $filter = array(), $filtermode='AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		
		$sql .= " t.entity,";
		$sql .= " t.fk_product,";
		$sql .= " t.batch,";
		$sql .= " t.eatby,";
		$sql .= " t.sellby,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.import_key";

		
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element. ' as t';

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$sqlwhere [] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' '.$filtermode.' ', $sqlwhere);
		}
		
		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield,$sortorder);
		}
		if (!empty($limit)) {
		 $sql .=  ' ' . $this->db->plimit($limit + 1, $offset);
		}
		$this->lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) 
			{
				$line = new ProductlotLine();

				$line->id = $obj->rowid;
				
				$line->entity = $obj->entity;
				$line->fk_product = $obj->fk_product;
				$line->batch = $obj->batch;
				$line->eatby = $this->db->jdate($obj->eatby);
				$line->sellby = $this->db->jdate($obj->sellby);
				$line->datec = $this->db->jdate($obj->datec);
				$line->tms = $this->db->jdate($obj->tms);
				$line->fk_user_creat = $obj->fk_user_creat;
				$line->fk_user_modif = $obj->fk_user_modif;
				$line->import_key = $obj->import_key;

				$this->lines[$line->id] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters
		
		if (isset($this->entity)) {
			 $this->entity = trim($this->entity);
		}
		if (isset($this->fk_product)) {
			 $this->fk_product = trim($this->fk_product);
		}
		if (isset($this->batch)) {
			 $this->batch = trim($this->batch);
		}
		if (isset($this->fk_user_creat)) {
			 $this->fk_user_creat = trim($this->fk_user_creat);
		}
		if (isset($this->fk_user_modif)) {
			 $this->fk_user_modif = trim($this->fk_user_modif);
		}
		if (isset($this->import_key)) {
			 $this->import_key = trim($this->import_key);
		}

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		$sql .= ' entity = '.(isset($this->entity)?$this->entity:"null").',';
		$sql .= ' fk_product = '.(isset($this->fk_product)?$this->fk_product:"null").',';
		$sql .= ' batch = '.(isset($this->batch)?"'".$this->db->escape($this->batch)."'":"null").',';
		$sql .= ' eatby = '.(! isset($this->eatby) || dol_strlen($this->eatby) != 0 ? "'".$this->db->idate($this->eatby)."'" : 'null').',';
		$sql .= ' sellby = '.(! isset($this->sellby) || dol_strlen($this->sellby) != 0 ? "'".$this->db->idate($this->sellby)."'" : 'null').',';
		$sql .= ' datec = '.(! isset($this->datec) || dol_strlen($this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').',';
		$sql .= ' tms = '.(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : "'".$this->db->idate(dol_now())."'").',';
		$sql .= ' fk_user_creat = '.(isset($this->fk_user_creat)?$this->fk_user_creat:"null").',';
		$sql .= ' fk_user_modif = '.(isset($this->fk_user_modif)?$this->fk_user_modif:"null").',';
		$sql .= ' import_key = '.(isset($this->import_key)?$this->import_key:"null");
		$sql .= ' WHERE rowid=' . $this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		if (!$error && !$notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.

			// Call triggers
			$result=$this->call_trigger('PRODUCTLOT_MODIFY',$user);
			if ($result < 0) { $error++; }
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user      User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if (!$error) {
			if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_DELETE',$user);
				//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
				//// End call triggers
			}
		}

		if (!$error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid=' . $this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid Id of object to clone
	 *
	 * @return int New id of clone
	 */
	public function createFromClone($fromid)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $user;
		$error = 0;
		$object = new Productlot($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$error ++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		// End
		if (!$error) {
			$this->db->commit();

			return $object->id;
		} else {
			$this->db->rollback();

			return - 1;
		}
	}
	
	
	/**
	 *	Return label of status of object
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label of status
	 */
	function getLibStatut($mode=0)
	{
	    return $this->LibStatut(0,$mode);
	}
	
	/**
	 *	Return label of a given status
	 *
	 *	@param	int		$statut     Status
	 *	@param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return string      		Label of status
	 */
	function LibStatut($statut,$mode=0)
	{
	    global $langs;
	
	    //$langs->load('stocks');

	    return '';
	}
	
	
	/**
	 *  Return a link to the a lot card (with optionaly the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
     *  @param	integer	$notooltip			1=Disable tooltip
     *  @param	int		$maxlen				Max length of visible user name
     *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $maxlen=24, $morecss='')
	{
		global $langs, $conf, $db;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;


        $result = '';

        $label = '<u>' . $langs->trans("Batch") . '</u>';
        $label.= '<div width="100%">';
        $label.= '<b>' . $langs->trans('Batch') . ':</b> ' . $this->batch;
        if ($this->eatby)
        {
            $label.= '<br><b>' . $langs->trans('EatByDate') . ':</b> ' . dol_print_date($this->eatby, 'day');
        }
        if ($this->sellby)
        {
            $label.= '<br><b>' . $langs->trans('SellByDate') . ':</b> ' . dol_print_date($this->sellby, 'day');
        }
        
        $link = '<a href="'.DOL_URL_ROOT.'/product/stock/productlot_card.php?id='.$this->id.'"';
        $link.= ($notooltip?'':' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip'.($morecss?' '.$morecss:'').'"');
        $link.= '>';
		$linkend='</a>';

        if ($withpicto)
        {
            $result.=($link.img_object(($notooltip?'':$label), 'barcode', ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
            if ($withpicto != 2) $result.=' ';
		}
		$result.= $link . $this->batch . $linkend;
		return $result;
	}
	
	
	/** 
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		
		$this->entity = '';
		$this->fk_product = '';
		$this->batch = '';
		$this->eatby = '';
		$this->sellby = '';
		$this->datec = '';
		$this->tms = '';
		$this->fk_user_creat = '';
		$this->fk_user_modif = '';
		$this->import_key = '';
	}

}

/**
 * Class ProductlotLine
 */
class ProductlotLine
{
	/**
	 * @var int ID
	 */
	public $id;
	/**
	 * @var mixed Sample line property 1
	 */
	
	public $entity;
	public $fk_product;
	public $batch;
	public $eatby = '';
	public $sellby = '';
	public $datec = '';
	public $tms = '';
	public $fk_user_creat;
	public $fk_user_modif;
	public $import_key;

	/**
	 * @var mixed Sample line property 2
	 */
	
}
