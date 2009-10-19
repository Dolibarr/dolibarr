<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/includes/modules/societe/mod_codecompta_panicum.class.php
        \ingroup    societe
        \brief      Fichier de la classe des gestion panicum des codes compta des societes clientes
        \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT."/includes/modules/societe/modules_societe.class.php");


/**
        \class 		mod_codecompta_panicum
        \brief 		Classe permettant la gestion panicum des codes compta des societes clients
*/

class mod_codecompta_panicum extends ModeleAccountancyCode
{
	var $nom;


	function mod_codecompta_panicum()
	{
		$this->nom = "Panicum";
	}


	function info($langs)
	{
		return $langs->trans("ModuleCompanyCode".$this->nom);
	}

  /**
   *    \brief      Renvoi code
   *    \param      DB              Handler d'acc�s base
   *    \param      societe         Objet societe
   */
  function get_code($DB, $societe)
  {
    // Renvoie toujours ok
    $this->code = $societe->code_compta;
    return 0;
  }
}

?>
