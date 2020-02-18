<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file       htdocs/core/class/discount.class.php
 * 		\ingroup    core propal facture commande
 *		\brief      File of class to manage absolute discounts
 */


/**
 *		Class to manage absolute discounts
 */
class DiscountAbsolute
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
	 * @var string Error code (or message)
	 */
	public $error;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors=array();

	/**
	 * @var int ID discount
	 */
	public $id;

    /**
	 * @var int Thirdparty ID
	 */
    public $fk_soc;

    public $discount_type;		// 0 => customer discount, 1 => supplier discount
    public $amount_ht;			//
    public $amount_tva;			//
    public $amount_ttc;			//
    public $multicurrency_amount_ht;
    public $multicurrency_amount_tva;
    public $multicurrency_amount_ttc;
    // Vat rate
    public $tva_tx;

    /**
	 * @var int User ID Id utilisateur qui accorde la remise
	 */
	public $fk_user;

    /**
	 * @var string description
	 */
	public $description;

    /**
     * Date creation record (datec)
     *
     * @var integer
     */
    public $datec;

    /**
     * @var int ID invoice line when a discount is used into an invoice line (for absolute discounts)
     */
    public $fk_facture_line;

    /**
     * @var int ID invoice when a discount line is used into an invoice (for credit note)
     */
    public $fk_facture;

    /**
     * @var int ID credit note or deposit used to create the discount
     */
    public $fk_facture_source;
    public $ref_facture_source;	    		// Ref credit note or deposit used to create the discount
	public $type_facture_source;

    public $fk_invoice_supplier_source;
    public $ref_invoice_supplier_source;	// Ref credit note or deposit used to create the discount
    public $type_invoice_supplier_source;

    /**
     *	Constructor
     *
     *  @param  	DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }


    /**
     *	Load object from database into memory
     *
     *  @param      int		$rowid       					id discount to load
     *  @param      int		$fk_facture_source				fk_facture_source
     *  @param		int		$fk_invoice_supplier_source		fk_invoice_supplier_source
     *	@return		int										<0 if KO, =0 if not found, >0 if OK
     */
    public function fetch($rowid, $fk_facture_source = 0, $fk_invoice_supplier_source = 0)
    {
    	global $conf;

        // Check parameters
        if (! $rowid && ! $fk_facture_source && ! $fk_invoice_supplier_source)
        {
            $this->error='ErrorBadParameters';
            return -1;
        }

        $sql = "SELECT sr.rowid, sr.fk_soc, sr.discount_type,";
        $sql.= " sr.fk_user,";
        $sql.= " sr.amount_ht, sr.amount_tva, sr.amount_ttc, sr.tva_tx,";
        $sql.= " sr.multicurrency_amount_ht, sr.multicurrency_amount_tva, sr.multicurrency_amount_ttc,";
        $sql.= " sr.fk_facture_line, sr.fk_facture, sr.fk_facture_source, sr.fk_invoice_supplier_line, sr.fk_invoice_supplier, sr.fk_invoice_supplier_source, sr.description,";
        $sql.= " sr.datec,";
        $sql.= " f.ref as ref_facture_source, f.type as type_facture_source,";
        $sql.= " fsup.ref as ref_invoice_supplier_source, fsup.type as type_invoice_supplier_source";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as sr";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON sr.fk_facture_source = f.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fsup ON sr.fk_invoice_supplier_source = fsup.rowid";
        $sql.= " WHERE sr.entity IN (".getEntity('invoice').")";
        if ($rowid) $sql.= " AND sr.rowid=".$rowid;
        if ($fk_facture_source) $sql.= " AND sr.fk_facture_source=".$fk_facture_source;
        if ($fk_invoice_supplier_source) $sql.= " AND sr.fk_invoice_supplier_source=".$fk_invoice_supplier_source;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->fk_soc = $obj->fk_soc;
                $this->discount_type = $obj->discount_type;

                $this->amount_ht = $obj->amount_ht;
                $this->amount_tva = $obj->amount_tva;
                $this->amount_ttc = $obj->amount_ttc;

                $this->multicurrency_amount_ht = $this->multicurrency_subprice = $obj->multicurrency_amount_ht;
                $this->multicurrency_amount_tva = $obj->multicurrency_amount_tva;
                $this->multicurrency_amount_ttc = $obj->multicurrency_amount_ttc;

                $this->tva_tx = $obj->tva_tx;
                $this->fk_user = $obj->fk_user;
                $this->fk_facture_line = $obj->fk_facture_line;
                $this->fk_facture = $obj->fk_facture;
                $this->fk_facture_source = $obj->fk_facture_source;		// Id credit note or deposit source
                $this->ref_facture_source = $obj->ref_facture_source;	// Ref credit note or deposit  source
                $this->type_facture_source = $obj->type_facture_source;	// Type credit note or deposit  source
                $this->fk_invoice_supplier_line = $obj->fk_invoice_supplier_line;
                $this->fk_invoice_supplier = $obj->fk_invoice_supplier;
                $this->fk_invoice_supplier_source = $obj->fk_invoice_supplier_source;		// Id credit note or deposit source
                $this->ref_invoice_supplier_source = $obj->ref_invoice_supplier_source;		// Ref credit note or deposit  source
                $this->type_invoice_supplier_source = $obj->type_invoice_supplier_source;	// Type credit note or deposit  source
                $this->description = $obj->description;
                $this->datec = $this->db->jdate($obj->datec);

                $this->db->free($resql);
                return 1;
            }
            else
            {
                $this->db->free($resql);
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
     *      Create a discount into database
     *
     *      @param      User	$user       User that create
     *      @return     int         		<0 if KO, >0 if OK
     */
    public function create($user)
    {
        global $conf, $langs;

        // Clean parameters
        $this->amount_ht=price2num($this->amount_ht);
        $this->amount_tva=price2num($this->amount_tva);
        $this->amount_ttc=price2num($this->amount_ttc);

        $this->tva_tx=price2num($this->tva_tx);

        $this->multicurrency_amount_ht=price2num($this->multicurrency_amount_ht);
        $this->multicurrency_amount_tva=price2num($this->multicurrency_amount_tva);
        $this->multicurrency_amount_ttc=price2num($this->multicurrency_amount_ttc);

        if (empty($this->multicurrency_amount_ht)) $this->multicurrency_amount_ht=0;
        if (empty($this->multicurrency_amount_tva)) $this->multicurrency_amount_tva=0;
        if (empty($this->multicurrency_amount_ttc)) $this->multicurrency_amount_ttc=0;

        // Check parameters
        if (empty($this->description))
        {
            $this->error='BadValueForPropertyDescription';
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
            return -1;
        }

        $userid = $user->id;
		if (! ($userid > 0))		// For example when record is saved into an anonymous context with a not loaded object $user.
		{
			include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$tmpinvoice = new Facture($this->db);
			$tmpinvoice->fetch($this->fk_facture_source);
			$userid = $tmpinvoice->fk_user_author;	// We use the author of invoice
		}

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise_except";
        $sql.= " (entity, datec, fk_soc, discount_type, fk_user, description,";
        $sql.= " amount_ht, amount_tva, amount_ttc, tva_tx,";
        $sql.= " multicurrency_amount_ht, multicurrency_amount_tva, multicurrency_amount_ttc,";
        $sql.= " fk_facture_source, fk_invoice_supplier_source";
        $sql.= ")";
        $sql.= " VALUES (".$conf->entity.", '".$this->db->idate($this->datec!=''?$this->datec:dol_now())."', ".$this->fk_soc.", ".(empty($this->discount_type)?0:intval($this->discount_type)).", ".$userid.", '".$this->db->escape($this->description)."',";
        $sql.= " ".$this->amount_ht.", ".$this->amount_tva.", ".$this->amount_ttc.", ".$this->tva_tx.",";
        $sql.= " ".$this->multicurrency_amount_ht.", ".$this->multicurrency_amount_tva.", ".$this->multicurrency_amount_ttc.", ";
        $sql.= " ".($this->fk_facture_source ? "'".$this->db->escape($this->fk_facture_source)."'":"null").",";
        $sql.= " ".($this->fk_invoice_supplier_source ? "'".$this->db->escape($this->fk_invoice_supplier_source)."'":"null");
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."societe_remise_except");
            return $this->id;
        }
        else
        {
            $this->error=$this->db->lasterror().' - sql='.$sql;
            return -1;
        }
    }


    /**
     *  Delete object in database. If fk_facture_source is defined, we delete all familiy with same fk_facture_source. If not, only with id is removed
     *
     *  @param      User    $user       Object of user asking to delete
     *  @return     int                 <0 if KO, >0 if OK
     */
    public function delete($user)
    {
        global $conf, $langs;

        // Check if we can remove the discount
        if ($this->fk_facture_source)
        {
            $sql="SELECT COUNT(rowid) as nb";
            $sql.=" FROM ".MAIN_DB_PREFIX."societe_remise_except";
            $sql.=" WHERE (fk_facture_line IS NOT NULL";	// Not used as absolute simple discount
            $sql.=" OR fk_facture IS NOT NULL)"; 			// Not used as credit note and not used as deposit
            $sql.=" AND fk_facture_source = ".$this->fk_facture_source;
            //$sql.=" AND rowid != ".$this->id;

            dol_syslog(get_class($this)."::delete Check if we can remove discount", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $obj = $this->db->fetch_object($resql);
                if ($obj->nb > 0)
                {
                    $this->error='ErrorThisPartOrAnotherIsAlreadyUsedSoDiscountSerieCantBeRemoved';
                    return -2;
                }
            }
            else
            {
                dol_print_error($this->db);
                return -1;
            }
        }

        // Check if we can remove the discount
        if ($this->fk_invoice_supplier_source)
        {
            $sql="SELECT COUNT(rowid) as nb";
            $sql.=" FROM ".MAIN_DB_PREFIX."societe_remise_except";
        	$sql.=" WHERE (fk_invoice_supplier_line IS NOT NULL";	// Not used as absolute simple discount
        	$sql.=" OR fk_invoice_supplier IS NOT NULL)"; 			// Not used as credit note and not used as deposit
        	$sql.=" AND fk_invoice_supplier_source = ".$this->fk_invoice_supplier_source;
        	//$sql.=" AND rowid != ".$this->id;

        	dol_syslog(get_class($this)."::delete Check if we can remove discount", LOG_DEBUG);
        	$resql=$this->db->query($sql);
        	if ($resql)
        	{
        		$obj = $this->db->fetch_object($resql);
        		if ($obj->nb > 0)
        		{
        			$this->error='ErrorThisPartOrAnotherIsAlreadyUsedSoDiscountSerieCantBeRemoved';
        			return -2;
        		}
        	}
        	else
        	{
        		dol_print_error($this->db);
        		return -1;
        	}
        }

        $this->db->begin();

        // Delete but only if not used
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_remise_except ";
        if ($this->fk_facture_source) $sql.= " WHERE fk_facture_source = ".$this->fk_facture_source;	// Delete all lines of same serie
        elseif ($this->fk_invoice_supplier_source) $sql.= " WHERE fk_invoice_supplier_source = ".$this->fk_invoice_supplier_source;	// Delete all lines of same serie
        else $sql.= " WHERE rowid = ".$this->id;	// Delete only line
        $sql.= " AND (fk_facture_line IS NULL";	// Not used as absolute simple discount
        $sql.= " AND fk_facture IS NULL)";		// Not used as credit note and not used as deposit
        $sql.= " AND (fk_invoice_supplier_line IS NULL";	// Not used as absolute simple discount
        $sql.= " AND fk_invoice_supplier IS NULL)";		// Not used as credit note and not used as deposit

        dol_syslog(get_class($this)."::delete Delete discount", LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            // If source of discount was a credit note or deposit, we change source statut.
            if ($this->fk_facture_source)
            {
                $sql = "UPDATE ".MAIN_DB_PREFIX."facture";
                $sql.=" set paye=0, fk_statut=1";
                $sql.=" WHERE (type = 2 or type = 3) AND rowid=".$this->fk_facture_source;

                dol_syslog(get_class($this)."::delete Update credit note or deposit invoice statut", LOG_DEBUG);
                $result=$this->db->query($sql);
                if ($result)
                {
                    $this->db->commit();
                    return 1;
                }
                else
                {
                    $this->error=$this->db->lasterror();
                    $this->db->rollback();
                    return -1;
                }
            }
            elseif($this->fk_invoice_supplier_source) {

            	$sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn";
            	$sql.=" set paye=0, fk_statut=1";
            	$sql.=" WHERE (type = 2 or type = 3) AND rowid=".$this->fk_invoice_supplier_source;

            	dol_syslog(get_class($this)."::delete Update credit note or deposit invoice statut", LOG_DEBUG);
            	$result=$this->db->query($sql);
            	if ($result)
            	{
            		$this->db->commit();
            		return 1;
            	}
            	else
            	{
            		$this->error=$this->db->lasterror();
            		$this->db->rollback();
            		return -1;
            	}
            }
            else
            {
                $this->db->commit();
                return 1;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }



    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Link the discount to a particular invoice line or a particular invoice.
     *	When discount is a global discount used as an invoice line, we link using rowidline.
     *	When discount is from a credit note used to reduce payment of an invoice, we link using rowidinvoice
     *
     *	@param		int		$rowidline		Invoice line id (To use discount into invoice lines)
     *	@param		int		$rowidinvoice	Invoice id (To use discount as a credit note to reduc payment of invoice)
     *	@return		int						<0 if KO, >0 if OK
     */
    public function link_to_invoice($rowidline, $rowidinvoice)
    {
        // phpcs:enable
        // Check parameters
        if (! $rowidline && ! $rowidinvoice)
        {
            $this->error='ErrorBadParameters';
            return -1;
        }
        if ($rowidline && $rowidinvoice)
        {
            $this->error='ErrorBadParameters';
            return -2;
        }

        $sql ="UPDATE ".MAIN_DB_PREFIX."societe_remise_except";
        if(! empty($this->discount_type)) {
        	if ($rowidline)    $sql.=" SET fk_invoice_supplier_line = ".$rowidline;
        	if ($rowidinvoice) $sql.=" SET fk_invoice_supplier = ".$rowidinvoice;
        } else {
        	if ($rowidline)    $sql.=" SET fk_facture_line = ".$rowidline;
        	if ($rowidinvoice) $sql.=" SET fk_facture = ".$rowidinvoice;
        }
        $sql.=" WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::link_to_invoice", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
        	if(! empty($this->discount_type)) {
        		$this->fk_invoice_supplier_line=$rowidline;
        		$this->fk_invoice_supplier=$rowidinvoice;
        	} else {
        		$this->fk_facture_line=$rowidline;
        		$this->fk_facture=$rowidinvoice;
        	}
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -3;
        }
    }


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Link the discount to a particular invoice line or a particular invoice.
     *	Do not call this if discount is linked to a reconcialiated invoice
     *
     *	@return		int							<0 if KO, >0 if OK
     */
    public function unlink_invoice()
    {
        // phpcs:enable
        $sql ="UPDATE ".MAIN_DB_PREFIX."societe_remise_except";
        if (! empty($this->discount_type)) {
            $sql.=" SET fk_invoice_supplier_line = NULL, fk_invoice_supplier = NULL";
        } else {
			$sql.=" SET fk_facture_line = NULL, fk_facture = NULL";
		}
        $sql.=" WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::unlink_invoice", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -3;
        }
    }


    /**
     *  Return amount (with tax) of discounts currently available for a company, user or other criteria
     *
     *	@param		Societe		$company		Object third party for filter
     *	@param		User		$user			Filtre sur un user auteur des remises
     * 	@param		string		$filter			Filtre autre
     * 	@param		int			$maxvalue		Filter on max value for discount
     *  @param      int			$discount_type  0 => customer discount, 1 => supplier discount
     * 	@return		int						<0 if KO, amount otherwise
     */
    public function getAvailableDiscounts($company = '', $user = '', $filter = '', $maxvalue = 0, $discount_type = 0)
    {
        global $conf;

        dol_syslog(get_class($this)."::getAvailableDiscounts discount_type=".$discount_type, LOG_DEBUG);

        $sql  = "SELECT SUM(rc.amount_ttc) as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
        $sql.= " WHERE rc.entity = " . $conf->entity;
        $sql.= " AND rc.discount_type=".intval($discount_type);
        if (! empty($discount_type)) {
        	$sql.= " AND (rc.fk_invoice_supplier IS NULL AND rc.fk_invoice_supplier_line IS NULL)"; // Available from supplier
        } else {
        	$sql.= " AND (rc.fk_facture IS NULL AND rc.fk_facture_line IS NULL)"; // Available to customer
        }
        if (is_object($company)) $sql.= " AND rc.fk_soc = ".$company->id;
        if (is_object($user))    $sql.= " AND rc.fk_user = ".$user->id;
        if ($filter)   $sql.=' AND ('.$filter.')';
        if ($maxvalue) $sql.=' AND rc.amount_ttc <= '.price2num($maxvalue);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            //while ($obj)
            //{
            //print 'zz'.$obj->amount;
            //$obj = $this->db->fetch_object($resql);
            //}
            return $obj->amount;
        }
        return -1;
    }


    /**
     *  Return amount (with tax) of all deposits invoices used by invoice as a payment.
     *  Should always be empty, except if option FACTURE_DEPOSITS_ARE_JUST_PAYMENTS is on (not recommended).
     *
     *	@param		CommonInvoice	$invoice		Object invoice (customer of supplier)
     *  @param 		int 		    $multicurrency 	Return multicurrency_amount instead of amount
     *	@return		int				     			<0 if KO, Sum of credit notes and deposits amount otherwise
     */
    public function getSumDepositsUsed($invoice, $multicurrency = 0)
    {
        dol_syslog(get_class($this)."::getSumDepositsUsed", LOG_DEBUG);

        if ($invoice->element == 'facture' || $invoice->element == 'invoice')
        {
            $sql = 'SELECT sum(rc.amount_ttc) as amount, sum(rc.multicurrency_amount_ttc) as multicurrency_amount';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc, '.MAIN_DB_PREFIX.'facture as f';
            $sql.= ' WHERE rc.fk_facture_source=f.rowid AND rc.fk_facture = '.$invoice->id;
            $sql.= ' AND f.type = 3';
        }
        elseif ($invoice->element == 'invoice_supplier')
        {
            $sql = 'SELECT sum(rc.amount_ttc) as amount, sum(rc.multicurrency_amount_ttc) as multicurrency_amount';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc, '.MAIN_DB_PREFIX.'facture_fourn as f';
            $sql.= ' WHERE rc.fk_invoice_supplier_source=f.rowid AND rc.fk_invoice_supplier = '.$invoice->id;
            $sql.= ' AND f.type = 3';
        }
        else
        {
            $this->error=get_class($this)."::getSumDepositsUsed was called with a bad object as a first parameter";
            dol_print_error($this->error);
            return -1;
        }

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($multicurrency) return $obj->multicurrency_amount;
			else return $obj->amount;
        }
        else
        {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     *  Return amount (with tax) of all credit notes invoices + excess received used by invoice as a payment
     *
     *	@param      CommonInvoice	  $invoice	    	Object invoice
     *	@param      int			      $multicurrency	Return multicurrency_amount instead of amount
     *	@return     int					        		<0 if KO, Sum of credit notes and excess received amount otherwise
     */
    public function getSumCreditNotesUsed($invoice, $multicurrency = 0)
    {
        dol_syslog(get_class($this)."::getSumCreditNotesUsed", LOG_DEBUG);

        if ($invoice->element == 'facture' || $invoice->element == 'invoice')
        {
            $sql = 'SELECT sum(rc.amount_ttc) as amount, sum(rc.multicurrency_amount_ttc) as multicurrency_amount';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc, '.MAIN_DB_PREFIX.'facture as f';
            $sql.= ' WHERE rc.fk_facture_source=f.rowid AND rc.fk_facture = '.$invoice->id;
            $sql.= ' AND (f.type = 2 OR f.type = 0)';	// Find discount coming from credit note or excess received
        }
        elseif ($invoice->element == 'invoice_supplier')
        {
            $sql = 'SELECT sum(rc.amount_ttc) as amount, sum(rc.multicurrency_amount_ttc) as multicurrency_amount';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc, '.MAIN_DB_PREFIX.'facture_fourn as f';
            $sql.= ' WHERE rc.fk_invoice_supplier_source=f.rowid AND rc.fk_invoice_supplier = '.$invoice->id;
            $sql.= ' AND (f.type = 2 OR f.type = 0)';	// Find discount coming from credit note or excess paid
        }
        else
        {
            $this->error=get_class($this)."::getSumCreditNotesUsed was called with a bad object as a first parameter";
            dol_print_error($this->error);
            return -1;
        }

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($multicurrency) return $obj->multicurrency_amount;
			else return $obj->amount;
        }
        else
        {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }
    /**
     *    	Return amount (with tax) of all converted amount for this credit note
     *
     *	@param		CommonInvoice	  $invoice	    	Object invoice
	 *	@param		int			      $multicurrency	Return multicurrency_amount instead of amount
     *	@return		int					        		<0 if KO, Sum of credit notes and deposits amount otherwise
     */
    public function getSumFromThisCreditNotesNotUsed($invoice, $multicurrency = 0)
    {
        dol_syslog(get_class($this)."::getSumCreditNotesUsed", LOG_DEBUG);

        if ($invoice->element == 'facture' || $invoice->element == 'invoice')
        {
            $sql = 'SELECT sum(rc.amount_ttc) as amount, sum(rc.multicurrency_amount_ttc) as multicurrency_amount';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc';
            $sql.= ' WHERE rc.fk_facture IS NULL AND rc.fk_facture_source = '.$invoice->id;
        }
        else if ($invoice->element == 'invoice_supplier')
        {
            $sql = 'SELECT sum(rc.amount_ttc) as amount, sum(rc.multicurrency_amount_ttc) as multicurrency_amount';
            $sql.= ' FROM '.MAIN_DB_PREFIX.'societe_remise_except as rc';
            $sql.= ' WHERE rc.fk_invoice_supplier IS NULL AND rc.fk_invoice_supplier_source = '.$invoice->id;
        }
        else
        {
            $this->error=get_class($this)."::getSumCreditNotesUsed was called with a bad object as a first parameter";
            dol_print_error($this->error);
            return -1;
        }

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($multicurrency) return $obj->multicurrency_amount;
			else return $obj->amount;
        }
        else
        {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     *  Return clickable ref of object (with picto or not)
     *
     *  @param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Picto only
     *  @param		string	$option			Where to link to ('invoice' or 'discount')
     *  @return		string					String with URL
     */
    public function getNomUrl($withpicto, $option = 'invoice')
    {
        global $langs;

        $result='';

        if ($option == 'invoice') {
            $facid=! empty($this->discount_type)?$this->fk_invoice_supplier_source:$this->fk_facture_source;
            $link=! empty($this->discount_type)?'/fourn/facture/card.php':'/compta/facture/card.php';
            $label=$langs->trans("ShowSourceInvoice").': '.$this->ref_facture_source;
            $link = '<a href="'.DOL_URL_ROOT.$link.'?facid='.$facid.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
            $linkend='</a>';
            $ref=! empty($this->discount_type)?$this->ref_invoice_supplier_source:$this->ref_facture_source;
            $picto='bill';
        }
        if ($option == 'discount') {
            $label=$langs->trans("Discount");
            $link = '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$this->fk_soc.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
            $linkend='</a>';
            $ref=$langs->trans("Discount");
            $picto='generic';
        }


        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$link.$ref.$linkend;
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
		global $user,$langs,$conf;

		$this->fk_soc         = 1;
		$this->amount_ht      = 10;
		$this->amount_tva     = 1.96;
		$this->amount_ttc     = 11.96;
		$this->tva_tx         = 19.6;
		$this->description    = 'Specimen discount';
	}
}
