<?php
/* Copyright (C) 2006-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2016		Juanjo Menent   	<jmenent@2byte.es>
 * Copyright (C) 2019	    Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2021		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file       htdocs/core/lib/bank.lib.php
 * \ingroup    bank
 * \brief      Ensemble de functions de base pour le module banque
 */


/**
 * Prepare array with list of tabs
 *
 * @param   Account	$object		Object related to tabs
 * @return  array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function bank_prepare_head(Account $object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("BankAccount");
	$head[$h][2] = 'bankname';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/compta/bank/bankentries_list.php?id=".$object->id;
	$head[$h][1] = $langs->trans("BankTransactions");
	$head[$h][2] = 'journal';
	$h++;

	if ($object->canBeConciliated() > 0) {
		$allowautomaticconciliation = getDolGlobalBool('MAIN_ALLOW_AUTOMATIC_CONCILIATION'); // TODO
		$titletoconciliatemanual = $langs->trans("Conciliate");
		$titletoconciliateauto = $langs->trans("Conciliate");
		if ($allowautomaticconciliation) {
			$titletoconciliatemanual .= ' ('.$langs->trans("Manual").')';
			$titletoconciliateauto .= ' ('.$langs->trans("Auto").')';
		}

		$param = '';

		// If not cash account and can be reconciliate
		if ($user->hasRight('banque', 'consolidate')) {
			$head[$h][0] = DOL_URL_ROOT."/compta/bank/bankentries_list.php?id=".$object->id.'&action=reconcile&sortfield=b.datev,b.dateo,b.rowid&sortorder=asc,asc,asc&search_conciliated=0&search_account='.$object->id.$param;
			$head[$h][1] = $titletoconciliatemanual;
			$head[$h][2] = 'reconcile';
			$h++;
		}/* else {
			$buttonreconcile = '<a class="butActionRefused classfortooltip" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$titletoconciliatemanual.'</a>';
		}*/

		if ($allowautomaticconciliation) {
			// If not cash account and can be reconciliate
			if ($user->hasRight('banque', 'consolidate')) {
				$newparam = $param;
				$newparam = preg_replace('/search_conciliated=\d+/i', '', $newparam);

				$head[$h][0] = DOL_URL_ROOT."/compta/bank/bankentries_list.php?id=".$object->id.'&action=reconcile&sortfield=b.datev,b.dateo,b.rowid&sortorder=asc,asc,asc&search_conciliated=0&search_account='.$object->id.$newparam;
				$head[$h][1] = $titletoconciliateauto;
				$head[$h][2] = 'reconcileauto';
				$h++;

				//$buttonreconcile .= ' <a class="butAction" style="margin-bottom: 5px !important; margin-top: 5px !important" href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?action=reconcile&sortfield=b.datev,b.dateo,b.rowid&sortorder=asc,asc,asc&search_conciliated=0'.$newparam.'">'.$titletoconciliateauto.'</a>';
			}/* else {
				$buttonreconcile .= ' <a class="butActionRefused" style="margin-bottom: 5px !important; margin-top: 5px !important" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$titletoconciliateauto.'</a>';
			}*/
		}
	}

	if ($object->type != Account::TYPE_CASH || getDolGlobalString('BANK_CAN_RECONCILIATE_CASHACCOUNT')) {
		$nbReceipts = 0;

		// List of all standing receipts
		$sql = "SELECT COUNT(DISTINCT(b.num_releve)) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
		$sql .= " WHERE b.fk_account = ".((int) $object->id);

		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$nbReceipts = $obj->nb;
			}
			$db->free($resql);
		}

		$head[$h][0] = DOL_URL_ROOT."/compta/bank/releve.php?account=".((int) $object->id);
		$head[$h][1] = $langs->trans("AccountStatements");
		if (($nbReceipts) > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbReceipts).'</span>';
		}
		$head[$h][2] = 'statement';
		$h++;
	}

	// Attached files
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->bank->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT."/compta/bank/document.php?account=".$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/compta/bank/annuel.php?account=".$object->id;
	$head[$h][1] = $langs->trans("Reports");
	$head[$h][2] = 'annual';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/compta/bank/treso.php?account=".$object->id;
	$head[$h][1] = $langs->trans("PlannedTransactions");
	$head[$h][2] = 'cash';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'bank');

	/*$head[$h][0] = DOL_URL_ROOT . "/compta/bank/info.php?id=" . $object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;*/

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'bank', 'remove');

	return $head;
}

/**
 * Prepare array with list of tabs for bank report
 *
 * @param   Account	$object		Object related to tabs
 * @return  array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function bank_report_prepare_head(Account $object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/compta/bank/annuel.php?account=".$object->id;
	$head[$h][1] = $langs->trans("IOMonthlyReporting");
	$head[$h][2] = 'annual';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/compta/bank/graph.php?account=".$object->id;
	$head[$h][1] = $langs->trans("Graph");
	$head[$h][2] = 'graph';
	$h++;

	return $head;
}
/**
 * Prepare array with list of tabs
 *
 * @param   ?CommonObject	$object						Object related to tabs
 * @return  array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function bank_admin_prepare_head($object)
{
	global $langs, $conf, $db;

	$langs->loadLangs(array("compta"));

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('bank_account');
	$extrafields->fetch_name_optionals_label('bank');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/bank.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/chequereceipts.php';
	$head[$h][1] = $langs->trans("CheckReceiptShort");
	$head[$h][2] = 'checkreceipts';
	$h++;


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'bank_admin');

	$head[$h][0] = DOL_URL_ROOT.'/admin/bank_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields").' ('.$langs->trans("BankAccounts").')';
	$nbExtrafields = $extrafields->attributes['bank_account']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/admin/bankline_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields").' ('.$langs->trans("BankTransactions").')';
	$nbExtrafields = $extrafields->attributes['bank']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'bankline_extrafields';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'bank_admin', 'remove');


	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   CommonObject					$object		Object related to tabs
 * @param   string							$num		val to account statement
 * @return  array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function account_statement_prepare_head($object, $num)
{
	global $langs, $conf, $db;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/releve.php?account='.$object->id.'&num='.$num;
	$head[$h][1] = $langs->trans("AccountStatement");
	$head[$h][2] = 'statement';
	$h++;

	// Attached files
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->bank->dir_output."/".$object->id.'/statement/'.dol_sanitizeFileName($num);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);

	$head[$h][0] = DOL_URL_ROOT."/compta/bank/account_statement_document.php?account=".$object->id."&num=".$num;
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'account_statement');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'account_statement', 'remove');

	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   CommonObject	$object		Object related to tabs
 * @return  array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function various_payment_prepare_head($object)
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/various_payment/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("VariousPayment");
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'various_payment');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->bank->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/various_payment/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/various_payment/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'various_payment', 'remove');

	return $head;
}

/**
 *      Check SWIFT information for a bank account
 *
 *      @param  ?Account	$account    A bank account (used to get BIC/SWIFT)
 *      @param	?string		$swift		Swift value (used to get BIC/SWIFT, param $account non used if provided)
 *      @return boolean                 True if information are valid, false otherwise
 */
function checkSwiftForAccount($account = null, $swift = null)
{
	if ($account == null && $swift == null) {
		return false;
	} elseif ($swift == null) {
		$swift = $account->bic;
	}
	if (preg_match("/^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/", $swift)) {
		return true;
	} else {
		return false;
	}
}

/**
 *      Check IBAN number information for a bank account.
 *
 *      @param  ?Account	$account    	A bank account
 *      @param	?string		$ibantocheck	Bank account number (used to get BAN, $account not used if provided)
 *      @return boolean                 	True if information are valid, false otherwise
 */
function checkIbanForAccount($account = null, $ibantocheck = null)
{
	if ($account == null && $ibantocheck == null) {
		return false;
	} elseif ($ibantocheck == null) {
		$ibantocheck = ($account->iban ? $account->iban : $account->iban_prefix);		// iban or iban_prefix for backward compatibility
	}
	require_once DOL_DOCUMENT_ROOT.'/includes/php-iban/oophp-iban.php';

	$iban = new PHP_IBAN\IBAN($ibantocheck);
	$check = $iban->Verify();

	if ($check) {
		return true;
	} else {
		return false;
	}
}

/**
 * Returns the iban human readable
 *
 * @param Account $account Account object
 * @return string
 */
function getIbanHumanReadable(Account $account)
{
	if ($account->getCountryCode() == 'FR') {
		require_once DOL_DOCUMENT_ROOT.'/includes/php-iban/oophp-iban.php';
		$ibantoprint = preg_replace('/[^a-zA-Z0-9]/', '', empty($account->iban) ? '' : $account->iban);
		$iban = new PHP_IBAN\IBAN($ibantoprint);
		return $iban->HumanFormat();
	}

	return $account->iban;
}

/**
 * 		Check account number information for a bank account
 *
 * 		@param	Account|CompanyBankAccount	$account    A bank account
 * 		@return boolean           		True if information are valid, false otherwise
 */
function checkBanForAccount($account)
{
	$country_code = $account->getCountryCode();

	// For compatibility between
	// account of type CompanyBankAccount class (we use number, cle_rib)
	// account of type Account class (we use num_compte, cle)
	if (empty($account->number)) {
		$account->number = $account->num_compte;  // @phan-suppress-current-line PhanUndeclaredProperty
	}
	if (empty($account->cle)) {
		$account->cle = $account->cle_rib;
	}

	dol_syslog("bank.lib::checkBanForAccount account->code_banque=".$account->code_banque." account->code_guichet=".$account->code_guichet." account->number=".$account->number." account->cle=".$account->cle." account->iban=".$account->iban." country_code=".$country_code, LOG_DEBUG);

	if ($country_code == 'FR') { // France rules
		$coef = array(62, 34, 3);
		// Concatenate the code parts
		$rib = strtolower(trim($account->code_banque).trim($account->code_guichet).trim($account->number).trim($account->cle));
		// On replace les eventuelles lettres par des chiffres.
		//$rib = strtr($rib, "abcdefghijklmnopqrstuvwxyz","12345678912345678912345678");	//Ne marche pas
		$rib = strtr($rib, "abcdefghijklmnopqrstuvwxyz", "12345678912345678923456789");
		// Separation du rib en 3 groups de 7 + 1 group de 2.
		// Multiplication de chaque group par les coef du tableau

		for ($i = 0, $s = 0; $i < 3; $i++) {
			$code = substr($rib, 7 * $i, 7);
			$s += ((int) $code) * $coef[$i];
		}
		// Soustraction du modulo 97 de $s a 97 pour obtenir la cle
		$cle_rib = 97 - ($s % 97);
		if ($cle_rib == $account->cle) {
			return true;
		}
		return false;
	}

	/*
	if ($country_code == 'BE') { // Belgian rules
	}
	*/

	if ($country_code == 'ES') { // Spanish rules
		$CCC = strtolower(trim($account->number));
		$rib = strtolower(trim($account->code_banque).trim($account->code_guichet));
		$cle_rib = strtolower(checkES($rib, $CCC));
		if ($cle_rib == strtolower($account->cle)) {
			return true;
		}
		return false;
	}
	if ($country_code == 'AU') {  // Australian
		if (strlen($account->code_banque) > 7) {
			return false; // Should be 6 but can be 123-456
		} elseif (strlen($account->code_banque) < 6) {
			return false; // Should be 6
		} else {
			return true;
		}
	}

	// No particular rule
	// If account is CompanyBankAccount class, we use number
	// If account is Account class, we use num_compte
	if (empty($account->number)) {
		return false;
	}

	return true;
}



/**
 * 	Returns the key for Spanish Banks Accounts
 *
 *  @param	string	$IentOfi	IentOfi
 *  @param	string	$InumCta	InumCta
 *  @return	string				Key
 */
function checkES($IentOfi, $InumCta)
{
	if (empty($IentOfi) || empty($InumCta) || strlen($IentOfi) != 8 || strlen($InumCta) != 10) {
		$keycontrol = "";
		return $keycontrol;
	}

	$ccc = $IentOfi.$InumCta;
	$numbers = "1234567890";

	$i = 0;

	while ($i <= strlen($ccc) - 1) {
		if (strpos($numbers, substr($ccc, $i, 1)) === false) {
			$keycontrol = "";
			return $keycontrol;
		}
		$i++;
	}

	$values = array(1, 2, 4, 8, 5, 10, 9, 7, 3, 6);
	$sum = 0;

	for ($i = 2; $i < 10; $i++) {
		$sum += $values[$i] * (int) substr($IentOfi, $i - 2, 1);
	}

	$key = 11 - $sum % 11;

	if ($key == 10) {
		$key = 1;
	}
	if ($key == 11) {
		$key = 0;
	}

	$keycontrol = $key;

	$sum = 0;

	for ($i = 0; $i < 11; $i++) {
		$sum += $values[$i] * (int) substr($InumCta, $i, 1); //int to cast result of substr to a number
	}

	$key = 11 - $sum % 11;

	if ($key == 10) {
		$key = 1;
	}
	if ($key == 11) {
		$key = 0;
	}

	$keycontrol .= $key;
	return $keycontrol;
}
