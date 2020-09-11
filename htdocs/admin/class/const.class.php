<?php
/* Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
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
 *	\file       htdocs/admin/class/const.const.php
 *	\ingroup    setup
 *	\brief      Class that contain a list with constants (no dependencies)
 */

class ConstClass
{
	// Keep in sync with: https://wiki.dolibarr.org/index.php/Setup_Other

	/**
	 * Return a list with useable constants in Dolibarr
	 *
	 * @return array A list with Dolibarr constants
	 *
	 *				Format: [Module, Version, Name])
	 *
	 *				Module:	The name of the module or the category of this constant, use for a better overview
	 *
	 *				Version: The minimum major version of Dolibarr to use this constant (A version lower 0 means that the constant is deprecated)
	 *
	 *				Name: The name of this constant
	 */
	public function getConstList()
	{
		return array_merge(
			$this->getGlobalOptions(),
			$this->getAccountancyModule(),
			$this->getAgendaModule(),
			$this->getBankModule(),
			$this->getBlockedLog(),
			$this->getContracts(),
			$this->getDirectDebitOrders(),
			$this->getEmailAndSms(),
			$this->getEmailingModule(),
			$this->getExpenseReport(),
			$this->getExportModule(),
			$this->getFoundationModule(),
			$this->getInterventionsModule(),
			$this->getInvoiceModule(),
			$this->getMultiCurrency(),
			$this->getLookAndTheme(),
			$this->getPdfOption(),
			$this->getOrderModule(),
			$this->getPointOfSale(),
			$this->getProductsModule(),
			$this->getProjectsModule(),
			$this->getProposalsModule(),
			$this->getServicesModule(),
			$this->getShipments(),
			$this->getStocks(),
			$this->getSuppliersModule(),
			$this->getThirdPartiesModule(),
			$this->getVATReport(),
			$this->getWysiwgEditor(),
			$this->getSTripeModule(),
			$this->getClickToDialModule(),
			$this->getSaasCloudHosting()
		);
	}

	// Global Options: 73 Entries
	private function getGlobalOptions()
	{
		return [
			["Global", 10, "ADD_UNSPLASH_LOGIN_BACKGROUND"],
			["Global",  0, "MAIN_APPLICATION_TITLE"],
			["Global",  0, "MAIN_AUTOFILL_DATE"],
			["Global",  0, "MAIN_AUTO_TIMESTAMP_IN_PUBLIC_NOTES"],
			["Global",  0, "MAIN_AUTO_TIMESTAMP_IN_PRIVATE_NOTES"],
			["Global", 10, "MAIN_COUNTRIES_IN_EEC"],
			["Global",  0, "MAIN_DEFAULT_PAYMENT_TERM_ID"],
			["Global", 12, "MAIN_DEFAULT_PAYMENT_TYPE_ID"],
			["Global", 10, "MAIN_DEFAULT_LANGUAGE_FILTER"],
			["Global", 11, "MAIN_LANGUAGES_ALLOWED"],
			["Global",  0, "MAIN_DISABLE_NOTES_TAB"],
			["Global",  0, "MAIN_DISABLE_CONTACTS_TAB"],
			["Global",  0, "MAIN_DISABLE_FULL_SCANLIST"],
			["Global",  0, "MAIN_DISABLE_JQUERY_JNOTIFY"],
			["Global",  4, "MAIN_DISABLE_AJAX_COMBOX"],
			["Global",  0, "MAIN_DISABLE_MULTIPLE_FILEUPLOAD"],
			["Global",  7, "MAIN_DISABLE_TRUNC"],
			["Global",  0, "MAIN_DISABLEDRAFTSTATUS"],
			["Global",  0, "MAIN_DOC_USE_OBJECT_THIRDPARTY_NAME"],
			["Global",  0, "MAIN_DOC_USE_TIMING"],
			["Global", 10, "MAIN_DOC_UPLOAD_NOT_RENAME_BY_DEFAULT"],
			["Global",  0, "MAIN_DOL_SCRIPTS_ROOT"],
			["Global",  0, "MAIN_ENABLE_LOG_TO_HTML"],
			["Global",  8, "MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES"],
			["Global",  0, "MAIN_FIRST_TO_UPPER"],
			["Global", 11, "MAIN_ALL_TO_UPPER"],
			["Global",  0, "MAIN_FILESYSTEM_ENCODING"],
			["Global",  0, "MAIN_FORCELANGDIR"],
			["Global",  0, "MAIN_HTML_TITLE"],
			["Global",  0, "MAIN_HELPCENTER_LINKTOUSE"],
			["Global",  4, "MAIN_LANDING_PAGE"],
			["Global",  0, "MAIN_LOGOUT_GOTO_URL"],
			["Global",  0, "MAIN_MAXTABS_IN_CARD"],
			["Global",  0, "MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING"],
			["Global",  0, "MAIN_MODULES_FOR_EXTERNAL"],
			["Global", 10, "MAIN_NO_CONCAT_DESCRIPTION"],
			["Global",  0, "MAIN_ONLY_LOGIN_ALLOWED"],
			["Global",  0, "MAIN_OPTIMIZE_SPEED"],
			["Global",  4, "MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN"],
			["Global",  4, "MAIN_PUBLIC_NOTE_IN_ADDRESS"],
			["Global",  0, "MAIN_REMOVE_INSTALL_WARNING"],
			["Global",  0, "MAIN_REPEATCONTACTONEACHTAB"],
			["Global",  4, "MAIN_REPLACE_TRANS_xx_XX"],
			["Global",  0, "MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND"],
			["Global",  5, "MAIN_SECURITY_CSRF_WITH_TOKEN"],
			["Global",  0, "MAIN_SERVER_TZ"],
			["Global",  6, "MAIN_SEARCH_FORM_ON_HOME_AREAS"],
			["Global",  5, "MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE"],
			["Global",  0, "MAIN_SERVICES_ARE_ECOMMERCE_200238EC"],
			["Global",  0, "MAIN_SHOW_TUNING_INFO"],
			["Global",  0, "MAIN_SHOW_TECHNICAL_ID"],
			["Global",  8, "MAIN_SHOWDATABASENAMEINHELPPAGESLINK"],
			["Global",  0, "MAIN_USE_HOURMIN_IN_DATE_RANGE"],
			["Global",  0, "MAIN_USE_JQUERY_JEDITABLE"],
			["Global",  0, "MAIN_USE_JQUERY_MULTISELECT"],
			["Global",  0, "MAIN_USE_OLD_SEARCH_FORM"],
			["Global",  7, "MAIN_USE_VAT_OF_PRODUCT_FOR_INDIVIDUAL_CUSTOMER_OUT_OF_EEC"],
			["Global",  0, "MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS"],
			["Global",  0, "MAIN_VIEW_LINE_NUMBER"],
			["Global", 12, "MAIN_VIEW_LINE_NUMBER_IN_LIST"],
			["Global",  0, "MAIN_VOLUME_DEFAULT_ROUND"],
			["Global",  0, "MAIN_VOLUME_DEFAULT_UNIT"],
			["Global",  0, "MAIN_WEIGHT_DEFAULT_ROUND"],
			["Global",  0, "MAIN_WEIGHT_DEFAULT_UNIT"],
			["Global",  0, "USER_HIDE_INACTIVE_IN_COMBOBOX"],
			["Global",  0, "MAIN_DISABLE_PDF_THUMBS"],
			["Global",  0, "MAIN_KEEP_REF_CUSTOMER_ON_CLONING"],
			["Global", 11, "MAIN_DONT_KEEP_NOTE_ON_CLONING"],
			["Global", 11, "MAIN_DOC_SORT_FIELD"],
			["Global", 11, "MAIN_DOC_SORT_ORDER"],
			["Global",  0, "MAIN_USE_ZIPTOWN_DICTIONNARY"],
			["Global", 11, "MAIN_USE_TOP_MENU_BOOKMARK_DROPDOWN"],
			["Global", 11, "MAIN_USE_TOP_MENU_SEARCH_DROPDOWN"],
			["Global", 12, "MAIN_PHONE_SEPAR"]
		];
	}

	// Accountancy Module: 1 Entry
	private function getAccountancyModule()
	{
		return [
			["Accountancy", 10, "ACCOUNTANCY_COMBO_FOR_AUX "]
		];
	}

	// Agenda Module: 6 Entries
	private function getAgendaModule()
	{
		return [
			["Agenda",  0, "AGENDA_MAX_EVENTS_DAY_VIEW"],
			["Agenda", -1, "AGENDA_USE_EVENT_TYPE"],
			["Agenda",  0, "AGENDA_AUTOSET_END_DATE_WITH_DELTA_HOURS"],
			["Agenda",  5, "AGENDA_DISABLE_LOCATION"],
			["Agenda",  0, "MAIN_ADD_EVENT_ON_ELEMENT_CARD"],
			["Agenda",  0, "AGENDA_ALL_CALENDARS"]
		];
	}

	// Bank Module: 4 Entries
	private function getBankModule()
	{
		return [
			["Bank",  4, "BANK_CAN_RECONCILIATE_CASHACCOUNT"],
			["Bank",  0, "BANK_DISABLE_CHECK_DEPOSIT "],
			["Bank",  0, "BANK_ASK_PAYMENT_BANK_DURING_ORDER"],
			["Bank",  0, "BANK_ASK_PAYMENT_BANK_DURING_PROPOSAL"]
		];
	}

	// BlockedLog: 1 Entry
	private function getBlockedLog()
	{
		return [
			["BlockedLog",  0, "BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY"]
		];
	}

	// Contracts: 1 Entry
	private function getContracts()
	{
		return [
			["Contracts",  0, "CONTRACT_SUPPORT_PRODUCTS"]
		];
	}

	// Direct Debit Orders: 1 Entry
	private function getDirectDebitOrders()
	{
		return [
			["Direct Debit Orders",  0, "WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS"]
		];
	}

	// Email and SMS: 34 Entries
	private function getEmailAndSms()
	{
		return [
			["Email and SMS",  8, "MAIN_MAILFORM_DISABLE_ENTERKEY"],
			["Email and SMS",  8, "MAIL_PREFIX_FOR_EMAIL_ID"],
			["Email and SMS",  0, "MAIN_MAIL_DEBUG"],
			["Email and SMS",  0, "MAIN_SMS_DEBUG"],
			["Email and SMS",  0, "MAIN_MAIL_ALLOW_SENDMAIL_F"],
			["Email and SMS",  0, "MAIN_MAIL_SENDMAIL_FORCE_BA"],
			["Email and SMS",  0, "MAIN_MAIL_NO_FULL_EMAIL"],
			["Email and SMS",  0, "MAIN_FIX_FOR_BUGGED_MTA"],
			["Email and SMS",  0, "MAIN_MAIL_DO_NOT_USE_SIGN"],
			["Email and SMS",  0, "MAIL_FORCE_DELIVERY_RECEIPT_INVOICE"],
			["Email and SMS",  0, "MAIL_FORCE_DELIVERY_RECEIPT_ORDER"],
			["Email and SMS",  0, "MAIL_FORCE_DELIVERY_RECEIPT_PROPAL"],
			["Email and SMS",  0, "MAIN_EMAIL_USECCC"],
			["Email and SMS",  0, "MAIN_MAIL_AUTOCOPY_PROPOSAL_TO"],
			["Email and SMS",  0, "MAIN_MAIL_AUTOCOPY_ORDER_TO"],
			["Email and SMS",  0, "MAIN_MAIL_AUTOCOPY_INVOICE_TO"],
			["Email and SMS",  0, "MAIN_EMAIL_ADD_TRACK_ID"],
			["Email and SMS",  0, "MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL"],
			["Email and SMS",  0, "MAIL_MAX_NB_OF_RECIPIENTS_TO_IN_SAME_EMAIL"],
			["Email and SMS",  0, "MAIL_MAX_NB_OF_RECIPIENTS_CC_IN_SAME_EMAIL"],
			["Email and SMS",  0, "MAIL_MAX_NB_OF_RECIPIENTS_BCC_IN_SAME_EMAIL"],
			["Email and SMS",  0, "MAIN_MAIL_FORCE_CONTENT_TYPE_TO_HTML"],
			["Email and SMS",  5, "MAIN_MAIL_USE_MULTI_PART"],
			["Email and SMS",  0, "MAIN_MAIL_ADD_INLINE_IMAGES_IF_IN_MEDIAS"],
			["Email and SMS",  6, "MAIN_COPY_FILE_IN_EVENT_AUTO"],
			["Email and SMS",  0, "MAIN_EXTERNAL_SMTP_CLIENT_IP_ADDRESS"],
			["Email and SMS",  0, "MAIN_EXTERNAL_MAIL_CLIENT_IP_ADDRESS"],
			["Email and SMS",  0, "MAIN_EXTERNAL_SMTP_SPF_STRING_TO_ADD"],
			["Email and SMS",  8, "MAIN_MAIL_ENABLED_USER_DEST_SELECT"]
		];
	}

	// Emailing Module: 8 Entries
	private function getEmailingModule()
	{
		return [
			["Emailing",  0, "MAILING_PREFIX_FOR_EMAIL_ID"],
			["Emailing",  0, "MAILING_NO_USING_PHPMAIL"],
			["Emailing",  0, "MAILING_SMTP_SETUP_EMAILS_FOR_QUESTIONS"],
			["Emailing",  0, "MAILING_LIMIT_WARNING_PHPMAIL"],
			["Emailing",  0, "MAILING_LIMIT_WARNING_NOPHPMAIL"],
			["Emailing",  0, "EMAILING_USE_ADVANCED_SELECTOR"],
			["Emailing",  0, "MAILING_LIMIT_SENDBYWEB"],
			["Emailing",  0, "MAILING_LIMIT_SENDBYCLI"]
		];
	}

	// Expense Report: 5 Entries
	private function getExpenseReport()
	{
		return [
			["Expense Report",  7, "EXPENSEREPORT_ALLOW_OVERLAPPING_PERIODS"],
			["Expense Report", 10, "EXPENSEREPORT_OVERRIDE_VAT"],
			["Expense Report",  6, "EXPENSEREPORT_USE_OLD_NUMBERING_RULE"],
			["Expense Report",  7, "MAIN_USE_EXPENSE_IK"],
			["Expense Report",  7, "MAIN_USE_EXPENSE_RULE"]
		];
	}

	// Export Module: 5 Entries
	private function getExportModule()
	{
		return [
			["Export",  0, "EXPORT_CSV_SEPARATOR_TO_USE"],
			["Export",  0, "EXPORT_CSV_FORCE_CHARSET"],
			["Export",  0, "EXPORTTOOL_CATEGORIES"],
			["Export",  0, "USE_STRICT_CSV_RULES"],
			["Export",  0, "EXPORTS_SHARE_MODELS"]
		];
	}

	// Foundation Module: 2 Entries
	private function getFoundationModule()
	{
		return [
			["Foundation",  0, "MEMBER_URL_REDIRECT_SUBSCRIPTION"],
			["Foundation",  0, "MEMBER_EXT_URL_SUBSCRIPTION_INFO"]
		];
	}

	// Interventions Module: 2 Entries
	private function getInterventionsModule()
	{
		return [
			["Interventions",  0, "FICHINTER_CLASSIFY_BILLED"],
			["Interventions",  0, "FICHINTER_DISABLE_DETAILS"]
		];
	}

	// Invoice Module: 27 Entries
	private function getInvoiceModule()
	{
		return [
			["Invoice",  6, "MAIN_DEPOSIT_MULTI_TVA"],
			["Invoice",  0, "FACTURE_SHOW_SEND_REMINDER"],
			["Invoice",  0, "INVOICE_CAN_ALWAYS_BE_EDITED"],
			["Invoice",  0, "INVOICE_CAN_ALWAYS_BE_REMOVED"],
			["Invoice",  0, "INVOICE_CAN_NEVER_BE_REMOVED"],
			["Invoice",  0, "INVOICE_POSITIVE_CREDIT_NOTE"],
			["Invoice",  0, "FACTURE_CHANGE_THIRDPARTY"],
			["Invoice",  0, "FACTURE_USE_PROFORMAT"],
			["Invoice",  0, "FACTURE_DEPOSITS_ARE_JUST_PAYMENTS"],
			["Invoice",  0, "FACTURE_SENDBYEMAIL_FOR_ALL_STATUS"],
			["Invoice",  4, "INVOICE_CREDIT_NOTE_STANDALONE"],
			["Invoice",  0, "INVOICE_USE_SITUATION"],
			["Invoice",  8, "INVOICE_USE_SITUATION_CREDIT_NOTE"],
			["Invoice",  0, "INVOICE_DISABLE_DEPOSIT"],
			["Invoice",  0, "INVOICE_DISABLE_REPLACEMENT"],
			["Invoice",  0, "INVOICE_DISABLE_CREDIT_NOTE"],
			["Invoice",  0, "MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT"],
			["Invoice",  0, "MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN"],
			["Invoice",  0, "WORKFLOW_BILL_ON_SHIPMENT"],
			["Invoice",  7, "INVOICE_ALLOW_EXTERNAL_DOWNLOAD"],
			["Invoice",  4, "INVOICE_POINTOFTAX_DATE"],
			["Invoice",  0, "FACTURE_ENABLE_NEGATIVE"],
			["Invoice",  8, "FACTURE_ENABLE_NEGATIVE_LINES"],
			["Invoice",  0, "INVOICE_USE_DEFAULT_DOCUMENT"],
			["Invoice",  0, "FACTURE_REUSE_NOTES_ON_CREATE_FROM"],
			["Invoice",  9, "MAIN_SHOW_FACNUMBER_IN_DISCOUNT_LIST"],
			["Invoice",  0, "INVOICE_ALLOW_FREE_REF"]
		];
	}

	// Look or Theme: 14 Entry
	private function getLookAndTheme()
	{
		return [
			["Look or Theme",  0, "MAIN_FAVICON_URL"],
			["Look or Theme",  0, "MAIN_OPTIMIZEFORTEXTBROWSER"],
			["Look or Theme",  4, "THEME_ELDY_DISABLE_IMAGE"],
			["Look or Theme",  0, "MAIN_MENU_HIDE_UNAUTHORIZED"],
			["Look or Theme",  7, "THEME_TOPMENU_STICKY_POSITION"],
			["Look or Theme",  6, "MAIN_EASTER_EGG_COMMITSTRIP"],
			["Look or Theme", 10, "MAIN_STATUS_USES_CSS"],
			["Look or Theme", 10, "MAIN_USE_NEW_TITLE_BUTTON"],
			["Look or Theme",  0, "MAIN_APPLICATION_DISABLED_STATEBOARD"],
			["Look or Theme",  0, "MAIN_APPLICATION_DISABLED_WORKBOARD"],
			["Look or Theme", 11, "MAIN_INCLUDE_GLOBAL_STATS_IN_OPENED_DASHBOARD"],
			["Look or Theme", 11, "MAIN_USE_TOP_MENU_BOOKMARK_DROPDOWN"],
			["Look or Theme", 11, "MAIN_USE_TOP_MENU_SEARCH_DROPDOWN"],
			["Look or Theme", 11, "THEME_AGRESSIVENESS_RATIO"]
		];
	}

	// MultiCurrency: 7 Entries
	private function getMultiCurrency()
	{
		return [
			["MultiCurrency",  0, "MAIN_MULTICURRENCY_ALLOW_SYNCHRONIZATION"]
		];
	}

	// Order  Module: 7 Entires
	private function getOrderModule()
	{
		return [
			["Order",  7, "MAIN_USE_PROPAL_REFCLIENT_FOR_ORDER"],
			["Order",  0, "ORDER_REQUIRE_SOURCE"],
			["Order",  0, "ORDER_VALID_AFTER_CLOSE_PROPAL"],
			["Order",  7, "ORDER_ALLOW_EXTERNAL_DOWNLOAD"],
			["Order",  0, "ORDER_ENABLE_NEGATIVE"],
			["Order", 11, "THIRDPARTY_PROPAGATE_EXTRAFIELDS_TO_ORDER"],
			["Order",  0, "WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER "]
		];
	}

	// PDF Options: 43 Entries
	private function getPdfOption()
	{
		return [
			["PDF Options",  0, "MAIN_ADD_PDF_BACKGROUND"],
			["PDF Options",  0, "MAIN_DISABLE_FORCE_SAVEAS"],
			["PDF Options",  0, "MAIN_DISABLE_PDF_AUTOUPDATE"],
			["PDF Options",  0, "MAIN_DISABLE_PDF_COMPRESSION"],
			["PDF Options",  0, "MAIN_DOCUMENTS_LOGO_HEIGHT"],
			["PDF Options",  0, "MAIN_DOCUMENTS_WITH_PICTURE_WIDTH"],
			["PDF Options",  0, "MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS"],
			["PDF Options",  0, "MAIN_GENERATE_PROPOSALS_WITH_PICTURE"],
			["PDF Options",  0, "MAIN_GENERATE_INVOICES_WITH_PICTURE"],
			["PDF Options",  0, "MAIN_GENERATE_SUPPLIER_PROPOSAL_WITH_PICTURE"],
			["PDF Options",  0, "MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT"],
			["PDF Options",  0, "MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN"],
			["PDF Options",  0, "MAIN_INVERT_SENDER_RECIPIENT"],
			["PDF Options",  0, "MAIN_ODT_AS_PDF"],
			["PDF Options",  0, "MAIN_ODT_AS_PDF_DEL_SOURCE"],
			["PDF Options",  0, "MAIN_PDF_FORCE_FONT"],
			["PDF Options", 11, "MAIN_PDF_FORCE_FONT_SIZE"],
			["PDF Options",  0, "MAIN_PDF_FREETEXT_HEIGHT"],
			["PDF Options",  0, "MAIN_PDF_TITLE_BACKGROUND_COLOR"],
			["PDF Options",  0, "MAIN_PDF_USE_LARGE_LOGO"],
			["PDF Options",  0, "MAIN_USE_BACKGROUND_ON_PDF"],
			["PDF Options",  0, "MAIN_USE_COMPANY_NAME_OF_CONTACT"],
			["PDF Options",  0, "MAIN_PDF_ADDALSOTARGETDETAILS"],
			["PDF Options",  8, "MAIN_TVAINTRA_IN_SOURCE_ADDRESS"],
			["PDF Options",  8, "MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING"],
			["PDF Options",  8, "MAIN_PROFID1_IN_SOURCE_ADDRESS"],
			["PDF Options",  8, "MAIN_PROFID2_IN_SOURCE_ADDRESS"],
			["PDF Options",  8, "MAIN_PROFID3_IN_SOURCE_ADDRESS"],
			["PDF Options",  8, "MAIN_PROFID4_IN_SOURCE_ADDRESS"],
			["PDF Options",  8, "MAIN_PROFID5_IN_SOURCE_ADDRESS"],
			["PDF Options",  8, "MAIN_PROFID6_IN_SOURCE_ADDRESS"],
			["PDF Options",  0, "PDF_BANK_HIDE_NUMBER_SHOW_ONLY_BICIBAN"],
			["PDF Options",  0, "PDF_BOLD_PRODUCT_REF_AND_PERIOD"],
			["PDF Options",  0, "PDF_HIDE_PRODUCT_REF_IN_SUPPLIER_LINES"],
			["PDF Options",  0, "PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME"],
			["PDF Options",  0, "PDF_SECURITY_ENCRYPTION"],
			["PDF Options",  0, "PDF_SECURITY_ENCRYPTION_RIGHTS"],
			["PDF Options",  0, "PDF_SECURITY_ENCRYPTION_USERPASS"],
			["PDF Options",  0, "PDF_SECURITY_ENCRYPTION_OWNERPASS"],
			["PDF Options",  0, "PDF_SECURITY_ENCRYPTION_PUBKEYS"],
			["PDF Options",  0, "PDF_SHOW_PROJECT"],
			["PDF Options",  0, "PDF_USE_1A"],
			["PDF Options",  0, "PDF_USE_ALSO_LANGUAGE_CODE"],
		];
	}

	// Point of Sale (POS): 3 Entires
	private function getPointOfSale()
	{
		return [
			["POS",  0, "CASHDESK_SHOW_KEYPAD"],
			["POS",  0, "POS_ADDON"],
			["POS",  0, "TAKEPOS_ENABLE_SUMUP"]
		];
	}

	// Products Module: 11 Entires
	private function getProductsModule()
	{
		return [
			["Products",  0, "CATEGORY_GRAPHSTATS_ON_PRODUCTS"],
			["Products",  0, "MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE"],
			["Products",  0, "PRODUCT_ADD_TYPE_IN_DOCUMENTS"],
			["Products",  0, "PRODUCT_DONOTSEARCH_ANYWHERE"],
			["Products",  4, "MAIN_DIRECT_STATUS_UPDATE"],
			["Products",  8, "MAIN_SEARCH_PRODUCT_BY_FOURN_REF"],
			["Products",  8, "MAIN_DISABLE_FREE_LINES"],
			["Products",  5, "MAIN_SHOW_PRODUCT_ACTIVITY_TRIM"],
			["Products",  0, "PRODUIT_DESC_IN_LIST"],
			["Products",  0, "PRODUCT_DISABLE_PROPAGATE_CUSTOMER_PRICES_ON_CHILD_COMPANIES"],
			["Products",  0, "PRODUCT_DISABLE_SIZE"],
			["Products",  4, "PRODUCT_DISABLE_LENGTH"],
			["Products",  4, "PRODUCT_DISABLE_SURFACE"],
			["Products",  0, "CATEGORY_GRAPHSTATS_ON_PRODUCTS"],
			["Products",  0, "PRODUCT_DISABLE_VOLUME"],
			["Products",  0, "PRODUCT_DISABLE_CUSTOM_INFO"],
			["Products",  0, "PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL"],
			["Products",  0, "PRODUIT_CUSTOMER_PRICES_BY_QTY"],
			["Products",  0, "PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES"],
			["Products",  0, "PRODUCT_MAX_LENGTH_COMBO"],
			["Products",  0, "PRODUCT_MAX_VISIBLE_PHOTO"],
			["Products",  0, "PRODUIT_PDF_MERGE_PROPAL"],
			["Products",  0, "PRODUCT_USE_OLD_PATH_FOR_PHOTO"],
			["Products",  0, "PRODUCT_USE_UNITS"],
			["Products", 10, "RESOURCE_ON_PRODUCTS_RESOURCE_ON_SERVICES"],
			["Products", 12, "PRODUCT_SHOW_ORIGIN_IN_COMBO"]
		];
	}

	// Projects Module: 11 Entires
	private function getProjectsModule()
	{
		return [
			["Projects",  8, "PROJECT_DISABLE_UNLINK_FROM_OVERVIEW"],
			["Projects",  4, "PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS"],
			["Projects",  8, "PROJECT_TIME_ON_ALL_TASKS_MY_PROJECTS"],
			["Projects",  8, "PROJECT_SHOW_REF_INTO_LISTS"],
			["Projects",  8, "PROJECT_HIDE_UNSELECTABLES"],
			["Projects",  8, "PROJECT_HIDE_TASKS"],
			["Projects",  8, "PROJECT_LIST_SHOW_STARTDATE"],
			["Projects",  8, "PROJECT_LINK_ON_OVERWIEW_DISABLED"],
			["Projects",  8, "PROJECT_CREATE_ON_OVERVIEW_DISABLED"],
			["Projects",  8, "PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY"],
			["Projects",  8, "PROJECT_ALLOW_COMMENT_ON_TASK"]
		];
	}

	// Proposals Module: 10 Entires
	private function getProposalsModule()
	{
		return [
			["Proposals",  0, "PROPAL_CLONE_ON_CREATE_PAGE"],
			["Proposals",  0, "MAIN_PROPAL_CHOOSE_ODT_DOCUMENT"],
			["Proposals",  0, "MAIN_GENERATE_PROPOSALS_WITH_PICTURE"],
			["Proposals",  0, "PROPAL_DISABLE_SIGNATURE"],
			["Proposals",  0, "WORKFLOW_PROPAL_CAN_CLASSIFY_BILLED_WITHOUT_INVOICES"],
			["Proposals",  0, "PRODUIT_PDF_MERGE_PROPAL"],
			["Proposals",  7, "PROPOSAL_ALLOW_EXTERNAL_DOWNLOAD"],
			["Proposals",  8, "MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING"],
			["Proposals",  0, "PROPOSAL_AUTO_ADD_AUTHOR_AS_CONTACT"],
			["Proposals",  7, "PROPALE_PDF_HIDE_PAYMENTTERMMODE"]
		];
	}

	// Services Module: 1 Entry
	private function getServicesModule()
	{
		return [
			["Services",  0, "SERVICE_ARE_ECOMMERCE_200238EC"]
		];
	}

	// Shipments: 2 Entires
	private function getShipments()
	{
		return [
			["Shipments",  0, "STOCK_WAREHOUSE_NOT_REQUIRED_FOR_SHIPMENTS"],
			["Shipments",  0, "SHIPMENT_GETS_ALL_ORDER_PRODUCTS"]
		];
	}

	// Stocks: 5 Entires
	private function getStocks()
	{
		return [
			["Stocks",  0, "CASHDESK_FORCE_STOCK_ON_BILL"],
			["Stocks",  0, "SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED"],
			["Stocks",  0, "STOCK_SUPPORTS_SERVICES"],
			["Stocks", 11, "USER_DEFAULT_WAREHOUSE"],
			["Stocks",  0, "WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER"]
		];
	}

	// Suppliers Module: 11 Entires
	private function getSuppliersModule()
	{
		return [
			["Suppliers",  4, "FOURN_PRODUCT_AVAILABILITY"],
			["Suppliers",  0, "RELOAD_PAGE_ON_SUPPLIER_CHANGE"],
			["Suppliers",  0, "SUPPLIER_ORDER_AUTOADD_USER_CONTACT"],
			["Suppliers",  0, "SUPPLIER_ORDER_DEFAULT_PAYMENT_MODE_ID"],
			["Suppliers",  4, "SUPPLIER_ORDER_USE_DISPATCH_STATUS"],
			["Suppliers",  0, "SUPPLIER_ORDER_NO_DIRECT_APPROVE"],
			["Suppliers",  6, "SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY"],
			["Suppliers",  6, "SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT"],
			["Suppliers",  6, "SUPPLIER_PROPOSAL_WITH_PREDEFINED_PRICES_ONLY"],
			["Suppliers", 10, "DISPLAY_DISCOUNTED_SUPPLIER_PRICE"],
			["Suppliers", 10, "MAIN_CAN_EDIT_SUPPLIER_ON_SUPPLIER_ORDER"]
		];
	}

	// Third Parties Module: 19 Entires
	private function getThirdPartiesModule()
	{
		return [
			["Third Parties",  8, "COMPANY_AQUARIUM_CLEAN_REGEX"],
			["Third Parties",  0, "SOCIETE_DISABLE_CUSTOMERS"],
			["Third Parties",  0, "SOCIETE_DISABLE_PROSPECTS"],
			["Third Parties",  0, "SOCIETE_DISABLE_STATE"],
			["Third Parties",  0, "SOCIETE_SORT_ON_TYPEENT"],
			["Third Parties",  0, "SOCIETE_ASK_FOR_SHIPPING_METHOD"],
			["Third Parties",  0, "SOCIETE_ADD_REF_IN_LIST"],
			["Third Parties",  0, "THIRDPARTY_CAN_HAVE_CATEGORY_EVEN_IF_NOT_CUSTOMER_PROSPECT_SUPPLIER"],
			["Third Parties",  0, "THIRDPARTY_DEFAULT_USEVAT"],
			["Third Parties",  0, "THIRDPARTY_DEFAULT_USELOCALTAX1"],
			["Third Parties",  0, "THIRDPARTY_DEFAULT_USELOCALTAX2"],
			["Third Parties",  0, "THIRDPARTY_NOTCUSTOMERPROSPECT_BY_DEFAULT"],
			["Third Parties",  0, "THIRDPARTY_NOTSUPPLIER_BY_DEFAULT"],
			["Third Parties",  0, "THIRDPARTY_INCLUDE_PARENT_IN_LINKTO"],
			["Third Parties",  0, "THIRDPARTY_INCLUDE_PROJECT_THIRDPARY_IN_LINKTO"],
			["Third Parties",  0, "THIRDPARTY_LOGO_ALLOW_EXTERNAL_DOWNLOAD"],
			["Third Parties",  0, "THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION"],
			["Third Parties",  0, "THIRDPARTY_QUICKSEARCH_ON_FIELDS"],
			["Third Parties",  0, "MEMBER_CAN_CONVERT_CUSTOMERS_TO_MEMBERS"]
		];
	}

	// VAT Report: 1 Entry
	private function getVATReport()
	{
		return [
			["VAT Report",  8, "MAIN_INCLUDE_ZERO_VAT_IN_REPORTS"]
		];
	}

	// WYSIWYG Editor: 1 Entry
	private function getWysiwgEditor()
	{
		return [
			["WYSIWYG Editor",  0, "FCKEDITOR_ENABLE_DETAILS_FULL"]
		];
	}

	// STRIPE Module: 1 Entry
	private function getStripeModule()
	{
		return [
			["STRIPE",  0, "STRIPE_FORCE_VERSION"]
		];
	}

	// ClickToDial Module: 1 Entry
	private function getClickToDialModule()
	{
		return [
			["ClickToDial",  0, "CLICKTODIAL_FORCENEWTARGET"]
		];
	}

	// For SaaS / Cloud hosting integrators: 10 Entries
	private function getSaasCloudHosting()
	{
		return [
			["Cloud hosting",  0, "CRON_DISABLE_KEY_CHANGE"],
			["Cloud hosting",  0, "CRON_DISABLE_TUTORIAL_CRON "],
			["Cloud hosting",  0, "MAILING_LIMIT_WARNING_PHPMAIL"],
			["Cloud hosting",  0, "MAIN_EXTERNAL_SMTP_CLIENT_IP_ADDRESS"],
			["Cloud hosting",  0, "MAIN_EXTERNAL_SMTP_SPF_STRING_TO_ADD"],
			["Cloud hosting",  0, "MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE"],
			["Cloud hosting",  0, "MAIN_FILECHECK_LOCAL_SUFFIX"],
			["Cloud hosting",  0, "MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING"],
			["Cloud hosting",  0, "SYSLOG_DISABLE_LOGHANDLER_SYSLOG"],
			["Cloud hosting",  0, "WEBSITE_REPLACE_INFO_ABOUT_USAGE_WITH_WEBSERVER"]
		];
	}
}
