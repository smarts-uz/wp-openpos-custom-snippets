<?php
/**
 * Stripe Tax Plugin Helper service
 * Contains currencies, countries, for origin addresses, tax registrations, tax calculations and methods to retrieve it
 *
 * @package Stripe\StripeTaxForWooCommerce\Stripe
 */

namespace Stripe\StripeTaxForWooCommerce\Stripe;

defined( 'ABSPATH' ) || exit;

use Exception;

/**
 * Stripe Tax Plugin Helper service
 */
class StripeTaxPluginHelper {

	/**
	 * Array keys, used in "locks" logics
	 *
	 * @see TaxRegistrations::get_locks()
	 */
	public const LOCK_BLOOMINGTON          = 'bloomington';
	public const LOCK_CA_PROVINCES         = 'ca_provinces';
	public const LOCK_CHICAGO_AMUSEMENT    = 'chicago_amusement';
	public const LOCK_CHICAGO_LEASE        = 'chicago_lease';
	public const LOCK_COUNTRIES            = 'countries';
	public const LOCK_EAST_DUNDEE          = 'east_dundee';
	public const LOCK_EVANSTON             = 'evanston';
	public const LOCK_LOCAL_COMMUNICATIONS = 'local_communications';
	public const LOCK_IOSS                 = 'ioss';
	public const LOCK_OSS_UNION            = 'oss_union';
	public const LOCK_OSS_NON_UNION        = 'oss_non_union';
	public const LOCK_SCHILLER_PARK        = 'schiller_park';
	public const LOCK_US_STATES            = 'us_states';

	/**
	 * Array keys, used to display Tax Registrations in List Table
	 */
	public const DISPLAY_LOCAL_AMUSEMENT_TAX_PREFIX = 'local_amusement_tax_';
	public const DISPLAY_LOCAL_LEASE_TAX_PREFIX     = 'local_lease_tax_';
	public const DISPLAY_STATE_COMMUNICATIONS_TAX   = 'state_communications_tax';

	/**
	 * FIPS codes
	 */
	public const FIPS_CHICAGO       = '14000';
	public const FIPS_BLOOMINGTON   = '06613';
	public const FIPS_EAST_DUNDEE   = '21696';
	public const FIPS_EVANSTON      = '24582';
	public const FIPS_SCHILLER_PARK = '68081';

	/**
	 * Supported presentment currencies for all countries, but without currencies for the United Arab Emirates
	 *
	 * @see https://stripe.com/docs/currencies#presentment-currencies
	 * @var array
	 */
	public const CURRENCY_INTERSECT = array(
		'usd' => '',
		'aed' => '',
		'afn' => '',
		'all' => '',
		'amd' => '',
		'ang' => '',
		'aoa' => '',
		'ars' => '',
		'aud' => '',
		'awg' => '',
		'azn' => '',
		'bam' => '',
		'bbd' => '',
		'bdt' => '',
		'bgn' => '',
		'bif' => '',
		'bmd' => '',
		'bnd' => '',
		'bob' => '',
		'brl' => '',
		'bsd' => '',
		'bwp' => '',
		'byn' => '',
		'bzd' => '',
		'cad' => '',
		'cdf' => '',
		'chf' => '',
		'clp' => '',
		'cny' => '',
		'cop' => '',
		'crc' => '',
		'cve' => '',
		'czk' => '',
		'djf' => '',
		'dkk' => '',
		'dop' => '',
		'dzd' => '',
		'egp' => '',
		'etb' => '',
		'eur' => '',
		'fjd' => '',
		'fkp' => '',
		'gbp' => '',
		'gel' => '',
		'gip' => '',
		'gmd' => '',
		'gnf' => '',
		'gtq' => '',
		'gyd' => '',
		'hkd' => '',
		'hnl' => '',
		'hrk' => '',
		'htg' => '',
		'huf' => '',
		'idr' => '',
		'ils' => '',
		'inr' => '',
		'isk' => '',
		'jmd' => '',
		'jpy' => '',
		'kes' => '',
		'kgs' => '',
		'khr' => '',
		'kmf' => '',
		'krw' => '',
		'kyd' => '',
		'kzt' => '',
		'lak' => '',
		'lbp' => '',
		'lkr' => '',
		'lrd' => '',
		'lsl' => '',
		'mad' => '',
		'mdl' => '',
		'mga' => '',
		'mkd' => '',
		'mmk' => '',
		'mnt' => '',
		'mop' => '',
		'mur' => '',
		'mvr' => '',
		'mwk' => '',
		'mxn' => '',
		'myr' => '',
		'mzn' => '',
		'nad' => '',
		'ngn' => '',
		'nio' => '',
		'nok' => '',
		'npr' => '',
		'nzd' => '',
		'pab' => '',
		'pen' => '',
		'pgk' => '',
		'php' => '',
		'pkr' => '',
		'pln' => '',
		'pyg' => '',
		'qar' => '',
		'ron' => '',
		'rsd' => '',
		'rub' => '',
		'rwf' => '',
		'sar' => '',
		'sbd' => '',
		'scr' => '',
		'sek' => '',
		'sgd' => '',
		'shp' => '',
		'sle' => '',
		'sos' => '',
		'srd' => '',
		'std' => '',
		'szl' => '',
		'thb' => '',
		'tjs' => '',
		'top' => '',
		'try' => '',
		'ttd' => '',
		'twd' => '',
		'tzs' => '',
		'uah' => '',
		'ugx' => '',
		'uyu' => '',
		'uzs' => '',
		'vnd' => '',
		'vuv' => '',
		'wst' => '',
		'xaf' => '',
		'xcd' => '',
		'xof' => '',
		'xpf' => '',
		'yer' => '',
		'zar' => '',
		'zmw' => '',
	);
	/**
	 * Supported presentment currencies, added for the United Arab Emirates
	 *
	 * @see https://stripe.com/docs/currencies?presentment-currency=AE#zero-decimal
	 * @var array
	 */
	public const CURRENCIES_ADDED_FOR_AE = array(
		'bhd' => '',
		'jod' => '',
		'kwd' => '',
		'omr' => '',
		'tnd' => '',
	);
	/**
	 * Zero-decimal currencies
	 *
	 * @see https://stripe.com/docs/currencies#zero-decimal
	 * @var array
	 */
	public const CURRENCIES_ZERO_DECIMAL = array(
		'bif' => '',
		'clp' => '',
		'djf' => '',
		'gnf' => '',
		'jpy' => '',
		'kmf' => '',
		'krw' => '',
		'mga' => '',
		'pyg' => '',
		'rwf' => '',
		'ugx' => '',
		'vnd' => '',
		'vuv' => '',
		'xaf' => '',
		'xof' => '',
		'xpf' => '',
	);
	/**
	 * Three-decimal currencies
	 *
	 * @see https://stripe.com/docs/currencies#three-decimal
	 * @var array
	 */
	public const CURRENCIES_THREE_DECIMAL = array(
		'bhd' => '',
		'jod' => '',
		'kwd' => '',
		'omr' => '',
		'tnd' => '',
	);
	/**
	 * Supported presentment currencies for each country
	 *
	 * @see https://stripe.com/docs/currencies#presentment-currencies
	 * @var array
	 */
	public const CURRENCIES_PER_SUPPORTED_COUNTRY = array(
		'AE' => self::CURRENCY_INTERSECT + self::CURRENCIES_ADDED_FOR_AE,
		'AT' => self::CURRENCY_INTERSECT,
		'AU' => self::CURRENCY_INTERSECT,
		'BE' => self::CURRENCY_INTERSECT,
		'BG' => self::CURRENCY_INTERSECT,
		'BR' => self::CURRENCY_INTERSECT,
		'CA' => self::CURRENCY_INTERSECT,
		'CH' => self::CURRENCY_INTERSECT,
		'CY' => self::CURRENCY_INTERSECT,
		'CZ' => self::CURRENCY_INTERSECT,
		'DE' => self::CURRENCY_INTERSECT,
		'DK' => self::CURRENCY_INTERSECT,
		'EE' => self::CURRENCY_INTERSECT,
		'ES' => self::CURRENCY_INTERSECT,
		'FI' => self::CURRENCY_INTERSECT,
		'FR' => self::CURRENCY_INTERSECT,
		'GB' => self::CURRENCY_INTERSECT,
		'GI' => self::CURRENCY_INTERSECT,
		'GR' => self::CURRENCY_INTERSECT,
		'HK' => self::CURRENCY_INTERSECT,
		'HR' => self::CURRENCY_INTERSECT,
		'HU' => self::CURRENCY_INTERSECT,
		'IE' => self::CURRENCY_INTERSECT,
		'IN' => self::CURRENCY_INTERSECT,
		'IT' => self::CURRENCY_INTERSECT,
		'JP' => self::CURRENCY_INTERSECT,
		'LI' => self::CURRENCY_INTERSECT,
		'LT' => self::CURRENCY_INTERSECT,
		'LU' => self::CURRENCY_INTERSECT,
		'LV' => self::CURRENCY_INTERSECT,
		'MT' => self::CURRENCY_INTERSECT,
		'MX' => self::CURRENCY_INTERSECT,
		'MY' => self::CURRENCY_INTERSECT,
		'NL' => self::CURRENCY_INTERSECT,
		'NO' => self::CURRENCY_INTERSECT,
		'NZ' => self::CURRENCY_INTERSECT,
		'PL' => self::CURRENCY_INTERSECT,
		'PT' => self::CURRENCY_INTERSECT,
		'RO' => self::CURRENCY_INTERSECT,
		'SE' => self::CURRENCY_INTERSECT,
		'SG' => self::CURRENCY_INTERSECT,
		'SI' => self::CURRENCY_INTERSECT,
		'SK' => self::CURRENCY_INTERSECT,
		'TH' => self::CURRENCY_INTERSECT,
		'US' => self::CURRENCY_INTERSECT,
	);
	/**
	 * List of all supported currencies in case we need to check only currency without country in which it supported.
	 *
	 * @see https://stripe.com/docs/currencies#presentment-currencies
	 * @var array
	 */
	public const CURRENCIES_ALL_SUPPORTED = self::CURRENCY_INTERSECT + self::CURRENCIES_ADDED_FOR_AE;
	/**
	 * List of currencies to be applied special rules before tax calculation API call
	 *
	 * @see https://stripe.com/docs/currencies#special-cases
	 * @var array
	 */
	public const CURRENCIES_SPECIAL_CASE_ROUND_AND_MULTIPLY_100 = array(
		'isk' => '',
		'huf' => '',
		'twd' => '',
		'ugx' => '',
	);
	/**
	 * List of supported Stripe Customer Tax ID's.
	 * Current list of ID's prepended with country code, that should be removed before API call.
	 * This is made so, for duplication "eu_vat" for different european countries.
	 *
	 * @see https://stripe.com/docs/billing/customer/tax-ids#supported-tax-id
	 */
	public const TAX_IDS = array(
		'AD_ad_nrt'     => 'Andorra - Andorran NRT number',
		'AR_ar_cuit'    => 'Argentina - Argentinian tax ID number',
		'AU_au_abn'     => 'Australia - Australian Business Number (AU ABN)',
		'AU_au_arn'     => 'Australia - Australian Taxation Office Reference Number',
		'AT_eu_vat'     => 'Austria - European VAT number', // Austria.
		'BE_eu_vat'     => 'Belgium - European VAT number', // Belgium.
		'BO_bo_tin'     => 'Bolivia - Bolivian tax ID',
		'BR_br_cnpj'    => 'Brazil - Brazilian CNPJ number',
		'BR_br_cpf'     => 'Brazil - Brazilian CPF number',
		'BG_bg_uic'     => 'Bulgaria - Bulgaria Unified Identification Code',
		'BG_eu_vat'     => 'Bulgaria - European VAT number', // Bulgaria.
		'CA_ca_bn'      => 'Canada - Canadian BN',
		'CA_ca_gst_hst' => 'Canada - Canadian GST/HST number',
		'CA_ca_pst_bc'  => 'Canada - Canadian PST number (British Columbia)',
		'CA_ca_pst_mb'  => 'Canada - Canadian PST number (Manitoba)',
		'CA_ca_pst_sk'  => 'Canada - Canadian PST number (Saskatchewan)',
		'CA_ca_qst'     => 'Canada - Canadian QST number (Québec)',
		'CL_cl_tin'     => 'Chile - Chilean TIN',
		'CN_cn_tin'     => 'China - Chinese tax ID',
		'CO_co_nit'     => 'Colombia - Colombian NIT number',
		'CR_cr_tin'     => 'Costa Rica - Costa Rican tax ID',
		'HR_eu_vat'     => 'Croatia - European VAT number', // Croatia.
		'CY_eu_vat'     => 'Cyprus - European VAT number', // Cyprus.
		'CZ_eu_vat'     => 'Czech Republic - European VAT number', // Czech Republic.
		'DK_eu_vat'     => 'Denmark - European VAT number', // Denmark.
		'DO_do_rcn'     => 'Dominican Republic - Dominican RCN number',
		'EC_ec_ruc'     => 'Ecuador - Ecuadorian RUC number',
		'EG_eg_tin'     => 'Egypt - Egyptian Tax Identification Number',
		'SV_sv_nit'     => 'El Salvador - El Salvadorian NIT number',
		'EE_eu_vat'     => 'Estonia - European VAT number', // Estonia.
		'EU_eu_oss_vat' => 'EU - European One Stop Shop VAT number for non-Union scheme', // EU.
		'FI_eu_vat'     => 'Finland - European VAT number', // Finland.
		'FR_eu_vat'     => 'France - European VAT number', // France.
		'GE_ge_vat'     => 'Georgia - Georgian VAT',
		'DE_eu_vat'     => 'Germany - European VAT number', // Germany.
		'GR_eu_vat'     => 'Greece - European VAT number', // Greece.
		'HK_hk_br'      => 'Hong Kong - Hong Kong BR number',
		'HU_eu_vat'     => 'Hungary - European VAT number', // Hungary.
		'HU_hu_tin'     => 'Hungary - Hungary tax number (adószám)',
		'IS_is_vat'     => 'Iceland - Icelandic VAT',
		'IN_in_gst'     => 'India - Indian GST number',
		'ID_id_npwp'    => 'Indonesia - Indonesian NPWP number',
		'IE_eu_vat'     => 'Ireland - European VAT number', // Ireland.
		'IL_il_vat'     => 'Israel - Israel VAT',
		'IT_eu_vat'     => 'Italy - European VAT number', // Italy.
		'JP_jp_cn'      => 'Japan - Japanese Corporate Number (*Hōjin Bangō*)',
		'JP_jp_rn'      => 'Japan - Japanese Registered Foreign Businesses\' Registration Number (*Tōroku Kokugai Jigyōsha no Tōroku Bangō*)',
		'JP_jp_trn'     => 'Japan - Japanese Tax Registration Number (*Tōroku Bangō*)',
		'KE_ke_pin'     => 'Kenya - Kenya Revenue Authority Personal Identification Number',
		'LV_eu_vat'     => 'Latvia - European VAT number', // Latvia.
		'LI_li_uid'     => 'Liechtenstein - Liechtensteinian UID number',
		'LT_eu_vat'     => 'Lithuania - European VAT number', // Lithuania.
		'LU_eu_vat'     => 'Luxembourg - European VAT number', // Luxembourg.
		'MY_my_frp'     => 'Malaysia - Malaysian FRP number',
		'MY_my_itn'     => 'Malaysia - Malaysian ITN',
		'MY_my_sst'     => 'Malaysia - Malaysian SST number',
		'MT_eu_vat'     => 'Malta - European VAT number', // Malta.
		'MX_mx_rfc'     => 'Mexico - Mexican RFC number',
		'NL_eu_vat'     => 'Netherlands - European VAT number', // Netherlands.
		'NZ_nz_gst'     => 'New Zealand - New Zealand GST number',
		'NO_no_vat'     => 'Norway - Norwegian VAT number',
		'PE_pe_ruc'     => 'Peru - Peruvian RUC number',
		'PH_ph_tin'     => 'Philippines - Philippines Tax Identification Number',
		'PL_eu_vat'     => 'Poland - European VAT number', // Poland.
		'PT_eu_vat'     => 'Portugal - European VAT number', // Portugal.
		'RO_eu_vat'     => 'Romania - European VAT number', // Romania.
		'RO_ro_tin'     => 'Romania - Romanian tax ID number',
		'RU_ru_inn'     => 'Russia - Russian INN',
		'RU_ru_kpp'     => 'Russia - Russian KPP',
		'SA_sa_vat'     => 'Saudi Arabia - Saudi Arabia VAT',
		'RS_rs_pib'     => 'Serbia - Serbian PIB number',
		'SG_sg_gst'     => 'Singapore - Singaporean GST',
		'SG_sg_uen'     => 'Singapore - Singaporean UEN',
		'SK_eu_vat'     => 'Slovakia - European VAT number', // Slovakia.
		'SI_eu_vat'     => 'Slovenia - European VAT number', // Slovenia.
		'SI_si_tin'     => 'Slovenia - Slovenia tax number (davčna številka)',
		'ZA_za_vat'     => 'South Africa - South African VAT number',
		'KR_kr_brn'     => 'South Korea - Korean BRN',
		'ES_es_cif'     => 'Spain - Spanish NIF number (previously Spanish CIF number)',
		'ES_eu_vat'     => 'Spain - European VAT number', // Spain.
		'SE_eu_vat'     => 'Sweden - European VAT number', // Sweden.
		'CH_ch_vat'     => 'Switzerland - Switzerland VAT number',
		'TW_tw_vat'     => 'Taiwan - Taiwanese VAT',
		'TH_th_vat'     => 'Thailand - Thai VAT',
		'TR_tr_tin'     => 'Turkey - Turkish Tax Identification Number',
		'UA_ua_vat'     => 'Ukraine - Ukrainian VAT',
		'AE_ae_trn'     => 'United Arab Emirates - United Arab Emirates TRN',
		'GB_eu_vat'     => 'United Kingdom (UK) - Northern Ireland VAT number', // United Kingdom.
		'GB_gb_vat'     => 'United Kingdom (UK) - United Kingdom VAT number',
		'US_us_ein'     => 'United States (US) - United States EIN',
		'UY_uy_ruc'     => 'Uruguay - Uruguayan RUC number',
		'VE_ve_rif'     => 'Venezuela - Venezuelan RIF number',
		'VN_vn_tin'     => 'Vietnam - Vietnamese tax ID number',
	);
	/**
	 * Allowed countries for Tax Registration
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_ALLOWED_COUNTRIES = array(
		'AU' => '',
		'AT' => '',
		'BE' => '',
		'BG' => '',
		'CA' => '',
		'CL' => '',
		'CO' => '',
		'HR' => '',
		'CY' => '',
		'CZ' => '',
		'DK' => '',
		'EE' => '',
		'FI' => '',
		'FR' => '',
		'DE' => '',
		'GR' => '',
		'HU' => '',
		'IS' => '',
		'ID' => '',
		'IE' => '',
		'IT' => '',
		'JP' => '',
		'LV' => '',
		'LT' => '',
		'LU' => '',
		'MY' => '',
		'MT' => '',
		'MX' => '',
		'NL' => '',
		'NZ' => '',
		'NO' => '',
		'PL' => '',
		'PT' => '',
		'RO' => '',
		'SA' => '',
		'SG' => '',
		'SK' => '',
		'SI' => '',
		'ZA' => '',
		'KR' => '',
		'ES' => '',
		'SE' => '',
		'CH' => '',
		'TH' => '',
		'TR' => '',
		'AE' => '',
		'GB' => '',
		'US' => '',
		'VN' => '',
	);
	/**
	 * Allowed US states for Tax Registration
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_ALLOWED_US_STATES = array(
		'AL' => '',
		'AK' => '',
		'AZ' => '',
		'AR' => '',
		'CA' => '',
		'CO' => '',
		'CT' => '',
		'DE' => '', // Delaware doesn't have sales tax.
		'DC' => '',
		'FL' => '', // Communications Tax.
		'GA' => '',
		'HI' => '',
		'ID' => '',
		'IL' => '', // Lease tax, amusement tax.
		'IN' => '',
		'IA' => '',
		'KS' => '',
		'KY' => '', // Communication tax.
		'LA' => '',
		'ME' => '',
		'MD' => '',
		'MA' => '',
		'MI' => '',
		'MN' => '',
		'MS' => '',
		'MO' => '',
		'MT' => '', // Montana doesn't have sales tax.
		'NE' => '',
		'NV' => '',
		'NH' => '', // New Hampshire doesn't have sales tax.
		'NJ' => '',
		'NM' => '',
		'NY' => '',
		'NC' => '',
		'ND' => '',
		'OH' => '',
		'OK' => '',
		'OR' => '', // Oregon doesn't have sales tax.
		'PA' => '',
		'RI' => '',
		'SC' => '',
		'SD' => '',
		'TN' => '',
		'TX' => '',
		'UT' => '',
		'VT' => '',
		'VA' => '',
		'WA' => '',
		'WV' => '',
		'WI' => '',
		'WY' => '',
	);
	/**
	 * Allowed Canada provinces for Tax Registration
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_ALLOWED_CA_PROVINCES = array(
		'BC' => '',
		'MB' => '',
		'SK' => '',
		'QC' => '',
	);
	/**
	 * US states that has no sales tax
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_NO_SALES_TAX_US_STATES = array(
		'DE' => '',
		'MT' => '',
		'NH' => '',
		'OR' => '',
	);
	/**
	 * US states that has Local Communication tax
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_LOCAL_COMMUNICATIONS_TAX_US_STATES = array(
		'FL' => '',
		'KY' => '',
	);
	/**
	 * US states that has lease and amusement tax
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_LEASE_AND_AMUSEMENT_TAX_US_STATES = array(
		'IL' => '',
	);
	/**
	 * EU countries
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_EU_TAX_COUNTRIES = array(
		'AT' => '',
		'BE' => '',
		'BG' => '',
		'CY' => '',
		'CZ' => '',
		'DE' => '',
		'DK' => '',
		'EE' => '',
		'ES' => '',
		'FI' => '',
		'FR' => '',
		'GR' => '',
		'HR' => '',
		'HU' => '',
		'IE' => '',
		'IT' => '',
		'LT' => '',
		'LU' => '',
		'LV' => '',
		'MT' => '',
		'NL' => '',
		'PL' => '',
		'PT' => '',
		'RO' => '',
		'SE' => '',
		'SI' => '',
		'SK' => '',
	);
	/**
	 * Countries, that allowed only selling digital goods (simplified tax type)
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_DIGITAL_TAX_COUNTRIES = array(
		'CL' => '',
		'ID' => '',
		'CO' => '',
		'KR' => '',
		'MX' => '',
		'MY' => '',
		'SA' => '',
		'TH' => '',
		'TR' => '',
		'VN' => '',
	);
	/**
	 * Countries, that has standard sales taxes (standard tax type)
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_STANDARD_TAX_COUNTRIES = array(
		'AE' => '',
		'AU' => '',
		'CH' => '',
		'GB' => '',
		'IS' => '',
		'JP' => '',
		'NO' => '',
		'NZ' => '',
		'SG' => '',
		'ZA' => '',
	);
	/**
	 * Names for some US tax names, that needed to mention (because others are just Sales tax)
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_US_TAX_NAMES = array(
		self::DISPLAY_STATE_COMMUNICATIONS_TAX => 'State and Local Communications Tax',
		self::DISPLAY_LOCAL_LEASE_TAX_PREFIX . self::FIPS_CHICAGO => 'Chicago Lease Tax',
		self::DISPLAY_LOCAL_AMUSEMENT_TAX_PREFIX . self::FIPS_CHICAGO => 'Chicago Amusement Tax',
		self::DISPLAY_LOCAL_AMUSEMENT_TAX_PREFIX . self::FIPS_BLOOMINGTON => 'Bloomington Amusement Tax',
		self::DISPLAY_LOCAL_AMUSEMENT_TAX_PREFIX . self::FIPS_EAST_DUNDEE => 'East Dundee Amusement Tax',
		self::DISPLAY_LOCAL_AMUSEMENT_TAX_PREFIX . self::FIPS_EVANSTON => 'Evanston Amusement Tax',
		self::DISPLAY_LOCAL_AMUSEMENT_TAX_PREFIX . self::FIPS_SCHILLER_PARK => 'Schiller Park Streaming Services Surcharge',
		'state_sales_tax'                      => '',
	);
	/**
	 * Possible keys of US states tax with names (usually just Sales tax)
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_US_TAX_TYPES = array(
		'state_sales_tax'          => 'Sales tax',
		'state_communications_tax' => 'Sales tax',
		'local_lease_tax'          => 'Sales tax',
		'local_amusement_tax'      => 'Sales tax',
	);
	/**
	 * Possible US states tax types
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_US_TYPE_OF_REGISTRATION = array(
		'state_sales_tax'          => 'Standard',
		'state_communications_tax' => 'Communications Tax',
		'local_lease_tax'          => 'Lease Tax',
		'local_amusement_tax'      => 'Amusement Tax',
	);
	/**
	 * Names of Canada taxes
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_CA_TAX_NAMES = array(
		'standard'             => 'GST/HST',
		'simplified'           => 'simplified GST/HST',
		'province_standard_BC' => 'PST',
		'province_standard_MB' => 'PST',
		'province_standard_SK' => 'PST',
		'province_standard_QC' => 'QST',
	);
	/**
	 * Types of taxes in Canada with names
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_CA_TAX_TYPES = array(
		'standard'          => 'GST/HST',
		'simplified'        => 'GST/HST',
		'province_standard' => 'GST/HST',
	);
	/**
	 * Types of registration in Canada
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_CA_TYPE_OF_REGISTRATION = array(
		'standard'          => 'Standard',
		'simplified'        => 'Simplified GST/HST',
		'province_standard' => 'Standard',
	);
	/**
	 * Possible EU countries tax names
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_EU_TAX_NAMES = array(
		'standard'      => '',
		'oss_union'     => 'OSS (EU)',
		'oss_non_union' => 'OSS (EU)',
		'ioss'          => 'IOSS (EU)',
	);
	/**
	 * Countries with tax type "VAT" and type of registration "Standard"
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_VAT_STANDARD_COUNTRIES = array(
		'AT' => '',
		'ES' => '',
		'BE' => '',
		'BG' => '',
		'HR' => '',
		'CY' => '',
		'CZ' => '',
		'EE' => '',
		'FI' => '',
		'FR' => '',
		'DE' => '',
		'GR' => '',
		'HU' => '',
		'IS' => '',
		'DK' => '',
		'IE' => '',
		'IT' => '',
		'LV' => '',
		'LT' => '',
		'LU' => '',
		'MT' => '',
		'NL' => '',
		'NO' => '',
		'PL' => '',
		'PT' => '',
		'RO' => '',
		'SG' => '',
		'SK' => '',
		'SI' => '',
		'ZA' => '',
		'SE' => '',
		'CH' => '',
		'AE' => '',
		'GB' => '',
	);
	/**
	 * Countries with tax type "VAT" and type of registration "Simplified"
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_VAT_SIMPLIFIED_COUNTRIES = array(
		'CL' => '',
		'CO' => '',
		'ID' => '',
		'MX' => '',
		'SA' => '',
		'KR' => '',
		'TH' => '',
		'TR' => '',
		'VN' => '',
	);
	/**
	 * Countries with tax type "GST" and type of registration "Standard"
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_GST_STANDARD_COUNTRIES = array(
		'AU' => '',
		'NZ' => '',
	);
	/**
	 * Countries with tax type "JCT" and type of registration "Standard"
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_JCT_STANDARD_COUNTRIES = array(
		'JP' => '',
	);
	/**
	 * Countries with tax type "Service Tax" and type of registration "Simplified"
	 *
	 * @var array
	 */
	public const TAX_REGISTRATION_SERVICE_TAX_SIMPLIFIED_COUNTRIES = array(
		'MY' => '',
	);
	/**
	 * List of allowed origin address countries
	 *
	 * @var array
	 */
	public const ORIGIN_ADDRESS_ALLOWED_COUNTRIES = array(
		'AE' => '',
		'AT' => '',
		'AU' => '',
		'BE' => '',
		'BG' => '',
		'CA' => '',
		'CH' => '',
		'CY' => '',
		'CZ' => '',
		'DE' => '',
		'DK' => '',
		'EE' => '',
		'ES' => '',
		'FI' => '',
		'FR' => '',
		'GB' => '',
		'GR' => '',
		'HK' => '',
		'HR' => '',
		'HU' => '',
		'IE' => '',
		'IT' => '',
		'JP' => '',
		'LT' => '',
		'LU' => '',
		'LV' => '',
		'MT' => '',
		'NL' => '',
		'NO' => '',
		'NZ' => '',
		'PL' => '',
		'PT' => '',
		'RO' => '',
		'SE' => '',
		'SG' => '',
		'SI' => '',
		'SK' => '',
		'US' => '',
	);
	/**
	 * List of allowed origin address US states
	 *
	 * @var array
	 */
	public const ORIGIN_ADDRESS_ALLOWED_US_STATES = array(
		'AA' => '',
		'AE' => '',
		'AL' => '',
		'AK' => '',
		'AP' => '',
		'AZ' => '',
		'AR' => '',
		'CA' => '',
		'CO' => '',
		'CT' => '',
		'DE' => '',
		'DC' => '',
		'FL' => '',
		'GA' => '',
		'HI' => '',
		'ID' => '',
		'IL' => '',
		'IN' => '',
		'IA' => '',
		'KS' => '',
		'KY' => '',
		'LA' => '',
		'ME' => '',
		'MD' => '',
		'MA' => '',
		'MI' => '',
		'MN' => '',
		'MS' => '',
		'MO' => '',
		'MT' => '',
		'NE' => '',
		'NV' => '',
		'NH' => '',
		'NJ' => '',
		'NM' => '',
		'NY' => '',
		'NC' => '',
		'ND' => '',
		'OH' => '',
		'OK' => '',
		'OR' => '',
		'PA' => '',
		'RI' => '',
		'SC' => '',
		'SD' => '',
		'TN' => '',
		'TX' => '',
		'UT' => '',
		'VT' => '',
		'VA' => '',
		'WA' => '',
		'WV' => '',
		'WI' => '',
		'WY' => '',
	);
	/**
	 * List of allowed origin address United Arab Emirates provinces
	 *
	 * @var array
	 */
	public const ORIGIN_ADDRESS_ALLOWED_AE_PROVINCES = array(
		'AZ' => 'Abu Dhabi',
		'SH' => 'Sharjah',
		'FU' => 'Fujairah',
		'UQ' => 'Umm Al Quwain',
		'DU' => 'Dubai',
		'RK' => 'Ras al Khaimah',
		'AJ' => 'Ajman',
	);

	/**
	 * ID, used to distinguish, when "customer ID not received" versus "customer ID not exists" (like for guests).
	 *
	 * @var string
	 */
	public const GUEST_CUSTOMER_ID = 'guest';

	/**
	 * Check if country is supported for Stripe Calculate Tax API call
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 *
	 * @return bool true if country supported and false if not.
	 */
	public static function is_country_supported( string $country ): bool {
		return array_key_exists( $country, self::CURRENCIES_PER_SUPPORTED_COUNTRY );
	}

	/**
	 * Returns supported currencies
	 *
	 * @see https://stripe.com/docs/currencies#presentment-currencies
	 * @return array<string, string> Array of supported currencies (as array key) in ISO 4217 alphabetic code lowercase.
	 */
	public static function get_all_supported_currencies(): array {
		return self::CURRENCIES_ALL_SUPPORTED;
	}

	/**
	 * Get supported currencies for the country in ISO 4217 alphabetic code lowercase format
	 *
	 * @param string $country ISO 3166-1 alpha-2 country code.
	 *
	 * @return array<string, string>
	 * @throws Exception In case country is not supported.
	 */
	public static function get_supported_currencies_for_country( string $country ): array {
		if ( ! self::is_country_supported( $country ) ) {
			throw new Exception( esc_html__( 'Country code not supported', 'stripe-tax-for-woocommerce' ) );
		}

		return self::CURRENCIES_PER_SUPPORTED_COUNTRY[ $country ];
	}

	/**
	 * Check if currency is supported
	 *
	 * @param string $currency Currency in ISO 4217 alphabetic code lowercase.
	 *
	 * @return bool true if supported and false otherwise
	 * @see https://stripe.com/docs/currencies#presentment-currencies
	 */
	public static function is_currency_supported( string $currency ): bool {
		return array_key_exists( $currency, self::get_all_supported_currencies() );
	}

	/**
	 * Check if currency is zero decimal
	 *
	 * @param string $currency Currency in ISO 4217 alphabetic code lowercase.
	 *
	 * @return bool true if zero decimal and false otherwise
	 * @throws Exception If currency is not valid.
	 * @see https://stripe.com/docs/currencies?presentment-currency=AE#zero-decimal
	 */
	public static function is_currency_zero_decimal( string $currency ): bool {
		Validate::validate_currency( $currency );

		return array_key_exists( $currency, self::CURRENCIES_ZERO_DECIMAL );
	}

	/**
	 * Check if currency is three decimal
	 *
	 * @param string $currency Currency in ISO 4217 alphabetic code lowercase.
	 *
	 * @return bool true if three decimal and false otherwise
	 * @throws Exception In case of incorrect data.
	 * @see https://stripe.com/docs/currencies#three-decimal
	 */
	public static function is_currency_three_decimal( string $currency ): bool {
		Validate::validate_currency( $currency );

		return array_key_exists( $currency, self::CURRENCIES_THREE_DECIMAL );
	}

	/**
	 * Check if currency is special case round
	 *
	 * @param string $currency Currency in ISO 4217 alphabetic code lowercase.
	 *
	 * @return bool true if special rules applied and false otherwise
	 * @throws Exception If currency is not correct.
	 * @see https://stripe.com/docs/currencies#special-cases
	 */
	public static function is_currency_special_case_round_and_multiply_100( string $currency ): bool {
		Validate::validate_currency( $currency );

		return array_key_exists( $currency, self::CURRENCIES_SPECIAL_CASE_ROUND_AND_MULTIPLY_100 );
	}

	/**
	 * Get allowed tax registration countries
	 *
	 * @return array
	 */
	public static function get_allowed_tax_registration_countries(): array {
		$all_countries = WC()->countries->get_countries();

		return array_intersect_key( $all_countries, self::TAX_REGISTRATION_ALLOWED_COUNTRIES );
	}

	/**
	 * Get allowed tax registration US States
	 *
	 * @return array
	 */
	public static function get_allowed_tax_registration_us_states(): array {
		$all_us_states = WC()->countries->states['US'];

		return array_intersect_key( $all_us_states, self::TAX_REGISTRATION_ALLOWED_US_STATES );
	}

	/**
	 * Get allowed tax registration CA provinces
	 *
	 * @return array
	 */
	public static function get_allowed_tax_registration_ca_provinces(): array {
		$all_ca_provinces = WC()->countries->states['CA'];

		return array_intersect_key( $all_ca_provinces, self::TAX_REGISTRATION_ALLOWED_CA_PROVINCES );
	}

	/**
	 * Get tax registration US states
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_us_states(): array {
		return array_keys( self::TAX_REGISTRATION_ALLOWED_US_STATES );
	}

	/**
	 * Get registration no sales tax US states
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_no_sales_tax_us_states(): array {
		return array_keys( self::TAX_REGISTRATION_NO_SALES_TAX_US_STATES );
	}

	/**
	 * Get tax registration local communications tax US states
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_local_communications_tax_us_states(): array {
		return array_keys( self::TAX_REGISTRATION_LOCAL_COMMUNICATIONS_TAX_US_STATES );
	}

	/**
	 * Get tax registration lease and amusement tax US states
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_lease_and_amusement_tax_us_states(): array {
		return array_keys( self::TAX_REGISTRATION_LEASE_AND_AMUSEMENT_TAX_US_STATES );
	}

	/**
	 * Get tax registration digital countries
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_digital_countries(): array {
		return array_keys( self::TAX_REGISTRATION_DIGITAL_TAX_COUNTRIES );
	}

	/**
	 * Get tax registration EU countries
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_eu_countries(): array {
		return array_keys( self::TAX_REGISTRATION_EU_TAX_COUNTRIES );
	}

	/**
	 * Get tax registration standard tax countries
	 *
	 * @return array
	 */
	public static function get_tax_registration_standard_tax_countries(): array {
		return array_keys( self::TAX_REGISTRATION_STANDARD_TAX_COUNTRIES );
	}

	/**
	 * Get tax registration US tax names
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_us_tax_names(): array {
		return self::TAX_REGISTRATION_US_TAX_NAMES;
	}

	/**
	 * Get tax registration US tax types
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_us_tax_types(): array {
		return self::TAX_REGISTRATION_US_TAX_TYPES;
	}

	/**
	 * Get tax registration US type of registration
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_us_type_of_registration(): array {
		return self::TAX_REGISTRATION_US_TYPE_OF_REGISTRATION;
	}

	/**
	 * Get tax registration CA tax names
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_ca_tax_names(): array {
		return self::TAX_REGISTRATION_CA_TAX_NAMES;
	}

	/**
	 * Get tax registration CA tax types
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_ca_tax_types(): array {
		return self::TAX_REGISTRATION_CA_TAX_TYPES;
	}

	/**
	 * Get tax registration CA type of registration
	 *
	 * @return string[]
	 */
	public static function get_tax_registration_ca_type_of_registration(): array {
		return self::TAX_REGISTRATION_CA_TYPE_OF_REGISTRATION;
	}

	/**
	 * Get tax registration flag image file contents for supported Tax Registrations country
	 *
	 * @param string $country Country.
	 *
	 * @return string
	 */
	public static function get_tax_registration_flag_image_file_contents( string $country ): string {
		$allowed_countries = self::get_allowed_tax_registration_countries();
		$file_name         = STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_IMG_DIR . 'tax_registration_flag_' . strtolower( $country ) . '.svg';

		if ( array_key_exists( strtoupper( $country ), $allowed_countries ) && file_exists( $file_name ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			return file_get_contents( $file_name );
		}

		return '';
	}

	/**
	 * Get tax registration flag EU image file contents
	 *
	 * @return string
	 */
	public static function get_tax_registration_flag_eu_image_file_contents(): string {
		$file_name = STRIPE_TAX_FOR_WOOCOMMERCE_ASSETS_IMG_DIR . 'tax_registration_flag_eu.svg';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return file_get_contents( $file_name );
	}

	/**
	 * Get allowed origin address countries
	 *
	 * @return array
	 */
	public static function get_allowed_origin_address_countries(): array {
		$all_countries = WC()->countries->get_countries();

		return array_intersect_key( $all_countries, self::ORIGIN_ADDRESS_ALLOWED_COUNTRIES );
	}

	/**
	 * Get allowed origin address states for Australia
	 *
	 * @return array
	 */
	public static function get_allowed_origin_address_au_states(): array {
		return WC()->countries->states['AU'];
	}

	/**
	 * Get allowed origin address provinces for Canada
	 *
	 * @return array
	 */
	public static function get_allowed_origin_address_ca_provinces(): array {
		return WC()->countries->states['CA'];
	}

	/**
	 * Get allowed origin address areas for Hong Kong
	 *
	 * @return array
	 */
	public static function get_allowed_origin_address_hk_areas(): array {
		$woo_hk_areas = WC()->countries->states['HK'];

		return array(
			'Hong Kong'       => $woo_hk_areas['HONG KONG'],
			'Kowloon'         => $woo_hk_areas['KOWLOON'],
			'New Territories' => $woo_hk_areas['NEW TERRITORIES'],
		);
	}

	/**
	 * Get allowed origin address counties for Ireland
	 *
	 * @return array
	 */
	public static function get_allowed_origin_address_ie_counties(): array {
		return WC()->countries->states['IE'];
	}

	/**
	 * Get allowed origin address Italy provinces
	 *
	 * @return array
	 */
	public static function get_allowed_origin_address_it_provinces(): array {
		return WC()->countries->states['IT'];
	}

	/**
	 * Get allowed origin address prefectures for Japan
	 *
	 * @return array
	 */
	public static function get_allowed_origin_address_jp_prefectures(): array {
		$woo_jp_prefectures               = WC()->countries->states['JP'];
		$mapped_for_stripe_jp_prefectures = array();
		// Replace from WooCommerce's 'JPXX' array keys to Stripe's 'XX' array keys.
		for ( $i = 1; $i <= 47; $i++ ) {
			$padded_number                                      = sprintf( '%02d', $i );
			$mapped_for_stripe_jp_prefectures[ $padded_number ] = $woo_jp_prefectures[ 'JP' . $padded_number ];
		}

		return $mapped_for_stripe_jp_prefectures;
	}

	/**
	 * Get allowed origin address provinces for Spain
	 *
	 * @return array
	 */
	public static function get_allowed_origin_address_es_provinces(): array {
		return WC()->countries->states['ES'];
	}

	/**
	 * Get allowed origin address provinces for AE
	 *
	 * @return string[]
	 */
	public static function get_allowed_origin_address_ae_provinces(): array {
		return self::ORIGIN_ADDRESS_ALLOWED_AE_PROVINCES;
	}

	/**
	 * Get allowed origin address US states
	 *
	 * @return array
	 */
	public static function get_allowed_origin_address_us_states(): array {
		$all_us_states = WC()->countries->states['US'];

		$allowed_us_states          = array_intersect_key( $all_us_states, self::ORIGIN_ADDRESS_ALLOWED_US_STATES );
		$add_countries_to_us_states = array(
			'AS' => '', // American Samoa.
			'GU' => '', // Guam.
			'MH' => '', // Marshall Islands.
			'FM' => '', // Micronesia.
			'MP' => '', // Northern Mariana Islands.
			'PW' => '', // Palau.
			'PR' => '', // Puerto Rico.
			'VI' => '', // Virgin Islands (US).
		);
		$all_countries              = WC()->countries->get_countries();
		$allowed_added_us_states    = array_intersect_key( $all_countries, $add_countries_to_us_states );

		$result = array_merge( $allowed_us_states, $allowed_added_us_states );

		asort( $result );

		return $result;
	}

	/**
	 * Get countries, where city field called "Town"
	 *
	 * @return string[]
	 */
	public static function get_city_is_town_or_city_countries(): array {
		return array(
			'GB' => '',
			'NO' => '',
			'SE' => '',
		);
	}

	/**
	 * Get countries, where city field called "District"
	 *
	 * @return string[]
	 */
	public static function get_city_is_district_countries(): array {
		return array(
			'HK' => '',
		);
	}

	/**
	 * Get countries where postal code field called "Eircode"
	 *
	 * @return string[]
	 */
	public static function get_postal_code_is_eircode_countries(): array {
		return array(
			'IE' => '',
		);
	}

	/**
	 * Get countries where postal code field called "ZIP"
	 *
	 * @return string[]
	 */
	public static function get_postal_code_is_zip_countries(): array {
		return array(
			'US' => '',
		);
	}

	/**
	 * Get countries where no city field exist
	 *
	 * @return string[]
	 */
	public static function get_no_city_countries(): array {
		return array(
			'AE' => '',
			'SG' => '',
		);
	}

	/**
	 * Get countries where no postal code field exist.
	 *
	 * @return string[]
	 */
	public static function get_no_postal_code_countries(): array {
		return array(
			'AE' => '',
			'HK' => '',
		);
	}

	/**
	 * Checks and fills Stripe Tax Settings object with proper properties
	 *
	 * @param mixed $settings Stripe Tax Settings object to check.
	 *
	 * @return object Filled with proper fields Stripe Tax object
	 */
	public static function fill_stripe_tax_settings_object( $settings ): object {
		if ( ! is_object( $settings ) ) {
			$settings = new \stdClass();
		}

		if ( ! isset( $settings->defaults ) || ! is_object( $settings->defaults ) ) {
			$settings->defaults = new \stdClass();
		}

		if ( empty( $settings->defaults->tax_code ) || ! is_string( $settings->defaults->tax_code ) ) {
			$settings->defaults->tax_code = null;
		}

		if ( ! isset( $settings->head_office ) || ! is_object( $settings->head_office ) ) {
			$settings->head_office = new \stdClass();
		}

		if ( ! isset( $settings->head_office->address ) || ! is_object( $settings->head_office->address ) ) {
			$settings->head_office->address = new \stdClass();
		}

		// City, country, line1, line2 and postal_code cannot be empty string.
		// Regardless if it is an empty string or not a string at all - we make it null.
		if ( empty( $settings->head_office->address->city ) || ! is_string( $settings->head_office->address->city ) ) {
			$settings->head_office->address->city = null;
		}

		if ( empty( $settings->head_office->address->country ) || ! is_string( $settings->head_office->address->country ) ) {
			$settings->head_office->address->country = null;
		}

		if ( empty( $settings->head_office->address->line1 ) || ! is_string( $settings->head_office->address->line1 ) ) {
			$settings->head_office->address->line1 = null;
		}

		if ( empty( $settings->head_office->address->line2 ) || ! is_string( $settings->head_office->address->line2 ) ) {
			$settings->head_office->address->line2 = null;
		}

		if ( empty( $settings->head_office->address->postal_code ) || ! is_string( $settings->head_office->address->postal_code ) ) {
			$settings->head_office->address->postal_code = null;
		}

		return $settings;
	}

	/**
	 * Get report link
	 *
	 * @return string
	 */
	public static function get_report_link(): string {
		return 'https://dashboard.stripe.com/tax/registrations';
	}

	/**
	 * Returns html element where, required field needs to be marked (usually by adding asterisk "*" character).
	 *
	 * @return string
	 */
	public static function get_required_field_mark_html(): string {
		ob_start();
		include STRIPE_TAX_FOR_WOOCOMMERCE_TEMPLATES_PARTS_DIR . 'admin-required-field-mark.php';

		return ob_get_clean();
	}

	/**
	 * Sets transient data flag on Stripe Settings update error
	 *
	 * @return void
	 */
	public static function set_stripe_settings_update_error_flag(): void {
		set_transient( 'stripe_tax_for_woocommerce_settings_update_error_flag', true );
	}

	/**
	 * Returns true if Stripe Settings update error occurred last time
	 *
	 * @return bool true if Stripe Settings update error occurred last time and false otherwise
	 */
	public static function get_stripe_settings_update_error_flag(): bool {
		$flag = get_transient( 'stripe_tax_for_woocommerce_settings_update_error_flag' );
		delete_transient( 'stripe_tax_for_woocommerce_settings_update_error_flag' );

		return $flag;
	}

	/**
	 * Gets cart shipping methods property.
	 *
	 * @param \WC_Cart|\WC_Order $wc_cart_or_order WooCommerce Cart object.
	 *
	 * @return array
	 */
	public static function get_cart_shipping_methods( $wc_cart_or_order ): array {
		if ( $wc_cart_or_order instanceof \WC_Cart ) {
			$cart_reflection           = new \ReflectionObject( $wc_cart_or_order );
			$shipping_methods_property = $cart_reflection->getProperty( 'shipping_methods' );
			$shipping_methods_property->setAccessible( true );
			$value = $shipping_methods_property->getValue( $wc_cart_or_order );
		} elseif ( $wc_cart_or_order instanceof \WC_Order ) {
			$value = $wc_cart_or_order->get_shipping_methods();
		} else {
			return array();
		}

		if ( is_null( $value ) ) {
			return array();
		}

		return $value;
	}

	/**
	 * Gets cart or order taxable shipping methods property.
	 *
	 * @param \WC_Cart|\WC_Order $wc_cart_or_order WooCommerce Cart object.
	 *
	 * @return array
	 */
	public static function get_cart_or_order_taxable_shipping_methods( $wc_cart_or_order ): array {
		$shipping_methods = self::get_cart_shipping_methods( $wc_cart_or_order );

		$taxable_shipping_methods = array();

		if ( $wc_cart_or_order instanceof \WC_Cart ) {
			$packages = $wc_cart_or_order->get_shipping_packages();
			WC()->shipping()->calculate_shipping( $packages );
			$all_shipping_methods = WC()->shipping()->load_shipping_methods( isset( $packages[0] ) ? $packages[0] : array() );
		} elseif ( $wc_cart_or_order instanceof \WC_Order ) {
			WC()->shipping()->load_shipping_methods(
				array(
					'contents'    => array(),
					'user'        => array(
						'ID' => (int) $wc_cart_or_order->get_customer_id( 'edit' ),
					),
					'destination' => array(
						'country'   => $wc_cart_or_order->get_shipping_country( 'edit' ),
						'state'     => $wc_cart_or_order->get_shipping_state( 'edit' ),
						'postcode'  => $wc_cart_or_order->get_shipping_postcode( 'edit' ),
						'city'      => $wc_cart_or_order->get_shipping_city( 'edit' ),
						'address'   => $wc_cart_or_order->get_shipping_address_1( 'edit' ),
						'address_1' => $wc_cart_or_order->get_shipping_address_1( 'edit' ), // Provide both address and address_1 for backwards compatibility.
						'address_2' => $wc_cart_or_order->get_shipping_address_2( 'edit' ),
					),
				)
			);
			$all_shipping_methods = WC()->shipping()->get_shipping_methods();
		}

		foreach ( $shipping_methods as $shipping_method_key => $shipping_method ) {
			if ( ( (int) $shipping_method->get_instance_id() === 0 ) ) {
				$taxable_shipping_methods[ $shipping_method_key ] = $shipping_method;
				continue;
			}
			foreach ( $all_shipping_methods as $selectable_shipping_method ) {

				if ( $wc_cart_or_order instanceof \WC_Cart ) {
					/**
					 * Types in case of WC_Cart.
					 *
					 * @var \WC_Shipping_Rate $shipping_method Shipping rates applied to cart (not provide taxability information).
					 * @var \WC_Shipping_Method $selectable_shipping_method All selectable shipping methods (provide taxability information)
					 */
					if ( (int) $shipping_method->get_instance_id() === (int) $selectable_shipping_method->get_instance_id() && $selectable_shipping_method->is_taxable() ) {
						if ( ! in_array( $shipping_method, $taxable_shipping_methods, true ) ) {
							$taxable_shipping_methods[ $shipping_method_key ] = $shipping_method;
						}
					}
				} elseif ( $wc_cart_or_order instanceof \WC_Order ) {
					/**
					 * Types in case of WC_Order.
					 *
					 * @var \WC_Order_Item_Shipping $shipping_method Shipping methods applied to order.
					 * @var \WC_Shipping_Method $selectable_shipping_method All selectable shipping methods (provide taxability information)
					 */
					$is_shipping_method_taxable = 'taxable' === $selectable_shipping_method->tax_status;

					if ( $is_shipping_method_taxable ) {
						$customer_id = $wc_cart_or_order->get_customer_id();

						if ( $customer_id ) {
							$customer = new \WC_Customer( $customer_id );
							if ( $customer && $customer->get_is_vat_exempt() ) {
								$is_shipping_method_taxable = false;
							}
						}
					}

					if ( (int) $shipping_method->get_instance_id() === (int) $selectable_shipping_method->get_instance_id() && $is_shipping_method_taxable ) {
						if ( ! in_array( $shipping_method, $taxable_shipping_methods, true ) ) {
							$taxable_shipping_methods[ $shipping_method_key ] = $shipping_method;
						}
					}
				}
			}
		}

		return $taxable_shipping_methods;
	}

	/**
	 * Get not taxable shipping methods for cart or order.
	 *
	 * @param \WC_Cart|\WC_Order $wc_cart_or_order Cart or order.
	 *
	 * @return array
	 */
	public static function get_cart_not_taxable_shipping_methods( $wc_cart_or_order ): array {
		$shipping_methods = self::get_cart_shipping_methods( $wc_cart_or_order );

		$not_taxable_shipping_methods = array();

		if ( $wc_cart_or_order instanceof \WC_Cart ) {
			$packages = $wc_cart_or_order->get_shipping_packages();
			WC()->shipping()->calculate_shipping( $packages );
			$all_shipping_methods = WC()->shipping()->load_shipping_methods( isset( $packages[0] ) ? $packages[0] : array() );
		} elseif ( $wc_cart_or_order instanceof \WC_Order ) {
			WC()->shipping()->load_shipping_methods(
				array(
					'contents'    => array(),
					'user'        => array(
						'ID' => (int) $wc_cart_or_order->get_customer_id( 'edit' ),
					),
					'destination' => array(
						'country'   => $wc_cart_or_order->get_shipping_country( 'edit' ),
						'state'     => $wc_cart_or_order->get_shipping_state( 'edit' ),
						'postcode'  => $wc_cart_or_order->get_shipping_postcode( 'edit' ),
						'city'      => $wc_cart_or_order->get_shipping_city( 'edit' ),
						'address'   => $wc_cart_or_order->get_shipping_address_1( 'edit' ),
						'address_1' => $wc_cart_or_order->get_shipping_address_1( 'edit' ), // Provide both address and address_1 for backwards compatibility.
						'address_2' => $wc_cart_or_order->get_shipping_address_2( 'edit' ),
					),
				)
			);
			$all_shipping_methods = WC()->shipping()->get_shipping_methods();
		}

		foreach ( $shipping_methods as $shipping_method_key => $shipping_method ) {
			if ( (int) $shipping_method->get_instance_id() === 0 ) {
				continue;
			}
			foreach ( $all_shipping_methods as $selectable_shipping_method ) {
				if ( $wc_cart_or_order instanceof \WC_Cart ) {
					/**
					 * Types in case of WC_Cart.
					 *
					 * @var \WC_Shipping_Rate $shipping_method Shipping rates applied to cart (not provide taxability information).
					 * @var \WC_Shipping_Method $selectable_shipping_method All selectable shipping methods (provide taxability information)
					 */
					if ( (int) $shipping_method->get_instance_id() === (int) $selectable_shipping_method->get_instance_id() && ! $selectable_shipping_method->is_taxable() ) {
						if ( ! in_array( $shipping_method, $not_taxable_shipping_methods, true ) ) {
							$not_taxable_shipping_methods[ $shipping_method_key ] = $shipping_method;
						}
					}
				} elseif ( $wc_cart_or_order instanceof \WC_Order ) {
					/**
					 * Types in case of WC_Order.
					 *
					 * @var \WC_Order_Item_Shipping $shipping_method Shipping methods applied to order.
					 * @var \WC_Shipping_Method $selectable_shipping_method All selectable shipping methods (provide taxability information)
					 */
					$is_shipping_method_taxable = 'taxable' === $selectable_shipping_method->tax_status;

					if ( $is_shipping_method_taxable ) {
						$customer_id = $wc_cart_or_order->get_customer_id();

						if ( $customer_id ) {
							$customer = new \WC_Customer( $customer_id );
							if ( $customer && $customer->get_is_vat_exempt() ) {
								$is_shipping_method_taxable = false;
							}
						}
					}
					if ( (int) $shipping_method->get_instance_id() === (int) $selectable_shipping_method->get_instance_id() && ! $is_shipping_method_taxable ) {
						if ( ! in_array( $shipping_method, $not_taxable_shipping_methods, true ) ) {
							$not_taxable_shipping_methods[ $shipping_method_key ] = $shipping_method;
						}
					}
				}
			}
		}

		return $not_taxable_shipping_methods;
	}

	/**
	 * Sets cart shipping methods property.
	 *
	 * @param \WC_Cart $wc_cart WooCommerce Cart object.
	 * @param array    $shipping_methods Array of shipping methods.
	 */
	public static function set_cart_shipping_methods( \WC_Cart $wc_cart, array $shipping_methods ) {
		$cart_reflection           = new \ReflectionObject( $wc_cart );
		$shipping_methods_property = $cart_reflection->getProperty( 'shipping_methods' );
		$shipping_methods_property->setAccessible( true );

		$shipping_methods_property->setValue( $wc_cart, $shipping_methods );
	}

	/**
	 * Allowed html tags for escaping.
	 */
	public static function get_admin_allowed_html() {
		$allowed_html = array(
			'a'          => array(
				'href'   => true,
				'title'  => true,
				'target' => true,
			),
			'b'          => true,
			'strong'     => true,
			'i'          => true,
			'em'         => true,
			'p'          => true,
			'br'         => true,
			'ul'         => true,
			'ol'         => array(
				'start' => true,
				'type'  => true,
			),
			'li'         => true,
			'h1'         => true,
			'h2'         => true,
			'h3'         => true,
			'h4'         => true,
			'h5'         => true,
			'h6'         => true,
			'img'        => array(
				'src'    => true,
				'alt'    => true,
				'title'  => true,
				'width'  => true,
				'height' => true,
			),
			'span'       => array(
				'class' => true,
				'style' => true,
			),
			'div'        => array(
				'class' => true,
				'style' => true,
			),
			'blockquote' => array(
				'cite' => true,
			),
			'code'       => true,
			'pre'        => true,
		);
		return $allowed_html;
	}

	/**
	 * Allowed svg tags for escaping.
	 */
	public static function get_admin_allowed_svg_tags() {
		$allowed_svg = array(
			'svg'     => array(
				'class'       => true,
				'xmlns'       => true,
				'xmlns:xlink' => true,
				'x'           => true,
				'y'           => true,
				'viewbox'     => true,
				'style'       => array(
					'type'              => true,
					'enable-background' => true,
					'position'          => true,
					'top'               => true,
					'width'             => true,
					'height'            => true,
				),
				'xml:space'   => true,
			),
			'style'   => array(
				'type' => true,
			),
			'g'       => true,
			'path'    => array(
				'class' => true,
				'd'     => true,
			),
			'polygon' => array(
				'class'  => true,
				'points' => true,
			),
			'rect'    => array(
				'x'      => true,
				'y'      => true,
				'class'  => true,
				'width'  => true,
				'height' => true,
			),
		);
		return $allowed_svg;
	}

	/**
	 * Function does exit. Useful, when PHPUnit unit test needs to check "exit" calls.
	 *
	 * @return void
	 */
	public static function do_exit() {
		exit;
	}
}
