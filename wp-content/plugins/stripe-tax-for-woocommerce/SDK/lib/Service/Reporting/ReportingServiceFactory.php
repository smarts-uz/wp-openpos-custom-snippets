<?php

// File generated from our OpenAPI spec

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Service\Reporting;

/**
 * Service factory class for API resources in the Reporting namespace.
 *
 * @property ReportRunService $reportRuns
 * @property ReportTypeService $reportTypes
 */
class ReportingServiceFactory extends \Stripe\StripeTaxForWooCommerce\SDK\lib\Service\AbstractServiceFactory {

	/**
	 * @var array<string, string>
	 */
	private static $classMap = array(
		'reportRuns'  => ReportRunService::class,
		'reportTypes' => ReportTypeService::class,
	);

	protected function getServiceClass( $name ) {
		return \array_key_exists( $name, self::$classMap ) ? self::$classMap[ $name ] : null;
	}
}
