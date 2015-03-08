<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Ion Agorria          <ion@agorria.com>
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
 *	\file       htdocs/comm/mailing/class/mailing.class.php
 *	\ingroup    mailing
 *	\brief      File of class to manage emailings module
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *	Class to manage emailings module
 */
class Mailing extends CommonObject
{
    public $element='mailing';
    public $table_element='mailing';

    var $id;
    var $statut;
    var $titre; //TODO Deprecated
    var $description;
    var $sujet; //TODO Deprecated
    var $subject;
    var $body;
    var $line;
    var $nbemail;
    var $bgcolor;
    var $bgimage;
    var $mail_type;

    var $email_from;
    var $email_replyto;
    var $email_errorsto;

    var $joined_file1;
    var $joined_file2;
    var $joined_file3;
    var $joined_file4;

    var $user_creat;
    var $user_valid;

    var $date_creat;
    var $date_valid;

    var $extraparams=array();

    // List of language codes for status
    public $statuts=array(
        0 => 'MailingStatusDraft',
        1 => 'MailingStatusValidated',
        2 => 'MailingStatusSentPartialy',
        3 => 'MailingStatusSentCompletely',
    );
    public static $statut_dest=array(
        -1 => 'MailingStatusError',
        1  => 'MailingStatusSent',
        2  => 'MailingStatusRead',
        3  => 'MailingStatusNotContact',
    );

    /**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Create an EMailing
     *
     *  @param	User	$user 		Object of user making creation
     *  @return int	   				-1 if error, Id of created object if OK
     */
    function create($user)
    {
        global $conf, $langs;

        $this->db->begin();

        $this->description=trim($this->description);
        $this->email_from=trim($this->email_from);

        if (! $this->email_from)
        {
            $this->error = $langs->trans("ErrorMailFromRequired");
            $this->errors[] = $langs->trans("ErrorMailFromRequired");
            return -1;
        }

        $now=dol_now();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing";
        $sql .= " (date_creat, fk_user_creat, entity, mail_type)";
        $sql .= " VALUES ('".$this->db->idate($now)."', ".$user->id.", ".$conf->entity.", ".$this->mail_type.")";

        if (! $this->description)
        {
            $this->description = $langs->trans("NoTitle");
        }

        dol_syslog("Mailing::Create", LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mailing");

            if ($this->update($user) > 0)
            {
                $this->db->commit();
            }
            else
            {
                $this->error=$this->db->lasterror();
                $this->errors[]=$this->db->lasterror();
                $this->db->rollback();
                return -1;
            }

            return $this->id;
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
     *  Update emailing record
     *
     *  @param	User	$user 		Object of user making change
     *  @return int				    < 0 if KO, > 0 if OK
     */
    function update($user)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."mailing ";
        $sql .= " SET titre = '".$this->db->escape($this->description)."'";
        $sql .= ", sujet = '".$this->db->escape($this->subject)."'";
        $sql .= ", body = '".$this->db->escape($this->body)."'";
        $sql .= ", line = '".$this->db->escape($this->line)."'";
        $sql .= ", email_from = '".$this->email_from."'";
        $sql .= ", email_replyto = '".$this->email_replyto."'";
        $sql .= ", email_errorsto = '".$this->email_errorsto."'";
        $sql .= ", bgcolor = '".($this->bgcolor?$this->bgcolor:null)."'";
        $sql .= ", bgimage = '".($this->bgimage?$this->bgimage:null)."'";
        $sql .= " WHERE rowid = ".$this->id;

        dol_syslog("Mailing::Update", LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->errors[]=$this->db->lasterror();
            return -1;
        }
    }

    /**
     *	Get object from database
     *
     *	@param	int		$rowid      Id of emailing
     *	@return	int					<0 if KO, >0 if OK
     */
    function fetch($rowid)
    {
        global $conf;

        $sql = "SELECT m.rowid, m.titre, m.sujet, m.body, m.line, m.bgcolor, m.bgimage";
        $sql.= ", m.email_from, m.email_replyto, m.email_errorsto";
        $sql.= ", m.mail_type, m.statut, m.nbemail";
        $sql.= ", m.fk_user_creat, m.fk_user_valid";
        $sql.= ", m.date_creat";
        $sql.= ", m.date_valid";
        $sql.= ", m.date_envoi";
        $sql.= ", m.extraparams";
        $sql.= " FROM ".MAIN_DB_PREFIX."mailing as m";
        $sql.= " WHERE m.rowid = ".$rowid;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);

                $this->id				= $obj->rowid;
                $this->ref				= $obj->rowid;
                $this->statut			= $obj->statut;
                $this->nbemail			= $obj->nbemail;
                $this->titre			= $obj->titre; //TODO Deprecated
                $this->description		= $obj->titre;
                $this->mail_type		= $obj->mail_type;

                $this->sujet			= $obj->sujet; //TODO Deprecated
                $this->subject			= $obj->sujet;
                if (!empty($conf->global->FCKEDITOR_ENABLE_MAILING) && dol_textishtml(dol_html_entity_decode($obj->body, ENT_COMPAT | ENT_HTML401))) {
                    $this->body				= dol_html_entity_decode($obj->body, ENT_COMPAT | ENT_HTML401);
                }else {
                    $this->body				= $obj->body;
                }
                if (!empty($conf->global->FCKEDITOR_ENABLE_MAILING) && dol_textishtml(dol_html_entity_decode($obj->line, ENT_COMPAT | ENT_HTML401))) {
                    $this->line				= dol_html_entity_decode($obj->line, ENT_COMPAT | ENT_HTML401);
                }else {
                    $this->line				= $obj->line;
                }

                $this->bgcolor			= $obj->bgcolor;
                $this->bgimage			= $obj->bgimage;

                $this->email_from		= $obj->email_from;
                $this->email_replyto	= $obj->email_replyto;
                $this->email_errorsto	= $obj->email_errorsto;

                $this->user_creat		= $obj->fk_user_creat;
                $this->user_valid		= $obj->fk_user_valid;

                $this->date_creat		= $this->db->jdate($obj->date_creat);
                $this->date_valid		= $this->db->jdate($obj->date_valid);
                $this->date_envoi		= $this->db->jdate($obj->date_envoi);

                $this->extraparams		= (array) json_decode($obj->extraparams, true);

                return 1;
            }
            else
            {
                dol_syslog(get_class($this)."::fetch Error -1");
                return -1;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::fetch Error -2");
            return -2;
        }
    }


    /**
     *	Load an object from its id and create a new one in database
     *
     *	@param  int		$fromid     	Id of object to clone
     *	@param	int		$option1		1=Copy content, 0=Forget content
     *	@param	int		$option2		Not used
     *	@return	int						New id of clone
     */
    function createFromClone($fromid,$option1,$option2)
    {
        global $user,$langs;

        $error=0;

        $object=new Mailing($this->db);

        $this->db->begin();

        // Load source object
        $object->fetch($fromid);
        $object->id=0;
        $object->statut=0;

        // Clear fields
        $object->description=$langs->trans("CopyOf").' '.$object->description.' '.dol_print_date(dol_now());

        // If no option copy content
        if (empty($option1))
        {
            // Clear values
            $object->nbemail            = 0;
            $object->subject              = '';
            $object->body               = '';
            $object->line               = '';
            $object->bgcolor            = '';
            $object->bgimage            = '';

            $object->email_from         = '';
            $object->email_replyto      = '';
            $object->email_errorsto     = '';

            $object->user_creat         = $user->id;
            $object->user_valid         = '';

            $object->date_creat         = '';
            $object->date_valid         = '';
            $object->date_envoi         = '';
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
            //Clone target
            if (!empty($option2)) {

                require_once DOL_DOCUMENT_ROOT .'/core/modules/mailings/modules_mailings.php';

                $mailing_target = new MailingTargets($this->db);

                $target_array=array();

                $sql = "SELECT fk_contact, ";
                $sql.=" lastname,   ";
                $sql.=" firstname,";
                $sql.=" email,";
                $sql.=" other,";
                $sql.=" source_url,";
                $sql.=" source_id ,";
                $sql.=" source_type ";
                $sql.= " FROM ".MAIN_DB_PREFIX."mailing_cibles ";
                $sql.= " WHERE fk_mailing = ".$fromid;

                dol_syslog(get_class($this)."::createFromClone", LOG_DEBUG);
                $result=$this->db->query($sql);
                if ($result)
                {
                    if ($this->db->num_rows($result))
                    {
                        while ($obj = $this->db->fetch_object($result)) {

                            $target_array[]=array('fk_contact'=>$obj->fk_contact,
                            'lastname'=>$obj->lastname,
                            'firstname'=>$obj->firstname,
                            'email'=>$obj->email,
                            'other'=>$obj->other,
                            'source_url'=>$obj->source_url,
                            'source_id'=>$obj->source_id,
                            'source_type'=>$obj->source_type);
                        }

                    }
                }
                else
                {
                    $this->error=$this->db->lasterror();
                    $this->errors[]=$this->db->lasterror();
                    return -1;
                }

                $mailing_target->add_to_target($object->id, $target_array);
            }

        }

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
     *  Validate emailing
     *
     *  @param	User	$user      	Objet user qui valide
     * 	@return	int					<0 if KO, >0 if OK
     */
    function valid($user)
    {
        $now=dol_now();

        $sql = "UPDATE ".MAIN_DB_PREFIX."mailing ";
        $sql .= " SET statut = 1, date_valid = '".$this->db->idate($now)."', fk_user_valid=".$user->id;
        $sql .= " WHERE rowid = ".$this->id;

        dol_syslog("Mailing::valid", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->errors[]=$this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Delete emailing
     *
     *  @param	int		$rowid      id du mailing a supprimer
     *  @return int         		1 en cas de succes
     */
    function delete($rowid)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing";
        $sql.= " WHERE rowid = ".$rowid;

        dol_syslog("Mailing::delete", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->errors[]=$this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Change status of each recipient
     *
     *	@param	User	$user      	Objet user qui valide
     *  @return int         		<0 if KO, >0 if OK
     */
    function reset_targets_status($user)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
        $sql.= " SET statut = 0";
        $sql.= " WHERE fk_mailing = ".$this->id;

        dol_syslog("Mailing::reset_targets_status", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->errors[]=$this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Retourne le libelle du statut d'un mailing (brouillon, validee, ...
     *
     *  @param	int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
     *  @return string        			Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    /**
     *  Renvoi le libelle d'un statut donne
     *
     *  @param	int		$statut        	Id statut
     *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *  @return string        			Label
     */
    function LibStatut($statut,$mode=0)
    {
        global $langs;
        $langs->load('mails');

        if ($mode == 0)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        if ($mode == 1)
        {
            return $langs->trans($this->statuts[$statut]);
        }
        if ($mode == 2)
        {
            if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
            if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut1').' '.$langs->trans($this->statuts[$statut]);
            if ($statut == 2) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]);
            if ($statut == 3) return img_picto($langs->trans($this->statuts[$statut]),'statut6').' '.$langs->trans($this->statuts[$statut]);
        }
        if ($mode == 3)
        {
            if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut0');
            if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut1');
            if ($statut == 2) return img_picto($langs->trans($this->statuts[$statut]),'statut3');
            if ($statut == 3) return img_picto($langs->trans($this->statuts[$statut]),'statut6');
        }
        if ($mode == 4)
        {
            if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
            if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut1').' '.$langs->trans($this->statuts[$statut]);
            if ($statut == 2) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]);
            if ($statut == 3) return img_picto($langs->trans($this->statuts[$statut]),'statut6').' '.$langs->trans($this->statuts[$statut]);
        }
        if ($mode == 5)
        {
            if ($statut == 0)  return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut0');
            if ($statut == 1)  return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut1');
            if ($statut == 2)  return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut3');
            if ($statut == 3)  return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut6');
        }
        return "";
    }


    /**
     *  Renvoi le libelle d'un statut donne
     *
     *  @param	int		$statut        	Id statut
     *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *  @return string        			Label
     */
    static public function libStatutDest($statut,$mode=0)
    {
        global $langs;
        $langs->load('mails');

        if ($mode == 0)
        {
            return $langs->trans(Mailing::$statut_dest[$statut]);
        }
        if ($mode == 1)
        {
            return $langs->trans(Mailing::$statut_dest[$statut]);
        }
        if ($mode == 2)
        {
            if ($statut ==-1) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_error();
            if ($statut == 1) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusSent"),'statut4');
            if ($statut == 2) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusRead"),'statut6');
            if ($statut == 3) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusNotContact"),'statut8');
        }
        if ($mode == 3)
        {
            if ($statut ==-1) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_error();
            if ($statut == 1) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusSent"),'statut4');
            if ($statut == 2) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusRead"),'statut6');
            if ($statut == 3) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusNotContact"),'statut8');
        }
        if ($mode == 4)
        {
            if ($statut ==-1) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_error();
            if ($statut == 1) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusSent"),'statut4');
            if ($statut == 2) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusRead"),'statut6');
            if ($statut == 3) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusNotContact"),'statut8');
        }
        if ($mode == 5)
        {
            if ($statut ==-1) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_error();
            if ($statut == 1) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusSent"),'statut4');
            if ($statut == 2) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusRead"),'statut6');
            if ($statut == 3) return $langs->trans(Mailing::$statut_dest[$statut]).' '.img_picto($langs->trans("MailingStatusNotContact"),'statut8');
        }
        return "";
    }

    /**
     *  Handles mail substitutions
     *
     *  @param	int		$mode 			0 = Title, 1 = Body, 2 = Line
     *  @param	Object	$obj			Object to get data
     *  @return string					-1 if error, Id of created object if OK
     */
    function handleSubstitutions($mode, $obj)
    {
        global $conf, $langs;
        $text = "";

        // Fill substitution array with data from $obj depending of mail type
        if ($mode == 0 || $mode == 1) {
            $text = $mode == 0 ? $this->subject: $this->body;
            if ($this->mail_type == 0) {
                $substitutions = array(
                    //Common
                    '__ID__' => $obj->source_id,
                    '__EMAIL__' => $obj->email,
                    '__MAILTOEMAIL__' => '<a href="mailto:'.$obj->email.'">'.$obj->email.'</a>',
                    //Specific
                    '__LASTNAME__' => $obj->lastname,
                    '__FIRSTNAME__' => $obj->firstname,
                    '__CHECK_READ__' => '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$obj->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>',
                    '__UNSUBSCRIBE__' => '<a href="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-unsubscribe.php?tag='.$obj->tag.'&unsuscrib=1&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" target="_blank">'.$langs->trans("MailUnsubcribe") . '</a>',
                    //'__OTHER_FIELD__' Done dinamically
                );

                // create dinamic tags for __OTHER_FIELD__
                $other = explode(';', $obj->other);
                foreach ($other as $pair) {
                    $pair = explode('=', $pair);
                    $substitutions['__OTHER_' . strtoupper($pair[0]) . '__'] = $pair[1];
                }
            } else if ($this->mail_type == 1) {
                //$obj should be a CommandeFournisseur
                $soc = new Societe($this->db);
                $soc->fetch($obj->socid);
                $lines = "";
                if ($mode == 1) { //Only show lines in body
                    foreach ($obj->lines as $index=>$line) {
                        $line_obj = array($index, $line);
                        $lines = $lines . $this->handleSubstitutions(2, $line_obj) . '\n';
                    }
                }
                $substitutions = array(
                    //Common
                    '__ID__' => $obj->id,
                    '__EMAIL__' => $soc->email,
                    '__MAILTOEMAIL__' => '<a href="mailto:'.$soc->email.'">'.$soc->email.'</a>',
                    //Specific
                    '__REF__' => $obj->ref,
                    '__REF_SUPPLIER__' => $obj->ref_supplier,
                    '__THIRDPARTY_NAME__' => $soc->name,
                    '__LINES__' => $lines,
                    //'__EXTRAFIELD_ORDER_FIELD__' Done dinamically
                );
                // Create dinamic tags for __EXTRAFIELD_ORDER_FIELD__
                $extrafields = new ExtraFields($this->db);
                $extralabels=$extrafields->fetch_name_optionals_label($obj->table_element,true);
                $obj->fetch_optionals($obj->id, $extralabels);
                foreach ($extrafields->attribute_label as $key=>$label)
                {
                    $substitutions['__EXTRAFIELD_ORDER_' . strtoupper($key) . '__'] = $obj->array_options['options_'.$key];
                }
            }
        } else if ($mode == 2) {
            $text = $this -> line;
            if ($this->mail_type == 1) {
                //$obj should be a array(index, line)
                $format = "";
                $line = $obj[1];
                $product = new Product($this->db);
                $product->fetch($line->fk_product, '', '', 1);
                $substitutions = array(
                    '__NUM__' => $obj[0],
                    '__REF__' => $line->product_ref,
                    '__REF_SUPPLIER__' => $line->ref_supplier,
                    '__LABEL__' => $line->product_label,
                    '__DESCRIPTION__' => $line->product_desc,
                    '__DATE_START__' => dol_print_date($line->date_start, $format),
                    '__DATE_END__' => dol_print_date($line->date_end, $format),
                    '__QUANTITY__' => $line->qty,
                    '__TOTAL__' => $line->total_ttc,
                    //'__EXTRAFIELD_PRODUCT_FIELD__' Done dinamically
                );

                // Create dinamic tags for __EXTRAFIELD_PRODUCT_FIELD__
                $extrafields = new ExtraFields($this->db);
                $extralabels = $extrafields->fetch_name_optionals_label('product', true);
                $product->fetch_optionals($product->id, $extralabels);
                foreach ($extrafields->attribute_label as $key => $label) {
                    $substitutions['__EXTRAFIELD_PRODUCT_' . strtoupper($key) . '__'] = $product->array_options['options_' . $key];
                }
            }
        }

        //Paypal substitutions
        if (! empty($conf->paypal->enabled) && ! empty($conf->global->PAYPAL_SECURITY_TOKEN))
        {
            $substitutions['__SECUREKEYPAYPAL__']=dol_hash($conf->global->PAYPAL_SECURITY_TOKEN, 2);
            if (empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE)) $substitutions['__SECUREKEYPAYPAL_MEMBER__']=dol_hash($conf->global->PAYPAL_SECURITY_TOKEN, 2);
            else $substitutions['__SECUREKEYPAYPAL_MEMBER__']=dol_hash($conf->global->PAYPAL_SECURITY_TOKEN . 'membersubscription' . $obj->source_id, 2);
        }

        // Complete the substitutions with external substitutions
        complete_substitutions_array($substitutions, $langs);
        // Do substitutions and return result
        return make_substitutions($text, $substitutions);
    }

    /**
     *  Returns the correct substitutions for language depending of mail type and requested $mode
     *
     *  @param	int         $mode 			0 = language substitutions, 1 = line language substitutions
     *  @return array       Substitution
     */
    public function getSubstitutionsLang($mode)
    {
        global $conf;

        if ($mode == 0) {
            // Array of language codes for substitutions (See also file comm/mailing/card.php that should manage same substitutions)
            $substitution_common = array(
                '__ID__' => 'IdRecord',
                '__EMAIL__' => 'EMail',
                '__MAILTOEMAIL__' => 'TagMailtoEmail',
                '__SIGNATURE__' => 'TagSignature',
                //,'__PERSONALIZED__' => 'Personalized'	// Hidden because not used yet
            );

            //Paypal stuff
            if (!empty($conf->paypal->enabled) && !empty($conf->global->PAYPAL_SECURITY_TOKEN)) {
                $substitution_common['__SECUREKEYPAYPAL__'] = 'SecureKeyPaypal';
                if (!empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE)) {
                    $substitution_common['__SECUREKEYPAYPAL_MEMBER__'] = 'SecureKeyPaypalUniquePerMember';
                }
            }

            if ($this->mail_type == 0) { //Mail type 0 extra substitutions + commons
                return array_merge($substitution_common, array(
                    '__LASTNAME__' => 'Lastname',
                    '__FIRSTNAME__' => 'Firstname',
                    '__CHECK_READ__' => 'TagCheckMail',
                    '__UNSUBSCRIBE__' => 'TagUnsubscribe',
                    '__OTHER_FIELD__' => 'OtherField',
                ));
            } else if ($this->mail_type == 1) { //Mail type 1 extra substitutions + commons
                return array_merge($substitution_common, array(
                    '__REF__' => 'Ref',
                    '__REF_SUPPLIER__' => 'RefSupplier',
                    '__THIRDPARTY_NAME__' => 'ThirdPartyName',
                    '__LINES__' => 'MailLines',
                    '__EXTRAFIELD_ORDER_FIELD__' => 'ExtraFieldOrder'
                ));
            }
        } else if ($mode == 1) {
            if ($this->mail_type == 1) {
                return array(
                    '__NUM__' => 'Number',
                    '__REF__' => 'Ref',
                    '__REF_SUPPLIER__' => 'RefSupplier',
                    '__LABEL__' => 'Label',
                    '__DESCRIPTION__' => 'Description',
                    '__DATE_START__' => 'DateStart',
                    '__DATE_END__' => 'DateEnd',
                    '__QUANTITY__' => 'Quantity',
                    '__TOTAL__' => 'AmountTTC',
                    '__EXTRAFIELD_PRODUCT_FIELD__' => 'ExtraFieldProduct'
                );
            }
        }
        return array();
    }

    /**
     *  Returns the correct substitutions for test depending of mail type
     *
     *  @return array       Substitution
     */
    public function getSubstitutionsTest()
    {
        global $conf, $user;

        // Array of common test
        $substitution_common = array(
            '__ID__' => 'TESTIdRecord',
            '__EMAIL__' => 'TESTEMail',
            '__MAILTOEMAIL__' => 'TESTMailtoEmail',
            '__SIGNATURE__' => (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN))?$user->signature:''),
            '__CHECK_READ__' => 'TagCheckMail',
            '__UNSUBSCRIBE__' => 'TagUnsubscribe'
            //,'__PERSONALIZED__' => 'TESTPersonalized'	// Not used yet
        );

        //Paypal stuff
        if (!empty($conf->paypal->enabled) && !empty($conf->global->PAYPAL_SECURITY_TOKEN)) {
            $substitution_common['__SECUREKEYPAYPAL__'] = 'TESTSecureKeyPaypal';
            if (!empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE)) {
                $substitution_common['__SECUREKEYPAYPAL_MEMBER__'] = 'TESTSecureKeyPaypalUniquePerMember';
            }
        }

        //Lines stuff
        $substitution_line = array(
            '__NUM__' => 'TESTNumber',
            '__REF__' => 'TESTRef',
            '__REF_SUPPLIER__' => 'TESTRefSupplier',
            '__LABEL__' => 'TESTLabel',
            '__DESCRIPTION__' => 'TESTDescription',
            '__DATE_START__' => 'TESTDateStart',
            '__DATE_END__' => 'TESTDateEnd',
            '__QUANTITY__' => 'TESTQuantity',
            '__TOTAL__' => 'TESTTotal',
            //'__EXTRAFIELD_PRODUCT_FIELD__' => 'ExtraFieldProduct'
        );
        $lines = make_substitutions($this->line, $substitution_line);

        if ($this->mail_type == 0) { //Mail type 0 extra substitutions + commons
            return array_merge($substitution_common, array(
                '__LASTNAME__' => 'TESTLastname',
                '__FIRSTNAME__' => 'TESTFirstname',
                //'__OTHER_FIELD__' => 'OtherField',
            ));
        } else if ($this->mail_type == 1) { //Mail type 1 extra substitutions + commons
            return array_merge($substitution_common, array(
                '__REF__' => 'TESTRef',
                '__REF_SUPPLIER__' => 'TESTRefSupplier',
                '__THIRDPARTY_NAME__' => 'TESTThirdPartyName',
                '__LINES__' => !empty($lines) ? $lines : 'TESTLines',
                //'__EXTRAFIELD_ORDER_FIELD__' => 'ExtraFieldOrder'
            ));
        }
        return array();
    }

    /**
     * List all mailings with selected mail_type
     *
     *  @param	int		$mail_type 	Mail Type, if null no filter will be used
     *  @return	array				Array of mailings
     */
    function listMailings($mail_type = null)
    {
        $sql = "SELECT rowid, statut, titre, mail_type";
        $sql.= " FROM ".MAIN_DB_PREFIX."mailing";
        if ($mail_type != null) {
            $sql.= " WHERE mail_type = ".$mail_type;
        }

    	dol_syslog(get_class($this)."::listMailings", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $retarray = array();

            while ($record = $this->db->fetch_array($resql))
            {
                $mail = array();
                $mail["id"]             = $record["rowid"];
                $mail["statut"]         = $record["statut"];
                $mail["description"]    = $record["titre"];
                $mail["mail_type"]      = $record["mail_type"];
                $retarray[]=$mail;
            }

            $this->db->free($resql);
            return $retarray;
        }
        else
        {
            $this->error=$this->db->error();
            $this->errors[]=$this->db->error();
            return -1;
        }
    }
}

