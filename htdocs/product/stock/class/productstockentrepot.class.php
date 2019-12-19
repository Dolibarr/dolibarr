<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018-2019  Frédéric France     <frederic.france@netlogic.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/product/stock/class/productstockentrepot.class.php
 * \ingroup ProductEntrepot
 * \brief   This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *          Put some comments here
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class ProductStockEntrepot
 *
 * Put here description of your class
 *
 * @see CommonObject
 */
class ProductStockEntrepot extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'ProductStockEntrepot';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'product_warehouse_properties';

	public $tms = '';

	/**
     * @var int ID
     */
	public $fk_product;

	/**
     * @var int ID
     */
	public $fk_entrepot;

	public $seuil_stock_alerte;
	public $desiredstock;
	public $import_key;


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

		if (isset($this->fk_product)) $this->fk_product = (int) $this->fk_product;
		if (isset($this->fk_entrepot)) $this->fk_entrepot = (int) $this->fk_entrepot;
		if (isset($this->seuil_stock_alerte)) $this->seuil_stock_alerte = trim($this->seuil_stock_alerte);
		if (isset($this->desiredstock)) $this->desiredstock = trim($this->desiredstock);
		if (isset($this->import_key)) $this->import_key = trim($this->import_key);

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';

		$sql.= 'fk_product,';
		$sql.= 'fk_entrepot,';
		$sql.= 'seuil_stock_alerte,';
		$sql.= 'desiredstock,';
		$sql.= 'import_key';


		$sql .= ') VALUES (';

		$sql .= ' '.(! isset($this->fk_product)?'NULL':$this->fk_product).',';
		$sql .= ' '.(! isset($this->fk_entrepot)?'NULL':$this->fk_entrepot).',';
		$sql .= ' '.(! isset($this->seuil_stock_alerte)?'0':$this->seuil_stock_alerte).',';
		$sql .= ' '.(! isset($this->desiredstock)?'0':$this->desiredstock).',';
		$sql .= ' '.(! isset($this->import_key)?'NULL':"'".$this->db->escape($this->import_key)."'");


		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

			//if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action to call a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_CREATE',$user);
				//if ($result < 0) $error++;
				//// End call triggers
			//}
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
	 * @param int    $id  				Id object
	 * @param int    $fk_product 		Id product
	 * @param int    $fk_entrepot  		Id warehouse
	 * @return int 						<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $fk_product = 0, $fk_entrepot = 0)
	{
		if(empty($id) && (empty($fk_product) || empty($fk_entrepot))) return -1;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.tms,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_entrepot,";
		$sql .= " t.seuil_stock_alerte,";
		$sql .= " t.desiredstock,";
		$sql .= " t.import_key";
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if(!empty($id)) $sql .= ' WHERE t.rowid = ' . $id;
		else $sql.= ' WHERE t.fk_product = '.$fk_product.' AND t.fk_entrepot = '.$fk_entrepot;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$numrows = $this->db->num_rows($resql);
			if ($numrows)
			{
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_product = $obj->fk_product;
				$this->fk_entrepot = $obj->fk_entrepot;
				$this->seuil_stock_alerte = $obj->seuil_stock_alerte;
				$this->desiredstock = $obj->desiredstock;
				$this->import_key = $obj->import_key;
			}

			// Retreive all extrafield
			// fetch optionals attributes and labels
			$this->fetch_optionals();

			// $this->fetch_lines();

			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int	 $fk_product Product from which we want to get limit and desired stock by warehouse
	 * @param int	 $fk_entrepot Warehouse in which we want to get products limit and desired stock
	 * @param string $sortorder  Sort Order
	 * @param string $sortfield  Sort field
	 * @param int    $limit      offset limit
	 * @param int    $offset     offset limit
	 * @param array  $filter     filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($fk_product = '', $fk_entrepot = '', $sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';

		$sql .= " t.tms,";
		$sql .= " t.fk_product,";
		$sql .= " t.fk_entrepot,";
		$sql .= " t.seuil_stock_alerte,";
		$sql .= " t.desiredstock,";
		$sql .= " t.import_key";


		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element. ' as t';

		$sql .= ' WHERE 1=1';

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$sqlwhere [] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
			}
		}
		if (count($sqlwhere) > 0) $sql .= ' AND ' . implode(' '.$filtermode.' ', $sqlwhere);

		if(!empty($fk_product)) $sql .= ' AND fk_product = '.$fk_product;
		elseif(!empty($fk_entrepot)) $sql .= ' AND fk_entrepot = '.$fk_entrepot;
		// "elseif" used instead of "if" because getting list with specified fk_product and specified fk_entrepot would be the same as doing a fetch

		if (!empty($sortfield)) $sql .= $this->db->order($sortfield, $sortorder);
		if (!empty($limit)) $sql .=  ' ' . $this->db->plimit($limit, $offset);

		$lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$lines[$obj->rowid] = array(
										'id'=>$obj->rowid
										,'fk_product'=>$obj->fk_product
										,'fk_entrepot'=>$obj->fk_entrepot
										,'seuil_stock_alerte'=>$obj->seuil_stock_alerte
										,'desiredstock'=>$obj->desiredstock
									);
			}
			$this->db->free($resql);

			return $lines;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

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

		if (isset($this->fk_product)) $this->fk_product = (int) $this->fk_product;
		if (isset($this->fk_entrepot)) $this->fk_entrepot = (int) $this->fk_entrepot;
		if (isset($this->seuil_stock_alerte)) $this->seuil_stock_alerte = trim($this->seuil_stock_alerte);
		if (isset($this->desiredstock)) $this->desiredstock = trim($this->desiredstock);
		if (isset($this->import_key)) $this->import_key = trim($this->import_key);


		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';

		$sql .= ' tms = '.(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : "'".$this->db->idate(dol_now())."'").',';
		$sql .= ' fk_product = '.(isset($this->fk_product)?$this->fk_product:"null").',';
		$sql .= ' fk_entrepot = '.(isset($this->fk_entrepot)?$this->fk_entrepot:"null").',';
		$sql .= ' seuil_stock_alerte = '.(isset($this->seuil_stock_alerte)?$this->seuil_stock_alerte:"null").',';
		$sql .= ' desiredstock = '.(isset($this->desiredstock)?$this->desiredstock:"null").',';
		$sql .= ' import_key = '.(isset($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null");


		$sql .= ' WHERE rowid=' . $this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
		}

		//if (!$error && !$notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.

			//// Call triggers
			//$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
			//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
			//// End call triggers
		//}

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

		//if (!$error && !$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_DELETE',$user);
				//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
				//// End call triggers
		//}

		if (!$error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid=' . $this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
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
	 * @param	User	$user	   User making the clone
	 * @param   int     $fromid    Id of object to clone
	 * @return  int                New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;
		$object = new ProductStockEntrepot($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$error ++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
		}

		unset($object->context['createfromclone']);

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
	 *  Return a link to the user card (with optionaly the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
     *  @param	integer	$notooltip			1=Disable tooltip
     *  @param	int		$maxlen				Max length of visible user name
     *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $maxlen = 24, $morecss = '')
	{
		global $langs, $conf, $db;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;


        $result = '';
        $companylink = '';

        $label = '<u>' . $langs->trans("MyModule") . '</u>';
        $label.= '<div width="100%">';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $link = '<a href="'.DOL_URL_ROOT.'/ProductEntrepot/card.php?id='.$this->id.'"';
        $link.= ($notooltip?'':' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip'.($morecss?' '.$morecss:'').'"');
        $link.= '>';
		$linkend='</a>';

        if ($withpicto)
        {
            $result.=($link.img_object(($notooltip?'':$label), 'label', ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend);
            if ($withpicto != 2) $result.=' ';
        }
        $result.= $link . $this->ref . $linkend;
        return $result;
    }

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un status donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string 			       	Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
        // phpcs:enable
		global $langs;

		if ($mode == 0)
		{
			if ($status == 1) return $langs->trans('Enabled');
			elseif ($status == 0) return $langs->trans('Disabled');
		}
		elseif ($mode == 1)
		{
			if ($status == 1) return $langs->trans('Enabled');
			elseif ($status == 0) return $langs->trans('Disabled');
		}
		elseif ($mode == 2)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'), 'statut4').' '.$langs->trans('Enabled');
			elseif ($status == 0) return img_picto($langs->trans('Disabled'), 'statut5').' '.$langs->trans('Disabled');
		}
		elseif ($mode == 3)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'), 'statut4');
			elseif ($status == 0) return img_picto($langs->trans('Disabled'), 'statut5');
		}
		elseif ($mode == 4)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'), 'statut4').' '.$langs->trans('Enabled');
			elseif ($status == 0) return img_picto($langs->trans('Disabled'), 'statut5').' '.$langs->trans('Disabled');
		}
		elseif ($mode == 5)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'), 'statut4');
			elseif ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'), 'statut5');
		}
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

		$this->tms = '';
		$this->fk_product = null;
		$this->fk_entrepot = null;
		$this->seuil_stock_alerte = '';
		$this->desiredstock = '';
		$this->import_key = '';
	}
}
