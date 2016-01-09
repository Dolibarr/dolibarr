<?php
/* Copyright (C) 2012		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2015		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 */

/**
 *	\file       htdocs/core/class/html.formbank.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */


/**
 *	Class to manage generation of HTML components for bank module
 */
class FormBank
{
    var $db;
    var $error;


    /**
     * Constructor
     *
     * @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Retourne la liste des types de comptes financiers
     *
     *  @param	integer	$selected        Type pre-selectionne
     *  @param  string	$htmlname        Nom champ formulaire
     *  @return	void
     */
    function select_type_comptes_financiers($selected = Account::TYPE_CURRENT, $htmlname = 'type')
    {
        global $langs;
        $langs->load("banks");

        $account = new Account($this->db);
        $form = new Form($this->db);

        print $form->selectarray($htmlname, $account->type_lib, $selected);
    }

    /**
     * Returns the name of the Iban label. India uses 'IFSC' and the rest of the world 'IBAN' name.
     *
     * @param Account $account Account object
     * @return string
     */
    public function getIbanLabel(Account $account)
    {
        if ($account->getCountryCode() == 'IN') {
            return 'IFSC';
        }

        return 'IBANNumber';
    }

}

