<?php
/* Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \defgroup   domain     Module domain
 *      \brief      Module to manage a list of DNS names
 *		\version	$Id$
 */

/**
 *       \file       htdocs/includes/modules/modDomain.class.php
 *       \ingroup    domain
 *       \brief      Fichier de description et activation du module domain
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
 *       \class      modDomain
 *       \brief      Classe de description et activation du module Domain
 */
class modDomain extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modDomain($DB)
	{
		$this->db = $DB;
		$this->numero = 1300 ;

		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion d'une base de noms de domaines";
		$this->version = 'development';			// 'development' or 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 3;
		$this->picto='user';

		// Dir
		//----
		$this->dirs = array();

		// Config pages
		//-------------
		$this->config_page_url = array();

		// Dependancies
		//-------------
		$this->depends = array();
		$this->requiredby = array();
		$this->langfiles = array("domains");

		// Constantes
		//-----------
		$this->const = array();

		// Boites
		//-------
		$this->boxes = array();

		// Permissions
		//------------
		$this->rights = array();
		$this->rights_class = 'domain';
		$r=0;

		$r++;
		$this->rights[$r][0] = 1301;
		$this->rights[$r][1] = 'Read domain names';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = 1302;
		$this->rights[$r][1] = 'Create/modify domain names';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'create';

		$r++;
		$this->rights[$r][0] = 1303;
		$this->rights[$r][1] = 'Delete domain names';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';

		// Exports
		//--------
		$r=0;

		// $this->export_code[$r]          Code unique identifiant l'export (tous modules confondus)
		// $this->export_label[$r]         Libelle par defaut si traduction de cle "ExportXXX" non trouvee (XXX = Code)
		// $this->export_permission[$r]    Liste des codes permissions requis pour faire l'export
		// $this->export_fields_sql[$r]    Liste des champs exportables en codif sql
		// $this->export_fields_name[$r]   Liste des champs exportables en codif traduction
		// $this->export_sql[$r]           Requete sql qui offre les donnees a l'export
	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		global $conf;

		// Permissions
		$this->remove();

		$sql = array();

		return $this->_init($sql);
	}

	/**
	 *    \brief      Fonction appelee lors de la desactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}

}
?>
