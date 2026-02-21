<?php
/* Copyright (C) 2026 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

class ActionsMassSubscriptionBatch extends CommonHookActions
{
	public $db;
	public $error = '';
	public $errors = array();
	public $results = array();
	public $resprints;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;

		$context = isset($parameters['currentcontext']) ? $parameters['currentcontext'] : (isset($parameters['context']) ? $parameters['context'] : '');
		if (!in_array('memberlist', explode(':', (string) $context))) {
			return 0;
		}
		if (!$user->hasRight('adherent', 'creer') || !$user->hasRight('masssubscriptionbatch', 'run')) {
			return 0;
		}

		$langs->load('masssubscriptionbatch@masssubscriptionbatch');
		$this->resprints = '<option value="masssubinvoiceemail">'.img_picto('', 'payment', 'class="pictofixedwidth"').$langs->trans('MassSubInvoiceEmailAction').'</option>';
		return 0;
	}

	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user;

		if (($parameters['massaction'] ?? '') !== 'masssubinvoiceemail') {
			return 0;
		}

		if (!$user->hasRight('adherent', 'creer') || !$user->hasRight('masssubscriptionbatch', 'run')) {
			setEventMessages($langs->trans('NotEnoughPermissions'), null, 'errors');
			return 0;
		}

		$langs->loadLangs(array('members', 'masssubscriptionbatch@masssubscriptionbatch'));
		$toselect = is_array($parameters['toselect']) ? $parameters['toselect'] : array();
		$sendmailenabled = (int) getDolGlobalInt('MASSSUBSCRIPTIONBATCH_DEFAULT_SENDMAIL');

		$member = new Adherent($this->db);
		$membertype = new AdherentType($this->db);

		$nbcreated = 0;
		$nbsentemail = 0;
		$nberrors = 0;
		$datesubscription = dol_now();

		foreach ($toselect as $id) {
			if ($member->fetch((int) $id) <= 0) {
				$nberrors++;
				continue;
			}
			if ($membertype->fetch($member->typeid) <= 0) {
				$nberrors++;
				continue;
			}

			$amount = is_numeric($membertype->amount) ? (float) $membertype->amount : 0.0;
			if (!empty($member->last_subscription_amount)) {
				$amount = max($amount, (float) $member->last_subscription_amount);
			}

			$durationvalue = !empty($membertype->duration_value) ? $membertype->duration_value : 1;
			$durationunit = !empty($membertype->duration_unit) ? $membertype->duration_unit : 'y';
			$datesubend = dol_time_plus_duree(dol_time_plus_duree($datesubscription, $durationvalue, $durationunit), -1, 'd');
			$label = $langs->transnoentitiesnoconv('MembershipPaid', dol_print_date($datesubend, 'day'));

			$this->db->begin();

			$subscriptionid = $member->subscription($datesubscription, $amount, 0, '', $label, '', '', '', $datesubend);
			if ($subscriptionid <= 0) {
				$this->db->rollback();
				$nberrors++;
				continue;
			}

			$rescomplement = $member->subscriptionComplementaryActions($subscriptionid, 'invoiceonly', 0, $datesubscription, '', '', $label, $amount, '', '', '', 1);
			if ($rescomplement < 0) {
				$this->db->rollback();
				$nberrors++;
				continue;
			}

			$this->db->commit();
			$nbcreated++;

			if ($sendmailenabled && !empty($member->email)) {
				$listofpaths = array();
				$listofnames = array();
				$listofmimes = array();

				if (is_object($member->invoice)) {
					$invoicediroutput = $conf->facture->dir_output;
					$fileparams = dol_most_recent_file($invoicediroutput.'/'.$member->invoice->ref, preg_quote($member->invoice->ref, '/').'[^\-]+');
					if (!empty($fileparams['fullname'])) {
						$file = $fileparams['fullname'];
						$listofpaths = array($file);
						$listofnames = array(basename($file));
						$listofmimes = array(dol_mimetype($file));
					}
				}

				$subject = $langs->transnoentitiesnoconv('MembershipPaid', dol_print_date($datesubend, 'day'));
				$text = $membertype->getMailOnSubscription();
				$ressend = $member->sendEmail($text, $subject, $listofpaths, $listofmimes, $listofnames);
				if ($ressend > 0) {
					$nbsentemail++;
				}
			}
		}

		setEventMessages($langs->trans('XSubsriptionCreated', $nbcreated), null, 'mesgs');
		if ($sendmailenabled) {
			setEventMessages($langs->trans('XEmailSent', $nbsentemail), null, 'mesgs');
		}
		if ($nberrors > 0) {
			setEventMessages($langs->trans('MassSubInvoiceEmailErrors', $nberrors), null, 'warnings');
		}

		return 0;
	}
}
