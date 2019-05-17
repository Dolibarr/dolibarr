<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

/**
 * API class for invoices
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Invoices extends DolibarrApi
{
    /**
     *
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'socid',
    );

    /**
     * @var Facture $invoice {@type Facture}
     */
    public $invoice;

    /**
     * Constructor
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->invoice = new Facture($this->db);
    }

    /**
     * Get properties of a invoice object
     *
     * Return an array with invoice informations
     *
     * @param 	int 	$id ID of invoice
     * @return 	array|mixed data without useless information
     *
     * @throws 	RestException
     */
	function get($id)
	{
		if(! DolibarrApiAccess::$user->rights->facture->lire) {
			throw new RestException(401);
		}

		$result = $this->invoice->fetch($id);
		if (! $result) {
			throw new RestException(404, 'Invoice not found');
		}

		// Get payment details
		$this->invoice->totalpaid = $this->invoice->getSommePaiement();
		$this->invoice->totalcreditnotes = $this->invoice->getSumCreditNotesUsed();
		$this->invoice->totaldeposits = $this->invoice->getSumDepositsUsed();
		$this->invoice->remaintopay = price2num($this->invoice->total_ttc - $this->invoice->totalpaid - $this->invoice->totalcreditnotes - $this->invoice->totaldeposits, 'MT');

		if (! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Add external contacts ids
		$this->invoice->contacts_ids = $this->invoice->liste_contact(-1,'external',1);

		$this->invoice->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->invoice);
	}

    /**
     * List invoices
     *
     * Get a list of invoices
     *
     * @param string	$sortfield	      Sort field
     * @param string	$sortorder	      Sort order
     * @param int		$limit		      Limit for list
     * @param int		$page		      Page number
     * @param string   	$thirdparty_ids	  Thirdparty ids to filter orders of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param string	$status		      Filter by invoice status : draft | unpaid | paid | cancelled
     * @param string    $sqlfilters       Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return array                      Array of invoice objects
     *
	 * @throws RestException
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids='', $status='', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE t.entity IN ('.getEntity('facture').')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($socids) $sql.= " AND t.fk_soc IN (".$socids.")";

        if ($search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale

		// Filter by status
        if ($status == 'draft')     $sql.= " AND t.fk_statut IN (0)";
        if ($status == 'unpaid')    $sql.= " AND t.fk_statut IN (1)";
        if ($status == 'paid')      $sql.= " AND t.fk_statut IN (2)";
        if ($status == 'cancelled') $sql.= " AND t.fk_statut IN (3)";
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)
        {
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result)
        {
            $i=0;
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $invoice_static = new Facture($db);
                if ($invoice_static->fetch($obj->rowid))
                {
                	// Get payment details
                	$invoice_static->totalpaid = $invoice_static->getSommePaiement();
                	$invoice_static->totalcreditnotes = $invoice_static->getSumCreditNotesUsed();
                	$invoice_static->totaldeposits = $invoice_static->getSumDepositsUsed();
                	$invoice_static->remaintopay = price2num($invoice_static->total_ttc - $invoice_static->totalpaid - $invoice_static->totalcreditnotes - $invoice_static->totaldeposits, 'MT');

					// Add external contacts ids
					$invoice_static->contacts_ids = $invoice_static->liste_contact(-1,'external',1);

                	$obj_ret[] = $this->_cleanObjectDatas($invoice_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve invoice list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No invoice found');
        }
		return $obj_ret;
    }

    /**
     * Create invoice object
     *
     * @param array $request_data   Request datas
     * @return int                  ID of invoice
     */
    function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
			throw new RestException(401, "Insuffisant rights");
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->invoice->$field = $value;
        }
        if(! array_key_exists('date', $request_data)) {
            $this->invoice->date = dol_now();
        }
        /* We keep lines as an array
         if (isset($request_data["lines"])) {
            $lines = array();
            foreach ($request_data["lines"] as $line) {
                array_push($lines, (object) $line);
            }
            $this->invoice->lines = $lines;
        }*/

        if ($this->invoice->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating invoice", array_merge(array($this->invoice->error), $this->invoice->errors));
        }
        return $this->invoice->id;
    }

     /**
     * Create an invoice using an existing order.
     *
     *
     * @param int   $orderid       Id of the order
     *
     * @url     POST /createfromorder/{orderid}
     *
     * @return int
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 405
     */
    function createInvoiceFromOrder($orderid)
    {

        require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

        if(! DolibarrApiAccess::$user->rights->commande->lire) {
            throw new RestException(401);
        }
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401);
        }
        if(empty($orderid)) {
            throw new RestException(400, 'Order ID is mandatory');
        }

        $order = new Commande($this->db);
        $result = $order->fetch($orderid);
        if( ! $result ) {
            throw new RestException(404, 'Order not found');
        }

        $result = $this->invoice->createFromOrder($order, DolibarrApiAccess::$user);
        if( $result < 0) {
            throw new RestException(405, $this->invoice->error);
        }
        $this->invoice->fetchObjectLinked();
        return $this->_cleanObjectDatas($this->invoice);
    }

    /**
     * Get lines of an invoice
     *
     * @param int   $id             Id of invoice
     *
     * @url	GET {id}/lines
     *
     * @return int
     */
    function getLines($id)
    {
    	if(! DolibarrApiAccess::$user->rights->facture->lire) {
    		throw new RestException(401);
    	}

    	$result = $this->invoice->fetch($id);
    	if( ! $result ) {
    		throw new RestException(404, 'Invoice not found');
    	}

    	if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
    		throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    	}
    	$this->invoice->getLinesArray();
    	$result = array();
    	foreach ($this->invoice->lines as $line) {
    		array_push($result,$this->_cleanObjectDatas($line));
    	}
    	return $result;
    }

    /**
     * Update a line to a given invoice
     *
     * @param int   $id             Id of invoice to update
     * @param int   $lineid         Id of line to update
     * @param array $request_data   InvoiceLine data
     *
     * @url	PUT {id}/lines/{lineid}
     *
     * @return object
     *
     * @throws 200
     * @throws 304
     * @throws 401
     * @throws 404
     */
    function putLine($id, $lineid, $request_data = null)
    {
    	if(! DolibarrApiAccess::$user->rights->facture->creer) {
    		throw new RestException(401);
    	}

    	$result = $this->invoice->fetch($id);
    	if( ! $result ) {
    		throw new RestException(404, 'Invoice not found');
    	}

    	if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
    		throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    	}
    	$request_data = (object) $request_data;
    	$updateRes = $this->invoice->updateline(
    		$lineid,
    		$request_data->desc,
    		$request_data->subprice,
    		$request_data->qty,
    		$request_data->remise_percent,
    		$request_data->date_start,
    		$request_data->date_end,
    		$request_data->tva_tx,
    		$request_data->localtax1_tx,
    		$request_data->localtax2_tx,
    		'HT',
    		$request_data->info_bits,
    		$request_data->product_type,
    		$request_data->fk_parent_line,
    		0,
    		$request_data->fk_fournprice,
    		$request_data->pa_ht,
    		$request_data->label,
    		$request_data->special_code,
    		$request_data->array_options,
    		$request_data->situation_percent,
    		$request_data->fk_unit,
    		$request_data->multicurrency_subprice
    		);

    	if ($updateRes > 0) {
    		$result = $this->get($id);
    		unset($result->line);
    		return $this->_cleanObjectDatas($result);
	    } else {
	    	throw new RestException(304, $this->invoice->error);
    	}
    }

    /**
     * Deletes a line of a given invoice
     *
     * @param int   $id             Id of invoice
     * @param int   $lineid 		Id of the line to delete
     *
     * @url     DELETE {id}/lines/{lineid}
     *
     * @return array
     *
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 405
     */
    function deleteLine($id, $lineid)
    {

    	if(! DolibarrApiAccess::$user->rights->facture->creer) {
    		throw new RestException(401);
    	}
    	if(empty($lineid)) {
    		throw new RestException(400, 'Line ID is mandatory');
    	}

    	if( ! DolibarrApi::_checkAccessToResource('facture',$id)) {
    		throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    	}

    	$result = $this->invoice->fetch($id);
    	if( ! $result ) {
    		throw new RestException(404, 'Invoice not found');
    	}

    	// TODO Check the lineid $lineid is a line of ojbect

    	$updateRes = $this->invoice->deleteline($lineid);
    	if ($updateRes > 0) {
    		return $this->get($id);
    	}
    	else
    	{
    		throw new RestException(405, $this->invoice->error);
    	}
    }

    /**
     * Update invoice
     *
     * @param int   $id             Id of invoice to update
     * @param array $request_data   Datas
     * @return int
     */
    function put($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
			throw new RestException(401);
		}

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->invoice->$field = $value;
        }

        // update bank account
        if (!empty($this->invoice->fk_account))
        {
             if($this->invoice->setBankAccount($this->invoice->fk_account) == 0)
             {
                 throw new RestException(400,$this->invoice->error);
             }
        }

        if($this->invoice->update(DolibarrApiAccess::$user))
            return $this->get($id);

        return false;
    }

    /**
     * Delete invoice
     *
     * @param int   $id Invoice ID
     * @return array
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->facture->supprimer) {
			throw new RestException(401);
		}
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if( $this->invoice->delete($id) < 0)
        {
            throw new RestException(500);
        }

         return array(
            'success' => array(
                'code' => 200,
                'message' => 'Invoice deleted'
            )
        );
    }

    /**
     * Add a line to a given invoice
     *
     * Exemple of POST query :
     * {
     *     "desc": "Desc", "subprice": "1.00000000", "qty": "1", "tva_tx": "20.000", "localtax1_tx": "0.000", "localtax2_tx": "0.000",
     *     "fk_product": "1", "remise_percent": "0", "date_start": "", "date_end": "", "fk_code_ventilation": 0,  "info_bits": "0",
     *     "fk_remise_except": null,  "product_type": "1", "rang": "-1", "special_code": "0", "fk_parent_line": null, "fk_fournprice": null,
     *     "pa_ht": "0.00000000", "label": "", "array_options": [], "situation_percent": "100", "fk_prev_id": null, "fk_unit": null
     * }
     *
     * @param int   $id             Id of invoice
     * @param array $request_data   InvoiceLine data
     *
     * @url     POST {id}/lines
     *
     * @return int
     *
     * @throws 200
     * @throws 401
     * @throws 404
     * @throws 400
     */
    function postLine($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401);
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;

        // Reset fk_parent_line for no child products and special product
        if (($request_data->product_type != 9 && empty($request_data->fk_parent_line)) || $request_data->product_type == 9) {
            $request_data->fk_parent_line = 0;
        }

        // calculate pa_ht
        $marginInfos = getMarginInfos($request_data->subprice, $request_data->remise_percent, $request_data->tva_tx, $request_data->localtax1_tx, $request_data->localtax2_tx, $request_data->fk_fournprice, $request_data->pa_ht);
        $pa_ht = $marginInfos[0];

        $updateRes = $this->invoice->addline(
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            $request_data->fk_product,
            $request_data->remise_percent,
            $request_data->date_start,
            $request_data->date_end,
            $request_data->fk_code_ventilation,
            $request_data->info_bits,
            $request_data->fk_remise_except,
            'HT',
            0,
            $request_data->product_type,
            $request_data->rang,
            $request_data->special_code,
            $request_data->origin,
            $request_data->origin_id,
            $request_data->fk_parent_line,
            empty($request_data->fk_fournprice)?null:$request_data->fk_fournprice,
            $pa_ht,
            $request_data->label,
            $request_data->array_options,
            $request_data->situation_percent,
            $request_data->fk_prev_id,
            $request_data->fk_unit
        );

        if ($updateRes < 0) {
            throw new RestException(400, 'Unable to insert the new line. Check your inputs. '.$this->invoice->error);
        }

        return $updateRes;
    }

    /**
     * Adds a contact to an invoice
     *
     * @param   int 	$id             	Order ID
     * @param   int 	$fk_socpeople       	Id of thirdparty contact (if source = 'external') or id of user (if souce = 'internal') to link
     * @param   string 	$type_contact           Type of contact (code). Must a code found into table llx_c_type_contact. For example: BILLING
     * @param   string  $source             	external=Contact extern (llx_socpeople), internal=Contact intern (llx_user)
     * @param   int     $notrigger              Disable all triggers
     *
     * @url POST    {id}/contacts
     *
     * @return  array
     *
     * @throws 200
     * @throws 304
     * @throws 401
     * @throws 404
     * @throws 500
     *
     */
    function addContact($id, $fk_socpeople, $type_contact, $source, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
                throw new RestException(401);
        }
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
                throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
                throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->invoice->add_contact($fk_socpeople,$type_contact,$source,$notrigger);
        if ($result < 0) {
                throw new RestException(500, 'Error : '.$this->invoice->error);
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->invoice);
    }



    /**
     * Sets an invoice as draft
     *
     * @param   int $id             Order ID
     * @param   int $idwarehouse    Warehouse ID
     *
     * @url POST    {id}/settodraft
     *
     * @return  array
     *
     * @throws 200
     * @throws 304
     * @throws 401
     * @throws 404
     * @throws 500
     *
     */
    function settodraft($id, $idwarehouse=-1)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401);
        }
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->invoice->set_draft(DolibarrApiAccess::$user, $idwarehouse);
        if ($result == 0) {
            throw new RestException(304, 'Nothing done.');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error : '.$this->invoice->error);
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->invoice);
    }


    /**
     * Validate an invoice
     *
	 * If you get a bad value for param notrigger check that ou provide this in body
     * {
     *   "idwarehouse": 0,
     *   "notrigger": 0
     * }
     *
     * @param   int $id             Invoice ID
     * @param   int $idwarehouse    Warehouse ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     *
     * @url POST    {id}/validate
     *
     * @return  array
     */
    function validate($id, $idwarehouse=0, $notrigger=0)
    {
    	if(! DolibarrApiAccess::$user->rights->facture->creer) {
    		throw new RestException(401);
    	}
    	$result = $this->invoice->fetch($id);
    	if( ! $result ) {
    		throw new RestException(404, 'Invoice not found');
    	}

    	if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
    		throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    	}

    	$result = $this->invoice->validate(DolibarrApiAccess::$user, '', $idwarehouse, $notrigger);
    	if ($result == 0) {
    		throw new RestException(304, 'Error nothing done. May be object is already validated');
    	}
    	if ($result < 0) {
    		throw new RestException(500, 'Error when validating Invoice: '.$this->invoice->error);
    	}

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->invoice);
    }

    /**
     * Sets an invoice as paid
     *
     * @param   int 	$id            Order ID
     * @param   string 	$close_code    Code renseigne si on classe a payee completement alors que paiement incomplet (cas escompte par exemple)
     * @param   string 	$close_note    Commentaire renseigne si on classe a payee alors que paiement incomplet (cas escompte par exemple)
     *
     * @url POST    {id}/settopaid
     *
     * @return  array 	An invoice object
     *
     * @throws 200
     * @throws 304
     * @throws 401
     * @throws 404
     * @throws 500
     */
    function settopaid($id, $close_code='', $close_note='')
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401);
        }
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->invoice->set_paid(DolibarrApiAccess::$user, $close_code, $close_note);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error : '.$this->invoice->error);
        }


        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->invoice);
    }


    /**
     * Sets an invoice as unpaid
     *
     * @param   int     $id            Order ID
     *
     * @url POST    {id}/settounpaid
     *
     * @return  array   An invoice object
     *
     * @throws 200
     * @throws 304
     * @throws 401
     * @throws 404
     * @throws 500
     */
    function settounpaid($id)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401);
        }
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->invoice->set_unpaid(DolibarrApiAccess::$user);
        if ($result == 0) {
            throw new RestException(304, 'Nothing done');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error : '.$this->invoice->error);
        }


        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($this->invoice);
    }

     /**
     * Add a discount line into an invoice (as an invoice line) using an existing absolute discount
     *
     * Note that this consume the discount.
     *
     * @param int   $id             Id of invoice
     * @param int   $discountid     Id of discount
     *
     * @url     POST {id}/usediscount/{discountid}
     *
     * @return int
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 405
     */
    function useDiscount($id, $discountid)
    {

        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401);
        }
        if(empty($id)) {
            throw new RestException(400, 'Invoice ID is mandatory');
        }
        if(empty($discountid)) {
            throw new RestException(400, 'Discount ID is mandatory');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        $result = $this->invoice->insert_discount($discountid);
        if( $result < 0) {
            throw new RestException(405, $this->invoice->error);
        }

        return $result;
    }

     /**
     * Add an available credit note discount to payments of an existing invoice.
     *
     *  Note that this consume the credit note.
     *
     * @param int   $id            Id of invoice
     * @param int   $discountid    Id of a discount coming from a credit note
     *
     * @url     POST {id}/usecreditnote/{discountid}
     *
     * @return int
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 405
     */
    function useCreditNote($id, $discountid)
    {

        require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';

        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401);
        }
        if(empty($id)) {
            throw new RestException(400, 'Invoice ID is mandatory');
        }
        if(empty($discountid)) {
            throw new RestException(400, 'Credit ID is mandatory');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $discount = new DiscountAbsolute($this->db);
        $result = $discount->fetch($discountid);
        if( ! $result ) {
            throw new RestException(404, 'Credit not found');
        }

        $result = $discount->link_to_invoice(0, $id);
        if( $result < 0) {
            throw new RestException(405, $discount->error);
        }

        return $result;
    }

    /**
     * Get list of payments of a given invoice
     *
     * @param int   $id             Id of invoice
     *
     * @url     GET {id}/payments
     *
     * @return array
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 405
     */
    function getPayments($id)
    {

        if(! DolibarrApiAccess::$user->rights->facture->lire) {
            throw new RestException(401);
        }
        if(empty($id)) {
            throw new RestException(400, 'Invoice ID is mandatory');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        $result = $this->invoice->getListOfPayments();
        if( $result < 0) {
            throw new RestException(405, $this->invoice->error);
        }

        return $result;
    }


    /**
     * Add payment line to a specific invoice with the remain to pay as amount.
     *
     * @param int     $id                               Id of invoice
     * @param string  $datepaye           {@from body}  Payment date        {@type timestamp}
     * @param int     $paiementid         {@from body}  Payment mode Id {@min 1}
     * @param string  $closepaidinvoices  {@from body}  Close paid invoices {@choice yes,no}
     * @param int     $accountid          {@from body}  Account Id {@min 1}
     * @param string  $num_paiement       {@from body}  Payment number (optional)
     * @param string  $comment            {@from body}  Note (optional)
     * @param string  $chqemetteur        {@from body}  Payment issuer (mandatory if paiementcode = 'CHQ')
     * @param string  $chqbank            {@from body}  Issuer bank name (optional)
     *
     * @url     POST {id}/payments
     *
     * @return int  Payment ID
     * @throws 400
     * @throws 401
     * @throws 404
     */
    function addPayment($id, $datepaye, $paiementid, $closepaidinvoices, $accountid, $num_paiement='', $comment='', $chqemetteur='', $chqbank='')
    {
        global $conf;

    	require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';

    	if(! DolibarrApiAccess::$user->rights->facture->creer) {
    		throw new RestException(403);
    	}
    	if(empty($id)) {
    		throw new RestException(400, 'Invoice ID is mandatory');
    	}

    	if( ! DolibarrApi::_checkAccessToResource('facture',$id)) {
    		throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    	}

    	if (! empty($conf->banque->enabled)) {
    		if(empty($accountid)) {
    			throw new RestException(400, 'Account ID is mandatory');
    		}
    	}

    	if(empty($paiementid)) {
    		throw new RestException(400, 'Paiement ID or Paiement Code is mandatory');
    	}


    	$result = $this->invoice->fetch($id);
    	if( ! $result ) {
    		throw new RestException(404, 'Invoice not found');
    	}

    	// Calculate amount to pay
    	$totalpaye = $this->invoice->getSommePaiement();
    	$totalcreditnotes = $this->invoice->getSumCreditNotesUsed();
    	$totaldeposits = $this->invoice->getSumDepositsUsed();
    	$resteapayer = price2num($this->invoice->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');

    	$this->db->begin();

    	$amounts = array();
    	$multicurrency_amounts = array();

    	// Clean parameters amount if payment is for a credit note
    	if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE) {
    		$resteapayer = price2num($resteapayer,'MT');
    		$amounts[$id] = -$resteapayer;
    		// Multicurrency
    		$newvalue = price2num($this->invoice->multicurrency_total_ttc,'MT');
    		$multicurrency_amounts[$id] = -$newvalue;
    	} else {
    		$resteapayer = price2num($resteapayer,'MT');
    		$amounts[$id] = $resteapayer;
    		// Multicurrency
    		$newvalue = price2num($this->invoice->multicurrency_total_ttc,'MT');
    		$multicurrency_amounts[$id] = $newvalue;
    	}


    	// Creation of payment line
    	$paiement = new Paiement($this->db);
    	$paiement->datepaye     = $datepaye;
    	$paiement->amounts      = $amounts;                           // Array with all payments dispatching with invoice id
    	$paiement->multicurrency_amounts = $multicurrency_amounts;    // Array with all payments dispatching
    	$paiement->paiementid   = $paiementid;
    	$paiement->paiementcode = dol_getIdFromCode($this->db,$paiementid,'c_paiement','id','code',1);
    	$paiement->num_paiement = $num_paiement;
    	$paiement->note         = $comment;

    	$paiement_id = $paiement->create(DolibarrApiAccess::$user, ($closepaidinvoices=='yes'?1:0));    // This include closing invoices
    	if ($paiement_id < 0)
    	{
    		$this->db->rollback();
    		throw new RestException(400, 'Payment error : '.$paiement->error);
    	}

    	if (! empty($conf->banque->enabled)) {
    		$label='(CustomerInvoicePayment)';

    		if($paiement->paiementcode == 'CHQ' && empty($chqemetteur)) {
    			throw new RestException(400, 'Emetteur is mandatory when payment code is '.$paiement->paiementcode);
    		}
    		if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE) $label='(CustomerInvoicePaymentBack)';  // Refund of a credit note
    		$result=$paiement->addPaymentToBank(DolibarrApiAccess::$user,'payment',$label,$accountid,$chqemetteur,$chqbank);
    		if ($result < 0)
    		{
    			$this->db->rollback();
    			throw new RestException(400, 'Add payment to bank error : '.$paiement->error);
    		}
    	}

    	$this->db->commit();

    	return $paiement_id;
    }

    /**
     * Add a payment to pay partially or completely one or several invoices.
     * Warning: Take care that all invoices are owned by the same customer.
     * Example of value for parameter arrayofamounts: {"1": "99.99", "2": "10"}
     *
     * @param array   $arrayofamounts     {@from body}  Array with id of invoices with amount to pay for each invoice
     * @param string  $datepaye           {@from body}  Payment date        {@type timestamp}
     * @param int     $paiementid         {@from body}  Payment mode Id {@min 1}
     * @param string  $closepaidinvoices  {@from body}  Close paid invoices {@choice yes,no}
     * @param int     $accountid          {@from body}  Account Id {@min 1}
     * @param string  $num_paiement       {@from body}  Payment number (optional)
     * @param string  $comment            {@from body}  Note (optional)
     * @param string  $chqemetteur        {@from body}  Payment issuer (mandatory if paiementcode = 'CHQ')
     * @param string  $chqbank            {@from body}  Issuer bank name (optional)
     *
     * @url     POST /paymentsdistributed
     *
     * @return int  Payment ID
     * @throws 400
     * @throws 401
     * @throws 403
     * @throws 404
     */
    function addPaymentDistributed($arrayofamounts, $datepaye, $paiementid, $closepaidinvoices, $accountid, $num_paiement='', $comment='', $chqemetteur='', $chqbank='')
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';

        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(403);
        }
        foreach($arrayofamounts as $id => $amount) {
        	if(empty($id)) {
        		throw new RestException(400, 'Invoice ID is mandatory. Fill the invoice id and amount into arrayofamounts parameter. For example: {"1": "99.99", "2": "10"}');
        	}
        	if( ! DolibarrApi::_checkAccessToResource('facture',$id)) {
        		throw new RestException(403, 'Access not allowed on invoice ID '.$id.' for login '.DolibarrApiAccess::$user->login);
        	}
        }

        if (! empty($conf->banque->enabled)) {
        	if(empty($accountid)) {
        		throw new RestException(400, 'Account ID is mandatory');
        	}
        }
        if(empty($paiementid)) {
        	throw new RestException(400, 'Paiement ID or Paiement Code is mandatory');
        }

        $this->db->begin();

        $amounts = array();
        $multicurrency_amounts = array();

        // Loop on each invoice to pay
        foreach($arrayofamounts as $id => $amount)
        {
        	$result = $this->invoice->fetch($id);
        	if( ! $result ) {
        		$this->db->rollback();
        		throw new RestException(404, 'Invoice ID '.$id.' not found');
        	}

        	// Calculate amount to pay
        	$totalpaye = $this->invoice->getSommePaiement();
        	$totalcreditnotes = $this->invoice->getSumCreditNotesUsed();
        	$totaldeposits = $this->invoice->getSumDepositsUsed();
        	$resteapayer = price2num($this->invoice->total_ttc - $totalpaye - $totalcreditnotes - $totaldeposits, 'MT');
        	if ($amount != 'remain')
        	{
        		if ($amount > $resteapayer)
        		{
        			$this->db->rollback();
        			throw new RestException(400, 'Payment amount on invoice ID '.$id.' ('.$amount.') is higher than remain to pay ('.$resteapayer.')');
        		}
        		$resteapayer = $amount;
        	}
            // Clean parameters amount if payment is for a credit note
            if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE) {
                $resteapayer = price2num($resteapayer,'MT');
                $amounts[$id] = -$resteapayer;
                // Multicurrency
                $newvalue = price2num($this->invoice->multicurrency_total_ttc,'MT');
                $multicurrency_amounts[$id] = -$newvalue;
            } else {
                $resteapayer = price2num($resteapayer,'MT');
                $amounts[$id] = $resteapayer;
                // Multicurrency
                $newvalue = price2num($this->invoice->multicurrency_total_ttc,'MT');
                $multicurrency_amounts[$id] = $newvalue;
            }
        }

        // Creation of payment line
        $paiement = new Paiement($this->db);
        $paiement->datepaye     = $datepaye;
        $paiement->amounts      = $amounts;                           // Array with all payments dispatching with invoice id
        $paiement->multicurrency_amounts = $multicurrency_amounts;    // Array with all payments dispatching
        $paiement->paiementid   = $paiementid;
        $paiement->paiementcode = dol_getIdFromCode($this->db,$paiementid,'c_paiement','id','code',1);
        $paiement->num_paiement = $num_paiement;
        $paiement->note         = $comment;
        $paiement_id = $paiement->create(DolibarrApiAccess::$user, ($closepaidinvoices=='yes'?1:0));    // This include closing invoices
        if ($paiement_id < 0)
        {
            $this->db->rollback();
            throw new RestException(400, 'Payment error : '.$paiement->error);
        }
        if (! empty($conf->banque->enabled)) {
            $label='(CustomerInvoicePayment)';
            if($paiement->paiementcode == 'CHQ' && empty($chqemetteur)) {
                  throw new RestException(400, 'Emetteur is mandatory when payment code is '.$paiement->paiementcode);
            }
            if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE) $label='(CustomerInvoicePaymentBack)';  // Refund of a credit note
            $result=$paiement->addPaymentToBank(DolibarrApiAccess::$user,'payment',$label,$accountid,$chqemetteur,$chqbank);
            if ($result < 0)
            {
                $this->db->rollback();
                throw new RestException(400, 'Add payment to bank error : '.$paiement->error);
            }
        }

        $this->db->commit();

        return $paiement_id;
    }

    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {

        $object = parent::_cleanObjectDatas($object);

        unset($object->note);
        unset($object->address);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);

        return $object;
    }

    /**
     * Validate fields before create or update object
     *
     * @param array|null    $data       Datas to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
    {
        $invoice = array();
        foreach (Invoices::$FIELDS as $field) {
            if (!isset($data[$field])) {
                throw new RestException(400, "$field field missing");
            }
            $invoice[$field] = $data[$field];
        }
        return $invoice;
    }
}
