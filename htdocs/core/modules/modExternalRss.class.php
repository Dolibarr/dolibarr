<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \defgroup   externalrss     Module externalrss
 *	\brief      Module pour inclure des informations externes RSS
 *	\file       htdocs/core/modules/modExternalRss.class.php
 *	\ingroup    externalrss
 *	\brief      Fichier de description et activation du module externalrss
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module externalrss
 */
class modExternalRss extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 320;

		$this->family = "technic";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Ajout de files d'informations RSS dans les ecrans Dolibarr";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'rss';

		// Data directories to create when module is enabled
		$this->dirs = array("/externalrss/temp");

		// Config pages
		$this->config_page_url = array("external_rss.php");

		// Dependencies
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(4, 2, 0);
		$this->phpmax = array();

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();
		// Les boites sont ajoutees lors de la configuration des flux

		// Permissions
		$this->rights = array();
		$this->rights_class = 'externalrss';
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		$sql = array();

		// Recherche configuration de boites
		$this->boxes = array();
		$sql = "select name, value from ".MAIN_DB_PREFIX."const";
		$sql .= " WHERE name like 'EXTERNAL_RSS_TITLE_%'";
		$sql .= " AND entity = ".$conf->entity;
		$result = $this->db->query($sql);
		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{
				if (preg_match('/EXTERNAL_RSS_TITLE_([0-9]+)/i', $obj->name, $reg))
				{
					// Definie la boite si on a trouvee une ancienne configuration
					//$this->boxes[$reg[1]][0] = "(ExternalRSSInformations)";
					$this->boxes[$reg[1]]['file'] = "box_external_rss.php";
					$this->boxes[$reg[1]]['note'] = $reg[1]." (".$obj->value.")";
				}
			}
			$this->db->free($result);
		}

		$sql = array();

		return $this->_init($sql, $options);
	}

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
		$sql = array();

		// Delete old declarations of RSS box
		$this->boxes[0]['file'] = "box_external_rss.php";

		return $this->_remove($sql, $options);
    }
}
