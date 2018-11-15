<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2013	   Philippe Grand	    <philippe.grand@atoo-net.com>
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
 *	\defgroup   expedition     Module shipping
 *	\brief      Module pour gerer les expeditions de produits
 *	\file       htdocs/core/modules/modExpedition.class.php
 *	\ingroup    expedition
 *	\brief      Fichier de description et activation du module Expedition
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Expedition
 */
class modExpedition extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf, $user;

		$this->db = $db;
		$this->numero = 80;

		$this->family = "crm";
		$this->module_position = '40';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des expeditions";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = "sending";

		// Data directories to create when module is enabled
		$this->dirs = array("/expedition/temp",
							"/expedition/sending",
		                    "/expedition/sending/temp",
		                    "/expedition/receipt",
		                    "/expedition/receipt/temp",
							"/doctemplates/shipment",
							"/doctemplates/delivery"
		                    );

		// Config pages
		$this->config_page_url = array("confexped.php");

		// Dependencies
		$this->depends = array("modCommande");
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('deliveries','sendings');

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "EXPEDITION_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "rouget";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des bons expeditions en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "EXPEDITION_ADDON_NUMBER";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_expedition_safor";
		$this->const[$r][3] = 'Name for numbering manager for shipments';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "EXPEDITION_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/shipment";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "LIVRAISON_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "typhon";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des bons de reception en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "LIVRAISON_ADDON_NUMBER";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_livraison_jade";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des bons de reception';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "LIVRAISON_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/delivery";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "MAIN_SUBMODULE_EXPEDITION";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "Enable shipments";
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'expedition';
		$r=0;

		$r++;
		$this->rights[$r][0] = 101;
		$this->rights[$r][1] = 'Lire les expeditions';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 102;
		$this->rights[$r][1] = 'Creer modifier les expeditions';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 104;
		$this->rights[$r][1] = 'Valider les expeditions';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'shipping_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 105; // id de la permission
		$this->rights[$r][1] = 'Envoyer les expeditions aux clients'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'shipping_advance';
        $this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 106;
		$this->rights[$r][1] = 'Exporter les expeditions';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'shipment';
		$this->rights[$r][5] = 'export';

		$r++;
		$this->rights[$r][0] = 109;
		$this->rights[$r][1] = 'Supprimer les expeditions';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 1101;
		$this->rights[$r][1] = 'Lire les bons de livraison';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 1102;
		$this->rights[$r][1] = 'Creer modifier les bons de livraison';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 1104;
		$this->rights[$r][1] = 'Valider les bons de livraison';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 1109;
		$this->rights[$r][1] = 'Supprimer les bons de livraison';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'supprimer';


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r=0;

		include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		$shipment=new Commande($this->db);
		$contact_arrays=$shipment->liste_type_contact('external','',0,0,'');
		if (is_array($contact_arrays) && count($contact_arrays)>0){
			$idcontacts=join(',',array_keys($shipment->liste_type_contact('external','',0,0,'')));
		} else {
			$idcontacts=0;
		}


		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Shipments';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("expedition","shipment","export"));
		$this->export_fields_array[$r]=array(
			's.rowid'=>"IdCompany",'s.nom'=>'ThirdParty','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','d.nom'=>'State','co.label'=>'Country',
			'co.code'=>'CountryCode','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.idprof5'=>'ProfId5',
			's.idprof6'=>'ProfId6','c.rowid'=>"Id",'c.ref'=>"Ref",'c.ref_customer'=>"RefCustomer",'c.fk_soc'=>"IdCompany",'c.date_creation'=>"DateCreation",
			'c.date_delivery'=>"DateDeliveryPlanned",'c.tracking_number'=>"TrackingNumber",'c.height'=>"Height",'c.width'=>"Width",'c.size'=>"Depth",
			'c.size_units'=>'SizeUnits','c.weight'=>"Weight",'c.weight_units'=>"WeightUnits",'c.fk_statut'=>'Status','c.note_public'=>"NotePublic",
			'ed.rowid'=>'LineId','cd.description'=>'Description','ed.qty'=>"Qty",'p.rowid'=>'ProductId','p.ref'=>'ProductRef','p.label'=>'ProductLabel',
			'p.weight'=>'ProductWeight','p.weight_units'=>'WeightUnits','p.volume'=>'ProductVolume','p.volume_units'=>'VolumeUnits'
		);
		if ($idcontacts && ! empty($conf->global->SHIPMENT_ADD_CONTACTS_IN_EXPORT)) $this->export_fields_array[$r]+=array('sp.rowid'=>'IdContact','sp.lastname'=>'Lastname','sp.firstname'=>'Firstname','sp.note_public'=>'NotePublic');
		//$this->export_TypeFields_array[$r]=array(
		//	's.rowid'=>"List:societe:nom",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','co.label'=>'List:c_country:label:label',
		//	'co.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','c.ref'=>"Text",'c.ref_client'=>"Text",
		//	'c.date_creation'=>"Date",'c.date_commande'=>"Date",'c.amount_ht'=>"Numeric",'c.remise_percent'=>"Numeric",'c.total_ht'=>"Numeric",
		//	'c.total_ttc'=>"Numeric",'c.facture'=>"Boolean",'c.fk_statut'=>'Status','c.note_public'=>"Text",'c.date_livraison'=>'Date','ed.qty'=>"Text"
		//);
		$this->export_TypeFields_array[$r]=array(
			's.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','co.label'=>'List:c_country:label:label','co.code'=>'Text','s.phone'=>'Text',
			's.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','c.ref'=>"Text",'c.ref_customer'=>"Text",'c.date_creation'=>"Date",
			'c.date_delivery'=>"Date",'c.tracking_number'=>"Numeric",'c.height'=>"Numeric",'c.width'=>"Numeric",'c.weight'=>"Numeric",'c.fk_statut'=>'Status',
			'c.note_public'=>"Text",'ed.qty'=>"Numeric",'d.nom'=>'Text'
		);
		$this->export_entities_array[$r]=array(
			's.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','d.nom'=>'company','co.label'=>'company',
			'co.code'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.ape'=>'company','s.siret'=>'company','s.idprof4'=>'company',
			's.idprof5'=>'company','s.idprof6'=>'company','c.rowid'=>"shipment",'c.ref'=>"shipment",'c.ref_customer'=>"shipment",'c.fk_soc'=>"shipment",
			'c.date_creation'=>"shipment",'c.date_delivery'=>"shipment",'c.tracking_number'=>'shipment','c.height'=>"shipment",'c.width'=>"shipment",
			'c.size'=>'shipment','c.size_units'=>'shipment','c.weight'=>"shipment",'c.weight_units'=>'shipment','c.fk_statut'=>"shipment",'c.note_public'=>"shipment",
			'ed.rowid'=>'shipment_line','cd.description'=>'shipment_line','ed.qty'=>"shipment_line",'p.rowid'=>'product','p.ref'=>'product','p.label'=>'product',
			'p.weight'=>'product','p.weight_units'=>'product','p.volume'=>'product','p.volume_units'=>'product'
		);
		if ($idcontacts && ! empty($conf->global->SHIPMENT_ADD_CONTACTS_IN_EXPORT)) $this->export_entities_array[$r]+=array('sp.rowid'=>'contact','sp.lastname'=>'contact','sp.firstname'=>'contact','sp.note_public'=>'contact');
		$this->export_dependencies_array[$r]=array('shipment_line'=>'ed.rowid','product'=>'ed.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		if ($idcontacts && ! empty($conf->global->SHIPMENT_ADD_CONTACTS_IN_EXPORT))
		{
		    $keyforselect='socpeople'; $keyforelement='contact'; $keyforaliasextra='extra3';
		    include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		}
		$keyforselect='expedition'; $keyforelement='shipment'; $keyforaliasextra='extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect='expeditiondet'; $keyforelement='shipment_line'; $keyforaliasextra='extra2';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'expedition as c';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'expedition_extrafields as extra ON c.rowid = extra.fk_object,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'societe as s';
		if(!$user->rights->societe->client->voir) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON s.fk_departement = d.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as co ON s.fk_pays = co.rowid,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'expeditiondet as ed';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'expeditiondet_extrafields as extra2 ON ed.rowid = extra2.fk_object';
		$this->export_sql_end[$r] .=' , '.MAIN_DB_PREFIX.'commandedet as cd';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on cd.fk_product = p.rowid';
		if ($idcontacts && ! empty($conf->global->SHIPMENT_ADD_CONTACTS_IN_EXPORT))
		{
		  $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'element_contact as ee ON ee.element_id = cd.fk_commande AND ee.fk_c_type_contact IN ('.$idcontacts.')';
		  $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'socpeople as sp ON sp.rowid = ee.fk_socpeople';
		  $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'socpeople_extrafields as extra3 ON sp.rowid = extra3.fk_object';
		}
		$this->export_sql_end[$r] .=' WHERE c.fk_soc = s.rowid AND c.rowid = ed.fk_expedition AND ed.fk_origin_line = cd.rowid';
		$this->export_sql_end[$r] .=' AND c.entity IN ('.getEntity('expedition').')';
		if(!$user->rights->societe->client->voir) $this->export_sql_end[$r] .=' AND sc.fk_user = '.$user->id;
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf,$langs;

		// Permissions
		$this->remove($options);

		//ODT template
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/shipment/template_shipment.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/shipment';
		$dest=$dirodt.'/template_shipment.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src,$dest,0,0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile',$src,$dest);
				return 0;
			}
		}

		$sql = array();

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'shipping' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','shipping',".$conf->entity.")",
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[3][2])."' AND type = 'delivery' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[3][2])."','delivery',".$conf->entity.")",
		);

		return $this->_init($sql,$options);
	}
}
