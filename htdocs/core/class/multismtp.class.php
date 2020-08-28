<?php
/* Copyright (C) 2015-2016 Marcos García de La Fuente	<hola@marcosgdf.com>
 * Copyright (C) 2020      Alexandre Spangaro			<aspangaro@open-dsi.fr>
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
 *	Class to manage the configuration of an email account for each user
 */
class Multismtp
{
	/**
	 * User
	 * @var int
	 */
	private $fk_user;

	/**
	 * SMTP server
	 * @var string
	 */
	public $smtp_server;

	/**
	 * SMTP port
	 * @var string
	 */
	public $smtp_port;

	/**
	 * SMTP TLS
	 * @var bool
	 */
	public $smtp_tls;

	/**
	 * SMTP STARTTLS
	 * @var bool
	 */
	public $smtp_starttls;

	/**
	 * SMTP username
	 * @var string
	 */
	public $smtp_id;

	/**
	 * SMTP password
	 * @var string
	 */
	public $smtp_pw;

	/**
	 * IMAP server
	 * @var string
	 */
	public $imap_server;

	/**
	 * IMAP port
	 * @var string
	 */
	public $imap_port;

	/**
	 * IMAP TLS
	 * @var bool
	 */
	public $imap_tls;

	/**
	 * IMAP username
	 * @var string
	 */
	public $imap_id;

	/**
	 * IMAP password
	 * @var string
	 */
	public $imap_pw;

	/**
	 * IMAP folder
	 * @var string
	 */
	public $imap_folder;

	/**
	 * Database handler
	 * @var DoliDB
	 */
	private $db;

	/**
	 * Config class
	 * @var Conf
	 */
	private $conf;

	/**
	 * Multismtp constructor.
	 * Cannot use typehinting because of 3.4 compatibility
	 *
	 * @param DoliDB $db Database handler
	 * @param Conf $conf Config class
	 */
	public function __construct($db, Conf $conf)
	{
		$this->db = $db;
		$this->conf = $conf;
	}

	/**
	 * Retrieves SMTP configuration for the given user
	 *
	 * @param User $user User to retrieve data from
	 * @return bool true if everything was OK, false if no records found
	 * @throws Exception When DB error
	 */
	public function fetch(User $user)
	{
		$this->fk_user = $user->id;

		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'user_smtp WHERE fk_user = '.(int) $this->fk_user;

		$query = $this->db->query($sql);

		if (!$query) {
			throw new Exception($this->db->error());
		}

		if (!$this->db->num_rows($query)) {
			return false;
		}

		$resql = $this->db->fetch_object($query);

		$this->smtp_id = $resql->smtp_id;
		$this->smtp_pw = $resql->smtp_pw;
		$this->smtp_server = $resql->smtp_server;
		$this->smtp_port = $resql->smtp_port;
		$this->smtp_tls = (bool) $resql->smtp_tls;
		$this->smtp_starttls = (bool) $resql->smtp_starttls;
		$this->imap_id = $resql->imap_id;
		$this->imap_pw = $resql->imap_pw;
		$this->imap_server = $resql->imap_server;
		$this->imap_port = $resql->imap_port;
		$this->imap_tls = (bool) $resql->imap_tls;
		$this->imap_folder = $resql->imap_folder;

		return true;
	}

	/**
	 * Updates the data
	 *
	 * @return bool
	 * @throws Exception When a DB error happens
	 * @throws BadMethodCallException When fk_user is not set
	 */
	public function update()
	{
		if (!$this->fk_user) {
			throw new BadMethodCallException();
		}

		$smtp_server = null;
		$smtp_port = null;
		$smtp_tls = null;
		$smtp_starttls = null;
		$imap_server = null;
		$imap_port = null;
		$imap_tls = null;

		if ($this->conf->global->MULTISMTP_ALLOW_CHANGESERVER == 1) {
			$smtp_server = $this->smtp_server;

			if ($this->smtp_port !== null) {
				$smtp_port = (int) $this->smtp_port;
			}
			if ($this->smtp_tls !== null) {
				$smtp_tls = (int) $this->smtp_tls;
			}

			if ($this->smtp_starttls !== null) {
				$smtp_starttls = (int) $this->smtp_starttls;
			}
		}

		if (!$this->conf->global->MULTISMTP_IMAP_CONF_SERVER) {
			$imap_server = $this->imap_server;

			if ($this->imap_tls !== null) {
				$imap_tls = (int) $this->imap_tls;
			}
		}

		if (!$this->conf->global->MULTISMTP_IMAP_CONF_PORT && $this->imap_port !== null) {
			$imap_port = (int) $this->imap_port;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_smtp (
		smtp_id, smtp_pw, smtp_server, smtp_port, smtp_tls, smtp_starttls, imap_id, imap_pw, imap_server, imap_port,
		imap_tls, imap_folder, fk_user)
			VALUES (
			".($this->smtp_id ? "'".$this->db->escape($this->smtp_id)."'" : "null").",
			".($this->smtp_pw ? "'".$this->db->escape($this->smtp_pw)."'" : "null").",
			".($smtp_server ? "'".$this->db->escape($smtp_server)."'" : "null").",
			".($smtp_port ?: "null").",
			".($smtp_tls ?: "null").",
			".($smtp_starttls ?: "null").",
			".($this->imap_id ? "'".$this->db->escape($this->imap_id)."'" : "null").",
			".($this->imap_pw ? "'".$this->db->escape($this->imap_pw)."'" : "null").",
			".($imap_server ? "'".$this->db->escape($imap_server)."'" : "null").",
			".($imap_port ?: "null").",
			".($imap_tls ?: "null").",
			".($this->imap_folder ? "'".$this->db->escape($this->imap_folder)."'" : "null").",
			".(int) $this->fk_user.")";

		if (!$this->db->query($sql)) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."user_smtp SET
		smtp_id = ".($this->smtp_id ? "'".$this->db->escape($this->smtp_id)."'" : "null").",
		smtp_pw = ".($this->smtp_pw ? "'".$this->db->escape($this->smtp_pw)."'" : "null").",
		smtp_server = ".($smtp_server ? "'".$this->db->escape($smtp_server)."'" : "null").",
		smtp_port = ".($smtp_port ?: "null").",
		smtp_tls = ".($smtp_tls ?: "null").",
		smtp_starttls = ".($smtp_starttls ?: "null").",
		imap_id = ".($this->imap_id ? "'".$this->db->escape($this->imap_id)."'" : "null").",
		imap_pw = ".($this->imap_pw ? "'".$this->db->escape($this->imap_pw)."'" : "null").",
		imap_server = ".($imap_server ? "'".$this->db->escape($imap_server)."'" : "null").",
		imap_port = ".($imap_port ?: "null").",
		imap_tls = ".($imap_tls ?: "null").",
		imap_folder = ".($this->imap_folder ? "'".$this->db->escape($this->imap_folder)."'" : "null")."
		WHERE fk_user = ".(int) $this->fk_user;

			if (!$this->db->query($sql)) {
				throw new Exception($this->db->error());
			}
		}

		return true;
	}

	/**
	 * Checks if IMAP configuration is set
	 * @return bool
	 */
	public function checkImapConfig()
	{
		$credentials = $this->getImapCredentials();

		return $credentials['id'] && $credentials['port'] && $credentials['server'] && $credentials['id'];
	}

	/**
	 * Checks IMAP credentials
	 * @return bool
	 */
	public function checkImap()
	{
		$res = $this->openImapHandler();

		if ($res) {
			imap_close($res);
		}

		return (bool) $res;
	}

	/**
	 * Checks if SMTP configuration is set
	 * @return bool
	 */
	public function checkSmtpConfig()
	{
		$credentials = $this->getSmtpCredentials();

		return $credentials['server'] && $credentials['port'] && $credentials['id'] && $credentials['pw'];
	}

	/**
	 * Checks SMTP credentials
	 *
	 * @param User $user Current user checking the server
	 * @return true|string In case of failure, a description of the error is returned
	 */
	public function checkSmtp(User $user)
	{
		if ($this->conf->global->MULTISMTP_ALLOW_CHANGESERVER != 1) {
			return true;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

		$credentials = $this->getSmtpCredentials();

		$server = $credentials['server'];

		// If we use SSL/TLS
		// Since 4.0, an ssl:// is automatically added in CMailFile::check_server_port
		// therefore we have to call replaceConfiguration because of it
		if (versioncompare(versiondolibarrarray(), array(4,0,-5)) <= 0) {
			if (($credentials['tls'] || $credentials['starttls']) && function_exists('openssl_open')) {
				$server = 'ssl://'.$server;
			}
		} else {
			if (!replaceConfiguration($this->db, $user, $this->conf)) {
				return false;
			}
		}

		$mail = new CMailFile('', '', '', '');

		if (!$mail->check_server_port($server, $credentials['port'])) {
			return $mail->error;
		}

		return true;
	}

	/**
	 * Returns IMAP folders
	 * @return array|false
	 */
	public function getImapFolders()
	{
		$res = $this->openImapHandler();

		if (!$res) {
			return false;
		}

		if ($list = imap_list($res, $this->getImapString(), '*')) {
			$return = array();

			foreach ($list as $mailbox) {
				$return[$mailbox] = str_replace($this->getImapString(), '', $mailbox);
			}

			return $return;
		}

		return array();
	}

	/**
	 * Returns the name of the IMAP folder removing IMAP string
	 * @return string
	 */
	public function getFriendlyImapFolder()
	{
		return str_replace($this->getImapString(), '', $this->imap_folder);
	}

	/**
	 * Saves the email to the configured mailbox
	 * @param CMailFile $mailfile Emailing class
	 * @return bool
	 */
	public function saveMail(CMailFile $mailfile)
	{
		$imap = $this->openImapHandler();

		if (!$imap) {
			return false;
		}

		if ($this->conf->global->MAIN_MAIL_SENDMODE == 'smtps') {
			$header = $mailfile->smtps->getHeader();
			$body = $mailfile->smtps->getBodyContent();

			$string = $header.$body;
		}  else {
			$header = $mailfile->headers;
			$body = $mailfile->message;

			//Adding missing headers
			$header .= $mailfile->eol.'To: '.$mailfile->getValidAddress($mailfile->addr_to, 0, 1);
			$header .= $mailfile->eol.'Subject: '.$mailfile->encodetorfc2822($mailfile->subject);

			$string = $header.$mailfile->eol.$mailfile->eol.$body;
		}

		//http://runnable.com/UnZFxM5V3x9TAABX/send-an-email-using-imap-and-save-it-to-the-sent-folder-for-php
		if (!imap_append(
			$imap,
			$this->imap_folder,
			$string
		)) {
			return false;
		}

		imap_close($imap);

		return true;
	}

	/**
	 * Removes all IMAP credentials from the database
	 * @return bool
	 */
	public static function removeAllImapCredentials()
	{
		global $db;

		$sql = "UPDATE ".MAIN_DB_PREFIX."user_smtp SET imap_server = NULL,
imap_port  = NULL,
imap_tls  = NULL,
imap_id = NULL,
imap_pw = NULL,
imap_folder = NULL";

		if (!$db->query($sql)) {
			return false;
		}

		return true;
	}

	/**
	 * Removes all IMAP servers, ports and tls info
	 * @return bool
	 */
	public static function removeAllImapServerInfo()
	{
		global $db;

		$sql = "UPDATE ".MAIN_DB_PREFIX."user_smtp SET imap_server = NULL,
imap_port  = NULL,
imap_tls  = NULL";

		if (!$db->query($sql)) {
			return false;
		}

		return true;
	}

	/**
	 * Removes all IMAP credentials from the database
	 * @return bool
	 */
	public static function removeAllSmtpCredentials()
	{
		global $db;

		$sql = "UPDATE ".MAIN_DB_PREFIX."user_smtp SET smtp_server = NULL,
smtp_port  = NULL,
smtp_tls  = NULL,
smtp_starttls  = NULL,
smtp_id = NULL,
smtp_pw = NULL";

		if (!$db->query($sql)) {
			return false;
		}

		return true;
	}

	/**
	 * Returns an array with the SMTP credentials
	 * @return array
	 */
	public function getSmtpCredentials()
	{
		$array = array(
			'server' => $this->conf->global->MAIN_MAIL_SMTP_SERVER,
			'port' => $this->conf->global->MAIN_MAIL_SMTP_PORT,
			'tls' => $this->conf->global->MAIN_MAIL_EMAIL_TLS,
			'starttls' => $this->conf->global->MAIN_MAIL_EMAIL_STARTTLS,
			'id' => $this->smtp_id,
			'pw' => $this->smtp_pw
		);

		if ($this->conf->global->MULTISMTP_ALLOW_CHANGESERVER == 1) {
			if ($this->smtp_port !== null) {
				$array['port'] = $this->smtp_port;
			}

			if ($this->smtp_tls !== null) {
				$array['tls'] = (int) $this->smtp_tls;
			}

			if ($this->smtp_starttls !== null) {
				$array['starttls'] = (int) $this->smtp_starttls;
			}

			if ($this->smtp_server) {
				$array['server'] = $this->smtp_server;
			}
		}

		return $array;
	}

	/**
	 * Returns an array with IMAP credentials
	 * @return array
	 */
	public function getImapCredentials()
	{
		$array = array(
			'server' => $this->imap_server,
			'port' => $this->imap_port,
			'tls' => $this->imap_tls,
			'id' => $this->imap_id,
			'pw' => $this->imap_pw,
			'folder' => $this->imap_folder
		);

		if ($this->conf->global->MULTISMTP_IMAP_CONF_SERVER) {
			$array['server'] = $this->conf->global->MULTISMTP_IMAP_CONF_SERVER;
			$array['tls'] = $this->conf->global->MULTISMTP_IMAP_CONF_TLS;
		}

		if ($this->conf->global->MULTISMTP_IMAP_CONF_PORT) {
			$array['port'] = $this->conf->global->MULTISMTP_IMAP_CONF_PORT;
		}

		return $array;
	}

	/**
	 * Returns IMAP string used by imap_open()
	 * @return string
	 */
	private function getImapString()
	{
		$credentials = $this->getImapCredentials();

		$string = $credentials['server'].':'.$credentials['port'];

		if ($credentials['tls']) {
			$string .= '/ssl';
		}

		if ($this->conf->global->MULTISMTP_IMAP_NOVALIDATECERT) {
			$string .= '/novalidate-cert';
		}

		return '{'.$string.'}';
	}

	/**
	 * Opens an IMAP connection
	 * @return resource
	 */
	private function openImapHandler()
	{
		return @imap_open($this->getImapString(), $this->imap_id, $this->imap_pw, 0, 1);
	}
}
