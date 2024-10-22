<?php
/* Copyright (C) 2024		MDW	<mdeweerd@users.noreply.github.com>
 */

namespace {
	/**
	 *		Use an OVH account to send SMS with Dolibarr
	 */
	class OvhSms extends \CommonObject
	{
		public $db;
		//!< To store db handler
		public $error;
		//!< To return error code (or message)
		public $errors = array();
		//!< To return several error codes (or messages)
		public $element = 'ovhsms';
		//!< Id that identify managed object
		public $id;
		public $account;
		public $socid;
		public $contact_id;
		public $member_id;
		public $fk_project;
		public $nostop;
		public $expe;
		public $dest;
		public $message;
		public $validity;
		public $class;
		public $deferred;
		public $priority;
		public $soap;
		// Old API
		public $conn;
		// New API
		public $endpoint;
		/**
		 *	Constructor
		 *
		 * 	@param	DoliDB	$db		Database handler
		 */
		public function __construct($db)
		{
		}
		/**
		 * Logout
		 *
		 * @return	void
		 */
		public function logout()
		{
		}
		// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps,PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
		/**
		 * Send SMS
		 *
		 * @return	int     <=0 if error, >0 if OK
		 */
		public function SmsSend()
		{
		}
		/**
		 * Show HTML select box to select account
		 *
		 * @return	void
		 */
		public function printListAccount()
		{
		}
		/**
		 * Return list of SMSAccounts
		 *
		 * @return	array
		 */
		public function getSmsListAccount()
		{
		}
		// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps,PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
		/**
		 * Return Credit
		 *
		 * @return	array
		 */
		public function CreditLeft()
		{
		}
		// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps,PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
		/**
		 * Return History
		 *
		 * @return	array
		 */
		public function SmsHistory()
		{
		}
		// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps,PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
		/**
		 * Return list of possible SMS senders
		 *
		 * @return array|int	                    <0 if KO, array with list of available senders if OK
		 */
		public function SmsSenderList()
		{
		}
		/**
		 * Call soapDebug method to output traces
		 *
		 * @return	void
		 */
		public function soapDebug()
		{
		}
	}
}
