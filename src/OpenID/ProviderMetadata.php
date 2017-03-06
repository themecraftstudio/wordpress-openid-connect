<?php


namespace Themecraft\WordPress\OpenIDConnect\OpenID;


use GuzzleHttp\Client;
use Themecraft\WordPress\OpenIDConnect\Exception\UnknownProviderException;
use Themecraft\WordPress\OpenIDConnect\Settings;

class ProviderMetadata
{
	/** @var array */
	private $metadata;

	/** @var  string */
	private $issuer;

	public function __construct($issuer) {
		// Issuer location returned by WebFinder must match 'issuer' in metadata
		// and the 'iss' claim value in ID Tokens from this issuer.
		// Therefore:
		//   a) must be a URL using the https scheme with no query or fragment component that the OP
		//      asserts as its Issuer Identifier.
		//   b) The returned Issuer location MUST be a URI with a scheme component that MUST be https,
		//      a host component, and optionally, port and path components and no query or fragment components.	}
		$schema = 'https://';
		if (substr($issuer, 0, strlen($schema)) !== $schema)
			throw new \Exception('Issuer URI must have an https schema');

		$this->issuer = $issuer;
	}
		/**
	 * Returns provider metadata
	 */
	private function fetch()
	{
		$openidConfigurationURI = $this->issuer .'/.well-known/openid-configuration';

		$client = new Client();
		$result = $client->request('GET', $openidConfigurationURI);
		$this->metadata = json_decode($result->getBody(), true);
	}

	protected function getMetadata(string $value)
	{
		if (!$this->metadata)
			$this->fetch();

		if (!array_key_exists($value, $this->metadata))
			return null;

		return $this->metadata[$value];
	}

	protected function hasMetadata(string $value): bool
	{
		return $this->getMetadata($value) !== null;
	}

	/**
	 * REQUIRED
	 * @return array|null
	 */
	public function getSupportedResponseTypes(): array
	{
		return $this->getMetadata('response_types_supported') ?? [];
	}

	/**
	 * RECOMMENDED
	 * @return array|null
	 */
	public function getSupportedClaims(): array
	{
		return $this->getMetadata('claims_supported') ?? [];
	}

	/**
	 * RECOMMENDED
	 * @return array|null
	 */
	public function getSupportedScopes(): array
	{
		return $this->getMetadata('scopes_supported') ?? [];
	}

	/**
	 * REQUIRED unless only Implicit Flow is used
	 * @return null|string
	 */
	public function getTokenEndpoint(): string
	{
		return $this->getMetadata('token_endpoint');
	}

	/**
	 * REQUIRED
	 * @return string
	 */
	public function getAuthorizationEndpoint(): string
	{
		return $this->metadata['authorization_endpoint'];
	}

	/**
	 * REQUIRED
	 */
	public function getIssuer(): string
	{
		return $this->metadata['issuer'];
	}

	/**
	 * OPTIONAL
	 */
	public function getTokenEndpointSupportedAuthMethods(): array
	{
		return $this->metadata['token_endpoint_auth_methods_supported'];
	}
}
