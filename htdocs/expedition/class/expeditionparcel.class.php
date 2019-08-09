<?php
/* Copyright (C) 2019 Dolibarr
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/expedition/class/expeditionparcel.class.php
 *  \ingroup    expedition
 *  \brief      Shipment parcel management class file.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobjectline.class.php";



/**
 *	Class to manage shipments
 */
class ExpeditionParcel extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element="shipping";

	/**
	 * @var int Field with ID of parent key if this field has a parent
	 */
	public $fk_element="fk_expedition";

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element="expeditionparcel";


	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'sending';



	public $lines=array();



	//todo
	public $todo;
	public $weight_todo;





	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->lines = array();

	}

	

	/**
	 *  Create a parcel relative to an expedition in database
	 *
	 *  @param	User	$user       User object that creates
	 * 	@param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return int 				<0 if error, id expeditionparcel created if ok
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $hookmanager;

		$error = 0;

		$this->user = $user;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		
		$sql.= "fk_expedition";
		$sql.= ", description";
		$sql.= ", value";
		$sql.= ", fk_parcel_type";
		$sql.= ", height";
		$sql.= ", width";
		$sql.= ", size";
		$sql.= ", size_units";
		$sql.= ", weight";
		$sql.= ", weight_units";
		$sql.= ", dangerous_goods";
		$sql.= ", tail_lift";

		$sql.= ") VALUES (";
		$sql.= ", ".$this->id;//fk_expedition
		$sql.= ", ".(!empty($this->description)?"'".$this->db->escape($this->description)."'":"null");//description
		$sql.= ", ".$this->value;//value
		$sql.= ", ".$this->fk_parcel_type;//fk_parcel_type
		$sql.= ", ".$this->height;//height
		$sql.= ", ".$this->width;//width
		$sql.= ", ".$this->size;//size
		$sql.= ", ".$this->size_units;//size_units
		$sql.= ", ".$this->weight;//weight
		$sql.= ", ".$this->weight_units;//weight_units
		$sql.= ", ".$this->dangerous_goods;//dangerous_goods
		$sql.= ", ".$this->tail_lift;//tail_lift

		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->lineid = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return $this->lineid;
				
			}
			else
			{
				$error++;
				$this->error=$this->db->error()." - sql=$sql";
				$this->db->rollback();
				return -2;
			}	
			
		}
		else
		{
			$error++;
			$this->error=$this->db->error()." - sql=$sql";
			$this->db->rollback();
			return -1;
		}
	}


}
