<?php
/* Copyright (C) 2011 Dimitri Mouillard   <dmouillard@teclib.com>
 * Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016 Ferran Marcet       <fmarcet@2byte.es>
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
 *       \file       htdocs/expensereport/class/expensereport.class.php
 *       \ingroup    expensereport
 *       \brief      File to manage Expense Reports
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';

/**
 * Class to manage Trips and Expenses
 */
class ExpenseReport extends CommonObject
{
    var $element='expensereport';
    var $table_element='expensereport';
    var $table_element_line = 'expensereport_det';
    var $fk_element = 'fk_expensereport';
    var $picto = 'trip';

    var $lignes=array();
    
    public $date_debut;
    
    public $date_fin;

    var $fk_user_validator;
    var $status;
    var $fk_statut;     // -- 0=draft, 2=validated (attente approb), 4=canceled, 5=approved, 6=payed, 99=denied
    var $fk_c_paiement;
    var $paid;

    var $user_author_infos;
    var $user_validator_infos;

    var $fk_typepayment;
	var $num_payment;
    var $code_paiement;
    var $code_statut;

    // ACTIONS

    // Create
    var $date_create;
    var $fk_user_author;    // Note fk_user_author is not the 'author' but the guy the expense report is for.

    // Update
	var $date_modif;
    var $fk_user_modif;
    
    // Refus
    var $date_refuse;
    var $detail_refuse;
    var $fk_user_refuse;

    // Annulation
    var $date_cancel;
    var $detail_cancel;
    var $fk_user_cancel;

    // Validation
    var $date_valid;
    var $fk_user_valid;
    var $user_valid_infos;

    // Approve
    var $date_approve;
    var $fk_user_approve;

    // Paiement
    var $user_paid_infos;

    /*
        END ACTIONS
    */


    /**
     *  Constructor
     *
     *  @param  DoliDB  $db     Handler acces base de donnees
     */
    function __construct($db)
    {
        $this->db = $db;
        $this->total_ht = 0;
        $this->total_ttc = 0;
        $this->total_tva = 0;
        $this->modepaymentid = 0;

        // List of language codes for status
        $this->statuts_short = array(0 => 'Draft', 2 => 'Validated', 4 => 'Canceled', 5 => 'Approved', 6 => 'Paid', 99 => 'Refused');
        $this->statuts = array(0 => 'Draft', 2 => 'ValidatedWaitingApproval', 4 => 'Canceled', 5 => 'Approved', 6 => 'Paid', 99 => 'Refused');
        $this->statuts_logo = array(0 => 'statut0', 2 => 'statut1', 4 => 'statut5', 5 => 'statut3', 6 => 'statut6', 99 => 'statut8');

        return 1;
    }

    /**
     * Create object in database
     *
     * @param   User    $user   User that create
     * @return  int             <0 if KO, >0 if OK
     */
    function create($user)
    {
        global $conf;

        $now = dol_now();

        $fuserid = $this->fk_user_author;       // Note fk_user_author is not the 'author' but the guy the expense report is for.
        if (empty($fuserid)) $fuserid = $user->id;
        
        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
        $sql.= "ref";
        $sql.= ",total_ht";
        $sql.= ",total_ttc";
        $sql.= ",total_tva";
        $sql.= ",date_debut";
        $sql.= ",date_fin";
        $sql.= ",date_create";
        $sql.= ",fk_user_author";
        $sql.= ",fk_user_validator";
        $sql.= ",fk_user_modif";
        $sql.= ",fk_statut";
        $sql.= ",fk_c_paiement";
        $sql.= ",paid";
        $sql.= ",note_public";
        $sql.= ",note_private";
        $sql.= ",entity";
        $sql.= ") VALUES(";
        $sql.= "'(PROV)'";
        $sql.= ", ".$this->total_ht;
        $sql.= ", ".$this->total_ttc;
        $sql.= ", ".$this->total_tva;
        $sql.= ", '".$this->db->idate($this->date_debut)."'";
        $sql.= ", '".$this->db->idate($this->date_fin)."'";
        $sql.= ", '".$this->db->idate($now)."'";
        $sql.= ", ".$fuserid;
        $sql.= ", ".($this->fk_user_validator > 0 ? $this->fk_user_validator:"null");
        $sql.= ", ".($this->fk_user_modif > 0 ? $this->fk_user_modif:"null");
        $sql.= ", ".($this->fk_statut > 1 ? $this->fk_statut:0);
        $sql.= ", ".($this->modepaymentid?$this->modepaymentid:"null");
        $sql.= ", 0";
        $sql.= ", ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
        $sql.= ", ".($this->note_private?"'".$this->db->escape($this->note_private)."'":"null");
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
            $this->ref='(PROV'.$this->id.')';

            $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element." SET ref='".$this->ref."' WHERE rowid=".$this->id;
            dol_syslog(get_class($this)."::create sql=".$sql);
            $resql=$this->db->query($sql);
            if (!$resql) $error++;

            foreach ($this->lignes as $i => $val)
            {
                $newndfline=new ExpenseReportLine($this->db);
                $newndfline=$this->lignes[$i];
                $newndfline->fk_expensereport=$this->id;
                if ($result >= 0)
                {
                    $result=$newndfline->insert();
                }
                if ($result < 0)
                {
                    $error++;
                    break;
                }
            }

            if (! $error)
            {
                $result=$this->update_price();
                if ($result > 0)
                {
                    $this->db->commit();
                    return $this->id;
                }
                else
                {
                    $this->db->rollback();
                    return -3;
                }
            }
            else
            {
                dol_syslog(get_class($this)."::create error ".$this->error, LOG_ERR);
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->error=$this->db->error()." sql=".$sql;
            $this->db->rollback();
            return -1;
        }

    }


    /**
     *	Load an object from its id and create a new one in database
     *
     *	@param		int			$socid			Id of thirdparty
     *	@return		int							New id of clone
     */
    function createFromClone($socid=0)
    {
        global $user,$hookmanager;
    
        $error=0;
    
        $this->context['createfromclone'] = 'createfromclone';
    
        $this->db->begin();
    
        // get extrafields so they will be clone
        foreach($this->lines as $line)
            //$line->fetch_optionals($line->rowid);
    
            // Load source object
            $objFrom = clone $this;
    
            $this->id=0;
            $this->ref = '';
            $this->statut=0;
    
            // Clear fields
            $this->fk_user_author     = $user->id;     // Note fk_user_author is not the 'author' but the guy the expense report is for.
            $this->fk_user_valid      = '';
            $this->date_create  	  = '';
            $this->date_creation      = '';
            $this->date_validation    = '';
    
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
    
                // Call trigger
                $result=$this->call_trigger('EXPENSEREPORT_CLONE',$user);
                if ($result < 0) $error++;
                // End call triggers
            }
    
            unset($this->context['createfromclone']);
    
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
     * update
     *
     * @param   User    $user                   User making change
     * @param   User    $userofexpensereport    New user we want to have the expense report on.
     * @return  int                             <0 if KO, >0 if OK
     */
    function update($user, $userofexpensereport=null)
    {
        global $langs;

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " total_ht = ".$this->total_ht;
        $sql.= " , total_ttc = ".$this->total_ttc;
        $sql.= " , total_tva = ".$this->total_tva;
        $sql.= " , date_debut = '".$this->db->idate($this->date_debut)."'";
        $sql.= " , date_fin = '".$this->db->idate($this->date_fin)."'";
        if ($userofexpensereport && is_object($userofexpensereport))
        {
            $sql.= " , fk_user_author = ".($userofexpensereport->id > 0 ? "'".$userofexpensereport->id."'":"null");     // Note fk_user_author is not the 'author' but the guy the expense report is for.
        }
        $sql.= " , fk_user_validator = ".($this->fk_user_validator > 0 ? $this->fk_user_validator:"null");
        $sql.= " , fk_user_valid = ".($this->fk_user_valid > 0 ? $this->fk_user_valid:"null");
        $sql.= " , fk_user_modif = ".$user->id;
        $sql.= " , fk_statut = ".($this->fk_statut >= 0 ? $this->fk_statut:'0');
        $sql.= " , fk_c_paiement = ".($this->fk_c_paiement > 0 ? $this->fk_c_paiement:"null");
        $sql.= " , note_public = ".(!empty($this->note_public)?"'".$this->db->escape($this->note_public)."'":"''");
        $sql.= " , note_private = ".(!empty($this->note_private)?"'".$this->db->escape($this->note_private)."'":"''");
        $sql.= " , detail_refuse = ".(!empty($this->detail_refuse)?"'".$this->db->escape($this->detail_refuse)."'":"''");
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *  Load an object from database
     *
     *  @param  int     $id     Id                      {@min 1}
     *  @param  string  $ref    Ref                     {@name ref}
     *  @return int             <0 if KO, >0 if OK
     */
    function fetch($id, $ref='')
    {
        global $conf;

        $sql = "SELECT d.rowid, d.ref, d.note_public, d.note_private,";                                 // DEFAULT
        $sql.= " d.detail_refuse, d.detail_cancel, d.fk_user_refuse, d.fk_user_cancel,";                // ACTIONS
        $sql.= " d.date_refuse, d.date_cancel,";                                                        // ACTIONS
        $sql.= " d.total_ht, d.total_ttc, d.total_tva,";                                                // TOTAUX (int)
        $sql.= " d.date_debut, d.date_fin, d.date_create, d.tms as date_modif, d.date_valid, d.date_approve,";	// DATES (datetime)
        $sql.= " d.fk_user_author, d.fk_user_modif, d.fk_user_validator,";
        $sql.= " d.fk_user_valid, d.fk_user_approve,";
        $sql.= " d.fk_statut as status, d.fk_c_paiement,";
        $sql.= " dp.libelle as libelle_paiement, dp.code as code_paiement";                             // INNER JOIN paiement
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as d LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as dp ON d.fk_c_paiement = dp.id";
        if ($ref) $sql.= " WHERE d.ref = '".$this->db->escape($ref)."'";
        else $sql.= " WHERE d.rowid = ".$id;
        $sql.= $restrict;

        dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql) ;
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
                $this->id           = $obj->rowid;
                $this->ref          = $obj->ref;
                $this->total_ht     = $obj->total_ht;
                $this->total_tva    = $obj->total_tva;
                $this->total_ttc    = $obj->total_ttc;
                $this->note_public  = $obj->note_public;
                $this->note_private = $obj->note_private;
                $this->detail_refuse = $obj->detail_refuse;
                $this->detail_cancel = $obj->detail_cancel;

                $this->date_debut       = $this->db->jdate($obj->date_debut);
                $this->date_fin         = $this->db->jdate($obj->date_fin);
                $this->date_valid       = $this->db->jdate($obj->date_valid);
                $this->date_approve     = $this->db->jdate($obj->date_approve);
                $this->date_create      = $this->db->jdate($obj->date_create);
                $this->date_modif       = $this->db->jdate($obj->date_modif);
                $this->date_refuse      = $this->db->jdate($obj->date_refuse);
                $this->date_cancel      = $this->db->jdate($obj->date_cancel);

                $this->fk_user_author           = $obj->fk_user_author;    // Note fk_user_author is not the 'author' but the guy the expense report is for.
                $this->fk_user_modif            = $obj->fk_user_modif;
                $this->fk_user_validator        = $obj->fk_user_validator;
                $this->fk_user_valid            = $obj->fk_user_valid;
                $this->fk_user_refuse           = $obj->fk_user_refuse;
                $this->fk_user_cancel           = $obj->fk_user_cancel;
                $this->fk_user_approve          = $obj->fk_user_approve;
                
                $user_author = new User($this->db);
                if ($this->fk_user_author > 0) $user_author->fetch($this->fk_user_author);

                $this->user_author_infos = dolGetFirstLastname($user_author->firstname, $user_author->lastname);

                $user_approver = new User($this->db);
                if ($this->fk_user_validator > 0) $user_approver->fetch($this->fk_user_validator);
                $this->user_validator_infos = dolGetFirstLastname($user_approver->firstname, $user_approver->lastname);

                $this->fk_statut                = $obj->status;
                $this->status                   = $obj->status;
                $this->fk_c_paiement            = $obj->fk_c_paiement;
                $this->paid                     = $obj->paid;

                if ($this->fk_statut==5 || $this->fk_statut==6)
                {
                    $user_valid = new User($this->db);
                    if ($this->fk_user_valid > 0) $user_valid->fetch($this->fk_user_valid);
                    $this->user_valid_infos = dolGetFirstLastname($user_valid->firstname, $user_valid->lastname);
                }

                $this->libelle_statut   = $obj->libelle_statut;
                $this->libelle_paiement = $obj->libelle_paiement;
                $this->code_statut      = $obj->code_statut;
                $this->code_paiement    = $obj->code_paiement;

                $this->lignes = array();    // deprecated
                $this->lines = array();

                $result=$this->fetch_lines();

                return $result;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     *    Classify the expense report as paid
     *
     *    @param    int     $id                 Id of expense report
     *    @param    user    $fuser              User making change
     *    @return   int                         <0 if KO, >0 if OK
     */
    function set_paid($id, $fuser)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."expensereport";
        $sql.= " SET fk_statut = 6, paid=1";
        $sql.= " WHERE rowid = ".$id." AND fk_statut = 5";

        dol_syslog(get_class($this)."::set_paid sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->affected_rows($resql))
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Returns the label status
     *
     *  @param      int     $mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     *  @return     string              Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->status,$mode);
    }

    /**
     *  Returns the label of a statut
     *
     *  @param      int     $status     id statut
     *  @param      int     $mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return     string              Label
     */
    function LibStatut($status,$mode=0)
    {
        global $langs;

        if ($mode == 0)
            return $langs->transnoentities($this->statuts[$status]);

        if ($mode == 1)
            return $langs->transnoentities($this->statuts_short[$status]);

        if ($mode == 2)
            return img_picto($langs->transnoentities($this->statuts_short[$status]), $this->statuts_logo[$status]).' '.$langs->transnoentities($this->statuts_short[$status]);

        if ($mode == 3)
            return img_picto($langs->transnoentities($this->statuts_short[$status]), $this->statuts_logo[$status]);

        if ($mode == 4)
            return img_picto($langs->transnoentities($this->statuts_short[$status]),$this->statuts_logo[$status]).' '.$langs->transnoentities($this->statuts[$status]);

        if ($mode == 5)
            return '<span class="hideonsmartphone">'.$langs->transnoentities($this->statuts_short[$status]).' </span>'.img_picto($langs->transnoentities($this->statuts_short[$status]),$this->statuts_logo[$status]);

        if ($mode == 6)
            return $langs->transnoentities($this->statuts[$status]).' '.img_picto($langs->transnoentities($this->statuts_short[$status]),$this->statuts_logo[$status]);
    }


    /**
     *  Load information on object
     *
     *  @param  int     $id      Id of object
     *  @return void
     */
    function info($id)
    {
        global $conf;

        $sql = "SELECT f.rowid,";
        $sql.= " f.date_create as datec,";
        $sql.= " f.tms as date_modification,";
        $sql.= " f.date_valid as datev,";
        $sql.= " f.date_approve as datea,";
        $sql.= " f.fk_user_author as fk_user_creation,";
        $sql.= " f.fk_user_modif as fk_user_modification,";
        $sql.= " f.fk_user_valid,";
        $sql.= " f.fk_user_approve";
        $sql.= " FROM ".MAIN_DB_PREFIX."expensereport as f";
        $sql.= " WHERE f.rowid = ".$id;
        $sql.= " AND f.entity = ".$conf->entity;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id                = $obj->rowid;

                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->date_modification);
                $this->date_validation   = $this->db->jdate($obj->datev);
                $this->date_approbation  = $this->db->jdate($obj->datea);

                $cuser = new User($this->db);
                $cuser->fetch($obj->fk_user_author);
                $this->user_creation     = $cuser;

                if ($obj->fk_user_creation)
                {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_creation);
                    $this->user_creation     = $cuser;
                }
                if ($obj->fk_user_valid)
                {
                    $vuser = new User($this->db);
                    $vuser->fetch($obj->fk_user_valid);
                    $this->user_validation     = $vuser;
                }
                if ($obj->fk_user_modification)
                {
                    $muser = new User($this->db);
                    $muser->fetch($obj->fk_user_modification);
                    $this->user_modification   = $muser;
                }
                if ($obj->fk_user_approve)
                {
                    $auser = new User($this->db);
                    $auser->fetch($obj->fk_user_approve);
                    $this->user_approve   = $auser;
                }
                
            }
            $this->db->free($resql);
        }
        else
        {
            dol_print_error($this->db);
        }
    }



    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *  id must be 0 if object instance is a specimen.
     *
     *  @return void
     */
    function initAsSpecimen()
    {
        global $user,$langs,$conf;

        $now=dol_now();

        // Initialise parametres
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->specimen=1;
        $this->date_create = $now;
        $this->date_debut = $now;
        $this->date_fin = $now;
        $this->date_approve = $now;

        $this->status = 5;
        $this->fk_statut = 5;

        $this->fk_user_author = $user->id;
        $this->fk_user_valid = $user->id;
        $this->fk_user_approve = $user->id;
        $this->fk_user_validator = $user->id;

        $this->note_private='Private note';
        $this->note_public='SPECIMEN';
        $nbp = 5;
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line=new ExpenseReportLine($this->db);
            $line->comments=$langs->trans("Comment")." ".$xnbp;
            $line->date=($now-3600*(1+$xnbp));
            $line->total_ht=100;
            $line->total_tva=20;
            $line->total_ttc=120;
            $line->qty=1;
            $line->vatrate=20;
            $line->value_unit=120;
            $line->fk_expensereport=0;
            $line->type_fees_code='TRA';

            $line->projet_ref = 'ABC';

            $this->lines[$xnbp]=$line;
            $xnbp++;

            $this->total_ht+=$line->total_ht;
            $this->total_tva+=$line->total_tva;
            $this->total_ttc+=$line->total_ttc;
        }
    }

    /**
     * fetch_line_by_project
     *
     * @param   int     $projectid      Project id
     * @param   User    $user           User
     * @return  int                     <0 if KO, >0 if OK
     */
    function fetch_line_by_project($projectid,$user='')
    {
        global $conf,$db,$langs;

        $langs->load('trips');

        if($user->rights->expensereport->lire) {

            $sql = "SELECT de.fk_expensereport, de.date, de.comments, de.total_ht, de.total_ttc";
            $sql.= " FROM ".MAIN_DB_PREFIX."expensereport_det as de";
            $sql.= " WHERE de.fk_projet = ".$projectid;

            dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
            $result = $db->query($sql) ;
            if ($result)
            {
                $num = $db->num_rows($result);
                $i = 0;
                $total_HT = 0;
                $total_TTC = 0;

                while ($i < $num)
                {

                    $objp = $db->fetch_object($result);

                    $sql2 = "SELECT d.rowid, d.fk_user_author, d.ref, d.fk_statut";
                    $sql2.= " FROM ".MAIN_DB_PREFIX."expensereport as d";
                    $sql2.= " WHERE d.rowid = '".$objp->fk_expensereport."'";

                    $result2 = $db->query($sql2);
                    $obj = $db->fetch_object($result2);

                    $objp->fk_user_author = $obj->fk_user_author;
                    $objp->ref = $obj->ref;
                    $objp->fk_c_expensereport_status = $obj->fk_statut;
                    $objp->rowid = $obj->rowid;

                    $total_HT = $total_HT + $objp->total_ht;
                    $total_TTC = $total_TTC + $objp->total_ttc;
                    $author = new User($db);
                    $author->fetch($objp->fk_user_author);

                    print '<tr>';
                    print '<td><a href="'.DOL_URL_ROOT.'/expensereport/card.php?id='.$objp->rowid.'">'.$objp->ref_num.'</a></td>';
                    print '<td align="center">'.dol_print_date($objp->date,'day').'</td>';
                    print '<td>'.$author->getNomUrl().'</td>';
                    print '<td>'.$objp->comments.'</td>';
                    print '<td align="right">'.price($objp->total_ht).'</td>';
                    print '<td align="right">'.price($objp->total_ttc).'</td>';
                    print '<td align="right">';

                    switch($objp->fk_c_expensereport_status) {
                        case 4:
                            print img_picto($langs->trans('StatusOrderCanceled'),'statut5');
                            break;
                        case 1:
                            print $langs->trans('Draft').' '.img_picto($langs->trans('Draft'),'statut0');
                            break;
                        case 2:
                            print $langs->trans('TripForValid').' '.img_picto($langs->trans('TripForValid'),'statut3');
                            break;
                        case 5:
                            print $langs->trans('TripForPaid').' '.img_picto($langs->trans('TripForPaid'),'statut3');
                            break;
                        case 6:
                            print $langs->trans('TripPaid').' '.img_picto($langs->trans('TripPaid'),'statut4');
                            break;
                    }
                    /*
                     if ($status==4) return img_picto($langs->trans('StatusOrderCanceled'),'statut5');
                    if ($status==1) return img_picto($langs->trans('StatusOrderDraft'),'statut0');
                    if ($status==2) return img_picto($langs->trans('StatusOrderValidated'),'statut1');
                    if ($status==2) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3');
                    if ($status==5) return img_picto($langs->trans('StatusOrderToBill'),'statut4');
                    if ($status==6) return img_picto($langs->trans('StatusOrderOnProcess'),'statut6');
                    */
                    print '</td>';
                    print '</tr>';

                    $i++;
                }

                print '<tr class="liste_total"><td colspan="4">'.$langs->trans("Number").': '.$i.'</td>';
                print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_HT).'</td>';
                print '<td align="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_TTC).'</td>';
                print '<td>&nbsp;</td>';
                print '</tr>';

            }
            else
            {
                $this->error=$db->error();
                return -1;
            }
        }

    }

    /**
     * recalculer
     * TODO Replace this with call to update_price if not already done
     *
     * @param   int         $id     Id of expense report
     * @return  int                 <0 if KO, >0 if OK
     */
    function recalculer($id)
    {
        $sql = 'SELECT tt.total_ht, tt.total_ttc, tt.total_tva';
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line.' as tt';
        $sql.= ' WHERE tt.'.$this->fk_element.' = '.$id;

        $total_ht = 0; $total_tva = 0; $total_ttc = 0;

        dol_syslog('ExpenseReport::recalculer sql='.$sql,LOG_DEBUG);

        $result = $this->db->query($sql);
        if($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num):
            $objp = $this->db->fetch_object($result);
            $total_ht+=$objp->total_ht;
            $total_tva+=$objp->total_tva;
            $i++;
            endwhile;

            $total_ttc = $total_ht + $total_tva;
            $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
            $sql.= " total_ht = ".$total_ht;
            $sql.= " , total_ttc = ".$total_ttc;
            $sql.= " , total_tva = ".$total_tva;
            $sql.= " WHERE rowid = ".$id;
            $result = $this->db->query($sql);
            if($result):
            $this->db->free($result);
            return 1;
            else:
            $this->error=$this->db->error();
            dol_syslog('ExpenseReport::recalculer: Error '.$this->error,LOG_ERR);
            return -3;
            endif;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog('ExpenseReport::recalculer: Error '.$this->error,LOG_ERR);
            return -3;
        }
    }

    /**
     * fetch_lines
     *
     * @return  int     <0 if OK, >0 if KO
     */
    function fetch_lines()
    {
        $this->lines=array();

        $sql = ' SELECT de.rowid, de.comments, de.qty, de.value_unit, de.date,';
        $sql.= ' de.'.$this->fk_element.', de.fk_c_type_fees, de.fk_projet, de.tva_tx,';
        $sql.= ' de.total_ht, de.total_tva, de.total_ttc,';
        $sql.= ' ctf.code as code_type_fees, ctf.label as libelle_type_fees,';
        $sql.= ' p.ref as ref_projet, p.title as title_projet';
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line.' as de';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_fees as ctf ON de.fk_c_type_fees = ctf.id';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as p ON de.fk_projet = p.rowid';
        $sql.= ' WHERE de.'.$this->fk_element.' = '.$this->id;

        dol_syslog('ExpenseReport::fetch_lines sql='.$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $objp = $this->db->fetch_object($resql);

                $deplig = new ExpenseReportLine($this->db);

                $deplig->rowid          = $objp->rowid;
                $deplig->comments       = $objp->comments;
                $deplig->qty            = $objp->qty;
                $deplig->value_unit     = $objp->value_unit;
                $deplig->date           = $objp->date;

                $deplig->fk_expensereport = $objp->fk_expensereport;
                $deplig->fk_c_type_fees = $objp->fk_c_type_fees;
                $deplig->fk_projet      = $objp->fk_projet;

                $deplig->total_ht       = $objp->total_ht;
                $deplig->total_tva      = $objp->total_tva;
                $deplig->total_ttc      = $objp->total_ttc;

                $deplig->type_fees_code     = empty($objp->code_type_fees)?'TF_OTHER':$objp->code_type_fees;
                $deplig->type_fees_libelle  = $objp->libelle_type_fees;
				$deplig->tva_tx			    = $objp->tva_tx;
                $deplig->vatrate            = $objp->tva_tx;
                $deplig->projet_ref         = $objp->ref_projet;
                $deplig->projet_title       = $objp->title_projet;

                $this->lignes[$i] = $deplig;
                $this->lines[$i] = $deplig;

                $i++;
            }
            $this->db->free($resql);
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog('ExpenseReport::fetch_lines: Error '.$this->error, LOG_ERR);
            return -3;
        }
    }


    /**
     * delete
     *
     * @param   User    $fuser      User that delete
     * @return  int                 <0 if KO, >0 if OK
     */
    function delete(User $fuser=null)
    {
        global $user,$langs,$conf;

        if (! $rowid) $rowid=$this->id;

        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element_line.' WHERE '.$this->fk_element.' = '.$rowid;
        if ($this->db->query($sql))
        {
            $sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE rowid = '.$rowid;
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->error=$this->db->error()." sql=".$sql;
                dol_syslog("ExpenseReport.class::delete ".$this->error, LOG_ERR);
                $this->db->rollback();
                return -6;
            }
        }
        else
        {
            $this->error=$this->db->error()." sql=".$sql;
            dol_syslog("ExpenseReport.class::delete ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -4;
        }
    }

    /**
     * Set to status validate
     *
     * @param   User    $fuser      User
     * @return  int                 <0 if KO, >0 if OK
     */
    function setValidate($fuser)
    {
        global $conf,$langs;

        $this->oldref = $this->ref;
        $expld_car = (empty($conf->global->NDF_EXPLODE_CHAR))?"-":$conf->global->NDF_EXPLODE_CHAR;

        // Sélection de la date de début de la NDF
        $sql = 'SELECT date_debut';
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' WHERE rowid = '.$this->id;
        $result = $this->db->query($sql);
        $objp = $this->db->fetch_object($result);
        $this->date_debut = $this->db->jdate($objp->date_debut);

        $update_number_int = false;

        // Create next ref if ref is PROVxx
        // Rename directory if dir was a temporary ref
        if (preg_match('/^[\(]?PROV/i', $this->ref))
        {
            // Sélection du numéro de ref suivant
            $ref_next = $this->getNextNumRef();
            $ref_number_int = ($this->ref+1)-1;
            $update_number_int = true;
            // Création du ref_number suivant
            if($ref_next)
            {
                $prefix="ER";
                if (! empty($conf->global->EXPENSE_REPORT_PREFIX)) $prefix=$conf->global->EXPENSE_REPORT_PREFIX;
                $this->ref = str_replace(' ','_', $this->user_author_infos).$expld_car.$prefix.$this->ref.$expld_car.dol_print_date($this->date_debut,'%y%m%d');
            }
            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            // We rename directory in order to avoid losing the attachments
            $oldref = dol_sanitizeFileName($this->oldref);
            $newref = dol_sanitizeFileName($this->ref);
            $dirsource = $conf->expensereport->dir_output.'/'.$oldref;
            $dirdest = $conf->expensereport->dir_output.'/'.$newref;
            if (file_exists($dirsource))
            {
                dol_syslog(get_class($this)."::valid() rename dir ".$dirsource." into ".$dirdest);

                if (@rename($dirsource, $dirdest))
                {
                    dol_syslog("Rename ok");
                    // Rename docs starting with $oldref with $newref
                    $listoffiles=dol_dir_list($conf->expensereport->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
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
        if ($this->fk_statut != 2)
        {
        	$now = dol_now();

            $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET ref = '".$this->ref."', fk_statut = 2, fk_user_valid = ".$fuser->id.", date_valid='".$this->db->idate($now)."'";
            if ($update_number_int) {
                $sql.= ", ref_number_int = ".$ref_number_int;
            }
            $sql.= ' WHERE rowid = '.$this->id;
            
            $resql=$this->db->query($sql);
            if ($resql)
            {
                return 1;
            }
            else
            {
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::set_save expensereport already with save status", LOG_WARNING);
        }
    }

    /**
     * set_save_from_refuse
     *
     * @param   User    $fuser      User
     * @return  int                 <0 if KO, >0 if OK
     */
    function set_save_from_refuse($fuser)
    {
        global $conf,$langs;

        // Sélection de la date de début de la NDF
        $sql = 'SELECT date_debut';
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= ' WHERE rowid = '.$this->id;

        $result = $this->db->query($sql);

        $objp = $this->db->fetch_object($result);

        $this->date_debut = $this->db->jdate($objp->date_debut);

        if ($this->fk_statut != 2)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET fk_statut = 2";
            $sql.= ' WHERE rowid = '.$this->id;

            dol_syslog(get_class($this)."::set_save_from_refuse sql=".$sql, LOG_DEBUG);

            if ($this->db->query($sql))
            {
                return 1;
            }
            else
            {
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::set_save_from_refuse expensereport already with save status", LOG_WARNING);
        }
    }

    /**
     * Set status to approved
     *
     * @param   User    $fuser      User
     * @return  int                 <0 if KO, >0 if OK
     */
    function setApproved($fuser)
    {
        $now=dol_now();

        // date approval
        $this->date_approve = $this->db->idate($now);
        if ($this->fk_statut != 5)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET ref = '".$this->ref."', fk_statut = 5, fk_user_approve = ".$fuser->id.",";
            $sql.= " date_approve='".$this->date_approve."'";
            $sql.= ' WHERE rowid = '.$this->id;
            if ($this->db->query($sql))
            {
                return 1;
            }
            else
            {
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::set_valide expensereport already with valide status", LOG_WARNING);
        }
    }

    /**
     * setDeny
     *
     * @param User      $fuser      User
     * @param Details   $details    Details
     */
    function setDeny($fuser,$details)
    {
        $now = dol_now();

        // date de refus
        if ($this->fk_statut != 99)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET ref = '".$this->ref."', fk_statut = 99, fk_user_refuse = ".$fuser->id.",";
            $sql.= " date_refuse='".$this->db->idate($now)."',";
            $sql.= " detail_refuse='".$this->db->escape($details)."',";
            $sql.= " fk_user_approve = NULL";
            $sql.= ' WHERE rowid = '.$this->id;
            if ($this->db->query($sql))
            {
                $this->fk_statut = 99;
                $this->fk_user_refuse = $fuser->id;
                $this->detail_refuse = $details;
                $this->date_refuse = $now;
                return 1;
            }
            else
            {
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::setDeny expensereport already with refuse status", LOG_WARNING);
        }
    }

    /**
     * set_unpaid
     *
     * @param   User    $fuser      User
     * @return  int                 <0 if KO, >0 if OK
     */
    function set_unpaid($fuser)
    {
        if ($this->fk_c_deplacement_statuts != 5)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET fk_statut = 5";
            $sql.= ' WHERE rowid = '.$this->id;

            dol_syslog(get_class($this)."::set_unpaid sql=".$sql, LOG_DEBUG);

            if ($this->db->query($sql)):
            return 1;
            else:
            $this->error=$this->db->error();
            return -1;
            endif;
        }
        else
        {
            dol_syslog(get_class($this)."::set_unpaid expensereport already with unpaid status", LOG_WARNING);
        }
    }

    /**
     * set_cancel
     *
     * @param   User    $fuser      User
     * @param   string  $detail     Detail
     * @return  int                 <0 if KO, >0 if OK
     */
    function set_cancel($fuser,$detail)
    {
        $this->date_cancel = $this->db->idate(gmmktime());
        if ($this->fk_statut != 4)
        {
            $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
            $sql.= " SET fk_statut = 4, fk_user_cancel = ".$fuser->id;
            $sql.= ", date_cancel='".$this->date_cancel."'";
            $sql.= " ,detail_cancel='".$this->db->escape($detail)."'";
            $sql.= ' WHERE rowid = '.$this->id;

            dol_syslog(get_class($this)."::set_cancel sql=".$sql, LOG_DEBUG);

            if ($this->db->query($sql))
            {
                return 1;
            }
            else
            {
                $this->error=$this->db->error();
                return -1;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::set_cancel expensereport already with cancel status", LOG_WARNING);
        }
    }

    /**
     * Return next reference of expense report not already used
     *
     * @return    string            free ref
     */
    function getNextNumRef()
    {
        global $conf;

        $expld_car = (empty($conf->global->NDF_EXPLODE_CHAR))?"-":$conf->global->NDF_EXPLODE_CHAR;
        $num_car = (empty($conf->global->NDF_NUM_CAR_REF))?"5":$conf->global->NDF_NUM_CAR_REF;

        $sql = 'SELECT MAX(de.ref_number_int) as max';
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' de';
        
        $result = $this->db->query($sql);

        if($this->db->num_rows($result) > 0):
        $objp = $this->db->fetch_object($result);
        $this->ref = $objp->max;
        $this->ref++;
        while(strlen($this->ref) < $num_car):
        $this->ref = "0".$this->ref;
        endwhile;
        else:
        $this->ref = 1;
        while(strlen($this->ref) < $num_car):
        $this->ref = "0".$this->ref;
        endwhile;
        endif;

        if ($result):
        return 1;
        else:
        $this->error=$this->db->error();
        return -1;
        endif;
    }

    /**
     *  Return clicable name (with picto eventually)
     *
     *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
     *	@param		int		$max			Max length of shown ref
     *	@param		int		$short			1=Return just URL
     *	@param		string	$moretitle		Add more text to title tooltip
     *	@param		int		$notooltip		1=Disable tooltip
     *	@return		string					String with URL
     */
    function getNomUrl($withpicto=0,$max=0,$short=0,$moretitle='',$notooltip=0)
    {
        global $langs, $conf;

        $result='';

        $url = DOL_URL_ROOT.'/expensereport/card.php?id='.$this->id;

        if ($short) return $url;

        $picto='trip';
        $label = '<u>' . $langs->trans("ShowExpenseReport") . '</u>';
        if (! empty($this->ref))
            $label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        if (! empty($this->total_ht))
            $label.= '<br><b>' . $langs->trans('AmountHT') . ':</b> ' . price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_tva))
            $label.= '<br><b>' . $langs->trans('VAT') . ':</b> ' . price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
        if (! empty($this->total_ttc))
            $label.= '<br><b>' . $langs->trans('AmountTTC') . ':</b> ' . price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
        if ($moretitle) $label.=' - '.$moretitle;

        $ref=$this->ref;
        if (empty($ref)) $ref=$this->id;

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowExpenseReport");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip"';
        }

        $linkstart = '<a href="'.$url.'"';
        $linkstart.=$linkclose.'>';
        $linkend='</a>';

        if ($withpicto) $result.=($linkstart.img_object(($notooltip?'':$label), $picto, ($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).$linkend.' ');
        $result.=$linkstart.($max?dol_trunc($ref,$max):$ref).$linkend;
        return $result;
    }

    /**
     *  Update total of an expense report when you add a line.
     *
     *  @param    string    $ligne_total_ht    Amount without taxes
     *  @param    string    $ligne_total_tva    Amount of all taxes
     *  @return    void
     */
    function update_totaux_add($ligne_total_ht,$ligne_total_tva)
    {
        $this->total_ht = $this->total_ht + $ligne_total_ht;
        $this->total_tva = $this->total_tva + $ligne_total_tva;
        $this->total_ttc = $this->total_ht + $this->total_tva;

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " total_ht = ".$this->total_ht;
        $sql.= " , total_ttc = ".$this->total_ttc;
        $sql.= " , total_tva = ".$this->total_tva;
        $sql.= " WHERE rowid = ".$this->id;

        $result = $this->db->query($sql);
        if ($result):
        return 1;
        else:
        $this->error=$this->db->error();
        return -1;
        endif;
    }

    /**
     *  Update total of an expense report when you delete a line.
     *
     *  @param    string    $ligne_total_ht    Amount without taxes
     *  @param    string    $ligne_total_tva    Amount of all taxes
     *  @return    void
     */
    function update_totaux_del($ligne_total_ht,$ligne_total_tva)
    {
        $this->total_ht = $this->total_ht - $ligne_total_ht;
        $this->total_tva = $this->total_tva - $ligne_total_tva;
        $this->total_ttc = $this->total_ht + $this->total_tva;

        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " total_ht = ".$this->total_ht;
        $sql.= " , total_ttc = ".$this->total_ttc;
        $sql.= " , total_tva = ".$this->total_tva;
        $sql.= " WHERE rowid = ".$this->id;

        $result = $this->db->query($sql);
        if ($result):
        return 1;
        else:
        $this->error=$this->db->error();
        return -1;
        endif;
    }


    /**
     * updateline
     *
     * @param   int         $rowid                  Line to edit
     * @param   int         $type_fees_id           Type payment
     * @param   int         $projet_id              Project id
     * @param   double      $vatrate                Vat rate
     * @param   string      $comments               Description
     * @param   real        $qty                    Qty
     * @param   double      $value_unit             Value init
     * @param   int         $date                   Date
     * @param   int         $expensereport_id       Expense report id
     * @return  int                                 <0 if KO, >0 if OK
     */
    function updateline($rowid, $type_fees_id, $projet_id, $vatrate, $comments, $qty, $value_unit, $date, $expensereport_id)
    {
        global $user;

        if ($this->fk_statut==0 || $this->fk_statut==99)
        {
            $this->db->begin();

            // calcul de tous les totaux de la ligne
            $total_ttc  = price2num($qty*$value_unit, 'MT');

            $tx_tva = $vatrate / 100;
            $tx_tva = $tx_tva + 1;
            $total_ht   = price2num($total_ttc/$tx_tva, 'MT');

            $total_tva = price2num($total_ttc - $total_ht, 'MT');
            // fin calculs

            $ligne = new ExpenseReportLine($this->db);
            $ligne->comments        = $comments;
            $ligne->qty             = $qty;
            $ligne->value_unit      = $value_unit;
            $ligne->date            = $date;

            $ligne->fk_expensereport= $expensereport_id;
            $ligne->fk_c_type_fees  = $type_fees_id;
            $ligne->fk_projet       = $projet_id;

            $ligne->total_ht        = $total_ht;
            $ligne->total_tva       = $total_tva;
            $ligne->total_ttc       = $total_ttc;
            $ligne->vatrate         = price2num($vatrate);
            $ligne->rowid           = $rowid;

            // Select des infos sur le type fees
            $sql = "SELECT c.code as code_type_fees, c.label as libelle_type_fees";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_type_fees as c";
            $sql.= " WHERE c.id = ".$type_fees_id;
            $result = $this->db->query($sql);
            $objp_fees = $this->db->fetch_object($result);
            $ligne->type_fees_code      = $objp_fees->code_type_fees;
            $ligne->type_fees_libelle   = $objp_fees->libelle_type_fees;

            // Select des informations du projet
            $sql = "SELECT p.ref as ref_projet, p.title as title_projet";
            $sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
            $sql.= " WHERE p.rowid = ".$projet_id;
            $result = $this->db->query($sql);
            if ($result) {
            	$objp_projet = $this->db->fetch_object($result);
            }
            $ligne->projet_ref          = $objp_projet->ref_projet;
            $ligne->projet_title        = $objp_projet->title_projet;

            $result = $ligne->update($user);
            if ($result > 0)
            {
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->error=$ligne->error;
                $this->errors=$ligne->errors;
                $this->db->rollback();
                return -2;
            }
        }
    }

    /**
     * deleteline
     *
     * @param   int     $rowid      Row id
     * @param   User    $fuser      User
     * @return  int                 <0 if KO, >0 if OK
     */
    function deleteline($rowid, $fuser='')
    {
        $this->db->begin();

        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element_line;
        $sql.= ' WHERE rowid = '.$rowid;

        dol_syslog(get_class($this)."::deleteline sql=".$sql);
        $result = $this->db->query($sql);
        if (!$result)
        {
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::deleteline  Error ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();

        return 1;
    }

    /**
     * periode_existe
     *
     * @param   User    $fuser          User
     * @param   Date    $date_debut     Start date
     * @param   Date    $date_fin       End date
     * @return  int                     <0 if KO, >0 if OK
     */
    function periode_existe($fuser, $date_debut, $date_fin)
    {
        $sql = "SELECT rowid, date_debut, date_fin";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE fk_user_author = '{$fuser->id}'";

        dol_syslog(get_class($this)."::periode_existe sql=".$sql);
        $result = $this->db->query($sql);
        if($result)
        {
            $num_lignes = $this->db->num_rows($result); $i = 0;

            if ($num_lignes>0)
            {
                $date_d_form = $date_debut;
                $date_f_form = $date_fin;

                $existe = false;

                while ($i < $num_lignes)
                {
                    $objp = $this->db->fetch_object($result);

                    $date_d_req = $this->db->jdate($objp->date_debut); // 3
                    $date_f_req = $this->db->jdate($objp->date_fin);      // 4

                    if (!($date_f_form < $date_d_req || $date_d_form > $date_f_req)) $existe = true;

                    $i++;
                }

                if($existe) return 1;
                else return 0;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::periode_existe  Error ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     * Return list of people with permission to validate expense reports.
     * Search for permission "approve expense report"
     *
     * @return  array       Array of user ids
     */
    function fetch_users_approver_expensereport()
    {
        $users_validator=array();

        $sql = "SELECT DISTINCT ur.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."user_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
        $sql.= " WHERE ur.fk_id = rd.id and rd.module = 'expensereport' AND rd.perms = 'approve'";                                              // Permission 'Approve';
        $sql.= "UNION";
        $sql.= " SELECT DISTINCT ugu.fk_user";
        $sql.= " FROM ".MAIN_DB_PREFIX."usergroup_user as ugu, ".MAIN_DB_PREFIX."usergroup_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
        $sql.= " WHERE ugu.fk_usergroup = ur.fk_usergroup AND ur.fk_id = rd.id and rd.module = 'expensereport' AND rd.perms = 'approve'";       // Permission 'Approve';
        //print $sql;
        
        dol_syslog(get_class($this)."::fetch_users_approver_expensereport sql=".$sql);
        $result = $this->db->query($sql);
        if($result)
        {
            $num_lignes = $this->db->num_rows($result); $i = 0;
            while ($i < $num_lignes)
            {
                $objp = $this->db->fetch_object($result);
                array_push($users_validator,$objp->fk_user);
                $i++;
            }
            return $users_validator;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch_users_approver_expensereport  Error ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Create a document onto disk accordign to template module.
     *
     *  @param      string      $modele         Force le mnodele a utiliser ('' to not force)
     *  @param      Translate   $outputlangs    objet lang a utiliser pour traduction
     *  @param      int         $hidedetails    Hide details of lines
     *  @param      int         $hidedesc       Hide description
     *  @param      int         $hideref        Hide ref
     *  @return     int                         0 if KO, 1 if OK
     */
    public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
    {
        global $conf,$langs;

        $langs->load("trips");

        // Positionne le modele sur le nom du modele a utiliser
        if (! dol_strlen($modele))
        {
            if (! empty($conf->global->EXPENSEREPORT_ADDON_PDF))
            {
                $modele = $conf->global->EXPENSEREPORT_ADDON_PDF;
            }
            else
            {
                $modele = 'standard';
            }
        }

        $modelpath = "core/modules/expensereport/doc/";

        return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
    }

    /**
     * List of types
     *
     * @param   int     $active     Active or not
     * @return  array
     */
    function listOfTypes($active=1)
    {
        global $langs;
        $ret=array();
        $sql = "SELECT id, code, label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_type_fees";
        $sql.= " WHERE active = ".$active;
        dol_syslog(get_class($this)."::listOfTypes", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ( $result )
        {
            $num = $this->db->num_rows($result);
            $i=0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                $ret[$obj->code]=(($langs->trans($obj->code)!=$obj->code)?$langs->trans($obj->code):$obj->label);
                $i++;
            }
        }
        else
        {
            dol_print_error($this->db);
        }
        return $ret;
    }

	/**
     *      Charge indicateurs this->nb pour le tableau de bord
     *
     *      @return     int         <0 if KO, >0 if OK
     */
    function load_state_board()
    {
        global $conf;

        $this->nb=array();

        $sql = "SELECT count(ex.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."expensereport as ex";
        $sql.= " WHERE ex.fk_statut > 0";
        $sql.= " AND ex.entity IN (".getEntity('expensereport', 1).")";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nb["expensereports"]=$obj->nb;
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
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *      @param	User	$user   		Objet user
     *      @param  string  $option         'topay' or 'toapprove'
     *      @return WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
     */
    function load_board($user, $option='topay')
    {
        global $conf, $langs;

        if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

	    $now=dol_now();

	    $userchildids = $user->getAllChildIds(1);
	    
        $sql = "SELECT ex.rowid, ex.date_valid";
        $sql.= " FROM ".MAIN_DB_PREFIX."expensereport as ex";
        if ($option == 'toapprove') $sql.= " WHERE ex.fk_statut = 2";
        else $sql.= " WHERE ex.fk_statut = 5";
        $sql.= " AND ex.entity IN (".getEntity('expensereport', 1).")";
        $sql.= " AND (ex.fk_user_author IN (".join(',',$userchildids).")";
        $sql.= " OR ex.fk_user_validator IN (".join(',',$userchildids)."))";

        $resql=$this->db->query($sql);
        if ($resql)
        {
	        $langs->load("members");

	        $response = new WorkboardResponse();
	        if ($option == 'toapprove')
	        {
	           $response->warning_delay=$conf->expensereport->approve->warning_delay/60/60/24;
	           $response->label=$langs->trans("ExpenseReportsToApprove");
	           $response->url=DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm&amp;statut=2';
	        }
	        else
	        {
	            $response->warning_delay=$conf->expensereport->payment->warning_delay/60/60/24;
	            $response->label=$langs->trans("ExpenseReportsToPay");
	            $response->url=DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm&amp;statut=5';
	        }
	        $response->img=img_object($langs->trans("ExpenseReports"),"trip");

            while ($obj=$this->db->fetch_object($resql))
            {
	            $response->nbtodo++;
                
	            if ($option == 'toapprove')
	            {
	                if ($this->db->jdate($obj->date_valid) < ($now - $conf->expensereport->approve->warning_delay)) {
	                    $response->nbtodolate++;
	                }
	            }
	            else
	            {
                    if ($this->db->jdate($obj->date_valid) < ($now - $conf->expensereport->payment->warning_delay)) {
    	                $response->nbtodolate++;
                    }
	            }
            }

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
     * Return if an expense report is late or not
     *
     * @param  string  $option          'topay' or 'toapprove'
     * @return boolean                  True if late, False if not late
     */
    public function hasDelay($option)
    {
        global $conf;
    
        //Only valid members
        if ($option == 'toapprove' && $this->status != 2) return false;
        if ($option == 'topay' && $this->status != 5) return false;
    
        $now = dol_now();
        if ($option == 'toapprove')
        {
            return ($this->datevalid?$this->datevalid:$this->date_valid) < ($now - $conf->expensereport->approve->warning_delay);
        }
        else
            return ($this->datevalid?$this->datevalid:$this->date_valid) < ($now - $conf->expensereport->payment->warning_delay);
    }    
}


/**
 * Class of expense report details lines
 */
class ExpenseReportLine
{
    var $db;
    var $error;

    var $rowid;
    var $comments;
    var $qty;
    var $value_unit;
    var $date;

    var $fk_c_type_fees;
    var $fk_projet;
    var $fk_expensereport;

    var $type_fees_code;
    var $type_fees_libelle;

    var $projet_ref;
    var $projet_title;

    var $vatrate;
    var $total_ht;
    var $total_tva;
    var $total_ttc;

    /**
     * Constructor
     *
     * @param DoliDB    $db     Handlet database
     */
    function __construct($db)
    {
        $this->db= $db;
    }

    /**
     * Fetch record for expense report detailed line
     *
     * @param   int     $rowid      Id of object to load
     * @return  int                 <0 if KO, >0 if OK
     */
    function fetch($rowid)
    {
        $sql = 'SELECT fde.rowid, fde.fk_expensereport, fde.fk_c_type_fees, fde.fk_projet, fde.date,';
        $sql.= ' fde.tva_tx as vatrate, fde.comments, fde.qty, fde.value_unit, fde.total_ht, fde.total_tva, fde.total_ttc,';
        $sql.= ' ctf.code as type_fees_code, ctf.label as type_fees_libelle,';
        $sql.= ' pjt.rowid as projet_id, pjt.title as projet_title, pjt.ref as projet_ref';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'expensereport_det as fde';
        $sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_type_fees as ctf ON fde.fk_c_type_fees=ctf.id';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as pjt ON fde.fk_projet=pjt.rowid';
        $sql.= ' WHERE fde.rowid = '.$rowid;

        $result = $this->db->query($sql);

        if($result)
        {
            $objp = $this->db->fetch_object($result);

            $this->rowid = $objp->rowid;
            $this->id = $obj->rowid;
            $this->ref = $obj->ref;
            $this->fk_expensereport = $objp->fk_expensereport;
            $this->comments = $objp->comments;
            $this->qty = $objp->qty;
            $this->date = $objp->date;
            $this->value_unit = $objp->value_unit;
            $this->fk_c_type_fees = $objp->fk_c_type_fees;
            $this->fk_projet = $objp->fk_projet;
            $this->type_fees_code = $objp->type_fees_code;
            $this->type_fees_libelle = $objp->type_fees_libelle;
            $this->projet_ref = $objp->projet_ref;
            $this->projet_title = $objp->projet_title;
            $this->vatrate = $objp->vatrate;
            $this->total_ht = $objp->total_ht;
            $this->total_tva = $objp->total_tva;
            $this->total_ttc = $objp->total_ttc;

            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     * insert
     *
     * @param   int     $notrigger      1=No trigger
     * @return  int                     <0 if KO, >0 if OK
     */
    function insert($notrigger=0)
    {
        global $langs,$user,$conf;

        $error=0;

        dol_syslog("ExpenseReportLine::Insert rang=".$this->rang, LOG_DEBUG);

        // Clean parameters
        $this->comments=trim($this->comments);
        if (!$this->value_unit_HT) $this->value_unit_HT=0;
        $this->qty = price2num($this->qty);
        $this->vatrate = price2num($this->vatrate);

        $this->db->begin();

        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.'expensereport_det';
        $sql.= ' (fk_expensereport, fk_c_type_fees, fk_projet,';
        $sql.= ' tva_tx, comments, qty, value_unit, total_ht, total_tva, total_ttc, date)';
        $sql.= " VALUES (".$this->fk_expensereport.",";
        $sql.= " ".$this->fk_c_type_fees.",";
        $sql.= " ".($this->fk_projet>0?$this->fk_projet:'null').",";
        $sql.= " ".$this->vatrate.",";
        $sql.= " '".$this->db->escape($this->comments)."',";
        $sql.= " ".$this->qty.",";
        $sql.= " ".$this->value_unit.",";
        $sql.= " ".$this->total_ht.",";
        $sql.= " ".$this->total_tva.",";
        $sql.= " ".$this->total_ttc.",";
        $sql.= "'".$this->db->idate($this->date)."'";
        $sql.= ")";

        dol_syslog("ExpenseReportLine::insert sql=".$sql);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'expensereport_det');

            $tmpparent=new ExpenseReport($this->db);
            $tmpparent->fetch($this->fk_expensereport);
            $result = $tmpparent->update_price();
            if ($result < 0)
            {
                $error++;
                $this->error = $tmpparent->error;
                $this->errors = $tmpparent->errors;
            }
        }

        if (! $error)
        {
            $this->db->commit();
            return $this->rowid;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog("ExpenseReportLine::insert Error ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }

    /**
     * update
     *
     * @param   User    $fuser      User
     * @return  int                 <0 if KO, >0 if OK
     */
    function update($fuser)
    {
        global $fuser,$langs,$conf;

        $error=0;

        // Clean parameters
        $this->comments=trim($this->comments);
        $this->vatrate = price2num($this->vatrate);
        $this->value_unit = price2num($this->value_unit);

        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE ".MAIN_DB_PREFIX."expensereport_det SET";
        $sql.= " comments='".$this->db->escape($this->comments)."'";
        $sql.= ",value_unit=".$this->value_unit."";
        $sql.= ",qty=".$this->qty."";
        $sql.= ",date='".$this->db->idate($this->date)."'";
        $sql.= ",total_ht=".$this->total_ht."";
        $sql.= ",total_tva=".$this->total_tva."";
        $sql.= ",total_ttc=".$this->total_ttc."";
        $sql.= ",tva_tx=".$this->vatrate;
        if ($this->fk_c_type_fees) $sql.= ",fk_c_type_fees=".$this->fk_c_type_fees;
        else $sql.= ",fk_c_type_fees=null";
        if ($this->fk_projet) $sql.= ",fk_projet=".$this->fk_projet;
        else $sql.= ",fk_projet=null";
        $sql.= " WHERE rowid = ".$this->rowid;

        dol_syslog("ExpenseReportLine::update sql=".$sql);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $tmpparent=new ExpenseReport($this->db);
            $result = $tmpparent->fetch($this->fk_expensereport);
            if ($result > 0)
            {
                $result = $tmpparent->update_price();
                if ($result < 0)
                {
                    $error++;
                    $this->error = $tmpparent->error;
                    $this->errors = $tmpparent->errors;
                }
            }
            else
            {
                $error++;
                $this->error = $tmpparent->error;
                $this->errors = $tmpparent->errors;
            }
        }
        else
        {
            $error++;
            dol_print_error($this->db);
        }

        if (! $error)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog("ExpenseReportLine::update Error ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }
}


/**
 *    Retourne la liste deroulante des differents etats d'une note de frais.
 *    Les valeurs de la liste sont les id de la table c_expensereport_statuts
 *
 *    @param    int     $selected       preselect status
 *    @param    string  $htmlname       Name of HTML select
 *    @param    int     $useempty       1=Add empty line
 *    @param    int     $useshortlabel  Use short labels
 *    @return   string                  HTML select with status
 */
function select_expensereport_statut($selected='',$htmlname='fk_statut',$useempty=1, $useshortlabel=0)
{
    global $db, $langs;

    $tmpep=new ExpenseReport($db);

    print '<select class="flat" name="'.$htmlname.'">';
    if ($useempty) print '<option value="-1">&nbsp;</option>';
    $arrayoflabels=$tmpep->statuts;
    if ($useshortlabel) $arrayoflabels=$tmpep->statuts_short;
    foreach ($arrayoflabels as $key => $val)
    {
        if ($selected != '' && $selected == $key)
        {
            print '<option value="'.$key.'" selected>';
        }
        else
        {
            print '<option value="'.$key.'">';
        }
        print $langs->trans($val);
        print '</option>';
    }
    print '</select>';
}

/**
 *  Return list of types of notes with select value = id
 *
 *  @param      int     $selected       Preselected type
 *  @param      string  $htmlname       Name of field in form
 *  @param      int     $showempty      Add an empty field
 *  @param      int     $active         1=Active only, 0=Unactive only, -1=All
 *  @return     string                  Select html
 */
function select_type_fees_id($selected='',$htmlname='type',$showempty=0, $active=1)
{
    global $db,$langs,$user;
    $langs->load("trips");

    print '<select class="flat" name="'.$htmlname.'">';
    if ($showempty)
    {
        print '<option value="-1"';
        if ($selected == -1) print ' selected';
        print '>&nbsp;</option>';
    }

    $sql = "SELECT c.id, c.code, c.label as type FROM ".MAIN_DB_PREFIX."c_type_fees as c";
    if ($active >= 0) $sql.= " WHERE c.active = ".$active;
    $sql.= " ORDER BY c.label ASC";
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            print '<option value="'.$obj->id.'"';
            if ($obj->code == $selected || $obj->id == $selected) print ' selected';
            print '>';
            if ($obj->code != $langs->trans($obj->code)) print $langs->trans($obj->code);
            else print $langs->trans($obj->type);
            $i++;
        }
    }
    print '</select>';
}
