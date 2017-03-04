<?php


namespace Themecraft\WordPress\OpenIDConnect\Exception;


class UnknownProviderException extends \Exception {

	/**
	 * UnknownProviderException constructor.
	 *
	 * @param $issuer
	 */
	public function __construct( $issuer ) {
		parent::__construct(sprintf('Provider %s has not been configured', $issuer));
	}

}
