<?php
/*
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
 * or see http://www.gnu.org/
 */

 /**
 *  \file           htdocs/core/modules_compat.inc.php
 *  \brief          Include for external modules
 */
// VERSION 3.9
// Compat for db tables
// table accouting account
define('TABLE_ACCOUNTING_ACCOUNT', 'accounting_account');
define('ACCOUNTING_ACCOUNT_ROWID', 'rowid');
define('ACCOUNTING_ACCOUNT_ENTITY', 'entity');
define('ACCOUNTING_ACCOUNT_DATEC', 'datec');
define('ACCOUNTING_ACCOUNT_TMS', 'tms');
define('ACCOUNTING_ACCOUNT_FK_PCG_VERSION', 'fk_pcg_version');
define('ACCOUNTING_ACCOUNT_PCG_TYPE', 'pcg_type');
define('ACCOUNTING_ACCOUNT_PCG_SUBTYPE', 'pcg_subtype');
define('ACCOUNTING_ACCOUNT_ACCOUNT_NUMBER', 'account_number');
define('ACCOUNTING_ACCOUNT_ACCOUNT_PARENT', 'account_parent');
define('ACCOUNTING_ACCOUNT_LABEL', 'label');
define('ACCOUNTING_ACCOUNT_FK_USER_AUTHOR', 'fk_user_author');
define('ACCOUNTING_ACCOUNT_FK_USER_MODIF', 'fk_user_modif');
define('ACCOUNTING_ACCOUNT_ACTIVE', 'active');
// table accounting bookkeeping
// table accounting fiscal year
// table accounting system
// table accountingdebcred
// table accountingtransaction
// table actioncomm
// table actioncomm extrafields
// table actioncomm resources
// table adherent
// table of thirdparty
define('TABLE_THIRDPARTY', 'societe');
define('THIRDPARTY_ROWID', 'rowid');
define('THIRDPARTY_NAME', 'nom');
define('THIRDPARTY_NAME_ALIAS', 'name_alias');
define('THIRDPARTY_ENTITY', 'entity');
define('THIRDPARTY_REF_EXT', 'ref_ext');
define('THIRDPARTY_REF_INT', 'ref_int');
define('THIRDPARTY_STATUT', 'statut');
define('THIRDPARTY_PARENT', 'parent');
define('THIRDPARTY_TMS', 'tms');
define('THIRDPARTY_DATEC', 'datec');
define('THIRDPARTY_THIRDPARTY_CODE', 'code_client');
define('THIRDPARTY_SUPPLIER_CODE', 'code_fournisseur');
define('THIRDPARTY_ACCOUNTING_CODE', 'code_compta');
define('THIRDPARTY_SUPPLIER_ACCOUNTING_CODE', 'code_compta_fournisseur');
define('THIRDPARTY_ADDRESS', 'address');
define('THIRDPARTY_ZIP', 'zip');
define('THIRDPARTY_TOWN', 'town');
define('THIRDPARTY_FK_STATE', 'fk_departement');
define('THIRDPARTY_FK_COUNTRY', 'fk_pays');
define('THIRDPARTY_PHONE', 'fk_pays');
define('THIRDPARTY_FAX', 'fk_pays');
define('THIRDPARTY_URL', 'fk_pays');
define('THIRDPARTY_EMAIL', 'fk_pays');
define('THIRDPARTY_SKYPE', 'fk_pays');
define('THIRDPARTY_FK_WORKFORCE', 'fk_effectif');
define('THIRDPARTY_FK_BUSINESS_TYPE', 'fk_typent');
define('THIRDPARTY_FK_JURIDICAL', 'fk_forme_juridique');
define('THIRDPARTY_FK_CURRENCY', 'fk_currency');
define('THIRDPARTY_IDPROF1', 'siren');
define('THIRDPARTY_IDPROF2', 'siret');
define('THIRDPARTY_IDPROF3', 'ape');
define('THIRDPARTY_IDPROF4', 'idprof4');
define('THIRDPARTY_IDPROF5', 'idprof5');
define('THIRDPARTY_IDPROF6', 'idprof6');
// ...

// Compat for Triggers Names
// Users
define('USER_CREATE', 'USER_CREATE');
define('USER_MODIFY', 'USER_MODIFY');
define('USER_NEW_PASSWORD', 'USER_NEW_PASSWORD');
define('USER_ENABLEDISABLE', 'USER_ENABLEDISABLE');
define('USER_DELETE', 'USER_DELETE');
// ...
