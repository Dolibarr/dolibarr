<?php
/* Copyright (C) 2010 Regis Houssin  <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/product/canvas/product/actions_card_product.class.php
 *	\ingroup    produit
 *	\brief      File with class of actions for canvas product
 */
include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


/**
 *	Class with controller methods for product canvas
 */
class ActionsCardProduct
{
    var $targetmodule;
    var $canvas;
    var $card;

    //! Template container
	var $tpl = array();

	// List of fiels for action=list
	var $field_list =array();
    public $list_datas = array();


    /**
	 *    Constructor
	 *
     *    @param	DoliDB	$db             Database handler
     *    @param	string	$dirmodule		Name of directory of module
     *    @param	string	$targetmodule	Name of directory where canvas is stored
     *    @param	string	$canvas         Name of canvas
     *    @param	string	$card           Name of tab (sub-canvas)
	 */
	function __construct($db, $dirmodule, $targetmodule, $canvas, $card)
	{
        $this->db               = $db;
        $this->dirmodule		= $dirmodule;
        $this->targetmodule     = $targetmodule;
        $this->canvas           = $canvas;
        $this->card             = $card;

        $this->name 			= "product";
		$this->definition 		= "Product canvas (défaut)";
		$this->fieldListName    = "product_default";
		$this->next_prev_filter = "canvas='product'";
	}


	/**
	 *    Assign custom values for canvas (for example into this->tpl to be used by templates)
	 *
	 *    @param	string	$action    Type of action
	 *    @param	integer	$id			Id of object
	 *    @param	string	$ref		Ref of object
	 *    @return	void
	 */
	function assign_values(&$action, $id=0, $ref='')
	{
		global $limit, $offset, $sortfield, $sortorder;
        global $conf, $langs, $user, $mysoc, $canvas;
		global $form, $formproduct;

   		$tmpobject = new Product($this->db);
   		if (! empty($id) || ! empty($ref)) $tmpobject->fetch($id,$ref);
        $this->object = $tmpobject;

		//parent::assign_values($action);

	    foreach($this->object as $key => $value)
        {
            $this->tpl[$key] = $value;
        }

        $this->tpl['error'] = get_htmloutput_errors($this->object->error,$this->object->errors);

        // canvas
		$this->tpl['canvas'] = $this->canvas;

		// id
		$this->tpl['id'] = $this->id;

		// Ref
		$this->tpl['ref'] = $this->ref;

		// Label
		$this->tpl['label'] = $this->label;

		// Description
		$this->tpl['description'] = nl2br($this->description);

		// Statut
		$this->tpl['status'] = $this->object->getLibStatut(2);

		// Note
		$this->tpl['note'] = nl2br($this->note);

		if ($action == 'create')
		{
			// Price
			$this->tpl['price'] = $this->price;
			$this->tpl['price_min'] = $this->price_min;
			$this->tpl['price_base_type'] = $form->selectPriceBaseType($this->price_base_type, "price_base_type");

			// VAT
			$this->tpl['tva_tx'] = $form->load_tva("tva_tx",-1,$mysoc,'');
		}

		if ($action == 'create' || $action == 'edit')
		{
			// Status
			$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
			$this->tpl['status'] = $form->selectarray('statut',$statutarray,$this->status);

			//To Buy
			$statutarray=array('1' => $langs->trans("Yes"), '0' => $langs->trans("No"));
			$this->tpl['tobuy'] = $form->selectarray('tobuy',$statutarray,$this->status_buy);

            $this->tpl['description'] = $this->description;
            $this->tpl['note'] = $this->note;
		}

		if ($action == 'view')
		{
            $head = product_prepare_head($this->object);

            $this->tpl['showrefnav'] = $form->showrefnav($this->object,'ref','',1,'ref');

    		$titre=$langs->trans("CardProduct".$this->object->type);
    		$picto=($this->object->type==Product::TYPE_SERVICE?'service':'product');
    		$this->tpl['showhead']=dol_get_fiche_head($head, 'card', $titre, 0, $picto);
            $this->tpl['showend']=dol_get_fiche_end();

            // Accountancy buy code
			$this->tpl['accountancyBuyCodeKey'] = $form->editfieldkey("ProductAccountancyBuyCode",'productaccountancycodesell',$this->accountancy_code_sell,$this,$user->rights->produit->creer);
			$this->tpl['accountancyBuyCodeVal'] = $form->editfieldval("ProductAccountancyBuyCode",'productaccountancycodesell',$this->accountancy_code_sell,$this,$user->rights->produit->creer);

			// Accountancy sell code
			$this->tpl['accountancySellCodeKey'] = $form->editfieldkey("ProductAccountancySellCode",'productaccountancycodebuy',$this->accountancy_code_buy,$this,$user->rights->produit->creer);
			$this->tpl['accountancySellCodeVal'] = $form->editfieldval("ProductAccountancySellCode",'productaccountancycodebuy',$this->accountancy_code_buy,$this,$user->rights->produit->creer);
		}

		$this->tpl['finished'] = $this->object->finished;
		$this->tpl['ref'] = $this->object->ref;
		$this->tpl['label'] = $this->object->label;
		$this->tpl['id'] = $this->object->id;
		$this->tpl['type'] = $this->object->type;
		$this->tpl['note'] = $this->object->note;
		$this->tpl['seuil_stock_alerte'] = $this->object->seuil_stock_alerte;

		if ($action == 'create')
		{
			// Title
			$this->tpl['title'] = $langs->trans("NewProduct");
		}

		if ($action == 'edit')
		{
			$this->tpl['title'] = $langs->trans('Modify').' '.$langs->trans('Product').' : '.$this->object->ref;
		}

		if ($action == 'create' || $action == 'edit')
		{
    		// Status
    		$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
    		$this->tpl['status'] = $form->selectarray('statut',$statutarray,$_POST["statut"]);

    		$statutarray=array('1' => $langs->trans("ProductStatusOnBuy"), '0' => $langs->trans("ProductStatusNotOnBuy"));
    		$this->tpl['status_buy'] = $form->selectarray('statut_buy',$statutarray,$_POST["statut_buy"]);

		    // Finished
			$statutarray=array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
			$this->tpl['finished'] = $form->selectarray('finished',$statutarray,$this->object->finished);

			// Weight
			$this->tpl['weight'] = $this->object->weight;
			$this->tpl['weight_units'] = $formproduct->load_measuring_units("weight_units","weight",$this->object->weight_units);

			// Length
			$this->tpl['length'] = $this->object->length;
			$this->tpl['length_units'] = $formproduct->load_measuring_units("length_units","size",$this->object->length_units);

			// Surface
			$this->tpl['surface'] = $this->object->surface;
			$this->tpl['surface_units'] = $formproduct->load_measuring_units("surface_units","surface",$this->object->surface_units);

			// Volume
			$this->tpl['volume'] = $this->object->volume;
			$this->tpl['volume_units'] = $formproduct->load_measuring_units("volume_units","volume",$this->object->volume_units);
		}

		if ($action == 'view')
		{
    		// Status
    		$this->tpl['status'] = $this->object->getLibStatut(2,0);
    		$this->tpl['status_buy'] = $this->object->getLibStatut(2,1);

    		// Photo
			$this->tpl['nblignes'] = 4;
			if ($this->object->is_photo_available($conf->product->multidir_output[$this->object->entity]))
			{
				$this->tpl['photos'] = $this->object->show_photos('product', $conf->product->multidir_output[$this->object->entity],1,1,0,0,0,80);
			}

			// Nature
			$this->tpl['finished'] = $this->object->getLibFinished();

			// Weight
			if ($this->object->weight != '')
			{
				$this->tpl['weight'] = $this->object->weight." ".measuring_units_string($this->object->weight_units,"weight");
			}

			// Length
			if ($this->object->length != '')
			{
				$this->tpl['length'] = $this->object->length." ".measuring_units_string($this->object->length_units,"size");
			}

			// Surface
			if ($this->object->surface != '')
			{
				$this->tpl['surface'] = $this->object->surface." ".measuring_units_string($this->object->surface_units,"surface");
			}

			// Volume
			if ($this->object->volume != '')
			{
				$this->tpl['volume'] = $this->object->volume." ".measuring_units_string($this->object->volume_units,"volume");
			}

    		$this->tpl['fiche_end']=dol_get_fiche_end();
		}

		if ($action == 'list')
		{
	        $this->LoadListDatas($limit, $offset, $sortfield, $sortorder);
		}

	}


	/**
	 * 	Fetch field list
	 *
	 *  @return	void
	 */
	private function getFieldList()
	{
		global $conf, $langs;

		$this->field_list = array();

		$sql = "SELECT rowid, name, alias, title, align, sort, search, enabled, rang";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_field_list";
		$sql.= " WHERE element = '".$this->db->escape($this->fieldListName)."'";
		$sql.= " AND entity = ".$conf->entity;
		$sql.= " ORDER BY rang ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < $num)
			{
				$fieldlist = array();

				$obj = $this->db->fetch_object($resql);

				$fieldlist["id"]		= $obj->rowid;
				$fieldlist["name"]		= $obj->name;
				$fieldlist["alias"]		= $obj->alias;
				$fieldlist["title"]		= $langs->trans($obj->title);
				$fieldlist["align"]		= $obj->align;
				$fieldlist["sort"]		= $obj->sort;
				$fieldlist["search"]	= $obj->search;
				$fieldlist["enabled"]	= verifCond($obj->enabled);
				$fieldlist["order"]		= $obj->rang;

				array_push($this->field_list,$fieldlist);

				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db,$sql);
		}
	}


	/**
	 * 	Fetch datas list and save into ->list_datas
	 *
	 *  @param	int		$limit		Limit number of responses
	 *  @param	int		$offset		Offset for first response
	 *  @param	string	$sortfield	Sort field
	 *  @param	string	$sortorder	Sort order ('ASC' or 'DESC')
	 *  @return	void
	 */
	function LoadListDatas($limit, $offset, $sortfield, $sortorder)
	{
		global $conf, $langs;

        $this->getFieldList();

        $this->list_datas = array();

		// Clean parameters
        $sall=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));

		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$fieldname = "s".$field['alias'];
				$$fieldname = trim(GETPOST($fieldname));
			}
		}

		$sql = 'SELECT DISTINCT ';

		// Fields requiered
		$sql.= 'p.rowid, p.price_base_type, p.fk_product_type, p.seuil_stock_alerte, p.entity';

		// Fields not requiered
		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$sql.= ", ".$field['name']." as ".$field['alias'];
			}
		}

		$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
		$sql.= " WHERE p.entity IN (".getEntity('product').")";

		if ($sall)
		{
			$clause = '';
			$sql.= " AND (";
			foreach($this->field_list as $field)
			{
				if ($field['enabled'])
				{
					$sql.= $clause." ".$field['name']." LIKE '%".$this->db->escape($sall)."%'";
					if ($clause=='') $clause = ' OR';
				}
			}
			$sql.= ")";
		}

		// Search fields
		foreach($this->field_list as $field)
		{
			if ($field['enabled'])
			{
				$fieldname = "s".$field['alias'];
				if (${$fieldname}) $sql.= " AND ".$field['name']." LIKE '%".$this->db->escape(${$fieldname})."%'";
			}
		}

		if (isset($_GET["tosell"]) && dol_strlen($_GET["tosell"]) > 0)
		{
			$sql.= " AND p.tosell = ".$this->db->escape($_GET["tosell"]);
		}
		if (isset($_GET["canvas"]) && dol_strlen($_GET["canvas"]) > 0)
		{
			$sql.= " AND p.canvas = '".$this->db->escape($_GET["canvas"])."'";
		}
		$sql.= $this->db->order($sortfield,$sortorder);
		$sql.= $this->db->plimit($limit+1, $offset);
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < min($num,$limit))
			{
				$datas = array();

				$obj = $this->db->fetch_object($resql);

				$datas["id"]        = $obj->rowid;

				foreach($this->field_list as $field)
				{
					if ($field['enabled'])
					{
						$alias = $field['alias'];

						if ($alias == 'ref')
						{
							$this->id 		= $obj->rowid;
							$this->ref 		= $obj->$alias;
							$this->type 	= $obj->fk_product_type;
							$this->entity	= $obj->entity;
							$datas[$alias] 	= $this->getNomUrl(1,'',24);
						}
						else if ($alias == 'stock')
						{
							$this->load_stock();
							if ($this->stock_reel < $obj->seuil_stock_alerte) $datas[$alias] = $this->stock_reel.' '.img_warning($langs->trans("StockTooLow"));
							else $datas[$alias] = $this->stock_reel;
						}
						else if ($alias == 'label')	$datas[$alias] = dol_trunc($obj->$alias,40);
						else if (preg_match('/price/i',$alias))	$datas[$alias] = price($obj->$alias);
						else if ($alias == 'datem') $datas[$alias] = dol_print_date($this->db->jdate($obj->$alias),'day');
						else if ($alias == 'status') $datas[$alias] = $this->LibStatut($obj->$alias,5);
						else $datas[$alias] = $obj->$alias;
					}
				}

				array_push($this->list_datas,$datas);

				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

}

