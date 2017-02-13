<?php
/* Copyright (C) 2004-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2013	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      	Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cedric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/commande/doc/pdf_proforma.modules.php
 *	\ingroup    commande
 *	\brief      Fichier de la classe permettant de generer les commandes au modele Proforma
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/doc/pdf_einstein.modules.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Classe permettant de generer les commandes au modele Proforma
 */
class pdf_proforma extends pdf_einstein
{

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		parent::__construct($db);

		$this->name = "proforma";
		$this->description = $langs->trans('PDFProformaDescription');
	}


	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	string		$titlekey		Translation key to show as title of document
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey="InvoiceProForma")
	{
		global $conf,$langs,$hookmanager;

		parent::_pagehead($pdf, $object, $showaddress, $outputlangs, $titlekey);
	}

}

