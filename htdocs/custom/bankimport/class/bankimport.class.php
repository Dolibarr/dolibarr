<?php

dol_include_once('/compta/bank/class/account.class.php');
dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';

class BankImport
{
	/** @var string Negative direction token */
	private $neg_dir;

	protected $db;

	/** @var Account */
	public $account;
	public $file;

	public $dateStart;
	public $dateEnd;
	public $numReleve;
	public $hasHeader;
	public $lineHeader; // When using history, we keep the original header to preserve the correct column titles
	public $TOriginLine=array(); // Contains the original file lines, for history tracking

	public $TBank = array(); // Will contain all account lines of the period
	public $TCheckReceipt = array(); // Will contain check receipt made for account lines of the period
	public $TFile = array(); // Will contain all file lines

	public $nbCreated = 0;
	public $nbReconciled = 0;

	function __construct($db) {
		$this->db = &$db;
		$this->dateStart = strtotime('first day of last month');
		$this->dateEnd = strtotime('last day of last month');
	}

	/**
	 * Set vars we will work with
	 */
	function analyse($accountId, $filename, $dateStart, $dateEnd, $numReleve, $hasHeader) {
		global $conf, $langs;

		// Bank account selected
		if($accountId <= 0) {
			setEventMessage($langs->trans('ErrorAccountIdNotSelected'), 'errors');
			return false;
		} else {
			$this->account = new Account($this->db);
			$this->account->fetch($accountId);
		}

		// Start and end date regarding bank statement
		$this->dateStart = $dateStart;
		$this->dateEnd = $dateEnd;

		// Statement number
		$this->numReleve = $numReleve;
		$this->hasHeader = $hasHeader;

		// Bank statement file (csv or filename if csv already uploaded)
		if(is_file($filename)) {
			$this->file = $filename;
		} else if(!empty($_FILES[$filename])) {

			if($_FILES[$filename]['error'] != 0) {
				setEventMessage($langs->trans('ErrorFile' . $_FILES[$filename]['error']), 'errors');
				return false;
			}/* else if($_FILES[$filename]['type'] != 'text/csv' && $_FILES[$filename]['type'] != 'text/plain' &&  && $_FILES[$filename]['type'] != 'application/octet-stream') {
				setEventMessage($langs->trans('ErrorFileIsNotCSV') . ' ' . $_FILES[$filename]['type'], 'errors');
				return false;
			}*/
			else {

				dol_include_once('/core/lib/files.lib.php');
				dol_include_once('/core/lib/images.lib.php');
				$upload_dir = $conf->bankimport->dir_output . '/' . dol_sanitizeFileName($this->account->ref);

				dol_add_file_process($upload_dir,1,1,$filename);
				$this->file = $upload_dir . '/' . $_FILES[$filename]['name'];
				$info = pathinfo($this->file);
				$this->file = $info['dirname'].'/'.dol_sanitizeFileName($info['filename'].'.'.strtolower($info['extension']));

				if(!is_file($this->file)) {
					return false;
				}
			}
		}

		return true;
	}

	function load_transactions($delimiter='', $dateFormat='', $mapping_string='', $enclosure='"') {
		global $hookmanager;

		// Allow hook-based override
		if (is_object($hookmanager)) {
			$parameters = array("moduleName" => "bankimport");
			$action = "load_transactions";
			$reshook = $hookmanager->executeHooks('doActions', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook > 0) {
				print $hookmanager->resPrint;
				return;
			} else {
                setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
            }

		}

		$this->load_bank_transactions();
		$this->load_check_receipt();
		$this->load_file_transactions($delimiter, $dateFormat, $mapping_string, $enclosure);
	}

	// Load bank lines
	function load_bank_transactions() {
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "bank WHERE fk_account = " . $this->account->id . " ";
		$sql.= "AND dateo BETWEEN '" . date('Y-m-d', $this->dateStart) . "' AND '" . date('Y-m-d', $this->dateEnd) . "' ";
		$sql.= "ORDER BY datev ASC";

		$resql = $this->db->query($sql);
		$TBankLineId = array();
		while($obj = $this->db->fetch_object($resql)) {
			$TBankLineId[] = $obj->rowid;
		}

		foreach($TBankLineId as $bankid) {
			$bankLine = new AccountLine($this->db);
			$bankLine->fetch($bankid);
			$this->TBank[$bankid] = $bankLine;
		}
	}

	// Load check receipt regarding bank lines
	function load_check_receipt() {
		foreach($this->TBank as $bankLine) {
			if($bankLine->fk_bordereau > 0 && empty($this->TCheckReceipt[$bankLine->fk_bordereau])) {
				$bord = new RemiseCheque($this->db);
				$bord->fetch($bankLine->fk_bordereau);

				$this->TCheckReceipt[$bankLine->fk_bordereau] = $bord;
			}
		}
	}

	// Load file lines
	function load_file_transactions($delimiter='', $dateFormat='', $mapping_string='', $enclosure='"') {
		global $conf, $langs, $hookmanager;

		// Allow hook-based override
		if (is_object($hookmanager)) {
			$parameters = array("moduleName" => "bankimport");
			$action = "load_file_transactions";
			$reshook = $hookmanager->executeHooks('doActions', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook > 0) {
				print $hookmanager->resPrint;
				return;
			} else {
                setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
            }
		}
		if(empty($delimiter)) $delimiter = getDolGlobalString('BANKIMPORT_SEPARATOR');
		if(empty($dateFormat)) $dateFormat = strtr(getDolGlobalString('BANKIMPORT_DATE_FORMAT'), array('%'=>''));

		if(empty($mapping_string)) $mapping_string = getDolGlobalString('BANKIMPORT_MAPPING');
		$mapping_string = preg_replace_callback('|=([^' . $delimiter . ']*)|', 'BankImport::extractNegDir', $mapping_string);

		if($delimiter == '\t')$delimiter="\t";

		if(strpos($mapping_string,$delimiter) === false) $mapping = explode(";", $mapping_string); // for tab delimiter
		else $mapping = explode($delimiter, $mapping_string); // for tab delimiter

		$f1 = fopen($this->file, 'r');
		if($this->hasHeader) $this->lineHeader = fgets($f1, 4096);

		while(!feof($f1)) {

			if(getDolGlobalString('BANKIMPORT_MAC_COMPATIBILITY')) {
				$ligne = fgets($f1, 4096);
				if (empty($ligne)) continue;
//				print '<hr>'.$ligne.'<br />';
				$dataline = str_getcsv(trim($ligne), $delimiter, $enclosure);

			}
			else {
				$dataline = fgetcsv($f1, 4096, $delimiter, $enclosure);
				if (empty($dataline)) continue;
			}
//		  var_dump($dataline, $delimiter, $enclosure);

			$mapping_en_colonne = (strpos($mapping_string, ':') !== false) ? true : false;

			if((count($dataline) == count($mapping)) || $mapping_en_colonne) {
				$this->TOriginLine[] = $dataline;

				if($mapping_en_colonne) $data = $this->construct_data_tab_column_file($mapping, $dataline[0]);
				else $data = array_combine($mapping, $dataline);

				// Handle debit / credit amount
				if (empty($data['debit']) && empty($data['credit'])) {
					$amount = (float)price2num($data['amount']);

					// Direction support
					if (!empty($data['direction'])) {
						if ($data['direction'] == $this->neg_dir) {
							$amount *= -1;
						}
					}

					if ($amount >= 0) {
						$data['credit'] = $amount;
					} elseif ($amount < 0) {
						$data['debit'] = $amount;
					}
				} else {
					$data['debit'] = (float)price2num($data['debit']);

					if ($data['debit'] > 0) {
						$data['debit'] *= -1;
					}
					$data['credit'] = (float)price2num($data['credit']);
				}

				$data['amount'] = (!empty($data['debit']) ? $data['debit'] : $data['credit']);

				//$time = date_parse_from_format($dateFormat, $data['date']);
				//$data['datev'] = mktime(0, 0, 0, $time['month'], $time['day'], $time['year']+2000);

				// TODO: createFromFormat does not work with PHP < 5.3
				$datetime = DateTime::createFromFormat($dateFormat, $data['date']);

				$data['datev'] = ($datetime === false) ? 0 : $datetime->getTimestamp();

				$data['error'] = '';
			} else {
				$data = array();
				$data['error'] = $langs->trans('LineDoesNotMatchWithMapping');
			}

			$this->TFile[] = $data;
		}

		fclose($f1);
	}

	function construct_data_tab_column_file(&$mapping, $data) {

		$TDataFinal = array();
		$pos = 0;
		foreach($mapping as $m) {

			$TTemp = explode(':', $m);

			$label_colonne = $TTemp[0];
			$nb_car = $TTemp[1];
			$res = substr($data, $pos, $nb_car);
			$res = trim($res);
			$TDataFinal[$label_colonne] = $res;
			$pos += $nb_car;
		}

		return $TDataFinal;

	}

	function compare_transactions() {

		// For each file transaction, we search in Dolibarr bank transaction if there is a match by amount
		foreach($this->TFile as &$fileLine) {
			$amount = price2num($fileLine['amount']); // Transform to numeric string
			if(is_numeric($amount)) {
				$transac = $this->search_dolibarr_transaction_by_amount($amount, $fileLine['label'], $fileLine['datev']);
				if($transac === false) $transac = $this->search_dolibarr_transaction_by_receipt($amount);
				$fileLine['bankline'] = $transac;
			}
		}
	}

	private function search_dolibarr_transaction_by_amount($amount, $label, $fileDatev = 0) {
		global $conf, $langs;
		$langs->load("banks");

		$amount = floatval($amount); // Transform to float
		$useDateProximity = getDolGlobalString('BANKIMPORT_USE_DATE_PROXIMITY');
		$toleranceDays = getDolGlobalInt('BANKIMPORT_DATE_TOLERANCE_DAYS');

		if ($useDateProximity && $fileDatev > 0) {
			// Date-proximity mode: find the closest match by date among all amount matches
			$bestIndex = null;
			$bestDiff = PHP_INT_MAX;

			foreach ($this->TBank as $i => $bankLine) {
				$test = ($amount == $bankLine->amount);
				if (getDolGlobalString('BANKIMPORT_MATCH_BANKLINES_BY_AMOUNT_AND_LABEL')) {
					$test = ($amount == $bankLine->amount && $label == $bankLine->label);
				}
				if (!empty($test)) {
					$bankDatev = is_int($bankLine->datev) ? $bankLine->datev : strtotime($bankLine->datev);
					$dateDiff = abs($fileDatev - $bankDatev) / 86400; // difference in days

					// Skip if outside tolerance window
					if ($toleranceDays > 0 && $dateDiff > $toleranceDays) {
						continue;
					}

					if ($dateDiff < $bestDiff) {
						$bestDiff = $dateDiff;
						$bestIndex = $i;
					}
				}
			}

			if ($bestIndex !== null) {
				$bankLine = $this->TBank[$bestIndex];
				unset($this->TBank[$bestIndex]);
				return array($this->get_bankline_data($bankLine));
			}
		} else {
			// Original behavior: first match wins
			foreach ($this->TBank as $i => $bankLine) {
				$test = ($amount == $bankLine->amount);
				if (getDolGlobalString('BANKIMPORT_MATCH_BANKLINES_BY_AMOUNT_AND_LABEL')) {
					$test = ($amount == $bankLine->amount && $label == $bankLine->label);
				}
				if (!empty($test)) {
					unset($this->TBank[$i]);
					return array($this->get_bankline_data($bankLine));
				}
			}
		}

		return false;
	}

	/**
	 * @param $amount
	 * @return array|false
	 */
	private function search_dolibarr_transaction_by_receipt($amount) {
		global $langs;
		$langs->load("banks");

		$amount = floatval($amount); // Transform to float
		foreach($this->TCheckReceipt as $bordereau) {
			if($amount == $bordereau->amount) {
				$TBankLine = array();
				foreach($this->TBank as $i => $bankLine) {
					if($bankLine->fk_bordereau == $bordereau->id) {
						unset($this->TBank[$i]);

						$TBankLine[] = $this->get_bankline_data($bankLine);
					}
				}

				return $TBankLine;
			}
		}

		return false;
	}

	private function get_bankline_data($bankLine) {
		global $langs, $db;

		if(!empty($bankLine->num_releve)) {
			$link = '<a href="' . dol_buildpath(
				'/compta/bank/releve.php'
					. '?num=' . $bankLine->num_releve
					. '&account=' . $bankLine->fk_account, 2
				) . '">'
				. $bankLine->num_releve
				. '</a>';
			$result = $langs->transnoentitiesnoconv('AlreadyReconciledWithStatement', $link);
			$autoaction = false;
		} else {
			$result = $langs->transnoentitiesnoconv('WillBeReconciledWithStatement', $this->numReleve);
			$autoaction = true;
		}

		$societestatic = new Societe($db);
		$userstatic = new User($db);
		$chargestatic = new ChargeSociales($db);
		$memberstatic = new Adherent($db);

		$links = $this->account->get_url($bankLine->id);
		$relatedItem = '';
		foreach($links as $key=>$val) {
			if ($links[$key]['type'] == 'company') {
				$societestatic->id = $links[$key]['url_id'];
				$societestatic->name = $links[$key]['label'];
				$relatedItem = $societestatic->getNomUrl(1,'',16);
			} else if ($links[$key]['type'] == 'user') {
				$userstatic->id = $links[$key]['url_id'];
				$userstatic->lastname = $links[$key]['label'];
				$relatedItem = $userstatic->getNomUrl(1,'');
			} else if ($links[$key]['type'] == 'sc') {
				// sc=old value
				$chargestatic->id = $links[$key]['url_id'];
				if (preg_match('/^\((.*)\)$/i',$links[$key]['label'],$reg)) {
					if ($reg[1] == 'socialcontribution') $reg[1] = 'SocialContribution';
					$chargestatic->lib = $langs->trans($reg[1]);
				} else {
					$chargestatic->lib = $links[$key]['label'];
				}
				$chargestatic->ref = $chargestatic->lib;
				$relatedItem = $chargestatic->getNomUrl(1,16);
			} else if ($links[$key]['type'] == 'member') {
				$memberstatic->id = $links[$key]['url_id'];
				$memberstatic->ref = $links[$key]['label'];
				$relatedItem = $memberstatic->getNomUrl(1,16,'card');
			}
		}

		return array(
			'id' => $bankLine->id
			,'url' => $bankLine->getNomUrl(1)
			,'date' => dol_print_date($bankLine->datev,"day")
			,'label' => (preg_match('/^\((.*)\)$/i',$bankLine->label,$reg) ? $langs->trans($reg[1]) : dol_trunc($bankLine->label,60))
			,'amount' => price($bankLine->amount)
			,'result' => $result
			,'autoaction' => $autoaction
			,'relateditem' => $relatedItem
			,'time' => $bankLine->datev
		);
	}

	/**
	 * Actions made after file check by user
	 */
	public function import_data($TLine)
	{
		global $conf, $db;

		$TLineBackup = $TLine; // backup because the program modifies the array it iterates over

		$PDOdb = new TPDOdb;
		if (!empty($TLine['piece']))
		{
			dol_include_once('/compta/paiement/class/paiement.class.php');
			dol_include_once('/fourn/class/paiementfourn.class.php');
			dol_include_once('/fourn/class/fournisseur.facture.class.php');
			dol_include_once('/compta/sociales/class/paymentsocialcontribution.class.php');

			/*
			 * Manually created payments
			 */

			$db = &$this->db;
			foreach($TLine['piece'] as $iFileLine=>$TObject)
			{
				if(!empty($TLine['fk_soc'][$iFileLine])) {
					$l_societe = new Societe($db);
					$l_societe->fetch($TLine['fk_soc'][$iFileLine]);
				}

				$fk_payment = $TLine['fk_payment'][$iFileLine];
				$date_paye = $this->TFile[$iFileLine]['datev'];

				foreach($TObject as $typeObject=>$TAmounts)
				{
					if(!empty($TAmounts))
					{
						switch ($typeObject)
						{
							case 'facture':
								$fk_bank = $this->doPaymentForFacture($TLine, $TAmounts, $l_societe, $iFileLine, $fk_payment, $date_paye);
								break;
							case 'fournfacture':
								$fk_bank = $this->doPaymentForFactureFourn($TLine, $TAmounts, $l_societe, $iFileLine, $fk_payment, $date_paye);
								break;
							case 'charge':
								$fk_bank = $this->doPaymentForCharge();
								break;
						}

					}
				}

			}

			unset($TLine['piece']);
		}

		unset($TLine['fk_payment'], $TLine['fk_soc'], $TLine['type']);

		if (isset($TLine['new']))
		{
			if(!empty($TLine['new'])) {
				foreach($TLine['new'] as $iFileLine) {
					$oper = 'PRE';
					$sql = 'SELECT c.code FROM  '. MAIN_DB_PREFIX . 'c_paiement c WHERE id='.intval($TLineBackup['fk_payment'][$iFileLine]).' LIMIT 1;';
					$res = $db->query($sql);
					if ($res){
						$obj =  $db->fetch_object($res);
						$oper = $obj->code;
					}

					$bankLineId = $this->create_bank_transaction($this->TFile[$iFileLine], $oper);
					if($bankLineId > 0) {
						$bankLine = new AccountLine($this->db);
						$bankLine->fetch($bankLineId);
						$this->reconcile_bank_transaction($bankLine, $this->TFile[$iFileLine]);
					}
				}
			}
			unset($TLine['new']);
		}

		foreach($TLine as $bankLineId => $iFileLine)
		{
			$this->reconcile_bank_transaction($this->TBank[$bankLineId], $this->TFile[$iFileLine]);
			if (getDolGlobalString('BANKIMPORT_HISTORY_IMPORT') && $bankLineId > 0)
			{
				$this->insertHistoryLine($PDOdb, $iFileLine, $bankLineId);
			}
		}
	}

	private function validateInvoices(&$TAmounts, $type) {

		global $db, $user;

		dol_include_once('/compta/facture/class/facture.class.php');
		dol_include_once('/fourn/class/fournisseur.facture.class.php');

		$TTypeElement = array('payment'=>'Facture', 'payment_supplier'=>'FactureFournisseur');

		if(!empty($TAmounts) && in_array($type, array_keys($TTypeElement))) {
			foreach($TAmounts as $facid=>$amount) {
				$f = new $TTypeElement[$type]($db);
				if($f->fetch($facid) > 0 && $f->statut == 0 && $amount > 0) $f->validate($user);
			}
		}

	}

	private function doPaymentForFacture(&$TLine, &$TAmounts, &$l_societe, $iFileLine, $fk_payment, $date_paye)
	{
		return $this->doPayment($TLine, $TAmounts, $l_societe, $iFileLine, $fk_payment, $date_paye, 'payment');
	}

	private function doPaymentForFactureFourn(&$TLine, &$TAmounts, &$l_societe, $iFileLine, $fk_payment, $date_paye)
	{
		return $this->doPayment($TLine, $TAmounts, $l_societe, $iFileLine, $fk_payment, $date_paye, 'payment_supplier');
	}

	private function doPaymentForCharge(&$TLine, &$TAmounts, &$l_societe, $iFileLine, $fk_payment, $date_paye)
	{
		return $this->doPayment($TLine, $TAmounts, $l_societe, $iFileLine, $fk_payment, $date_paye, 'payment_sc');
	}

	private function doPayment(&$TLine, &$TAmounts, &$l_societe, $iFileLine, $fk_payment, $date_paye, $type='payment')
	{
		global $conf, $langs,$user;

		$note = $langs->trans('TitleBankImport') .' - '.$this->numReleve;

		if ($type == 'payment') $paiement = new Paiement($this->db);
		elseif ($type == 'payment_supplier') $paiement = new PaiementFourn($this->db);
		elseif ($type == 'payment_supplier') $paiement = new PaymentSocialContribution($this->db);
		else exit($langs->trans('BankImport_FatalError_PaymentType_NotPossible', $type));

		if(getDolGlobalString('BANKIMPORT_ALLOW_DRAFT_INVOICE')) $this->validateInvoices($TAmounts, $type);

	    $paiement->datepaye     = $date_paye;
	    $paiement->amounts      = $TAmounts;   // Array with all payments dispatching
	    $paiement->paiementid   = $fk_payment;
	    if (floatval(DOL_VERSION) >= 13.0) $paiement->num_payment = '';
        else $paiement->num_paiement = '';
	    $paiement->note         = $note;

		$paiement_id = $paiement->create($user, 1);

		if ($paiement_id > 0)
		{
			$bankLineId = $paiement->addPaymentToBank($user, $type, !empty($this->TFile[$iFileLine]['label']) ? $this->TFile[$iFileLine]['label'] : $note, $this->account->id, $l_societe->name, '');
			$TLine[$bankLineId] = $iFileLine;

			$bankLine = new AccountLine($this->db);
			$bankLine->fetch($bankLineId);
			$this->TBank[$bankLineId] = $bankLine;

			// Remove the entered new entry
			foreach($TLine['new'] as $k=>$iFileLineNew)
			{
				if($iFileLineNew == $iFileLine) unset($TLine['new'][$k]);
			}

			// Only for customer invoices (supplier down payments do not exist)
			if(getDolGlobalInt('BANKIMPORT_AUTO_CREATE_DISCOUNT') && $type === 'payment') $this->createDiscount($TAmounts);

			return $bankLineId;
		}

		return 0; // Payment fail, can't return bankLineId
	}

	private function createDiscount(&$TAmounts) {

		global $db, $user;

		dol_include_once('/core/class/discount.class.php');

		foreach($TAmounts as $id_fac => $amount) {

			$object = new Facture($db);
			$object->fetch($id_fac);
			if($object->type != 3) continue; // Only down payments

			$object->fetch_thirdparty();

			// Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
			$discountcheck=new DiscountAbsolute($db);
			$result=$discountcheck->fetch(0,$object->id);

			$canconvert=0;
			if ($object->type == Facture::TYPE_DEPOSIT && $object->paye == 1 && empty($discountcheck->id)) $canconvert=1;	// we can convert deposit into discount if deposit is payed completely and not already converted (see real condition into condition used to show button converttoreduc)
			if ($object->type == Facture::TYPE_CREDIT_NOTE && $object->paye == 0 && empty($discountcheck->id)) $canconvert=1;	// we can convert credit note into discount if credit note is not payed back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
			if ($canconvert)
			{
				$db->begin();

				// Loop over each VAT rate
				$i = 0;
				foreach ($object->lines as $line) {
					$amount_ht [$line->tva_tx] += $line->total_ht;
					$amount_tva [$line->tva_tx] += $line->total_tva;
					$amount_ttc [$line->tva_tx] += $line->total_ttc;
					$i ++;
				}

				// Insert one discount by VAT rate category
				$discount = new DiscountAbsolute($db);
				if ($object->type == Facture::TYPE_CREDIT_NOTE)
					$discount->description = '(CREDIT_NOTE)';
				elseif ($object->type == Facture::TYPE_DEPOSIT)
					$discount->description = '(DEPOSIT)';
				else {
					setEventMessage($langs->trans('CantConvertToReducAnInvoiceOfThisType'),'errors');
				}
				$discount->tva_tx = abs($object->total_ttc);
				$discount->fk_soc = $object->socid;
				$discount->fk_facture_source = $object->id;

				$error = 0;
				foreach ($amount_ht as $tva_tx => $xxx) {
					$discount->amount_ht = abs($amount_ht [$tva_tx]);
					$discount->amount_tva = abs($amount_tva [$tva_tx]);
					$discount->amount_ttc = abs($amount_ttc [$tva_tx]);
					$discount->tva_tx = abs($tva_tx);

					$result = $discount->create($user);
					if ($result < 0)
					{
						$error++;
						break;
					}
				}

				if (empty($error))
				{
					// Mark invoice as paid
					$result = $object->set_paid($user);
					if ($result >= 0)
					{
						//$mesgs[]='OK'.$discount->id;
						$db->commit();
					}
					else
					{
						setEventMessage($object->error,'errors');
						$db->rollback();
					}
				}
				else
				{
					setEventMessage($discount->error,'errors');
					$db->rollback();
				}
			}

		}

	}

	private function insertHistoryLine(&$PDOdb, $iFileLine, $fk_bank)
	{
		if (!empty($this->hasHeader) && !empty($this->TOriginLine[$iFileLine]))
		{
			$header = $this->parseHeader($this->lineHeader);
			$line = $this->parseLine($this->TOriginLine[$iFileLine]);

			$historyLine = new TBankImportHistory;

			$historyLine->num_releve = $this->numReleve;
			$historyLine->fk_bank = $fk_bank;
			$historyLine->line_imported_title = $header;
			$historyLine->line_imported_value = $line;

			$historyLine->save($PDOdb);
		}
	}

	public function parseHeader($headerToParse)
	{
		global $conf;

		$header = explode(getDolGlobalString('BANKIMPORT_SEPARATOR'), $headerToParse);
		$header = array_map(array('BankImport', 'cleanString'), $header);

		return $header;
	}

	public static function cleanString($strToClean)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
		$strToClean = trim($strToClean);
		$strToClean = preg_replace('/\s{2,}/', '', $strToClean);
		$strToClean = dol_strtolower(dol_string_unaccent($strToClean));

		return ucfirst($strToClean);
	}

	public function parseLine($lineArrayToParse)
	{
		$line = array_map(array('BankImport', 'cleanStringForLine'), $lineArrayToParse);

		return $line;
	}

	public static function cleanStringForLine($strToClean)
	{
		$strToClean = trim($strToClean);
		$strToClean = preg_replace('/\s{2,}/', '', $strToClean);

		return $strToClean;
	}

	/**
	 * @param  array  $fileLine
	 * @param	string		$oper			'VIR','PRE','LIQ','VAD','CB','CHQ'...
	 * @return int
	 */
	private function create_bank_transaction($fileLine, $oper = 'PRE') {
		global $user;

		$bankLineId = $this->account->addline($fileLine['datev'], $oper, $fileLine['label'], $fileLine['amount'], '', '', $user);
		$this->nbCreated++;

		return $bankLineId;
	}

	private function reconcile_bank_transaction($bankLine, $fileLine) {
		global $user,$conf;

		// Set conciliation
		$bankLine->num_releve = $this->numReleve;
		$bankLine->update_conciliation($user, 0);

		// Update value date
        if (is_int($bankLine->datev)){
            $dateDiff = ($fileLine['datev'] - $bankLine->datev) / 24 / 3600;
        } else {
            $dateDiff = ($fileLine['datev'] - strtotime($bankLine->datev)) / 24 / 3600;
        }
		$bankLine->datev_change($bankLine->id, $dateDiff);

		$this->nbReconciled++;
	}

	/**
	 * Extract negative direction token from direction key
	 *
	 * @param array $matches Regex matches
	 * @return string Last separator (Effectively removing the extracted negative direction)
	 */
	private function extractNegDir(array $matches) {
		$this->neg_dir = $matches[1];
		return substr($matches[0], -1);
	}
}


class TBankImportHistory extends TObjetStd
{
	function __construct()
	{
		$this->set_table( MAIN_DB_PREFIX.'bankimport_history' );

		$this->add_champs('num_releve',array('type'=>'varchar','length'=>50,'index'=>true));
		$this->add_champs('fk_bank',array('type'=>'integer','index'=>true));
        $this->add_champs('line_imported_title,line_imported_value', array('type'=>'array'));

        $this->_init_vars();

	    $this->start();
	}

}
