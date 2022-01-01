<?php
/* Copyright (C) 2017		ATM-Consulting  	 <support@atm-consulting.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/rapport/pdf_paiement_fourn.class.php
 *	\ingroup    banque
 *	\brief      File to build payment reports
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/rapport/pdf_paiement.class.php';

/**
 *	Classe permettant de generer les rapports de paiement
 */
class pdf_paiement_fourn extends pdf_paiement
{
	/**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
		$this->doc_type = "fourn";
	}
}
