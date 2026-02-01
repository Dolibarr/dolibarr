<?php

/* Copyright (C) 2024-2025  Florian Hödl  <florian@hoedl.co>
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
 */

/**
 * \defgroup   paymentedit     Module PaymentEdit
 * \brief      Module to enable editing of various payments in Dolibarr
 *
 * \file       htdocs/custom/paymentedit/core/modules/modPaymentEdit.class.php
 * \ingroup    paymentedit
 * \brief      Description and activation file for module PaymentEdit
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module PaymentEdit
 */
class modPaymentEdit extends DolibarrModules
{
    /**
     * Constructor. Define names, constants, directories, boxes, permissions
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;
        $this->db = $db;

        // Module ID (must be unique)
        $this->numero = 510201;

        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'paymentedit';

        // Family: 'crm','financial','hr','projects','products','ecm','technic','interface','other'
        $this->family = "financial";

        // Module position in the family
        $this->module_position = '90';

        // Module label (no space allowed)
        $this->name = preg_replace('/^mod/i', '', get_class($this));

        // Module description
        $this->description = "PaymentEditDescription";
        $this->descriptionlong = "PaymentEditDescriptionLong";

        // Author
        $this->editor_name = 'Anexum GmbH';
        $this->editor_url = 'https://anexum.at';

        // Version
        $this->version = '1.0.0';

        // Key used in llx_const table to save module status
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

        // Module icon
        $this->picto = 'payment';

        // Define some features supported by module
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
            // Hook context for various payment card page
            'hooks' => array(
                'data' => array(
                    'variouscard',
                    'globalcard',
                ),
                'entity' => '0',
            ),
            'moduleforexternal' => 0,
        );

        // Data directories to create when module is enabled
        $this->dirs = array("/paymentedit/temp");

        // Config pages
        $this->config_page_url = array("setup.php@paymentedit");

        // Dependencies
        $this->hidden = false;
        $this->depends = array('modBanque');
        $this->requiredby = array();
        $this->conflictwith = array();

        // Languages
        $this->langfiles = array("paymentedit@paymentedit");

        // Constants
        $this->const = array();

        // Boxes/Widgets
        $this->boxes = array();

        // Cronjobs
        $this->cronjobs = array();

        // Permissions - uses banque rights
        $this->rights = array();

        // Main menu entries
        $this->menu = array();
    }

    /**
     * Function called when module is enabled.
     *
     * @param string $options Options when enabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $result = $this->_load_tables('/install/mysql/', 'paymentedit');
        if ($result < 0) {
            return -1;
        }

        // Create extrafields during init
        // include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        // $extrafields = new ExtraFields($this->db);

        $sql = array();

        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled.
     *
     * @param string $options Options when disabling module ('', 'noboxes')
     * @return int             1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();

        return $this->_remove($sql, $options);
    }
}
