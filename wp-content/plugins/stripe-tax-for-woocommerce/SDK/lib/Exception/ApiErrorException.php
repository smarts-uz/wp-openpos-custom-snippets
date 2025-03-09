<?php

namespace Stripe\StripeTaxForWooCommerce\SDK\lib\Exception;

/**
 * Implements properties and methods common to all (non-SPL) Stripe\StripeTaxForWooCommerce\SDK\lib exceptions.
 */
abstract class ApiErrorException extends \Exception implements ExceptionInterface {

	protected $error;
	protected $httpBody;
	protected $httpHeaders;
	protected $httpStatus;
	protected $jsonBody;
	protected $requestId;
	protected $stripeCode;

	/**
	 * Creates a new API error exception.
	 *
	 * @param string                                                                       $message the exception message
	 * @param null|int                                                                     $httpStatus the HTTP status code
	 * @param null|string                                                                  $httpBody the HTTP body as a string
	 * @param null|array                                                                   $jsonBody the JSON deserialized body
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\CaseInsensitiveArray $httpHeaders the HTTP headers array
	 * @param null|string                                                                  $stripeCode the Stripe\StripeTaxForWooCommerce\SDK\lib error code
	 *
	 * @return static
	 */
	public static function factory(
		$message,
		$httpStatus = null,
		$httpBody = null,
		$jsonBody = null,
		$httpHeaders = null,
		$stripeCode = null
	) {
		$instance = new static( $message );
		$instance->setHttpStatus( $httpStatus );
		$instance->setHttpBody( $httpBody );
		$instance->setJsonBody( $jsonBody );
		$instance->setHttpHeaders( $httpHeaders );
		$instance->setStripeCode( $stripeCode );

		$instance->setRequestId( null );
		if ( $httpHeaders && isset( $httpHeaders['Request-Id'] ) ) {
			$instance->setRequestId( $httpHeaders['Request-Id'] );
		}

		$instance->setError( $instance->constructErrorObject() );

		return $instance;
	}

	/**
	 * Gets the Stripe\StripeTaxForWooCommerce\SDK\lib error object.
	 *
	 * @return null|\Stripe\StripeTaxForWooCommerce\SDK\lib\ErrorObject
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Sets the Stripe\StripeTaxForWooCommerce\SDK\lib error object.
	 *
	 * @param null|\Stripe\StripeTaxForWooCommerce\SDK\lib\ErrorObject $error
	 */
	public function setError( $error ) {
		$this->error = $error;
	}

	/**
	 * Gets the HTTP body as a string.
	 *
	 * @return null|string
	 */
	public function getHttpBody() {
		return $this->httpBody;
	}

	/**
	 * Sets the HTTP body as a string.
	 *
	 * @param null|string $httpBody
	 */
	public function setHttpBody( $httpBody ) {
		$this->httpBody = $httpBody;
	}

	/**
	 * Gets the HTTP headers array.
	 *
	 * @return null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\CaseInsensitiveArray
	 */
	public function getHttpHeaders() {
		return $this->httpHeaders;
	}

	/**
	 * Sets the HTTP headers array.
	 *
	 * @param null|array|\Stripe\StripeTaxForWooCommerce\SDK\lib\Util\CaseInsensitiveArray $httpHeaders
	 */
	public function setHttpHeaders( $httpHeaders ) {
		$this->httpHeaders = $httpHeaders;
	}

	/**
	 * Gets the HTTP status code.
	 *
	 * @return null|int
	 */
	public function getHttpStatus() {
		return $this->httpStatus;
	}

	/**
	 * Sets the HTTP status code.
	 *
	 * @param null|int $httpStatus
	 */
	public function setHttpStatus( $httpStatus ) {
		$this->httpStatus = $httpStatus;
	}

	/**
	 * Gets the JSON deserialized body.
	 *
	 * @return null|array<string, mixed>
	 */
	public function getJsonBody() {
		return $this->jsonBody;
	}

	/**
	 * Sets the JSON deserialized body.
	 *
	 * @param null|array<string, mixed> $jsonBody
	 */
	public function setJsonBody( $jsonBody ) {
		$this->jsonBody = $jsonBody;
	}

	/**
	 * Gets the Stripe\StripeTaxForWooCommerce\SDK\lib request ID.
	 *
	 * @return null|string
	 */
	public function getRequestId() {
		return $this->requestId;
	}

	/**
	 * Sets the Stripe\StripeTaxForWooCommerce\SDK\lib request ID.
	 *
	 * @param null|string $requestId
	 */
	public function setRequestId( $requestId ) {
		$this->requestId = $requestId;
	}

	/**
	 * Gets the Stripe\StripeTaxForWooCommerce\SDK\lib error code.
	 *
	 * Cf. the `CODE_*` constants on {@see \Stripe\StripeTaxForWooCommerce\SDK\lib\ErrorObject} for possible
	 * values.
	 *
	 * @return null|string
	 */
	public function getStripeCode() {
		return $this->stripeCode;
	}

	/**
	 * Sets the Stripe\StripeTaxForWooCommerce\SDK\lib error code.
	 *
	 * @param null|string $stripeCode
	 */
	public function setStripeCode( $stripeCode ) {
		$this->stripeCode = $stripeCode;
	}

	/**
	 * Returns the string representation of the exception.
	 *
	 * @return string
	 */
	public function __toString() {
		$parentStr = parent::__toString();
		$statusStr = ( null === $this->getHttpStatus() ) ? '' : "(Status {$this->getHttpStatus()}) ";
		$idStr     = ( null === $this->getRequestId() ) ? '' : "(Request {$this->getRequestId()}) ";

		return "Error sending request to Stripe\StripeTaxForWooCommerce\SDK\lib: {$statusStr}{$idStr}{$this->getMessage()}\n{$parentStr}";
	}

	protected function constructErrorObject() {
		if ( null === $this->jsonBody || ! \array_key_exists( 'error', $this->jsonBody ) ) {
			return null;
		}

		return \Stripe\StripeTaxForWooCommerce\SDK\lib\ErrorObject::constructFrom( $this->jsonBody['error'] );
	}
}
