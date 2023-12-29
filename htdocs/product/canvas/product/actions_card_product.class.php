<?php
/* Copyright (C) 2010-2018 Regis Houssin  <regis.houssin@inodbox.com>
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
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $dirmodule;
	public $targetmodule;
	public $canvas;
	public $card;

	public $name;
	public $definition;
	public $description;
	public $price_base_type;
	public $accountancy_code_sell;
	public $accountancy_code_buy;
	public $fieldListName;
	public $next_prev_filter;

	//! Object container
	public $object;

	//! Template container
	public $tpl = array();

	// List of fiels for action=list
	public $field_list = array();

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();


	/**
	 *    Constructor
	 *
	 *    @param	DoliDB	$db             Database handler
	 *    @param	string	$dirmodule		Name of directory of module
	 *    @param	string	$targetmodule	Name of directory where canvas is stored
	 *    @param	string	$canvas         Name of canvas
	 *    @param	string	$card           Name of tab (sub-canvas)
	 */
	public function __construct($db, $dirmodule, $targetmodule, $canvas, $card)
	{
		$this->db               = $db;
		$this->dirmodule = $dirmodule;
		$this->targetmodule     = $targetmodule;
		$this->canvas           = $canvas;
		$this->card             = $card;

		$this->name = "product";
		$this->definition = "Product canvas (default)";
		$this->fieldListName    = "product_default";
		$this->next_prev_filter = "canvas='product'";
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Assign custom values for canvas (for example into this->tpl to be used by templates)
	 *
	 *    @param	string	$action    Type of action
	 *    @param	integer	$id			Id of object
	 *    @param	string	$ref		Ref of object
	 *    @return	void
	 */
	public function assign_values(&$action, $id = 0, $ref = '')
	{
		// phpcs:enable
		global $conf, $langs, $user, $mysoc, $canvas;
		global $form, $formproduct;

		$tmpobject = new Product($this->db);
		if (!empty($id) || !empty($ref)) {
			$tmpobject->fetch($id, $ref);
		}
		$this->object = $tmpobject;

		foreach ($this->object as $key => $value) {
			$this->tpl[$key] = $value;
		}

		$this->tpl['error'] = get_htmloutput_errors($this->object->error, $this->object->errors);

		// canvas
		$this->tpl['canvas'] = $this->canvas;

		// id
		$this->tpl['id'] = $this->object->id;

		// Ref
		$this->tpl['ref'] = $this->object->ref;

		// Label
		$this->tpl['label'] = $this->object->label;

		// Description
		$this->tpl['description'] = nl2br($this->description);

		// Statut
		$this->tpl['status'] = $this->object->getLibStatut(2);

		// Note
		$this->tpl['note'] = $this->object->note_private;

		if ($action == 'create') {
			// Price
			$this->tpl['price'] = $this->object->price;
			$this->tpl['price_min'] = $this->object->price_min;
			$this->tpl['price_base_type'] = $form->selectPriceBaseType($this->price_base_type, "price_base_type");

			// VAT
			$this->tpl['tva_tx'] = $form->load_tva("tva_tx", -1, $mysoc, '');
		}

		if ($action == 'view') {
			$head = product_prepare_head($this->object);

			$this->tpl['showrefnav'] = $form->showrefnav($this->object, 'ref', '', 1, 'ref');

			$titre = $langs->trans("CardProduct".$this->object->type);
			$picto = ($this->object->type == Product::TYPE_SERVICE ? 'service' : 'product');
			$this->tpl['showhead'] = dol_get_fiche_head($head, 'card', $titre, 0, $picto);
			$this->tpl['showend'] = dol_get_fiche_end();

			// Accountancy buy code
			$this->tpl['accountancyBuyCodeKey'] = $form->editfieldkey("ProductAccountancyBuyCode", 'productaccountancycodesell', $this->accountancy_code_sell, $this, $user->rights->produit->creer);
			$this->tpl['accountancyBuyCodeVal'] = $form->editfieldval("ProductAccountancyBuyCode", 'productaccountancycodesell', $this->accountancy_code_sell, $this, $user->rights->produit->creer);

			// Accountancy sell code
			$this->tpl['accountancySellCodeKey'] = $form->editfieldkey("ProductAccountancySellCode", 'productaccountancycodebuy', $this->accountancy_code_buy, $this, $user->rights->produit->creer);
			$this->tpl['accountancySellCodeVal'] = $form->editfieldval("ProductAccountancySellCode", 'productaccountancycodebuy', $this->accountancy_code_buy, $this, $user->rights->produit->creer);
		}

		$this->tpl['finished'] = $this->object->finished;
		$this->tpl['ref'] = $this->object->ref;
		$this->tpl['label'] = $this->object->label;
		$this->tpl['id'] = $this->object->id;
		$this->tpl['type'] = $this->object->type;
		$this->tpl['note'] = $this->object->note_private;
		$this->tpl['seuil_stock_alerte'] = $this->object->seuil_stock_alerte;

		if ($action == 'create') {
			// Title
			$this->tpl['title'] = $langs->trans("NewProduct");
		}

		if ($action == 'edit') {
			$this->tpl['title'] = $langs->trans('Modify').' '.$langs->trans('Product').' : '.$this->object->ref;
		}

		if ($action == 'create' || $action == 'edit') {
			// Status
			$statutarray = array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
			$this->tpl['status'] = $form->selectarray('statut', $statutarray, $this->object->status);

			$statutarray = array('1' => $langs->trans("ProductStatusOnBuy"), '0' => $langs->trans("ProductStatusNotOnBuy"));
			$this->tpl['status_buy'] = $form->selectarray('statut_buy', $statutarray, $this->object->status_buy);

			$this->tpl['description'] = $this->object->description;
			$this->tpl['note'] = $this->object->note_private;

			// Finished
			$statutarray = array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
			$this->tpl['finished'] = $form->selectarray('finished', $statutarray, $this->object->finished);

			// Weight
			$this->tpl['weight'] = $this->object->weight;
			$this->tpl['weight_units'] = $formproduct->selectMeasuringUnits("weight_units", "weight", $this->object->weight_units, 0, 2);

			// Length
			$this->tpl['length'] = $this->object->length;
			$this->tpl['length_units'] = $formproduct->selectMeasuringUnits("length_units", "size", $this->object->length_units, 0, 2);

			// Surface
			$this->tpl['surface'] = $this->object->surface;
			$this->tpl['surface_units'] = $formproduct->selectMeasuringUnits("surface_units", "surface", $this->object->surface_units, 0, 2);

			// Volume
			$this->tpl['volume'] = $this->object->volume;
			$this->tpl['volume_units'] = $formproduct->selectMeasuringUnits("volume_units", "volume", $this->object->volume_units, 0, 2);
		}

		if ($action == 'view') {
			// Photo
			$this->tpl['nblines'] = 4;
			if ($this->object->is_photo_available($conf->product->multidir_output[$this->object->entity])) {
				$this->tpl['photos'] = $this->object->show_photos('product', $conf->product->multidir_output[$this->object->entity], 1, 1, 0, 0, 0, 80);
			}

			// Nature
			$this->tpl['finished'] = $this->object->getLibFinished();

			// Weight
			if ($this->object->weight != '') {
				$this->tpl['weight'] = $this->object->weight." ".measuringUnitString(0, "weight", $this->object->weight_units);
			}

			// Length
			if ($this->object->length != '') {
				$this->tpl['length'] = $this->object->length." ".measuringUnitString(0, "size", $this->object->length_units);
			}

			// Surface
			if ($this->object->surface != '') {
				$this->tpl['surface'] = $this->object->surface." ".measuringUnitString(0, "surface", $this->object->surface_units);
			}

			// Volume
			if ($this->object->volume != '') {
				$this->tpl['volume'] = $this->object->volume." ".measuringUnitString(0, "volume", $this->object->volume_units);
			}

			$this->tpl['fiche_end'] = dol_get_fiche_end();
		}
	}


	/**
	 * 	Fetch field list
	 *
	 *  @return	void
	 */
	private function getFieldListCanvas()
	{
		global $conf, $langs;

		$this->field_list = array();

		$sql = "SELECT rowid, name, alias, title, align, sort, search, visible, enabled, rang";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_field_list";
		$sql .= " WHERE element = '".$this->db->escape($this->fieldListName)."'";
		$sql .= " AND entity = ".$conf->entity;
		$sql .= " ORDER BY rang ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < $num) {
				$fieldlist = array();

				$obj = $this->db->fetch_object($resql);

				$fieldlist["id"] = $obj->rowid;
				$fieldlist["name"] = $obj->name;
				$fieldlist["alias"]		= $obj->alias;
				$fieldlist["title"]		= $langs->trans($obj->title);
				$fieldlist["align"]		= $obj->align;
				$fieldlist["sort"] = $obj->sort;
				$fieldlist["search"]	= $obj->search;
				$fieldlist["visible"]	= $obj->visible;
				$fieldlist["enabled"]	= verifCond($obj->enabled);
				$fieldlist["order"]		= $obj->rang;

				array_push($this->field_list, $fieldlist);

				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db, $sql);
		}
	}
}
