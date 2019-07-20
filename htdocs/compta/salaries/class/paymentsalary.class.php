<?php
/* Copyright (C) 2011-2018 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2014      Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/compta/salaries/class/paymentsalary.class.php
 *  \ingroup    salaries
 *  \brief      Class for salaries module payment
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *  Class to manage salary payments
 */
class PaymentSalary extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element='payment_salary';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element='payment_salary';

    /**
     * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
     */
    public $picto='payment';

    public $tms;

    /**
     * @var int User ID
     */
    public $fk_user;

    public $datep;
    public $datev;
    public $amount;

    /**
     * @var int ID
     */
    public $fk_project;

    public $type_payment;
    public $num_payment;

    /**
     * @var string salary payments label
     */
    public $label;

    public $datesp;
    public $dateep;

    /**
     * @var int ID
     */
    public $fk_bank;

    /**
     * @var int ID
     */
    public $fk_user_author;

    /**
     * @var int ID
     */
    public $fk_user_modif;


    /**
     *	Constructor
     *
     *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->element = 'payment_salary';
        $this->table_element = 'payment_salary';
    }

    /**
     * Update database
     *
     * @param   User	$user        	User that modify
     * @param	int		$notrigger	    0=no, 1=yes (no update trigger)
     * @return  int         			<0 if KO, >0 if OK
     */
    public function update($user = null, $notrigger = 0)
    {
        global $conf, $langs;

        $error=0;

        // Clean parameters
        $this->amount=trim($this->amount);
        $this->label=trim($this->label);
        $this->note=trim($this->note);

        // Check parameters
        if (empty($this->fk_user) || $this->fk_user < 0)
        {
            $this->error='ErrorBadParameter';
            return -1;
        }

        $this->db->begin();

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."payment_salary SET";

        $sql.= " tms='".$this->db->idate($this->tms)."',";
        $sql.= " fk_user=".$this->fk_user.",";
        $sql.= " datep='".$this->db->idate($this->datep)."',";
        $sql.= " datev='".$this->db->idate($this->datev)."',";
        $sql.= " amount=".price2num($this->amount).",";
        $sql.= " fk_projet=".((int) $this->fk_project).",";
        $sql.= " fk_typepayment=".$this->fk_typepayment."',";
        $sql.= " num_payment='".$this->db->escape($this->num_payment)."',";
        $sql.= " label='".$this->db->escape($this->label)."',";
        $sql.= " datesp='".$this->db->idate($this->datesp)."',";
        $sql.= " dateep='".$this->db->idate($this->dateep)."',";
        $sql.= " note='".$this->db->escape($this->note)."',";
        $sql.= " fk_bank=".($this->fk_bank > 0 ? (int) $this->fk_bank : "null").",";
        $sql.= " fk_user_author=".((int) $this->fk_user_author).",";
        $sql.= " fk_user_modif=".($this->fk_user_modif > 0 ? (int) $this->fk_user_modif : 'null');

        $sql.= " WHERE rowid=".$this->id;

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            return -1;
        }

        if (! $notrigger)
        {
            // Call trigger
            $result=$this->call_trigger('PAYMENT_SALARY_MODIFY', $user);
            if ($result < 0) $error++;
            // End call triggers
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
     *  Load object in memory from database
     *
     *  @param	int		$id         id object
     *  @param  User	$user       User that load
     *  @return int         		<0 if KO, >0 if OK
     */
    public function fetch($id, $user = null)
    {
        global $langs;
        $sql = "SELECT";
        $sql.= " s.rowid,";

        $sql.= " s.tms,";
        $sql.= " s.fk_user,";
        $sql.= " s.datep,";
        $sql.= " s.datev,";
        $sql.= " s.amount,";
        $sql.= " s.fk_projet as fk_project,";
        $sql.= " s.fk_typepayment,";
        $sql.= " s.num_payment,";
        $sql.= " s.label,";
        $sql.= " s.datesp,";
        $sql.= " s.dateep,";
        $sql.= " s.note,";
        $sql.= " s.fk_bank,";
        $sql.= " s.fk_user_author,";
        $sql.= " s.fk_user_modif,";
        $sql.= " b.fk_account,";
        $sql.= " b.fk_type,";
        $sql.= " b.rappro";

        $sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as s";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON s.fk_bank = b.rowid";
        $sql.= " WHERE s.rowid = ".$id;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id				= $obj->rowid;
                $this->ref				= $obj->rowid;
                $this->tms				= $this->db->jdate($obj->tms);
                $this->fk_user			= $obj->fk_user;
                $this->datep			= $this->db->jdate($obj->datep);
                $this->datev			= $this->db->jdate($obj->datev);
                $this->amount			= $obj->amount;
                $this->fk_project		= $obj->fk_project;
                $this->type_payement	= $obj->fk_typepayment;
                $this->num_payment		= $obj->num_payment;
                $this->label			= $obj->label;
                $this->datesp			= $this->db->jdate($obj->datesp);
                $this->dateep			= $this->db->jdate($obj->dateep);
                $this->note				= $obj->note;
                $this->fk_bank			= $obj->fk_bank;
                $this->fk_user_author	= $obj->fk_user_author;
                $this->fk_user_modif	= $obj->fk_user_modif;
                $this->fk_account		= $obj->fk_account;
                $this->fk_type			= $obj->fk_type;
                $this->rappro			= $obj->rappro;
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
     *  Delete object in database
     *
     *	@param	User	$user       User that delete
     *	@return	int					<0 if KO, >0 if OK
     */
    public function delete($user)
    {
        global $conf, $langs;

        $error=0;

        // Call trigger
        $result=$this->call_trigger('PAYMENT_SALARY_DELETE', $user);
        if ($result < 0) return -1;
        // End call triggers


        $sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_salary";
        $sql.= " WHERE rowid=".$this->id;

        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            return -1;
        }

        return 1;
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
        $this->id=0;

        $this->tms='';
        $this->fk_user='';
        $this->datep='';
        $this->datev='';
        $this->amount='';
        $this->label='';
        $this->datesp='';
        $this->dateep='';
        $this->note='';
        $this->fk_bank='';
        $this->fk_user_author='';
        $this->fk_user_modif='';
    }

    /**
     *  Create in database
     *
     *  @param      User	$user       User that create
     *  @return     int      			<0 if KO, >0 if OK
     */
    public function create($user)
    {
        global $conf,$langs;

        $error=0;
        $now=dol_now();

        // Clean parameters
        $this->amount=price2num(trim($this->amount));
        $this->label=trim($this->label);
        $this->note=trim($this->note);
        $this->fk_bank=trim($this->fk_bank);
        $this->fk_user_author=trim($this->fk_user_author);
        $this->fk_user_modif=trim($this->fk_user_modif);

        // Check parameters
        if (! $this->label)
        {
            $this->error=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
            return -3;
        }
        if ($this->fk_user < 0 || $this->fk_user == '')
        {
            $this->error=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Employee"));
            return -4;
        }
        if ($this->amount < 0 || $this->amount == '')
        {
            $this->error=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount"));
            return -5;
        }
        if (! empty($conf->banque->enabled) && (empty($this->accountid) || $this->accountid <= 0))
        {
            $this->error=$langs->trans("ErrorFieldRequired", $langs->transnoentities("Account"));
            return -6;
        }
        if (! empty($conf->banque->enabled) && (empty($this->type_payment) || $this->type_payment <= 0))
        {
            $this->error=$langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode"));
            return -7;
        }

        $this->db->begin();

        // Insert into llx_payment_salary
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."payment_salary (fk_user";
        $sql.= ", datep";
        $sql.= ", datev";
        $sql.= ", amount";
        $sql.= ", fk_projet";
        $sql.= ", salary";
        $sql.= ", fk_typepayment";
        $sql.= ", num_payment";
        if ($this->note) $sql.= ", note";
        $sql.= ", label";
        $sql.= ", datesp";
        $sql.= ", dateep";
        $sql.= ", fk_user_author";
        $sql.= ", datec";
        $sql.= ", fk_bank";
        $sql.= ", entity";
        $sql.= ") ";
        $sql.= " VALUES (";
        $sql.= "'".$this->db->escape($this->fk_user)."'";
        $sql.= ", '".$this->db->idate($this->datep)."'";
        $sql.= ", '".$this->db->idate($this->datev)."'";
        $sql.= ", ".$this->amount;
        $sql.= ", ".($this->fk_project > 0? $this->fk_project : 0);
        $sql.= ", ".($this->salary > 0 ? $this->salary : "null");
        $sql.= ", ".$this->db->escape($this->type_payment);
        $sql.= ", '".$this->db->escape($this->num_payment)."'";
        if ($this->note) $sql.= ", '".$this->db->escape($this->note)."'";
        $sql.= ", '".$this->db->escape($this->label)."'";
        $sql.= ", '".$this->db->idate($this->datesp)."'";
        $sql.= ", '".$this->db->idate($this->dateep)."'";
        $sql.= ", '".$this->db->escape($user->id)."'";
        $sql.= ", '".$this->db->idate($now)."'";
        $sql.= ", NULL";
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {

            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."payment_salary");

            if ($this->id > 0)
            {
                if (! empty($conf->banque->enabled) && ! empty($this->amount))
                {
                    // Insert into llx_bank
                    require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

                    $acc = new Account($this->db);
                    $result=$acc->fetch($this->accountid);
                    if ($result <= 0) dol_print_error($this->db);

                    // Insert payment into llx_bank
                    // Add link 'payment_salary' in bank_url between payment and bank transaction
                    $bank_line_id = $acc->addline(
                        $this->datep,
                        $this->type_payment,
                        $this->label,
                        -abs($this->amount),
                        $this->num_payment,
                        '',
                        $user,
                        '',
                        '',
                        '',
                        $this->datev
                    );

                    // Update fk_bank into llx_paiement.
                    // So we know the payment which has generate the banking ecriture
                    if ($bank_line_id > 0)
                    {
                        $this->update_fk_bank($bank_line_id);
                    }
                    else
                    {
                        $this->error=$acc->error;
                        $error++;
                    }

                    if (! $error)
                    {
                        // Add link 'payment_salary' in bank_url between payment and bank transaction
                        $url=DOL_URL_ROOT.'/compta/salaries/card.php?id=';

                        $result=$acc->add_url_line($bank_line_id, $this->id, $url, "(SalaryPayment)", "payment_salary");
                        if ($result <= 0)
                        {
                            $this->error=$acc->error;
                            $error++;
                        }
                    }

                    $fuser=new User($this->db);
                    $fuser->fetch($this->fk_user);

                    // Add link 'user' in bank_url between operation and bank transaction
                    $result=$acc->add_url_line(
                        $bank_line_id,
                        $this->fk_user,
                        DOL_URL_ROOT.'/user/card.php?id=',
                        $fuser->getFullName($langs),
                        // $langs->trans("SalaryPayment").' '.$fuser->getFullName($langs).' '.dol_print_date($this->datesp,'dayrfc').' '.dol_print_date($this->dateep,'dayrfc'),
                        'user'
                    );

                    if ($result <= 0)
                    {
                        $this->error=$acc->error;
                        $error++;
                    }
                }

                // Call trigger
                $result=$this->call_trigger('PAYMENT_SALARY_CREATE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
            else $error++;

            if (! $error)
            {
                $this->db->commit();
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
            $this->error=$this->db->error();
            $this->db->rollback();
            return -1;
        }
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Update link between payment salary and line generate into llx_bank
     *
     *  @param	int		$id_bank    Id bank account
     *	@return	int					<0 if KO, >0 if OK
     */
    public function update_fk_bank($id_bank)
    {
        // phpcs:enable
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'payment_salary SET fk_bank = '.$id_bank;
        $sql.= ' WHERE rowid = '.$this->id;
        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     *	Send name clicable (with possibly the picto)
     *
     *	@param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
     *	@param	string	$option						link option
     *  @param	int  	$notooltip					1=Disable tooltip
     *  @param  string  $morecss            		Add more css on link
     *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *	@return	string								Chaine with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $db, $conf, $langs, $hookmanager;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';

        $label = '<u>' . $langs->trans("ShowSalaryPayment") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

        $url = DOL_URL_ROOT.'/compta/salaries/card.php?id='.$this->id;

        if ($option != 'nolink')
        {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
            if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowMyObject");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';

            /*
             $hookmanager->initHooks(array('myobjectdao'));
             $parameters=array('id'=>$this->id);
             $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
             if ($reshook > 0) $linkclose = $hookmanager->resPrint;
             */
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

        $linkstart = '<a href="'.$url.'"';
        $linkstart.=$linkclose.'>';
        $linkend='</a>';

        $result .= $linkstart;
        if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
        if ($withpicto != 2) $result.= $this->ref;
        $result .= $linkend;
        //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

        global $action,$hookmanager;
        $hookmanager->initHooks(array('salarypayment'));
        $parameters=array('id'=>$this->id, 'getnomurl'=>$result);
        $reshook=$hookmanager->executeHooks('getNomUrl', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) $result = $hookmanager->resPrint;
        else $result .= $hookmanager->resPrint;

        return $result;
    }

    /**
     * Information on record
     *
     * @param	int		$id      Id of record
     * @return	void
     */
    public function info($id)
    {
        $sql = 'SELECT ps.rowid, ps.datec, ps.fk_user_author';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'payment_salary as ps';
        $sql.= ' WHERE ps.rowid = '.$id;

        dol_syslog(get_class($this).'::info', LOG_DEBUG);
        $result = $this->db->query($sql);

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
                    $this->user_creation = $cuser;
                }
                $this->date_creation     = $this->db->jdate($obj->datec);
            }
            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     * Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
     *
     * @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     * @return  string				Libelle
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->statut, $mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Renvoi le libelle d'un statut donne
     *
     * @param   int		$status     Statut
     * @param   int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     * @return	string  		    Libelle du statut
     */
    public function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
        global $langs;	// TODO Renvoyer le libelle anglais et faire traduction a affichage

        $langs->load('compta');
        /*if ($mode == 0)
        {
            if ($status == 0) return $langs->trans('ToValidate');
            if ($status == 1) return $langs->trans('Validated');
        }
        if ($mode == 1)
        {
            if ($status == 0) return $langs->trans('ToValidate');
            if ($status == 1) return $langs->trans('Validated');
        }
        if ($mode == 2)
        {
            if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
            if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
        }
        if ($mode == 3)
        {
            if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1');
            if ($status == 1) return img_picto($langs->trans('Validated'),'statut4');
        }
        if ($mode == 4)
        {
            if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
            if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
        }
        if ($mode == 5)
        {
            if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
            if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
        }
        if ($mode == 6)
        {
            if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
            if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
        }*/
        return '';
    }
}
