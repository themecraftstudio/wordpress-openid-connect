<?php

namespace Themecraft\WordPress\OpenIDConnect\OpenID;

use Exception;
use GuzzleHttp\Client;
use Themecraft\WordPress\OpenIDConnect\Exception\UnknownProviderException;
use Themecraft\WordPress\OpenIDConnect\Settings;

class Provider
{
	/** @var string */
	private $issuer;

	/** @var  string */
	private $clientId;

	/** @var string */
	private $clientSecret;

	/** @var array */
	protected $metadata;

	/** @var  string */
	protected $accessToken;

	public function __construct(string $issuer, ?string $clientId, ?string $clientSecret) {
		$this->issuer = $issuer;
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
	}

	/**
	 * @param $issuer
	 * @return Provider
	 * @throws UnknownProviderException
	 */
	public static function getByIssuer( $issuer ): Provider {
		if (!Settings::hasProvider($issuer))
			throw new UnknownProviderException($issuer);

		$settings = Settings::getProviderSettings($issuer);

		return new Provider($issuer, $settings['client_id'], $settings['client_secret']);
	}

	public function supportsResponseType(string $type): bool
	{
		// response_types_supported REQUIRED by spec
		return in_array($type, $this->getMetadata()['response_types_supported']);
	}

	public function supportsClaim(string $claim): ?bool
	{
		// claims_supported RECOMMENDED by spec
		if (!array_key_exists('claims_supported', $this->getMetadata()))
			return null;

		return in_array($claim, $this->getMetadata()['claims_supported']);
	}

	public function supportsScope(string $scope): ?bool
	{
		// scopes_supported RECOMMENDED by spec
		if (!array_key_exists('scopes_supported', $this->getMetadata()))
			return null;

		return in_array($scope, $this->getMetadata()['scopes_supported']);
	}

	private function getMetadata(): array
	{
		if ($this->metadata)
			return $this->metadata;

		$openidConfigurationURI = $this->getIssuer() . '/.well-known/openid-configuration';

		$client = new Client();
		$result = $client->request('GET', $openidConfigurationURI);
		$this->metadata = json_decode($result->getBody(), true);

		return $this->metadata;
	}

	protected function getTokenEndpoint(): ?string
	{
		return in_array('token_endpoint', $this->getMetadata()) ? $this->getMetadata()['token_endpoint'] : null;
	}

	public function getAccessToken(?string $code): string
	{
		if ($this->accessToken && $this->accessTokenIsValid())
			return $this->accessToken;

		// check openid configuration supports 'client_secret_basic' in 'token_endpoint_auth_methods_supported'
		// else check for 'client_secret_post'

		if (!$this->supportsTokenEndpointAuthMethod('client_secret_post'))
			// we are screwed
			throw new Exception('client_secret_post not supported by the token endpoint. So sad.');

		$client = new Client();
		$result = $client->post($this->getTokenEndpoint(), [ 'form_params' => [
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => site_url() . '/wp-login.php',
			'client_id' => $this->getClientId(),
			'client_secret' => $this->getClientSecret()
		]]);
		$result = json_decode($result->getBody());

		if ($result->token_type !== 'Bearer')
			throw new Exception('Expecting token_type to be Bearer, got '. $result->token_type .' instead');

		$accessToken = $result->access_token;
		$idToken = $result->id_token;

		if (empty($accessToken) || empty($idToken))
			throw new Exception('empty access token or id token');

		// Validate access token and id tokens as per 3.1.3.5

		// decode id token and check whether there is the email
		// alternatively use the userinfo endpoint
		// FAIL: there should not be a path for which we can't fetch the email
		//       as preconditions where checked in login_form_login action ('email' is in claims_supported or scopes_supported)


		return $this->accessToken = $accessToken;
	}

	public function getIssuer(): string
	{
		return $this->issuer;
	}

	public function getClientId(): ?string
	{
		return $this->clientId;
	}

	public function getClientSecret(): ?string
	{
		return $this->clientSecret;
	}

	public function getAuthorizationEndpoint()
	{
		// authorization_endpoint REQUIRED by spec
		return $this->getMetadata()['authorization_endpoint'];
	}

	public function supportsTokenEndpointAuthMethod(string $method): ?bool
	{
		// token_endpoint_auth_methods_supported OPTIONAL by spec
		if (!array_key_exists('token_endpoint_auth_methods_supported', $this->getMetadata()))
			return null;

		return in_array($method, $this->getMetadata());
	}

	private function accessTokenIsValid(): bool
	{
		return true;
	}
}
