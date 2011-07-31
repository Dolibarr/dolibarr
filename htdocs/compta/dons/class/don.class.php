<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 *   	\file       htdocs/compta/dons/class/don.class.php
 *		\ingroup    don
 *		\brief      Fichier de la classe des dons
 *		\version    $Id: don.class.php,v 1.11 2011/08/03 00:46:39 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");


/**
 *      \class      Don
 *		\brief      Classe permettant la gestion des dons
 */
class Don extends CommonObject
{
    var $db;
    var $error;
    var $element='don';
    var $table_element='don';

    var $id;
    var $date;
    var $amount;
    var $prenom;
    var $nom;
    var $societe;
    var $adresse;
    var $cp;
    var $ville;
    var $pays;
    var $email;
    var $public;
    var $fk_project;
    var $modepaiement;
    var $modepaiementid;
    var $note;
    var $statut;

    var $projet;

    /**
     *    \brief  Constructeur
     *    \param  DB          	Handler d'acces base
     */
    function Don($DB)
    {
        global $langs;

        $this->db = $DB ;
        $this->modepaiementid = 0;

        $langs->load("donations");
        $this->labelstatut[-1]=$langs->trans("Canceled");
        $this->labelstatut[0]=$langs->trans("DonationStatusPromiseNotValidated");
        $this->labelstatut[1]=$langs->trans("DonationStatusPromiseValidated");
        $this->labelstatut[2]=$langs->trans("DonationStatusPaid");
        $this->labelstatutshort[-1]=$langs->trans("Canceled");
        $this->labelstatutshort[0]=$langs->trans("DonationStatusPromiseNotValidatedShort");
        $this->labelstatutshort[1]=$langs->trans("DonationStatusPromiseValidatedShort");
        $this->labelstatutshort[2]=$langs->trans("DonationStatusPaidShort");
    }


    /**
     *    \brief      Retourne le libelle du statut d'un don (brouillon, validee, abandonnee, payee)
     *    \param      mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
     *    \return     string        Libelle
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }

    /**
     *    	\brief      Renvoi le libelle d'un statut donne
     *    	\param      statut        	Id statut
     *    	\param      mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *    	\return     string        	Libelle du statut
     */
    function LibStatut($statut,$mode=0)
    {
        global $langs;

        if ($mode == 0)
        {
            return $this->labelstatut[$statut];
        }
        if ($mode == 1)
        {
            return $this->labelstatutshort[$statut];
        }
        if ($mode == 2)
        {
            if ($statut == -1) return img_picto($this->labelstatut[$statut],'statut5').' '.$this->labelstatutshort[$statut];
            if ($statut == 0)  return img_picto($this->labelstatut[$statut],'statut0').' '.$this->labelstatutshort[$statut];
            if ($statut == 1)  return img_picto($this->labelstatut[$statut],'statut1').' '.$this->labelstatutshort[$statut];
            if ($statut == 2)  return img_picto($this->labelstatut[$statut],'statut6').' '.$this->labelstatutshort[$statut];
        }
        if ($mode == 3)
        {
            $prefix='Short';
            if ($statut == -1) return img_picto($this->labelstatut[$statut],'statut5');
            if ($statut == 0)  return img_picto($this->labelstatut[$statut],'statut0');
            if ($statut == 1)  return img_picto($this->labelstatut[$statut],'statut1');
            if ($statut == 2)  return img_picto($this->labelstatut[$statut],'statut6');
        }
        if ($mode == 4)
        {
            if ($statut == -1) return img_picto($this->labelstatut[$statut],'statut5').' '.$this->labelstatut[$statut];
            if ($statut == 0)  return img_picto($this->labelstatut[$statut],'statut0').' '.$this->labelstatut[$statut];
            if ($statut == 1)  return img_picto($this->labelstatut[$statut],'statut1').' '.$this->labelstatut[$statut];
            if ($statut == 2)  return img_picto($this->labelstatut[$statut],'statut6').' '.$this->labelstatut[$statut];
        }
        if ($mode == 5)
        {
            $prefix='Short';
            if ($statut == -1) return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut5');
            if ($statut == 0)  return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut0');
            if ($statut == 1)  return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut1');
            if ($statut == 2)  return $this->labelstatutshort[$statut].' '.img_picto($this->labelstatut[$statut],'statut6');
        }
    }


    /**
     *		\brief		Initialise le don avec valeurs fictives alaatoire
     *					Sert a generer un recu de don pour l'aperu des modeles ou demo
     */
    function initAsSpecimen()
    {
        global $conf, $user,$langs;

        // Charge tableau des id de societe socids
        $socids = array();

        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe";
        $sql.= " WHERE client IN (1, 3)";
        $sql.= " AND entity = ".$conf->entity;
        $sql.= " LIMIT 10";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num_socs = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num_socs)
            {
                $i++;

                $row = $this->db->fetch_row($resql);
                $socids[$i] = $row[0];
            }
        }

        // Initialise parametres
        $this->id=0;
        $this->ref = 'SPECIMEN';
        $this->specimen=1;
        $this->nom = 'Doe';
        $this->prenom = 'John';
        $this->socid = $socids[$socid];
        $this->date = time();
        $this->amount = 100;
        $this->public = 1;
        $this->societe = 'The Company';
        $this->adresse = 'Twist road';
        $this->cp = '99999';
        $this->ville = 'Town';
        $this->note_public='SPECIMEN';
        $this->email='email@email.com';
        $this->note='';
        $this->statut=1;
    }


    /**
     *
     *
     */
    function check($minimum=0)
    {
        $err = 0;

        if (dol_strlen(trim($this->societe)) == 0)
        {
            if ((dol_strlen(trim($this->nom)) + dol_strlen(trim($this->prenom))) == 0)
            {
                $error_string[$err] = "Vous devez saisir vos nom et prenom ou le nom de votre societe.";
                $err++;
            }
        }

        if (dol_strlen(trim($this->adresse)) == 0)
        {
            $error_string[$err] = "L'adresse saisie est invalide";
            $err++;
        }

        if (dol_strlen(trim($this->cp)) == 0)
        {
            $error_string[$err] = "Le code postal saisi est invalide";
            $err++;
        }

        if (dol_strlen(trim($this->ville)) == 0)
        {
            $error_string[$err] = "La ville saisie est invalide";
            $err++;
        }

        if (dol_strlen(trim($this->email)) == 0)
        {
            $error_string[$err] = "L'email saisi est invalide";
            $err++;
        }

        $this->amount = trim($this->amount);

        $map = range(0,9);
        for ($i = 0; $i < dol_strlen($this->amount) ; $i++)
        {
            if (!isset($map[substr($this->amount, $i, 1)] ))
            {
                $error_string[$err] = "Le montant du don contient un/des caractere(s) invalide(s)";
                $err++;
                $amount_invalid = 1;
                break;
            }
        }

        if (! $amount_invalid)
        {
            if ($this->amount == 0)
            {
                $error_string[$err] = "Le montant du don est null";
                $err++;
            }
            else
            {
                if ($this->amount < $minimum && $minimum > 0)
                {
                    $error_string[$err] = "Le montant minimum du don est de $minimum";
                    $err++;
                }
            }
        }

        if ($err)
        {
            $this->error = $error_string;
            return 0;
        }
        else
        {
            return 1;
        }

    }

    /**
     *    Create donation record into database
     *    @param      user          Objet utilisateur qui cree le don
     *    @return     int           Id don cree si ok, <0 si ko
     *    TODO    add numbering module for Ref
     */
    function create($user)
    {
        global $conf;

        $now=dol_now();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."don (";
        $sql.= "datec";
        $sql.= ", entity";
        $sql.= ", amount";
        $sql.= ", fk_paiement";
        $sql.= ", prenom";
        $sql.= ", nom";
        $sql.= ", societe";
        $sql.= ", adresse";
        $sql.= ", cp";
        $sql.= ", ville";
        $sql.= ", pays";
        $sql.= ", public";
        $sql.= ", fk_don_projet";
        $sql.= ", note";
        $sql.= ", fk_user_author";
        $sql.= ", fk_user_valid";
        $sql.= ", datedon";
        $sql.= ", email";
        $sql.= ", phone";
        $sql.= ", phone_mobile";
        $sql.= ") VALUES (";
        $sql.= " '".$this->db->idate($now)."'";
        $sql.= ", ".$conf->entity;
        $sql.= ", ".price2num($this->amount);
        $sql.= ", ".($this->modepaiementid?$this->modepaiementid:"null");
        $sql.= ", '".$this->db->escape($this->prenom)."'";
        $sql.= ", '".$this->db->escape($this->nom)."'";
        $sql.= ", '".$this->db->escape($this->societe)."'";
        $sql.= ", '".$this->db->escape($this->adresse)."'";
        $sql.= ", '".$this->cp."'";
        $sql.= ", '".$this->db->escape($this->ville)."'";
        $sql.= ", '".$this->db->escape($this->pays)."'"; // TODO use fk_pays
        $sql.= ", ".$this->public;
        $sql.= ", ".($this->fk_project > 0?$this->fk_project:"null");
        $sql.= ", '".$this->db->escape($this->note)."'";
        $sql.= ", ".$user->id;
        $sql.= ", null";
        $sql.= ", '".$this->db->idate($this->date)."'";
        $sql.= ", '".$this->db->escape($this->email)."'";
        $sql.= ", '".$this->db->escape($this->phone)."'";
        $sql.= ", '".$this->db->escape($this->phone_mobile)."'";
        $sql.= ")";

        dol_syslog("Don::create sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            return $this->db->last_insert_id(MAIN_DB_PREFIX."don");
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *    Update a donation record
     *    @param      user        Objet utilisateur qui met a jour le don
     *    @return     int         >0 if OK, <0 if KO
     */
    function update($user)
    {

        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET ";
        $sql .= "amount = " . price2num($this->amount);
        $sql .= ",fk_paiement = ".($this->modepaiementid?$this->modepaiementid:"null");
        $sql .= ",prenom = '".$this->db->escape($this->prenom)."'";
        $sql .= ",nom='".$this->db->escape($this->nom)."'";
        $sql .= ",societe='".$this->db->escape($this->societe)."'";
        $sql .= ",adresse='".$this->db->escape($this->adresse)."'";
        $sql .= ",cp='".$this->cp."'";
        $sql .= ",ville='".$this->db->escape($this->ville)."'";
        $sql .= ",pays='".$this->db->escape($this->pays)."'"; // TODO use fk_pays
        $sql .= ",public=".$this->public;
        $sql .= ",fk_don_projet=".($this->fk_project>0?$this->fk_project:'null');
        $sql .= ",note='".$this->db->escape($this->note)."'";
        $sql .= ",datedon='".$this->db->idate($this->date)."'";
        $sql .= ",email='".$this->email."'";
        $sql .= ",phone='".$this->phone."'";
        $sql .= ",phone_mobile='".$this->phone_mobile."'";
        $sql .= ",fk_statut=".$this->statut;
        $sql .= " WHERE rowid = $this->id";

        dol_syslog("Don::update sql=".$sql);
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
     *    Delete a donation
     *    @param  rowid     Id of donation to delete
     */
    function delete($rowid)
    {

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."don WHERE rowid = $rowid AND fk_statut = 0;";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ( $this->db->affected_rows($resql) )
            {
                return 1;
            }
            else
            {
                return -1;
            }
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *      Load donation from database
     *      @param      rowid       Id of donation to load
     *      @param      ref         Ref of donation to load
     *      @return     int         <0 if KO, >0 if OK
     */
    function fetch($rowid,$ref='')
    {
        global $conf;

        $sql = "SELECT d.rowid, d.datec, d.tms as datem, d.datedon,";
        $sql.= " d.prenom, d.nom, d.societe, d.amount, d.fk_statut, d.adresse, d.cp, d.ville, d.pays, d.public, d.amount, d.fk_paiement, d.note, cp.libelle, d.email, d.phone, d.phone_mobile, d.fk_don_projet,";
        $sql.= " p.title as project_label";
        $sql.= " FROM ".MAIN_DB_PREFIX."don as d";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = d.fk_don_projet";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON cp.id = d.fk_paiement";
        $sql.= " WHERE d.rowid = ".$rowid." AND d.entity = ".$conf->entity;

        dol_syslog("Don::fetch sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id             = $obj->rowid;
                $this->ref            = $obj->rowid;
                $this->datec          = $this->db->jdate($obj->datec);
                $this->datem          = $this->db->jdate($obj->datem);
                $this->date           = $this->db->jdate($obj->datedon);
                $this->prenom         = $obj->prenom;
                $this->nom            = $obj->nom;
                $this->societe        = $obj->societe;
                $this->statut         = $obj->fk_statut;
                $this->adresse        = $obj->adresse;
                $this->cp             = $obj->cp;
                $this->ville          = $obj->ville;
                $this->zip            = $obj->cp;
                $this->town           = $obj->ville;
                $this->email          = $obj->email;
                $this->phone          = $obj->phone;
                $this->phone_mobile   = $obj->phone_mobile;
                $this->pays           = $obj->pays;
                $this->projet         = $obj->project_label;
                $this->fk_project     = $obj->fk_don_projet;
                $this->public         = $obj->public;
                $this->modepaiementid = $obj->fk_paiement;
                $this->modepaiement   = $obj->libelle;
                $this->amount         = $obj->amount;
                $this->note			  = $obj->note;
                $this->commentaire    = $obj->note;	// deprecated
            }
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }

    }

    /**
     *    Valide une promesse de don
     *    @param  rowid   id du don a modifier
     *    @param  userid  utilisateur qui valide la promesse
     *    @return   int     <0 if KO, >0 if OK
     */
    function valid_promesse($rowid, $userid)
    {

        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 1, fk_user_valid = $userid WHERE rowid = $rowid AND fk_statut = 0";

        dol_syslog("sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ( $this->db->affected_rows($resql) )
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
     *    Classe le don comme paye, le don a ete recu
     *    @param    rowid           id du don a modifier
     *    @param    modepaiementd   mode de paiement
     *    @return   int             <0 if KO, >0 if OK
     */
    function set_paye($rowid, $modepaiement='')
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 2";
        if ($modepaiement)
        {
            $sql .= ", fk_paiement=$modepaiement";
        }
        $sql .=  " WHERE rowid = $rowid AND fk_statut = 1";

        dol_syslog("sql=".$sql);
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
     *    Classe le don comme encaisse
     *    @param    rowid   id du don a modifier
     *    @return   int     <0 if KO, >0 if OK
     */
    function set_encaisse($rowid)
    {

        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 3 WHERE rowid = $rowid AND fk_statut = 2";

        dol_syslog("sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ( $this->db->affected_rows($resql) )
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
     *    Set donation sto status canceled
     *    @param    rowid   id du don a modifier
     *    @return   int     <0 if KO, >0 if OK
     */
    function set_cancel($rowid)
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = -1 WHERE rowid = ".$rowid;

        dol_syslog("sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ( $this->db->affected_rows($resql) )
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
     *    	\brief		Somme des dons
     *		\param		param	1=promesses de dons validees , 2=xxx, 3=encaisses
     */
    function sum_donations($param)
    {
        global $conf;

        $result=0;

        $sql = "SELECT sum(amount) as total";
        $sql.= " FROM ".MAIN_DB_PREFIX."don";
        $sql.= " WHERE fk_statut = ".$param;
        $sql.= " AND entity = ".$conf->entity;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            $result=$obj->total;
        }

        return $result;
    }


    /**
     *	\brief      Return clicable name (with picto eventually)
     *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     *	\return		string			Chaine avec URL
     */
    function getNomUrl($withpicto=0)
    {
        global $langs;

        $result='';

        $lien = '<a href="'.DOL_URL_ROOT.'/compta/dons/fiche.php?rowid='.$this->id.'">';
        $lienfin='</a>';

        $picto='generic';

        $label=$langs->trans("ShowDonation").': '.$this->ref;

        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
        return $result;
    }

    /**
     *      Return full name (civility+' '+name+' '+lastname)
     *      @param      langs           Language object for translation of civility
     *      @param      option          0=No option, 1=Add civility
     *      @param      nameorder       -1=Auto, 0=Lastname+Firstname, 1=Firstname+Lastname
     *      @return     string          String with full name
     */
    function getFullName($langs,$option=0,$nameorder=-1)
    {
        global $conf;

        $ret='';
        if ($option && $this->civilite_id)
        {
            if ($langs->transnoentitiesnoconv("Civility".$this->civilite_id)!="Civility".$this->civilite_id) $ret.=$langs->transnoentitiesnoconv("Civility".$this->civilite_id).' ';
            else $ret.=$this->civilite_id.' ';
        }

        // If order not defined, we use the setup
        if ($nameorder < 0) $nameorder=(! $conf->global->MAIN_FIRSTNAME_NAME_POSITION);

        if ($nameorder)
        {
            if ($this->prenom) $ret.=$this->prenom;
            if ($this->prenom && $this->nom) $ret.=' ';
            if ($this->nom)      $ret.=$this->nom;
        }
        else
        {
            if ($this->nom)      $ret.=$this->nom;
            if ($this->prenom && $this->nom) $ret.=' ';
            if ($this->prenom) $ret.=$this->prenom;
        }

        return trim($ret);
    }
}
?>
