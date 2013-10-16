<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copytight (C) 2013	   Florian Henry        <florian.henry@open-concept.pro>
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
 *	\file       htdocs/compta/bank/class/account.class.php
 *	\ingroup    banque
 *	\brief      File of class to manage bank accounts
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *	Class to manage bank accounts
 */
class Account extends CommonObject
{
    public $element='bank_account';
    public $table_element='bank_account';

    var $rowid;	 	// deprecated
    var $id;
    var $ref;
    var $label;
    //! 1=Compte courant/check/carte, 2=Compte liquide, 0=Compte épargne
    var $courant;
    var $type;      // same as courant
    //! Name
    var $bank;
    var $clos;
    var $rappro=1;    // If bank need to be conciliated
    var $url;
    //! BBAN field for French Code banque
    var $code_banque;
    //! BBAN field for French Code guichet
    var $code_guichet;
    //! BBAN main account number
    var $number;
    //! BBAN field for French Cle de controle
    var $cle_rib;
    //! BIC/SWIFT number
    var $bic;
    //! IBAN number (International Bank Account Number)
    var $iban_prefix;
    var $proprio;
    var $owner_address;

    var $state_id;
    var $state_code;
    var $state;

    var $country_id;
    var $country_code;
    var $country;

    var $type_lib=array();

    var $account_number;

    var $currency_code;
    var $min_allowed;
    var $min_desired;
    var $comment;


    /**
     *  Constructor
     *
     *  @param	DoliDB		$db		Database handler
     */
    function __construct($db)
    {
        global $langs;

        $this->db = $db;

        $this->clos = 0;
        $this->solde = 0;

        $this->type_lib[0]=$langs->trans("BankType0");
        $this->type_lib[1]=$langs->trans("BankType1");
        $this->type_lib[2]=$langs->trans("BankType2");

        $this->status[0]=$langs->trans("StatusAccountOpened");
        $this->status[1]=$langs->trans("StatusAccountClosed");

        return 1;
    }


    /**
     *  Return if a bank account need to be conciliated
     *
     *  @return     int         1 if need to be concialiated, < 0 otherwise.
     */
    function canBeConciliated()
    {
        if (empty($this->rappro)) return -1;
        if ($this->courant == 2) return -2;
        if ($this->clos) return -3;
        return 1;
    }


    /**
     *      Add a link between bank line record and its source
     *
     *      @param	int		$line_id    Id ecriture bancaire
     *      @param  int		$url_id     Id parametre url
     *      @param  string	$url        Url
     *      @param  string	$label      Link label
     *      @param  string	$type       Type of link ('payment', 'company', 'member', ...)
     *      @return int         		<0 if KO, id line if OK
     */
    function add_url_line($line_id, $url_id, $url, $label, $type)
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_url (";
        $sql.= "fk_bank";
        $sql.= ", url_id";
        $sql.= ", url";
        $sql.= ", label";
        $sql.= ", type";
        $sql.= ") VALUES (";
        $sql.= "'".$line_id."'";
        $sql.= ", '".$url_id."'";
        $sql.= ", '".$url."'";
        $sql.= ", '".$this->db->escape($label)."'";
        $sql.= ", '".$type."'";
        $sql.= ")";

        dol_syslog(get_class($this)."::add_url_line sql=".$sql);
        if ($this->db->query($sql))
        {
            $rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_url");
            return $rowid;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::add_url_line ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * 		TODO Move this into AccountLine
     *      Return array with links from llx_bank_url
     *
     *      @param	int		$fk_bank        To search using bank transaction id
     *      @param	int		$url_id         To search using link to
     *      @param  string	$type           To search using type
     *      @return array           		Array of links
     */
    function get_url($fk_bank='', $url_id='', $type='')
    {
        $lines = array();

        // Check parameters
        if (! empty($fk_bank) && (! empty($url_id) || ! empty($type)))
        {
            $this->error="ErrorBadParameter";
            return -1;
        }

        $sql = "SELECT fk_bank, url_id, url, label, type";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank_url";
        if ($fk_bank > 0) {
            $sql.= " WHERE fk_bank = ".$fk_bank;
        }
        else { $sql.= " WHERE url_id = ".$url_id." AND type = '".$type."'";
        }
        $sql.= " ORDER BY type, label";

        dol_syslog(get_class($this)."::get_url sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $i = 0;
            $num = $this->db->num_rows($result);
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                // Anciens liens (pour compatibilite)
                $lines[$i][0] = $obj->url;
                $lines[$i][1] = $obj->url_id;
                $lines[$i][2] = $obj->label;
                $lines[$i][3] = $obj->type;
                // Nouveaux liens
                $lines[$i]['url'] = $obj->url;
                $lines[$i]['url_id'] = $obj->url_id;
                $lines[$i]['label'] = $obj->label;
                $lines[$i]['type'] = $obj->type;
                $lines[$i]['fk_bank'] = $obj->fk_bank;
                $i++;
            }
        }
        else dol_print_error($this->db);

        return $lines;
    }

    /**
     *  Add an entry into table ".MAIN_DB_PREFIX."bank
     *
     *  @param	timsestmap	$date			Date operation
     *  @param	string		$oper			1,2,3,4... (deprecated) or TYP,VIR,PRE,LIQ,VAD,CB,CHQ...
     *  @param	string		$label			Descripton
     *  @param	float		$amount			Amount
     *  @param	string		$num_chq		Numero cheque ou virement
     *  @param	string		$categorie		Categorie optionnelle
     *  @param	User		$user			User that create
     *  @param	string		$emetteur		Name of cheque writer
     *  @param	string		$banque			Bank of cheque writer
     *  @return	int							Rowid of added entry, <0 if KO
     */
    function addline($date, $oper, $label, $amount, $num_chq, $categorie, $user, $emetteur='',$banque='')
    {
        // Clean parameters
        $emetteur=trim($emetteur);
        $banque=trim($banque);

        $now=dol_now();

        if (is_numeric($oper))    // Clean oper to have a code instead of a rowid
        {
            $sql ="SELECT code FROM ".MAIN_DB_PREFIX."c_paiement";
            $sql.=" WHERE id=".$oper;
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $obj=$this->db->fetch_object($resql);
                $oper=$obj->code;
            }
            else
            {
                dol_print_error($this->db,'Failed to get payment type code');
                return -1;
            }
        }

        // Check parameters
        if (! $oper)
        {
            $this->error="oper not defined";
            return -1;
        }
        if (! $this->rowid)
        {
            $this->error="this->rowid not defined";
            return -2;
        }
        if ($this->courant == 2 && $oper != 'LIQ')
        {
            $this->error="ErrorCashAccountAcceptsOnlyCashMoney";
            return -3;
        }

        $this->db->begin();

        $datev = $date;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (";
        $sql.= "datec";
        $sql.= ", dateo";
        $sql.= ", datev";
        $sql.= ", label";
        $sql.= ", amount";
        $sql.= ", fk_user_author";
        $sql.= ", num_chq";
        $sql.= ", fk_account";
        $sql.= ", fk_type";
        $sql.= ",emetteur,banque";
        $sql.= ") VALUES (";
        $sql.= "'".$this->db->idate($now)."'";
        $sql.= ", '".$this->db->idate($date)."'";
        $sql.= ", '".$this->db->idate($datev)."'";
        $sql.= ", '".$this->db->escape($label)."'";
        $sql.= ", ".price2num($amount);
        $sql.= ", '".$user->id."'";
        $sql.= ", ".($num_chq?"'".$num_chq."'":"null");
        $sql.= ", '".$this->rowid."'";
        $sql.= ", '".$oper."'";
        $sql.= ", ".($emetteur?"'".$this->db->escape($emetteur)."'":"null");
        $sql.= ", ".($banque?"'".$this->db->escape($banque)."'":"null");
        $sql.= ")";

        dol_syslog(get_class($this)."::addline sql=".$sql);
        if ($this->db->query($sql))
        {
            $rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."bank");
            if ($categorie)
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (";
                $sql.= "lineid";
                $sql.= ", fk_categ";
                $sql.= ") VALUES (";
                $sql.= "'".$rowid."'";
                $sql.= ", '".$categorie."'";
                $sql.= ")";

                $result = $this->db->query($sql);
                if (! $result)
                {
                    $this->db->rollback();
                    $this->error=$this->db->error();
                    return -3;
                }
            }
            $this->db->commit();
            return $rowid;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::addline ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }

    /**
     *  Create bank account into database
     *
     *  @return int        			< 0 if KO, > 0 if OK
     */
    function create()
    {
        global $langs,$conf;

        // Clean parameters
        if (! $this->min_allowed) $this->min_allowed=0;
        if (! $this->min_desired) $this->min_desired=0;
        $this->state_id = ($this->state_id?$this->state_id:$this->state_id);
        $this->country_id = ($this->country_id?$this->country_id:$this->country_id);

        // Check parameters
        if (empty($this->country_id))
        {
            $this->error=$langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Country"));
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
            return -1;
        }
        if (empty($this->ref))
        {
            $this->error=$langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref"));
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
            return -1;
        }
        if (empty($this->date_solde))
        {
            $this->error=$langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("DateInitialBalance"));
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
            return -1;
        }

        // Chargement librairie pour acces fonction controle RIB
        require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';

        $now=dol_now();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_account (";
        $sql.= "datec";
        $sql.= ", ref";
        $sql.= ", label";
        $sql.= ", entity";
        $sql.= ", account_number";
        $sql.= ", currency_code";
        $sql.= ", rappro";
        $sql.= ", min_allowed";
        $sql.= ", min_desired";
        $sql.= ", comment";
        $sql.= ", state_id";
        $sql.= ", fk_pays";
        $sql.= ") VALUES (";
        $sql.= "'".$this->db->idate($now)."'";
        $sql.= ", '".$this->db->escape($this->ref)."'";
        $sql.= ", '".$this->db->escape($this->label)."'";
        $sql.= ", ".$conf->entity;
        $sql.= ", '".$this->db->escape($this->account_number)."'";
        $sql.= ", '".$this->currency_code."'";
        $sql.= ", ".$this->rappro;
        $sql.= ", ".price2num($this->min_allowed);
        $sql.= ", ".price2num($this->min_desired);
        $sql.= ", '".$this->db->escape($this->comment)."'";
        $sql.= ", ".($this->state_id>0?"'".$this->state_id."'":"null");
        $sql.= ", ".$this->country_id;
        $sql.= ")";

        dol_syslog(get_class($this)."::create sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_account");

            $result=$this->update();
            if ($result > 0)
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (";
                $sql.= "datec";
                $sql.= ", label";
                $sql.= ", amount";
                $sql.= ", fk_account";
                $sql.= ", datev";
                $sql.= ", dateo";
                $sql.= ", fk_type";
                $sql.= ", rappro";
                $sql.= ") VALUES (";
                $sql.= "'".$this->db->idate($now)."'";
                $sql.= ", '(".$langs->trans("InitialBankBalance").")'";
                $sql.= ", ".price2num($this->solde);
                $sql.= ", '".$this->id."'";
                $sql.= ", '".$this->db->idate($this->date_solde)."'";
                $sql.= ", '".$this->db->idate($this->date_solde)."'";
                $sql.= ", 'SOLD'";
                $sql.= ", 0";		// Not conciliated by default
                $sql.= ")";

                $resql=$this->db->query($sql);
                if (! $resql)
                {
                    $this->error=$this->db->lasterror();
                    dol_syslog($this->error, LOG_ERR);
                    return -3;
                }
            }
            return $this->id;
        }
        else
        {
            if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
            {
                $this->error=$langs->trans("ErrorBankLabelAlreadyExists");
                dol_syslog($this->error, LOG_ERR);
                return -1;
            }
            else {
                $this->error=$this->db->error()." sql=".$sql;
                dol_syslog($this->error, LOG_ERR);
                return -2;
            }
        }
    }

    /**
     *    	Update bank account card
     *
     *    	@param	User	$user       Object user making action
     *		@return	int					<0 si ko, >0 si ok
     */
    function update($user='')
    {
        global $langs,$conf;

        // Clean parameters
        if (! $this->min_allowed) $this->min_allowed=0;
        if (! $this->min_desired) $this->min_desired=0;
        $this->state_id = ($this->state_id?$this->state_id:$this->state_id);
        $this->country_id = ($this->country_id?$this->country_id:$this->country_id);

        // Check parameters
        if (empty($this->country_id))
        {
            $this->error=$langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Country"));
            dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
            return -1;
        }
        if (empty($this->ref))
        {
            $this->error=$langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref"));
            dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
            return -1;
        }
        if (! $this->label) $this->label = "???";

        $sql = "UPDATE ".MAIN_DB_PREFIX."bank_account SET ";

        $sql.= " ref   = '".$this->db->escape($this->ref)."'";
        $sql.= ",label = '".$this->db->escape($this->label)."'";

        $sql.= ",courant = ".$this->courant;
        $sql.= ",clos = ".$this->clos;
        $sql.= ",rappro = ".$this->rappro;
        $sql.= ",url = ".($this->url?"'".$this->url."'":"null");
        $sql.= ",account_number = '".$this->account_number."'";

        $sql.= ",currency_code = '".$this->currency_code."'";

        $sql.= ",min_allowed = '".price2num($this->min_allowed)."'";
        $sql.= ",min_desired = '".price2num($this->min_desired)."'";
        $sql.= ",comment     = '".$this->db->escape($this->comment)."'";

        $sql.= ",state_id = ".($this->state_id>0?"'".$this->state_id."'":"null");
        $sql.= ",fk_pays = ".$this->country_id;

        $sql.= " WHERE rowid = ".$this->id;
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog(get_class($this)."::update sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *    	Update BBAN (RIB) account fields
     *
     *    	@param	User	$user       Object user making update
     *		@return	int					<0 if KO, >0 if OK
     */
    function update_bban($user='')
    {
        global $conf,$langs;

        // Clean parameters
        $this->state_id = ($this->state_id?$this->state_id:$this->state_id);
        $this->country_id = ($this->country_id?$this->country_id:$this->country_id);

        // Chargement librairie pour acces fonction controle RIB
        require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';

        dol_syslog(get_class($this)."::update_bban $this->code_banque,$this->code_guichet,$this->number,$this->cle_rib,$this->iban");

        // Check parameters
        if (! $this->ref)
        {
            $this->error=$langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->trans("Ref"));
            return -2;
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX."bank_account SET ";
        $sql.= " bank  = '".$this->db->escape($this->bank)."'";
        $sql.= ",code_banque='".$this->code_banque."'";
        $sql.= ",code_guichet='".$this->code_guichet."'";
        $sql.= ",number='".$this->number."'";
        $sql.= ",cle_rib='".$this->cle_rib."'";
        $sql.= ",bic='".$this->bic."'";
        $sql.= ",iban_prefix = '".$this->iban."'";
        $sql.= ",domiciliation='".$this->db->escape($this->domiciliation)."'";
        $sql.= ",proprio = '".$this->db->escape($this->proprio)."'";
        $sql.= ",owner_address = '".$this->db->escape($this->owner_address)."'";
        $sql.= ",state_id = ".($this->state_id>0?"'".$this->state_id."'":"null");
        $sql.= ",fk_pays = ".$this->country_id;
        $sql.= " WHERE rowid = ".$this->id;
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog(get_class($this)."::update_bban sql=$sql");

        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *      Load a bank account into memory from database
     *
     *      @param	int		$id      	Id of bank account to get
     *      @param  string	$ref     	Ref of bank account to get
     *      @return	int					<0 if KO, >0 if OK
     */
    function fetch($id,$ref='')
    {
        global $conf;

        if (empty($id) && empty($ref))
        {
            $this->error="ErrorBadParameters";
            return -1;
        }

        $sql = "SELECT ba.rowid, ba.ref, ba.label, ba.bank, ba.number, ba.courant, ba.clos, ba.rappro, ba.url,";
        $sql.= " ba.code_banque, ba.code_guichet, ba.cle_rib, ba.bic, ba.iban_prefix as iban,";
        $sql.= " ba.domiciliation, ba.proprio, ba.owner_address, ba.state_id, ba.fk_pays as country_id,";
        $sql.= " ba.account_number, ba.currency_code,";
        $sql.= " ba.min_allowed, ba.min_desired, ba.comment,";
        $sql.= ' p.code as country_code, p.libelle as country,';
        $sql.= ' d.code_departement as state_code, d.nom as state';
        $sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_pays as p ON ba.fk_pays = p.rowid';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON ba.state_id = d.rowid';
        $sql.= " WHERE entity IN (".getEntity($this->element, 1).")";
        if ($id)  $sql.= " AND ba.rowid  = ".$id;
        if ($ref) $sql.= " AND ba.ref = '".$this->db->escape($ref)."'";

        dol_syslog(get_class($this)."::fetch sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id            = $obj->rowid;
                $this->rowid         = $obj->rowid;		// deprecated
                $this->ref           = $obj->ref;
                $this->label         = $obj->label;
                $this->type          = $obj->courant;
                $this->courant       = $obj->courant;
                $this->bank          = $obj->bank;
                $this->clos          = $obj->clos;
                $this->rappro        = $obj->rappro;
                $this->url           = $obj->url;

                $this->code_banque   = $obj->code_banque;
                $this->code_guichet  = $obj->code_guichet;
                $this->number        = $obj->number;
                $this->cle_rib       = $obj->cle_rib;
                $this->bic           = $obj->bic;
                $this->iban          = $obj->iban;
                $this->iban_prefix   = $obj->iban;	// deprecated
                $this->domiciliation = $obj->domiciliation;
                $this->proprio       = $obj->proprio;
                $this->owner_address = $obj->owner_address;

                $this->state_id        = $obj->state_id;
                $this->state_code      = $obj->state_code;
                $this->state           = $obj->state;

                $this->country_id    = $obj->country_id;
                $this->country_code  = $obj->country_code;
                $this->country       = $obj->country;

                $this->account_number = $obj->account_number;

                $this->currency_code  = $obj->currency_code;
                $this->account_currency_code  = $obj->currency_code;
                $this->min_allowed    = $obj->min_allowed;
                $this->min_desired    = $obj->min_desired;
                $this->comment        = $obj->comment;
                return 1;
            }
            else
            {
                return 0;
            }
            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *    Delete bank account from database
     *
     *    @return      int         <0 if KO, >0 if OK
     */
    function delete()
    {
        global $conf;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_account";
        $sql.= " WHERE rowid  = ".$this->rowid;
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog(get_class($this)."::delete sql=".$sql);
        $result = $this->db->query($sql);
        if ($result) {
            return 1;
        }
        else {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *    Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
     *
     *    @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
     *    @return   string        		Libelle
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->clos,$mode);
    }

    /**
     *    Renvoi le libelle d'un statut donne
     *
     *    @param	int		$statut        	Id statut
     *    @param    int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *    @return   string        			Libelle du statut
     */
    function LibStatut($statut,$mode=0)
    {
        global $langs;
        $langs->load('banks');

        if ($mode == 0)
        {
            if ($statut==0) return $langs->trans("StatusAccountOpened");
            if ($statut==1) return $langs->trans("StatusAccountClosed");
        }
        if ($mode == 1)
        {
            if ($statut==0) return $langs->trans("StatusAccountOpened");
            if ($statut==1) return $langs->trans("StatusAccountClosed");
        }
        if ($mode == 2)
        {
            if ($statut==0) return img_picto($langs->trans("StatusAccountOpened"),'statut4').' '.$langs->trans("StatusAccountOpened");
            if ($statut==1) return img_picto($langs->trans("StatusAccountClosed"),'statut5').' '.$langs->trans("StatusAccountClosed");
        }
        if ($mode == 3)
        {
            if ($statut==0) return img_picto($langs->trans("StatusAccountOpened"),'statut4');
            if ($statut==1) return img_picto($langs->trans("StatusAccountClosed"),'statut5');
        }
        if ($mode == 4)
        {
            if ($statut==0) return img_picto($langs->trans("StatusAccountOpened"),'statut4').' '.$langs->trans("StatusAccountOpened");
            if ($statut==1) return img_picto($langs->trans("StatusAccountClosed"),'statut5').' '.$langs->trans("StatusAccountClosed");
        }
        if ($mode == 5)
        {
            if ($statut==0) return $langs->trans("StatusAccountOpened").' '.img_picto($langs->trans("StatusAccountOpened"),'statut4');
            if ($statut==1) return $langs->trans("StatusAccountClosed").' '.img_picto($langs->trans("StatusAccountClosed"),'statut5');
        }
    }


    /**
     *    Renvoi si un compte peut etre supprimer ou non (sans mouvements)
     *
     *    @return     boolean     vrai si peut etre supprime, faux sinon
     */
    function can_be_deleted()
    {
        $can_be_deleted=false;

        $sql = "SELECT COUNT(rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank";
        $sql.= " WHERE fk_account=".$this->id;

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj=$this->db->fetch_object($resql);
            if ($obj->nb <= 1) $can_be_deleted=true;    // Juste le solde
        }
        else {
            dol_print_error($this->db);
        }
        return $can_be_deleted;
    }


    /**
     *   Return error
     *
     *   @return	string		Error string
     */
    function error()
    {
        return $this->error;
    }

    /**
     * 	Return current sold
     *
     * 	@param	int		$option		1=Exclude future operation date (this is to exclude input made in advance and have real account sold)
     *	@return	int					Current sold (value date <= today)
     */
    function solde($option=0)
    {
        $sql = "SELECT sum(amount) as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank";
        $sql.= " WHERE fk_account = ".$this->id;
        if ($option == 1) $sql.= " AND dateo <= '".$this->db->idate(dol_now())."'";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj=$this->db->fetch_object($resql);
                $solde = $obj->amount;
            }
            $this->db->free($resql);
            return $solde;
        }
    }

    /**
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *      @param	User	$user        		Objet user
     *		@param	int		$filteraccountid	To get info for a particular account id
     *      @return int         				<0 if KO, 0=Nothing to show, >0 if OK
     */
    function load_board($user,$filteraccountid=0)
    {
        global $conf;

        if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

        $now=dol_now();

        $this->nbtodo=$this->nbtodolate=0;

        $sql = "SELECT b.rowid, b.datev as datefin";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank as b,";
        $sql.= " ".MAIN_DB_PREFIX."bank_account as ba";
        $sql.= " WHERE b.rappro=0";
        $sql.= " AND b.fk_account = ba.rowid";
        $sql.= " AND ba.entity = ".$conf->entity;
        $sql.= " AND (ba.rappro = 1 AND ba.courant != 2)";	// Compte rapprochable
        if ($filteraccountid) $sql.=" AND ba.rowid = ".$filteraccountid;

        //print $sql;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($this->db->jdate($obj->datefin) < ($now - $conf->bank->rappro->warning_delay)) $this->nbtodolate++;
            }
            return $num;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *    	Renvoie nom clicable (avec eventuellement le picto)
     *
     *		@param	int		$withpicto		Inclut le picto dans le lien
     *      @param  string	$mode           ''=Link to card, 'transactions'=Link to transactions card
     *		@return	string					Chaine avec URL
     */
    function getNomUrl($withpicto=0, $mode='')
    {
        global $langs;

        $result='';

        if (empty($mode))
        {
            $lien = '<a href="'.DOL_URL_ROOT.'/compta/bank/fiche.php?id='.$this->id.'">';
            $lienfin='</a>';
        }
        else if ($mode == 'transactions')
        {
            $lien = '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$this->id.'">';
            $lienfin='</a>';
        }

        if ($withpicto) $result.=($lien.img_object($langs->trans("ShowAccount"),'account').$lienfin.' ');
        $result.=$lien.$this->label.$lienfin;
        return $result;
    }


    // Method after here are common to Account and CompanyBankAccount


    /**
     *     Return if an account has valid information
     *
     *     @return     int         1 if correct, <=0 if wrong
     */
    function verif()
    {
        require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';

        // Call function to check BAN
        if (! checkBanForAccount($this))
        {
            $this->error_number = 12;
            $this->error_message = 'RIBControlError';
        }

        if ($this->error_number == 0)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    /**
     * 	Return account country code
     *
     *	@return		string		country code
     */
    function getCountryCode()
    {
        global $mysoc;

        // We return country code of bank account
        if (! empty($this->country_code)) return $this->country_code;

        // For backward compatibility, we try to guess country from other information
        if (! empty($this->iban))
        {
            if ($mysoc->country_code === 'IN') return $mysoc->country_code;	// Test to know if we can trust IBAN

            // If IBAN defined, we can know country of account from it
            if (preg_match("/^([a-zA-Z][a-zA-Z])/i",$this->iban,$reg)) return $reg[1];
        }

        // If this class is linked to a third party
        if (! empty($this->socid))
        {
            require_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';
            $company=new Societe($this->db);
            $result=$company->fetch($this->socid);
            if (! empty($company->country_code)) return $company->country_code;
        }

        // We return country code of managed company
        if (! empty($mysoc->country_code)) return $mysoc->country_code;

        return '';
    }

    /**
     * Return if a bank account is defined with detailed information (bank code, desk code, number and key).
     * More information on codes used by countries on page http://en.wikipedia.org/wiki/Bank_code
     *
     * @return		int        0=No bank code need + Account number is enough
     *                         1=Need 2 fields for bank code: Bank, Desk (France, Spain, ...) + Account number and key
     *                         2=Neek 1 field for bank code:  Bank only (Sort code for Great Britain, BSB for Australia) + Account number
     */
    function useDetailedBBAN()
    {
        $country_code=$this->getCountryCode();

        if (in_array($country_code,array('CH','DE','FR','ES','GA','IT'))) return 1; // France, Spain, Gabon
        if (in_array($country_code,array('AU','BE','CA','DK','GR','GB','ID','IE','IR','KR','NL','NZ','UK','US'))) return 2;      // Australia, Great Britain...
        return 0;
    }

    /**
     *	Load miscellaneous information for tab "Info"
     *
     *	@param  int		$id		Id of object to load
     *	@return	void
     */
    function info($id)
    {

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
        $this->ref             = 'MBA';
        $this->label           = 'My Bank account';
        $this->bank            = 'MyBank';
        $this->courant         = 1;
        $this->clos            = 0;
        $this->code_banque     = '123';
        $this->code_guichet    = '456';
        $this->number          = 'ABC12345';
        $this->cle_rib         = 50;
        $this->bic             = 'AA12';
        $this->iban            = 'FR999999999';
        $this->iban_prefix     = 'FR';  // deprecated
        $this->domiciliation   = 'The bank addresse';
        $this->proprio         = 'Owner';
        $this->owner_address   = 'Owner address';
        $this->country_id      = 1;
    }

}


/**
 *	Class to manage bank transaction lines
 */
class AccountLine extends CommonObject
{
    var $error;
    var $db;
    var $element='bank';
    var $table_element='bank';

    var $id;
    var $ref;
    var $datec;
    var $dateo;
    var $datev;
    var $amount;
    var $label;
    var $note;
    var $fk_user_author;
    var $fk_user_rappro;
    var $fk_type;
    var $rappro;        // Is it conciliated
    var $num_releve;    // If conciliated, what is bank receipt
    var $num_chq;       // Num of cheque
    var $bank_chq;      // Bank of cheque
    var $fk_bordereau;  // Id of cheque receipt

    var $fk_account;            // Id of bank account
    var $bank_account_label;    // Label of bank account


    /**
     *  Constructor
     *
     *  @param	DoliDB	$db		Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Load into memory content of a bank transaction line
     *
     *  @param		int		$rowid   	Id of bank transaction to load
     *  @param      string	$ref     	Ref of bank transaction to load
     *  @param      string	$num     	External num to load (ex: num of transaction for paypal fee)
     *	@return		int					<0 if KO, 0 if OK but not found, >0 if OK and found
     */
    function fetch($rowid,$ref='',$num='')
    {
        global $conf;

        // Check parameters
        if (empty($rowid) && empty($ref) && empty($num)) return -1;

        $sql = "SELECT b.rowid, b.datec, b.datev, b.dateo, b.amount, b.label as label, b.fk_account,";
        $sql.= " b.fk_user_author, b.fk_user_rappro,";
        $sql.= " b.fk_type, b.num_releve, b.num_chq, b.rappro, b.note,";
        $sql.= " b.fk_bordereau, b.banque, b.emetteur,";
        //$sql.= " b.author"; // Is this used ?
        $sql.= " ba.ref as bank_account_ref, ba.label as bank_account_label";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank as b,";
        $sql.= " ".MAIN_DB_PREFIX."bank_account as ba";
        $sql.= " WHERE b.fk_account = ba.rowid";
        $sql.= " AND ba.entity = ".$conf->entity;
        if ($num) $sql.= " AND b.num_chq='".$this->db->escape($num)."'";
        else if ($ref) $sql.= " AND b.rowid='".$this->db->escape($ref)."'";
        else $sql.= " AND b.rowid=".$rowid;

        dol_syslog(get_class($this)."::fetch sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $ret=0;

            $obj = $this->db->fetch_object($result);
            if ($obj)
            {
                $this->id				= $obj->rowid;
                $this->rowid			= $obj->rowid;
                $this->ref				= $obj->rowid;

                $this->datec			= $obj->datec;
                $this->datev			= $obj->datev;
                $this->dateo			= $obj->dateo;
                $this->amount			= $obj->amount;
                $this->label			= $obj->label;
                $this->note				= $obj->note;

                $this->fk_user_author	= $obj->fk_user_author;
                $this->fk_user_rappro	= $obj->fk_user_rappro;

                $this->fk_type			= $obj->fk_type;      // Type of transaction
                $this->rappro			= $obj->rappro;
                $this->num_releve		= $obj->num_releve;

                $this->num_chq			= $obj->num_chq;
                $this->bank_chq			= $obj->bank_chq;
                $this->fk_bordereau		= $obj->fk_bordereau;

                $this->fk_account		= $obj->fk_account;
                $this->bank_account_ref   = $obj->bank_account_ref;
                $this->bank_account_label = $obj->bank_account_label;

                $ret=1;
            }
            $this->db->free($result);
            return $ret;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *      Delete transaction bank line record
     *
     *		@param	User	$user	User object that delete
     *      @return	int 			<0 if KO, >0 if OK
     */
    function delete($user=0)
    {
        $nbko=0;

        if ($this->rappro)
        {
            // Protection to avoid any delete of consolidated lines
            $this->error="DeleteNotPossibleLineIsConsolidated";
            return -1;
        }

        $this->db->begin();

        // Delete urls
        $result=$this->delete_urls($user);
        if ($result < 0)
        {
            $nbko++;
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid=".$this->rowid;
        dol_syslog(get_class($this)."::delete sql=".$sql);
        $result = $this->db->query($sql);
        if (! $result) $nbko++;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank WHERE rowid=".$this->rowid;
        dol_syslog(get_class($this)."::delete sql=".$sql);
        $result = $this->db->query($sql);
        if (! $result) $nbko++;

        if (! $nbko)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            return -$nbko;
        }
    }


    /**
     *      Delete bank line records
     *
     *		@param	User	$user	User object that delete
     *      @return	int 			<0 if KO, >0 if OK
     */
    function delete_urls($user=0)
    {
        $nbko=0;

        if ($this->rappro)
        {
            // Protection to avoid any delete of consolidated lines
            $this->error="ErrorDeleteNotPossibleLineIsConsolidated";
            return -1;
        }

        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_url WHERE fk_bank=".$this->rowid;
        dol_syslog(get_class($this)."::delete_urls sql=".$sql);
        $result = $this->db->query($sql);
        if (! $result) $nbko++;

        if (! $nbko)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            return -$nbko;
        }
    }


    /**
     *		Update bank account record in database
     *
     *		@param	User	$user			Object user making update
     *		@param 	int		$notrigger		0=Disable all triggers
     *		@return	int						<0 if KO, >0 if OK
     */
    function update($user,$notrigger=0)
    {
        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
        $sql.= " amount = ".price2num($this->amount).",";
        $sql.= " datev='".$this->db->idate($this->datev)."',";
        $sql.= " dateo='".$this->db->idate($this->dateo)."'";
        $sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog(get_class($this)."::update sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *		Update conciliation field
     *
     *		@param	User	$user		Objet user making update
     *		@param 	int		$cat		Category id
     *		@return	int					<0 if KO, >0 if OK
     */
    function update_conciliation($user,$cat)
    {
        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
        $sql.= " rappro = 1";
        $sql.= ", num_releve = '".$this->num_releve."'";
        $sql.= ", fk_user_rappro = ".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::update_conciliation sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if (! empty($cat))
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (";
                $sql.= "lineid";
                $sql.= ", fk_categ";
                $sql.= ") VALUES (";
                $sql.= $this->id;
                $sql.= ", ".$cat;
                $sql.= ")";

                dol_syslog(get_class($this)."::update_conciliation sql=".$sql, LOG_DEBUG);
                $resql = $this->db->query($sql);

                // No error check. Can fail if category already affected
            }

            $this->rappro=1;

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
     * 	Increase/decrease value date of a rowid
     *
     *	@param	int		$rowid		Id of line
     *	@param	int		$sign		1 or -1
     *	@return	int					>0 if OK, 0 if KO
     */
    function datev_change($rowid,$sign=1)
    {
        $sql = "SELECT datev FROM ".MAIN_DB_PREFIX."bank WHERE rowid = ".$rowid;
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj=$this->db->fetch_object($resql);
            $newdate=$this->db->jdate($obj->datev)+(3600*24*$sign);

            $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
            $sql.= " datev = '".$this->db->idate($newdate)."'";
            $sql.= " WHERE rowid = ".$rowid;

            $result = $this->db->query($sql);
            if ($result)
            {
                if ($this->db->affected_rows($result))
                {
                    return 1;
                }
            }
            else
            {
                dol_print_error($this->db);
                return 0;
            }
        }
        else dol_print_error($this->db);
        return 0;
    }

    /**
     * 	Increase value date of a rowid
     *
     *	@param	int		$id		Id of line to change
     *	@return	int				>0 if OK, 0 if KO
     */
    function datev_next($id)
    {
        return $this->datev_change($id,1);
    }

    /**
     * 	Decrease value date of a rowid
     *
     *	@param	int		$id		Id of line to change
     *	@return	int				>0 if OK, 0 if KO
     */
    function datev_previous($id)
    {
        return $this->datev_change($id,-1);
    }


    /**
     *	Load miscellaneous information for tab "Info"
     *
     *	@param  int		$id		Id of object to load
     *	@return	void
     */
    function info($id)
    {
        $sql = 'SELECT b.rowid, b.datec,';
        $sql.= ' b.fk_user_author, b.fk_user_rappro';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'bank as b';
        $sql.= ' WHERE b.rowid = '.$id;

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
                if ($obj->fk_user_rappro)
                {
                    $ruser = new User($this->db);
                    $ruser->fetch($obj->fk_user_rappro);
                    $this->user_rappro = $ruser;
                }

                $this->date_creation     = $this->db->jdate($obj->datec);
                //$this->date_rappro       = $obj->daterappro;    // Not yet managed
            }
            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *    	Renvoie nom clicable (avec eventuellement le picto)
     *
     *		@param	int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     *		@param	int		$maxlen			Longueur max libelle
     *		@param	string	$option			Option ('showall')
     *		@return	string					Chaine avec URL
     */
    function getNomUrl($withpicto=0,$maxlen=0,$option='')
    {
        global $langs;

        $result='';

        $lien = '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$this->rowid.'">';
        $lienfin='</a>';

        if ($withpicto) $result.=($lien.img_object($langs->trans("ShowTransaction"),'account').$lienfin.' ');
        $result.=$lien.$this->rowid.$lienfin;

        if ($option == 'showall' || $option == 'showconciliated') $result.=' (';
        if ($option == 'showall')
        {
            $result.=$langs->trans("BankAccount").': ';
            $accountstatic=new Account($this->db);
            $accountstatic->id=$this->fk_account;
            $accountstatic->label=$this->bank_account_label;
            $result.=$accountstatic->getNomUrl(0).', ';
        }
        if ($option == 'showall' || $option == 'showconciliated')
        {
            $result.=$langs->trans("BankLineConciliated").': ';
            $result.=yn($this->rappro);
        }
        if ($option == 'showall' || $option == 'showconciliated') $result.=')';

        return $result;
    }

}

?>
