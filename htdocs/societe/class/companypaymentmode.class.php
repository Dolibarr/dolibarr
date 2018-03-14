<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file        class/companypaymentmode.class.php
 * \ingroup     monmodule
 * \brief       This file is a CRUD class file for CompanyPaymentMode (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for CompanyPaymentMode
 */
class CompanyPaymentMode extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'companypaymentmode';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'societe_rib';
	/**
	 * @var int  Does companypaymentmode support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 2;
	/**
	 * @var int  Does companypaymentmode support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;
	/**
	 * @var string String with name of icon for companypaymentmode. Must be the part after the 'object_' into object_companypaymentmode.png
	 */
	public $picto = 'generic';


	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'showoncombobox' if field must be shown into the label of combobox
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' =>array('type'=>'integer', 'label'=>'Rowid', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>10),
		'fk_soc' =>array('type'=>'integer', 'label'=>'Fk soc', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>15),
		'label' =>array('type'=>'varchar(30)', 'label'=>'Label', 'enabled'=>1, 'visible'=>-2, 'position'=>30),
		'bank' =>array('type'=>'varchar(255)', 'label'=>'Bank', 'enabled'=>1, 'visible'=>-2, 'position'=>35),
		'code_banque' =>array('type'=>'varchar(128)', 'label'=>'Code banque', 'enabled'=>1, 'visible'=>-2, 'position'=>40),
		'code_guichet' =>array('type'=>'varchar(6)', 'label'=>'Code guichet', 'enabled'=>1, 'visible'=>-2, 'position'=>45),
		'number' =>array('type'=>'varchar(255)', 'label'=>'Number', 'enabled'=>1, 'visible'=>-2, 'position'=>50),
		'cle_rib' =>array('type'=>'varchar(5)', 'label'=>'Cle rib', 'enabled'=>1, 'visible'=>-2, 'position'=>55),
		'bic' =>array('type'=>'varchar(20)', 'label'=>'Bic', 'enabled'=>1, 'visible'=>-2, 'position'=>60),
		'iban_prefix' =>array('type'=>'varchar(34)', 'label'=>'Iban prefix', 'enabled'=>1, 'visible'=>-2, 'position'=>65),
		'domiciliation' =>array('type'=>'varchar(255)', 'label'=>'Domiciliation', 'enabled'=>1, 'visible'=>-2, 'position'=>70),
		'proprio' =>array('type'=>'varchar(60)', 'label'=>'Proprio', 'enabled'=>1, 'visible'=>-2, 'position'=>75),
		'owner_address' =>array('type'=>'text', 'label'=>'Owner address', 'enabled'=>1, 'visible'=>-2, 'position'=>80),
		'default_rib' =>array('type'=>'tinyint(4)', 'label'=>'Default rib', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>85),
		'rum' =>array('type'=>'varchar(32)', 'label'=>'Rum', 'enabled'=>1, 'visible'=>-2, 'position'=>90),
		'date_rum' =>array('type'=>'date', 'label'=>'Date rum', 'enabled'=>1, 'visible'=>-2, 'position'=>95),
		'frstrecur' =>array('type'=>'varchar(16)', 'label'=>'Frstrecur', 'enabled'=>1, 'visible'=>-2, 'position'=>100),
		'type' =>array('type'=>'varchar(32)', 'label'=>'Type', 'enabled'=>1, 'visible'=>-2, 'position'=>110),
		'last_four' =>array('type'=>'varchar(4)', 'label'=>'Last four', 'enabled'=>1, 'visible'=>-2, 'position'=>115),
		'card_type' =>array('type'=>'varchar(255)', 'label'=>'Card type', 'enabled'=>1, 'visible'=>-2, 'position'=>120),
		'cvn' =>array('type'=>'varchar(255)', 'label'=>'Cvn', 'enabled'=>1, 'visible'=>-2, 'position'=>125),
		'exp_date_month' =>array('type'=>'integer', 'label'=>'Exp date month', 'enabled'=>1, 'visible'=>-2, 'position'=>130),
		'exp_date_year' =>array('type'=>'integer', 'label'=>'Exp date year', 'enabled'=>1, 'visible'=>-2, 'position'=>135),
		'country_code' =>array('type'=>'varchar(10)', 'label'=>'Country code', 'enabled'=>1, 'visible'=>-2, 'position'=>140),
		'approved' =>array('type'=>'integer', 'label'=>'Approved', 'enabled'=>1, 'visible'=>-2, 'position'=>145),
		'email' =>array('type'=>'varchar(255)', 'label'=>'Email', 'enabled'=>1, 'visible'=>-2, 'position'=>150),
		'max_total_amount_of_all_payments' =>array('type'=>'double(24,8)', 'label'=>'Max total amount of all payments', 'enabled'=>1, 'visible'=>-2, 'position'=>155),
		'preapproval_key' =>array('type'=>'varchar(255)', 'label'=>'Preapproval key', 'enabled'=>1, 'visible'=>-2, 'position'=>160),
		'total_amount_of_all_payments' =>array('type'=>'double(24,8)', 'label'=>'Total amount of all payments', 'enabled'=>1, 'visible'=>-2, 'position'=>165),
		'stripe_card_ref' =>array('type'=>'varchar(128)', 'label'=>'Stripe card ref', 'enabled'=>1, 'visible'=>-2, 'position'=>170),
		'status' =>array('type'=>'integer', 'label'=>'Status', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>175),
		'starting_date' =>array('type'=>'date', 'label'=>'Starting date', 'enabled'=>1, 'visible'=>-2, 'position'=>180),
		'ending_date' =>array('type'=>'date', 'label'=>'Ending date', 'enabled'=>1, 'visible'=>-2, 'position'=>185),
		'datec' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-2, 'position'=>20),
		'tms' =>array('type'=>'timestamp', 'label'=>'Tms', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>25),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'Import key', 'enabled'=>1, 'visible'=>-2, 'position'=>105),
	//'aaa' =>array('type'=>'date', 'label'=>'Ending date', 'enabled'=>0, 'visible'=>-2, 'position'=>185),
	);
	public $rowid;
	public $fk_soc;
	public $label;
	public $bank;
	public $code_banque;
	public $code_guichet;
	public $number;
	public $cle_rib;
	public $bic;
	public $iban_prefix;
	public $domiciliation;
	public $proprio;
	public $owner_address;
	public $default_rib;
	public $rum;
	public $date_rum;
	public $frstrecur;
	public $type;
	public $last_four;
	public $card_type;
	public $cvn;
	public $exp_date_month;
	public $exp_date_year;
	public $country_code;
	public $approved;
	public $email;
	public $max_total_amount_of_all_payments;
	public $preapproval_key;
	public $total_amount_of_all_payments;
	public $stripe_card_ref;
	public $status;
	public $starting_date;
	public $ending_date;
	public $datec;
	public $tms;
	public $import_key;
	// END MODULEBUILDER PROPERTIES



	// If this object has a subtable with lines

	/**
	 * @var int    Name of subtable line
	 */
	//public $table_element_line = 'companypaymentmodedet';
	/**
	 * @var int    Field with ID of parent key if this field has a parent
	 */
	//public $fk_element = 'fk_companypaymentmode';
	/**
	 * @var int    Name of subtable class that manage subtable lines
	 */
	//public $class_element_line = 'CompanyPaymentModeline';
	/**
	 * @var array  Array of child tables (child tables to delete before deleting a record)
	 */
	//protected $childtables=array('companypaymentmodedet');
	/**
	 * @var CompanyPaymentModeLine[]     Array of subtable lines
	 */
	//public $lines = array();



	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible']=0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled']=0;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Clone and object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $hookmanager, $langs;
	    $error = 0;

	    dol_syslog(__METHOD__, LOG_DEBUG);

	    $object = new self($this->db);

	    $this->db->begin();

	    // Load source object
	    $object->fetchCommon($fromid);
	    // Reset some properties
	    unset($object->id);
	    unset($object->fk_user_creat);
	    unset($object->import_key);

	    // Clear fields
	    $object->ref = "copy_of_".$object->ref;
	    $object->title = $langs->trans("CopyOf")." ".$object->title;
	    // ...

	    // Create clone
		$object->context['createfromclone'] = 'createfromclone';
	    $result = $object->createCommon($user);
	    if ($result < 0) {
	        $error++;
	        $this->error = $object->error;
	        $this->errors = $object->errors;
	    }

	    // End
	    if (!$error) {
	        $this->db->commit();
	        return $object;
	    } else {
	        $this->db->rollback();
	        return -1;
	    }
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param 	int    	$id   	Id object
	 * @param 	string 	$ref  	Ref
	 * @param	int		$socid	Id of company to get first default payment mode
	 * @param	string	$type	Filter on type ('ban', 'card', ...)
	 * @return 	int         	<0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $socid = 0, $type = '')
	{
		if ($socid) $sql.= " AND fk_soc  = ".$this->db->escape($socid)." AND default_rib = 1";
		if ($type)  $sql.= " AND type = '".$this->db->escape($type)."'";

		$result = $this->fetchCommon($id, $ref, $morewhere);
		if ($result > 0 && ! empty($this->table_element_line)) $this->fetchLines();
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	/*public function fetchLines()
	{
		$this->lines=array();

		// Load lines with object CompanyPaymentModeLine

		return count($this->lines)?1:0;
	}*/

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
     *  @param	int  	$notooltip					1=Disable tooltip
     *  @param  string  $morecss            		Add more css on link
     *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $morecss='', $save_lastsearch_value=-1)
	{
		global $db, $conf, $langs;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';
        $companylink = '';

        $label = '<u>' . $langs->trans("CompanyPaymentMode") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $url = dol_buildpath('/monmodule/companypaymentmode_card.php',1).'?id='.$this->id;

        if ($option != 'nolink')
        {
	        // Add param to save lastsearch_values or not
	        $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
	        if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
	        if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowCompanyPaymentMode");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}

	/**
	 * Set a Payment mode as Default
	 *
	 * @param   int     $id    		Payment mode ID
	 * @param	string	$alltypes	1=The default is for all payment types instead of per type
	 * @return  int             	0 if KO, 1 if OK
	 */
	function setAsDefault($id=0, $alltypes=0)
	{
		$sql1 = "SELECT rowid as id, fk_soc, type FROM ".MAIN_DB_PREFIX."societe_rib";
		$sql1.= " WHERE rowid = ".($id?$id:$this->id);

		dol_syslog(get_class($this).'::setAsDefault', LOG_DEBUG);
		$result1 = $this->db->query($sql1);
		if ($result1)
		{
			if ($this->db->num_rows($result1) == 0)
			{
				return 0;
			}
			else
			{
				$obj = $this->db->fetch_object($result1);

				$type = '';
				if (empty($alltypes)) $type = $obj->type;

				$this->db->begin();

				$sql2 = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET default_rib = 0";
				$sql2.= " WHERE default_rib <> 0 AND fk_soc = ".$obj->fk_soc;
				if ($type) $sql2.= " AND type = '".$this->db->escape($type)."'";
				dol_syslog(get_class($this).'::setAsDefault', LOG_DEBUG);
				$result2 = $this->db->query($sql2);

				$sql3 = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET default_rib = 1";
				$sql3.= " WHERE rowid = ".$obj->id;
				if ($type) $sql3.= " AND type = '".$this->db->escape($type)."'";
				dol_syslog(get_class($this).'::setAsDefault', LOG_DEBUG);
				$result3 = $this->db->query($sql3);

				if (!$result2 || !$result3)
				{
					dol_print_error($this->db);
					$this->db->rollback();
					return -1;
				}
				else
				{
					$this->db->commit();
					return 1;
				}
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			$prefix='';
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 1)
		{
			if ($status == 1) return $langs->trans('Enabled');
			if ($status == 0) return $langs->trans('Disabled');
		}
		if ($mode == 2)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 3)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 4)
		{
			if ($status == 1) return img_picto($langs->trans('Enabled'),'statut4').' '.$langs->trans('Enabled');
			if ($status == 0) return img_picto($langs->trans('Disabled'),'statut5').' '.$langs->trans('Disabled');
		}
		if ($mode == 5)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
		if ($mode == 6)
		{
			if ($status == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4');
			if ($status == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5');
		}
	}

	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql.= ' fk_user_creat, fk_user_modif';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql.= ' WHERE t.rowid = '.$id;
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation   = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture)
				{
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture   = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);

		}
		else
		{
			dol_print_error($this->db);
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
		$this->initAsSpecimenCommon();
	}

}
