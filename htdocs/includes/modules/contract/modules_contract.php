<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
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
 *  \file       htdocs/includes/modules/contract/mod_contract.php
 *  \ingroup    contract
 *  \brief      File of class to manage contract numbering
 *  \version    $Id$
 */

class ModelNumRefContracts
{
	var $error='';

	/**     
	 *	Return if a module can be used or not
	 * @return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 *	Return default description of numbering model
	 *	@return     string      text description
	 */
	function info()
	{
		global $langs;
		$langs->load("contracts");
		return $langs->trans("NoDescription");
	}

	/**     
	 *	Return numbering example
	 *	@return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("contracts");
		return $langs->trans("NoExample");
	}

	/**     
	 *	Test if existing numbers make problems with numbering
	 *	@return     boolean     false if conflit, true if ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/** 
	 *	Return next value
	 *	@return     string      Value
	 */
	function getNextValue()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Return numbering version module
	 *	@return     string      Value
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}
?>
