<?php
/* Copyright (C) 2010  Regis Houssin     <regis@dolibarr.fr>
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
 *      \file       htdocs/includes/triggers/interface_modPropale_PropalWorkflow.class.php
 *      \ingroup    propale
 *      \brief      Trigger file for proposal workflow
 *      \version	$Id$
 */


/**
 *      \class      InterfacePropalWorkflow
 *      \brief      Classe des fonctions triggers des actions personalisees du workflow
 */

class InterfacePropalWorkflow
{
    var $db;

    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfacePropalWorkflow($DB)
    {
        $this->db = $DB ;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "propale";
        $this->description = "Triggers of this module allows to manage proposal workflow";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'propal';
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

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
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
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 if fatal error, 0 si nothing done, >0 if ok
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Mettre ici le code a executer en reaction de l'action
        // Les donnees de l'action sont stockees dans $object

        // Proposals
        if ($action == 'PROPAL_CLOSE_SIGNED')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            return $this->_createOrderFromPropal($action,$object,$user,$langs,$conf);
        }

		return 0;
    }

    /**
	 * 		Create an order from a propal
	 */
    function _createOrderFromPropal($action,$object,$user,$langs,$conf)
    {
    	$error=0;

		if ($conf->commande->enabled)
		{
			// Signed proposal
			if ($object->statut == 2)
			{
				include_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

				$order = new Commande($this->db);
				$orderline = new OrderLine($this->db);

				$order->date_commande = dol_now();
				$order->source = 0;

				for ($i = 0 ; $i < sizeof($object->lines) ; $i++)
				{
					$line = new OrderLine($this->db);

					$line->libelle           = $object->lines[$i]->libelle;
					$line->desc              = $object->lines[$i]->desc;
					$line->price             = $object->lines[$i]->price;
					$line->subprice          = $object->lines[$i]->subprice;
					$line->tva_tx            = $object->lines[$i]->tva_tx;
					$line->localtax1_tx		 = $object->lines[$i]->localtax1_tx;
					$line->localtax2_tx		 = $object->lines[$i]->localtax2_tx;
					$line->qty               = $object->lines[$i]->qty;
					$line->fk_remise_except  = $object->lines[$i]->fk_remise_except;
					$line->remise_percent    = $object->lines[$i]->remise_percent;
					$line->fk_product        = $object->lines[$i]->fk_product;
					$line->info_bits         = $object->lines[$i]->info_bits;
					$line->product_type      = $object->lines[$i]->product_type;
					$line->special_code		 = $object->lines[$i]->special_code;

					$order->lines[$i] = $line;
				}

				$order->socid                = $object->socid;
				$order->fk_project           = $object->fk_project;
				$order->cond_reglement_id    = $object->cond_reglement_id;
				$order->mode_reglement_id    = $object->mode_reglement_id;
				$order->date_livraison       = $object->date_livraison;
				$order->fk_delivery_address  = $object->fk_delivery_address;
				$order->contact_id           = $object->contactid;
				$order->ref_client           = $object->ref_client;
				$order->note                 = $object->note;
				$order->note_public          = $object->note_public;

				$order->origin 		= $object->element;
				$order->origin_id 	= $object->id;

				$ret = $order->create($user);

				if ($ret > 0)
				{
					// Ne pas passer par la commande provisoire
					if ($conf->global->COMMANDE_VALID_AFTER_CLOSE_PROPAL == 1)
					{
						$order->fetch($ret);
						$order->valid($user);
					}

					return 1;
				}
			}
			else return 0;
		}
		else return 0;
    }

}
?>
