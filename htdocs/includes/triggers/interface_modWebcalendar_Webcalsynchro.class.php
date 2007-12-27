<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
        \file       htdocs/includes/triggers/interface_modWebcalendar_webcalsynchro.class.php
        \ingroup    webcalendar
        \brief      Fichier de gestion des triggers webcalendar
*/

include_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');


/**
        \class      InterfaceWebcalsynchro
        \brief      Classe des fonctions triggers des actions webcalendar
*/

class InterfaceWebcalsynchro
{
    var $db;
    var $error;
    
    var $date;
    var $duree;
    var $texte;
    var $desc;
    
    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'accès base
     */
    function InterfaceWebcalsynchro($DB)
    {
        $this->db = $DB ;
    
        $this->name = "Webcalsynchro";
        $this->family = "webcal";
        $this->description = "Les triggers de ce composant permettent d'insérer un évênement dans le calendrier webcalendar pour chaque grand évênement Dolibarr.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
    }
    
    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      \brief      Fonction appelée lors du déclenchement d'un évènement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre présentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerné
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Mettre ici le code à exécuter en réaction de l'action
        // Les données de l'action sont stockées dans $object

        if (! $conf->webcal->enabled) return 0;     // Module non actif
        if (! $object->use_webcal) return 0;        // Option syncro webcal non active

        // Actions
        if ($action == 'ACTION_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            // Initialisation donnees (date,duree,texte,desc)
            if ($object->type_id == 5 && $object->contact->fullname)
            {
                $libellecal =$langs->transnoentities("TaskRDVWith",$object->contact->getFullName($langs))."\n";
                $libellecal.=$object->note;
            }
            else
            {
                $libellecal="";
                if ($langs->transnoentities("Action".$object->type_code) != "Action".$object->type_code)
                {
                    $libellecal.=$langs->transnoentities("Action".$object->type_code)."\n";
                }
                $libellecal.=($object->label!=$libellecal?$object->label."\n":"");
                $libellecal.=($object->note?$object->note:"");
            }

            $this->date=$object->date ? $object->date : $object->datep;
            $this->duree=$object->duree;
            $this->texte=$object->societe->nom;
            $this->desc=$libellecal;
        }

		// Third parties
        elseif ($action == 'COMPANY_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            
            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("NewCompanyToDolibarr",$object->nom);
            $this->desc=$langs->transnoentities("NewCompanyToDolibarr",$object->nom);
            if ($object->prefix) $this->desc.=" (".$object->prefix.")";
            //$this->desc.="\n".$langs->transnoentities("Customer").': '.yn($object->client);
            //$this->desc.="\n".$langs->transnoentities("Supplier").': '.yn($object->fournisseur);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }

		// Contracts
        elseif ($action == 'CONTRACT_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("ContractValidatedInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("ContractValidatedInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'CONTRACT_CANCEL')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("ContractCanceledInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("ContractCanceledInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'CONTRACT_CLOSE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("ContractClosedInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("ContractClosedInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }

		// Proposals
        elseif ($action == 'PROPAL_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("PropalValidatedInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("PropalValidatedInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'PROPAL_CLOSE_SIGNED')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("PropalClosedSignedInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("PropalClosedSignedInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'PROPAL_CLOSE_REFUSED')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("PropalClosedRefusedInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("PropalClosedRefusedInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        
        // Invoices
		elseif ($action == 'BILL_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'BILL_PAYED')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("InvoicePayedInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("InvoicePayedInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'BILL_CANCELED')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }

        // Payments
        elseif ($action == 'PAYMENT_CUSTOMER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("CustomerPaymentDoneInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("CustomerPaymentDoneInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("AmountTTC").': '.$object->total;
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'PAYMENT_SUPPLIER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("SupplierPaymentDoneInDolibarr",$object->ref);
            $this->desc=$langs->transnoentities("SupplierPaymentDoneInDolibarr",$object->ref);
            $this->desc.="\n".$langs->transnoentities("AmountTTC").': '.$object->total;
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }

        // Members
        elseif ($action == 'MEMBER_CREATE')
        {
		}
        elseif ($action == 'MEMBER_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("MemberValidatedInDolibarr",$object->id);
            $this->desc=$langs->transnoentities("MemberValidatedInDolibarr",$object->id);
            $this->desc.="\n".$langs->transnoentities("Member").': '.$object->fullname;
            $this->desc.="\n".$langs->transnoentities("Type").': '.$object->type;
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'MEMBER_SUBSCRIPTION')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("MemberSubscriptionInDolibarr",$object->id);
            $this->desc=$langs->transnoentities("MemberSubscriptionInDolibarr",$object->id);
            $this->desc.="\n".$langs->transnoentities("Member").': '.$object->fullname;
            $this->desc.="\n".$langs->transnoentities("Type").': '.$object->type;
            $this->desc.="\n".$langs->transnoentities("Amount").': '.$object->last_subscription_amount;
            $this->desc.="\n".$langs->transnoentities("Period").': '.dolibarr_print_date($object->last_subscription_date_start,'day').' - '.dolibarr_print_date($object->last_subscription_date_end,'day');
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'MEMBER_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("MemberModifiedInDolibarr",$object->id);
            $this->desc=$langs->transnoentities("MemberModifiedInDolibarr",$object->id);
            $this->desc.="\n".$langs->transnoentities("Member").': '.$object->fullname;
            $this->desc.="\n".$langs->transnoentities("Type").': '.$object->type;
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'MEMBER_RESILIATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("MemberResiliatedInDolibarr",$object->id);
            $this->desc=$langs->transnoentities("MemberResiliatedInDolibarr",$object->id);
            $this->desc.="\n".$langs->transnoentities("Member").': '.$object->fullname;
            $this->desc.="\n".$langs->transnoentities("Type").': '.$object->type;
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }
        elseif ($action == 'MEMBER_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");

            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("MemberDeletedInDolibarr",$object->id);
            $this->desc=$langs->transnoentities("MemberDeletedInDolibarr",$object->id);
            $this->desc.="\n".$langs->transnoentities("Member").': '.$object->fullname;
            $this->desc.="\n".$langs->transnoentities("Type").': '.$object->type;
            $this->desc.="\n".$langs->transnoentities("Author").': '.$user->login;
        }

		// If not found
/*
        else
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.");
			return 0;
        }
*/

        // Ajoute entrée dans webcal
        if ($this->date)
        {

            // Crée objet webcal et connexion avec params $conf->webcal->db->xxx
            $webcal = new Webcal();
            if (! $webcal->localdb->ok)
            {
                // Si la creation de l'objet n'as pu se connecter
                $error ="Dolibarr n'a pu se connecter à la base Webcalendar avec les identifiants définis (host=".$conf->webcal->db->host." dbname=".$conf->webcal->db->name." user=".$conf->webcal->db->user."). ";
                $error.="La mise a jour Webcalendar a été ignorée.";
                $this->error=$error;
    
                //dolibarr_syslog("interface_webcal.class.php: ".$this->error);
                return -1;
            }

            $webcal->date=$this->date;
            $webcal->duree=$this->duree;
            $webcal->texte=$this->texte;
            $webcal->desc=$this->desc;

            $result=$webcal->add($user);
            if ($result > 0)
            {
                return 1;
            }
            else
            {
                $error ="Echec insertion dans webcal: ".$webcal->error." ";
                $error.="La mise a jour Webcalendar a été ignorée.";
                $this->error=$error;

                //dolibarr_syslog("interface_webcal.class.php: ".$this->error);
                return -2;
            }
        }

		return 0;
    }

}
?>
