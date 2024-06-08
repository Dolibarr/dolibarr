<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * You can add one or multiple tax IDs to a <a
 * href="https://stripe.com/docs/api/customers">customer</a>. A customer's tax IDs
 * are displayed on invoices and credit notes issued for the customer.
 *
 * Related guide: <a href="https://stripe.com/docs/billing/taxes/tax-ids">Customer
 * Tax Identification Numbers</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|string $country Two-letter ISO code representing the country of the tax ID.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string|\Stripe\Customer $customer ID of the customer.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property string $type Type of the tax ID, one of <code>ae_trn</code>, <code>au_abn</code>, <code>au_arn</code>, <code>bg_uic</code>, <code>br_cnpj</code>, <code>br_cpf</code>, <code>ca_bn</code>, <code>ca_gst_hst</code>, <code>ca_pst_bc</code>, <code>ca_pst_mb</code>, <code>ca_pst_sk</code>, <code>ca_qst</code>, <code>ch_vat</code>, <code>cl_tin</code>, <code>eg_tin</code>, <code>es_cif</code>, <code>eu_oss_vat</code>, <code>eu_vat</code>, <code>gb_vat</code>, <code>ge_vat</code>, <code>hk_br</code>, <code>hu_tin</code>, <code>id_npwp</code>, <code>il_vat</code>, <code>in_gst</code>, <code>is_vat</code>, <code>jp_cn</code>, <code>jp_rn</code>, <code>jp_trn</code>, <code>ke_pin</code>, <code>kr_brn</code>, <code>li_uid</code>, <code>mx_rfc</code>, <code>my_frp</code>, <code>my_itn</code>, <code>my_sst</code>, <code>no_vat</code>, <code>nz_gst</code>, <code>ph_tin</code>, <code>ru_inn</code>, <code>ru_kpp</code>, <code>sa_vat</code>, <code>sg_gst</code>, <code>sg_uen</code>, <code>si_tin</code>, <code>th_vat</code>, <code>tr_tin</code>, <code>tw_vat</code>, <code>ua_vat</code>, <code>us_ein</code>, or <code>za_vat</code>. Note that some legacy tax IDs have type <code>unknown</code>
 * @property string $value Value of the tax ID.
 * @property null|\Stripe\StripeObject $verification Tax ID verification information.
 */
class TaxId extends ApiResource
{
    const OBJECT_NAME = 'tax_id';

    use ApiOperations\Delete;

    const TYPE_AE_TRN = 'ae_trn';
    const TYPE_AU_ABN = 'au_abn';
    const TYPE_AU_ARN = 'au_arn';
    const TYPE_BG_UIC = 'bg_uic';
    const TYPE_BR_CNPJ = 'br_cnpj';
    const TYPE_BR_CPF = 'br_cpf';
    const TYPE_CA_BN = 'ca_bn';
    const TYPE_CA_GST_HST = 'ca_gst_hst';
    const TYPE_CA_PST_BC = 'ca_pst_bc';
    const TYPE_CA_PST_MB = 'ca_pst_mb';
    const TYPE_CA_PST_SK = 'ca_pst_sk';
    const TYPE_CA_QST = 'ca_qst';
    const TYPE_CH_VAT = 'ch_vat';
    const TYPE_CL_TIN = 'cl_tin';
    const TYPE_EG_TIN = 'eg_tin';
    const TYPE_ES_CIF = 'es_cif';
    const TYPE_EU_OSS_VAT = 'eu_oss_vat';
    const TYPE_EU_VAT = 'eu_vat';
    const TYPE_GB_VAT = 'gb_vat';
    const TYPE_GE_VAT = 'ge_vat';
    const TYPE_HK_BR = 'hk_br';
    const TYPE_HU_TIN = 'hu_tin';
    const TYPE_ID_NPWP = 'id_npwp';
    const TYPE_IL_VAT = 'il_vat';
    const TYPE_IN_GST = 'in_gst';
    const TYPE_IS_VAT = 'is_vat';
    const TYPE_JP_CN = 'jp_cn';
    const TYPE_JP_RN = 'jp_rn';
    const TYPE_JP_TRN = 'jp_trn';
    const TYPE_KE_PIN = 'ke_pin';
    const TYPE_KR_BRN = 'kr_brn';
    const TYPE_LI_UID = 'li_uid';
    const TYPE_MX_RFC = 'mx_rfc';
    const TYPE_MY_FRP = 'my_frp';
    const TYPE_MY_ITN = 'my_itn';
    const TYPE_MY_SST = 'my_sst';
    const TYPE_NO_VAT = 'no_vat';
    const TYPE_NZ_GST = 'nz_gst';
    const TYPE_PH_TIN = 'ph_tin';
    const TYPE_RU_INN = 'ru_inn';
    const TYPE_RU_KPP = 'ru_kpp';
    const TYPE_SA_VAT = 'sa_vat';
    const TYPE_SG_GST = 'sg_gst';
    const TYPE_SG_UEN = 'sg_uen';
    const TYPE_SI_TIN = 'si_tin';
    const TYPE_TH_VAT = 'th_vat';
    const TYPE_TR_TIN = 'tr_tin';
    const TYPE_TW_VAT = 'tw_vat';
    const TYPE_UA_VAT = 'ua_vat';
    const TYPE_UNKNOWN = 'unknown';
    const TYPE_US_EIN = 'us_ein';
    const TYPE_ZA_VAT = 'za_vat';

    const VERIFICATION_STATUS_PENDING = 'pending';
    const VERIFICATION_STATUS_UNAVAILABLE = 'unavailable';
    const VERIFICATION_STATUS_UNVERIFIED = 'unverified';
    const VERIFICATION_STATUS_VERIFIED = 'verified';

    /**
     * @return string the API URL for this tax id
     */
    public function instanceUrl()
    {
        $id = $this['id'];
        $customer = $this['customer'];
        if (!$id) {
            throw new Exception\UnexpectedValueException(
                "Could not determine which URL to request: class instance has invalid ID: {$id}"
            );
        }
        $id = Util\Util::utf8($id);
        $customer = Util\Util::utf8($customer);

        $base = Customer::classUrl();
        $customerExtn = \urlencode($customer);
        $extn = \urlencode($id);

        return "{$base}/{$customerExtn}/tax_ids/{$extn}";
    }

    /**
     * @param array|string $_id
     * @param null|array|string $_opts
     *
     * @throws \Stripe\Exception\BadMethodCallException
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = 'Tax IDs cannot be retrieved without a customer ID. Retrieve ' .
               "a tax ID using `Customer::retrieveTaxId('customer_id', " .
               "'tax_id_id')`.";

        throw new Exception\BadMethodCallException($msg);
    }
}
