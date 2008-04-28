<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo  <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007      Patrick Raguin 		<patrick.raguin@gmail.com>
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
        \file       htdocs/ecm/htmlecm.form.class.php
        \brief      Fichier de la classe des fonctions prédéfinie de composants html
		\version	$Id$
*/
require_once(DOL_DOCUMENT_ROOT."/ecm/ecmdirectory.class.php");


/**
        \class      FormEcm
        \brief      Classe permettant la génération de composants html
		\remarks	Only common components must be here.
*/
class FormEcm
{
	var $db;
	var $error;
    
	// Cache arrays
	var $cache_types_paiements=array();
	var $cache_conditions_paiements=array();

	var $tva_taux_value;
	var $tva_taux_libelle;

	
  /**
     \brief     Constructeur
     \param     DB      handler d'accès base de donnée
  */
	  function FormEcm($DB)
	  {
	    $this->db = $DB;
	    
	    return 1;
	  }

	  
    /**
     *    \brief    Retourne la liste des catégories du type choisi
     *    \param    selected    	Id categorie preselectionnee
     *    \param    select_name		Nom formulaire HTML
     */
    function select_all_sections($selected='',$select_name='')
    {
        global $langs;
        $langs->load("ecm");

		if ($select_name=="") $select_name="catParent";

		$cat = new ECMDirectory($this->db);
		$cate_arbo = $cat->get_full_arbo();

		$output = '<select class="flat" name="'.$select_name.'">';
		if (is_array($cate_arbo))
		{
			if (! sizeof($cate_arbo)) $output.= '<option value="-1" disabled="true">'.$langs->trans("NoCategoriesDefined").'</option>';
			else
			{
				$output.= '<option value="-1">&nbsp;</option>';
				foreach($cate_arbo as $key => $value)
				{
					if ($cate_arbo[$key]['id'] == $selected)
					{
						$add = 'selected="true" ';
					}
					else
					{
						$add = '';
					}
					$output.= '<option '.$add.'value="'.$cate_arbo[$key]['id'].'">'.$cate_arbo[$key]['fulllabel'].'</option>';
				}
			}
		}
		$output.= '</select>';
		$output.= "\n";
		return $output; 
	}
}

?>
