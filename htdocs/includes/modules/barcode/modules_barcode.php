<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *   \file       htdocs/includes/modules/barcode/modules_barcode.php
 *   \ingroup    barcode
 *   \brief      Fichier contenant la classe mere de generation des codes barres
 *   \version    $Id: modules_barcode.php,v 1.5 2011/07/31 23:28:17 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT.'/lib/functions.lib.php');


/**
 *  \class      ModeleBarCode
 *	\brief      Classe mere des modeles de code barre
 */
class ModeleBarCode
{
	var $error='';


	/**     \brief     	Return if a module can be used or not
	*      	\return		boolean     true if module can be used
	*/
	function isEnabled()
	{
		return true;
	}

}

?>
