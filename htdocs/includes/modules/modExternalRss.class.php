<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**     \defgroup   externalrss     Module externalrss
        \brief      Module pour inclure des informations externes RSS
*/

/**
        \file       htdocs/includes/modules/modExternalRss.class.php
        \ingroup    externalrss
        \brief      Fichier de description et activation du module externalrss
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modExternalRss
		\brief      Classe de description et activation du module externalrss
*/

class modExternalRss extends DolibarrModules
{

	/**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
	function modExternalRss($DB)
	{
		global $conf;
	
		$this->db = $DB ;
		$this->id = 'externalrss';   // Same value xxx than in file modXxx.class.php file
		$this->numero = 320;
	
		$this->family = "technic";
		$this->name = "Syndication RSS";
		$this->description = "Ajout de files d'informations RSS dans les écrans Dolibarr";
		$this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
		$this->const_name = 'MAIN_MODULE_EXTERNALRSS';
		$this->special = 1;
		$this->picto='rss';
	
		// Dir
		$this->dirs = array();
	
		// Config pages
		$this->config_page_url = array("external_rss.php");
	
		// Dépendances
		$this->depends = array();
		$this->requiredby = array();
		$this->phpmin = array(4,2,0);
		$this->phpmax = array();
	
		// Constantes
		$this->const = array();
	
		// Répertoires
		$this->dirs = array();
		$this->dirs[0] = $conf->externalrss->dir_temp;
	
		// Boxes
		$this->boxes = array();
		// Les boites sont ajoutées lors de la configuration des flux
	
		// Permissions
		$this->rights = array();
		$this->rights_class = 'externalrss';
	}

	/**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
	function init()
	{
		$sql = array();
	
		// Recherche configuration de boites
		$this->boxes=array();
		$sql="select name, value from ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE name like 'EXTERNAL_RSS_TITLE_%'";
		$result=$this->db->query($sql);
		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{
				if (eregi('EXTERNAL_RSS_TITLE_([0-9]+)',$obj->name,$reg))
				{
					// Definie la boite si on a trouvée une ancienne configuration
					$this->boxes[$reg[1]][0] = "(ExternalRSSInformations)";
					$this->boxes[$reg[1]][1] = "box_external_rss.php";
					$this->boxes[$reg[1]][2] = $reg[1]." (".$obj->value.")";
				}
			}
			$this->db->free($result);
		}
	
		$sql = array();
	
		return $this->_init($sql);
	}

	/**
	*    \brief      Fonction appelée lors de la désactivation d'un module.
	*                Supprime de la base les constantes, boites et permissions du module.
	*/
	function remove()
	{
		$sql = array();
	
		// Supprime anciennes delcarations de la boite RSS
		$this->boxes[0][1] = "box_external_rss.php";
	
		return $this->_remove($sql);
	}
}
?>
