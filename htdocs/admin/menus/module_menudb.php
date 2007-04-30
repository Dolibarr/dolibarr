<?php

/* Copyright (C) 2007 Patrick Raguin <patrick.raguin@gmail.com>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/includes/modules/menudb/modules_menudb.php
		\ingroup    menudb
		\brief      Fichier contenant la classe mère d'affichage des menus'
		\version    $Revision$
*/

class menuDb {
	var $newmenu;
	var $mainmenu;
	var $leftmenu;

	function menuDb($db) {
		$this->db = $db;
	}

	function menuCharger($mainmenu, $newmenu,$type_user, $leftmenu) 
	{

		global $langs,$user, $conf;

		$this->mainmenu = $mainmenu;
		$this->newmenu = $newmenu;
		$this->leftmenu = $leftmenu;

		$sql = "SELECT m.rowid, m.titre, m.level FROM " . MAIN_DB_PREFIX . "menu as m WHERE m.mainmenu = '" . $this->mainmenu . "'";
		$result = $this->db->query($sql);
		$menuTop = $this->db->fetch_object($result);

		$data[] = array ($menutop->rowid,-1,$this->mainmenu);

		$sql = "SELECT m.rowid, m.fk_menu, m.url, m.titre, m.langs, m.right, m.target, m.mainmenu, m.leftmenu FROM " . MAIN_DB_PREFIX . "menu as m ";
		if($type_user == 0)$sql.= "WHERE m.user <> 1 ";
		else $sql.= "WHERE m.user > 0 ";
		$sql.= "ORDER BY m.order, m.rowid";
		$res = $this->db->query($sql);

		if ($res) {
			$num = $this->db->num_rows();

			$i = 1;
			while ($menu = $this->db->fetch_array($res)) {
				$langs->load($menu['langs']);
				$titre = $langs->trans($menu['titre']);
				$rights = $this->verifRights($menu['right']);
				$data[] = array (
					$menu['rowid'],
					$menu['fk_menu'],
					$menu['url'],
					$titre,
					$rights,
					$menu['target'],
					$menu['leftmenu']
				);
				$i++;

			}

		}

		$this->recur($data, $menuTop->rowid, 1);

		return $this->newmenu;

	}

	function recur($tab, $pere, $rang) {
		$leftmenu = $this->leftmenu;
		//ballayage du tableau
		for ($x = 0; $x < count($tab); $x++) {

			//si un élément a pour père : $pere
			if ($tab[$x][1] == $pere) {

				//on affiche le menu

				if ($this->verifConstraint($tab[$x][0], $tab[$x][6], $tab[$x][7]) != 0) {

					if ($tab[$x][6]) {

						$leftmenuConstraint = false;
						$str = "if(" . $tab[$x][6] . ") \$leftmenuConstraint = true;";

						eval ($str);
						if ($leftmenuConstraint == true) {
							$this->newmenu->add_submenu(DOL_URL_ROOT . $tab[$x][2], $tab[$x][3], $rang -1, $tab[$x][4], $tab[$x][5]);
							$this->recur($tab, $tab[$x][0], $rang +1);
						}
					} else {
						$this->newmenu->add_submenu(DOL_URL_ROOT . $tab[$x][2], $tab[$x][3], $rang -1, $tab[$x][4], $tab[$x][5]);
						$this->recur($tab, $tab[$x][0], $rang +1);
					}

				}
			}
		}
	}

	function verifConstraint($rowid, $mainmenu = "", $leftmenu = "") 
	{
		global $user, $conf, $user;

		$constraint = true;

		$sql = "SELECT c.rowid, c.action, mc.user FROM " . MAIN_DB_PREFIX . "menu_constraint as c, " . MAIN_DB_PREFIX . "menu_const as mc WHERE mc.fk_constraint = c.rowid AND (mc.user = 0 OR mc.user = 2 ) AND mc.fk_menu = '" . $rowid . "'";
		$result = $this->db->query($sql);

		if ($result) 
		{
			//echo $sql;
			$num = $this->db->num_rows();
			$i = 0;
			while (($i < $num) && $constraint == true) 
			{
				$obj = $this->db->fetch_object($result);
				$strconstraint = "if(!(" . $obj->action . ")) { \$constraint = false;}";

				eval ($strconstraint);
				$i++;
			}
		}

		return $constraint;
	}

	function verifRights($strRights) {

		global $user,$conf,$user;

		if ($strRights != "") {
			$rights = true;

			$tab_rights = explode(" || ", $strRights);
			$i = 0;
			while (($i < count($tab_rights)) && ($rights == true)) {
				$str = "if(!(" . $strRights . ")) { \$rights = false;}";
				eval ($str);
				$i++;
			}
		} else
			$rights = true;

		return $rights;
	}

	function listeMainmenu() {
		$sql = "SELECT DISTINCT m.mainmenu FROM " . MAIN_DB_PREFIX . "menu as m";
		$res = $this->db->query($sql);

		if ($res) {
			$i = 0;
			while ($menu = $this->db->fetch_array($res)) {
				$overwritemenufor[$i] = $menu['mainmenu'];
				$i++;
			}
		}
		
		return $overwritemenufor;
	}
	
	
	function menutopCharger($type_user,$mainmenu)
	{
		
		global $langs, $user, $conf;
		
		$sql = "SELECT m.rowid, m.mainmenu, m.titre, m.url, m.langs, m.right FROM ".MAIN_DB_PREFIX."menu as m WHERE m.level = -1 "; 
		if($type_user == 0)$sql.= "AND m.user <> 1 ";
		else $sql.= "AND m.user > 0 ";
		$sql.= "ORDER BY m.order";
		$result = $this->db->query($sql);

		if ($result)
		{
				
			$numa = $this->db->num_rows();

			$a = 0;
			$b = 0;
			while ($a < $numa)
			{
				// Affichage entete menu
				$objm = $this->db->fetch_object($result);
				
				if ($this->verifConstraint($objm->rowid))
		        {
		            $langs->load($objm->langs);
		        
		            $class="";
		            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == $objm->mainmenu)
		            {
		                $class='id="sel"';
		            }
		            $chaine="";
		        	
		        	$right = true;
		        	
		        	if ($objm->right)
		        	{
		        		$str = "if(!(".$objm->right.")) \$right = false;";
		        		eval($str);
		        	}
		        	
		        	if(eregi("/",$objm->titre))
		        	{
		        		$tab_titre = explode("/",$objm->titre);
		        		$chaine = $langs->trans($tab_titre[0])."/".$langs->trans($tab_titre[1]);
		        	}
		        	else
		        	{
		        		$chaine = $langs->trans($objm->titre);
		        	} 
		        		
		        	$tabMenu[$b]['titre'] = $chaine;
		        	$tabMenu[$b]['url'] = $objm->url;
		        	$tabMenu[$b]['atarget'] = $this->atarget;
		        	$tabMenu[$b]['class'] = $class;
		        	$tabMenu[$b]['right'] = $right;
		        	
					$b++;
					
		        }	
		        			
				$a++;	
			}
		}
		
		return $tabMenu;
		
	}
}
?>
