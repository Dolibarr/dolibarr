<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/includes/triggers/interface_all_Logevents.class.php
        \ingroup    core
        \brief      Trigger file for 
		\version	$Id$
*/


/**
        \class      InterfaceLogevents
        \brief      Classe des fonctions triggers des actions agenda
*/

class InterfaceLogevents
{
    var $db;
    var $error;
    
    var $date;
    var $duree;
    var $texte;
    var $desc;
    
    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceLogevents($DB)
    {
        $this->db = $DB ;
    
        $this->name = "Eventsynchro";
        $this->family = "core";
        $this->description = "Les triggers de ce composant permettent de logguer les evenements Dolibarr (modification status des objets).";
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
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      langs       Objet langs
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {
        if (! empty($conf->global->MAIN_LOGEVENTS_DISABLE_ALL)) return 0;	// Log events is disabled (hidden features)

		$key='MAIN_LOGEVENTS_'.$action;
		//dolibarr_syslog("xxxxxxxxxxx".$key);
		if (empty($conf->global->$key)) return 0;				// Log events not enabled for this action
		
        // Actions
        if ($action == 'USER_LOGIN')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
		
            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("UserLogged",$object->nom);
            $this->desc=$langs->transnoentities("UserLogged",$object->nom);
		}
        if ($action == 'USER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
		
            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("NewUserCreated",$object->nom);
            $this->desc=$langs->transnoentities("NewUserCreated",$object->nom);
		}
        elseif ($action == 'USER_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");

            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("UserModified",$object->nom);
            $this->desc=$langs->transnoentities("UserModified",$object->nom);
        }
        elseif ($action == 'USER_NEW_PASSWORD')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");

            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("NewUserPassword",$object->nom);
            $this->desc=$langs->transnoentities("NewUserPassword",$object->nom);
        }
        elseif ($action == 'USER_DISABLE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("UserDisabled",$object->nom);
            $this->desc=$langs->transnoentities("UserDisabled",$object->nom);
        }
        elseif ($action == 'USER_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("UserDeleted",$object->nom);
            $this->desc=$langs->transnoentities("Userdeleted",$object->nom);
        }

		// Groupes
        elseif ($action == 'GROUP_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("NewGroupCreated",$object->nom);
            $this->desc=$langs->transnoentities("NewGroupCreated",$object->nom);
		}
        elseif ($action == 'GROUP_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("GroupModified",$object->nom);
            $this->desc=$langs->transnoentities("GroupModified",$object->nom);
		}
        elseif ($action == 'GROUP_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("GroupDeleted",$object->nom);
            $this->desc=$langs->transnoentities("GroupDeleted",$object->nom);
		}

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
            $this->date=time();
            $this->duree=0;
            $this->texte=$langs->transnoentities("NewMemberCreated",$object->id);
            $this->desc=$langs->transnoentities("NewMemberCreated",$object->id);
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

        // Add entry in event table
        if ($this->date)
        {
			include_once(DOL_DOCUMENT_ROOT.'/core/events.class.php');
			
			$event=new Events($this->db);
            $event->type=$action;
            $event->dateevent=$this->date;
            $event->label=$this->texte;
            $event->description=$this->desc;

            $result=$event->create($user);
            if ($result > 0)
            {
                return 1;
            }
            else
            {
                $error ="Failed to insert : ".$webcal->error." ";
                $this->error=$error;

                //dolibarr_syslog("interface_webcal.class.php: ".$this->error);
                return -1;
            }
        }

		return 0;
    }

}
?>
