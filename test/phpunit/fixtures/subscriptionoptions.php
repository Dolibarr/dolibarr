<?php
/* Copyright (C) 2026 Marcel
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/html.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/lib/subscription_options.lib.php';

/** Synthetic permissions, without an application session. */
class SubscriptionOptionsTestUser
{
	/** @var int External third party */
	public $socid = 0;
	/** @var string[] Denied permissions */
	public $denied = array();
	/** @param string ...$parts Permission path @return bool */
	public function hasRight(...$parts)
	{
		return !in_array(implode('.', $parts), $this->denied, true);
	}
}

/** Fail closed on any unexpected SQL; no connection exists. */
class SubscriptionOptionsTestDatabase
{
	/** @var int Nested transaction level */
	public $transaction_opened = 0;
	/** @var string[] Operations */
	public $calls = array();
	/** @var string[] Executed synthetic SQL */
	public $queries = array();
	/** @var string Failure injection */
	public $failure = '';
	/** @var mixed Latest synthetic subscription amount */
	public $latest = null;
	/** @var bool Simulated duplicate start date */
	public $duplicate = false;
	/** @var array<int,array{rowid:int,member:int,dateadh:?string,datef:?string}> Synthetic existing periods */
	public $periods = array();
	/** @var string[] Synthetic persisted objects */
	public $records = array();
	/** @var string[] Transaction snapshot */
	private $before = array();
	/** @param int $timestamp Timestamp @return string SQL date */
	public function idate($timestamp)
	{
		return dol_print_date($timestamp, '%Y-%m-%d %H:%M:%S');
	}
	/** @return string */
	public function prefix()
	{
		return 'test_';
	}
	/** @param int $limit Limit @return string */
	public function plimit($limit)
	{
		return ' LIMIT '.((int) $limit);
	}
	/** @return int */
	public function begin()
	{
		if (!$this->transaction_opened) {
			$this->before = $this->records;
		}
		$this->transaction_opened++;
		$this->calls[] = 'begin';
		return 1;
	}
	/** @return int */
	public function commit()
	{
		$this->calls[] = 'commit';
		if ($this->failure !== 'commit') {
			$this->transaction_opened--;
		}
		return $this->failure === 'commit' ? 0 : 1;
	}
	/** @return int */
	public function rollback()
	{
		$this->transaction_opened = max(0, $this->transaction_opened - 1);
		if (!$this->transaction_opened) {
			$this->records = $this->before;
		}
		$this->calls[] = 'rollback';
		return 1;
	}
	/** @param string $sql Query @return object|false */
	public function query($sql)
	{
		$this->queries[] = $sql;
		if (preg_match("/^SELECT rowid FROM test_subscription WHERE fk_adherent = ([0-9]+) AND dateadh >= '([^']+)' AND dateadh < '([^']+)'(?: LIMIT 1| FOR UPDATE)$/D", $sql, $parts)) {
			$this->calls[] = 'duplicate';
			if ($this->failure === 'period_query') {
				return false;
			}
			$match = $this->duplicate;
			foreach ($this->periods as $period) {
				$match = $match || ($period['member'] == $parts[1] && $period['dateadh'] !== null && $period['dateadh'] >= $parts[2] && $period['dateadh'] < $parts[3]);
			}
			return (object) array('row' => $match ? (object) array('rowid' => 50) : null);
		}
		if (preg_match("/^SELECT rowid, dateadh, datef FROM test_subscription WHERE fk_adherent = ([0-9]+) AND \(dateadh IS NULL OR dateadh < '([^']+)'\) AND \(datef IS NULL OR datef >= '([^']+)'\) ORDER BY rowid(?: FOR UPDATE)?$/D", $sql, $parts)) {
			$this->calls[] = 'overlap';
			$matches = array();
			foreach ($this->periods as $period) {
				if ($period['member'] == $parts[1] && ($period['dateadh'] === null || $period['dateadh'] < $parts[2]) && ($period['datef'] === null || $period['datef'] >= $parts[3])) {
					$matches[$period['rowid']] = (object) $period;
				}
			}
			ksort($matches);
			return (object) array('rows' => array_values($matches));
		}
		if (preg_match('/^SELECT rowid FROM test_adherent WHERE rowid = [0-9]+ FOR UPDATE$/D', $sql)) {
			$this->calls[] = 'lock';
			return $this->failure === 'lock' ? false : (object) array('row' => (object) array('rowid' => 1));
		}
		if ($sql === 'SELECT subscription FROM test_subscription WHERE fk_adherent = 1 ORDER BY datec DESC, rowid DESC LIMIT 1') {
			$this->calls[] = 'history';
			return (object) array('row' => $this->latest === null ? null : (object) array('subscription' => $this->latest));
		}
		throw new LogicException('Unexpected query in isolated test');
	}
	/** @param object $res Result @return object|null */
	public function fetch_object($res)
	{
		if (isset($res->rows)) {
			return array_shift($res->rows);
		}
		return $res->row;
	}
	/** @param object $res Result @return void */
	public function free($res)
	{
	}
}

/** Member type without a database connection. */
class SubscriptionOptionsTestType extends AdherentType
{
	public function __construct()
	{
	}
}

/** Exercises the real orchestration with synthetic core operations. */
class SubscriptionOptionsTestMember extends Adherent
{
	/** @var AdherentType Test type */
	public $testType;
	/** @var string Failure injection */
	public $failure = '';
	/** @var array<string,mixed> Captured arguments */
	public $observed = array();
	/**
	 * @param User $user User
	 * @return AdherentType
	 */
	protected function subscriptionTypeForBatch($user)
	{
		$this->db->calls[] = 'authorize';
		if ($this->failure === 'access') {
			throw new RuntimeException('NotEnoughPermissions');
		}
		return $this->testType;
	}
	/**
	 * @param int $force_thirdparty_id ID
	 * @return int
	 */
	public function fetch_thirdparty($force_thirdparty_id = 0)
	{
		$this->thirdparty = (object) array('id' => 42, 'entity' => $this->failure === 'thirdparty_entity' ? 2 : 1, 'element' => 'societe', 'name' => 'Synthetic third party');
		return $this->failure === 'thirdparty' ? -1 : 1;
	}
	/**
	 * @param mixed ...$args Core subscription arguments
	 * @return int
	 */
	public function subscription(...$args)
	{
		if ($this->failure === 'nested_exception') {
			$this->db->begin();
			throw new RuntimeException('Synthetic trigger exception');
		}
		$this->db->calls[] = 'subscription';
		$this->observed['subscription'] = $args;
		$this->db->records[] = 'subscription';
		return $this->failure === 'subscription' ? -1 : 10;
	}
	/**
	 * @param mixed ...$args Core complementary action arguments
	 * @return int
	 */
	public function subscriptionComplementaryActions(...$args)
	{
		$this->db->calls[] = 'complementary';
		$this->observed['complementary'] = $args;
		if (!empty($args[11]) && !$this->socid) {
			$this->db->records[] = 'thirdparty';
			$this->socid = 99;
			if (in_array($this->failure, array('thirdparty_create', 'invoice_after_thirdparty'), true)) {
				return -1;
			}
		}
		$this->invoice = $args[1] === 'invoiceonly' ? (object) array('id' => 20) : null;
		return $this->failure === 'invoice' ? -1 : 1;
	}
	/**
	 * @param AdherentType $type Type
	 * @param bool $requireInvoicePdf Attach invoice
	 * @return int
	 */
	public function sendSubscriptionConfirmation(AdherentType $type, $requireInvoicePdf = false)
	{
		if (end($this->db->calls) !== 'commit') {
			throw new LogicException('Mail must follow commit');
		}
		$this->db->calls[] = 'mail';
		$this->observed['attachment'] = $requireInvoicePdf;
		if ($this->failure === 'pdf') {
			$this->error = 'SubscriptionBulkPdfMissing';
			return -1;
		}
		if ($this->failure === 'mail_exception') {
			throw new RuntimeException('Simulated mail exception');
		}
		return $this->failure === 'mail' ? -1 : 1;
	}
}

/** Test member injecting a real HookManager without installing an extension. */
class SubscriptionOptionsHookTestMember extends SubscriptionOptionsTestMember
{
	/** @var HookManager Test hooks */
	public $mailhooks;
	/**
	 * @param AdherentType $type Type
	 * @param array $parameters Parameters
	 * @param Adherent $member Member
	 * @param string $action Action
	 * @param bool $requireInvoicePdf PDF
	 * @param HookManager|null $mailhooks Manager
	 * @return int
	 */
	public function sendSubscriptionConfirmationWithHooks(AdherentType $type, $parameters, &$member, &$action, $requireInvoicePdf = false, $mailhooks = null)
	{
		return parent::sendSubscriptionConfirmationWithHooks($type, $parameters, $member, $action, $requireInvoicePdf, $mailhooks ?: $this->mailhooks);
	}
}

/** @return array{0:SubscriptionOptionsTestDatabase,1:SubscriptionOptionsTestMember,2:SubscriptionOptionsTestUser} */
function subscriptionOptionsTestContext()
{
	$db = new SubscriptionOptionsTestDatabase();
	$member = new SubscriptionOptionsTestMember($db);
	$member->id = 1;
	$member->socid = 42;
	$member->entity = 1;
	$member->email = 'member@example.invalid';
	$member->firstname = 'Test';
	$member->lastname = 'Member';
	$member->testType = new SubscriptionOptionsTestType();
	$member->testType->amount = '24';
	$member->testType->label = 'Synthetic type';
	$member->testType->duration_value = 1;
	$member->testType->duration_unit = 'y';
	return array($db, $member, new SubscriptionOptionsTestUser());
}

/** @return void Set up synthetic configuration only. */
function subscriptionOptionsTestEnvironment()
{
	global $conf, $langs, $hookmanager, $mc;
	$mc = null;
	$hookmanager = new class {
		public function executeHooks(...$args)
		{
			return 0;
		}
	};
	$conf = (object) array('entity' => 1, 'global' => new stdClass(), 'modules' => array('member' => 1, 'invoice' => 1, 'societe' => 1), 'cache' => array());
	$conf->tzuserinputkey = 'gmt';
	$conf->tzuserdisplaykey = 'gmt';
	$conf->currency = 'EUR';
	$conf->modules_parts = array('hooks' => array());
	$conf->format_date_short = '%m/%d/%Y';
	$langs = new Translate(DOL_DOCUMENT_ROOT, $conf);
	$langs->setDefaultLang('en_US');
	$langs->tab_translate = array('Subscription' => 'Subscription');
}

/** @return array Synthetic context exercising the real common batch validation. */
function subscriptionOptionsStatusTestContext()
{
	require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php';
	if (!defined('MAIN_DB_PREFIX')) {
		define('MAIN_DB_PREFIX', 'test_');
	}
	$db = new class extends DoliDBMysqli {
		public $calls = array();
		public $records = array();
		private $synthetic;
		public function __construct() { $this->synthetic = new SubscriptionOptionsTestDatabase(); }
		public function prefix() { return 'test_'; }
		public function query($sql, $usesavepoint = 0, $type = 'auto', $result_mode = 0)
		{
			if (in_array($sql, array('BEGIN', 'COMMIT', 'ROLLBACK'), true)) {
				return true;
			}
			if ($sql === 'SELECT rowid FROM test_adherent_type WHERE rowid = 1 AND entity IN (1)') {
				return (object) array('row' => (object) array('rowid' => 1));
			}
			if (strpos($sql, 'SELECT d.rowid, d.libelle as label,') === 0 && strpos($sql, ' FROM '.MAIN_DB_PREFIX.'adherent_type as d WHERE d.rowid = 1') !== false) {
				return (object) array('row' => (object) array('rowid' => 1, 'label' => 'Synthetic type', 'morphy' => 'phy', 'status' => 1, 'duration' => '1y', 'subscription' => 1, 'caneditamount' => 0, 'minimumamount' => 0, 'amount' => 24, 'amountformuladescription' => '', 'mail_valid' => '', 'note_public' => '', 'vote' => 0));
			}
			return $this->synthetic->query(trim($sql));
		}
		public function fetch_object($result) { return $this->synthetic->fetch_object($result); }
		public function num_rows($result) { return isset($result->row) ? 1 : 0; }
		public function free($result = null) {}
	};
	$member = new class($db) extends SubscriptionOptionsTestMember {
		public function fetch($rowid, $ref = '', $socid = 0, $ref_ext = '', $fetch_optionals = true, $fetch_subscriptions = true) { return 1; }
		protected function subscriptionTypeForBatch($user)
		{
			$method = new ReflectionMethod(Adherent::class, 'subscriptionTypeForBatch');
			$method->setAccessible(true);
			return $method->invoke($this, $user);
		}
	};
	$member->id = 1;
	$member->typeid = 1;
	$member->entity = 1;
	$member->socid = 42;
	$member->firstname = 'Synthetic';
	$member->lastname = 'Member';
	$user = new class($db) extends User {
		public function hasRight($module, $permlevel1, $permlevel2 = '') { return true; }
	};
	return array($db, $member, $user);
}

/** @return array<string,callable():bool> Isolated regression scenarios */
function subscriptionOptionsScenarios()
{
	$scenarios = array();
	$date = dol_mktime(0, 0, 0, 1, 1, 2027);
	foreach (array(
		'MS-01' => array(null, 1, true),
		'MS-02' => array('0,-1,1', 1, true),
		'MS-03' => array('0,-1', 1, false),
		'MS-04' => array('1', 1, true),
		'MS-05' => array('1', 1, true),
		'MS-06-draft' => array('0,-1,1', 0, false),
		'MS-06-resiliated' => array('0,-1,1', -1, false),
	) as $name => $case) {
		$scenarios[$name] = static function () use ($name, $case, $date) {
			global $conf, $hookmanager, $extrafields;
			$savedhooks = $hookmanager;
			$savedextras = $extrafields ?? null;
			try {
				if ($case[0] !== null) {
					$conf->global->MEMBER_SUBSCRIPTION_ALLOWED_FOR_STATUS = $case[0];
				}
				// Isolate access hooks and empty extra fields, not the actual member/type validation.
				$hookmanager = new class {
					public function executeHooks(...$args) { return $args[0] === 'restrictedArea' ? 1 : 0; }
				};
				$extrafields = (object) array('attributes' => array('adherent_type' => array('loaded' => true)));
				list($db, $member, $user) = subscriptionOptionsStatusTestContext();
				$member->status = $case[1];
				$row = $member->previewSubscriptionWithOptions($user, $date, false, false);
				if (($row['status'] === 'ready') !== $case[2]) {
					return false;
				}
				if (!$case[2]) {
					return $row['errors'] === array('SubscriptionBulkIneligible') && !$db->records;
				}
				if ($name === 'MS-05') {
					$conf->global->MEMBER_SUBSCRIPTION_ALLOWED_FOR_STATUS = '0,-1';
				}
				$result = $member->createSubscriptionWithOptions($user, $date, false, false, $row['end'], false, $row['fingerprint']);
				return $name === 'MS-05'
					? ($result['error'] === 'SubscriptionBulkIneligible' && !$db->records && $db->transaction_opened === 0 && !isset($member->observed['subscription']))
					: ($result['subscription'] === 10 && $result['error'] === '');
			} finally {
				$hookmanager = $savedhooks;
				$extrafields = $savedextras;
			}
		};
	}

	foreach (array(array('invalid'), array(), null, 123, true, false, new stdClass(), 1.5, 'invalid', '', '123', str_repeat('A', 32), str_repeat('a', 31), str_repeat('a', 33)) as $index => $invalid) {
		$scenarios['result_notice_invalid_token_'.$index] = static function () use ($invalid) {
			$saved = $_SESSION ?? array();
			try {
				$counts = array('subscription' => 3, 'invoice' => 2, 'sent' => 1);
				$token = subscriptionOptionsStoreResultNotice($counts, 7, 1);
				$before = $_SESSION;
				return subscriptionOptionsTakeResultNotice($invalid, 7, 1) === null
					&& $_SESSION === $before
					&& subscriptionOptionsTakeResultNotice($token, 7, 1) === $counts
					&& !isset($_SESSION['subscription_options_result_notice'][$token])
					&& subscriptionOptionsTakeResultNotice($token, 7, 1) === null;
			} finally {
				$_SESSION = $saved;
			}
		};
	}
	foreach (array(array(8, 1), array(7, 2)) as $identity) {
		$scenarios['result_notice_wrong_identity_'.implode('_', $identity)] = static function () use ($identity) {
			$saved = $_SESSION ?? array();
			try {
				$token = subscriptionOptionsStoreResultNotice(array('subscription' => 1, 'invoice' => 0, 'sent' => 0), 7, 1);
				return subscriptionOptionsTakeResultNotice($token, $identity[0], $identity[1]) === null
					&& !isset($_SESSION['subscription_options_result_notice'][$token]);
			} finally {
				$_SESSION = $saved;
			}
		};
	}
	foreach (array(array(3, 3, 3), array(2, 1, 0), array(3, 3, 1), array(0, 0, 0)) as $values) {
		$scenarios['result_notice_counts_'.implode('_', $values)] = static function () use ($values) {
			$saved = $_SESSION ?? array();
			try {
				$_SESSION['dol_events'] = array('errors' => array('Existing error'), 'warnings' => array('Existing warning'), 'mesgs' => array('Other success'));
				$events = $_SESSION['dol_events'];
				$counts = array_combine(array('subscription', 'invoice', 'sent'), $values);
				$token = subscriptionOptionsStoreResultNotice($counts, 7, 1);
				$other = subscriptionOptionsStoreResultNotice(array('subscription' => 1, 'invoice' => 0, 'sent' => 0), 7, 1);
				return subscriptionOptionsTakeResultNotice('', 7, 1) === null
					&& subscriptionOptionsTakeResultNotice($token, 7, 1) === $counts
					&& subscriptionOptionsTakeResultNotice($token, 7, 1) === null
					&& isset($_SESSION['subscription_options_result_notice'][$other])
					&& $_SESSION['dol_events'] === $events;
			} finally {
				$_SESSION = $saved;
			}
		};
	}
	foreach (array('owner', 'entity', 'missing', 'negative', 'string', 'html') as $invalid) {
		$scenarios['result_notice_invalid_'.$invalid] = static function () use ($invalid) {
			$saved = $_SESSION ?? array();
			try {
				$counts = array('subscription' => 1, 'invoice' => 0, 'sent' => 0);
				$token = subscriptionOptionsStoreResultNotice($counts, 7, 1);
				$notice = &$_SESSION['subscription_options_result_notice'][$token];
				if ($invalid === 'owner' || $invalid === 'entity') {
					$notice[$invalid] = 99;
				} elseif ($invalid === 'missing') {
					unset($notice['counts']['sent']);
				} else {
					$notice['counts']['sent'] = $invalid === 'negative' ? -1 : ($invalid === 'string' ? '1' : '<script>alert(1)</script>');
				}
				return subscriptionOptionsTakeResultNotice($token, 7, 1) === null
					&& !isset($_SESSION['subscription_options_result_notice'][$token]);
			} finally {
				$_SESSION = $saved;
			}
		};
	}
	foreach (array(0, 1) as $disabled) {
		$scenarios['result_notice_render_disabled_'.$disabled] = static function () use ($disabled) {
			global $conf, $langs;
			$conf->use_javascript_ajax = 1;
			$conf->global->MAIN_DISABLE_JQUERY_JNOTIFY = $disabled;
			$langs->load('members');
			$html = subscriptionOptionsRenderResultNotice(array('subscription' => 3, 'invoice' => 2, 'sent' => 1));
			$message = dol_escape_htmltag($langs->transnoentities('SubscriptionBulkSummary', 3, 2, 1));
			return strpos($html, '<span>'.$message.'</span>') !== false
				&& strpos($html, 'class="ok clearboth" role="status"') !== false
				&& strpos($html, '<script nonce="'.getNonce().'">') !== false
				&& strpos($html, 'if ('.($disabled ? 'false' : 'true').' && window.jQuery') !== false
				&& strpos($html, 'sticky: true') !== false
				&& strpos($html, 'showClose: true') !== false
				&& strpos($html, 'tabindex: "0"') !== false
				&& strpos($html, 'setTimeout') === false;
		};
	}

	foreach (array(false, true) as $invoice) {
		foreach (array(false, true) as $mail) {
			$scenarios['options_invoice_'.(int) $invoice.'_mail_'.(int) $mail] = static function () use ($date, $invoice, $mail) {
				list($db, $member, $user) = subscriptionOptionsTestContext();
				$result = $member->createSubscriptionWithOptions($user, $date, $invoice, $mail);
				return $result === array('subscription' => 10, 'invoice' => $invoice ? 20 : 0, 'sent' => $mail ? 1 : 0, 'error' => '', 'warning' => '')
					&& $member->observed['subscription'][1] === 24.0 && $member->observed['subscription'][2] === 0
					&& $member->observed['complementary'][1] === ($invoice ? 'invoiceonly' : 'none') && $member->observed['complementary'][2] === 0
					&& (!$mail || $member->observed['attachment'] === $invoice);
			};
		}
	}
	foreach (array('lock', 'access', 'subscription', 'invoice', 'commit', 'thirdparty', 'thirdparty_entity', 'nested_exception') as $failure) {
		$scenarios['rollback_'.$failure] = static function () use ($date, $failure) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$db->failure = $member->failure = $failure;
			$result = $member->createSubscriptionWithOptions($user, $date, true, true);
			return $result['error'] !== '' && !$result['subscription'] && !$result['invoice'] && !$result['sent'] && end($db->calls) === 'rollback' && !$db->transaction_opened && !in_array('mail', $db->calls, true);
		};
	}
	foreach (array('pdf', 'mail', 'mail_exception', 'missing_email') as $failure) {
		$scenarios['mail_warning_preserves_records_'.$failure] = static function () use ($date, $failure) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$member->failure = $failure;
			if ($failure === 'missing_email') {
				$member->email = '';
			}
			$result = $member->createSubscriptionWithOptions($user, $date, true, true);
			return $result['warning'] !== '' && $result['subscription'] === 10 && $result['invoice'] === 20 && !$result['sent'] && !$result['error'] && !in_array('rollback', $db->calls, true);
		};
	}
	foreach (array('24', null, '', '0', '-1', 'invalid') as $configured) {
		$scenarios['amount_'.var_export($configured, true)] = static function () use ($configured) {
			list($db, $member) = subscriptionOptionsTestContext();
			$db->latest = '17.5';
			try {
				$amount = $member->getSubscriptionAmountForBatch($configured);
			} catch (RuntimeException $e) {
				return in_array($configured, array('-1', 'invalid'), true) && !$db->calls;
			}
			return in_array($configured, array('24', '0'), true) ? $amount === (float) $configured && !$db->calls : $amount === 17.5 && $db->calls === array('history');
		};
	}
	$scenarios['missing_history'] = static function () {
		list($db, $member) = subscriptionOptionsTestContext();
		try {
			$member->getSubscriptionAmountForBatch(null);
		} catch (RuntimeException $e) {
			return $e->getMessage() === 'SubscriptionBulkAmountMissing';
		}
		return false;
	};
	foreach (array(array(1, 'y', 12, 31), array(0, 'y', 12, 31), array(6, 'm', 6, 30), array(3, 'w', 1, 21)) as $case) {
		$scenarios['duration_'.$case[0].$case[1]] = static function () use ($date, $case) {
			$type = (object) array('duration_value' => $case[0], 'duration_unit' => $case[1]);
			return Adherent::subscriptionEndDateForBatch($date, $type) === dol_mktime(0, 0, 0, $case[2], $case[3], 2027);
		};
	}
	foreach (array('adherent.cotisation.creer', 'facture.creer', 'facture.invoice_advance.validate', 'facture.invoice_advance.send') as $right) {
		$scenarios['denied_'.$right] = static function () use ($date, $right) {
			global $conf;
			$conf->global->MAIN_USE_ADVANCED_PERMS = 1;
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$user->denied = array($right);
			$result = $member->createSubscriptionWithOptions($user, $date, true, true);
			return $result['error'] === 'NotEnoughPermissions' && !$db->calls;
		};
	}
	$scenarios['multiple_members_keep_individual_amounts_and_isolate_failures'] = static function () use ($date) {
		$outcomes = array();
		foreach (array('24', '12', '0', '8') as $amount) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$member->testType->amount = $amount;
			$result = $member->createSubscriptionWithOptions($user, $date, true, false);
			$outcomes[] = $result['subscription'] ? $member->observed['subscription'][1] : 0;
		}
		return $outcomes === array(24.0, 12.0, 0.0, 8.0);
	};
	$scenarios['real_mail_helper_refuses_missing_invoice_pdf'] = static function () {
		list($db) = subscriptionOptionsTestContext();
		$member = new Adherent($db);
		return $member->sendSubscriptionConfirmation(new SubscriptionOptionsTestType(), true) < 0 && $member->error === 'SubscriptionBulkPdfMissing' && !$db->calls;
	};
	foreach (array('MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH', 'MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR') as $setting) {
		$scenarios['calendar_'.$setting] = static function () use ($setting) {
			global $conf;
			$conf->global->$setting = 1;
			$type = (object) array('duration_value' => 3, 'duration_unit' => 'w');
			return Adherent::subscriptionEndDateForBatch(dol_mktime(0, 0, 0, 2, 10, 2027), $type) === dol_mktime(0, 0, 0, 3, 2, 2027);
		};
	}
	$scenarios['fallback_amount_used_by_core_subscription'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->testType->amount = null;
		$db->latest = '17.5';
		$result = $member->createSubscriptionWithOptions($user, $date, false, false);
		return $result['subscription'] === 10 && $member->observed['subscription'][1] === 17.5;
	};
	$scenarios['mail_disabled_keeps_records'] = static function () use ($date) {
		global $conf;
		$conf->global->MAIN_DISABLE_ALL_MAILS = 1;
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$result = $member->createSubscriptionWithOptions($user, $date, true, true);
		return $result['warning'] === 'SubscriptionBulkEmailDisabled' && $result['subscription'] === 10 && !$result['sent'] && !in_array('mail', $db->calls, true);
	};
	foreach (array('external', 'module_disabled', 'existing_transaction', 'invalid_date') as $case) {
		$scenarios['reject_'.$case] = static function () use ($date, $case) {
			global $conf;
			list($db, $member, $user) = subscriptionOptionsTestContext();
			if ($case === 'external') {
				$user->socid = 42;
			} elseif ($case === 'module_disabled') {
				unset($conf->modules['member']);
			} elseif ($case === 'existing_transaction') {
				$db->transaction_opened = 1;
			}
			$result = $member->createSubscriptionWithOptions($user, $case === 'invalid_date' ? 0 : $date, true, true);
			return $result['error'] !== '' && !$result['subscription'] && !in_array('subscription', $db->calls, true) && !in_array('mail', $db->calls, true);
		};
	}
	$scenarios['no_invoice_needs_no_invoice_rights'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$user->denied = array('facture.creer', 'facture.invoice_advance.send');
		$result = $member->createSubscriptionWithOptions($user, $date, false, true);
		return $result['subscription'] === 10 && $result['sent'] === 1 && !$result['invoice'];
	};
	$scenarios['real_authorization_rejects_member_in_other_entity'] = static function () use ($date) {
		list($db, $unused, $user) = subscriptionOptionsTestContext();
		$member = new class($db) extends Adherent {
			/** @param mixed ...$args Fetch arguments @return int */
			public function fetch(...$args)
			{
				$this->entity = 2;
				return 1;
			}
		};
		$member->id = 1;
		$result = $member->createSubscriptionWithOptions($user, $date, false, false);
		return $result['error'] === 'NotEnoughPermissions' && !$result['subscription'] && $db->calls === array('begin', 'lock', 'rollback');
	};
	$scenarios['preview_matches_creation_without_writes'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$row = $member->previewSubscriptionWithOptions($user, $date, true, true);
		$readonly = $db->calls === array('authorize', 'duplicate', 'overlap');
		$result = $member->createSubscriptionWithOptions($user, $date, true, true);
		return $readonly && $row['member'] !== '' && $row['type'] === 'Synthetic type' && $row['thirdparty'] === 'Synthetic third party'
			&& !$row['notices'] && $result['subscription'] === 10 && $row['amount'] === $member->observed['subscription'][1]
			&& $row['end'] === $member->observed['subscription'][8] && $row['description'] === $member->observed['complementary'][6];
	};
	$scenarios['preview_retains_details_on_amount_error'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->testType->amount = '-1';
		$row = $member->previewSubscriptionWithOptions($user, $date, true, false);
		return $row['amount'] === null && $row['end'] > $date && $row['thirdparty'] === 'Synthetic third party'
			&& $row['notices'] === array('SubscriptionBulkAmountMissing') && $db->calls === array('authorize', 'duplicate', 'overlap');
	};
	foreach (array(false, true) as $invoice) {
		$scenarios['preview_missing_thirdparty_invoice_'.(int) $invoice] = static function () use ($date, $invoice) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$member->failure = 'thirdparty';
			$row = $member->previewSubscriptionWithOptions($user, $date, $invoice, false);
			return $row['thirdparty'] === '' && $row['amount'] === 24.0 && $row['notices'] === ($invoice ? array('SubscriptionBulkThirdPartyMissing') : array());
		};
	}
	$scenarios['preview_denied_access_discloses_no_details'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->failure = 'access';
		$row = $member->previewSubscriptionWithOptions($user, $date, true, false);
		return $row['member'] === '' && $row['type'] === '' && $row['thirdparty'] === '' && $row['amount'] === null && $row['notices'] === array('NotEnoughPermissions');
	};
	foreach (array(array(0, 0), array(1, 0), array(0, 1), array(1, 1)) as $flags) {
		foreach (array(array(2, 'd', 2026, 10, 11), array(3, 'w', 2026, 10, 30), array(1, 'm', 2026, 11, 9), array(4, 'm', 2027, 2, 9), array(0, 'y', 2027, 10, 9), array(1, 'y', 2027, 10, 9), array(5, 'y', 2031, 10, 9)) as $case) {
			$scenarios['duration_flags_'.implode('', $flags).'_'.$case[0].$case[1]] = static function () use ($flags, $case) {
				global $conf;
				$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH = $flags[0];
				$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR = $flags[1];
				$type = (object) array('duration_value' => $case[0], 'duration_unit' => $case[1]);
				return Adherent::subscriptionEndDateForBatch(dol_mktime(0, 0, 0, 10, 10, 2026), $type) === dol_mktime(0, 0, 0, $case[3], $case[4], $case[2]);
			};
		}
	}
	$scenarios['duration_leap_year_and_year_boundary'] = static function () {
		$type = (object) array('duration_value' => 2, 'duration_unit' => 'd');
		return Adherent::subscriptionEndDateForBatch(dol_mktime(0, 0, 0, 2, 28, 2028), $type) === dol_mktime(0, 0, 0, 2, 29, 2028)
			&& Adherent::subscriptionEndDateForBatch(dol_mktime(0, 0, 0, 12, 31, 2027), $type) === dol_mktime(0, 0, 0, 1, 1, 2028);
	};
	$scenarios['individual_start_history_validation_today'] = static function () use ($date) {
		list($db, $member) = subscriptionOptionsTestContext();
		$member->datefin = dol_mktime(23, 59, 59, 12, 31, 2026);
		$a = $member->subscriptionStartDateForBatch($date);
		$member->datefin = dol_mktime(0, 0, 0, 3, 31, 2027);
		$b = $member->subscriptionStartDateForBatch($date);
		$member->datefin = null;
		$member->datevalid = dol_mktime(15, 30, 0, 4, 2, 2026);
		$c = $member->subscriptionStartDateForBatch($date);
		$member->datevalid = null;
		return $a === $date && $b === dol_mktime(0, 0, 0, 4, 1, 2027)
			&& $c === dol_mktime(0, 0, 0, 4, 2, 2026) && $member->subscriptionStartDateForBatch($date) === $date;
	};
	foreach (array('m', 'Y', '', '3m') as $correction) {
		$scenarios['start_global_priority_'.$correction] = static function () use ($correction) {
			global $conf;
			list($db, $member) = subscriptionOptionsTestContext();
			$member->datefin = dol_mktime(0, 0, 0, 10, 31, 2026);
			$conf->global->MEMBER_SUBSCRIPTION_START_AFTER = '+1Y';
			$conf->global->MEMBER_SUBSCRIPTION_START_FIRST_DAY_OF = $correction;
			$expected = array('m' => array(2028, 2, 1), 'Y' => array(2028, 1, 1), '' => array(2028, 2, 10), '3m' => array(2026, 7, 1));
			$parts = $expected[$correction];
			return $member->subscriptionStartDateForBatch(dol_mktime(0, 0, 0, 2, 10, 2027)) === dol_mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
		};
	}
	$scenarios['manual_period_preservation_and_reset'] = static function () use ($date) {
		$previous = array('start' => $date, 'end' => $date + 86400, 'manualend' => false);
		$auto = subscriptionOptionsPeriodInput($previous, $date + 86400, $previous['end']);
		$manual = subscriptionOptionsPeriodInput($previous, $date + 86400, $date + 20 * 86400);
		$previous['manualend'] = true;
		$kept = subscriptionOptionsPeriodInput($previous, $date + 86400, $previous['end']);
		$reset = subscriptionOptionsPeriodInput($previous, $date, $previous['end'], true);
		$common = subscriptionOptionsPeriodInput($previous, $date, $previous['end'], false, $date + 86400);
		return $auto['end'] === null && !$auto['manualend'] && $manual['end'] === $date + 20 * 86400 && $manual['manualend']
			&& $kept['end'] === $previous['end'] && $reset['end'] === null && !$reset['manualend'] && $common['start'] === $date + 86400 && $common['end'] === $previous['end'];
	};
	$scenarios['preview_automatic_start_and_explicit_end_reach_creation'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->datefin = $date - 86400;
		$row = $member->previewSubscriptionWithOptions($user, null, false, false, $date + 5 * 86400);
		$result = $member->createSubscriptionWithOptions($user, $row['start'], false, false, $row['end'], false, $row['fingerprint']);
		return !$row['errors'] && $row['start'] === $date && $result['subscription'] === 10 && $member->observed['subscription'][8] === $date + 5 * 86400;
	};
	$scenarios['amount_or_thirdparty_changes_require_review'] = static function () use ($date) {
		foreach (array('amount', 'socid') as $change) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$row = $member->previewSubscriptionWithOptions($user, $date, true, false);
			if ($change === 'amount') {
				$member->testType->amount = '25';
			} else {
				$member->socid = 84;
			}
			$result = $member->createSubscriptionWithOptions($user, $date, true, false, $row['end'], false, $row['fingerprint']);
			if ($result['error'] !== 'SubscriptionBulkDataChanged' || $db->records) {
				return false;
			}
		}
		return true;
	};
	foreach (array('duplicate', 'reversed', 'missing') as $case) {
		$scenarios['invalid_period_'.$case] = static function () use ($date, $case) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$db->duplicate = $case === 'duplicate';
			$end = $case === 'reversed' ? $date - 86400 : ($case === 'missing' ? 0 : $date + 86400);
			$row = $member->previewSubscriptionWithOptions($user, $date, false, false, $end);
			$result = $member->createSubscriptionWithOptions($user, $date, false, false, $end);
			return $row['status'] === 'error' && $result['error'] !== '' && !$db->records;
		};
	}
	$scenarios['duplicate_appearing_after_preview_blocks_creation'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$row = $member->previewSubscriptionWithOptions($user, $date, true, false);
		$db->duplicate = true;
		$result = $member->createSubscriptionWithOptions($user, $date, true, false, $row['end'], false, $row['fingerprint']);
		return $result['error'] === 'SubscriptionBulkDuplicateStart' && !$db->records;
	};
	$scenarios['preview_period_and_amount_warnings_are_independent'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$a = $member->previewSubscriptionWithOptions($user, $date, false, false);
		$b = $a;
		$b['type'] = 'A different label alone';
		if (subscriptionOptionsPreviewWarnings(array($a, $b))) {
			return false;
		}
		$b['amount'] = 50.0;
		if (subscriptionOptionsPreviewWarnings(array($a, $b)) !== array('SubscriptionBulkDifferentAmounts')) {
			return false;
		}
		$b['end'] += 86400;
		return subscriptionOptionsPreviewWarnings(array($a, $b)) === array('SubscriptionBulkDifferentPeriods', 'SubscriptionBulkDifferentAmounts');
	};
	foreach (array('existing', 'missing_off', 'missing_on', 'no_invoice', 'denied', 'inaccessible', 'thirdparty_create', 'invoice_after_thirdparty') as $case) {
		$scenarios['autothirdparty_'.$case] = static function () use ($date, $case) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$member->socid = in_array($case, array('existing', 'inaccessible'), true) ? 42 : 0;
			$invoice = $case !== 'no_invoice';
			$auto = $case !== 'missing_off';
			if ($case === 'denied') {
				$user->denied = array('societe.creer');
			}
			$member->failure = $case === 'inaccessible' ? 'thirdparty_entity' : $case;
			$row = $member->previewSubscriptionWithOptions($user, $date, $invoice, false, null, $auto);
			if ($db->records) {
				return false;
			}
			$result = $member->createSubscriptionWithOptions($user, $date, $invoice, false, $row['end'], $auto, $row['fingerprint']);
			if (in_array($case, array('missing_off', 'denied', 'inaccessible', 'thirdparty_create', 'invoice_after_thirdparty'), true)) {
				return $result['error'] !== '' && !$result['subscription'] && !$db->records;
			}
			return $result['subscription'] === 10 && in_array('thirdparty', $db->records, true) === ($case === 'missing_on')
				&& $member->observed['complementary'][11] === ($case === 'missing_on' ? 1 : 0)
				&& $row['status'] === ($case === 'missing_on' ? 'warning' : 'ready');
		};
	}
	$scenarios['mail_warning_does_not_block_ready_period'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->email = '';
		$row = $member->previewSubscriptionWithOptions($user, $date, false, true);
		return $row['status'] === 'warning' && !$row['errors'] && $row['warnings'] === array('SubscriptionBulkEmailMissing');
	};
	$scenarios['strict_calendar_input_rejects_impossible_dates'] = static function () {
		$savedPost = $_POST;
		$savedGet = $_GET;
		try {
			$_GET = array();
			$_POST = array('testyear' => '2027', 'testmonth' => '2', 'testday' => '29');
			if (subscriptionOptionsReadDate('test') !== 0) {
				return false;
			}
			$_POST['testyear'] = '2028';
			return subscriptionOptionsReadDate('test') === dol_mktime(0, 0, 0, 2, 29, 2028);
		} finally {
			$_POST = $savedPost;
			$_GET = $savedGet;
		}
	};
	$scenarios['render_editable_individual_fields_and_escape_names'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->firstname = '<script>alert(1)</script>';
		$row = $member->previewSubscriptionWithOptions($user, $date, false, false);
		$form = new class {
			/** @param int $timestamp Date @param string $prefix Prefix @param mixed ...$args Options @return string */
			public function selectDate($timestamp, $prefix, ...$args)
			{
				return '<input name="'.$prefix.'" value="'.$timestamp.'">';
			}
		};
		$html = subscriptionOptionsRenderPreview($form, array(1 => $row, 2 => $row));
		return substr_count($html, '<th>') === 7 && strpos($html, '<script>') === false
			&& strpos($html, 'name="substart1"') !== false && strpos($html, 'name="subend1"') !== false
			&& strpos($html, 'name="substart2"') !== false && strpos($html, 'name="subend2"') !== false;
	};
	$scenarios['mixed_members_create_only_authorized_thirdparties'] = static function () use ($date) {
		$outcomes = array();
		foreach (array(42, 0, 0) as $socid) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$member->socid = $socid;
			$row = $member->previewSubscriptionWithOptions($user, $date, true, false, null, true);
			$result = $member->createSubscriptionWithOptions($user, $date, true, false, $row['end'], true, $row['fingerprint']);
			$outcomes[] = array($result['subscription'], in_array('thirdparty', $db->records, true));
		}
		return $outcomes === array(array(10, false), array(10, true), array(10, true));
	};
	foreach (array('UTC', 'Europe/Berlin') as $serverzone) {
		foreach (array('gmt', 'tzserver', 'tzuserrel') as $inputzone) {
			foreach (array(
				array(2026, 10, 10, 1, 'm', '2026-11-09'),
				array(2026, 10, 10, 2, 'd', '2026-10-11'),
				array(2026, 10, 10, 3, 'w', '2026-10-30'),
				array(2026, 10, 10, 4, 'm', '2027-02-09'),
				array(2026, 10, 10, 5, 'y', '2031-10-09'),
				array(2026, 10, 25, 1, 'w', '2026-10-31'),
				array(2027, 3, 28, 1, 'w', '2027-04-03'),
				array(2027, 2, 1, 1, 'm', '2027-02-28'),
				array(2028, 2, 1, 1, 'm', '2028-02-29'),
				array(2027, 4, 1, 1, 'm', '2027-04-30'),
				array(2027, 1, 1, 1, 'm', '2027-01-31'),
				array(2028, 2, 29, 1, 'y', '2029-02-28'),
				array(2027, 1, 31, 1, 'm', '2027-03-02')
			) as $case) {
				$scenarios['date_pipeline_'.$serverzone.'_'.$inputzone.'_'.implode('_', $case)] = static function () use ($serverzone, $inputzone, $case) {
					global $conf;
					$savedzone = date_default_timezone_get();
					$savedPost = $_POST;
					$savedGet = $_GET;
					$savedSession = isset($_SESSION) ? $_SESSION : array();
					try {
						date_default_timezone_set($serverzone);
						$conf->tzuserinputkey = $inputzone;
						$_SESSION['dol_tz_string'] = 'America/New_York';
						$_GET = array();
						$_POST = array('startyear' => $case[0], 'startmonth' => $case[1], 'startday' => $case[2]);
						$start = subscriptionOptionsReadDate('start');
						list($db, $member, $user) = subscriptionOptionsTestContext();
						$member->testType->duration_value = $case[3];
						$member->testType->duration_unit = $case[4];
						$row = $member->previewSubscriptionWithOptions($user, $start, false, false);
						if ($row['errors'] || dol_print_date($row['end'], '%Y-%m-%d %H:%M:%S') !== $case[5].' 00:00:00') {
							return false;
						}
						$parts = explode('-', $case[5]);
						$_POST = array('endyear' => $parts[0], 'endmonth' => $parts[1], 'endday' => $parts[2]);
						$confirmed = subscriptionOptionsReadDate('end');
						$result = $member->createSubscriptionWithOptions($user, $start, false, false, $confirmed, false, $row['fingerprint']);
						// Exercise the real database date converters without constructing a connection.
						require_once DOL_DOCUMENT_ROOT.'/core/db/mysqli.class.php';
						$converter = (new ReflectionClass('DoliDBMysqli'))->newInstanceWithoutConstructor();
						$roundtrip = $converter->jdate($converter->idate($confirmed));
						return $result['subscription'] === 10 && $confirmed === $row['end'] && $member->observed['subscription'][8] === $confirmed
							&& dol_print_date($roundtrip, '%Y-%m-%d') === $case[5];
					} finally {
						date_default_timezone_set($savedzone);
						$_POST = $savedPost;
						$_GET = $savedGet;
						$_SESSION = $savedSession;
					}
				};
			}
		}
	}
	foreach (array(array(0, 0, null), array(1, 0, '2026-10-31'), array(0, 1, '2026-12-31'), array(1, 1, '2026-10-31')) as $flags) {
		$scenarios['explicit_calendar_'.implode('_', $flags)] = static function () use ($flags) {
			global $conf;
			$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH = $flags[0];
			$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR = $flags[1];
			$start = dol_mktime(0, 0, 0, 10, 10, 2026);
			$previous = array('start' => $start, 'end' => dol_mktime(0, 0, 0, 4, 1, 2027), 'manualend' => true);
			$input = subscriptionOptionsPeriodInput($previous, $start, $previous['end'], false, null, true);
			if ($flags[2] === null) {
				return $input['end'] === $previous['end'];
			}
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$row = $member->previewSubscriptionWithOptions($user, $input['start'], false, false, $input['end']);
			$reset = subscriptionOptionsPeriodInput($row + array('manualend' => true), $row['start'], $row['end'], true);
			$normal = $member->previewSubscriptionWithOptions($user, $reset['start'], false, false, $reset['end']);
			return !$db->records && $input['manualend'] && dol_print_date($row['end'], '%Y-%m-%d') === $flags[2]
				&& dol_print_date($normal['end'], '%Y-%m-%d') === '2027-10-09';
		};
	}
	$scenarios['calendar_invalid_start_and_duplicate'] = static function () use ($date) {
		global $conf;
		$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH = 1;
		$previous = array('start' => $date, 'end' => $date, 'manualend' => false);
		$input = subscriptionOptionsPeriodInput($previous, 0, 0, false, null, true);
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$db->duplicate = true;
		$row = $member->previewSubscriptionWithOptions($user, $date, false, false, Adherent::subscriptionCalendarEndForBatch($date));
		return $input['end'] === 0 && Adherent::subscriptionCalendarEndForBatch(0) === null && $row['errors'] === array('SubscriptionBulkDuplicateStart') && !$db->records;
	};
	foreach (array(array(0, 0, ''), array(1, 0, 'Month'), array(0, 1, 'Year'), array(1, 1, 'Both')) as $flags) {
		$scenarios['calendar_notice_'.implode('_', $flags)] = static function () use ($flags) {
			global $conf, $langs;
			$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH = $flags[0];
			$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR = $flags[1];
			$langs->load('members');
			$form = new class {
				/** @param mixed ...$args Selector arguments @return string */
				public function selectDate(...$args) { return '<input>'; }
			};
			$rows = array();
			foreach (array(array(2, 2028, 29, 'm'), array(10, 2026, 31, 'y')) as $case) {
				list($db, $member, $user) = subscriptionOptionsTestContext();
				$member->testType->duration_unit = $case[3];
				$start = dol_mktime(0, 0, 0, $case[0], 10, $case[1]);
				$rows[] = $member->previewSubscriptionWithOptions($user, $start, false, false);
				$manualend = dol_mktime(0, 0, 0, 4, 1, 2029);
				$previous = array('start' => $start, 'end' => $manualend, 'manualend' => true);
				$input = subscriptionOptionsPeriodInput($previous, $start, $manualend, false, null, true);
				$expected = $flags[0] ? dol_mktime(0, 0, 0, $case[0], $case[2], $case[1])
					: ($flags[1] ? dol_mktime(0, 0, 0, 12, 31, $case[1]) : $manualend);
				if ($input['start'] !== $start || $input['end'] !== $expected || $db->records) {
					return false;
				}
			}
			$html = subscriptionOptionsRenderPreview($form, $rows);
			$active = $flags[2] !== '';
			if ((strpos($html, 'name="applysubscriptioncalendar"') !== false) !== $active) {
				return false;
			}
			foreach (array('Month', 'Year', 'Both') as $rule) {
				$text = $langs->transnoentities('SubscriptionBulkCalendarEnd'.$rule.'Info');
				if (strpos($text, 'including manually entered end dates') === false
					|| strpos($text, 'Review and confirm the updated preview.') === false
					|| (strpos($html, dol_escape_htmltag($text)) !== false) !== ($rule === $flags[2])) {
					return false;
				}
			}
			return !$active || (strpos($html, '>Apply configured calendar end date</button>') !== false
				&& strpos($html, 'Active calendar rule: '.($flags[2] === 'Year' ? 'Year-end.' : 'Month-end.')) !== false
				&& ($flags[2] !== 'Both' || strpos($html, 'month-end takes precedence and year-end is not applied.') !== false));
		};
	}
	$scenarios['calendar_controls_and_compact_messages'] = static function () use ($date) {
		global $conf;
		$form = new class {
			/** @param mixed ...$args Selector arguments @return string */
			public function selectDate(...$args)
			{
				return '<input>';
			}
		};
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$row = $member->previewSubscriptionWithOptions($user, $date, false, false);
		$single = subscriptionOptionsRenderPreview($form, array(1 => $row));
		$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR = 1;
		$other = $row;
		$other['end'] += 86400;
		$other['amount'] += 1;
		$multiple = subscriptionOptionsRenderPreview($form, array(1 => $row, 2 => $other));
		return strpos($single, 'name="applysubscriptioncalendar"') === false && substr_count($single, 'class="subscription-options-notices"') === 1
			&& strpos($multiple, 'name="applysubscriptioncalendar"') !== false && substr_count($multiple, 'class="subscription-options-notices"') === 2
			&& substr_count($multiple, 'class="warning"') === 2 && substr_count($multiple, 'class="info"') === 2;
	};
	$scenarios['calendar_alignment_and_renewal_across_timezones'] = static function () {
		global $conf;
		$savedzone = date_default_timezone_get();
		$savedSession = isset($_SESSION) ? $_SESSION : array();
		try {
			foreach (array('UTC', 'Europe/Berlin') as $serverzone) {
				date_default_timezone_set($serverzone);
				foreach (array('gmt', 'tzserver', 'tzuserrel') as $inputzone) {
					$conf->tzuserinputkey = $inputzone;
					$_SESSION['dol_tz_string'] = 'America/New_York';
					$start = dol_mktime(0, 0, 0, 10, 10, 2026);
					$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH = 1;
					$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR = 1;
					if (dol_print_date(Adherent::subscriptionCalendarEndForBatch($start), '%Y-%m-%d %H:%M:%S') !== '2026-10-31 00:00:00') {
						return false;
					}
					$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH = 0;
					if (dol_print_date(Adherent::subscriptionCalendarEndForBatch($start), '%Y-%m-%d %H:%M:%S') !== '2026-12-31 00:00:00') {
						return false;
					}
					list($db, $member) = subscriptionOptionsTestContext();
					$member->datefin = dol_mktime(23, 59, 59, 3, 27, 2027);
					if (dol_print_date($member->subscriptionStartDateForBatch($start), '%Y-%m-%d %H:%M:%S') !== '2027-03-28 00:00:00') {
						return false;
					}
				}
			}
			return true;
		} finally {
			date_default_timezone_set($savedzone);
			$_SESSION = $savedSession;
		}
	};
	foreach (array(
		'Contribution, {Member type}, {Start date} to {End date}' => 'Contribution, Synthetic type, 01/01/2027 to 12/31/2027',
		'{Member type}' => 'Synthetic type',
		'Static fee' => 'Static fee',
		'{End date} / {Start date}: {Member type}' => '12/31/2027 / 01/01/2027: Synthetic type',
		'Extra: {Start date}' => 'Extra: 01/01/2027',
		'{Unknown} {member type}' => '{Unknown} {member type}',
		'<b>Fee</b> & "€"' => '<b>Fee</b> & "€"',
		'' => '',
	) as $template => $expected) {
		$scenarios['description_'.md5($template)] = static function () use ($date, $template, $expected) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$row = $member->previewSubscriptionWithOptions($user, $date, true, false, null, false, $template);
			$result = $member->createSubscriptionWithOptions($user, $row['start'], true, false, $row['end'], false, $row['fingerprint'], $template);
			return $row['description'] === $expected && $result['invoice'] === 20
				&& $member->observed['complementary'][6] === dol_escape_htmltag($expected)
				&& $member->observed['subscription'][4] === dol_escape_htmltag($expected)
				&& $member->observed['complementary'][7] === 24.0;
		};
	}
	foreach (array(str_repeat('x', 1025), "bad\ntext", "bad\0text", "bad\x7ftext", "bad\xFFtext", array('bad')) as $i => $template) {
		$scenarios['description_invalid_'.$i] = static function () use ($date, $template) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$result = $member->createSubscriptionWithOptions($user, $date, true, false, null, false, null, $template);
			return $result['error'] === 'SubscriptionBulkDescriptionInvalid' && !$db->records;
		};
	}
	foreach (array('template', 'type', 'start', 'end') as $change) {
		$scenarios['description_reconfirmation_'.$change] = static function () use ($date, $change) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$template = '{Member type} {Start date} {End date}';
			$row = $member->previewSubscriptionWithOptions($user, $date, true, false, null, false, $template);
			if ($change === 'template') {
				$template .= ' changed';
			} elseif ($change === 'type') {
				$member->testType->label = 'Other type';
			} else {
				$row[$change] = Adherent::subscriptionCalendarShift($row[$change], 1, 'd');
			}
			$result = $member->createSubscriptionWithOptions($user, $row['start'], true, false, $row['end'], false, $row['fingerprint'], $template);
			return $result['error'] === 'SubscriptionBulkDataChanged' && !$db->records;
		};
	}
	$scenarios['description_calendar_refresh_invoice_toggle'] = static function () use ($date) {
		global $conf;
		$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH = 1;
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$template = 'Fee {Member type}: {Start date} - {End date}';
		$end = Adherent::subscriptionCalendarEndForBatch($date);
		$previous = '';
		foreach (array(true, false, true, true) as $invoice) {
			$row = $member->previewSubscriptionWithOptions($user, $date, $invoice, false, $end, false, $template);
			if ($previous !== '' && $previous !== $row['description']) {
				return false;
			}
			$previous = $row['description'];
		}
		return $previous === 'Fee Synthetic type: 01/01/2027 - 01/31/2027' && !$db->records;
	};
	$scenarios['description_without_invoice_and_localized_dates'] = static function () use ($date) {
		global $langs;
		$langs->tab_translate['FormatDateShort'] = '%d.%m.%Y';
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$template = '{Member type} ab {Start date}';
		$member->testType->label = 'Ermäßigt';
		$row = $member->previewSubscriptionWithOptions($user, $date, false, false, null, false, $template);
		$result = $member->createSubscriptionWithOptions($user, $date, false, false, $row['end'], false, $row['fingerprint'], $template);
		return $result['subscription'] === 10 && !$result['invoice'] && $member->observed['subscription'][4] === dol_escape_htmltag('Ermäßigt ab 01.01.2027');
	};
	foreach (array('ready', 'warning', 'amount', 'duplicate', 'thirdparty', 'mixed') as $kind) {
		$scenarios['preview_counts_'.$kind] = static function () use ($date, $kind) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$ready = $member->previewSubscriptionWithOptions($user, $date, true, false);
			$member->email = '';
			$warning = $member->previewSubscriptionWithOptions($user, $date, true, true);
			if ($kind === 'amount') {
				$member->testType->amount = -1;
			} elseif ($kind === 'duplicate') {
				$db->duplicate = true;
			} else {
				$member->socid = 0;
			}
			$error = $member->previewSubscriptionWithOptions($user, $date, true, false);
			$rows = $kind === 'mixed' ? array($ready, $warning, $error) : array($kind === 'ready' ? $ready : ($kind === 'warning' ? $warning : $error));
			$counts = subscriptionOptionsPreviewCounts($rows);
			return $counts['selected'] === count($rows) && $counts['selected'] === $counts['ready'] + $counts['skipped']
				&& $counts['ready'] === ($kind === 'mixed' ? 2 : (in_array($kind, array('ready', 'warning'), true) ? 1 : 0));
		};
	}
	$scenarios['preview_counts_after_option_change'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->socid = 0;
		$before = subscriptionOptionsPreviewCounts(array($member->previewSubscriptionWithOptions($user, $date, true, false)));
		$after = subscriptionOptionsPreviewCounts(array($member->previewSubscriptionWithOptions($user, $date, false, false)));
		return $before['skipped'] === 1 && $after['ready'] === 1;
	};
	$scenarios['description_escaping_icon_and_seven_columns'] = static function () use ($date) {
		global $langs;
		$langs->tab_translate['SubscriptionBulkInvoiceLine'] = 'Invoice line: %s';
		$langs->tab_translate['Refresh'] = 'Refresh';
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$row = $member->previewSubscriptionWithOptions($user, $date, false, false, null, false, '<script>alert(1)</script> & {Member type}');
		$form = new class {
			/** @param mixed ...$args Selector arguments @return string */
			public function selectDate(...$args) { return '<input>'; }
		};
		$html = subscriptionOptionsRenderPreview($form, array(1 => $row));
		return substr_count($html, '<th>') === 7 && strpos($html, '<script>') === false
			&& strpos($html, '&lt;script&gt;') !== false && strpos($html, 'title="Refresh"') !== false
			&& strpos($html, 'aria-label=') !== false && strpos($html, '>&#8635;</button>') !== false;
	};
	$scenarios['description_limits_missing_dates_and_literal_replacement'] = static function () use ($date) {
		return subscriptionOptionsDescription(str_repeat('ä', 1024), '', null, null) === str_repeat('ä', 1024)
			&& subscriptionOptionsDescription('{Start date}', 'Type', 0, $date) === ''
			&& subscriptionOptionsDescription('{End date}', 'Type', $date, null) === ''
			&& subscriptionOptionsDescription('{Member type}', '{Start date}', $date, $date) === '{Start date}';
	};
	$scenarios['description_date_update_and_reset_preserve_other_row'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$template = '{Start date} - {End date}';
		$first = $member->previewSubscriptionWithOptions($user, $date, false, false, null, false, $template);
		$second = $first;
		$second['manualend'] = true;
		$second['end'] = Adherent::subscriptionCalendarShift($date, 2, 'm');
		$input = subscriptionOptionsPeriodInput($first, Adherent::subscriptionCalendarShift($date, 1, 'd'), $first['end'], true);
		$other = subscriptionOptionsPeriodInput($second, $second['start'], $second['end']);
		$updated = $member->previewSubscriptionWithOptions($user, $input['start'], false, false, $input['end'], false, $template);
		return $updated['description'] === '01/02/2027 - 01/01/2028' && $other['end'] === $second['end'] && $other['manualend'] && !$db->records;
	};
	$scenarios['different_templates_with_equal_description_require_confirmation'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$row = $member->previewSubscriptionWithOptions($user, $date, true, false, null, false, '{Member type}');
		$result = $member->createSubscriptionWithOptions($user, $date, true, false, $row['end'], false, $row['fingerprint'], 'Synthetic type');
		return $result['error'] === 'SubscriptionBulkDataChanged' && !$db->records;
	};
	foreach (array(
		array('2026-01-01', '2026-12-31', 'error'),
		array('2026-01-01', '2026-06-30', 'error'),
		array('2026-01-02', '2026-12-31', 'warning'),
		array('2026-07-01', '2027-06-30', 'warning'),
		array('2026-12-31', '2027-12-30', 'warning'),
		array('2027-01-01', '2027-12-31', 'ready'),
		array('2025-03-01', '2026-02-28', 'warning'),
		array('2025-03-01', '2026-01-01', 'warning'),
		array('2025-01-01', '2025-12-31', 'ready'),
	) as $index => $case) {
		$scenarios['period_decision_matrix_'.$index] = static function () use ($case) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$db->periods = array(array('rowid' => 50, 'member' => 1, 'dateadh' => '2026-01-01 17:45:00', 'datef' => '2026-12-31 00:00:00'));
			$start = dol_stringtotime($case[0]);
			$end = dol_stringtotime($case[1]);
			$row = $member->previewSubscriptionWithOptions($user, $start, true, false, $end);
			$result = $member->createSubscriptionWithOptions($user, $start, true, false, $end, false, $row['fingerprint'], null, $row['overlaps']);
			$counts = subscriptionOptionsPreviewCounts(array($row));
			return $row['status'] === $case[2]
				&& $counts['ready'] === ($case[2] === 'error' ? 0 : 1)
				&& $result['subscription'] === ($case[2] === 'error' ? 0 : 10)
				&& $result['invoice'] === ($case[2] === 'error' ? 0 : 20)
				&& ($case[2] !== 'error' || $result['error'] === 'SubscriptionBulkDuplicateStart');
		};
	}
	foreach (array('added', 'modified', 'removed', 'replaced', 'duplicate') as $change) {
		$scenarios['confirmed_overlaps_changed_'.$change] = static function () use ($change) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$db->periods = array(array('rowid' => 50, 'member' => 1, 'dateadh' => '2026-01-01 00:00:00', 'datef' => '2026-12-31 00:00:00'));
			$start = dol_mktime(0, 0, 0, 7, 1, 2026);
			$row = $member->previewSubscriptionWithOptions($user, $start, true, true);
			if ($change === 'removed') {
				$db->periods = array();
			} elseif ($change === 'modified') {
				$db->periods[0]['datef'] = '2026-12-30 00:00:00';
			} elseif ($change === 'replaced') {
				$db->periods[0]['rowid'] = 51;
			} else {
				$db->periods[] = array('rowid' => 51, 'member' => 1, 'dateadh' => $change === 'duplicate' ? '2026-07-01 22:00:00' : '2026-06-01 00:00:00', 'datef' => null);
			}
			$result = $member->createSubscriptionWithOptions($user, $start, true, true, $row['end'], false, $row['fingerprint'], null, $row['overlaps']);
			return $row['status'] === 'warning' && $result['error'] === ($change === 'duplicate' ? 'SubscriptionBulkDuplicateStart' : 'SubscriptionBulkOverlapChanged')
				&& !$db->records && !in_array('mail', $db->calls, true) && !$db->transaction_opened;
		};
	}
	$scenarios['new_overlap_after_ready_preview'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$row = $member->previewSubscriptionWithOptions($user, $date, true, false);
		$db->periods[] = array('rowid' => 50, 'member' => 1, 'dateadh' => '2026-12-01 00:00:00', 'datef' => null);
		$result = $member->createSubscriptionWithOptions($user, $date, true, false, $row['end'], false, $row['fingerprint'], null, $row['overlaps']);
		return $row['status'] === 'ready' && $result['error'] === 'SubscriptionBulkOverlapChanged' && !$db->records;
	};
	$scenarios['overlap_requires_preview_confirmation'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$db->periods[] = array('rowid' => 50, 'member' => 1, 'dateadh' => '2026-12-01 00:00:00', 'datef' => null);
		$result = $member->createSubscriptionWithOptions($user, $date, false, false);
		return $result['error'] === 'SubscriptionBulkOverlapChanged' && !$db->records;
	};
	$scenarios['multiple_overlaps_open_periods_and_other_members'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$db->periods = array(
			array('rowid' => 53, 'member' => 2, 'dateadh' => '2027-01-01 00:00:00', 'datef' => null),
			array('rowid' => 52, 'member' => 1, 'dateadh' => '2027-06-01 00:00:00', 'datef' => '2027-07-01 00:00:00'),
			array('rowid' => 50, 'member' => 1, 'dateadh' => '2026-01-01 00:00:00', 'datef' => null),
			array('rowid' => 51, 'member' => 1, 'dateadh' => null, 'datef' => '2027-01-01 00:00:00'),
		);
		$row = $member->previewSubscriptionWithOptions($user, $date, false, false);
		return $row['status'] === 'warning' && array_column($row['overlaps'], 'id') === array(50, 51, 52);
	};
	$scenarios['duplicate_start_ignores_existing_end_and_stops_overlap_query'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$db->periods = array(
			array('rowid' => 50, 'member' => 1, 'dateadh' => '2027-01-01 23:59:59', 'datef' => '2026-12-01 00:00:00'),
			array('rowid' => 51, 'member' => 1, 'dateadh' => '2027-01-01 00:00:00', 'datef' => null),
		);
		$row = $member->previewSubscriptionWithOptions($user, $date, false, false);
		return $row['errors'] === array('SubscriptionBulkDuplicateStart') && !in_array('overlap', $db->calls, true);
	};
	$scenarios['overlap_preserves_other_warnings_and_error_priority'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$db->periods[] = array('rowid' => 50, 'member' => 1, 'dateadh' => '2026-12-01 00:00:00', 'datef' => null);
		$member->email = '';
		$row = $member->previewSubscriptionWithOptions($user, $date, false, true);
		$member->testType->amount = -1;
		$error = $member->previewSubscriptionWithOptions($user, $date, false, true);
		return $row['status'] === 'warning' && $row['warnings'] === array('SubscriptionBulkOverlapWarning', 'SubscriptionBulkEmailMissing')
			&& $error['status'] === 'error' && $error['warnings'] === $row['warnings'] && $error['errors'] === array('SubscriptionBulkAmountMissing');
	};
	$scenarios['period_edit_calendar_and_reset_revalidate'] = static function () {
		global $conf;
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->testType->duration_unit = 'm';
		$db->periods[] = array('rowid' => 50, 'member' => 1, 'dateadh' => '2026-10-25 14:00:00', 'datef' => '2026-10-31 00:00:00');
		$start = dol_mktime(0, 0, 0, 10, 1, 2026);
		$shortend = dol_mktime(0, 0, 0, 10, 24, 2026);
		$row = $member->previewSubscriptionWithOptions($user, $start, false, false, $shortend);
		$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_MONTH = 1;
		$input = subscriptionOptionsPeriodInput($row, $start, $shortend, false, null, true);
		$calendar = $member->previewSubscriptionWithOptions($user, $start, false, false, $input['end']);
		$input = subscriptionOptionsPeriodInput($row, $start, $shortend, true);
		$reset = $member->previewSubscriptionWithOptions($user, $start, false, false, $input['end']);
		$duplicate = $member->previewSubscriptionWithOptions($user, dol_mktime(0, 0, 0, 10, 25, 2026), false, false);
		return $row['status'] === 'ready' && $calendar['status'] === 'warning' && $reset['status'] === 'warning' && $duplicate['status'] === 'error';
	};
	foreach (array('UTC', 'Europe/Berlin') as $zone) {
		foreach (array(array(3, 28, 2027), array(10, 25, 2026)) as $day) {
			$scenarios['duplicate_calendar_day_dst_'.$zone.'_'.implode('_', $day)] = static function () use ($zone, $day) {
				global $conf;
				$previouszone = date_default_timezone_get();
				try {
					date_default_timezone_set($zone);
					$conf->tzuserinputkey = 'tzserver';
					list($db, $member, $user) = subscriptionOptionsTestContext();
					$start = dol_mktime(12, 0, 0, $day[0], $day[1], $day[2]);
					$db->periods[] = array('rowid' => 50, 'member' => 1, 'dateadh' => $db->idate(dol_mktime(23, 59, 59, $day[0], $day[1], $day[2])), 'datef' => null);
					$row = $member->previewSubscriptionWithOptions($user, $start, false, false);
					return $row['errors'] === array('SubscriptionBulkDuplicateStart');
				} finally {
					date_default_timezone_set($previouszone);
				}
			};
		}
	}
	$scenarios['period_database_failure_and_member_isolation'] = static function () use ($date) {
		$outcomes = array();
		foreach (array('period_query', '', '') as $i => $failure) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$db->failure = $failure;
			$db->duplicate = $i === 1;
			$outcomes[] = $member->createSubscriptionWithOptions($user, $date, false, false)['subscription'];
		}
		return $outcomes === array(0, 0, 10);
	};
	$scenarios['period_reads_locked_only_during_creation'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$row = $member->previewSubscriptionWithOptions($user, $date, false, false);
		foreach ($db->queries as $sql) {
			if (strpos($sql, 'FOR UPDATE') !== false) {
				return false;
			}
		}
		$db->queries = array();
		$result = $member->createSubscriptionWithOptions($user, $date, false, false, $row['end'], false, $row['fingerprint'], null, $row['overlaps']);
		return $result['subscription'] === 10 && count($db->queries) === 3
			&& substr_count(implode(' ', $db->queries), 'FOR UPDATE') === 3;
	};
	$scenarios['same_start_on_other_member_is_ready'] = static function () use ($date) {
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$db->periods[] = array('rowid' => 50, 'member' => 2, 'dateadh' => '2027-01-01 00:00:00', 'datef' => null);
		return $member->previewSubscriptionWithOptions($user, $date, false, false)['status'] === 'ready';
	};
	foreach (array('en_US', 'de_DE') as $locale) {
		$scenarios['overlap_notice_rendered_'.$locale] = static function () use ($date, $locale) {
			global $langs;
			$langs->setDefaultLang($locale);
			$langs->load('members');
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$db->periods[] = array('rowid' => 50, 'member' => 1, 'dateadh' => '2026-12-01 00:00:00', 'datef' => null);
			$member->email = '';
			$row = $member->previewSubscriptionWithOptions($user, $date, false, true);
			$form = new class {
				/** @param mixed ...$args Selector arguments @return string */
				public function selectDate(...$args) { return '<input>'; }
			};
			$html = subscriptionOptionsRenderPreview($form, array(1 => $row));
			return strpos($html, dol_escape_htmltag($langs->trans('SubscriptionBulkOverlapWarning'))) !== false
				&& strpos($html, dol_escape_htmltag($langs->trans('SubscriptionBulkEmailMissing'))) !== false
				&& subscriptionOptionsPreviewCounts(array($row)) === array('selected' => 1, 'ready' => 1, 'skipped' => 0);
		};
	}
	foreach (array('UTC', 'Europe/Berlin') as $zone) {
		foreach (array(array(10, 10, 2026, '2026-10-11'), array(10, 31, 2026, '2026-11-01'), array(12, 31, 2026, '2027-01-01'), array(2, 28, 2028, '2028-02-29'), array(3, 28, 2027, '2027-03-29'), array(10, 25, 2026, '2026-10-26')) as $case) {
			$scenarios['one_day_'.$zone.'_'.$case[3]] = static function () use ($zone, $case) {
				global $conf;
				$previouszone = date_default_timezone_get();
				try {
					date_default_timezone_set($zone);
					$conf->tzuserinputkey = 'tzserver';
					list($db, $member, $user) = subscriptionOptionsTestContext();
					$member->testType->duration_unit = 'd';
					$start = dol_mktime(0, 0, 0, $case[0], $case[1], $case[2]);
					$row = $member->previewSubscriptionWithOptions($user, $start, false, false);
					$result = $member->createSubscriptionWithOptions($user, $start, false, false, $row['end'], false, $row['fingerprint']);
					return $row['status'] === 'ready' && dol_print_date($row['end'], '%Y-%m-%d') === $case[3] && $result['subscription'] === 10
						&& $member->observed['subscription'][8] > $member->observed['subscription'][0];
				} finally {
					date_default_timezone_set($previouszone);
				}
			};
		}
	}
	foreach (array(0, -86400, 43200) as $offset) {
		$scenarios['manual_nonlater_calendar_end_'.$offset] = static function () use ($date, $offset) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$member->testType->duration_unit = 'd';
			$valid = $member->previewSubscriptionWithOptions($user, $date, false, false);
			$row = $member->previewSubscriptionWithOptions($user, $date, false, false, $date + $offset);
			$result = $member->createSubscriptionWithOptions($user, $date, false, false, $date + $offset, false, $valid['fingerprint']);
			return $row['errors'] === array('SubscriptionBulkEndNotAfterStart') && $result['error'] === 'SubscriptionBulkEndNotAfterStart' && !$db->records
				&& subscriptionOptionsPreviewCounts(array($valid, $row)) === array('selected' => 2, 'ready' => 1, 'skipped' => 1);
		};
	}
	foreach (array(array(1, 'h'), array(24, 'h'), array(1, 'mn'), array(1, 's')) as $duration) {
		$scenarios['subday_duration_not_promoted_'.$duration[0].$duration[1]] = static function () use ($date, $duration) {
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$member->testType->duration_value = $duration[0];
			$member->testType->duration_unit = $duration[1];
			return $member->previewSubscriptionWithOptions($user, $date, false, false)['errors'] === array('SubscriptionBulkEndNotAfterStart');
		};
	}
	$scenarios['calendar_last_day_invalid_and_reset_one_day'] = static function () {
		global $conf;
		$conf->global->MEMBER_SUBSCRIPTION_SUGGEST_END_OF_YEAR = 1;
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->testType->duration_unit = 'd';
		$start = dol_mktime(0, 0, 0, 12, 31, 2026);
		$row = $member->previewSubscriptionWithOptions($user, $start, false, false);
		$input = subscriptionOptionsPeriodInput($row, $start, $row['end'], false, null, true);
		$invalid = $member->previewSubscriptionWithOptions($user, $start, false, false, $input['end']);
		$reset = subscriptionOptionsPeriodInput($invalid, $start, $invalid['end'], true);
		$valid = $member->previewSubscriptionWithOptions($user, $start, false, false, $reset['end']);
		return $invalid['status'] === 'error' && $valid['end'] === $row['end'] && $valid['status'] === 'ready';
	};
	foreach (array(0, '0', '0.00', null, '') as $configured) {
		foreach (array(false, true) as $invoice) {
			$scenarios['zero_amount_'.var_export($configured, true).'_invoice_'.(int) $invoice] = static function () use ($date, $configured, $invoice) {
				list($db, $member, $user) = subscriptionOptionsTestContext();
				$member->testType->amount = $configured;
				$db->latest = $configured === null || $configured === '' ? '0.00' : '24.00';
				$row = $member->previewSubscriptionWithOptions($user, $date, $invoice, true);
				$result = $member->createSubscriptionWithOptions($user, $date, $invoice, true, $row['end'], false, $row['fingerprint']);
				return $row['amount'] === 0.0 && $row['status'] === 'ready' && $result['subscription'] === 10 && $result['invoice'] === ($invoice ? 20 : 0) && $result['sent'] === 1
					&& $member->observed['subscription'][1] === 0.0 && $member->observed['complementary'][7] === 0.0
					&& (in_array('history', $db->calls, true) === ($configured === null || $configured === ''));
			};
		}
	}
	$scenarios['zero_amount_visible_and_different_amount_warning'] = static function () use ($date) {
		global $conf;
		$conf->global->MAIN_MAX_DECIMALS_TOT = '2:2';
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$member->testType->amount = 0;
		$row = $member->previewSubscriptionWithOptions($user, $date, false, false);
		$other = $row;
		$other['amount'] = 24.0;
		$form = new class {
			/** @param mixed ...$args Selector arguments @return string */
			public function selectDate(...$args) { return '<input>'; }
		};
		$html = subscriptionOptionsRenderPreview($form, array(1 => $row));
		return strpos($html, dol_escape_htmltag(price(0).' EUR')) !== false
			&& subscriptionOptionsPreviewWarnings(array($row, $other)) === array('SubscriptionBulkDifferentAmounts');
	};
	foreach (array('continue', 'modify', 'replace', 'error', 'throw', 'transport', 'pdf', 'nomail') as $mode) {
		foreach (array(false, true) as $invoice) {
			$scenarios['mail_hook_'.$mode.'_invoice_'.(int) $invoice] = static function () use ($date, $mode, $invoice) {
				global $hookmanager;
				require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';
				list($db, $base, $user) = subscriptionOptionsTestContext();
				$member = new SubscriptionOptionsHookTestMember($db);
				foreach (array('id', 'socid', 'entity', 'email', 'firstname', 'lastname', 'testType') as $key) {
					$member->$key = $base->$key;
				}
				$member->failure = $mode === 'transport' ? 'mail' : ($mode === 'pdf' ? 'pdf' : '');
				$observer = new class extends CommonHookActions {
					public $mode;
					public $calls = 0;
					public $parameters;
					/** @param array $parameters Parameters @param Adherent $object Member @param string $action Action @param HookManager $manager Manager @return int */
					public function sendMail($parameters, &$object, &$action, $manager) {
						$this->calls++;
						$this->parameters = $parameters;
						if ($action !== 'subscription' || $parameters['currentcontext'] !== 'subscription') { throw new LogicException('Wrong context'); }
						if ($this->mode === 'modify') { $object->email = 'changed@example.invalid'; $action = 'changed'; }
						if ($this->mode === 'throw') { throw new RuntimeException('Synthetic hook exception'); }
						return $this->mode === 'replace' ? 1 : ($this->mode === 'error' ? -1 : 0);
					}
				};
				$observer->mode = $mode;
				$manager = new HookManager($db);
				$manager->contextarray = array('subscription');
				$manager->hooks = array('subscription' => array('synthetic' => $observer));
				$manager->hooksSorted = array('subscription' => array('50:synthetic' => $observer));
				$member->mailhooks = $manager;
				$previous = $hookmanager;
				$result = $member->createSubscriptionWithOptions($user, $date, $invoice, $mode !== 'nomail');
				$expectedSent = in_array($mode, array('continue', 'modify'), true) ? 1 : 0;
				$expectedCalls = $mode === 'nomail' ? 0 : 1;
				return $result['subscription'] === 10 && $result['invoice'] === ($invoice ? 20 : 0) && !$result['error'] && $result['sent'] === $expectedSent
					&& $observer->calls === $expectedCalls && $hookmanager === $previous && !in_array('rollback', $db->calls, true)
					&& ($mode !== 'modify' || $member->email === 'changed@example.invalid')
					&& (!in_array($mode, array('replace', 'error', 'throw', 'nomail'), true) || !in_array('mail', $db->calls, true))
					&& ($mode === 'nomail' || ($observer->parameters['amount'] === 24.0 && $observer->parameters['datesubscription'] === $date && isset($observer->parameters['datesubend'])));
			};
		}
	}
	foreach (array(-1, 0, 1) as $hookreturn) {
		$scenarios['single_mail_shared_dispatcher_'.$hookreturn] = static function () use ($hookreturn) {
			global $hookmanager;
			require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';
			list($db, $member) = subscriptionOptionsTestContext();
			$db->calls[] = 'commit';
			$observer = new class extends CommonHookActions {
				public $calls = 0;
				public $hookreturn;
				/** @param array $parameters Parameters @param Adherent $object Member @param string $action Action @param HookManager $manager Manager @return int */
				public function sendMail($parameters, &$object, &$action, $manager) {
					$this->calls++;
					$object->email = 'single@example.invalid';
					$action = 'changed';
					return $this->hookreturn;
				}
			};
			$observer->hookreturn = $hookreturn;
			$manager = new HookManager($db);
			$manager->contextarray = array('subscription');
			$manager->hooks = array('subscription' => array('synthetic' => $observer));
			$manager->hooksSorted = array('subscription' => array('50:synthetic' => $observer));
			$originalmanager = $hookmanager;
			$action = 'subscription';
			$result = $member->sendSubscriptionConfirmationWithHooks($member->testType, array(), $member, $action, false, $manager);
			return $result === ($hookreturn === 0 ? 1 : ($hookreturn > 0 ? 0 : -1)) && $observer->calls === 1
				&& $action === 'changed' && $member->email === 'single@example.invalid' && $hookmanager === $originalmanager
				&& count(array_keys($db->calls, 'mail', true)) === ($hookreturn === 0 ? 1 : 0);
		};
	}
	$scenarios['core_rejects_equal_dates_without_database_write'] = static function () use ($date) {
		require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
		list($db, $member, $user) = subscriptionOptionsTestContext();
		$subscription = new Subscription($db);
		$subscription->dateh = $date;
		$subscription->datef = $date;
		return $subscription->create($user) < 0 && !$db->calls;
	};
	foreach (array(-1, 'bad', INF, NAN) as $i => $invalidamount) {
		$scenarios['invalid_amount_never_falls_back_'.$i] = static function () use ($invalidamount) {
			list($db, $member) = subscriptionOptionsTestContext();
			$db->latest = 24;
			try {
				$member->getSubscriptionAmountForBatch($invalidamount);
			} catch (RuntimeException $e) {
				return $e->getMessage() === 'SubscriptionBulkAmountMissing' && !$db->calls;
			}
			return false;
		};
	}

	$scenarios['bulk_translation_inventory'] = static function () {
		$content = file_get_contents(DOL_DOCUMENT_ROOT.'/langs/en_US/members.lang');
		preg_match_all('/^(SubscriptionBulk\w+)=/m', $content, $matches);
		$definitions = $matches[1];
		$references = array();
		foreach (array('/adherents/list.php', '/adherents/lib/subscription_options.lib.php', '/adherents/class/adherent.class.php') as $file) {
			foreach (token_get_all(file_get_contents(DOL_DOCUMENT_ROOT.$file)) as $token) {
				if (!is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
					continue;
				}
				$key = trim($token[1], "'\"");
				if (preg_match('/^SubscriptionOptions[A-Z]\w*$/', $key)) {
					return false;
				}
				if (preg_match('/^SubscriptionBulk[A-Z]\w*$/', $key)) {
					$references[] = $key;
				}
			}
		}
		return count($definitions) === 47 && count(array_unique($definitions)) === 47
			&& !array_diff($definitions, $references) && !array_diff($references, $definitions)
			&& !preg_match('/^SubscriptionOptions|^SubscriptionBulk(?:Mail|DescriptionDefault)=|^[\t ]*$/m', $content)
			&& strpos($content, "# Labels for subscription massaction\n") !== false
			&& strpos($content, "\nCreateSubscriptionWithOptions=") !== false;
	};
	$scenarios['bulk_default_and_mail_controller_bindings'] = static function () {
		$source = file_get_contents(DOL_DOCUMENT_ROOT.'/adherents/list.php');
		return strpos($source, '$subscriptiondescription = $langs->transnoentities(\'Subscription\').\', {Member type}, {Start date} - {End date}\';') !== false
			&& strpos($source, "'name' => 'subscriptionmail', 'label' => \$langs->trans('SendAcknowledgementByMail'), 'value' => \$sendmail") !== false;
	};
	foreach (array('en_US' => array('Contribution', 'Send confirmation email'), 'de_DE' => array('Mitgliedsbeitrag', 'Bestätigungsmail senden')) as $locale => $labels) {
		$scenarios['bulk_localized_defaults_'.$locale] = static function () use ($date, $locale, $labels) {
			global $conf, $langs;
			$langs = new Translate(DOL_DOCUMENT_ROOT, $conf);
			$langs->setDefaultLang($locale);
			$langs->loadLangs(array('main', 'members'));
			$template = $langs->transnoentities('Subscription').', {Member type}, {Start date} - {End date}';
			if ($template !== $labels[0].', {Member type}, {Start date} - {End date}' || $langs->transnoentities('SendAcknowledgementByMail') !== $labels[1]) {
				return false;
			}
			list($db, $member, $user) = subscriptionOptionsTestContext();
			$row = $member->previewSubscriptionWithOptions($user, $date, true, false, null, false, $template);
			$expected = $labels[0].', Synthetic type, '.dol_print_date($row['start'], 'day').' - '.dol_print_date($row['end'], 'day');
			$result = $member->createSubscriptionWithOptions($user, $row['start'], true, false, $row['end'], false, $row['fingerprint'], $template);
			return $row['description'] === $expected && $result['invoice'] === 20
				&& $member->observed['complementary'][6] === dol_escape_htmltag($expected);
		};
	}
	$scenarios['bulk_all_labels_resolve'] = static function () {
		global $conf, $langs;
		$langs = new Translate(DOL_DOCUMENT_ROOT, $conf);
		$langs->setDefaultLang('en_US');
		$langs->load('members');
		preg_match_all('/^(SubscriptionBulk\w+)=/m', file_get_contents(DOL_DOCUMENT_ROOT.'/langs/en_US/members.lang'), $matches);
		foreach ($matches[1] as $key) {
			if ($langs->transnoentities($key) === $key) {
				return false;
			}
		}
		return true;
	};

	foreach (array('valid', 'array', 'nested_array', 'empty', 'missing', 'uppercase', 'invalid_characters', 'short', 'long', 'unknown', 'expired', 'owner', 'entity', 'missing_rows', 'get_only', 'post_precedence', 'replay') as $case) {
		$scenarios['confirmation_token_'.$case] = static function () use ($case) {
			global $conf, $langs;
			$saved = array($_GET, $_POST, $_SESSION);
			try {
				$token = str_repeat('a', 32);
				$other = str_repeat('b', 32);
				$user = (object) array('id' => 42);
				$batch = array('owner' => 42, 'entity' => (int) $conf->entity, 'created' => dol_now(), 'rows' => array(), 'ids' => array(), 'descriptiontemplate' => 'Manual description', 'options' => array(false, false, false));
				$_GET = array();
				$_POST = array('subscriptionoptionstoken' => $token);
				$_SESSION = array('subscription_options' => array($token => $batch, $other => $batch));
				if ($case === 'array' || $case === 'nested_array') {
					parse_str($case === 'array' ? 'subscriptionoptionstoken[]=invalid' : 'subscriptionoptionstoken[a][]=invalid', $_POST);
				} elseif ($case === 'empty') {
					$_POST['subscriptionoptionstoken'] = '';
				} elseif ($case === 'missing' || $case === 'get_only') {
					$_POST = array();
				} elseif ($case === 'uppercase') {
					$_POST['subscriptionoptionstoken'] = str_repeat('A', 32);
				} elseif ($case === 'invalid_characters') {
					$_POST['subscriptionoptionstoken'] = str_repeat('g', 32);
				} elseif ($case === 'short' || $case === 'long') {
					$_POST['subscriptionoptionstoken'] = str_repeat('a', $case === 'short' ? 31 : 33);
				} elseif ($case === 'unknown') {
					$_POST['subscriptionoptionstoken'] = str_repeat('c', 32);
				} elseif ($case === 'expired') {
					$_SESSION['subscription_options'][$token]['created'] -= 3601;
				} elseif ($case === 'owner' || $case === 'entity') {
					$_SESSION['subscription_options'][$token][$case]++;
				} elseif ($case === 'missing_rows') {
					unset($_SESSION['subscription_options'][$token]['rows']);
				}
				if ($case === 'get_only' || $case === 'post_precedence') {
					$_GET['subscriptionoptionstoken'] = $other;
				}
				if (in_array($case, array('empty', 'uppercase', 'invalid_characters', 'short', 'long'), true)) {
					// A matching session entry must not make a malformed token acceptable.
					$_SESSION['subscription_options'][$_POST['subscriptionoptionstoken']] = $batch;
				}
				$before = $_SESSION;
				$confirm = 'yes';
				$subscriptiondescription = '';
				$subscriptioninputs = array();
				// Execute the actual controller block after the authentication/CSRF prerequisites.
				$source = file_get_contents(DOL_DOCUMENT_ROOT.'/adherents/list.php');
				$start = strpos($source, "\$subscriptionOptionsToken = GETPOST('subscriptionoptionstoken'");
				$end = strpos($source, '// Each form can be consumed once.', $start);
				if ($start === false || $end === false) {
					return false;
				}
				$code = substr($source, $start, $end - $start);
				// Replace only the terminating HTTP response; never evaluate request-supplied code.
				$code = str_replace('accessforbidden(', 'throw new RuntimeException(', $code);
				$accepted = false;
				try {
					eval($code);
					$accepted = true;
				} catch (RuntimeException $e) {
					if ($e->getMessage() !== $langs->trans('SubscriptionBulkExpired')) {
						return false;
					}
				}
				if (!in_array($case, array('valid', 'post_precedence', 'replay'), true)) {
					return !$accepted && $_SESSION === $before;
				}
				if (!$accepted || isset($_SESSION['subscription_options'][$token]) || $_SESSION['subscription_options'][$other] !== $batch || $subscriptiondescription !== 'Manual description') {
					return false;
				}
				if ($case === 'replay') {
					$before = $_SESSION;
					try {
						eval($code);
						return false;
					} catch (RuntimeException $e) {
						return $e->getMessage() === $langs->trans('SubscriptionBulkExpired') && $_SESSION === $before;
					}
				}
				return true;
			} finally {
				list($_GET, $_POST, $_SESSION) = $saved;
			}
		};
	}

	return $scenarios;
}
