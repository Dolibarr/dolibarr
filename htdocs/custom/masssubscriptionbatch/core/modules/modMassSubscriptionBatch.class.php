<?php
/* Copyright (C) 2026 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modMassSubscriptionBatch extends DolibarrModules
{
	public function __construct($db)
	{
		$this->db = $db;

		$this->numero = 106500;
		$this->rights_class = 'masssubscriptionbatch';
		$this->family = 'members';
		$this->module_position = '90';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = 'Mass member subscriptions with automatic invoice and email';
		$this->version = '1.1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'payment';

		$this->module_parts = array(
			'triggers' => 0,
			'login' => 0,
			'substitutions' => 0,
			'menus' => 0,
			'tpl' => 0,
			'barcode' => 0,
			'models' => 0,
			'printing' => 0,
			'theme' => 0,
			'css' => array(),
			'js' => array(),
			'hooks' => array('data' => array('memberlist')),
			'moduleforexternal' => 0,
			'websitetemplates' => 0,
			'captcha' => 0,
		);

		$this->dirs = array('/masssubscriptionbatch/temp');
		$this->config_page_url = array('setup.php@masssubscriptionbatch');
		$this->depends = array('modAdherent', 'modSociete', 'modFacture');
		$this->requiredby = array();
		$this->langfiles = array('masssubscriptionbatch@masssubscriptionbatch');
		$this->phpmin = array(7, 2);
		$this->need_dolibarr_version = array(20, 0);

		$this->editor_name = 'Custom';
		$this->editor_url = '';

		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = 1065001;
		$this->rights[$r][1] = 'Run mass subscription + invoice + email action';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'run';
		$this->rights[$r][5] = '';
	}
}
