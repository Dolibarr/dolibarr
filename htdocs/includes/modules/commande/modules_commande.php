<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
 *
 */

/**	    \file       htdocs/includes/modules/commande/modules_commande.php
		\ingroup    commande
		\brief      Fichier contenant la classe mère de generation des commandes en PDF
		            et la classe mère de numérotation des commandes
		\version    $Revision$
*/



/**	    \class  ModelePDFCommandes
		\brief  Classe mère des modèles de commandes
*/

class ModelePDFCommandes extends FPDF
{
    var $error='';

   /** 
        \brief Renvoi le dernier message d'erreur de création de PDF de commande
    */
    function pdferror()
    {
        return $this->error;
    }

}



/**	\class  ModeleNumRefCommandes
	\brief  Classe mère des modèles de numérotation des références de commandes
*/

class ModeleNumRefCommandes
{
    var $error='';

    /**     \brief      Renvoi la description par defaut du modele de numérotation
     *      \return     string      Texte descripif
     */
    function info()
    {
        global $langs;
        $langs->load("orders");
        return $langs->trans("NoDescription");
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getExample()
    {
        global $langs;
        $langs->load("orders");
        return $langs->trans("NoExample");
    }

}


?>
