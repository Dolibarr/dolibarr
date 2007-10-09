<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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

/**     \defgroup   tax		Module taxes
        \brief      Module pour inclure des fonctions de saisies des taxes et charges sociales
*/

/**
        \file       htdocs/includes/modules/modTax.class.php
        \ingroup    comptabilite
        \brief      Fichier de description et activation du module Taxe
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
		\class 		modTax
        \brief      Classe de description et activation du module Tax
*/
class modTax extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
	function modTax($DB)
	{
		global $conf;
	
		$this->db = $DB ;
		$this->id = 'tax';   // Same value xxx than in file modXxx.class.php file
		$this->numero = 500 ;
	
		$this->family = "financial";
		$this->name = "Taxes et charges sociales";
		$this->description = "Gestion des taxes et charges sociales";
	
		$this->revision = explode(" ","$Revision$");
		$this->version = $this->revision[1];
	
		$this->const_name = 'MAIN_MODULE_TAX';
		$this->special = 0;
        $this->picto='bill';
	
		// Config pages
		$this->config_page_url = array();
	
		// Dépendances
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("compta","bills");
	
		// Constantes
		$this->const = array();
	
		// Répertoires
		$this->dirs = array();
		$this->dirs[0] = $conf->tax->dir_output;
		$this->dirs[1] = $conf->tax->dir_temp;
	
		// Boites
		$this->boxes = array();
	
		// Permissions
		$this->rights = array();
		$this->rights_class = 'tax';
		$r=0;
	
		$r++;
		$this->rights[$r][0] = 91;
		$this->rights[$r][1] = 'Lire les charges';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'charges';
		$this->rights[$r][5] = 'lire';
	
		$r++;
		$this->rights[$r][0] = 92;
		$this->rights[$r][1] = 'Créer modifier les charges';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'charges';
		$this->rights[$r][5] = 'creer';
	
		$r++;
		$this->rights[$r][0] = 93;
		$this->rights[$r][1] = 'Supprimer les charges';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'charges';
		$this->rights[$r][5] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 94;
		$this->rights[$r][1] = 'Exporter les charges';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'charges';
		$this->rights[$r][5] = 'export';

       
	    // Exports
        //--------
        $r=0;
    
        $r++;
        $this->export_code[$r]=$this->id.'_'.$r;
        $this->export_label[$r]='Taxes et charges sociales, et leurs règlements';
        $this->export_fields_array[$r]=array('cc.libelle'=>"Type",'c.libelle'=>"Label",'c.date_ech'=>'DateDue','c.periode'=>'Period','c.amount'=>"AmountExpected","c.paye"=>"Status",'p.datep'=>'DatePayment','p.amount'=>'AmountPayment','p.num_paiement'=>'Numero');
        $this->export_entities_array[$r]=array('cc.libelle'=>"tax_type",'c.libelle'=>'tax','c.date_ech'=>'tax','c.periode'=>'tax','c.amount'=>"tax","c.paye"=>"tax",'p.datep'=>'payment','p.amount'=>'payment','p.num_paiement'=>'payment');
        $this->export_alias_array[$r]=array('cc.libelle'=>"type",'c.libelle'=>'label','c.date_ech'=>'datedue','c.periode'=>'period','c.amount'=>"amount_clamed","c.paye"=>"status",'p.datep'=>'date_payment','p.amount'=>'amount_payment','p.num_paiement'=>'num_payment');
        $this->export_sql[$r]="select distinct ";
        $i=0;
        foreach ($this->export_alias_array[$r] as $key => $value)
        {
            if ($i > 0) $this->export_sql[$r].=', ';
            else $i++;
            $this->export_sql[$r].=$key.' as '.$value;
        }
        $this->export_sql[$r].=' from '.MAIN_DB_PREFIX.'c_chargesociales as cc, '.MAIN_DB_PREFIX.'chargesociales as c LEFT JOIN '.MAIN_DB_PREFIX.'paiementcharge as p ON p.fk_charge = c.rowid WHERE c.fk_type = cc.id';
        $this->export_permission[$r]=array(array("tax","charges","export"));

	}


   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
	function init()
	{
		global $conf;
	
		// Nettoyage avant activation
		$this->remove();
	
		return $this->_init($sql);
	}

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
	function remove()
	{
		$sql = array();
	
		return $this->_remove($sql);
	}
}
?>
