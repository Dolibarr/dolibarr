<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Christophe Combelles <ccomb@free.fr>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");


/**
 *	\class      Account
 *	\brief      Class to manage bank accounts
 */
class Account extends CommonObject
{
    public $element='bank_account';
    public $table_element='bank_account';

    var $rowid;
    var $ref;
    var $label;
    //! 1=Compte courant/check/carte, 2=Compte liquide, 0=Compte épargne
    var $courant;
    var $type;      // same as courant
    //! Name
    var $bank;
    var $clos;
    var $rappro;    // If bank need to be conciliated
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
    var $adresse_proprio;


    var $fk_departement;
    var $departement_code;
    var $departement;

    var $fk_pays;
    var $pays_code;
    var $pays;

    var $type_lib=array();

    var $account_number;

    var $currency_code;
    var $min_allowed;
    var $min_desired;
    var $comment;


    /**
     *  Constructeur
     */
    function Account($DB)
    {
        global $langs;

        $this->db = $DB;

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
     *      @param      line_id     Id ecriture bancaire
     *      @param      url_id      Id parametre url
     *      @param      url         Url
     *      @param      label       Link label
     *      @param      type        Type of link ('payment', 'company', 'member', ...)
     *      @return     int         <0 if KO, id line if OK
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
     *      @param      fk_bank         To search using bank transaction id
     *      @param		url_id          To search using link to
     *      @param      type            To search using type
     *      @return     array           Array of links
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
        if ($fk_bank > 0) { $sql.= " WHERE fk_bank = ".$fk_bank; }
        else { $sql.= " WHERE url_id = ".$url_id." AND type = '".$type."'"; }
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
     *  @param		$date			Date operation
     *  @param		$oper			1,2,3,4... (deprecated) or TYP,VIR,PRE,LIQ,VAD,CB,CHQ...
     *  @param		$label			Descripton
     *  @param		$amount			Amount
     *  @param		$num_chq		Numero cheque ou virement
     *  @param		$categorie		Categorie optionnelle
     *  @param		$user			User that create
     *  @param		$emetteur		Name of cheque writer
     *  @param		$banque			Bank of cheque writer
     *  @return		int				Rowid of added entry, <0 if KO
     */
    function addline($date, $oper, $label, $amount, $num_chq='', $categorie='', $user, $emetteur='',$banque='')
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
            $this->error="Account::addline oper not defined";
            return -1;
        }
        if (! $this->rowid)
        {
            $this->error="Account::addline this->rowid not defined";
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

        dol_syslog("Account::addline sql=".$sql);
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
            dol_syslog("Account::addline ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }

    /**
     *      Create bank account into database
     *      @param      user        Object user making action
     *      @return     int        < 0 if KO, > 0 if OK
     */
    function create($user='')
    {
        global $langs,$conf;

        // Check parameters
        if (! $this->min_allowed) $this->min_allowed=0;
        if (! $this->min_desired) $this->min_desired=0;
        if (empty($this->fk_pays))
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Country"));
            dol_syslog("Account::update ".$this->error, LOG_ERR);
            return -1;
        }
        if (empty($this->ref))
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref"));
            dol_syslog("Account::update ".$this->error, LOG_ERR);
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
        $sql.= ", fk_departement";
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
        $sql.= ", ".($this->fk_departement>0?"'".$this->fk_departement."'":"null");
        $sql.= ", ".$this->fk_pays;
        $sql.= ")";

        dol_syslog("Account::create sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->affected_rows($resql))
            {
                $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_account");
                if ( $this->update() )
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
                    $sql.= $this->db->idate($now);
                    $sql.= ", '(".$langs->trans("InitialBankBalance").")'";
                    $sql.= ", ".price2num($this->solde);
                    $sql.= ", '".$this->id."'";
                    $sql.= ", '".$this->db->idate($this->date_solde)."'";
                    $sql.= ", '".$this->db->idate($this->date_solde)."'";
                    $sql.= ", 'SOLD'";
                    $sql.= ", 0";		// Not conciliated by default
                    $sql.= ")";

                    $this->db->query($sql);
                }
                return $this->id;
            }
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
     *    	@param      user        Object user making action
     *		@return		int			<0 si ko, >0 si ok
     */
    function update($user='')
    {
        global $langs,$conf;

        // Check parameters
        if (! $this->min_allowed) $this->min_allowed=0;
        if (! $this->min_desired) $this->min_desired=0;
        if (empty($this->fk_pays))
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Country"));
            dol_syslog("Account::update ".$this->error, LOG_ERR);
            return -1;
        }
        if (empty($this->ref))
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref"));
            dol_syslog("Account::update ".$this->error, LOG_ERR);
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

        $sql.= ",fk_departement = ".($this->fk_departement>0?"'".$this->fk_departement."'":"null");
        $sql.= ",fk_pays = ".$this->fk_pays;

        $sql.= " WHERE rowid = ".$this->id;
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog("Account::update sql=".$sql);
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
     *    	@param      user        Object user making update
     *		@return		int			<0 if KO, >0 if OK
     */
    function update_bban($user='')
    {
        global $conf,$langs;

        // Chargement librairie pour acces fonction controle RIB
        require_once(DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php');

        dol_syslog("Account::update_bban $this->code_banque,$this->code_guichet,$this->number,$this->cle_rib,$this->iban");

        // Check parameters
        if (! $this->ref)
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Ref"));
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
        $sql.= ",adresse_proprio = '".$this->db->escape($this->adresse_proprio)."'";
        $sql.= ",fk_departement = ".($this->fk_departement>0?"'".$this->fk_departement."'":"null");
        $sql.= ",fk_pays = ".$this->fk_pays;
        $sql.= " WHERE rowid = ".$this->id;
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog("Account::update_bban sql=$sql");

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
     *      @param      id      	Id of bank account to get
     *      @param      ref     	Ref of bank account to get
     *      @param		ref_ext		External ref of bank account to get
     */
    function fetch($id,$ref='',$ref_ext='')
    {
        global $conf;

        if (empty($id) && empty($ref) && empty($ref_ext))
        {
        	$this->error="ErrorBadParameters";
        	return -1;
        }

        $sql = "SELECT ba.rowid, ba.ref, ba.label, ba.bank, ba.number, ba.courant, ba.clos, ba.rappro, ba.url,";
        $sql.= " ba.code_banque, ba.code_guichet, ba.cle_rib, ba.bic, ba.iban_prefix as iban,";
        $sql.= " ba.domiciliation, ba.proprio, ba.adresse_proprio, ba.fk_departement, ba.fk_pays,";
        $sql.= " ba.account_number, ba.currency_code,";
        $sql.= " ba.min_allowed, ba.min_desired, ba.comment,";
        $sql.= ' p.code as pays_code, p.libelle as pays,';
        $sql.= ' d.code_departement as departement_code, d.nom as departement';
        $sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_pays as p ON ba.fk_pays = p.rowid';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON ba.fk_departement = d.rowid';
        $sql.= " WHERE entity = ".$conf->entity;
        if ($id)  $sql.= " AND ba.rowid  = ".$id;
        if ($ref) $sql.= " AND ba.ref = '".$this->db->escape($ref)."'";

        dol_syslog("Account::fetch sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id            = $obj->rowid;		// deprecated
                $this->rowid         = $obj->rowid;
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
                $this->adresse_proprio = $obj->adresse_proprio;

                $this->fk_departement  = $obj->fk_departement;
                $this->departement_code= $obj->departement_code;
                $this->departement     = $obj->departement;

                $this->fk_pays       = $obj->fk_pays;
                $this->pays_code     = $obj->pays_code;
                $this->pays          = $obj->pays;

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
     *    @return      int         <0 if KO, >0 if OK
     */
    function delete()
    {
        global $conf;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_account";
        $sql.= " WHERE rowid  = ".$this->rowid;
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog("Account::delete sql=".$sql);
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
     *    @param      mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
     *    @return     string        Libelle
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->clos,$mode);
    }

    /**
     *    	Renvoi le libelle d'un statut donne
     *    	@param      statut        	Id statut
     *    	@param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *    	@return     string        	Libelle du statut
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
     */
    function error()
    {
        return $this->error;
    }

    /**
     * 	Return current sold
     * 	@param		option		1=Exclude future operation date (this is to exclude input made in advance and have real account sold)
     *	@return		int			Current sold (value date <= today)
     */
    function solde($option=0)
    {
        $sql = "SELECT sum(amount) as amount";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank";
        $sql.= " WHERE fk_account = ".$this->id;
        if ($option == 1) $sql.= " AND dateo <= ".$this->db->idate(time());

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
     *	@param	rowid
     *	@param	sign	1 or -1
     */
    function datev_change($rowid,$sign=1)
    {
        $sql = "SELECT datev FROM ".MAIN_DB_PREFIX."bank WHERE rowid = ".$rowid;
        $resql = $this->db->query($sql);
        if ($resql)
        {
        	$obj=$this->db->fetch_object($resql);
        	$newdate=$this->db->jdate($obj->datev)+(3600*24*$sign);

	    	$sql = "UPDATE ".MAIN_DB_PREFIX."bank SET ";
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
     *	@param	rowid
     */
    function datev_next($rowid)
    {
    	return $this->datev_change($rowid,1);
    }

    /**
     *	@param	rowid
     */
    function datev_previous($rowid)
    {
    	return $this->datev_change($rowid,-1);
    }

    /**
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *      @param      user        		Objet user
     *		@param		filteraccountid		To get info for a particular account id
     *      @return     int         		<0 if KO, 0=Nothing to show, >0 if OK
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
                if ($obj->rappro) $foundaccounttoconciliate++;
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
     *		@param		withpicto		Inclut le picto dans le lien
     *      @param      mode            ''=Link to card, 'transactions'=Link to transactions card
     *		@return		string			Chaine avec URL
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
     *	@return		String		country code
     */
    function getCountryCode()
    {
        global $mysoc;

        // We return country code of bank account
        if (! empty($this->pays_code)) return $this->pays_code;

        // For backward compatibility, we try to guess country from other information
        if (! empty($this->iban))
        {
            if ($mysoc->pays_code === 'IN') return $mysoc->pays_code;	// Test to know if we can trust IBAN

            // If IBAN defined, we can know country of account from it
            if (preg_match("/^([a-zA-Z][a-zA-Z])/i",$this->iban,$reg)) return $reg[1];
        }

        // If this class is linked to a third party
        if (! empty($this->socid))
        {
            require_once(DOL_DOCUMENT_ROOT ."/societe/class/societe.class.php");
            $company=new Societe($this->db);
            $result=$company->fetch($this->socid);
            if (! empty($company->pays_code)) return $company->pays_code;
        }

        // We return country code of managed company
        if (! empty($mysoc->pays_code)) return $mysoc->pays_code;

        return '';
    }

    /**
     * 	Return if a bank account is defined with detailed information (bank code, desk code, number and key)
     * 	@return		int        0=Use only an account number
     *                         1=Need Bank, Desk, Number and Key (France, Spain, ...)
     *                         2=Neek Bank only (BSB for Australia)
     */
    function useDetailedBBAN()
    {
        $country_code=$this->getCountryCode();

        if (in_array($country_code,array('FR','ES','GA'))) return 1; // France, Spain, Gabon
        if (in_array($country_code,array('AU'))) return 2;           // Australia
        return 0;
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
        $this->adresse_proprio = 'Owner address';
    }

}


/**
 *	\class      AccountLine
 *	\brief      Class to manage bank transaction lines
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
     *  Constructeur
     */
    function AccountLine($DB, $rowid=0)
    {
        global $langs;

        $this->db = $DB;
        $this->rowid = $rowid;

        return 1;
    }

    /**
     *  Load into memory content of a bank transaction line
     *  @param      rowid   Id of bank transaction to load
     *  @param      ref     Ref of bank transaction to load
     *  @param      num     External num to load (ex: num of transaction for paypal fee)
     *	@return		int		<0 if KO, >0 if OK
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
        $sql.= " ba.label as bank_account_label";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
        $sql.= ", ".MAIN_DB_PREFIX."bank_account as ba";
        $sql.= " WHERE b.fk_account = ba.rowid";
        $sql.= " AND ba.entity = ".$conf->entity;
        if ($num) $sql.= " AND b.num_chq='".$num."'";
        else if ($ref) $sql.= " AND b.rowid='".$ref."'";
        else $sql.= " AND b.rowid=".$rowid;

        dol_syslog("AccountLine::fetch sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
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
                $this->bank_account_label = $obj->bank_account_label;
            }
            $this->db->free($result);
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *      Delete transaction bank line record
     *		@param		user	User object that delete
     *      @return		int 	<0 if KO, >0 if OK
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
        $result=$this->delete_urls();
        if ($result < 0)
        {
             $nbko++;
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid=".$this->rowid;
        dol_syslog("AccountLine::delete sql=".$sql);
        $result = $this->db->query($sql);
        if (! $result) $nbko++;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank WHERE rowid=".$this->rowid;
        dol_syslog("AccountLine::delete sql=".$sql);
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
     *		@param		user	User object that delete
     *      @return		int 	<0 if KO, >0 if OK
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
        dol_syslog("AccountLine::delete_urls sql=".$sql);
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
     *		@param 		user			Object user making update
     *		@param 		notrigger		0=Disable all triggers
     *		@return		int				<0 if KO, >0 if OK
     */
    function update($user,$notrigger=0)
    {
        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
        $sql.= " amount = ".price2num($this->amount).",";
        $sql.= " datev='".$this->db->idate($this->datev)."',";
        $sql.= " dateo='".$this->db->idate($this->dateo)."'";
        $sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog("AccountLine::update sql=".$sql);
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
            dol_syslog("AccountLine::update ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *		Update conciliation field
     *		@param 		user			Objet user making update
     *		@param 		cat				Category id
     *		@return		int				<0 if KO, >0 if OK
     */
    function update_conciliation($user,$cat)
    {
        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
        $sql.= " rappro = 1";
        $sql.= ", num_releve = '".$this->num_releve."'";
        $sql.= ", fk_user_rappro = ".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog("AccountLine::update_conciliation sql=".$sql, LOG_DEBUG);
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

                dol_syslog("AccountLine::update_conciliation sql=".$sql, LOG_DEBUG);
                $resql = $this->db->query($sql);

                // No error check. Can fail if category already affected
            }

            $bankline->rappro=1;

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
     *      Charge les informations d'ordre info dans l'objet
     *      @param     rowid       Id of object
     */
    function info($rowid)
    {
        $sql = 'SELECT b.rowid, b.datec,';
        $sql.= ' b.fk_user_author, b.fk_user_rappro';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'bank as b';
        $sql.= ' WHERE b.rowid = '.$rowid;

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
     *		@param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     *		@param		maxlen			Longueur max libelle
     *		@param		option			Option ('showall')
     *		@return		string			Chaine avec URL
     */
    function getNomUrl($withpicto=0,$maxlen=0,$option='')
    {
        global $langs;

        $result='';

        $lien = '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$this->rowid.'">';
        $lienfin='</a>';

        if ($withpicto) $result.=($lien.img_object($langs->trans("ShowTransaction"),'account').$lienfin.' ');
        $result.=$lien.$this->rowid.$lienfin;

        if ($option == 'showall')
        {
            $result.=' (';
            $result.=$langs->trans("BankAccount").': ';
            $accountstatic=new Account($this->db);
            $accountstatic->id=$this->fk_account;
            $accountstatic->label=$this->bank_account_label;
            $result.=$accountstatic->getNomUrl(0).', ';
            $result.=$langs->trans("BankLineConciliated").': ';
            $result.=yn($this->rappro);
            $result.=')';
        }

        return $result;
    }

}

?>
