<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent            <jmenent@2byte.es>
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
 *       \file       htdocs/core/modules/product/mod_codeproduct_elephant.php
 *       \ingroup    product
 *       \brief      File of class to manage product code with elephant rule
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';


/**
 *       \class                 mod_codeproduct_elephant
 *       \brief                 Class to manage product code with elephant rule
 */
class mod_codeproduct_jdc extends ModeleProductCode
{
        /**
         * @var string Nom du modele
         * @deprecated
         * @see $name
         */
        public $nom = 'Jdc';

        /**
         * @var string model name
         */
        public $name = 'Jdc';

        public $code_modifiable; // Code modifiable

        public $code_modifiable_invalide; // Code modifiable si il est invalide

        public $code_modifiable_null; // Code modifiables si il est null

        public $code_null; // Code facultatif

        /**
         * Dolibarr version of the loaded document
         * @var string
         */
        public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

        /**
         * @var int Automatic numbering
         */
        public $code_auto;

        public $searchcode; // String de recherche

        public $numbitcounter; // Nombre de chiffres du compteur

        public $prefixIsRequired; // Le champ prefix du tiers doit etre renseigne quand on utilise {pre}


        /**
         *      Constructor
         */
        public function __construct()
        {
                $this->code_null = 0;
                $this->code_modifiable = 0;
                $this->code_modifiable_invalide = 0;
                $this->code_modifiable_null = 0;
                $this->code_auto = 1;
                $this->prefixIsRequired = 0;
        }


        /**
         *  Return description of module
         *
         *  @param      Translate       $langs          Object langs
         *  @return string                              Description of module
         */
        public function info($langs)
        {
                global $conf, $mc;
                global $form;

                $langs->load("products");

                return '';
        }


        /**
         * Return an example of result returned by getNextValue
         *
         * @param       Translate       $langs          Object langs
         * @param       product         $objproduct             Object product
         * @param       int                     $type           Type of third party (1:customer, 2:supplier, -1:autodetect)
         * @return      string                                  Return string example
         */
        public function getExample($langs, $objproduct = 0, $type = -1)
        {
                return 'ECL-300-0157';
        }

        /**
         * Return next value
         *
         * @param       Product         $objproduct     Object product
         * @param       int                 $type       Produit ou service (0:product, 1:service)
         * @return      string                          Value if OK, '' if module not configured, <0 if KO
         */
        public function getNextValue($objproduct = 0, $type = -1)
        {
                global $db, $conf;

                require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

                $familyRef = "ECL-300";

                $start = 9;
                $sql = "SELECT MAX(CAST(SUBSTRING(ref FROM ".$start.") AS SIGNED)) as max";
                $sql .= " FROM ".MAIN_DB_PREFIX."product";
                $sql .= " WHERE ref LIKE '".$db->escape($familyRef)."-%'";

                $resql = $db->query($sql);
                if ($resql)
                {
                        $obj = $db->fetch_object($resql);
                        if ($obj) $max = intval($obj->max);
                        else $max = 0;
                }

                if ($max >= (pow(10, 4) - 1)) $num = $max + 1; // If counter > 9999, we do not format on 4 chars, we take number as it is
                else $num = sprintf("%04s", $max + 1);

                return $familiyRef."-".$num;
        }


        // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
        /**
         *   Check if mask/numbering use prefix
         *
         *   @return    int                     0 or 1
         */
        public function verif_prefixIsUsed()
        {
                // phpcs:enable
                global $conf;

                $mask = $conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT;
                if (preg_match('/\{pre\}/i', $mask)) return 1;

                $mask = $conf->global->PRODUCT_ELEPHANT_MASK_SERVICE;
                if (preg_match('/\{pre\}/i', $mask)) return 1;

                return 0;
        }


        /**
         *      Check validity of code according to its rules
         *
         *      @param  DoliDB          $db                     Database handler
         *      @param  string          $code           Code to check/correct
         *      @param  Product         $product        Object product
         *  @param  int                 $type           0 = product , 1 = service
         *  @return int                                         0 if OK
         *                                                                      -1 ErrorBadCustomerCodeSyntax
         *                                                                      -2 ErrorCustomerCodeRequired
         *                                                                      -3 ErrorCustomerCodeAlreadyUsed
         *                                                                      -4 ErrorPrefixRequired
         *                                                                      -5 Other (see this->error)
         */
        public function verif($db, &$code, $product, $type)
        {
                global $conf;

                require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

                $result = 0;
                $code = strtoupper(trim($code));

                if (empty($code) && $this->code_null && empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED))
                {
                        $result = 0;
                } elseif (empty($code) && (!$this->code_null || !empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED)))
                {
                        $result = -2;
                } else {
                        // Get Mask value
                        $mask = '';
                        if ($type == 0) $mask = empty($conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT) ? '' : $conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT;
                        if ($type == 1) $mask = empty($conf->global->PRODUCT_ELEPHANT_MASK_SERVICE) ? '' : $conf->global->PRODUCT_ELEPHANT_MASK_SERVICE;
                        if (!$mask)
                        {
                                $this->error = 'NotConfigured';
                                return -5;
                        }

                        $result = check_value($mask, $code);
                        if (is_string($result))
                        {
                                $this->error = $result;
                                return -5;
                        }
                }

                dol_syslog("mod_codeclient_elephant::verif type=".$type." result=".$result);
                return $result;
        }


        // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
        /**
         *  Renvoi si un code est pris ou non (par autre tiers)
         *
         *  @param      DoliDB          $db                     Handler acces base
         *  @param      string          $code           Code a verifier
         *  @param      Product         $product                Objet product
         *  @return     int                                             0 if available, <0 if KO
         */
        public function verif_dispo($db, $code, $product)
        {
                // phpcs:enable
                $sql = "SELECT ref FROM ".MAIN_DB_PREFIX."product";
                $sql .= " WHERE ref = '".$db->escape($code)."'";
                if ($product->id > 0) $sql .= " AND rowid <> ".$product->id;

                $resql = $db->query($sql);
                if ($resql)
                {
                        if ($db->num_rows($resql) == 0)
                        {
                                return 0;
                        } else {
                                return -1;
                        }
                } else {
                        return -2;
                }
        }
}