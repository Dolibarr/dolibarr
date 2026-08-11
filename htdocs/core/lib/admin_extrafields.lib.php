<?php
/* Copyright (C) 2026  Frédéric France  <frederic.france@free.fr>
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
 *	\file		htdocs/core/lib/admin_extrafields.lib.php
 *	\brief		Whitelist of elementtype values accepted by the unified
 *				extrafields admin page (htdocs/admin/extrafields.php).
 *
 *	This is a closed registry: only elementtype values explicitly listed
 *	here can ever be processed by the unified extrafields admin page.
 *	Do not make this dynamic or pattern-based — the whole point is that
 *	an attacker-controlled string can never reach ExtraFields::addExtraField(),
 *	ExtraFields::update()/delete(), or the raw SQL built in
 *	core/actions_extrafields.inc.php, without having first matched one of
 *	these hardcoded keys.
 */

/**
 * Build a closure that mirrors the isModEnabled("product")/isModEnabled("service")
 * conditional found across the product/service family of extrafields admin wrapper
 * pages: the base translation key applies when both modules are enabled, and each
 * of the other two keys applies when only one of the two modules is enabled.
 *
 * This exists purely to avoid repeating the same 8-line conditional in every
 * `title`/`headlabel` closure of the product-family registry entries below —
 * the original wrapper files all contained this exact conditional shape
 * verbatim, only the translation keys (and, for the base key, trans() vs
 * transnoentitiesnoconv()) differ between $title and $textobject.
 *
 * @param string $keyBoth          Translation key when both product and service are enabled
 * @param string $keyServiceOnly   Translation key when only the service module is enabled
 * @param string $keyProductOnly   Translation key when only the product module is enabled
 * @param bool   $noconv           Use transnoentitiesnoconv() instead of trans() for $keyBoth,
 *                                 matching the original wrapper files' $textobject assignment
 * @return callable
 */
function extrafieldsAdminProductServiceLabel($keyBoth, $keyServiceOnly, $keyProductOnly, $noconv = false)
{
	return function () use ($keyBoth, $keyServiceOnly, $keyProductOnly, $noconv) {
		global $langs;
		$label = $noconv ? $langs->transnoentitiesnoconv($keyBoth) : $langs->trans($keyBoth);
		if (!isModEnabled("product")) {
			$label = $langs->trans($keyServiceOnly);
		} elseif (!isModEnabled("service")) {
			$label = $langs->trans($keyProductOnly);
		}
		return $label;
	};
}

/**
 * Return the whitelist of elementtype values accepted by
 * htdocs/admin/extrafields.php, and the page metadata needed to render
 * each one (which tab-bar function to call, which lang files to load, ...).
 *
 * `headlabel` is the string (or no-arg callable returning an already-translated
 * string) passed to dol_get_fiche_head()'s $title argument (the tab-head picto's
 * alt/title tooltip). `textobject` is the string (or callable) shown in the
 * "Define any additional / custom attributes that must be added to: %s" sentence
 * rendered by core/tpl/admin_extrafields_view.tpl.php. The two original per-object
 * wrapper files each set these independently and they are frequently NOT the same
 * lang key — do not assume they match. `textobject` is optional and falls back to
 * the *resolved* `headlabel` value when omitted, which is safe only when the source
 * wrapper file passed the same string (or the same variable) to both, or never set
 * $textobject at all. Like `title`, `headlabel`/`textobject` callables are called
 * with no arguments and their return value is used as-is (already translated) —
 * they are NOT passed through $langs->trans()/transnoentitiesnoconv() again.
 *
 * @return array<string,array{headfunction:string,headfile:string,tabid:string,headlabel:string|callable,headpicto:string,title:string|callable,helpurl:string,langs:string[],textobject?:string|callable}>
 */
function getExtrafieldsAdminMap()
{
	return array(
		'societe' => array(
			'headfunction' => 'societe_admin_prepare_head',
			'headfile'     => 'core/lib/company.lib.php',
			'tabid'        => 'attributes',
			'headlabel'    => 'ThirdParties',
			'textobject'   => 'ThirdParty',
			'headpicto'    => 'company',
			'title'        => 'CompanySetup',
			'helpurl'      => 'EN:Module Third Parties setup|FR:Paramétrage_du_module_Tiers',
			'langs'        => array('companies', 'admin', 'members'),
		),
		'socpeople' => array(
			'headfunction' => 'societe_admin_prepare_head',
			'headfile'     => 'core/lib/company.lib.php',
			'tabid'        => 'attributes_contacts',
			'headlabel'    => 'ThirdParties',
			'textobject'   => 'ContactsAddresses',
			'headpicto'    => 'company',
			'title'        => 'CompanySetup',
			'helpurl'      => 'EN:Module Third Parties setup|FR:Paramétrage_du_module_Tiers',
			'langs'        => array('companies', 'admin'),
		),
		'product' => array(
			'headfunction' => 'product_admin_prepare_head',
			'headfile'     => 'core/lib/product.lib.php',
			'tabid'        => 'attributes',
			'headlabel'    => extrafieldsAdminProductServiceLabel('ProductsAndServices', 'Services', 'Products', true),
			'headpicto'    => 'product',
			'title'        => extrafieldsAdminProductServiceLabel('ProductServiceSetup', 'ServiceSetup', 'ProductSetup'),
			'helpurl'      => '',
			'langs'        => array('companies', 'admin', 'products'),
		),
		'product_lang' => array(
			'headfunction' => 'product_admin_prepare_head',
			'headfile'     => 'core/lib/product.lib.php',
			'tabid'        => 'translationAttributes',
			'headlabel'    => 'ProductLangExtrafieldsSetup',
			'textobject'   => 'Product',
			'headpicto'    => 'product',
			'title'        => 'ProductLangExtrafieldsSetup',
			'helpurl'      => '',
			'langs'        => array('admin', 'products'),
		),
		'product_price' => array(
			'headfunction' => 'product_admin_prepare_head',
			'headfile'     => 'core/lib/product.lib.php',
			'tabid'        => 'levelAttributes',
			'headlabel'    => extrafieldsAdminProductServiceLabel('ProductsAndServices', 'Services', 'Products', true),
			'headpicto'    => 'product',
			'title'        => extrafieldsAdminProductServiceLabel('ProductServiceSetup', 'ServiceSetup', 'ProductSetup'),
			'helpurl'      => '',
			'langs'        => array('companies', 'admin', 'products'),
		),
		'product_customer_price' => array(
			'headfunction' => 'product_admin_prepare_head',
			'headfile'     => 'core/lib/product.lib.php',
			'tabid'        => 'customerAttributes',
			'headlabel'    => extrafieldsAdminProductServiceLabel('ProductsAndServices', 'Services', 'Products', true),
			'headpicto'    => 'product',
			'title'        => extrafieldsAdminProductServiceLabel('ProductServiceSetup', 'ServiceSetup', 'ProductSetup'),
			'helpurl'      => '',
			'langs'        => array('companies', 'admin', 'products'),
		),
		'product_fournisseur_price' => array(
			'headfunction' => 'product_admin_prepare_head',
			'headfile'     => 'core/lib/product.lib.php',
			'tabid'        => 'supplierAttributes',
			'headlabel'    => extrafieldsAdminProductServiceLabel('ProductsAndServices', 'Services', 'Products', true),
			'headpicto'    => 'product',
			'title'        => extrafieldsAdminProductServiceLabel('ProductServiceSetup', 'ServiceSetup', 'ProductSetup'),
			'helpurl'      => '',
			'langs'        => array('companies', 'admin', 'products'),
		),
		'product_lot' => array(
			'headfunction' => 'product_lot_admin_prepare_head',
			'headfile'     => 'core/lib/product.lib.php',
			'tabid'        => 'attributes',
			'headlabel'    => 'Batch',
			'headpicto'    => 'lot',
			'title'        => 'ProductLotSetup',
			'helpurl'      => '',
			'langs'        => array('companies', 'admin', 'products', 'productbatch'),
		),
		'entrepot' => array(
			'headfunction' => 'stock_admin_prepare_head',
			'headfile'     => 'core/lib/stock.lib.php',
			'tabid'        => 'attributes',
			'headlabel'    => 'StockSetup',
			'textobject'   => 'Warehouses',
			'headpicto'    => 'stock',
			'title'        => 'StockSetup',
			'helpurl'      => '',
			'langs'        => array('companies', 'admin', 'stocks'),
		),
		'stock_mouvement' => array(
			'headfunction' => 'stock_admin_prepare_head',
			'headfile'     => 'core/lib/stock.lib.php',
			'tabid'        => 'stockMouvementAttributes',
			'headlabel'    => 'StockMouvementExtraFields',
			'textobject'   => 'StockMovement',
			'headpicto'    => 'account',
			'title'        => 'StockSetup',
			'helpurl'      => '',
			'langs'        => array('stock@stock', 'admin'),
		),
		'inventory' => array(
			'headfunction' => 'stock_admin_prepare_head',
			'headfile'     => 'core/lib/stock.lib.php',
			'tabid'        => 'inventoryAttributes',
			'headlabel'    => 'InventoryExtraFields',
			'textobject'   => 'Inventory',
			'headpicto'    => 'account',
			'title'        => 'InventorySetup',
			'helpurl'      => '',
			'langs'        => array('stock@stock', 'admin'),
		),
		'product_attribute' => array(
			'headfunction' => 'adminProductAttributePrepareHead',
			'headfile'     => 'variants/lib/variants.lib.php',
			'tabid'        => 'product_attribute',
			'headlabel'    => 'ProductAttributeExtrafieldsSetup',
			'headpicto'    => 'product',
			'title'        => 'ProductAttributeExtrafieldsSetup',
			'helpurl'      => '',
			'langs'        => array('admin', 'other', 'product'),
		),
		'product_attribute_value' => array(
			'headfunction' => 'adminProductAttributePrepareHead',
			'headfile'     => 'variants/lib/variants.lib.php',
			'tabid'        => 'product_attribute_value',
			'headlabel'    => 'ProductAttributeValueExtrafieldsSetup',
			'headpicto'    => 'product',
			'title'        => 'ProductAttributeValueExtrafieldsSetup',
			'helpurl'      => '',
			'langs'        => array('admin', 'other', 'sendings'),
		),
		'adherent' => array(
			'headfunction' => 'member_admin_prepare_head',
			'headfile'     => 'core/lib/member.lib.php',
			'tabid'        => 'attributes',
			'headlabel'    => 'Members',
			'headpicto'    => 'user',
			'title'        => 'MembersSetup',
			'helpurl'      => 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder',
			'langs'        => array('admin', 'members'),
		),
		'adherent_type' => array(
			'headfunction' => 'member_admin_prepare_head',
			'headfile'     => 'core/lib/member.lib.php',
			'tabid'        => 'attributes_type',
			'headlabel'    => 'Members',
			'textobject'   => 'MembersTypes',
			'headpicto'    => 'user',
			'title'        => 'MembersSetup',
			'helpurl'      => 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder',
			'langs'        => array('admin', 'members'),
		),
		'bank_account' => array(
			'headfunction' => 'bank_admin_prepare_head',
			'headfile'     => 'core/lib/bank.lib.php',
			'tabid'        => 'attributes',
			'headlabel'    => 'BankSetupModule',
			'textobject'   => 'Bank',
			'headpicto'    => 'account',
			'title'        => 'BankSetupModule',
			'helpurl'      => '',
			'langs'        => array('banks', 'admin'),
		),
		'bank' => array(
			'headfunction' => 'bank_admin_prepare_head',
			'headfile'     => 'core/lib/bank.lib.php',
			'tabid'        => 'bankline_extrafields',
			'headlabel'    => 'BankSetupModule',
			'textobject'   => 'BankTransaction',
			'headpicto'    => 'account',
			'title'        => 'BankSetupModule',
			'helpurl'      => '',
			'langs'        => array('admin', 'companies', 'bills', 'other', 'banks'),
		),
		'paiement' => array(
			'headfunction' => 'bank_admin_prepare_head',
			'headfile'     => 'core/lib/bank.lib.php',
			'tabid'        => 'bank_payments_extrafields',
			'headlabel'    => 'BankSetupModule',
			'textobject'   => 'BankTransaction',
			'headpicto'    => 'account',
			'title'        => 'BankSetupModule',
			'helpurl'      => '',
			'langs'        => array('admin', 'companies', 'bills', 'other', 'banks'),
		),
		'payment_various' => array(
			'headfunction' => 'bank_admin_prepare_head',
			'headfile'     => 'core/lib/bank.lib.php',
			'tabid'        => 'bank_various_payment_extrafields',
			'headlabel'    => 'BankSetupModule',
			'textobject'   => 'VariousPayments',
			'headpicto'    => '',
			'title'        => 'BankSetupModule',
			'helpurl'      => '',
			'langs'        => array('admin', 'banks', 'other'),
		),
		'bom_bom' => array(
			'headfunction' => 'bomAdminPrepareHead',
			'headfile'     => 'bom/lib/bom.lib.php',
			'tabid'        => 'bom_extrafields',
			'headlabel'    => 'ExtraFields',
			'textobject'   => 'BOM',
			'headpicto'    => 'account',
			'title'        => 'BOMsSetup',
			'helpurl'      => '',
			'langs'        => array('mrp', 'admin'),
		),
		'bom_bomline' => array(
			'headfunction' => 'bomAdminPrepareHead',
			'headfile'     => 'bom/lib/bom.lib.php',
			'tabid'        => 'bomline_extrafields',
			'headlabel'    => 'ExtraFields',
			'textobject'   => 'BOM',
			'headpicto'    => 'account',
			'title'        => 'BOMsSetup',
			'helpurl'      => '',
			'langs'        => array('mrp', 'admin'),
		),
	);
}
