<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2011 Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley			<marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani			<acianfa@free.fr>
 * Copyright (C) 2008      Raphael Bertrand			<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2020 Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2010-2018 Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2014 Christophe Battarel  	<christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014      Marcos García            <marcosgdf@gmail.com>
 * Copyright (C) 2016      Ferran Marcet            <fmarcet@2byte.es>
 * Copyright (C) 2018      Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
 * Copyright (C) 2022      Gauthier VERDOL     		<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/supplier_proposal/class/supplier_proposal.class.php
 *	\brief      File of class to manage supplier proposals
 */

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonincoterm.class.php';

/**
 *	Class to manage price ask supplier
 */
class SupplierProposal extends CommonObject
{
	use CommonIncoterm;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'supplier_proposal';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'supplier_proposal';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'supplier_proposaldet';

	/**
	 * @var string Name of class line
	 */
	public $class_element_line = 'SupplierProposalLine';
	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_supplier_proposal';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'supplier_proposal';

	/**
	 * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 * @var integer
	 */
	public $restrictiononfksoc = 1;

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	public $socid; // Id client

	/**
	 * @deprecated
	 * @see $user_author_id
	 */
	public $author;

	public $ref_fourn; //Reference saisie lors de l'ajout d'une ligne à la demande
	public $ref_supplier; //Reference saisie lors de l'ajout d'une ligne à la demande

	/**
	 * @var int
	 * @deprecated
	 */
	public $statut; // 0 (draft), 1 (validated), 2 (signed), 3 (not signed), 4 (processed/billed)

	/**
	 * @var int|string Date of proposal
	 */
	public $date;

	/**
	 * @var null|int|'' date_livraison
	 */
	public $delivery_date;

	/**
	 * @deprecated
	 * @see $date_creation
	 */
	public $datec;

	/**
	 * @deprecated
	 * @see $date_validation
	 */
	public $datev;

	public $user_author_id;

	/**
	 * @deprecated
	 * @see $price_ht
	 */
	public $price;

	/**
	 * @deprecated
	 * @see $total_tva
	 */
	public $tva;

	/**
	 * @deprecated
	 * @see $total_ttc
	 */
	public $total;

	public $cond_reglement_code;
	public $cond_reglement_doc;		// label doc

	public $mode_reglement_code;
	/**
	 * @deprecated
	 * @var string	Mode reglement
	 */
	public $mode_reglement;

	/**
	 * @var array<string,string>  (Encoded as JSON in database)
	 */
	public $extraparams = array();
	public $lines = array();
	public $line;

	public $labelStatus = array();
	public $labelStatusShort = array();

	public $nbtodo;
	public $nbtodolate;

	// Multicurrency
	/**
	 * @var int ID
	 */
	public $fk_multicurrency;

	public $multicurrency_code;
	public $multicurrency_tx;
	public $multicurrency_total_ht;
	public $multicurrency_total_tva;
	public $multicurrency_total_ttc;

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Signed quote
	 */
	const STATUS_SIGNED = 2;

	/**
	 * Not signed quote, canceled
	 */
	const STATUS_NOTSIGNED = 3;

	/**
	 * Billed or closed/processed quote
	 */
	const STATUS_CLOSE = 4;



	/**
	 *	Constructor
	 *
	 *	@param      DoliDB	$db         Database handler
	 *	@param      int		$socid		Id third party
	 *	@param      int		$supplier_proposalid   Id supplier_proposal
	 */
	public function __construct($db, $socid = 0, $supplier_proposalid = 0)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->socid = $socid;
		$this->id = $supplier_proposalid;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Add line into array ->lines
	 *
	 * 	@param  int		$idproduct       	Product Id to add
	 * 	@param  float	$qty             	Quantity
	 * 	@param  int		$remise_percent  	Discount effected on Product
	 *  @return	int							Return integer <0 if KO, >0 if OK
	 *
	 *	TODO	Remplacer les appels a cette fonction par generation object Ligne
	 */
	public function add_product($idproduct, $qty, $remise_percent = 0)
	{
		// phpcs:enable
		global $conf, $mysoc;

		if (!$qty) {
			$qty = 1;
		}

		dol_syslog(get_class($this)."::add_product $idproduct, $qty, $remise_percent");
		if ($idproduct > 0) {
			$prod = new Product($this->db);
			$prod->fetch($idproduct);

			$productdesc = $prod->description;

			$tva_tx = get_default_tva($mysoc, $this->thirdparty, $prod->id);
			$tva_npr = get_default_npr($mysoc, $this->thirdparty, $prod->id);
			if (empty($tva_tx)) {
				$tva_npr = 0;
			}
			$localtax1_tx = get_localtax($tva_tx, 1, $mysoc, $this->thirdparty, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $mysoc, $this->thirdparty, $tva_npr);

			// multiprix
			if ($conf->global->PRODUIT_MULTIPRICES && $this->thirdparty->price_level) {
				$price = $prod->multiprices[$this->thirdparty->price_level];
			} else {
				$price = $prod->price;
			}

			$line = new SupplierProposalLine($this->db);

			$line->fk_product = $idproduct;
			$line->desc = $productdesc;
			$line->qty = $qty;
			$line->subprice = $price;
			$line->remise_percent = $remise_percent;
			$line->tva_tx = $tva_tx;

			$this->lines[] = $line;
			return 1;
		}
		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Adding line of fixed discount in the proposal in DB
	 *
	 *	@param     int		$idremise			Id of fixed discount
	 *  @return    int          				>0 if OK, <0 if KO
	 */
	public function insert_discount($idremise)
	{
		// phpcs:enable
		global $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$this->db->begin();

		$remise = new DiscountAbsolute($this->db);
		$result = $remise->fetch($idremise);

		if ($result > 0) {
			if ($remise->fk_facture) {	// Protection against multiple submission
				$this->error = $langs->trans("ErrorDiscountAlreadyUsed");
				$this->db->rollback();
				return -5;
			}

			$supplier_proposalligne = new SupplierProposalLine($this->db);
			$supplier_proposalligne->fk_supplier_proposal = $this->id;
			$supplier_proposalligne->fk_remise_except = $remise->id;
			$supplier_proposalligne->desc = $remise->description; // Description ligne
			$supplier_proposalligne->tva_tx = $remise->tva_tx;
			$supplier_proposalligne->subprice = -$remise->amount_ht;
			$supplier_proposalligne->fk_product = 0; // Id produit predefini
			$supplier_proposalligne->qty = 1;
			$supplier_proposalligne->remise_percent = 0;
			$supplier_proposalligne->rang = -1;
			$supplier_proposalligne->info_bits = 2;

			$supplier_proposalligne->total_ht  = -$remise->amount_ht;
			$supplier_proposalligne->total_tva = -$remise->amount_tva;
			$supplier_proposalligne->total_ttc = -$remise->amount_ttc;

			$result = $supplier_proposalligne->insert();
			if ($result > 0) {
				$result = $this->update_price(1);
				if ($result > 0) {
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $supplier_proposalligne->error;
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *    	Add a proposal line into database (linked to product/service or not)
	 * 		Les parameters sont deja cense etre juste et avec valeurs finales a l'appel
	 *		de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
	 *		par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,'',produit)
	 *		et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
	 *
	 * 		@param    	string		$desc				Description de la ligne
	 * 		@param    	double		$pu_ht				Prix unitaire
	 * 		@param    	double		$qty             	Quantite
	 * 		@param    	double		$txtva           	Taux de tva
	 * 		@param		double		$txlocaltax1		Local tax 1 rate
	 *  	@param		double		$txlocaltax2		Local tax 2 rate
	 *		@param    	int			$fk_product      	Product/Service ID predefined
	 * 		@param    	double		$remise_percent  	Percentage discount of the line
	 * 		@param    	string		$price_base_type	HT or TTC
	 * 		@param    	double		$pu_ttc             Prix unitaire TTC
	 * 		@param    	int			$info_bits			Bits of type of lines
	 *      @param      int			$type               Type of line (product, service)
	 *      @param      int			$rang               Position of line
	 *      @param		int			$special_code		Special code (also used by externals modules!)
	 *      @param		int			$fk_parent_line		Id of parent line
	 *      @param		int			$fk_fournprice		Id supplier price. If 0, we will take best price. If -1 we keep it empty.
	 *      @param		int			$pa_ht				Buying price without tax
	 *      @param		string		$label				???
	 *      @param		array		$array_options		extrafields array
	 * 		@param		string		$ref_supplier			Supplier price reference
	 * 		@param		int			$fk_unit			Id of the unit to use.
	 * 		@param		string		$origin				'order', 'supplier_proposal', ...
	 * 		@param		int			$origin_id			Id of origin line
	 * 		@param		double		$pu_ht_devise		Amount in currency
	 * 		@param		int			$date_start			Date start
	 * 		@param		int			$date_end			Date end
	 *    	@return    	int         	    			>0 if OK, <0 if KO
	 *
	 *    	@see       	add_product()
	 */
	public function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1 = 0, $txlocaltax2 = 0, $fk_product = 0, $remise_percent = 0, $price_base_type = 'HT', $pu_ttc = 0, $info_bits = 0, $type = 0, $rang = -1, $special_code = 0, $fk_parent_line = 0, $fk_fournprice = 0, $pa_ht = 0, $label = '', $array_options = [], $ref_supplier = '', $fk_unit = 0, $origin = '', $origin_id = 0, $pu_ht_devise = 0, $date_start = 0, $date_end = 0)
	{
		global $mysoc, $conf, $langs;

		dol_syslog(get_class($this)."::addline supplier_proposalid=$this->id, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_except=$remise_percent, price_base_type=$price_base_type, pu_ttc=$pu_ttc, info_bits=$info_bits, type=$type");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Clean parameters
		if (empty($remise_percent)) {
			$remise_percent = 0;
		}
		if (empty($qty)) {
			$qty = 0;
		}
		if (empty($info_bits)) {
			$info_bits = 0;
		}
		if (empty($rang)) {
			$rang = 0;
		}
		if (empty($fk_parent_line) || $fk_parent_line < 0) {
			$fk_parent_line = 0;
		}
		if (empty($pu_ht)) {
			$pu_ht = 0;
		}

		$remise_percent = price2num($remise_percent);
		$qty = (float) price2num($qty);
		$pu_ht = price2num($pu_ht);
		$pu_ttc = price2num($pu_ttc);
		if (!preg_match('/\((.*)\)/', (string) $txtva)) {
			$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
		}
		$txlocaltax1 = price2num($txlocaltax1);
		$txlocaltax2 = price2num($txlocaltax2);
		$pa_ht = price2num($pa_ht);
		if ($price_base_type == 'HT') {
			$pu = $pu_ht;
		} else {
			$pu = $pu_ttc;
		}

		// Check parameters
		if ($type < 0) {
			return -1;
		}

		if ($this->statut == self::STATUS_DRAFT) {
			$this->db->begin();

			if ($fk_product > 0) {
				if (getDolGlobalString('SUPPLIER_PROPOSAL_WITH_PREDEFINED_PRICES_ONLY')) {
					// Check quantity is enough
					dol_syslog(get_class($this)."::addline we check supplier prices fk_product=".$fk_product." fk_fournprice=".$fk_fournprice." qty=".$qty." ref_supplier=".$ref_supplier);
					$productsupplier = new ProductFournisseur($this->db);
					if ($productsupplier->fetch($fk_product) > 0) {
						$product_type = $productsupplier->type;
						$label = $productsupplier->label;
						$fk_prod_fourn_price = $fk_fournprice;

						// We use 'none' instead of $ref_supplier, because fourn_ref may not exists anymore. So we will take the first supplier price ok.
						// If we want a dedicated supplier price, we must provide $fk_prod_fourn_price.
						// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
						$result = $productsupplier->get_buyprice($fk_prod_fourn_price, $qty, $fk_product, 'none', $this->socid); // Search on couple $fk_prod_fourn_price/$qty first, then on triplet $qty/$fk_product/$ref_supplier/$this->socid
						if ($result > 0) {
							$pu = $productsupplier->fourn_pu; // Unit price supplier price set by get_buyprice
							$ref_supplier = $productsupplier->ref_supplier; // Ref supplier price set by get_buyprice
							// is remise percent not keyed but present for the product we add it
							if ($remise_percent == 0 && $productsupplier->remise_percent != 0) {
								$remise_percent = $productsupplier->remise_percent;
							}
						}
						if ($result == 0) {                   // If result == 0, we failed to found the supplier reference price
							$langs->load("errors");
							$this->error = "Ref ".$productsupplier->ref." ".$langs->trans("ErrorQtyTooLowForThisSupplier");
							$this->db->rollback();
							dol_syslog(get_class($this)."::addline we did not found supplier price, so we can't guess unit price");
							//$pu    = $productsupplier->fourn_pu;     // We do not overwrite unit price
							//$ref   = $productsupplier_fourn;    // We do not overwrite ref supplier price
							return -1;
						}
						if ($result == -1) {
							$langs->load("errors");
							$this->error = "Ref ".$productsupplier->ref." ".$langs->trans("ErrorQtyTooLowForThisSupplier");
							$this->db->rollback();
							dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_DEBUG);
							return -1;
						}
						if ($result < -1) {
							$this->error = $productsupplier->error;
							$this->errors = $productsupplier->errors;
							$this->db->rollback();
							dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_ERR);
							return -1;
						}
					} else {
						$this->error = $productsupplier->error;
						$this->errors = $productsupplier->errors;
						$this->db->rollback();
						return -1;
					}
				}
			} else {
				$product_type = $type;
			}

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			if (isModEnabled("multicurrency") && $pu_ht_devise > 0) {
				$pu = 0;
			}

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu = $pu_ht = $tabprice[3];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			// Rang to use
			$ranktouse = $rang;
			if ($ranktouse == -1) {
				$rangmax = $this->line_max($fk_parent_line);
				$ranktouse = $rangmax + 1;
			}

			// TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$price = $pu;
			$remise = 0;
			if ($remise_percent > 0) {
				$remise = round(($pu * (float) $remise_percent / 100), 2);
				$price = $pu - $remise;
			}

			// Insert line
			$this->line = new SupplierProposalLine($this->db);

			$this->line->fk_supplier_proposal = $this->id;
			$this->line->label = $label;
			$this->line->desc = $desc;
			$this->line->qty = $qty;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx = $txtva;
			$this->line->localtax1_tx = ($total_localtax1 ? $localtaxes_type[1] : 0);
			$this->line->localtax2_tx = ($total_localtax2 ? $localtaxes_type[3] : 0);
			$this->line->localtax1_type = empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$this->line->localtax2_type = empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];
			$this->line->fk_product = $fk_product;
			$this->line->remise_percent = $remise_percent;
			$this->line->subprice = $pu_ht;
			$this->line->rang = $ranktouse;
			$this->line->info_bits = $info_bits;
			$this->line->total_ht = $total_ht;
			$this->line->total_tva = $total_tva;
			$this->line->total_localtax1 = $total_localtax1;
			$this->line->total_localtax2 = $total_localtax2;
			$this->line->total_ttc = $total_ttc;
			$this->line->product_type = $type;
			$this->line->special_code = $special_code;
			$this->line->fk_parent_line = $fk_parent_line;
			$this->line->fk_unit = $fk_unit;
			$this->line->origin = $origin;
			$this->line->origin_id = $origin_id;
			$this->line->ref_fourn = $this->db->escape($ref_supplier);
			$this->line->date_start = $date_start;
			$this->line->date_end = $date_end;

			// infos merge
			if (!empty($fk_product) && $fk_product > 0 && empty($fk_fournprice) && empty($pa_ht)) {
				// When fk_fournprice is 0, we take the lowest buying price
				include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
				$productFournisseur = new ProductFournisseur($this->db);
				$productFournisseur->find_min_price_product_fournisseur($fk_product);
				$this->line->fk_fournprice = $productFournisseur->product_fourn_price_id;
			} else {
				$this->line->fk_fournprice = ($fk_fournprice > 0 ? $fk_fournprice : 0); // If fk_fournprice is -1, we will not use fk_fournprice
			}
			$this->line->pa_ht = $pa_ht;
			//var_dump($this->line->fk_fournprice);exit;

			// Multicurrency
			$this->line->fk_multicurrency = $this->fk_multicurrency;
			$this->line->multicurrency_code = $this->multicurrency_code;
			$this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht		= $multicurrency_total_ht;
			$this->line->multicurrency_total_tva	= $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc	= $multicurrency_total_ttc;

			// Mise en option de la ligne
			if (empty($qty) && empty($special_code)) {
				$this->line->special_code = 3;
			}

			if (is_array($array_options) && count($array_options) > 0) {
				$this->line->array_options = $array_options;
			}

			$result = $this->line->insert();
			if ($result > 0) {
				// Reorder if child line
				if (!empty($fk_parent_line)) {
					$this->line_order(true, 'DESC');
				} elseif ($ranktouse > 0 && $ranktouse <= count($this->lines)) { // Update all rank of all other lines
					$linecount = count($this->lines);
					for ($ii = $ranktouse; $ii <= $linecount; $ii++) {
						$this->updateRangOfLine($this->lines[$ii - 1]->id, $ii + 1);
					}
				}

				// Mise a jour information denormalisees au niveau de la propale meme
				$result = $this->update_price(1, 'auto', 0, $this->thirdparty); // This method is designed to add line from user input so total calculation must be done using 'auto' mode.
				if ($result > 0) {
					$this->db->commit();
					return $this->line->id;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $this->line->error;
				$this->errors = $this->line->errors;
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = 'BadStatusOfObjectToAddLine';
			return -5;
		}
	}


	/**
	 *  Update a proposal line
	 *
	 *  @param      int			$rowid           	Id de la ligne
	 *  @param      double		$pu		     	  	Unit price (HT or TTC depending on price_base_type)
	 *  @param      double		$qty            	Quantity
	 *  @param      double		$remise_percent  	Discount on line
	 *  @param      double		$txtva	          	VAT rate
	 * 	@param	  	double		$txlocaltax1		Local tax 1 rate
	 *  @param	  	double		$txlocaltax2		Local tax 2 rate
	 *  @param      string		$desc            	Description
	 *	@param	  	string		$price_base_type	HT or TTC
	 *	@param      int			$info_bits        	Miscellaneous information
	 *	@param		int			$special_code		Special code (also used by externals modules!)
	 * 	@param		int			$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
	 * 	@param		int			$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
	 *  @param		int			$fk_fournprice		Id of origin supplier price
	 *  @param		int			$pa_ht				Price (without tax) of product when it was bought
	 *  @param		string		$label				???
	 *  @param		int			$type				0/1=Product/service
	 *  @param		array		$array_options		extrafields array
	 * 	@param		string		$ref_supplier		Supplier price reference
	 *	@param		int			$fk_unit			Id of the unit to use.
	 * 	@param		double		$pu_ht_devise		Unit price in currency
	 *  @return     int     		        		0 if OK, <0 if KO
	 */
	public function updateline($rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1 = 0, $txlocaltax2 = 0, $desc = '', $price_base_type = 'HT', $info_bits = 0, $special_code = 0, $fk_parent_line = 0, $skip_update_total = 0, $fk_fournprice = 0, $pa_ht = 0, $label = '', $type = 0, $array_options = [], $ref_supplier = '', $fk_unit = 0, $pu_ht_devise = 0)
	{
		global $conf, $user, $langs, $mysoc;

		dol_syslog(get_class($this)."::updateLine $rowid, $pu, $qty, $remise_percent, $txtva, $desc, $price_base_type, $info_bits");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Clean parameters
		$remise_percent = price2num($remise_percent);
		$qty = (float) price2num($qty);
		$pu = price2num($pu);
		if (!preg_match('/\((.*)\)/', (string) $txtva)) {
			$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
		}
		$txlocaltax1 = price2num($txlocaltax1);
		$txlocaltax2 = price2num($txlocaltax2);
		$pa_ht = price2num($pa_ht);
		if (empty($qty) && empty($special_code)) {
			$special_code = 3; // Set option tag
		}
		if (!empty($qty) && $special_code == 3) {
			$special_code = 0; // Remove option tag
		}

		if ($this->status == 0) {
			$this->db->begin();

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $mysoc, $this->thirdparty);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			if (isModEnabled("multicurrency") && $pu_ht_devise > 0) {
				$pu = 0;
			}

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht = $tabprice[3];
			$pu_tva = $tabprice[4];
			$pu_ttc = $tabprice[5];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			$pu = $pu_ht;
			if ($price_base_type == 'TTC') {
				$pu = $pu_ttc;
			}

			//Fetch current line from the database and then clone the object and set it in $oldline property
			$line = new SupplierProposalLine($this->db);
			$line->fetch($rowid);
			$line->fetch_optionals();

			$fk_product = $line->fk_product;

			// Stock previous line records
			$staticline = clone $line;

			$line->oldline = $staticline;
			$this->line = $line;
			$this->line->context = $this->context;

			// Reorder if fk_parent_line change
			if (!empty($fk_parent_line) && !empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line) {
				$rangmax = $this->line_max($fk_parent_line);
				$this->line->rang = $rangmax + 1;
			}

			$this->line->id					= $rowid;
			$this->line->label = $label;
			$this->line->desc = $desc;
			$this->line->qty				= $qty;
			$this->line->product_type = $type;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx = $txtva;
			$this->line->localtax1_tx		= $txlocaltax1;
			$this->line->localtax2_tx		= $txlocaltax2;
			$this->line->localtax1_type		= empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$this->line->localtax2_type		= empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];
			$this->line->remise_percent		= $remise_percent;
			$this->line->subprice			= $pu;
			$this->line->info_bits			= $info_bits;
			$this->line->total_ht			= $total_ht;
			$this->line->total_tva			= $total_tva;
			$this->line->total_localtax1	= $total_localtax1;
			$this->line->total_localtax2	= $total_localtax2;
			$this->line->total_ttc			= $total_ttc;
			$this->line->special_code = $special_code;
			$this->line->fk_parent_line		= $fk_parent_line;
			$this->line->skip_update_total = $skip_update_total;
			$this->line->ref_fourn = $ref_supplier;
			$this->line->fk_unit = $fk_unit;

			// infos merge
			if (!empty($fk_product) && $fk_product > 0 && empty($fk_fournprice) && empty($pa_ht)) {
				// by external module, take lowest buying price
				include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
				$productFournisseur = new ProductFournisseur($this->db);
				$productFournisseur->find_min_price_product_fournisseur($fk_product);
				$this->line->fk_fournprice = $productFournisseur->product_fourn_price_id;
			} else {
				$this->line->fk_fournprice = $fk_fournprice;
			}
			$this->line->pa_ht = $pa_ht;

			if (is_array($array_options) && count($array_options) > 0) {
				// We replace values in this->line->array_options only for entries defined into $array_options
				foreach ($array_options as $key => $value) {
					$this->line->array_options[$key] = $array_options[$key];
				}
			}

			// Multicurrency
			$this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht		= $multicurrency_total_ht;
			$this->line->multicurrency_total_tva	= $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc	= $multicurrency_total_ttc;

			$result = $this->line->update();
			if ($result > 0) {
				// Reorder if child line
				if (!empty($fk_parent_line)) {
					$this->line_order(true, 'DESC');
				}

				$this->update_price(1);

				$this->db->commit();
				return $result;
			} else {
				$this->error = $this->db->error();
				$this->db->rollback();
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::updateline Erreur -2 SupplierProposal en mode incompatible pour cette action");
			return -2;
		}
	}


	/**
	 *  Delete detail line
	 *
	 *  @param		int		$lineid			Id of line to delete
	 *  @return     int         			>0 if OK, <0 if KO
	 */
	public function deleteLine($lineid)
	{
		global $user;

		if ($this->statut == 0) {
			$line = new SupplierProposalLine($this->db);

			// For triggers
			$line->fetch($lineid);

			if ($line->delete($user) > 0) {
				$this->update_price(1);

				return 1;
			} else {
				return -1;
			}
		} else {
			return -2;
		}
	}


	/**
	 *  Create commercial proposal into database
	 * 	this->ref can be set or empty. If empty, we will use "(PROVid)"
	 *
	 * 	@param		User	$user		User that create
	 * 	@param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return     int     			Return integer <0 if KO, >=0 if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $langs, $conf, $mysoc, $hookmanager;
		$error = 0;

		$now = dol_now();

		dol_syslog(get_class($this)."::create");

		// Check parameters
		$result = $this->fetch_thirdparty();
		if ($result < 0) {
			$this->error = "Failed to fetch company";
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -3;
		}
		if (!empty($this->ref)) {	// We check that ref is not already used
			$result = self::isExistingObject($this->element, 0, $this->ref); // Check ref is not yet used
			if ($result > 0) {
				$this->error = 'ErrorRefAlreadyExists';
				dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);
				$this->db->rollback();
				return -1;
			}
		}

		// Set tmp vars
		$delivery_date = $this->delivery_date;

		// Multicurrency
		if (!empty($this->multicurrency_code)) {
			list($this->fk_multicurrency, $this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code, $now);
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

		$this->db->begin();

		// Insert into database
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."supplier_proposal (";
		$sql .= "fk_soc";
		$sql .= ", price";
		$sql .= ", total_tva";
		$sql .= ", total_ttc";
		$sql .= ", datec";
		$sql .= ", ref";
		$sql .= ", fk_user_author";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", model_pdf";
		$sql .= ", fk_cond_reglement";
		$sql .= ", fk_mode_reglement";
		$sql .= ", fk_account";
		$sql .= ", date_livraison";
		$sql .= ", fk_shipping_method";
		$sql .= ", fk_projet";
		$sql .= ", entity";
		$sql .= ", fk_multicurrency";
		$sql .= ", multicurrency_code";
		$sql .= ", multicurrency_tx";
		$sql .= ") ";
		$sql .= " VALUES (";
		$sql .= ((int) $this->socid);
		$sql .= ", 0";
		$sql .= ", 0";
		$sql .= ", 0";
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", '(PROV)'";
		$sql .= ", ".($user->id > 0 ? ((int) $user->id) : "null");
		$sql .= ", '".$this->db->escape($this->note_private)."'";
		$sql .= ", '".$this->db->escape($this->note_public)."'";
		$sql .= ", '".$this->db->escape($this->model_pdf)."'";
		$sql .= ", ".($this->cond_reglement_id > 0 ? ((int) $this->cond_reglement_id) : 'NULL');
		$sql .= ", ".($this->mode_reglement_id > 0 ? ((int) $this->mode_reglement_id) : 'NULL');
		$sql .= ", ".($this->fk_account > 0 ? ((int) $this->fk_account) : 'NULL');
		$sql .= ", ".(isDolTms($delivery_date) ? "'".$this->db->idate($delivery_date)."'" : "null");
		$sql .= ", ".($this->shipping_method_id > 0 ? ((int) $this->shipping_method_id) : 'NULL');
		$sql .= ", ".($this->fk_project > 0 ? ((int) $this->fk_project) : "null");
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".((int) $this->fk_multicurrency);
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".((float) $this->multicurrency_tx);
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."supplier_proposal");

			if ($this->id) {
				$this->ref = '(PROV'.$this->id.')';
				$sql = 'UPDATE '.MAIN_DB_PREFIX."supplier_proposal SET ref='".$this->db->escape($this->ref)."' WHERE rowid=".((int) $this->id);

				dol_syslog(get_class($this)."::create", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
				}

				if (!empty($this->linkedObjectsIds) && empty($this->linked_objects)) {	// To use new linkedObjectsIds instead of old linked_objects
					$this->linked_objects = $this->linkedObjectsIds; // TODO Replace linked_objects with linkedObjectsIds
				}

				// Add object linked
				if (!$error && $this->id && !empty($this->linked_objects) && is_array($this->linked_objects)) {
					foreach ($this->linked_objects as $origin => $tmp_origin_id) {
						if (is_array($tmp_origin_id)) {       // New behaviour, if linked_object can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
							foreach ($tmp_origin_id as $origin_id) {
								$ret = $this->add_object_linked($origin, $origin_id);
								if (!$ret) {
									dol_print_error($this->db);
									$error++;
								}
							}
						}
					}
				}

				/*
				 *  Insertion du detail des produits dans la base
				 */
				if (!$error) {
					$fk_parent_line = 0;
					$num = count($this->lines);

					for ($i = 0; $i < $num; $i++) {
						// Reset fk_parent_line for no child products and special product
						if (($this->lines[$i]->product_type != 9 && empty($this->lines[$i]->fk_parent_line)) || $this->lines[$i]->product_type == 9) {
							$fk_parent_line = 0;
						}

						$result = $this->addline(
							$this->lines[$i]->desc,
							$this->lines[$i]->subprice,
							$this->lines[$i]->qty,
							$this->lines[$i]->tva_tx,
							$this->lines[$i]->localtax1_tx,
							$this->lines[$i]->localtax2_tx,
							$this->lines[$i]->fk_product,
							$this->lines[$i]->remise_percent,
							'HT',
							0,
							0,
							$this->lines[$i]->product_type,
							$this->lines[$i]->rang,
							$this->lines[$i]->special_code,
							$fk_parent_line,
							$this->lines[$i]->fk_fournprice,
							$this->lines[$i]->pa_ht,
							empty($this->lines[$i]->label) ? '' : $this->lines[$i]->label, // deprecated
							$this->lines[$i]->array_options,
							$this->lines[$i]->ref_fourn,
							$this->lines[$i]->fk_unit,
							'supplier_proposal',
							$this->lines[$i]->rowid
						);

						if ($result < 0) {
							$error++;
							$this->error = $this->db->error;
							dol_print_error($this->db);
							break;
						}
						// Defined the new fk_parent_line
						if ($result > 0 && $this->lines[$i]->product_type == 9) {
							$fk_parent_line = $result;
						}
					}
				}

				if (!$error) {
					// Mise a jour infos denormalisees
					$resql = $this->update_price(1);
					if ($resql) {
						$action = 'update';

						// Actions on extra fields
						if (!$error) {
							$result = $this->insertExtraFields();
							if ($result < 0) {
								$error++;
							}
						}

						if (!$error && !$notrigger) {
							// Call trigger
							$result = $this->call_trigger('PROPOSAL_SUPPLIER_CREATE', $user);
							if ($result < 0) {
								$error++;
							}
							// End call triggers
						}
					} else {
						$this->error = $this->db->lasterror();
						$error++;
					}
				}
			} else {
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error) {
				$this->db->commit();
				dol_syslog(get_class($this)."::create done id=".$this->id);
				return $this->id;
			} else {
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *		Load an object from its id and create a new one in database
	 *
	 *      @param	    User	$user		    User making the clone
	 *		@param		int		$fromid			Id of thirdparty
	 * 	 	@return		int						New id of clone
	 */
	public function createFromClone(User $user, $fromid = 0)
	{
		global $conf, $hookmanager;

		$error = 0;
		$now = dol_now();

		$this->db->begin();

		// get extrafields so they will be clone
		foreach ($this->lines as $line) {
			$line->fetch_optionals();
		}

		// Load source object
		$objFrom = clone $this;

		$objsoc = new Societe($this->db);

		// Change socid if needed
		if (!empty($fromid) && $fromid != $this->socid) {
			if ($objsoc->fetch($fromid) > 0) {
				$this->socid = $objsoc->id;
				$this->cond_reglement_id = (!empty($objsoc->cond_reglement_id) ? $objsoc->cond_reglement_id : 0);
				$this->mode_reglement_id = (!empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
				unset($this->fk_project);
			}

			// TODO Change product price if multi-prices
		} else {
			$objsoc->fetch($this->socid);
		}

		$this->id = 0;
		$this->statut = 0;

		if (!getDolGlobalString('SUPPLIER_PROPOSAL_ADDON') || !is_readable(DOL_DOCUMENT_ROOT."/core/modules/supplier_proposal/" . getDolGlobalString('SUPPLIER_PROPOSAL_ADDON').".php")) {
			$this->error = 'ErrorSetupNotComplete';
			return -1;
		}

		// Clear fields
		$this->user_author_id = $user->id;
		$this->user_validation_id = 0;
		$this->date = $now;

		// Set ref
		require_once DOL_DOCUMENT_ROOT."/core/modules/supplier_proposal/" . getDolGlobalString('SUPPLIER_PROPOSAL_ADDON').'.php';
		$obj = getDolGlobalString('SUPPLIER_PROPOSAL_ADDON');
		$modSupplierProposal = new $obj();
		$this->ref = $modSupplierProposal->getNextValue($objsoc, $this);

		// Create clone
		$this->context['createfromclone'] = 'createfromclone';
		$result = $this->create($user);
		if ($result < 0) {
			$error++;
		}

		if (!$error) {
			// Hook of thirdparty module
			if (is_object($hookmanager)) {
				$parameters = array('objFrom' => $objFrom);
				$action = '';
				$reshook = $hookmanager->executeHooks('createFrom', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->setErrorsFromObject($hookmanager);
					$error++;
				}
			}
		}

		unset($this->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Load a proposal from database and its ligne array
	 *
	 *	@param      int			$rowid		id of object to load
	 *	@param		string		$ref		Ref of proposal
	 *	@return     int         			>0 if OK, <0 if KO
	 */
	public function fetch($rowid, $ref = '')
	{
		global $conf;

		$sql = "SELECT p.rowid, p.entity, p.ref, p.fk_soc as socid";
		$sql .= ", p.total_ttc, p.total_tva, p.localtax1, p.localtax2, p.total_ht";
		$sql .= ", p.datec";
		$sql .= ", p.date_valid as datev";
		$sql .= ", p.date_livraison as delivery_date";
		$sql .= ", p.model_pdf, p.extraparams";
		$sql .= ", p.note_private, p.note_public";
		$sql .= ", p.fk_projet as fk_project, p.fk_statut";
		$sql .= ", p.fk_user_author, p.fk_user_valid, p.fk_user_cloture";
		$sql .= ", p.fk_cond_reglement";
		$sql .= ", p.fk_mode_reglement";
		$sql .= ', p.fk_account';
		$sql .= ", p.fk_shipping_method";
		$sql .= ", p.fk_multicurrency, p.multicurrency_code, p.multicurrency_tx, p.multicurrency_total_ht, p.multicurrency_total_tva, p.multicurrency_total_ttc";
		$sql .= ", c.label as statut_label";
		$sql .= ", cr.code as cond_reglement_code, cr.libelle as cond_reglement, cr.libelle_facture as cond_reglement_libelle_doc";
		$sql .= ", cp.code as mode_reglement_code, cp.libelle as mode_reglement";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_propalst as c, ".MAIN_DB_PREFIX."supplier_proposal as p";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as cp ON p.fk_mode_reglement = cp.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as cr ON p.fk_cond_reglement = cr.rowid';
		$sql .= " WHERE p.fk_statut = c.id";
		$sql .= " AND p.entity IN (".getEntity('supplier_proposal').")";
		if ($ref) {
			$sql .= " AND p.ref = '".$this->db->escape($ref)."'";
		} else {
			$sql .= " AND p.rowid = ".((int) $rowid);
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id                   = $obj->rowid;
				$this->entity               = $obj->entity;

				$this->ref                  = $obj->ref;
				$this->total_ht             = $obj->total_ht;
				$this->total_tva            = $obj->total_tva;
				$this->total_localtax1		= $obj->localtax1;
				$this->total_localtax2		= $obj->localtax2;
				$this->total_ttc            = $obj->total_ttc;
				$this->socid                = $obj->socid;
				$this->fk_project           = $obj->fk_project;
				$this->model_pdf            = $obj->model_pdf;
				$this->note                 = $obj->note_private; // TODO deprecated
				$this->note_private         = $obj->note_private;
				$this->note_public          = $obj->note_public;
				$this->statut               = (int) $obj->fk_statut;
				$this->status               = (int) $obj->fk_statut;
				$this->datec                = $this->db->jdate($obj->datec); // TODO deprecated
				$this->datev                = $this->db->jdate($obj->datev); // TODO deprecated
				$this->date_creation = $this->db->jdate($obj->datec);	// Creation date
				$this->date                 = $this->date_creation;
				$this->date_validation = $this->db->jdate($obj->datev); // Validation date
				$this->delivery_date        = $this->db->jdate($obj->delivery_date);
				$this->shipping_method_id   = ($obj->fk_shipping_method > 0) ? $obj->fk_shipping_method : null;

				$this->mode_reglement_id    = $obj->fk_mode_reglement;
				$this->mode_reglement_code  = $obj->mode_reglement_code;
				$this->mode_reglement       = $obj->mode_reglement;
				$this->fk_account           = ($obj->fk_account > 0) ? $obj->fk_account : null;
				$this->cond_reglement_id    = $obj->fk_cond_reglement;
				$this->cond_reglement_code  = $obj->cond_reglement_code;
				$this->cond_reglement       = $obj->cond_reglement;
				$this->cond_reglement_doc   = $obj->cond_reglement_libelle_doc;

				$this->extraparams = (array) json_decode($obj->extraparams, true);

				$this->user_author_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->user_closing_id = $obj->fk_user_cloture;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht = $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$this->db->free($resql);

				$this->lines = array();

				// Lines of supplier proposals
				$sql = "SELECT d.rowid, d.fk_supplier_proposal, d.fk_parent_line, d.label as custom_label, d.description, d.price, d.tva_tx, d.localtax1_tx, d.localtax2_tx, d.qty, d.fk_remise_except, d.remise_percent, d.subprice, d.fk_product,";
				$sql .= " d.info_bits, d.total_ht, d.total_tva, d.total_localtax1, d.total_localtax2, d.total_ttc, d.fk_product_fournisseur_price as fk_fournprice, d.buy_price_ht as pa_ht, d.special_code, d.rang, d.product_type,";
				$sql .= ' p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label as product_label,';
				$sql .= ' d.ref_fourn as ref_produit_fourn,';
				$sql .= ' d.fk_multicurrency, d.multicurrency_code, d.multicurrency_subprice, d.multicurrency_total_ht, d.multicurrency_total_tva, d.multicurrency_total_ttc, d.fk_unit';
				$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposaldet as d";
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON d.fk_product = p.rowid";
				$sql .= " WHERE d.fk_supplier_proposal = ".((int) $this->id);
				$sql .= " ORDER by d.rang";

				$result = $this->db->query($sql);
				if ($result) {
					$num = $this->db->num_rows($result);
					$i = 0;

					while ($i < $num) {
						$objp                   = $this->db->fetch_object($result);

						$line                   = new SupplierProposalLine($this->db);

						$line->rowid = $objp->rowid; // deprecated
						$line->id = $objp->rowid;
						$line->fk_supplier_proposal = $objp->fk_supplier_proposal;
						$line->fk_parent_line = $objp->fk_parent_line;
						$line->product_type     = $objp->product_type;
						$line->label            = $objp->custom_label;
						$line->desc             = $objp->description; // Description ligne
						$line->qty              = $objp->qty;
						$line->tva_tx           = $objp->tva_tx;
						$line->localtax1_tx		= $objp->localtax1_tx;
						$line->localtax2_tx		= $objp->localtax2_tx;
						$line->subprice         = $objp->subprice;
						$line->fk_remise_except = $objp->fk_remise_except;
						$line->remise_percent   = $objp->remise_percent;

						$line->info_bits        = $objp->info_bits;
						$line->total_ht         = $objp->total_ht;
						$line->total_tva        = $objp->total_tva;
						$line->total_localtax1	= $objp->total_localtax1;
						$line->total_localtax2	= $objp->total_localtax2;
						$line->total_ttc        = $objp->total_ttc;
						$line->fk_fournprice 	= $objp->fk_fournprice;
						$marginInfos = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
						$line->pa_ht = $marginInfos[0];
						$line->marge_tx			= $marginInfos[1];
						$line->marque_tx		= $marginInfos[2];
						$line->special_code     = $objp->special_code;
						$line->rang             = $objp->rang;

						$line->fk_product       = $objp->fk_product;

						$line->ref = $objp->product_ref; // deprecated
						$line->product_ref = $objp->product_ref;
						$line->libelle = $objp->product_label; // deprecated
						$line->product_label = $objp->product_label;
						$line->product_desc     = $objp->product_desc; // Description produit
						$line->fk_product_type  = $objp->fk_product_type;

						$line->ref_fourn = $objp->ref_produit_fourn;

						// Multicurrency
						$line->fk_multicurrency = $objp->fk_multicurrency;
						$line->multicurrency_code = $objp->multicurrency_code;
						$line->multicurrency_subprice 	= $objp->multicurrency_subprice;
						$line->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
						$line->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
						$line->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;
						$line->fk_unit = $objp->fk_unit;

						$this->lines[$i] = $line;

						$i++;
					}
					$this->db->free($result);
				} else {
					$this->error = $this->db->error();
					return -1;
				}

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				return 1;
			}

			$this->error = "Record Not Found";
			return 0;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Set status to validated
	 *
	 *  @param	User	$user       Object user that validate
	 *  @param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return int         		Return integer <0 if KO, >=0 if OK
	 */
	public function valid($user, $notrigger = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		global $conf, $langs;

		$error = 0;
		$now = dol_now();

		if ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('supplier_proposal', 'creer'))
		   || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('supplier_proposal', 'validate_advance'))) {
			$this->db->begin();

			// Numbering module definition
			$soc = new Societe($this->db);
			$result = $soc->fetch($this->socid);

			if ($result < 0) {
				return -1;
			}

			// Define new ref
			if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) { // empty should not happened, but when it occurs, the test save life
				$num = $this->getNextNumRef($soc);
			} else {
				$num = $this->ref;
			}
			$this->newref = dol_sanitizeFileName($num);

			$sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal";
			$sql .= " SET ref = '".$this->db->escape($num)."',";
			$sql .= " fk_statut = 1, date_valid='".$this->db->idate($now)."', fk_user_valid=".((int) $user->id);
			$sql .= " WHERE rowid = ".((int) $this->id)." AND fk_statut = 0";

			dol_syslog(get_class($this)."::valid", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$error++;
			}

			// Trigger calls
			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('PROPOSAL_SUPPLIER_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->oldref = $this->ref;

				// Rename directory if dir was a temporary ref
				if (preg_match('/^[\(]?PROV/i', $this->ref)) {
					// Now we rename also files into index
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'supplier_proposal/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'supplier_proposal/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'supplier_proposal/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filepath = 'supplier_proposal/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}

					// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
					$oldref = dol_sanitizeFileName($this->ref);
					$newref = dol_sanitizeFileName($num);
					$dirsource = $conf->supplier_proposal->dir_output.'/'.$oldref;
					$dirdest = $conf->supplier_proposal->dir_output.'/'.$newref;
					if (!$error && file_exists($dirsource)) {
						dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);
						if (@rename($dirsource, $dirdest)) {
							dol_syslog("Rename ok");
							// Rename docs starting with $oldref with $newref
							$listoffiles = dol_dir_list($conf->supplier_proposal->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
							foreach ($listoffiles as $fileentry) {
								$dirsource = $fileentry['name'];
								$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
								$dirsource = $fileentry['path'].'/'.$dirsource;
								$dirdest = $fileentry['path'].'/'.$dirdest;
								@rename($dirsource, $dirdest);
							}
						}
					}
				}

				$this->ref = $num;
				$this->statut = self::STATUS_VALIDATED;
				$this->status = self::STATUS_VALIDATED;
				$this->user_validation_id = $user->id;
				$this->datev = $now;
				$this->date_validation = $now;

				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			dol_syslog("You don't have permission to validate supplier proposal", LOG_WARNING);
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set delivery date
	 *
	 *	@param      User 	$user        		Object user that modify
	 *	@param      int		$delivery_date		Delivery date
	 *	@return     int         				Return integer <0 if ko, >0 if ok
	 *	@deprecated Use  setDeliveryDate
	 */
	public function set_date_livraison($user, $delivery_date)
	{
		// phpcs:enable
		return $this->setDeliveryDate($user, $delivery_date);
	}

	/**
	 *	Set delivery date
	 *
	 *	@param      User 		$user        		Object user that modify
	 *	@param      int			$delivery_date     Delivery date
	 *	@return     int         					Return integer <0 if ko, >0 if ok
	 */
	public function setDeliveryDate($user, $delivery_date)
	{
		if ($user->hasRight('supplier_proposal', 'creer')) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal ";
			$sql .= " SET date_livraison = ".(isDolTms($delivery_date) ? "'".$this->db->idate($delivery_date)."'" : 'null');
			$sql .= " WHERE rowid = ".((int) $this->id);

			if ($this->db->query($sql)) {
				$this->delivery_date = $delivery_date;
				return 1;
			} else {
				$this->error = $this->db->error();
				dol_syslog(get_class($this)."::setDeliveryDate Erreur SQL");
				return -1;
			}
		}
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set an overall discount on the proposal
	 *
	 *	@param      User	$user       Object user that modify
	 *	@param      double	$remise      Amount discount
	 *	@return     int         		Return integer <0 if ko, >0 if ok
	 */
	/*
	public function set_remise_percent($user, $remise)
	{
		// phpcs:enable
		$remise = trim($remise) ?trim($remise) : 0;

		if ($user->hasRight('supplier_proposal', 'creer')) {
			$remise = price2num($remise, 2);

			$sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal SET remise_percent = ".((float) $remise);
			$sql .= " WHERE rowid = ".((int) $this->id)." AND fk_statut = 0";

			if ($this->db->query($sql)) {
				$this->remise_percent = ((float) $remise);
				$this->update_price(1);
				return 1;
			} else {
				$this->error = $this->db->error();
				return -1;
			}
		}
		return 0;
	}
	*/

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set an absolute overall discount on the proposal
	 *
	 *	@param      User	$user        Object user that modify
	 *	@param      double	$remise      Amount discount
	 *	@return     int         		Return integer <0 if ko, >0 if ok
	 */
	/*
	public function set_remise_absolue($user, $remise)
	{
		// phpcs:enable
		if (empty($remise)) {
			$remise = 0;
		}

		$remise = price2num($remise);

		if ($user->hasRight('supplier_proposal', 'creer')) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal ";
			$sql .= " SET remise_absolue = ".((float) $remise);
			$sql .= " WHERE rowid = ".((int) $this->id)." AND fk_statut = 0";

			if ($this->db->query($sql)) {
				$this->remise_absolue = $remise;
				$this->update_price(1);
				return 1;
			} else {
				$this->error = $this->db->error();
				return -1;
			}
		}
		return 0;
	}
	*/


	/**
	 *	Reopen the commercial proposal
	 *
	 *	@param      User	$user		Object user that close
	 *	@param      int		$statut		Statut
	 *	@param      string	$note		Comment
	 *  @param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return     int         		Return integer <0 if KO, >0 if OK
	 */
	public function reopen($user, $statut, $note = '', $notrigger = 0)
	{
		global $langs, $conf;

		$this->statut = $statut;
		$error = 0;

		$sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal";
		$sql .= " SET fk_statut = ".((int) $this->statut).",";
		if (!empty($note)) {
			$sql .= " note_private = '".$this->db->escape($note)."',";
		}
		$sql .= " date_cloture = NULL, fk_user_cloture = NULL";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::reopen", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}
		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('PROPOSAL_SUPPLIER_REOPEN', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			if (!empty($this->errors)) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *	Close the askprice
	 *
	 *	@param      User	$user		Object user that close
	 *	@param      int		$status		Status
	 *	@param      string	$note		Comment
	 *	@return     int         		Return integer <0 if KO, >0 if OK
	 */
	public function cloture($user, $status, $note)
	{
		global $langs, $conf;
		$hidedetails = 0;
		$hidedesc = 0;
		$hideref = 0;
		$this->statut = $status;
		$error = 0;
		$now = dol_now();

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal";
		$sql .= " SET fk_statut = ".((int) $status).", note_private = '".$this->db->escape($note)."', date_cloture='".$this->db->idate($now)."', fk_user_cloture=".$user->id;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$modelpdf = $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_CLOSED ? $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_CLOSED : (empty($this->model_pdf) ? '' : $this->model_pdf);
			$triggerName = 'PROPOSAL_SUPPLIER_CLOSE_REFUSED';

			if ($status == 2) {
				$triggerName = 'PROPOSAL_SUPPLIER_CLOSE_SIGNED';
				$modelpdf = $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_TOBILL ? $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_TOBILL : (empty($this->model_pdf) ? '' : $this->model_pdf);

				if (getDolGlobalString('SUPPLIER_PROPOSAL_UPDATE_PRICE_ON_SUPPlIER_PROPOSAL')) {     // TODO This option was not tested correctly. Error if product ref does not exists
					$result = $this->updateOrCreatePriceFournisseur($user);
				}
			}
			if ($status == 4) {
				$triggerName = 'PROPOSAL_SUPPLIER_CLASSIFY_BILLED';
			}

			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				if (getDolGlobalInt('MAIN_MULTILANGS')) {
					$outputlangs = new Translate("", $conf);
					$newlang = (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $this->thirdparty->default_lang);
					$outputlangs->setDefaultLang($newlang);
				}
				//$ret=$object->fetch($id);    // Reload to get new records
				$this->generateDocument($modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}

			// Call trigger
			$result = $this->call_trigger($triggerName, $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Add or update supplier price according to result of proposal
	 *
	 *	@param     User	    $user       Object user
	 *  @return    int                  > 0 if OK
	 */
	public function updateOrCreatePriceFournisseur($user)
	{
		global $conf;

		dol_syslog(get_class($this)."::updateOrCreatePriceFournisseur", LOG_DEBUG);
		foreach ($this->lines as $product) {
			if ($product->subprice <= 0) {
				continue;
			}
			$productsupplier = new ProductFournisseur($this->db);

			$multicurrency_tx = 1;
			$fk_multicurrency = 0;

			if (empty($this->thirdparty)) {
				$this->fetch_thirdparty();
			}

			$ref_fourn = $product->ref_fourn;
			if (empty($ref_fourn)) {
				$ref_fourn = $product->ref_supplier;
			}
			if (isModEnabled("multicurrency") && !empty($product->multicurrency_code)) {
				list($fk_multicurrency, $multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $product->multicurrency_code);
			}
			$productsupplier->id = $product->fk_product;

			$productsupplier->update_buyprice($product->qty, $product->total_ht, $user, 'HT', $this->thirdparty, '', $ref_fourn, $product->tva_tx, 0, 0, 0, $product->info_bits, '', '', array(), '', $product->multicurrency_total_ht, 'HT', $multicurrency_tx, $product->multicurrency_code, '', '', '');
		}

		return 1;
	}

	/**
	 *	Update ProductFournisseur
	 *
	 * 	@param		int 	$idProductFournPrice	id of llx_product_fournisseur_price
	 * 	@param		Product $product				contain information to update
	 *	@param      User	$user					Object user
	 *	@return     int         					Return integer <0 if KO, >0 if OK
	 */
	public function updatePriceFournisseur($idProductFournPrice, $product, $user)
	{
		$price = price2num($product->subprice * $product->qty, 'MU');
		$unitPrice = price2num($product->subprice, 'MU');

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'product_fournisseur_price SET '.(!empty($product->ref_fourn) ? 'ref_fourn = "'.$this->db->escape($product->ref_fourn).'", ' : '').' price ='.((float) $price).', unitprice ='.((float) $unitPrice).' WHERE rowid = '.((int) $idProductFournPrice);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
		return 1;
	}

	/**
	 *	Create ProductFournisseur
	 *
	 *	@param		Product 	$product	Object Product
	 *	@param      User		$user		Object user
	 *	@return     int         			Return integer <0 if KO, >0 if OK
	 */
	public function createPriceFournisseur($product, $user)
	{
		global $conf;

		$price = price2num($product->subprice * $product->qty, 'MU');
		$qty = price2num($product->qty);
		$unitPrice = price2num($product->subprice, 'MU');

		$now = dol_now();

		$values = array(
			"'".$this->db->idate($now)."'",
			$product->fk_product,
			$this->thirdparty->id,
			"'".$product->ref_fourn."'",
			$price,
			$qty,
			$unitPrice,
			$product->tva_tx,
			$user->id
		);
		if (isModEnabled("multicurrency")) {
			if (!empty($product->multicurrency_code)) {
				include_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
				$multicurrency = new MultiCurrency($this->db); //need to fetch because empty fk_multicurrency and rate
				$multicurrency->fetch(0, $product->multicurrency_code);
				if (!empty($multicurrency->id)) {
					$values[] = $multicurrency->id;
					$values[] = "'".$product->multicurrency_code."'";
					$values[] = $product->multicurrency_subprice;
					$values[] = $product->multicurrency_total_ht;
					$values[] = $multicurrency->rate->rate;
				} else {
					for ($i = 0; $i < 5; $i++) {
						$values[] = 'NULL';
					}
				}
			}
		}

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'product_fournisseur_price ';
		$sql .= '(datec, fk_product, fk_soc, ref_fourn, price, quantity, unitprice, tva_tx, fk_user';
		if (isModEnabled("multicurrency") && !empty($product->multicurrency_code)) {
			$sql .= ',fk_multicurrency, multicurrency_code, multicurrency_unitprice, multicurrency_price, multicurrency_tx';
		}
		$sql .= ')  VALUES ('.implode(',', $values).')';

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set draft status
	 *
	 *	@param		User	$user		Object user that modify
	 *	@return		int					Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user)
	{
		// phpcs:enable
		global $conf, $langs;

		$error = 0;

		if ($this->statut == self::STATUS_DRAFT) {
			dol_syslog(get_class($this)."::setDraft already draft status", LOG_WARNING);
			return 0;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal";
		$sql .= " SET fk_statut = ".self::STATUS_DRAFT;
		$sql .= " WHERE rowid = ".((int) $this->id);

		if ($this->db->query($sql)) {
			if (!$error) {
				$this->oldcopy = clone $this;
			}

			if (!$error) {
				// Call trigger
				$result = $this->call_trigger('PROPOSAL_SUPPLIER_UNVALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				$this->status = self::STATUS_DRAFT;
				$this->statut = self::STATUS_DRAFT;	// deprecated
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return list of askprice (eventually filtered on user) into an array
	 *
	 *    @param	int		$shortlist			0=Return array[id]=ref, 1=Return array[](id=>id,ref=>ref,name=>name)
	 *    @param	int		$draft				0=not draft, 1=draft
	 *    @param	int		$notcurrentuser		0=all user, 1=not current user
	 *    @param    int		$socid				Id third party
	 *    @param    int		$limit				For pagination
	 *    @param    int		$offset				For pagination
	 *    @param    string	$sortfield			Sort criteria
	 *    @param    string	$sortorder			Sort order
	 *    @return	array|int		       				-1 if KO, array with result if OK
	 */
	public function liste_array($shortlist = 0, $draft = 0, $notcurrentuser = 0, $socid = 0, $limit = 0, $offset = 0, $sortfield = 'p.datec', $sortorder = 'DESC')
	{
		// phpcs:enable
		global $user;

		$ga = array();

		$search_sale = 0;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$search_sale = $user->id;
		}

		$sql = "SELECT s.rowid, s.nom as name, s.client,";
		$sql .= " p.rowid as supplier_proposalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
		$sql .= " p.datep as dp, p.fin_validite as datelimite";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."supplier_proposal as p, ".MAIN_DB_PREFIX."c_propalst as c";
		$sql .= " WHERE p.entity IN (".getEntity('supplier_proposal').")";
		$sql .= " AND p.fk_soc = s.rowid";
		$sql .= " AND p.fk_statut = c.id";
		if ($socid) {
			$sql .= " AND s.rowid = ".((int) $socid);
		}
		if ($draft) {
			$sql .= " AND p.fk_statut = 0";
		}
		if ($notcurrentuser > 0) {
			$sql .= " AND p.fk_user_author <> ".((int) $user->id);
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		$sql .= $this->db->order($sortfield, $sortorder);
		$sql .= $this->db->plimit($limit, $offset);

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($result);

					if ($shortlist == 1) {
						$ga[$obj->supplier_proposalid] = $obj->ref;
					} elseif ($shortlist == 2) {
						$ga[$obj->supplier_proposalid] = $obj->ref.' ('.$obj->name.')';
					} else {
						$ga[$i]['id'] = $obj->supplier_proposalid;
						$ga[$i]['ref'] 	= $obj->ref;
						$ga[$i]['name'] = $obj->name;
					}

					$i++;
				}
			}
			return $ga;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Delete askprice
	 *
	 *	@param	User	$user        	Object user that delete
	 *	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return	int						1 if ok, otherwise if error
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('PROPOSAL_SUPPLIER_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$main = MAIN_DB_PREFIX.'supplier_proposaldet';
			$ef = $main."_extrafields";
			$sqlef = "DELETE FROM $ef WHERE fk_object IN (SELECT rowid FROM $main WHERE fk_supplier_proposal = ".((int) $this->id).")";
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."supplier_proposaldet WHERE fk_supplier_proposal = ".((int) $this->id);
			if ($this->db->query($sql)) {
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."supplier_proposal WHERE rowid = ".((int) $this->id);
				if ($this->db->query($sqlef) && $this->db->query($sql)) {
					// Delete linked object
					$res = $this->deleteObjectLinked();
					if ($res < 0) {
						$error++;
					}

					if (!$error) {
						// Delete record into ECM index (Note that delete is also done when deleting files with the dol_delete_dir_recursive
						$this->deleteEcmFiles(0); // Deleting files physically is done later with the dol_delete_dir_recursive
						$this->deleteEcmFiles(1); // Deleting files physically is done later with the dol_delete_dir_recursive

						// We remove directory
						$ref = dol_sanitizeFileName($this->ref);
						if ($conf->supplier_proposal->dir_output && !empty($this->ref)) {
							$dir = $conf->supplier_proposal->dir_output."/".$ref;
							$file = $dir."/".$ref.".pdf";
							if (file_exists($file)) {
								dol_delete_preview($this);

								if (!dol_delete_file($file, 0, 0, 0, $this)) { // For triggers
									$this->error = 'ErrorFailToDeleteFile';
									$this->errors = array('ErrorFailToDeleteFile');
									$this->db->rollback();
									return 0;
								}
							}
							if (file_exists($dir)) {
								$res = @dol_delete_dir_recursive($dir);
								if (!$res) {
									$this->error = 'ErrorFailToDeleteDir';
									$this->errors = array('ErrorFailToDeleteDir');
									$this->db->rollback();
									return 0;
								}
							}
						}
					}

					// Removed extrafields
					if (!$error) {
						$result = $this->deleteExtraFields();
						if ($result < 0) {
							$error++;
							$errorflag = -4;
							dol_syslog(get_class($this)."::delete erreur ".$errorflag." ".$this->error, LOG_ERR);
						}
					}

					if (!$error) {
						dol_syslog(get_class($this)."::delete ".$this->id." by ".$user->id, LOG_DEBUG);
						$this->db->commit();
						return 1;
					} else {
						$this->error = $this->db->lasterror();
						$this->db->rollback();
						return 0;
					}
				} else {
					$this->error = $this->db->lasterror();
					$this->db->rollback();
					return -3;
				}
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Object SupplierProposal Information
	 *
	 * 	@param	int		$id		Proposal id
	 *  @return	void
	 */
	public function info($id)
	{
		$sql = "SELECT c.rowid, ";
		$sql .= " c.datec as date_creation, c.date_valid as date_validation, c.date_cloture as date_closure,";
		$sql .= " c.fk_user_author, c.fk_user_valid, c.fk_user_cloture";
		$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal as c";
		$sql .= " WHERE c.rowid = ".((int) $id);

		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id                = $obj->rowid;

				$this->date_creation     = $this->db->jdate($obj->date_creation);
				$this->date_validation   = $this->db->jdate($obj->date_validation);
				$this->date_cloture      = $this->db->jdate($obj->date_closure);

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->user_closing_id = $obj->fk_user_cloture;
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}


	/**
	 *    	Return label of status of proposal (draft, validated, ...)
	 *
	 *    	@param      int			$mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *    	@return     string		Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut((isset($this->statut) ? $this->statut : $this->status), $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of a status (draft, validated, ...)
	 *
	 *  @param      int			$status		Id status
	 *  @param  	int			$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return     string      Label
	 */
	public function LibStatut($status, $mode = 1)
	{
		// phpcs:enable

		// Init/load array of translation of status
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load("supplier_proposal");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv("SupplierProposalStatusDraft");
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv("SupplierProposalStatusValidated");
			$this->labelStatus[self::STATUS_SIGNED] = $langs->transnoentitiesnoconv("SupplierProposalStatusSigned");
			$this->labelStatus[self::STATUS_NOTSIGNED] = $langs->transnoentitiesnoconv("SupplierProposalStatusNotSigned");
			$this->labelStatus[self::STATUS_CLOSE] = $langs->transnoentitiesnoconv("SupplierProposalStatusClosed");
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv("SupplierProposalStatusDraftShort");
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv("SupplierProposalStatusValidatedShort");
			$this->labelStatusShort[self::STATUS_SIGNED] = $langs->transnoentitiesnoconv("SupplierProposalStatusSignedShort");
			$this->labelStatusShort[self::STATUS_NOTSIGNED] = $langs->transnoentitiesnoconv("SupplierProposalStatusNotSignedShort");
			$this->labelStatusShort[self::STATUS_CLOSE] = $langs->transnoentitiesnoconv("SupplierProposalStatusClosedShort");
		}

		$statusnew = '';
		if ($status == self::STATUS_DRAFT) {
			$statusnew = 'status0';
		} elseif ($status == self::STATUS_VALIDATED) {
			$statusnew = 'status1';
		} elseif ($status == self::STATUS_SIGNED) {
			$statusnew = 'status4';
		} elseif ($status == self::STATUS_NOTSIGNED) {
			$statusnew = 'status9';
		} elseif ($status == self::STATUS_CLOSE) {
			$statusnew = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusnew, $mode);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param          User	$user   Object user
	 *      @param          string	$mode   "opened" for askprice to close, "signed" for proposal to invoice
	 *      @return         WorkboardResponse|int	Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $mode)
	{
		// phpcs:enable
		global $conf, $user, $langs;

		$now = dol_now();

		$clause = " WHERE";

		$sql = "SELECT p.rowid, p.ref, p.datec as datec, p.date_cloture as datefin";
		$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal as p";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = " AND";
		}
		$sql .= $clause." p.entity IN (".getEntity('supplier_proposal').")";
		if ($mode == 'opened') {
			$sql .= " AND p.fk_statut = 1";
		}
		if ($mode == 'signed') {
			$sql .= " AND p.fk_statut = 2";
		}
		if ($user->socid) {
			$sql .= " AND p.fk_soc = ".((int) $user->socid);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$label = $labelShort = '';
			$status = '';
			if ($mode == 'opened') {
				$delay_warning = !empty($conf->supplier_proposal->cloture->warning_delay) ? $conf->supplier_proposal->cloture->warning_delay : 0;
				$status = self::STATUS_VALIDATED;
				$label = $langs->trans("SupplierProposalsToClose");
				$labelShort = $langs->trans("ToAcceptRefuse");
			}
			if ($mode == 'signed') {
				$delay_warning = !empty($conf->supplier_proposal->facturation->warning_delay) ? $conf->supplier_proposal->facturation->warning_delay : 0;
				$status = self::STATUS_SIGNED;
				$label = $langs->trans("SupplierProposalsToProcess"); // May be billed or ordered
				$labelShort = $langs->trans("ToClose");
			}

			$response = new WorkboardResponse();
			$response->warning_delay = $delay_warning / 60 / 60 / 24;
			$response->label = $label;
			$response->labelShort = $labelShort;
			$response->url = DOL_URL_ROOT.'/supplier_proposal/list.php?search_status='.$status;
			$response->img = img_object('', "propal");

			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;
				if ($mode == 'opened') {
					$datelimit = $this->db->jdate($obj->datefin);
					if ($datelimit < ($now - $delay_warning)) {
						$response->nbtodolate++;
					}
				}
				// TODO Definir regle des propales a facturer en retard
				// if ($mode == 'signed' && ! count($this->FactureListeArray($obj->rowid))) $this->nbtodolate++;
			}
			return $response;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return int
	 */
	public function initAsSpecimen()
	{
		global $user, $langs, $conf;

		// Load array of products prodids
		$num_prods = 0;
		$prodids = array();
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		$sql .= $this->db->plimit(100);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods) {
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->socid = 1;
		$this->date = time();
		$this->cond_reglement_id   = 1;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_id   = 7;
		$this->mode_reglement_code = 'CHQ';
		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';
		// Lines
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$line = new SupplierProposalLine($this->db);
			$line->desc = $langs->trans("Description")." ".$xnbp;
			$line->qty = 1;
			$line->subprice = 100;
			$line->tva_tx = 19.6;
			$line->localtax1_tx = 0;
			$line->localtax2_tx = 0;
			if ($xnbp == 2) {
				$line->total_ht = 50;
				$line->total_ttc = 59.8;
				$line->total_tva = 9.8;
				$line->remise_percent = 50;
			} else {
				$line->total_ht = 100;
				$line->total_ttc = 119.6;
				$line->total_tva = 19.6;
				$line->remise_percent = 00;
			}

			if ($num_prods > 0) {
				$prodid = mt_rand(1, $num_prods);
				$line->fk_product = $prodids[$prodid];
			}

			$this->lines[$xnbp] = $line;

			$this->total_ht       += $line->total_ht;
			$this->total_tva      += $line->total_tva;
			$this->total_ttc      += $line->total_ttc;

			$xnbp++;
		}

		return 1;
	}

	/**
	 *      Load indicator this->nb of global stats widget
	 *
	 *      @return     int         Return integer <0 if ko, >0 if ok
	 */
	public function loadStateBoard()
	{
		global $conf, $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(p.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." p.entity IN (".getEntity('supplier_proposal').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["supplier_proposals"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Returns the reference to the following non used Proposal used depending on the active numbering module
	 *  defined into SUPPLIER_PROPOSAL_ADDON
	 *
	 *  @param	Societe		$soc  	Object thirdparty
	 *  @return string      		Reference libre pour la propale
	 */
	public function getNextNumRef($soc)
	{
		global $conf, $db, $langs;
		$langs->load("supplier_proposal");

		if (getDolGlobalString('SUPPLIER_PROPOSAL_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('SUPPLIER_PROPOSAL_ADDON') . ".php";
			$classname = getDolGlobalString('SUPPLIER_PROPOSAL_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/supplier_proposal/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($soc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				return "";
			}
		} else {
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("SupplierProposal"));
			return "";
		}
	}

	/**
	 * getTooltipContentArray
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @since v18
	 * @return array{picto?:string,ref?:string,refsupplier?:string,label?:string,date?:string,date_echeance?:string,amountht?:string,total_ht?:string,totaltva?:string,amountlt1?:string,amountlt2?:string,amountrevenustamp?:string,totalttc?:string}|array{optimize:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $menumanager;

		$langs->load('supplier_proposal');

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowSupplierProposal")];
		}

		$option = $params['option'] ?? '';
		$datas = [];

		$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("SupplierProposal").'</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		if (!empty($this->ref)) {
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (!empty($this->ref_fourn)) {
			$datas['ref_supplier'] = '<br><b>'.$langs->trans('RefSupplier').':</b> '.$this->ref_fourn;
		}
		if (!empty($this->total_ht)) {
			$datas['amount_ht'] = '<br><b>'.$langs->trans('AmountHT').':</b> '.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
		}
		if (!empty($this->total_tva)) {
			$datas['amount_vat'] = '<br><b>'.$langs->trans('VAT').':</b> '.price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
		}
		if (!empty($this->total_ttc)) {
			$datas['amount_ttc'] = '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
		}

		return $datas;
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      int		$withpicto					Add picto into link
	 *	@param      string	$option						Where point the link ('compta', 'expedition', 'document', ...)
	 *	@param      string	$get_params    				Parameters added to url
	 *  @param	    int   	$notooltip					1=Disable tooltip
	 *  @param      int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param		int		$addlinktonotes				Add link to show notes
	 *	@return     string          					String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $get_params = '', $notooltip = 0, $save_lastsearch_value = -1, $addlinktonotes = 0)
	{
		global $langs, $conf, $user, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$url = '';
		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		if ($option == '') {
			$url = DOL_URL_ROOT.'/supplier_proposal/card.php?id='.$this->id.$get_params;
		}
		if ($option == 'document') {
			$url = DOL_URL_ROOT.'/supplier_proposal/document.php?id='.$this->id.$get_params;
		}

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip) && $user->hasRight('propal', 'lire')) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowSupplierProposal");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.'"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		if ($addlinktonotes) {
			$txttoshow = ($user->socid > 0 ? $this->note_public : $this->note_private);
			if ($txttoshow) {
				$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($txttoshow, 1);
				$result .= ' <span class="note inline-block">';
				$result .= '<a href="'.DOL_URL_ROOT.'/supplier_proposal/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow).'">';
				$result .= img_picto('', 'note');
				$result .= '</a>';
				//$result.=img_picto($langs->trans("ViewNote"),'object_generic');
				//$result.='</a>';
				$result .= '</span>';
			}
		}
		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}

	/**
	 * 	Retrieve an array of supplier proposal lines
	 *
	 * 	@return int		>0 if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		// For other object, here we call fetch_lines. But fetch_lines does not exists on supplier proposal

		$sql = 'SELECT pt.rowid, pt.label as custom_label, pt.description, pt.fk_product, pt.fk_remise_except,';
		$sql .= ' pt.qty, pt.tva_tx, pt.vat_src_code, pt.remise_percent, pt.subprice, pt.info_bits,';
		$sql .= ' pt.total_ht, pt.total_tva, pt.total_ttc, pt.fk_product_fournisseur_price as fk_fournprice, pt.buy_price_ht as pa_ht, pt.special_code, pt.localtax1_tx, pt.localtax2_tx,';
		$sql .= ' pt.product_type, pt.rang, pt.fk_parent_line,';
		$sql .= ' p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid,';
		$sql .= ' p.description as product_desc, pt.ref_fourn as ref_supplier,';
		$sql .= ' pt.fk_multicurrency, pt.multicurrency_code, pt.multicurrency_subprice, pt.multicurrency_total_ht, pt.multicurrency_total_tva, pt.multicurrency_total_ttc, pt.fk_unit';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'supplier_proposaldet as pt';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
		$sql .= ' WHERE pt.fk_supplier_proposal = '.((int) $this->id);
		$sql .= ' ORDER BY pt.rang ASC, pt.rowid';

		dol_syslog(get_class($this).'::getLinesArray', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$this->lines[$i] = new SupplierProposalLine($this->db);
				$this->lines[$i]->id = $obj->rowid; // for backward compatibility
				$this->lines[$i]->rowid				= $obj->rowid;
				$this->lines[$i]->label 			= $obj->custom_label;
				$this->lines[$i]->description = $obj->description;
				$this->lines[$i]->fk_product = $obj->fk_product;
				$this->lines[$i]->ref = $obj->ref;
				$this->lines[$i]->product_label = $obj->product_label;
				$this->lines[$i]->product_desc		= $obj->product_desc;
				$this->lines[$i]->fk_product_type = $obj->fk_product_type; // deprecated
				$this->lines[$i]->product_type		= $obj->product_type;
				$this->lines[$i]->qty = $obj->qty;
				$this->lines[$i]->subprice = $obj->subprice;
				$this->lines[$i]->fk_remise_except = $obj->fk_remise_except;
				$this->lines[$i]->remise_percent = $obj->remise_percent;
				$this->lines[$i]->tva_tx = $obj->tva_tx;
				$this->lines[$i]->vat_src_code = $obj->vat_src_code;
				$this->lines[$i]->info_bits			= $obj->info_bits;
				$this->lines[$i]->total_ht = $obj->total_ht;
				$this->lines[$i]->total_tva			= $obj->total_tva;
				$this->lines[$i]->total_ttc			= $obj->total_ttc;
				$this->lines[$i]->fk_fournprice = $obj->fk_fournprice;
				$marginInfos = getMarginInfos($obj->subprice, $obj->remise_percent, $obj->tva_tx, $obj->localtax1_tx, $obj->localtax2_tx, $this->lines[$i]->fk_fournprice, $obj->pa_ht);
				$this->lines[$i]->pa_ht = $marginInfos[0];
				$this->lines[$i]->marge_tx = $marginInfos[1];
				$this->lines[$i]->marque_tx = $marginInfos[2];
				$this->lines[$i]->fk_parent_line = $obj->fk_parent_line;
				$this->lines[$i]->special_code = $obj->special_code;
				$this->lines[$i]->rang = $obj->rang;

				$this->lines[$i]->ref_fourn = $obj->ref_supplier; // deprecated
				$this->lines[$i]->ref_supplier = $obj->ref_supplier;

				// Multicurrency
				$this->lines[$i]->fk_multicurrency = $obj->fk_multicurrency;
				$this->lines[$i]->multicurrency_code = $obj->multicurrency_code;
				$this->lines[$i]->multicurrency_subprice 	= $obj->multicurrency_subprice;
				$this->lines[$i]->multicurrency_total_ht 	= $obj->multicurrency_total_ht;
				$this->lines[$i]->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->lines[$i]->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;
				$this->lines[$i]->fk_unit = $obj->fk_unit;

				$i++;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 * 	@param	    string		$modele			Force model to use ('' to not force)
	 * 	@param		Translate	$outputlangs	Object langs to use for output
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param   null|array  $moreparams     Array to provide more information
	 * 	@return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load("supplier_proposal");
		$outputlangs->load("products");

		if (!dol_strlen($modele)) {
			$modele = 'aurore';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('SUPPLIER_PROPOSAL_ADDON_PDF')) {
				$modele = getDolGlobalString('SUPPLIER_PROPOSAL_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/supplier_proposal/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}


	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'supplier_proposal'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old product id
	 * @param int $dest_id New product id
	 * @return bool
	 */
	public static function replaceProduct(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'supplier_proposaldet'
		);

		return CommonObject::commonReplaceProduct($db, $origin_id, $dest_id, $tables);
	}


	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    			$option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array{string,mixed}		$arraydata				Array of data
	 *  @return		string											HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		//$return .= '<i class="fa fa-dol-action"></i>'; // Can be image
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'socid')) {
			$return .= '<span class="info-box-ref"> | '.$this->socid.'</span>';
		}
		if (property_exists($this, 'delivery_date')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("DateEnd").'</span> : <span class="info-box-label">'.dol_print_date($this->delivery_date).'</span>';
		}
		if (property_exists($this, 'total_ttc')) {
			$return .= '<br><span class="opacitymedium" >'.$langs->trans("AmountHT").' : </span><span class="info-box-label amount">'.price($this->total_ttc).'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}


/**
 *	Class to manage supplier_proposal lines
 */
class SupplierProposalLine extends CommonObjectLine
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'supplier_proposaldet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'supplier_proposaldet';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'supplier_proposal';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_supplier_proposal';

	public $oldline;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var int ID
	 */
	public $fk_supplier_proposal;

	/**
	 * @var int ID
	 */
	public $fk_parent_line;

	public $desc; // Description ligne

	/**
	 * @var int ID
	 */
	public $fk_product; // Id produit predefini

	/**
	 * @deprecated
	 * @see $product_type
	 */
	public $fk_product_type;
	/**
	 * Product type
	 * @var int
	 * @see Product::TYPE_PRODUCT, Product::TYPE_SERVICE
	 */
	public $product_type = Product::TYPE_PRODUCT;

	/**
	 * @var float Quantity
	 */
	public $qty;
	public $tva_tx;
	public $vat_src_code;

	/**
	 * Unit price before taxes
	 * @var float
	 */
	public $subprice;
	public $remise_percent;

	/**
	 * @var int ID
	 */
	public $fk_remise_except;

	public $rang = 0;

	/**
	 * @var int ID
	 */
	public $fk_fournprice;

	public $pa_ht;
	public $marge_tx;
	public $marque_tx;

	/**
	 * @var int special code
	 */
	public $special_code; // Tag for special lines (exclusive tags)
	// 1: frais de port
	// 2: ecotaxe
	// 3: option line (when qty = 0)

	public $info_bits = 0; // Liste d'options cumulables:
	// Bit 0: 	0 si TVA normal - 1 if TVA NPR
	// Bit 1:	0 ligne normal - 1 if fixed reduction

	public $total_ht; // Total HT de la ligne toute quantite et incluant la remise ligne
	public $total_tva; // Total TVA de la ligne toute quantite et incluant la remise ligne
	public $total_ttc; // Total TTC de la ligne toute quantite et incluant la remise ligne

	public $date_start;
	public $date_end;

	// From llx_product
	/**
	 * @deprecated
	 * @see $product_ref
	 */
	public $ref;

	/**
	 * Product reference
	 * @var string
	 */
	public $product_ref;

	/**
	 * @deprecated
	 * @see $product_label
	 */
	public $libelle;

	/**
	 *  Product label
	 * @var string
	 */
	public $product_label;

	/**
	 * Custom label
	 * @var string
	 */
	public $label;

	/**
	 * Product description
	 * @var string
	 */
	public $product_desc;

	public $localtax1_tx; // Local tax 1
	public $localtax2_tx; // Local tax 2
	public $localtax1_type; // Local tax 1 type
	public $localtax2_type; // Local tax 2 type
	public $total_localtax1; // Line total local tax 1
	public $total_localtax2; // Line total local tax 2

	public $skip_update_total; // Skip update price total for special lines

	public $ref_fourn;
	public $ref_supplier;

	// Multicurrency
	/**
	 * @var int ID
	 */
	public $fk_multicurrency;

	public $multicurrency_code;
	public $multicurrency_subprice;
	public $multicurrency_total_ht;
	public $multicurrency_total_tva;
	public $multicurrency_total_ttc;

	/**
	 * 	Class line Constructor
	 *
	 * 	@param	DoliDB	$db	Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Retrieve the propal line object
	 *
	 *	@param	int		$rowid		Propal line id
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT pd.rowid, pd.fk_supplier_proposal, pd.fk_parent_line, pd.fk_product, pd.label as custom_label, pd.description, pd.price, pd.qty, pd.tva_tx,';
		$sql .= ' pd.date_start, pd.date_end,';
		$sql .= ' pd.remise, pd.remise_percent, pd.fk_remise_except, pd.subprice,';
		$sql .= ' pd.info_bits, pd.total_ht, pd.total_tva, pd.total_ttc, pd.fk_product_fournisseur_price as fk_fournprice, pd.buy_price_ht as pa_ht, pd.special_code, pd.rang,';
		$sql .= ' pd.localtax1_tx, pd.localtax2_tx, pd.total_localtax1, pd.total_localtax2,';
		$sql .= ' p.ref as product_ref, p.label as product_label, p.description as product_desc,';
		$sql .= ' pd.product_type, pd.ref_fourn as ref_produit_fourn,';
		$sql .= ' pd.fk_multicurrency, pd.multicurrency_code, pd.multicurrency_subprice, pd.multicurrency_total_ht, pd.multicurrency_total_tva, pd.multicurrency_total_ttc, pd.fk_unit';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'supplier_proposaldet as pd';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pd.fk_product = p.rowid';
		$sql .= ' WHERE pd.rowid = '.((int) $rowid);

		$result = $this->db->query($sql);
		if ($result) {
			if ($objp = $this->db->fetch_object($result)) {
				$this->id = $objp->rowid;
				$this->fk_supplier_proposal = $objp->fk_supplier_proposal;
				$this->fk_parent_line = $objp->fk_parent_line;
				$this->label = $objp->custom_label;
				$this->desc = $objp->description;
				$this->qty = $objp->qty;
				$this->subprice = $objp->subprice;
				$this->tva_tx = $objp->tva_tx;
				$this->remise_percent = $objp->remise_percent;
				$this->fk_remise_except = $objp->fk_remise_except;
				$this->fk_product = $objp->fk_product;
				$this->info_bits = $objp->info_bits;
				$this->date_start = $this->db->jdate($objp->date_start);
				$this->date_end = $this->db->jdate($objp->date_end);

				$this->total_ht = $objp->total_ht;
				$this->total_tva = $objp->total_tva;
				$this->total_ttc = $objp->total_ttc;

				$this->fk_fournprice = $objp->fk_fournprice;

				$marginInfos = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
				$this->pa_ht = $marginInfos[0];
				$this->marge_tx = $marginInfos[1];
				$this->marque_tx = $marginInfos[2];

				$this->special_code = $objp->special_code;
				$this->product_type = $objp->product_type;
				$this->rang = $objp->rang;

				$this->ref = $objp->product_ref; // deprecated
				$this->product_ref = $objp->product_ref;
				$this->libelle = $objp->product_label; // deprecated
				$this->product_label = $objp->product_label;
				$this->product_desc = $objp->product_desc;

				$this->ref_fourn = $objp->ref_produit_fourn;

				// Multicurrency
				$this->fk_multicurrency = $objp->fk_multicurrency;
				$this->multicurrency_code = $objp->multicurrency_code;
				$this->multicurrency_subprice = $objp->multicurrency_subprice;
				$this->multicurrency_total_ht = $objp->multicurrency_total_ht;
				$this->multicurrency_total_tva = $objp->multicurrency_total_tva;
				$this->multicurrency_total_ttc = $objp->multicurrency_total_ttc;
				$this->fk_unit = $objp->fk_unit;

				$this->db->free($result);
				return 1;
			}
			return 0;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Insert object line propal in database
	 *
	 *	@param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0)
	{
		global $conf, $langs, $user;

		$error = 0;

		dol_syslog(get_class($this)."::insert rang=".$this->rang);

		// Clean parameters
		if (empty($this->tva_tx)) {
			$this->tva_tx = 0;
		}
		if (empty($this->vat_src_code)) {
			$this->vat_src_code = '';
		}
		if (empty($this->localtax1_tx)) {
			$this->localtax1_tx = 0;
		}
		if (empty($this->localtax2_tx)) {
			$this->localtax2_tx = 0;
		}
		if (empty($this->localtax1_type)) {
			$this->localtax1_type = 0;
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = 0;
		}
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}
		if (empty($this->rang)) {
			$this->rang = 0;
		}
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}
		if (empty($this->info_bits)) {
			$this->info_bits = 0;
		}
		if (empty($this->special_code)) {
			$this->special_code = 0;
		}
		if (empty($this->fk_parent_line)) {
			$this->fk_parent_line = 0;
		}
		if (empty($this->fk_fournprice)) {
			$this->fk_fournprice = 0;
		}
		if (empty($this->fk_unit)) {
			$this->fk_unit = 0;
		}
		if (empty($this->subprice)) {
			$this->subprice = 0;
		}

		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0) {
			$result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
			if ($result < 0) {
				return $result;
			} else {
				$this->pa_ht = $result;
			}
		}

		// Check parameters
		if ($this->product_type < 0) {
			return -1;
		}

		$this->db->begin();

		// Insert line into database
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'supplier_proposaldet';
		$sql .= ' (fk_supplier_proposal, fk_parent_line, label, description, fk_product, product_type,';
		$sql .= ' date_start, date_end,';
		$sql .= ' fk_remise_except, qty, tva_tx, vat_src_code, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
		$sql .= ' subprice, remise_percent, ';
		$sql .= ' info_bits, ';
		$sql .= ' total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, fk_product_fournisseur_price, buy_price_ht, special_code, rang,';
		$sql .= ' ref_fourn,';
		$sql .= ' fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc, fk_unit)';
		$sql .= " VALUES (".$this->fk_supplier_proposal.",";
		$sql .= " ".($this->fk_parent_line > 0 ? ((int) $this->fk_parent_line) : "null").",";
		$sql .= " ".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null").",";
		$sql .= " '".$this->db->escape($this->desc)."',";
		$sql .= " ".($this->fk_product ? ((int) $this->fk_product) : "null").",";
		$sql .= " '".$this->db->escape($this->product_type)."',";
		$sql .= " ".($this->date_start ? "'".$this->db->idate($this->date_start)."'" : "null").",";
		$sql .= " ".($this->date_end ? "'".$this->db->idate($this->date_end)."'" : "null").",";
		$sql .= " ".($this->fk_remise_except ? ((int) $this->fk_remise_except) : "null").",";
		$sql .= " ".price2num($this->qty, 'MS').",";
		$sql .= " ".price2num($this->tva_tx).",";
		$sql .= " '".$this->db->escape($this->vat_src_code)."',";
		$sql .= " ".price2num($this->localtax1_tx).",";
		$sql .= " ".price2num($this->localtax2_tx).",";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= " ".price2num($this->subprice, 'MU') .",";
		$sql .= " ".((float) $this->remise_percent).",";
		$sql .= " ".(isset($this->info_bits) ? ((int) $this->info_bits) : "null").",";
		$sql .= " ".price2num($this->total_ht, 'MT').",";
		$sql .= " ".price2num($this->total_tva, 'MT').",";
		$sql .= " ".price2num($this->total_localtax1, 'MT').",";
		$sql .= " ".price2num($this->total_localtax2, 'MT').",";
		$sql .= " ".price2num($this->total_ttc, 'MT').",";
		$sql .= " ".(!empty($this->fk_fournprice) ? ((int) $this->fk_fournprice) : "null").",";
		$sql .= " ".(isset($this->pa_ht) ? price2num($this->pa_ht, 'MU') : "null").",";
		$sql .= ' '.((int) $this->special_code).',';
		$sql .= ' '.((int) $this->rang).',';
		$sql .= " '".$this->db->escape($this->ref_fourn)."'";
		$sql .= ", ".($this->fk_multicurrency > 0 ? ((int) $this->fk_multicurrency) : 'null');
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".price2num($this->multicurrency_subprice, 'CU');
		$sql .= ", ".price2num($this->multicurrency_total_ht, 'CT');
		$sql .= ", ".price2num($this->multicurrency_total_tva, 'CT');
		$sql .= ", ".price2num($this->multicurrency_total_ttc, 'CT');
		$sql .= ", ".($this->fk_unit ? ((int) $this->fk_unit) : 'null');
		$sql .= ')';

		dol_syslog(get_class($this).'::insert', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'supplier_proposaldet');

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINESUPPLIER_PROPOSAL_INSERT', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Delete line in database
	 *
	 * @param	User	$user		User making the deletion
	 * @return	int  				Return integer <0 if KO, >0 if OK
	 */
	public function delete($user)
	{
		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."supplier_proposaldet";
		$sql .= " WHERE rowid = ".((int) $this->id);

		if ($this->db->query($sql)) {
			// Remove extrafields
			if (!$error) {
				$result = $this->deleteExtraFields();
				if ($result < 0) {
					$error++;
					dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
				}
			}

			// Call trigger
			$result = $this->call_trigger('LINESUPPLIER_PROPOSAL_DELETE', $user);
			if ($result < 0) {
				$this->db->rollback();
				return -1;
			}
			// End call triggers

			$this->db->commit();

			return 1;
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Update propal line object into DB
	 *
	 *	@param 	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	int					Return integer <0 if ko, >0 if ok
	 */
	public function update($notrigger = 0)
	{
		global $conf, $langs, $user;

		$error = 0;

		// Clean parameters
		if (empty($this->tva_tx)) {
			$this->tva_tx = 0;
		}
		if (empty($this->localtax1_tx)) {
			$this->localtax1_tx = 0;
		}
		if (empty($this->localtax2_tx)) {
			$this->localtax2_tx = 0;
		}
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}
		if (empty($this->localtax1_type)) {
			$this->localtax1_type = 0;
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = 0;
		}
		if (empty($this->marque_tx)) {
			$this->marque_tx = 0;
		}
		if (empty($this->marge_tx)) {
			$this->marge_tx = 0;
		}
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}
		if (empty($this->info_bits)) {
			$this->info_bits = 0;
		}
		if (empty($this->special_code)) {
			$this->special_code = 0;
		}
		if (empty($this->fk_parent_line)) {
			$this->fk_parent_line = 0;
		}
		if (empty($this->fk_fournprice)) {
			$this->fk_fournprice = 0;
		}
		if (empty($this->fk_unit)) {
			$this->fk_unit = 0;
		}
		if (empty($this->subprice)) {
			$this->subprice = 0;
		}

		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0) {
			$result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
			if ($result < 0) {
				return $result;
			} else {
				$this->pa_ht = $result;
			}
		}

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposaldet SET";
		$sql .= " description='".$this->db->escape($this->desc)."'";
		$sql .= " , label=".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null");
		$sql .= " , product_type=".((int) $this->product_type);
		$sql .= " , date_start=".($this->date_start ? "'".$this->db->idate($this->date_start)."'" : "null");
		$sql .= " , date_end=".($this->date_end ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= " , tva_tx='".price2num($this->tva_tx)."'";
		$sql .= " , localtax1_tx=".price2num($this->localtax1_tx);
		$sql .= " , localtax2_tx=".price2num($this->localtax2_tx);
		$sql .= " , localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql .= " , localtax2_type='".$this->db->escape($this->localtax2_type)."'";
		$sql .= " , qty='".price2num($this->qty)."'";
		$sql .= " , subprice=".price2num($this->subprice);
		$sql .= " , remise_percent=".price2num($this->remise_percent);
		$sql .= " , info_bits='".$this->db->escape($this->info_bits)."'";
		if (empty($this->skip_update_total)) {
			$sql .= " , total_ht=".price2num($this->total_ht);
			$sql .= " , total_tva=".price2num($this->total_tva);
			$sql .= " , total_ttc=".price2num($this->total_ttc);
			$sql .= " , total_localtax1=".price2num($this->total_localtax1);
			$sql .= " , total_localtax2=".price2num($this->total_localtax2);
		}
		$sql .= " , fk_product_fournisseur_price=".(!empty($this->fk_fournprice) ? "'".$this->db->escape($this->fk_fournprice)."'" : "null");
		$sql .= " , buy_price_ht=".price2num($this->pa_ht);
		$sql .= " , special_code=".((int) $this->special_code);
		$sql .= " , fk_parent_line=".($this->fk_parent_line > 0 ? $this->fk_parent_line : "null");
		if (!empty($this->rang)) {
			$sql .= ", rang=".((int) $this->rang);
		}
		$sql .= " , ref_fourn=".(!empty($this->ref_fourn) ? "'".$this->db->escape($this->ref_fourn)."'" : "null");
		$sql .= " , fk_unit=".($this->fk_unit ? $this->fk_unit : 'null');

		// Multicurrency
		$sql .= " , multicurrency_subprice=".price2num($this->multicurrency_subprice);
		$sql .= " , multicurrency_total_ht=".price2num($this->multicurrency_total_ht);
		$sql .= " , multicurrency_total_tva=".price2num($this->multicurrency_total_tva);
		$sql .= " , multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc);

		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINESUPPLIER_PROPOSAL_MODIFY', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update DB line fields total_xxx
	 *	Used by migration
	 *
	 *	@return		int		Return integer <0 if ko, >0 if ok
	 */
	public function update_total()
	{
		// phpcs:enable
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposaldet SET";
		$sql .= " total_ht=".price2num($this->total_ht, 'MT');
		$sql .= ",total_tva=".price2num($this->total_tva, 'MT');
		$sql .= ",total_ttc=".price2num($this->total_ttc, 'MT');
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("SupplierProposalLine::update_total", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}
}
