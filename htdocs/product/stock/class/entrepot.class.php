<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2016	   Francis Appels       <francis.appels@yahoo.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/product/stock/class/entrepot.class.php
 *  \ingroup    stock
 *  \brief      Fichier de la classe de gestion des entrepots
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *  Class to manage warehouses
 */
class Entrepot extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='stock';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='entrepot';

	public $picto='stock';
	public $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * Warehouse closed, inactive
	 */
	const STATUS_CLOSED = 0;

	/**
	 * Warehouse open and operations for customer shipping, supplier dispatch, internal stock transfers/corrections allowed.
	 */
	const STATUS_OPEN_ALL = 1;

	/**
	 * Warehouse open and operations for stock transfers/corrections allowed (not for customer shipping and supplier dispatch).
	 */
	const STATUS_OPEN_INTERNAL = 2;

	public $libelle;

	/**
	 * @var string description
	 */
	public $description;

	public $statut;
	public $lieu;

	/**
	 * @var string Address
	 */
	public $address;

	/**
	 * @var string Zipcode
	 */
	public $zip;

    /**
	 * @var string Town
	 */
	public $town;

	/**
     * @var int ID
     */
	public $fk_parent;

	// List of short language codes for status
	public $statuts = array();

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;
		$this->db = $db;

		$this->statuts[self::STATUS_CLOSED] = 'Closed2';
		if ($conf->global->ENTREPOT_EXTRA_STATUS)
		{
			$this->statuts[self::STATUS_OPEN_ALL] = 'OpenAll';
			$this->statuts[self::STATUS_OPEN_INTERNAL] = 'OpenInternal';
		}
		else
		{
			$this->statuts[self::STATUS_OPEN_ALL] = 'Opened';
		}
	}

	/**
	 *	Creation d'un entrepot en base
	 *
	 *	@param		User	$user       Object user that create the warehouse
	 *	@return		int					>0 if OK, =<0 if KO
	 */
	public function create($user)
	{
		global $conf;

		$error = 0;

		$this->libelle = trim($this->libelle);

		// Si libelle non defini, erreur
		if ($this->libelle == '')
		{
			$this->error = "ErrorFieldRequired";
			return 0;
		}

		$now=dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."entrepot (ref, entity, datec, fk_user_author, fk_parent)";
		$sql .= " VALUES ('".$this->db->escape($this->libelle)."', ".$conf->entity.", '".$this->db->idate($now)."', ".$user->id.", ".($this->fk_parent > 0 ? $this->fk_parent : "NULL").")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."entrepot");
			if ($id > 0)
			{
				$this->id = $id;

				if (! $error)
				{
					$result = $this->update($id, $user);
					if ($result <= 0)
					{
						$error++;
					}
				}

				if (! $error)
				{
					$this->db->commit();
					return $id;
				}
				else
				{
					dol_syslog(get_class($this)."::create return -3");
					$this->db->rollback();
					return -3;
				}
			}
			else {
				$this->error="Failed to get insert id";
				dol_syslog(get_class($this)."::create return -2");
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::create Error ".$this->db->error());
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Update properties of a warehouse
	 *
	 *	@param		int		$id     id of warehouse to modify
	 *	@param      User	$user	User object
	 *	@return		int				>0 if OK, <0 if KO
	 */
	public function update($id, $user)
	{
	    if (empty($id)) $id = $this->id;

		// Check if new parent is already a child of current warehouse
		if(!empty($this->fk_parent))
		{
			$TChildWarehouses = array($id);
			$TChildWarehouses = $this->get_children_warehouses($this->id, $TChildWarehouses);
			if(in_array($this->fk_parent, $TChildWarehouses))
			{
				$this->error = 'ErrorCannotAddThisParentWarehouse';
				return -2;
			}
		}

		$this->libelle=trim($this->libelle);
		$this->description=trim($this->description);

		$this->lieu=trim($this->lieu);

		$this->address=trim($this->address);
		$this->zip=trim($this->zip);
		$this->town=trim($this->town);
		$this->country_id=($this->country_id > 0 ? $this->country_id : 0);

		$sql = "UPDATE ".MAIN_DB_PREFIX."entrepot ";
		$sql .= " SET ref = '" . $this->db->escape($this->libelle) ."'";
		$sql .= ", fk_parent = " . (($this->fk_parent > 0) ? $this->fk_parent : "NULL");
		$sql .= ", description = '" . $this->db->escape($this->description) ."'";
		$sql .= ", statut = " . $this->statut;
		$sql .= ", lieu = '" . $this->db->escape($this->lieu) ."'";
		$sql .= ", address = '" . $this->db->escape($this->address) ."'";
		$sql .= ", zip = '" . $this->db->escape($this->zip) ."'";
		$sql .= ", town = '" . $this->db->escape($this->town) ."'";
		$sql .= ", fk_pays = " . $this->country_id;
		$sql .= " WHERE rowid = " . $id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *	Delete a warehouse
	 *
	 *	@param		User	$user		   Object user that made deletion
	 *  @param      int     $notrigger     1=No trigger
	 *	@return		int					   <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog(get_class($this)."::delete id=".$this->id, LOG_DEBUG);

		$this->db->begin();

		if (! $error && empty($notrigger))
		{
            // Call trigger
            $result=$this->call_trigger('WAREHOUSE_DELETE', $user);
            if ($result < 0) { $error++; }
            // End call triggers
		}

		$elements = array('stock_mouvement','product_stock','product_warehouse_properties');
		foreach($elements as $table)
		{
			if (! $error)
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX.$table;
				$sql.= " WHERE fk_entrepot = " . $this->id;

				$result=$this->db->query($sql);
				if (! $result)
				{
					$error++;
					$this->errors[] = $this->db->lasterror();
				}
			}
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."entrepot";
			$sql.= " WHERE rowid = " . $this->id;

			$resql1=$this->db->query($sql);
			if (! $resql1)
			{
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		if (! $error)
		{
			// Update denormalized fields because we change content of produt_stock. Warning: Do not use "SET p.stock", does not works with pgsql
			$sql = "UPDATE ".MAIN_DB_PREFIX."product as p SET stock = (SELECT SUM(ps.reel) FROM ".MAIN_DB_PREFIX."product_stock as ps WHERE ps.fk_product = p.rowid)";

			$resql2=$this->db->query($sql);
			if (! $resql2)
			{
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Load warehouse data
	 *
	 *	@param		int		$id     Warehouse id
	 *	@param		string	$ref	Warehouse label
	 *	@return		int				>0 if OK, <0 if KO
	 */
	public function fetch($id, $ref = '')
	{
		global $conf;

		dol_syslog(get_class($this)."::fetch id=".$id." ref=".$ref);

		// Check parameters
		if (! $id && ! $ref)
		{
			$this->error='ErrorWrongParameters';
			dol_syslog(get_class($this)."::fetch ".$this->error);
			return -1;
		}

		$sql  = "SELECT rowid, fk_parent, ref as label, description, statut, lieu, address, zip, town, fk_pays as country_id";
		$sql .= " FROM ".MAIN_DB_PREFIX."entrepot";
		if ($id)
		{
			$sql.= " WHERE rowid = '".$id."'";
		}
		else
		{
			$sql.= " WHERE entity = " .$conf->entity;
			if ($ref) $sql.= " AND ref = '".$this->db->escape($ref)."'";
		}

		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result) > 0)
			{
				$obj=$this->db->fetch_object($result);

				$this->id             = $obj->rowid;
				$this->fk_parent      = $obj->fk_parent;
				$this->ref            = $obj->label;
				$this->label          = $obj->label;			// deprecated
				$this->libelle        = $obj->label;            // deprecated
				$this->description    = $obj->description;
				$this->statut         = $obj->statut;
				$this->lieu           = $obj->lieu;
				$this->address        = $obj->address;
				$this->zip            = $obj->zip;
				$this->town           = $obj->town;
				$this->country_id     = $obj->country_id;

				include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	            $tmp=getCountry($this->country_id, 'all');
				$this->country=$tmp['label'];
				$this->country_code=$tmp['code'];

				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 * 	Load warehouse info data
	 *
	 *  @param	int		$id      warehouse id
	 *  @return	void
	 */
	public function info($id)
	{
		$sql = "SELECT e.rowid, e.datec, e.tms as datem, e.fk_user_author";
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
		$sql.= " WHERE e.rowid = ".$id;

		dol_syslog(get_class($this)."::info", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation     = $cuser;
				}

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		}
		else
		{
	        dol_print_error($this->db);
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of all warehouses
	 *
	 *	@param	int		$status		Status
	 * 	@return array				Array list of warehouses
	 */
	public function list_array($status = 1)
	{
        // phpcs:enable
		$liste = array();

		$sql = "SELECT rowid, ref as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot";
		$sql.= " WHERE entity IN (".getEntity('stock').")";
		$sql.= " AND statut = ".$status;

		$result = $this->db->query($sql);
		$i = 0;
		$num = $this->db->num_rows($result);
		if ( $result )
		{
			while ($i < $num)
			{
				$row = $this->db->fetch_row($result);
				$liste[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free($result);
		}
		return $liste;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return number of unique different product into a warehouse
	 *
	 * 	@return		Array		Array('nb'=>Nb, 'value'=>Value)
	 */
	public function nb_different_products()
	{
        // phpcs:enable
		$ret=array();

		$sql = "SELECT count(distinct p.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
		$sql.= ", ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE ps.fk_entrepot = ".$this->id;
		$sql.= " AND ps.fk_product = p.rowid";

		//print $sql;
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			$ret['nb']=$obj->nb;
			$this->db->free($result);
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		return $ret;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return stock and value of warehosue
	 *
	 * 	@return		Array		Array('nb'=>Nb, 'value'=>Value)
	 */
	public function nb_products()
	{
        // phpcs:enable
		$ret=array();

		$sql = "SELECT sum(ps.reel) as nb, sum(ps.reel * p.pmp) as value";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
		$sql.= ", ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE ps.fk_entrepot = ".$this->id;
		$sql.= " AND ps.fk_product = p.rowid";

		//print $sql;
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			$ret['nb']=$obj->nb;
			$ret['value']=$obj->value;
			$this->db->free($result);
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		return $ret;
	}

	/**
	 *	Return label of status of object
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return label of a given status
	 *
	 *	@param	int		$statut     Status
	 *	@param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return string      		Label of status
	 */
	public function LibStatut($statut, $mode = 0)
	{
        // phpcs:enable
		global $langs;

		$langs->load('stocks');

		$picto = 'statut5';
		$label = $langs->trans($this->statuts[$statut]);


		if ($mode == 0)
		{
			return $label;
		}
		elseif ($mode == 1)
		{
			return $label;
		}
		elseif ($mode == 2)
		{
			if ($statut > 0) $picto = 'statut4';
			return img_picto($label, $picto).' '.$label;
		}
		elseif ($mode == 3)
		{
			if ($statut > 0) $picto = 'statut4';
			return img_picto($label, $picto).' '.$label;
		}
		elseif ($mode == 4)
		{
			if ($statut > 0) $picto = 'statut4';
			return img_picto($label, $picto).' '.$label;
		}
		elseif ($mode == 5)
		{
			if ($statut > 0) $picto = 'statut4';
			return $label.' '.img_picto($label, $picto);
		}
	}


	/**
	 *	Return clickable name (possibility with the pictogram)
	 *
	 *	@param		int		$withpicto		with pictogram
	 *	@param		string	$option			Where the link point to
	 *  @param      int     $showfullpath   0=Show ref only. 1=Show full path instead of Ref (this->fk_parent must be defined)
     *  @param	    int   	$notooltip		1=Disable tooltip
	 *	@return		string					String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $showfullpath = 0, $notooltip = 0)
	{
		global $conf, $langs;
		$langs->load("stocks");

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result='';

        $label = '<u>' . $langs->trans("ShowWarehouse").'</u>';
        $label.= '<br><b>' . $langs->trans('Ref') . ':</b> ' . (empty($this->ref)?(empty($this->label)?$this->libelle:$this->label):$this->ref);
        if (! empty($this->lieu))
            $label.= '<br><b>' . $langs->trans('LocationSummary').':</b> '.$this->lieu;

        $url = DOL_URL_ROOT.'/product/stock/card.php?id='.$this->id;

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowWarehouse");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip"';
        }

        $linkstart = '<a href="'.$url.'"';
        $linkstart.=$linkclose.'>';
        $linkend='</a>';

        $result .= $linkstart;
        if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
        if ($withpicto != 2) $result.= ($showfullpath ? $this->get_full_arbo() : (empty($this->label)?$this->libelle:$this->label));
        $result .= $linkend;

		return $result;
	}

	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    public function initAsSpecimen()
    {
        global $user,$langs,$conf,$mysoc;

        $now=dol_now();

        // Initialize parameters
        $this->id=0;
        $this->libelle = 'WAREHOUSE SPECIMEN';
        $this->description = 'WAREHOUSE SPECIMEN '.dol_print_date($now, 'dayhourlog');
		$this->statut=1;
        $this->specimen=1;

		$this->lieu='Location test';
        $this->address='21 jump street';
        $this->zip='99999';
        $this->town='MyTown';
        $this->country_id=1;
        $this->country_code='FR';
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return full path to current warehouse
	 *
	 *	@return		string	String full path to current warehouse separated by " >> "
	 */
	public function get_full_arbo()
	{
        // phpcs:enable
        global $user,$langs,$conf;

        $TArbo = array(empty($this->label)?$this->libelle:$this->label);

        $protection=100; // We limit depth of warehouses to 100

        $warehousetmp = new Entrepot($this->db);

        $parentid = $this->fk_parent;       // If parent_id not defined on current object, we do not start consecutive searches of parents
        $i=0;
        while ($parentid > 0 && $i < $protection)
        {
            $sql = 'SELECT fk_parent FROM '.MAIN_DB_PREFIX.'entrepot WHERE rowid = '.$parentid;
            $resql = $this->db->query($sql);
            if ($resql)
            {
                $objarbo = $this->db->fetch_object($resql);
                if ($objarbo)
                {
                	$warehousetmp->fetch($parentid);
                	$TArbo[] = $warehousetmp->label;
                 	$parentid = $objarbo->fk_parent;
                }
                else break;
            }
            else dol_print_error($this->db);

            $i++;
        }

        return implode(' >> ', array_reverse($TArbo));
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return array of children warehouses ids from $id warehouse (recursive function)
	 *
	 * @param   int         $id					id parent warehouse
	 * @param   integer[]	$TChildWarehouses	array which will contain all children (param by reference)
	 * @return  integer[]   $TChildWarehouses	array which will contain all children
	 */
    public function get_children_warehouses($id, &$TChildWarehouses)
    {
        // phpcs:enable

		$sql = 'SELECT rowid
				FROM '.MAIN_DB_PREFIX.'entrepot
				WHERE fk_parent = '.$id;

		$resql = $this->db->query($sql);
		if($resql) {
			while($res = $this->db->fetch_object($resql)) {
				$TChildWarehouses[] = $res->rowid;
				$this->get_children_warehouses($res->rowid, $TChildWarehouses);
			}
		}

		return $TChildWarehouses;
    }

	/**
	 *	Create object on disk
	 *
	 *	@param     string		$modele			force le modele a utiliser ('' to not force)
	 * 	@param     Translate	$outputlangs	Object langs to use for output
	 *  @param     int			$hidedetails    Hide details of lines
	 *  @param     int			$hidedesc       Hide description
	 *  @param     int			$hideref        Hide ref
	 *  @return    int             				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf,$user,$langs;

		$langs->load("stocks");

		if (! dol_strlen($modele)) {

			$modele = 'standard';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->STOCK_ADDON_PDF)) {
				$modele = $conf->global->STOCK_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/stock/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}
}
