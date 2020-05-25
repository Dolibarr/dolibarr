<?php
/*
 * Copyright (C) 2016 Xebax Christy <xebax@wanadoo.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

/**
 * API class for accounts
 *
 * @property DoliDB db
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class BankAccounts extends DolibarrApi
{

    /**
     * array $FIELDS Mandatory fields, checked when creating an object
     */
    static $FIELDS = array(
        'ref',
        'label',
        'type',
        'currency_code',
        'country_id'
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * Get the list of accounts.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Limit for list
     * @param int       $page       Page number
	 * @param  int    	$category   Use this param to filter list by category
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.import_key:<:'20160101')"
     * @return array                List of account objects
     *
     * @throws RestException
     */
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $category = 0, $sqlfilters = '')
    {
        $list = array();

        if (!DolibarrApiAccess::$user->rights->banque->lire) {
            throw new RestException(401);
        }

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."bank_account as t";
    	if ($category > 0) {
            $sql .= ", ".MAIN_DB_PREFIX."categorie_account as c";
		}
        $sql .= ' WHERE t.entity IN ('.getEntity('bank_account').')';
    	// Select accounts of given category
    	if ($category > 0) {
            $sql .= " AND c.fk_categorie = ".$this->db->escape($category)." AND c.fk_account = t.rowid ";
		}
        // Add sql filters
        if ($sqlfilters)
        {
            if (!DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql .= $this->db->order($sortfield, $sortorder);
        if ($limit) {
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $obj = $this->db->fetch_object($result);
                $account = new Account($this->db);
                if ($account->fetch($obj->rowid) > 0) {
                    $list[] = $this->_cleanObjectDatas($account);
                }
            }
        } else {
            throw new RestException(503, 'Error when retrieving list of accounts: '.$this->db->lasterror());
        }

        return $list;
    }

    /**
     * Get account by ID.
     *
     * @param int    $id    ID of account
     * @return array Account object
     *
     * @throws RestException
     */
    public function get($id)
    {
        if (!DolibarrApiAccess::$user->rights->banque->lire) {
            throw new RestException(401);
        }

        $account = new Account($this->db);
        $result = $account->fetch($id);
        if (!$result) {
            throw new RestException(404, 'account not found');
        }

        return $this->_cleanObjectDatas($account);
    }

    /**
     * Create account object
     *
     * @param array $request_data    Request data
     * @return int ID of account
     */
    public function post($request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->banque->configurer) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        $account = new Account($this->db);
        foreach ($request_data as $field => $value) {
            $account->$field = $value;
        }
        // Date of the initial balance (required to create an account).
        $account->date_solde = time();
        // courant and type are the same thing but the one used when
        // creating an account is courant
        $account->courant = $account->type;

        if ($account->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error creating bank account', array_merge(array($account->error), $account->errors));
        }
        return $account->id;
    }

    /**
     * Create an internal wire transfer between two bank accounts
     *
     * @param int     $bankaccount_from_id  BankAccount ID to use as the source of the internal wire transfer		{@from body}{@required true}
     * @param int     $bankaccount_to_id    BankAccount ID to use as the destination of the internal wire transfer  {@from body}{@required true}
     * @param string  $date					Date of the internal wire transfer (UNIX timestamp)						{@from body}{@required true}{@type timestamp}
     * @param string  $description			Description of the internal wire transfer								{@from body}{@required true}
     * @param float	  $amount				Amount to transfer from the source to the destination BankAccount		{@from body}{@required true}
     * @param float	  $amount_to			Amount to transfer to the destination BankAccount (only when accounts does not share the same currency)		{@from body}{@required false}
     *
     * @url POST    /transfer
     *
     * @return array
     *
     * @status 201
     *
     * @throws RestException 401 Unauthorized: User does not have permission to configure bank accounts
	 * @throws RestException 404 Not Found: Either the source or the destination bankaccount for the provided id does not exist
     * @throws RestException 422 Unprocessable Entity: Refer to detailed exception message for the cause
	 * @throws RestException 500 Internal Server Error: Error(s) returned by the RDBMS
     */
    public function transfer($bankaccount_from_id = 0, $bankaccount_to_id = 0, $date = null, $description = "", $amount = 0.0, $amount_to = 0.0)
    {
        if (!DolibarrApiAccess::$user->rights->banque->configurer) {
            throw new RestException(401);
        }

        if ($bankaccount_from_id === $bankaccount_to_id) {
            throw new RestException(422, 'bankaccount_from_id and bankaccount_to_id must be different !');
        }

        require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

        $accountfrom = new Account($this->db);
        $resultAccountFrom = $accountfrom->fetch($bankaccount_from_id);

        if ($resultAccountFrom === 0) {
            throw new RestException(404, 'The BankAccount for bankaccount_from_id provided does not exist.');
        }

        $accountto = new Account($this->db);
        $resultAccountTo = $accountto->fetch($bankaccount_to_id);

        if ($resultAccountTo === 0) {
            throw new RestException(404, 'The BankAccount for bankaccount_to_id provided does not exist.');
        }

        if ($accountto->currency_code == $accountfrom->currency_code)
        {
            $amount_to = $amount;
        }
        else
        {
            if (!$amount_to || empty($amount_to))
            {
                throw new RestException(422, 'You must provide amount_to value since bankaccount_from and bankaccount_to does not share the same currency.');
            }
        }

        $this->db->begin();

        $error = 0;
        $bank_line_id_from = 0;
        $bank_line_id_to = 0;
        $result = 0;
        $user = DolibarrApiAccess::$user;

        // By default, electronic transfert from bank to bank
        $typefrom = 'PRE';
        $typeto = 'VIR';

        if ($accountto->courant == Account::TYPE_CASH || $accountfrom->courant == Account::TYPE_CASH)
        {
            // This is transfer of change
            $typefrom = 'LIQ';
            $typeto = 'LIQ';
        }

        /**
         * Creating bank line records
         */

        if (!$error) {
            $bank_line_id_from = $accountfrom->addline($date, $typefrom, $description, -1 * price2num($amount), '', '', $user);
        }
        if (!($bank_line_id_from > 0)) {
            $error++;
        }

        if (!$error) {
            $bank_line_id_to = $accountto->addline($date, $typeto, $description, price2num($amount_to), '', '', $user);
        }
        if (!($bank_line_id_to > 0)) {
            $error++;
        }

        /**
         * Creating links between bank line record and its source
         */

        $url = DOL_URL_ROOT.'/compta/bank/line.php?rowid=';
        $label = '(banktransfert)';
        $type = 'banktransfert';

        if (!$error) {
            $result = $accountfrom->add_url_line($bank_line_id_from, $bank_line_id_to, $url, $label, $type);
        }
        if (!($result > 0)) {
            $error++;
        }

        if (!$error) {
            $result = $accountto->add_url_line($bank_line_id_to, $bank_line_id_from, $url, $label, $type);
        }
        if (!($result > 0)) {
            $error++;
        }

        if (!$error)
        {
            $this->db->commit();

            return array(
                'success' => array(
                    'code' => 201,
                    'message' => 'Internal wire transfer created successfully.'
                )
            );
        }
        else
        {
            $this->db->rollback();
            throw new RestException(500, $accountfrom->error.' '.$accountto->error);
        }
    }

    /**
     * Update account
     *
     * @param int    $id              ID of account
     * @param array  $request_data    data
     * @return int
     */
    public function put($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->banque->configurer) {
            throw new RestException(401);
        }

        $account = new Account($this->db);
        $result = $account->fetch($id);
        if (!$result) {
            throw new RestException(404, 'account not found');
        }

        foreach ($request_data as $field => $value) {
            if ($field == 'id') continue;
            $account->$field = $value;
        }

        if ($account->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->get($id);
        }
        else
        {
            throw new RestException(500, $account->error);
        }
    }

    /**
     * Delete account
     *
     * @param int    $id    ID of account
     * @return array
     */
    public function delete($id)
    {
        if (!DolibarrApiAccess::$user->rights->banque->configurer) {
            throw new RestException(401);
        }
        $account = new Account($this->db);
        $result = $account->fetch($id);
        if (!$result) {
            throw new RestException(404, 'account not found');
        }

        if ($account->delete(DolibarrApiAccess::$user) < 0) {
            throw new RestException(401, 'error when deleting account');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'account deleted'
            )
        );
    }

    /**
     * Validate fields before creating an object
     *
     * @param array|null    $data    Data to validate
     * @return array
     *
     * @throws RestException
     */
    private function _validate($data)
    {
        $account = array();
        foreach (BankAccounts::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $account[$field] = $data[$field];
        }
        return $account;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
    /**
     * Clean sensible object datas
     *
     * @param object    $object    Object to clean
     * @return array Array of cleaned object properties
     */
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
        $object = parent::_cleanObjectDatas($object);

        unset($object->rowid);

        return $object;
    }

    /**
     * Get the list of lines of the account.
     *
     * @param int $id ID of account
     * @return array Array of AccountLine objects
     *
     * @throws RestException
     *
     * @url GET {id}/lines
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.import_key:<:'20160101')"
     */
    public function getLines($id, $sqlfilters = '')
    {
        $list = array();

        if (!DolibarrApiAccess::$user->rights->banque->lire) {
            throw new RestException(401);
        }

        $account = new Account($this->db);
        $result = $account->fetch($id);
        if (!$result) {
            throw new RestException(404, 'account not found');
        }

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."bank ";
        $sql .= " WHERE fk_account = ".$id;

		// Add sql filters
		if ($sqlfilters)
		{
			if (!DolibarrApi::_checkFilters($sqlfilters))
			{
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

        $sql .= " ORDER BY rowid";

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            for ($i = 0; $i < $num; $i++) {
                $obj = $this->db->fetch_object($result);
                $accountLine = new AccountLine($this->db);
                if ($accountLine->fetch($obj->rowid) > 0) {
                    $list[] = $this->_cleanObjectDatas($accountLine);
                }
            }
        } else {
            throw new RestException(503, 'Error when retrieving list of account lines: '.$accountLine->error);
        }

        return $list;
    }

    /**
     * Add a line to an account
     *
     * @param int    $id            ID of account
     * @param int    $date          Payment date (timestamp) {@from body} {@type timestamp}
     * @param string $type          Payment mode (TYP,VIR,PRE,LIQ,VAD,CB,CHQ...) {@from body}
     * @param string $label         Label {@from body}
     * @param float  $amount        Amount (may be 0) {@from body}
     * @param int    $category      Category
     * @param string $cheque_number Cheque numberl {@from body}
     * @param string $cheque_writer Name of cheque writer {@from body}
     * @param string $cheque_bank   Bank of cheque writer {@from body}
     * @return int  ID of line
     *
     * @url POST {id}/lines
     */
    public function addLine($id, $date, $type, $label, $amount, $category = 0, $cheque_number = '', $cheque_writer = '', $cheque_bank = '')
    {
        if (!DolibarrApiAccess::$user->rights->banque->modifier) {
            throw new RestException(401);
        }

        $account = new Account($this->db);
        $result = $account->fetch($id);
        if (!$result) {
            throw new RestException(404, 'account not found');
        }

        $result = $account->addline(
            $date,
            $type,
            $label,
            $amount,
            $cheque_number,
            $category,
            DolibarrApiAccess::$user,
            $cheque_writer, $cheque_bank
        );
        if ($result < 0) {
            throw new RestException(503, 'Error when adding line to account: '.$account->error);
        }
        return $result;
    }

    /**
     * Add a link to an account line
     *
     * @param int    $id    		ID of account
     * @param int    $line_id       ID of account line
     * @param int    $url_id        ID to set in the URL {@from body}
     * @param string $url           URL of the link {@from body}
     * @param string $label         Label {@from body}
     * @param string $type          Type of link ('payment', 'company', 'member', ...) {@from body}
     * @return int  ID of link
     *
     * @url POST {id}/lines/{line_id}/links
     */
    public function addLink($id, $line_id, $url_id, $url, $label, $type)
    {
        if (!DolibarrApiAccess::$user->rights->banque->modifier) {
            throw new RestException(401);
        }

        $account = new Account($this->db);
        $result = $account->fetch($id);
        if (!$result) {
            throw new RestException(404, 'account not found');
        }

        $accountLine = new AccountLine($this->db);
        $result = $accountLine->fetch($line_id);
        if (!$result) {
            throw new RestException(404, 'account line not found');
        }

        $result = $account->add_url_line($line_id, $url_id, $url, $label, $type);
        if ($result < 0) {
            throw new RestException(503, 'Error when adding link to account line: '.$account->error);
        }
        return $result;
    }
}
