<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2011 Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley			<marc@ocebo.com>
 * Copyright (C) 2005-2013 Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani			<acianfa@free.fr>
 * Copyright (C) 2008      Raphael Bertrand			<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2015 Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2010-2018 Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2014 Christophe Battarel  	<christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014      Marcos García            <marcosgdf@gmail.com>
 * Copyright (C) 2016      Ferran Marcet            <fmarcet@2byte.es>
 * Copyright (C) 2018      Nicolas ZABOURI			<info@inovea-conseil.com>
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
 *	\file       htdocs/supplier_proposal/class/supplier_proposal.class.php
 *	\brief      File of class to manage supplier proposals
 */

require_once DOL_DOCUMENT_ROOT .'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT .'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT .'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT .'/multicurrency/class/multicurrency.class.php';

/**
 *	Class to manage price ask supplier
 */
class SupplierProposal extends CommonObject
{
    /**
	 * @var string ID to identify managed object
	 */
	public $element='supplier_proposal';

    /**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='supplier_proposal';

    /**
	 * @var int    Name of subtable line
	 */
	public $table_element_line='supplier_proposaldet';

	/**
	 * @var int Field with ID of parent key if this field has a parent
	 */
    public $fk_element='fk_supplier_proposal';

    public $picto='propal';

    /**
     * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     * @var int
     */
    public $ismultientitymanaged = 1;

    /**
     * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
     * @var integer
     */
    public $restrictiononfksoc = 1;

    /**
     * {@inheritdoc}
     */
    protected $table_ref_field = 'ref';

    public $socid;		// Id client

	/**
	 * @deprecated
	 * @see user_author_id
	 */
    public $author;

    public $ref_fourn;					//Reference saisie lors de l'ajout d'une ligne à la demande
    public $ref_supplier;				//Reference saisie lors de l'ajout d'une ligne à la demande
    public $statut;					// 0 (draft), 1 (validated), 2 (signed), 3 (not signed), 4 (processed/billed)
    public $date;						// Date of proposal
    public $date_livraison;

	/**
	 * @deprecated
	 * @see date_creation
	 */
	public $datec;

	/**
	 * Creation date
	 * @var int
	 */
	public $date_creation;

	/**
	 * @deprecated
	 * @see date_validation
	 */
	public $datev;

	/**
	 * Validation date
	 * @var int
	 */
	public $date_validation;


    public $user_author_id;
    public $user_valid_id;
    public $user_close_id;

	/**
	 * @deprecated
	 * @see price_ht
	 */
    public $price;

	/**
	 * @deprecated
	 * @see total_tva
	 */
    public $tva;

	/**
	 * @deprecated
	 * @see total_ttc
	 */
    public $total;

    public $cond_reglement_code;
    public $mode_reglement_code;
    public $remise = 0;
    public $remise_percent = 0;
    public $remise_absolue = 0;

    public $products=array();
    public $extraparams=array();

    public $lines = array();
    public $line;

    public $labelstatut=array();
    public $labelstatut_short=array();

    public $nbtodo;
    public $nbtodolate;

    public $specimen;

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
    function __construct($db, $socid="", $supplier_proposalid=0)
    {
        global $conf,$langs;

        $this->db = $db;

        $this->socid = $socid;
        $this->id = $supplier_proposalid;

        $this->products = array();
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     * 	Add line into array products
     *  $this->client doit etre charge
     *
     * 	@param  int		$idproduct       	Product Id to add
     * 	@param  int		$qty             	Quantity
     * 	@param  int		$remise_percent  	Discount effected on Product
     *  @return	int							<0 if KO, >0 if OK
     *
     *	TODO	Remplacer les appels a cette fonction par generation objet Ligne
     *			insere dans tableau $this->products
     */
    function add_product($idproduct, $qty, $remise_percent=0)
    {
        // phpcs:enable
        global $conf, $mysoc;

        if (! $qty) $qty = 1;

        dol_syslog(get_class($this)."::add_product $idproduct, $qty, $remise_percent");
        if ($idproduct > 0)
        {
            $prod=new Product($this->db);
            $prod->fetch($idproduct);

            $productdesc = $prod->description;

            $tva_tx = get_default_tva($mysoc,$this->thirdparty,$prod->id);
            $tva_npr = get_default_npr($mysoc,$this->thirdparty,$prod->id);
            if (empty($tva_tx)) $tva_npr=0;
            $localtax1_tx = get_localtax($tva_tx,1,$mysoc,$this->thirdparty,$tva_npr);
            $localtax2_tx = get_localtax($tva_tx,2,$mysoc,$this->thirdparty,$tva_npr);

            // multiprix
            if($conf->global->PRODUIT_MULTIPRICES && $this->thirdparty->price_level)
            {
                $price = $prod->multiprices[$this->thirdparty->price_level];
            }
            else
            {
                $price = $prod->price;
            }

            $line = new SupplierProposalLine($this->db);

            $line->fk_product=$idproduct;
            $line->desc=$productdesc;
            $line->qty=$qty;
            $line->subprice=$price;
            $line->remise_percent=$remise_percent;
            $line->tva_tx=$tva_tx;

            $this->lines[]=$line;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Adding line of fixed discount in the proposal in DB
     *
     *	@param     int		$idremise			Id of fixed discount
     *  @return    int          				>0 if OK, <0 if KO
     */
    function insert_discount($idremise)
    {
        // phpcs:enable
        global $langs;

        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
        include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

        $this->db->begin();

        $remise=new DiscountAbsolute($this->db);
        $result=$remise->fetch($idremise);

        if ($result > 0)
        {
            if ($remise->fk_facture)	// Protection against multiple submission
            {
                $this->error=$langs->trans("ErrorDiscountAlreadyUsed");
                $this->db->rollback();
                return -5;
            }

            $supplier_proposalligne=new SupplierProposalLine($this->db);
            $supplier_proposalligne->fk_supplier_proposal=$this->id;
            $supplier_proposalligne->fk_remise_except=$remise->id;
            $supplier_proposalligne->desc=$remise->description;   	// Description ligne
            $supplier_proposalligne->tva_tx=$remise->tva_tx;
            $supplier_proposalligne->subprice=-$remise->amount_ht;
            $supplier_proposalligne->fk_product=0;					// Id produit predefini
            $supplier_proposalligne->qty=1;
            $supplier_proposalligne->remise=0;
            $supplier_proposalligne->remise_percent=0;
            $supplier_proposalligne->rang=-1;
            $supplier_proposalligne->info_bits=2;

            // TODO deprecated
            $supplier_proposalligne->price=-$remise->amount_ht;

            $supplier_proposalligne->total_ht  = -$remise->amount_ht;
            $supplier_proposalligne->total_tva = -$remise->amount_tva;
            $supplier_proposalligne->total_ttc = -$remise->amount_ttc;

            $result=$supplier_proposalligne->insert();
            if ($result > 0)
            {
                $result=$this->update_price(1);
                if ($result > 0)
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
            else
            {
                $this->error=$supplier_proposalligne->error;
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            return -2;
        }
    }

    /**
     *    	Add a proposal line into database (linked to product/service or not)
     * 		Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
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
     *		@param    	int			$fk_product      	Id du produit/service predefini
     * 		@param    	double		$remise_percent  	Pourcentage de remise de la ligne
     * 		@param    	string		$price_base_type	HT or TTC
     * 		@param    	double		$pu_ttc             Prix unitaire TTC
     * 		@param    	int			$info_bits			Bits de type de lignes
     *      @param      int			$type               Type of line (product, service)
     *      @param      int			$rang               Position of line
     *      @param		int			$special_code		Special code (also used by externals modules!)
     *      @param		int			$fk_parent_line		Id of parent line
     *      @param		int			$fk_fournprice		Id supplier price
     *      @param		int			$pa_ht				Buying price without tax
     *      @param		string		$label				???
     *      @param		array		$array_option		extrafields array
	 * 		@param		string		$ref_supplier			Supplier price reference
	 * 		@param		int			$fk_unit			Id of the unit to use.
	 * 		@param		string		$origin				'order', 'supplier_proposal', ...
	 * 		@param		int			$origin_id			Id of origin line
     * 		@param		double		$pu_ht_devise		Amount in currency
     *    	@return    	int         	    			>0 if OK, <0 if KO
     *
     *    	@see       	add_product
     */
    function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1=0, $txlocaltax2=0, $fk_product=0, $remise_percent=0, $price_base_type='HT', $pu_ttc=0, $info_bits=0, $type=0, $rang=-1, $special_code=0, $fk_parent_line=0, $fk_fournprice=0, $pa_ht=0, $label='',$array_option=0, $ref_supplier='', $fk_unit='', $origin='', $origin_id=0, $pu_ht_devise=0)
    {
    	global $mysoc, $conf;

        dol_syslog(get_class($this)."::addline supplier_proposalid=$this->id, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_except=$remise_percent, price_base_type=$price_base_type, pu_ttc=$pu_ttc, info_bits=$info_bits, type=$type");
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        // Clean parameters
        if (empty($remise_percent)) $remise_percent=0;
        if (empty($qty)) $qty=0;
        if (empty($info_bits)) $info_bits=0;
        if (empty($rang)) $rang=0;
        if (empty($fk_parent_line) || $fk_parent_line < 0) $fk_parent_line=0;
        if (empty($pu_ht)) $pu_ht=0;

        $remise_percent=price2num($remise_percent);
        $qty=price2num($qty);
        $pu_ht=price2num($pu_ht);
        $pu_ttc=price2num($pu_ttc);
        $txtva=price2num($txtva);
        $txlocaltax1=price2num($txlocaltax1);
        $txlocaltax2=price2num($txlocaltax2);
    		$pa_ht=price2num($pa_ht);
        if ($price_base_type=='HT')
        {
            $pu=$pu_ht;
        }
        else
        {
            $pu=$pu_ttc;
        }

        // Check parameters
        if ($type < 0) return -1;

        if ($this->statut == self::STATUS_DRAFT)
        {
            $this->db->begin();

            if ($fk_product > 0)
            {
            	if (! empty($conf->global->SUPPLIER_PROPOSAL_WITH_PREDEFINED_PRICES_ONLY))
            	{
            		// Check quantity is enough
            		dol_syslog(get_class($this)."::addline we check supplier prices fk_product=".$fk_product." fk_fournprice=".$fk_fournprice." qty=".$qty." ref_supplier=".$ref_supplier);
            		$prod = new Product($this->db, $fk_product);
            		if ($prod->fetch($fk_product) > 0)
            		{
            			$product_type = $prod->type;
            			$label = $prod->label;
            			$fk_prod_fourn_price = $fk_fournprice;

            			// We use 'none' instead of $ref_supplier, because fourn_ref may not exists anymore. So we will take the first supplier price ok.
            			// If we want a dedicated supplier price, we must provide $fk_prod_fourn_price.
            			$result=$prod->get_buyprice($fk_prod_fourn_price, $qty, $fk_product, 'none', ($this->fk_soc?$this->fk_soc:$this->socid));   // Search on couple $fk_prod_fourn_price/$qty first, then on triplet $qty/$fk_product/$ref_supplier/$this->fk_soc
            			if ($result > 0)
            			{
            				$pu = $prod->fourn_pu;       // Unit price supplier price set by get_buyprice
            				$ref_supplier = $prod->ref_supplier;   // Ref supplier price set by get_buyprice
            				// is remise percent not keyed but present for the product we add it
            				if ($remise_percent == 0 && $prod->remise_percent !=0)
            					$remise_percent =$prod->remise_percent;
            			}
            			if ($result == 0)                   // If result == 0, we failed to found the supplier reference price
            			{
            				$langs->load("errors");
            				$this->error = "Ref " . $prod->ref . " " . $langs->trans("ErrorQtyTooLowForThisSupplier");
            				$this->db->rollback();
            				dol_syslog(get_class($this)."::addline we did not found supplier price, so we can't guess unit price");
            				//$pu    = $prod->fourn_pu;     // We do not overwrite unit price
            				//$ref   = $prod->ref_fourn;    // We do not overwrite ref supplier price
            				return -1;
            			}
            			if ($result == -1)
            			{
            				$langs->load("errors");
            				$this->error = "Ref " . $prod->ref . " " . $langs->trans("ErrorQtyTooLowForThisSupplier");
            				$this->db->rollback();
            				dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_DEBUG);
            				return -1;
            			}
            			if ($result < -1)
            			{
            				$this->error=$prod->error;
            				$this->db->rollback();
            				dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_ERR);
            				return -1;
            			}
            		}
            		else
            		{
            			$this->error=$prod->error;
            			$this->db->rollback();
            			return -1;
            		}
            	}
            }
            else
            {
            	$product_type = $type;
            }

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

            $localtaxes_type=getLocalTaxesFromRate($txtva,0,$this->thirdparty,$mysoc);
            $txtva = preg_replace('/\s*\(.*\)/','',$txtva);  // Remove code into vatrate.

            if ($conf->multicurrency->enabled && $pu_ht_devise > 0) {
                $pu = 0;
            }

            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
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
            $rangtouse = $rang;
            if ($rangtouse == -1)
            {
                $rangmax = $this->line_max($fk_parent_line);
                $rangtouse = $rangmax + 1;
            }

            // TODO A virer
            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $price = $pu;
            $remise = 0;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }

            // Insert line
            $this->line=new SupplierProposalLine($this->db);

            $this->line->fk_supplier_proposal=$this->id;
            $this->line->label=$label;
            $this->line->desc=$desc;
            $this->line->qty=$qty;
            $this->line->tva_tx=$txtva;
            $this->line->localtax1_tx=($total_localtax1?$localtaxes_type[1]:0);
            $this->line->localtax2_tx=($total_localtax2?$localtaxes_type[3]:0);
            $this->line->localtax1_type = $localtaxes_type[0];
			$this->line->localtax2_type = $localtaxes_type[2];
            $this->line->fk_product=$fk_product;
            $this->line->remise_percent=$remise_percent;
            $this->line->subprice=$pu_ht;
            $this->line->rang=$rangtouse;
            $this->line->info_bits=$info_bits;
            $this->line->total_ht=$total_ht;
            $this->line->total_tva=$total_tva;
            $this->line->total_localtax1=$total_localtax1;
            $this->line->total_localtax2=$total_localtax2;
            $this->line->total_ttc=$total_ttc;
            $this->line->product_type=$type;
            $this->line->special_code=$special_code;
            $this->line->fk_parent_line=$fk_parent_line;
            $this->line->fk_unit=$fk_unit;
            $this->line->origin=$origin;
            $this->line->origin_id=$origin_id;
			$this->line->ref_fourn = $this->db->escape($ref_supplier);

			// infos marge
			if (!empty($fk_product) && empty($fk_fournprice) && empty($pa_ht)) {
			    // by external module, take lowest buying price
			    include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
			    $productFournisseur = new ProductFournisseur($this->db);
			    $productFournisseur->find_min_price_product_fournisseur($fk_product);
			    $this->line->fk_fournprice = $productFournisseur->product_fourn_price_id;
			} else {
			    $this->line->fk_fournprice = $fk_fournprice;
			}
			$this->line->pa_ht = $pa_ht;

			// Multicurrency
			$this->line->fk_multicurrency			= $this->fk_multicurrency;
			$this->line->multicurrency_code			= $this->multicurrency_code;
            $this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
            $this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
            $this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

            // Mise en option de la ligne
            if (empty($qty) && empty($special_code)) $this->line->special_code=3;

            // TODO deprecated
            $this->line->price=$price;
            $this->line->remise=$remise;

            if (is_array($array_option) && count($array_option)>0) {
            	$this->line->array_options=$array_option;
            }

            $result=$this->line->insert();
            if ($result > 0)
            {
                // Reorder if child line
                if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

                // Mise a jour informations denormalisees au niveau de la propale meme
                $result=$this->update_price(1,'auto',0,$this->thirdparty);	// This method is designed to add line from user input so total calculation must be done using 'auto' mode.
                if ($result > 0)
                {
                    $this->db->commit();
                    return $this->line->rowid;
                }
                else
                {
                    $this->error=$this->db->error();
                    $this->db->rollback();
                    return -1;
                }
            }
            else
            {
                $this->error=$this->line->error;
                $this->db->rollback();
                return -2;
            }
        }
    }


    /**
     *  Update a proposal line
     *
     *  @param      int			$rowid           	Id de la ligne
     *  @param      double		$pu		     	  	Prix unitaire (HT ou TTC selon price_base_type)
     *  @param      double		$qty            	Quantity
     *  @param      double		$remise_percent  	Remise effectuee sur le produit
     *  @param      double		$txtva	          	Taux de TVA
     * 	@param	  	double		$txlocaltax1		Local tax 1 rate
     *  @param	  	double		$txlocaltax2		Local tax 2 rate
     *  @param      string		$desc            	Description
     *	@param	  	double		$price_base_type	HT ou TTC
     *	@param      int			$info_bits        	Miscellaneous informations
     *	@param		int			$special_code		Special code (also used by externals modules!)
     * 	@param		int			$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
     * 	@param		int			$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
     *  @param		int			$fk_fournprice		Id of origin supplier price
     *  @param		int			$pa_ht				Price (without tax) of product when it was bought
     *  @param		string		$label				???
     *  @param		int			$type				0/1=Product/service
	 *  @param		array		$array_option		extrafields array
	 * 	@param		string		$ref_supplier			Supplier price reference
	 *	@param		int			$fk_unit			Id of the unit to use.
     *  @return     int     		        		0 if OK, <0 if KO
     */
	function updateline($rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1=0, $txlocaltax2=0, $desc='', $price_base_type='HT', $info_bits=0, $special_code=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=0, $pa_ht=0, $label='', $type=0, $array_option=0, $ref_supplier='', $fk_unit='')
    {
        global $conf,$user,$langs, $mysoc;

        dol_syslog(get_class($this)."::updateLine $rowid, $pu, $qty, $remise_percent, $txtva, $desc, $price_base_type, $info_bits");
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        // Clean parameters
        $remise_percent=price2num($remise_percent);
        $qty=price2num($qty);
        $pu = price2num($pu);
        $txtva = price2num($txtva);
        $txlocaltax1=price2num($txlocaltax1);
        $txlocaltax2=price2num($txlocaltax2);
    	$pa_ht=price2num($pa_ht);
        if (empty($qty) && empty($special_code)) $special_code=3;    // Set option tag
        if (! empty($qty) && $special_code == 3) $special_code=0;    // Remove option tag

        if ($this->statut == 0)
        {
            $this->db->begin();

            // Calcul du total TTC et de la TVA pour la ligne a partir de
            // qty, pu, remise_percent et txtva
            // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
            // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

            $localtaxes_type=getLocalTaxesFromRate($txtva,0,$this->thirdparty,$mysoc);
            $txtva = preg_replace('/\s*\(.*\)/','',$txtva);  // Remove code into vatrate.

            $tabprice=calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx);
            $total_ht  = $tabprice[0];
            $total_tva = $tabprice[1];
            $total_ttc = $tabprice[2];
            $total_localtax1 = $tabprice[9];
            $total_localtax2 = $tabprice[10];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
            $multicurrency_total_tva = $tabprice[17];
            $multicurrency_total_ttc = $tabprice[18];

            // Anciens indicateurs: $price, $remise (a ne plus utiliser)
            $price = $pu;
            if ($remise_percent > 0)
            {
                $remise = round(($pu * $remise_percent / 100), 2);
                $price = $pu - $remise;
            }

            // Update line
            $this->line=new SupplierProposalLine($this->db);

            // Stock previous line records
            $staticline=new SupplierProposalLine($this->db);
            $staticline->fetch($rowid);
            $this->line->oldline = $staticline;

            // Reorder if fk_parent_line change
            if (! empty($fk_parent_line) && ! empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line)
            {
                $rangmax = $this->line_max($fk_parent_line);
                $this->line->rang = $rangmax + 1;
            }

            $this->line->rowid				= $rowid;
            $this->line->label				= $label;
            $this->line->desc				= $desc;
            $this->line->qty				= $qty;
            $this->line->product_type			= $type;
            $this->line->tva_tx				= $txtva;
            $this->line->localtax1_tx		= $txlocaltax1;
            $this->line->localtax2_tx		= $txlocaltax2;
			$this->line->localtax1_type		= $localtaxes_type[0];
			$this->line->localtax2_type		= $localtaxes_type[2];
            $this->line->remise_percent		= $remise_percent;
            $this->line->subprice			= $pu;
            $this->line->info_bits			= $info_bits;
            $this->line->total_ht			= $total_ht;
            $this->line->total_tva			= $total_tva;
            $this->line->total_localtax1	= $total_localtax1;
            $this->line->total_localtax2	= $total_localtax2;
            $this->line->total_ttc			= $total_ttc;
            $this->line->special_code		= $special_code;
            $this->line->fk_parent_line		= $fk_parent_line;
            $this->line->skip_update_total	= $skip_update_total;
            $this->line->ref_fourn			= $ref_supplier;
			$this->line->fk_unit			= $fk_unit;

            // infos marge
            if (!empty($fk_product) && empty($fk_fournprice) && empty($pa_ht)) {
                // by external module, take lowest buying price
                include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
			    $productFournisseur = new ProductFournisseur($this->db);
			    $productFournisseur->find_min_price_product_fournisseur($fk_product);
			    $this->line->fk_fournprice = $productFournisseur->product_fourn_price_id;
			} else {
			    $this->line->fk_fournprice = $fk_fournprice;
			}
            $this->line->pa_ht = $pa_ht;

            // TODO deprecated
            $this->line->price=$price;
            $this->line->remise=$remise;

            if (is_array($array_option) && count($array_option)>0) {
            	$this->line->array_options=$array_option;
            }

			// Multicurrency
			$this->line->multicurrency_subprice		= price2num($pu * $this->multicurrency_tx);
			$this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
            $this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
            $this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

            $result=$this->line->update();
            if ($result > 0)
            {
                // Reorder if child line
                if (! empty($fk_parent_line)) $this->line_order(true,'DESC');

                $this->update_price(1);

                $this->fk_supplier_proposal = $this->id;
                $this->rowid = $rowid;

                $this->db->commit();
                return $result;
            }
            else
            {
                $this->error=$this->db->error();
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
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
    function deleteline($lineid)
    {
        if ($this->statut == 0)
        {
            $line=new SupplierProposalLine($this->db);

            // For triggers
            $line->fetch($lineid);

            if ($line->delete() > 0)
            {
                $this->update_price(1);

                return 1;
            }
            else
            {
                return -1;
            }
        }
        else
        {
            return -2;
        }
    }


    /**
     *  Create commercial proposal into database
     * 	this->ref can be set or empty. If empty, we will use "(PROVid)"
     *
     * 	@param		User	$user		User that create
     * 	@param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
     *  @return     int     			<0 if KO, >=0 if OK
     */
    function create($user, $notrigger=0)
    {
        global $langs,$conf,$mysoc,$hookmanager;
        $error=0;

        $now=dol_now();

        dol_syslog(get_class($this)."::create");

        // Check parameters
        $result=$this->fetch_thirdparty();
        if ($result < 0)
        {
            $this->error="Failed to fetch company";
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
            return -3;
        }

        // Check parameters
		if (! empty($this->ref))	// We check that ref is not already used
		{
			$result=self::isExistingObject($this->element, 0, $this->ref);	// Check ref is not yet used
			if ($result > 0)
			{
				$this->error='ErrorRefAlreadyExists';
				dol_syslog(get_class($this)."::create ".$this->error,LOG_WARNING);
				$this->db->rollback();
				return -1;
			}
		}

		// Multicurrency
		if (!empty($this->multicurrency_code)) list($this->fk_multicurrency,$this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code);
		if (empty($this->fk_multicurrency))
		{
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

        $this->db->begin();

        // Insert into database
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."supplier_proposal (";
        $sql.= "fk_soc";
        $sql.= ", price";
        $sql.= ", remise";
        $sql.= ", remise_percent";
        $sql.= ", remise_absolue";
        $sql.= ", tva";
        $sql.= ", total";
        $sql.= ", datec";
        $sql.= ", ref";
        $sql.= ", fk_user_author";
        $sql.= ", note_private";
        $sql.= ", note_public";
        $sql.= ", model_pdf";
        $sql.= ", fk_cond_reglement";
        $sql.= ", fk_mode_reglement";
        $sql.= ", fk_account";
        $sql.= ", date_livraison";
        $sql.= ", fk_shipping_method";
        $sql.= ", fk_projet";
        $sql.= ", entity";
        $sql.= ", fk_multicurrency";
        $sql.= ", multicurrency_code";
        $sql.= ", multicurrency_tx";
        $sql.= ") ";
        $sql.= " VALUES (";
        $sql.= $this->socid;
        $sql.= ", 0";
        $sql.= ", ".$this->remise;
        $sql.= ", ".($this->remise_percent?$this->db->escape($this->remise_percent):'null');
        $sql.= ", ".($this->remise_absolue?$this->db->escape($this->remise_absolue):'null');
        $sql.= ", 0";
        $sql.= ", 0";
        $sql.= ", '".$this->db->idate($now)."'";
        $sql.= ", '(PROV)'";
        $sql.= ", ".($user->id > 0 ? "'".$user->id."'":"null");
        $sql.= ", '".$this->db->escape($this->note_private)."'";
        $sql.= ", '".$this->db->escape($this->note_public)."'";
        $sql.= ", '".$this->db->escape($this->modelpdf)."'";
        $sql.= ", ".($this->cond_reglement_id > 0 ? $this->cond_reglement_id : 'NULL');
        $sql.= ", ".($this->mode_reglement_id > 0 ? $this->mode_reglement_id : 'NULL');
        $sql.= ", ".($this->fk_account>0?$this->fk_account:'NULL');
        $sql.= ", ".($this->date_livraison!=''?"'".$this->db->idate($this->date_livraison)."'":"null");
        $sql.= ", ".($this->shipping_method_id>0?$this->shipping_method_id:'NULL');
        $sql.= ", ".($this->fk_project?$this->fk_project:"null");
        $sql.= ", ".$conf->entity;
		$sql.= ", ".(int) $this->fk_multicurrency;
		$sql.= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql.= ", ".(double) $this->multicurrency_tx;
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."supplier_proposal");

            if ($this->id)
            {
                $this->ref='(PROV'.$this->id.')';
                $sql = 'UPDATE '.MAIN_DB_PREFIX."supplier_proposal SET ref='".$this->db->escape($this->ref)."' WHERE rowid=".$this->id;

                dol_syslog(get_class($this)."::create", LOG_DEBUG);
                $resql=$this->db->query($sql);
                if (! $resql) $error++;

                if (! empty($this->linkedObjectsIds) && empty($this->linked_objects))	// To use new linkedObjectsIds instead of old linked_objects
                {
                	$this->linked_objects = $this->linkedObjectsIds;	// TODO Replace linked_objects with linkedObjectsIds
                }

                // Add object linked
                if (! $error && $this->id && is_array($this->linked_objects) && ! empty($this->linked_objects))
                {
                	foreach($this->linked_objects as $origin => $tmp_origin_id)
                	{
                		if (is_array($tmp_origin_id))       // New behaviour, if linked_object can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
                		{
                			foreach($tmp_origin_id as $origin_id)
                			{
                				$ret = $this->add_object_linked($origin, $origin_id);
                				if (! $ret)
                				{
                					dol_print_error($this->db);
                					$error++;
                				}
                			}
                		}
                	}
                }

                // Add linked object (deprecated, use ->linkedObjectsIds instead)
                if (! $error && $this->origin && $this->origin_id)
                {
                	$ret = $this->add_object_linked();
                	if (! $ret)	dol_print_error($this->db);
                }

                /*
                 *  Insertion du detail des produits dans la base
                 */
                if (! $error)
                {
                    $fk_parent_line=0;
                    $num=count($this->lines);

                    for ($i=0;$i<$num;$i++)
                    {
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
							$this->lines[$i]->label,
							$this->lines[$i]->array_options,
							$this->lines[$i]->ref_fourn,
							$this->lines[$i]->fk_unit,
							'supplier_proposal',
							$this->lines[$i]->rowid
						);

                        if ($result < 0)
                        {
                            $error++;
                            $this->error=$this->db->error;
                            dol_print_error($this->db);
                            break;
                        }
                        // Defined the new fk_parent_line
                        if ($result > 0 && $this->lines[$i]->product_type == 9) {
                            $fk_parent_line = $result;
                        }
                    }
                }

                if (! $error)
                {
                    // Mise a jour infos denormalisees
                    $resql=$this->update_price(1);
                    if ($resql)
                    {
                    	$action='update';

                    	// Actions on extra fields
                   		if (! $error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))
                   		{
                   			$result=$this->insertExtraFields();
                   			if ($result < 0)
                   			{
                   				$error++;
                   			}
                    	}

                        if (! $error && ! $notrigger)
                        {
                            // Call trigger
                            $result=$this->call_trigger('PROPAL_SUPPLIER_CREATE',$user);
                            if ($result < 0) { $error++; }
                            // End call triggers
                        }
                    }
                    else
					{
                        $this->error=$this->db->lasterror();
                        $error++;
                    }
                }
            }
            else
			{
                $this->error=$this->db->lasterror();
                $error++;
            }

            if (! $error)
            {
                $this->db->commit();
                dol_syslog(get_class($this)."::create done id=".$this->id);
                return $this->id;
            }
            else
            {
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Insert into DB a supplier_proposal object completely defined by its data members (ex, results from copy).
     *
     *	@param 		User	$user	User that create
     *	@return    	int				Id of the new object if ok, <0 if ko
     *	@see       	create
     */
    function create_from($user)
    {
        // phpcs:enable
        $this->products=$this->lines;

        return $this->create($user);
    }

    /**
     *		Load an object from its id and create a new one in database
     *
     *		@param		int				$socid			Id of thirdparty
     * 	 	@return		int								New id of clone
     */
    function createFromClone($socid=0)
    {
        global $user,$langs,$conf,$hookmanager;

        $error=0;
        $now=dol_now();

        $this->db->begin();

		// get extrafields so they will be clone
		foreach($this->lines as $line)
			$line->fetch_optionals();

		// Load source object
		$objFrom = clone $this;

        $objsoc=new Societe($this->db);

        // Change socid if needed
        if (! empty($socid) && $socid != $this->socid)
        {
            if ($objsoc->fetch($socid) > 0)
            {
                $this->socid 				= $objsoc->id;
                $this->cond_reglement_id	= (! empty($objsoc->cond_reglement_id) ? $objsoc->cond_reglement_id : 0);
                $this->mode_reglement_id	= (! empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
                $this->fk_project			= '';
            }

            // TODO Change product price if multi-prices
        }
        else
        {
            $objsoc->fetch($this->socid);
        }

        $this->id=0;
        $this->statut=0;

        if (empty($conf->global->SUPPLIER_PROPOSAL_ADDON) || ! is_readable(DOL_DOCUMENT_ROOT ."/core/modules/supplier_proposal/".$conf->global->SUPPLIER_PROPOSAL_ADDON.".php"))
        {
            $this->error='ErrorSetupNotComplete';
            return -1;
        }

        // Clear fields
        $this->user_author	= $user->id;
        $this->user_valid	= '';
        $this->date			= $now;

        // Set ref
        require_once DOL_DOCUMENT_ROOT ."/core/modules/supplier_proposal/".$conf->global->SUPPLIER_PROPOSAL_ADDON.'.php';
        $obj = $conf->global->SUPPLIER_PROPOSAL_ADDON;
        $modSupplierProposal = new $obj;
        $this->ref = $modSupplierProposal->getNextValue($objsoc,$this);

        // Create clone
        $result=$this->create($user);
        if ($result < 0) $error++;

        if (! $error)
        {
            // Hook of thirdparty module
            if (is_object($hookmanager))
            {
                $parameters=array('objFrom'=>$objFrom);
                $action='';
                $reshook=$hookmanager->executeHooks('createFrom',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0) $error++;
            }
        }

        // End
        if (! $error)
        {
            $this->db->commit();
            return $this->id;
        }
        else
        {
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
    function fetch($rowid,$ref='')
    {
        global $conf;

        $sql = "SELECT p.rowid, p.entity, p.ref, p.remise, p.remise_percent, p.remise_absolue, p.fk_soc";
        $sql.= ", p.total, p.tva, p.localtax1, p.localtax2, p.total_ht";
        $sql.= ", p.datec";
        $sql.= ", p.date_valid as datev";
        $sql.= ", p.date_livraison as date_livraison";
        $sql.= ", p.model_pdf, p.extraparams";
        $sql.= ", p.note_private, p.note_public";
        $sql.= ", p.fk_projet, p.fk_statut";
        $sql.= ", p.fk_user_author, p.fk_user_valid, p.fk_user_cloture";
        $sql.= ", p.fk_cond_reglement";
        $sql.= ", p.fk_mode_reglement";
        $sql.= ', p.fk_account';
        $sql.= ", p.fk_shipping_method";
		$sql.= ", p.fk_multicurrency, p.multicurrency_code, p.multicurrency_tx, p.multicurrency_total_ht, p.multicurrency_total_tva, p.multicurrency_total_ttc";
        $sql.= ", c.label as statut_label";
        $sql.= ", cr.code as cond_reglement_code, cr.libelle as cond_reglement, cr.libelle_facture as cond_reglement_libelle_doc";
        $sql.= ", cp.code as mode_reglement_code, cp.libelle as mode_reglement";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_propalst as c, ".MAIN_DB_PREFIX."supplier_proposal as p";
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as cp ON p.fk_mode_reglement = cp.id';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as cr ON p.fk_cond_reglement = cr.rowid';
        $sql.= " WHERE p.fk_statut = c.id";
        $sql.= " AND p.entity IN (".getEntity('supplier_proposal').")";
        if ($ref) $sql.= " AND p.ref='".$ref."'";
        else $sql.= " AND p.rowid=".$rowid;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id                   = $obj->rowid;
                $this->entity               = $obj->entity;

                $this->ref                  = $obj->ref;
                $this->remise               = $obj->remise;
                $this->remise_percent       = $obj->remise_percent;
                $this->remise_absolue       = $obj->remise_absolue;
                $this->total                = $obj->total; // TODO deprecated
                $this->total_ht             = $obj->total_ht;
                $this->total_tva            = $obj->tva;
                $this->total_localtax1		= $obj->localtax1;
                $this->total_localtax2		= $obj->localtax2;
                $this->total_ttc            = $obj->total;
                $this->socid                = $obj->fk_soc;
                $this->fk_project           = $obj->fk_projet;
                $this->modelpdf             = $obj->model_pdf;
                $this->note                 = $obj->note_private; // TODO deprecated
                $this->note_private         = $obj->note_private;
                $this->note_public          = $obj->note_public;
                $this->statut               = (int) $obj->fk_statut;
                $this->statut_libelle       = $obj->statut_label;
                $this->datec                = $this->db->jdate($obj->datec); // TODO deprecated
                $this->datev                = $this->db->jdate($obj->datev); // TODO deprecated
                $this->date_creation		= $this->db->jdate($obj->datec); //Creation date
                $this->date_validation		= $this->db->jdate($obj->datev); //Validation date
                $this->date_livraison       = $this->db->jdate($obj->date_livraison);
                $this->shipping_method_id   = ($obj->fk_shipping_method>0)?$obj->fk_shipping_method:null;

                $this->mode_reglement_id    = $obj->fk_mode_reglement;
                $this->mode_reglement_code  = $obj->mode_reglement_code;
                $this->mode_reglement       = $obj->mode_reglement;
                $this->fk_account           = ($obj->fk_account>0)?$obj->fk_account:null;
                $this->cond_reglement_id    = $obj->fk_cond_reglement;
                $this->cond_reglement_code  = $obj->cond_reglement_code;
                $this->cond_reglement       = $obj->cond_reglement;
                $this->cond_reglement_doc   = $obj->cond_reglement_libelle_doc;

                $this->extraparams			= (array) json_decode($obj->extraparams, true);

                $this->user_author_id = $obj->fk_user_author;
                $this->user_valid_id  = $obj->fk_user_valid;
                $this->user_close_id  = $obj->fk_user_cloture;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code 		= $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht 	= $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

                if ($obj->fk_statut == 0)
                {
                    $this->brouillon = 1;
                }

                // Retreive all extrafield
                // fetch optionals attributes and labels
                $this->fetch_optionals();

                $this->db->free($resql);

                $this->lines = array();

                // Lines of supplier proposals
                $sql = "SELECT d.rowid, d.fk_supplier_proposal, d.fk_parent_line, d.label as custom_label, d.description, d.price, d.tva_tx, d.localtax1_tx, d.localtax2_tx, d.qty, d.fk_remise_except, d.remise_percent, d.subprice, d.fk_product,";
				$sql.= " d.info_bits, d.total_ht, d.total_tva, d.total_localtax1, d.total_localtax2, d.total_ttc, d.fk_product_fournisseur_price as fk_fournprice, d.buy_price_ht as pa_ht, d.special_code, d.rang, d.product_type,";
                $sql.= ' p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label as product_label,';
                $sql.= ' d.ref_fourn as ref_produit_fourn,';
				$sql.= ' d.fk_multicurrency, d.multicurrency_code, d.multicurrency_subprice, d.multicurrency_total_ht, d.multicurrency_total_tva, d.multicurrency_total_ttc, d.fk_unit';
                $sql.= " FROM ".MAIN_DB_PREFIX."supplier_proposaldet as d";
                $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON d.fk_product = p.rowid";
                $sql.= " WHERE d.fk_supplier_proposal = ".$this->id;
                $sql.= " ORDER by d.rang";

                $result = $this->db->query($sql);
                if ($result)
                {
                    $num = $this->db->num_rows($result);
                    $i = 0;

                    while ($i < $num)
                    {
                        $objp                   = $this->db->fetch_object($result);

                        $line                   = new SupplierProposalLine($this->db);

                        $line->rowid			= $objp->rowid; // deprecated
                        $line->id				= $objp->rowid;
                        $line->fk_supplier_proposal		= $objp->fk_supplier_proposal;
                        $line->fk_parent_line	= $objp->fk_parent_line;
                        $line->product_type     = $objp->product_type;
                        $line->label            = $objp->custom_label;
                        $line->desc             = $objp->description;  // Description ligne
                        $line->qty              = $objp->qty;
                        $line->tva_tx           = $objp->tva_tx;
                        $line->localtax1_tx		= $objp->localtax1_tx;
                        $line->localtax2_tx		= $objp->localtax2_tx;
                        $line->subprice         = $objp->subprice;
                        $line->fk_remise_except = $objp->fk_remise_except;
                        $line->remise_percent   = $objp->remise_percent;
                        $line->price            = $objp->price;		// TODO deprecated

                        $line->info_bits        = $objp->info_bits;
                        $line->total_ht         = $objp->total_ht;
                        $line->total_tva        = $objp->total_tva;
                        $line->total_localtax1	= $objp->total_localtax1;
                        $line->total_localtax2	= $objp->total_localtax2;
                        $line->total_ttc        = $objp->total_ttc;
      					$line->fk_fournprice 	= $objp->fk_fournprice;
						$marginInfos			= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
						$line->pa_ht 			= $marginInfos[0];
						$line->marge_tx			= $marginInfos[1];
						$line->marque_tx		= $marginInfos[2];
                        $line->special_code     = $objp->special_code;
                        $line->rang             = $objp->rang;

                        $line->fk_product       = $objp->fk_product;

                        $line->ref				= $objp->product_ref;		// TODO deprecated
                        $line->product_ref		= $objp->product_ref;
                        $line->libelle			= $objp->product_label;		// TODO deprecated
                        $line->product_label	= $objp->product_label;
                        $line->product_desc     = $objp->product_desc; 		// Description produit
                        $line->fk_product_type  = $objp->fk_product_type;

						$line->ref_fourn		= $objp->ref_produit_fourn;

						// Multicurrency
						$line->fk_multicurrency 		= $objp->fk_multicurrency;
						$line->multicurrency_code 		= $objp->multicurrency_code;
						$line->multicurrency_subprice 	= $objp->multicurrency_subprice;
						$line->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
						$line->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
						$line->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;
						$line->fk_unit					= $objp->fk_unit;

                        $this->lines[$i]        = $line;

                        $i++;
                    }
                    $this->db->free($result);
                }
                else
                {
                    $this->error=$this->db->error();
                    return -1;
                }

                // Retreive all extrafield
                // fetch optionals attributes and labels
                $this->fetch_optionals();

                return 1;
            }

            $this->error="Record Not Found";
            return 0;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *  Set status to validated
     *
     *  @param	User	$user       Object user that validate
     *  @param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
     *  @return int         		<0 if KO, >=0 if OK
     */
    function valid($user, $notrigger=0)
    {
    	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    	global $conf,$langs;

        $error=0;
        $now=dol_now();

        if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->supplier_proposal->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->supplier_proposal->validate_advance)))
        {
            $this->db->begin();

            // Numbering module definition
            $soc = new Societe($this->db);
            $soc->fetch($this->socid);

            // Define new ref
            if (! $error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) // empty should not happened, but when it occurs, the test save life
            {
            	$num = $this->getNextNumRef($soc);
            }
            else
            {
            	$num = $this->ref;
            }
            $this->newref = $num;

            $sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal";
            $sql.= " SET ref = '".$this->db->escape($num)."',";
            $sql.= " fk_statut = 1, date_valid='".$this->db->idate($now)."', fk_user_valid=".$user->id;
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

            dol_syslog(get_class($this)."::valid", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				dol_print_error($this->db);
				$error++;
			}

   			// Trigger calls
			if (! $error && ! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('SUPPLIER_PROPOSAL_VALIDATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers
            }

            if (! $error)
            {
            	$this->oldref = $this->ref;

            	// Rename directory if dir was a temporary ref
            	if (preg_match('/^[\(]?PROV/i', $this->ref))
            	{
            		// Rename of propal directory ($this->ref = old ref, $num = new ref)
            		// to  not lose the linked files
            		$oldref = dol_sanitizeFileName($this->ref);
            		$newref = dol_sanitizeFileName($num);
            		$dirsource = $conf->supplier_proposal->dir_output.'/'.$oldref;
            		$dirdest = $conf->supplier_proposal->dir_output.'/'.$newref;

            		if (file_exists($dirsource))
            		{
            			dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);
            			if (@rename($dirsource, $dirdest))
            			{
            				dol_syslog("Rename ok");
            				// Rename docs starting with $oldref with $newref
            				$listoffiles=dol_dir_list($conf->supplier_proposal->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
            				foreach($listoffiles as $fileentry)
            				{
            					$dirsource=$fileentry['name'];
            					$dirdest=preg_replace('/^'.preg_quote($oldref,'/').'/',$newref, $dirsource);
            					$dirsource=$fileentry['path'].'/'.$dirsource;
            					$dirdest=$fileentry['path'].'/'.$dirdest;
            					@rename($dirsource, $dirdest);
            				}
            			}
            		}
            	}

            	$this->ref=$num;
            	$this->brouillon=0;
            	$this->statut = 1;
            	$this->user_valid_id=$user->id;
            	$this->datev=$now;

            	$this->db->commit();
            	return 1;
            }
            else
			{
            	$this->db->rollback();
            	return -1;
            }
        }
        else
        {
        	dol_syslog("You don't have permission to validate supplier proposal", LOG_WARNING);
        	return -2;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Set delivery date
     *
     *	@param      User 		$user        		Object user that modify
     *	@param      int			$date_livraison     Delivery date
     *	@return     int         					<0 if ko, >0 if ok
     */
    function set_date_livraison($user, $date_livraison)
    {
        // phpcs:enable
        if (! empty($user->rights->supplier_proposal->creer))
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal ";
            $sql.= " SET date_livraison = ".($date_livraison!=''?"'".$this->db->idate($date_livraison)."'":'null');
            $sql.= " WHERE rowid = ".$this->id;

            if ($this->db->query($sql))
            {
                $this->date_livraison = $date_livraison;
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                dol_syslog(get_class($this)."::set_date_livraison Erreur SQL");
                return -1;
            }
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Set an overall discount on the proposal
     *
     *	@param      User	$user       Object user that modify
     *	@param      double	$remise      Amount discount
     *	@return     int         		<0 if ko, >0 if ok
     */
    function set_remise_percent($user, $remise)
    {
        // phpcs:enable
        $remise=trim($remise)?trim($remise):0;

        if (! empty($user->rights->supplier_proposal->creer))
        {
            $remise = price2num($remise);

            $sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal SET remise_percent = ".$remise;
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

            if ($this->db->query($sql) )
            {
                $this->remise_percent = $remise;
                $this->update_price(1);
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Set an absolute overall discount on the proposal
     *
     *	@param      User	$user        Object user that modify
     *	@param      double	$remise      Amount discount
     *	@return     int         		<0 if ko, >0 if ok
     */
    function set_remise_absolue($user, $remise)
    {
        // phpcs:enable
        $remise=trim($remise)?trim($remise):0;

        if (! empty($user->rights->supplier_proposal->creer))
        {
            $remise = price2num($remise);

            $sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal ";
            $sql.= " SET remise_absolue = ".$remise;
            $sql.= " WHERE rowid = ".$this->id." AND fk_statut = 0";

            if ($this->db->query($sql) )
            {
                $this->remise_absolue = $remise;
                $this->update_price(1);
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
    }



    /**
     *	Reopen the commercial proposal
     *
     *	@param      User	$user		Object user that close
     *	@param      int		$statut		Statut
     *	@param      string	$note		Comment
     *  @param		int		$notrigger	1=Does not execute triggers, 0= execute triggers
     *	@return     int         		<0 if KO, >0 if OK
     */
    function reopen($user, $statut, $note='', $notrigger=0)
    {
        global $langs,$conf;

        $this->statut = $statut;
        $error=0;

        $sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal";
        $sql.= " SET fk_statut = ".$this->statut.",";
		if (! empty($note)) $sql.= " note_private = '".$this->db->escape($note)."',";
        $sql.= " date_cloture=NULL, fk_user_cloture=NULL";
        $sql.= " WHERE rowid = ".$this->id;

    	$this->db->begin();

		dol_syslog(get_class($this)."::reopen", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}
		if (! $error)
		{
			if (! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('SUPPLIER_PROPOSAL_REOPEN',$user);
                if ($result < 0) { $error++; }
                // End call triggers
			}
		}

		// Commit or rollback
		if ($error)
		{
		    if (!empty($this->errors))
		    {
    			foreach($this->errors as $errmsg)
    			{
    				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
    				$this->error.=($this->error?', '.$errmsg:$errmsg);
    			}
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
     *	Close the askprice
     *
     *	@param      User	$user		Object user that close
     *	@param      int		$statut		Statut
     *	@param      string	$note		Comment
     *	@return     int         		<0 if KO, >0 if OK
     */
    function cloture($user, $statut, $note)
    {
        global $langs,$conf;

        $this->statut = $statut;
        $error=0;
        $now=dol_now();

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal";
        $sql.= " SET fk_statut = ".$statut.", note_private = '".$this->db->escape($note)."', date_cloture='".$this->db->idate($now)."', fk_user_cloture=".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
        	$modelpdf=$conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_CLOSED?$conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_CLOSED:$this->modelpdf;
        	$trigger_name='SUPPLIER_PROPOSAL_CLOSE_REFUSED';

            if ($statut == 2)
            {
            	$trigger_name='SUPPLIER_PROPOSAL_CLOSE_SIGNED';
				$modelpdf=$conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_TOBILL?$conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_TOBILL:$this->modelpdf;

                if (! empty($conf->global->SUPPLIER_PROPOSAL_UPDATE_PRICE_ON_SUPPlIER_PROPOSAL))     // TODO This option was not tested correctly. Error if product ref does not exists
                {
                    $result = $this->updateOrCreatePriceFournisseur($user);
                }
            }
            if ($statut == 4)
            {
            	$trigger_name='SUPPLIER_PROPOSAL_CLASSIFY_BILLED';
            }

            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
            {
             	// Define output language
              	$outputlangs = $langs;
               	if (! empty($conf->global->MAIN_MULTILANGS))
               	{
               		$outputlangs = new Translate("",$conf);
               		$newlang=(GETPOST('lang_id','aZ09') ? GETPOST('lang_id','aZ09') : $this->thirdparty->default_lang);
               		$outputlangs->setDefaultLang($newlang);
               	}
               	//$ret=$object->fetch($id);    // Reload to get new records
	               $this->generateDocument($modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
            }

            // Call trigger
            $result=$this->call_trigger($trigger_name,$user);
            if ($result < 0) { $error++; }
            // End call triggers

            if ( ! $error )
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
        else
        {
            $this->error=$this->db->lasterror();
            $this->errors[]=$this->db->lasterror();
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
	function updateOrCreatePriceFournisseur($user)
	{
		$productsupplier = new ProductFournisseur($this->db);

		dol_syslog(get_class($this)."::updateOrCreatePriceFournisseur", LOG_DEBUG);
		foreach ($this->lines as $product)
		{
			if ($product->subprice <= 0) continue;

			$idProductFourn = $productsupplier->find_min_price_product_fournisseur($product->fk_product, $product->qty);
			$res = $productsupplier->fetch($idProductFourn);

			if ($productsupplier->id) {
				if ($productsupplier->fourn_qty == $product->qty) {
					$this->updatePriceFournisseur($productsupplier->product_fourn_price_id, $product, $user);
				} else {
					$this->createPriceFournisseur($product, $user);
				}
			} else {
				$this->createPriceFournisseur($product, $user);
			}
		}

		return 1;
	}

	/**
     *	Upate ProductFournisseur
     *
	 * 	@param		int 	$idProductFournPrice	id of llx_product_fournisseur_price
	 * 	@param		int 	$product				contain informations to update
	 *	@param      User	$user					Object user
     *	@return     int         					<0 if KO, >0 if OK
     */
    function updatePriceFournisseur($idProductFournPrice, $product, $user)
    {
		$price=price2num($product->subprice*$product->qty,'MU');
		$unitPrice = price2num($product->subprice,'MU');

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'product_fournisseur_price SET '.(!empty($product->ref_fourn) ? 'ref_fourn = "'.$product->ref_fourn.'", ' : '').' price ='.$price.', unitprice ='.$unitPrice.' WHERE rowid = '.$idProductFournPrice;

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error=$this->db->error();
            $this->db->rollback();
            return -1;
		}
	}

	 /**
     *	Create ProductFournisseur
	 *
     *	@param		Product 	$product	Object Product
	 *	@param      User		$user		Object user
     *	@return     int         			<0 if KO, >0 if OK
     */
    function createPriceFournisseur($product, $user)
    {
	 	$price=price2num($product->subprice*$product->qty,'MU');
	    $qty=price2num($product->qty);
		$unitPrice = price2num($product->subprice,'MU');
		$now=dol_now();

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

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'product_fournisseur_price ';
		$sql .= '(datec, fk_product, fk_soc, ref_fourn, price, quantity, unitprice, tva_tx, fk_user) VALUES ('.implode(',', $values).')';

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error=$this->db->error();
            $this->db->rollback();
            return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Set draft status
     *
     *	@param		User	$user		Object user that modify
     *	@return		int					<0 if KO, >0 if OK
     */
    function set_draft($user)
    {
        // phpcs:enable
        global $conf,$langs;

        $sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposal SET fk_statut = 0";
        $sql.= " WHERE rowid = ".$this->id;

        if ($this->db->query($sql))
        {
            $this->statut = 0;
            $this->brouillon = 1;
            return 1;
        }
        else
        {
            return -1;
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *    Return list of askprice (eventually filtered on user) into an array
     *
     *    @param	int		$shortlist			0=Return array[id]=ref, 1=Return array[](id=>id,ref=>ref,name=>name)
     *    @param	int		$draft				0=not draft, 1=draft
     *    @param	int		$notcurrentuser		0=all user, 1=not current user
     *    @param    int		$socid				Id third pary
     *    @param    int		$limit				For pagination
     *    @param    int		$offset				For pagination
     *    @param    string	$sortfield			Sort criteria
     *    @param    string	$sortorder			Sort order
     *    @return	int		       				-1 if KO, array with result if OK
     */
    function liste_array($shortlist=0, $draft=0, $notcurrentuser=0, $socid=0, $limit=0, $offset=0, $sortfield='p.datec', $sortorder='DESC')
    {
        // phpcs:enable
        global $conf,$user;

        $ga = array();

        $sql = "SELECT s.rowid, s.nom as name, s.client,";
        $sql.= " p.rowid as supplier_proposalid, p.fk_statut, p.total_ht, p.ref, p.remise, ";
        $sql.= " p.datep as dp, p.fin_validite as datelimite";
        if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", sc.fk_soc, sc.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."supplier_proposal as p, ".MAIN_DB_PREFIX."c_propalst as c";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE p.entity IN (".getEntity('supplier_proposal').")";
        $sql.= " AND p.fk_soc = s.rowid";
        $sql.= " AND p.fk_statut = c.id";
        if (! $user->rights->societe->client->voir && ! $socid) //restriction
        {
        	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        }
        if ($socid) $sql.= " AND s.rowid = ".$socid;
        if ($draft)	$sql.= " AND p.fk_statut = 0";
        if ($notcurrentuser > 0) $sql.= " AND p.fk_user_author <> ".$user->id;
        $sql.= $this->db->order($sortfield,$sortorder);
        $sql.= $this->db->plimit($limit,$offset);

        $result=$this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            if ($num)
            {
                $i = 0;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($result);

                    if ($shortlist == 1)
                    {
                        $ga[$obj->supplier_proposalid] = $obj->ref;
                    }
                    else if ($shortlist == 2)
                    {
                        $ga[$obj->supplier_proposalid] = $obj->ref.' ('.$obj->name.')';
                    }
                    else
					{
                        $ga[$i]['id']	= $obj->supplier_proposalid;
                        $ga[$i]['ref'] 	= $obj->ref;
                        $ga[$i]['name'] = $obj->name;
                    }

                    $i++;
                }
            }
            return $ga;
        }
        else
        {
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
    function delete($user, $notrigger=0)
    {
        global $conf,$langs;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $error=0;

        $this->db->begin();

        if (! $notrigger)
        {
            // Call trigger
            $result=$this->call_trigger('SUPPLIER_PROPOSAL_DELETE',$user);
            if ($result < 0) { $error++; }
            // End call triggers
        }

        if (! $error)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."supplier_proposaldet WHERE fk_supplier_proposal = ".$this->id;
            if ($this->db->query($sql))
            {
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."supplier_proposal WHERE rowid = ".$this->id;
                if ($this->db->query($sql))
                {
                    // Delete linked object
                    $res = $this->deleteObjectLinked();
                    if ($res < 0) $error++;

                    if (! $error)
                    {
                        // We remove directory
                        $ref = dol_sanitizeFileName($this->ref);
                        if ($conf->supplier_proposal->dir_output && !empty($this->ref))
                        {
                            $dir = $conf->supplier_proposal->dir_output . "/" . $ref ;
                            $file = $dir . "/" . $ref . ".pdf";
                            if (file_exists($file))
                            {
                                dol_delete_preview($this);

                                if (! dol_delete_file($file,0,0,0,$this)) // For triggers
                                {
                                    $this->error='ErrorFailToDeleteFile';
                                    $this->errors=array('ErrorFailToDeleteFile');
                                	$this->db->rollback();
                                    return 0;
                                }
                            }
                            if (file_exists($dir))
                            {
                                $res=@dol_delete_dir_recursive($dir);
                                if (! $res)
                                {
                                    $this->error='ErrorFailToDeleteDir';
                                    $this->errors=array('ErrorFailToDeleteDir');
                                    $this->db->rollback();
                                    return 0;
                                }
                            }
                        }
                    }

                    // Removed extrafields
                    if (! $error)
                    {
                    	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
                    	{
                    		$result=$this->deleteExtraFields();
                    		if ($result < 0)
                    		{
                    			$error++;
                    			$errorflag=-4;
                    			dol_syslog(get_class($this)."::delete erreur ".$errorflag." ".$this->error, LOG_ERR);
                    		}
                    	}
                    }

                    if (! $error)
                    {
                        dol_syslog(get_class($this)."::delete ".$this->id." by ".$user->id, LOG_DEBUG);
                        $this->db->commit();
                        return 1;
                    }
                    else
                    {
                        $this->error=$this->db->lasterror();
                        $this->db->rollback();
                        return 0;
                    }
                }
                else
                {
                    $this->error=$this->db->lasterror();
                    $this->db->rollback();
                    return -3;
                }
            }
            else
            {
                $this->error=$this->db->lasterror();
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
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
    function info($id)
    {
        $sql = "SELECT c.rowid, ";
        $sql.= " c.datec, c.date_valid as datev, c.date_cloture as dateo,";
        $sql.= " c.fk_user_author, c.fk_user_valid, c.fk_user_cloture";
        $sql.= " FROM ".MAIN_DB_PREFIX."supplier_proposal as c";
        $sql.= " WHERE c.rowid = ".$id;

        $result = $this->db->query($sql);

        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id                = $obj->rowid;

                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_validation   = $this->db->jdate($obj->datev);
                $this->date_cloture      = $this->db->jdate($obj->dateo);

                $cuser = new User($this->db);
                $cuser->fetch($obj->fk_user_author);
                $this->user_creation     = $cuser;

                if ($obj->fk_user_valid)
                {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_valid);
                    $this->user_validation     = $vuser;
                }

                if ($obj->fk_user_cloture)
                {
                    $cluser = new User($this->db);
                    $cluser->fetch($obj->fk_user_cloture);
                    $this->user_cloture     = $cluser;
                }
            }
            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *    	Return label of status of proposal (draft, validated, ...)
     *
     *    	@param      int			$mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     *    	@return     string		Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *  Return label of a status (draft, validated, ...)
     *
     *  @param      int			$statut		id statut
     *  @param      int			$mode      	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     *  @return     string      Label
     */
	function LibStatut($statut,$mode=1)
    {
        // phpcs:enable
    	// Init/load array of translation of status
    	if (empty($this->labelstatut) || empty($this->labelstatut_short))
    	{
    		global $langs;
    		$langs->load("supplier_proposal");
    		$this->labelstatut[0]=$langs->trans("SupplierProposalStatusDraft");
    		$this->labelstatut[1]=$langs->trans("SupplierProposalStatusValidated");
    		$this->labelstatut[2]=$langs->trans("SupplierProposalStatusSigned");
    		$this->labelstatut[3]=$langs->trans("SupplierProposalStatusNotSigned");
    		$this->labelstatut[4]=$langs->trans("SupplierProposalStatusClosed");
    		$this->labelstatut_short[0]=$langs->trans("SupplierProposalStatusDraftShort");
    		$this->labelstatut_short[1]=$langs->trans("Opened");
    		$this->labelstatut_short[2]=$langs->trans("SupplierProposalStatusSignedShort");
    		$this->labelstatut_short[3]=$langs->trans("SupplierProposalStatusNotSignedShort");
    		$this->labelstatut_short[4]=$langs->trans("SupplierProposalStatusClosedShort");
    	}

    	$statuttrans='';
		if ($statut==0) $statuttrans='statut0';
		elseif ($statut==1) $statuttrans='statut1';
		elseif ($statut==2) $statuttrans='statut3';
		elseif ($statut==3) $statuttrans='statut5';
		elseif ($statut==4) $statuttrans='statut6';

		if ($mode == 0)	return $this->labelstatut[$statut];
		elseif ($mode == 1)	return $this->labelstatut_short[$statut];
		elseif ($mode == 2)	return img_picto($this->labelstatut[$statut], $statuttrans).' '.$this->labelstatut_short[$statut];
		elseif ($mode == 3)	return img_picto($this->labelstatut[$statut], $statuttrans);
		elseif ($mode == 4)	return img_picto($this->labelstatut[$statut],$statuttrans).' '.$this->labelstatut[$statut];
		elseif ($mode == 5)	return '<span class="hideonsmartphone">'.$this->labelstatut_short[$statut].' </span>'.img_picto($this->labelstatut[$statut],$statuttrans);
		elseif ($mode == 6)	return '<span class="hideonsmartphone">'.$this->labelstatut[$statut].' </span>'.img_picto($this->labelstatut[$statut],$statuttrans);
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *      @param          User	$user   Object user
     *      @param          int		$mode   "opened" for askprice to close, "signed" for proposal to invoice
     *      @return         int             <0 if KO, >0 if OK
     */
    function load_board($user,$mode)
    {
        // phpcs:enable
        global $conf, $user, $langs;

        $now=dol_now();

        $this->nbtodo=$this->nbtodolate=0;
        $clause = " WHERE";

        $sql = "SELECT p.rowid, p.ref, p.datec as datec";
        $sql.= " FROM ".MAIN_DB_PREFIX."supplier_proposal as p";
        if (!$user->rights->societe->client->voir && !$user->socid)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc";
            $sql.= " WHERE sc.fk_user = " .$user->id;
            $clause = " AND";
        }
        $sql.= $clause." p.entity IN (".getEntity('supplier_proposal').")";
        if ($mode == 'opened') $sql.= " AND p.fk_statut = 1";
        if ($mode == 'signed') $sql.= " AND p.fk_statut = 2";
        if ($user->socid) $sql.= " AND p.fk_soc = ".$user->socid;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($mode == 'opened') {
	            $delay_warning=$conf->supplier_proposal->cloture->warning_delay;
	            $statut = self::STATUS_VALIDATED;
	            $label = $langs->trans("SupplierProposalsToClose");
            }
            if ($mode == 'signed') {
	            $delay_warning=$conf->supplier_proposal->facturation->warning_delay;
	            $statut = self::STATUS_SIGNED;
	            $label = $langs->trans("SupplierProposalsToProcess");      // May be billed or ordered
            }

	        $response = new WorkboardResponse();
	        $response->warning_delay = $delay_warning/60/60/24;
	        $response->label = $label;
	        $response->url = DOL_URL_ROOT.'/supplier_proposal/list.php?viewstatut='.$statut;
	        $response->img = img_object('',"propal");

            // This assignment in condition is not a bug. It allows walking the results.
            while ($obj=$this->db->fetch_object($resql))
            {
                $response->nbtodo++;
                if ($mode == 'opened')
                {
                    $datelimit = $this->db->jdate($obj->datefin);
                    if ($datelimit < ($now - $delay_warning))
                    {
                        $response->nbtodolate++;
                    }
                }
                // TODO Definir regle des propales a facturer en retard
                // if ($mode == 'signed' && ! count($this->FactureListeArray($obj->rowid))) $this->nbtodolate++;
            }
            return $response;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $user,$langs,$conf;

        // Load array of products prodids
        $num_prods = 0;
        $prodids = array();
        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."product";
        $sql.= " WHERE entity IN (".getEntity('product').")";
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num_prods = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_prods)
            {
                $i++;
                $row = $this->db->fetch_row($resql);
                $prodids[$i] = $row[0];
            }
        }

        // Initialise parametres
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->specimen=1;
        $this->socid = 1;
        $this->date = time();
        $this->cond_reglement_id   = 1;
        $this->cond_reglement_code = 'RECEP';
        $this->mode_reglement_id   = 7;
        $this->mode_reglement_code = 'CHQ';
        $this->note_public='This is a comment (public)';
        $this->note_private='This is a comment (private)';
        // Lines
        $nbp = 5;
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line=new SupplierProposalLine($this->db);
            $line->desc=$langs->trans("Description")." ".$xnbp;
            $line->qty=1;
            $line->subprice=100;
            $line->price=100;
            $line->tva_tx=19.6;
            $line->localtax1_tx=0;
            $line->localtax2_tx=0;
            if ($xnbp == 2)
            {
                $line->total_ht=50;
                $line->total_ttc=59.8;
                $line->total_tva=9.8;
                $line->remise_percent=50;
            }
            else
            {
                $line->total_ht=100;
                $line->total_ttc=119.6;
                $line->total_tva=19.6;
                $line->remise_percent=00;
            }

            if ($num_prods > 0)
            {
            	$prodid = mt_rand(1, $num_prods);
            	$line->fk_product=$prodids[$prodid];
            }

            $this->lines[$xnbp]=$line;

            $this->total_ht       += $line->total_ht;
            $this->total_tva      += $line->total_tva;
            $this->total_ttc      += $line->total_ttc;

            $xnbp++;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *      Charge indicateurs this->nb de tableau de bord
     *
     *      @return     int         <0 if ko, >0 if ok
     */
    function load_state_board()
    {
        // phpcs:enable
        global $conf, $user;

        $this->nb=array();
        $clause = "WHERE";

        $sql = "SELECT count(p.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."supplier_proposal as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
        if (!$user->rights->societe->client->voir && !$user->socid)
        {
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
            $sql.= " WHERE sc.fk_user = " .$user->id;
            $clause = "AND";
        }
        $sql.= " ".$clause." p.entity IN (".getEntity('supplier_proposal').")";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            // This assignment in condition is not a bug. It allows walking the results.
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nb["askprice"]=$obj->nb;
            }
            $this->db->free($resql);
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->lasterror();
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
    function getNextNumRef($soc)
    {
        global $conf, $db, $langs;
        $langs->load("supplier_proposal");

        if (! empty($conf->global->SUPPLIER_PROPOSAL_ADDON))
        {
        	$mybool=false;

            $file = $conf->global->SUPPLIER_PROPOSAL_ADDON.".php";
            $classname = $conf->global->SUPPLIER_PROPOSAL_ADDON;

            // Include file with class
            $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
            foreach ($dirmodels as $reldir) {

                $dir = dol_buildpath($reldir."core/modules/supplier_proposal/");

                // Load file with numbering class (if found)
                $mybool|=@include_once $dir.$file;
            }

            if (! $mybool)
            {
            	dol_print_error('',"Failed to include file ".$file);
            	return '';
            }

            $obj = new $classname();
            $numref = "";
            $numref = $obj->getNextValue($soc,$this);

            if ($numref != "")
            {
                return $numref;
            }
            else
			{
                $this->error=$obj->error;
                return "";
            }
        }
        else
		{
            $langs->load("errors");
            print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete");
            return "";
        }
    }

    /**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int		$withpicto					Add picto into link
     *	@param      string	$option						Where point the link ('compta', 'expedition', 'document', ...)
     *	@param      string	$get_params    				Parametres added to url
     *  @param	    int   	$notooltip					1=Disable tooltip
     *  @param      int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *	@return     string          					String with URL
     */
    function getNomUrl($withpicto=0, $option='', $get_params='', $notooltip=0, $save_lastsearch_value=-1)
    {
        global $langs, $conf, $user;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $url='';
        $result='';

        $label='<u>'.$langs->trans("ShowSupplierProposal").'</u>';
        if (! empty($this->ref))
        $label.= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
        if (! empty($this->ref_fourn))
            $label.= '<br><b>'.$langs->trans('RefSupplier').':</b> '.$this->ref_fourn;
        if (! empty($this->total_ht))
            $label.= '<br><b>' . $langs->trans('AmountHT') . ':</b> ' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_tva))
            $label.= '<br><b>' . $langs->trans('VAT') . ':</b> ' . price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_ttc))
            $label.= '<br><b>' . $langs->trans('AmountTTC') . ':</b> ' . price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
        if ($option == '') {
            $url = DOL_URL_ROOT.'/supplier_proposal/card.php?id='.$this->id. $get_params;
        }
        if ($option == 'document') {
            $url = DOL_URL_ROOT.'/supplier_proposal/document.php?id='.$this->id. $get_params;
        }

        if ($option !== 'nolink')
        {
        	// Add param to save lastsearch_values or not
        	$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
        	if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
        	if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $linkclose='';
        if (empty($notooltip) && $user->rights->propal->lire)
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowSupplierProposal");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip"';
        }

        $linkstart = '<a href="'.$url.'"';
        $linkstart.=$linkclose.'>';
        $linkend='</a>';

        $picto='supplier_proposal';

        $result .= $linkstart;
        if ($withpicto) $result.=img_object(($notooltip?'':$label), $this->picto, ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
        if ($withpicto != 2) $result.= $this->ref;
        $result .= $linkend;

        return $result;
    }

    /**
     * 	Retrieve an array of supplier proposal lines
	 *
	 * 	@return int		>0 if OK, <0 if KO
     */
    function getLinesArray()
    {
        // For other object, here we call fetch_lines. But fetch_lines does not exists on supplier proposal

        $sql = 'SELECT pt.rowid, pt.label as custom_label, pt.description, pt.fk_product, pt.fk_remise_except,';
        $sql.= ' pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, pt.info_bits,';
        $sql.= ' pt.total_ht, pt.total_tva, pt.total_ttc, pt.fk_product_fournisseur_price as fk_fournprice, pt.buy_price_ht as pa_ht, pt.special_code, pt.localtax1_tx, pt.localtax2_tx,';
        $sql.= ' pt.product_type, pt.rang, pt.fk_parent_line,';
        $sql.= ' p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid,';
        $sql.= ' p.description as product_desc, pt.ref_fourn as ref_supplier,';
		$sql.= ' pt.fk_multicurrency, pt.multicurrency_code, pt.multicurrency_subprice, pt.multicurrency_total_ht, pt.multicurrency_total_tva, pt.multicurrency_total_ttc, pt.fk_unit';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'supplier_proposaldet as pt';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
        $sql.= ' WHERE pt.fk_supplier_proposal = '.$this->id;
        $sql.= ' ORDER BY pt.rang ASC, pt.rowid';

        dol_syslog(get_class($this).'::getLinesArray', LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;

            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                $this->lines[$i]					= new SupplierProposalLine($this->db);
                $this->lines[$i]->id				= $obj->rowid; // for backward compatibility
                $this->lines[$i]->rowid				= $obj->rowid;
                $this->lines[$i]->label 			= $obj->custom_label;
                $this->lines[$i]->description 		= $obj->description;
                $this->lines[$i]->fk_product		= $obj->fk_product;
                $this->lines[$i]->ref				= $obj->ref;
                $this->lines[$i]->product_label		= $obj->product_label;
                $this->lines[$i]->product_desc		= $obj->product_desc;
                $this->lines[$i]->fk_product_type	= $obj->fk_product_type;  // deprecated
                $this->lines[$i]->product_type		= $obj->product_type;
                $this->lines[$i]->qty				= $obj->qty;
                $this->lines[$i]->subprice			= $obj->subprice;
                $this->lines[$i]->fk_remise_except 	= $obj->fk_remise_except;
                $this->lines[$i]->remise_percent	= $obj->remise_percent;
                $this->lines[$i]->tva_tx			= $obj->tva_tx;
                $this->lines[$i]->info_bits			= $obj->info_bits;
                $this->lines[$i]->total_ht			= $obj->total_ht;
                $this->lines[$i]->total_tva			= $obj->total_tva;
                $this->lines[$i]->total_ttc			= $obj->total_ttc;
				$this->lines[$i]->fk_fournprice		= $obj->fk_fournprice;
				$marginInfos						= getMarginInfos($obj->subprice, $obj->remise_percent, $obj->tva_tx, $obj->localtax1_tx, $obj->localtax2_tx, $this->lines[$i]->fk_fournprice, $obj->pa_ht);
				$this->lines[$i]->pa_ht				= $marginInfos[0];
				$this->lines[$i]->marge_tx			= $marginInfos[1];
				$this->lines[$i]->marque_tx			= $marginInfos[2];
				$this->lines[$i]->fk_parent_line	= $obj->fk_parent_line;
                $this->lines[$i]->special_code		= $obj->special_code;
                $this->lines[$i]->rang				= $obj->rang;

                $this->lines[$i]->ref_fourn				= $obj->ref_supplier;	// deprecated
                $this->lines[$i]->ref_supplier			= $obj->ref_supplier;

				// Multicurrency
				$this->lines[$i]->fk_multicurrency 			= $obj->fk_multicurrency;
				$this->lines[$i]->multicurrency_code 		= $obj->multicurrency_code;
				$this->lines[$i]->multicurrency_subprice 	= $obj->multicurrency_subprice;
				$this->lines[$i]->multicurrency_total_ht 	= $obj->multicurrency_total_ht;
				$this->lines[$i]->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->lines[$i]->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;
				$this->lines[$i]->fk_unit				 	= $obj->fk_unit;

                $i++;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
            $this->error=$this->db->error();
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
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0, $moreparams=null)
	{
		global $conf, $langs;

		$langs->load("supplier_proposal");

		if (! dol_strlen($modele)) {

			$modele = 'aurore';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->SUPPLIER_PROPOSAL_ADDON_PDF)) {
				$modele = $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/supplier_proposal/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}


	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
	    $tables = array(
	        'supplier_proposal'
	    );

	    return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
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
	public $error='';

    /**
	 * @var string ID to identify managed object
	 */
	public $element='supplier_proposaldet';

    /**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='supplier_proposaldet';

    public $oldline;

    // From llx_supplier_proposaldet
    public $rowid; // deprecated

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

    public $desc;          	// Description ligne

    /**
     * @var int ID
     */
    public $fk_product;		// Id produit predefini

	/**
	 * @deprecated
	 * @see product_type
	 */
	public $fk_product_type;
	/**
	 * Product type
	 * @var int
	 * @see Product::TYPE_PRODUCT, Product::TYPE_SERVICE
	 */
    public $product_type = Product::TYPE_PRODUCT;

    public $qty;
    public $tva_tx;
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

    public $special_code;	// Tag for special lines (exlusive tags)
    // 1: frais de port
    // 2: ecotaxe
    // 3: option line (when qty = 0)

    public $info_bits = 0;	// Liste d'options cumulables:
    // Bit 0: 	0 si TVA normal - 1 si TVA NPR
    // Bit 1:	0 ligne normale - 1 si ligne de remise fixe

    public $total_ht;			// Total HT  de la ligne toute quantite et incluant la remise ligne
    public $total_tva;			// Total TVA  de la ligne toute quantite et incluant la remise ligne
    public $total_ttc;			// Total TTC de la ligne toute quantite et incluant la remise ligne

	/**
	 * @deprecated
	 * @see remise_percent, fk_remise_except
	 */
    public $remise;

	/**
	 * @deprecated
	 * @see subprice
	 */
    public $price;

    // From llx_product
	/**
	 * @deprecated
	 * @see product_ref
	 */
	public $ref;

	/**
	 * Product reference
	 * @var string
	 */
	public $product_ref;

	/**
	 * @deprecated
	 * @see product_label
	 */
	public $libelle;

	/**
	 *  Product label
	 * @var string
	 */
	public $product_label;

	/**
	 * Product description
	 * @var string
	 */
	public $product_desc;

    public $localtax1_tx;		// Local tax 1
    public $localtax2_tx;		// Local tax 2
    public $localtax1_type;	// Local tax 1 type
	public $localtax2_type;	// Local tax 2 type
    public $total_localtax1;  	// Line total local tax 1
    public $total_localtax2;	// Line total local tax 2

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
     * 	Class line Contructor
     *
     * 	@param	DoliDB	$db	Database handler
     */
    function __construct($db)
    {
        $this->db= $db;
    }

    /**
     *	Retrieve the propal line object
     *
     *	@param	int		$rowid		Propal line id
     *	@return	int					<0 if KO, >0 if OK
     */
	function fetch($rowid)
	{
		$sql = 'SELECT pd.rowid, pd.fk_supplier_proposal, pd.fk_parent_line, pd.fk_product, pd.label as custom_label, pd.description, pd.price, pd.qty, pd.tva_tx,';
		$sql.= ' pd.remise, pd.remise_percent, pd.fk_remise_except, pd.subprice,';
		$sql.= ' pd.info_bits, pd.total_ht, pd.total_tva, pd.total_ttc, pd.fk_product_fournisseur_price as fk_fournprice, pd.buy_price_ht as pa_ht, pd.special_code, pd.rang,';
		$sql.= ' pd.localtax1_tx, pd.localtax2_tx, pd.total_localtax1, pd.total_localtax2,';
		$sql.= ' p.ref as product_ref, p.label as product_label, p.description as product_desc,';
		$sql.= ' pd.product_type, pd.ref_fourn as ref_produit_fourn,';
		$sql.= ' pd.fk_multicurrency, pd.multicurrency_code, pd.multicurrency_subprice, pd.multicurrency_total_ht, pd.multicurrency_total_tva, pd.multicurrency_total_ttc, pd.fk_unit';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'supplier_proposaldet as pd';
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pd.fk_product = p.rowid';
		$sql.= ' WHERE pd.rowid = '.$rowid;

		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);

			$this->rowid			= $objp->rowid; // deprecated
			$this->id				= $objp->rowid;
			$this->fk_supplier_proposal		= $objp->fk_supplier_proposal;
			$this->fk_parent_line	= $objp->fk_parent_line;
			$this->label			= $objp->custom_label;
			$this->desc				= $objp->description;
			$this->qty				= $objp->qty;
			$this->price			= $objp->price;		// deprecated
			$this->subprice			= $objp->subprice;
			$this->tva_tx			= $objp->tva_tx;
			$this->remise			= $objp->remise;
			$this->remise_percent	= $objp->remise_percent;
			$this->fk_remise_except = $objp->fk_remise_except;
			$this->fk_product		= $objp->fk_product;
			$this->info_bits		= $objp->info_bits;

			$this->total_ht			= $objp->total_ht;
			$this->total_tva		= $objp->total_tva;
			$this->total_ttc		= $objp->total_ttc;

			$this->fk_fournprice	= $objp->fk_fournprice;

			$marginInfos			= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
			$this->pa_ht			= $marginInfos[0];
			$this->marge_tx			= $marginInfos[1];
			$this->marque_tx		= $marginInfos[2];

			$this->special_code		= $objp->special_code;
			$this->product_type		= $objp->product_type;
			$this->rang				= $objp->rang;

			$this->ref				= $objp->product_ref;      // deprecated
			$this->product_ref		= $objp->product_ref;
			$this->libelle			= $objp->product_label;  // deprecated
			$this->product_label	= $objp->product_label;
			$this->product_desc		= $objp->product_desc;

			$this->ref_fourn		= $objp->ref_produit_forun;

			// Multicurrency
			$this->fk_multicurrency 		= $objp->fk_multicurrency;
			$this->multicurrency_code 		= $objp->multicurrency_code;
			$this->multicurrency_subprice 	= $objp->multicurrency_subprice;
			$this->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
			$this->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
			$this->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;
			$this->fk_unit				 	= $objp->fk_unit;

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    /**
     *  Insert object line propal in database
     *
     *	@param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
     *	@return		int						<0 if KO, >0 if OK
     */
    function insert($notrigger=0)
    {
        global $conf,$langs,$user;

        $error=0;

        dol_syslog(get_class($this)."::insert rang=".$this->rang);

        // Clean parameters
        if (empty($this->tva_tx)) $this->tva_tx=0;
        if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
        if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
        if (empty($this->localtax1_type)) $this->localtax1_type=0;
		if (empty($this->localtax2_type)) $this->localtax2_type=0;
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
        if (empty($this->rang)) $this->rang=0;
        if (empty($this->remise)) $this->remise=0;
        if (empty($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->special_code)) $this->special_code=0;
        if (empty($this->fk_parent_line)) $this->fk_parent_line=0;
        if (empty($this->fk_fournprice)) $this->fk_fournprice=0;
        if (empty($this->fk_unit)) $this->fk_unit=0;
        if (empty($this->subprice)) $this->subprice=0;

        if (empty($this->pa_ht)) $this->pa_ht=0;

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0)
		{
			if (($result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product)) < 0)
			{
				return $result;
			}
			else
			{
				$this->pa_ht = $result;
			}
		}

        // Check parameters
        if ($this->product_type < 0) return -1;

        $this->db->begin();

        // Insert line into database
        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'supplier_proposaldet';
        $sql.= ' (fk_supplier_proposal, fk_parent_line, label, description, fk_product, product_type,';
		$sql.= ' fk_remise_except, qty, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
        $sql.= ' subprice, remise_percent, ';
        $sql.= ' info_bits, ';
        $sql.= ' total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, fk_product_fournisseur_price, buy_price_ht, special_code, rang,';
        $sql.= ' ref_fourn,';
		$sql.= ' fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc, fk_unit)';
        $sql.= " VALUES (".$this->fk_supplier_proposal.",";
        $sql.= " ".($this->fk_parent_line>0?"'".$this->db->escape($this->fk_parent_line)."'":"null").",";
        $sql.= " ".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
        $sql.= " '".$this->db->escape($this->desc)."',";
        $sql.= " ".($this->fk_product?"'".$this->db->escape($this->fk_product)."'":"null").",";
        $sql.= " '".$this->db->escape($this->product_type)."',";
        $sql.= " ".($this->fk_remise_except?"'".$this->db->escape($this->fk_remise_except)."'":"null").",";
        $sql.= " ".price2num($this->qty).",";
        $sql.= " ".price2num($this->tva_tx).",";
        $sql.= " ".price2num($this->localtax1_tx).",";
        $sql.= " ".price2num($this->localtax2_tx).",";
		$sql.= " '".$this->db->escape($this->localtax1_type)."',";
		$sql.= " '".$this->db->escape($this->localtax2_type)."',";
        $sql.= " ".(!empty($this->subprice)?price2num($this->subprice):"null").",";
        $sql.= " ".price2num($this->remise_percent).",";
        $sql.= " ".(isset($this->info_bits)?"'".$this->db->escape($this->info_bits)."'":"null").",";
        $sql.= " ".price2num($this->total_ht).",";
        $sql.= " ".price2num($this->total_tva).",";
        $sql.= " ".price2num($this->total_localtax1).",";
        $sql.= " ".price2num($this->total_localtax2).",";
        $sql.= " ".price2num($this->total_ttc).",";
        $sql.= " ".(!empty($this->fk_fournprice)?"'".$this->db->escape($this->fk_fournprice)."'":"null").",";
        $sql.= " ".(isset($this->pa_ht)?"'".price2num($this->pa_ht)."'":"null").",";
        $sql.= ' '.$this->special_code.',';
        $sql.= ' '.$this->rang.',';
        $sql.= " '".$this->db->escape($this->ref_fourn)."'";
		$sql.= ", ".($this->fk_multicurrency > 0?$this->fk_multicurrency:'null');
		$sql.= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql.= ", ".$this->multicurrency_subprice;
		$sql.= ", ".$this->multicurrency_total_ht;
		$sql.= ", ".$this->multicurrency_total_tva;
		$sql.= ", ".$this->multicurrency_total_ttc;
        $sql.= ", ".($this->fk_unit?$this->fk_unit:'null');
		$sql.= ')';

        dol_syslog(get_class($this).'::insert', LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'supplier_proposaldet');
			$this->id=$this->rowid;

            if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
            {
            	$result=$this->insertExtraFields();
            	if ($result < 0)
            	{
            		$error++;
            	}
            }

            if (! $error && ! $notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('LINESUPPLIER_PROPOSAL_INSERT',$user);
                if ($result < 0)
                {
                    $this->db->rollback();
                    return -1;
                }
                // End call triggers
            }

            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error()." sql=".$sql;
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * 	Delete line in database
     *
     *	@return	 int  <0 if ko, >0 if ok
     */
    function delete()
    {
        global $conf,$langs,$user;

        $error=0;
        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."supplier_proposaldet WHERE rowid = ".$this->rowid;
        dol_syslog("SupplierProposalLine::delete", LOG_DEBUG);
        if ($this->db->query($sql) )
        {

        	// Remove extrafields
        	if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
        	{
        		$this->id=$this->rowid;
        		$result=$this->deleteExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        			dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
        		}
        	}

            // Call trigger
            $result=$this->call_trigger('LINESUPPLIER_PROPOSAL_DELETE',$user);
            if ($result < 0)
            {
                $this->db->rollback();
                return -1;
            }
            // End call triggers

            $this->db->commit();

            return 1;
        }
        else
        {
            $this->error=$this->db->error()." sql=".$sql;
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *	Update propal line object into DB
     *
     *	@param 	int		$notrigger	1=Does not execute triggers, 0= execute triggers
     *	@return	int					<0 if ko, >0 if ok
     */
    function update($notrigger=0)
    {
        global $conf,$langs,$user;

        $error=0;

        // Clean parameters
        if (empty($this->tva_tx)) $this->tva_tx=0;
        if (empty($this->localtax1_tx)) $this->localtax1_tx=0;
        if (empty($this->localtax2_tx)) $this->localtax2_tx=0;
        if (empty($this->total_localtax1)) $this->total_localtax1=0;
        if (empty($this->total_localtax2)) $this->total_localtax2=0;
		if (empty($this->localtax1_type)) $this->localtax1_type=0;
		if (empty($this->localtax2_type)) $this->localtax2_type=0;
        if (empty($this->marque_tx)) $this->marque_tx=0;
        if (empty($this->marge_tx)) $this->marge_tx=0;
        if (empty($this->price)) $this->price=0;	// TODO A virer
        if (empty($this->remise)) $this->remise=0;	// TODO A virer
        if (empty($this->remise_percent)) $this->remise_percent=0;
        if (empty($this->info_bits)) $this->info_bits=0;
        if (empty($this->special_code)) $this->special_code=0;
        if (empty($this->fk_parent_line)) $this->fk_parent_line=0;
        if (empty($this->fk_fournprice)) $this->fk_fournprice=0;
        if (empty($this->fk_unit)) $this->fk_unit=0;
        if (empty($this->subprice)) $this->subprice=0;

		if (empty($this->pa_ht)) $this->pa_ht=0;

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0)
		{
			if (($result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product)) < 0)
			{
				return $result;
			}
			else
			{
				$this->pa_ht = $result;
			}
		}

        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposaldet SET";
        $sql.= " description='".$this->db->escape($this->desc)."'";
        $sql.= " , label=".(! empty($this->label)?"'".$this->db->escape($this->label)."'":"null");
        $sql.= " , product_type=".$this->product_type;
        $sql.= " , tva_tx='".price2num($this->tva_tx)."'";
        $sql.= " , localtax1_tx=".price2num($this->localtax1_tx);
        $sql.= " , localtax2_tx=".price2num($this->localtax2_tx);
		$sql.= " , localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql.= " , localtax2_type='".$this->db->escape($this->localtax2_type)."'";
        $sql.= " , qty='".price2num($this->qty)."'";
        $sql.= " , subprice=".price2num($this->subprice)."";
        $sql.= " , remise_percent=".price2num($this->remise_percent)."";
        $sql.= " , price=".price2num($this->price)."";					// TODO A virer
        $sql.= " , remise=".price2num($this->remise)."";				// TODO A virer
        $sql.= " , info_bits='".$this->db->escape($this->info_bits)."'";
        if (empty($this->skip_update_total))
        {
            $sql.= " , total_ht=".price2num($this->total_ht)."";
            $sql.= " , total_tva=".price2num($this->total_tva)."";
            $sql.= " , total_ttc=".price2num($this->total_ttc)."";
            $sql.= " , total_localtax1=".price2num($this->total_localtax1)."";
            $sql.= " , total_localtax2=".price2num($this->total_localtax2)."";
        }
		$sql.= " , fk_product_fournisseur_price=".(! empty($this->fk_fournprice)?"'".$this->db->escape($this->fk_fournprice)."'":"null");
		$sql.= " , buy_price_ht=".price2num($this->pa_ht);
        if (strlen($this->special_code)) $sql.= " , special_code=".$this->special_code;
        $sql.= " , fk_parent_line=".($this->fk_parent_line>0?$this->fk_parent_line:"null");
        if (! empty($this->rang)) $sql.= ", rang=".$this->rang;
        $sql.= " , ref_fourn=".(! empty($this->ref_fourn)?"'".$this->db->escape($this->ref_fourn)."'":"null");
        $sql.= " , fk_unit=".($this->fk_unit?$this->fk_unit:'null');

		// Multicurrency
		$sql.= " , multicurrency_subprice=".price2num($this->multicurrency_subprice)."";
        $sql.= " , multicurrency_total_ht=".price2num($this->multicurrency_total_ht)."";
        $sql.= " , multicurrency_total_tva=".price2num($this->multicurrency_total_tva)."";
        $sql.= " , multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc)."";

		$sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
        	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        	{
        		$this->id=$this->rowid;
        		$result=$this->insertExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        		}
        	}

            if (! $error && ! $notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('LINESUPPLIER_PROPOSAL_UPDATE',$user);
                if ($result < 0)
                {
                    $this->db->rollback();
                    return -1;
                }
                // End call triggers
            }

            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -2;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
    /**
     *	Update DB line fields total_xxx
     *	Used by migration
     *
     *	@return		int		<0 if ko, >0 if ok
     */
    function update_total()
    {
        // phpcs:enable
        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."supplier_proposaldet SET";
        $sql.= " total_ht=".price2num($this->total_ht,'MT')."";
        $sql.= ",total_tva=".price2num($this->total_tva,'MT')."";
        $sql.= ",total_ttc=".price2num($this->total_ttc,'MT')."";
        $sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog("SupplierProposalLine::update_total", LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -2;
        }
    }
}
