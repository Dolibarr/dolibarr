<?php

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modAccountingReports extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;

        $this->db = $db;
        $this->numero = 500230;
        $this->rights_class = 'accountingreports';

        $this->family = 'financial';
        $this->module_position = 500;
        $this->name = 'AccountingReports';
        $this->description = 'Adds custom accounting report menu entries';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_ACCOUNTINGREPORTS';
        $this->picto = 'accountancy';

        $this->depends = array('modAccounting');
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->phpmin = array(7, 4);
        $this->need_dolibarr_version = array(23, 0);

        $this->langfiles = array('accountancy');

        $this->rights = array();
        $this->menu = array();

        $r = 0;

        // Left menu parent under Accountancy
        $this->menu[$r++] = array(
            'fk_menu'  => 'fk_mainmenu=accountancy',
            'type'     => 'left',
            'titre'    => 'Financial Statements',
            'mainmenu' => 'accountancy',
            'leftmenu' => 'accountingreports',
            'url'      => '',
            'langs'    => 'accountancy',
            'position' => 900,
            'enabled'  => '$conf->accountingreports->enabled',
            'perms'    => '$user->rights->accounting->comptarapport->lire',
            'target'   => '',
            'user'     => 2
        );

        // Balance Sheet
        $this->menu[$r++] = array(
            'fk_menu'  => 'fk_mainmenu=accountancy,fk_leftmenu=accountingreports',
            'type'     => 'left',
            'titre'    => 'Balance Sheet',
            'mainmenu' => 'accountancy',
            'leftmenu' => 'accountingreports_balance_sheet',
            'url'      => '/accountancy/report/balance_sheet.php',
            'langs'    => 'accountancy',
            'position' => 901,
            'enabled'  => '$conf->accountingreports->enabled',
            'perms'    => '$user->rights->accounting->comptarapport->lire',
            'target'   => '',
            'user'     => 2
        );

        // Income Statement
        $this->menu[$r++] = array(
            'fk_menu'  => 'fk_mainmenu=accountancy,fk_leftmenu=accountingreports',
            'type'     => 'left',
            'titre'    => 'Income Statement',
            'mainmenu' => 'accountancy',
            'leftmenu' => 'accountingreports_income_statement',
            'url'      => '/accountancy/report/income_statement.php',
            'langs'    => 'accountancy',
            'position' => 902,
            'enabled'  => '$conf->accountingreports->enabled',
            'perms'    => '$user->rights->accounting->comptarapport->lire',
            'target'   => '',
            'user'     => 2
        );

        // Trial Balance
        $this->menu[$r++] = array(
            'fk_menu'  => 'fk_mainmenu=accountancy,fk_leftmenu=accountingreports',
            'type'     => 'left',
            'titre'    => 'Trial Balance',
            'mainmenu' => 'accountancy',
            'leftmenu' => 'accountingreports_trial_balance',
            'url'      => '/accountancy/report/trial_balance.php',
            'langs'    => 'accountancy',
            'position' => 903,
            'enabled'  => '$conf->accountingreports->enabled',
            'perms'    => '$user->rights->accounting->comptarapport->lire',
            'target'   => '',
            'user'     => 2
        );
    }

    public function init($options = '')
    {
        $sql = array();
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}