<?php
/* Copyright (C) 2005      Christophe
 * Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 *      \file       htdocs/core/boxes/box_comptes.php
 *      \ingroup    banque
 *      \brief      Module to generate box for bank accounts
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';


/**
 * Class to manage the box to show last users
 */
class box_comptes extends ModeleBoxes
{
	var $boxcode="currentaccounts";
	var $boximg="object_bill";
	var $boxlabel="BoxCurrentAccounts";
	var $depends = array("banque");     // Box active if module banque active

	var $db;
	var $param;
	var $enabled = 1;

	var $info_box_head = array();
	var $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB	$db      	Database handler
     *  @param	string	$param		More parameters
	 */
	function __construct($db,$param='')
	{
		global $conf, $user;

		$this->db = $db;

		// disable module for such cases
		$listofmodulesforexternal=explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL);
		if (! in_array('banque',$listofmodulesforexternal) && ! empty($user->societe_id)) $this->enabled=0;	// disabled for external users

		$this->hidden = ! ($user->rights->banque->lire);
	}

	/**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
	 */
	function loadBox($max=5)
	{
		global $user, $langs, $db, $conf;

		$this->max=$max;

		$this->info_box_head = array('text' => $langs->trans("BoxTitleCurrentAccounts"));

        if ($user->rights->banque->lire) {
			$sql = "SELECT b.rowid, b.ref, b.label, b.bank,b.number, b.courant, b.clos, b.rappro, b.url";
			$sql.= ", b.code_banque, b.code_guichet, b.cle_rib, b.bic, b.iban_prefix as iban";
			$sql.= ", b.domiciliation, b.proprio, b.owner_address";
			$sql.= ", b.account_number, b.currency_code";
			$sql.= ", b.min_allowed, b.min_desired, comment";
            $sql.= ', b.fk_accountancy_journal';
            $sql.= ', aj.code as accountancy_journal';
            $sql.= " FROM ".MAIN_DB_PREFIX."bank_account as b";
            $sql.= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'accounting_journal as aj ON aj.rowid=b.fk_accountancy_journal';
            $sql.= " WHERE b.entity = ".$conf->entity;
			$sql.= " AND clos = 0";
			//$sql.= " AND courant = 1";
			$sql.= " ORDER BY label";
			$sql.= $db->plimit($max, 0);

            dol_syslog(get_class($this)."::loadBox", LOG_DEBUG);
            $result = $db->query($sql);
            if ($result) {
                $num = $db->num_rows($result);

                $line = 0;
                $solde_total = array();

                $account_static = new Account($db);
                while ($line < $num) {
                    $objp = $db->fetch_object($result);

                    $account_static->id = $objp->rowid;
					$account_static->ref = $objp->ref;
                    $account_static->label = $objp->label;
                    $account_static->number = $objp->number;
                    $account_static->account_number = $objp->account_number;
                    $account_static->currency_code = $objp->currency_code;
                    $account_static->accountancy_journal = $objp->accountancy_journal;
                    $solde=$account_static->solde(0);

                    $solde_total[$objp->currency_code] += $solde;

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $account_static->getNomUrl(1),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $objp->number,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => price($solde, 0, $langs, 0, -1, -1, $objp->currency_code)
                    );

                    $line++;
                }

                // Total
                foreach ($solde_total as $key=>$solde) {
                    $this->info_box_contents[$line][] = array(
                        'tr' => 'class="liste_total"',
                        'td' => 'align="left" class="liste_total"',
                        'text' => $langs->trans('Total').' '.$key,
                    );
                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right" class="liste_total"',
                        'text' => '&nbsp;'
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right" class="liste_total"',
                        'text' => price($solde, 0, $langs, 0, -1, -1, $key)
                    );
                    $line++;
                }

                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }
        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'align="left" class="nohover opacitymedium"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
            );
        }

	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}

}

