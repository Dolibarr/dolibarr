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

/**	    \file       htdocs/includes/modules/mailings/modules_mailings.php
		\ingroup    mailing
		\brief      Fichier contenant la classe mère des classes de liste de destinataires mailing
		\version    $Revision$
*/


/**	    \class      MailingTargets
		\brief      Classe mère des classes de liste de destinataires mailing
*/

class MailingTargets
{
    var $error='';

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Retourne la traduction de la clé MailingModuleDescXXX ou XXX nom du module, ou $this->desc si non trouvé
     */
    function getDesc()
    {
        global $langs;
        $langs->load("mails");
        $transstring="MailingModuleDesc".$this->name;
        if ($langs->trans($transstring) != $transstring) return $langs->trans($transstring); 
        else return $this->desc;
    }

    /**     \brief      Renvoi un exemple de numérotation
     *      \return     string      Example
     */
    function getNbOfRecords()
    {
        return 0;
    }
}

?>
