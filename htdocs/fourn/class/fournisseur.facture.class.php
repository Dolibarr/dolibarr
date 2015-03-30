<?php
/* Copyright (C) 2002-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley			<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2014	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
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
 *	\file       htdocs/fourn/class/fournisseur.facture.class.php
 *	\ingroup    fournisseur,facture
 *	\brief      File of class to manage suppliers invoices
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';


/**
 *	Class to manage suppliers invoices
 */
class FactureFournisseur extends CommonInvoice
{
    public $element='invoice_supplier';
    public $table_element='facture_fourn';
    public $table_element_line='facture_fourn_det';
    public $fk_element='fk_facture_fourn';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    /**
     * {@inheritdoc}
     */
    protected $table_ref_field = 'ref';

    var $rowid;
    var $ref;
    var $product_ref;
    var $ref_supplier;
    var $socid;
    //Check constants for types
    var $type = self::TYPE_STANDARD;
    //! 0=draft,
    //! 1=validated
    //! 2=classified paid partially (close_code='discount_vat','badcustomer') or completely (close_code=null),
    //! Also 2, should be 3=classified abandoned and no payment done (close_code='badcustomer','abandon' ou 'replaced')
    var $statut;
    //! 1 si facture payee COMPLETEMENT, 0 sinon (ce champ ne devrait plus servir car insuffisant)
    var $paye;

    var $author;
    var $libelle;
    var $datec;            // Creation date
    var $tms;              // Last update date
    var $date;             // Invoice date
    var $date_echeance;    // Max payment date
    var $amount;
    var $remise;
    var $tva;
    var $localtax1;
    var $localtax2;
    var $total_ht;
    var $total_tva;
    var $total_localtax1;
    var $total_localtax2;
    var $total_ttc;
    var $note;			// deprecated
    var $note_private;
    var $note_public;
    var $propalid;
    var $cond_reglement_id;
    var $cond_reglement_code;
    var $fk_account;
    var $mode_reglement_id;
    var $mode_reglement_code;

	/**
	 * Invoice lines
	 * @var SupplierInvoiceLine[]
	 */
    public $lines = array();
    var $fournisseur;	// deprecated

	//Incorterms
	var $fk_incoterms;
	var $location_incoterms;
	var $libelle_incoterms;  //Used into tooltip
	
    var $extraparams=array();

    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        $this->amount = 0;
        $this->remise = 0;
        $this->tva = 0;
        $this->total_localtax1 = 0;
        $this->total_localtax2 = 0;
        $this->total_ht = 0;
        $this->total_tva = 0;
        $this->total_ttc = 0;
        $this->propalid = 0;

        $this->products = array();
    }

    /**
     *    Create supplier invoice into database
     *
     *    @param      User		$user       object utilisateur qui cree
     *    @return     int    	     		id facture si ok, < 0 si erreur
     */
    function create($user)
    {
        global $langs,$conf,$hookmanager;

		$error=0;
        $now=dol_now();

        // Clean parameters
        if (isset($this->ref_supplier)) $this->ref_supplier=trim($this->ref_supplier);
        if (empty($this->date)) $this->date=$now;

        $socid = $this->socid;
        $ref_supplier = $this->ref_supplier;
        $amount = $this->amount;
        $remise = $this->remise;

        $this->db->begin();

        if (! $remise) $remise = 0 ;
        $totalht = ($amount - $remise);

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn (";
		$sql.= "ref";
        $sql.= ", ref_supplier";
        $sql.= ", entity";
        $sql.= ", libelle";
        $sql.= ", fk_soc";
        $sql.= ", datec";
        $sql.= ", datef";
		$sql.= ", fk_projet";
		$sql.= ", fk_cond_reglement";
		$sql.= ", fk_mode_reglement";
        $sql.= ", fk_account";
        $sql.= ", note_private";
        $sql.= ", note_public";
        $sql.= ", fk_user_author";
        $sql.= ", date_lim_reglement";
		$sql.= ", fk_incoterms, location_incoterms";
        $sql.= ")";
        $sql.= " VALUES (";
		$sql.= "'(PROV)'";
        $sql.= ", '".$this->db->escape($this->ref_supplier)."'";
        $sql.= ", ".$conf->entity;
        $sql.= ", '".$this->db->escape($this->libelle)."'";
        $sql.= ", ".$this->socid;
        $sql.= ", '".$this->db->idate($now)."'";
        $sql.= ", '".$this->db->idate($this->date)."'";
		$sql.= ", ".(isset($this->fk_project)?$this->fk_project:"null");
		$sql.= ", ".(isset($this->cond_reglement_id)?$this->cond_reglement_id:"null");
		$sql.= ", ".(isset($this->mode_reglement_id)?$this->mode_reglement_id:"null");
        $sql.= ", ".($this->fk_account>0?$this->fk_account:'NULL');
        $sql.= ", '".$this->db->escape($this->note_private)."'";
        $sql.= ", '".$this->db->escape($this->note_public)."'";
        $sql.= ", ".$user->id.",";
        $sql.= $this->date_echeance!=''?"'".$this->db->idate($this->date_echeance)."'":"null";
		$sql.= ", ".(int) $this->fk_incoterms;
        $sql.= ", '".$this->db->escape($this->location_incoterms)."'";
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn');

            // Update ref with new one
            $this->ref='(PROV'.$this->id.')';
            $sql = 'UPDATE '.MAIN_DB_PREFIX."facture_fourn SET ref='".$this->ref."' WHERE rowid=".$this->id;

            dol_syslog(get_class($this)."::create", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if (! $resql) $error++;

            // Add object linked
            if (! $error && $this->id && ! empty($this->origin) && ! empty($this->origin_id))
            {
                $ret = $this->add_object_linked();
                if (! $ret)
                {
                    dol_print_error($this->db);
                    $error++;
                }
            }

            foreach ($this->lines as $i => $val)
            {
                $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
                $sql .= ' VALUES ('.$this->id.');';

                dol_syslog(get_class($this)."::create", LOG_DEBUG);
                $resql_insert=$this->db->query($sql);
                if ($resql_insert)
                {
                    $idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');

                    $this->updateline(
                        $idligne,
                        $this->lines[$i]->description,
                        $this->lines[$i]->pu_ht,
                        $this->lines[$i]->tva_tx,
                        $this->lines[$i]->localtax1_tx,
                        $this->lines[$i]->localtax2_tx,
                        $this->lines[$i]->qty,
                        $this->lines[$i]->fk_product,
                        'HT',
                        (! empty($this->lines[$i]->info_bits)?$this->lines[$i]->info_bits:''),
                        $this->lines[$i]->product_type
                    );
                }
            }
            // Update total price
            $result=$this->update_price();
            if ($result > 0)
            {
            	$action='create';

				// Actions on extra fields (by external module or standard code)
				// FIXME le hook fait double emploi avec le trigger !!
				$hookmanager->initHooks(array('supplierinvoicedao'));
				$parameters=array('socid'=>$this->id);
				$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
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
				else if ($reshook < 0) $error++;
                // Call trigger
                $result=$this->call_trigger('BILL_SUPPLIER_CREATE',$user);
                if ($result < 0) $error++;
                // End call triggers

                if (! $error)
                {
                    $this->db->commit();
                    return $this->id;
                }
                else
                {
                    $this->db->rollback();
                    return -4;
                }
            }
            else
            {
                $this->error=$langs->trans('FailedToUpdatePrice');
                $this->db->rollback();
                return -3;
            }
        }
        else
        {
            if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {
                $this->error=$langs->trans('ErrorRefAlreadyExists');
                $this->db->rollback();
                return -1;
            }
            else
            {
                $this->error=$this->db->error();
                $this->db->rollback();
                return -2;
            }
        }
    }

    /**
     *    Load object in memory from database
     *
     *    @param	int		$id         Id supplier invoice
     *    @param	string	$ref		Ref supplier invoice
     *    @return   int        			<0 if KO, >0 if OK, 0 if not found
     */
    function fetch($id='',$ref='')
    {
        global $langs;

        $sql = "SELECT";
        $sql.= " t.rowid,";
		$sql.= " t.ref,";
        $sql.= " t.ref_supplier,";
        $sql.= " t.entity,";
        $sql.= " t.type,";
        $sql.= " t.fk_soc,";
        $sql.= " t.datec,";
        $sql.= " t.datef,";
        $sql.= " t.tms,";
        $sql.= " t.libelle,";
        $sql.= " t.paye,";
        $sql.= " t.amount,";
        $sql.= " t.remise,";
        $sql.= " t.close_code,";
        $sql.= " t.close_note,";
        $sql.= " t.tva,";
        $sql.= " t.localtax1,";
        $sql.= " t.localtax2,";
        $sql.= " t.total,";
        $sql.= " t.total_ht,";
        $sql.= " t.total_tva,";
        $sql.= " t.total_ttc,";
        $sql.= " t.fk_statut,";
        $sql.= " t.fk_user_author,";
        $sql.= " t.fk_user_valid,";
        $sql.= " t.fk_facture_source,";
        $sql.= " t.fk_projet,";
        $sql.= " t.fk_cond_reglement,";
        $sql.= " t.fk_account,";
        $sql.= " t.fk_mode_reglement,";
        $sql.= " t.date_lim_reglement,";
        $sql.= " t.note_private,";
        $sql.= " t.note_public,";
        $sql.= " t.model_pdf,";
        $sql.= " t.import_key,";
        $sql.= " t.extraparams,";
        $sql.= " cr.code as cond_reglement_code, cr.libelle as cond_reglement_libelle,";
        $sql.= " p.code as mode_reglement_code, p.libelle as mode_reglement_libelle,";
        $sql.= ' s.nom as socnom, s.rowid as socid,';
        $sql.= ' t.fk_incoterms, t.location_incoterms,';
        $sql.= " i.libelle as libelle_incoterms";
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as t';
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON (t.fk_soc = s.rowid)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_payment_term as cr ON (t.fk_cond_reglement = cr.rowid)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as p ON (t.fk_mode_reglement = p.id)";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON t.fk_incoterms = i.rowid';
        if ($id)  $sql.= " WHERE t.rowid=".$id;
        if ($ref) $sql.= " WHERE t.ref='".$this->db->escape($ref)."'";

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id					= $obj->rowid;
                $this->ref					= $obj->ref?$obj->ref:$obj->rowid;	// We take rowid if ref is empty for backward compatibility

                $this->ref_supplier			= $obj->ref_supplier;
                $this->entity				= $obj->entity;
                $this->type					= empty($obj->type)? self::TYPE_STANDARD:$obj->type;
                $this->fk_soc				= $obj->fk_soc;
                $this->datec				= $this->db->jdate($obj->datec);
                $this->date					= $this->db->jdate($obj->datef);
                $this->datep				= $this->db->jdate($obj->datef);
                $this->tms					= $this->db->jdate($obj->tms);
                $this->libelle				= $obj->libelle;
                $this->label				= $obj->libelle;
                $this->paye					= $obj->paye;
                $this->amount				= $obj->amount;
                $this->remise				= $obj->remise;
                $this->close_code			= $obj->close_code;
                $this->close_note			= $obj->close_note;
                $this->tva					= $obj->tva;
                $this->total_localtax1		= $obj->localtax1;
                $this->total_localtax2		= $obj->localtax2;
                $this->total				= $obj->total;
                $this->total_ht				= $obj->total_ht;
                $this->total_tva			= $obj->total_tva;
                $this->total_ttc			= $obj->total_ttc;
                $this->fk_statut			= $obj->fk_statut;
                $this->statut				= $obj->fk_statut;
                $this->fk_user_author		= $obj->fk_user_author;
                $this->author				= $obj->fk_user_author;
                $this->fk_user_valid		= $obj->fk_user_valid;
                $this->fk_facture_source	= $obj->fk_facture_source;
                $this->fk_project			= $obj->fk_projet;
	            $this->cond_reglement_id	= $obj->fk_cond_reglement;
	            $this->cond_reglement_code	= $obj->cond_reglement_code;
	            $this->cond_reglement		= $obj->cond_reglement_libelle;
	            $this->cond_reglement_doc	= $obj->cond_reglement_libelle;
                $this->fk_account           = $obj->fk_account;
	            $this->mode_reglement_id	= $obj->fk_mode_reglement;
	            $this->mode_reglement_code	= $obj->mode_reglement_code;
	            $this->mode_reglement		= $obj->mode_reglement_libelle;
                $this->date_echeance		= $this->db->jdate($obj->date_lim_reglement);
                $this->note					= $obj->note_private;	// deprecated
                $this->note_private			= $obj->note_private;
                $this->note_public			= $obj->note_public;
                $this->model_pdf			= $obj->model_pdf;
                $this->modelpdf			    = $obj->model_pdf;
                $this->import_key			= $obj->import_key;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;									
				$this->libelle_incoterms = $obj->libelle_incoterms;
				
                $this->extraparams			= (array) json_decode($obj->extraparams, true);

                $this->socid  = $obj->socid;
                $this->socnom = $obj->socnom;

                // Retreive all extrafield
                // fetch optionals attributes and labels
                require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
                $extrafields=new ExtraFields($this->db);
                $extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
                $this->fetch_optionals($this->id,$extralabels);

                if ($this->statut == 0) $this->brouillon = 1;

                $result=$this->fetch_lines();
                if ($result < 0)
                {
                    $this->error=$this->db->lasterror();
                    return -3;
                }

            }
            else
            {
                $this->error='Bill with id '.$id.' not found';
                dol_syslog(get_class($this).'::fetch '.$this->error);
                return 0;
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
     *	Load this->lines
     *
     *	@return     int         1 si ok, < 0 si erreur
     */
    function fetch_lines()
    {
        $sql = 'SELECT f.rowid, f.ref as ref_supplier, f.description, f.pu_ht, f.pu_ttc, f.qty, f.remise_percent, f.tva_tx';
        $sql.= ', f.localtax1_tx, f.localtax2_tx, f.total_localtax1, f.total_localtax2 ';
        $sql.= ', f.total_ht, f.tva as total_tva, f.total_ttc, f.fk_product, f.product_type, f.info_bits, f.rang, f.special_code, f.fk_parent_line';
        $sql.= ', p.rowid as product_id, p.ref as product_ref, p.label as label, p.description as product_desc';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn_det as f';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON f.fk_product = p.rowid';
        $sql.= ' WHERE fk_facture_fourn='.$this->id;
        $sql.= ' ORDER BY f.rang, f.rowid';

        dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
        $resql_rows = $this->db->query($sql);
        if ($resql_rows)
        {
            $num_rows = $this->db->num_rows($resql_rows);
            if ($num_rows)
            {
                $i = 0;
                while ($i < $num_rows)
                {
                    $obj = $this->db->fetch_object($resql_rows);

                    $this->lines[$i]					= new SupplierInvoiceLine($this->db);

                    $this->lines[$i]->id				= $obj->rowid;
                    $this->lines[$i]->rowid				= $obj->rowid;
                    $this->lines[$i]->description		= $obj->description;
                    $this->lines[$i]->product_ref		= $obj->product_ref;       // Internal reference
                    $this->lines[$i]->ref				= $obj->product_ref;       // deprecated.
                    $this->lines[$i]->ref_supplier		= $obj->ref_supplier;      // Reference product supplier TODO Rename field ref to ref_supplier into table llx_facture_fourn_det and llx_commande_fournisseurdet and update fields it into updateline
                    $this->lines[$i]->libelle			= $obj->label;             // This field may contains label of product (when invoice create from order)
                    $this->lines[$i]->product_desc		= $obj->product_desc;      // Description du produit
                    $this->lines[$i]->subprice			= $obj->pu_ht;
                    $this->lines[$i]->pu_ht				= $obj->pu_ht;
                    $this->lines[$i]->pu_ttc			= $obj->pu_ttc;
                    $this->lines[$i]->tva_tx			= $obj->tva_tx;
                    $this->lines[$i]->localtax1_tx		= $obj->localtax1_tx;
                    $this->lines[$i]->localtax2_tx		= $obj->localtax2_tx;
                    $this->lines[$i]->qty				= $obj->qty;
                    $this->lines[$i]->remise_percent    = $obj->remise_percent;
                    $this->lines[$i]->tva				= $obj->total_tva;
                    $this->lines[$i]->total_ht			= $obj->total_ht;
                    $this->lines[$i]->total_tva			= $obj->total_tva;
                    $this->lines[$i]->total_localtax1	= $obj->total_localtax1;
                    $this->lines[$i]->total_localtax2	= $obj->total_localtax2;
                    $this->lines[$i]->total_ttc			= $obj->total_ttc;
                    $this->lines[$i]->fk_product		= $obj->fk_product;
                    $this->lines[$i]->product_type		= $obj->product_type;
                    $this->lines[$i]->info_bits		    = $obj->info_bits;
                    $this->lines[$i]->fk_parent_line    = $obj->fk_parent_line;
                    $this->lines[$i]->special_code		= $obj->special_code;
                    $this->lines[$i]->rang       		= $obj->rang;

                    $i++;
                }
            }
            $this->db->free($resql_rows);
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -3;
        }
    }


    /**
     *  Update database
     *
     *  @param	User	$user            User that modify
     *  @param  int		$notrigger       0=launch triggers after, 1=disable triggers
     *  @return int 			         <0 if KO, >0 if OK
     */
    function update($user=null, $notrigger=0)
    {
        global $conf, $langs;
        $error=0;

        // Clean parameters
		if (isset($this->ref)) $this->ref=trim($this->ref);
        if (isset($this->ref_supplier)) $this->ref_supplier=trim($this->ref_supplier);
        if (isset($this->entity)) $this->entity=trim($this->entity);
        if (isset($this->type)) $this->type=trim($this->type);
        if (isset($this->fk_soc)) $this->fk_soc=trim($this->fk_soc);
        if (isset($this->libelle)) $this->libelle=trim($this->libelle);
        if (isset($this->paye)) $this->paye=trim($this->paye);
        if (isset($this->amount)) $this->amount=trim($this->amount);
        if (isset($this->remise)) $this->remise=trim($this->remise);
        if (isset($this->close_code)) $this->close_code=trim($this->close_code);
        if (isset($this->close_note)) $this->close_note=trim($this->close_note);
        if (isset($this->tva)) $this->tva=trim($this->tva);
        if (isset($this->localtax1)) $this->localtax1=trim($this->localtax1);
        if (isset($this->localtax2)) $this->localtax2=trim($this->localtax2);
        if (empty($this->total)) $this->total=0;
        if (empty($this->total_ht)) $this->total_ht=0;
        if (empty($this->total_tva)) $this->total_tva=0;
        //	if (isset($this->total_localtax1)) $this->total_localtax1=trim($this->total_localtax1);
        //	if (isset($this->total_localtax2)) $this->total_localtax2=trim($this->total_localtax2);
        if (isset($this->total_ttc)) $this->total_ttc=trim($this->total_ttc);
        if (isset($this->statut)) $this->statut=trim($this->statut);
        if (isset($this->author)) $this->author=trim($this->author);
        if (isset($this->fk_user_valid)) $this->fk_user_valid=trim($this->fk_user_valid);
        if (isset($this->fk_facture_source)) $this->fk_facture_source=trim($this->fk_facture_source);
        if (isset($this->fk_project)) $this->fk_project=trim($this->fk_project);
        if (isset($this->cond_reglement_id)) $this->cond_reglement_id=trim($this->cond_reglement_id);
        if (isset($this->note_private)) $this->note=trim($this->note_private);
        if (isset($this->note_public)) $this->note_public=trim($this->note_public);
        if (isset($this->model_pdf)) $this->model_pdf=trim($this->model_pdf);
        if (isset($this->import_key)) $this->import_key=trim($this->import_key);


        // Check parameters
        // Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn SET";
		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
        $sql.= " ref_supplier=".(isset($this->ref_supplier)?"'".$this->db->escape($this->ref_supplier)."'":"null").",";
        $sql.= " entity=".(isset($this->entity)?$this->entity:"null").",";
        $sql.= " type=".(isset($this->type)?$this->type:"null").",";
        $sql.= " fk_soc=".(isset($this->fk_soc)?$this->fk_soc:"null").",";
        $sql.= " datec=".(dol_strlen($this->datec)!=0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
        $sql.= " datef=".(dol_strlen($this->date)!=0 ? "'".$this->db->idate($this->date)."'" : 'null').",";
        if (dol_strlen($this->tms) != 0) $sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
        $sql.= " libelle=".(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
        $sql.= " paye=".(isset($this->paye)?$this->paye:"null").",";
        $sql.= " amount=".(isset($this->amount)?$this->amount:"null").",";
        $sql.= " remise=".(isset($this->remise)?$this->remise:"null").",";
        $sql.= " close_code=".(isset($this->close_code)?"'".$this->db->escape($this->close_code)."'":"null").",";
        $sql.= " close_note=".(isset($this->close_note)?"'".$this->db->escape($this->close_note)."'":"null").",";
        $sql.= " tva=".(isset($this->tva)?$this->tva:"null").",";
        $sql.= " localtax1=".(isset($this->localtax1)?$this->localtax1:"null").",";
        $sql.= " localtax2=".(isset($this->localtax2)?$this->localtax2:"null").",";
        $sql.= " total=".(isset($this->total)?$this->total:"null").",";
        $sql.= " total_ht=".(isset($this->total_ht)?$this->total_ht:"null").",";
        $sql.= " total_tva=".(isset($this->total_tva)?$this->total_tva:"null").",";
        $sql.= " total_ttc=".(isset($this->total_ttc)?$this->total_ttc:"null").",";
        $sql.= " fk_statut=".(isset($this->statut)?$this->statut:"null").",";
        $sql.= " fk_user_author=".(isset($this->author)?$this->author:"null").",";
        $sql.= " fk_user_valid=".(isset($this->fk_user_valid)?$this->fk_user_valid:"null").",";
        $sql.= " fk_facture_source=".(isset($this->fk_facture_source)?$this->fk_facture_source:"null").",";
        $sql.= " fk_projet=".(isset($this->fk_project)?$this->fk_project:"null").",";
        $sql.= " fk_cond_reglement=".(isset($this->cond_reglement_id)?$this->cond_reglement_id:"null").",";
        $sql.= " date_lim_reglement=".(dol_strlen($this->date_echeance)!=0 ? "'".$this->db->idate($this->date_echeance)."'" : 'null').",";
        $sql.= " note_private=".(isset($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null").",";
        $sql.= " note_public=".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null").",";
        $sql.= " model_pdf=".(isset($this->model_pdf)?"'".$this->db->escape($this->model_pdf)."'":"null").",";
        $sql.= " import_key=".(isset($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null")."";
        $sql.= " WHERE rowid=".$this->id;

        $this->db->begin();

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        if (! $error)
        {
            if (! $notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('BILL_SUPPLIER_UPDATE',$user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
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
     *	Delete invoice from database
     *
     *	@param     	int		$rowid      	Id of invoice to delete
     *	@return		int						<0 if KO, >0 if OK
     */
    function delete($rowid)
    {
        global $user,$langs,$conf;

        if (! $rowid) $rowid=$this->id;

        dol_syslog("FactureFournisseur::delete rowid=".$rowid, LOG_DEBUG);

        // TODO Test if there is at least on payment. If yes, refuse to delete.

        $error=0;
        $this->db->begin();

        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det WHERE fk_facture_fourn = '.$rowid.';';
        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn WHERE rowid = '.$rowid;
            dol_syslog(get_class($this)."::delete", LOG_DEBUG);
            $resql2 = $this->db->query($sql);
            if (! $resql2) {
            	$error++;
            }
        }
        else {
        	$error++;
        }

		if (! $error)
		{
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) $error++;
		}

        if (! $error)
        {
            // Call trigger
            $result=$this->call_trigger('BILL_SUPPLIER_DELETE',$user);
            if ($result < 0)
            {
        		$this->db->rollback();
        	    return -1;
        	}
        	// Fin appel triggers
        }

        if (! $error)
        {
        	// Delete linked object
        	$res = $this->deleteObjectLinked();
        	if ($res < 0) $error++;
        }

        if (! $error)
        {
        	// We remove directory
        	if ($conf->fournisseur->facture->dir_output)
        	{
        		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        		$ref = dol_sanitizeFileName($this->ref);
        		$dir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id, 2).$ref;
        		$file = $dir . "/" . $ref . ".pdf";
        		if (file_exists($file))
        		{
        			if (! dol_delete_file($file,0,0,0,$this)) // For triggers
        			{
        				$this->error='ErrorFailToDeleteFile';
        				$error++;
        			}
        		}
        		if (file_exists($dir))
        		{
        			$res=@dol_delete_dir_recursive($dir);

        			if (! $res)
        			{
        				$this->error='ErrorFailToDeleteDir';
        				$error++;
        			}
        		}
        	}
        }

        // Remove extrafields
        if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
        {
        	$result=$this->deleteExtraFields();
        	if ($result < 0)
        	{
        		$error++;
        		dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
        	}
        }

        if (! $error)
        {
        	dol_syslog(get_class($this)."::delete $this->id by $user->id", LOG_DEBUG);
        	$this->db->commit();
        	return 1;
        }
        else
        {
        	$this->error=$this->db->lasterror();
        	$this->db->rollback();
        	return -$error;
        }
    }


    /**
     *	Tag invoice as a payed invoice
     *
     *	@param      User	$user       Object user
     *	@return     int         		<0 si ko, >0 si ok
     */
    function set_paid($user)
    {
        global $conf,$langs;
        $error=0;

        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn';
        $sql.= ' SET paye = 1, fk_statut=2';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog("FactureFournisseur::set_paid", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Call trigger
            $result=$this->call_trigger('BILL_SUPPLIER_PAYED',$user);
            if ($result < 0) $error++;
            // End call triggers
        }
        else
        {
            $error++;
            $this->error=$this->db->error();
            dol_print_error($this->db);
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
     *	Tag la facture comme non payee completement + appel trigger BILL_UNPAYED
     *	Fonction utilisee quand un paiement prelevement est refuse,
     *	ou quand une facture annulee et reouverte.
     *
     *	@param      User	$user       Object user that change status
     *	@return     int         		<0 si ok, >0 si ok
     */
    function set_unpaid($user)
    {
        global $conf,$langs;
        $error=0;

        $this->db->begin();

        $sql = 'UPDATE '.MAIN_DB_PREFIX.'facture_fourn';
        $sql.= ' SET paye=0, fk_statut=1, close_code=null, close_note=null';
        $sql.= ' WHERE rowid = '.$this->id;

        dol_syslog("FactureFournisseur::set_unpaid", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Call trigger
            $result=$this->call_trigger('BILL_SUPPLIER_UNPAYED',$user);
            if ($result < 0) $error++;
            // End call triggers
        }
        else
        {
            $error++;
            $this->error=$this->db->lasterror();
            dol_syslog("FactureFournisseur::set_unpaid ".$this->error);
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
     *	Tag invoice as validated + call trigger BILL_VALIDATE
     *
     *	@param	User	$user           Object user that validate
     *	@param  string	$force_number   Reference to force on invoice
     *	@param	int		$idwarehouse	Id of warehouse for stock change
     *  @param	int		$notrigger		1=Does not execute triggers, 0= execuete triggers
     *	@return int 			        <0 if KO, =0 if nothing to do, >0 if OK
     */
    function validate($user, $force_number='', $idwarehouse=0, $notrigger=0)
    {
        global $conf,$langs;

        $error=0;

        // Protection
        if ($this->statut > 0)	// This is to avoid to validate twice (avoid errors on logs and stock management)
        {
            dol_syslog(get_class($this)."::validate no draft status", LOG_WARNING);
            return 0;
        }

        // Check parameters
        if (preg_match('/^'.preg_quote($langs->trans("CopyOf").' ').'/', $this->ref_supplier))
        {
            $this->error=$langs->trans("ErrorFieldFormat",$langs->transnoentities("RefSupplier"));
            return -1;
        }

        $this->db->begin();

        // Define new ref
        if ($force_number)
        {
            $num = $force_number;
        }
        else if (preg_match('/^[\(]?PROV/i', $this->ref))
        {
            $num = $this->getNextNumRef($this->client);
        }
        else
		{
            $num = $this->ref;
        }
        $this->newref = $num;

        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
        $sql.= " SET ref='".$num."', fk_statut = 1, fk_user_valid = ".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::validate", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            // Si on incrémente le produit principal et ses composants à la validation de facture fournisseur
            if (! $error && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL))
            {
                require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
                $langs->load("agenda");

                $cpt=count($this->lines);
                for ($i = 0; $i < $cpt; $i++)
                {
                    if ($this->lines[$i]->fk_product > 0)
                    {
                        $mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
                        // We increase stock for product
                        $up_ht_disc=$this->lines[$i]->pu_ht;
                        if (! empty($this->lines[$i]->remise_percent) && empty($conf->global->STOCK_EXCLUDE_DISCOUNT_FOR_PMP)) $up_ht_disc=price2num($up_ht_disc * (100 - $this->lines[$i]->remise_percent) / 100, 'MU');
                        $result=$mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $up_ht_disc, $langs->trans("InvoiceValidatedInDolibarr",$num));
                        if ($result < 0) { $error++; }
                    }
                }
            }

            // Triggers call
            if (! $error && empty($notrigger))
            {
                // Call trigger
                $result=$this->call_trigger('BILL_SUPPLIER_VALIDATE',$user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (! $error)
            {
	            $this->oldref = $this->ref;

            	// Rename directory if dir was a temporary ref
            	if (preg_match('/^[\(]?PROV/i', $this->ref))
            	{
            		// On renomme repertoire facture ($this->ref = ancienne ref, $num = nouvelle ref)
            		// in order not to lose the attached files
            		$oldref = dol_sanitizeFileName($this->ref);
            		$newref = dol_sanitizeFileName($num);

            		$dirsource = $conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id,2).$oldref;
            		$dirdest = $conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id,2).$newref;
            		if (file_exists($dirsource))
            		{
            			dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);

            			if (@rename($dirsource, $dirdest))
            			{
            				dol_syslog("Rename ok");
                            // Rename docs starting with $oldref with $newref
	                        $listoffiles=dol_dir_list($conf->fournisseur->facture->dir_output.'/'.get_exdir($this->id,2).$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
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
            }

            // Set new ref and define current statut
            if (! $error)
            {
            	$this->ref = $num;
            	$this->statut=1;
            	//$this->date_validation=$now; this is stored into log table
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
        else
        {
            $this->error=$this->db->error();
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *	Set draft status
     *
     *	@param	User	$user			Object user that modify
     *	@param	int		$idwarehouse	Id warehouse to use for stock change.
     *	@return	int						<0 if KO, >0 if OK
     */
    function set_draft($user, $idwarehouse=-1)
    {
        global $conf,$langs;

        $error=0;

        if ($this->statut == 0)
        {
            dol_syslog(get_class($this)."::set_draft already draft status", LOG_WARNING);
            return 0;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
        $sql.= " SET fk_statut = 0";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::set_draft", LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            // Si on incremente le produit principal et ses composants a la validation de facture fournisseur, on decremente
            if ($result >= 0 && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL))
            {
                require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
                $langs->load("agenda");

                $cpt=count($this->lines);
                for ($i = 0; $i < $cpt; $i++)
                {
                    if ($this->lines[$i]->fk_product > 0)
                    {
                        $mouvP = new MouvementStock($this->db);
                        // We increase stock for product
                        $result=$mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceBackToDraftInDolibarr",$this->ref));
                    }
                }
            }

            if ($error == 0)
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
            $this->error=$this->db->error();
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *	Ajoute une ligne de facture (associe a aucun produit/service predefini)
     *	Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
     *	de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
     *	par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,idprod)
     *	et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue).
     *
     *	@param    	string	$desc            	Description de la ligne
     *	@param    	double	$pu              	Prix unitaire (HT ou TTC selon price_base_type, > 0 even for credit note)
     *	@param    	double	$txtva           	Taux de tva force, sinon -1
     *	@param		double	$txlocaltax1		LocalTax1 Rate
     *	@param		double	$txlocaltax2		LocalTax2 Rate
     *	@param    	double	$qty             	Quantite
     *	@param    	int		$fk_product      	Id du produit/service predefini
     *	@param    	double	$remise_percent  	Pourcentage de remise de la ligne
     *	@param    	date	$date_start      	Date de debut de validite du service
     * 	@param    	date	$date_end        	Date de fin de validite du service
     * 	@param    	string	$ventil          	Code de ventilation comptable
     *	@param    	int		$info_bits			Bits de type de lines
     *	@param    	string	$price_base_type 	HT ou TTC
     *	@param		int		$type				Type of line (0=product, 1=service)
     *  @param      int		$rang            	Position of line
     *  @param		int		$notrigger			Disable triggers
	 *  @param		array	$array_options		extrafields array
     *	@return    	int             			>0 if OK, <0 if KO
     *
     *  FIXME Add field ref (that should be named ref_supplier) and label into update. For example can be filled when product line created from order.
     */
    function addline($desc, $pu, $txtva, $txlocaltax1, $txlocaltax2, $qty, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $price_base_type='HT', $type=0, $rang=-1, $notrigger=false, $array_options=0)
    {
        dol_syslog(get_class($this)."::addline $desc,$pu,$qty,$txtva,$fk_product,$remise_percent,$date_start,$date_end,$ventil,$info_bits,$price_base_type,$type", LOG_DEBUG);
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        // Clean parameters
        if (empty($remise_percent)) $remise_percent=0;
        if (empty($qty)) $qty=0;
        if (empty($info_bits)) $info_bits=0;
        if (empty($rang)) $rang=0;
        if (empty($ventil)) $ventil=0;
        if (empty($txtva)) $txtva=0;
        if (empty($txlocaltax1)) $txlocaltax1=0;
        if (empty($txlocaltax2)) $txlocaltax2=0;

        $remise_percent=price2num($remise_percent);
        $qty=price2num($qty);
        $pu=price2num($pu);
        $txtva=price2num($txtva);
        $txlocaltax1=price2num($txlocaltax1);
        $txlocaltax2=price2num($txlocaltax2);

        // Check parameters
        if ($type < 0) return -1;


        $this->db->begin();

        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'facture_fourn_det (fk_facture_fourn)';
        $sql.= ' VALUES ('.$this->id.')';
        dol_syslog(get_class($this)."::addline", LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $idligne = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture_fourn_det');

            $result=$this->updateline($idligne, $desc, $pu, $txtva, $txlocaltax1, $txlocaltax2, $qty, $fk_product, $price_base_type, $info_bits, $type, $remise_percent, true, '', '', $array_options);
            if ($result > 0)
            {
                $this->rowid = $idligne;

                if (! $notrigger)
                {
                    global $conf, $langs, $user;
                    // Call trigger
                    $result=$this->call_trigger('LINEBILL_SUPPLIER_CREATE',$user);
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
                dol_syslog("Error error=".$this->error, LOG_ERR);
                $this->db->rollback();
                return -1;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->db->rollback();
            return -2;
        }
    }

    /**
     * Update a line detail into database
     *
     * @param     	int			$id            		Id of line invoice
     * @param     	string		$desc         		Description of line
     * @param     	double		$pu          		Prix unitaire (HT ou TTC selon price_base_type)
     * @param     	double		$vatrate       		VAT Rate
     * @param		double		$txlocaltax1		LocalTax1 Rate
     * @param		double		$txlocaltax2		LocalTax2 Rate
     * @param     	double		$qty           		Quantity
     * @param     	int			$idproduct			Id produit
     * @param	  	double		$price_base_type	HT or TTC
     * @param	  	int			$info_bits			Miscellaneous informations of line
     * @param		int			$type				Type of line (0=product, 1=service)
     * @param     	double		$remise_percent  	Pourcentage de remise de la ligne
     * @param		int			$notrigger			Disable triggers
     * @param      	timestamp	$date_start     	Date start of service
     * @param      	timestamp   $date_end       	Date end of service
	 * @param		array		$array_options		extrafields array
     * @return    	int           					<0 if KO, >0 if OK
     */
    function updateline($id, $desc, $pu, $vatrate, $txlocaltax1=0, $txlocaltax2=0, $qty=1, $idproduct=0, $price_base_type='HT', $info_bits=0, $type=0, $remise_percent=0, $notrigger=false, $date_start='', $date_end='', $array_options=0)
    {
    	global $mysoc;
        dol_syslog(get_class($this)."::updateline $id,$desc,$pu,$vatrate,$qty,$idproduct,$price_base_type,$info_bits,$type,$remise_percent", LOG_DEBUG);
        include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

        $pu = price2num($pu);
        $qty  = price2num($qty);
		$remise_percent=price2num($remise_percent);

        // Check parameters
        if (! is_numeric($pu) || ! is_numeric($qty)) return -1;
        if ($type < 0) return -1;

        // Clean parameters
		if (empty($vatrate)) $vatrate=0;
        if (empty($txlocaltax1)) $txlocaltax1=0;
        if (empty($txlocaltax2)) $txlocaltax2=0;

        $txlocaltax1=price2num($txlocaltax1);
        $txlocaltax2=price2num($txlocaltax2);

        // Calcul du total TTC et de la TVA pour la ligne a partir de
        // qty, pu, remise_percent et txtva
        // TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
        // la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

        $localtaxes_type=getLocalTaxesFromRate($vatrate,0,$mysoc, $this->thirdparty);

        $tabprice = calcul_price_total($qty, $pu, $remise_percent, $vatrate, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type);
        $total_ht  = $tabprice[0];
        $total_tva = $tabprice[1];
        $total_ttc = $tabprice[2];
        $pu_ht  = $tabprice[3];
        $pu_tva = $tabprice[4];
        $pu_ttc = $tabprice[5];
        $total_localtax1 = $tabprice[9];
        $total_localtax2 = $tabprice[10];
        if (empty($info_bits)) $info_bits=0;

        if ($idproduct)
        {
            $product=new Product($this->db);
            $result=$product->fetch($idproduct);
            $product_type = $product->type;
        }
        else
        {
            $product_type = $type;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn_det SET";
        $sql.= " description ='".$this->db->escape($desc)."'";
        $sql.= ", pu_ht = ".price2num($pu_ht);
        $sql.= ", pu_ttc = ".price2num($pu_ttc);
        $sql.= ", qty = ".price2num($qty);
        $sql.= ", remise_percent = ".price2num($remise_percent);
        $sql.= ", tva_tx = ".price2num($vatrate);
        $sql.= ", localtax1_tx = ".price2num($txlocaltax1);
        $sql.= ", localtax2_tx = ".price2num($txlocaltax2);
		$sql.= ", localtax1_type = '".$localtaxes_type[0]."'";
		$sql.= ", localtax2_type = '".$localtaxes_type[2]."'";
        $sql.= ", total_ht = ".price2num($total_ht);
        $sql.= ", tva= ".price2num($total_tva);
        $sql.= ", total_localtax1= ".price2num($total_localtax1);
        $sql.= ", total_localtax2= ".price2num($total_localtax2);
        $sql.= ", total_ttc = ".price2num($total_ttc);
        if ($idproduct) $sql.= ", fk_product = ".$idproduct;
        else $sql.= ", fk_product = null";
        $sql.= ", product_type = ".$product_type;
        $sql.= ", info_bits = ".$info_bits;
        $sql.= " WHERE rowid = ".$id;

        dol_syslog(get_class($this)."::updateline", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->rowid = $id;

        	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$linetmp = new SupplierInvoiceLine($this->db);
				$linetmp->id=$this->rowid;
				$linetmp->array_options = $array_options;
				$result=$linetmp->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

            if (! $error && ! $notrigger)
            {
                global $conf, $langs, $user;
                // Call trigger
                $result=$this->call_trigger('LINEBILL_SUPPLIER_UPDATE',$user);
                if ($result < 0)
                {
                    $this->db->rollback();
                    return -1;
                }
                // End call triggers
            }

            // Update total price into invoice record
            $result=$this->update_price('','auto');

			if (! $error)
			{
	            $this->db->commit();
            	return $result;
			}
			else
			{
	            $this->db->rollback();
            	return -1;
			}
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     * 	Delete a detail line from database
     *
     * 	@param  int		$rowid      	Id of line to delete
     *	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
     * 	@return	int						<0 if KO, >0 if OK
     */
    function deleteline($rowid, $notrigger=0)
    {
    	global $user, $langs, $conf;

        if (! $rowid) $rowid=$this->id;

        dol_syslog(get_class($this)."::delete rowid=".$rowid, LOG_DEBUG);

        $error=0;
    	$this->db->begin();

		if (! $error && ! $notrigger)
        {
            // Call trigger
            $result=$this->call_trigger('LINEBILL_SUPPLIER_DELETE',$user);
            if ($result < 0) $error++;
            // End call triggers
        }

    	if (! $error)
    	{
	        // Supprime ligne
	        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture_fourn_det ';
	        $sql.= ' WHERE rowid = '.$rowid;
        	dol_syslog(get_class($this)."::delete", LOG_DEBUG);
	        $resql = $this->db->query($sql);
	        if (! $resql)
	        {
	        	$error++;
	        	$this->error=$this->db->lasterror();
	        }
    	}

    	if (! $error)
    	{
	        // Mise a jour prix facture
    		$result=$this->update_price();
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
     *	Charge les informations d'ordre info dans l'objet facture
     *
     *	@param  int		$id       	Id de la facture a charger
     *	@return	void
     */
    function info($id)
    {
        $sql = 'SELECT c.rowid, datec, tms as datem,';
        $sql.= ' fk_user_author, fk_user_valid';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as c';
        $sql.= ' WHERE c.rowid = '.$id;

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
                    $this->user_creation     = $cuser;
                }
                if ($obj->fk_user_valid)
                {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_valid);
                    $this->user_validation = $vuser;
                }
                $this->date_creation     = $obj->datec;
                $this->date_modification = $obj->datem;
                //$this->date_validation   = $obj->datev; Should be stored in log table
            }
            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *	@param      User	$user       Object user
     *	@return WorkboardResponse|int <0 if KO, WorkboardResponse if OK
     */
    function load_board($user)
    {
        global $conf, $user, $langs;

        $sql = 'SELECT ff.rowid, ff.date_lim_reglement as datefin';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as ff';
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= ' WHERE ff.paye=0';
        $sql.= ' AND ff.fk_statut > 0';
        $sql.= " AND ff.entity = ".$conf->entity;
        if ($user->societe_id) $sql.=' AND ff.fk_soc = '.$user->societe_id;
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND ff.fk_soc = sc.fk_soc AND sc.fk_user = ".$user->id;

        $resql=$this->db->query($sql);
        if ($resql)
        {
	        $langs->load("bills");
	        $now=dol_now();

	        $response = new WorkboardResponse();
	        $response->warning_delay=$conf->facture->fournisseur->warning_delay/60/60/24;
	        $response->label=$langs->trans("SupplierBillsToPay");
	        $response->url=DOL_URL_ROOT.'/fourn/facture/list.php?filtre=paye:0';
	        $response->img=img_object($langs->trans("Bills"),"bill");

            while ($obj=$this->db->fetch_object($resql))
            {
                $response->nbtodo++;
                if (! empty($obj->datefin) && $this->db->jdate($obj->datefin) < ($now - $conf->facture->fournisseur->warning_delay)) {
	                $response->nbtodolate++;
                }
            }
            $this->db->free($resql);
            return $response;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *	Return clicable name (with picto eventually)
     *
     *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
     *	@param		string	$option			Sur quoi pointe le lien
     * 	@param		int		$max			Max length of shown ref
     * 	@return		string					Chaine avec URL
     */
    function getNomUrl($withpicto=0,$option='',$max=0)
    {
        global $langs;

        $result='';
        $label = '<u>' . $langs->trans("ShowSupplierInvoice") . '</u>';
        if (! empty($this->ref))
            $label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        if (! empty($this->ref_supplier))
            $label.= '<br><b>' . $langs->trans('RefSupplier') . ':</b> ' . $this->ref_supplier;
        if (! empty($this->total_ht))
            $label.= '<br><b>' . $langs->trans('AmountHT') . ':</b> ' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_tva))
            $label.= '<br><b>' . $langs->trans('TVA') . ':</b> ' . price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_ttc))
            $label.= '<br><b>' . $langs->trans('AmountTTC') . ':</b> ' . price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);

        if ($option == 'document')
        {
            $link = '<a href="'.DOL_URL_ROOT.'/fourn/facture/document.php?facid='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
            $linkend='</a>';
        }
        else
        {
            $link = '<a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
            $linkend='</a>';
        }

        $ref=$this->ref;
        if (empty($ref)) $ref=$this->id;

        if ($withpicto) $result.=($link.img_object($label, 'bill', 'class="classfortooltip"').$linkend.' ');
        $result.=$link.($max?dol_trunc($ref,$max):$ref).$linkend;
        return $result;
    }

	 /**
      *      Return next reference of supplier invoice not already used (or last reference)
      *      according to numbering module defined into constant INVOICE_SUPPLIER_ADDON_NUMBER
      *
      *      @param	   Societe		$soc		Thirdparty object
      *      @param    string		$mode		'next' for next value or 'last' for last value
      *      @return   string					free ref or last ref
      */
    function getNextNumRef($soc,$mode='next')
    {
        global $db, $langs, $conf;
        $langs->load("orders");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->INVOICE_SUPPLIER_ADDON_NUMBER)) $conf->global->INVOICE_SUPPLIER_ADDON_NUMBER='mod_facture_fournisseur_cactus';

        $mybool=false;

        $file = $conf->global->INVOICE_SUPPLIER_ADDON_NUMBER.".php";
        $classname = $conf->global->INVOICE_SUPPLIER_ADDON_NUMBER;

        // Include file with class
        $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

        foreach ($dirmodels as $reldir) {

            $dir = dol_buildpath($reldir."core/modules/supplier_invoice/");

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
        $numref = $obj->getNumRef($soc,$this,$mode);

        if ($numref != "")
        {
        	return $numref;
        }
        else
       {
        	//dol_print_error($db,get_class($this)."::getNextNumRef ".$obj->error);
        	return false;
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
        global $langs,$conf;
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

        $now = dol_now();

        // Charge tableau des produits prodids
        $prodids = array();

        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."product";
        $sql.= " WHERE entity IN (".getEntity('product', 1).")";

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
        $this->ref_supplier = 'SUPPLIER_REF_SPECIMEN';
        $this->specimen=1;
        $this->socid = 1;
        $this->date = $now;
        $this->date_lim_reglement=$this->date+3600*24*30;
        $this->cond_reglement_code = 'RECEP';
        $this->mode_reglement_code = 'CHQ';
        $this->note_public='This is a comment (public)';
        $this->note_private='This is a comment (private)';
        // Lines
        $nbp = 5;
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line=new FactureLigne($this->db);
            $line->desc=$langs->trans("Description")." ".$xnbp;
            $line->qty=1;
            $line->subprice=100;
            $line->pu_ht=100;		// the canelle template use pu_ht and not subprice
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
    			$line->remise_percent=0;
			}

			$prodid = rand(1, $num_prods);
            $line->fk_product=$prodids[$prodid];
            $line->product_type=0;

            $this->lines[$xnbp]=$line;

    		$this->total_ht       += $line->total_ht;
    		$this->total_tva      += $line->total_tva;
    		$this->total_ttc      += $line->total_ttc;

            $xnbp++;
        }

        $this->amount_ht      = $xnbp*100;
        $this->total_ht       = $xnbp*100;
        $this->total_tva      = $xnbp*19.6;
        $this->total_ttc      = $xnbp*119.6;
    }

	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @return         int     <0 if KO, >0 if OK
	 */
	function load_state_board()
	{
		global $conf, $user;

		$this->nb=array();

		$clause = "WHERE";

		$sql = "SELECT count(f.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
		if (!$user->rights->societe->client->voir && !$user->societe_id)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql.= " WHERE sc.fk_user = " .$user->id;
			$clause = "AND";
		}
		$sql.= " ".$clause." f.entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["supplier_invoices"]=$obj->nb;
			}
            $this->db->free($resql);
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}

    /**
     *	Load an object from its id and create a new one in database
     *
     *	@param      int		$fromid     	Id of object to clone
     *	@param		int		$invertdetail	Reverse sign of amounts for lines
     * 	@return		int						New id of clone
     */
    function createFromClone($fromid,$invertdetail=0)
    {
        global $user,$langs;

        $error=0;

        $object=new FactureFournisseur($this->db);

		$object->context['createfromclone'] = 'createfromclone';

		$this->db->begin();

        // Load source object
        $object->fetch($fromid);
        $object->id=0;
        $object->statut=0;

        // Clear fields
        $object->ref_supplier=$langs->trans("CopyOf").' '.$object->ref_supplier;
        $object->author             = $user->id;
        $object->user_valid         = '';
        $object->fk_facture_source  = 0;
        $object->date_creation      = '';
        $object->date_validation    = '';
        $object->date               = '';
        $object->date_echeance      = '';
        $object->ref_client         = '';
        $object->close_code         = '';
        $object->close_note         = '';

        // Loop on each line of new invoice
        foreach($object->lines as $i => $line)
        {
            if (isset($object->lines[$i]->info_bits) && ($object->lines[$i]->info_bits & 0x02) == 0x02)	// We do not clone line of discounts
            {
                unset($object->lines[$i]);
            }
        }

        // Create clone
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
	 *	Create a document onto disk according to template model.
	 *
	 *	@param	    string		$modele			Force template to use ('' to not force)
	 *	@param		Translate	$outputlangs	Object lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf, $user, $langs;

		$langs->load("suppliers");

		// Set the model on the model name to use
		if (! dol_strlen($modele))
		{
			if (! empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF))
			{
				$modele = $conf->global->INVOICE_SUPPLIER_ADDON_PDF;
			}
			else
			{
				$modele = 'canelle';
			}
		}

		$modelpath = "core/modules/supplier_invoice/pdf/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	/**
	 * Returns the rights used for this class
	 * @return stdClass
	 */
	public function getRights()
	{
		global $user;

		return $user->rights->fournisseur->facture;
	}


}



/**
 *  Class to manage line invoices
 */
class SupplierInvoiceLine extends CommonInvoice
{
	public $element='facture_fourn_det';
	public $table_element='facture_fourn_det';

	var $oldline;

	public $ref;
	public $product_ref;
	public $ref_supplier;
	public $libelle;
	public $product_desc;

	/**
	 * Unit price before taxes
	 * @var float
	 * @deprecated Use $subprice
	 */
	public $pu_ht;

	/**
	 * Unit price included taxes
	 * @var float
	 */
	public $pu_ttc;

	/**
	 * Total VAT amount
	 * @var float
	 * @deprecated Use $total_tva instead
	 */
	public $tva;

	/**
	 * Id of the corresponding supplier invoice
	 * @var int
	 */
	var $fk_facture_fourn;

	/**
	 * Product label
	 * @var string
	 */
	var $label;				// deprecated

	/**
	 * Description of the line
	 * @var string
	 */
	var $description;

	var $skip_update_total; // Skip update price total for special lines

	/**
	 * @var int Situation advance percentage
	 */
	public $situation_percent;

	/**
	 * @var int Previous situation line id reference
	 */
	public $fk_prev_id;

	/**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db= $db;
    }


}

