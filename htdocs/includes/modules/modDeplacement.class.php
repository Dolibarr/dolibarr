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
 \defgroup   deplacement     Module deplacement et notes de frais
 \brief      Module pour gerer les deplacements et notes de frais
 \version	$Id$
 */

/**
 \file       htdocs/includes/modules/modDeplacement.class.php
 \ingroup    deplacement
 \brief      Fichier de description et activation du module Deplacement et notes de frais
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 \class      modDeplacement
 \brief      Classe de description et activation du module Deplacement
 */

class modDeplacement extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acc�s base
	 */
	function modDeplacement($DB)
	{
		$this->db = $DB ;
		$this->numero = 75 ;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des notes de frais et deplacements";		// Si traduction Module75Desc non trouvee

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto = "trip";

		// Dir
		$this->dirs = array();

		// Config pages
		$this->config_page_url = array();
		$this->langfiles = array("companies","trips");
		
		// D�pendances
		$this->depends = array();
		$this->requiredby = array();

		// Constantes
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'deplacement';

		$this->rights[1][0] = 170;
		$this->rights[1][1] = 'Lire les deplacements';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 171;
		$this->rights[2][1] = 'Creer/modifier les deplacements';
		$this->rights[2][2] = 'w';
		$this->rights[2][3] = 0;
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 172;
		$this->rights[3][1] = 'Supprimer les deplacements';
		$this->rights[3][2] = 'd';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'supprimer';

		$this->rights[3][0] = 178;
		$this->rights[3][1] = 'Exporter les deplacements';
		$this->rights[3][2] = 'd';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'export';

		// Exports
		$r=0;
		
		$r++;
		$this->export_code[$r]='trips_'.$r;
		$this->export_label[$r]='List of trips and expenses';
		$this->export_permission[$r]=array(array("deplacement","export"));
		$this->export_fields_array[$r]=array('d.rowid'=>"TripId",'d.type'=>"Type",'d.km'=>"FeesKilometersOrAmout",'d.note'=>'Note','s.nom'=>'ThirdParty');
		$this->export_entities_array[$r]=array('d.rowid'=>"Trip",'d.type'=>"Trip",'d.km'=>"Trip",'d.note'=>'Trip','s.nom'=>'company');
		$this->export_alias_array[$r]=array('d.rowid'=>"idtrip",'d.type'=>"type",'d.km'=>"km",'d.note'=>'note','s.nom'=>'name');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'deplacement as d, '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .=' WHERE d.fk_soc = s.rowid';
		
	}


	/**
	 *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
	 *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
	 */
	function init()
	{
		// Permissions
		$this->remove();

		$sql = array();

		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appel�e lors de la d�sactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);

	}
}
?>
