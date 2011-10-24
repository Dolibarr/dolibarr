<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/core/triggers/interface_modAgenda_ActionsAuto.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 */


/**
 *	\class      InterfaceActionsAuto
 *  \brief      Class of triggered functions for agenda module
 */
class InterfaceActionsAuto
{
    var $db;
    var $error;

    var $date;
    var $duree;
    var $texte;
    var $desc;

    /**
     *   Constructor.
     *   @param      DB      Database handler
     */
    function InterfaceActionsAuto($DB)
    {
        $this->db = $DB ;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "agenda";
        $this->description = "Triggers of this module add actions in agenda according to setup made in agenda setup.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->picto = 'action';
    }

    /**
     *   Return name of trigger file
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *   @return     string      Version of trigger file
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
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      Following properties must be filled:
     *      $object->actiontypecode (translation action code: AC_OTH, ...)
     *      $object->actionmsg (note, long text)
     *      $object->actionmsg2 (label, short text)
     *      $object->sendtoid (id of contact)
     *      $object->socid
     *      Optionnal:
     *      $object->fk_element
     *      $object->elementtype
     *
     *      @param      action      Event code (COMPANY_CREATE, PROPAL_VALIDATE, ...)
     *      @param      object      Object action is done on
     *      @param      user        Object user
     *      @param      langs       Object langs
     *      @param      conf        Object conf
     *      @return     int         <0 if KO, 0 if no action are done, >0 if OK
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {
		$key='MAIN_AGENDA_ACTIONAUTO_'.$action;
        //dol_syslog("xxxxxxxxxxx".$key);

        if (empty($conf->agenda->enabled)) return 0;     // Module not active, we do nothing
		if (empty($conf->global->$key)) return 0;	// Log events not enabled for this action

		$ok=0;

		// Actions
		if ($action == 'COMPANY_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("NewCompanyToDolibarr",$object->nom);
            $object->actionmsg=$langs->transnoentities("NewCompanyToDolibarr",$object->nom);
            if ($object->prefix) $object->actionmsg.=" (".$object->prefix.")";
            //$this->desc.="\n".$langs->transnoentities("Customer").': '.yn($object->client);
            //$this->desc.="\n".$langs->transnoentities("Supplier").': '.yn($object->fournisseur);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->socid=$object->id;
			$ok=1;
        }
        elseif ($action == 'CONTRACT_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("contracts");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ContractValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("ContractValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$ok=1;
		}
		elseif ($action == 'PROPAL_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("propal");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("PropalValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
		}
        elseif ($action == 'PROPAL_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("propal");
            $langs->load("agenda");

            $object->actiontypecode='AC_EMAIL';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProposalSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("ProposalSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
		}
		elseif ($action == 'PROPAL_CLOSE_SIGNED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("propal");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClosedSignedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClosedSignedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
		}
		elseif ($action == 'PROPAL_CLOSE_REFUSED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("propal");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClosedRefusedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClosedRefusedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
		}
		elseif ($action == 'ORDER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("orders");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("OrderValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
		}
        elseif ($action == 'ORDER_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("orders");
            $langs->load("agenda");

            $object->actiontypecode='AC_EMAIL';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("OrderSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
		}
		elseif ($action == 'BILL_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
		}
        elseif ($action == 'BILL_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

            $object->actiontypecode='AC_EMAIL';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("InvoiceSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
		}
		elseif ($action == 'BILL_PAYED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$ok=1;
		}
		elseif ($action == 'BILL_CANCEL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$ok=1;
		}
		elseif ($action == 'FICHEINTER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("interventions");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$object->fk_element=0;
			$object->elementtype='';
			$ok=1;
		}
        elseif ($action == 'FICHEINTER_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("interventions");
            $langs->load("agenda");

            $object->actiontypecode='AC_EMAIL';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionSentByEMail",$object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionSentByEMail",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            // Parameters $object->sendotid defined by caller
            //$object->sendtoid=0;
            $ok=1;
        }
		elseif ($action == 'SHIPPING_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("sendings");
            $langs->load("agenda");

            $object->actiontypecode='AC_SHIP';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ShippingSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("ShippingSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
		}
		elseif ($action == 'ORDER_SUPPLIER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("orders");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("OrderValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
            $ok=1;
		}
        elseif ($action == 'ORDER_SUPPLIER_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");
            $langs->load("orders");

            $object->actiontypecode='AC_EMAIL';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierOrderSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendotid defined by caller
            //$object->sendtoid=0;
            $ok=1;
        }
		elseif ($action == 'BILL_SUPPLIER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
            $ok=1;
		}
        elseif ($action == 'BILL_SUPPLIER_SENTBYMAIL')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");
            $langs->load("orders");

            $object->actiontypecode='AC_EMAIL';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierInvoiceSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierInvoiceSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
            $ok=1;
        }
		elseif ($action == 'BILL_SUPPLIER_PAYED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
		}
		elseif ($action == 'BILL_SUPPLIER_CANCELED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("bills");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
		}

        // Members
        elseif ($action == 'MEMBER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
        }
        elseif ($action == 'MEMBER_SUBSCRIPTION')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberSubscriptionAddedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberSubscriptionAddedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Amount").': '.$object->last_subscription_amount;
            $object->actionmsg.="\n".$langs->transnoentities("Period").': '.dol_print_date($object->last_subscription_date_start,'day').' - '.dol_print_date($object->last_subscription_date_end,'day');
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
        }
        elseif ($action == 'MEMBER_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'MEMBER_RESILIATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberResiliatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberResiliatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
        }
        elseif ($action == 'MEMBER_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("other");
            $langs->load("members");
            $langs->load("agenda");

			$object->actiontypecode='AC_OTH';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberDeletedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberDeletedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$ok=1;
        }

		// If not found
        /*
        else
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.");
			return 0;
        }
        */

        // Add entry in event table
        if ($ok)
        {
			$now=dol_now();

            require_once(DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php');
            require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
			$contactforaction=new Contact($this->db);
            $societeforaction=new Societe($this->db);
            if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
            if ($object->socid > 0)    $societeforaction->fetch($object->socid);

			// Insertion action
			require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code   = $object->actiontypecode;
			$actioncomm->label       = $object->actionmsg2;
			$actioncomm->note        = $object->actionmsg;
			$actioncomm->datep       = $now;
			$actioncomm->datef       = $now;
			$actioncomm->durationp   = 0;
			$actioncomm->punctual    = 1;
			$actioncomm->percentage  = -1;   // Not applicable
			$actioncomm->contact     = $contactforaction;
			$actioncomm->societe     = $societeforaction;
			$actioncomm->author      = $user;   // User saving action
			//$actioncomm->usertodo  = $user;	// User affected to action
			$actioncomm->userdone    = $user;	// User doing action

			$actioncomm->fk_element  = $object->id;
			$actioncomm->elementtype = $object->element;

			$ret=$actioncomm->add($user);       // User qui saisit l'action
			if ($ret > 0)
			{
				return 1;
			}
			else
			{
                $error ="Failed to insert : ".$actioncomm->error." ";
                $this->error=$error;

                dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
                return -1;
			}
		}

		return 0;
    }

}
?>
