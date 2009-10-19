<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");

class box_energie_graph extends ModeleBoxes {

	var $boxcode="energie_graph";
	var $boximg="object_energie";
	var $boxlabel;
	var $depends = array("energie");     // Box active si module energie actif

	var $db;
	var $param;

	var $box_multiple = 1;

	var $info_box_head = array();
	var $info_box_contents = array();

	/**
	 *      \brief      Constructeur de la classe
	 */
	function box_energie_graph()
	{
		global $langs;
		$langs->load("boxes");

		$this->boxlabel=$langs->trans("EnergieGraph");
	}

	/**
	 *      \brief      Charge les donn�es en m�moire pour affichage ult�rieur
	 *      \param      $max        Nombre maximum d'enregistrements � charger
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db;
		$langs->load("boxes");

		$this->max=$max;

		$text = '<a href="energie/">'.$langs->trans("Energie").'</a>';

		$this->info_box_head = array('text' => $text,$max);


		$file = "small-all.1.png";
		$libelle = '<img border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';


		$this->info_box_contents[0][0] = array('td' => 'align="left" width="16"',
					      'logo' => $this->boximg,
					      'url' => DOL_URL_ROOT."/energie/compteur.php?id=".$objp->rowid);
		$this->info_box_contents[0][1] = array('td' => 'align="left"',
					      'text' => $libelle,
					      'url' => DOL_URL_ROOT."/energie/compteur.php?id=".$objp->rowid);


	}

	function showBox()
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}

?>
