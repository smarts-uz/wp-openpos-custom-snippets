<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Exception;

/**
 * ApiConnection is thrown in the event that the SDK can't connect to Stripe\StripeTaxForWooCommerce\SDK\lib's
 * servers. That can be for a variety of different reasons from a downed
 * network to a bad TLS certificate.
 */
class ApiConnectionException extends ApiErrorException {

}
